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
  $Id: index.php,v 1.1.1.1 2012/07/30 rocio mera rmera@palosanto.com Exp $ */
    include_once "libs/paloSantoACL.class.php";
    include_once "libs/paloSantoAsteriskConfig.class.php";
    include_once "libs/paloSantoPBX.class.php";
	global $arrConf;
class paloSantoTrunk extends paloAsteriskDB{
    public $tech;

    function paloSantoTrunk(&$pDB)
    {
       parent::__construct($pDB);
    }

    function getNumTrunks($domain=null,$tech=null,$status=null){
        $where=array();
        $arrParam=null;

        $query="SELECT count(t.trunkid) FROM trunk as t";
        if(!empty($domain) && $domain!='all'){
            $where[]=" t.trunkid IN (SELECT trunkid FROM trunk_organization WHERE organization_domain=?)";
            $arrParam[]=$domain;
        }
        if(!empty($tech)){
            $where[]=" tech=? ";
            $arrParam[]=$tech;
        }
        if(!empty($status)){
            $where[]=" disabled=? ";
            $arrParam[]=$status;
        }
        if(count($where)>0){
            $query .=" WHERE ".implode(" AND ",$where);
        }
        
        $result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result==false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result[0];
    }


    function getTrunks($domain=null,$tech=null,$status=null,$limit=null,$offset=null){
        $where=array();
        $arrParam=null;
        
        $query="SELECT * from trunk";
        if(!empty($domain) && $domain!='all'){
            $where[]=" trunkid IN (SELECT trunkid FROM trunk_organization WHERE organization_domain=?)";
            $arrParam[]=$domain;
        }
        if(!empty($tech)){
            $where[]=" tech=? ";
            $arrParam[]=$tech;
        }
        if(!empty($status)){
            $where[]=" disabled=? ";
            $arrParam[]=$status;
        }
        if(count($where)>0){
            $query .=" WHERE ".implode(" AND ",$where);
        }
        
        if(isset($limit) && isset($offset)){
            $query .=" limit ? offset ?";
            $arrParam[]=$limit;
            $arrParam[]=$offset;
        }
        
        $result=$this->_DB->fetchTable($query,true,$arrParam);
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result;
    }

    function getArrDestine($idTrunk){
        $query="SELECT * from trunk_dialpatterns WHERE trunkid=? order by seq";
        $result=$this->_DB->fetchTable($query,false,array($idTrunk));

        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 
    }

    //debo devolver un arreglo que contengan los parametros del Trunk
    function getTrunkById($id){
        global $arrConf;
        $arrTrunk=array();
        if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = _tr("Trunk ID must be numeric");
            return false;
        }
        
        $query="SELECT * from trunk where trunkid=?";
        
