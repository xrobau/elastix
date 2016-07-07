<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/10/09 12:48:09 jjvega Exp $ */

class paloSantoVoiceMail
{
    var $errMsg;

    function writeFileVoiceMail($Ext,$Name,$VoiceMail_PW,$VM_Email_Address, $VM_Pager_Email_Addr, $VM_Options,
                                $VM_EmailAttachment, $VM_Play_CID, $VM_Play_Envelope, $VM_Delete_Vmail, $option)
    {
        $path = "/etc/asterisk/voicemail.conf";
        if (file_exists($path))
            exec("sed -ie '/^$Ext =>/d' $path");
        else{
           $this->errMsg = "File $path does not exist";
           return false;
        }
        if( $option == 1 ){ //se modifica
            if($VM_Options!="") $VM_Options .= "|";
            if($VM_EmailAttachment!='yes') $VM_EmailAttachment = 'no';
            if($VM_Play_CID!='yes')        $VM_Play_CID = 'no';
            if($VM_Play_Envelope!='yes')   $VM_Play_Envelope = 'no';
            if($VM_Delete_Vmail!='yes')    $VM_Delete_Vmail = 'no';
            $adderLine = "$Ext => $VoiceMail_PW,$Name,$VM_Email_Address,$VM_Pager_Email_Addr,".
                         "{$VM_Options}attach=$VM_EmailAttachment|saycid=$VM_Play_CID|".
                         "envelope=$VM_Play_Envelope|delete=$VM_Delete_Vmail";
            if($fh = fopen($path, "a")){
                fputs($fh,$adderLine."\n");
                return true;
            }
            else
                return false;
        }
        else
            return true;
    }

    function loadConfiguration($extension)
    {
        $path = "/etc/asterisk/voicemail.conf";
        $grep = exec("grep '^$extension => ' $path");

        if( $grep != '' && $grep != null ){
            preg_match("/^$extension => ([[:alnum:]]*),([[:alnum:]| ]*),([[:alnum:]| |@|\.]*),([[:alnum:]| |@|\.]*),([[:alnum:]| |=]*)attach=(yes|no)\|saycid=(yes|no)\|envelope=(yes|no)\|delete=(yes|no)/i",$grep, $arrResult);
            return $arrResult;
        }
        //[0] => 408 => 1234,Desarrollo Elastix,bomv.27@gmail.com,,attach=no|saycid=no|envelope=no|delete=no
        //[1] => 1234 [2] => bomv.27@gmail.com
        //[3] => [4] => [5] => no [6] => no [7] => no [8] => no
        return null;
    }
}
?>
