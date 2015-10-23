<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-31                                               |
  | http://www.elastix.com                                               |
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
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoMyExtension.class.php";

    load_language_module($module_name);
    
    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    global $pACL;
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $extension = $pACL->getUserExtension($user);
    $isAdministrator = $pACL->isUserAdministratorGroup($user);
    if($extension=="" || is_null($extension)){
	if($isAdministrator) 
	  $smarty->assign("mb_message", "<b>"._tr("no_extension")."</b>");
	else
	  $smarty->assign("mb_message", "<b>"._tr("contact_admin")."</b>");
	return "";
    }
  
    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "save_new":
            $content = saveNewMyExtension($smarty, $module_name, $local_templates_dir, $arrConf, $extension, $isAdministrator);
            break;
        default:
            $content = viewFormMyExtension($smarty, $module_name, $local_templates_dir, $arrConf, $extension);
            break;
    }
    return $content;
}

function viewFormMyExtension($smarty, $module_name, $local_templates_dir, $arrConf, $extension)
{
    $pMyExtension = new paloSantoMyExtension();
    $oForm = new paloForm($smarty,createFieldForm());
    
    $pMyExtension->AMI_OpenConnect();
    $statusDND       = $pMyExtension->getConfig_DoNotDisturb($extension);
    $statusCW        = $pMyExtension->getConfig_CallWaiting($extension);
    $statusCF        = $pMyExtension->getConfig_CallForwarding($extension);
    $statusCFU       = $pMyExtension->getConfig_CallForwardingOnUnavail($extension);
    $statusCFB       = $pMyExtension->getConfig_CallForwardingOnBusy($extension);
    $statusRecording = $pMyExtension->getRecordSettings($extension);
    $extensionCID    = $pMyExtension->getExtensionCID($extension);
    $pMyExtension->AMI_CloseConnect();

    $_DATA["do_not_disturb"]    = $statusDND;
    $_DATA["call_waiting"]      = $statusCW;
    $_DATA["call_forward"]      = $statusCF["enable"];
    $_DATA["phone_number_CF"]   = isset($statusCF["phoneNumber"])?$statusCF["phoneNumber"]:_tr("Configure a phone number here...");
    $_DATA["call_forward_U"]    = $statusCFU["enable"];
    $_DATA["phone_number_CFU"]  = isset($statusCFU["phoneNumber"])?$statusCFU["phoneNumber"]:_tr("Configure a phone number here...");
    $_DATA["call_forward_B"]    = $statusCFB["enable"];
    $_DATA["phone_number_CFB"]  = isset($statusCFB["phoneNumber"])?$statusCFB["phoneNumber"]:_tr("Configure a phone number here...");
    $_DATA = array_merge($_DATA,$statusRecording);
    $_DATA = array_merge($_DATA,$_POST); //doy prefencias a los registros que vengan por POST
    
    $smarty->assign("SAVE", _tr("Save Configuration"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("icon", "images/list.png");//extension
    $smarty->assign("EXTENSION",_tr("SETTINGS FOR YOUR EXTENSION:")." ".$extensionCID." (".$extension.")");//extension
    $smarty->assign("TAG_CALL_FORW_CONF", _tr("Call Forward Configuration"));
    $smarty->assign("TAG_CALL_MON_SET", _tr("Call Monitor Settings"));
    $smarty->assign("recording_priority_value",$_DATA['recording_priority']);
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("My Extension"), $_DATA);
    return "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
}

function saveNewMyExtension($smarty, $module_name, $local_templates_dir, $arrConf, $extension, $isAdministrator)
{
    $pMyExtension = new paloSantoMyExtension();
    $oForm = new paloForm($smarty,createFieldForm());
    $message = "";
    if(!$oForm->validateForm($_POST)){
        // Validation basic, not empty and VALIDATION_TYPE 
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "{$k}[{$v['mensaje']}], ";
        }
        $smarty->assign("mb_message", $strErrorMsg);
    }
    else{
        if(isset($extension)){
            $enableDND      = getParameter("do_not_disturb");
            $enableCW       = getParameter("call_waiting");
            $enableCF       = getParameter("call_forward");
            $enableCFU      = getParameter("call_forward_U");
            $enableCFB      = getParameter("call_forward_B");//return on or off
            $phoneNumberCF  = trim(getParameter("phone_number_CF"));//is a number !!
            $phoneNumberCFU = trim(getParameter("phone_number_CFU"));
            $phoneNumberCFB = trim(getParameter("phone_number_CFB"));
            $arrRecordingStatus['recording_in_external']  = getParameter("recording_in_external");
            $arrRecordingStatus['recording_out_external'] = getParameter("recording_out_external");
            $arrRecordingStatus['recording_in_internal']  = getParameter("recording_in_internal");
            $arrRecordingStatus['recording_out_internal'] = getParameter("recording_out_internal");
            $arrRecordingStatus['recording_ondemand']     = getParameter("recording_ondemand");
            $arrRecordingStatus['recording_priority']     = getParameter("recording_priority");
            
            $pMyExtension->AMI_OpenConnect();
            $statusCW   = $pMyExtension->setConfig_CallWaiting($enableCW,$extension);
            if(!$statusCW)  $message .= _tr($pMyExtension->errMsg)."<br />";
            
            $statusDND  = $pMyExtension->setConfig_DoNotDisturb($enableDND,$extension);
            if(!$statusDND) $message .= _tr($pMyExtension->errMsg)."<br />";
            
            $statusCF   = $pMyExtension->setConfig_CallForward($enableCF,$phoneNumberCF,$extension);
            if(!$statusCF)  $message .= _tr($pMyExtension->errMsg)."<br />";
            
            $statusCFU  = $pMyExtension->setConfig_CallForwardOnUnavail($enableCFU,$phoneNumberCFU,$extension);
            if(!$statusCFU) $message .= _tr($pMyExtension->errMsg)."<br />";
            
            $statusCFB  = $pMyExtension->setConfig_CallForwardOnBusy($enableCFB,$phoneNumberCFB,$extension);
            if(!$statusCFB) $message .= _tr($pMyExtension->errMsg)."<br />";
            
            $statusRecording = $pMyExtension->setRecordSettings($extension,$arrRecordingStatus);
            if(!$statusRecording) $message .= _tr($pMyExtension->errMsg)."<br />";
            $pMyExtension->AMI_CloseConnect();
            
            if($statusCW && $statusDND && $statusCF && $statusCFU && $statusCFB && $statusRecording){
                $smarty->assign("mb_message",_tr("Your configuration has been saved correctly"));
            }
            else{
                $smarty->assign("mb_title", _tr("Error"));
                $smarty->assign("mb_message", $message);
            }
         }
         else{
	    if($isAdministrator)
		$message =  "<b>"._tr("no_extension")."</b>";
	    else
		$message =  "<b>"._tr("contact_admin")."</b>";
            
            $smarty->assign("mb_title", _tr("Notice"));
            $smarty->assign("mb_message", $message);
        }
     }
    return viewFormMyExtension($smarty, $module_name, $local_templates_dir, $arrConf, $extension);
}

