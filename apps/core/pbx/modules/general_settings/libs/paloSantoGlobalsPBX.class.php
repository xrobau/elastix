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
  $Id: paloSantoFeatuteCode.class.php,v 1.1 2012/07/30 rocio mera rmera@palosanto.com Exp $ */

/*
* la tabla globals_settings contiene los valores por default de las globales
  que seran usadas para crear las variables globales de
  de cada organizacion

* la tabla globals contiene los valores de la variables globales
  usadas dentro de cada organizacion
*/
global $arrConf;

include_once $arrConf['basePath']."/libs/paloSantoACL.class.php";
include_once $arrConf['basePath']."/libs/paloSantoConfig.class.php";
include_once $arrConf['basePath']."/libs/paloSantoAsteriskConfig.class.php";
include_once $arrConf['basePath']."/libs/extensions.class.php";
include_once $arrConf['basePath']."/libs/misc.lib.php";


class paloGlobalsPBX extends paloAsteriskDB{
	protected $code;
	protected $domain;
	public $errMsg;
	public $_DBSQLite;

	function paloGlobalsPBX(&$pDB,$domain){
		if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
			$this->errMsg="Invalid domain format";
		}else{
			$this->domain=$domain;

			parent::__construct($pDB);
            
			$result=$this->getCodeByDomain($domain);
			if($result==false){
				$this->errMsg .=_tr("Can't create a new instace of paloGlobalsPBX").$this->errMsg;
			}else{
				$this->code=$result["code"];
			}
		}
	}
	
	function validateGlobalsPBX(){
        //validamos que la instancia de paloDevice que se esta usando haya sido creda correctamente
        if(is_null($this->code) || is_null($this->domain))
            return false;
        return true;
    }
	
	/**
        obtine una lista de los tonos de marcado por paises
        que estan registrados en el archivo indications.conf
        En el archivo indications.conf se define el modo de marcado 
        de cada pais
	*/
    function getToneZonePBX(){
        $arrTZ=array();
        $arrCountry=getCountry();
        
        $pConfig = new paloConfig("/var/www/elastixdir/asteriskconf", "elastix_pbx.conf", "=", "[[:space:]]*=[[:space:]]*");
        $arrConfig = $pConfig->leer_configuracion(false);
        
        $astIndications = $arrConfig['ASTETCDIR']['valor']."/indications.conf";
        $content=file($astIndications);
        if($content===false){
            return false;
        }else{
            foreach($content as $value){
                if(preg_match("/^\[[a-z]{2}\]$/",$value)){
                    $str=str_replace(array("[","]"),"",$value);
                    $arrTz[$str]=$str;
                }
            }
        }
        return $arrTz;
    }
    
    /**
        esta funcion solo es llamada al momento de crear una nueva organizacion dentro del sisitema
    */
    function insertDBGlobals($country,&$_DBSQLite){
        $arrCredentiasls=getUserCredentials();
        $userLevel1=$arrCredentiasls["userlevel"];
        if($userLevel1!="superadmin"){
            $this->errMsg =_tr("You are no authorized to perform this action");
            return false;
        }
        
        $queryCode="SELECT code from organization where domain=?";
        $code=$_DBSQLite->getFirstRowQuery($queryCode, false, array($this->domain));
        if($code===false){
            $this->errMsg = $this->_DBSQLite->errMsg;
            return false;
        }elseif(count($code)==0){
            $this->errMsg = _tr("Organization doesn't exist");
            return false;
        }else
            $this->code=$code[0];
        
        $query="INSERT INTO globals values (?,?,?)";

        $arrLngPBX=getLanguagePBX();
        $arrTZPBX=$this->getToneZonePBX();
        //de acuerdo al pais al que pertenece la organizacion se seleccion el
        //pais y el TONEZONE del mismo, en caso de no existir entre los que se
        //encuantrarn configurados en el servidor asterisk, se escogen los valoras por
        //default
        $language=$tonezone="";
        $arrSettings=getCountrySettings($country);
        if($arrSettings!=false){
            if($arrSettings["language"]!=""){
                if(array_key_exists($arrSettings["language"],$arrLngPBX))
                    $language=$arrSettings["language"];
            }
            if($arrSettings["tonezone"]!=""){
                 if(array_key_exists($arrSettings["tonezone"],$arrTZPBX))
                    $tonezone=$arrSettings["tonezone"];
            }
        }
        
        //acabamos de crear la organizacion y llenamos con los valores
        //default de las globales
        $arrProp=$this->getAllGlobalSettings();
        //print_r($arrProp);
        if($arrProp===false){
            return false;
        }else{
            foreach($arrProp as $property){
                switch($property["variable"]){
                    case "LANGUAGE":
                        $value=(empty($language))?$property["value"]:$language;
                        break;
                    case "TONEZONE":
                        $value=(empty($tonezone))?$property["value"]:$tonezone;
                        break;
                    case "MIXMON_DIR":
                        $value=(empty($property["value"]))?"":$property["value"].$this->domain."/";
                        break;
                    case "VMX_CONTEXT":
                        $value=(empty($property["value"]))?"":$this->code."-".$property["value"];
                        break;
                    case "VMX_TIMEDEST_CONTEXT":
                        $value=(empty($property["value"]))?"":$this->code."-".$property["value"];
                        break;
                    case "VMX_LOOPDEST_CONTEXT":
                        $value=(empty($property["value"]))?"":$this->code."-".$property["value"];
                        break;
                    case "TRANSFER_CONTEXT":
                        $value=(empty($property["value"]))?"":$this->code."-".$property["value"];
                        break;
                    default:
                        $value=isset($property["value"])?$property["value"]:"";
                        break;
                }
                $insert=$this->_DB->genQuery($query,array($this->domain,$property["variable"],$value));
                if($insert==false){
                    $this->errMsg=_tr("Problem setting globals variables").$this->_DB->errMsg;
                    break;
                }
            }
            return $insert;
        }
    }
    
    function updateGlobalsDB($arrProp){
        
    }
    
    function getGlobalVar($variable){
        $query="SELECT value from globals where organization_domain=? and variable=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($this->domain,$variable));
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result;
    }
    
    function getGlobalVarSettings($variable){
        $query="SELECT value from globals_settings where variable=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($variable));
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result;
    }
    
    /**
        la tabla globals contiene los valores de la variables globales
        usadas dentro de la organizacion
    */
    function getAllGlobals(){
        $query="SELECT variable,value from globals where organization_domain=?";
        $result=$this->_DB->fetchTable($query,true,$this->domain);
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result;
    }
    
    /**
        la tabla globals_settings contiene los valores por default de las globales
        que seran usadas para crear las variables globales de
        de cada organizacion
    */
    function getAllGlobalSettings(){
        $query="SELECT variable,value from globals_settings";
        $result=$this->_DB->fetchTable($query,true);
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result;
    }
}
?>