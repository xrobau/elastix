<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

include_once "libs/paloSantoFax.class.php";
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoJSON.class.php";
include_once "libs/paloSantoOrganization.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent($smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
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
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

	//conexion resource
    $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);

	//obtenemos las credenciales del usuario
	$arrCredentials=getUserCredentials();

    $accion = getAction();
    switch($accion){
        case "checkFaxStatus":
            $contenidoModulo = checkFaxStatus("faxListStatus",$smarty, $module_name, $local_templates_dir, $arrConf, $arrLang, $arrCredentials);
            break;
         case "checkFaxStatus2":
            $contenidoModulo = faxListStatus2("faxListStatus2",$smarty, $module_name, $local_templates_dir, $arrConf, $arrLang, $pDB, $arrCredentials);
            break;
        case "checkSendStatus":
            $contenidoModulo = faxSendStatus("faxSendStatus",$smarty, $module_name, $local_templates_dir, $arrConf, $arrLang, $pDB, $arrCredentials);
            break;
        case "stateFax":
            $contenidoModulo = stateFax("stateFax",$smarty, $module_name, $local_templates_dir, $arrConf, $arrLang, $pDB, $arrCredentials);
            break;
        case "setFaxMsg":
            $contenidoModulo = setFaxMsg("setFaxMsg",$smarty, $module_name, $local_templates_dir, $arrConf, $arrLang,$pDB, $arrCredentials);
            break;
        default:
            $contenidoModulo = listFax($pDB, $smarty, $module_name, $local_templates_dir, $arrCredentials);
            break;
    }
    return $contenidoModulo;
}

function listFax(&$pDB, $smarty, $module_name, $local_templates_dir, $credentials){
    $limit = 30;
    $oFax  = new paloFax($pDB);
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $idOrgFil=null;
    
    //parametros
    $idOrgGet=getParameter("idOrganization");
    if($userLevel1=="superadmin"){
        if(isset($idOrgGet)){   
            if($idOrgGet!="all")
                $idOrgFil=$idOrgGet;
        }
    }else{
        $idOrgFil=$credentials["id_organization"];
    }
    $url["menu"]=$module_name;
    $url["idOrganization"]=$idOrgFil;

    
    if($credentials["userlevel"]!="other"){
        $total = $oFax->getTotalFax($idOrgFil);
        $idUser = null;
    }else{
        $total = 1;
        $idUser=$pACL->getIdUser($userAccount);
    }	

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->pagingShow(true);
    $oGrid->setURL($url);
    $oGrid->setTitle(_tr("Virtual Fax List"));
    $oGrid->setIcon("/modules/$module_name/images/fax_virtual_fax_list.png");

        $arrColumns = array(
        _tr("Fax Extension"),
        _tr("Destination Email"),
        _tr("Caller ID Name"),
        _tr("Caller ID Number"),
        _tr("Status"),
        "");
    $oGrid->setColumns($arrColumns);
    $offset = $oGrid->calculateOffset();

    $arrFax = $oFax->getFaxList($idOrgFil,$idUser,$offset,$limit);
    $arrFaxStatus = $oFax->getFaxStatus();

    $arrData = array();
    foreach($arrFax as $fax) {
            $arrTmp    = array();
            $arrTmp[0] = $fax['extension'];
            $arrTmp[1] = $fax['email'];
            $arrTmp[2] = $fax['clid_name'] . "&nbsp;";
            $arrTmp[3] = $fax['clid_number'] . "&nbsp;";
            $arrTmp[4] = $arrFaxStatus['ttyIAX'.$fax['dev_id']].' on ttyIAX'.$fax['dev_id'];
            $arrTmp[5] = "<div class='load' id='".$fax['extension']."' style='text-align: center;'><strong>?</strong></div>";

            $arrData[] = $arrTmp;
    }

    $session = getSession();
    $session['faxlist']['faxListStatus'] = $arrData;
    putSession($session);

    if($userLevel1 == "superadmin"){
        $arrOrgz=array("all"=>"all");
        foreach(($pORGZ->getOrganization()) as $value){
            if($value["id"]!=1)
                $arrOrgz[$value["id"]]=$value["name"];
        }
        $arrFormElements = createFieldFilter($arrOrgz);
        $oFilterForm = new paloForm($smarty, $arrFormElements);
        if(isset($idOrgFil))
            $_POST["idOrganization"]=$idOrgFil;
        else{
            $_POST["idOrganization"]="all";
        }
        $oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization")." = ".$arrOrgz[$_POST["idOrganization"]], $_POST, array("idOrganization" => "all"),true);
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
        $oGrid->showFilter(trim($htmlFilter));
    }

    $oGrid->setData($arrData);
    return $oGrid->fetchGrid();
}

function checkFaxStatus($function, $smarty, $module_name, $local_templates_dir, $arrConf, $arrLang)
{
    $executed_time = 1; //en segundos
    $max_time_wait = 30; //en segundos
    $event_flag    = false;
    $data          = null;

    $i = 1;
    while(($i*$executed_time) <= $max_time_wait){
        $return = $function($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang);
        $data   = $return['data'];
        if($return['there_was_change']){
            $event_flag = true;
            break;
        }
        $i++;
        sleep($executed_time); //cada $executed_time estoy revisando si hay algo nuevo....
    }
	$return = $function($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang);
	$data   = $return['data'];
   return $data;
}

