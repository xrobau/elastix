<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2008/01/04 10:39:57 bmacias Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoJSON.class.php";
    include_once "libs/misc.lib.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoEndPoint.class.php";
    include_once "modules/$module_name/libs/paloSantoFileEndPoint.class.php";

    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);    

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig     = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP      = $pConfig->leer_configuracion(false);
    $dsnAsterisk = $arrAMP['AMPDBENGINE']['valor']."://". 
                   $arrAMP['AMPDBUSER']['valor']. ":". 
                   $arrAMP['AMPDBPASS']['valor']. "@".
                   $arrAMP['AMPDBHOST']['valor'];
    $dsnSqlite   = $arrConfModule['dsn_conn_database_1'];

    if(isset($_POST["endpoint_scan"])) $accion ="endpoint_scan";
    else if(isset($_POST["endpoint_set"])) $accion ="endpoint_set";
    else if(isset($_POST["endpoint_unset"])) $accion ="endpoint_unset";
    else if(isset($_POST["action"])) $accion = $_POST["action"];
    else $accion ="endpoint_show";
    $content = "";

    // Asegurarse de que el arreglo siempre exista, aunque esté vacío
    if (!isset($_SESSION['elastix_endpoints']))
        $_SESSION['elastix_endpoints'] = array();

    switch($accion){ 
        case "endpoint_scan":
            $content = endpointScan($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf);
            break;
        case "endpoint_set":
            $content = endpointConfiguratedSet($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf);
            break;
        case "endpoint_unset":
            $content = endpointConfiguratedUnset($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf);
            break;
	case "getDevices":
	    $content = getDevices($dsnAsterisk,$dsnSqlite);
	    break;
        default: // endpoint_show            
            $content = buildReport($_SESSION['elastix_endpoints'], $smarty, $module_name, $arrLang, network());
            break;
    }
    return $content;
}

