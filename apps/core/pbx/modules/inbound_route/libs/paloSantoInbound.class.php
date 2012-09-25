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
    include_once "/var/www/html/libs/paloSantoACL.class.php";
    include_once "/var/www/html/libs/paloSantoAsteriskConfig.class.php";
    include_once "/var/www/html/libs/paloSantoPBX.class.php";
	global $arrConf;
	
class paloSantoInbound extends paloAsteriskDB{
    public $_DB; //conexion base de mysql elxpbx
    public $errMsg;
    protected $code;
    protected $domain;

    function paloSantoInbound(&$pDB,$domain)
    {
       parent::__construct($pDB);
        
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
        }else{
            $this->domain=$domain;

            $result=$this->getCodeByDomain($domain);
            if($result==false){
                $this->errMsg .=_tr("Can't create a new instace of paloQueuePBX").$this->errMsg;
            }else{
                $this->code=$result["code"];
            }
        }
    }

    function getNumInbound($domain=null){
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
		$query="SELECT count(id) from inbound_route $where";
		$result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result==false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result[0];
    }

	
	function getInbounds($domain=null){
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

		$query="SELECT * from inbound_route $where";
                
		$result=$this->_DB->fetchTable($query,true,$arrParam);
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result;
    }

    //debo devolver un arreglo que contengan los parametros del Inbound
	function getInboundById($id){
		global $arrConf;
		$arrInbound=array();
		$where="";
		if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = "Extension ID must be numeric";
		    return false;
        }

		$param=array($id);
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $this->domain)){
            $this->errMsg="Invalid domain format";
            return false;
        }else{
            $where=" and organization_domain=?";
            $param[]=$this->domain;
        }

		$query="SELECT * from inbound_route where id=? $where";
		$result=$this->_DB->getFirstRowQuery($query,true,$param);
		
                if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}elseif(count($result)>0){
			return $result;
		}else
		      return false;
    }
    
    function createNewInbound($arrProp){
        $query="INSERT INTO inbound_route (";
        $arrOpt=array();

        //debe haberse seteado un nombre
        if(!isset($arrProp["description"]) || $arrProp["description"]==""){
            $this->errMsg="Description of inbound can't be empty";
            return false;
        }else{
            $val = $this->checkName($arrProp['domain'],$arrProp['description']);
            if($val==1){
               $this->errMsg="Description Name is already used by another Inbound Route"; 
               return false;
            }else{
               $query .="description,";
               $arrOpt[0]=$arrProp["description"];
            }
        }

        //si se define un callerid 
        if(isset($arrProp["did_number"])){
            $query .="did_number,";
            $arrOpt[count($arrOpt)]=$arrProp["did_number"];
        }

        if(isset($arrProp["cid_number"])){
            $query .="cid_number,";
            $arrOpt[count($arrOpt)]=$arrProp["cid_number"];
        }
        
        if(isset($arrProp["alertinfo"])){
            $query .="alertinfo,";
            $arrOpt[count($arrOpt)]=$arrProp["alertinfo"];
        }

        if(isset($arrProp["cid_prefix"])){
            $query .="cid_prefix,";
            $arrOpt[count($arrOpt)]=$arrProp["cid_prefix"];
        }
        
        if(isset($arrProp["moh"])){
            $query .="moh,";
            $arrOpt[count($arrOpt)]=$arrProp["moh"];
        }

        if(isset($arrProp["ring"])){
            $query .="ringing,";
            $arrOpt[count($arrOpt)]=$arrProp["ring"];
        }

        if(isset($arrProp["delay_answer"])){
            $query .="delay_answer,";
            $arrOpt[count($arrOpt)]=$arrProp["delay_answer"];
        }

        if(isset($arrProp["primanager"])){
            $query .="primanager,";
            $arrOpt[count($arrOpt)]=$arrProp["primanager"];
        }

        if(isset($arrProp["max_attempt"])){
            $query .="max_attempt,";
            $arrOpt[count($arrOpt)]=$arrProp["max_attempt"];
        }

        if(isset($arrProp["min_length"])){
            $query .="min_length,";
            $arrOpt[count($arrOpt)]=$arrProp["min_length"];
        }
  
        if(isset($arrProp["cid_lookup"])){
            $query .="cid_lookup,";
            $arrOpt[count($arrOpt)]=$arrProp["cid_lookup"];
        }

        if(isset($arrProp["language"])){
            $query .="language,";
            $arrOpt[count($arrOpt)]=$arrProp["language"];
        }

        if(isset($arrProp["goto"])){
            $query .="goto,";
            $arrOpt[count($arrOpt)]=$arrProp["goto"];
        }

        if(isset($arrProp["destination"])){
            if($this->validateDestine($this->domain,$arrProp["destination"])!=false){
                $query .="destination,";
                $arrOpt[count($arrOpt)]=$arrProp["destination"];
            }else{
                $this->errMsg="Invalid destination";
                return false;
            }
        }
        
        if(!isset($arrProp["domain"]) || $arrProp["domain"]==""){
            $this->errMsg="Invalid organization";
            return false;
        }else{
            $query .="organization_domain";
            $arrOpt[count($arrOpt)]=$arrProp["domain"];
        }

        $query .=")";
        $qmarks = "(";
        for($i=0;$i<count($arrOpt);$i++){
            $qmarks .="?,"; 
        }
        $qmarks=substr($qmarks,0,-1).")"; 
        $query = $query." values".$qmarks;
        $result=$this->createInbound($query,$arrOpt,$arrProp);
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 
    }

    private function createInbound($query,$arrOpt,$arrProp){
        $result=$this->executeQuery($query,$arrOpt);
                
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 
    }

    function updateInboundPBX($arrProp,$idInbound){
        $query="UPDATE inbound_route SET ";
        $arrOpt=array();

        if(!isset($arrProp["description"]) || $arrProp["description"]==""){
            $this->errMsg="Name of inbound can't be empty";
            return false;
        }else{
            $val = $this->checkName($arrProp['domain'],$arrProp['description'],$idInbound);
            if($val==1){
                $this->errMsg="Route Name is already used";
                return false;
            }else{
                $query .="description=?,";
                $arrOpt[0]=$arrProp["description"];
            }
        }

        //si se define un callerid 
        if(isset($arrProp["did_number"])){
            $query .="did_number=?,";
            $arrOpt[count($arrOpt)]=$arrProp["did_number"];
        }
      
        if(isset($arrProp["cid_number"])){
            $query .="cid_number=?,";
            $arrOpt[count($arrOpt)]=$arrProp["cid_number"];
        }
      
        //si se define un password
        if(isset($arrProp["alertinfo"])){
            $query .="alertinfo=?,";
            $arrOpt[count($arrOpt)]=$arrProp["alertinfo"];
        }

        
        if(isset($arrProp["cid_prefix"])){
            $query .="cid_prefix=?,";
            $arrOpt[count($arrOpt)]=$arrProp["cid_prefix"];
        }

        if(isset($arrProp["moh"])){
            $query .="moh=?,";
            $arrOpt[count($arrOpt)]=$arrProp["moh"];
        }

        if(isset($arrProp["delay_answer"])){
            $query .="delay_answer=?,";
            $arrOpt[count($arrOpt)]=$arrProp["delay_answer"];
        }

        if(isset($arrProp["max_attempt"])){
            $query .="max_attempt=?,";
            $arrOpt[count($arrOpt)]=$arrProp["max_attempt"];
        }

        if(isset($arrProp["min_length"])){
            $query .="min_length=?,";
            $arrOpt[count($arrOpt)]=$arrProp["min_length"];
        }

        if(isset($arrProp["language"])){
            $query .="language=?,";
            $arrOpt[count($arrOpt)]=$arrProp["language"];
        }

        if(isset($arrProp["goto"])){
            $query .="goto=?,";
            $arrOpt[count($arrOpt)]=$arrProp["goto"];
        }

        if(isset($arrProp["destination"])){
            if($this->validateDestine($this->domain,$arrProp["destination"])!=false){
                $query .="destination=?,";
                $arrOpt[count($arrOpt)]=$arrProp["destination"];
            }else{
                $this->errMsg="Invalid destination";
                return false;
            }
        }
        
        if(isset($arrProp["ring"])){
            $query .="ringing=?,";
            $arrOpt[count($arrOpt)]=$arrProp["ring"];
        }
        
        if(isset($arrProp["pri_manager"])){
            $query .="primanager=?,";
            $arrOpt[count($arrOpt)]=$arrProp["pri_manager"];
        }
        if(!isset($arrProp["domain"]) || $arrProp["domain"]==""){
            $this->errMsg="Invalid Oraganization";
            return false;
        }else{
            $query .="organization_domain=?";
            $arrOpt[count($arrOpt)]=$arrProp["domain"];
        }
        //caller id options
                
        $query = $query." WHERE id=?";
        $arrOpt[count($arrOpt)]=$idInbound;
        
        $exito=$this->updateInbound($query,$arrOpt,$arrProp);
        if($exito==false)
            $this->errMsg=$this->errMsg;
        return $exito; 
    }

    private function updateInbound($query,$arrOpt,$arrProp){
        $result=$this->executeQuery($query,$arrOpt);
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 
    }


    function checkName($domain,$description,$id_inbound=null){
          $where="";
          if(!isset($id_inbound))
              $id_inbound = "";
          
            $arrParam=null;
          if(isset($domain)){
              if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
                  $this->errMsg="Invalid domain format";
                  return false;
              }else{
                  $where="where organization_domain=? AND id<>? AND description=? ";
                  $arrParam=array($domain,$id_inbound,$description);
              }
          }
          
          $query="SELECT description from inbound_route $where";
          
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

    function deleteInbound($inboundId){
        
          $query="DELETE from inbound_route where id=?";
          if($this->executeQuery($query,array($inboundId))){
              return true;
          }else{
              $this->errMsg="Inbound can't be deleted.".$this->errMsg;
              return false;
          } 
    }
    
    function createDialplanIndbound(&$arrFromInt){
        if(is_null($this->code) || is_null($this->domain))
            return false;
            
        $arrExt=array();
        $arrIn=$this->getInbounds($this->domain);
        if($arrIn===false){
            $this->errMsg=_tr("Error creating dialplan for inbound routes. ").$this->_DB->errMsg; 
            return false;
        }else{
            foreach($arrIn as $value){
                $exten=$value["did_number"];
                $cid=$value["cid_number"];
               
                //debe tener un destino final la ruta y este debe ser valido
                if(isset($value["destination"])){
                    $goto=$this->getGotoDestine($this->domain,$value["destination"]);
                    if($goto==false)
                        continue;
                }else
                    continue;
                
                $cidroute = false;
                if($cid!="" &&  $exten==""){
                    $exten="_.";
                    $context="1";
                    $cidroute = true;
                }elseif(($cid != '' && $exten != '') || ($cid == '' && $exten == '')){
                    $context="1";
                }else{
                    $context="2";
                }
                                
                $exten = (($exten == "")?"s":$exten);
                $exten=($cid=="")?$exten:$exten."/".$cid;
                
                if ($cidroute) {
                     $arrExt[$context][]=new paloExtensions($exten, new ext_setvar('__FROM_DID','${EXTEN}'),"1");
                     $arrExt[$context][]=new paloExtensions($exten, new ext_goto('1','s'));
                     $exten = "s/$cid";
                     $arrExt[$context][]=new paloExtensions($exten, new ext_execif('$["${FROM_DID}" = ""]','Set','__FROM_DID=${EXTEN}'));
                } else {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setvar('__FROM_DID','${EXTEN}'),"1");
                }
                
                // always set callerID name
                $arrExt[$context][]=new paloExtensions($exten, new ext_execif('$[ "${CALLERID(name)}" = "" ] ','Set','CALLERID(name)=${CALLERID(num)}'));

                if (!empty($value['moh']) && trim($value['moh']) != 'default') {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setmusiconhold($value['moh']));
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setvar('__MOHCLASS',$value['moh']));
                }
                
                // If we require RINGING, signal it as soon as we enter.
                if ($value['ringnig'] === "on") {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_ringing(''));
                }
                if ($value['delay_answer']) {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_wait($value['delay_answer']));
                }
                
                if ($value['primanager'] == "1") {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_macro($this->code.'-privacy-mgr',$value['max_attempt'].','.$value['min_length']));
                } else {
                    // if privacymanager is used, this is not necessary as it will not let blocked/anonymous calls through
                    // otherwise, we need to save the caller presence to set it properly if we forward the call back out the pbx
                    // note - the indirect table could go away as of 1.4.20 where it is fixed so that SetCallerPres can take
                    // the raw format.
                    //
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setvar('__CALLINGPRES_SV','${CALLERPRES()}'));
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setcallerpres('allowed_not_screened'));
                }
                
                if (!empty($value['alertinfo'])) {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setvar("__ALERT_INFO", str_replace(';', '\;', $value['alertinfo'])));
                }
                
                if (!empty($value['cid_prefix'])) {
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setvar('_RGPREFIX', $value['cid_prefix']));
                    $arrExt[$context][]=new paloExtensions($exten, new ext_setvar('CALLERID(name)','${RGPREFIX}${CALLERID(name)}'));
                }
                
                $arrExt[$context][]=new paloExtensions($exten, new extension("Goto(".$goto.")"));
            }
            
            $arrContext=array();
            //creamos el contexto "ext-did-0001" y "ext-did-0002"
            foreach($arrExt as $key => $value){
                $context=new paloContexto($this->code,"ext-did-000".$key);
                if($context===false){
                    $context->errMsg="ext-did-000".$key." Error: ".$context->errMsg;
                }else{
                    $context->arrExtensions=$value;
                    $arrContext[]=$context;
                }
            }
            
            //creamos el context ext-did
            $context=new paloContexto($this->code,"ext-did");
            if($context===false){
                $context->errMsg="ext-did. Error: ".$context->errMsg;
            }else{
                $context->arrExtensions=array(new paloExtensions('foo',new ext_noop('bar'),"1"));
                $context->arrInclude=array("ext-did-0001",'ext-did-0002');
                $arrFromInt[]="ext-did";
                $arrContext[]=$context;
            }
            return $arrContext;
        }
    }
}
?>
