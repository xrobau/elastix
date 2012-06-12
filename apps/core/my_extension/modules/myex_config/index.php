<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-31                                               |
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
  $Id: index.php,v 1.1 2010-08-09 10:08:51 Mercy Anchundia manchundia@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoACL.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoMyExtension.class.php";

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
    $arrConf['dsn_conn_database'] = generarDSNSistema('asteriskuser', 'asterisk');
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $pDBACL = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pDBACL);
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $extension = $pACL->getUserExtension($user);
    $isAdministrator = $pACL->isUserAdministratorGroup($user);
    if($extension=="" || is_null($extension)){
	if($isAdministrator) 
	  $smarty->assign("mb_message", "<b>".$arrLang["no_extension"]."</b>");
	else
	  $smarty->assign("mb_message", "<b>".$arrLang["contact_admin"]."</b>");
	return "";
    }
    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "save_new":
            $content = saveNewMyExtension($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $extension, $isAdministrator);
            break;
        default: // view_form
            $content = viewFormMyExtension($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $extension);
            break;
    }
    return $content;
}

function viewFormMyExtension($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $extension)
{
    $pMyExtension = new paloSantoMyExtension($pDB);
    
    $arrFormMyExtension = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormMyExtension);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");
    $smarty->assign("ID", $id); //persistence id with input hidden in tpl

    $_SESSION["my_extension"]["extension"] = $extension;
    if($action=="view")
	$oForm->setViewMode();
    else if($action=="view_edit" || getParameter("save_edit"))
	$oForm->setEditMode();
    //end, Form data persistence to errors and other events.

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
	$dataMyExtension = $pMyExtension->getMyExtensionById($id);
	if(is_array($dataMyExtension) & count($dataMyExtension)>0)
	    $_DATA = $dataMyExtension;
	else{
	    $smarty->assign("mb_title", $arrLang["Error get Data"]);
	    $smarty->assign("mb_message", $pMyExtension->errMsg);
	}
    }

    $statusDND       = $pMyExtension->getConfig_DoNotDisturb($extension);
    $statusCW        = $pMyExtension->getConfig_CallWaiting($extension);
    $statusCF        = $pMyExtension->getConfig_CallForwarding($extension);
    $statusCFU       = $pMyExtension->getConfig_CallForwardingOnUnavail($extension);
    $statusCFB       = $pMyExtension->getConfig_CallForwardingOnBusy($extension);
    $statusRecording = array('record_in' => FALSE, 'record_out' => FALSE);
    if ($extension != '')
        $statusRecording = $pMyExtension->getRecordSettings($extension);
    $extensionCID    = $pMyExtension->getExtensionCID($extension);

    $_DATA["do_not_disturb"]    = $statusDND;
    $_DATA["call_waiting"]      = $statusCW;
    $_DATA["record_incoming"]    = $statusRecording["record_in"];
    $_DATA["record_outgoing"]    = $statusRecording["record_out"];
    
    if(isset($_SESSION["my_extension"]["enableCF"])){//error state stored in session
          $_DATA["call_forward"] = $_SESSION["my_extension"]["enableCF"];
          unset($_SESSION["my_extension"]["enableCF"]);
    } 
    else $_DATA["call_forward"]  = $statusCF["enable"];
 
    if(isset($_SESSION["my_extension"]["phoneNumberCF"])){//error state stored in session
         $_DATA["phone_number_CF"]   = $_SESSION["my_extension"]["phoneNumberCF"];
         unset($_SESSION["my_extension"]["phoneNumberCF"]);
    }else if(isset($statusCF["phoneNumber"]))//only is true when statusCF is "on"
         $_DATA["phone_number_CF"]   = $statusCF["phoneNumber"]; 
    else $_DATA["phone_number_CF"]   = $arrLang["Configure a phone number here..."];

    if(isset($_SESSION["my_extension"]["enableCFU"])){//error state stored in session
          $_DATA["call_forward_U"] = $_SESSION["my_extension"]["enableCFU"];
          unset($_SESSION["my_extension"]["enableCFU"]);
    }else $_DATA["call_forward_U"]    = $statusCFU["enable"];

    if(isset($_SESSION["my_extension"]["phoneNumberCFU"])){//error state stored in session
         $_DATA["phone_number_CFU"]   = $_SESSION["my_extension"]["phoneNumberCFU"];
         unset($_SESSION["my_extension"]["phoneNumberCFU"]);
    }else if(isset($statusCFU["phoneNumber"]))//only is true when statusCF is "on"
         $_DATA["phone_number_CFU"]   =   $statusCFU["phoneNumber"]; 
    else $_DATA["phone_number_CFU"]   = $arrLang["Configure a phone number here..."];

    if(isset($_SESSION["my_extension"]["enableCFB"])){//error state stored in session
         $_DATA["call_forward_B"] = $_SESSION["my_extension"]["enableCFB"];
         unset($_SESSION["my_extension"]["enableCFB"]);
    }else $_DATA["call_forward_B"]    = $statusCFB["enable"];

    if(isset($_SESSION["my_extension"]["phoneNumberCFB"])){//error state stored in session
         $_DATA["phone_number_CFB"]   = $_SESSION["my_extension"]["phoneNumberCFB"];
         unset($_SESSION["my_extension"]["phoneNumberCFB"]);
    }else if(isset($statusCFB["phoneNumber"]))//only is true when statusCF is "on"
         $_DATA["phone_number_CFB"]   =   $statusCFB["phoneNumber"]; 
    else $_DATA["phone_number_CFB"]   = $arrLang["Configure a phone number here..."];

    $smarty->assign("SAVE", $arrLang["Save Configuration"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("icon", "images/list.png");//extension
    $smarty->assign("EXTENSION",$arrLang["SETTINGS FOR YOUR EXTENSION:"]." ".$extensionCID." (".$extension.")");//extension

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",$arrLang["My Extension"], $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewMyExtension($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $extension, $isAdministrator)
{
    $pMyExtension = new paloSantoMyExtension($pDB);
    $arrFormMyExtension = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormMyExtension);
    $message = "";
    if(!$oForm->validateForm($_POST)){
        // Validation basic, not empty and VALIDATION_TYPE 
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";
        }
        $smarty->assign("mb_message", $strErrorMsg);
    }
    else{
        $s = $_SESSION["my_extension"]["extension"];
        if(isset($_SESSION["my_extension"]["extension"])){
            $extension  = $_SESSION["my_extension"]["extension"];
            $enableDND  = getParameter("do_not_disturb");//return on or off
            $enableCW   = getParameter("call_waiting");//return on or off
            $enableCF   = getParameter("call_forward");//return on or off
            $enableCFU  = getParameter("call_forward_U");//return on or off
            $enableCFB  = getParameter("call_forward_B");//return on or off
            $statusCW   = $pMyExtension->setConfig_CallWaiting($enableCW,$extension);
            $statusDND  = $pMyExtension->setConfig_DoNotDisturb($enableDND,$extension);
            $phoneNumberCF  = trim(getParameter("phone_number_CF"));//is a number !!
            $phoneNumberCFU = trim(getParameter("phone_number_CFU"));
            $phoneNumberCFB = trim(getParameter("phone_number_CFB"));
            /*recordings*/
            $recordIncomingOption  = getParameter("record_incoming");
            $recordOutgoingOption  = getParameter("record_outgoing");
            /************/
            if($enableCF == "on"){
                if(preg_match( "/^[0-9]+$/",$phoneNumberCF))
                    $statusCF   = $pMyExtension->setConfig_CallForward($enableCF,$phoneNumberCF,$extension);
                else{
                    $_SESSION["my_extension"]["enableCF"] = "on";
                    $_SESSION["my_extension"]["phoneNumberCF"] = $phoneNumberCF;
                    $message .=  $arrLang["Please check your phone number for Call Forward"]."<br />";
                    $smarty->assign("mb_title", $arrLang["Validation Error"]);
                    $smarty->assign("mb_message", $message);
                }
            }else{$statusCF  = $pMyExtension->setConfig_CallForward($enableCF,"",$extension);}
           if($enableCFU == "on"){
                if(preg_match( "/^[0-9]+$/",$phoneNumberCFU))
                    $statusCFU  = $pMyExtension->setConfig_CallForwardOnUnavail($enableCFU,$phoneNumberCFU,$extension);
                else{
                    $_SESSION["my_extension"]["enableCFU"] = "on";
                    $_SESSION["my_extension"]["phoneNumberCFU"] = $phoneNumberCFU;
                    $message .=  $arrLang["Please check your phone number for Call Forward On Unavailable"]."<br />";
                    $smarty->assign("mb_title", $arrLang["Validation Error"]);
                    $smarty->assign("mb_message", $message);
                }
           }else{$statusCFU  = $pMyExtension->setConfig_CallForwardOnUnavail($enableCFU,"",$extension);}
            if($enableCFB == "on"){
                if(preg_match( "/^[0-9]+$/",$phoneNumberCFB))
                    $statusCFB  = $pMyExtension->setConfig_CallForwardOnBusy($enableCFB,$phoneNumberCFB,$extension);
                else{
                    $_SESSION["my_extension"]["enableCFB"] = "on";
                    $_SESSION["my_extension"]["phoneNumberCFB"] = $phoneNumberCFB;
                    $message .=  $arrLang["Please check your phone number for Call Forward On Busy"]."<br />";
                    $smarty->assign("mb_title", $arrLang["Validation Error"]);
                    $smarty->assign("mb_message", $message);
                }
            }else{$statusCFB  = $pMyExtension->setConfig_CallForwardOnBusy($enableCFB,"",$extension);
            }

            if(!$statusCW)
                 $message .= $arrLang["Error processing CallWaiting"]."<br />";
            if(!$statusDND)
                 $message .= $arrLang["Error processing Do Not Disturb"]."<br />";
             if(!$statusCF)
                 $message .= $arrLang["Error processing Call Forward"]."<br />";
            if(!$statusCFU)
                 $message .= $arrLang["Error processing Call Forward on Unavailable"]."<br />";
            if(!$statusCFB)
                 $message .= $arrLang["Error processing Call Forward on Busy"]."<br />";

            /*recordings*/
            $statusRecording = $pMyExtension->setRecordSettings($extension,$recordIncomingOption,$recordOutgoingOption);
            /************/
            if($statusCW && $statusDND && $statusCF && $statusCFU && $statusCFB && $statusRecording){
                 $message = $arrLang["Your configuration has been saved correctly"]."<br />";
            }
         $smarty->assign("mb_message", $message);
         }else{
	    if($isAdministrator)
		$message =  "<b>".$arrLang["no_extension"]."</b>";
	    else
		$message =  "<b>".$arrLang["contact_admin"]."</b>";
            $smarty->assign("mb_message", $message);
        }
     }
    $content = viewFormMyExtension($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang, $extension);
    return $content;
}