function endpointConfiguratedShow($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf)
{
    $arrData = array();
    if(!isset($_SESSION['elastix_endpoints']) || !is_array($_SESSION['elastix_endpoints']) || empty($_SESSION['elastix_endpoints'])){
        $paloEndPoint        = new paloSantoEndPoint($dsnAsterisk,$dsnSqlite);
        $arrEndpointsConf    = $paloEndPoint->listEndpointConf();
        $arrVendor           = $paloEndPoint->listVendor();
        $arrDeviceFreePBX    = $paloEndPoint->getDeviceFreePBX();
	$arrDeviceFreePBXAll = $paloEndPoint->getDeviceFreePBX(true);
        $endpoint_mask       = isset($_POST['endpoint_mask'])?$_POST['endpoint_mask']:network();
        $pValidator          = new PaloValidar();

        if(!$pValidator->validar('endpoint_mask', $endpoint_mask, 'ip/mask')){
            $smarty->assign("mb_title",$arrLang['ERROR'].":");
            $strErrorMsg = "";
            if(is_array($pValidator->arrErrores) && count($pValidator->arrErrores) > 0){
                foreach($pValidator->arrErrores as $k=>$v) {
                    $strErrorMsg .= "$k, ";
                }
            }
            $smarty->assign("mb_message",$arrLang['Invalid Format in Parameter'].": ".$strErrorMsg);
        }else{

	    $pattonDevices    = $paloEndPoint->getPattonDevices();
            $arrEndpointsMap  = $paloEndPoint->endpointMap($endpoint_mask,$arrVendor,$arrEndpointsConf,$pattonDevices);

            if($arrEndpointsMap==false){
                $smarty->assign("mb_title",$arrLang['ERROR'].":");
                $smarty->assign("mb_message",$paloEndPoint->errMsg);
            }

            if(is_array($arrEndpointsMap) && count($arrEndpointsMap)>0){
                foreach($arrEndpointsMap as $key => $endspoint){
		    if(isset($endspoint['model_no']) && $endspoint['model_no'] != ""){
			if($paloEndPoint->modelSupportIAX($endspoint['model_no']))
			    $comboDevices = combo($arrDeviceFreePBXAll,$endspoint['account']);
			else
			    $comboDevices = combo($arrDeviceFreePBX,$endspoint['account']);
		    }
		    else
			$comboDevices = combo(array("Select a model" => _tr("Select a model")),"");
                    if($endspoint['configurated']){
                        $unset  = "<input type='checkbox' name='epmac_{$endspoint['mac_adress']}'  />";
                        $report = $paloEndPoint->compareDevicesAsteriskSqlite($endspoint['account']);
                    }
                    else{
                        $unset  = "";
                    }
                    if($endspoint['desc_vendor'] == "Unknown")
                        $endspoint['desc_vendor'] = $paloEndPoint->getDescription($endspoint['name_vendor']);
		    $macWithout2Points = str_replace(":","",$endspoint['mac_adress']);
                    $currentExtension = $paloEndPoint->getExtension($endspoint['ip_adress']);

		    if($endspoint["name_vendor"] == "Patton"){
			$arrTmp[0] = "";
			$arrTmp[1] = "";
			$arrTmp[5] = $endspoint["model_no"];
			$arrTmp[6] = _tr("Not Applicable");
			$arrTmp[7] = _tr("Not Applicable");
		    }
		    else{
			$arrTmp[0] = "<input type='checkbox' name='epmac_{$endspoint['mac_adress']}'  />";
			$arrTmp[1] = $unset;
			$arrTmp[5] = "<select name='id_model_device_{$endspoint['mac_adress']}' onchange='getDevices(this,\"$macWithout2Points\");'>".combo($paloEndPoint->getAllModelsVendor($endspoint['name_vendor']),$endspoint['model_no'])."</select>";
			$arrTmp[6] = "<select name='id_device_{$endspoint['mac_adress']}' id='id_device_$macWithout2Points'   >$comboDevices</select>";
			if($currentExtension != "Not Registered")
			    $arrTmp[7] = "<font color = 'green'>$currentExtension</font>";
			else
			    $arrTmp[7] = $currentExtension;
		    }

                    $arrTmp[2] = $endspoint['mac_adress'];
                    $arrTmp[3] = "<a href='http://{$endspoint['ip_adress']}/' target='_blank'>{$endspoint['ip_adress']}</a><input type='hidden' name='ip_adress_endpoint_{$endspoint['mac_adress']}' value='{$endspoint['ip_adress']}' />";
                    $arrTmp[4] = $endspoint['name_vendor']." / ".$endspoint['desc_vendor']."&nbsp;<input type='hidden' name='id_vendor_device_{$endspoint['mac_adress']}' value='{$endspoint['id_vendor']}' />&nbsp;<input type='hidden' name='name_vendor_device_{$endspoint['mac_adress']}' value='{$endspoint['name_vendor']}' />";
                  
                    $arrData[] = $arrTmp;
                }
                $_SESSION['elastix_endpoints'] = $arrData; 
                //Lo guardo en la session para hacer mucho mas rapido el proceso 
                //de configuracion de los endpoint. Solo la primera vez corre el 
                //comado nmap y cuando quiera el usuario correrlo de nuevo lo debe 
                //hacer por medio del boton Discover Endpoints in this Network, ahi de nuevo vuelve a 
                //construir el arreglo $arrData.
            }
        }
    }
    else{
        $arrData = $_SESSION['elastix_endpoints'];
    }
    return buildReport($arrData,$smarty,$module_name,$arrLang, $endpoint_mask);
}

function getDevices($dsnAsterisk,$dsnSqlite)
{
    $jsonObject    = new PaloSantoJSON();
    $paloEndPoint  = new paloSantoEndPoint($dsnAsterisk,$dsnSqlite);
    $idModel	   = getParameter("id_model");
    if($idModel == "unselected")
	$jsonObject->set_message(array("Select a model" => _tr("Select a model")));
    else{
	$iaxSupport	   = $paloEndPoint->modelSupportIAX($idModel);
	if($iaxSupport === null)
	    $jsonObject->set_error("yes");
	else{
	    if($iaxSupport)
		$jsonObject->set_message($paloEndPoint->getDeviceFreePBX(true));
	    else
		$jsonObject->set_message($paloEndPoint->getDeviceFreePBX());
	}
    }
    return $jsonObject->createJSON();
}

