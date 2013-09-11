<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.6-3                                               |
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
  $Id: index.php,v 1.1 2009-06-08 03:06:39 Oscar Navarrete onavarrete@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoControlPanel.class.php";

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
    $arrConf['dsn_conn_database1'] = generarDSNSistema('asteriskuser', 'asterisk');
    $pDB1 = new paloDB($arrConf['dsn_conn_database1']);
    $pDB2 = new paloDB($arrConf['dsn_conn_database2']);

    //actions
    $action = getAction();
    $content  = "";

    switch($action){
        case "call":
            $content = callAction($pDB1, $pDB2);
            break;
        case "voicemail":
            $content = voicemailAction($pDB1,$pDB2);
            break;
        case "hangup":
            $content = hangupAction($pDB1, $pDB2);
            break;
        case "refresh":
            $content = waitingChanges("refreshAction", $pDB1, $pDB2);
            break;
        case "getAllData":
            $content = getAllDataAction($pDB1, $pDB2);
            break;
        case "savechange":
            $content = savechangeAction($pDB1, $pDB2);
            break;
        case "savechange2":
            $content = savechange2Action($pDB1, $pDB2);
            break;
        case "saveresize":
            $content = saveresizeAction($pDB1, $pDB2);
            break;
        case "loadBoxes":
            $content = loadBoxesAction($pDB1, $pDB2, $module_name);
            break;
        case "loadArea":
            $content = loadAreaAction($pDB1, $pDB2, $module_name);
            break;
        case "saveEdit":
            $content = saveEditAction($pDB1, $pDB2);
            break;
        case "addExttoQueue":
            $content = addExttoQueueAction($pDB1, $pDB2);
            break;
        default: // view_form
            $content = viewFormControlPanel($smarty, $module_name, $local_templates_dir, $pDB1, $pDB2, $arrConf, $arrLang);
            break;
    }
    return $content;
}

function viewFormControlPanel($smarty, $module_name, $local_templates_dir, &$pDB1, &$pDB2, $arrConf, $arrLang)
{
    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $oForm = new paloForm($smarty,array());
    $arrDevices = $pControlPanel->getAllDevicesARRAY();
    $arrAreas = $pControlPanel->getDesignArea();
  //  $arrQueues  = $pControlPanel->getAllQueuesARRAY2();
    //$arrDAHDITrunks  = $pControlPanel->getDAHDITrunksARRAY();
  //  $arrSIPTrunks = $pControlPanel->getSIPTrunksARRAY();
  //  $arrConferences = $pControlPanel->getConferences();
    $session = getSession();
    if(isset($session['operator_panel'])){
        unset($session['operator_panel']);
        putSession($session);
    }
    $smarty->assign("module_name",$module_name);
    $smarty->assign("icon", "/modules/$module_name/images/pbx_operator_panel.png");
    $smarty->assign("arrDevicesExten", isset($arrDevices[1])?$arrDevices[1]:null);
    $smarty->assign("arrDevicesArea1", isset($arrDevices[2])?$arrDevices[2]:null);
    $smarty->assign("arrDevicesArea2", isset($arrDevices[3])?$arrDevices[3]:null);
    $smarty->assign("arrDevicesArea3", isset($arrDevices[4])?$arrDevices[4]:null);
    $smarty->assign("lengthExten", isset($arrDevices[1])?count($arrDevices[1]):null);
    $smarty->assign("lengthArea2", isset($arrDevices[2])?count($arrDevices[2]):null);
    $smarty->assign("lengthArea3", isset($arrDevices[3])?count($arrDevices[3]):null);
    $smarty->assign("lengthArea4", isset($arrDevices[4])?count($arrDevices[4]):null);
  /*  $smarty->assign("arrQueues", isset($arrQueues)?$arrQueues:null);
   // $smarty->assign("arrTrunks", $arrDAHDITrunks);
    $smarty->assign("lengthQueues", isset($arrQueues)?count($arrQueues):null);
   // $smarty->assign("lengthTrunks", isset($arrDAHDITrunks)?count($arrDAHDITrunks):null);
  //  $smarty->assign("lengthTrunksSIP", isset($arrSIPTrunks)?count($arrSIPTrunks):null);
   // $smarty->assign("arrTrunksSIP", $arrSIPTrunks);
    $smarty->assign("arrConferences", $arrConferences);
    $smarty->assign("lengthConferences", isset($arrConferences)?count($arrConferences):null);*/
    $i=1;
    foreach($arrAreas as $key => $value){
        $smarty->assign("nameA$i", $value['a.name']);
        $smarty->assign("descripArea$i", $value['a.description']);
        $smarty->assign("height$i", $value['a.height']);
        $smarty->assign("width$i", $value['a.width']);
        $smarty->assign("size$i", $value['a.no_column']);
        $i++;
    }
    //New Feauters
    $totalQueues = 0;
    $arrNumQueues = $pControlPanel->getAsterisk_QueueInfo();
    foreach($arrNumQueues as $key=>$value){
        $totalQueues += $value;
    }
    $smarty->assign("total_queues",$totalQueues);

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",$arrLang["Control Panel"], $_POST);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function waitingChanges($function, &$pDB1, &$pDB2)
{
    $executed_time =  2; //en segundos
    $max_time_wait = 30; //en segundos
    $data          = null;

    $i = 1;
    while(($i*$executed_time) <= $max_time_wait){
        $return = $function($pDB1, $pDB2);
        $data   = $return['data'];
        //wlog("chat_server/index.php: waitingChanges-$function --> espera número $i, ¿hubo cambio?=$return[there_was_change]");
        if($return['there_was_change']){
            break;
        }
        $i++;
        sleep($executed_time); //cada $executed_time estoy revisando si hay algo nuevo....
    }
   return $data;
}

function callAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $number_org = getParameter('extStart');
    $number_dst = getParameter('extFinish');
    if (!is_null($number_org) & !is_null($number_dst)){
        $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
        $pControlPanel->makeCalled($number_org, $number_dst);
    }
    $jsonObject->set_message("");
    return $jsonObject->createJSON();
}

function voicemailAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $number_org = getParameter('extStart');
    if (!is_null($number_org)){
        $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
        $number_dst = "*98";
        $pControlPanel->makeCalled($number_org, $number_dst);
    }
    $jsonObject->set_message("");
    return $jsonObject->createJSON();
}

function hangupAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $number_org = getParameter('extStart');
    if (!is_null($number_org)){
        $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
        $pControlPanel->hangupCalled($number_org);
    }
    $jsonObject->set_message("");
    return $jsonObject->createJSON();
}

function refreshAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $message = $pControlPanel->getAllDevicesXML();
    $arrPrev = array();
    $data = array();

    $session = getSession();

    if(isset($session['operator_panel']['prev'])){
        $arrPrev = $session['operator_panel']['prev'];
    }
    else{
        $session['operator_panel']['prev'] = $message;
        putSession($session);
    }
    $diff = getDifferences($message,$arrPrev);
    if(count($diff) > 0){
        $i=0;
        foreach($diff as $key => $value){
            foreach($value as $key2 => $value2){
                if($key2 == "activity"){
                    if(!isset($session['operator_panel'][$message[$key]["numconf"]])){
                        $data[$i]['Tipo'] = "conference";
                        $data[$i]['key'] = $message[$key]["numconf"];
                        $data[$i]['data'] = array($key2 => $value2);
                        $i++;
                        $session = getSession();
                        $session['operator_panel'][$message[$key]["numconf"]]=array();
                        putSession($session);
                    }
                }elseif($key2 == "parties"){
                    $data[$i]['Tipo'] = "conference";
                    $data[$i]['key'] = $message[$key]["numconf"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "speak_time" && $value2 != " "){
                if(!isset($session['operator_panel'][$key])){
                            if($message[$key]["context"] == "macro-dialout-trunk" && $message[$key]["trunk"] != " "){                    
                                $data[$i]['Tipo'] = "trunk";
                                $data[$i]['key'] = $message[$key]["user"]."_".$message[$key]["trunk"];
                                $data[$i]['data'] = array($key2 => $value2);
                                $i++;   
                                $session = getSession();
                                $session['operator_panel'][$key]=array();
                                putSession($session);  
                            }elseif($message[$key]["context"] != "macro-dialout-trunk"){
                                $data[$i]['Tipo'] = "extension";
                                $data[$i]['key'] = $message[$key]["user"];
                                $data[$i]['data'] = array($key2 => $value2);
                                $i++;
                                $session = getSession();   
                                $session['operator_panel'][$key]=array();
                                putSession($session);  
                            }
                }
                }elseif($key2 == "waiting" || $key2 == "queueNumber"){
                    $data[$i]['Tipo'] = "queue";
                    $data[$i]['key'] = $message[$key]["queueNumber"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "time"){
                    if($value2 == " "){
                        $session = getSession();
                        if(isset($session['operator_panel'][$message[$key]["lotNumber"]])){
                            unset($session['operator_panel'][$message[$key]["lotNumber"]]);
                            putSession($session);
                        }
                    }
                    if(!isset($session['operator_panel'][$message[$key]["lotNumber"]])){
                        $data[$i]['Tipo'] = "parkinglot";
                        $data[$i]['key'] = $message[$key]["lotNumber"];
                        $data[$i]['data'] = array($key2 => $value2);
                        $i++;
                        if($value2 != " "){
                            $session = getSession();
                            $session['operator_panel'][$message[$key]["lotNumber"]] = array();
                            putSession($session);
                        }
                    }
                }elseif($key2 == "extension" || $key2 == "lotNumber"){
                    $data[$i]['Tipo'] = "parkinglot";
                    $data[$i]['key'] = $message[$key]["lotNumber"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "voicemail"){
                    $data[$i]['Tipo'] = "extension";
                    $data[$i]['key'] = $message[$key]["user"];
                    $data[$i]['data'] = array($key2 => $value2."_".$message[$key]["voicemail_cnt"]);
                    $i++;
                }
                else{
                    $session = getSession();
                    $data[$i]['Tipo'] = "extension";
                    $data[$i]['key'] = $message[$key]["user"];
                    if($value2 == "Down"){
                        if(isset($session['operator_panel'][$key])){
                            unset($session['operator_panel'][$key]);
                            putSession($session);
                        }
                    }
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }
            }
            if(isset($arrPrev[$key]["trunk"]) && isset($message[$key]["trunk"]))
                if($arrPrev[$key]["trunk"] != " " && $message[$key]["trunk"] == " "){
                    $data[$i]['Tipo'] = "trunk";
                    $data[$i]['key'] = $arrPrev[$key]["trunk"];
                    $data[$i]['data'] = array("statusTrunk" => "off");
                    $i++;
                }
            if(isset($arrPrev[$key]["numconf"]) && isset($message[$key]["numconf"]) && isset($arrPrev[$key]["parties"]))
                if($arrPrev[$key]["numconf"] != " " && $message[$key]["numconf"] == " " && $arrPrev[$key]["parties"] == "1"." "._tr("Participant")){
                    $data[$i]['Tipo'] = "conference";
                    $data[$i]['key'] = $arrPrev[$key]["numconf"];
                    $data[$i]['data'] = array("statusConf" => "off");
                    $i++;
                    $session = getSession();
                    if(isset($session['operator_panel'][$arrPrev[$key]["numconf"]])){
                        unset($session['operator_panel'][$arrPrev[$key]["numconf"]]);
                        putSession($session);
                    }
                }
        }
        if(count($data)>0){
            $status = true;
            $jsonObject->set_status("CHANGED");
            $jsonObject->set_message($data);
        }
        else{
            $status = false;
            $jsonObject->set_message(array());
        }
    }
    else{
        $status = false;
        $jsonObject->set_message(array());
    }
    //writeLOG("access.log",print_r($data,true));
    $result = array("there_was_change" => $status, "data" => $jsonObject->createJSON());
    return $result;
}

function getDifferences($message,$arrPrev)
{
    $result  = array();
    $session = getSession();
    if(count($arrPrev) > 0){
        foreach($message as $key => $value){
            $tmp = array_diff($value,$arrPrev[$key]);
            if(count($tmp)>0)
                $result[$key] = $tmp;
        }
        if(count($result)>0){
            $session['operator_panel']['prev'] = $message;
            putSession($session);
        }
        return $result;
    }
    else{
        $session['operator_panel']['prev'] = $message;
        putSession($session);
        return $message;
    }
}

function loadBoxesAction(&$pDB1, &$pDB2, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $arrDevices = $pControlPanel->getAllDevicesARRAY();
    $arrQueues  = $pControlPanel->getAllQueuesARRAY2();
    $arrDAHDITrunks  = $pControlPanel->getDAHDITrunksARRAY();
    $arrSIPTrunks = $pControlPanel->getSIPTrunksARRAY();
    $arrParkinglots = $pControlPanel->getParkinglots();
    $arrAreas = $pControlPanel->getDesignArea();
    $arrConferences = $pControlPanel->getConferences();
    foreach($arrAreas as $key => $value){
        $length[] = $value['a.no_column'];
    }
    $numslots = 0;
    foreach($arrParkinglots as $key => $value){
        if($value["keyword"] == "numslots")
            $numslots = $value["data"];
        elseif($value["keyword"] == "parkext")
            $parkext = $value["data"];
    }
    $arrPark  = array();
    $arrExten = array();
    $arrArea1 = array();
    $arrArea2 = array();
    $arrArea3 = array();
    $arrQue   = array();
    $arrDAHDI = array();
    $arrSIP   = array();
    $arrCon   = array();
    for($i=1;$i<=$numslots;$i++){
        $ext = $parkext + $i;
        $arrPark[] = array("id" => $ext, "type" => "parkinglot", "title" => "<b>Parked ($ext)</b>", "info" => $ext, "module_name" => $module_name, "img_name" => "parking.png", "status" => "on", "droppable" => false);
    }
    $arrDevices[1] = isset($arrDevices[1])? $arrDevices[1]:array();
    $arrDevices[2] = isset($arrDevices[2])? $arrDevices[2]:array();
    $arrDevices[3] = isset($arrDevices[3])? $arrDevices[3]:array();
    $arrDevices[4] = isset($arrDevices[4])? $arrDevices[4]:array();
    foreach($arrDevices[1] as $key => $value){
	$short_name = "<b>$key:</b>&nbsp;$value[short_name]";
	if(strlen($value["short_name"])>12)
	    $short_name .= "...";
        $arrExten[] = array("id" => $key, "type" => "extension", "title" => $short_name, "info" => $value["full_name"], "module_name" => $module_name, "img_name" => "phhonez0.png", "status" => $value["status"], "droppable" => true);
    }
    foreach($arrDevices[2] as $key => $value){
	$short_name = "<b>$key:</b>&nbsp;$value[short_name]";
	if(strlen($value["short_name"])>12)
	    $short_name .= "...";
        $arrArea1[] = array("id" => $key, "type" => "area1", "title" => $short_name, "info" => $value["full_name"], "module_name" => $module_name, "img_name" => "phhonez0.png", "status" => $value["status"], "droppable" => true);
    }
    foreach($arrDevices[3] as $key => $value){
	$short_name = "<b>$key:</b>&nbsp;$value[short_name]";
	if(strlen($value["short_name"])>12)
	    $short_name .= "...";
        $arrArea2[] = array("id" => $key, "type" => "area2", "title" => $short_name, "info" => $value["full_name"], "module_name" => $module_name, "img_name" => "phhonez0.png", "status" => $value["status"], "droppable" => true);
    }
    foreach($arrDevices[4] as $key => $value){
	$short_name = "<b>$key:</b>&nbsp;$value[short_name]";
	if(strlen($value["short_name"])>12)
	    $short_name .= "...";
        $arrArea3[] = array("id" => $key, "type" => "area3", "title" => $short_name, "info" => $value["full_name"], "module_name" => $module_name, "img_name" => "phhonez0.png", "status" => $value["status"], "droppable" => true);
    }
    foreach($arrQueues as $key => $value){
	$short_name = "<b>$value[number]:</b>&nbsp;".substr($value['name'],0,12);
	if(strlen($value["name"])>12)
	    $short_name .= "...";
        $arrQue[] = array("id" => $value["number"], "type" => "queue", "title" => $short_name, "info" => $value["members"], "module_name" => $module_name, "img_name" => "queue.png", "status" => "on", "droppable" => false);
    }
    foreach($arrDAHDITrunks as $key => $value){
        $arrDAHDI[] = array("id" => $value, "type" => "dahdiTrunk", "title" => "<b>$value</b>", "info" => $value, "module_name" => $module_name, "img_name" => "icon_trunk2.png", "status" => "on", "droppable" => false);
    }
    foreach($arrSIPTrunks as $key => $value){
	$short_name = "<b>".substr($value['name'],0,12)."</b>";
	if(strlen($value["name"])>12)
	    $short_name .= "...";
        $arrSIP[] = array("id" => $value["name"], "type" => "sipTrunk", "title" => $short_name, "info" => $value["name"], "module_name" => $module_name, "img_name" => "icon_trunk2.png", "status" => $value["status"], "droppable" => false);
    }
    foreach($arrConferences as $key => $value){
	$short_name = "<b>$value[exten]:</b>&nbsp;".substr($value['description'],0,9);
	if(strlen($value["description"])>12)
	    $short_name .= "...";
        $arrCon[] = array("id" => $value["exten"], "type" => "conference", "title" => $short_name, "info" => $value["exten"].": ".$value['description'], "module_name" => $module_name, "img_name" => "conference.png", "status" => "on", "droppable" => false);
    }
    $arrData[0] = array("length" => ceil(count($arrDevices[1])/$length[0]), "data" => $arrExten);
    $arrData[1] = array("length" => ceil(count($arrDevices[2])/$length[1]), "data" => $arrArea1);
    $arrData[2] = array("length" => ceil(count($arrDevices[3])/$length[2]), "data" => $arrArea2);
    $arrData[3] = array("length" => ceil(count($arrDevices[4])/$length[3]), "data" => $arrArea3);
    $arrData[4] = array("length" => ceil(count($arrQueues)/$length[4]), "data" => $arrQue);
    $arrData[5] = array("length" => ceil(count($arrDAHDITrunks)/$length[5]), "data" => $arrDAHDI);
    $arrData[6] = array("length" => ceil(count($arrSIPTrunks)/$length[6]), "data" => $arrSIP);
    $arrData[7] = array("length" => ceil(count($arrConferences)/$length[7]), "data" => $arrCon);
    $arrData[8] = array("length" => ceil($numslots/$length[8]), "data" => $arrPark);

    $jsonObject->set_message($arrData);
    return $jsonObject->createJSON();
}

function savechangeAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $number_org = getParameter('extStart');
    $id_area    = getParameter('area');
    //if (!is_null($number_org) & !is_null($id_area)){
        $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
        $pControlPanel->saveChangeArea($number_org,$id_area);
    //}
    $jsonObject->set_message("");
    return $jsonObject->createJSON();
}

function getAllDataAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $message = $pControlPanel->getAllDevicesXML();
    $arrPrev = array();
    $data = array();

    $session = getSession();

    if(isset($session['operator_panel']['prev'])){
        $arrPrev = $session['operator_panel']['prev'];
    }
    else{
        $session['operator_panel']['prev'] = $message;
        putSession($session);
    }
    $diff = $message;
    if(count($diff) > 0){
        $i=0;
        foreach($diff as $key => $value){
            foreach($value as $key2 => $value2){
                if($key2 == "activity"){
                    if(!isset($session['operator_panel'][$message[$key]["numconf"]])){
                        $data[$i]['Tipo'] = "conference";
                        $data[$i]['key'] = $message[$key]["numconf"];
                        $data[$i]['data'] = array($key2 => $value2);
                        $i++;
                        $session = getSession();
                        $session['operator_panel'][$message[$key]["numconf"]]=array();
                        putSession($session);
                    }
                }elseif($key2 == "parties"){
                    $data[$i]['Tipo'] = "conference";
                    $data[$i]['key'] = $message[$key]["numconf"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "speak_time" && $value2 != " "){
                    if($message[$key]["context"] == "macro-dialout-trunk" && $message[$key]["trunk"] != " "){                    
                        $data[$i]['Tipo'] = "trunk";
                        $data[$i]['key'] = $message[$key]["user"]."_".$message[$key]["trunk"];
                        $data[$i]['data'] = array($key2 => $value2);
                        $i++;   
                    }elseif($message[$key]["context"] != "macro-dialout-trunk"){
                        $data[$i]['Tipo'] = "extension";
                        $data[$i]['key'] = $message[$key]["user"];
                        $data[$i]['data'] = array($key2 => $value2);
                        $i++;
                    }
                }elseif($key2 == "waiting" || $key2 == "queueNumber"){
                    $data[$i]['Tipo'] = "queue";
                    $data[$i]['key'] = $message[$key]["queueNumber"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "time"){
                    $data[$i]['Tipo'] = "parkinglot";
                    $data[$i]['key'] = $message[$key]["lotNumber"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "extension" || $key2 == "lotNumber"){
                    $data[$i]['Tipo'] = "parkinglot";
                    $data[$i]['key'] = $message[$key]["lotNumber"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }elseif($key2 == "voicemail"){
                    $data[$i]['Tipo'] = "extension";
                    $data[$i]['key'] = $message[$key]["user"];
                    $data[$i]['data'] = array($key2 => $value2."_".$message[$key]["voicemail_cnt"]);
                    $i++;
                }
                else{
                    $data[$i]['Tipo'] = "extension";
                    $data[$i]['key'] = $message[$key]["user"];
                    $data[$i]['data'] = array($key2 => $value2);
                    $i++;
                }
        }
    }
    if(count($data)>0){
        $jsonObject->set_status("CHANGED");
        $jsonObject->set_message($data);
    }
    else{
        $jsonObject->set_message(array());
    }
    }
    else{
        $jsonObject->set_message(array());
    }
   // writeLOG("access.log", print_r($data,true));
    return $jsonObject->createJSON();
}

function savechange2Action(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $number_org = getParameter('extStart');
    $number_dst = getParameter('extFinish');
    //if (!is_null($number_org) & !is_null($number_dst)){
        $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
        $pControlPanel->saveChangeArea2($number_org,$number_dst);
    //}
    $jsonObject->set_message("");
    return $jsonObject->createJSON();
}


function saveresizeAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $id_area = getParameter('area');
    $type    = getParameter('type');
    $height  = getParameter('height');
    $width   =  getParameter('width');

    if($width>747)
        $num=4;
    elseif($width>559 && $width<748)
        $num=3;
    elseif($width>370 && $width<560)
        $num=2;
    elseif($width>184 && $width<371)
        $num=1;

    if($type!="alsoResize")
        $pControlPanel->updateResizeArea($height,$width,$num,$id_area);
    else
        $pControlPanel->updateResizeArea2($height,$width,$num,$id_area);
    return $jsonObject->createJSON();
}

function loadAreaAction(&$pDB1, &$pDB2, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $message = array();
    $message['xml'] = $pControlPanel->getAllAreasXML();
    $message['module_name'] = $module_name;
    $message['loading'] = _tr('Loading');
    $jsonObject->set_message($message);
    return $jsonObject->createJSON();
}

function saveEditAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $id_area = getParameter('area');
    $description = getParameter('description');

    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $jsonObject->set_message($pControlPanel->updateDescriptionArea($description,$id_area));
    return $jsonObject->createJSON();
}

function addExttoQueueAction(&$pDB1, &$pDB2)
{
    $jsonObject = new PaloSantoJSON();
    $number_org = getParameter('extStart');
    $queue      = getParameter('queue');

    $pControlPanel = new paloSantoControlPanel($pDB1,$pDB2);
    $pControlPanel->queueAddMember($queue, $number_org);
    $jsonObject->set_message("");
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

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_open")) 
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else if(getParameter("action")=="call")
        return "call";
    else if(getParameter("action")=="loadBoxes")
        return "loadBoxes";
    else if(getParameter("action")=="voicemail")
        return "voicemail";
    else if(getParameter("action")=="hangup")
        return "hangup";
    else if(getParameter("action")=="getAllData")
        return "getAllData";
    else if(getParameter("action")=="refresh")
        return "refresh";
    else if(getParameter("action")=="savechange")
        return "savechange";
    else if(getParameter("action")=="savechange2")
        return "savechange2";
    else if(getParameter("action")=="saveresize")
        return "saveresize";
    else if(getParameter("action")=="loadArea")
        return "loadArea";
    else if(getParameter("action")=="saveEdit")
        return "saveEdit";
    else if(getParameter("action")=="addExttoQueue")
        return "addExttoQueue";
    else
        return "report"; //cancel
}
?>
