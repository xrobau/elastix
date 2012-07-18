<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version {ELASTIX_VERSION}                                    |
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
  $Id: {FILE_NAME},v 1.1 {DATE} {YOUR_NAME} {YOUR_EMAIL} Exp $ */

global $arrConf;

include_once $arrConf['basePath']."/libs/paloSantoACL.class.php";
include_once $arrConf['basePath']."/libs/paloSantoConfig.class.php";
include_once $arrConf['basePath']."/libs/paloSantoAsteriskConfig.class.php";
include_once $arrConf['basePath']."/libs/extensions.class.php";
include_once $arrConf['basePath']."/libs/misc.lib.php";

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

class paloAsteriskDB {
	public $_DB;
	public $errMsg;
	public $dsn;

	function paloAsteriskDB(&$pDB)
    {
		if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        }else{
			$this->setdsn();
			$pDB = new paloDB($this->dsn);
			if(!empty($pDB->errMsg)) {
				$this->errMsg = $pDB->errMsg;
			} else{
			$this->_DB = $pDB;
			}
		}
    }

	function setdsn()
	{
		$pConfig = new paloConfig("/var/www/elastixdir/asteriskconf", "/elastix_pbx.conf", "=", "[[:space:]]*=[[:space:]]*");
		$arrConfig = $pConfig->leer_configuracion(false);
		$this->dsn = "mysql://" . $arrConfig['DBUSER']['valor'] . ":" . $arrConfig['DBPASSWORD']['valor'] . "@" . $arrConfig['DBHOST']['valor'] . "/".$arrConfig['DBNAME']['valor'];
	}

	function executeQuery($query,$arrayParam)
	{
		$result=$this->_DB->genQuery($query,$arrayParam);
		if($result==false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}
		return $result;
	}

	function getResultQuery($query,$arrayParam,$assoc=false,$noexits="Don't exist registers.")
	{
		$result=$this->_DB->fetchTable($query,$assoc,$arrayParam);
		if($result===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}elseif($result==false){
			$this->errMsg = $noexits;
			return false;
		}else{
			return $result;
		}
	}

	function getFirstResultQuery($query,$arrayParam,$assoc=false,$noexits="Don't exist registers.")
	{
		$result=$this->_DB->getFirstRowQuery($query,$assoc,$arrayParam);
		if($result===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}elseif($result==false){
			$this->errMsg = $noexits;
			return false;
		}else{
			return $result;
		}
	}

	//esto es para los numeros de extensiones internas
	function validateName($deviceName)
	{
		if(preg_match("/^(organization[[:digit:]]{3}){1}_[[:digit:]]+$/", $deviceName)){
			return true;
		}else{
			return false;
		}
	}

	function prunePeer($device,$tech){
		if(!$this->validateName($device))
		{
			$this->errMsg=_tr("Invalid device name");
			return false;
		}

		$errorM="";
		$astMang=AsteriskManagerConnect($errorM);
		if($astMang==false){
			$this->errMsg=$errorM;
			return false;
		}else{ //borro las propiedades dentro de la base ASTDB de asterisk
			$result=$astMang->prunePeer($tech,$device);
		}
		return true;
	}

}

class paloSip extends paloAsteriskDB {
	public $name; 			// name device, unique id for that
	public $context;  		// Default context for incoming calls. Defaults to 'default'
	public $callingpres;
	public $permit; //="0.0.0.0/0.0.0.0";
	public $deny; //="0.0.0.0/0.0.0.0";
	public $secret;
	public $md5secret;
	public $remotesecret;
	public $dial;
	public $transport;
	public $dtmfmode; //="rfc2833";
	public $directmedia;
	public $nat; //="yes";
	public $callgroup;
	public $pickupgroup;
	public $language;
	public $allow;
	public $disallow;
	public $insecure;
	public $trustrpid;
	public $progressinband;
	public $promiscredir;
	public $useclientcode;
	public $accountcode;
	public $setvar;
	public $callerid;
	public $fullname;
	public $cid_number;
	public $amaflags;
	public $callcounter;
	public $busylevel;
	public $allowoverlap;
	public $allowsubscribe;
	public $allowtransfer;
	public $ignoresdpversion;
	public $subscribecontext;
	public $template;
	public $videosupport;
	public $maxcallbitrate;
	public $rfc2833compensate;
	public $mailbox;
	public $session_timers; //nombre del campo en la tabla session-timers
	public $session_expires; //nombre del campo en la tabla session-expires
	public $session_minse; //nombre del campo en la tabla session-minse
	public $session_refresher; //nombre del campo en la tabla session-refresher
	public $t38pt_usertpsource;
	public $regexten;
	public $fromdomain;
	public $fromuser;
	public $host; //="dynamic";
	public $port; //="5060";
	public $qualify; //="yes";
    public $type; //="friend";
	public $defaultip; //setear null si no se la va a usar
	public $defaultuser;
	public $rtptimeout;
	public $rtpholdtimeout;
	public $sendrpid;
	public $outboundproxy;
	public $callbackextension;
	public $registertrying;
	public $timert1;
	public $timerb;
	public $fullcontact;
	public $ipaddr;
	public $qualifyfreq;
	public $vmexten;
	public $contactpermit;
	public $contactdeny;
	public $lastms;
	public $regserver;
	public $regseconds;
	public $useragent;
	public $constantssrc;
	public $usereqphone;
	public $textsupport;
	public $faxdetect; //="no";
	public $buggymwi;
	public $auth;
	public $trunkname;
	public $mohinterpret;
	public $mohsuggest;
	public $parkinglot;
	public $hasvoicemail;
	public $subscribemwi;
	public $autoframing;
	public $rtpkeepalive;
	public $call_limit; //nombre del campo en la tabla call-limit
	public $g726nonstandard;
	public $organization_domain;