function buildReport($arrData, $smarty, $module_name, $arrLang, $endpoint_mask)
{
    $nav = (isset($_GET['nav']) && $_GET['nav'] != '') 
        ? $_GET['nav'] 
        : ((isset($_GET['navpost']) && $_GET['navpost'] != '')
            ? $_GET['navpost'] : NULL);
    $start = (isset($_GET['start']) && $_GET['start'] != '') 
        ? $_GET['start'] 
        : ((isset($_GET['startpost']) && $_GET['startpost'] != '')
            ?$_GET['startpost'] : NULL);

    $ip = $_SERVER['SERVER_ADDR'];
    $devices = subMask($ip);
    $limit  = 20;
    $total  = count($arrData); 
    $oGrid  = new paloSantoGrid($smarty);
    $offset = $oGrid->getOffSet($limit,$total,$nav,$start);
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    if($devices<=20){
       $devices = pow(2,(32-$devices));
       $smarty->assign("mb_title",$arrLang['WARNING'].":");
       $smarty->assign("mb_message",$arrLang["It can take several minutes, because your ip address has some devices, "].$devices);
    }

    if ($total <= $limit)
        $arrDataPorcion = $arrData;
    else $arrDataPorcion = array_slice($arrData, $offset, $limit);

    $arrGrid = array("title"    => $arrLang["Endpoint Configurator"],
        "url"      => array(
            'menu' => $module_name,
            'navpost' => $nav,
            'startpost' => $start,
            ),
        "icon"     => "images/endpoint.png",
        "width"    => "99%",
        "start"    => ($total==0) ? 0 : $offset + 1,
        "end"      => $end,
        "total"    => $total,
        "columns"  => array(0 => array("name"      => "<input type='submit' name='endpoint_set' value='{$arrLang['Set']}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to set endpoint(s)?"]}');\" />",
                                       "property1" => ""),
                            1 => array("name"      => "<input type='submit' name='endpoint_unset' value='{$arrLang['Unset']}' class='button' onclick=\" return confirmSubmit('{$arrLang["Are you sure you wish to unset endpoint(s)?"]}');\" />",
                                       "property1" => ""),
                            2 => array("name"      => $arrLang["MAC Adress"],
                                       "property1" => ""),
                            3 => array("name"      => $arrLang["IP Adress"],
                                       "property1" => ""),
                            4 => array("name"      => $arrLang["Vendor"],
                                       "property1" => ""),
                            5 => array("name"      => $arrLang["Phone Type"], 
                                       "property1" => ""),
                            6 => array("name"      => $arrLang["User Extension"],
                                       "property1" => ""),
                            7 => array("name"      => $arrLang["Current Extension"],
                                       "property1" => "")));
    $html_filter = "<input type='submit' name='endpoint_scan' value='{$arrLang['Discover Endpoints in this Network']}' class='button' />";
    $html_filter.= "&nbsp;&nbsp;<input type='text' name='endpoint_mask' value='$endpoint_mask' style='text-align:right; width:90px;' />";
    $oGrid->showFilter($html_filter);
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrDataPorcion,$arrLang);
    return $contenidoModulo;
}

function endpointScan($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf)
{
    unset($_SESSION['elastix_endpoints']);
    return endpointConfiguratedShow($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf);
}

