<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                               |
  | http://www.elastix.com                                               |
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
  $Id: paloSantoPeersInformation.class.php,v 1.1 2008-08-03 11:08:42 Andres Flores aflores@palosanto.com Exp $ */
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
class paloSantoPeersInformation {
    var $_DB;
    var $errMsg;

    function paloSantoPeersInformation(&$pDB)
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

    /*HERE YOUR FUNCTIONS*/
/*    
	function addInformationPeer($dataPeer)
    {
        $arrTmp = array();
        foreach($dataPeer as $key => $value)
           $arrTmp[$key] = $this->_DB->DBCAMPO($value);   

        $queryInsert = $this->_DB->construirInsert('peer', $arrTmp);
        $result = $this->_DB->genQuery($queryInsert);
        return $result;
    }

    function getNameCertificate($mac)
    {
        $root_certicate = "/var/lib/asterisk/keys";
        $macCertificate = "CER".str_replace(":","",$mac);
        if(file_exists("$root_certicate/$macCertificate.pub")){
           return $macCertificate;
        }else 
           return "No Found";
    }
*/
    function  addInfoRequest($mac, $ip, $company, $comment, $certificate,$key)
    {

        $data = array($mac,$ip,$certificate,$key,$comment,$company);

        $query = "INSERT INTO peer(mac, model, host, inkey , outkey, status, his_status, key, comment, company)".
            "VALUES(?,'symmetric',?,?,'','Requesting connection','waiting response',?,?,?)";

        $result=$this->_DB->genQuery($query, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

   function UpdateOutKey($certificate ,$peerId)
   {
      $data = array($certificate, $peerId);
      $query = "UPDATE peer SET outkey=? where id=?";
      $result=$this->_DB->genQuery($query, $data);
      if($result==FALSE)
      {
          $this->errMsg = $this->_DB->errMsg;
          return array();
      }
      return $result;  
   }

   function hostExist($mac)
   {
      $data = array($mac);
      $query = "SELECT host FROM peer where mac=?";
      $result=$this->_DB->getFirstRowQuery($query, true, $data);
      if($result==FALSE)
      {
         $this->errMsg = $this->_DB->errMsg;
         return array();
      }
      return $result;
   }
/*
    function uploadInformationPeer($data, $where)
    {

        $arrTmp = array();
        foreach($data as $key => $value)
           $arrTmp[$key] = $this->_DB->DBCAMPO($value);

        $queryInsert = $this->_DB->construirUpdate("peer", $arrTmp, "id=$where");
        $result = $this->_DB->genQuery($queryInsert);
        return $result;
    }
*/
    function addInformationParameter($dataParameter, $id)
    {
       $arrTmp = array();
       $data = array();
       $result = "";

       foreach($dataParameter as $name => $value)
       {
         $data[0] = $name;
         $data[1] = $value;
         $data[2] = $id;
         $queryInsert = "INSERT INTO parameter(name, value, id_peer) VALUES(?, ?, ?)";
         $result = $this->_DB->genQuery($queryInsert, $data);
         //hay que manejar el error en el caso de que no pueda insertar

       }
       return $result;
    }

   function createPeerParameter()
   {
     $dataParameter = array();
     //$dataParameter["'precache'"]  = "outbound";
     $dataParameter["include"]   = "priv";
     $dataParameter["permit"]    = "priv";
     $dataParameter["quality"]   = "yes";
     $dataParameter["order"]     = "primary";
     return $dataParameter;
   }

    private function obtainForeignKey($idPeer)
    {
        $data = array($idPeer);
        $query = "SELECT inkey FROM peer WHERE id=?";
        $result=$this->_DB->getFirstRowQuery($query, true, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result['inkey'];
    }

    function deleteInformationPeer($idPeer)
    {
        // obtain the foreign public key
        $nameKey = $this->obtainForeignKey($idPeer);
        $root = "/var/lib/asterisk/keys/".$nameKey.'.pub';
        $data = array($idPeer);
        $fileName = basename($root);
        $dirFile  = "/var/lib/asterisk/keys/$fileName";
        if(is_file($dirFile))
            unlink($dirFile);
        $query  = "DELETE FROM peer WHERE id=?";
        $result = $this->_DB->genQuery($query, $data);
        return $result;
    }

    function deleteInformationParameter($idPeer)
    {
        $data = array($idPeer);
        $query = "DELETE FROM parameter WHERE id_peer=?";
        $result = $this->_DB->genQuery($query, $data);
        return $result;
    }

    function getHostStatus($host)
    {
      $host_remote = $this->_DB->DBCAMPO($host);
      $data = array($host);
      $query = "SELECT * FROM peer where host=? and (status='waiting response' or status='request accepted' or status='connected' or status='disconnected' or status='request delete')" ;
      $result=$this->_DB->getFirstRowQuery($query, true, $data);
      if($result==FALSE)
      {
         $this->errMsg = $this->_DB->errMsg;
         return false;
      }
      return true;
    }

    function getIdPeer($MAC=null)
    {
      $where = "";
      $tmpMac = "";
      $query = "SELECT id FROM peer";
      if($MAC != null){
         $tmpMac =  $this->_DB->DBCAMPO($MAC);
         $query .= " WHERE mac=$tmpMac";
      }

      $result=$this->_DB->getFirstRowQuery($query, true);
      if($result==FALSE)
      {
         $this->errMsg = $this->_DB->errMsg;
         return array();
      }
      return $result;
    }

    function getIdPeerbyRemoteHost($host)
    {
        $data = array($host);
        $query = "SELECT id from peer where host=?";
        $result=$this->_DB->getFirstRowQuery($query, true, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return $result['id'];
    }

    //Funcion que crea el archivo dundi_peers_custom_elastix.conf
    function createFileDPCE($peers,$arrLang)
    {
       $dundi_file = "/etc/asterisk/dundi_peers_custom_elastix.conf";
       $fh = fopen($dundi_file, "w+");
       if($fh){
         if(fwrite($fh, $peers) == false){
		   $this->errMsg = $arrLang["Unabled write file"];
           fclose($fh);
           return false;
         }
       }else{
          $this->errMsg = $arrLang["Unabled open file"];
          return false;
       }
       return true;
    }

    function ObtainNumPeersInformation()
    {
        //Here your implementation
        $query   = "SELECT COUNT(*) FROM peer";

        $result=$this->_DB->getFirstRowQuery($query);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function ObtainPeersInformation($limit=null, $offset=null, $field_pattern=null)
    {
        //Here your implementation
        $query   = "SELECT * FROM peer";

        $result=$this->_DB->fetchTable($query, true);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function StatusDisconnect($peerId)
    {
        $data = array($peerId);
        $query = "UPDATE peer SET status ='disconnected' WHERE id=?";
        $result=$this->_DB->genQuery($query, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function StatusConnect($peerId)
    {
        $data = array($peerId);
        $query = "UPDATE peer SET status ='connected' WHERE id=?";
        $result=$this->_DB->genQuery($query, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function hisStatusConnect($ip_answer, $action)
    {
        $his_status = "";
        if($action == 5)
            $his_status = "connected";
        else if($action == 6)
                    $his_status = "disconnected";
        $data   = array($his_status, $ip_answer);
        $query  = "UPDATE peer SET his_status=? WHERE host=?";
        $result = $this->_DB->genQuery($query, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

   function UpdateInfoRequest($ip, $mac, $key, $company, $comment)
   {
     $macCertificate = "CER".str_replace(":","",$mac);
     $data = array($mac, $macCertificate, $key, $comment, $company, $ip);

     $query = "UPDATE peer SET mac=?, inkey=?, status='request accepted',his_status='disconnected', key=?, comment=?, company=? where host=? and status='waiting response'";

     $result=$this->_DB->genQuery($query, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;

   }

   function UpdateInfoForReject($ip_answer, $action)
   {
      $status = "";
      $his_status = "";
      if($action == 3){
         $status = "request reject";
         $his_status = "connection rejected";
      }
      else{ if($action == 4){
                $status = "request delete";
                $his_status = "connection deleted";
           }
      }
      $data = array($status, $his_status, $ip_answer);
      $query = "UPDATE peer SET status=?, his_status=? where host=?";

      $result=$this->_DB->genQuery($query, $data);
      if($result==FALSE)
      {
          $this->errMsg = $this->_DB->errMsg;
          return array();
      }
      return $result;
   }
   //crea la clave publica del peer del quien esta solicitando la coneccion
   function createKeyPubServer($key_answer, $mac_answer)
   {
     // verificar el certificado $key_answer
     if(!preg_match("/^\w{2}:\w{2}:\w{2}:\w{2}:\w{2}:\w{2}$/",$mac_answer)){ //00:16:76:42:78:2F
        echo "Unabled write file";
        return;
     }

     if(!$this->keyIsValid($key_answer))
        return;

     $pub_key = "CER".str_replace(":","",$mac_answer);
     $dir = "/var/lib/asterisk/keys/$pub_key.pub";
     $fileName = basename($dir);
     $fh = fopen("/var/lib/asterisk/keys/$fileName", "w+");
     if($fh){
        if(fwrite($fh, "$key_answer") == false){
           echo "Unabled write file";
           fclose($fh);
         }
     }else
           echo "Unabled open file";
   }

    // Se usa en módulo general_information
    function GenKeyPub($company)
    {
      $root = "/var/lib/asterisk/keys";
      exec("/usr/sbin/astgenkey -q -n $root/$company",$arrReg,$arrFlag);
      if($arrFlag == 0)
        return true;
      else
        return false;
    }

    private function keyIsValid($key)
    {
        // primero verificar si la primera linea contiene -----BEGIN PUBLIC KEY----- | -----BEGIN RSA PRIVATE KEY-----
        if(!preg_match("/^(.|\n)*-----BEGIN PUBLIC KEY-----(.|\n)*$/",$key)){
            echo "No certificate valid";
            return FALSE;
        }
        $tmp = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        // segundo verificar si la segunda linea contiene -----END PUBLIC KEY----- | -----END RSA PRIVATE KEY-----
        if(!preg_match("/^(.|\n)*-----END PUBLIC KEY-----(.|\n)*$/",$key)){
            echo "No certificate valid";
            return FALSE;
        }
        $tmp = str_replace("-----END PUBLIC KEY-----", "", $tmp);
        $tmp = str_replace("\n", "", $tmp);
        // tercero hacer un decode_base64 de lo que se encuentre entre -----BEGIN PUBLIC KEY----- y -----END PUBLIC KEY-----
        $tmpDecode = base64_decode($tmp);
        if($tmpDecode){// cuarto si no hubo error anterior de ese salida del paso 3 hacer un encode_base64
            $tmpEncode = base64_encode($tmpDecode);// quinto comparar la salida del paso cuatro con $key 
            if($tmpEncode == $tmp)
                return TRUE;
            else
                return FALSE;
        }else{ 
            echo "No certificate valid";
            return FALSE;
        }
    }

 
    function ObtainPeersDataById($id)
    {
        //Here your implementation
        $data = array($id);
        $query   = "SELECT * FROM peer WHERE id=?";

        $result=$this->_DB->getFirstRowQuery($query, true, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getSecret()
    {
        $query   = "SELECT secret FROM general WHERE id=1";

        $result=$this->_DB->getFirstRowQuery($query, true);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result['secret'];
    }

    function ObtainPeersParametersById($id)
    {
        //Here your implementation
        $data    = array($id);
        $query   = "SELECT name, value FROM parameter WHERE id_peer=?";
        $result  = $this->_DB->fetchTable($query, true, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

   function AddServerRequest($ip, $mac, $status, $certificate)
   {
      $data = array($ip, $certificate, $status);
      $query = "INSERT INTO peer(mac,model,host,inkey,outkey,status,his_status)VALUES('','symmetric',?,'',?,?,'Requesting connection')";
      $result=$this->_DB->genQuery($query, $data);
        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
   }

    function reloadAsterisk($dsn_agi_manager)
    {
        $arrResult = $this->AsteriskManager_Command($dsn_agi_manager['host'], $dsn_agi_manager['user'], $dsn_agi_manager['password'], "reload");
    }

    private function AsteriskManager_Command($host, $user, $password, $command) {
        global $arrLang;
        $astman = new AGI_AsteriskManager( );
        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = _tr("Error when connecting to Asterisk Manager");
        } else{
            $salida = $astman->Command("$command");
            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["data"]);
            }
        }
        return false;
    }

}
?>