function faxListStatus($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang)
{
    $oFax    = new paloFax();
    $arrFax  = $oFax->getFaxList();
    $status  = TRUE;
    $end = count($arrFax);
    $arrFaxStatus = $oFax->getFaxStatus();
    $arrData    = array();
    foreach($arrFax as $fax) {
        $arrData[$fax['extension']] = $arrFaxStatus['ttyIAX'.$fax['dev_id']].' on ttyIAX'.$fax['dev_id'];
    }

    $statusArr    = thereChanges($arrData);
    if(empty($statusArr))
        $status = FALSE;
    $jsonObject = new PaloSantoJSON();
    if($status){
        $msgResponse["faxes"] = $statusArr;
        $jsonObject->set_status("CHANGED");
        $jsonObject->set_message($msgResponse);
    }else{
        $jsonObject->set_status("NOCHANGED");
    }

    return array("there_was_change" => true,
                 "data" => $jsonObject->createJSON());
}

function thereChanges($data){
    $session = getSession();
    $arrData = array();
    if (isset($session['faxlist']['faxListStatus']) && 
        is_array($session['faxlist']['faxListStatus']))
        $arrData = $session['faxlist']['faxListStatus'];
    $arraResult = array();
    foreach($arrData as $key => $value){
        $fax = $value[0];
        $status = $value[4];
        if(isset($data[$fax]) & $data[$fax] != $status){
            $arraResult[$fax] = $data[$fax];
            $arrData[$key][4] = $data[$fax];
        }
    }
    $session['faxlist']['faxListStatus'] = $arrData;
    putSession($session);
    return $arraResult;
}
//Verifica el Tono del Fax
function faxListStatus2($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang,$pDB)
{
    $oFax    = new paloFax($pDB);
    $arrFax  = $oFax->getFaxList();
    $status  = TRUE;
    $end = count($arrFax);
    //$arrFaxStatus = $oFax->getFaxStatus();
    //$arrFaxStatus    = array();
    $ext = getParameter('ext');
    $arrFaxStatus = $oFax->checkFaxStatus($ext);
    $statusArr =  "Fax";

    $jsonObject = new PaloSantoJSON();
    $msgResponse["faxes"] = $arrFaxStatus;

    $jsonObject->set_status($statusArr);
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}
//Verificar el Estado del Envío del fax
function faxSendStatus($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang, $pDB)
{
    $oFax    = new paloFax($pDB);
    $arrFax  = $oFax->getFaxList();
    $status  = TRUE;
    $end = count($arrFax);
    //$arrFaxStatus = $oFax->getFaxStatus();
    //$arrFaxStatus    = array();
    $ext = getParameter('ext');
    $arrFaxStatus = $oFax->getSendStatus($ext);
    $statusArr =  "Fax";

    $jsonObject = new PaloSantoJSON();
    $msgResponse = $arrFaxStatus;

    $jsonObject->set_status($statusArr);
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}
//Verificar si el fax se  Envío o canceló
function stateFax($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang, $pDB)
{
    $oFax    = new paloFax($pDB);
    $arrFax  = $oFax->getFaxList();
    $status  = TRUE;
    $end = count($arrFax);
    //$arrFaxStatus = $oFax->getFaxStatus();
    //$arrFaxStatus    = array();
    $jid = getParameter('jid');
    $arrFaxStatus = $oFax->getStateFax($jid);
    $statusArr =  "Fax";

    $jsonObject = new PaloSantoJSON();
    $msgResponse = $arrFaxStatus;

    $jsonObject->set_status($statusArr);
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}

//Obtener Estado del Fax en Faxviewer: Enviado o Error  
function setFaxMsg($smarty, $module_name, $local_templates_dir, $arrConf, $arrLang,$pDB)
{
    $oFax    = new paloFax($pDB);
    $arrFax  = $oFax->getFaxList();
    $status  = TRUE;
    $end = count($arrFax);
    $arrFaxStatus = $oFax->setFaxMsg();
    $statusArr =  "Fax";

    $jsonObject = new PaloSantoJSON();
    $msgResponse = $arrFaxStatus;

    $jsonObject->set_status($statusArr);
    $jsonObject->set_message($msgResponse);

    return $jsonObject->createJSON();
}



function getSession()
{
    session_commit();
    ini_set("session.use_cookies","0");
    if(session_start()){
        $tmp = $_SESSION;
        session_commit();
    }
    return $tmp;
}

function putSession($data)//data es un arreglo
{
    session_commit();
    ini_set("session.use_cookies","0");
    if(session_start()){
        $_SESSION = $data;
        session_commit();
    }
}

function createFieldFilter($arrOrgz)
{
    $arrFormElements = array(
			"idOrganization" => array( "LABEL"                  => _tr("Organization"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => $arrOrgz,
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => "",
										"ONCHANGE"	       => "javascript:submit();"),
                    );
    return $arrFormElements;
}

function getAction()
{
    if(getParameter("action")=="checkFaxStatus")
        return "checkFaxStatus";
     if(getParameter("action")=="checkFaxStatus2")
        return "checkFaxStatus2";
    if(getParameter("action")=="checkSendStatus")
        return "checkSendStatus";
    if(getParameter("action")=="stateFax")
        return "stateFax";
    if(getParameter("action")=="setFaxMsg")
        return "setFaxMsg";
    else
        return "default";
}
?>