function endpointConfiguratedSet($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf)
{
    $paloEndPoint     = new paloSantoEndPoint($dsnAsterisk,$dsnSqlite);
    $paloFileEndPoint = new PaloSantoFileEndPoint($arrConf["tftpboot_path"]);
    $arrFindVendor    = array(); //variable de ayuda, para llamar solo una vez la funcion createFilesGlobal de cada vendor
    $valid = validateParameterEndpoint($_POST, $module_name,$dsnAsterisk,$dsnSqlite);
    if($valid!=false){
        $smarty->assign("mb_title",$arrLang['ERROR'].":");
        $smarty->assign("mb_message",$valid);
        $endpoint_mask = isset($_POST['endpoint_mask'])?$_POST['endpoint_mask']:network();

        return buildReport($_SESSION['elastix_endpoints'],$smarty,$module_name,$arrLang, $endpoint_mask);
    }
    foreach($_POST as $key => $values){
        if(substr($key,0,6) == "epmac_"){ //encontre una mac seleccionada entoces por forma empirica con ayuda del mac_adress obtego los parametros q se relacionan con esa mac.
            $tmpMac = substr($key,6);
	    $tech   = $paloEndPoint->getTech($_POST["id_device_$tmpMac"]);
            $freePBXParameters = $paloEndPoint->getDeviceFreePBXParameters($_POST["id_device_$tmpMac"],$tech);

            $tmpEndpoint['id_device']   = $freePBXParameters['id_device'];
            $tmpEndpoint['desc_device'] = $freePBXParameters['desc_device'];
            $tmpEndpoint['account']     = $freePBXParameters['account_device'];
            $tmpEndpoint['secret']      = $freePBXParameters['secret_device'];
            $tmpEndpoint['id_model']    = $_POST["id_model_device_$tmpMac"];
            $tmpEndpoint['mac_adress']  = $tmpMac;
            $tmpEndpoint['id_vendor']   = $_POST["id_vendor_device_$tmpMac"];
            $tmpEndpoint['name_vendor'] = $_POST["name_vendor_device_$tmpMac"];
            $tmpEndpoint['ip_adress']   = $_POST["ip_adress_endpoint_$tmpMac"];
            $tmpEndpoint['comment']     = "Nada";
      
            //Variables usadas para parametros extras
            $name_model = $paloEndPoint->getModelById($tmpEndpoint['id_model']);
            $arrParametersOld = $paloEndPoint->getParameters($tmpEndpoint['mac_adress']);
            $arrParameters = $paloFileEndPoint->updateArrParameters($tmpEndpoint['name_vendor'], $name_model, $arrParametersOld);
            $tmpEndpoint['arrParameters']=$arrParameters;

            if($paloEndPoint->createEndpointDB($tmpEndpoint)){
                //verifico si la funcion createFilesGlobal del vendor ya fue ejecutado
                if(!in_array($tmpEndpoint['name_vendor'],$arrFindVendor)){
                    if($paloFileEndPoint->createFilesGlobal($tmpEndpoint['name_vendor']))
                        $arrFindVendor[] = $tmpEndpoint['name_vendor'];
                }
                //escribir archivos
                $ArrayData['vendor'] = $tmpEndpoint['name_vendor'];
                $ArrayData['data'] = array(
                        "filename"     => strtolower(str_replace(":","",$tmpMac)),
                        "DisplayName"  => $tmpEndpoint['desc_device'],
                        "id_device"    => $tmpEndpoint['id_device'],
                        "secret"       => $tmpEndpoint['secret'],
                        "model"        => $name_model,
                        "ip_endpoint"  => $tmpEndpoint['ip_adress'],
                        "arrParameters"=> $tmpEndpoint['arrParameters'],
			"tech"	       => $tech
                        );

                //Falta si hay error en la creacion de un archivo, ya esta para saber q error es, el problema es como manejar un error o los errores dentro del este lazo (foreach).
                //ejemplo: if($paloFile->createFiles($ArrayData)==false){ $paloFile->errMsg  (mostrar error con smarty)}
                $paloFileEndPoint->createFiles($ArrayData);
            }
        }
    }
    $smarty->assign("mb_title", _tr("MESSAGE"));
    $smarty->assign("mb_message", _tr("The Extension(s) parameters have been saved. Each checked phone will be configured with the new parameters once it has finished rebooting"));
    unset($_SESSION['elastix_endpoints']);
    return endpointConfiguratedShow($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf);
}