function createFieldForm($arrLang)
{
    $arrOptions = array('val1' => 'Value 1', 'val2' => 'Value 2', 'val3' => 'Value 3');

    $arrFields = array(
            "do_not_disturb"   => array(    "LABEL"                  => $arrLang["Do Not Disturb"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "call_waiting"   => array(      "LABEL"                  => $arrLang["Call Waiting"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
             "call_forward"   => array(     "LABEL"                  => $arrLang["Call Forward"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "call_forward_U"   => array(    "LABEL"                  => $arrLang["Call Forward on Unavailable"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "call_forward_B"   => array(    "LABEL"                  => $arrLang["Call Forward on Busy"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone_number_CF"     => array( "LABEL"                  => $arrLang["Call Forward"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:190px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone_number_CFU"     => array( "LABEL"                 => $arrLang["Call Forward on Unavailable"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:190px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone_number_CFB"     => array( "LABEL"                 => $arrLang["Call Forward on Busy"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:190px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "record_incoming"                => array( "LABEL"                 => $arrLang["Record Incoming"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("Always" => $arrLang["Always"],"Never" => $arrLang["Never"],"Adhoc" => $arrLang["On-Demand"]),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "record_outgoing"                => array( "LABEL"                 => $arrLang["Record Outgoing"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("Always" => $arrLang["Always"],"Never" => $arrLang["Never"],"Adhoc" => $arrLang["On-Demand"]),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                        )
            );
    return $arrFields;
}
//$smarty->assign("check_file", "checked");
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
    else
        return "report"; //cancel
}
?>
