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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/01/31 21:31:55 afigueroa Exp $ */

//ini_set("display_errors", true);
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

class paloSantoLoadExtension {
    var $_DB;
    var $errMsg;

    function paloSantoLoadExtension(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    function createSipDevices($Ext, $Secret, $VoiceMail)
    {
        $VoiceMail = strtolower($VoiceMail);

        if(eregi("^enable",$VoiceMail))
            $mailbox = "$Ext@default";
        else $mailbox = "$Ext@device";

        $sql = "select count(id) from sip where id='$Ext';";
        $result = $this->_DB->getFirstRowQuery($sql);
        if(is_array($result) && count($result)>0)
        {
            if($result[0]>0)
            {
                $sql = "update sip set data = '$Secret'  where id='$Ext' and keyword='secret';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
                //2do sql se ejecuta abajo
                $sql = "update sip set data = '$mailbox' where id='$Ext' and keyword='mailbox';";
            }else{
                $sql =
                    "insert into sip (id,keyword,data) values
                    ('$Ext','secret','$Secret'),
                    ('$Ext','dtmfmode','rfc2833'),
                    ('$Ext','canreinvite','no'),
                    ('$Ext','context','from-internal'),
                    ('$Ext','host','dynamic'),
                    ('$Ext','type','friend'),
                    ('$Ext','nat','yes'),
                    ('$Ext','port','5060'),
                    ('$Ext','qualify','yes'),
                    ('$Ext','callgroup',''),
                    ('$Ext','pickupgroup',''),
                    ('$Ext','disallow',''),
                    ('$Ext','allow',''),
                    ('$Ext','dial','SIP/$Ext'),
                    ('$Ext','accountcode',''),
                    ('$Ext','mailbox','$mailbox'),
                    ('$Ext','call-limit','4'),
                    ('$Ext','account','$Ext'),
                    ('$Ext','callerid','device <$Ext>'),
                    ('$Ext','record_in','Adhoc'),
                    ('$Ext','record_out','Adhoc');";
            }

            if(!$this->_DB->genQuery($sql))
            {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
            return true;
        }else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function createUsers($Ext,$Name,$VoiceMail,$Direct_DID)
    {
        $VoiceMail = strtolower($VoiceMail);

        if(eregi("^enable",$VoiceMail))
            $voicemail = "default";
        else $voicemail = "novm";

        $sql = "select count(*) from users where extension='$Ext';";
        $result = $this->_DB->getFirstRowQuery($sql);
        if(is_array($result) && count($result)>0)
        {
            if($result[0]>0)
            {
                $sql =
                    "update users set name='$Name', voicemail='$voicemail', directdid='$Direct_DID'
                     where extension='$Ext';";
            }else{
                $sql =
                    "insert into users (
                        extension,name,voicemail,ringtimer,recording,
                        directdid,faxexten,answer,wait,privacyman,
                        mohclass) 
                    values (
                        '$Ext','$Name','$voicemail',0,'out=Adhoc|in=Adhoc',
                        '$Direct_DID','default',0,0,0,
                        'acc_1');";
            }
            if(!$this->_DB->genQuery($sql))
            {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
            return true;
        }else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function createDevices($Ext, $tech, $Name)
    {
        $tech = strtolower($tech);
        if($tech=='sip')
            $dial = "SIP/$Ext";
        else if($tech=='iax2')
            $dial = "IAX2/$Ext";

        $sql = "select count(*) from devices where id='$Ext';";
        $result = $this->_DB->getFirstRowQuery($sql);
        if(is_array($result) && count($result)>0)
        {
            if($result[0]>0)
            {
                $sql =
                    "update devices set tech='$tech', dial='$dial', description='$Name'
                     where id='$Ext'";
            }else{
                $sql =
                    "insert into devices (
                        id,tech,dial,devicetype,user,description) 
                    values (
                        '$Ext','$tech','$dial','fixed','$Ext','$Name');";
            }
            if(!$this->_DB->genQuery($sql))
            {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
            return true;
        }else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function queryExtensions()
    {
        $path = "/etc/asterisk/voicemail.conf";

        $sql = "select * from
                    (select u.extension, u.name, u.directdid, d.tech from users u, devices d where u.extension=d.id) as r1,
                    (select data as secret, id from sip where keyword='secret') as r2
                where r1.extension=r2.id;";
        $result = $this->_DB->fetchTable($sql, true);
        $arrExtensions = array();

        if(is_array($result) && count($result)>0){
            //Call Waiting
            $arrCallWaiting = $this->databaseCallWaiting();
            foreach($arrCallWaiting as $key => $valor)
            {
                if(eregi("^/CW/([[:alnum:]]*)[ |:]*([[:alnum:]]*)", $valor, $arrResult))
                {
                    $arrCW[$arrResult[1]] = $arrResult[2];
                }
            }

            //Extension
            foreach($result as $key => $extension){
                $extension['callwaiting']=isset($arrCW[$extension['extension']]) ? $arrCW[$extension['extension']] : 'DISABLED';

                $extension['voicemail'] = 'disable';
                $extension['vm_secret'] = '';
                $extension['email_address'] = '';
                $extension['pager_email_address'] = '';
                $extension['vm_options'] = '';
                $extension['email_attachment'] = 'no';
                $extension['play_cid'] = 'no';
                $extension['play_envelope'] = 'no';
                $extension['delete_vmail'] = 'no';

                $grep = exec("grep '^{$extension['extension']}' $path");
                if($grep != '' && $grep!=null)
                {
                    $extension['voicemail'] = 'enabled';
                    if(eregi("^{$extension['extension']} => ([[:alnum:]]*),[[:alnum:]| ]*,([[:alnum:]| |@|\.]*),([[:alnum:]| |@|\.]*),([[:alnum:]| |=]*)attach=(yes|no)\|saycid=(yes|no)\|envelope=(yes|no)\|delete=(yes|no)",$grep, $arrResult))
                    {
                        $extension['vm_secret'] = $arrResult[1];
                        $extension['email_address'] = $arrResult[2];
                        $extension['pager_email_address'] = $arrResult[3];
                        $extension['vm_options'] = substr($arrResult[4],0, strlen($arrResult[4])-1);
                        $extension['email_attachment'] = $arrResult[5];
                        $extension['play_cid'] = $arrResult[6];
                        $extension['play_envelope'] = $arrResult[7];
                        $extension['delete_vmail'] = $arrResult[8];
                    }
                }
                $arrExtensions[] = $extension;
            }
        }
        return $arrExtensions;
    }

    function writeFileVoiceMail($Ext,$Name,$VoiceMail,$VoiceMail_PW,$VM_Email_Address,
            $VM_Pager_Email_Addr, $VM_Options, $VM_EmailAttachment, $VM_Play_CID,
            $VM_Play_Envelope, $VM_Delete_Vmail)
    {
        $path = "/etc/asterisk/voicemail.conf";
        $VoiceMail = strtolower($VoiceMail);

        if(eregi("^enable",$VoiceMail)){
            exec("sed -ie '/^$Ext =>/d' $path");
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
            else{
                return false;
            }
        }else return true;
    }

    function databaseCallWaiting()
    {
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect("127.0.0.1", 'admin' , 'elastix456'))
            $this->errMsg = "Error connect AGI_AsteriskManager";
        else{
            $salida = $astman->command("database show");
            if (strtoupper($salida["Response"]) != "ERROR") {
                return split("\n", $salida["data"]);
            }else return false;
        }

        $astman->disconnect();
    }

    function processCallWaiting($callwaiting,$extension)
    {
        $callwaiting = trim(strtolower($callwaiting));
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect("127.0.0.1", 'admin' , 'elastix456'))
            $this->errMsg = "Error connect AGI_AsteriskManager";

        if (eregi("^enable", $callwaiting)) {
            $r = $astman->command("database put CW $extension \"ENABLED\"");
            return (bool)strstr($r["data"], "success");
        } else {
            $r = $astman->command("database del CW $extension");
            return (bool)strstr($r["data"], "removed") || (bool)strstr($r["data"], "not exist");
        }

        $astman->disconnect();
    }

    function do_reloadAll($data_connection, $arrAST, $arrAMP) {
        $bandera = true;

        if (isset($arrAMP["PRE_RELOAD"]['valor']) && !empty($arrAMP['PRE_RELOAD']['valor'])){
            exec( $arrAMP["PRE_RELOAD"]['valor']);
        }

        $retrieve = $arrAMP['AMPBIN']['valor'].'/retrieve_conf';
        exec($retrieve);

        //reload MOH to get around 'reload' not actually doing that, reload asterisk
        $command_data = array("moh reload", "reload");
        $arrResult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);

        if (isset($arrAMP['FOPRUN']['valor'])) {
            //bounce op_server.pl
            $wOpBounce = $arrAMP['AMPBIN']['valor'].'/bounce_op.sh';
            exec($wOpBounce.' &>'.$arrAST['astlogdir']['valor'].'/freepbx-bounce_op.log');
        }

        //store asterisk reloaded status
        $sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'";
        if(!$this->_DB->genQuery($sql))
        {
            $this->errMsg = $this->_DB->errMsg;
            $bandera = false;
        }

        if (isset($arrAMP["POST_RELOAD"]['valor']) && !empty($arrAMP['POST_RELOAD']['valor']))  {
            exec( $arrAMP["POST_RELOAD"]['valor']);
        }

        if(!$bandera) return false;
        else return true;
    }

    function AsteriskManager_Command($host, $user, $password, $command_data) {
        global $arrLang;
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
            foreach($command_data as $key => $valor)
                $salida = $astman->send_request('Command', array('Command'=>"$valor"));

            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return split("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }
}
?>