	function paloSip(&$pDB)
	{
		if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        }else{
			$this->setdsn();
			$pDB = new paloDB($this->dsn);
			if(!empty($pDB->errMsg)) {
				$this->errMsg = $this->_DB->errMsg;
			} else{
				$this->_DB = $pDB;
			}
		}
	}

	function existPeer($deviceName)
	{
		if($this->validateName($deviceName)){
			$query="Select count(name) from sip where name=?";
			$arrayParam=array($deviceName);
			$result=$this->getFirstResultQuery($query,$arrayParam);
			if($result[0]==0)
			{
				return false;
			}else
				$this->errMsg="Already exist a sip peer with same name";
		}else{
			$this->errMsg="Invalid sip name";
		}
		return true;
	}

	function hashMd5Secret($name,$secret)
	{
		$cadena=$name.":asterisk:".$secret;
		exec("echo -n '$cadena' | md5sum",$opt,$ret);
		if($ret!=0)
		{
			$this->errMsg="Error setting secret for sip device";
			return null;
		}else{
			$md5secret=trim(substr($opt[0],0,strpos($opt[0],'-')));
			return $md5secret;
		}
	}

	function insertDB($code)
	{
		//valido que no exista otro dispositivo sip creado con el mismo nombre
		if(!isset($this->name) || !isset($this->md5secret) || !isset($this->context)){
			$this->errMsg="Field name, secret, context can't be empty";
		}elseif(!$this->existPeer($code."_".$this->name)){
			$arrValues=array();
			$question="(";
			$Prop="(";
			$i=0;
			$arrPropertyes=get_object_vars($this);
			foreach($arrPropertyes as $key => $value){
				if(isset($value) && $key!="_DB" && $key!="errMsg" && $key!="dsn"){
					switch ($key){
						case "session_timers":
							$Prop .="session-timers,";
							break;
						case "session_expires":
							$Prop .="session-expires,";
							break;
						case "session_minse":
							$Prop .="session-minse,";
							break;
						case "session_refresher":
							$Prop .="session-refresher,";
							break;
						case "call-limit":
							$Prop .="call-limit,";
							break;
						case "context":
							$Prop .=$key.",";
							$value = $code."-".$value;
							break;
						case "name":
							$Prop .=$key.",";
							$value = $code."_".$value;
							break;
						default:
							$Prop .=$key.",";
							break;
					}
					$arrValues[$i]=$value;
					$question .="?,";
					$i++;
				}
			}

			$question=substr($question,0,-1).")";
			$Prop=substr($Prop,0,-1).")";

			$query="INSERT INTO sip $Prop value $question";
			if($this->executeQuery($query,$arrValues)){
				return true;
			}
		}
		return false;
	}

	function setGroupProp($arrProp,$domain)
	{
		foreach($arrProp as $name => $value){
			if(property_exists($this,$name)){
				if(isset($value) && $value!="")
					$this->$name=$value;
			}
		}

		$defaultSetting=$this->getDefaultSettings($domain);
		foreach($defaultSetting as $key => $valor){
			if(isset($valor) && $valor!="" && property_exists($this,$key))
			{
				if(!isset($this->$key))
					$this->$key=$valor;
			}
		}
	}

	//esta funcion solo debe ser llamada desde palodevice
	function deletefromDB($deviceName)
	{
		$query="delete from sip where name=?";
		if($this->executeQuery($query,array($deviceName))){
			return true;
		}else
			return false;
	}

	function getDefaultSettings($domain)
	{
		$query="SELECT * from sip_general where organization_domain=?";
		$arrResult=$this->getFirstResultQuery($query,array($domain),true,"Don't exist registers.");
		if($arrResult==false)
			return array();
		else
			return $arrResult; 
	}

	function setParameter($device,$parameter,$value){
		$query="UPDATE sip set $parameter=? where name=?";
		if($this->executeQuery($query,array($parameter,$value,$device))){
			return true;
		}
		return false;
	}

	function setSecret($device,$secret,$organization){
		$query="update sip set md5secret=? where name=? and organization_domain=?";
		$secret=$this->hashMd5Secret($device,$secret);
		if(is_null($secret))
			$this->errMsg="Error setting secret for sip channel.";
		else{
			if($this->executeQuery($query,array($secret,$device,$organization))){
				$this->prunePeer($device,"sip");
				return true;
			}else{
				$this->errMsg="Secret couldn't be updated for sip channel. ".$this->errMsg;
				return false;
			}
		}
	}

}


class paloIax extends paloAsteriskDB {
	public $name;
	public $type; //="friend";
	public $username;
	public $mailbox;
	public $secret;
	public $dial;
	public $dbsecret;
	public $context;
	public $regcontext;
	public $host; //="dynamic";
	public $ipaddr; //no setearlos, Must be updateable by Asterisk user
	public $port; //Must be updateable by Asterisk user;
	public $defaultip;
	public $sourceaddress;
	public $mask;
	public $regexten;
	public $regseconds;
	public $accountcode;
	public $mohinterpret;
	public $mohsuggest;
	public $inkeys;
	public $outkeys;
	public $language;
	public $callerid;
	public $cid_number;
	public $sendani;
	public $fullname;
	public $trunk;
	public $auth;
	public $maxauthreq;
	public $requirecalltoken;
	public $encryption;
	public $transfer; //="no";
	public $jitterbuffer;
	public $forcejitterbuffer;
	public $disallow;
	public $allow;
	public $codecpriority;
	public $qualify; //="yes";
	public $qualifysmoothing;
	public $qualifyfreqok;
	public $qualifyfreqnotok;
	public $timezone;
	public $adsi;
	public $amaflags;
	public $setvar;
	public $deny; //no llenar nada menos que sea necesario
	public $permit; //no llenar nada menos que sea necesario
	public $organization_domain;

	function paloIax(&$pDB)
	{
		if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        }else{
			$this->setdsn();
			$pDB = new paloDB($this->dsn);
			if(!empty($pDB->errMsg)) {
				$this->errMsg = $this->_DB->errMsg;
			} else{
				$this->_DB = $pDB;
			}
		}
	}

	function existPeer($deviceName)
	{
		if($this->validateName($deviceName)){
			$query="Select count(name) from iax where name=?";
			$arrayParam=array($deviceName);
			$result=$this->getFirstResultQuery($query,$arrayParam);
			if($result[0]==0)
			{
				return false;
			}else
				$this->errMsg="Already exist a iax peer with this name";
		}else{
			$this->errMsg="Invalid peer name";
		}
		return true;
	}

	function hashMd5Secret($name,$secret)
	{
		/*$cadena=$name.":asterisk:".$secret;
		exec("echo -n '$cadena' | md5sum",$opt,$ret);
		if($ret!=0)
		{
			$this->errMsg="Error setting secret for device";
			return null;
		}else{
			$md5secret=trim(substr($opt[0],0,strpos($opt[0],'-')));
			return $md5secret;
		}*/
		return $secret;
	}

	function insertDB($code)
	{
		//valido que no exista otro dispositivo iax creado con el mismo nombre
		if(!isset($this->name) || !isset($this->secret) || !isset($this->context)){
			$this->errMsg="Field name, secret, context can't be empty";
		}elseif(!$this->existPeer($code."_".$this->name)){
			$arrValues=array();
			$question="(";
			$Prop="(";
			$i=0;
			$arrPropertyes=get_object_vars($this);
			foreach($arrPropertyes as $key => $value){
				if(isset($value) && $key!="_DB" && $key!="errMsg" && $key!="dsn"){
					if($key=="context")
						$value = $code."-".$value;
					if($key=="name")
						$value = $code."_".$value;
					$Prop .=$key.",";
					$arrValues[$i]=$value;
					$question .="?,";
					$i++;
				}
			}

			$question=substr($question,0,-1).")";
			$Prop=substr($Prop,0,-1).")";

			$query="INSERT INTO iax $Prop value $question";
			if($this->executeQuery($query,$arrValues)){
				return true;
			}
		}
		return false;
	}

	//TODO:
	function setParameter($device,$parameter,$value){
		$query="UPDATE iax set $parameter=? where name=?";
		if($this->executeQuery($query,array($value,$device))){
			return true;
		}
		return false;
	}

	function setGroupProp($arrProp,$domain)
	{
		foreach($arrProp as $name => $value){
			if(property_exists($this,$name)){
				if(isset($value) && $value!="")
					$this->$name=$value;
			}
		}

		$defaultSetting=$this->getDefaultSettings($domain);
		foreach($defaultSetting as $key => $valor){
			if(isset($valor) && $valor!="" && property_exists($this,$key))
			{
				if(!isset($this->$key))
					$this->$key=$valor;
			}
		}
	}

	function deletefromDB($deviceName)
	{
		$query="delete from iax where name=?";
		if($this->executeQuery($query,array($deviceName))){
			return true;
		}else
			return false;
	}

	function getDefaultSettings($domain)
	{
		$query="SELECT * from iax_general where organization_domain=?";
		$arrResult=$this->getFirstResultQuery($query,array($domain),true,"Don't exist registers.");
		if($arrResult==false)
			return array();
		else
			return $arrResult;
	}

	//TODO: Ver la forma de autentificar iax usando md5
	// mandar el password codificado usando md5
	function setSecret($device,$secret,$organization){
		$query="update iax set secret=? where name=? and organization_domain=?";
		$secret=md5($secret);
		if($this->executeQuery($query,array($secret,$device,$organization))){
			$this->prunePeer($device,"iax");
			return true;
		}else{
			$this->errMsg="Secret couldn't be updated for iax channel. ".$this->errMsg;
			return false;
		}
	}

}


