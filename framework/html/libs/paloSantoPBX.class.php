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
  $Id: paloSantoPBX.class.php,v 1.1 2012/07/30 rocio mera rmera@palosanto.com Exp $ */

global $arrConf;

include_once "/var/www/html/libs/paloSantoACL.class.php";
include_once "/var/www/html/libs/paloSantoConfig.class.php";
include_once "/var/www/html/libs/paloSantoAsteriskConfig.class.php";
include_once "/var/www/html/libs/extensions.class.php";
include_once "/var/www/html/libs/misc.lib.php";

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
	require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

class paloAsteriskDB {
	public $_DB;
	public $errMsg;

	function __construct(&$pDB)
    {
		if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        }else{
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
		}
		return $result;
	}

	function getFirstResultQuery($query,$arrayParam,$assoc=false,$noexits="Don't exist registers.")
	{
		$result=$this->_DB->getFirstRowQuery($query,$assoc,$arrayParam);
		if($result===false){
			$this->errMsg = $this->_DB->errMsg;
			return false;
		}elseif($result==false){
			$this->errMsg = $noexits;
		}
		return $result;
	}


	function getCodeByDomain($domain){
		global $arrConf;
		$pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
		$query="SELECT code from organization where domain=?";
		$result=$pDB->getFirstRowQuery($query,true,array($domain));
		if($result===false)
			$this->errMsg=$pDB->errMsg;
		elseif(count($result)==0 || empty($result["code"]))
			$this->errMsg=_tr("Organization doesn't exist");
		return $result;
	}

	//revisar que no exista dentro de la organizacion otra elemento con el mismo patron de marcado
	function existExtension($extension,$domain)
	{
		$exist=true;
		//validamos que el patron de marcado no sea usado como extension
		$query="SELECT count(id) from extension where exten=? and organization_domain=?";
		$result=$this->getFirstResultQuery($query,array($extension,$domain));
		if($result[0]!=0){
			$this->errMsg=_tr("Already exits a extension with same pattern").$this->errMsg;
		}else{
			//validamos que el patron de marcado no esta siendo usado para una extension de fax
			$query="SELECT count(id) from fax where exten=? and organization_domain=?";
			$result=$this->getFirstResultQuery($query,array($extension,$domain));
			if($result[0]!=0){
				$this->errMsg=_tr("Already exits a fax extension with same pattern").$this->errMsg;
			}else{
				//validamos que el patron de marcado no este siendo usado por los features code
				$query="SELECT 1 from features_code f join features_code_settings fg on f.name=fg.name 
				where f.code=? or fg.default_code=? and f.organization_domain=?";
				$result=$this->getFirstResultQuery($query,array($extension,$extension,$domain));
				if(count($result)>0 || $result===false){
					$this->errMsg=_tr("Already exits a feature code with same pattern").$this->errMsg;
				}else{
                    //validamos que el patron de marcado no este siendo usado por una cola
					$query="SELECT count(name) from queue where queue_number=? and organization_domain=?";
                    $result=$this->getFirstResultQuery($query,array($extension,$domain));
                    if($result[0]!=0){
                        $this->errMsg=_tr("Already exits a queue with same pattern").$this->errMsg;
                    }else{
                        //valido que el patron de marcado no este siendo usado por un ring_group
                        $query="SELECT 1 from ring_group where organization_domain=? and rg_number=?";
                        $result=$this->getFirstResultQuery($query,array($extension,$domain));
                        if(count($result)>0 || $result===false){
                            $this->errMsg=_tr("Already exits a ring group with same pattern").$this->errMsg;
                        }else
                            $exist=false;
                    }
				}
			}
		}
		return $exist;
	}

	//por el momento solo se pueden crear dispositivos de tipos sip e iax, no son soportadas
	//otras tecnologias
	function getAllDevice($domain,$tech=null){
		$where="";
		$arrParam=array($domain);
		if(!empty($tech)){
			$where=" and tech=?";
			if(strtolower($tech)=="iax")
				$arrParam[]="iax2";
			else
				$arrParam[]=strtolower($tech);
		}
		$query="SELECT dial, device, exten from extension where organization_domain=? $where";
		$result=$this->getResultQuery($query,$arrParam,true,"Don't exist any devices created");
		return $result;
	}

	function prunePeer($device,$tech){
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
    
    function loadPeer($device,$tech){
        $errorM="";
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        }else{ //borro las propiedades dentro de la base ASTDB de asterisk
            $result=$astMang->loadPeer($tech,$device);
        }
        return true;
    }
    
    function getRecordingsSystem($domain){
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }
        
        $recordings=array();
        $query="Select uniqueid,name,organization_domain from recordings where organization_domain=? or organization_domain=?";
        $result=$this->getResultQuery($query,array($domain,""),true,"");
        if($result!=false){
            foreach($result as $value){
                $recordings[$value["uniqueid"]]=$value["name"];
                if($value["organization_domain"]=="")
                    $recordings[$value["uniqueid"]]=$value["name"]."- system";
            }
        }
        return $recordings; 
    }
    
    //devuelve el archivo de audio que corresponde al id dado 
    //caso contrario devuelve falso
    function getFileRecordings($domain,$key){
        $file=null;
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return null;
        }
        
        $query="SELECT filename,source from recordings where (organization_domain=? or organization_domain=?) and uniqueid=?";
        $result=$this->getFirstResultQuery($query,array($domain,"",$key),true);
        if($result!=false){
            if($result["source"]=="custom")
                $path="\\/var\\/lib\\/asterisk\\/sounds\\/custom\\/";
            else
                $path="\\/var\\/lib\\/asterisk\\/sounds\\/$domain\\/".$result["source"]."\\/";
            if(preg_match_all("/^($path(\w|-|\.|\(|\)|\s)+)\.(wav|WAV|Wav|gsm|GSM|Gsm|Wav49|wav49|WAV49)$/",$result["filename"],$match))
                $file=$match[1][0];
        }
        return $file;
    }
    
    function getMoHClass($domain){
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }
        
        $moh=array();
        $query="Select name,description,organization_domain from musiconhold where organization_domain=? or organization_domain=?";
        $result=$this->getResultQuery($query,array($domain,""),true,"");
        if($result!=false){
            foreach($result as $value){
                $moh[$value["name"]]=$value["description"];
                if($value["organization_domain"]=="")
                    $moh[$value["name"]]=$value["description"]."- system";
            }
        }
        return $moh; 
    }
    
    function existMoHClass($class,$domain=null){
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return null;
        }
        
        $query="SELECT 1 from musiconhold where name=? and (organization_domain=? or organization_domain=?)";
        $result=$this->getFirstResultQuery($query,array($class,$domain,""));
        if(is_array($result) && count($result)>0){
            return true;
        }
        return false;
    }
    
    //devuelve un arreglo que contiene los posibles destinos de ultimo recurso dado una categoria
    //categorias son: extensions, ivrs, queues, trunks, phonebook, terminate call
    function getDefaultDestination($domain,$categoria){
        $arrDestine=array();
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }
        
        switch($categoria){
            case "extensions":
                $qExt="SELECT dial,exten from extension where organization_domain=?";
                $result=$this->getResultQuery($qExt,array($domain),true);
                foreach($result as $value){
                    $arrDestine["extensions,".$value["exten"]]=$value["exten"]." (".$value["dial"].")";
                }
                break;
            case "ivrs":
                $qIvr="SELECT id,name from ivr where organization_domain=?";
                $result=$this->getResultQuery($qIvr,array($domain),true);
                foreach($result as $value){
                    $arrDestine["ivrs,".$value["id"]]=$value["name"];
                }
                break;
            /*case "trunks":
                $qTrunk="SELECT trunkid,name,tech from trunk where organization_domain=?";
                $result=$this->getResultQuery($qTrunk,array($domain),true);
                foreach($result as $value){
                    $arrDestine["trunks,".$value["trunkid"]]=$value["name"]." (".$value["tech"].")";
                }
                break;*/
            case "queues":
                $qQueues="SELECT name,queue_number,description from queue where organization_domain=?";
                $result=$this->getResultQuery($qQueues,array($domain),true);
                foreach($result as $value){
                    $arrDestine["queues,".$value["name"]]=$value["queue_number"]." (".$value["description"].")";
                }
                break;
            case "ring_group":
                $query="SELECT rg_name,rg_number from ring_group where organization_domain=?";
                $result=$this->getResultQuery($query,array($domain),true);
                foreach($result as $value){
                    $arrDestine["ring_group,".$value["rg_number"]]=$value["rg_number"]." (".$value["rg_name"].")";
                }
                break;
            case "time_conditions":
                $query="SELECT id,name from time_conditions where organization_domain=?";
                $result=$this->getResultQuery($query,array($domain),true);
                foreach($result as $value){
                    $arrDestine["time_conditions,".$value["id"]]=$value["name"];
                }
                break;
            case "terminate_call":
                    $arrDestine["terminate_call,hangup"]=_tr("Hangup");
                    $arrDestine["terminate_call,congestion"]=_tr("Congestion");
                    $arrDestine["terminate_call,busy"]=_tr("Play busytones");
                    $arrDestine["terminate_call,zapateller"]=_tr("Play SIT Tone (Zapateller)");
                    $arrDestine["terminate_call,musiconhold"]=_tr("Put call in hold");
                    $arrDestine["terminate_call,ring"]=_tr("Play ringtones");
                break;
            case "phonebook":
                    $arrDestine["phonebook,phonebook"]=_tr("Phonebook");
                break;
        }
        return $arrDestine;
    }
    
    function validateDestine($domain,$valor){
        $arrDestine=array();
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }
        
        $result=explode(",",$valor);
        if(count($result)<2){
            return false;
        }
        $categoria=$result[0];
        $select=$result[1];
        
        switch($categoria){
            case "extensions":
                $query="SELECT count(exten) from extension where organization_domain=? and exten=?";
                $result=$this->getFirstResultQuery($query,array($domain,$select));
                if($result[0]!="1"){
                    return false;
                }
                break;
            case "ivrs":
                $query="SELECT count(id) from ivr where organization_domain=? and id=?";
                $result=$this->getFirstResultQuery($query,array($domain,$select));
                if($result[0]!="1"){
                    return false;
                }
                break;
           /* case "trunks":
                $query="SELECT count(trunkid) from trunk where organization_domain=? and trunkid=?";
                $result=$this->getFirstResultQuery($query,array($domain,$select));
                if($result[0]!="1"){
                    return false;
                }
                break;*/
            case "queues":
                $query="SELECT count(name) from queue where organization_domain=? and name=?";
                $result=$this->getFirstResultQuery($query,array($domain,$select));
                if($result[0]!="1"){
                    return false;
                }
                break;
            case "ring_group":
                $query="SELECT count(rg_number) from ring_group where organization_domain=? and rg_number=?";
                $result=$this->getFirstResultQuery($query,array($domain,$select));
                if($result[0]!="1"){
                    return false;
                }
                break;
            case "time_conditions":
                $query="SELECT count(id) from time_conditions where organization_domain=? and id=?";
                $result=$this->getFirstResultQuery($query,array($domain,$select));
                if($result[0]!="1"){
                    return false;
                }
                break;
            case "terminate_call":
                if(!preg_match("/^(hangup|congestion|busy|zapateller|musiconhold|ring){1}$/",$select)){
                    return false;
                }
                break;
            case "phonebook":
                break;
            default:
                return false;
        }
        return true;
    }
    
    //funcion que dado los datos de destinations guardados devuelve el goto del mismo
    function getGotoDestine($domain,$valor){
        $arrDestine=array();
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }
        
        $result=explode(",",$valor);
        if(count($result)<2){
            return false;
        }
        $categoria=$result[0];
        $destino=$result[1];
        
        $rc=$this->getCodeByDomain($domain);
        if($rc==false)
            return false;
            
        $code=$rc["code"];
        
        switch($categoria){
            case "extensions":
                $query="SELECT exten from extension where organization_domain=? and exten=?";
                $result=$this->getFirstResultQuery($query,array($domain,$destino),true);
                if($result!=false){
                    return "$code-from-did-direct,".$result["exten"].",1";
                }
                break;
            case "ivrs":
                $query="SELECT id from ivr where organization_domain=? and id=?";
                $result=$this->getFirstResultQuery($query,array($domain,$destino),true);
                if($result!=false){
                    return "$code-ivr-".$result["id"].",s,1";
                }
                break;
            /*case "trunks":
                $query="SELECT trunkid from trunk where organization_domain=? and trunkid=?";
                $result=$this->getFirstResultQuery($query,array($domain,$destino),true);
                if($result!=false){
                    return "$code-ext-trunk,".$result["trunkid"].",1";
                }
                break;*/
            case "queues":
                $query="SELECT queue_number from queue where organization_domain=? and name=?";
                $result=$this->getFirstResultQuery($query,array($domain,$destino),true);
                if($result!=false){
                    return "$code-ext-queues,".$result["queue_number"].",1";
                }
                break;
            case "ring_group":
                $query="SELECT rg_number from ring_group where organization_domain=? and rg_number=?";
                $result=$this->getFirstResultQuery($query,array($domain,$destino),true);
                if($result!=false){
                    return "$code-ext-group,".$result["rg_number"].",1";
                }
                break;
            case "time_conditions":
                $query="SELECT id from time_conditions where organization_domain=? and id=?";
                $result=$this->getFirstResultQuery($query,array($domain,$destino),true);
                if($result!=false){
                    return "$code-timeconditions,".$result["id"].",1";
                }
                break;
            case "terminate_call":
                if(preg_match("/^(hangup|congestion|busy|zapateller|musiconhold|ring){1}$/",$destino)){
                    return "$code-app-blackhole,".$destino.",1";
                }
                break;
            case "phonebook":
                    return "$code-app-pbdirectory,pbdirectory,1";
                break;
            default:
                return false;
        }
        return false;
    }
    
    function getCategoryDefault($domain){
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }
        $arrCat=array("none"=>_tr("- choose one -"),"terminate_call"=>_tr("Terminate Call"),"phonebook"=>_tr("Phonebook"));
        $query="select exten from extension where organization_domain=?";
        $result=$this->getFirstResultQuery($query,array($domain));
        if($result!=false){
            $arrCat["extensions"]=_tr("Extensions");
        }
        $query="select id from ivr where organization_domain=?";
        $result=$this->getFirstResultQuery($query,array($domain));
        if($result!=false){
            $arrCat["ivrs"]=_tr("Ivrs");
        }
        /*$query="select trunkid from trunk where organization_domain=?";
        $result=$this->getFirstResultQuery($query,array($domain));
        if($result!=false){
            $arrCat["trunks"]=_tr("Trunks");
        }*/
        $query="select queue_number from queue where organization_domain=?";
        $result=$this->getFirstResultQuery($query,array($domain));
        if($result!=false){
            $arrCat["queues"]=_tr("Queues");
        }
        $query="select rg_number from ring_group where organization_domain=?";
        $result=$this->getFirstResultQuery($query,array($domain));
        if($result!=false){
            $arrCat["ring_group"]=_tr("Ring Group");
        }
        $query="select id from time_conditions where organization_domain=?";
        $result=$this->getFirstResultQuery($query,array($domain));
        if($result!=false){
            $arrCat["time_conditions"]=_tr("Time Conditions");
        }
        return $arrCat;
    }
    
    /**
        funcion que sirve para verificar si un determinado modulo 
        se encuentra cargado dentro de asterisk
        @param string $module_name nombre del modulo 
        @return bool retorna verdadero si el module esta cargado
                     falso caso contrario
    */
    function isAsteriskModInstaled($module_name){
        $errorM="";
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
        }else{ //comprobamos que el modulo esta cargado
            $result = $astMang->command("module show like $module_name");
            if(preg_match('/[1-9] modules loaded/', $result['data']))
                return true;
        }
        return false;
    }
    
    function getGlobalVar($variable,$domain){
        $query="SELECT value from globals where organization_domain=? and variable=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($domain,$variable));
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result[0];
    }
    
    function getAudioFormatAsterisk(){
        $errorM="";
        $arrFormats=array();
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
        }else{ //comprobamos que el modulo esta cargado
            $result = $astMang->command("module show like format");
            $test=explode("\n",$result['data']);
            foreach($test as $line){
                if(preg_match("/^(format_([[:alnum:]]|_)+\.so)(.)(.)(.)/",$line,$match)){
                    $format=substr(substr(trim($match[0]),7),0,-3);
                    if($format=="pcm"){
                        $arrFormats["alaw"]="alaw";
                        $arrFormats["ulaw"]="ulaw";
                    }else{
                        $arrFormats[$format]=$format;
                    }
                }
            }
        }
        return $arrFormats;
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
	public $rtpkeepalive;
	public $call_limit; //nombre del campo en la tabla call-limit
	public $g726nonstandard;
	public $organization_domain;

	function paloSip(&$pDB)
	{
		parent::__construct($pDB);
	}

	function existPeer($deviceName)
	{
		if($this->validateName($deviceName)){
			$query="Select count(name) from sip where name=?";
			$arrayParam=array($deviceName);
			$result=$this->getFirstResultQuery($query,$arrayParam);
			if($result[0]==0){
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

	function insertDB()
	{
		//valido que el dispositivo tenga seteado el parametro organization_domain y que este exista como dominio de algunaç
		//organizacion
		$result=$this->getCodeByDomain($this->organization_domain);
		if($result==false){
			$this->errMsg =_tr("Can't create the sip device").$this->errMsg;
			return false;
		}
		$code=$result["code"];
		//valido que no exista otro dispositivo sip creado con el mismo nombre y que los cambios obligatorios esten seteados
		if(!isset($this->name) || !isset($this->md5secret) || !isset($this->context)){
			$this->errMsg="Field name, secret, context can't be empty";
		}elseif(!$this->existPeer($code."_".$this->name)){
			$arrValues=array();
			$question="(";
			$Prop="(";
			$i=0;
			$arrPropertyes=get_object_vars($this);
			foreach($arrPropertyes as $key => $value){
				if(isset($value) && $key!="_DB" && $key!="errMsg" && $value!="noset"){
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
						case "call_limit":
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
	
	function updateParameters($arrProp){
        $arrQuery=array();
        $arrParam=array();
        $result=$this->getCodeByDomain($arrProp["organization_domain"]);
        if($result==false){
            $this->errMsg =_tr("Can't create the sip device").$this->errMsg;
            return false;
        }
        $code=$result["code"];
        if($this->existPeer($code."_".$arrProp["name"])){
            foreach($arrProp as $name => $value){
                if(property_exists($this,$name)){
                    if(isset($value)){
                        if($name!="name" && $name!="_DB" && $name!="errMsg" && $name!="organization_domain"){
                            if($value=="" || $value=="noset"){
                                $value=NULL;
                            }
                            switch ($name){
                                case "session_timers":
                                    $arrQuery[]="session-timers=?";
                                    break;
                                case "session_expires":
                                    $arrQuery[]="session-expires=?";
                                    break;
                                case "session_minse":
                                    $arrQuery[]="session-minse=?";
                                    break;
                                case "session_refresher":
                                    $arrQuery[]="session-refresher=?";
                                    break;
                                case "call_limit":
                                    $arrQuery[]="call-limit=?";
                                    break;
                                case "context":
                                    $arrQuery[]="context=?";
                                    $value = $code."-".$value;
                                    break;
                                default:
                                    $arrQuery[]="$name=?";
                                    break;
                            }
                            $arrParam[]=$value;
                        }
                    }
                }
            }
            if(count($arrQuery)>0){
                $query ="Update sip set ".implode(",",$arrQuery);
                $query .=" where name=? and organization_domain=?";
                $arrParam[]=$code."_".$arrProp["name"];
                $arrParam[]=$arrProp["organization_domain"];
                return $this->executeQuery($query,$arrParam);
            }else
                return true;
        }else
            return false;
    }

    function setParameter($device,$parameter,$value){
        $query="UPDATE sip set $parameter=? where name=?";
        if($this->executeQuery($query,array($parameter,$value,$device))){
            return true;
        }
        return false;
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
	
	function updateDefaultSettings($arrProp)
    {
        $arrQuery=array();
        $arrParam=array();
        
        $result=$this->getCodeByDomain($arrProp["organization_domain"]);
        if($result==false){
            $this->errMsg =_tr("Can't create the iax device").$this->errMsg;
            return false;
        }
        
        $code=$result["code"];
        foreach($arrProp as $name => $value){
            if(property_exists($this,$name)){
                if(isset($value)){
                    if($name!="_DB" && $name!="errMsg" && $name!="organization_domain"){
                        if($value=="" || $value=="noset"){
                            $value=NULL;
                        }
                        switch ($name){
                            case "session_timers":
                                $arrQuery[]="session-timers=?";
                                break;
                            case "session_expires":
                                $arrQuery[]="session-expires=?";
                                break;
                            case "session_minse":
                                $arrQuery[]="session-minse=?";
                                break;
                            case "session_refresher":
                                $arrQuery[]="session-refresher=?";
                                break;
                            case "call_limit":
                                $arrQuery[]="call-limit=?";
                                break;
                            default:
                                $arrQuery[]="$name=?";
                                break;
                        }
                        $arrParam[]=$value;
                    }
                }
            }
        }
        if(count($arrQuery)>0){
            $query ="Update sip_general set ".implode(",",$arrQuery)." where organization_domain=?";
            $arrParam[]=$arrProp["organization_domain"];
            return $this->executeQuery($query,$arrParam);
        }else
            return true;
    }


	function setSecret($device,$secret,$organization){
		$query="update sip set md5secret=? where name=? and organization_domain=?";
		$secret=$this->hashMd5Secret($device,$secret);
		if(is_null($secret))
			$this->errMsg="Error setting secret for sip channel.";
		else{
			if($this->executeQuery($query,array($secret,$device,$organization))){
				return true;
			}else{
				$this->errMsg="Secret couldn't be updated for sip channel. ".$this->errMsg;
				return false;
			}
		}
	}

	//esto es para los numeros de extensiones internas
	function validateName($deviceName)
	{
		if(preg_match("/^[[:alnum:]_]+$/", $deviceName)){
            return true;
        }else{
            return false;
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
		parent::__construct($pDB);
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

	function insertDB()
	{
		//valido que el dispositivo tenga seteado el parametro organization_domain y que este exista como dominio de algunaç
		//organizacion
		$result=$this->getCodeByDomain($this->organization_domain);
		if($result==false){
			$this->errMsg =_tr("Can't create the iax device").$this->errMsg;
			return false;
		}
		$code=$result["code"];
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
				if(isset($value) && $key!="_DB" && $key!="errMsg" && $value!="noset"){
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
	
	function updateParameters($arrProp){
        $arrQuery=array();
        $arrParam=array();
        $result=$this->getCodeByDomain($arrProp["organization_domain"]);
        if($result==false){
            $this->errMsg =_tr("Can't create the iax device").$this->errMsg;
            return false;
        }
        $code=$result["code"];
        if($this->existPeer($code."_".$arrProp["name"])){
            foreach($arrProp as $name => $value){
                if(property_exists($this,$name)){
                    if(isset($value)){
                        if($name!="name" && $name!="_DB" && $name!="errMsg" && $name!="organization_domain"){
                            if($value=="" || $value=="noset"){
                                $value=NULL;
                            }
                            switch ($name){
                                case "context":
                                    $arrQuery[]="$name=?";
                                    $value = $code."-".$value;
                                    break;
                                default:
                                    $arrQuery[]="$name=?";
                                    break;
                            }
                            $arrParam[]=$value;
                        }
                    }
                }
            }
            if(count($arrQuery)>0){
                $query ="Update iax set ".implode(",",$arrQuery);
                $query .=" where name=? and organization_domain=?";
                $arrParam[]=$code."_".$arrProp["name"];
                $arrParam[]=$arrProp["organization_domain"];
                return $this->executeQuery($query,$arrParam);
            }else
                return true;
        }else
            return false;
    }

    function setParameter($device,$parameter,$value){
        $query="UPDATE iax set $parameter=? where name=?";
        if($this->executeQuery($query,array($value,$device))){
            return true;
        }
        return false;
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

	function updateDefaultSettings($arrProp)
    {
        $arrQuery=array();
        $arrParam=array();
        
        $result=$this->getCodeByDomain($arrProp["organization_domain"]);
        if($result==false){
            $this->errMsg =_tr("Can't create the iax device").$this->errMsg;
            return false;
        }
        
        $code=$result["code"];
        foreach($arrProp as $name => $value){
            if(property_exists($this,$name)){
                if(isset($value)){
                    if($name!="_DB" && $name!="errMsg" && $name!="organization_domain"){
                        if($value=="" || $value=="noset"){
                            $value=NULL;
                        }
                        $arrQuery[]="$name=?";
                        $arrParam[]=$value;
                    }
                }
            }
        }
        if(count($arrQuery)>0){
            $query ="Update iax_general set ".implode(",",$arrQuery)." where organization_domain=?";
            $arrParam[]=$arrProp["organization_domain"];
            return $this->executeQuery($query,$arrParam);
        }else
            return true;
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

	//TODO: Ver la forma de autentificar iax usando md5
	// por ahora mandamos el password codificado usando md5 en el caso de que se use dispositivo iax para crear
	// los peers usados en los faxes
	function setSecret($device,$secret,$organization){
		$query="update iax set secret=? where name=? and organization_domain=?";
		//obtenemos el md5 de la clave
		$secret=md5($secret);
		if($this->executeQuery($query,array($secret,$device,$organization))){
			return true;
		}else{
			$this->errMsg="Secret couldn't be updated for iax channel. ".$this->errMsg;
			return false;
		}
	}

	//esto es para los numeros de extensiones internas
	function validateName($deviceName)
	{
		if(preg_match("/^[[:alnum:]_]+$/", $deviceName)){
			return true;
		}else{
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
		parent::__construct($pDB);
	}

	function createVoicemail()
	{
		$result=$this->getCodeByDomain($this->organization_domain);
		if($result==false){
			$this->errMsg =_tr("Can't create the voicemiail").$this->errMsg;
			return false;
		}
		$code=$result["code"];
		//valido que no exista otro mailbox creado con el mismo numero
		if(!isset($this->mailbox) || !isset($this->password) || !isset($this->context)){
			$this->errMsg="Field Mailbox, Voicemail Password, Voicemail Context , can't be empty";
		}elseif(!$this->existVoicemail($this->mailbox,$this->organization_domain)){
			$arrValues=array();
			$question="(";
			$Prop="(";
			$i=0;
			$arrPropertyes=get_object_vars($this);
			foreach($arrPropertyes as $key => $value){
				if(isset($value)){
					if($key!="_DB" && $key!="errMsg" && $value!="noset"){
						if($key=="callback" || $key=="dialout" || $key=="exitcontext" || $key=="context")
							$value = $code."-".$value;
						$Prop .=$key.",";
						$arrValues[$i]=$value;
						$question .="?,";
						$i++;
					}
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

	function deletefromDB($vm,$domain)
	{
		$query="delete from voicemail where mailbox=? and organization_domain=?";
		if($this->executeQuery($query,array($vm,$domain))){
			return true;
		}else
			return false;
	}

	function existVoicemail($vm,$domain)
	{
		$query="select count(mailbox) from voicemail where mailbox=? and organization_domain=?";
		$result=$this->getFirstResultQuery($query,array($vm,$domain),false);
		if($result==false){
			return true;
		}else{
			if($result[0]!=0){
				$this->errMsg=_tr("Mailbox already exist");
				return true;
			}
			return false;
		}
	}

	function updateParameters($arrProp){
		$arrQuery=array();
		$arrParam=array();
		$result=$this->getCodeByDomain($arrProp["organization_domain"]);
		if($result==false){
			$this->errMsg =_tr("Can't create the voicemail").$this->errMsg;
			return false;
		}
		$code=$result["code"];
		$context="$code-".$this->context;
		if($this->existVoicemail($arrProp["mailbox"],$arrProp["organization_domain"])){
			foreach($arrProp as $name => $value){
				if(property_exists($this,$name)){
					if(isset($value)){
						if($name!="mailbox" && $name!="_DB" && $name!="errMsg" && $name!="organization_domain"){
							if($value=="" || $value=="noset"){
								$value=NULL;
							}
							switch ($name){
								case "context":
									$arrQuery[]="$name=?";
									$value = $code."-".$value;
									break;
								case "callback":
									$arrQuery[]="$name=?";
									$value = $code."-".$value;
									break;
								case "dialout":
									$arrQuery[]="$name=?";
									$value = $code."-".$value;
									break;
								case "exitcontext":
									$arrQuery[]="$name=?";
									$value = $code."-".$value;
									break;
								default:
									$arrQuery[]="$name=?";
									break;
							}
							$arrParam[]=$value;
						}
					}
				}
			}
			if(count($arrQuery)>0){
				$query ="Update voicemail set ".implode(",",$arrQuery);
				$query .=" where mailbox=? and organization_domain=?";
				$arrParam[]=$arrProp["mailbox"];
				$arrParam[]=$arrProp["organization_domain"];
				return $this->executeQuery($query,$arrParam);
			}else
				return true;
		}else
			return false;
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
	
	function updateDefaultSettings($arrProp)
    {
        $arrQuery=array();
        $arrParam=array();
        $result=$this->getCodeByDomain($arrProp["organization_domain"]);
        if($result==false){
            $this->errMsg =_tr("Can't create the iax device").$this->errMsg;
            return false;
        }
        
        $code=$result["code"];
        foreach($arrProp as $name => $value){
            if(property_exists($this,$name)){
                if(isset($value)){
                    if($name!="_DB" && $name!="errMsg" && $name!="organization_domain"){
                        if($value=="" || $value=="noset"){
                            $value=NULL;
                        }
                        $arrQuery[]="$name=?";
                        $arrParam[]=$value;
                    }
                }
            }
        }
        if(count($arrQuery)>0){
            $query ="Update voicemail_general set ".implode(",",$arrQuery)." where organization_domain=?";
            $arrParam[]=$arrProp["organization_domain"];
            return $this->executeQuery($query,$arrParam);
        }else
            return true;
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
	protected $code;
	public $errMsg;

	function paloDevice($domain,$type,&$pDB2)
	{
		if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
			$this->errMsg="Invalid domain format";
		}else{
			$this->domain=$domain;

			if($type=="sip"){
				$this->tecnologia=new paloSip($pDB2);
				$this->errMsg=$this->tecnologia->errMsg;
			}elseif($type=="iax2"){
				$this->tecnologia=new paloIax($pDB2);
				$this->errMsg=$this->tecnologia->errMsg;
			}else{
				$this->errMsg="Invalid technology name";
			}

			$result=$this->tecnologia->getCodeByDomain($domain);
			if($result==false){
				$this->errMsg .=_tr("Can't create a new instace of paloDevice").$this->tecnologia->errMsg;
			}else{
				$this->code=$result["code"];
			}
		}
	}

	function getCode(){
		return $this->code;
	}

	function getDomain(){
		return $this->domain;
	}

	function validatePaloDevice(){
		//validamos que la instancia de paloDevice que se esta usando haya sido creda correctamente
		if(is_null($this->code) || is_null($this->tecnologia) || is_null($this->domain))
			return false;
		return true;
	}

	function getExtension($exten){
        $query="SELECT * from extension where organization_domain=? and exten=?";
        $result=$this->tecnologia->getFirstResultQuery($query,array($this->domain,$exten),true,"Don't exist extension.");
        if($result==false)
            $this->errMsg=$this->tecnologia->errMsg;
        return $result;
    }
    
    function getFaxExtension($faxExten){
        $query="SELECT * from fax where organization_domain=? and exten=?";
        $result=$this->tecnologia->getFirstResultQuery($query,array($this->domain,$faxExten),true,"Don't exist fax extension.");
        if($result==false)
            $this->errMsg=$this->tecnologia->errMsg;
        return $result;
    }
    
    function getTotalExtensions(){
        $query="Select count(id) from extension where organization_domain=?";
        $result=$this->tecnologia->getFirstResultQuery($query,array($this->domain),false,"Don't exist extensions for this domain");
        if($result===false){ 
            return false;
        }else
            return $result[0];
    }
    
    //retorna verdadero si se ha alcanzado el maximo numero se extensiones por organizacion
    function maxMunExtensionByOrg(){
        global $arrConf;
        $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
        $qOrg="SELECT value from organization_properties where key=? and category=? and id_organization=(SELECT id from organization where domain=?)";
        $res_num_exten=$pDB->getFirstRowQuery($qOrg,false,array("max_num_exten","limit",$this->domain));
        if($res_num_exten!=false){
            $max_num_exten=$res_num_exten[0];
            if(ctype_digit($max_num_exten)){
                if($max_num_exten!=0){
                    $numExten=$this->getTotalExtensions();
                    if($numExten>=$max_num_exten){
                        $this->errMsg=_tr("Err: You can't create new extensions because you have reached the max numbers of  extensions permitted")." Contact with the server's admin.";
                        return true;
                    }
                }
            }
        }
        return false;
    }
	/**
		funcion utilizada para crear una nueva extension en asterisk
		crea el peer y hace el correspondiente registro de la extension
		en la base elxpbx
	*/
	function createNewDevice($arrProp,$type)
	{
		if(!$this->validatePaloDevice())
			return false;

        if($type=="iax2")
            $this->tecnologia=new paloIax($this->tecnologia->_DB);
        elseif($type=="sip")
            $this->tecnologia=new paloSip($this->tecnologia->_DB);
        else{
            $this->errMsg=_tr("Invalid Technology");
            return false;
        }
        
        if($this->maxMunExtensionByOrg()){
            return false;
        }
        
		$device=$this->code."_".$arrProp['name'];
		if(!$this->existExtension($arrProp['name'],$type)){//se verifica que no exista otra extension y dispositivo igual
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

			//seteamos el callerid del equipo
			$arrProp['callerid']="device <".$arrProp['name'].">";

			if($arrProp['create_vm']=="yes"){
				$arrVoicemail['organization_domain']=$this->domain;
				$arrVoicemail["context"] = isset($arrProp["vmcontext"])?$arrProp["vmcontext"]:null;
				$arrVoicemail["mailbox"] = isset($arrProp['name'])?$arrProp["name"]:null;
				$arrVoicemail["password"] = isset($arrProp["vmpassword"])?$arrProp["vmpassword"]:null;
				$arrVoicemail["email"] = isset($arrProp["vmemail"])?$arrProp["vmemail"]:null;
				$arrVoicemail["attach"] = isset($arrProp["vmattach"])?$arrProp["vmattach"]:null;
				$arrVoicemail["saycid"] = isset($arrProp["vmsaycid"])?$arrProp["vmsaycid"]:null;
				$arrVoicemail["envelope"] = isset($arrProp["vmenvelope"])?$arrProp["vmenvelope"]:null;
				$arrVoicemail["deletevoicemail"] = isset($arrProp["vmdelete"])?$arrProp["vmdelete"]:null;
				$arrVoicemail["fullname"] = isset($arrProp["fullname"])?$arrProp["fullname"]:$arrProp['name'];
				//leer las caractirsiticas que el usuario puede poner en vmoptions, estas deben estar separadas por un " | "
				if(isset($arrProp['vmoptions'])){
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
				if($pVM->createVoicemail()==false){
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
			if($this->tecnologia->insertDB()==false){
				$this->errMsg="Error setting parameter $type device ".$this->tecnologia->errMsg;
				return false;
			}

			//guardar los setting en la tabla extensions; para despues con esta informacion procede a crear las extensiones de tipo local en el plan de marcado
			if(isset($arrProp['rt'])){
				if(!preg_match("/^[[:digit:]]+$/",$arrProp['rt']) && !($arrProp['rt']>0 && $arrProp['rt']<60))
					$arrProp['rt']=15;
				else
					$arrProp['rt']=$arrProp['rt'];
			}else
				$arrProp['rt']=15;

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
					$arrProp["record_out"]="always";
				case "never":
					$arrProp["record_out"]="never";
				default:
					$arrProp["record_out"]="on_demand";
			}

			$outClid=isset($arrProp['out_clid'])?$arrProp['out_clid']:"";
			$exito=$this->insertExtensionDB($this->domain,$type,$arrProp['dial'],$arrProp['name'],$device,$arrProp['rt'],$arrProp['record_in'],$arrProp['record_out'],$this->tecnologia->context,$arrProp["voicemail_context"],$outClid,"","","");
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

	function createFaxExtension($arrProp,$type){
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return false;

		if($type=="iax2")
            $this->tecnologia=new paloIax($this->tecnologia->_DB);
        elseif($type=="sip")
            $this->tecnologia=new paloSip($this->tecnologia->_DB);
        else{
            $this->errMsg=_tr("Invalid Technology");
            $continuar=false;
        }
		
		if(!$this->existExtension($arrProp['name'],$type)){
			$device=$this->code."_".$arrProp['name'];
			$arrProp['dial'] = strtoupper($type)."/".$device;
			$arrProp['organization_domain']=$this->domain;
			
			//validamos que se haya ingresado un secret para el dispositivo
            if(isset($arrProp['secret']) && $arrProp['secret']!=""){
                $arrProp['secret']=md5($arrProp['secret']);
            }else{
                $this->errMsg="Field secret can't be empty";
                return false;
            }
			
			if(isset($arrProp['rt'])){
				if(!preg_match("/^[[:digit:]]+$/",$arrProp['rt']) && !($arrProp['rt']>0 && $arrProp['rt']<60))
					$arrProp['rt']=15;
				else
					$arrProp['rt']=$arrProp['rt'];
			}else
				$arrProp['rt']=15;

			$this->tecnologia->setGroupProp($arrProp,$this->domain);
			if($this->tecnologia->insertDB()==false){
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
			$this->errMsg=$this->tecnologia->errMsg;
		return $result; 
	}

	//en la tabla debe haber un unico numero de de extension por dominio
	private function insertFaxExtensionDB($domain,$tech,$dial,$exten,$device,$rt,$context,$callerIDname,$callerIDnumber)
	{
		$query="INSERT INTO fax (organization_domain,tech,dial,exten,device,rt,context,callerid_name,callerid_number) values (?,?,?,?,?,?,?,?,?)";
		$result=$this->tecnologia->executeQuery($query,array($domain,$tech,$dial,$exten,$device,$rt,$context,$callerIDname,$callerIDnumber));
		if($result==false)
			$this->errMsg=$this->tecnologia->errMsg;
		return $result;
	}

	private function insertDeviceASTDB($arrProp)
	{
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return false;
        
		if(!preg_match("/^[[:alnum:]_]+$/", $this->code)){
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
		$arrSetting["outboundcid"]=isset($arrProp["out_clid"])?$arrProp["out_clid"]:"\"\"";
		$arrSetting["password"]=isset($arrProp["password"])?$arrProp["password"]:"\"\"";
		$arrSetting["ringtimer"]=$arrProp['rt'];
		$arrSetting["voicemail"]=$arrProp["voicemail_context"];

		//validamos los recording
        switch(strtolower($arrProp["record_out"])){
            case "always":
                $stRecord="out=always";
                break;
            case "never":
                $stRecord="out=never";
                break;
            default:
                $stRecord="out=on_demand";
                break;
        }
        $stRecord .="|";
		switch(strtolower($arrProp["record_in"])){
            case "always":
                $stRecord .="in=always";
                break;
            case "never":
                $stRecord .="in=never";
                break;
            default:
                $stRecord .="in=on_demand";
                break;
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
					$error=true;
					break;
				}
			}
		}

		//se guardan los datos del dispositivo, esto realmente sirvecuando a un dispositivo se le ha asociado
		//varias extensiones, por el momento esto no esta soportado
		$family="DEVICE/$code/$code"."_".$arrProp['name'];
		$arrKey=array("default_user"=>$arrProp['name'],"dial"=>$arrProp['dial'],"type"=>"fixed","user"=>$arrProp['name']);
		foreach($arrKey as $key => $value){
			$result=$astMang->database_put($family,$key,$value);
			if(strtoupper($result["Response"]) == "ERROR"){
				$error=true;
				break;
			}
		}

		//si se habilito el callwaiting ingresa ese dato a la base ASTDB
		if(isset($arrProp['callwaiting'])){
			if($arrProp['callwaiting']=="yes")
				$result=$astMang->database_put("CW/$code",$arrProp['name'],"ENABLED");
			else
				$result=$astMang->database_del("CW/$code",$arrProp['name']);
		}else
			$result=$astMang->database_del("CW/$code",$arrProp['name']);
		if(strtoupper($result["Response"]) == "ERROR"){
			$error=true;
		}

		//si tiene activado el screen de llamadas
		if(isset($arrProp['screen'])){
			switch($arrProp['screen']){
				case "memory":
					$result=$astMang->database_put("EXTUSER/$code/".$arrProp['name'],"screen","memory");
					break;
				case "nomemory":
					$result=$astMang->database_put("EXTUSER/$code/".$arrProp['name'],"screen","nomemory");
					break;
				default:
					$result=$astMang->database_del("EXTUSER/$code/".$arrProp['name'],"screen");
					break;
			}
		}
		if(strtoupper($result["Response"]) == "ERROR"){
			$error=true;
		}
		
		//si se activo el servicio de dictation
		if(isset($arrProp['dictate'])){
			if($arrProp['dictate']=="yes"){
				$result=$astMang->database_put("EXTUSER/$code/".$arrProp['name']."/dictate","enabled","enabled");
				switch($arrProp['dictformat']){
					case "gsm":
						$astMang->database_put("EXTUSER/$code/".$arrProp['name']."/dictate","format","gsm");
						break;
					case "wav":
						$astMang->database_put("EXTUSER/$code/".$arrProp['name']."/dictate","format","wav");
						break;
					default:
						$astMang->database_put("EXTUSER/$code/".$arrProp['name']."/dictate","format","ogg");
						break;
				}
				if(isset($arrProp['dictemail'])){
					$astMang->database_put("EXTUSER/$code/".$arrProp['name']."/dictate","email",$arrProp['dictemail']);
				}
			}
		}
		if(strtoupper($result["Response"]) == "ERROR")
			$error=true;
        
        //VmX Locater
        $this->setVmxVoicemailAstDB($arrProp);
        
		//si hubo algun error eliminar los datos que fueron insertados antes del error
		if($error){
			$this->errMsg = _tr("Couldn't be inserted data in ASTDB");
			$result=$astMang->database_delTree("EXTUSER/".$this->code."/".$arrProp['name']);
			$result=$astMang->database_delTree("DEVICE/".$this->code."/".$code."_".$arrProp['name']);
			$result=$astMang->database_del("CW",$this->code."/".$arrProp['name']);
			return false;
		}else
			return true;
	}
	
	private function setVmxVoicemailAstDB($arrProp){
        //VmX Locater
        $state_unavail=$state_busy="disabled";
        $ext0=$ext1=$ext2=$context=$vmx_opts_timeout=null;
        $context=$this->tecnologia->getGlobalVar("VMX_CONTEXT",$this->domain);
        $vmx_opts_timeout=$this->tecnologia->getGlobalVar("VMX_OPTS_TIMEOUT",$this->domain);
        $context=($context==false)?"":$context;
        $vmx_opts_timeout=($vmx_opts_timeout==false)?"":$vmx_opts_timeout;
        
        $familia="EXTUSER/".$this->code."/".$arrProp['name']."/vmx";
        $pri="1";
        
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        }
        //extensiones que marcar
        if(isset($arrProp["vmx_operator"])){
            if($arrProp["vmx_operator"]=="off"){
                if(isset($arrProp["vmx_extension_0"]))
                    $ext0=$arrProp["vmx_extension_0"];
            }
        }
        if(isset($arrProp["vmx_extension_1"]))
            $ext1=$arrProp["vmx_extension_1"];
        if(isset($arrProp["vmx_extension_2"]))
            $ext2=$arrProp["vmx_extension_2"];
        
        for($i=0;$i<3;$i++){
            if(!is_null(${"ext".$i}) && ${"ext".$i}!=""){
                $astMang->database_put("$familia/$i","ext",${"ext".$i});
                $astMang->database_put("$familia/$i","context",$context);
                $astMang->database_put("$familia/$i","pri",$pri);
            }else{
                $astMang->database_del("$familia/$i","ext");
                $astMang->database_del("$familia/$i","context");
                $astMang->database_del("$familia/$i","pri");
            }
        }
         
        if(isset($arrProp["vmx_locator"])){
            if($arrProp["vmx_locator"]=="enabled"){
                if(isset($arrProp["vmx_use"])){
                    if($arrProp["vmx_use"]=="both"){
                        $state_unavail="enabled";
                        $state_busy="enabled";
                    }else{
                        if($arrProp["vmx_use"]=="unavailable"){
                            $state_unavail="enabled";
                        }
                        if($arrProp["vmx_use"]=="busy"){
                            $state_busy="enabled";
                        }
                    }
                }
            }
        }
                    
        if($arrProp["voicemail_context"]=="novm"){
            $state_unavail="bloked";
            $state_busy="bloked";
        }
        
        $astMang->database_put("$familia/unavail","state",$state_unavail);
        $astMang->database_put("$familia/unavail/vmxopts","timeout",$vmx_opts_timeout);
        
        $astMang->database_put("$familia/busy","state",$state_busy);
        $astMang->database_put("$familia/busy/vmxopts","timeout",$vmx_opts_timeout);
	}

	function editDevice($arrProp){
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return false;

        $result=$this->getExtension($arrProp["name"]);
        
		if($result!=false){
            $device=$result['device'];
            $tech=$result['tech'];
            
            if($tech=="iax2")
                $this->tecnologia=new paloIax($this->tecnologia->_DB);
            elseif($tech=="sip")
                $this->tecnologia=new paloSip($this->tecnologia->_DB);
            else{
                $this->errMsg=_tr("Invalid Technology");
                return false;
            }
            
			//si ingreso un nuevo secrte lo actualizamos
			if(isset($arrProp['secret']) && $arrProp['secret']!=""){
				if($tech=="sip"){
					$arrProp['md5secret']=$this->tecnologia->hashMd5Secret($device,$arrProp['secret']);
					$arrProp['secret']=null;
				}
			}

			$arrProp['organization_domain']=$this->domain;

			//verificar si existe un voicemail para la extension
			// 1- Si existe y $arrProp['create_vm']=="yes" => editarlo
			// 2- Si existe y $arrProp['create_vm']=="no" => borrarlo
			// 3- Si no existe y $arrProp['create_vm']=="yes" => crearlo
			$exitoVM=true;
			$pVM=new paloVoicemail($this->tecnologia->_DB);
			$existVM=$pVM->existVoicemail($arrProp['name'],$this->domain);
			if($arrProp['create_vm']=="yes"){
				$arrVoicemail['organization_domain']=$this->domain;
				$arrVoicemail["context"] = isset($arrProp["vmcontext"])?$arrProp["vmcontext"]:null;
				$arrVoicemail["mailbox"] = isset($arrProp['name'])?$arrProp["name"]:null;
				$arrVoicemail["password"] = isset($arrProp["vmpassword"])?$arrProp["vmpassword"]:null;
				$arrVoicemail["email"] = isset($arrProp["vmemail"])?$arrProp["vmemail"]:null;
				$arrVoicemail["attach"] = isset($arrProp["vmattach"])?$arrProp["vmattach"]:null;
				$arrVoicemail["saycid"] = isset($arrProp["vmsaycid"])?$arrProp["vmsaycid"]:null;
				$arrVoicemail["envelope"] = isset($arrProp["vmenvelope"])?$arrProp["vmenvelope"]:null;
				$arrVoicemail["deletevoicemail"] = isset($arrProp["vmdelete"])?$arrProp["vmdelete"]:null;
				$arrVoicemail["fullname"] = isset($arrProp["fullname"])?$arrProp["fullname"]:$arrProp['name'];
				//leer las caractirsiticas que el usuario puede poner en vmoptions, estas deben estar separadas por un " | "
				if(isset($arrProp['vmoptions'])){
					$arrTemp=explode("|",$arrProp['vmoptions']);
					foreach($arrTemp as $value){
						$arrVmOpt=explode("=",$value);
						if(count($arrVmOpt)==2)
							$arrVoicemail[$arrVmOpt[0]]=$arrVmOpt[1];
					}
				}

				if($existVM){
					$exitoVM=$pVM->updateParameters($arrVoicemail);
				}else{
					$pVM->setVoicemailProp($arrVoicemail,$this->domain);
					$exitoVM=$pVM->createVoicemail();
				}
			}else{
				if($existVM){
					$exitoVM=$pVM->deletefromDB($arrProp['name'],$this->domain);
				}
			}

			if(!$exitoVM){
				$this->errMsg=_tr("Error setting voicemail parameters").$pVM->errMsg;
				return false;
			}

			if($arrProp['create_vm']=="yes"){
				$arrProp['mailbox']=$arrProp['name']."@".$this->code."-".$arrVoicemail["context"];
				$arrProp["voicemail_context"]=$this->code."-".$arrVoicemail["context"];
			}else
				$arrProp["voicemail_context"]="novm";

			//actualizamos el dispositivo
			if($this->tecnologia->updateParameters($arrProp)==false){
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
					break;
				case "never":
					$arrProp["record_in"]="never";
					break;
				default:
					$arrProp["record_in"]="on_demand";
					break;
			}

			//validamos los recording
			switch(strtolower($arrProp["record_out"])){
				case "always":
					$arrProp["record_out"]="always";
					break;
				case "never":
					$arrProp["record_out"]="never";
					break;
				default:
					$arrProp["record_out"]="on_demand";
					break;
			}

			$outClid=isset($arrProp['out_clid'])?$arrProp['out_clid']:"";
			$exito=$this->editExtensionDB($this->domain,$arrProp['name'],$arrProp['rt'],$arrProp['record_in'],$arrProp['record_out'], $arrProp['context'],$arrProp["voicemail_context"],$outClid,"","","");
			if($exito){
				if($this->insertDeviceASTDB($arrProp)){
					return true;
				}else{
					$this->errMsg="Extension couldn't be updated .".$this->errMsg;
					return false;
				}
			}else{
				$this->errMsg="Problem when trying updated data in extensions table. ".$this->errMsg;
				return false;
			}
		}else{
			return false;
		}
	}

	function editExtensionDB($domain,$exten,$rt,$record_in,$record_out,$context,$voicemail,$outboundcid,$alias,$mohclass,$noanswer){
		$query="UPDATE extension SET rt=?, record_in=?, record_out=?, context=?, voicemail=?, outboundcid=?, alias=?, mohclass=?, noanswer=? where exten=? and organization_domain=?";
		$result=$this->tecnologia->executeQuery($query,array($rt,$record_in,$record_out,$context,$voicemail,$outboundcid,$alias,$mohclass,$noanswer,$exten,$domain));
		if($result==false)
			$this->errMsg=$this->tecnologia->errMsg;
		return $result; 
	}
	
	
    function editFaxDevice($arrProp){
        //validamos que la organizacion exista
        //validamos que la instacia del objeto haya sido creada correctamente
        if(!$this->validatePaloDevice())
            return true;

        $result=$this->getFaxExtension($arrProp["name"]);

        if($result==false){
            //hubo problemas al hacer la consulta
            return false;
        }else{
            if($result["tech"]=="iax2")
                $this->tecnologia=new paloIax($this->tecnologia->_DB);
            else
                $this->tecnologia=new paloSip($this->tecnologia->_DB);

            $arrProp["organization_domain"]=$this->domain;
            if($this->tecnologia->updateParameters($arrProp)==false){
                $this->errMsg="Error setting parameter ".$result["tech"]." device ".$this->tecnologia->errMsg;
                return false;
            }    
            
            if($this->editFaxExtensionDB($arrProp["name"],$arrProp["fullname"],$arrProp["cid_number"])==false){
                $this->errMsg="Problem when trying updated data in fax table. ".$this->errMsg;
                return false;
            }
            return true;
        }
    }

        //en la tabla debe haber un unico numero de de extension por dominio
    private function editFaxExtensionDB($exten,$callerIDname,$callerIDnumber)
    {
        $query="UPDATE fax SET callerid_name=?, callerid_number=? where exten=? and organization_domain=?";
        $result=$this->tecnologia->executeQuery($query,array($callerIDname,$callerIDnumber,$exten,$this->domain));
        if($result==false)
            $this->errMsg=$this->tecnologia->errMsg;
        return $result;
    }

	//revisar que no exista dentro de la organizacion otra extension con el mismo patron
	function existExtension($extension,$tech)
	{
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

		$exist=true;
		if(!preg_match("/^[[:alnum:]]+$/", $extension)){
			$this->errMsg="Invalid extension format";
			return true;
		}elseif(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $this->domain)){
			$this->errMsg="Invalid domain format";
			return true;
		}
		
		$device=$this->code."_$extension";

		//validamos que el patron de marcado no sea usado como extension
		$exist=$this->tecnologia->existExtension($extension,$this->domain);
		if(!$exist){
			$continuar=true;
			//validamos que no exista el peer
			if($tech=="iax2")
				$this->tecnologia=new paloIax($this->tecnologia->_DB);
			elseif($tech=="sip")
				$this->tecnologia=new paloSip($this->tecnologia->_DB);
			else{
				$this->errMsg=_tr("Invalid Technology");
				$continuar=false;
			}

			if($continuar){
				if(!$this->tecnologia->existPeer($device))
					$exist=false;
				$this->errMsg .=$this->tecnologia->errMsg;
			}
		}else{
			$this->errMsg=$this->tecnologia->errMsg;
		}
		return $exist;
	}

	//recibe el dispositivo asociado a la extension
	function changePasswordExtension($password,$exten){
		//validamos que la organizacion exista
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

		$result=$this->getExtension($exten);
		//verifico que exista el dispositivo al que se le quiere cambiar le password y que el mismo
		//este asociado a una extension

		if($result==false){
			//hubo problemas al hacer la consulta
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
		//validamos que la organizacion exista
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

		$result=$this->getFaxExtension($exten);
		//verifico que exista el dispositivo al que se le quiere cambiar el password y que el mismo
		//este asociado a una extension

		if($result==false){
			//hubo problemas al hacer la consulta
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
		//validamos que la organizacion exista
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

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
		//validamos que la organizacion exista
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

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
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

		$query="Select id, organization_domain, exten, device, tech from fax where exten=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($extension,$this->domain),true,"Don't fax exist extension $extension. ");
		if($result==false && $this->tecnologia->errMsg!="Don't exist fax extension $extension. "){
			$this->errMsg="Fax extension can't be deleted. ".$this->tecnologia->errMsg;
			return false;
		}else{
			$device=$result["device"];
			
			$tech=$result["tech"];    
            if($tech=="sip"){
                $this->tecnologia=new paloSip($this->tecnologia->_DB);
            }elseif($tech=="iax2"){
                $this->tecnologia=new paloIax($this->tecnologia->_DB);
            }
            
			if($this->tecnologia->deletefromDB($device)==false)
				return false;

			$dquery="delete from fax where device=? and tech=? and organization_domain=?";
			$exito=$this->tecnologia->executeQuery($dquery,array($device,$result["tech"],$this->domain));
			if(!$exito){
				$this->errMsg="Extension can't be deleted. ".$this->tecnologia->errMsg;
				return false;
			}

		}
		return true;
	}

	//funcion que se encarga de borrar una extension
    // 1. Se elimina el canal asociado con la extension (sip o iax) de la base de datos
	// 2. Si la extension tiene voicemail se elimina el voicemail
    // 3. Se eliminia el registro correspondiente de esa extension de la tabla extensions
	// 4. Despues de ello se debe mandar a recargar el plan de marcado para que los cambios tengan efecto
    //    dentro de asterisk
	function deleteExtension($extension){
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

		$query="Select id, organization_domain, exten, device, tech, voicemail from extension where exten=? and organization_domain=?";
		$result=$this->tecnologia->getFirstResultQuery($query,array($extension,$this->domain),true,"Don't exist extension $extension. ");
		if($result==false && $this->tecnologia->errMsg!="Don't exist extension $extension. "){
			$this->errMsg="Extension can't be deleted. ".$this->tecnologia->errMsg;
			return false;
		}else{
			if(is_array($result) && count($result)>0){
				//se borra el voicemail asociado a la extension
				if(isset($result["voicemail"]) && $result["voicemail"]!="novm"){
					$pVoicemail= new paloVoicemail($this->tecnologia->_DB);
					$dvoicemial=$pVoicemail->deletefromDB($result["exten"],$this->domain);
				}
				$device=$result["device"];
                $tech=$result["tech"];
                
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
				$this->deleteAstDBExt($extension,$device);
			}
		}
		return true;
	}

	function deleteAstDBExt($exten,$device){
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return true;

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
			$result=$astMang->database_del("Cw",$this->code."/".$exten);
		}
		return true;
	}


	function createDialPlanLocalExtension(&$arrFromInt){
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return false;

		$arrExtensionLocal=array();
		$arrExtensionIvr=array();
		$arrContext=array();

		$query="Select * from extension where organization_domain=?";
		$result=$this->tecnologia->getResultQuery($query,array($this->domain),true,"Don't exist extensions for this domain");
		if($result===false){
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
			$contextoLocal->errMsg="ext-local. Error: ".$contextoLocal->errMsg;
		}else{
			$contextoLocal->arrExtensions=$arrExtensionLocal;
			$arrFromInt[]["name"]="ext-local";//se hace la inclusion del contexto creado en el arreglo de from internal additional
											   //de la organizacion
		}

		$contextofromIvr=new paloContexto($this->code,"from-did-direct-ivr");
		if($contextofromIvr===false){
			$contextofromIvr->errMsg="from-did-direct-ivr. Error: ".$contextofromIvr->errMsg;
		}else
			$contextofromIvr->arrExtensions=$arrExtensionIvr;

		$arrContext=array($contextoLocal,$contextofromIvr);
		return $arrContext; 
	}

	function createDialPlanFaxExtension(&$arrFromInt){
		//validamos que la instacia del objeto haya sido creada correctamente
		if(!$this->validatePaloDevice())
			return false;

		$arrExtensionFax=array();
		$arrFax=array();
		$query="Select * from fax where organization_domain=?";
		$result=$this->tecnologia->getResultQuery($query,array($this->domain),true,"Don't exist faxs extensions for this domain");
		if($result===false){
			$this->errMsg=_tr("Error creating dialplan for faxs extensions").$this->tecnologia->errMsg;
			return false;
		}else{
			if(is_array($result)){
                $i=1;
				foreach($result as $value){
					$exten=$value["exten"];
					$arrExtensionFax[] = new paloExtensions($exten,new ext_noop('Receiving Fax for: '.$value["callerid_name"].' ('.$value["callerid_number"].'), From: ${CALLERID(all)}'),1);
					if($value["rt"]!=0 && isset($value["rt"])){
						$arrExtensionFax[] = new paloExtensions($exten,new ext_setvar("RT",$value["rt"]));
					}
					$arrExtensionFax[] = new paloExtensions($exten,new ext_dial($value["dial"],'${RT}'));
					$arrExtensionFax[] = new paloExtensions($exten,new ext_return());
					$arrFax[]=new paloExtensions("fax",new ext_Gosub("1",$exten),$i);
					$i++;
				}
				$arrFax[]=new paloExtensions("fax",new ext_congestion(),$i);
				$arrFax[]=new paloExtensions("fax",new ext_macro($this->code."-hangupcall",""),++$i);
				$arrExtensionFax[] = new paloExtensions("s",new ext_answer(),1);
				$arrExtensionFax[] = new paloExtensions("s",new ext_wait(4));
				$arrExtensionFax[] = new paloExtensions("h",new ext_macro($this->code."-hangupcall",""),1);
			}
		}

		$arrExtensionFax=array_merge($arrExtensionFax,$arrFax);
		$contextoFax=new paloContexto($this->code,"ext-fax");
		if($contextoFax===false){
			$contextoFax->errMsg="ext-fax. Error: ".$contextoFax->errMsg;
		}else{
			$contextoFax->arrExtensions=$arrExtensionFax;
			$arrFromInt[]["name"]="ext-fax";//se hace la inclusion del contexto creado en el arreglo de from internal additional				   //de la organizacion
		}

		$arrContext=array($contextoFax);
		return $arrContext; 
	}
}
?>