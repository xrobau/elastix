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
class paloSantoOutbound extends paloAsteriskDB{
    var $_DB; //conexion base de mysql elxpbx
    var $errMsg;
    protected $code;
    protected $domain;

    function paloSantoOutbound(&$pDB,$domain)
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

    function getNumOutbound($domain=null){
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
		$query="SELECT count(id) from outbound_route $where";
		$result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result==false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result[0];
    }

	
	function getOutbounds($domain=null){
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

		$query="SELECT * from outbound_route $where";
                
		$result=$this->_DB->fetchTable($query,true,$arrParam);
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result;
         }


	 function getTrunks($domain=null){
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

		$query="SELECT trunkid, name, tech  from trunk $where";
              
		$result=$this->_DB->fetchTable($query,true,$arrParam);
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else{
			foreach($result as $value){
				$arrTrunk[$value['trunkid']]=$value['name']."/".strtoupper($value['tech']);
			}
			return $arrTrunk;
		}
			
	
         }

	  function getAllTrunks($domain=null){
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

		$query="SELECT t.trunkid, t.name, t.tech  from trunk t $where";
              
		$result=$this->_DB->fetchTable($query,false,$arrParam);
		

		
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else{
		      foreach($result as $value){
			  $arrTrunk[$value[0]]=$value[1]."/".strtoupper($value[2]);
		      }   
		      return $arrTrunk;
		}
         }

	
	 function getTrunkById($id,$domain=null){
		global $arrConf;
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
		
		$query="SELECT t.trunkid, t.name, t.tech  from trunk t where trunkid=? $where";
		$result=$this->_DB->getFirstRowQuery($query,true,$param);

		if($result===false){
		   $this->errMsg=$this->_DB->errMsg;
		   return false;
		}else{
		  // $arrTrunk[$result["trunkid"]]=$result["name"]."/".strtoupper($result["tech"]);
		   return $result;
		}
		    
  
          }    


	 
	function checkName($domain=null){
		  $where="";
		  if(getParameter("id_trunk"))
		    $id_ivr = getParameter("id_trunk");
		  else
		    $id_ivr = "";
		  $displayname = getParameter("channelid");
		  $arrParam=null;
		  if(isset($domain)){
			  if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				  $this->errMsg="Invalid domain format";
				  return false;
			  }else{
				  $where="where organization_domain=? AND trunkid<>? AND channelid=? ";
				  $arrParam=array($domain,$id_ivr,$displayname);
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

		
        function getArrDestine($idOutbound){
	      $query="SELECT * from outbound_route_dialpattern WHERE outbound_route_id=? order by seq";
              $result=$this->_DB->fetchTable($query,false,array($idOutbound));
	
	      if($result==false)
		 $this->errMsg=$this->errMsg;

              return $result; 

	}

	function getArrTrunkPriority($idOutbound){
	      $query="SELECT t.trunkid,t.name,t.tech from outbound_route_trunkpriority o, trunk t WHERE t.trunkid=o.trunk_id AND o.outbound_route_id=? order by o.seq";
              $result=$this->_DB->fetchTable($query,false,array($idOutbound));
	      $arrTrunk = array();
	      if($result==false)
		 $this->errMsg=$this->errMsg;


		foreach($result as $value){
		    $arrTrunk[$value[0]]=$value[1]."/".strtoupper($value[2]);
		}
              return $arrTrunk; 

	}
	
        //debo devolver un arreglo que contengan los parametros del Trunk
	function getOutboundById($id,$domain=null){
		global $arrConf;
		$arrOutbound=array();
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

		$query="SELECT routename,outcid,outcid_mode,routepass,mohsilence,time_group_id,organization_domain ";
                $query.="from outbound_route where id=? $where";
		$result=$this->_DB->getFirstRowQuery($query,true,$param);
                if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}elseif(count($result)>0){
			$arrOutbound["routename"]=$result["routename"];
			$arrOutbound["outcid"]=$result["outcid"];
			$arrOutbound["outcid_mode"]=$result["outcid_mode"];
			$arrOutbound["routepass"]=$result["routepass"];
			$arrOutbound["mohsilence"]=$result["mohsilence"];
			$arrOutbound["time_group_id"]=$result["time_group_id"];
			$arrOutbound["domain"]=$result["organization_domain"];   			
			return $arrOutbound;
		}
		
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
		$pGPBX = new paloGlobalsPBX($this->_DB,$domain);
		$arrExtension["ring_timer"]=$pGPBX->getGlobalVar("RINGTIMER");
        $arrExtension["language"]=$pGPBX->getGlobalVar("LANGUAGE");
		
		return $arrExtension;
	}
	
	function getVMdefault($domain){
        $arrVM=array();
        $queryV="SELECT attach,context,deletevoicemail,saycid,envelope from voicemail_general where organization_domain=?";
        $resultV=$this->_DB->getFirstRowQuery($queryV,true,array($domain));
        if($resultV==false){
            $this->errMsg .=_tr("Error getting voicemail default settings").$this->_DB->errMsg;
        }else{
            $arrVM["vmcontext"]=isset($resultV["context"])?$resultV["context"]:null;
            $arrVM["vmattach"]=isset($resultV["attach"])?$resultV["attach"]:null;
            $arrVM["vmdelete"]=isset($resultV["deletevoicemail"])?$resultV["deletevoicemail"]:null;
            $arrVM["vmsaycid"]=isset($resultV["saycid"])?$resultV["saycid"]:null;
            $arrVM["vmenvelope"]=isset($resultV["envelope"])?$resultV["envelope"]:null;
        }
        return $arrVM;
	}
}
?>
