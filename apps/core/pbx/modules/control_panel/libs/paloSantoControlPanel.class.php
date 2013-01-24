<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.6-3                                               |
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
  $Id: paloSantoControlPanel.class.php,v 1.1 2009-06-08 03:06:39 Oscar Navarrete onavarrete@palosanto.com Exp $ */

global $arrConf;
if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}
require_once $arrConf['basePath']."/libs/paloSantoTrunk.class.php";

/* Esta clase sólo sirve para tragarse el mensaje de error para que no se 
 * escriba permanentemente en el log de httpd */ 
class dummy_pagi {
	function conlog($a, $b) {}
}

class paloSantoControlPanel {
    var $_DB1;
    var $_DB2;
    var $errMsg;

    function paloSantoControlPanel(&$pDB1, &$pDB2)
    {
        if (is_object($pDB1)) {
            $this->_DB1 =& $pDB1;
            $this->errMsg = $this->_DB1->errMsg;
        } else {
            $dsn = (string)$pDB1;
            $this->_DB1 = new paloDB($dsn);

            if (!$this->_DB1->connStatus) {
                $this->errMsg = $this->_DB1->errMsg;
            }
        }

        if (is_object($pDB2)) {
            $this->_DB2 =& $pDB2;
            $this->errMsg = $this->_DB2->errMsg;
        } else {
            $dsn = (string)$pDB2;
            $this->_DB2 = new paloDB($dsn);

            if (!$this->_DB2->connStatus) {
                $this->errMsg = $this->_DB2->errMsg;
            }
        }
    }

