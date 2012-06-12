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

    function createSipDevices($Ext, $Secret, $VoiceMail, $Context)
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
                $sql = "update sip set data = '$mailbox' where id='$Ext' and keyword='mailbox';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
                $sql = "update sip set data = '$Context' where id='$Ext' and keyword='context';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
            }else{
                $sql =
                    "insert into sip (id,keyword,data) values
                    ('$Ext','record_out','Adhoc'),
                    ('$Ext','record_in','Adhoc'),
                    ('$Ext','callerid','device <$Ext>'),
                    ('$Ext','account','$Ext'),
                    ('$Ext','mailbox','$mailbox'),
                    ('$Ext','accountcode',''),
                    ('$Ext','dial','SIP/$Ext'),
                    ('$Ext','allow',''),
                    ('$Ext','disallow',''),
                    ('$Ext','pickupgroup',''),
                    ('$Ext','callgroup',''),
                    ('$Ext','qualify','yes'),
                    ('$Ext','port','5060'),
                    ('$Ext','nat','yes'),
                    ('$Ext','type','friend'),
                    ('$Ext','host','dynamic'),
                    ('$Ext','context','$Context'),
                    ('$Ext','canreinvite','no'),
                    ('$Ext','dtmfmode','rfc2833'),
                    ('$Ext','secret','$Secret');";

                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
            }
            return true;
        }else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function createUsers($Ext,$Name,$VoiceMail,$Direct_DID,$Outbound_CID)
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
                    "update users set name='$Name', voicemail='$voicemail', outboundcid='$Outbound_CID'
                     where extension='$Ext';";
            }else{
                $sql =
                    "insert into users (
                        extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid,
                        mohclass,sipname) 
                    values (
                        '$Ext','','$Name','$voicemail',0,'','out=Adhoc|in=Adhoc','$Outbound_CID',
                        'default','');";
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
                        id,tech,dial,devicetype,user,description,emergency_cid) 
                    values (
                        '$Ext','$tech','$dial','fixed','$Ext','$Name','');";
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
                    (select u.extension, u.name, u.outboundcid, d.tech from users u, devices d where u.extension=d.id) as r1,
                    (select data as secret, id from sip where keyword='secret') as r2,
                    (select data as context, id from sip where keyword='context') as r3
                where r1.extension=r2.id and r1.extension=r3.id;";
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
                $extension['directdid'] = "";
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
            $salida = $astman->command("database show CW");
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

        //para crear los archivos de configuracion en /etc/asterisk
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
        //$salida = array();
        
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

    function putDataBaseFamily($data_connection, $Ext, $tech, $Name, $VoiceMail)
    {
	if(eregi("^enable",$VoiceMail)) 	 
            $voicemail = "default"; 	 
        else $voicemail = "novm";

        $tech = strtolower($tech);
        if($tech=='sip')
            $dial = "SIP/$Ext";
        else if($tech=='iax2')
            $dial = "IAX2/$Ext";

        $arrFamily=array(
                "database put AMPUSER $Ext/cidname \"$Name\"",
                "database put AMPUSER $Ext/cidnum  $Ext",
                "database put AMPUSER $Ext/device  $Ext",
                "database put AMPUSER $Ext/noanswer",
                "database put AMPUSER $Ext/outboundcid",
                "database put AMPUSER $Ext/password",
                "database put AMPUSER $Ext/recording  out=Adhoc|in=Adhoc",
                "database put AMPUSER $Ext/ringtimer 0",
                "database put AMPUSER $Ext/voicemail $voicemail",
                "database put DEVICE $Ext/default_user $Ext",
                "database put DEVICE $Ext/dial $dial",
                "database put DEVICE $Ext/type fixed",
                "database put DEVICE $Ext/user $Ext");

        return $this->AsteriskManager_Command($data_connection['host'],
                                              $data_connection['user'],
                                              $data_connection['password'],
                                              $arrFamily);
    }
    //Esta funcion obtiene todas las extensiones tipo SIP
    function getExtensionSip()
    {
       $query = "SELECT * FROM devices where tech='sip'";
       $result=$this->_DB->fetchTable($query, true);
       if( $result == false ){
           $this->errMsg = $this->_DB->errMsg;
           return array();
       }else
           return $result;         

    }
    //PASO 1: 
    //Elimina el arbol jerarquico de cada extesion de la base de datos de asterisk
    function deleteTreeSip($data_connection, $arrAST, $arrAMP, $arrSipExt)
    {
      global $arrLang;
	  $arrAMPUSER = array();
	  $arrDEVICE = array();
	  $arrCW = array();
	  $arrCF = array();
	  $arrCFB = array();
	  $arrCFU = array();

      foreach($arrSipExt as $ext)
             $arrAMPUSER[] ="database deltree AMPUSER/{$ext['id']}";
      foreach($arrSipExt as $ext)
             $arrDEVICE[] ="database deltree DEVICE/{$ext['id']}";
      foreach($arrSipExt as $ext)
             $arrCW[] ="database deltree CW/{$ext['id']}";
      foreach($arrSipExt as $ext)
             $arrCF[] ="database deltree CF/{$ext['id']}";
      foreach($arrSipExt as $ext)
             $arrCFB[] ="database deltree CFB/{$ext['id']}";
      foreach($arrSipExt as $ext)
             $arrCFU[] ="database deltree CFU/{$ext['id']}";                              

      //BLQOUE AMPUSER/extension      
      $AMPresult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $arrAMPUSER ); 
      if($AMPresult == false){
            $this->errMsg = $arrLang["Unable delete AMPUSER in database astDB"];
            return false;
      }

      //BLQOUE DEVICE/extension
      $DEVICEresult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $arrDEVICE ); 
      if($DEVICEresult == false){
            $this->errMsg = $arrLang["Unable delete DEVICE in database astDB"];
            return false;
      }

      //BLQOUE CW/extension
      $CWresult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $arrCW ); 
      if($CWresult == false){
            $this->errMsg = $arrLang["Unable delete CW in database astDB"];
            return false;
      }

      //BLQOUE CF/extension
      $CFresult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $arrCF ); 
      if($CFresult == false){
            $this->errMsg = $arrLang["Unable delete CF in database astDB"];
            return false;
      }

      //BLQOUE CFB/extension
      $CFBresult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $arrCFB ); 
      if($CFBresult == false){
            $this->errMsg = $arrLang["Unable delete CFB in database astDB"];
            return false;
      }

      //BLQOUE CFU/extension
      $CFUresult = $this->AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $arrCFU ); 
      if($CFBresult == false){
            $this->errMsg = $arrLang["Unable delete CFU in database astDB"];
            return false;
      }
      return true;
    }

    //PASO 2: borrar las 2 tablas (sip, devices, users) + IAX
    //Funcion que borra todas las extenciones
    function deleteAllExtension()
    {
        $querys = array();

        $querys[] = "DELETE FROM sip";
        $querys[] = "DELETE u FROM users u INNER JOIN devices d ON u.extension=d.id and d.tech='sip'";
        $querys[] = "DELETE FROM devices WHERE tech='sip'";
        //$querys[] = "DELETE FROM iax";

        foreach($querys as $key => $query){
            $result = $this->_DB->genQuery($query, true);
            if( $result == false )
                return $this->_DB->errMsg;            
        }
        return true;
    }
}
?>
