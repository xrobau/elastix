<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-31                                               |
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
  $Id: paloSantoMyExtension.class.php,v 1.1 2010-08-09 10:08:51 Mercy Anchundia manchundia@palosanto.com Exp $ */

require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

class paloSantoMyExtension {
    var $_DB;
    var $errMsg;

    function paloSantoMyExtension(&$pDB)
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


    function getInsecurePortsById($id)
    {
        $query = "SELECT * FROM table WHERE id=$id";

        $result=$this->_DB->getFirstRowQuery($query,true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }
 


    /*HERE YOUR FUNCTIONS*/
    function updateDatabaseAsterisk($technology,$extension,$recordSettingsType)//$recordSettingsType is an array i.e: $recordSettingsIn["record_in"]  = $state_in;
    {
        if($technology != null){
            if(isset($recordSettingsType["record_in"])){
                $record_value  = $recordSettingsType["record_in"];
                $record_type = 'record_in';
            }
            else if(isset($recordSettingsType["record_out"])){
                $record_value = $recordSettingsType["record_out"];
                $record_type = 'record_out';
            }

            // Se actualiza la tabla correspondiente a la tecnología
            $query1 = "SELECT count(*) exists FROM $technology WHERE id = '$extension' AND keyword = '$record_type';";
            
            $result=$this->_DB->getFirstRowQuery($query1,true);
            if(is_array($result) && $result["exists"]==0){
            //not exist keyword
                  $query2 = "SELECT flags FROM $technology WHERE id = '$extension' order by flags desc limit 1;";
                  $result     = $this->_DB->getFirstRowQuery($query2,true);
                  $flag       = $result['flags'] + 1;
                  $query3 = "INSERT INTO $technology VALUES('$extension','$record_type','$record_value',$flag);";
                  $result=$this->_DB->genQuery($query3);
                  if($result==FALSE)
                  {
                        $this->errMsg = $this->_DB->errMsg;
                        return false;
                  }
            }else{
                $query4 = "UPDATE $technology SET data ='$record_value' WHERE id='$extension' AND keyword='$record_type';";
                $result=$this->_DB->genQuery($query4);
                if($result==FALSE)
                {
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
            }
            
            // Se actualiza la tabla users
            $sPeticion = 'SELECT recording FROM users WHERE extension = ?';
            $result = $this->_DB->getFirstRowQuery($sPeticion, TRUE, array($extension));
            if (is_array($result) && isset($result['recording'])) {
                $regs = NULL;
                if (preg_match('/^out=(\w+)\|in=(\w+)$/', $result['recording'], $regs)) {
                    $sValorOut = $regs[1]; $sValorIn = $regs[2];
                } else {
                    $sValorOut = $sValorIn = 'Adhoc';
                }
                if ($record_type == 'record_in') $sValorIn = $record_value;
                if ($record_type == 'record_out') $sValorOut = $record_value;
                $sRecNuevo = "out=$sValorOut|in=$sValorIn";
                if ($sRecNuevo != $result['recording']) {
                    $sPeticion = 'UPDATE users SET recording = ? WHERE extension = ?';
                    $result = $this->_DB->genQuery($sPeticion, array($sRecNuevo, $extension));
                    if($result==FALSE)
                    {
                        $this->errMsg = $this->_DB->errMsg;
                        return false;
                    }
                }
            }
            
            return true;
        }
        return false;
    }

    function AMI_Command($command)
    {
        $astman = new AGI_AsteriskManager();
        $return = false;
      
        if(!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin())){
            $this->errMsg = "Error connect AGI_AsteriskManager";
            $return = false;
        }
        else{
            $r = $astman->command($command);
            $return = $r["data"];
            $astman->disconnect();
        }
        return $return;
    }

    function setConfig_CallWaiting($enableCW,$extension)
    {
        $enableCW = trim(strtolower($enableCW));
        $return = false;

        if(eregi("^on", $enableCW)){
            $r = $this->AMI_Command("database put CW $extension \"ENABLED\"");
            if($r) $return = (bool)strstr($r, "success");
        }
        else{
            $r = $this->AMI_Command("database del CW $extension");
            if($r) $return = (bool)strstr($r, "removed") || (bool)strstr($r, "not exist");
        }
        return $return;
    }

    function setConfig_DoNotDisturb($enableDND,$extension)
    {
        $enableDND = trim(strtolower($enableDND));
        $return = false;

        if (eregi("^on", $enableDND)) {
            $r = $this->AMI_Command("database put DND $extension \"YES\"");
            if($r) $return = (bool)strstr($r, "success");
        } else {
            $r = $this->AMI_Command("database del DND $extension");
            if($r) $return = (bool)strstr($r, "removed") || (bool)strstr($r, "not exist");
        }
        return $return;
    }

    function setConfig_CallForward($enableCF,$phoneNumberCF,$extension)
    {
        $enableCF = trim(strtolower($enableCF));
        $return = false;
        if (eregi("^on", $enableCF)) {
            $r = $this->AMI_Command("database put CF $extension $phoneNumberCF");
            if($r) $return = (bool)strstr($r, "success");
        } else {
            $r = $this->AMI_Command("database del CF $extension");
            if($r) $return = (bool)strstr($r, "removed") || (bool)strstr($r, "not exist");
        }
        return $return;
    }
    
    function setConfig_CallForwardOnUnavail($enableCFU,$phoneNumberCFU,$extension)
    {
        $enableCFU = trim(strtolower($enableCFU));
        $return = false;

        if (eregi("^on", $enableCFU)) {
            $r = $this->AMI_Command("database put CFU $extension $phoneNumberCFU");
            if($r) $return = (bool)strstr($r, "success");
        } else {
            $r = $this->AMI_Command("database del CFU $extension");
            if($r) $return = (bool)strstr($r, "removed") || (bool)strstr($r, "not exist");
        }
        return $return;
    }

    function setConfig_CallForwardOnBusy($enableCFB,$phoneNumberCFB,$extension)
    {
        $enableCFB = trim(strtolower($enableCFB));
        $return = false;

        if (eregi("^on", $enableCFB)) {
            $r = $this->AMI_Command("database put CFB $extension $phoneNumberCFB");
            if($r) $return = (bool)strstr($r, "success");
        } else {
            $r = $this->AMI_Command("database del CFB $extension");
            if($r) $return = (bool)strstr($r, "removed") || (bool)strstr($r, "not exist");
        }
        return $return;
    }

    function getConfig_CallWaiting($extension)
    {
        $return = false;
        $r = $this->AMI_Command("database get CW $extension");
        if($r != false && strpos($r,"Value: ENABLED") !== false) 
             $return = "on";
        else $return = "off";
        return $return;
    }

    function getConfig_DoNotDisturb($extension)
    {
        $return = false;
        $r = $this->AMI_Command("database get DND $extension");
        if ($r != false && strpos($r,"Value: ") !== false) 
                $return = "on";
        else $return = "off";
        return $return;
    }

    function getConfig_CallForwarding($extension)
    {
            $return = array();
            $r = $this->AMI_Command("database get CF $extension");
            if($r != false && strpos($r,"Value: ") !== false){ 
               $return["enable"] = "on";
               $arrData = explode(":",$r);
               $return["phoneNumber"] = trim($arrData[2]);
            }else $return["enable"] = "off";
            return $return;
    }

    function getConfig_CallForwardingOnUnavail($extension)
    {
            $return = array();
            $r = $this->AMI_Command("database get CFU $extension");
            if($r != false && strpos($r,"Value: ") !== false){ 
               $return["enable"] = "on";
               $arrData = explode(":",$r);
               $return["phoneNumber"] = trim($arrData[2]);
            }else $return["enable"] = "off";
            return $return;
    }

    function getConfig_CallForwardingOnBusy($extension)
    {
            $return = array();
            $r = $this->AMI_Command("database get CFB $extension");
            if($r != false && strpos($r,"Value: ") !== false){ 
               $return["enable"] = "on";
               $arrData = explode(":",$r);
               $return["phoneNumber"] = trim($arrData[2]);
            }else $return["enable"] = "off";
            return $return;
    }
    //database get AMPUSER/10004 cidname
    function getExtensionCID($extension)
    {
        $return = false;
        $r = $this->AMI_Command("database get AMPUSER/$extension cidname");
         if ($r != false && strpos($r,"Value: ") !== false){
             $arrData = explode(":",$r);
             $return  = $cidname = trim($arrData[2]);
        }
        return $return;
    }

    /*Recordings*/
    function setRecordSettings($exten,$state_in,$state_out) 
    {
      $technology = $this->getTechnology($exten);
      $return = false;
      $rSuccess = false;
      $value_opt= "out=".$state_out."|in=".$state_in;
      $r = $this->AMI_Command("database put AMPUSER $exten/recording $value_opt\r\n\r\n");
      if($r) $rSuccess = (bool)strstr($r, "success");
      $recordSettingsTypeIn["record_in"]  = $state_in;
      $recordSettingsTypeOut["record_out"] = $state_out;
      $statusIN = $this->updateDatabaseAsterisk($technology,$exten,$recordSettingsTypeIn);
      $stateOut = $this->updateDatabaseAsterisk($technology,$exten,$recordSettingsTypeOut);
      if($statusIN && $stateOut && $rSuccess)
        $return = true;
      return $return;

    }

    function getTechnology($extension)
    {    $technology = null;
         $r = $this->AMI_Command("database get DEVICE/$extension dial\r\n\r\n");
         if($r != false && strpos($r,"Value: ") !== false){
            $arrData              = explode(":",$r);//i.e: Value: SIP/408
            $type_Extension       = trim($arrData[2]);//i.e: SIP/408
            $arrDataTech          = explode("/",$type_Extension);
            $technology           = strtolower(trim($arrDataTech[0]));//i.e: SIP/408
         }
         return $technology;
    }

    function getRecordSettings($extension)
    {
      $return = array();
      $r = $this->AMI_Command("database get AMPUSER $extension/recording");
      if($r != false && strpos($r,"in=Always") !== false){
        $return['record_in'] = 'Always';
      }
      else if($r != false && strpos($r,"in=Never") !== false){
        $return['record_in'] = 'Never';
      }
      else if($r != false && strpos($r,"in=Adhoc") !== false){
        $return['record_in'] = 'Adhoc';
      }
      if($r != false && strpos($r,"out=Always") !== false){
        $return['record_out'] = 'Always';
      }
      else if($r != false && strpos($r,"out=Never") !== false){
        $return['record_out'] = 'Never';
      }
      else if($r != false && strpos($r,"out=Adhoc") !== false){
        $return['record_out'] = 'Adhoc';
      }
      return $return;
    }

/**********/
}
?>