    function AsteriskManagerAPI($action, $parameters, $return_data=false) 
    {
        global $arrLang;
        $astman_host = "127.0.0.1";
        $astman_user = 'admin';
        $astman_pwrd = obtenerClaveAMIAdmin();

        $astman = new AGI_AsteriskManager();
        $astman->pagi = new dummy_pagi;

        if (!$astman->connect("$astman_host", "$astman_user" , "$astman_pwrd")) {
            $this->errMsg = _tr("Error when connecting to Asterisk Manager");
        } else{
            $salida = $astman->send_request($action, $parameters);
            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                if($return_data) return $salida;
                else return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }


    function getAllDevicesARRAY(){
        global $arrLang;
        $arrDevs   = $this->getAllDevices();
        $arrChan   = $this->showChannels();
        $arrDesign = $this->getDesign();

        $arrRecords = array();
        if(is_array($arrDevs) & count($arrDevs)>0){
            foreach($arrDevs as $key => $ext_data){
                if(ereg("^/AMPUSER/([[:digit:]]+)/cidname[[:space:]]+:[[:space:]]+([[:alnum:] -_\.]+)$",$ext_data,$arrReg)){
                    $value['user']    = $arrReg[1];
                    $value['cidname'] = $arrReg[2];

                    $call_dstn = " ";
                    if(isset($arrChan[$value['user']]['dstn'])){
                        $tmp = explode("-",$arrChan[$value['user']]['dstn']);
                        $tmp = explode("/",$tmp[0]);
                        $call_dstn = isset($tmp[1])?$tmp[1]:" ";
                    }
                    $speak_time = isset($arrChan[$value['user']]['time'])?$arrChan[$value['user']]['time']:" ";
                    $arrVM      = $this->mailboxCount($value['user']);
                    $status     = $this->getDeviceRegistry($value['user']);
                    $area       = isset($arrDesign[$value['user']])?$arrDesign[$value['user']]:1;

                    $arrRecords[$area][$value['user']] = array(
                        "short_name"     => substr($value['cidname'],0,12),
                        "full_name"      => $value['cidname'],
                        "status"         => $status,
                        "voicemail"      => ($arrVM['new']>0)?1:0,
                        "voicemail_cnt"  => "New $arrVM[new], Old $arrVM[old]",
                        "call_dstn"      => $call_dstn,
                        "speak_time"     => $speak_time);
                }
            }
        }
        return $arrRecords;

    }


    function showChannels()
    {
        //$parameters  = array('Command'=>'core show channels verbose');
        $parameters  = array('Command'=>'core show channels concise');
        $data        = $this->AsteriskManagerAPI('Command',$parameters,true);
        $arrChannels = explode("\n",$data['data']);
        $arrData     = null;

        if(is_array($arrChannels) & count($arrChannels)>0){
            foreach($arrChannels as $key => $line){
                $tmp = explode("!",$line);
                if(count($tmp) > 10){
                    //$tmp[0] channel que orginina la llamada TECHNOLOGY/USER-uniquecode
                    $dev=explode("/",$tmp[0]);
                    if(is_array($dev) && count($dev)==2){
                        if($dev[0]!="Local"){
                            $pos=strpos($dev[1],"-");
                            if($pos!==false){
                                $user=substr($dev[1],0,$pos);
                                $arrData[$user] = array(
                                    'context' => $tmp[1],//para ver si es macro-dialout-trunk
                                    'state' => $tmp[4],
                                    'data' => $tmp[6],//para ver la troncal que es casi igual a tmp[11]
                                    //'callerid' => $tmp[8],
                                    'time' => $this->Sec2HHMMSS($tmp[11]),
                                    'dstn' => $tmp[12],
                                    'ext' => $tmp[2]
                                    //'brigedto' => $tmp[12],
                                );
                            }
                        }
                    }
                }
            }
        }
        return $arrData;
    }

    function getAllDevicesXML(){
        global $arrLang;
        $arrDevs   = $this->getAllDevices();
        $arrChan   = $this->showChannels();
        $arrConferences = $this->getDataConferences();
        $numconf = " ";
        $parties = " ";
        $activity = " ";
        //$arrDesign = $this->getDesign();
        $arrRecords = null;
        if(is_array($arrDevs) & count($arrDevs)>0){
            foreach($arrDevs as $key => $ext_data){
                if(ereg("^/AMPUSER/([[:digit:]]+)/cidname[[:space:]]+:([[:alnum:]| |-|_|\.]+)$",$ext_data,$arrReg)){
                    $value['user']    = $arrReg[1];
                    $value['cidname'] = $arrReg[2];
                    $call_dstn = " ";
                    if(isset($arrChan[$value['user']]['dstn'])){
                        if($arrChan[$value['user']]['dstn'] == "(None)"){
                            if(isset($arrChan[$value['user']]['ext']))
                                $call_dstn = $arrChan[$value['user']]['ext'];
                        }
                        else{
                            $tmp = explode("-",$arrChan[$value['user']]['dstn']);
                            $tmp = explode("/",$tmp[0]);
                            $call_dstn = isset($tmp[1])?$tmp[1]:" ";
                        }
                    }
                    $state_call = isset($arrChan[$value['user']]['state'])?$arrChan[$value['user']]['state']:"Down";
                    $speak_time = isset($arrChan[$value['user']]['time'])?$arrChan[$value['user']]['time']:" ";
                    $context = isset($arrChan[$value['user']]['context'])?$arrChan[$value['user']]['context']:" ";
                    $trunk = " ";
                    if($context=="macro-dialout-trunk"){
                        if(isset($arrChan[$value['user']]['dstn'])){
                            $tmp = explode("-",$arrChan[$value['user']]['dstn']);
                            $tmp = explode("/",$tmp[0]);
                            if(isset($tmp[1]))
                                $trunk = isset($tmp[0])?$tmp[0]."/".$tmp[1]:" ";
                        }
                        if(isset($arrChan[$value['user']]['data'])){
                            $tmp = explode(",",$arrChan[$value['user']]['data']);
                            $tmp = explode("/",$tmp[0]);
                            $call_dstn = isset($tmp[2])?$tmp[2]:" ";
                        }
                    }
                    $numconf = " ";
                    $parties = " ";
                    $activity = " ";
                    $tmp = null;
                    if(isset($arrChan[$value['user']]['data'])){
                         $tmp = explode(",",$arrChan[$value['user']]['data']);
                    }
                    if(isset($tmp))
                        if(isset($arrConferences[$tmp[0]])){
                            $numconf = $arrConferences[$tmp[0]]['number'];
                            $call_dstn = $numconf;
                            if($arrConferences[$tmp[0]]['parties'] > 1)
                              $parties = $arrConferences[$tmp[0]]['parties']." "._tr("Participants");
                            else
                              $parties = $arrConferences[$tmp[0]]['parties']." "._tr("Participant");
                            $activity = $arrConferences[$tmp[0]]['activity'];
                        }
                    $arrVM      = $this->mailboxCount($value['user']);
                    $voicemail  = ($arrVM['new']>0)?1:0;
                    $status     = $this->getDeviceRegistry($value['user']);
                    //$area       = isset($arrDesign[$value['user']])?$arrDesign[$value['user']]:1;
                   /* if($numconf != " ")
                        $statusConf = "on";
                    else
                        $statusConf = "off";*/
                    $arrRecords[]=array("user" => $value['user'], "status" => $status, "voicemail" => $voicemail, "voicemail_cnt" => "New $arrVM[new], Old $arrVM[old]", "state_call" => $state_call,"call_dstn" => $call_dstn, "speak_time" => $speak_time, "context" => $context, "trunk" => $trunk,"numconf" => $numconf, "parties" => $parties, "activity" => $activity);//, "statusConf" => $statusConf);
                }
            }
        }
        $arrParkinglots = $this->getParkinglots();
        $arrParking = $this->getDataParkinglots();
	$numslots = 0;
        foreach($arrParkinglots as $key => $value){
	    if($value["keyword"] == "numslots")
		$numslots = $value["data"];
	    elseif($value["keyword"] == "parkext")
		$parkext = $value["data"];
        }
        for($i=0;$i<$numslots;$i++){
            $time = " ";
            $extension = " ";
            if(isset($arrParking[$i+1+$parkext])){
                $time = $arrParking[$i+1+$parkext]["time"];
                $extension = $arrParking[$i+1+$parkext]["extension"];
            }
            $arrRecords[] = array("lotNumber" => $i+1+$parkext, "time" => $time, "extension" => $extension);
        }
        
        $arrqueue = $this->getAllQueuesARRAY2();
        foreach($arrqueue as $key => $queue){
            $arrRecords[] = array("queueNumber" => $queue["number"], "waiting" => $queue["queue_wait"]);
        }
        return $arrRecords;
    }

    function getDataConferences()
    {
       $parameters = array('Command'=>"meetme list");
       $data = $this->AsteriskManagerAPI("Command",$parameters,true);
       $arrConferences = explode("\n",$data['data']);
       $arrConf = array();
       if(is_array($arrConferences))
        foreach($arrConferences as $key => $line){
            if(preg_match_all('/^([[:digit:]]+)[[:space:]]*([[:digit:]]+)[[:space:]]*[[:alnum:]|\/]+[[:space:]]*([[:digit:]|\:]+)/',$line,$matches)){
                $arrConf[$matches[1][0]]['number'] = $matches[1][0];
                for($j=0;$j<strlen($matches[2][0]);$j++){                 
                    if($matches[2][0][$j] != 0){
                        $parties = substr($matches[2][0],$j);
                        $arrConf[$matches[1][0]]['parties'] = $parties;
                        break;
                    }
                }
                $arrConf[$matches[1][0]]['activity'] = $matches[3][0];
            }
        }
        return $arrConf;
    }

    function getDataParkinglots()
    {
        $parameters = array('Command'=>"parkedcalls show");
        $data = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrParkinglots = explode("\n",$data['data']);
        $arrParking = array();
        if(is_array($arrParkinglots))
            foreach($arrParkinglots as $key => $line){
                if(preg_match_all('/^([[:digit:]]+)[[:space:]]*([[:alnum:]|\/|\-]+)[[:space:]]*[[:alnum:]|\(|\-]+[[:space:]]*[[:alnum:]]+[[:space:]]*[[:digit:]]+[[:space:]]*[[:alnum:]|\)]+[[:space:]]*([[:digit:]]+)/',$line,$matches)){
                    $tmp = $matches[2][0];
                    $tmp = explode("/",$tmp);
                    $tmp = explode("-",$tmp[1]);
                    $arrParking[$matches[1][0]]["extension"] = $tmp[0];
                    $time = $matches[3][0];
                    $arrParking[$matches[1][0]]["time"] = $this->Sec2HHMMSS($time);
                    $arrParking[$matches[1][0]]["parkinglot"] = $matches[1][0];
                }
            }
        return $arrParking;
    }

    function getAllDevices()
    {
        $parameters = array('Command'=>"database showkey cidname");
        $data = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrDevice = explode("\n",$data['data']);
        return $arrDevice;
    }

    function getDeviceRegistry($ext)
    {
        $parameters = array('Command'=>"database showkey Registry/$ext");
        $data = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrData = explode("\n",$data['data']);
       
        $arrData = isset($arrData[1])?$arrData[1]:"";
        $arrData = explode("/",$arrData);

        return isset($arrData[2])?"on":"off";
    }


    function getDataExt($ext)
    {
        $parameters = array('Command'=>"database show AMPUSER $ext/cidname");
        $data = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrData = explode("\n",$data['data']);
        $arrData = isset($arrData[1])?$arrData[1]:"";
        $arrData = explode(":",$arrData);
        $salida['cidname'] = isset($arrData[1])?trim($arrData[1]):"";

        $parameters = array('Command'=>"database show DEVICE  $ext/dial");
        $data = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrData = explode("\n",$data['data']);
        $arrData = isset($arrData[1])?$arrData[1]:"";
        $arrData = explode(":",$arrData);
        $salida['dial'] = isset($arrData[1])?trim($arrData[1]):"";

        return $salida;
    }

    function makeCalled($number_org, $number_dst)
    {
        $dataExt    = $this->getDataExt($number_org);
        $parameters = $this->Originate($number_org, $number_dst, $dataExt['dial'], $dataExt['cidname']);

        return $this->AsteriskManagerAPI("Originate",$parameters);
    }

    function Originate($origen, $destino, $channel="", $description="")
    {
        $parameters = array();
        $parameters['Channel']      = $channel;
        $parameters['CallerID']     = "$description <$origen>";
        $parameters['Exten']        = $destino;
        $parameters['Context']      = "";
        $parameters['Priority']     = 1;
        $parameters['Application']  = "";
        $parameters['Data']         = "";

        return $parameters;
    }

    function getChannelExt($ext)
    {       
        $parameters  = array('Command'=>'core show channels concise');
        $data        = $this->AsteriskManagerAPI('Command',$parameters,true);
        $arrChannels = explode("\n",$data['data']);

        if(is_array($arrChannels) & count($arrChannels)>0){
            $arrDataExt = $this->getDataExt($ext);
            foreach($arrChannels as $key => $line){
                $tmp = explode("!",$line);
                if(count($tmp) > 10){                    
                    if(ereg($arrDataExt['dial'],$tmp[0]))
                        return $tmp[0];
                }
            }
        }
        return null;
    }

    function hangupCalled($ext)
    {
        $channel = $this->getChannelExt($ext);
        $parameters = array('Channel'=>$channel);
        return $this->AsteriskManagerAPI("Hangup",$parameters);
    }

    function queueAddMember($queue,$ext)
    {
        $dataExt    = $this->getDataExt($ext);
        $parameters = array('Queue'=>$queue,'Interface'=>$dataExt['dial']);
        return $this->AsteriskManagerAPI("QueueAdd",$parameters);
    }

    function mailboxCount($number_org)
    {
        $parameters = array('Mailbox'=>"$number_org@default");
        $arrVM = $this->AsteriskManagerAPI("MailboxCount",$parameters,true);

        $arrData['new'] = isset($arrVM['NewMessages'])?$arrVM['NewMessages']:0;
        $arrData['old'] = isset($arrVM['OldMessages'])?$arrVM['OldMessages']:0;
        return $arrData;
    }

    function getAllQueuesARRAY()
    {
        $query = "select extension queue, descr name from queues_config;";
        $result=$this->_DB1->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB1->errMsg;
            return array();
        }

        $arrQueue = array();
        foreach($result as $key => $value)
            $arrQueue[$value['queue']] = $value['name'];
        return $arrQueue;
    }

