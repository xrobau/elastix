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
    function getAddressBook($iduser, $limit=NULL, $offset=NULL, $field_name=NULL, $field_pattern=NULL, $count=FALSE)
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
            $strWhere .= "($field_name like ? ";
	    if($field_name=="telefono"){
		$strWhere .= " or extension like ?) ";
		$arrParams[] = $field_pattern;
	    }
	    else
		$strWhere .= ") ";
	    $strWhere .= "and (iduser=? or status='isPublic') ";
	    $arrParams[] = $iduser;
	}
        // Clausula WHERE aqui
        if(!empty($strWhere)) $query .= "WHERE $strWhere ";
	else{
	    $query .= "WHERE iduser=? or status='isPublic'";
	    $arrParams[] = $iduser;
	}
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

    function getAddressBookByCsv($iduser, $limit=NULL, $offset=NULL, $field_name=NULL, $field_pattern=NULL, $count=FALSE)
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
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
            return false;
	}
	else
	    return $result;
    }

    function addContact($data,$returnId=false)
    {
        //$queryInsert = $this->_DB->construirInsert('contact', $data);
	$data[] = time();
        $queryInsert = "insert into contact(name,last_name,telefono,email,iduser,picture,address,company,notes,status,last_update) values(?,?,?,?,?,?,?,?,?,?,?)";
        $result = $this->_DB->genQuery($queryInsert, $data);
	if($result==FALSE){
	    $this->errMsg = $this->_DB->errMsg;
            return false;
	}
	$id = $this->getLastInsertId();
	$result = $this->addHistory($id,$data[4],$data[9],"contact",$data[10],"create","A contact was created: id=$id, name=$data[0], last_name=$data[1], telefono=$data[2], email=$data[3], iduser=$data[4], picture=$data[5], address=$data[6], company=$data[7], notes=$data[8], status=$data[9]");
	if($result==FALSE){
	    $this->errMsg = $this->_DB->errMsg;
            return false;
	}
	if($returnId)
	    return $id;
	else
	    return $result;
    }

    function addContactCsv($data)
    {
        //$queryInsert = $this->_DB->construirInsert('contact', $data);
	$data[] = time();
        $queryInsert = "insert into contact(name,last_name,telefono,email,iduser,address,company,status,last_update) values(?,?,?,?,?,?,?,?,?)";
        $result = $this->_DB->genQuery($queryInsert, $data);
	
	$id = $this->getLastInsertId();
	$result = $this->addHistory($id,$data[4],$data[7],"contact",$data[8],"create","A contact was created: id=$id, name=$data[0], last_name=$data[1], telefono=$data[2], email=$data[3], iduser=$data[4], address=$data[5], company=$data[6], status=$data[7]");
	if($result==FALSE){
	    $this->errMsg = $this->_DB->errMsg;
            return false;
	}

        return $result;
    }

    function updateContact($data,$id)
    {
        //$queryUpdate = $this->_DB->construirUpdate('contact', $data,$where);
//        die($queryUpdate);
	$time = time();
        $queryUpdate = "update contact set name=?, last_name=?, telefono=?, email=?, iduser=?, picture=?, address=?, company=?, notes=?, status=?, last_update='$time'  where id=?";
	$data[] = $id;
        $result = $this->_DB->genQuery($queryUpdate, $data);

	$result = $this->addHistory($id,$data[4],$data[9],"contact",$time,"modify","A contact was modified: id=$id, name=$data[0], last_name=$data[1], telefono=$data[2], email=$data[3], iduser=$data[4], picture=$data[5], address=$data[6], company=$data[7], notes=$data[8], status=$data[9]");
	if($result==FALSE){
	    $this->errMsg = $this->_DB->errMsg;
            return false;
	}

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
	$status = $this->getStatus($id,$id_user);
        $params = array($id, $id_user);
        $query = "DELETE FROM contact WHERE id=? and iduser=?";
        $result = $this->_DB->genQuery($query, $params);
        if($result[0] > 0)
            return true;
	$time = time();
	$result = $this->addHistory($id,$id_user,$status,"contact",$time,"delete","A contact was deleted");
	if($result==FALSE){
	    $this->errMsg = $this->_DB->errMsg;
            return false;
	}
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
            $command_data['channel'] = $arrData[12]; // $arrData[0] tiene mi canal de conversa, $arrData[12] tiene el canal con quies estoy conversando
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

    function getLastInsertId(){
        $query = "SELECT id FROM contact order by id desc";
        $result = $this->_DB->getFirstRowQuery($query, TRUE);
        if($result != FALSE || $result != "")
            return $result['id'];
        else
            return false;
    }

    function addHistory($id_register,$id_user,$status,$type,$timestamp,$action,$description)
    {
	$query = "INSERT INTO history (id_register,id_user,status,type,timestamp,action,description) VALUES(?,?,?,?,?,?,?)";
	$result = $this->_DB->genQuery($query,array($id_register,$id_user,$status,$type,$timestamp,$action,$description));
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true; 
    }
    
    function getLastQueueid()
    {
	$query = "SELECT id FROM queues order by id desc";
        $result = $this->_DB->fetchTable($query, TRUE);
        if($result === FALSE || count($result) == 0)
            return 0;
        else{
	    $idNumber = 0;
	    foreach($result as $value){
		$number = explode("-",$value["id"]);
		if($number[2] > $idNumber)
		    $idNumber = $number[2];
	    }
            return $idNumber;
	}
    }

    function addQueue($data,$type,$user)
    {
	$id = $this->getLastQueueid();
	$next = $id + 1;
	$id = "queue-contact-$next";
	$query = "INSERT INTO queues (id,type,user,data,status) VALUES (?,?,?,?,'NEW')";
	$result = $this->_DB->genQuery($query, array($id,$type,$user,$data));
	if($result == FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return $id;
    }

    function getDataTicket($ticket,$id_user)
    {
	$query = "SELECT * FROM queues WHERE id=? AND user=? AND type='contact'";
	$result = $this->_DB->getFirstRowQuery($query, TRUE, array($ticket,$id_user));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return NULL;
	}
	elseif(count($result) == 0)
	    return FALSE;
	else
	    return $result;
    }

    function getContactsAfterSync($last_sync,$contacts,$id_user,$dataResponse)
    {
	$query = "SELECT * FROM contact WHERE last_update > ? AND (iduser=? OR status='isPublic')";
	$result = $this->_DB->fetchTable($query, true, array($last_sync,$id_user));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else{
	    $query = "SELECT * FROM history WHERE timestamp > ? AND action='delete' AND (id_user=? OR status='isPublic') AND type='contact'";
	    $deleted_contacts = $this->_DB->fetchTable($query, true, array($last_sync,$id_user));
	    if($deleted_contacts === FALSE){
		$this->errMsg = $this->_DB->errMsg;
		return FALSE;
	    }
	    foreach($deleted_contacts as $key => $deleted){
		$remove = FALSE;
		foreach($contacts as $contact){
		    if($deleted["id_register"] == $contact->id && $deleted["timestamp"] < $contact->last_update){
			$remove = TRUE;
			break;
		    }
		}
		if(!$remove){
		  $next = count($result);
		  $result[$next]["id"] = $deleted["id_register"];
		  $result[$next]["delete"] = "yes";  
		}
	    }
	    $arrContacts = array();
	    foreach($result as $key => $value){
		$remove = FALSE;
		$isFromClient = FALSE;
		if(is_array($dataResponse) && count($dataResponse) > 0){
		    foreach($dataResponse as $data){
			if($value["id"] == $data->id){
			    $value["id_client"] = $data->id_client;
			    $isFromClient = TRUE;
			    break;
			}   
		    }
		}
		if(!$isFromClient){
		    foreach($contacts as $contact){
			if(isset($contact->id)){
			    if($contact->id == $value["id"]){
				if(isset($contact->id_client))
				    $value["id_client"] = $contact->id_client;
				if(isset($contact->name))
				    if($contact->name != $value["name"])
					break;
				if(isset($contact->last_name))
				    if($contact->last_name != $value["last_name"])
					break;
				if(isset($contact->telefono))
				    if($contact->telefono != $value["telefono"])
					break;
				if(isset($contact->extension))
				    if($contact->extension != $value["extension"])
					break;
				if(isset($contact->email))
				    if($contact->email != $value["email"])
					break;
				if(isset($contact->picture))
				    if($contact->picture != $value["picture"])
					break;
				if(isset($contact->address))
				    if($contact->address != $value["address"])
					break;
				if(isset($contact->notes))
				    if($contact->notes != $value["notes"])
					break;
				if(isset($contact->company))
				    if($contact->company != $value["company"])
					break;
				if(isset($contact->status))
				    if($contact->status != $value["status"])
					break;
				$remove = TRUE;
			    }
			}
		    }
		}
		if(!$remove){
		    if($isFromClient)
                        $value["new"] = "no";
                    else{ // Se verifica si es un registro nuevo para el cliente
                        $query = "SELECT COUNT(*) FROM history WHERE type='contact' AND action='create' AND id_register=? AND (id_user=? OR status='isPublic') AND timestamp > ?";
                        $created = $this->_DB->getFirstRowQuery($query,FALSE,array($value["id"],$id_user,$last_sync));
                        if($created === FALSE){
                            $this->errMsg = $this->_DB->errMsg;
                            return FALSE;
                        }
                        if($created[0] == 0)
                            $value["new"] = "no";
                        else
                            $value["new"] = "yes";
                    }

		    $next = count($arrContacts);

	 	    //TODO: Esto se debe eliminar al corregir en la base cambiando el campo telefono por phone
                    if(isset($value["telefono"])){
                        $value["phone"] = $value["telefono"];
                        unset($value["telefono"]);
                    }
		
		    $arrContacts[$next] = $value;
		    if(!isset($arrContacts[$next]["delete"]))
			$arrContacts[$next]["delete"] = "no";
		}
	    }
	    return $arrContacts;
	}
    }

    function removeQueue($ticket)
    {
	$query = "DELETE FROM queues WHERE id=?";
	$result = $this->_DB->genQuery($query, array($ticket));
	if($result == FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return TRUE;
    }

    function getStatus($id,$id_user)
    {
	$query = "SELECT status FROM contact WHERE id=? AND iduser=?";
	$result = $this->_DB->getFirstRowQuery($query,TRUE,array($id,$id_user));
	if($result == FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return "";
	}
	else
	    return $result["status"];
    }

    function getUserContacts($id_user,$fields=NULL)
    {
	if(is_null($fields))
	    $fields = "*";
	$query = "SELECT $fields FROM contact WHERE iduser=? OR status='isPublic'";
	$result = $this->_DB->fetchTable($query, true, array($id_user));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return $result;
    }

    function getUnsolvedQueues()
    {
	$query = "SELECT * FROM queues WHERE type='contact' AND status='NEW'";
	$result = $this->_DB->fetchTable($query, true);
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return $result;
    }

    function changeStatusQueue($id,$status)
    {
	$query = "UPDATE queues SET status=? WHERE id=?";
	$result = $this->_DB->genQuery($query,array($status,$id));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return TRUE;
    }

    function contactDeleted($id,$id_user)
    {
	$query = "SELECT * FROM history WHERE id_register=? AND type='contact' AND action='delete' AND id_user=?";
	$result = $this->_DB->getFirstRowQuery($query,TRUE,array($id,$id_user));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return $result;
    }

    function contactExists($id)
    {
	$query = "SELECT COUNT(*) FROM contact WHERE id=?";
	$result = $this->_DB->getFirstRowQuery($query,FALSE,array($id));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return NULL;
	}
	elseif($result[0] == 0)
	    return FALSE;
	else
	    return TRUE;
    }

    function addContactWithId($id,$data)
    {
	$data[] = time();
	$data[] = $id;
	$query = "insert into contact(name,last_name,telefono,email,iduser,picture,address,company,notes,status,last_update,id) values(?,?,?,?,?,?,?,?,?,?,?,?)";
	$result = $this->_DB->genQuery($query,$data);
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return TRUE;
    }

    function setQueueDataResponse($id,$dataResponse)
    {
	$query = "UPDATE queues SET response_data=? WHERE id=?";
	$result = $this->_DB->genQuery($query,array($dataResponse,$id));
	if($result === FALSE){
	    $this->errMsg = $this->_DB->errMsg;
	    return FALSE;
	}
	else
	    return TRUE;
    }
}
?>
