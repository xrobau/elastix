<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 3.0.0                                                |
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
  | Some functions within this class or script that implements an	     | 	
  | asterisk dialplan are based in FreePBX code.			             |
  | FreePBX® is a Registered Trademark of Schmooze Com, Inc.   		     |
  | http://www.freepbx.org - http://www.schmoozecom.com 		         |
  +----------------------------------------------------------------------+
  $Id: paloSantoAnnouncement.class.php,v 1.1 2014-03-12 Bruno Macias bmacias@elastix.org Exp $ */

class paloSantoAnnouncement extends paloAsteriskDB{
    protected $code;
    protected $domain;

    function paloSantoAnnouncement(&$pDB,$domain)
    {
       parent::__construct($pDB);
        
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
        }else{
            $this->domain=$domain;

            $result=$this->getCodeByDomain($domain);
            if($result==false){
                $this->errMsg .=_tr("Can't create a new instace of paloSantoAnnouncement").$this->errMsg;
            }else{
                $this->code=$result["code"];
            }
        }
    }

    function setDomain($domain){
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
        }else{
            $this->domain=$domain;
            $result=$this->getCodeByDomain($domain);
            if($result==false){
                $this->errMsg .=_tr("Can't create a new instace of paloSantoAnnouncement").$this->errMsg;
            }else{
                $this->code=$result["code"];
            }
        }
    }
    
    function getDomain(){
        return $this->domain;
    }
    
    function validateDomainPBX(){
        if(is_null($this->code) || is_null($this->domain))
            return false;
        return true;
    }
    
    function getNumAnnouncement($domain=null,$announcement_name=null){
        $where=array();
        $arrParam=null;

        $query="SELECT count(id) from announcement";
        if(isset($domain) && $domain!='all'){
            $where[]=" organization_domain=?";
            $arrParam[]=$domain;
        }
        if(isset($announcement_name) && $announcement_name!=''){
            $where[]=" UPPER(description) like ?";
            $arrParam[]="%".strtoupper($announcement_name)."%";
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

    
    function getAnnouncement($domain=null,$announcement_name=null,$limit=null,$offset=null){
        $where=array();
        $arrParam=null;

        $query="SELECT *, (SELECT name from recordings where uniqueid=recording_id) recording_name from announcement";
        if(isset($domain) && $domain!='all'){
            $where[]=" organization_domain=?";
            $arrParam[]=$domain;
        }
        if(isset($announcement_name) && $announcement_name!=''){
            $where[]=" UPPER(description) like ?";
            $arrParam[]="%".strtoupper($announcement_name)."%";
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

    function getAnnouncementById($id){
        if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = _tr("Invalid Announcement ID");
            return false;
        }

        $query="SELECT * from announcement where id=? and organization_domain=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($id,$this->domain));
        
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }elseif(count($result)>0){
            return $result;
        }else
              return false;
    }
    
    function createNewAnnouncement($arrProp){
        if(!$this->validateDomainPBX()){
            $this->errMsg=_tr("Invalid Organization");
            return false;
        }
    
        $query="INSERT INTO announcement (";
        $arrOpt=array();
        
        $query .="organization_domain,";
        $arrOpt[count($arrOpt)]=$this->domain;

        //debe haberse seteado description
        if(!isset($arrProp["description"]) || $arrProp["description"]==""){
            $this->errMsg=_tr("Field 'Description' can't be empty");
            return false;
        }else{
            $query .="description,";
            $arrOpt[count($arrOpt)]=$arrProp["description"];
        }
        
        if(isset($arrProp["allow_skip"])){
            $query .="allow_skip,";
            $arrOpt[count($arrOpt)]=$arrProp["allow_skip"];
        }

        if(isset($arrProp["return_ivr"])){
            $query .="return_ivr,";
            $arrOpt[count($arrOpt)]=$arrProp["return_ivr"];
        }
        
        if(isset($arrProp["noanswer"])){
            $query .="noanswer,";
            $arrOpt[count($arrOpt)]=$arrProp["noanswer"];
        }
        
        if(isset($arrProp["repeat_msg"])){
            $query .="repeat_msg,";
            $arrOpt[count($arrOpt)]=$arrProp["repeat_msg"];
        }

        if(isset($arrProp["recording_id"])){
            if($arrProp["recording_id"]!="none"){
                if($this->getFileRecordings($this->domain,$arrProp["recording_id"])==false){
                    $arrProp["recording_id"]="none";
                }
            }
            $query .="recording_id,";
            $arrOpt[count($arrOpt)]=$arrProp["recording_id"];
        }
        
       if(isset($arrProp["destination"])){
            if($this->validateDestine($this->domain,$arrProp["destination"])!=false){
                $query .="destination,goto";
                $arrOpt[count($arrOpt)]=$arrProp["destination"];
                $tmp=explode(",",$arrProp["destination"]);
                $arrOpt[count($arrOpt)]=$tmp[0];
            }else{
                $this->errMsg="Invalid destination";
                return false;
            }
        }

        $query .=")";
        $qmarks = "(";
        for($i=0;$i<count($arrOpt);$i++){
            $qmarks .="?,"; 
        }
        $qmarks=substr($qmarks,0,-1).")"; 
        $query = $query." values".$qmarks;
        $result=$this->executeQuery($query,$arrOpt);
                
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 
    }

    function updateAnnouncementPBX($arrProp){
        $query="UPDATE announcement SET ";
        $arrOpt=array();

        $result=$this->getAnnouncementById($arrProp["id"]);
        if($result==false){
            $this->errMsg=_tr("Announcement doesn't exist").$this->errMsg;
            return false;
        }
        $idAnnouncement=$result["id"];
        
        //debe haberse seteado description
        if(!isset($arrProp["description"]) || $arrProp["description"]==""){
            $this->errMsg=_tr("Field 'Description' can't be empty");
            return false;
        }else{
            $query .="description=?,";
            $arrOpt[count($arrOpt)]=$arrProp["description"];
        }
        
        if(isset($arrProp["allow_skip"])){
            $query .="allow_skip=?,";
            $arrOpt[count($arrOpt)]=$arrProp["allow_skip"];
        }

        if(isset($arrProp["return_ivr"])){
            $query .="return_ivr=?,";
            $arrOpt[count($arrOpt)]=$arrProp["return_ivr"];
        }
        
        if(isset($arrProp["noanswer"])){
            $query .="noanswer=?,";
            $arrOpt[count($arrOpt)]=$arrProp["noanswer"];
        }
        
        if(isset($arrProp["repeat_msg"])){
            $query .="repeat_msg=?,";
            $arrOpt[count($arrOpt)]=$arrProp["repeat_msg"];
        }

        if(isset($arrProp["recording_id"])){
            if($arrProp["recording_id"]!="none"){
                if($this->getFileRecordings($this->domain,$arrProp["recording_id"])==false){
                    $arrProp["recording_id"]="none";
                }
            }
            $query .="recording_id=?,";
            $arrOpt[count($arrOpt)]=$arrProp["recording_id"];
        }
        
       if(isset($arrProp["destination"])){
            if($this->validateDestine($this->domain,$arrProp["destination"])!=false){
                $query .="destination=?,goto=?";
                $arrOpt[count($arrOpt)]=$arrProp["destination"];
                $tmp=explode(",",$arrProp["destination"]);
                $arrOpt[count($arrOpt)]=$tmp[0];
            }else{
                $this->errMsg="Invalid destination";
                return false;
            }
        }       
        
        //caller id options                
        $query = $query." WHERE id=?"; 
        $arrOpt[count($arrOpt)]=$idAnnouncement;
        $result=$this->executeQuery($query,$arrOpt);
        if($result==false)
            $this->errMsg=$this->errMsg;
        return $result; 
         
    }


    function deleteAnnouncement($id){
        $result=$this->getAnnouncementById($id);
        if($result==false){
            $this->errMsg=_tr("Announcement doesn't exist").$this->errMsg;
            return false;
        }
        
        $query="DELETE from announcement where id=?";
        if($this->executeQuery($query,array($id))){
            return true;
        }else{
            $this->errMsg = _tr("Announcement can't be deleted.").$this->errMsg;
            return false;
        } 
    }
    
    function createDialplanAnnouncement(&$arrFromInt){
        if(is_null($this->code) || is_null($this->domain))
            return false;
            
        $arrAnnouncement = $this->getAnnouncement($this->domain);
        if($arrAnnouncement===false){
            $this->errMsg=_tr("Error creating dialplan for Announcement. ").$this->errMsg; 
            return false;
        }
        else{
            $arrContext = array();
            
            foreach($arrAnnouncement as $value){
                $recording_file = $this->getFileRecordings($this->domain,$value["recording_id"]);
                $repeat_msg     = ($value['repeat_msg']=="no") || ($value['repeat_msg']==0)?false:$value['repeat_msg'];
                $allow_skip     = ($value['allow_skip']=="no")?false:$value['allow_skip'];
                $return_ivr     = ($value['return_ivr']=="no")?false:$value['return_ivr'];
                $noanswer       = ($value['noanswer']=="no")?false:$value['noanswer'];
                $exten          = "s";
                
                if(isset($value["destination"])){
                    $goto = $this->getGotoDestine($this->domain,$value["destination"]);
                    if($goto==false)
                        $goto = "h,1";
                }
                    
                if(!$noanswer){
                    $arrExt[]=new paloExtensions($exten, new ext_gotoif('$["${CDR(disposition)}" = "ANSWERED"]','begin'),1);
                    $arrExt[]=new paloExtensions($exten, new ext_answer(''));
                    $arrExt[]=new paloExtensions($exten, new ext_wait('1'));
                }
                $arrExt[]=new paloExtensions($exten, new ext_noop('Playing announcement '.$value['description']),($noanswer)?"1":"n","begin");
            
                if(!($allow_skip || $repeat_msg))
                    $arrExt[]=new paloExtensions($exten, new ext_playback($recording_file.',noanswer'));
                
                if($repeat_msg)
                    $arrExt[]=new paloExtensions($exten, new ext_responsetimeout(1));
                
                if($allow_skip || $repeat_msg)
                    $arrExt[]=new paloExtensions($exten, new ext_background($recording_file.',nm'),"n","play");
                
                if($repeat_msg)
                    $arrExt[]=new paloExtensions($exten, new ext_waitexten(''));
                
                if($return_ivr){
                    if(!$repeat_msg)
                        $arrExt[]=new paloExtensions($exten, new ext_gotoif('$["x${IVR_CONTEXT}" = "x"]', $goto.':${IVR_CONTEXT},return,1'));
                } 
                else{
                    if(!$repeat_msg)
                        $arrExt[]=new paloExtensions($exten, new extension("Goto(".$goto.")"));
                }
                
                if($allow_skip){
                    $arrExt[]=new paloExtensions("_X", new ext_noop('User skipped announcement'),"1");
                    if ($return_ivr)
                        $arrExt[]=new paloExtensions("_X", new ext_gotoif('$["x${IVR_CONTEXT}" = "x"]', $goto.':${IVR_CONTEXT},return,1'));
                    else 
                        $arrExt[]=new paloExtensions("_X", new extension("Goto(".$goto.")"));
                }
                
                if($repeat_msg)
                    $arrExt[]=new paloExtensions($repeat_msg,new ext_goto('s,play'),"1");
                
                // if repeat_msg enabled then set exten to t to allow for the key to be pressed, otherwise play message and go
                if($return_ivr){
                    if($repeat_msg)
                        $arrExt[]=new paloExtensions("t", new ext_gotoif('$["x${IVR_CONTEXT}" = "x"]', $goto.':${IVR_CONTEXT},return,1'),"1");
                    if($allow_skip || $repeat_msg)
                        $arrExt[]=new paloExtensions('i', new ext_gotoif('$["x${IVR_CONTEXT}" = "x"]', $goto.':${IVR_CONTEXT},return,1'),"1");
                } 
                else{
                    if($repeat_msg)
                        $arrExt[]=new paloExtensions("t", new extension("Goto(".$goto.")"),"1");
                    if($allow_skip || $repeat_msg)
                        $arrExt[]=new paloExtensions('i', new extension("Goto(".$goto.")"),"1");
                }
            
                //creamos context app-announcement
                $context = new paloContexto($this->code,"app-announcement-{$value['id']}");
                if($context === false)
                    $context->errMsg = "ext-announcement. Error: ".$context->errMsg;
                else{
                    $context->arrExtensions = $arrExt;
                    $arrContext[]           = $context;
                    $arrExt                 = array();
                }
            }
        
            return $arrContext;
        }
    }
}
?>