class paloVoicemail extends paloAsteriskDB{
	public $context;
	public $mailbox; // Mailbox number.  Should be numeric.
	public $password; //  Must be numeric.  Negative if you don't want it to be changed from VoicemailMain
	public $fullname; // Used in email and for Directory app
	public $email; //Email address (will get sound file if attach=yes)
	public $pager; // Email address (won't get sound file)
	public $attach; //Attach sound file to email - YES/no
    public $attachfmt;//Which sound format to attach
	public $serveremail; // Send email from this address
	public $language; //Prompts in alternative language
	public $tz;// Alternative timezone, as defined in voicemail.conf
	public $deletevoicemail;//Delete voicemail from server after sending email notification - yes/NO
	public $saycid;// Read back CallerID information during playback - yes/NO
	public $sendvoicemail; //Allow user to send voicemail from within VoicemailMain - YES/no
	public $review; //Listen to voicemail and approve before sending - yes/NO
	public $tempgreetwarn; //Warn user a temporary greeting exists - yes/NO
	public $operator; // Allow '0' to jump out during greeting - yes/NO
	public $envelope;// Hear date/time of message within VoicemailMain - YES/no
	public $sayduration;//Hear length of message within VoicemailMain - yes/NO
	public $saydurationm;//Minimum duration in minutes to say
	public $forcename;// Force new user to record name when entering voicemail - yes/NO
	public $forcegreetings;//Force new user to record greetings when entering voicemail - yes/NO
	public $callback;//Context in which to dial extension for callback
	public $dialout;// Context in which to dial extension (from advanced menu)
	public $exitcontext;// Context in which to execute 0 or * escape during greeting
	public $maxmsg;// Maximum messages in a folder (100 if not specified)
	public $volgain;//Increase DB gain on recorded message by this amount (0.0 means none)
	public $imapuser;//IMAP user for authentication (if using IMAP storage)
	public $imappassword;// IMAP password for authentication (if using IMAP storage)
	public $stamp;
	public $organization_domain;

	function paloVoicemail(&$pDB)
	{
		if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        }else{
			$this->setdsn();
			$pDB = new paloDB($this->dsn);
			if(!empty($pDB->errMsg)) {
				$this->errMsg = $this->_DB->errMsg;
			} else{
				$this->_DB = $pDB;
			}
		}
	}

	function createVoicemail($code)
	{
		//valido que no exista otro dispositivo iax creado con el mismo nombre
		if(!isset($this->mailbox) || !isset($this->password) || !isset($this->context)){
			$this->errMsg="Field Mailbox, Voicemail Password, Voicemail Context , can't be empty";
		}else{
			$arrValues=array();
			$question="(";
			$Prop="(";
			$i=0;
			$arrPropertyes=get_object_vars($this);
			foreach($arrPropertyes as $key => $value){
				if(isset($value) && $key!="_DB" && $key!="errMsg"){
					if($key=="callback" || $key=="dialout" || $key=="exitcontext" || $key=="context")
						$value = $code."-".$value;
					$Prop .=$key.",";
					$arrValues[$i]=$value;
					$question .="?,";
					$i++;
				}
			}
			

			$question=substr($question,0,-1).")";
			$Prop=substr($Prop,0,-1).")";

			$query="INSERT INTO voicemail $Prop value $question";
			if($this->executeQuery($query,$arrValues)){
				return true;
			}
		}
		return false;
	}

	//TODO:
	function editVoicemail()
	{
		
	}

	function deletefromDB($vm,$context)
	{
		$query="delete from voicemail where mailbox=? and context=?";
		if($this->executeQuery($query,array($vm,$context))){
			return true;
		}else
			return false;
	}

	function setVoicemailProp($arrProp,$domain)
	{
		foreach($arrProp as $name => $value){
			if(property_exists($this,$name)){
				if(isset($value) && $value!="")
					$this->$name=$value;
			}
		}

		$defaultSetting=$this->getDefaultSettings($domain);
		foreach($defaultSetting as $key => $valor){
			if(isset($valor) && $valor!="" && property_exists($this,$key))
			{
				if(!isset($this->$key))
					$this->$key=$valor;
			}
		}
	}

	function getDefaultSettings($domain)
	{
		$query="SELECT * from voicemail_general where organization_domain=?";
		$arrResult=$this->getFirstResultQuery($query,array($domain),true,"Don't exist registers.");
		if($arrResult==false)
			return array();
		else
			return $arrResult;
	}

	//TODO: el password del voicemail es el numero de voicemail -- esto para que la clave del usuario no este
	// en texto plano aqui- POSIBLE HUECO DE SEGURIDAD
	function setPassword($mailbox,$password,$organization){
		$query="update voicemail set password=? where mailbox=? and organization_domain=?";
		if($this->executeQuery($query,array($password,$mailbox,$organization))){
			return true;
		}else{
			$this->errMsg="Password couldn't be updated for voicemail. ".$this->errMsg;
			return false;
		}
	}

}


class paloDevice{
	public $tecnologia;
	protected $domain;
	protected $code="";
	public $errMsg;

	function paloDevice($domain,$type,&$pDB2)
	{
		require_once("libs/paloSantoOrganization.class.php");
		if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
			$this->errMsg="Invalid domain format";
		}

		$this->domain=$domain;
		$pDB=new paloDB("sqlite3:////var/www/db/elastix.db");
		$pOrgz=new paloSantoOrganization($pDB);
		$resCode=$pOrgz->getOrganizationCode($domain);
		$this->code=$resCode["code"];

