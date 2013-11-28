<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 3.0.0                                                |
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
  $Id: index.php,v 1.1 20013-08-26 15:24:01 wreyes wreyes@palosanto.com Exp $ */
//include elastix framework

include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //global variables
    global $arrConf;
    global $arrCredentials;
   // global $arrConfModule;
    //$arrConf = array_merge($arrConf,$arrConfModule);
  
    //folder path for custom templates
    $local_templates_dir=getWebDirModule($module_name);

    //conexion resource
    $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
   	$pACL = new paloACL($pDB);

    $pImap = new paloImap();
    /*$imap_login->login($hostname, $username ,$password);
   
    // Do some mail stuff here, like get headers...., use obj connection
    $message_headers = imap_mailboxmsginfo($imap_login->connection);
   
    // show the folders....
    //print_r($imap_login->folders, true);
   
    //print '<br /><hr size="1" noshade />';
   
    //print_r($message_headers, true);
   

    // close the connection
    $imap_login->close_mail_connection();

   // $smarty->assign("USER_NAME", $arrFill["name"]);
    $smarty->assign("MODULE_NAME", $module_name);
    $smarty->assign("id_user", $uid);
    $hostname = '{localhost:143/imap/novalidate-cert}INBOX';
    $username =  $arrFill["username"];
    $password =   $_SESSION['elastix_pass2'];
    $inbox = imap_open($hostname,$username,$password) or die('Ha fallado la conexión: ' . imap_last_error());
    $emailnum = imap_search($inbox,'ALL');
    
    $list=imap_getmailboxes($inbox,'{localhost:143/imap/novalidate-cert}',"*");*/
    
    //actions
    $accion = getAction();
    
    switch($accion){
        case "view_mail":
            $content = view_mail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "delete_mail":
            $content = delete_mail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "download_attach":
            $content = download_attach($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        default:
            $content = reportMail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
    }
    return $content;
}

function reportMail($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, &$pImap)
{
    //obtenemos el mailbox que deseamos leer
    $mailbox=getParameter('mailbox');
    $action=getParameter('action');
    
    //creamos la connección al mailbox
    $pImap->setMailbox($mailbox);
    
    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        print($pImap->errMsg);
    }
    
    $listMailbox=$pImap->getMailboxList($searh_pattern='');
    if($result===false){
        print($pImap->errMsg);
    }else{
        $smarty->assign('MAILBOX_FOLDER_LIST',$listMailbox);
    }
    
    //obtenemos el numero de 
    $emailnum = $pImap->readMailbox();
    
    //imap_clearflag_full($inbox,"1,2,3,4","\\Seen");
    if($emailnum) {
        foreach($emailnum as $email_number) {    
            $overview= imap_fetch_overview($pImap->getConnection(),$email_number,0);
            $tmp_str= substr($overview[0]->date,0,17);
            $mails[]= array("from" => $overview[0]->from,
                            "subject" => $overview[0]->subject,
                            "date"=> $tmp_str,
                            "UID"=>$email_number,
                            "status"=>$overview[0]->seen);
        }
        $mails_final = array_reverse($mails);
        $smarty->assign("MAILS",$mails_final);
    }

    $pImap->close_mail_connection();
    $smarty->assign("ICON_TYPE", "web/apps/$module_name/images/mail2.png");
   
    $smarty->assign("CONTENT_OPT_MENU",'<div class="icn_m"><span class="lp ml10 glyphicon glyphicon-envelope"></span></div>
    <div class="icn_m"><span class="lp ml10 glyphicon glyphicon-refresh"></span></div>  
    <div class="icn_m"><span class="lp ml10 glyphicon glyphicon-trash"></span></div> 
    <div class="icn_m" id="filter_but"><span class="lp ml10 glyphicon glyphicon-search"></span></div>');
    $html = $smarty->fetch("file:$local_templates_dir/form.tpl");
    $contenidoModulo = "<div>".$html."</div>";
    return $contenidoModulo;
}
function view_mail($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf,$inbox)
{
   $jsonObject = new PaloSantoJSON();
   $idMail=getParameter("idMail");
   $body=imap_qprint(imap_body($inbox,$idMail));
   $jsonObject->set_message($body);
   
   return $jsonObject->createJSON();
}
function getAction()
{
    if(getParameter("action")=="view_bodymail"){
      return "view_bodymail";  
    }else
      return "report";
}
?>
