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

    function getNumTrunks($domain=null){
		$where="";
		$arrParam=null;

		if(isset($domain)){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format";
				return false;
			}else{
				$where="JOIN did_details as d where d.keyword=? and d.did in (SELECT did from did where organization_domain=?)";
				$arrParam=array("trunk",$domain);
			}
		}
		
		$query="SELECT count(t.trunkid) from trunk as t $where";
		$result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result==false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result[0];
    }

	
	function getTrunks($domain=null,$limit=null,$offset=null){
		$where=$pagging="";
		$arrParam=null;
		if(isset($domain)){
            if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
                $this->errMsg="Invalid domain format";
                return false;
            }else{
                $where="JOIN did_details as d where d.keyword=? and d.did in (SELECT did from did where organization_domain=?)";
                $arrParam=array("trunk",$domain);
            }
        }
        
        if(isset($limit) && isset($offset)){
            $pagging=" limit ? offset ?";
            $arrParam[]=$limit;
            $arrParam[]=$offset;
        }

		$query="SELECT * from trunk $where $pagging";
                
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
			
			//obtenemos los detalles del peer si la truncal es de tipo sip o iax2
			if($arrTrunk["tech"]=="sip" || $arrTrunk["tech"]=="iax2"){
                $query="SELECT context,name,register,type,username,host,qualify,disallow,allow,amaflags,deny,permit, $queryT from ? where name=?";
                if($arrTrunk["tech"]=="sip"){
                    $queryT="insecure,nat,dtmfmode,fromuser,fromdomain,sendrpid,canreinvite,useragent,videosupport,maxcallbitrate,qualifyfreq,rtptimeout,rtpholdtimeout,rtpkeepalive";
                }else{
                    $queryT="auth,trunk,trunkfreq,trunktimestamps,sendani,adsi,requirecalltoken,encryption,jitterbuffer,forcejitterbuffer,codecpriority,qualifysmoothing,qualifyfreqok,qualifyfreqnotok";
                }
                $result=$this->_DB->getFirstRowQuery($query,true,array($arrTrunk["tech"],$arrTrunk["channelid"]));
                if($result==false){
                    $this->errMsg=_tr("Error getting peer details. ").$this->_DB->errMsg;
                    return false;
                }else{
                    $arrTrunk=array_merge($result,$arrTrunk);
                }
			}
			return $arrTrunk;
		}
    }
	
	function getDefaultConfig($tech){
        $arrTrunk=array();
        $arrTrunk["type"]="friend";
        $arrTrunk["qualify"]="yes";
        $arrTrunk["context"]="from-pstn";
        $arrTrunk["disallow"]="all";
        $arrTrunk["allow"]="ulaw,alaw,gsm";
        $arrTrunk["deny"]="0.0.0.0/0.0.0.0";
        $arrTrunk["permit"]="0.0.0.0/0.0.0.0";
        if($tech=="sip"){
            $arrTrunk["insecure"]="port,invite";
            $arrTrunk["nat"]="auto";
            $arrTrunk["dtmfmode"]="rfc2833";
            $arrTrunk["sendrpid"]="no";
            $arrTrunk["trustrpid"]="no";
            $arrTrunk["canreinvite"]="no";
            $arrTrunk["useragent"]="";
        }elseif($tech=="iax"){
            $arrTrunk["auth"]="plaintext";
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
        if(!preg_match("/^(sip|iax2|dahdi)$/",$tech){
            $this->errMsg="Invalid technology";
        }else{
            if($tech=="sip" || $tech=="sip"){
            }
            if(!isset($name) || $name==""){
            }
        }
        if(!isset($arrProp["channelid"]) || $arrProp["channelid"]==""){
            $this->errMsg="Trunk Name can't be empty";
        }else{
            $channelId=$arrProp["channelid"];
            $val = $this->checkName($arrProp['domain'],$channelId);
                if($val==1)
               $this->errMsg="Trunk Name is already used"; 
            else
            $query .="channelid";
            $arrOpt[count($arrOpt)]=$arrProp["channelid"];
        }
	}
	
	function createNewTrunk($arrProp,$arrDialPattern){
        //definimos el tipo de truncal que vamos a crear
        switch($arrProp["tech"]){
            case "sip":
                $this->tech="sip";
                break;
            case "dahdi":
                $this->tech="dahdi";
                break;
            case "iax2":
                $this->tech="iax2";
                break;
            default:
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
        if(!preg_match("/^(on|off|cnum|all)$/",$arrProp["cid_options"])){
            $this->errMsg="Invalid CID Options";
            return false;
        }
        
        //si existe un maximo numero de canales
        if($arrProp["max_chans"]!=""){
            if(!preg_match("/^[[:digit:]]+$/",$arrProp["max_chans"])){
                $this->errMsg="Invalid value field Maximun Channels";
                return false;
            }
        }

        //oupbound dial prefix
        if($arrProp["dialout_prefix"]!=""){
            if(!preg_match("/^[[:digit:]]+$/",$arrProp["dialout_prefix"])){
                $this->errMsg="Invalid value field Outbound Prefix";
                return false;
            }
        }

        if($arrProp["tech"]=="dahdi"){
            //no debe haber otra truncal de la misma tecnoligia con el mismo channelid
            if($this->existTrunk()=="true"){
                $this->errMsg=_tr("Already Exist another DAHDI trunk with the same DAHDI Identifier. ").$this->errMsg;
                return false;
            }
        }elseif($arrProp["tech"]=="sip"){
            //no debe haber otra truncal de la misma tecnoligia con el mismo channelid
            if($this->existTrunk()=="true"){
                $this->errMsg=_tr("Already exist another SIP trunk with Peer Name. ").$this->errMsg;
                return false;
            }
            if($this->createSipIaxTrunk($arrProp)==false){
                $this->errMsg=_tr("Error when trying created sip Peer. ").$this->errMsg;
                return false;
            }
        }else{
            //no debe haber otra truncal de la misma tecnoligia con el mismo channelid
            if($this->existTrunk()=="true"){
                $this->errMsg=_tr("Already exist another IAX2 trunk with Peer Name. ").$this->errMsg;
                return false;
            }
            if($this->createSipIaxTrunk($arrProp)==false){
                $this->errMsg=_tr("Error when trying created IAX2 Peer. ").$this->errMsg;
                return false;
            }
        }
        
        
        $query="INSERT INTO trunk (name,tech,outcid,keepcid,maxchans,dialoutprefix,channelid,disabled) values (?,?,?,?,?,?,?,?)";
        $arrOpt=array($type);
        
        $query .=")";
        $qmarks = "(";
        for($i=0;$i<count($arrOpt);$i++){
            $qmarks .="?,"; 
        }
        $qmarks=substr($qmarks,0,-1).")"; 
        $query = $query."values".$qmarks;
        if($this->errMsg==""){
            if($this->type=="sip"){
                $exito=$this->createSipIaxTrunk($channelId,$query,$arrOpt,$arrProp);
            }elseif($this->type=="iax2"){
                $exito=$this->createSipIaxTrunk($channelId,$query,$arrOpt,$arrProp);
            }elseif($this->type=="dahdi"){
                $exito=$this->createDahdiTrunk($channelId,$query,$arrOpt,$arrProp);
                                     
            }
        }else{
            return false;
        }

        //return true;
        
        if($exito==true){
            //si ahi dialpatterns se los procesa
            $result=$this->getFirstResultQuery("SELECT LAST_INSERT_ID()",NULL);
            $trunkid=$result[0];
            if($this->createDialPattern($arrDialPattern,$trunkid)==false){
                $this->errMsg="Trunk can't be created .".$this->errMsg;
                return false;
            }else
                return true;
        }else
            return false;

    }
    
    function createSipIaxTrunk($arrProp){
        
    }
}

/*class paloTrunkPBX extends paloAsteriskDB{
    public $type;

    function paloTrunkPBX($domain,&$pDB2){
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
        }else{
            $this->domain=$domain;

            parent::__construct($pDB2);

            $result=$this->getCodeByDomain($domain);
            if($result==false){
                $this->errMsg .=_tr("Can't create a new instace of paloTrunk").$this->errMsg;
            }else{
                $this->code=$result["code"];
            }
        }
    }

    function createNewTrunk($arrProp,$type,$arrDialPattern){
        $query="INSERT INTO trunk (tech,";
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
            default:
                $this->errMsg="Invalid type of trunk";
                return false;
        }

        
        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["name"]) || $arrProp["name"]==""){
            $this->errMsg="Name of trunk can't be empty";
        }else{
            $query .="name,";
            $arrOpt[1]=$arrProp["name"];
        }

        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["domain"]) || $arrProp["domain"]==""){
            $this->errMsg="It's necesary you create a new organization so you can create a Trunk to this organization";
        }else{
            $query .="organization_domain,";
            $arrOpt[count($arrOpt)]=$arrProp["domain"];
        }

        //si se define un callerid de salida
        if(isset($arrProp["outcid"])){
            $query .="outcid,";
            $arrOpt[count($arrOpt)]=$arrProp["outcid"];
        }
      
        //si se define un callerid de salida
        if(isset($arrProp["failtrunk"])){
            $query .="failscript,";
            $arrOpt[count($arrOpt)]=$arrProp["failtrunk"];
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
            $query .="dialoutprefix,";
            $arrOpt[count($arrOpt)]=$arrProp["dialout_prefix"];
        }

        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["channelid"]) || $arrProp["channelid"]==""){
            $this->errMsg="Trunk Name can't be empty";
        }else{
            $channelId=$arrProp["channelid"];
            $val = $this->checkName($arrProp['domain'],$channelId);
                if($val==1)
               $this->errMsg="Trunk Name is already used"; 
            else
            $query .="channelid";
            $arrOpt[count($arrOpt)]=$arrProp["channelid"];
        }
        
        $query .=")";
        $qmarks = "(";
        for($i=0;$i<count($arrOpt);$i++){
            $qmarks .="?,"; 
        }
        $qmarks=substr($qmarks,0,-1).")"; 
        $query = $query."values".$qmarks;
        if($this->errMsg==""){
            if($this->type=="sip"){
                $exito=$this->createSipIaxTrunk($channelId,$query,$arrOpt,$arrProp);
            }elseif($this->type=="iax2"){
                $exito=$this->createSipIaxTrunk($channelId,$query,$arrOpt,$arrProp);
            }elseif($this->type=="dahdi"){
                $exito=$this->createDahdiTrunk($channelId,$query,$arrOpt,$arrProp);
                                     
            }
        }else{
            return false;
        }

        //return true;
        
        if($exito==true){
            //si ahi dialpatterns se los procesa
            $result=$this->getFirstResultQuery("SELECT LAST_INSERT_ID()",NULL);
            $trunkid=$result[0];
            if($this->createDialPattern($arrDialPattern,$trunkid)==false){
                $this->errMsg="Trunk can't be created .".$this->errMsg;
                return false;
            }else
                return true;
        }else
            return false;

    }

    function updateTrunkPBX($arrProp,$arrDialPattern,$idTrunk){
        $query="UPDATE trunk SET ";
        $arrOpt=array();

    
        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["name"]) || $arrProp["name"]==""){
            $this->errMsg="Name of trunk can't be empty";
        }else{
            $query .="name=?,";
            $arrOpt[0]=$arrProp["name"];
        }

        //si se define un callerid de salida
        if(isset($arrProp["outcid"])){
            $query .="outcid=?,";
            $arrOpt[count($arrOpt)]=$arrProp["outcid"];
        }
      
        //si se define un callerid de salida
        if(isset($arrProp["failtrunk"])){
            $query .="failscript=?,";
            $arrOpt[count($arrOpt)]=$arrProp["failtrunk"];
        }

        //caller id options
        if($arrProp["cid_options"]!="on" && $arrProp["cid_options"]!="off" && $arrProp["cid_options"]!="cnum" && $arrProp["cid_options"]!="all"){
            $this->errMsg="Invalid CID Options";
        }else{
            $query .="keepcid=?,";
            $arrOpt[count($arrOpt)]=$arrProp["cid_options"];
        }
        
        //si existe un maximo numero de canales
        if(!preg_match("/^[[:digit:]]+$/",$arrProp["max_chans"])){
            $this->errMsg="Invalid value field Maximun Channels";
        }else{
            $query .="maxchans=?,";
            $arrOpt[count($arrOpt)]=$arrProp["max_chans"];
        }

        //si esta o no deshabilitada la truncal
        if(isset($arrProp["disabled"])){
            $query .="disabled=?,";
            $arrOpt[count($arrOpt)]=$arrProp["disabled"];
        }
        

        //oupbound dial prefix
        if(!preg_match("/^[[:digit:]]+$/",$arrProp["dialout_prefix"])){
            $this->errMsg="Invalid value field Outbound Prefix";
        }else{
            $query .="dialoutprefix=?,";
            $arrOpt[count($arrOpt)]=$arrProp["dialout_prefix"];
        }

        //-------otros campos----------    
          
        //debe haberse seteado un nombre para la truncal
        if(!isset($arrProp["channelid"]) || $arrProp["channelid"]==""){
            $this->errMsg="Trunk Name can't be empty";
        }else{
            $channelId=$arrProp["channelid"];
            $val = $this->checkName($arrProp['domain'],$channelId,$idTrunk);
                if($val==1)
               $this->errMsg="Trunk Name is already used"; 
            else
            $query .="channelid=?";
            $arrOpt[count($arrOpt)]=$arrProp["channelid"];
        }
        
        
        //$qmarks = "(";
/*      for($i=0;$i<count($arrOpt);$i++){
            $qmarks .="?,"; 
        }*/
        //$qmarks=substr($qmarks,0,-1).")"; 
/*        $query = $query." WHERE trunkid=?";
            $arrOpt[count($arrOpt)]=$idTrunk;
        
        if($this->errMsg==""){
                $exito=$this->updateTrunk($channelId,$query,$arrProp,$arrOpt);
        }else{
            return false;
        }

        //return true;
        
        if($exito==true){
            //si ahi dialpatterns se los procesa
            $resultDelete = $this->deleteDialPatterns($idTrunk);
                        if(($resultDelete==false)||($this->createDialPattern($arrDialPattern,$idTrunk)==false)){
                $this->errMsg="Trunk can't be updated.".$this->errMsg;
                return false;
            }else
                return true;
        }else
            return false;

    }

    function deleteTrunk($trunkid){
        $resultDelete = $this->deleteDialPatterns($trunkid);
        
        $query="DELETE from trunk where trunkid=?";
        if(($this->executeQuery($query,array($trunkid)))&&($resultDelete==true)){
            return true;
        }else{
            $this->errMsg="Trunk can't be deleted.".$this->errMsg;
            return false;
        }
                     
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
      
    private function createDialPattern($arrDialPattern,$trunkid)
    {
        $result=true;

        if(is_array($arrDialPattern) && count($arrDialPattern)!=0){
            $temp=$arrDialPattern;
            $seq = 0;
            $query="INSERT INTO trunk_dialpatterns (trunkid,match_pattern_prefix,match_pattern_pass,prepend_digits,seq) values (?,?,?,?,?)";
            foreach($arrDialPattern as $pattern){
                  $prepend = getParameter("prepend_digit".$pattern);
                  $prefix = getParameter("pattern_prefix".$pattern);
                  $pattern = getParameter("pattern_pass".$pattern);
                  $seq++;

                  if(isset($prepend)){
                    //validamos los campos
                    if(!preg_match("/^[[:digit:]]*$/",$prepend)){
                        $this->errMsg="Invalid dial pattern";
                        $result=false;
                        break;
                    }
                  }else
                    $prepend="";
                  
                  if(isset($prefix)){
                    if(!preg_match("/^([XxZzNn[:digit:]]*(\[[0-9]+\-{1}[0-9]+\])*(\[[0-9]+\])*)+$/",$prefix)){
                        $this->errMsg="Invalid dial pattern";
                        $result=false;
                        break;
                    }
                  }else
                    $prefix="";

                  if(isset($pattern)){
                    if(!preg_match("/^([XxZzNn[:digit:]]*(\[[0-9]+\-{1}[0-9]+\])*(\[[0-9]+\])*)+$/",$pattern)){
                        $this->errMsg="Invalid dial pattern";
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

    function checkName($domain,$channelId, $id_trunk=null){
          $where="";
          if(!isset($id_trunk))
              $id_trunk = "";
          
                  $arrParam=null;
          if(isset($domain)){
              if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
                  $this->errMsg="Invalid domain format";
                  return false;
              }else{
                  $where="where organization_domain=? AND trunkid<>? AND channelid=? ";
                  $arrParam=array($domain,$id_trunk,$channelId);
              }
          }
          
          $query="SELECT channelid from trunk $where";
          
          $result=$this->_DB->fetchTable($query,true,$arrParam);
          if($result===false){
              $this->errMsg=$this->_DB->errMsg;
              return false;
          }else{
             if ($result==null)
                 return 0;
             else
                 return 1;
            }
    }

    private function createDahdiTrunk($channelId,$query,$arrOpt,$arrProp)
    {                                
        //setear el identificador apropiadamente (channel id)
        //campo group dentro de chan_dahdi.conf group=numCode(numeroGrupo)
        //numero de grupo 2 digitos maximos al principio seteado 00
        if(!preg_match("/^[g|r]{0,1}[[:digit:]]{1,2}$/",$channelId)){
            $this->errMsg="Invalid DAHDI Identifier";
            return false;
        }

        if(!preg_match("/^organization[[:digit:]]{3}$/",$this->code)){
            $this->errMsg="Invalid code organization";
            return false;
        }

        $numCode=strpos($this->code,13);
        if(substr($channelId,0,1)=="r" || substr($channelId,0,1)=="g"){
            $channelId=substr($channelId,0,1).$numCode.substr($channelId,1);
        }else{
            $channelId=$numCode.$channelId;
        }

        $result=$this->executeQuery($query,$arrOpt);
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

    private function createSipIaxTrunk($channelId,$query,$arrOpt,$arrProp){
        if(!isset($arrProp["name"]) || $arrProp["name"]==""){
            $this->errMsg="Trunk can't be created. Trunk Name can't be empty";
            return false;
        }
        $result=$this->executeQuery($query,$arrOpt);
        
        
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 

        //validamos que no existe otros peers con el mismo nombre de truncal
        
    }

    private function updateTrunk($channelId,$query,$arrProp,$arrOpt){
        if(!isset($arrProp["name"]) || $arrProp["name"]==""){
            $this->errMsg="Trunk can't be created. Trunk Name can't be empty";
            return false;
        }
        $result=$this->executeQuery($query,$arrOpt);
        
        
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 

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
    }*/
?>