		if($type=="sip")
			$this->tecnologia=new paloSip($pDB2);
		elseif($type=="iax2")
			$this->tecnologia=new paloIax($pDB2);
		elseif($type=="dadhi")
			$this->tecnologia=new paloDadhi($pDB2);
		else{
			$this->errMsg="Invalid technology name";
		}
	}

	function getCode(){
		return $this->code;
	}


	/**
		funcion utilizada para crear una nueva extension en asterisk
		crea el peer y hace el correspondiente registro de la extension
		en la base elx_pbx
	*/
	function createNewDevice($arrProp,$type)
	{
		$device=$this->code."_".$arrProp['name'];
		if(!$this->existExtension($arrProp['name'],$type)){//se verifica que no exista otra extension y dispositivo igual
														   //tambien se setea la tecnologia adecuada dentro de esta funcion
			$arrProp['dial'] = strtoupper($type)."/".$device;

			//validamos que se haya ingresado un secret para el dispositivo
			if(isset($arrProp['secret']) && $arrProp['secret']!=""){
				if($type=="sip"){
					$arrProp['md5secret']=$this->tecnologia->hashMd5Secret($device,$arrProp['secret']);
					$arrProp['secret']="";
				}
			}else{
				$this->errMsg="Field secret can't be empty";
				return false;
			}

			$arrProp['organization_domain']=$this->domain;
			//validamos ip de campos permit y deny
			/*$arrProp['permit'] = ($arrProp['permit'] == "" || !isset($arrProp['permit']))? "0.0.0.0/0.0.0.0" : $arrProp['permit'];
			$arrProp['deny'] = ($arrProp['deny'] == "" || !isset($arrProp['deny']))? "0.0.0.0/0.0.0.0" : $arrProp['deny'];
			print($arrProp['permit']);
			print($arrProp['deny']);
			$permit = explode(".",$arrProp['permit']);
			$deny = explode(".",$arrProp['deny']);
			if(count($permit)!=4 || count($deny)!=4){
				$this->errMsg="Invalid value for ip in field permit o deny";
				return false;
			}else{
				foreach(array_merge($permit,$deny) as $value){
					if($value>255 || $value<0)
						$this->errMsg="Invalid value for ip in field permit o deny";
						return false;
				}
			}*/

			//seteamos el callerid del equipo
			$arrProp['callerid']="device <".$arrProp['name'].">";

			if($arrProp['create_vm']=="yes"){
				$arrVoicemail["context"] = isset($arrProp["vmcontext"])?$arrProp["vmcontext"]:null;
				$arrVoicemail["mailbox"] = isset($arrProp['name'])?$arrProp["name"]:null;
				$arrVoicemail["password"] = isset($arrProp["vmpassword"])?$arrProp["vmpassword"]:null;
				$arrVoicemail["email"] = isset($arrProp["vmemail"])?$arrProp["vmemail"]:null;
				$arrVoicemail["attach"] = isset($arrProp["vmattach"])?$arrProp["vmattach"]:null;
				$arrVoicemail["saycid"] = isset($arrProp["vmsaycid"])?$arrProp["vmsaycid"]:null;
				$arrVoicemail["envelope"] = isset($arrProp["vmenvelope"])?$arrProp["vmenvelope"]:null;
				$arrVoicemail["deletevoicemail"] = isset($arrProp["vmdelete"])?$arrProp["vmdelete"]:null;
				//leer las caractirsiticas que el usuario puede poner en vmoptions, estas deben estar separadas por un " | "
				if(isset($arrProp['vmoptions']))
				{
					$arrTemp=explode("|",$arrProp['vmoptions']);
					foreach($arrTemp as $value){
						$arrVmOpt=explode("=",$value);
						if(count($arrVmOpt)==2)
							$arrVoicemail[$arrVmOpt[0]]=$arrVmOpt[1];
					}
				}
				
				//mandar a crear el voicemail
				$pVM=new paloVoicemail($this->tecnologia->_DB);
				$pVM->setVoicemailProp($arrVoicemail,$this->domain);
				if($pVM->createVoicemail($this->code)==false){
					$this->errMsg="Error setting parameter voicemail ".$pVM->errMsg;
					return false;
				}
			}

			if($arrProp['create_vm']=="yes"){
				$arrProp['mailbox']=$arrProp['name']."@".$this->code."-".$pVM->context;
				$arrProp["voicemail_context"]=$this->code."-".$pVM->context;
			}else
				$arrProp["voicemail_context"]="novm";

			//mandar a crear el dispositivo usando realtime
			$this->tecnologia->setGroupProp($arrProp,$this->domain);
			if($this->tecnologia->insertDB($this->code)==false){
				$this->errMsg="Error setting parameter $type device ".$this->tecnologia->errMsg;
				return false;
			}

			//guardar los setting en la tabla extensions; para despues con esta informacion procede a crear las extensiones de tipo local en el plan de marcado
			if(isset($arrProp['rt'])){
				if(!preg_match("/^[[:digit:]]+$/",$arrProp['rt']) && !($arrProp['rt']>0 && $arrProp['rt']<60))
					$arrProp['rt']=0;
				else
					$arrProp['rt']=$arrProp['rt'];
			}else
				$arrProp['rt']=0;

			//validamos los recording
			switch(strtolower($arrProp["record_in"])){
				case "always":
					$arrProp["record_in"]="always";
				case "never":
					$arrProp["record_in"]="never";
				default:
					$arrProp["record_in"]="on_demand";
			}

			//validamos los recording
			switch(strtolower($arrProp["record_out"])){
				case "always":
					$arrProp["record_in"]="always";
				case "never":
					$arrProp["record_in"]="never";
				default:
					$arrProp["record_in"]="on_demand";
			}

			$exito=$this->insertExtensionDB($this->domain,$type,$arrProp['dial'],$arrProp['name'],$device,$arrProp['rt'],$arrProp['record_in'],$arrProp['record_out'],$this->tecnologia->context,$arrProp["voicemail_context"],"","","","");
			if($exito){
				if($this->insertDeviceASTDB($arrProp))
					return true;
				else{
					$this->errMsg="Extension couldn't be created .".$this->errMsg;
					return false;
				}
			}else{
				$this->errMsg="Problem when trying insert data in table extensions. ".$this->errMsg;
				return false;
			}
		}else{
			$this->errMsg="This number extension already exists .".$this->errMsg;
			return false;
		}
		
	}

	function createFaxExtension($arrProp){
		$type="iax2";
		if(!$this->existExtension($arrProp['name'],$type)){
			$device=$this->code."_".$arrProp['name'];
			$arrProp['dial'] = strtoupper($type)."/".$device;
			$arrProp['organization_domain']=$this->domain;
			if(isset($arrProp['rt'])){
				if(!preg_match("/^[[:digit:]]+$/",$arrProp['rt']) && !($arrProp['rt']>0 && $arrProp['rt']<60))
					$arrProp['rt']=0;
				else
					$arrProp['rt']=$arrProp['rt'];
			}else
				$arrProp['rt']=0;

			$this->tecnologia->setGroupProp($arrProp,$this->domain);
			if($this->tecnologia->insertDB($this->code)==false){
				$this->errMsg="Error setting parameter $type device ".$this->tecnologia->errMsg;
				return false;
			}

			$exito=$this->insertFaxExtensionDB($this->domain,$type,$arrProp['dial'],$arrProp['name'],$device,$arrProp['rt'],$this->tecnologia->context,$arrProp['fullname'],$arrProp['cid_number']);
			if($exito){
				return true;
			}else{
				$this->errMsg="Problem when trying insert data in table extensions. ".$this->errMsg;
				return false;
			}
		}else{
			$this->errMsg="This number extension already exists .".$this->errMsg;
			return false;
		}
	}

	//en la tabla debe haber un unico numero de de extension por dominio
	private function insertExtensionDB($domain,$tech,$dial,$exten,$device,$rt,$record_in,$record_out,$context,$voicemail,$outboundcid,$alias,$mohclass,$noanswer)
	{
		$query="INSERT INTO extension (organization_domain,tech,dial,exten,device,rt,record_in,record_out,context,voicemail,outboundcid,alias,mohclass,noanswer) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$result=$this->tecnologia->executeQuery($query,array($domain,$tech,$dial,$exten,$device,$rt,$record_in,$record_out,$context,$voicemail,$outboundcid,$alias,$mohclass,$noanswer));
		if($result==false)
			$this->errMsg="Couldn't be saved parameters in table extensions ".$this->tecnologia->errMsg;
		return $result; 
	}

	//en la tabla debe haber un unico numero de de extension por dominio
	private function insertFaxExtensionDB($domain,$tech,$dial,$exten,$device,$rt,$context,$callerIDname,$callerIDnumber)
	{
		$query="INSERT INTO fax (organization_domain,tech,dial,exten,device,rt,context,callerid_name,callerid_number) values (?,?,?,?,?,?,?,?,?)";
		$result=$this->tecnologia->executeQuery($query,array($domain,$tech,$dial,$exten,$device,$rt,$context,$callerIDname,$callerIDnumber));
		if($result==false)
			$this->errMsg="Couldn't be saved parameters in table extensions ".$this->tecnologia->errMsg;
		return $result;
	}

	private function insertDeviceASTDB($arrProp)
	{
		if(!preg_match("/^organization[[:digit:]]{3}$/", $this->code)){
			$this->errMsg="Invalid code format";
			return false;
		}

		if(!isset($arrProp["name"]) || $arrProp["name"]==""){
			$this->errMsg="Extension can not be empty";
			return false;
		}

		$arrSetting=array();

		if(isset($arrProp["clid_number"]) && $arrProp["clid_number"]!="")
			$arrSetting["cidnum"]="\"".$arrProp['clid_number']."\"";
		else
			$arrSetting["cidnum"]=$arrProp["name"];

		if(isset($arrProp["fullname"]) && $arrProp["fullname"]!="")
			$arrSetting["cidname"]="\"".$arrProp["fullname"]."\"";
		else
			$arrSetting["cidname"]=$arrProp["name"];

		$arrSetting["device"]=$this->code."_".$arrProp["name"];

		$arrSetting["language"]=isset($arrProp["language"])?$arrProp["language"]:"\"\"";
		$arrSetting["noanswer"]=isset($arrProp["noanswer"])?$arrProp["noanswer"]:"\"\"";
		$arrSetting["outboundcid"]=isset($arrProp["outboundcid"])?$arrProp["outboundcid"]:"\"\"";
		$arrSetting["password"]=isset($arrProp["password"])?$arrProp["password"]:"\"\"";


		$arrSetting["ringtimer"]=$arrProp['rt'];

		$arrSetting["voicemail"]=$arrProp["voicemail_context"];

		switch(strtolower($arrProp["record_in"])){
				case "always":
					$stRecord="in=always";
				case "never":
					$stRecord="in=never";
				default:
					$stRecord="in=on_demand";
			}
			$stRecord .="|";
			//validamos los recording
			switch(strtolower($arrProp["record_out"])){
				case "always":
					$stRecord .="out=always";
				case "never":
					$stRecord .="out=never";
				default:
					$stRecord .="out=on_demand";
			}

		$arrSetting["recording"]=$stRecord;

		$error=false;
		$code=$this->code;
		$familia="EXTUSER/$code/".$arrProp['name'];
		$arrInsert=array();

		$errorM="";
		$astMang=AsteriskManagerConnect($errorM);
		if($astMang==false){
			$this->errMsg=$errorM;
			return false;
		}else{ //seteo las propiedades en la base ASTDB de asterisk
			foreach($arrSetting as $key => $value){
				$result=$astMang->database_put($familia,$key,$value);
				if(strtoupper($result["Response"]) == "ERROR"){
					$this->errMsg = _tr("Couldn't be inserted data in ASTDB");
					$error=true;
					break;
				}
				$arrInsert[$key]=$value;
			}
		}

		//se guardan los datos del dispositivo, esto realmente sirvecuando a un dispositivo se le ha asociado
		//varias extensiones, por el momento esto no esta soportado
		$family="DEVICE/$code/$code"."_".$arrProp['name'];
		$arrKey=array("default_user"=>$arrProp['name'],"dial"=>$arrProp['dial'],"type"=>"fixed","user"=>$arrProp['name']);
		foreach($arrKey as $key => $value){
			$result=$astMang->database_put($family,$key,$value);
			if(strtoupper($result["Response"]) == "ERROR"){
				$this->errMsg = _tr("Couldn't be inserted data in ASTDB");
				$error=true;
				break;
			}
			$arrInsert[$key]=$value;
		}

		//si se habilito el callwaiting ingresa ese dato a la base ASTDB
		if(isset($arrProp['callwaiting'])){
			if($arrProp['callwaiting']=="yes")
				$result=$astMang->database_put("CW/$code/",$arrProp['name'],"ENABLED");
			else
				$result=$astMang->database_del("CW/$code/",$arrProp['name']);
		}else
			$result=$astMang->database_del("CW/$code/",$arrProp['name']);

		//si hubo algun error eliminar los datos que fueron insertados antes del error
		if($error){
			foreach($arrInsert as $key => $value){
				$result=$astMang->database_del($familia,$key);
			}
			return false;
		}else
			return true;
		//TODO: FALTA REVISAR SERVICIO DE DICTATION
	}

	//revisar que no exista dentro de la organizacion otra extension con el mismo numero
	function existExtension($extension,$tech)
	{
		$exito=false;
		if(!preg_match("/^[[:alnum:]]+$/", $extension)){
			$this->errMsg="Invalid extension format";
			return true;
		}elseif(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $this->domain)){
			$this->errMsg="Invalid domain format";
			return true;
		}
		
		$device=$this->code."_$extension";

		//validamos que el patron de marcado no sea usado como extension
		$query="SELECT count(id) from extension where exten=? and organization_domain=?";
		//print_r($this->tecnologia);
		$result=$this->tecnologia->getFirstResultQuery($query,array($extension,$this->domain));
		if($result[0]!=0)
			$exito=true;

		//validamos que el patron de marcado no esta siendo usado para una extension de fax
		$query="SELECT count(id) from extension where exten=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($extension,$this->domain));
		if($result[0]!=0)
			$exito=true;

		//validamos que no exista el peer
		if($tech=="iax2")
			$this->tecnologia=new paloIax($this->tecnologia->_DB);
		else
			$this->tecnologia=new paloSip($this->tecnologia->_DB);

		if($this->tecnologia->existPeer($device))
			$exito=true;

		$this->errMsg=$this->tecnologia->errMsg;
		return $exito;
	}

	//recibe el dispositivo asociado a la extension
	function changePasswordExtension($password,$exten){
		$query="SELECT tech, device, voicemail from extension where exten=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($exten,$this->domain),true,"Don't exist extension");
		//verifico que exista el dispositivo al que se le quiere cambiar le password y que el mismo
		//este asociado a una extension

		if($result==false){
			//hubo problemas al hacer la consulta
			$this->errMsg=$this->tecnologia->errMsg;
			return false;
		}else{
			if($result["tech"]=="iax2")
				$this->tecnologia=new paloIax($this->tecnologia->_DB);
			else
				$this->tecnologia=new paloSip($this->tecnologia->_DB);

			//si la extension tiene un voicemail cambiar el password asociado al voicemail
			if($result["voicemail"]!="novm"){
				$pVoicemail= new paloVoicemail($this->tecnologia->_DB);
				if(!$pVoicemail->setPassword($exten,$password,$this->domain)){
					$this->errMsg=$pVoicemail->errMsg;
					return false;
				}
			}

			if(!$this->tecnologia->setSecret($result["device"],$password,$this->domain)){
				$this->errMsg=$this->tecnologia->errMsg;
				return false;
			}else{
				return true;
			}
		}
	}

	function changePasswordFaxExtension($password,$exten){
		$query="SELECT tech, device from fax where exten=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($exten,$this->domain),true,"Don't exist Fax extension");
		//verifico que exista el dispositivo al que se le quiere cambiar el password y que el mismo
		//este asociado a una extension

		if($result==false){
			//hubo problemas al hacer la consulta
			$this->errMsg=$this->tecnologia->errMsg;
			return false;
		}else{
			if($result["tech"]=="iax2")
				$this->tecnologia=new paloIax($this->tecnologia->_DB);
			else
				$this->tecnologia=new paloSip($this->tecnologia->_DB);

			if(!$this->tecnologia->setSecret($result["device"],$password,$this->domain)){
				$this->errMsg=$this->tecnologia->errMsg;
				return false;
			}else{
				return true;
			}
		}
	}

	//vuelve a reconstruir las desde los datos anteriores contenidos en astDB
	function backupAstDBEXT($exten){
		$exito=true;
		if(!isset($exten) || $exten==""){
			$this->errMsg="Invalid extension backup. ".$this->errMsg;
			return false;
		}

		$errorM="";
		$astMang=AsteriskManagerConnect($errorM);
		if($astMang==false){
			$this->errMsg=$errorM;
			return false;
		}

		$arrBackup=array();
		$arrBackup["exten"]=$exten;
		$device=$this->code."_$exten";
		$arrFamily=array("EXTUSER/".$this->code."/$exten","DEVICE/".$this->code."/$device","DND/".$this->code."/$exten","CALLTRACE/".$this->code."/$exten","CFU/".$this->code."/$exten","CFB/".$this->code."/$exten","CF/".$this->code."/$exten");
		foreach($arrFamily as $value){
			$result=$astMang->database_show($value);
			$arrBackup[]=$result;
		}
		return $arrBackup;
	}

	//TODO: hacer validaciones de los elementos que se van a insertar en la base astDB
	function restoreBackupAstDBEXT($arrBackup){
		$exito=false;
		if(!isset($arrBackup["exten"]) || $arrBackup["exten"]==""){
			$this->errMsg="Invalid extension to restore. ".$this->errMsg;
			return false;
		}

		$errorM="";
		$astMang=AsteriskManagerConnect($errorM);
		if($astMang==false){
			$this->errMsg=$astMang;
			return false;
		}

		foreach($arrBackup as $value){
			if(is_array($value)){
				foreach($value as $family => $valor){
					$arrFamily=explode("/",$family);
					$astMang->database_put($arrFamily[1],implode("/",array_slice($arrFamily,2)),"\"$valor\"");
				}
			}
		}
		return $exito;
	}

	function deleteFaxExtension($extension){
		$tech="iax2";
		$query="Select id, organization_domain, exten, device from fax where exten=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($extension,$this->domain),true,"Don't fax exist extension $extension. ");
		if($result==false && $this->tecnologia->errMsg!="Don't exist fax extension $extension. "){
			$this->errMsg="Fax extension can't be deleted. ".$this->tecnologia->errMsg;
			return false;
		}else{
			$device=$result["device"];
			$this->tecnologia=new paloIax($this->tecnologia->_DB);
			if($this->tecnologia->deletefromDB($device)==false)
				return false;

			$dquery="delete from fax where device=? and tech=? and organization_domain=?";
			$exito=$this->tecnologia->executeQuery($dquery,array($device,$tech,$this->domain));
			if(!$exito){
				$this->errMsg="Extension can't be deleted. ".$this->tecnologia->errMsg;
				return false;
			}

			$errorM="";
			$astMang=AsteriskManagerConnect($errorM);
			if($astMang==false){
				$this->errMsg=$errorM;
				return false;
			}else
				$result=$astMang->prunePeer($tech,$device);
		}
		return true;
	}

	//funcion que se encarga de borrar una extension
    // 1. Se elimina el canal asociado con la extension (sip o iax) de la base de datos
	// 2. Si la extension tiene voicemail se elimina el voicemail
    // 3. Se eliminia el registro correspondiente de esa extension de la tabla extensions
	// 4. Despues de ello se debe mandar a recargar el plan de marcado para que los cambios tengan efecto
    //    dentro de asterisk
	function deleteExtension($extension,$tech){
		$query="Select id, organization_domain, exten, device, tech, voicemail from extension where exten=? and tech=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($extension,$tech,$this->domain),true,"Don't exist extension $extension. ");
		if($result==false && $this->tecnologia->errMsg!="Don't exist extension $extension. "){
			$this->errMsg="Extension can't be deleted. ".$this->tecnologia->errMsg;
			return false;
		}else{
			if(is_array($result) && count($result)>0){
				//se borra el voicemail asociado a la extension
				if(isset($result["voicemail"]) && $result["voicemail"]!="novm"){
					$pVoicemail= new paloVoicemail($this->tecnologia->_DB);
					$dvoicemial=$pVoicemail->deletefromDB($result["exten"],$result["voicemail"]);
				}
				$device=$result["device"];

				if($tech=="sip"){
					$this->tecnologia=new paloSip($this->tecnologia->_DB);
				}elseif($tech=="iax2"){
					$this->tecnologia=new paloIax($this->tecnologia->_DB);
				}

				$this->tecnologia->deletefromDB($device);

				$dquery="delete from extension where device=? and tech=? and organization_domain=?";
				$exito=$this->tecnologia->executeQuery($dquery,array($device,$tech,$this->domain));
				if(!$exito){
					$this->errMsg="Extension can't be deleted. ".$this->tecnologia->errMsg;
					return false;
				}

				//borramos las entradas dentro de astDB
				$this->deleteAstDBExt($extension,$device,$tech);
			}
		}
		return true;
	}

	function deleteAstDBExt($exten,$device,$tech){
		$errorM="";
		$astMang=AsteriskManagerConnect($errorM);
		if($astMang==false){
			$this->errMsg=$errorM;
			return false;
		}else{ //borro las propiedades dentro de la base ASTDB de asterisk
			$result=$astMang->database_delTree("EXTUSER/".$this->code."/".$exten);
			$result=$astMang->database_delTree("DEVICE/".$this->code."/".$device);
			$result=$astMang->database_del("DND",$this->code."/".$exten);
			$result=$astMang->database_del("CALLTRACE",$this->code."/".$exten);
			$result=$astMang->database_del("CFU",$this->code."/".$exten);
			$result=$astMang->database_del("CFB",$this->code."/".$exten);
			$result=$astMang->database_del("CF",$this->code."/".$exten);
			$result=$astMang->prunePeer($tech,$device);
		}
		return true;
	}

	function createDialPlanLocalExtension(){
		$arrExtensionLocal=array();
		$arrExtensionIvr=array();
		$arrContext=array();

		$query="Select * from extension where organization_domain=?";
		$result=$this->tecnologia->getResultQuery($query,array($this->domain),true,"Don't exist extensions for this domain");
		if($result==false && $this->tecnologia->errMsg!="Don't exist extensions for this domain"){
			$this->errMsg="Error creating dialplan for locals extensions. ".$this->tecnologia->errMsg; 
			return false;
		}else{
			if(is_array($result)){
				foreach($result as $value){
					$exten=$value["exten"];
					if(!isset($value["voicemail"]) || $value["voicemail"]=="" || $value["voicemail"]=="novm")
						$voicemail="novm";
					else
						$voicemail=$exten;

					if($value["rt"]!=0 && isset($value["rt"])){
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_setvar($this->code."_RINGTIMER",$value["rt"]),1);
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_macro($this->code.'-exten-vm',$voicemail.",".$exten));
					}else
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_macro($this->code.'-exten-vm',$voicemail.",".$exten),1);

					if($voicemail != "novm") {
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_goto('1','vmret'));
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_hint($exten,$this->domain),"hint");
						$arrExtensionLocal[] = new paloExtensions('${'.$this->code.'_VM_PREFIX}'.$exten,new ext_macro($this->code.'-vm',$voicemail.',DIRECTDIAL,${IVR_RETVM}'),1);
						$arrExtensionLocal[] = new paloExtensions('${'.$this->code.'_VM_PREFIX}'.$exten,new ext_goto('1','vmret'));
						$arrExtensionLocal[] = new paloExtensions("vmb".$exten,new ext_macro($this->code.'-vm',$voicemail.',BUSY,${IVR_RETVM}'),1);
						$arrExtensionLocal[] = new paloExtensions("vmb".$exten,new ext_goto('1','vmret'));
						$arrExtensionLocal[] = new paloExtensions('vmu'.$exten,new ext_macro($this->code.'-vm',$voicemail.',NOANSWER,${IVR_RETVM}'),1);
						$arrExtensionLocal[] = new paloExtensions('vmu'.$exten,new ext_goto('1','vmret'));
						$arrExtensionLocal[] = new paloExtensions('vms'.$exten,new ext_macro($this->code.'-vm',$voicemail.',NOMESSAGE,${IVR_RETVM}'),1);
						$arrExtensionLocal[] = new paloExtensions('vms'.$exten,new ext_goto('1','vmret'));
					} else {
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_goto('1','return','${IVR_CONTEXT}'));
						$arrExtensionLocal[]= new paloExtensions($exten,new ext_hint($exten,$this->domain),"hint");
					}

					if(isset($value["alias"]) && $value["alias"]!="")
						$arrExtensionLocal[] = new paloExtensions($exten,new ext_goto('1',$exten));
					
					// creamos un contexto para que la extensiones locales esten incluidas dentro del ivr
					$arrExtensionIvr[] = new paloExtensions($exten,new ext_execif('$["${BLKVM_OVERRIDE}" != ""]','Noop','Deleting: ${BLKVM_OVERRIDE}: ${DB_DELETE(${BLKVM_OVERRIDE})}'),"1");
					$arrExtensionIvr[] = new paloExtensions($exten,new ext_setvar('__NODEST', ''));
					$arrExtensionIvr[] = new paloExtensions($exten, new ext_goto('1',$exten,$this->code.'-from-did-direct'));

					if($voicemail != "novm") {
						$arrExtensionIvr[] = new paloExtensions('${'.$this->code.'_VM_PREFIX}'.$exten,new ext_execif('$["${BLKVM_OVERRIDE}" != ""]','Noop','Deleting: ${BLKVM_OVERRIDE}: ${DB_DELETE(${BLKVM_OVERRIDE})}'),"1");
						$arrExtensionIvr[] = new paloExtensions('${'.$this->code.'_VM_PREFIX}'.$exten,new ext_setvar('__NODEST', ''));
						$arrExtensionIvr[] = new paloExtensions('${'.$this->code.'_VM_PREFIX}'.$exten,new ext_macro($this->code.'-vm',$voicemail.',DIRECTDIAL,${IVR_RETVM}'));
						$arrExtensionIvr[] = new paloExtensions('${'.$this->code.'_VM_PREFIX}'.$exten,new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]',$this->code.'-ext-local,vmret,playret'));
					}
				}
			}
			$arrExtensionLocal[] = new paloExtensions("vmret",new ext_gotoIf('"${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""',"playret"),1);
			$arrExtensionLocal[] = new paloExtensions("vmret",new ext_hangup());
			$arrExtensionLocal[] = new paloExtensions("vmret",new ext_playback("exited-vm-will-be-transfered&silence/1"),"n","playret");
			$arrExtensionLocal[] = new paloExtensions("vmret",new ext_goto("1","return",'${IVR_CONTEXT}'));
			$arrExtensionLocal[] = new paloExtensions("h",new ext_macro($this->code."-hangupcall",""),1);
		}

		$contextoLocal=new paloContexto($this->code,"ext-local");
		if($contextoLocal===false){
			$this->errMsg=$contextoLocal->errMsg;
			return false;
		}else
			$contextoLocal->arrExtensions=$arrExtensionLocal;

		$contextofromIvr=new paloContexto($this->code,"from-did-direct-ivr");
		if($contextofromIvr===false){
			$this->errMsg=$contextofromIvr->errMsg;
			return false;
		}else
			$contextofromIvr->arrExtensions=$arrExtensionIvr;

		$arrContext=array($contextoLocal,$contextofromIvr);
		return $arrContext; 
	}

	function createDialPlanFaxExtension(){
		$arrExtensionFax=array();
		$query="Select * from fax where organization_domain=?";
		$result=$this->tecnologia->getResultQuery($query,array($this->domain),true,"Don't exist faxs extensions for this domain");
		if($result==false && $this->tecnologia->errMsg!="Don't exist faxs extensions for this domain"){
			$this->errMsg=_tr("Error creating dialplan for faxs extensions").$this->tecnologia->errMsg;
			return false;
		}else{
			if(is_array($result)){
				foreach($result as $value){
					$exten=$value["exten"];
					$arrExtensionFax[] = new paloExtensions($exten,new ext_noop('Receiving Fax for: '.$value["callerid_name"].' ('.$value["callerid_number"].'), From: ${CALLERID(all)}'),1);
					if($value["rt"]!=0 && isset($value["rt"])){
						$arrExtensionFax[] = new paloExtensions($exten,new ext_setvar("RT",$value["rt"]));
					}
					$arrExtensionFax[] = new paloExtensions($exten,new ext_setvar("DSTRING",$value["dial"]));
					$arrExtensionFax[] = new paloExtensions($exten,new ext_goto("1","s",$this->code."-ext-fax"));
				}
				$arrExtensionFax[] = new paloExtensions("s",new ext_dial('${DSTRING},${RT}'),1,"receivefax");
				$arrExtensionFax[] = new paloExtensions("h",new ext_macro($this->code."-hangupcall",""),1);
			}
		}

		$contextoFax=new paloContexto($this->code,"ext-fax");
		if($contextoFax===false){
			$this->errMsg=$contextoFax->errMsg;
			return false;
		}else
			$contextoFax->arrExtensions=$arrExtensionFax;

		$arrContext=array($contextoFax);
		return $arrContext; 
	}

}


