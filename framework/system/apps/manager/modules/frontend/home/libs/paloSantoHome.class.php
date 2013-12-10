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
*/
  
global $arrConf;
require_once("libs/misc.lib.php");
require_once("configs/email.conf.php");

class paloHome
{
    
}
/*
  @author: paloImap,v 1 2013/05/09 01:07:03 Washington Reyes wreyes@palosanto.com Exp $
  @author: paloImap,v 2 2013/11/21 01:07:03 Rocio Mera rmera@palosanto.com Exp $ */
class paloImap {
    private $user;
    private $port;
    private $host;
    private $imap_ref;
    private $mailbox='INBOX';
    private $folders;
    private $default_folders = array('Sent','Drafts','Trash','Spam');
    private $sort_field = 'date';
    private $sort_order = 'DESC';
    private $default_charset = 'ISO-8859-1';
    private $struct_charset = NULL;
    private $offset = 0;
    private $message_by_page = 3;
    public $errMsg = '';
    private $connection; //contiene la la coneccion a un IMAP a un buzon
    
    public function paloImap($mailbox='INBOX',$host='',$port='', $default_folders=''){
        global $CYRUS;
        
        $this->host=empty($host)?$host:$CYRUS['HOST'];
        $this->port=empty($port)?$port:$CYRUS['PORT'];
        $this->mailbox=empty($mailbox)?'INBOX':$mailbox;
        if(is_array($default_folders) && count($default_folders)>0);
            $this->default_folders==$default_folders;
    }
    
    public function setMailbox($mailbox){
        $this->mailbox=$mailbox;
    }
    
    public function getMailbox(){
        return $this->mailbox;
    }
    
    public function setDefaultFolders($arrFolders){
        $this->$default_folders=$arrFolders;
    }
    
    public function getConnection(){
        return $this->connection;
    }
    
    public function getMessageByPage(){
        return $this->message_by_page;
    }
    
    public function getOffset(){
        return $this->offset;
    }
    
    public function setMessageByPage($message_by_page){
        $this->message_by_page=$message_by_page;
    }
    
    public function setOffset($offset){
        $this->offset=$offset;
    }
    
    public function getMsgByPage(){
        return $this->$message_by_page;
    }
    
    public function login($user, $pass, $use_ssl=null, $validate_cert=false){
        //validamos la cuenta del usuario
        if(!preg_match("/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,4})+$/",$user)){
            $this->errMsg=_tr('Invalid Username');
            return false;
        }
        
        //validamos el password
        if($pass=='' || $pass===false){
            $this->errMsg=_tr('Password can not be empty');
            return false;
        }
        
        //TODO: revisar conecction usando ssl
        
        if($validate_cert){
            $cert_opt="validate-cert";
        }else{
            $cert_opt="novalidate-cert";
        }
        
        //validamos host, si no esta configurado usamos localhost
        $this->host=empty($this->host)?'localhost':$this->host;
        //validamos port, si no esta configurado usamos 143
        $this->port=empty($this->port)?'143':$this->port;
        
        $this->imap_ref = "{".$this->host.":".$this->port."/imap/novalidate-cert}";
        
        //el nombre dle buzon que se vaya a leer debe tener 
        $this->mailbox=empty($this->mailbox)?'INBOX':$this->mailbox;
        
        $str_connection=$this->imap_ref.imap_utf7_encode($this->mailbox);
        
        $this->connection = @imap_open($str_connection, $user, $pass);
        
        if(!$this->connection){
            $this->errMsg=_tr("Your login failed for user <strong>'.$user.'</strong>. Please try to enter your username and password again.<br/>");
            return false;
        }else
            return true;
    }

    /**
     * This function create the default mailbox
     */
    public function create_mailbox($folder) {
        $exist=false;
        //chequeamos que la carpeta no exista
        $list_mailbox=imap_list($this->connection ,$this->imap_ref, "*");
        if (is_array($list_mailbox)) {
            foreach ($list_mailbox as $mailbox) {
                if(imap_utf7_decode($mailbox)==$this->imap_ref.$folder){
                    $exist=true;
                }
            }
        } else {
            $this->errMsg="Imap_list failed: " . imap_last_error();
            return false;
        }
        
        if(!$exist){
            $result=imap_createmailbox($imap_stream, imap_utf7_encode($this->imap_ref.$folder));
            if(!$result){
                $this->errMsg="Imap_createmailbox failed: " . imap_last_error();
            }
            return $result;
        }else{
            $this->errMsg=_tr("Already exist a folder with teh same name");
            return false;
        }
    }
    