        $result=$this->_DB->getFirstRowQuery($query,true,array($id));
        
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }elseif(count($result)>0){
            $arrTrunk["trunk_name"]=$result["name"];
            $arrTrunk["tech"]=$result["tech"];
            $arrTrunk["outcid"]=$result["outcid"];
            $arrTrunk["keepcid"]=$result["keepcid"];
            $arrTrunk["maxchans"]=$result["maxchans"];
            $arrTrunk["dialoutprefix"]=$result["dialoutprefix"];
            $arrTrunk["channelid"]=$result["channelid"];
            $arrTrunk["disabled"]=$result["disabled"];
            $arrTrunk["register"]=$result["string_register"]; 
            $arrTrunk["sec_call_time"]=$result["sec_call_time"];
            
            if($arrTrunk["tech"]=="iax2"){
                $tech="iax";
            }else{
                $tech=$arrTrunk["tech"];
            }
            
            //obtenemos los detalles del peer si la truncal es de tipo sip o iax2
            if($tech=="sip" || $tech=="iax"){
                $query="SELECT * from $tech where name=?";
                $result=$this->_DB->getFirstRowQuery($query,true,array($arrTrunk["channelid"]));
                if($result==false){
                    $this->errMsg=_tr("Error getting peer details. ").$this->_DB->errMsg;
                    return false;
                }else{
                    $arrTrunk=array_merge($result,$arrTrunk);
                }
            }
            
            $arrTrunk["select_orgs"]=array();
            //obtenemos las organizaciones asociadas a las truncal
            $query="SELECT organization_domain from trunk_organization where trunkid=?";
            $result=$this->_DB->fetchTable($query,true,array($id));
            if($result===false){
                $this->errMsg=_tr("Error getting organizations related with trunks. ").$this->_DB->errMsg;
                return false;
            }else{
                foreach($result as $value){
                    $arrTrunk["select_orgs"][]=$value["organization_domain"];
                }
            }
            
            if($arrTrunk["sec_call_time"]=="yes"){
                $arrSec=$this->getSecTimeASTDB($id);
                foreach($arrSec as $key => $value){
                    $arrTrunk[$key]=$value;
                }
            }
            return $arrTrunk;
        }
    }

    function getSecTimeASTDB($id_trunk){
        $arrSec=array("maxcalls_time"=>0,"period_time"=>"3600","BLOCK"=>"");
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
        }else{
            $family="TRUNK/$id_trunk/COUNT_TIME";
            $result=$astMang->database_show($family);
            foreach($result as $key => $value){
                switch($key){
                    case "/$family/PERIOD":
                        $arrSec["period_time"]=$value/60;
                        break;
                    case "/$family/MAX":
                        $arrSec["maxcalls_time"]=$value;
                        break;
                    default:
                        $arrSec[substr(strrchr($key, "/"), 1)]=$value;
                        break;
                }
            }
            $astMang->disconnect();
        }
        return $arrSec;
    }

    function getDefaultConfig($tech){
        $arrTrunk=array();
        $arrTrunk["type"]="friend";
        $arrTrunk["qualify"]="yes";
        $arrTrunk["context"]="from-pstn";
        $arrTrunk["disallow"]="all";
        $arrTrunk["allow"]="ulaw;alaw;gsm";
        $arrTrunk["deny"]="0.0.0.0/0.0.0.0";
        $arrTrunk["permit"]="0.0.0.0/0.0.0.0";
        $arrTrunk["sec_call_time"]="no";
        $arrTrunk["period_time"]="60";
        if($tech=="sip"){
            $arrTrunk["insecure"]="port,invite";
            $arrTrunk["host"]="dynamic";
            $arrTrunk["nat"]="noset";
            $arrTrunk["dtmfmode"]="auto";
            $arrTrunk["sendrpid"]="no";
            $arrTrunk["trustrpid"]="no";
            $arrTrunk["directmedia"]="no";
            $arrTrunk["useragent"]="";
        }elseif($tech=="iax2"){
            $arrTrunk["auth"]="plaintext";
            $arrTrunk["host"]="dynamic";
            $arrTrunk["trunk"]="yes";
            $arrTrunk["trunkfreq"]="20";
            $arrTrunk["trunktimestamps"]="yes";
            $arrTrunk["sendani"]="yes";
            $arrTrunk["adsi"]="no";
        }
        return $arrTrunk;
    }

    function existTrunk($tech,$name){
        $exist=true;
        if(!preg_match("/^(sip|iax2|dahdi|custom)$/",$tech)){
            $this->errMsg="Invalid technology";
            return true;
        }
        
        if($tech=="sip" || $tech=="iax2"){
            $msg=_tr("Peer Name can't be empty");
        }else{
            $ttt=($tech=="dahdi")?_tr("DAHDI Identifier"):_tr("Dial String");
            $msg=_tr("$ttt can't be empty");
        }
        if(!isset($name) || $name==""){
            $this->errMsg=$msg;
            return true;
        }
        
        $query="SELECT channelid from trunk where tech=? and channelid=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($tech,$name));
        if($result===false || count($result)!=0){
            $this->errMsg=$this->_DB->errMsg;
            return true;
        }
        
        return false;
    }

    function createNewTrunk($arrProp,$arrDialPattern){
        //definimos el tipo de truncal que vamos a crear
        if(!preg_match("/^(sip|iax2|dahdi|custom)$/",$arrProp["tech"])){
            $this->errMsg="Invalid tech trunk";
            return false;
        }
                
        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["trunk_name"]) || $arrProp["trunk_name"]==""){
            $this->errMsg="Name of trunk can't be empty";
            return false;
        }

        if(!isset($arrProp["outcid"])){
            $arrProp["outcid"]="";
        }
        
        //caller id options
        if(!preg_match("/^(on|off|cnum|all)$/",$arrProp["keepcid"])){
            $this->errMsg="Invalid CID Options";
            return false;
        }
        if($arrProp["keepcid"]=="all"){
            if(empty($arrProp["outcid"])){
                $this->errMsg="Field 'Outbound Caller ID' can't be empty";
                return false;
            }
        }
        
        //si existe un maximo numero de canales
        if($arrProp["max_chans"]!=""){
            if(!preg_match("/^[[:digit:]]+$/",$arrProp["max_chans"])){
                $this->errMsg="Invalid value field Maximun Channels";
                return false;
            }else{
                $arrProp["max_chans"]=$arrProp["max_chans"]+0;
            }
        }

        //outbound dial prefix
        if($arrProp["dialout_prefix"]!==""){
            if(!preg_match("/^[0-9w\\+#]+$/",$arrProp["dialout_prefix"])){
                $this->errMsg="Invalid value field Outbound Dial Prefix";
                return false;
            }
        }
        
        if(!isset($arrProp["register"])){
            $arrProp["register"]="";
        }

        if($arrProp["tech"]=="dahdi" || $arrProp["tech"]=="custom"){
            //no debe haber otra truncal de la misma tecnologia con el mismo channelid
            $ttt=($arrProp["tech"]=="dahdi")?_tr("DAHDI Identifier"):_tr("Dial String");
            if($arrProp["tech"]=="dahdi"){
                if(!preg_match("/^(g|r){0,1}[0-9]+$/",$arrProp["channelid"])){
                    $error=_tr("Field DAHDI Identifier can't be empty and must be a dahdi number or channel number");
                    return false;
                }
            }
            if($this->existTrunk($arrProp["tech"],$arrProp["channelid"])==true){
                $this->errMsg=_tr("Already Exist another {$arrProp["tech"]} trunk with the same $ttt. ").$this->errMsg;
                return false;
            }
        }else{
            $TYPE=strtoupper($arrProp["tech"]);
            $arrProp["channelid"]=$arrProp["name"];
            //no debe haber otra truncal con el mismo nombre de peer
            if($this->existTrunk($arrProp["tech"],$arrProp["name"])==true){
                $this->errMsg=_tr("Already exist another $TYPE trunk with Peer Name. ").$this->errMsg;
                return false;
            }
            if($this->createSipIaxTrunk($arrProp)==false){
                $this->errMsg=_tr("Error when trying created $TYPE Peer. ").$this->errMsg;
                return false;
            }
        }
        
        $query="INSERT INTO trunk (name,tech,outcid,keepcid,maxchans,dialoutprefix,channelid,disabled,string_register) values (?,?,?,?,?,?,?,?,?)";
        $exito=$this->executeQuery($query,array($arrProp["trunk_name"],$arrProp["tech"],$arrProp["outcid"],$arrProp["keepcid"],$arrProp["max_chans"],$arrProp["dialout_prefix"],$arrProp["channelid"],$arrProp["disabled"],$arrProp["register"]));
        
        if($exito==true){
            //si ahi dialpatterns se los procesa
            $query="SELECT trunkid from trunk where tech=? and channelid=?";
            $result=$this->_DB->getFirstRowQuery($query,false,array($arrProp["tech"],$arrProp["channelid"]));
            $trunkid=$result[0];
            if($this->createDialPattern($arrDialPattern,$trunkid)==false){
                $this->errMsg=_tr("Trunk can't be created .").$this->errMsg;
                return false;
            }
        }else{
            $this->errMsg=_tr("Trunk could not be created .").$this->_DB->errMsg;
            return false;
        }
        
        //guardamos las organizaciones relacionadas con la truncal
        if($this->trunkOrganization($trunkid,$arrProp["select_orgs"])==false){
            return false;
        }else{
            if($this->setTrunkASTDB($trunkid,$arrProp)==false){
                $this->errMsg=_tr("Trunk could not be updated.").$this->errMsg;
                return false;
            }else
                return true;
        }
    }

    private function trunkOrganization($id_trunk,$arrOrg){
        global $arrConf;
        $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
        $pORGZ = new paloSantoOrganization($pDB2);
        $arrTmp=$pORGZ->getOrganization("","","","");
        $arrOrgz=array();
        foreach($arrTmp as $value){
            if(!empty($value["domain"]))
                $arrOrgz[$value["domain"]]=$value["domain"];
        }
        
        $query="INSERT into trunk_organization values (?,?)";
        //obtenemos las oraganizacion seleccionadas
        $orgs=explode(",",$arrOrg);
        foreach($orgs as $value){
            if($value!=""){
                if(in_array($value,$arrOrgz)){
                    if($this->_DB->genQuery($query,array($id_trunk,$value))==false){
                        $this->errMsg .=_tr("Organization in trunk couldn't be setted. ").$this->_DB->errMsg;
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function createSipIaxTrunk($arrProp){
        $tech=$arrProp["tech"];
        if($tech=="sip"){
            $this->type=new paloSip($this->_DB);
        }elseif($tech=="iax2"){
            $tech="iax";
            $this->type=new paloIax($this->_DB);
        }else{
            $this->errMsg=_tr("Invalid technology");
            return false;
        }
        
        //valido que no exista otro peer con el mismo nombre
        $query="SELECT name from $tech where name=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($arrProp["name"]));
        if($result===false || count($result)!=0){
            $this->errMsg=_tr("Already exista a $tech peer with the same name .").$this->_DB->errMsg;
            return false;
        }
        
        if(!isStrongPassword($arrProp["secret"])){
            $error=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
            return false;
        }
        
        if($tech=="sip"){
            $arrProp['md5secret']=$this->type->hashMd5Secret($arrProp['name'],$arrProp['secret']);
            $arrProp['secret']="";
        }
        
        //los campos deny y permit no pueden ser vacios en caso que se use tecnologia iax2
        if(!$this->validateIP($arrProp['deny'])){
            $arrProp['deny']="0.0.0.0/0.0.0.0";
        }
        
        if(!$this->validateIP($arrProp['permit'])){
            $arrProp['permit']="0.0.0.0/0.0.0.0";
        }
        
        if(empty($arrProp['host'])){
            $arrProp['host']="dynamic";
        }
        
        $arrValues=array();
        $question="(";
        $Prop="(";
        $i=0;
        foreach($arrProp as $key => $value){
            if(property_exists($this->type,$key)){
                if($value!="noset"){
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
                        default:
                            $Prop .=$key.",";
                            break;
                    }
                    $arrValues[$i]=$value;
                    $question .="?,";
                    $i++;
                }
            }
        }
        
        $question=substr($question,0,-1).")";
        $Prop=substr($Prop,0,-1).")";

        $query="INSERT INTO $tech $Prop value $question";
        if($this->executeQuery($query,$arrValues)){
            return true;
        }else
            return false;
    }

    private function setTrunkASTDB($trunkid,$arrProp){
        //caracteristicas necesarias para activar caracteristica de seguridad
        $disabled=$arrProp["disabled"];
        $set_security=$arrProp["sec_call_time"];
        $period_time=$arrProp["period_time"];
        $max_calls=$arrProp["maxcalls_time"];

        $errorM="";    
        if($set_security!="yes"){
            $set_security="no";
        }
        $family="TRUNK/$trunkid/COUNT_TIME";
        
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        }else{
            //borramos todos los campos anteriores de la truncal
            $result=$astMang->database_delTree("TRUNK/$trunkid");
            
            //campos usados dentro del plan de marcado, estos antes eran globales
            $tech=strtoupper($arrProp["tech"]);
            $channelId=$arrProp["channelid"];
            $astMang->database_put("TRUNK/$trunkid","OUT","$tech/$channelId");
            
            $outcid="";
            if(isset($arrProp["outcid"])){
                if($arrProp["outcid"]!=""){
                    $outcid="\"".$arrProp["outcid"]."\"";
                    $astMang->database_put("TRUNK/$trunkid","OUTCID",$outcid);
                }
            }
            
            if(preg_match("/^[0-9]$/",$arrProp["max_chans"]))
                $astMang->database_put("TRUNK/$trunkid","OUTMAXCHANS",$arrProp["max_chans"]);
                
            $outprefix=isset($arrProp["dialout_prefix"])?$arrProp["dialout_prefix"]:"";
            $astMang->database_put("TRUNK/$trunkid","OUTPREFIX",$outprefix);
            
            $disabled="off";
            if(isset($arrProp["disabled"])){
                if($arrProp["disabled"]=="on")
                    $disabled="on";
            }
            $astMang->database_put("TRUNK/$trunkid","OUTDISABLE",$disabled);
            
            $keepCid="off";
            if(isset($arrProp["keepcid"])){
                switch($arrProp["keepcid"]){
                    case "on":
                        $keepCid="on";
                        break;
                    case "cnum":
                        $keepCid="cnum";
                        break;
                    case "all":
                        $keepCid="all";
                        break;
                }
            }
            $astMang->database_put("TRUNK/$trunkid","OUTKEEPCID",$keepCid);
            
            if($keepCid=="all")
                $astMang->database_put("TRUNK/$trunkid","FORCEDOUTCID",$outcid);
            
            $qPrefix="SELECT count(trunkid) from trunk_dialpatterns where trunkid=?";
            $trunkPrefix=$this->_DB->getFirstRowQuery($qPrefix,false,array($trunkid));
            if($trunkPrefix[0]!="0")
                $astMang->database_put("TRUNK/$trunkid","PREFIX_TRUNK",$trunkPrefix[0]);
            
            
            //security max call by period of time
            if($set_security=="yes"){
                if(!preg_match("/^[0-9]+$/",$max_calls)){
                    $this->errMsg=_tr("Field 'Max Num Calls' can't be empty");
                    return false;
                }
                if(!preg_match("/^[0-9]+$/",$period_time)){
                    $this->errMsg=_tr("Field 'Period of Time' can't be empty");
                    return false;
                }
                
                //el periodo de tiempo se lo almacena en segundos
                //period_time se lo recibe en minutos
                if(ctype_digit($period_time)){
                    $period=$period_time*60;
                }else
                    $period=3600;
                    
                $start_time = time();
                $count=0;    
                $astMang->database_put($family,"PERIOD",$period);
                $astMang->database_put($family,"COUNT",0);
                $astMang->database_put($family,"MAX",$max_calls);
                $astMang->database_put($family,"NUM_PERIOD",0);
                $astMang->database_put($family,"START_TIME",$start_time);
            }
            $astMang->disconnect();
        }
        
        return $this->executeQuery("Update trunk set sec_call_time=? where trunkid=?",array($set_security,$trunkid));
    } 

    function updateTrunkPBX($arrProp,$arrDialPattern){
        //verificamos que la truncal exista
        $idTrunk=$arrProp["id_trunk"];
        $query="SELECT tech,channelid from trunk where trunkid=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($idTrunk));
        if($result===false || count($result)==0){
            $this->errMsg=_tr("Trunk dosen't exist");
            return false;
        }
        
        $arrProp["tech"]=$result["tech"];
        $channelid=$result["channelid"];
        
        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["trunk_name"]) || $arrProp["trunk_name"]==""){
            $this->errMsg="Name of trunk can't be empty";
            return false;
        }

        //caller id options
        if(!preg_match("/^(on|off|cnum|all)$/",$arrProp["keepcid"])){
            $this->errMsg="Invalid CID Options";
            return false;
        }
        
        if($arrProp["keepcid"]=="all"){
            if(empty($arrProp["outcid"])){
                $this->errMsg="Field 'Outbound Caller ID' can't be empty";
                return false;
            }
        }
        
        if(empty($arrProp["outcid"])){
            $arrProp["outcid"]="";
        }
        
        //si existe un maximo numero de canales
        if($arrProp["max_chans"]!=""){
            if(!preg_match("/^[[:digit:]]+$/",$arrProp["max_chans"])){
                $this->errMsg="Invalid value field Maximun Channels";
                return false;
            }else{
                $arrProp["max_chans"]=$arrProp["max_chans"]+0;
            }
        }

        if($arrProp["dialout_prefix"]!==""){
            if(!preg_match("/^[0-9w\\+#]+$/",$arrProp["dialout_prefix"])){
                $this->errMsg="Invalid value field Outbound Dial Prefix";
                return false;
            }
        }
        
        if(!isset($arrProp["register"])){
            $arrProp["register"]="";
        }

        if($arrProp["tech"]=="dahdi" || $arrProp["tech"]=="custom"){
            $ttt=($arrProp["tech"]=="dahdi")?_tr("DAHDI Identifier"):_tr("Dial String");
            if(empty($arrProp["channelid"])){
                $this->errMsg=_tr("Field $ttt can't be empty");
                return false;
            }
            if($arrProp["tech"]=="dahdi"){
                if(!preg_match("/^(g|r){0,1}[0-9]+$/",$arrProp["channelid"])){
                    $this->errMsg=_tr("Field DAHDI Identifier can't be empty and must be a dahdi number or channel number");
                    return false;
                }
            }
        }else{
            $TYPE=strtoupper($arrProp["tech"]);
            $arrProp["channelid"]=$channelid;
            $arrProp["name"]=$arrProp["channelid"];
            //no debe haber otra truncal de la misma tecnoligia con el mismo channelid
            if($this->updateSipIaxTrunk($arrProp)==false){
                $this->errMsg=_tr("Error when trying created $TYPE Peer. ").$this->errMsg;
                return false;
            }
        }
        
        $query="UPDATE trunk SET name=?,outcid=?,keepcid=?,maxchans=?,dialoutprefix=?,disabled=?,string_register=?,channelid=? where trunkid=?";
        $exito=$this->executeQuery($query,array($arrProp["trunk_name"],$arrProp["outcid"],$arrProp["keepcid"],$arrProp["max_chans"],$arrProp["dialout_prefix"],$arrProp["disabled"],$arrProp["register"],$arrProp["channelid"],$idTrunk));
        
        if($exito==true){
            //si ahi dialpatterns se los procesa
            $resultDelete = $this->deleteDialPatterns($idTrunk);
            if($resultDelete==false)
                return false;
            if($this->createDialPattern($arrDialPattern,$idTrunk)==false){
                $this->errMsg="Trunk can't be updated.".$this->errMsg;
                return false;
            }
        }else{
            $this->errMsg=_tr("Trunk could not be updated.").$this->_DB->errMsg;
            return false;
        }
        
        //guardamos las organizaciones relacionadas con la truncal
        $query="DELETE from trunk_organization where trunkid=?";
        if($this->_DB->genQuery($query,array($idTrunk))==false){
            $this->errMsg .=_tr("Trunk couldn't be updated. ").$this->_DB->errMsg;
            return false;
        }
        
        if($this->trunkOrganization($idTrunk,$arrProp["select_orgs"])==false){
            return false;
        }else{
            if($this->setTrunkASTDB($idTrunk,$arrProp)==false){
                $this->errMsg=_tr("Trunk could not be updated.").$this->errMsg;
                return false;
            }else
                return true;
        }
    }

    private function updateSipIaxTrunk($arrProp){
        $tech=$arrProp["tech"];
        if($tech=="sip"){
            $this->type=new paloSip($this->_DB);
        }elseif($tech=="iax2"){
            $tech="iax";
            $this->type=new paloIax($this->_DB);
        }else{
            $this->errMsg=_tr("Invalid technology");
            return false;
        }
        
        //valido que no exista otro peer con el mismo nombre
        $query="SELECT name from $tech where name=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($arrProp["name"]));
        if($result===false || count($result)==0){
            $this->errMsg=_tr("Peer trunk doesn't exist. ").$this->_DB->errMsg;
            return false;
        }
        
        if(isset($arrProp["secret"]) && $arrProp["secret"]!=""){
            if(!isStrongPassword($arrProp["secret"])){
                $error=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
                return false;
            }
            if($tech=="sip"){
                $arrProp['md5secret']=$this->type->hashMd5Secret($arrProp['name'],$arrProp['secret']);
                $arrProp['secret']=null;
            }
        }else{
            $arrProp["secret"]=null;
        }
        
        //los campos deny y permit no pueden ser vacios en caso que se use tecnologia iax2
        if(!$this->validateIP($arrProp['deny'])){
            $arrProp['deny']="0.0.0.0/0.0.0.0";
        }
        
        if(!$this->validateIP($arrProp['permit'])){
            $arrProp['permit']="0.0.0.0/0.0.0.0";
        }
        
        if(empty($arrProp['host'])){
            $arrProp['host']="dynamic";
        }
        
        $arrQuery=array();
        $arrParam=array();
        foreach($arrProp as $name => $value){
            if(property_exists($this->type,$name)){
                if(isset($value)){
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
        if(count($arrQuery)>0){
            $query ="Update $tech set ".implode(",",$arrQuery);
            $query .=" where name=?";
            $arrParam[]=$arrProp["name"];
            return $this->executeQuery($query,$arrParam);
        }else
            return true;
    }

    private function validateIP($field){
        $values=explode("/",$field);
        if(count($values)>2)
            return false;
        foreach($values as $ip){    
            if(!preg_match("/^([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})$/",$ip, $arrReg)) {
                return false;
            } else {
                if(($arrReg[1]<=255) and ($arrReg[1]>0) and ($arrReg[2]<=255) and ($arrReg[2]>=0) and
                    ($arrReg[3]<=255) and ($arrReg[3]>=0) and ($arrReg[4]<=255) and ($arrReg[4]>=0)) {
                    $return = true;
                } else {
                    return false;
                }
            }
        }
    }

    private function createDialPattern($arrDialPattern,$trunkid)
    {
        $result=true;
        if(is_array($arrDialPattern) && count($arrDialPattern)!=0){
            $temp=$arrDialPattern;
            $seq = 0;
            $query="INSERT INTO trunk_dialpatterns (trunkid,match_pattern_prefix,match_pattern_pass,prepend_digits,seq) values (?,?,?,?,?)";
            foreach($arrDialPattern as $pattern){
                $prepend = $pattern[3]; 
                $prefix = $pattern[1]; 
                $pattern = $pattern[2];
                $seq++;

                if(isset($prepend)){
                    //validamos los campos
                    if(!preg_match("/^[[:digit:]]*$/",$prepend)){
                        $this->errMsg .=_tr("Invalid dial pattern").". Prepend '$prepend'";
                        $result=false;
                        break;
                    }
                }else
                    $prepend="";
                    
                if(isset($prefix)){
                    if(!preg_match("/^([XxZzNn[:digit:]]*(\[[0-9]+\-{1}[0-9]+\])*(\[[0-9]+\])*)+$/",$prefix)){
                        $this->errMsg .=_tr("Invalid dial pattern").". Prefix '$prefix'";
                        $result=false;
                        break;
                    }
                }else
                    $prefix="";

                if(isset($pattern)){
                    if(!preg_match("/^([XxZzNn[:digit:]]*(\[[0-9]+\-{1}[0-9]+\])*(\[[0-9]+\])*\.{0,1})+$/",$pattern)){
                        $this->errMsg .=_tr("Invalid dial pattern").". Match Pattern '$pattern'";
                        $result=false;
                        break;
                    }
                }else
                    $pattern="";

                if($prepend!="" || $prefix!="" || $pattern!="")
                    $result=$this->executeQuery($query,array($trunkid,$prefix,$pattern,$prepend,$seq));
                
                if($result==false)
                break;
            }
        }
        return $result;
    }

    //solo puede borrar la truncal el superadmin
    function deleteTrunk($trunkid){
        if(!preg_match("/^[0-9]+$/",$trunkid)){
            $this->errMsg=_tr("Invalid Trunk. ");
            return false;
        }
                
        $query="SELECT channelid,tech from trunk where trunkid=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($trunkid));
        if($result===false || count($result)==0){
            $this->errMsg=_tr("Trunk doesn't exist. ").$this->_DB->errMsg;
            return false;
        }
        
        $resultDelete = $this->deleteDialPatterns($trunkid);
        //borramos el peer creado para la truncal en caso de que la tecnologia sea iax2 o sip
        if($result["tech"]=="iax2" || $result["tech"]=="sip"){
            $tech=$result["tech"];
            if($result["tech"]=="iax2")
                $tech="iax";
                
            $query="DELETE from $tech where name=?";
            if($this->_DB->genQuery($query,array($result["channelid"]))==false){
                $this->errMsg .=_tr("Peer couldn't be deleted. ").$this->_DB->errMsg;
                return false;
            }
        }
        
        $query="DELETE from trunk_organization where trunkid=?";
        if($this->_DB->genQuery($query,array($trunkid))==false){
            $this->errMsg .=_tr("Trunk can't be deleted. ").$this->_DB->errMsg;
            return false;
        }
        
        $query="DELETE from outbound_route_trunkpriority where trunk_id=?";
        if($this->_DB->genQuery($query,array($trunkid))==false){
            $this->errMsg .=_tr("Trunk can't be deleted. ").$this->_DB->errMsg;
            return false;
        }
        
        if($this->deleteTrunkASTDB($trunkid)==false){
            $this->errMsg =_tr("Trunk can't be deleted. ").$this->errMsg;
            return false;
        }
        
        $query="DELETE from trunk where trunkid=?";
        if(($this->executeQuery($query,array($trunkid)))&&($resultDelete==true)){
            return true;
        }else{
            $this->errMsg=_tr("Trunk can't be deleted. ").$this->errMsg;
            return false;
        }
        
        return true;
    }

    private function deleteDialPatterns($trunkid){
        $queryD="DELETE from trunk_dialpatterns where trunkid=?";
        $result=$this->_DB->genQuery($queryD,array($trunkid));
        if($result==false){
            $this->errMsg=_tr("Error trunk dialpatterns.").$this->_DB->errMsg;
            return false;
        }else
            return true;
    }

    private function deleteTrunkASTDB($id_trunk){
        $errorM="";
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        }else{ //borro las propiedades dentro de la base ASTDB de asterisk
            $result=$astMang->database_delTree("TRUNK/$id_trunk");
            $astMang->disconnect();
        }
        return true;
    }

    function actDesacTrunk($id,$action){
        if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = _tr("Invalid Trunk ID");
            return false;
        }
        
        $query="SELECT channelid,sec_call_time from trunk where trunkid=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($id));
        if($result===false || count($result)==0){
            $this->errMsg=_tr("Trunk doesn't exist. ").$this->_DB->errMsg;
            return false;
        }
        
        $action=($action=="on")?"on":"off";
        
        //cambiamos en la base de datos el parametro disabled
        $query="Update trunk set disabled=? where trunkid=?";
        if($this->_DB->genQuery($query,array($action,$id))==false){
            $this->errMsg .=_tr("Trunk can't be enabled. ").$this->_DB->errMsg;
            return false;
        }
        
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        }else{
            if($action=="off"){ 
                //borro las propiedades dentro de la base ASTDB de asterisk
                if($result["sec_call_time"]=="yes"){
                    $result=$astMang->database_del("TRUNK/$id/COUNT_TIME","BLOCK");
                    $result=$astMang->database_del("TRUNK/$id/COUNT_TIME","NUM_FAIL");
                    $astMang->database_put("TRUNK/$id/COUNT_TIME","COUNT",0);
                }
                $result=$astMang->database_del("TRUNK/$id","OUTDISABLE");
            }else{
                $astMang->database_put("TRUNK/$id","OUTDISABLE","on");
                $astMang->disabled;
            }
        }
        return true;
    }
}
?>