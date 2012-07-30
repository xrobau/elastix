<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-29                                               |
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
  $Id: paloSantoExtensions.class.php,v 1.1 2012-07-18 11:50:00 Rocio Mera rmera@palosanto.com Exp $ */
    include_once "libs/paloSantoACL.class.php";
	include_once "libs/paloSantoAsteriskConfig.class.php";
	include_once "libs/paloSantoPBX.class.php";
	global $arrConf;
class paloSantoExtensions{
    var $_DB; //conexion base de mysql elx_pbx
    var $errMsg;

    function paloSantoExtensions(&$pDB)
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

    function getNumExtensions($domain=null){
		$where="";
		$arrParam=null;

		if(isset($domain)){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format";
				return false;
			}else{
				$where="where organization_domain=?";
				$arrParam=array($domain);
			}
		}
		$query="SELECT count(id) from extension $where";
		$result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result==false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result[0];
    }

	function getExtensions($domain=null){
		$where="";
		$arrParam=null;
		if(isset($domain)){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format";
				return false;
			}else{
				$where="where organization_domain=?";
				$arrParam=array($domain);
			}
		}

		$query="SELECT * from extension $where";
		$result=$this->_DB->fetchTable($query,true,$arrParam);
        if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result;
    }

	function getExtensionByNum($domain,$exten){
		if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
			$this->errMsg="Invalid domain format";
			return false;
		}

		$query="SELECT * from extension where organization_domain=? and exten=?";
		$arrParam=array($domain,$exten);
		$result=$this->_DB->fetchTable($query,true,$arrParam);
        if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result;
    }

	//debo devolver un arreglo que contengan los parametros de la extension, dispositivo y voicemail
	function getExtensionById($id,$domain=null){
		global $arrConf;
		$arrExtension=array();
		$where="";
		if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = "Extension ID must be numeric";
			return false;
        }

		$param=array($id);
		if(isset($domain)){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format";
				return false;
			}else{
				$where=" and organization_domain=?";
				$param[]=$domain;
			}
		}

		$query="SELECT tech,exten,outboundcid,rt,record_in,record_out,organization_domain,voicemail,device from extension where id=? $where";
		$result=$this->_DB->getFirstRowQuery($query,true,$param);
        if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}elseif(count($result)>0){
			$arrExtension["technology"]=$result["tech"];
			$arrExtension["exten"]=$result["exten"];
			//$arrExtension["clid_name"]=$result["clid_name"];
			//$arrExtension["clid_number"]=$result["clid_number"];
			$arrExtension["ring_timer"]=$result["rt"];
			$arrExtension["record_in"]=$result["record_in"];
			$arrExtension["record_out"]=$result["record_out"];
			//obtenemos las caracteristicas de voicemail de la extension en caso de que este tenga creada uno
			if(isset($result["voicemail"]) && $result["voicemail"]!="no"){
				$query="SELECT * from voicemail where mailbox=? and organization_domain=?";
				$voicemail=$this->_DB->getFirstRowQuery($query,true,array($result["exten"],$result["organization_domain"]));
				if($voicemail==false){
					$arrExtension["create_vm"]="no";
					if($voicemail===false)
						$this->errMsg .=_tr("Error getting voicemail").$this->_DB->errMsg;
				}else{
					$arrExtension["create_vm"]="yes";
					$option="";
					foreach($voicemail as $key => $value){
						switch($key){
							case "password":
								$arrExtension["vmpassword"]=$value;
								break;
							case "email":
								$arrExtension["vmemail"]=$value;
								break;
							case "attach":
								$arrExtension["vmattach"]=$value;
								break;
							case "saycid":
								$arrExtension["vmsaycid"]=$value;
								break;
							case "deletevoicemail":
								$arrExtension["vmdelete"]=$value;
								break;
							case "envelope":
								$arrExtension["vmenvelope"]=$value;
								break;
							case "context":
								$arrExtension["vmcontext"]=$value;
								break;
							case "mailbox":
								break;
							default:
								if(isset($value)){
									if($key!="uniqueid" && $key!="organization_domain" && $key!="stamp"){
										$option .="$key=$value|";
									}
								}
						}
						$arrExtension["vmoptions"] = empty($option)?"":substr($option,0,-1);
					}
				}
			}
			//obtenemos las otras caracticas de la configuracion del dispositivo
			if($result["tech"]=="iax2"){
				$queryDev="SELECT context,dial,host,type,allow,disallow,port,qualify,accountcode,deny,permit,language,amaflags,";
				$queryDev .="defaultip,username,mohinterpret,mohsuggest,transfer,requirecalltoken,mask,jitterbuffer,forcejitterbuffer,";
				$queryDev .="codecpriority,qualifysmoothing,qualifyfreqok,qualifyfreqnotok,encryption,timezone,sendani,adsi from iax where name=? and organization_domain=?";
			}elseif($result["tech"]=="sip"){
				$queryDev="SELECT context,dial,host,type,allow,disallow,port,qualify,accountcode,deny,permit,language,amaflags,";
				$queryDev .="defaultip,username,mohinterpret,mohsuggest,dtmfmode,nat,canreinvite,allowtransfer,callgroup,pickupgroup,";
				$queryDev .="mailbox,vmexten,defaultuser,useragent,directmedia,callcounter,busylevel,videosupport,maxcallbitrate,";
				$queryDev .="qualifyfreq,rtptimeout,rtpholdtimeout,rtpkeepalive,progressinband,g726nonstandard,vmexten from sip where name=?   and organization_domain=?";
			}else{
				$this->errMsg .=_tr("Invalid Technology");
			}
			if(isset($queryDev)){
				$device=$this->_DB->getFirstRowQuery($queryDev,true,array($result["device"],trim($result["organization_domain"])));
				if($device==false){
					$this->errMsg .=_tr("Error getting device settings").$this->_DB->errMsg;
				}else{
					foreach($device as $key => $value){
						if(isset($value)){
							$arrExtension[$key]=$value;
						}
					}
				}
			}

			$arrExtension["domain"]=$result["organization_domain"];

			$pORGZ = new paloSantoOrganization($arrConf['elastix_dsn']['elastix']);
			$orgTmp=$pORGZ->getOrganizationByDomain_Name($result["organization_domain"]);
			if($orgTmp!=false){
				$astMang=AsteriskManagerConnect($errorM);
				if($astMang==false){
					$this->errMsg .=$errorM;
				}else{
					$arrExtension["clid_name"]=$astMang->database_get("EXTUSER/".$orgTmp["code"]."/".$result["exten"], "cidname");
					$arrExtension["clid_number"]=$astMang->database_get("EXTUSER/".$orgTmp["code"]."/".$result["exten"], "cidnum");
					$arrExtension["call_waiting"]=($astMang->database_get("CW/".$orgTmp["code"], $result["exten"])=="ENABLED")?"yes":"no";
					$arrExtension["screen"]=$astMang->database_get("EXTUSER/".$orgTmp["code"]."/".$result["exten"],"screen");
					$enDictate=$astMang->database_get("EXTUSER/".$orgTmp["code"]."/".$result["exten"]."/dictate", "enabled");
					$arrExtension["dictate"]=($enDictate=="enabled")?"yes":"no";
					$arrExtension["dictformat"]=$astMang->database_get("EXTUSER/".$orgTmp["code"]."/".$result["exten"]."/dictate", "format");
					$arrExtension["dictemail"]=$astMang->database_get("EXTUSER/".$orgTmp["code"]."/".$result["exten"]."/dictate", "email");
				}
			}
		}
		return $arrExtension;
    }

	function getDefaultSettings($domain,$tech){
		$arrExtension=array();
		$queryV="SELECT attach,context,deletevoicemail,saycid,envelope from voicemail_general where organization_domain=?";
		$resultV=$this->_DB->getFirstRowQuery($queryV,true,array($domain));
		if($resultV==false){
			$this->errMsg .=_tr("Error getting voicemail default settings").$this->_DB->errMsg;
		}else{
			$arrExtension["vmcontext"]=isset($resultV["context"])?$resultV["context"]:null;
			$arrExtension["vmattach"]=isset($resultV["attach"])?$resultV["attach"]:null;
			$arrExtension["vmdelete"]=isset($resultV["deletevoicemail"])?$resultV["deletevoicemail"]:null;
			$arrExtension["vmsaycid"]=isset($resultV["saycid"])?$resultV["saycid"]:null;
			$arrExtension["vmenvelope"]=isset($resultV["envelope"])?$resultV["envelope"]:null;
		}
		if($tech=="sip")
			$tech ="sip_general";
		elseif($tech=="iax2")
			$tech ="iax_general";
		$queryT="SELECT record_in,record_out,rt,callwaiting from $tech where organization_domain=?";
		$resultT=$this->_DB->getFirstRowQuery($queryT,true,array($domain));
		if($resultT==false){
			$this->errMsg .=_tr("Error getting technology default settings").$this->_DB->errMsg;
		}else{
			$arrExtension["record_in"]=isset($resultT["record_in"])?$resultT["record_in"]:null;
			$arrExtension["record_out"]=isset($resultT["record_out"])?$resultT["record_out"]:null;
			$arrExtension["ring_timer"]=isset($resultT["ring_timer"])?$resultT["rt"]:null;
			$arrExtension["call_waiting"]=isset($resultT["callwaiting"])?$resultT["callwaiting"]:null;
		}
		return $arrExtension;
	}
}
?>