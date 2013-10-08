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
  $Id: paloSantoForm.class.php,v 1.4 2007/05/09 01:07:03 gcarrillo Exp $ */
global $arrConf;
require_once("{$arrConf['elxPath']}/libs/misc.lib.php");
 
class paloHome
{
    
}

class Imap {
    public $folders;
    public $connection;

    public function login($hostname,$user, $pass) {
        $mbox = @imap_open($hostname, $user, $pass);
        if(!$mbox)
            return ('Your login failed for user <strong>'.$user.'</strong>. Please try to enter your username and password again.<br />');

        // Login worked, let us begin!!!!....

        // gather folder lost...
        $fldrs_made = 0;
        $folders = imap_listmailbox($mbox, $hostname, "*");
        // create the default folders....
        if(1 === $this->create_default_folders($mbox,$folders)) {
            $folders = imap_listmailbox($mbox, $hostname, "*");
            $fldrs_made = 1;
        }

        sort($folders);

        $this->folders = $folders;
        $this->connection = $mbox;

        if(1 === $fldrs_made)
            return ('User logged in successfully as '.$user.'. This is your first time logging in, welcome to our webmail!!!<br />');
        else
            return ('User logged in successfully as '.$user.'.<br />');
    }
    private function create_default_folders($imap_stream, $folders) {
        $change=0;
        if(!in_array('{imap.example.org}TRASH',$folders)) {
            @imap_createmailbox($imap_stream, imap_utf7_encode($hostname."TRASH"));
            $change=1;
        }
        if(!in_array('{imap.example.org}SENT',$folders)) {
            @imap_createmailbox($imap_stream, imap_utf7_encode($hostname."SENT"));
            $change=1;
        }
        if(!in_array('{imap.example.org}SPAM',$folders)) {
            @imap_createmailbox($imap_stream, imap_utf7_encode($hostname."SPAM"));
            $change=1;
        }
        if(!in_array('{imap.example.org}SENT',$folders)) {
            @imap_createmailbox($imap_stream, imap_utf7_encode($hostname."SENT"));
            $change=1;
        }
        if(!in_array('{imap.example.org}SENT',$folders)) {
            @imap_createmailbox($imap_stream, imap_utf7_encode($hostname."DRAFTS"));
            $change=1;
        }
        if(!in_array('{imap.example.org}MY_FOLDERS',$folders)) {
            @imap_createmailbox($imap_stream, imap_utf7_encode($hostname."PERSONAL EMAIL"));
            $change=1;
        }
        return $change;
    }
    public function close_mail_connection() {
        @imap_close($this->connection);
    }
}



?>
