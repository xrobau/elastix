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
include_once "libs/paloSantoGrid.class.php";

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
    
    //actions
    $accion = getAction();
    
    switch($accion){
        case "view_bodymail":
            $content = viewMail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "download_attach":
            $content = download_attach($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "get_inline_attach":
            $content = download_attach($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "mv_msg_to_folder":
            $content = moveMsgsToFolder($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "mark_msg_as":
            $content = markMsgAs($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "delete_msg_trash":
            $content = deleteMsgTrash($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "toggle_important":
            $content = toogle_important_msg($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        case "create_mailbox":
            $content = create_mailbox($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
        default:
            $content = reportMail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap);
            break;
    }
    return $content;
}

function reportMail($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, &$pImap)
{
    $jsonObject = new PaloSantoJSON();
    $arrFilter=array();
    
    //obtenemos el mailbox que deseamos leer
    $mailbox=getParameter('folder');
    $action=getParameter('action');
    
    //creamos la connección al mailbox
    $pImap->setMailbox($mailbox);
    $smarty->assign("CURRENT_MAILBOX",$pImap->getMailbox());
    
    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        if($action=='show_messages_folder'){
            $jsonObject->set_error($pImap->errMsg);
            return $jsonObject->createJSON();
        }else{
            $smarty->assign("ERROR_FIELD",$pImap->errMsg);
            return '';
        }
    }
    
    $listMailbox=$pImap->getMailboxList();
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        $smarty->assign("ERROR_FIELD",$pImap->errMsg);
    }else{
        $smarty->assign('MAILBOX_FOLDER_LIST',$listMailbox);
        $smarty->assign('NEW_FOLDER',_tr('New Folder'));
    }
    
    
    $view_filter_opt['all']=_tr("All");
    $view_filter_opt['seen']=_tr("Seen");
    $view_filter_opt['unseen']=_tr("Unseen");
    $view_filter_opt['flagged']=_tr("Important");
    $view_filter_opt['unflagged']=_tr("No Important");
    $smarty->assign("ELX_MAIL_FILTER_OPT",$view_filter_opt);
    
    $filter_view='all';
    $tmp_filter_view=getParameter('email_filter1');
    if(array_key_exists($tmp_filter_view,$view_filter_opt)){
        $filter_view=$tmp_filter_view;
    }
    $arrFilter=array("filter_view"=>$filter_view);
    
    //obtenemos el numero de correos que ahi en el buzon
    //filtrando por los parámetros dados
    $listUID=array();
    $total = $pImap->getNumMails($arrFilter,$listUID);
    if($total===false){
        $total=0;
        $jsonObject->set_error($pImap->errMsg);
        $smarty->assign("ERROR_FIELD",$pImap->errMsg);
    }
    
    $limit=50;
    //sacamos calculamos el offset
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $url['menu']=$module_name;
    $url['email_filter1']=$filter_view;
    
    $oGrid->setTitle(_tr('Contacts List'));
    $oGrid->setURL($url);
    $oGrid->setStart(($total==0) ? 0 : $offset + 1);
    $oGrid->setEnd($end);
    $oGrid->setTotal($total);
    
    $arrData=array();
    if($total!=0){
        $pImap->setMessageByPage($limit);
        $pImap->setOffset($offset);
        $emails = $pImap->readMails($listUID);
        if($emails!==false){
            foreach($emails as $email){
                $tmp=array();
                $class='elx_unseen_email';
                if($email['SEEN']==1){
                    $class='elx_seen_email';
                }
                $tmp[]="<div class='elx_row $class' id={$email['UID']}>";
                $tmp[]="<div class='sel'><input type='checkbox' value='{$email['UID']}' class='inp1 checkmail'/></div>";
                $tmp[]="<div class='ic'>";
                $tmp[]="<div class='icon'><img border='0' src='web/apps/home/images/mail2.png' class='icn_buz'></div>";
                $class='elx_unflagged_email';
                if($email['FLAGGED']==1){
                    $class='elx_flagged_email';
                }
                $tmp[]="<div class='star'><span class='st $class'>e</span></div>";
                $tmp[]="</div>";
                $tmp[]="<div class='from  elx_row_email_msg' <span>".htmlentities($email['from'],ENT_COMPAT,'UTF-8')."</span></div>";
                $tmp[]="<div class='subject elx_row_email_msg'> <span>".htmlentities($email['subject'],ENT_COMPAT,'UTF-8')."</span></div>";
                $tmp[]="<div class='date elx_row_email_msg'><span>".$email['date']."</span></div>";
                $tmp[]="</div>";
                $arrData[]=$tmp;
            }
            $smarty->assign("MAILS",$arrData);
        }else{
            $jsonObject->set_error($pImap->errMsg);
            $smarty->assign("ERROR_FIELD",$pImap->errMsg);
        }
    }

    $pImap->close_mail_connection();
    $listMailbox=array_diff($listMailbox,array($pImap->getMailBox()));
    $move_folder=array();
    foreach($listMailbox as $value){
        $move_folder[$value]=$value;
    }
    $smarty->assign("MOVE_FOLDERS",$move_folder);
    
    if($action=='show_messages_folder'){
        $message['email_content']=$arrData;
        $message['email_filter1']=$filter_view;
        $message['move_folders']=$move_folder;
        $jsonObject->set_message($message);
        return $jsonObject->createJSON();
    }
    
    $smarty->assign("ICON_TYPE","web/apps/$module_name/images/mail2.png");
 /*the red menu with images*/   
    $smarty->assign("CONTENT_OPT_MENU",'<div class="icn_m" id="email_new"><span class="lp ml10 glyphicon glyphicon-envelope" title='._tr("New").' '._tr("Mail").'></span></div>
    <div class="icn_m" id="email_refresh"><span class="lp ml10 glyphicon glyphicon-refresh" title='._tr("Refresh").'></span></div>  
    <div class="icn_m" id="email_trash"><span class="lp ml10 glyphicon glyphicon-trash" title='._tr("Trash").'></span></div> 
    <div class="icn_m" id="filter_but"><span class="lp ml10 glyphicon glyphicon-search" title='._tr("Search").'></span></div>');
    
    $mark_opt['seen']=_tr("Seen");
    $mark_opt['unseen']=_tr("Unseen");
    $mark_opt['flagged']=_tr("Important");
    $mark_opt['unflagged']=_tr("No Important");
    $smarty->assign("ELX_MAIL_MARK_OPT",$mark_opt);
    $smarty->assign("MOVE_TO",_tr("Move to"));
    $smarty->assign("MARK_AS",_tr("Mark message as"));
    
    $smarty->assign("NO_EMAIL_MSG",_tr("Not messages"));
    $smarty->assign("VIEW",_tr("View"));
    $smarty->assign("SELECTED_VIEW_FILTER",$filter_view);
    
    $smarty->assign("ACTION_MSG", _tr('Actions'));
    $arrActionsMsg['reply']=_tr('Reply');
    $arrActionsMsg['reply_all']=_tr('Reply All');
    $arrActionsMsg['forward']=_tr('Forward');
    $arrActionsMsg['delete']=_tr('Delete');
    $arrActionsMsg['flag_important']=_tr('Flag as Important');
    $arrActionsMsg['flag_unimportant']=_tr('Flag as Unimportant');
    $smarty->assign("ELX_EMAIL_MSG_ACT", $arrActionsMsg);
    
    $html = $smarty->fetch("file:$local_templates_dir/form.tpl");
    $contenidoModulo = "<div>".$html."</div>";
    return $contenidoModulo;
}
function moveMsgsToFolder($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, &$pImap){
    $jsonObject = new PaloSantoJSON();
   
    //current mailbox
    $mailbox=getParameter('current_folder');
    
    //creamos la connección al mailbox
    $pImap->setMailbox($mailbox);

    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    //lista de UIDs de mensajes a mover
    $lisUIDs=getParameter('UIDs');
    if(empty($lisUIDs)){
        $jsonObject->set_error(_tr("At_least_one"));
        return $jsonObject->createJSON();
    }
    
    $arrUID=array_diff(explode(",",$lisUIDs),array(''));
    if(!is_array($arrUID) || count($arrUID)==0){
        $jsonObject->set_error(_tr("At_least_one"));
        return $jsonObject->createJSON();
    }
    
    //carpetas a la que queremos mover los mensajes seleccionados
    $new_folder=getParameter('new_folder');
    
    if(!$pImap->moveMsgToFolder($mailbox,$new_folder,$arrUID)){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }else{
        $jsonObject->set_message(_tr("Success_mv"));
        return $jsonObject->createJSON();
    }
}
function markMsgAs($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, &$pImap){
    $jsonObject = new PaloSantoJSON();
   
    //current mailbox
    $mailbox=getParameter('current_folder');
    
    //creamos la connección al mailbox
    $pImap->setMailbox($mailbox);

    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    //lista de UIDs de mensajes a mover
    $lisUIDs=getParameter('UIDs');
    if(empty($lisUIDs)){
        $jsonObject->set_error(_tr("At_least_one"));
        return $jsonObject->createJSON();
    }
    
    $arrUID=array_diff(explode(",",$lisUIDs),array(''));
    if(!is_array($arrUID) || count($arrUID)==0){
        $jsonObject->set_error(_tr("At_least_one"));
        return $jsonObject->createJSON();
    }
    
    //carpetas a la que queremos mover los mensajes seleccionados
    $tag=getParameter('tag');
    
    if(!$pImap->markMsgFolder($tag,$arrUID)){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }else{
        $jsonObject->set_message(_tr("Success_tag"));
        return $jsonObject->createJSON();
    }
}
function toogle_important_msg($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $pImap){
    $jsonObject = new PaloSantoJSON();
   
    //current mailbox
    $mailbox=getParameter('current_folder');
    
    //creamos la connección al mailbox
    $pImap->setMailbox($mailbox);

    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    //uid del mensaje que vamos a marcar
    $uid=getParameter("uid");
    if(is_null($uid) || $uid=='' || $uid===false){
        $jsonObject->set_error('Invalid Email Message');
        return $jsonObject->createJSON();
    }
        
    //como vamos a marcar el mensaje
    $tag=getParameter('tag');
    if($tag!='flagged' && $tag!='unflagged'){
        $jsonObject->set_error('Invalid Action');
        return $jsonObject->createJSON();
    }
    
    $arrUID[]=$uid;
    
    if(!$pImap->markMsgFolder($tag,$arrUID)){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }else{
        $jsonObject->set_message(_tr("Success_tag"));
        return $jsonObject->createJSON();
    }
}
function deleteMsgTrash($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $pImap){
    $jsonObject = new PaloSantoJSON();
       
    //creamos la connección al mailbox
    $pImap->setMailbox("Trash");

    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    //lista de UIDs de mensajes a mover
    $lisUIDs=getParameter('UIDs');
    if(empty($lisUIDs)){
        $jsonObject->set_error(_tr("At_least_one"));
        return $jsonObject->createJSON();
    }
    
    $arrUID=array_diff(explode(",",$lisUIDs),array(''));
    if(!is_array($arrUID) || count($arrUID)==0){
        $jsonObject->set_error(_tr("At_least_one"));
        return $jsonObject->createJSON();
    }
    
    if(!$pImap->deleteMsgTrash($arrUID)){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }else{
        $jsonObject->set_message(_tr("Success_del"));
        return $jsonObject->createJSON();
    }
}
function viewMail($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, &$pImap)
{
    $jsonObject = new PaloSantoJSON();

    $mailbox=getParameter('current_folder');
    $pImap->setMailbox($mailbox);
    
    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    $uid=getParameter("uid");
    if(is_null($uid) || $uid=='' || $uid===false){
        $jsonObject->set_error('Invalid Email Message');
        return $jsonObject->createJSON();
    }
    
    $result=$pImap->readEmailMsg($uid);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
    }else{
        $jsonObject->set_message($result);
    }
    return $jsonObject->createJSON();
}
function create_mailbox($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap){
    $jsonObject = new PaloSantoJSON();

    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    $new_mailbox=getParameter("new_folder");
    if(is_null($new_mailbox) || $new_mailbox=='' || $new_mailbox===false){
        $jsonObject->set_error('Invalid Mailbox');
        return $jsonObject->createJSON();
    }
    
    $result=$pImap->createMailbox($new_mailbox);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
    }
    return $jsonObject->createJSON();
}
function download_attach($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap){
    $jsonObject = new PaloSantoJSON();

    $mailbox=getParameter('current_folder');
    $pImap->setMailbox($mailbox);
    
    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
        return $jsonObject->createJSON();
    }
    
    $uid=getParameter("uid");
    if(is_null($uid) || $uid=='' || $uid===false){
        $jsonObject->set_error('Invalid Email Message');
        return $jsonObject->createJSON();
    }
    
    $partNum=getParameter('partnum');
    $encoding=getParameter('enc');
    
    $pMessage=new paloImapMessage($pImap->getConnection(),$uid);
    $result=$pMessage->downloadAttachment($partNum, $encoding);
    
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
    }else{
        $jsonObject->set_message($result);
    }
    return $jsonObject->createJSON();
}
function inline_attach($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, &$pImap){
    //$jsonObject = new PaloSantoJSON();

    $mailbox=getParameter('current_folder');
    $pImap->setMailbox($mailbox);
    
    $result=$pImap->login($_SESSION['elastix_user'], $_SESSION['elastix_pass2']);
    if($result===false){
        return '';
    }
    
    $uid=getParameter("uid");
    if(is_null($uid) || $uid=='' || $uid===false){
        return '';
    }
    
    $partNum=getParameter('partnum');
    $encoding=getParameter('enc');
    
    $pMessage=new paloImapMessage($pImap->getConnection(),$uid);
    $result=$pMessage->getInlineAttach($partNum, $encoding);
    /*
    if($result===false){
        $jsonObject->set_error($pImap->errMsg);
    }else{
        $jsonObject->set_message($result);
    }
    return $jsonObject->createJSON();*/
}
function getAction()
{
    if(getParameter("action")=="view_bodymail"){
      return "view_bodymail";  
    }elseif(getParameter("action")=="mv_msg_to_folder"){
      return "mv_msg_to_folder";  
    }elseif(getParameter("action")=="mark_msg_as"){
      return "mark_msg_as";  
    }elseif(getParameter("action")=="delete_msg_trash"){
      return "delete_msg_trash";  
    }elseif(getParameter("action")=="toggle_important"){
      return "toggle_important";  
    }elseif(getParameter("action")=="create_mailbox"){
      return "create_mailbox";
    }elseif(getParameter("action")=="download_attach"){
      return "download_attach";  
    }elseif(getParameter("action")=="get_inline_attach"){
      return "get_inline_attach";
    }else
      return "report";
}
?>
