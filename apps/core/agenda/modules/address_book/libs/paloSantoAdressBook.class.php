<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/01/31 21:31:55 bmacias Exp $
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/06/25 16:51:50 afigueroa Exp $
  $Id: index.php,v 1.1 2010/02/04 09:20:00 onavarrete@palosanto.com Exp $
 */

//ini_set("display_errors", true);
if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}
global $arrConf; 
//include_once("$arrConf[basePath]/libs/paloSantoACL.class.php");

class paloAdressBook {
    var $_DB;
    var $errMsg;

    function paloAdressBook(&$pDB)
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

/*
This function obtain all records in the table, but, if the param $count is passed as true the function only return
a array with the field "total" containing the total of records.
*/
    function getAddressBook($limit=NULL, $offset=NULL, $field_name=NULL, $field_pattern=NULL, $count=FALSE, $iduser=NULL)
    {
	//Defining the fields to get. If the param $count is true, then we will get the result of the sql function count(), else, we will get all fields in the table.
	$fields=($count)?"count(id) as total":"*";
	//Begin to build the query.
        $query   = "SELECT $fields FROM contact ";
        $strWhere = "";
	$arrParams = array();
        if(!is_null($field_name) and !is_null($field_pattern)){
	    $arrFilters = array("id","name","last_name","telefono","extension","email","iduser","address","company","notes","status");
	    if(!in_array($field_name,$arrFilters))
		$field_name = "id";
	    $arrParams[] = $field_pattern;
	    $arrParams[] = $iduser;
            $strWhere .= " $field_name like ? and (iduser=? or status='isPublic') ";
	    if($field_name=="telefono"){
		$strWhere .= " or extension like ? and (iduser=? or status='isPublic') ";
		$arrParams[] = $field_pattern;
		$arrParams[] = $iduser;
	    }
	}
        // Clausula WHERE aqui
        if(!empty($strWhere)) $query .= "WHERE $strWhere ";
        //else   $query .= "WHERE $strWhere";
        //ORDER BY
        $query .= " ORDER BY last_name, name";

        // Limit
        if(!is_null($limit)){
	    $limit = (int)$limit;
            $query .= " LIMIT $limit ";
	}

	if(!is_null($offset) and $offset > 0){
	    $offset = (int)$offset;
	    $query .= " OFFSET $offset";
	}
        $result=$this->_DB->fetchTable($query, true, $arrParams);

        return $result;
    }

    function getAddressBookByCsv($limit=NULL, $offset=NULL, $field_name=NULL, $field_pattern=NULL, $count=FALSE, $iduser=NULL)
    {
	//Defining the fields to get. If the param $count is true, then we will get the result of the sql function count(), else, we will get all fields in the table.
	$fields=($count)?"count(id) as total":"*";

	//Begin to build the query.
        $query   = "SELECT $fields FROM contact ";

        $strWhere = "";
	$arrParams = array();
        if(!is_null($field_name) and !is_null($field_pattern)){
	    $arrFilters = array("id","name","last_name","telefono","extension","email","iduser","address","company","notes","status");
	    if(!in_array($field_name,$arrFilters))
		$field_name = "id";
	    $arrParams[] = $field_pattern;
	    $arrParams[] = $iduser;
            $strWhere .= " $field_name like ? and (iduser=?  or status='isPublic') ";
	    if($field_name=="telefono"){
		$strWhere .= " or extension like ? and (iduser=?  or status='isPublic') ";
		$arrParams[] = $field_pattern;
		$arrParams[] = $iduser;
	    }
	}

        // Clausula WHERE aqui
        if(!empty($strWhere)) $query .= "WHERE $strWhere ";
        else{
	    $query .= "WHERE iduser=? or status='isPublic'";
	    $arrParams[] = $iduser;
	}

        //ORDER BY
        $query .= " ORDER BY last_name, name";

        // Limit
        if(!is_null($limit)){
	    $limit = (int)$limit;
            $query .= " LIMIT $limit ";
	}

	if(!is_null($offset) and $offset > 0){
	    $offset = (int)$offset;
	    $query .= " OFFSET $offset";
	}
        $result=$this->_DB->fetchTable($query, true, $arrParams);

        return $result;
    }

    function contactData($id, $id_user)
    {
        $params = array($id, $id_user);
        $query   = "SELECT * FROM contact WHERE id=? and (iduser=? or status='isPublic')";

        //$strWhere = "id=$id";

        // Clausula WHERE aqui
        //if(!empty($strWhere)) $query .= "WHERE $strWhere ";

        $result=$this->_DB->getFirstRowQuery($query, true, $params);
        if(!$result && $result==null && count($result) < 1)
            return false;
        return $result;
    }

    function addContact($data)
    {
        //$queryInsert = $this->_DB->construirInsert('contact', $data);
        $queryInsert = "insert into contact(name,last_name,telefono,email,iduser,picture,address,company,notes,status) values(?,?,?,?,?,?,?,?,?,?)";
        $result = $this->_DB->genQuery($queryInsert, $data);

        return $result;
    }

    function addContactCsv($data)
    {
        //$queryInsert = $this->_DB->construirInsert('contact', $data);
        $queryInsert = "insert into contact(name,last_name,telefono,email,iduser,address,company,status) values(?,?,?,?,?,?,?,?)";
        $result = $this->_DB->genQuery($queryInsert, $data);

        return $result;
    }