    function getAllQueuesARRAY2()
    {
        $arrMember= $this->getQueueMembers();
        $arrQueue = array();
        $count=-1;
        if(is_array($arrMember) & count($arrMember)>0){
            foreach($arrMember as $key => $queue_data) {
                if(ereg("^([0-9]+)[[:space:]]*has ([0-9]+)",$queue_data, $arrReg)){
                    $count++;
                    $arrQueue[$count]['number'] = $arrReg[1]; 
                    $arrQueue[$count]['name'] = $arrReg[1]; 
                    $arrQueue[$count]['queue_wait'] = $arrReg[2]; 
                    $arrQueue[$count]['members']="Not Attended";
                }else{
                    if(preg_match("/^[[:alpha:]]+\/([[:digit:]]+)/",trim($queue_data), $data) || preg_match("/^.+\([[:alpha:]]+\/([[:digit:]]+)/",trim($queue_data),$data2)){
			$member = isset($data[1])?$data[1]:$data2[1];
			if($arrQueue[$count]['members'] == "Not Attended")
			    $arrQueue[$count]['members'] = "Queue attended by $member";
			else
			    $arrQueue[$count]['members'] .= ", $member";
                    }
                }
            }
        }
        return $arrQueue;
    }

    function getQueueMembers()
    {
        $arrQueueMembers = array();
        $parameters = array('Command'=>"queue show");
        $data = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrQueueMembers = explode("\n",$data['data']);
        return $arrQueueMembers;
    }


