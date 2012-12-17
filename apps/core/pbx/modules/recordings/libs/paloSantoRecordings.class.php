<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                               |
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
  $Id: default.conf.php,v 1.1 2008-06-12 09:06:35 afigueroa Exp $ */

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

    include_once "libs/paloSantoACL.class.php";
    include_once "libs/paloSantoAsteriskConfig.class.php";
    include_once "libs/paloSantoPBX.class.php";

class paloSantoRecordings extends paloAsteriskDB{
    var $_DB; //conexion base de mysql elxpbx
    var $errMsg;
    protected $code;
    protected $domain;

     function paloSantoRecordings(&$pDB,$domain)
    {
       parent::__construct($pDB);
       
        if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
            $this->errMsg="Invalid domain format...";
        }else{
            $this->domain=$domain;

            $result=$this->getCodeByDomain($domain);
            if($result==false){
                $this->errMsg .=_tr("Can't create a new instace of paloSantoRecording").$this->errMsg;
            }else{
                $this->code=$result["code"];
            }
        }
    }
    
    function getNumRecording($domain=null){
		$where="";
		$arrParam=null;

		if((isset($domain))&&($domain!="src_custom")){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format.";
				return false;
			}else{
				$where="where organization_domain=?";
				$arrParam=array($domain);
			}
		}
		if($domain=="src_custom"){
		    $where = " where source=?";
		    $arrParam=array("custom");
		}
		
		$query="SELECT count(uniqueid) from recordings $where";
		$result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
               if($result==false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result[0];
    }

    function getRecordings($domain=null,$limit=null,$offset=null){
		$where="";
		$pagging="";
		$arrParam=null;
		if((isset($domain))&&($domain!="src_custom")){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format....";
				return false;
			}else{
				$where="where organization_domain=? ";
				$arrParam=array($domain);
			}
		}
	      
		if(isset($limit) && isset($offset)){
		    $pagging=" limit ? offset ?";
		    $arrParam[]=$limit;
		    $arrParam[]=$offset;
		}

		if($domain=="src_custom"){
		    $where = " where source=?";
		    $arrParam=array("custom");
		}
		
		$query="SELECT * from recordings $where ORDER BY uniqueid DESC $pagging ";
                
		$result=$this->_DB->fetchTable($query,true,$arrParam);
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result;
    }
    
    function convertMP3toWAV($base,&$tmpFile,$file_sin_ext,$prep){
        $output = $ret = NULL;
        
        $tmp=$tmpFile;
        $tmpFile=$prep."_".$file_sin_ext.".wav";
        //mpg123 -w outputFile inputFile
	
        exec("mpg123 -w $base/$tmpFile $base/$tmp", $output, $ret);
	
        if ($ret != 0) {
            unlink("$base/$tmp");
            $this->errMsg = implode('', $output);
            return FALSE;
        }else{
            unlink("$base/$tmp");
            return TRUE;
        }
    }

    function getTipeOfFile($file){
	$mime_type="";
        $finfo = new finfo(FILEINFO_MIME, "/usr/share/misc/magic.mgc");
        if(is_file($file)){
            $mime_type = $finfo->file($file);
        }else{
            $this->errMsg = _tr("File doens't exist ").$file;
            return false;
        }
        return $mime_type;
    }
    
     function resampleMoHFiles($base,$tmpFile,$filename){
      //  sox inputFile -r 8000 -c 1 outputFile
        $output = $ret = NULL;		
	exec("sox $base/$tmpFile -r 8000 -c 1 $base/$filename", $output, $ret);
        if ($ret != 0) {
            unlink("$base/$tmpFile");
            $this->errMsg = implode('', $output);
            return FALSE;
        }else{
            unlink("$base/$tmpFile");
            return TRUE;
        }
    }

    function getRecordingsByUser($domain,$extUser){
		$where="";
		$arrParam=null;
		if(isset($domain)){
			if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/", $domain)){
				$this->errMsg="Invalid domain format..";
				return false;
			}else{
				$where="where organization_domain=? and source=?";
				$arrParam=array($domain,$extUser);
			}
		}

		$query="SELECT * from recordings $where";
                
		$result=$this->_DB->fetchTable($query,true,$arrParam);
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return false;
		}else
			return $result;
    }

     function getRecordingById($id,$userLevel1)
    {
        $dom = $this->domain;
	if($userLevel1=="admin"){
	    $query = "SELECT filename,name FROM recordings WHERE uniqueid=? and organization_domain=?";
	    $result = $this->_DB->getFirstRowQuery($query, TRUE, array($id,$dom));

	}
	elseif($userLevel1=="superadmin"){
	    $query = "SELECT filename,name FROM recordings WHERE uniqueid=?";
	    $result = $this->_DB->getFirstRowQuery($query, TRUE, array($id));
	}

//	$result = $this->_DB->getFirstRowQuery($query, TRUE, array($id,$dom));
        
	if($result != FALSE)
            return $result;
        else{
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
    }


    function Obtain_Extension_Current_User($arrConf)
    {
        $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
        $pACL = new paloACL($pDB_acl);
        $username = $_SESSION["elastix_user"];
        $extension = $pACL->getUserExtension($username);
        if(is_null($extension))
            return false;
        else return $extension;
    }


    function  Obtain_Protocol_Current_User($arrConf,$domain,$extension){
	$pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
	$pACL = new paloACL($pDB_acl);
	$username = $_SESSION["elastix_user"];
	//$extension = $pACL->getUserExtension($username);
        $arr_result2=array();
	$query2="SELECT id, exten, organization_domain, tech, dial, voicemail, device FROM extension where exten=? and  organization_domain=?";
	$arr_result2 = $this->_DB->getFirstRowQuery($query2,true,array($extension,$domain));
	if (!is_array($arr_result2) || count($arr_result2)==0) {
	    $this->errMsg = _tr("Can't get extension user").$this->_DB->errMsg;
	}
	return $arr_result2;
    }


    function Call2Phone($data_connection, $origen, $channel, $description, $recording_name)
    {
        $command_data['origen'] = $origen;
        $command_data['channel'] = $channel;
        $command_data['description'] = $description;
	$command_data["recording_name"]=$recording_name;
        return $this->AsteriskManager_Originate($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);
    }

     function hangupPhone($data_connection, $origen, $channel, $description)
    {
        $command_data['origen'] = $origen;
        $command_data['channel'] = $channel;
        $command_data['description'] = $description;
	return $this->AsteriskManager_Hangup($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);
    }

    //Verificamos el estado del channel, para saber si ha colgado o no.
    function callStatus($channelName){
	    $status ="hangup";
	    $arrChannel = explode("/", $channelName);
	    $pattern = "/^".$arrChannel[0]."\/".$arrChannel[1]."/";
	    exec("/usr/sbin/asterisk -rx 'core show channels concise'", $output, $retval);
	    
            if(count($output)==0){
	      $status ="hangup";
	    }else{
		foreach($output as $linea) {
		  if(preg_match($pattern, $linea, $arrReg)){
	             $status = "recording";
         	  }
		  
		}
	    }
		  
	  return $status;
   }
  
    function AsteriskManager_Originate($host, $user, $password, $command_data) {
        global $arrLang;
	
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
          //
            $parameters = $this->Originate($command_data['origen'], $command_data['channel'],$command_data['description'], $command_data["recording_name"]);

            $salida = $astman->send_request('Originate', $parameters);
           
            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

     function AsteriskManager_Hangup($host, $user, $password, $command_data) {
        global $arrLang;
        $astman = new AGI_AsteriskManager();
	$channel = "";
        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
          //
            $channelName = $command_data['channel'];
	    $arrChannel = explode("/", $channelName);
	    $pattern = "/^".$arrChannel[0]."\/".$arrChannel[1]."/";
	    exec("/usr/sbin/asterisk -rx 'core show channels concise'", $output, $retval);
            foreach($output as $linea) {
               if(preg_match($pattern, $linea, $arrReg)){
                        $arr = explode("!", $linea);
                        $channel = $arr[0];
                         
	       }else{
			$channel = $channelName;
	       }
	       
            }
	    $parameters = array('Channel'=>$channel);
	   
            $salida = $astman->send_request('Hangup',$parameters);
	    $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }


    function Originate($origen, $channel="", $description="", $recording_name)
    {
        $parameters = array();
        $parameters['Channel']      = $channel;
        $parameters['CallerID']     = "$description <$origen>";
        $parameters['Context']      = "";
        $parameters['Priority']     = 1;
        $parameters['Application']  = "Record";
        $parameters['Data']         = "/var/lib/asterisk/sounds/".$this->domain."/system/$recording_name.wav,,,k";
        return $parameters;
    }

  

    function Obtain_Protocol_from_Ext($dsn, $id)
    {
        $pDB = new paloDB($dsn);

        $query = "SELECT dial, description FROM devices WHERE id=$id";
        $result = $pDB->getFirstRowQuery($query, TRUE);
        if($result != FALSE)
            return $result;
        else{
            $this->errMsg = $pDB->errMsg;
            return FALSE;
        }
    }

    function createNewRecording($name,$filename,$source,$domain)
    {
        
        
        $query="INSERT INTO recordings (name,filename,organization_domain,source) values (?,?,?,?)";
        $result=$this->_DB->genQuery($query,array($name,$filename,$domain,$source));
        if($result==false){
          $this->errMsg=$this->_DB->errMsg;
            return false;
        }else
	    return true; 
    }

    function checkFilename($filename)
    {
        $query = "SELECT uniqueid FROM recordings WHERE filename like ?";
        $result=$this->_DB->getFirstRowQuery($query,false,array($filename));
      
        if(count($result)==0)
		return TRUE;
	else
		return FALSE;
    }


    function getId($name,$source)
    {
	$dom = $this->domain;
        $query = "SELECT uniqueid FROM recordings WHERE name=? and source=? and organization_domain=?";
     	$result = $this->_DB->getFirstRowQuery($query, TRUE, array($name,$source,$dom));
        
	if($result != FALSE)
            return $result;
        else{
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
    }

    function deleteRecordings($records,$domain,$userLevel1)
    {
	$retval = 0;
	$err = 0;
        $where="where ";
	$arrRec = array();
        $i=0;$arrComando="";
	//$bExito = true;
	foreach($records as $rec){
	   $i++;
	   $pieces = explode(",", $rec);
          
	   if($i==count($records))
                $where .= "filename = ?";
	   else
		$where .= "filename = ? or "; 
	  
	if($userLevel1=="admin"){
	    if($pieces[0]=="system")
		  $val = "/var/lib/asterisk/sounds/".$domain."/".$pieces[0]."/".$pieces[1];
	    else
		  $err = 1;
	}
	elseif($userLevel1=="superadmin")
	    if($pieces[0]=="custom")  
		  $val = "/var/lib/asterisk/sounds/".$pieces[0]."/".$pieces[1];
	    else
		  $err = 1;
	array_push($arrRec,$val); 
	}   

    if($err == 1){
       $this->errMsg=_tr("You are not authorized to perform this action").$this->_DB->errMsg;
       return false;
    }
    else{
     
	$arrTmp = array();
	foreach($arrRec as $arr){
	    if(file_exists($arr)){
		if(rename($arr,$arr.".old")==false)
		   $retval = 1;
	    }
	    
	}
	
	if($retval!=0)
	    return false;
	else{
	  //  $bExito=true;
	      
	    $queryD="DELETE from recordings $where";
	    $result=$this->_DB->genQuery($queryD,$arrRec);
	    
           
	    if($result==false){
	      $this->errMsg=_tr("Error Deleting Recordings ").$this->_DB->errMsg;
	      foreach($arrRec as $arr){
		  if(file_exists($arr.".old")){ 
		       $comando="mv $arr.old $arr";
		       exec($comando, $output, $retval);
		  }
	      }
	      return false;
	    }else{
	      foreach($arrRec as $arr){
		  if(file_exists($arr.".old")){ 
		       $comando="rm -f $arr.old ";
		       exec($comando, $output, $retval);
		  }
	      }
	      return true;

            }
       	} 
       }
     }
    
}
?>