function createFieldForm()
{
    $arrFields = array(
            "do_not_disturb"   => array(    "LABEL"                  => _tr("Do Not Disturb"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("off" => _tr("Disable"),"on" => _tr("Enable")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(off|on)$'
                                            ),
            "call_waiting"   => array(      "LABEL"                  => _tr("Call Waiting"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("off" => _tr("Disable"),"on" => _tr("Enable")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(off|on)$'
                                            ),
             "call_forward"   => array(     "LABEL"                  => _tr("Call Forward"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "call_forward_U"   => array(    "LABEL"                  => _tr("Call Forward on Unavailable"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "call_forward_B"   => array(    "LABEL"                  => _tr("Call Forward on Busy"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone_number_CF"     => array( "LABEL"                  => _tr("Call Forward"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:190px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone_number_CFU"     => array( "LABEL"                 => _tr("Call Forward on Unavailable"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:190px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "phone_number_CFB"     => array( "LABEL"                 => _tr("Call Forward on Busy"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:190px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "recording_in_external"=> array( "LABEL"                 => _tr("Inbound External Calls"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("always" => _tr("Always"),"dontcare" => _tr("Don't Care"),"never" => _tr("Never")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(always|dontcare|never)$'
                                            ),
            "recording_out_external"=> array( "LABEL"                 => _tr("Outbound External Calls"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("always" => _tr("Always"),"dontcare" => _tr("Don't Care"),"never" => _tr("Never")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(always|dontcare|never)$'
                                        ),
            "recording_in_internal"=> array( "LABEL"                 => _tr("Inbound Internal Calls"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("always" => _tr("Always"),"dontcare" => _tr("Don't Care"),"never" => _tr("Never")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(always|dontcare|never)$'
                                            ),
            "recording_out_internal"=> array( "LABEL"                 => _tr("Outbound Internal Calls"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("always" => _tr("Always"),"dontcare" => _tr("Don't Care"),"never" => _tr("Never")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(always|dontcare|never)$'
                                        ),
            "recording_ondemand"    => array( "LABEL"                 => _tr("On Demand Recording"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => array("disabled" => _tr("Disable"),"enabled" => _tr("Enable")),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => '^(disabled|enabled)$'
                                            ),
            "recording_priority"    => array( "LABEL"                 => _tr("Record Priority Policy"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "HIDDEN",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "recording_priority"),
                                            "VALIDATION_TYPE"        => "numeric_range",
                                            "VALIDATION_EXTRA_PARAM" => '0-20'
                                        )
            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else
        return "report"; //cancel
}
?>