    function getDAHDITrunksARRAY()
    {
       /* $result = getTrunks($this->_DB1);

        if($result==FALSE){
            $this->errMsg = $this->_DB1->errMsg;
            return array();
        }
        $arrTrunk = array();
        $arrTmp = array();
        foreach($result as $key => $value)
            $arrTmp[$value[0]] = $value[1];
        foreach($arrTmp as $key => $value){
            $trunk = explode("/",$value);
            if($trunk[0] != "DAHDI")
                $arrTrunk[] = $value;
        }*/
        $parameters = array('Command'=>"dahdi show channels");
        $result = $this->AsteriskManagerAPI("Command",$parameters,true); 
        $data = explode("\n",$result['data']);
	$arrTrunk = array();
        if(is_array($data) && count($data)>0)
          foreach($data as $line){
            $value = preg_match('/^[[:space:]]*([[:digit:]]+)/',$line,$matches);
            if($value)
                $arrTrunk[] = "DAHDI/$matches[1]";
        }
        return $arrTrunk;
    }

    function getSIPTrunksARRAY()
    {
        $result = getTrunks($this->_DB1);

        if($result==FALSE){
            $this->errMsg = $this->_DB1->errMsg;
            return array();
        }
        $arrTrunk = array();
        $arrTmp = array();
        foreach($result as $key => $value)
            $arrTmp[$value[0]] = $value[1];
        foreach($arrTmp as $key => $value){
            $trunk = explode("/",$value);
            if($trunk[0] != "DAHDI")
                $arrTrunk[] = $value;
        }
        foreach($arrTrunk as $key => $value){
            $tmp = explode("/",$value);
            $arrTrunk[$key] = array();
            $arrTrunk[$key]['name'] = $value;
	    $arrTrunk[$key]['status'] = "off";
            if($tmp[0] == "SIP")
                $arrTrunk[$key]['status'] = $this->getTrunkStatus($value,"SIP");
            elseif($tmp[0] == "IAX2")
                $arrTrunk[$key]['status'] = $this->getTrunkStatus($value,"IAX2");
        }
        return $arrTrunk;
    }

