<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-01 05:09:57 Bruno Macias <bmacias@palosanto.com> Exp $ */

include_once "/var/www/html/libs/cyradm.php";
include_once "/var/www/html/modules/antispam/libs/sieve-php.lib.php";

class paloSantoAntispam {
    var $fileMaster;
    var $fileLocal;
    var $folderPostfix;
    var $folderSpamassassin;
    var $errMsg;

    function paloSantoAntispam($pathPostfix,$pathSpamassassin,$fileMaster,$fileLocal)
    {
        $this->fileLocal     = $fileLocal;
        $this->fileMaster    = $fileMaster;
        $this->folderPostfix = $pathPostfix;
        $this->folderSpamassassin = $pathSpamassassin;
    }

    /*HERE YOUR FUNCTIONS*/

    function isActiveSpamFilter()
    {
        $output = $retval = NULL;
        exec('sudo /sbin/service generic-cloexec spamassassin status',
            $output, $retval);
        return ($retval == 0);
    }

    function getValueRequiredHits()
    {
        // Trato de abrir el archivo de configuracion
        $data = array();
        if($fh = @fopen($this->fileLocal, "r")) {
            while($line_file = fgets($fh, 4096)) {
                //line to valid:required_hits 5
                if(preg_match("/[[:space:]]*required_hits[[:space:]]+([[:digit:]]{0,2})/",$line_file,$arrReg)){
                        $data['level'] = $arrReg[1];
                }
                if(preg_match("/[[:space:]]*rewrite_header[[:space:]]*Subject[[:space:]]+(.*)/",$line_file,$arrReg2)){
                        $data['header'] = $arrReg2[1];
                }
            }
        }
        return $data;
    }

    function activateSpamFilter($time_spam = NULL)
    {
    	$this->errMsg = '';
        $output = $retval = NULL;
        if (!is_null($time_spam)) switch ($time_spam) {
        case 'one_week': $time_spam = 7; break;
        case 'two_week': $time_spam = 14; break;
        case 'one_month':
        default:         $time_spam = 30; break;
        }
        exec('/usr/bin/elastix-helper spamconfig --enablespamfilter'.
            (is_null($time_spam) ? '' : ' --deleteperiod '.escapeshellarg($time_spam)),
            $output, $retval);
        if ($retval != 0) {
            foreach ($output as $s) {
                $regs = NULL;
                if (preg_match('/^ERR: (.+)$/', trim($s), $regs)) {
                    $this->errMsg = $regs[1];
                }
            }
        	return FALSE;
        }
        return TRUE;
    }

    function disactivateSpamFilter()
    {
        $this->errMsg = '';
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper spamconfig --disablespamfilter',
            $output, $retval);
        if ($retval != 0) {
            foreach ($output as $s) {
                $regs = NULL;
                if (preg_match('/^ERR: (.+)$/', trim($s), $regs)) {
                    $this->errMsg = $regs[1];
                }
            }
            return FALSE;
        }
        return TRUE;
    }

    function changeFileLocal($level, $header)
    {
    	$this->errMsg = '';
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper spamconfig --setlevelheader'.
            ' --requiredhits '.escapeshellarg($level).
            ' --headersubject '.escapeshellarg($header),
            $output, $retval);
        if ($retval != 0) {
            foreach ($output as $s) {
                $regs = NULL;
                if (preg_match('/^ERR: (.+)$/', trim($s), $regs)) {
                    $this->errMsg = $regs[1];
                }
            }
        	$this->errMsg .= ' '._tr('The command failed when attempting to change the header');
            return FALSE;
        }
        return TRUE;
    }

/********************************************************************************************************************/
    //funcion que devuelve todas las cuentas de correos
    function getEmailList($pDB)
    {
        $query = "SELECT username, password FROM accountuser";
        $result=$pDB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $pDB->errMsg;
            return array();
        }
        return $result;
    }

    //funcion que crea la carpeta de Spam dado un email en el servidor IMAP mediante telnet, y la fecha desde donde no va a borrar los mensajes
    function deleteSpamMessages($email, $dateSince)
    {
        global $CYRUS;
        $cyr_conn = new cyradm;
        $error_msg = "";
        $error = $cyr_conn->imap_login();
        $dataEmail = explode("@",$email);
        if ($error===FALSE){
            $error_msg = "IMAP login error: $error <br>";
        }else{
            $seperator  = '/';
            $bValido=$cyr_conn->command(". select \"user" . $seperator . $dataEmail[0] . $seperator . "Spam@" . $dataEmail[1] ."\"");
            if(!$bValido)
                $error_msg = "Error selected Spam folder:".$cyr_conn->getMessage()."<br>";
            else{
                $bValido=$cyr_conn->command(". SEARCH NOT SINCE $dateSince"); // busca los email que no empiecen desde la fecha dada
                if(!$bValido)
                    $error_msg = "error cannot be added flags Deleted to the messages of Spam folder for $email:".$cyr_conn->getMessage()."<br>";
                else{
                    $sal  = explode("SEARCH", $bValido[0]);
                    $uids = trim($sal[1]); //ids de mensajes
                    if($uids != ""){
                        //$bValido=$cyr_conn->command(". store 1:* +flags \Deleted");
                        $uids = trim($uids);
                        $uids = str_replace(" ", ",",$uids);
                        if(strlen($uids)>100){
                            $arrID = explode(",","$uids");
                            $size = count($arrID);
                            $limitID = $arrID[0].":".$arrID[$size-1];
                            $bValido=$cyr_conn->command(". store $limitID +flags \Deleted");
                        }else
                            $bValido=$cyr_conn->command(". store $uids +flags \Deleted"); // messages $uids = 1 2 4 5 7 8
                        if(!$bValido)
                            $error_msg = "error cannot be deleted the messages of Spam folder for $email:".$cyr_conn->getMessage()."<br>";
                        else{
                            $bValido=$cyr_conn->command(". expunge");
                            if(!$bValido)
                                $error_msg = "error cannot be deleted the messages of Spam folder for $email:".$cyr_conn->getMessage()."<br>";
                            /*else{
                                $bValido=$cyr_conn->command(". noop");
                                if(!$bValido)
                                    $error_msg = "error cannot be deleted the messages of Spam folder for $email:".$cyr_conn->getMessage()."<br>";
                            }*/
                        }
                    }
                }
            }
            $cyr_conn->imap_logout();
        }
        return $error_msg;
    }

    function getTimeDeleteSpam()
    {
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper spamconfig --getdeleteperiod 2>&1', $output, $retval);
        if ($retval != 0 || count($output) < 1) return '';
        switch (trim($output[0])) {
        case '7':   return 'one_week';
        case '14':  return 'two_week';
        case '30':  return 'one_month';
        default:    return '';
        }
    }

}
?>