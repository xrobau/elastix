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
    private $list_page = 1;
    private $page_size = 10;
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
    
    public function setDefaultFolders($arrFolders){
        $this->$default_folders=$arrFolders;
    }
    
    public function getConnection(){
        return $this->connection;
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
                    $mailboxs[]=$folder;
                }
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
    
    public function readMailbox(){
        $emailnum = imap_search($this->connection,'ALL');
        return $emailnum; 
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
    
    
}



?>
