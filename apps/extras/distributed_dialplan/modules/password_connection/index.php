<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-58                                               |
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
  $Id: index.php,v 1.1 2010-12-09 02:12:32 Eduardo Cueva ecueva@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";
include_once "PHPMailer/class.phpmailer.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoPasswordConnection.class.php";

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
    $pDB = new paloDB($arrConf['dsn_conn_database']);


    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "getpassconnect":
            $content = getPassConnect($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        case "sendEmail":
            $content = sendEmail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
        default: // view_form
            $content = viewFormPasswordConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            break;
    }
    return $content;
}

function viewFormPasswordConnection($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pPasswordConnection = new paloSantoPasswordConnection($pDB);
    $arrFormPasswordConnection = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormPasswordConnection);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $_DATA['keyword'] = $pPasswordConnection->getSecretPass();
    $action = getParameter("action");
    $id     = getParameter("id");
    $smarty->assign("ID", $id); //persistence id with input hidden in tpl
    $_DATA['message'] = isset($_POST['message'])?$_POST['message']:$arrLang["I send you my 'secret connection', now you can send the request to share the dial plan."];
    if($action=="view")
        $oForm->setViewMode();
    else if($action=="view_edit" || getParameter("save_edit"))
        $oForm->setEditMode();
    //end, Form data persistence to errors and other events.

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
        $dataPasswordConnection = $pPasswordConnection->getPasswordConnectionById($id);
        if(is_array($dataPasswordConnection) & count($dataPasswordConnection)>0)
            $_DATA = $dataPasswordConnection;
        else{
            $smarty->assign("mb_title", $arrLang["Error get Data"]);
            $smarty->assign("mb_message", $pPasswordConnection->errMsg);
        }
    }

    $smarty->assign("SEND", $arrLang["Send"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("GET_PASS", $arrLang["Get my Secret Connection"]);
    $smarty->assign("message", $arrLang["Message"]);
    $smarty->assign("title_note", _tr("NOTE:"));
    $smarty->assign("note", _tr("This Key will be exclusively used for establishing connections with Remote Servers with the purpose of sharing the dialplan. Here you will be able to send this Key, via email, to the administrators of the Remote Servers you want to share your dialplan with."));
    if(!$pPasswordConnection->statusGeneralRegistration())
	$smarty->assign("mb_message", _tr("Please first fill the form in <a href='?menu=general_information'>General Information</a>"));
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",$arrLang["Password Connection"], $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;
}

function getPassConnect($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $jsonObject          = new PaloSantoJSON();
    $pPasswordConnection = new paloSantoPasswordConnection($pDB);
    $response = $pPasswordConnection->getPassConnect();
    $user                = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $msgResponse = array();
    if($user){
        $msgResponse['pass'] =  $pPasswordConnection->genRandomPassword(32,$response['certificate']);
    }
    $jsonObject->set_message($msgResponse);
    return $jsonObject->createJSON();
}

function sendEmail($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrLang)
{
    $pPasswordConnection = new paloSantoPasswordConnection($pDB);
    $arrFormPasswordConnection = createFieldForm($arrLang);
    $oForm = new paloForm($smarty,$arrFormPasswordConnection);
    exec("hostname",$arrConsole,$flagStatus);
    $hostname = $arrConsole[0];
    $subject  = getParameter("message");
    $emails   = getParameter("emails");
    $keyword  = getParameter("keyword");
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $From = 'admin@example.com';
    $uid  = Obtain_UID_From_User($user,$arrConf);
    $pDB3 = new paloDB($arrConf['dsn_scl']);
    $user_name = $pPasswordConnection->getNameUsers($uid,$pDB3,$user);
    if(!$pPasswordConnection->statusGeneralRegistration()){
	return viewFormPasswordConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
    }

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
        $content = viewFormPasswordConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
        return $content;
    }else{
        $msg = "
        <html>
            <head>
            <title>{$arrLang['Secret Connection']}</title>
            </head>
            <body>
                <h1 style='background-color:#A9A9A9; border-bottom:solid 1px #3b6d92; padding:10px 40px; font-size:28px; color:#fcfdff;'> {$arrLang['Secret Connection']} $hostname</h1>
                <div style='margin:0px 40px;'>
                    <div style='color:#000; font-size:26px; padding:15px 0px; margin-bottom:20px;'>
                        {$arrLang['Notification']}
                    </div>
                    <div style='margin-top:20px;'> 
                        <span style='font-style:italic; font-weight:bolder; font-size: 16px;'>{$arrLang['Dear User']}: </span>   
                    </div>
                    <div style='margin-top:20px; margin-bottom:30px;'>
                        {$arrLang['invitation_share']}:
                    </div>
                    <div style='margin-top:10px; margin-left:40px;'>
                        <div style='margin-top:10px; font-style:italic; font-weight:bolder;'>{$arrLang['secret']}: </div>
                        <div style='margin:0px 0px 0px 60px'>$keyword</div>
                    </div>
                    <div style='margin-top:10px; margin-left:40px;'>
                        <div style='margin-top:10px; font-style:italic; font-weight:bolder;'>{$arrLang['Message']}:</div>
                        <div style='margin:0px 0px 0px 60px'>$subject</div>
                    </div><br /><br />
                    <div style='margin-top:20px; margin-bottom:30px;'>
                        {$arrLang['steps']}:
                    </div>
                    <div style='margin:0px 0px 0px 60px'>https://[[YOUR_IP_SERVER]]/index.php?menu=peers_information&new_request=new_request</div>
                    <div style='margin-top:20px; text-align: center; color: #BEBEBE; font-size: 12px;'>   
                        <b>{$arrLang['noResponseNotification']}.</b><br />
                        <b>{$arrLang['copyrightNotification']}. 2006 - ".date("Y")."</b><br />   
                    </div>
                </div>
            </body>
        </html>";
        try{
            $mail = new PHPMailer();
            $mail->Host = "localhost";
            $mail->Body = $msg; 
            $mail->IsHTML(true); // El correo se envía como HTML
            $mail->WordWrap = 50;
            $mail->From = $From;
            $mail->FromName = $user_name;
            $emails.=",";
            $arrEmails = explode(",",$emails);
	    $subject = "{$arrLang['Secret Connection']} $hostname";
            for($i=0; $i<count($arrEmails)-1; $i++){
                $To = $arrEmails[$i];
                if($To != "" || $To !=" "){
                    $mail->ClearAddresses();
                    $mail->Subject =  utf8_decode($subject);
                    $mail->AddAddress($To, $To);
                    $mail->Send();
                }
            }
            $smarty->assign("mb_title", _tr('MESSAGE').":");
            $smarty->assign("mb_message", $arrLang['sucessful_send']);
            $_POST = "";
            $content = viewFormPasswordConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            return $content;
        }catch(phpmailerException $e){
            $smarty->assign("mb_message", $arrLang['Message cannot be sent. Try again']);
            $content = viewFormPasswordConnection($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
            return $content;
        }
    }
}

function Obtain_UID_From_User($user,$arrConf)
{
    global $arrConf;
    $pdbACL = new paloDB($arrConf['dsn_scl']);
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);
    if($uid!=FALSE)
        return $uid;
    else return -1;
}

function createFieldForm($arrLang)
{
    $arrOptions = array('val1' => 'Value 1', 'val2' => 'Value 2', 'val3' => 'Value 3');

    $arrFields = array(
            "keyword"   => array(      "LABEL"                  => $arrLang["Keyword"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "keyword", "size" => "29", "readonly" => "readonly"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "emails"   => array(      "LABEL"                  => $arrLang["Emails"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXTAREA",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "EDITABLE"               => "si",
                                            "COLS"                   => "50",
                                            "ROWS"                   => "4",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "message"   => array(      "LABEL"                  => $arrLang["Message"],
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXTAREA",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "EDITABLE"               => "si",
                                            "COLS"                   => "50",
                                            "ROWS"                   => "4",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("send")) //Get parameter by POST (submit)
        return "sendEmail";
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
    else if(getParameter("action")=="getpassconnect")
        return "getpassconnect";
    else
        return "report"; //cancel
}
?>