function validateParameterEndpoint($arrParameters, $module_name, $dsnAsterisk, $dsnSqlite)
{
    // Listar todos los proveedores disponibles
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $sVendorCfgDir = "$base_dir/modules/$module_name/libs/vendors";
    if (!is_dir($sVendorCfgDir)) {
        return _tr('Vendor configuration directory not found!');
    }
    $h = opendir($sVendorCfgDir);
    $vendorList = array();
    while (($s = readdir($h)) !== false) {
        $regs = NULL;
        if (preg_match('/^(.+)\.cfg\.php/', $s, $regs)) $vendorList[] = $regs[1];
    }
    closedir($h);

    $paloEndPoint = new paloSantoEndPoint($dsnAsterisk,$dsnSqlite);
    $arrDeviceFreePBX    = $paloEndPoint->getDeviceFreePBX();
    $arrDeviceFreePBXAll = $paloEndPoint->getDeviceFreePBX(true);
    $error = false;
    foreach($arrParameters as $key => $values){
        if(substr($key,0,6) == "epmac_"){ //encontre una mac seleccionada entoces por forma empirica con ayuda del mac_adress obtego los parametros q se relacionan con esa mac.
            $tmpMac    = substr($key,6);

            // Revisar que la subcadena sea realmente una dirección MAC
            if (!preg_match('/^((([[:xdigit:]]){2}:){5}([[:xdigit:]]){2})$/i', $tmpMac))
                $error .= "Invalid MAC address for endpoint<br />";
            
            $tmpDevice       = $arrParameters["id_device_$tmpMac"];
            $tmpModel        = $arrParameters["id_model_device_$tmpMac"];
            $tmpVendor       = $arrParameters["name_vendor_device_$tmpMac"];
	    $tmpidVendor     = $arrParameters["id_vendor_device_$tmpMac"];
	    $tmpModelsVendor = $paloEndPoint->getAllModelsVendor($tmpVendor);
	    if(!array_key_exists($tmpModel,$tmpModelsVendor))
		$error .= "The model entered does not exist or does not belong to this vendor. <br />";
	    $dataVendor = $paloEndPoint->getVendor(substr($tmpMac,0,8));
	    if(!isset($dataVendor["name"]) || $dataVendor["name"] != $tmpVendor || !isset($dataVendor["id"]) || $dataVendor["id"] != $tmpidVendor)
		$error .= "The id or/and name of vendor do not match with the mac address. <br />";
	    if(isset($tmpModel) && $tmpModel != ""){
		if($paloEndPoint->modelSupportIAX($tmpModel)){
		    $comboDevices = combo($arrDeviceFreePBXAll,$tmpDevice);
		    if(!array_key_exists($tmpDevice,$arrDeviceFreePBXAll))
			$error .= "The assigned User Extension does not exist or is not allowed. <br />";
		}
		else{
		    $comboDevices = combo($arrDeviceFreePBX,$tmpDevice);
		    if(!array_key_exists($tmpDevice,$arrDeviceFreePBX))
			$error .= "The assigned User Extension does not exist or is not allowed. <br />";
		}
	    }
	    else
		$comboDevices = combo(array("Select a model" => _tr("Select a model")),"");
	    
            if($tmpDevice == "unselected" || $tmpDevice == "no_device" || $tmpModel == "unselected" || $tmpDevice == "Select a model") //el primero que encuentre sin seleccionar mantiene el error
                $error .= "The mac adress $tmpMac unselected Phone Type or User Extension. <br />";

            // Revisar que el vendedor es uno de los vendedores conocidos
            if (!in_array($tmpVendor, $vendorList))
                $error .= "Invalid or unsupported vendor<br />";
	    
	    $macWithout2Points = str_replace(":","",$tmpMac);
            //PASO 2: Recorro el arreglo de la sesion para modificar y mantener los valores q el usuario ha decidido elegir asi cuando halla un error los datos persisten.
            if(isset($_SESSION['elastix_endpoints'])){
                foreach($_SESSION['elastix_endpoints'] as &$data){//tomo la referencia del elemento para poder modificar su contenido por referencia.
                    if($data[2]==$tmpMac){
                        $data[0] = "<input type='checkbox' name='epmac_$tmpMac' checked='checked' />";
                        $data[5] = "<select name='id_model_device_$tmpMac' onchange='getDevices(this,\"$macWithout2Points\");'>".combo($tmpModelsVendor,$tmpModel)."</select>";
                        $data[6] = "<select name='id_device_$tmpMac' id='id_device_$macWithout2Points'>".$comboDevices."</select>";
                    }
                }
            } 
        }
    }
    return $error;
}