    function getConferences()
    {
       $query = "select * from meetme;";
       $result=$this->_DB1->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB1->errMsg;
            return array();
        }
        return $result;
    }

    function getParkinglots()
    {
       $query = "select * from parkinglot where keyword = 'parkext' or keyword = 'numslots';";
       $result=$this->_DB1->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB1->errMsg;
            return array();
        }
        
        return $result;
    }

    function getTrunkStatus($value,$tech)
    {
        if($tech == "SIP")
            $parameters = array('Command'=>"sip show peers");
        elseif($tech == "IAX2")
            $parameters = array('Command'=>"iax2 show peers");
        $result = $this->AsteriskManagerAPI("Command",$parameters,true); 
        $data = explode("\n",$result['data']);
        $tmp = explode("/",$value);
        $value = $tmp[1];
        foreach($data as $key => $line){
            if(strpos($line,$value) !== false){
                $tmp = explode(" ",$line);
                foreach($tmp as $key2 => $value2){
                    if($key2 != 0){
                        if($value2 == "OK")
                            return "on";                     
                    }
                }
            } 
        }
        return "off";
    }


    function getDesign()
    {
        $query = "select id_device, id_area from item_box order by id_device asc;";
        $result=$this->_DB2->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB2->errMsg;
            return array();
        }

        $arrDesign = array();
        foreach($result as $key => $value)
            $arrDesign[$value['id_device']] = $value['id_area'];
        return $arrDesign;
    }

    function saveChangeArea($id_device, $id_area)
    {
        $query = "select count(*) existe from item_box where id_device=$id_device";
        $result=$this->_DB2->getFirstRowQuery($query,true);

        if(is_array($result) && $result['existe']==1)
            $query = "update item_box set id_area=$id_area where id_device=$id_device";
        else
            $query = "insert into item_box(id_device,id_area) values($id_device,$id_area);";

        $result=$this->_DB2->genQuery($query);
        if($result==FALSE){
            $this->errMsg = $this->_DB2->errMsg;
            return false;
        }
        return true;
    }

    function saveChangeArea2($id_device1, $id_device2)
    {
        $query = "select id_area, count(*) existe from item_box where id_device=$id_device2";
        $result=$this->_DB2->getFirstRowQuery($query,true);
        if($result['existe']==1){
            $id_area = $result['id_area'];
        }else{
            $id_area = 1;
        }
        return $this->saveChangeArea($id_device1, $id_area);
    }

    function updateResizeArea($height, $width, $no_column, $id_area)
    {
        $query = "update area set height=$height, width=$width, no_column=$no_column where id=$id_area";

        $result=$this->_DB2->genQuery($query);
        if($result==FALSE){
            $this->errMsg = $this->_DB2->errMsg;
            return false;
        }
        return true;
    }

    function updateResizeArea2($height, $width, $no_column, $id_area)
    {
        if($id_area==1){
            $query1 = "update area set height=$height, width=$width, no_column=$no_column where id=1";
    
            $result=$this->_DB2->genQuery($query1);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
    
            $query2 = "update area set width=$width, no_column=$no_column where id=6";
            $result=$this->_DB2->genQuery($query2);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
            
            $query3 = "update area set width=$width, no_column=$no_column where id=7";
            $result=$this->_DB2->genQuery($query3);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
        }elseif($id_area==6){
            $query1 = "update area set height=$height, width=$width, no_column=$no_column where id=6";
            $result=$this->_DB2->genQuery($query1);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
    
            $query2 = "update area set width=$width, no_column=$no_column where id=1";
            $result=$this->_DB2->genQuery($query2);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
            $query3 = "update area set width=$width, no_column=$no_column where id=7";
            $result=$this->_DB2->genQuery($query3);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }  
        }else{
            $query1 = "update area set height=$height, width=$width, no_column=$no_column where id=7";
            $result=$this->_DB2->genQuery($query1);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
    
            $query2 = "update area set width=$width, no_column=$no_column where id=1";
            $result=$this->_DB2->genQuery($query2);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
            $query3 = "update area set width=$width, no_column=$no_column where id=6";
            $result=$this->_DB2->genQuery($query3);
            if($result==FALSE){
                $this->errMsg = $this->_DB2->errMsg;
                return false;
            }
        }

        return true;
    }
    

    function getAllAreasXML() {
        global $arrLang;
        
        $arrAreas = $this->getDesignArea();
        $xmlRecords = "";
        if(is_array($arrAreas) & count($arrAreas)>0){
            $xmlRecords .= "<?xml version=\"1.0\"?>\n";
            $xmlRecords .= "<areas>\n";
            foreach($arrAreas as $key => $area_data){          
                $xmlRecords .= "  <area_box>\n";
                $xmlRecords .= "    <id>{$area_data['a.id']}</id>\n";
                $xmlRecords .= "    <name>{$area_data['a.name']}</name>\n";
                $xmlRecords .= "    <height>auto</height>\n";
                $xmlRecords .= "    <width>{$area_data['a.width']}</width>\n";
                $xmlRecords .= "    <color>{$area_data['a.color']}</color>\n";
                //$xmlRecords .= "    <description>{$area_data['a.description']}</description>\n";
                //$xmlRecords .= "    <no_items>{$area_data['no_items']}</no_items>\n";
                $xmlRecords .= "  </area_box>\n";
            }
            $xmlRecords .= "</areas>\n";
        }
        return $xmlRecords;
    } 

    function getDesignArea() {
        $query = "select a.id AS a_id, a.name AS a_name, a.height AS a_height, a.width AS a_width, a.description AS a_description, a.no_column AS a_no_column, a.color AS a_color, count(i.id_area) no_items from area a left join item_box i on a.id=i.id_area group by a.id;";
        $result=$this->_DB2->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB2->errMsg;
            return array();
        }
        $r = array();
        foreach ($result as $tupla) {
            $r[] = array(
                'a.id' => $tupla['a_id'],
                'a.name' => $tupla['a_name'],
                'a.height' => $tupla['a_height'],
                'a.width' => $tupla['a_width'],
                'a.description' => $tupla['a_description'],
                'a.no_column' => $tupla['a_no_column'],
                'a.color' => $tupla['a_color'],
                'no_items' => $tupla['no_items'],
            );
        }
        return $r;
    }

    function updateDescriptionArea($description, $id_area){
        $query = "update area set description='$description' where id=$id_area";
        
        $result=$this->_DB2->genQuery($query);
        if($result==FALSE){
            $this->errMsg = $this->_DB2->errMsg;
            //return false;
            return $message = "There are someone error, Please try again or report at the root";
        }
        //return true;
        return $message= "Saved Successful!";
    } 


    function Sec2HHMMSS($sec)
    {
        $HH = '00'; $MM = '00'; $SS = '00';

        if($sec >= 3600){ 
            $HH = (int)($sec/3600);
            $sec = $sec%3600; 
            if( $HH < 10 ) $HH = "0$HH";
        }

        if( $sec >= 60 ){ 
            $MM = (int)($sec/60);
            $sec = $sec%60;
            if( $MM < 10 ) $MM = "0$MM";
        }

        $SS = $sec;
        if( $SS < 10 ) $SS = "0$SS";

        return "$HH:$MM:$SS";
    }

    function getNumQueueWaitingByUser($user) {
        $parameters = array('Command'=>"queue show");
        $arrQueues = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrQue = array();
        $num = 0;
        foreach($arrQueues as $line){
            if(ereg("^([0-9]+)[[:space:]]*has ([0-9]+)",$line,$arrToken)){
                if(trim($arrToken[1])==$queue)   
                    $num = $arrToken[2];
            }
//             if(ereg("^[[:space:]]*Local/([0-9]+))){
//                 if(trim($arrMember[1]==$user))
//             }
        }
        return $arrQue;
    }

    function getAsterisk_QueueInfo() {
        $parameters = array('Command'=>"queue show");
        $arrQueues = $this->AsteriskManagerAPI("Command",$parameters,true);
        $arrQue = array();
        if(is_array($arrQueues))
            foreach($arrQueues as $line){
                if(ereg("^([0-9]+)[[:space:]]*has ([0-9]+)",$line,$arrToken))
                    $arrQue[$arrToken[1]] = $arrToken[2];
            }
        return $arrQue;
    }

     function getAllQueuesXML(){
        global $arrLang;
        $arrDevs   = $this->getAllQueuesARRAY2();

        $xmlRecords = "";
        if(is_array($arrDevs) & count($arrDevs)>0){
            $xmlRecords .= "<?xml version=\"1.0\"?>\n";
            $xmlRecords .= "<items>\n";
            foreach($arrDevs as $key => $queue_data){
                    $xmlRecords .= "  <queue>\n";
                    $xmlRecords .= "    <name>{$queue_data['name']}</name>\n";
                    $xmlRecords .= "    <queue_wait>{$queue_data['queue_wait']}</queue_wait>\n";
                    $xmlRecords .= "    <members>{$queue_data['members']}</members>\n";
                    $xmlRecords .= "  </queue>\n";
            }
            $xmlRecords .= "</items>\n";
        }
        return $xmlRecords;
    }
}
?>
