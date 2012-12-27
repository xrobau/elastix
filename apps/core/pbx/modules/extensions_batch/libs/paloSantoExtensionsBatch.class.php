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

    function createTechDevices($Ext, $Secret, $VoiceMail, $Context, $Tech, $Disallow, $Allow, $Deny, $Permit, $Callgroup, $Pickupgroup, $Record_Incoming, $Record_Outgoing)
    {
    
       $this->_DB->beginTransaction();

        $VoiceMail = strtolower($VoiceMail);        

        if(preg_match("/^enable/",$VoiceMail))
            $mailbox = "$Ext@default";
        else $mailbox = "$Ext@device";
    
        if($Tech == "iax2")
        $Tech = "iax";

        if($Tech == "sip"){
            $sql = "select count(id) from iax where id='$Ext';";
            $result = $this->_DB->getFirstRowQuery($sql);
            if(is_array($result) && count($result)>0){
                if($result[0]>0){
                    $sql = "delete from iax where id='$Ext';";
                    if(!$this->_DB->genQuery($sql))
                    {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->rollBack();
                        return false;
                    }
                }
            }
        }
        elseif($Tech == "iax"){
            $sql = "select count(id) from sip where id='$Ext';";
            $result = $this->_DB->getFirstRowQuery($sql);
            if(is_array($result) && count($result)>0){
                if($result[0]>0){
                    $sql = "delete from sip where id='$Ext';";
                    if(!$this->_DB->genQuery($sql))
                    {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->rollBack();
                        return false;
                    }
                }
            }
        }
        else{
            $this->errMsg = "Invalid $Tech ";
            $this->_DB->rollBack();
            return false;
        }

        $sql = "select count(id) from $Tech where id='$Ext';";
        $result = $this->_DB->getFirstRowQuery($sql);
        if(is_array($result) && count($result)>0)
        {
            if($result[0]>0)
            {
                $Deny = $this->validarIpMask($Deny);
                if($Deny == false){
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $Permit = $this->validarIpMask($Permit);
                if($Permit == false){
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $sql = "update $Tech set data = '$Secret'  where id='$Ext' and keyword='secret';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $sql = "update $Tech set data = '$mailbox' where id='$Ext' and keyword='mailbox';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $sql = "update $Tech set data = '$Context' where id='$Ext' and keyword='context';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $sql = "update $Tech set data = '$Disallow' where id='$Ext' and keyword='disallow';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $sql = "update $Tech set data = '$Allow' where id='$Ext' and keyword='allow';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                
                
                ///////////////////////////////////////////////////////////////////////////////////////////////
                // se valida deny y permit que en versiones anteriores no se tenía contemplado
                $sql = "select count(id) from $Tech where id='$Ext' and keyword='deny';";
                $result = $this->_DB->getFirstRowQuery($sql);
                
                if(is_array($result) && count($result)>0){
                    if($result[0]>0){
                        $sql = "update $Tech set data = '$Deny' where id='$Ext' and keyword='deny';";
                        if(!$this->_DB->genQuery($sql))
                        {
                            $this->errMsg = $this->_DB->errMsg;
                            $this->_DB->rollBack();
                            return false;
                        }
                    }
                    else{
                        $sql = "insert into $Tech (id,keyword,data)values('$Ext','deny','$Deny');";
                        if(!$this->_DB->genQuery($sql))
                        {
                            $this->errMsg = $this->_DB->errMsg;
                            $this->_DB->rollBack();
                            return false;
                        }
                    }
                    
                }
                else{
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                
                
                $sql = "select count(id) from $Tech where id='$Ext' and keyword='permit';";
                $result = $this->_DB->getFirstRowQuery($sql);
                
                if(is_array($result) && count($result)>0){
                    if($result[0]>0){
                        $sql = "update $Tech set data = '$Permit' where id='$Ext' and keyword='permit';";
                        if(!$this->_DB->genQuery($sql))
                        {
                            $this->errMsg = $this->_DB->errMsg;
                            $this->_DB->rollBack();
                            return false;
                        }
                    }
                    else{
                        $sql = "insert into $Tech (id,keyword,data)values('$Ext','permit','$Permit');";
                        if(!$this->_DB->genQuery($sql))
                        {
                            $this->errMsg = $this->_DB->errMsg;
                            $this->_DB->rollBack();
                            return false;
                        }
                    }
                    
                }
                else{
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }

                ///////////////////////////////////////////////////////////////////////////////////////////////////
                $sql = "update $Tech set data = '$Record_Incoming' where id='$Ext' and keyword='record_in';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $sql = "update $Tech set data = '$Record_Outgoing' where id='$Ext' and keyword='record_out';";
                if(!$this->_DB->genQuery($sql))
                {
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }

                if($Tech == "sip"){
                    $sql = "update $Tech set data = '$Callgroup' where id='$Ext' and keyword='callgroup';";
                    if(!$this->_DB->genQuery($sql))
                    {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->rollBack();
                        return false;
                    }
                    $sql = "update $Tech set data = '$Pickupgroup' where id='$Ext' and keyword='pickupgroup';";
                    if(!$this->_DB->genQuery($sql))
                    {
                        $this->errMsg = $this->_DB->errMsg;
                        $this->_DB->rollBack();
                        return false;
                    }
                }         
            }
            else{
                $Deny = $this->validarIpMask($Deny);
                if($Deny == false){
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }
                $Permit = $this->validarIpMask($Permit);
                if($Permit == false){
                    $this->errMsg = $this->_DB->errMsg;
                    $this->_DB->rollBack();
                    return false;
                }

                if($Tech == "iax")
                    $values = ",('$Ext','dial','IAX2/$Ext')
                        ,('$Ext','port','4569')
                        ,('$Ext','requirecalltoken','')
                        ,('$Ext','notransfer','yes')
                        ,('$Ext','setvar','REALCALLERIDNUM=$Ext');";
                else
                    $values = ",('$Ext','dial','SIP/$Ext')
                        ,('$Ext','pickupgroup','$Pickupgroup')
                        ,('$Ext','callgroup','$Callgroup')
                        ,('$Ext','port','5060')
                        ,('$Ext','nat','yes')
                        ,('$Ext','canreinvite','no')
                        ,('$Ext','dtmfmode','rfc2833');";
                    $sql =
                            "insert into $Tech (id,keyword,data) values
                            ('$Ext','record_out','$Record_Outgoing'),
                            ('$Ext','record_in','$Record_Incoming'),
                            ('$Ext','callerid','device <$Ext>'),
                            ('$Ext','account','$Ext'),
                            ('$Ext','mailbox','$mailbox'),
                            ('$Ext','accountcode',''),
                            ('$Ext','allow','$Allow'),
                            ('$Ext','disallow','$Disallow'),
                            ('$Ext','qualify','yes'),
                            ('$Ext','type','friend'),
                            ('$Ext','host','dynamic'),
                            ('$Ext','context','$Context'),
                            ('$Ext','secret','$Secret'),
                            ('$Ext','deny','$Deny'),
                            ('$Ext','permit','$Permit')
                    $values";

                        if(!$this->_DB->genQuery($sql))
                        {
                            $this->errMsg = $this->_DB->errMsg;
                            $this->_DB->rollBack();
                            return false;
                        }
            }
            $this->_DB->commit();
            return true;       
        }else{
            $this->errMsg = $this->_DB->errMsg;
            $this->_DB->rollBack();
            return false;
        }
    }

    function createUsers($Ext,$Name,$VoiceMail,$Direct_DID,$Outbound_CID, $Record_Incoming, $Record_Outgoing)
    {
        $this->_DB->beginTransaction();
        $VoiceMail = strtolower($VoiceMail);

        if(preg_match("/^enable/",$VoiceMail))
            $voicemail = "default";
        else $voicemail = "novm";

        $sql = "select count(*) from users where extension='$Ext';";
        $result = $this->_DB->getFirstRowQuery($sql);
        if(is_array($result) && count($result)>0)
        {
            if($result[0]>0)
            {
                $sql =
                    "update users set name='$Name', voicemail='$voicemail',recording='out=$Record_Outgoing|in=$Record_Incoming', outboundcid='$Outbound_CID'
                     where extension='$Ext';";
            }else{
                $sql =
                    "insert into users (
                        extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid,
                        mohclass,sipname) 
                    values (
                        '$Ext','','$Name','$voicemail',0,'','out=$Record_Outgoing|in=$Record_Incoming','$Outbound_CID',
                        'default','');";
            }
            if(!$this->_DB->genQuery($sql))
            {
                $this->errMsg = $this->_DB->errMsg;
                $this->_DB->rollBack();
                return false;
            }
            $this->_DB->commit();
            return true;
        }else{
            $this->errMsg = $this->_DB->errMsg;
            $this->_DB->rollBack();
            return false;
        }
    }

    function createDevices($Ext, $tech, $Name)
    {
        $tech = strtolower($tech);
        if($tech=='sip')
            $dial = "SIP/$Ext";
        else if($tech=='iax2' || $tech=="iax"){
        $tech = "iax2";
        $dial = "IAX2/$Ext";
    }
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
//////////////////////////////////////////////////////////////////////////////////////
    function queryDIDByExt($extension){
        $sql = "select destination, description, extension from incoming where destination like '%$extension%'";

        $result = $this->_DB->getFirstRowQuery($sql, true);

        if(!$result || $result == null)
        {
            $this->errMsg = $this->_DB->errMsg;
            return "";
        }
        if($result["extension"] != "" && $result["extension"] != null)
            return $result["extension"];
        if($result["description"] != "" && $result["description"] != null)
            return $result["description"];
        return "";
    }

    function createDirect_DID($Ext,$Direct_DID)
    {

        $sql = "select count(*) from incoming where destination like '%$Ext%';";
        $result = $this->_DB->getFirstRowQuery($sql);
        if(is_array($result) && count($result)>0)
        {
            if($result[0]>0)
            {
                $sql =
                    "update incoming set extension='$Direct_DID', description='$Direct_DID', destination='from-did-direct,$Ext,1'
                     where destination like '%$Ext%' limit 1;";
            }else{
                $sql =
                    "insert into incoming (cidnum,extension,description,destination, privacyman, alertinfo, ringing, grppre, delay_answer, pricid, pmmaxretries, pmminlength) 
                    values ('',
                        '$Direct_DID','$Direct_DID','from-did-direct,$Ext,1',0,'','','',0,'','','');";
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

    function processData($data, $path)
    {
        $arrExtensions = array();
        if(is_array($data) && count($data)>0){
   
            //Call Waiting
            $arrCallWaiting = $this->databaseCallWaiting();
            foreach($arrCallWaiting as $key => $valor)
            {
                if(preg_match("/^\/CW\/([[:alnum:]]*)[ |:]*([[:alnum:]]*)/", $valor, $arrResult))
                {
                    $arrCW[$arrResult[1]] = $arrResult[2];
                }
            }

            // Se carga la totalidad de voicemail.conf
            $voicemailData = array();
            foreach (file($path) as $s) {
               $regs = NULL;
               if (preg_match('/^\s*(\d+)\s*=>\s*(.+)/', trim($s), $regs)) {
                   $vmext = $regs[1];
                   $fields = array_map('trim', explode(',', $regs[2]));
                   $properties = array(
                       'vm_secret'              => $fields[0],
                       'email_address'          => $fields[2],
                       'pager_email_address'    => $fields[3],
                   );
                   $fields = array_map('trim', explode('|', $fields[4]));
                   foreach ($fields as $propval) {
                       $regs = NULL;
                       if (preg_match('/^(.+)=(.+)$/', $propval, $regs))
                           $properties[$regs[1]] = $regs[2];
                   }
                   $voicemailData[$vmext] = $properties;
               }
            }

            //Extension
            foreach($data as $key => $extension){
                $extension['callwaiting'] = isset($arrCW[$extension['extension']]) 
                    ? $arrCW[$extension['extension']] 
                    : 'DISABLED';
                $extension['directdid'] = $this->queryDIDByExt($extension['extension']);
                $extension['voicemail'] = 'disable';
                $extension['vm_secret'] = '';
                $extension['email_address'] = '';
                $extension['pager_email_address'] = '';
                $extension['vm_options'] = '';
                $extension['email_attachment'] = 'no';
                $extension['play_cid'] = 'no';
                $extension['play_envelope'] = 'no';
                $extension['delete_vmail'] = 'no';

                if (isset($voicemailData[$extension['extension']])) {
                    $extension['voicemail'] = 'enabled';
                    $properties = $voicemailData[$extension['extension']];
                    foreach (array(
                            'vm_secret'             => 'vm_secret',
                            'email_address'         => 'email_address',
                            'pager_email_address'   => 'pager_email_address',
                            'attach'                => 'email_attachment',
                            'saycid'                => 'play_cid',
                            'envelope'              => 'play_envelope',
                            'delete'                => 'delete_vmail',
                        ) as $k1 => $k2) {
                        if (isset($properties[$k1])) {
                            $extension[$k2] = $properties[$k1];
                            unset($properties[$k1]);
                        }
                    }
                    $vmoptions = array();
                    foreach ($properties as $k => $v) $vmoptions[] = "$k=$v";
                    $extension['vm_options'] = implode('|', $vmoptions);
                }
                $arrExtensions[] = $extension;
            }
        }
        
        return $arrExtensions;
    }

    function queryExtensions()
    {
        $path = "/etc/asterisk/voicemail.conf";
        
        $dataSIP = array();
        $dataIAX = array();
        $SIP = 0;
        $IAX = 0;
        
        $sqlSip = "select u.extension, u.name, u.outboundcid, d.tech from users u, devices d, sip s where u.extension=d.id and u.extension=s.id;";
        
        $rSIP = $this->_DB->fetchTable($sqlSip, true);
        if (!is_array($rSIP)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        else
        $SIP = 1;
        

        $sqlIAX = "select u.extension, u.name, u.outboundcid, d.tech from users u, devices d, iax i where u.extension=d.id and u.extension=i.id;";
        
        $rIAX = $this->_DB->fetchTable($sqlIAX, true);
        if (!is_array($rIAX)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        else
        $IAX = 1;
        
        if($SIP == 1 AND !empty($rSIP)){
            $extensionListSIP = array();
            foreach ($rSIP as $tupla) {
                $extensionListSIP[$tupla['extension']] = array(
                    'extension'     =>  $tupla['extension'],
                    'name'          =>  $tupla['name'],
                    'outboundcid'   =>  $tupla['outboundcid'],
                    'tech'          =>  $tupla['tech'],
                    'parameters'    =>  array(),
                );            
            }
            
            $sqlParametersSIP = "select id, keyword, data from sip ;";
            $resultSIP = $this->_DB->fetchTable($sqlParametersSIP, TRUE);
            
            if (!is_array($resultSIP)) {
                $this->errMsg = $this->_DB->errMsg;
                return NULL;
            }
                
            foreach ($resultSIP as $tupla) {
                if (isset($extensionListSIP[$tupla['id']]))
                    $extensionListSIP[$tupla['id']]['parameters'][$tupla['keyword']] = $tupla['data'];
            }
            
            $dataSIP = $this->processData($extensionListSIP,$path);
        }
        
    
        if($IAX == 1 AND !empty($rIAX)){
            $extensionListIAX = array();
            foreach ($rIAX as $tupla) {
                $extensionListIAX[$tupla['extension']] = array(
                    'extension'     =>  $tupla['extension'],
                    'name'          =>  $tupla['name'],
                    'outboundcid'   =>  $tupla['outboundcid'],
                    'tech'          =>  $tupla['tech'],
                    'parameters'    =>  array(),
                );            
            }
        
            $sqlParametersIAX = "select id, keyword, data from iax ;";
            $resultIAX = $this->_DB->fetchTable($sqlParametersIAX, TRUE);
            if (!is_array($resultIAX)) {
                $this->errMsg = $this->_DB->errMsg;
                return NULL;
            }
            
            foreach ($resultIAX as $tupla) {
                if (isset($extensionListIAX[$tupla['id']]))
                    $extensionListIAX[$tupla['id']]['parameters'][$tupla['keyword']] = $tupla['data'];
            }
        
            $dataIAX = $this->processData($extensionListIAX,$path);
        }
        
        return array_merge($dataSIP,$dataIAX);
    }
////////////////////////////////////////////////////////////////////////////////////////////
    function writeFileVoiceMail($Ext,$Name,$VoiceMail,$VoiceMail_PW,$VM_Email_Address,
            $VM_Pager_Email_Addr, $VM_Options, $VM_EmailAttachment, $VM_Play_CID,
            $VM_Play_Envelope, $VM_Delete_Vmail)
    {
        $VoiceMail = strtolower($VoiceMail);
	
        // Only numeric voicemail password allowed (Elastix bug #1238)
        if ($VoiceMail_PW != '' && !ctype_digit($VoiceMail_PW))
            return false;
	
	$path = "/etc/asterisk/voicemail.conf";
        if(file_exists($path))
             exec("sed -ie '/^$Ext =>/d' $path");
        else{
           $this->errMsg = "File $path does not exist";
           return false;
        }
        if(preg_match("/^enable/",$VoiceMail)){
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
        if (!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin()))
            $this->errMsg = "Error connect AGI_AsteriskManager";
        else{
            $salida = $astman->command("database show CW");
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["data"]);
            }else return false;
        }

        $astman->disconnect();
    }

    function processCallWaiting($callwaiting,$extension)
    {
        $callwaiting = trim(strtolower($callwaiting));
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin()))
            $this->errMsg = "Error connect AGI_AsteriskManager";

        if (preg_match("/^enable/", $callwaiting)) {
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
        $salida = array();
        $astman = new AGI_AsteriskManager();
        //$salida = array();
        
        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
            foreach($command_data as $key => $valor)
                $salida = $astman->send_request('Command', array('Command'=>"$valor"));

            $astman->disconnect();
            $salida["Response"] = isset($salida["Response"])?$salida["Response"]:"";
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

    function putDataBaseFamily($data_connection, $Ext, $tech, $Name, $VoiceMail, $Outbound_CID, $Record_Incoming, $Record_Outgoing)
    {
    if(preg_match("/^enable/",$VoiceMail))   
            $voicemail = "default";      
        else $voicemail = "novm";

        $tech = strtolower($tech);
        if($tech=='sip')
            $dial = "SIP/$Ext";
        else if($tech=='iax2' || $tech=='iax')
            $dial = "IAX2/$Ext";

        $arrFamily=array(
                "database put AMPUSER $Ext/cidname \"$Name\"",
                "database put AMPUSER $Ext/cidnum  $Ext",
                "database put AMPUSER $Ext/device  $Ext",
                "database put AMPUSER $Ext/noanswer",
                "database put AMPUSER $Ext/outboundcid $Outbound_CID",
                "database put AMPUSER $Ext/password",
                "database put AMPUSER $Ext/recording  out=$Record_Outgoing|in=$Record_Incoming",
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
    function getExtensions()
    {
       $query = "SELECT * FROM devices where tech='sip' or tech='iax2'";
       $result=$this->_DB->fetchTable($query, true);
       if( $result == false ){
           $this->errMsg = $this->_DB->errMsg;
           return array();
       }else
           return $result;         

    }
    //PASO 1: 
    //Elimina el arbol jerarquico de cada extesion de la base de datos de asterisk
    function deleteTree($data_connection, $arrAST, $arrAMP, $arrExt)
    {
      global $arrLang;
      $arrAMPUSER = array();
      $arrDEVICE = array();
      $arrCW = array();
      $arrCF = array();
      $arrCFB = array();
      $arrCFU = array();

      foreach($arrExt as $ext)
             $arrAMPUSER[] ="database deltree AMPUSER/{$ext['id']}";
      foreach($arrExt as $ext)
             $arrDEVICE[] ="database deltree DEVICE/{$ext['id']}";
      foreach($arrExt as $ext)
             $arrCW[] ="database deltree CW/{$ext['id']}";
      foreach($arrExt as $ext)
             $arrCF[] ="database deltree CF/{$ext['id']}";
      foreach($arrExt as $ext)
             $arrCFB[] ="database deltree CFB/{$ext['id']}";
      foreach($arrExt as $ext)
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

        $querys[] = "DELETE s FROM sip s INNER JOIN devices d ON s.id=d.id and d.tech='sip'";
    $querys[] = "DELETE i FROM iax i INNER JOIN devices d ON i.id=d.id and d.tech='iax2'";
        $querys[] = "DELETE u FROM users u INNER JOIN devices d ON u.extension=d.id and (d.tech='sip' or d.tech='iax2')";
        $querys[] = "DELETE FROM devices WHERE tech='sip' or tech='iax2'";
        //$querys[] = "DELETE FROM iax";

        foreach($querys as $key => $query){
            $result = $this->_DB->genQuery($query, true);
            if( $result == false )
                return $this->_DB->errMsg;            
        }
        return true;
    }

    function valida_password($Secret)
    {
        if(strlen($Secret) <= 5)
            return false;
            
        if (!preg_match("/[[:alnum:]]/", $Secret))
            return false;
            
        if (preg_match("/[[:space:]]/", $Secret))
            return false;    
        
        if (preg_match("/[[:punct:]]/", $Secret))
            return false;
        
        if (!preg_match("/[a-z]/", $Secret))
            return false;
            
        if (!preg_match("/[A-Z]/", $Secret))
            return false;
            
        if (!preg_match("/[0-9]/", $Secret))
            return false;

        return true;
    }

    function validarIpMask($ipMask)
    {
        $pattern = "/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}\/(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/";
        if ($ipMask == ""){
            $ipMask = "0.0.0.0/0.0.0.0";
        return $ipMask;
        }
        if(preg_match("/^0.0.0.0\/0.0.0.0$/", $ipMask))
            return $ipMask;
        
        if(preg_match($pattern,$ipMask))
            return $ipMask;

        if (preg_match("/&/",$ipMask)){
            $array = explode("&", $ipMask);
            foreach ($array as $clave => $valor) {
                if(!preg_match($pattern,$array[$clave]))
                    return false;
            }
            return $ipMask;
        }
        
    }
}
?>