    function updateContact($data,$id)
    {
        //$queryUpdate = $this->_DB->construirUpdate('contact', $data,$where);
//        die($queryUpdate);
        $queryUpdate = "update contact set name=?, last_name=?, telefono=?, email=?, iduser=?, picture=?, address=?, company=?, notes=?, status=?  where id=?";
	$data[] = $id;
        $result = $this->_DB->genQuery($queryUpdate, $data);

        return $result;
    }

    function existContact($name, $last_name, $telefono)
    {
        $query =     " SELECT count(*) as total FROM contact "
                    ." WHERE name=? and last_name=?"
                    ." and telefono=?";
	$arrParam = array($name,$last_name,$telefono);
        $result=$this->_DB->getFirstRowQuery($query, true, $arrParam);
        if(!$result)
            $this->errMsg = $this->_DB->errMsg;
        return $result;
    }

    function deleteContact($id, $id_user)
    {
        $params = array($id, $id_user);
        $query = "DELETE FROM contact WHERE id=? and iduser=?";
        $result = $this->_DB->genQuery($query, $params);
        if($result[0] > 0)
            return true;
        else return false;
    }

    function Call2Phone($data_connection, $origen, $destino, $channel, $description)
    {
        $command_data['origen'] = $origen;
        $command_data['destino'] = $destino;
        $command_data['channel'] = $channel;
        $command_data['description'] = $description;
        return $this->AsteriskManager_Originate($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);
    }

    function TranferCall($data_connection, $origen, $destino, $channel, $description)
    {
        exec("/usr/sbin/asterisk -rx 'core show channels concise' | grep ^$channel",$arrConsole,$flagStatus);
        if($flagStatus == 0){
            $arrData = explode("!",$arrConsole[0]);
            $command_data['origen']  = $origen;
            $command_data['destino'] = $destino;
            $command_data['channel'] = $arrData[11]; // $arrData[0] tiene mi canal de conversa, $arrData[11] tiene el canal con quies estoy conversando
            $command_data['description'] = $description;
            return $this->AsteriskManager_Redirect($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);
        }
        return false;
    }

    function AsteriskManager_Redirect($host, $user, $password, $command_data) {
        global $arrLang;
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
            $salida = $astman->Redirect($command_data['channel'], "", $command_data['destino'], "from-internal", "1");

            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

    function AsteriskManager_Originate($host, $user, $password, $command_data) {
        global $arrLang;
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = $arrLang["Error when connecting to Asterisk Manager"];
        } else{
            $parameters = $this->Originate($command_data['origen'], $command_data['destino'], $command_data['channel'], $command_data['description']);

            $salida = $astman->send_request('Originate', $parameters);

            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
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

    function Obtain_Protocol_from_Ext($dsn, $id)
    {
        $pDB = new paloDB($dsn);

        $query = "SELECT dial, description FROM devices WHERE id=?";
        $result = $pDB->getFirstRowQuery($query, TRUE, array($id));
        if($result != FALSE)
            return $result;
        else{
            $this->errMsg = $pDB->errMsg;
            return FALSE;
        }
    }

    function getDeviceFreePBX($dsn, $limit=NULL, $offset=NULL, $field_name=NULL, $field_pattern=NULL,$count=FALSE)
    {
        //Defining the fields to get. If the param $count is true, then we will get the result of the sql function count(), else, we will get all fields in the table.
        $fields=($count)?"count(id) as total":"*";

        //Begin to build the query.
        $query   = "SELECT $fields FROM devices ";

        $strWhere = "";
	$arrParam = array();
        if(!is_null($field_name) and !is_null($field_pattern))
        {
            if($field_name=='name'){
                $strWhere .= " description like ? ";
		$arrParam[] = $field_pattern;
	    }
            else if($field_name=='telefono'){
                $strWhere .= " id like ? ";
		$arrParam[] = $field_pattern;
	    }
        }

        // Clausula WHERE aqui
        if(!empty($strWhere)) $query .= "WHERE $strWhere ";

        //ORDER BY
        $query .= " ORDER BY  description";

        // Limit
        if(!is_null($limit)){
	    $limit = (int)$limit;
            $query .= " LIMIT $limit ";
	}

        if(!is_null($offset) and $offset > 0){
	    $offset = (int)$offset;
            $query .= " OFFSET $offset";
	}


        $pDB = new paloDB($dsn);
        if($pDB->connStatus)
            return false;
        $result = $pDB->fetchTable($query,true,$arrParam); //se consulta a la base asterisk

        return $result;
    }

    function getMailsFromVoicemail()
    {
        $result = array();
        $path = "/etc/asterisk/voicemail.conf";
        $lines = file($path);
        foreach($lines as $line)
        {
            if(eregi("([[:alnum:]]*) => ",$line, $regs))
            {
                $arrVal = explode(",", $line);
                $result[$regs[1]] = $arrVal[2];
            }
        }
        return $result;
    }

    function isEditablePublicContact($id, $id_user){
        $params = array($id, $id_user);
        $query   = "SELECT * FROM contact WHERE id=? and iduser=? ";

        //$strWhere = "id=$id";

        // Clausula WHERE aqui
        //if(!empty($strWhere)) $query .= "WHERE $strWhere ";

        $result=$this->_DB->getFirstRowQuery($query, true, $params);
        if(!$result && $result==null && count($result) < 1)
            return false;
        return $result;
    }

}
?>
