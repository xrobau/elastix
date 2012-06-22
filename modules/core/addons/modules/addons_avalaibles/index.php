<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-15                                             |
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
  $Id: index.php,v 1.1 2010-03-08 12:03:02 Bruno Macias bomv.27@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAddonsModules.class.php";
    include_once "modules/$module_name/libs/JSON.php";
    $smarty->assign('MODULE_NAME', $module_name);

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
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
    $arrLangJS = $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $action = getAction();
    $content = "";
    switch($action) {
        case "install":
            $content = installAddons($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case "get_status":
            $content = getStatus($pDB, $arrConf, $arrLang);
            break;
        case "get_lang":
            $content = getLang($arrLangJS);
            break;
        case "confirm":
            $content = getConfirm($pDB, $arrConf, $arrLang);
            break;
        case "getPackages":
            $content = getStatusUpdateCache($arrConf, $pDB, $arrLang);
            break;
        case "getStatusCache":
            $content = getStatusCache($pDB, $arrConf, $arrLang);
            break;
	case "getServerKey":
	    $content = getServerKey($pDB);
	    break;
	case "progressbar":
            $content = getProgressBar($smarty, $module_name, $pDB, $arrConf, $arrLang);
            break;
        case "check_update":
            $content = checkUpdates($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case "update":
            $content = updateAddons($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case "remove":
            $content = removeAddons($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case "getconfirmAddons":
            $content = getconfirmAddons($module_name, $pDB, $arrConf, $arrLang);
            break;
        case "toDoclearAddon":
	    $content = toDoclearAddon($module_name, $pDB, $arrConf, $arrLang);
	    break;
	case "currentProcess":
	    $content = currentProcess($pDB, $arrConf, $arrLang);
	    break;
        default:
            $content = reportAvailables($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
    }
    return $content;
}

function installAddons($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $name_rpm = getParameter("name_rpm");
    $data_exp = getParameter("data_exp");
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $arrSal['response'] = false;
    $json = new Services_JSON();
    //$_SESSION['elastix_addons']['data_install'] = $data_exp;

    $arrStatus = $pAddonsModules->getStatus($arrConf);
    $arrSal['name_rpm'] = $name_rpm;
    if(!isset($arrStatus) & $arrStatus==""){
    	$arrSal['error'] = "no_daemon";
    	$arrSal['name_rpm'] = $name_rpm;
    	return $json->encode($arrSal);
    }
    
    if($arrStatus['action'] == "none" && $arrStatus['status'] == "idle"){
        $salida = $pAddonsModules->addAddon($arrConf, $name_rpm);
        $arrSal['name_rpm'] = $name_rpm;
        if(ereg("OK Processing",$salida)){
            $arrStatus = $pAddonsModules->getStatus($arrConf);
            if($arrStatus['status'] != "error"){
                $arrSal['response'] = "OK";
                $pAddonsModules->clearActionTMP(); //TODO: falta validar
                $pAddonsModules->setActionTMP($name_rpm, 'install', $data_exp);
            }
            else{
                $arrSal['response'] = "error";
                setValueSessionNull($pAddonsModules);
            }
        }
        else{
            $arrSal['response'] = "error";
            setValueSessionNull($pAddonsModules);
        }
    }
    else{
        if($pAddonsModules->existsActionTMP()){
            if($arrStatus['action'] == "confirm")
                $arrSal['response'] = "status_confirm";
            else
                $arrSal['response'] = "there_install"; //retornar que existe una instalacion
            $arrDataTMP = $pAddonsModules->getActionTMP();
            $arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
	    $arrSal['msg_error'] = _tr("ErrorToOperation")."'".$arrDataTMP['action_rpm']."' "._tr("on")." ".$arrSal['name_rpm'];
        }
        else{
            $arrSal['response'] = "error";
            setValueSessionNull($pAddonsModules);
        }
    }
    $arrSal['installing'] = $arrLang['installing'];

    return $json->encode($arrSal);
}

function getStatus($pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $datatoInsert = getParameter("data_exp");
    sleep(10);
    $arrStatus = $pAddonsModules->getStatus($arrConf);

    $json = new Services_JSON();
    $arrSal['response'] = false;

    if(!isset($arrStatus) & $arrStatus==""){
    	$arrSal['error'] = "no_daemon";
    	return $json->encode($arrSal);
    }
    
    if($arrStatus['action'] == "confirm"){
        $salida = $pAddonsModules->confirmAddon($arrConf);
        if(ereg("OK Starting transaction...",$salida)){
            $arrSal['response'] = "OK";
            $arrDataTMP = $pAddonsModules->getActionTMP();
            $arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
            $arrSal['view_details'] = $arrLang['view_details'];
            //$_SESSION['elastix_addons']['data_install'] = $datatoInsert;
        }
        else{
            $arrSal['response'] = "error";
            setValueSessionNull($pAddonsModules);
            $arrSal['errmsg'] = $arrStatus['errmsg'][0];
            $arr_exp = explode("|",$datatoInsert);
            $arrSal['name_rpm'] = $arr_exp[1];
        }
    }
    else{
    	if($arrStatus['status'] == "error"){
    		$arrSal['response'] = "error";
            setValueSessionNull($pAddonsModules);
            $arrSal['errmsg'] = $arrStatus['errmsg'][0];
            $arr_exp = explode("|",$datatoInsert);
            $arrSal['name_rpm'] = $arr_exp[1];
            return $json->encode($arrSal);
    	}
        if($arrStatus['action'] == "reporefresh")
            $arrSal['status_action'] = $arrLang['Status'].": ".$arrLang['reporefresh'];
        if($arrStatus['action'] == "depsolving")
            $arrSal['status_action'] = $arrLang['Status'].": ".$arrLang['depsolving'];
        if(!isset($arrSal['status_action']) || $arrSal['status_action']=="")
            $arrSal['status_action'] = $arrLang['Status'].": ".$arrLang['downloading'];
        $arrSal['response'] = $arrStatus['action'];
        $arrDataTMP = $pAddonsModules->getActionTMP();
        $arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
    }
    return $json->encode($arrSal);
}

/**
 * Parametros y valores que se reciben de "getStatus" en un proceso de:
 * 
 * 		Instalacion: 
 * 			action: confirm 
 * 		Elimininacion:
 * 		Actualizacion:
 * 
 * */
function isProcessInstallations($pDB, $arrConf)
{
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $arrSal = array("statusInstall"=>"nothing","rpm_installed"=>"nothing","processToDo"=>"nothing");
    $arrStatus = $pAddonsModules->getStatus($arrConf);

    if($arrStatus['action'] == "none" && ($arrStatus['status'] == "idle" || $arrStatus['status'] == "error"))
	setValueSessionNull($pAddonsModules);

    $arrSal['data_cache'] = array();
    if($pAddonsModules->existsActionTMP()){
        if($arrStatus['action'] == "confirm")
            $arrSal['statusInstall'] = "status_confirm";
        else
            $arrSal['statusInstall'] = "there_install"; //retornar que existe una instalacion
        if(is_array($arrStatus['package']))
        	$arrSal['processToDo'] = $arrStatus['package'][0]['action'];
        $arrDataTMP = $pAddonsModules->getActionTMP();
        $arrSal['rpm_installing'] = $arrDataTMP['name_rpm'];
    }
	return $arrSal;
}

function getLang($arrLang){
    $json = new Services_JSON();
    return $json->encode($arrLang);
}

function currentProcess($pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $arrDataTMP = $pAddonsModules->getActionTMP();
    $action  = $arrDataTMP['action_rpm'];
    $dataExp = $arrDataTMP['data_exp'];
    $addon = explode("|",$dataExp);
    if($action=="install")
	$action = _tr("Install");
    else if($action=="update")
	$action = _tr("Upgrade");
    else if($action=="remove")
	$action = _tr("Uninstall");
    $arrSal['message']  = _tr("ErrorToOperation")." \"".$action."\" "._tr("on")." \"".$addon[0]." v".$addon[2]."-".$addon[3]."\"";
    $arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
    return $json->encode($arrSal);
}

function getConfirm($pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $arrStatus = $pAddonsModules->confirmAddon($arrConf);
    $json = new Services_JSON();
    $arr['status'] = $arrStatus;
    $arr['view_details'] = $arrLang['view_details'];
    return $json->encode($arr);
}

function reportAvailables($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pAvailables   = new paloSantoAddonsModules($pDB);
    $addons_search = getParameter("addons_search");
    $smarty->assign("ADDONS_SEARCH",$addons_search);
    $action = getParameter("nav");
    $start  = getParameter("start");
    $filter_field = getParameter("filter_field");
    $sid    = $pAvailables->getSID();
    $module_name2 = "addons_installed";

    ini_set("soap.wsdl_cache_enabled", "0");
    try {
        $client = new SoapClient($arrConf['url_webservice']);
    } catch (SoapFault $e) {
        $smarty->assign("mb_title", _tr("ERROR").": ");
        $smarty->assign("mb_message",_tr("The system can not connect to the Web Service resource. Please check your Internet connection."));
        return ;
    }

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    try {
        $totalAvailables = $client->getNumAddonsAvailables("2.0.4", "name", $addons_search, $filter_field);
    } catch (SoapFault $e) {
        $smarty->assign("mb_title", $arrLang["ERROR"].": ");
        $smarty->assign("mb_message",$arrLang["The system can not connect to the Web Service resource. Please check your Internet connection."]);
        return ;
    }
    $limit  = 10;
    $total  = $totalAvailables;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->pagingShow(true); // show paging section.
    $oGrid->setTplFile("$local_templates_dir/_list.tpl");

    $oGrid->calculatePagination($action,$start);
    $offset = $oGrid->getOffsetValue();
    $end    = $oGrid->getEnd();
    $_POST['filter_field'] = isset($filter_field)?$filter_field:"all";
    $url    = "?menu=$module_name&amp;filter_value=$addons_search";
    $arrData = null;
    try {
        $arrResult =$client->getAddonsAvailables("2.0.4", $limit, $offset, "name", $addons_search, $sid, $filter_field);
    } catch (SoapFault $e) {
        $smarty->assign("mb_title", $arrLang["ERROR"].": ");
        $smarty->assign("mb_message",$arrLang["The system can not connect to the Web Service resource. Please check your Internet connection."]);
        return ;
    }

    $serverKey = $pAvailables->getSID();
    if(isset($serverKey) & $serverKey != "")
	$serverKey = "&serverkey=$serverKey";
    else
	$serverKey = "";

    $addonsInstalled[]   = array();
    $addonsNoInstalled[] = array();

    if(is_array($arrResult) && $total>0){
        $smarty->assign('ETIQUETA_INSTALL', $arrLang['Install']);
        
      
	
	if((count($arrResult))%2!=0)
		  $arrResult[] = "relleno";

        $addonsToShow = $arrResult;
        foreach($addonsToShow as $key => $value){
	    if(is_string($value) && ($value=="relleno" || $value==_tr("Installed") || $value==_tr("Availables")))
		$arrTmp[0]=$value;
	    else{
		$versionToInstall = $value['version']."-".$value['release'];                
		$action = "";
		$upgrade = $pAvailables->idUpgraded($value);
        	if(!$pAvailables->exitAddons($value)){
		    $action = "Install";
		    $installed = false;
        	}else{
		    $action = "Uninstall";
		    $installed = true;
        	}

		$smarty->assign(array(
		    'ETIQUETA_DOWNLOADING'  =>  $arrLang['downloading'],
		    'URL_IMAGEN_PAQUETE'    =>  "$arrConf[url_images]/$value[name_rpm].jpeg",
		    'DESCRIPCION_PAQUETE'   =>  $value['description'],
		    'LOCATION'		    =>  $value['location'],
		    'PAQUETE_RPM'           =>  $value['name_rpm'],
		    'PAQUETE_NOMBRE'        =>  $value['name'],
		    'PAQUETE_VERSION'       =>  $value['version'],
		    'PAQUETE_RELEASE'       =>  $value['release'],
		    'PAQUETE_CREADOR'       =>  $value['developed_by'],
		    'URL_BUY'		=>  $value['url_marketplace'].$serverKey."&referer=",
		));
        	                
		$button = "";
		$comprado = true;
		//comercial no instalado, no comprado
		if(($value["is_commercial"]==1 && $value["fecha_compra"]==0 && !$installed)){// fecha_compra = 0 significa que no esta comprado aun este addons
		    $action = "buy";
		    $comprado = false;
		    $button = buttonInstall($installed,$value["name_rpm"],$arrLang,$action, $serverKey,$value['url_marketplace'],$installed, $comprado);
		}else if($value["is_commercial"]==1 && $value["fecha_compra"]==0 && $installed){// comercial instalado, no comprado
		    $action = "buy";
		    $comprado = false;
		    $button = buttonUnInstall($value["name_rpm"],$arrLang,$action, $serverKey, $versionToInstall, $pAvailables, $value['name'], $installed, $comprado, $upgrade);
		}else{
		    $action = "Install";
		    $comprado = true;
		    if($installed) // new
			$button = buttonUnInstall($value["name_rpm"],$arrLang,"Uninstall", $serverKey, $versionToInstall, $pAvailables, $value['name'], $installed, $comprado, $upgrade);
		    else //new
			$button = buttonInstall($installed,$value["name_rpm"],$arrLang,$action, $serverKey,$value['url_marketplace'],$installed, $comprado);
		}

		$smarty->assign("ACTION_INSTALL", $button);

		$imgPack   = "<div style='float: left; width: 177px; height: 126px; text-align: center;'>".$smarty->fetch("$local_templates_dir/imagen_paquete.tpl")."</div>";
		$infoPack  = "<div style='width: 93%'>".$smarty->fetch("$local_templates_dir/info_paquete.tpl")."</div>";

		// son dos columnas donde cada columna tendra tres divs
		$arrTmp[0] = $imgPack.$infoPack;
	    }
            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array("title"    => $arrLang["Availables"],
                        "icon"     => "images/list.png",
                        "width"    => "100%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array(
	    0 => array("name"      => "",
                                   "property1" => ""),
            1 => array("name"      => "",
                                   "property1" => ""),
            2 => array("name"      => "",
                                   "property1" => ""))
                    );

    $smarty->assign("Search", _tr("Search"));
    $smarty->assign("module_name", $module_name);
    $smarty->assign("uninstall", _tr("Uninstall"));
    $smarty->assign("install", _tr("Install"));
    $smarty->assign("textDownloading", _tr("Starting"));
    $smarty->assign("textRemoving", _tr("Removing"));
    $smarty->assign("textInstalling", _tr("Installing"));
    $smarty->assign("daemonOff", _tr("no_daemon"));
    $smarty->assign("search", _tr("Search"));
    $smarty->assign("tryItText", _tr("Try it"));
    $smarty->assign("textObservation", _tr("Please need to enable Centos repo, Elastix, Extra or others for the proper functioning, Detail of errors: "));
    $smarty->assign("error_details", _tr("Error(Details)"));
    $smarty->assign("iniDownloading",_tr("Initializing Download"));
    $oFilterForm = new paloForm($smarty, createFieldFilter());
    
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid($arrGrid, $arrData, $arrLang);

    return $content;
}

function buttonInstall($install, $name_rpm, $arrLang, $action, $serverKey,$url_marketplace,$installed, $comprado)
{
    $action = (isset($action) & $action !="")?$action:"Install";
    $url_marketplace = $url_marketplace.$serverKey;
    if(!$install){
	if($action == "buy"){//es comercial
	    $actionClase = "";
	    $tryIt = "";
	    if($serverKey == "")
			$actionClase = "registrationServer";
	    else{
			$actionClase = "buy";
	    }
	    if(!$comprado)
		$tryIt = "<input type='button' value='"._tr("Try it")."' class='install' id='$name_rpm' name='tryButton' style='display: none;' />";
			
	    $html = "<div id='img_$name_rpm' align='center' >".
			"<img alt='' src='modules/addons_avalaibles/images/loading.gif' class='loadingAjax' style='display: block;' />".
			"<div id='start_$name_rpm' style='display: none;'>".
			"<div class='text_starting' align='right'>$arrLang[Starting]</div>".
			"<div>".
			    "<img alt='' src='modules/addons_avalaibles/images/starting.gif' class='startingAjax' align='right' />".
			"</div>".
			"</div>".
			"<div>".
			    "<div style='float: right; padding-right: 2px;'>$tryIt</div>". // install
			    "<div style='float: right; padding-right: 2px;'></div>". // update
			    "<div style='float: right; padding-right: 2px;' >".// buy
				    "<input type='button' value='"._tr("Buy")."' class='$actionClase' id='".$name_rpm."_buy' name='buyButton' style='display: none;' />".
			    "</div>".
			"</div>".
		      "</div>";
	}else{// no es comercial
	    $html = "<div id='img_$name_rpm' align='center' >".
			"<img alt='' src='modules/addons_avalaibles/images/loading.gif' class='loadingAjax' style='display: block;' />".
			"<div id='start_$name_rpm' style='display: none;'>".
			"<div class='text_starting' align='right'>$arrLang[Starting]</div>".
			"<div>".
			    "<img alt='' src='modules/addons_avalaibles/images/starting.gif' class='startingAjax' align='right' />".
			"</div>".
			"</div>".
			"<div>".
			    "<div style='float: right; padding-right: 2px;' >". // install
				    "<input type='button' value='"._tr($action)."' class='install' id='$name_rpm' name='installButton' style='display: none;' />".
			    "</div>".
			    "<div style='float: right; padding-right: 2px;'></div>". // update
			    "<div style='float: right; padding-right: 2px;'></div>". // buy
			"</div>".
		      "</div>";
		}
    }else
        $html = "";
    return $html;
}

function buttonUnInstall($name_rpm, $arrLang, $action, $serverKey, $versionToInstall, $pAvailables, $packageName, $installed, $comprado, $upgrade)
{
    // verificando si existe actualizacion
    $action = (isset($action) & $action !="")?$action:"Uninstall";
    $actionClase = $action;
    $arrAddon = $pAvailables->getAddonByName($name_rpm);
    $update = "";
    $buy = "";
    
    if($upgrade['status']){ // comparando si las versiones son iguales
	$versionInstalled = str_replace("$name_rpm-","",$upgrade['old_version']);
	$versionToInstall = str_replace("$name_rpm-","",$upgrade['new_version']);
	//$title = "<b>"._tr("Current version").":</b> $packageName v$versionInstalled <br /><b>"._tr("Upgrade version").":</b> $packageName v$versionToInstall";
	$title = "<b>"._tr("Current version").":</b> $packageName v$versionInstalled <b><br />"._tr("Upgrade version").":</b> $packageName v$versionToInstall";
	$update = "<input type='button' value='"._tr("Upgrade")."' onclick='updateAddon(\"$name_rpm\");' class='updateAddon' title='$title' class='ttip' style='display: none;' />";
    }else
	$update = "&nbsp;";

    if($action=="buy"){
		if($serverKey != "")
			$actionClase = "buy";
		else
			$actionClase = "registrationServer";
		$buy = "<input type='button' value='"._tr("Buy")."' class='$actionClase' id='".$name_rpm."_buy' name='buyButton' style='display: none;' />";
    }else
    	$buy = "&nbsp;";
    
    $html = "<div id='img_$name_rpm' align='center' >".
		"<img alt='' src='modules/addons_avalaibles/images/loading.gif' class='loadingAjax' style='display: block;' />".
		"<div id='start_$name_rpm' style='display: none;'>".
		"<div class='text_starting' align='right'>$arrLang[Removing]</div>".
		"<div>".
		    "<img alt='' src='modules/addons_avalaibles/images/starting.gif' class='startingAjax' align='right' />".
		"</div>".
		"</div>".
		"<div>".
		    "<div style='float: right; padding-right: 2px;' >". // uninstall
			    "<input type='button' value='"._tr("Uninstall")."' class='uninstall' id='$name_rpm' onclick='removeAddon(\"$name_rpm\");' name='uninstallButton' style='display: none;' />".
		    "</div>".
		    "<div style='float: right; padding-right: 2px;'>$update</div>". // update
		    "<div style='float: right; padding-right: 2px;'>$buy</div>". // buy
		"</div>".
	    "</div>";
    return $html;
}

function quitSpecialCharacters($str)
{
    if(strpos($str,".")){
	$str = str_replace(".","|",$str);
    }
    return $str;
}


// funcion que ejecuta el demunio YUM para verificar los ultimos rpms a instalar
function getPackagesCache($arrConf, &$pDB, $arrLang){

    try{
    $client = new SoapClient($arrConf['url_webservice']);
    $packages = $client->getAllAddons("2.0.4");

    $pAddonsModules = new paloSantoAddonsModules($pDB);

    $arrStatus = $pAddonsModules->getStatus($arrConf);
    
    if(!isset($arrStatus) & $arrStatus==""){
    	$arrSal['error'] = "no_daemon";
    	setValueSessionNull($pAddonsModules);
    	return $arrSal;
    }

    if(isset($arrStatus['action']) && ($arrStatus['action'] == "none" && $arrStatus['status'] == "idle")){
        //$salida = $pAddonsModules->addAddon($arrConf, $packages);
        $salida = $pAddonsModules->testAddAddon($arrConf, $packages);
        if(ereg("OK Processing",$salida)){
            $arrStatus = $pAddonsModules->getStatus($arrConf);
            if($arrStatus['status'] != "error"){
                $arrSal['response'] = "OK";
            }
            else{
            	setValueSessionNull($pAddonsModules);
                $arrSal['response'] = "error";
                $arrSal['data_cache'] = array();
            }
        }
        else {
        	setValueSessionNull($pAddonsModules);
            $arrSal['response'] = "error";
            $arrSal['data_cache'] = array();
        }
    }
    else{
        if($pAddonsModules->existsActionTMP()){
            $arrDataTMP = $pAddonsModules->getActionTMP();
            $arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
            $tmp = explode("|",$arrDataTMP['data_exp']);

            if($arrStatus['action'] == "confirm"){
                $arrSal['response'] = "status_confirm";
                $arrSal['msg'] = $arrLang["There is a facility that awaits confirmation to install NAME, the user who initiated the installation was"]." ($arrDataTMP[user]).";
                $arrSal['msg'] = str_replace("NAME",$tmp[0]." version: $tmp[2]-$tmp[3]",$arrSal['msg']);
            }
            else{
		$arrSal['response'] = "there_install"; //retornar que existe una instalacion
		$arrSal['msg_error'] = _tr("ErrorToOperation")." '".$arrDataTMP['action_rpm']."' "._tr("on")." ".$arrSal['name_rpm'];
		$arrSal['msg'] = $tmp[0]."version $tmp[2]-$tmp[3]";
            }
        }
        else if($arrStatus['status'] == "error"){
            $arrSal['response'] = "error";
            setValueSessionNull($pAddonsModules);
        }else
            $arrSal['response'] = "OK";
    }

    $arrSal['status_action'] = $arrLang['Status'].": ".$arrStatus['status'];

    return $arrSal;
   }
   catch(SoapFault $e){
      return $e->__toString();
   }
}

// funcion que verifica si y se hizo la descarga en cache de los rpm anstes de instalar
function getStatusCache(&$pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    sleep(5);
    $arrStatus = $pAddonsModules->getStatus($arrConf);
    $json = new Services_JSON();

    //if($arrStatus['action'] == "confirm"){
    if(!isset($arrStatus) & $arrStatus==""){
    	$arrSal['error'] = "no_daemon";
    	return $arrSal;
    }
    if($arrStatus['action'] == "none" & $arrStatus['status'] == "idle"){ // if testadd ya realizado la descaraga en cache
        //$salida = $pAddonsModules->clearAddon($arrConf);
        //if(ereg("OK",$salida)){
            $arrSal['response'] = "OK";
            $client = new SoapClient($arrConf['url_webservice']);
            $packages = $client->getAllAddons("2.0.4");

            $arr_packages = explode(" ",$packages);
            $arr_RPMs = array();
            foreach ($arr_packages as $sNombreRPM) {
                $arr_RPMs[$sNombreRPM] = array('status' => 'OK', 'observation' => 'OK');
            }
            $pAddonsModules->fillDataCache($arr_packages, $arr_RPMs);
        /*}
        else
            $arrSal['response'] = "error";*/
    } elseif ($arrStatus['status'] != 'error') {
        if($arrStatus['action'] == "reporefresh")
            $arrSal['status_action'] = $arrLang['Status'].": ".$arrLang['reporefresh'];
        if($arrStatus['action'] == "depsolving")
            $arrSal['status_action'] = $arrLang['Status'].": ".$arrLang['depsolving'];
        if(!isset($arrSal['status_action']) || $arrSal['status_action']=="")
            $arrSal['status_action'] = $arrLang['Status'].": ".$arrLang['downloading'];
        $arrSal['response'] = $arrStatus['action'];
    } else {
        // Ha ocurrido un error 
        $pAddonsModules->clearAddon($arrConf);
        setValueSessionNull($pAddonsModules);
        $arrSal['response'] = "error";
        
        // Separar los mensajes que referencian a un paquete objetivo
        $listaErr = array();
        foreach ($arrStatus['errmsg'] as $sErrMsg) {
            $regs = NULL;
            if (preg_match('/^TARGET (\S+) REQUIRES (.+)$/', $sErrMsg, $regs)) {
                $listaErr[$regs[1]][] = $regs[2];
            }
        }

        $client = new SoapClient($arrConf['url_webservice']);
        $packages = $client->getAllAddons("2.0.4");

        $arr_packages = explode(" ",$packages);
        $arr_RPMs = array();
        foreach ($arr_packages as $sNombreRPM) {
            // TODO: internacionalizar
            if (isset($listaErr[$sNombreRPM])) {
                $arr_RPMs[$sNombreRPM] = array(
                    'status' => 'FAIL', 
                    'observation' => 'Addon '.$sNombreRPM.' requires '.implode(', ', $listaErr[$sNombreRPM]));
            } else {
                $arr_RPMs[$sNombreRPM] = array('status' => 'OK', 'observation' => 'OK');
            }
        }
        $pAddonsModules->fillDataCache($arr_packages, $arr_RPMs);
        $arrSal['data_cache'] = $pAddonsModules->getDataCache();
    }
    return $json->encode($arrSal);
}

// funcion que verifica si se debe o no actualizar la lista de rpm a instalar
function getStatusUpdateCache($arrConf, &$pDB, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $arrInstall = isProcessInstallations($pDB, $arrConf);
    //$arrInstall = array();
    if(isset($_SESSION['elastix_addons']['last_update'])){
        $timeLast = $_SESSION['elastix_addons']['last_update'];
        $timeNew = time();
	$arrStatus = $pAddonsModules->getStatus($arrConf);
	$actionStatus = $arrStatus['action'];
	$statusProc   = $arrStatus['status'];
        if(($timeNew - $timeLast) > 7200 || $actionStatus == "reporefresh" || $actionStatus == "depsolving" || $statusProc == "busy"){ //si es mayor a 5 minutos al fina1 son 2h -> 7200
            $_SESSION['elastix_addons']['last_update'] = $timeNew;
            $arrSal = getPackagesCache($arrConf, $pDB, $arrLang);
            $arrSal = array_merge($arrInstall,$arrSal);
            return $json->encode($arrSal);
        }
        else{ // no se actualiza.... se toma esta en cache
            $arrSal['response'] = "noFillDataCache";
            $arrData = $pAddonsModules->getDataCache();
            if(is_array($arrData) && count($arrData) > 0){
                $arrSal['data_cache'] = $arrData;
                $arrSal['status_action'] = "";
                $arrSal = array_merge($arrInstall,$arrSal);
                return $json->encode($arrSal);
            }
            else{ // La session existe pero no hay cache local de los addons
                $_SESSION['elastix_addons']['last_update'] = time();
                $arrSal = getPackagesCache($arrConf, $pDB, $arrLang);
                $arrSal = array_merge($arrInstall,$arrSal);
                return $json->encode($arrSal);
            }
        }
    }else{
        $_SESSION['elastix_addons']['last_update'] = time();
        $arrSal = getPackagesCache($arrConf, $pDB, $arrLang);
        $arrSal = array_merge($arrInstall,$arrSal);
        return $json->encode($arrSal);
    }
}


function getServerKey($pDB)
{
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $serverKey = $pAddonsModules->getSID();
    $arrSal["serverKey"] = $serverKey;
    return $json->encode($arrSal);
}


/***funciones de addons_installed***/
function getProgressBar($smarty, $module_name, $pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $arrStatus = $pAddonsModules->getStatus($arrConf);

    if($pAddonsModules->existsActionTMP()) {
		$arrDataTMP = $pAddonsModules->getActionTMP();
        $valueActual = "none";
        $valueTotal = "0";
		$time_wait = "";
        if(isset($arrStatus['package']))
            $valueActual = $arrStatus['package'];
        if(isset($arrStatus['porcent_total_ins']))
            $valueTotal = $arrStatus['porcent_total_ins'];

		if($arrDataTMP['init_time'] == "" || !isset($arrDataTMP['init_time']))
			$pAddonsModules->updateTimeActionTMP(strtotime(date("Y-m-d h:i:s")));
		else
			$time_wait = getTimeDownload($arrDataTMP['init_time'], $valueTotal);

        $arr['valueActual']  = $valueActual;
        $arr['valueTotal']   = $valueTotal;
		$arr['timeDownload'] = $time_wait;
        $arr['status']  = "progress";
        $arr['action']  = $arrStatus['action'];
        $arr['process_installed'] = $arrLang['process_installed'];
        if($arrStatus['status'] == "idle" && $arrStatus['action'] == "none"){
            $data_exp = $arrDataTMP['data_exp'];
            if(isset($data_exp) && $data_exp != ""){
                $arrDataInsert = explode("|",$data_exp);
                $pAddonsModules->insertAddons($arrDataInsert[0],$arrDataInsert[1],$arrDataInsert[2],$arrDataInsert[3]);
            }
            $pAddonsModules->clearActionTMP();
            $arr['status'] = "finished";
            $arr['response'] = "OK";
            //limpiar las variables de session del permiso de usuario 
            unset($_SESSION['elastix_user_permission']);
            // Refrescar el estado de actualización
            $addons_installed = $pAddonsModules->getCheckAddonsInstalled();
			setValueSessionNull($pAddonsModules);

            try {
                $client = new SoapClient($arrConf['url_webservice']);
                $arrAddons = $client->getCheckAddonsUpdate($addons_installed);
                $arrAddons = explode(",",$arrAddons);
                $pAddonsModules->updateInDB($arrAddons);
            } catch (SoapFault $e) {
                $smarty->assign("mb_title", $arrLang["ERROR"].": ");
                $smarty->assign("mb_message",$arrLang["The system can not connect to the Web Service resource. Please check your Internet connection."]);
            }
        }else if($arrStatus['status'] == "error"){
        	$arr['response'] = "error";
            setValueSessionNull($pAddonsModules);
            $arr['errmsg'] = trim($arrStatus['errmsg'][0]);
        }
        sleep(4);
    }
    else {
        $arr['status'] = "not_install";
        $arr['response'] = "not_install";
    }

    $json = new Services_JSON();
    return $json->encode($arr);
}

function getTimeDownload($ini_time, $valueTotal)
{
	if($ini_time == 0 || $ini_time == "")
		return "";
	else{
		$timestamp2 = mktime(date("h"),date("i"),date("s"),date("m"),date("d"),date("Y"));
		$timestamp1 = mktime(date("h",$ini_time),date("i",$ini_time),date("s",$ini_time),date("m",$ini_time),date("d",$ini_time),date("Y",$ini_time));
		//resto a una fecha la otra para obtener el tiempo transcurrido en la descarga
		$secondsTime = $timestamp2 - $timestamp1;

		// obteniendo el tiempo total que se demoraria la descarga
		$totalTime = 100 * ($secondsTime) / $valueTotal;
		// se obtiene el tiempo que faltaria para completar la descarga
		$timeOut = $totalTime - $secondsTime;
		$hour    = $timeOut / 3600;
		$hour    = abs($hour);
		$minute  = ( $hour - floor($hour) ) * 60;
		$minute  = abs($minute);
		$seconds = ( $minute - floor($minute) ) * 60;
		$seconds = abs($seconds);
		$result  = "";
		if(floor($hour) > 0)
			$result .= floor($hour)."hr ";
		if(floor($minute) > 0)
			$result .= floor($minute)."mn ";
		if(floor($seconds) > 0)
			$result .= floor($seconds)."seg";
		if($result != "")
			$result = _tr("Remaining Time:")." ".$result;
		return $result;
	}
}

function checkUpdates($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $addons_installed = $pAddonsModules->getCheckAddonsInstalled();

    ini_set("soap.wsdl_cache_enabled", "0");
    $client = new SoapClient($arrConf['url_webservice']);
    $arrAddons = $client->getCheckAddonsUpdate($addons_installed);

    //se deben mostrar los links de updates para mostrar
    $arrAddons = explode(",",$arrAddons);
    $pAddonsModules->updateInDB($arrAddons);

    return viewFormAddonsModules($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
}


function updateAddons($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $name_rpm = getParameter("name_rpm");
    $data_exp = getParameter("data_exp");
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $arrSal['response'] = false;
    $_SESSION['elastix_addons']['data_install'] = $data_exp;

    $arrStatus = $pAddonsModules->getStatus($arrConf);
    $arrSal['name_rpm'] = $name_rpm;
    if(!isset($arrStatus) & $arrStatus==""){
    	$arrSal['error'] = "no_daemon";
    	$arrSal['name_rpm'] = $name_rpm;
    	return $json->encode($arrSal);
    }
    if($arrStatus['action'] == "none" && $arrStatus['status'] == "idle"){
        $salida = $pAddonsModules->updateAddon($arrConf, $name_rpm);

        if(ereg("OK Processing",$salida)){
            $arrStatus = $pAddonsModules->getStatus($arrConf);
            if($arrStatus['status'] != "error"){
                $arrSal['response'] = "OK";
                $_SESSION['elastix_addons']['name_rpm'] = $name_rpm;
                $_SESSION['elastix_addons']['action_rpm'] = 'update';
		$pAddonsModules->clearActionTMP();
		$pAddonsModules->setActionTMP($name_rpm, 'update', $data_exp);
            }
            else{
            	setValueSessionNull($pAddonsModules);
                $arrSal['response'] = "error";
            }
        }
        else{
        	setValueSessionNull($pAddonsModules);
            $arrSal['response'] = "error";
        }
    }
    else{
	$arrDataTMP = $pAddonsModules->getActionTMP();
	$arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
        $arrSal['response'] = "there_install"; //retornar que existe una instalacion
	$arrSal['msg_error'] = _tr("ErrorToOperation")."'".$arrDataTMP['action_rpm']."' "._tr("on")." ".$arrSal['name_rpm'];
    }
    $arrSal['installing'] = $arrLang['installing'];

    return $json->encode($arrSal);
}

function removeAddons($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $name_rpm = getParameter("name_rpm");
    $data_exp = getParameter("data_exp");
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $arrSal['response'] = false;
    $_SESSION['elastix_addons']['data_install'] = '';

    $arrStatus = $pAddonsModules->getStatus($arrConf);
    $arrSal['name_rpm'] = $name_rpm;
    if(!isset($arrStatus) & $arrStatus==""){
    	$arrSal['error'] = "no_daemon";
    	$arrSal['name_rpm'] = $name_rpm;
    	return $json->encode($arrSal);
    }
    
    if($arrStatus['action'] == "none" && $arrStatus['status'] == "idle"){
        $salida = $pAddonsModules->removeAddon($arrConf, $name_rpm);
        if(ereg("OK Processing",$salida)){
            $arrStatus = $pAddonsModules->getStatus($arrConf);
            if($arrStatus['status'] != "error"){
                $arrSal['response'] = "OK";
                $_SESSION['elastix_addons']['name_rpm'] = $name_rpm;
                $_SESSION['elastix_addons']['action_rpm'] = 'remove';
		$pAddonsModules->clearActionTMP();
		$pAddonsModules->setActionTMP($name_rpm, 'remove', $data_exp);
            }
            else{
            	setValueSessionNull($pAddonsModules);
                $arrSal['response'] = "error";
            }
        }
        else{
        	setValueSessionNull($pAddonsModules);
            $arrSal['response'] = "error";
        }
    }
    else{
	$arrDataTMP = $pAddonsModules->getActionTMP();
	$arrSal['name_rpm'] = $arrDataTMP['name_rpm'];
        $arrSal['response'] = "there_install"; //retornar que existe una instalacion
	$arrSal['msg_error'] = _tr("ErrorToOperation")."'".$arrDataTMP['action_rpm']."' "._tr("on")." ".$arrSal['name_rpm'];
    }
    $arrSal['installing'] = _tr('removing');

    return $json->encode($arrSal);
}

function getconfirmAddons($module_name, &$pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $arrStatus = $pAddonsModules->getStatus($arrConf);
	$warnmsg = isset($arrStatus["warnmsg"][0])?$arrStatus["warnmsg"][0]:"";
    if($warnmsg == "No packages to install or update" ){
    	$result = $pAddonsModules->clearAddon($arrConf);
    	$arrStatus["clear"] = trim($result);
    }
    	
    if ($arrStatus['status'] == 'idle' && $arrStatus['action'] == 'confirm') {
        $sRespuesta = $pAddonsModules->confirmAddon($arrConf);
        if (preg_match('/^OK /', $sRespuesta)) {
            $arrStatus['response'] = 'OK';
        }
    } else {
        // Todavía está resolviendo dependencias...
        sleep(4);
    }
    

//    $arr['status'] = $arrStatus;

    return $json->encode($arrStatus);
}

function toDoclearAddon($module_name, &$pDB, $arrConf, $arrLang){
    $pAddonsModules = new paloSantoAddonsModules($pDB);
    $json = new Services_JSON();
    $result = "NO_OK";
    $cont = 0;
    while($cont < 5 && $result != "OK"){
	$result = $pAddonsModules->clearAddon($arrConf);
	$result = trim($result);
	if($result == "OK"){
	    setValueSessionNull($pAddonsModules);
	}
	$cont++;
	sleep(2);
    }
    $arrResult['response'] = trim($result);
    return $json->encode($arrResult);
}

function setValueSessionNull($pAddonsModules)
{
	$_SESSION['elastix_addons']['data_install'] = "";
	$_SESSION['elastix_addons']['name_rpm']     = "";
	$_SESSION['elastix_addons']['action_rpm']   = "";
	$pAddonsModules->clearActionTMP();
}

function createFieldFilter(){
    $arrFilter = array(
            "all" => _tr("All"),
            "commercial" => _tr("Commercial"),
            "noncommercial" => _tr("NonCommercial"),
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Search"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}



function getAction()
{
    if(getParameter("action")=="confirm")
        return "confirm";
    else if(getParameter("action")=="get_status")      //Get parameter by GET (command pattern, links)
        return "get_status";
    else if(getParameter("action")=="install")
        return "install";
    else if(getParameter("action")=="get_lang")
        return "get_lang";
    else if(getParameter("action")=="getPackagesCache")
        return "getPackages";
    else if(getParameter("action")=="getStatusCache")
        return "getStatusCache";
    else if(getParameter("action")=="getServerKey")
	return "getServerKey";
    else if(getParameter("action")=="progressbar")
        return "progressbar";
    else if(getParameter("action")=="get_statusBar")
        return "progressbar";
    else if(getParameter("action")=="getconfirmAddons")
        return "getconfirmAddons";
    else if(getParameter("action")=="update")
        return "update";
    else if(getParameter("action")=="remove")
        return "remove";
    else if(getParameter("check_update"))
        return "check_update";
    else if(getParameter("action")=="toDoclearAddon")
    	return "toDoclearAddon";
    else if(getParameter("action")=="currentProcess")
	return "currentProcess";
    else
        return "report"; //cancel
}
?>