function endpointConfiguratedUnset($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf)
{
    $paloEndPoint = new paloSantoEndPoint($dsnAsterisk,$dsnSqlite);
    $arrEndpoint = array();

    if(is_array($_POST) && count($_POST)>0){
        foreach($_POST as $key => $value){
            if(substr($key,0,6)=="epmac_"){
                $tmpMac = substr($key,6);
                $tmpEndpoint['id_model']    = $_POST["id_model_device_$tmpMac"];
                if($paloEndPoint->deleteEndpointsConf($tmpMac)){
                    $paloFile = new paloSantoFileEndPoint($arrConf["tftpboot_path"]);
                    $name_model = $paloEndPoint->getModelById($tmpEndpoint['id_model']);

                    $ArrayData['vendor'] = $_POST["name_vendor_device_$tmpMac"];
                    $ArrayData['data'] = array(
                                "filename"     => strtolower(str_replace(":","",$tmpMac)),
                                "model"        => $name_model);

                    //Falta si hay error en la eliminacion de un archivo, ya esta para saber q error es, el problema es como manejar un error o los errores dentro del este lazo (foreach).
                    //ejemplo: if($paloFile->deleteFiles($ArrayData)==false){ $paloFile->errMsg  (mostrar error con smarty)}
                    $paloFile->deleteFiles($ArrayData);
                }
            }
        }
    }
    unset($_SESSION['elastix_endpoints']);
    return endpointConfiguratedShow($smarty, $module_name, $local_templates_dir, $dsnAsterisk, $dsnSqlite, $arrLang, $arrConf);
    //header("Location: /?menu=$module_name");
}

function createStatus($type,$text)
{
    if($type==1)//Configurado sin novedad.
        return "<label style='color:green' >$text</label>";
    else if($type==2)//No configurado aun
        return "<label style='color:orange'>$text</label>";
    else if($type==3)//Configurado pero hay cambios, en el freepbx cambio y en el endpoint aun no.
        return "<label style='color:red'  >$text</label>";
}

function network()
{
    $ip=$_SERVER['SERVER_ADDR'];
    $total = subMask($ip);
    list($oc1, $oc2, $oc3, $oc4)=explode(".",$ip);
    return $oc1.".".$oc2.".".$oc3.".0"."/".$total;
}

function subMask($ip)
{
    $total = 0;
    $binario = "";
    $arrIp = array();
    $result = `ifconfig | grep $ip`;
    /*     inet addr:192.168.1.135  Bcast:192.168.1.255  Mask:255.255.255.0*/
    if(ereg("inet[[:space:]][[:alpha:]]{1,}:(([[:digit:]]*\.+[[:digit:]]{1,}){1,})[[:space:]]{1,}[[:alpha:]]{1,}:(([[:digit:]]*\.*[[:digit:]]{1,}){1,})[[:space:]]{1,}[[:alpha:]]{1,}:(([[:digit:]]*\.*[[:digit:]]{1,}){1,})",$result,$regs)){
        $arrIp = explode(".",$regs[5]);
        foreach($arrIp as $key => $valor){
            $binario = decbin($valor);
            $total += substr_count($binario,"1");
        }
        return $total;
    }
}
?>