    /**
     * This function return the list of mailbox
     * whit extra info has the number of message has been read.
     * Also this function check if exist the default folders. If any of default_folders
     * does not exist this function try to create this one
     */
    public function getMailboxList($searh_pattern=''){
        $mailboxs=array();
        $mailbox_list=imap_list($this->connection,$this->imap_ref,"*");
        if (is_array($mailbox_list)) {
            //loop through rach array index
            foreach ($mailbox_list as $folder) {
                //remove any slashes
                $folder = trim(stripslashes($folder));
        
                //remove $this->imap_ref from the folderName
                $folderName = str_replace($this->imap_ref, '', $folder);
  
                $mailboxs[]=imap_utf7_decode($folderName);
                
                //procedemos a subscribir los mailboxs
                imap_subscribe($this->connection ,$folder);
            }
                        
            //chequemos que existan las carpetas por default
            //en caso de no existir las borramos
            $list_create=array_diff($this->default_folders,$mailboxs);
            foreach($list_create as $folder){
                $result=imap_createmailbox($this->connection, imap_utf7_encode($this->imap_ref.$folder));
                if(!$result){
                    $this->errMsg="Imap_createmailbox failed: " . imap_last_error();
                    return false;
                }else{
                    $mailboxs[]=imap_utf7_decode($folder);
                }
                
                //procedemos a subscribir los mailboxs
                imap_subscribe($this->connection ,imap_utf7_encode($this->imap_ref.$folder));
            }
            
            return $mailboxs;
        } else {
            $this->errMsg="Imap_list failed: " . imap_last_error();
            return false;
        }
    }
    
    public function close_mail_connection() {
        @imap_close($this->connection);
    }
    
    public function getNumMails($arrFilter,&$listUID){
        $numMessage=0;
        //This function performs a search on the mailbox currently opened in the given IMAP stream.
        //Returns an array of UIDs of mails.
        $param='ALL';
        if(isset($arrFilter['filter_view'])){
            switch($arrFilter['filter_view']){
                case 'seen':
                    $param=strtoupper($arrFilter['filter_view']);
                    break;
                case 'unseen':
                    $param=strtoupper($arrFilter['filter_view']);
                    break;
                case 'flagged':
                    $param=strtoupper($arrFilter['filter_view']);
                    break;
                case 'unflagged':
                    $param=strtoupper($arrFilter['filter_view']);
                    break;
            }
        }
        
        $emailnum = imap_sort($this->connection,SORTARRIVAL,0,SE_UID,$param);
        if($emailnum!=false){
            $listUID=$emailnum;
            $numMessage=count($listUID);
        }
        return $numMessage; 
    }
    
    public function readMails($listUID){
        $emails=array();
        if(is_array($listUID)){
            $start=(count($listUID)-1)-$this->offset;
            $end=$start-$this->message_by_page;
            if($end<=0){
                $end=-1;
            }
            for($i=$start; $i > $end ; $i--){
                $overview = imap_fetch_overview($this->getConnection(),$listUID[$i],FT_UID);
                if($overview!==false && count($overview)>0){
                    
                    $emails[]= array("from" => isset($overview[0]->from)?$overview[0]->from:'',
                                    "subject" => isset($overview[0]->subject)?$overview[0]->subject:'',
                                    "date"=> isset($overview[0]->date)?substr($overview[0]->date,0,17):'',
                                    "UID"=>isset($overview[0]->uid)?$overview[0]->uid:0,
                                    "SEEN"=>isset($overview[0]->seen)?$overview[0]->seen:0,
                                    "FLAGGED"=>isset($overview[0]->flagged)?$overview[0]->flagged:0,
                                    "RECENT"=>isset($overview[0]->recent)?$overview[0]->recent:0,
                                    "ANSWERED"=>isset($overview[0]->answered)?$overview[0]->answered:0,
                                    "DELETED"=>isset($overview[0]->deleted)?$overview[0]->deleted:0,
                                    "DRAFT"=>isset($overview[0]->draft)?$overview[0]->draft:0);
                }
            }
        }
        return $emails;
    }
    
