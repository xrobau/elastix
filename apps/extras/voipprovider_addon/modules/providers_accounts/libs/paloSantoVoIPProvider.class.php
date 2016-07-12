<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2-2                                               |
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
  $Id: paloSantoVoIPProvider.class.php,v 1.2 2010-11-29 15:09:50 Eduardo Cueva ecueva@palosanto.com Exp $ */
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

class paloSantoVoIPProvider {
    var $_DB;
    var $errMsg;

    function paloSantoVoIPProvider(&$pDB)
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

    /*HERE YOUR FUNCTIONS*/

    function getNumVoIPProvider($filter_field, $filter_value)
    {
        $where = "";
        $query   = "SELECT COUNT(*) FROM provider_account";
        if(isset($filter_value) & $filter_value !=""){
            if($filter_field=="provider")
                $where = " pa, provider p WHERE pa.id_provider=p.id AND p.name like $filter_value";
            else
                $where = "WHERE $filter_field like $filter_value";
        }

        $query .= " $where";

        $result=$this->_DB->getFirstRowQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getVoIPProviderData($limit, $offset, $filter_field, $filter_value)
    {
        $where = "";
        $query   = "SELECT
                            pa.id AS id,
                            pa.account_name AS account_name,
                            pa.type_trunk AS type_trunk,
                            pa.id_provider AS id_provider,
                            pa.status AS status,
                            pa.callerID AS callerID,
                            pa.id_trunk AS id_trunk
                    FROM
                        provider_account pa";

        if(isset($filter_value) & $filter_value !=""){
            if($filter_field=="provider")
                $where = ", provider p WHERE pa.id_provider=p.id AND p.name like $filter_value";
            else
                $where = "WHERE $filter_field like $filter_value";
        }

        $query .= " $where
                    LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getAllTrunks()
    {
	$query = "select id,id_trunk from provider_account";
	$result = $this->_DB->fetchTable($query,true);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
	return $result;
    }

    function getVoIPProviders()
    {
        $query = "SELECT name FROM provider order by orden";
        $providers = array();
        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getInfoVoIPProvidersByName($name)
    {
        $data = array($name);
        $query = "SELECT 
                    a.type        AS type,
                    a.qualify     AS qualify,
                    a.insecure    AS insecure,
                    a.host        AS host,
                    a.fromuser    AS fromuser,
                    a.fromdomain  AS fromdomain,
                    a.dtmfmode    AS dtmfmode,
                    a.disallow    AS disallow,
                    a.context     AS context,
                    a.allow       AS allow,
                    a.trustrpid   AS trustrpid,
                    a.sendrpid    AS sendrpid,
                    a.canreinvite AS canreinvite,
                    p.type_trunk  AS type_trunk
                  FROM
                    provider p,
                    attribute a
                  WHERE
                    p.id = a.id_provider AND
                    p.name=?";
        $result=$this->_DB->getFirstRowQuery($query, true, $data);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getIdVoIPProvidersByName($name)
    {
        $data = array($name);
        $query = "SELECT id, type_trunk FROM provider WHERE name=?";
        $result=$this->_DB->getFirstRowQuery($query, true, $data);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getVoIPProviderAccountById($id)
    {
        $query = "SELECT 
                     id                AS id,
                     account_name      AS account_name,
                     username          AS username,
                     password          AS secret,
                     callerID          AS callerID,
                     type              AS type,
                     qualify           AS qualify,
                     insecure          AS insecure,
                     host              AS host,
                     fromuser          AS fromuser,
                     fromdomain        AS fromdomain,
                     dtmfmode          AS dtmfmode,
                     disallow          AS disallow,
                     context           AS context,
                     allow             AS allow,
                     trustrpid         AS trustrpid,
                     sendrpid          AS sendrpid,
                     canreinvite       AS canreinvite,
                     type_trunk        AS technology,
                     id_provider       AS id_provider,
					 status			   AS status
                  FROM provider_account WHERE id=?";
        $data = array($id);
        $result=$this->_DB->getFirstRowQuery($query,true,$data);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }
	
	function getAllAccountsActivates()
    {
        $query = "SELECT * FROM provider_account WHERE status='activate'";
        $result = $this->_DB->fetchTable($query, true);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result;
    }

    function getAllAccounts()
    {
        $query = "SELECT * FROM provider_account";
        $result = $this->_DB->fetchTable($query, true);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return $result;
    }

    function getVoIPProviderById($id)
    {
        $query = "SELECT * FROM provider WHERE id=?";
        $data = array($id);
        $result=$this->_DB->getFirstRowQuery($query,true,$data);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function insertAccount($data,$id_trunk){
        $query = "INSERT INTO provider_account(account_name,username,password,callerID,type,qualify,insecure,host,fromuser,fromdomain,dtmfmode,disallow,context,allow,trustrpid,sendrpid,canreinvite,type_trunk,id_provider,id_trunk) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $result = $this->_DB->genQuery($query, array_merge($data,array($id_trunk)));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }

    function updateAccount($data){

        $query  = "UPDATE provider_account SET account_name=?,username=?,password=?,callerID=?,type=?,qualify=?,insecure=?,host=?,fromuser=?,fromdomain=?,dtmfmode=?,disallow=?,context=?,allow=?,trustrpid=?,sendrpid=?,canreinvite=?,type_trunk=?, status=? WHERE id=?";
        $result = $this->_DB->genQuery($query, $data);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }
	
	function changeStatus($id, $status){
		$data  = array($status, $id);
		$query = "UPDATE provider_account SET status=? WHERE id=?";
        $result = $this->_DB->genQuery($query, $data);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
	}

    function deleteAccount($id){
        $data = array($id);
        $query = "DELETE FROM provider_account WHERE id=?";
        $result = $this->_DB->genQuery($query, $data);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }


    function setAsteriskFiles($dsn_agi_manager){

        //     /etc/asterisk/extensions_custom.conf          
        //     /etc/asterisk/localprefixes.conf              
        //     /etc/asterisk/sip_custom.conf                 
        //     /etc/asterisk/sip_registrations_custom.conf   
        //     /etc/asterisk/iax_custom.conf                 
        //     /etc/asterisk/iax_registrations_custom.conf   

		$FILE_IAX_CUSTOM = "/etc/asterisk/iax_custom_voipprovider.conf";
		$FILE_SIP_CUSTOM = "/etc/asterisk/sip_custom_voipprovider.conf";
		$FILE_REG_IAX    = "/etc/asterisk/iax_registrations_custom_voipprovider.conf";
		$FILE_REG_SIP    = "/etc/asterisk/sip_registrations_custom_voipprovider.conf";
		$FILE_EXT_CUSTOM = "/etc/asterisk/extensions_custom_voipprovider.conf";
		$FILE_LOCALPREFIX= "/etc/asterisk/localprefixes_voipprovider.conf";
        // creacion de archivos VoipproviderConf o eliminacion de contenido de archivos
        $this->createFilesVoipproviderConf();

        // verificacion de includes en archivos principales de asterisk
        $this->existFilesInclude();

        // Segunda accion: recorrido de todas las cuentas en la base de datos para escribir en archivos
        $accounts = $this->getAllAccountsActivates();
        $arrayConfig['globals']      = "[globals]\n";
        $arrayConfig['from-trunk']   = "";
        $arrayConfig['sip-custom']   = "";
        $arrayConfig['sip-register'] = "";
        $arrayConfig['iax-custom']   = "";
        $arrayConfig['iax-register'] = "";
        $textSipReg                  = "";
        $textIaxReg                  = "";

        if(isset($accounts) && $accounts!=""){
            $num_nextTrunk = $this->getIndexTrunk() + 1;
            foreach($accounts as $key => $value){
                $nameTrunk = $value['account_name'];
                $typeTrunk = $value['type_trunk'];
                $username  = $value['username'];
                $secret    = $value['password'];
                $callerID  = $value['callerID'];
                $host      = $value['host'];
                $type_provider = "";
                if(isset($value['id_provider']) || $value['id_provider'] !="")
                    $type_provider = $this->getVoIPProviderById($value['id_provider']);
                else
                    $type_provider['name'] = "custom";
                $type = $type_provider['name']."_".$nameTrunk;
                //por cada registro se crearan strings con datos a escribir
                $arrayConfig = $this->AddConfFileExtensionCustom($nameTrunk, $typeTrunk, $arrayConfig, $num_nextTrunk, $callerID); // escribe todo el archivo desde cero.
                //$arrayConfig = $this->addConfFileLocalPrefixes($nameTrunk, $typeTrunk, $arrayConfig, $num_nextTrunk);  // escribe reglas de la troncal
                // verificando tecnologia
                if($value['type_trunk']=="SIP"){
                    $textSipReg .="register=$username:$secret@$host/$username\n\n";
                    $arrayConfig = $this->addConfFileSipCustom($value, $type, $arrayConfig);
                }else{
                    $textIaxReg .="register=$username:$secret@$host\n\n";
                    $arrayConfig = $this->addConfFileIaxCustom($value, $type, $arrayConfig);
                }
                $num_nextTrunk++;
            }
            $text = $arrayConfig['globals'].";end of [globals]\n\n".$arrayConfig['from-trunk'];
            $this->saveFile($FILE_EXT_CUSTOM, "a", $text); // se guarda la configuracion extension_custom_voipprovider.conf
            //$this->saveFile($FILE_LOCALPREFIX, "a", $text); // se guarda las reglas de las troncales
		    $this->saveFile($FILE_REG_SIP, "a", $textSipReg);// se guardan la configuracion de sip_registrations_custom_voipprovider
		    $this->saveFile($FILE_REG_IAX, "a", $textIaxReg);
            $this->saveFile($FILE_SIP_CUSTOM, "w", $arrayConfig['sip-custom']);
		    $this->saveFile($FILE_IAX_CUSTOM, "w", $arrayConfig['iax-custom']);
        }
        // Quita accion: recargar asterisk
        $this->reloadAsterisk($dsn_agi_manager);
    }

    function createFilesVoipproviderConf(){
        $user            = "asterisk";
        $exte_custom     = "/etc/asterisk/extensions_custom_voipprovider.conf";
        $local_prefixes  = "/etc/asterisk/localprefixes_voipprovider.conf";
        $sip_custom      = "/etc/asterisk/sip_custom_voipprovider.conf";
        $sip_register    = "/etc/asterisk/sip_registrations_custom_voipprovider.conf";
        $iax_custom      = "/etc/asterisk/iax_custom_voipprovider.conf";
        $iax_register    = "/etc/asterisk/iax_registrations_custom_voipprovider.conf";

        $this->createFile($exte_custom);
        chown($exte_custom,$user);

        $this->createFile($local_prefixes);
        chown($local_prefixes,$user);

        $this->createFile($sip_custom);
        chown($sip_custom,$user);

        $this->createFile($sip_register);
        chown($sip_register,$user);

        $this->createFile($iax_custom);
        chown($iax_custom,$user);

        $this->createFile($iax_register);
        chown($iax_register,$user);
    }

    function existFilesInclude()
    {
        $FILE='/etc/asterisk/extensions_custom.conf';
        $includeLine = "#include extensions_custom_voipprovider.conf";
        $this->writeFilesInclude($FILE, $includeLine);

        $FILE='/etc/asterisk/localprefixes.conf';
        $includeLine = "#include localprefixes_voipprovider.conf";
        $this->writeFilesInclude($FILE, $includeLine);

        $FILE='/etc/asterisk/sip_custom.conf';
        $includeLine = "#include sip_custom_voipprovider.conf";
        $this->writeFilesInclude($FILE, $includeLine);

        $FILE='/etc/asterisk/sip_registrations_custom.conf';
        $includeLine = "#include sip_registrations_custom_voipprovider.conf";
        $this->writeFilesInclude($FILE, $includeLine);

        $FILE='/etc/asterisk/iax_custom.conf';
        $includeLine = "#include iax_custom_voipprovider.conf";
        $this->writeFilesInclude($FILE, $includeLine);

        $FILE='/etc/asterisk/iax_registrations_custom.conf';
        $includeLine = "#include iax_registrations_custom_voipprovider.conf";
        $this->writeFilesInclude($FILE, $includeLine);
    }

    function writeFilesInclude($FILE, $includeLine)
    {
        $fp = fopen($FILE,'a+');
        $line = "";
        $found = false;
        while($line = fgets($fp, filesize($FILE)))
        {
            if(preg_match("/".$includeLine."/", $line)){
                $found = true;
            }
        }
        if(!$found){
            $line .= "\n".$includeLine."\n";
            fwrite($fp,$line);
        }
        fclose($fp);
    }

    function AddConfFileExtensionCustom($nameTrunk, $typeTrunk, $arrayConfig, $num_nextTrunk, $callerID)
    {
        $line_conf1 = "";
        $line_conf2 = "";
 
        $line_conf1 .= "OUT_$num_nextTrunk = ".strtoupper($typeTrunk)."/$nameTrunk\n";
        $line_conf1 .= "OUTPREFIX_$num_nextTrunk =\n";
        $line_conf1 .= "OUTMAXCHANS_$num_nextTrunk =\n";
        $line_conf1 .= "OUTCID_$num_nextTrunk = $callerID\n";
        $line_conf1 .= "OUTKEEPCID_$num_nextTrunk = off\n";
        $line_conf1 .= "OUTFAIL_$num_nextTrunk =\n";
        $line_conf1 .= "OUTDISABLE_$num_nextTrunk = off\n";
        $line_conf1 .= "FORCEDOUTCID_$num_nextTrunk =\n\n";

        $line_conf2 .= "\n";
        $line_conf2 .= "[from-trunk-$typeTrunk-$nameTrunk]\n";
        $line_conf2 .= "include => from-trunk-$typeTrunk-$nameTrunk-custom\n";
        $line_conf2 .= "exten => _.,1,Set(GROUP()=OUT_$num_nextTrunk)\n";
        $line_conf2 .= "exten => _.,n,Goto(from-trunk,\${EXTEN},1)\n";
        $line_conf2 .= "; end of [from-trunk-$typeTrunk-$nameTrunk]\n";

        $arrayConfig['globals']    = $arrayConfig['globals'].$line_conf1;
        $arrayConfig['from-trunk'] = $arrayConfig['from-trunk'].$line_conf2;

        return $arrayConfig;
    }

    //solo guarda las troncales que contienen reglas
    function addConfFileLocalPrefixes($nameTrunk, $typeTrunk, $arrayConfig, $num_nextTrunk)
    {


    }

	function saveFile($FILE, $type_mode, $text)
	{
		$fp = fopen($FILE, $type_mode);
        fwrite($fp, $text);
        fclose($fp);
	}

    function addConfFileSipCustom($data, $type, $arrayConfig)
    {
        $text ="[$type]\n";
        if($data['username']!=null) $text .= "username={$data['username']}\n";
        if($data['type']!=null) $text .= "type={$data['type']}\n";
        if($data['password']!=null) $text .= "secret={$data['password']}\n";
        if($data['qualify']!=null) $text .= "qualify={$data['qualify']}\n";
        if($data['insecure']!=null) $text .= "insecure={$data['insecure']}\n";
        if($data['host']!=null) $text .= "host={$data['host']}\n";
        if($data['fromuser']!=null) $text .= "fromuser={$data['fromuser']}\n";
        if($data['fromdomain']!=null) $text .= "fromdomain={$data['fromdomain']}\n";
        if($data['dtmfmode']!=null) $text .= "dtmfmode={$data['dtmfmode']}\n";
        if($data['disallow']!=null) $text .= "disallow={$data['disallow']}\n";
        if($data['context']!=null) $text .= "context={$data['context']}\n";
        if($data['allow']!=null) $text .= "allow={$data['allow']}\n";
        if($data['trustrpid']!=null) $text .= "trustrpid={$data['trustrpid']}\n";
        if($data['sendrpid']!=null) $text .= "sendrpid={$data['sendrpid']}\n";
        if($data['canreinvite']!=null) $text .= "canreinvite={$data['canreinvite']}\n";
        $text .= "\n";
        $arrayConfig['sip-custom'] = $arrayConfig['sip-custom'].$text;
        return $arrayConfig;
    }

    function addConfFileIaxCustom($data, $type, $arrayConfig)
    {
        $text ="[$type]\n";
        if($data['username']!=null) $text .= "username={$data['username']}\n";
        if($data['type']!=null) $text .= "type={$data['type']}\n";
        if($data['password']!=null) $text .= "secret={$data['password']}\n";
        if($data['qualify']!=null) $text .= "qualify={$data['qualify']}\n";
        if($data['insecure']!=null) $text .= "insecure={$data['insecure']}\n";
        if($data['host']!=null) $text .= "host={$data['host']}\n";
        if($data['fromuser']!=null) $text .= "fromuser={$data['fromuser']}\n";
        if($data['fromdomain']!=null) $text .= "fromdomain={$data['fromdomain']}\n";
        if($data['dtmfmode']!=null) $text .= "dtmfmode={$data['dtmfmode']}\n";
        if($data['disallow']!=null) $text .= "disallow={$data['disallow']}\n";
        if($data['context']!=null) $text .= "context={$data['context']}\n";
        if($data['allow']!=null) $text .= "allow={$data['allow']}\n";
        if($data['trustrpid']!=null) $text .= "trustrpid={$data['trustrpid']}\n";
        if($data['sendrpid']!=null) $text .= "sendrpid={$data['sendrpid']}\n";
        if($data['canreinvite']!=null) $text .= "canreinvite={$data['canreinvite']}\n";
        $text .= "\n";

        $arrayConfig['iax-custom'] = $arrayConfig['iax-custom'].$text;
        return $arrayConfig;
    }

    function getIndexTrunk()
    {
        $FILE1='/etc/asterisk/extensions_additional.conf';
        $FILE2='/etc/asterisk/extensions_custom.conf';

        $dataTrunk1 = array();
        $dataTrunk2 = array();
        $fp1 = fopen($FILE1,'r');
        $fp2 = fopen($FILE2,'r');

        // OUT_index = type/name
        //OUT_4      = SIP/to_sip1.starvox.com
        $regex = "/^OUT_([[:digit:]]+) =/";

        while($line = fgets($fp1, filesize($FILE1))) {
            if(preg_match($regex, $line, $arrReg))
                $dataTrunk1[] = $arrReg[1];
        }
        fclose($fp1);

        while($line = fgets($fp2, filesize($FILE2))) {
            if(preg_match($regex, $line, $arrReg))
                $dataTrunk2[] = $arrReg[1];
        }
        fclose($fp2);

        array_push($dataTrunk1,$dataTrunk2);
        sort($dataTrunk1,SORT_NUMERIC);
        return array_pop($dataTrunk1);
    }

    function createFile($FILE){
        $fp = fopen($FILE, 'w');
        fwrite($fp, "");
        fclose($fp);
    }

    function reloadAsterisk($dsn_agi_manager)
    {
        $arrResult = $this->AsteriskManager_Command($dsn_agi_manager['host'], $dsn_agi_manager['user'], $dsn_agi_manager['password'], "reload");
    }

    function AsteriskManager_Command($host, $user, $password, $command) {
        global $arrLang;
        $astman = new AGI_AsteriskManager( );
        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
            $salida = $astman->Command("$command");
            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["data"]);
            }
        }
        return false;
    }
}
?>