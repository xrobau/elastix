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
  $Id: paloSantoIVR.class.php,v 1.1 2012-09-07 11:50:00 Rocio Mera rmera@palosanto.com Exp $ */
    include_once "/var/www/html/libs/paloSantoACL.class.php";
    include_once "/var/www/html/libs/paloSantoAsteriskConfig.class.php";
    include_once "/var/www/html/libs/paloSantoPBX.class.php";
	global $arrConf;

class paloConference extends paloAsteriskDB{
    protected $code;
    protected $domain;
        
    function paloConference(&$pDB,$domain){
        parent::__construct($pDB);
        
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format";
        }else{
            $this->domain=$domain;

            $result=$this->getCodeByDomain($domain);
            if($result==false){
                $this->errMsg .=_tr("Can't create a new instace of paloConference").$this->errMsg;
            }else{
                $this->code=$result["code"];
            }
        }
    }
    
    function getTotalConference($domain=NULL,$date,$state_conf="",$type_conf="",$name_conf=""){
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
        
        $cond=$this->createQueryCondition($date,$state_conf,$type_conf,$name_conf,$arrParam);
        
        $query="select count(bookid) from meetme $where $cond";
        $result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result[0];
    }
    
    private function createQueryCondition($date,$state_conf,$type_conf,$name_conf,&$arrParam){
        switch($state_conf){
            case "past":
                $state=" and endtime<?";
                $arrParam[]=$date;
                break;
            case "future":
                $state=" and startTime>?";
                $arrParam[]=$date;
                break;
            case "current":
                $state=" and startTime<=? and endtime>=?";
                $arrParam[]=$date;
                $arrParam[]=$date;
                break;
            default: 
                $state="";
        }
        
        switch($type_conf){
            case "yes":
                $type=" and startTime is not NULL and endtime is not NULL";
                break;
            case "no":
                $type=" and startTime is NULL and endtime is NULL";
                break;
            default: 
                $type="";
        }
        
        $name="";
        if($name_conf!=""){
            $name=" and name LIKE ?";
            $arrParam[]="%$name_conf%";
        }
        
        return $state.$type.$name;
    }
    
    function getConferesPagging($domain=null,$date,$limit,$offset,$state_conf="",$type_conf="",$name_conf=""){
        $where=$pagging="";
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
        
        //evaluamos los parametros de busqueda
        $cond=$this->createQueryCondition($date,$state_conf,$type_conf,$name_conf,$arrParam);
        
        if(isset($limit) && isset($offset)){
            $pagging=" limit ? offset ?";
            $arrParam[]=$limit;
            $arrParam[]=$offset;
        }
        
        $query="SELECT * from meetme $where $cond order by endtime desc $pagging";
                
        $result=$this->_DB->fetchTable($query,true,$arrParam);
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
            return $result;
    }
    
    private function existConfByName($name){
        if(empty($name)){
            $this->errMsg=_tr("Invalid field 'Conference Name'");
            return true;
        }
        
        $query="SELECT bookid from meetme where organization_domain=? and name=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($this->domain,$name));
        if($result===false || count($result)!=0){
            $this->errMsg=(count($result)!=0)?_tr("Already exist a conference with the same name"):$this->_DB->errMsg;
            return true;
        }else{
            return false;
        }
    }
    
    function getConferenceById($id){
        global $arrConf;
        if (!preg_match('/^[[:digit:]]+$/', "$id")) {
            $this->errMsg = _tr("Invalid Conference");
            return false;
        }

        $arrCredentiasls=getUserCredentials();
        $userLevel1=$arrCredentiasls["userlevel"];
        if($userLevel1=="admin"){
            $domain=$arrCredentiasls["domain"];
            if($domain==false){
                $this->errMsg=_tr("Invalid Organization");
                return false;
            }
        }else{
            $this->errMsg = _tr("Invalid Action");
            return false;
        }
        
        $query="SELECT * from meetme where organization_domain=? and bookid=?";
        $result=$this->_DB->getFirstRowQuery($query,true,array($domain,$id));
        if($result===false || count($result)==0){
            $this->errMsg=(count($result)==0)?_tr("Conference doesn't exist"):$this->_DB->errMsg;
            return false;
        }else{
            return $result;
        }
    }
    
    function createNewConf($arrProp){
        $query="INSERT INTO meetme (";
        $arrOpt=array();
    
        $arrCredentiasls=getUserCredentials();
        $userLevel1=$arrCredentiasls["userlevel"];
        if($userLevel1=="admin"){
            if($arrCredentiasls["domain"]!=$this->domain){
                $this->errMsg=_tr("Invalid Organization");
                return false;
            }
            $query .="organization_domain,";
            $arrOpt[]=$this->domain;
        }else{
            $this->errMsg = _tr("Invalid Action");
            return false;
        }
        
        if($this->existConfByName($arrProp["name"])==true){
            return false;
        }else{
            $query .="name,";
            $arrOpt[]=$arrProp["name"];
        }
        
        //el numero de la conferencia no debe estar siendo usado como patron de marcado
        if(!preg_match("/^[0-9]*$/",$arrProp["confno"])){
            $this->errMsg=_tr("Invalid Conference Number");
            return false;
        }
        if($this->existExtension($arrProp["confno"],$this->domain)==true){
            return false;
        }else{
            $query .="confno,";
            $arrOpt[]=$this->code."_".$arrProp["confno"];
            $query .="ext_conf,";
            $arrOpt[]=$arrProp["confno"];
        }
        
        if($arrProp['adminpin']!=""){
            if(!preg_match("/^[0-9]*$/",$arrProp['adminpin'])){
                $this->errMsg=_tr("Invalid Field 'Admin PIN'")._tr("Must contain only Digits");
                return false;
            }else{
                $query .="adminpin,";
                $arrOpt[]=$arrProp["adminpin"];
            }
        }
        
        if($arrProp['pin']!=""){
            if(!preg_match("/^[0-9]*$/",$arrProp['pin'])){
                $this->errMsg=_tr("Invalid Field 'User PIN'")._tr("Must contain only Digits");
                return false;
            }else{
                $query .="pin,";
                $arrOpt[]=$arrProp["pin"];
            }
        }
        
        if($arrProp['maxusers']!=""){
            if(!preg_match("/^[0-9]*$/",$arrProp['maxusers'])){
                $this->errMsg=_tr("Invalid Field 'maxusers'")._tr("Must contain only Digits");
                return false;
            }else{
                $query .="maxusers,";
                $arrOpt[]=$arrProp['maxusers'];
            }
        }
        
        if($arrProp['schedule']=="on"){
            if(!preg_match("/^(([1-2][0,9][0-9][0-9])-((0[1-9])|(1[0-2]))-((0[1-9])|([1-2][0-9])|(3[0-1]))) (([0-1][0-9]|2[0-3]):[0-5][0-9])$/",$arrProp['start_time'])){
                $this->errMsg=_tr("Invalid Format Start Time YYYY-MM-DD HH:MM");
                return false;
            }else{
                if(strtotime($arrProp['start_time']."+ 1 minutes")<time()){
                    $this->errMsg=_tr("Start Time can't less current time");
                    return false;
                }
                if(!preg_match("/^[0-9]{1,2}$/",$arrProp['duration']) || !preg_match("/^([0-5][0-9]|[0-9])$/",$arrProp['duration_min'])){
                    $this->errMsg=_tr("Invalid field 'duration'");
                    return false;
                }
                //obtenemos el endtime
                $endtime=strtotime($arrProp["start_time"])+((int)$arrProp['duration']*3600)+(int)$arrProp['duration_min']*60;
                $query .="startTime,endtime,";
                $arrOpt[]=$arrProp['start_time'];
                $arrOpt[]=strftime("%F %R",$endtime);   
            } 
        }
        
        $optAd="aAs";
        $optUser="";
        
        $optAd .=($arrProp['moderator_options_1']=="on")?"i":"";
        $optAd .=($arrProp['moderator_options_2']=="on")?"r":"";
        $optUser .=($arrProp['user_options_1']=="on")?"i":"";
        $optUser .=($arrProp['user_options_2']=="on")?"m":"";
        $optUser .=($arrProp['user_options_2']=="on")?"w":"";
        
        if($arrProp['announce_intro']!=""){
            $announ=$this->getFileRecordings($this->domain,$arrProp['announce_intro']);
            if($announ!=false){
                $optAd .="G($announ)";
                $optUser .="G($announ)";
            }
        }
        
        if($arrProp['moh']!=""){
            if($this->existMoHClass($arrProp['moh'],$this->domain)){
                $optAd .="M({$arrProp['moh']})";
                $optUser .="M({$arrProp['moh']})";
            }
        }
        
        $query .="opts,adminopts";
        $arrOpt[]=$optUser;
        $arrOpt[]=$optAd;
        
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
    
    function updateConference($arrProp){
        $arrConf=$this->getConferenceById($arrProp["id_conf"]);
        if($arrConf==false){
            return false;
        }
        
        $this->domain=$arrConf["organization_domain"];
        $query="Update meetme set ";
        
        if($arrProp["name"]!=$arrConf["name"]){
            if($this->existConfByName($arrProp["name"])==true){
                return false;
            }else{
                $query .="name=?,";
                $arrOpt[]=$arrProp["name"];
            }
        }
        
        if($arrProp['adminpin']!=""){
            if(!preg_match("/^[0-9]*$/",$arrProp['adminpin'])){
                $this->errMsg=_tr("Invalid Field 'Admin PIN'")._tr("Must contain only Digits");
                return false;
            }
        }else{
            $query .="adminpin=?,";
            $arrOpt[]=$arrProp["adminpin"];
        }
        
        if($arrProp['pin']!=""){
            if(!preg_match("/^[0-9]*$/",$arrProp['pin'])){
                $this->errMsg=_tr("Invalid Field 'User PIN'")._tr("Must contain only Digits");
                return false;
            }
        }else{
            $query .="pin=?,";
            $arrOpt[]=$arrProp["pin"];
        }
        
        if($arrProp['maxusers']!=""){
            if(!preg_match("/^[0-9]*$/",$arrProp['maxusers'])){
                $this->errMsg=_tr("Invalid Field 'maxusers'")._tr("Must contain only Digits");
                return false;
            }
        }else{
            $query .="maxusers=?,";
            $arrOpt[]=NULL;
        }
        
        if($arrProp['schedule']=="on"){
            if(!preg_match("/^(([1-2][0,9][0-9][0-9])-((0[1-9])|(1[0-2]))-((0[1-9])|([1-2][0-9])|(3[0-1]))) (([0-1][0-9]|2[0-3]):[0-5][0-9])$/",$arrProp['start_time'])){
                $this->errMsg=_tr("Invalid Format Start Time YYYY-MM-DD HH:MM");
                return false;
            }else{
                if(!preg_match("/^[0-9]{1,2}$/",$arrProp['duration']) || !preg_match("/^([0-5][0-9]|[0-9])$/",$arrProp['duration_min'])){
                    $this->errMsg=_tr("Invalid field 'duration'");
                    return false;
                }
                //obtenemos el endtime
                $endtime=strtotime($arrProp["start_time"])+((int)$arrProp['duration']*3600)+(int)$arrProp['duration_min']*60;
                $query .="startTime=?,endtime=?,";
                $arrOpt[]=$arrProp['start_time'];
                $arrOpt[]=strftime("%F %R",$endtime);
            } 
        }else{
            $query .="startTime=?,endtime=?,";
            $arrOpt[]='1900-01-01 12:00:00';
            $arrOpt[]='2999-01-01 12:00:00';
        }
        
        
        $optAd="aAs";
        $optUser="";
        
        $optAd .=($arrProp['moderator_options_1']=="on")?"i":"";
        $optAd .=($arrProp['moderator_options_2']=="on")?"r":"";
        $optUser .=($arrProp['user_options_1']=="on")?"i":"";
        $optUser .=($arrProp['user_options_2']=="on")?"m":"";
        $optUser .=($arrProp['user_options_2']=="on")?"w":"";
        
        if($arrProp['announce_intro']!=""){
            $announ=$this->getFileRecordings($this->domain,$arrProp['announce_intro']);
            if($announ!=false){
                $optAd .="G($announ)";
                $optUser .="G($announ)";
            }
        }
        
        if($arrProp['moh']!=""){
            if($this->existMoHClass($arrProp['moh'],$this->domain)){
                $optAd .="M({$arrProp['moh']})";
                $optUser .="M({$arrProp['moh']})";
            }
        }
        
        $query .="opts=?,adminopts=? where bookid=? and organization_domain=?";
        $arrOpt[]=$optUser;
        $arrOpt[]=$optAd;
        $arrOpt[]=$arrConf["bookid"];
        $arrOpt[]=$this->domain;
        
        $result=$this->executeQuery($query,$arrOpt);
        return $result; 
    }
    
    function deleteConference($id_conf){
        $arrConf=$this->getConferenceById($id_conf);
        if($arrConf==false){
            return false;
        }
        
        $query="DELETE from meetme where bookid=?";
        if($this->executeQuery($query,array($id_conf))){
            return true;
        }else{
            return false;
        }
    }
    
    private function AsteriskManager_Command($command) {
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        }else{
            $salida = $astMang->Command("$command");;
            $astMang->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["data"]);
            }
        }
        return false;
    }
    
    function ObtainCallers($room){
        $arrCallers=array();
        if(empty($this->domain) || empty($this->code)){
            return false;
        }
        
        if(!preg_match("/^".$this->code."_[0-9]+$/",$room)){
            $this->errMsg=_tr("Invalid Room");
            return false;
        }
        
        // User #: 01         1064 device               Channel: SIP/1064-00000001     (unmonitored) 00:00:11
        $regexp = '!^User #:[[:space:]]*([[:digit:]]+)[[:space:]]*([[:digit:]]+[[:alnum:]| |<|>]*)Channel: ([[:alnum:]]+/[[:alnum:]|_|-]+)[[:space:]]*(\(Admin\)){0,1}[[:space:]]*([[:alnum:]|\(|\)| ]+\))[[:space:]]*([[:digit:]|\:]+)$!i';
        
        $command = "meetme list $room";
        $arrResult = $this->AsteriskManager_Command($command);
        
        if(is_array($arrResult) && count($arrResult)>0) {
            foreach($arrResult as $Key => $linea) {
                if (preg_match($regexp, $linea, $arrReg)) {
                    $arrCallers[] = array(
                        'userId'    => $arrReg[1],
                        'callerId'  => $arrReg[2],
                        'mode'      => $arrReg[4], //se setea en caso de que el usuario sea de tipo admin (admin/user)
                        'status'    => $arrReg[5], //muted|no muted
                        'duration'  => $arrReg[6]
                    );
                }
            }
        }
        return $arrCallers;
    }

    function MuteCaller($room, $userId, $mute)
    {
        if($mute=='on')
            $action = 'mute';
        else
            $action = 'unmute';
        $command = "meetme $action $room $userId";
        $arrResult = $this->AsteriskManager_Command($command);
    }

    function KickCaller($room, $userId)
    {
        $action = 'kick';
        $command = "meetme $action $room $userId";
        $arrResult = $this->AsteriskManager_Command($command);
    }

    function InviteCaller($room, $ext_room, $channel, $callerId){
        $arrCallers=array();
        if(empty($this->domain) || empty($this->code)){
            return false;
        }
        
        if(!preg_match("/^".$this->code."_[0-9]+$/",$room)){
            $this->errMsg=_tr("Invalid Room");
            return false;
        }
        
        $query="Select exten from extension where dial=?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($channel));
        if($result==false){
            $this->errMsg=_tr("Invalid Exten");
            return false;
        }
        
        $astMang=AsteriskManagerConnect($errorM);
        if($astMang==false){
            $this->errMsg=$errorM;
            return false;
        } else{ 
            $parameters['Channel'] = $channel;
            $parameters['Context'] = $this->code."-ext-meetme";
            $parameters['Exten'] = $ext_room;
            $parameters['Priority']=1;
            $parameters['CallerID'] = $callerId;
            $parameters['Variable'] = "REALCALLERIDNUM=".$result[0];
            $salida = $astMang->send_request('Originate', $parameters);
            $astMang->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return true;
            }else
                return false;
        }
        return false;
    }
    
    function createDialplanConf(&$arrFromInt){
        if(is_null($this->code) || is_null($this->domain))
            return false;
    
        $arrExt=array();
        $query="SELECT ext_conf,confno from meetme where organization_domain=?";
        $result=$this->_DB->fetchTable($query,true,array($this->domain));
        if($result===false){
            $this->errMsg=$this->_DB->errMsg;
            return false;
        }else{
            foreach($result as $value){
                if(isset($value["ext_conf"]) && $value["ext_conf"]!=""){
                    $exten=$value["ext_conf"];
                    $arrExt[]=new paloExtensions($exten,new ext_setvar('MEETME_RECORDINGFILE', '/var/spool/asterisk/monitor/palosanto.com/meetme-conf-rec-'.$value["ext_conf"].'-${UNIQUEID}'),1);
                    $arrExt[]=new paloExtensions($exten,new ext_macro($this->code.'-user-callerid',"SKIPTTL"));
                    $arrExt[]=new paloExtensions($exten,new ext_meetme($value["confno"]));
                    $arrExt[]=new paloExtensions($exten,new ext_hangup());
                }
            }
            $arrExt[]=new paloExtensions("h",new ext_macro($this->code."-hangupcall"),1);
        }
        
        $arrContext=array();
        //creamos el context ext-meetme
        $context=new paloContexto($this->code,"ext-meetme");
        if($context===false){
            $context->errMsg="ext-meetme. Error: ".$context->errMsg;
        }else{
            $context->arrExtensions=$arrExt;
            $arrFromInt[]["name"]="ext-meetme";
            $arrContext[]=$context;
        }
        return $arrContext; 
    }
}
?>