class paloTrunks extends paloAsteriskDB{
	public $type;
	protected $dominio;
	protected $code;
	public $_DB;
	public $errMsg;

	function paloTrunk($domain,&$pDB2){
		if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
			$this->errMsg="Invalid domain format";
		}

		$this->domain=$domain;
		$pDB=new paloDB("sqlite3:////var/www/db/elastix.db");
		$pOrgz=new paloSantoOrganization($pDB);
		$resCode=$pOrgz->getOrganizationCode($domain);
		$this->code=$resCode["code"];

		if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        }else{
			$this->setdsn();
			$pDB = new paloDB($this->dsn);
			if(!empty($pDB->errMsg)) {
				$this->errMsg = $this->_DB->errMsg;
			} else{
				$this->_DB = $pDB;
			}
		}
	}

	function createNewTrunk($arrProp,$type){
		$query="INSERT INTO trunks values (tech";
		$arrOpt=array($type);

		//definimos el tipo de truncal que vamos a crear
		switch($type){
			case "sip":
				$this->type="sip";
				break;
			case "dahdi":
				$this->type="dahdi";
				break;
			case "iax2":
				$this->type="iax2";
				break;
			case "enum":
				$this->type="enum";
				break;
			case "dundi":
				$this->type="dundi";
				break;
			case "custom":
				$this->type="custom";
				break;
			default:
				$this->errMsg="Invalid type of trunk";
				return false;
		}

		//debe haberse seteado un nombre para la truncal
		if(!isset($arrProp["name"]) || $arrProp["name"]==""){
			$this->errMsg="Name of trunk could be empty";
		}else{
			$query .="name,";
			$arrOpt[1]=$arrProp["name"];
		}
		
		//si se define un callerid de salida
		if(isset($arrProp["out_cid"])){
			$query .="outcid,";
			$arrOpt[count($arrOpt)]=$arrProp["name"];
		}

		//caller id options
		if($arrProp["cid_options"]!="on" && $arrProp["cid_options"]!="off" && $arrProp["cid_options"]!="cnum" && $arrProp["cid_options"]!="all"){
			$this->errMsg="Invalid CID Options";
		}else{
			$query .="keepcid,";
			$arrOpt[count($arrOpt)]=$arrProp["cid_options"];
		}
		
		//si existe un maximo numero de canales
		if(!preg_match("/^[[:digit:]]+$/",$arrProp["max_chans"])){
			$this->errMsg="Invalid value field Maximun Channels";
		}else{
			$query .="maxchans,";
			$arrOpt[count($arrOpt)]=$arrProp["max_chans"];
		}

		//si esta o no deshabilitada la truncal
		if($arrProp["disabled"]=="on"){
			$query .="disabled,";
			$arrOpt[count($arrOpt)]=$arrProp["disabled"];
		}

		//oupbound dial prefix
		if(!preg_match("/^[[:digit:]]+$/",$arrProp["dialout_prefix"])){
			$this->errMsg="Invalid value field Outbound Prefix";
		}else{
			$query .="dialoutprefix";
			$arrOpt[count($arrOpt)]=$arrProp["dialout_prefix"];
		}

		$query .=")";
		$qmarks = "(";
		for($i=0;$i<count($arrOpt);$i++){
			$qmarks .="?,"; 
		}
		$qmarks=substr($qmarks,0,-1).")"; 

		if($this->errMsg==""){
			if($this->type=="sip"){
				$exito=createSipTrunk($channelId,$trunkid,$query,$arrOpt,$arrProp);
			}elseif($this->type=="iax"){
				$exito=createIaxTrunk($channelId,$trunkid,$query,$arrOpt,$arrProp);
			}elseif($this->type=="dahdi"){
				$exito=createDahdiTrunk($channelId,$trunkid,$query,$arrOpt);
			}
		}else{
			return false;
		}

		if($exito==true){
			//si ahi dialpatterns se los procesa
			if($this->createDialPattern($arrProp["dialPattern"],$trunkid)==false){
				$this->errMsg="Trunk can't be created .".$this->errMsg;
				return false;
			}else
				return true;
		}else
			return false;

	}

	private function createDialPattern($arrDialPattern,$trunkid)
	{
		$result=true;

		if(is_array($arrDialPattern) && count($arrDialPattern)!=0){
			$temp=$arrDialPattern;
			foreach($temp as $ind => $value){
				if(isset($value["prepend"])){
					//validamos los campos
					if(!preg_match("/^[[:digit:]]*$/",$value["prepend"])){
						$this->errMsg="Invalid dial pattern";
						break;
					}
				}else
					$arrDialPattern[$ind]["prepend"]="";

				if(isset($value["prefix"])){
					if(!preg_match("/^([XxZzNn[:digit:]]*(\[[0-9]+\-{1}[0-9]+\])*(\[[0-9]+\])*)+$/",$value["prefix"])){
						$this->errMsg="Invalid dial pattern";
						break;
					}
				}else
					$arrDialPattern[$ind]["prefix"]="";

				if(isset($value["match"])){
					if(!preg_match("/^([XxZzNn[:digit:]]*(\[[0-9]+\-{1}[0-9]+\])*(\[[0-9]+\])*)+$/",$value["match"])){
						$this->errMsg="Invalid dial pattern";
						break;
					}
				}else
					$arrDialPattern[$ind]["match"]="";
			}

			if($this->errMsg==false)
				return false;
			else{
				//verificar que exista una truncal con ese trunkid
				$query="INSERT INTO trunk_dialpatterns (trunkid,match_pattern_prefix,match_pattern_pass,prepend_digits,seq) values (?,?,?,?,?)";
				foreach($arrDialPattern as $key => $value){
					if($value["prepend"]!="" || $value["prefix"]!="" || $value["match"]!="")
						$result=$this->executeQuery($query,array($trunkid,$value["prepend"],$value["prefix"],$value["match"],$key));
						if($result==false) //habria que eliminar los campos que fueron insertados demas
							break;
				}
			}
		}
		return $result;
	}

	private function createDahdiTrunk($channelId,&$trunkid,$query,$arrProp)
	{
		//setear el identificador apropiadamente (channel id)
		//campo group dentro de chan_dahdi.conf group=numCode(numeroGrupo)
		//numero de grupo 2 digitos maximos al principio seteado 00
		if(!preg_match("/^[g|r]{0,1}[[:digit:]]{1,2}$/",$channelId)){
			$this->errMsg="Invalid DAHDI Identifier";
			return false;
		}

		if(!preg_match("/^organization_[[:digit:]]{3}$/",$this->code)){
			$this->errMsg="Invalid code organization";
			return false;
		}

		$numCode=strpos($this->code,13);
		if(substr($channelId,0,1)=="r" || substr($channelId,0,1)=="g"){
			$channelId=substr($channelId,0,1).$numCode.substr($channelId,1);
		}else{
			$channelId=$numCode.$channelId;
		}

		$result=$this->executeQuery($query,$arrProp);
		if($result==true){ //habria que eliminar los campos que fueron insertados demas
			//obtengo el id de la ultima truncal insertada
			$result=$this->getFirstResultQuery("SELECT LAST_INSERT_ID()",NULL);
			$trunkid=$result[0];
			return true;
		}else{
			$this->errMsg="Trunk can't be created .".$this->errMsg;
			return false;
		}
	}

	function createDialPlanTrunk(){
		
	}

	private function createSipTrunk($channelId,$trunkid,$query,$arrOpt,$arrProp){
		if(!isset($arrProp["trunk_name"]) || $arrProp["trunk_name"]==""){
			$this->errMsg="Trunk can't be created . Trunk Name can't be empty";
			return false;
		}
		//validamos que no existe otros peers con el mismo nombre de truncal
		
	}

	private function createIaxTrunk($channelId,$trunkid,$query,$arrOpt,$arrProp){
		
	}
	
	//debe retorna un arregle donde el id es el nombre del contexto
	function createExtTrunk()
	{
		//escribir en el contexto ext-trunk cada truncal
		//escribir las globales de cada truncal
		//si se le setea un patron de marcado a la truncal entonces escribir el contexto sub-flp-idtrunk para dicha truncal
	}
}
?>