    public function moveMsgToFolder($current_folder,$new_folder,$listUID){
        if(is_array($listUID) && count($listUID)>0){
            if($new_folder==='' || $new_folder===false || !isset($new_folder)){
                $this->errMsg=_tr("Dest_mail_inv");
                return false;
            }
            
            if($current_folder==$new_folder){
                $this->errMsg=_tr("Dest_mail_inv");
                return false;
            }
            
            //procedemos a mover los mensaje a la nueva carpeta. Usamos la bandera 'CP_UID', porque
            //pasamos como parametros los UIDs de los mensajes en lugar de la secuencia
            $result=imap_mail_move($this->getConnection(), implode(",",$listUID), imap_utf7_encode($new_folder) ,CP_UID);
            if($result==false){
                //algo paso devolvemos el error
                $this->errMsg="Imap_move failed: " . imap_last_error();
                return false;
            }else{
                //despues d eusar las funciones imap_mail_move or imap_mail_copy or imap_delete es necesary usar la 
                //funcion imap_expunge
                imap_expunge($this->getConnection()); 
                return true;
            }
        }else{
            $this->errMsg=_tr("At_least_one");
            return false;
        }
    }
    
    public function markMsgFolder($tag,$listUID){
        if(is_array($listUID) && count($listUID)>0){
            $valid_tags=array('seen','unseen','flagged','unflagged');
            
            if(!in_array($tag,$valid_tags)){
                $this->errMsg=_tr("Invalid_tag");
                return false;
            }
        
            if($tag=='unseen' || $tag=='unflagged'){
                //en el caso de unseen y unflagged lo que debemos hacer es quitar los tags seen y flagged de los mensajes
                if($tag=='unseen')
                    $tag='seen';
                elseif($tag=='unflagged')
                    $tag='flagged';
                    
                return $this->flagMsg($tag,$listUID,'unset');
            }else{
                return $this->flagMsg($tag,$listUID,'set');
            }
        }else{
            $this->errMsg=_tr("At_least_one");
            return false;
        }
    }
    
    /**
     * This function given a list UID message set or unset a especified flag
     * from this message
     * @param string flag => key flag to set or unset
     * @param array $lisUID => UID message array
     * @param string $action => action to do. This can be set or unset
     * @param bool => true if the action is success else false
     */
    private function flagMsg($flag,$listUID,$action='set'){
        $valid_tags=array('seen','flagged','deleted','answered','draft');
        if(is_array($listUID) && count($listUID)>0){
            
            if(!in_array($flag,$valid_tags)){
                $this->errMsg=_tr("Invalid_tag");
                return false;
            }
            
            //The flags which you can set are \Seen, \Answered, \Flagged, \Deleted, and \Draft as defined by » RFC2060.
            $flag="\\".ucfirst($flag);
            if($action=='set'){
                $result=imap_setflag_full($this->connection, implode(",",$listUID),$flag,ST_UID);
            }else{
                $result=imap_clearflag_full($this->connection, implode(",",$listUID),$flag,ST_UID);
            }
            
            if($result==false){
                $this->errMsg="Imap_setflag failed: " . imap_last_error();
            }
            return $result;
        }else{
            $this->errMsg=_tr("No_messages");
            return false;
        }
    }
    
    function deleteMsgTrash($listUID){
        
    }
    
    function readEmailMsg($uid){
        $body=imap_qprint(imap_body($this->getConnection(),$uid));
        return $body;
    }
    
    private function parseHeaderEmail(){
        
    }
    
    private function parseBodyEmail(){
    
    }
}

class paloImapHeader{
    
}

class paloImalMessage{
    private $app;
    private $imap;
    private $opt = array();
    private $inline_parts = array();
    private $parse_alternative = false;
  
    public $uid = null;
    public $headers;
    public $structure;
    public $parts = array();
    public $mime_parts = array();
    public $attachments = array();
    public $subject = '';
    public $sender = null;
    public $is_safe = false;
    
    function getBody($uid, $imap) {
        $body = get_part($imap, $uid, "TEXT/HTML");
        // if HTML body is empty, try getting text body
        if ($body == "") {
            $body = get_part($imap, $uid, "TEXT/PLAIN");
        }
        return $body;
    }
 
    function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
        if (!$structure) {
            $structure = imap_fetchstructure($imap, $uid, FT_UID);
        }
        if ($structure) {
            if ($mimetype == get_mime_type($structure)) {
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
                switch ($structure->encoding) {
                    case 3: return imap_base64($text);
                    case 4: return imap_qprint($text);
                    default: return $text;
            }
        }
    
            // multipart 
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = "";
                    if ($partNumber) {
                        $prefix = $partNumber . ".";
                    }
                    $data = get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }
    
    function get_mime_type($structure) {
        $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
    
        if ($structure->subtype) {
        return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }
}



?>
