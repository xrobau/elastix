<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: ContactsSynchronizer.class.php,v 1.48 2012/05/31 13:46:58 Alberto Santos asantos@palosanto.com Exp $ */
$base_dir="/var/www/html";
$module_name="address_book";
require_once('AbstractProcess.class.php');
require_once("$base_dir/libs/misc.lib.php");
require_once("$base_dir/configs/default.conf.php");
require_once("$base_dir/libs/paloSantoDB.class.php");
require_once("$base_dir/modules/$module_name/configs/default.conf.php");
require_once("$base_dir/modules/$module_name/libs/paloSantoAdressBook.class.php");
require_once("$base_dir/libs/JSON.php");

class ContactsSynchronizer extends AbstractProcess
{
    private $oMainLog;      	// Log abierto por framework de demonio
    private $_pAddress_book;    // Objeto instanciado a la clase paloSantoAddressBook

    function inicioPostDemonio($infoConfig, &$oMainLog)
    {
	global $arrConfModule;
        // Guardar referencias al log del programa
        $this->oMainLog =& $oMainLog;
	$pDBAddressBook = new paloDB($arrConfModule["dsn_conn_database"]);
	$this->_pAddress_book = new paloAdressBook($pDBAddressBook);
	
        return TRUE;
    }
    
    function procedimientoDemonio()
    {
	$json = new Services_JSON();
	$arrQueues = $this->_pAddress_book->getUnsolvedQueues();
	if($arrQueues === FALSE){
	    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
	    return FALSE;
	}
	foreach($arrQueues as $queue){
	    $dataResponse = array();
	    $data = $json->decode($queue["data"]);
	    if(!isset($data->last_sync) || !isset($data->contacts)){
		$this->oMainLog->output("The data of the ticket {$queue["id"]} is wrong or corrupted. This data has to be a JSON string containing the keywords \"last_sync\" and \"contacts\".");
		$result = $this->_pAddress_book->changeStatusQueue($queue["id"],"ERROR");
		if($result === FALSE){
		    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
		    return FALSE;
		}			
	    }
	    elseif(!is_array($data->contacts)){
		$this->oMainLog->output("The data of the contacts in ticket {$queue["id"]} is wrong or corrupted. It has to be an array.");
		$result = $this->_pAddress_book->changeStatusQueue($queue["id"],"ERROR");
                if($result === FALSE){
		    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
                    return FALSE;
                }
	    }
	    else{
		$arrContacts = $data->contacts;
		foreach($arrContacts as $syncContact){
		    if(!isset($syncContact->last_update)){
			$this->oMainLog->output("The data of a contact in ticket {$queue["id"]} is wrong or corrupted. It must have the field \"last_update\".");	
		    }
		    else{
			if(isset($syncContact->delete))
			    $delete = $syncContact->delete;
			else
			    $delete = "no";
			if(isset($syncContact->id) && !empty($syncContact->id)){ //Es una actualización de un contacto
			    $contact = $this->_pAddress_book->contactData($syncContact->id,$queue["user"]);
			    if($contact === FALSE){
				$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
				return FALSE;
			    }
			    $contact = $this->defineData($contact);
			    $updateData = $this->setUpdateData($syncContact,$contact,$queue["user"]);
			    if(isset($contact["id"]) && !empty($contact["id"])){ //El contacto si existe en el servidor
				if($syncContact->last_update > $contact["last_update"]){ //Se debe actualizar el contacto ya que es nueva data
				    if($delete == "yes"){
					$result = $this->_pAddress_book->deleteContact($contact["id"],$queue["user"]);
					if($result === FALSE){
					    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
					    return FALSE;
					}
					else
					    $this->oMainLog->output("Contact with id = $contact[id] was deleted by sync.");
				    }
				    else{
					$result = $this->_pAddress_book->updateContact($updateData,$contact["id"]);
					if($result === FALSE){
					    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
					    return FALSE;
					}
					else
					    $this->oMainLog->output("Contact with id = $contact[id] was updated by sync.");
				    }
				}
				else{ //Se guarda en el historial
				    if($delete == "yes"){
					$description = "A contact was deleted";
					$action = "delete";
				    }
				    else{
					$description = "A contact was modified: id=$contact[id], name=$updateData[0], last_name=$updateData[1], telefono=$updateData[2], email=$updateData[3], iduser=$updateData[4], picture=$updateData[5], address=$updateData[6], company=$updateData[7], notes=$updateData[8], status=$updateData[9]";
					$action = "modify";
				    }
				    $result = $this->_pAddress_book->addHistory($contact["id"],$updateData[4],$updateData[9],"contact",$syncContact->last_update,$action,$description);
				    if($result === FALSE){
					$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
					return FALSE;
				    }
				    else
					$this->oMainLog->output("Contact with id = $contact[id] was added to the history by sync.");
				}
			    }
			    else{ //El contacto no existe por lo que hay que verificar si ha sido eliminado
				if($delete != "yes"){ //Si el campo delete es yes, no hay que hacer nada.
				    $contactDeleted = $this->_pAddress_book->contactDeleted($syncContact->id,$queue["user"]);
				    if($contactDeleted === FALSE){
					$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
					return FALSE;
				    }
				    elseif(count($contactDeleted) == 0){//Algo extraño pasó ya que el cliente tiene un id de un contacto que no existe en el servidor, se lo vuelve a crear
					//Verificamos que el id del contacto no esté creado ya y pertenezca a otro usuario
					$idExists = $this->_pAddress_book->contactExists($syncContact->id);
					if(is_null($idExists)){
					    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
					    return FALSE;
					}
					elseif($idExists === FALSE){ //Lo creamos
					    $result = $this->_pAddress_book->addContactWithId($syncContact->id,$updateData);
					    if($result === FALSE){
						$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
						return FALSE;
					    }
					    else
						$this->oMainLog->output("Contact with id = {$syncContact->id} was created by sync.");
					}
					else{
					    //Quiere decir que el contacto si existe pero le pertenece a otro usuario. Por el momento no se hará nada.
					}
				    }
				    else{
					//El contacto fue eliminado, por lo que hay que verificar la fecha
					if($syncContact->last_update > $contactDeleted["timestamp"]){ // Hay que volverlo a crear
					    $result = $this->_pAddress_book->addContactWithId($syncContact->id,$updateData);
					    if($result === FALSE){
						$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
						return FALSE;
					    }
					    else
						$this->oMainLog->output("Contact with id = {$syncContact->id} was recreated by sync.");
					}
					else{ //Se guarda en el historial el cambio
					    $result = $this->_pAddress_book->addHistory($syncContact->id,$queue["user"],$updateData[9],"contact",$syncContact->last_update,"modify","A contact was modified: id={$syncContact->id}, name=$updateData[0], last_name=$updateData[1], telefono=$updateData[2], email=$updateData[3], iduser=$updateData[4], picture=$updateData[5], address=$updateData[6], company=$updateData[7], notes=$updateData[8], status=$updateData[9]");
					    if($result === FALSE){
						$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
						return FALSE;
					    }
					    else
						$this->oMainLog->output("Contact with id = {$syncContact->id} was added to the history by sync.");
					}
				    }
				}
			    }
			}
			else{ //Contacto nuevo creado por el cliente
			    if($delete != "yes"){ //Si el campo delete es yes, no hay que hacer nada.
				$contact = $this->defineData();
				$updateData = $this->setUpdateData($syncContact,$contact,$queue["user"]);
				$id_contact = $this->_pAddress_book->addContact($updateData,TRUE);
				if($id_contact === FALSE){
				    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
				    return FALSE;
				}
				$syncContact->id = $id_contact;
				$dataResponse[] = $syncContact;
				$this->oMainLog->output("Contact with id = $id_contact was created by sync.");
			    }
			}
		    }
		}
		if(count($dataResponse) > 0){
		    $dataResponse = $json->encode($dataResponse);
		    $result = $this->_pAddress_book->setQueueDataResponse($queue["id"],$dataResponse);
		    if($result === FALSE){
			$this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
			return FALSE;
		    }
		}
		$this->oMainLog->output("The ticket {$queue["id"]} has been resolved.");
		$result = $this->_pAddress_book->changeStatusQueue($queue["id"],"OK");
                if($result === FALSE){
		    $this->oMainLog->output("Database Error: ".$this->_pAddress_book->errMsg);
                    return FALSE;
                }
	    }
	}
	usleep(5000000);
        return TRUE;
    }

    protected function defineData($data=array())
    {
	if(count($data) > 0)
	    return $data;
	else{
	    $contact["id"] = "";
	    $contact["name"] = "";
	    $contact["last_name"] = "";
	    $contact["telefono"]= "";
	    $contact["email"] 	= "";
	    $contact["picture"] = "";
	    $contact["address"] = "";
	    $contact["company"] = "";
	    $contact["notes"] 	= "";
	    $contact["status"] 	= "";
	    return $contact;
	}
    }

    protected function setUpdateData($syncContact,$contact,$user)
    {
	$updateData = array();
	$updateData[] = (isset($syncContact->name)) ? $syncContact->name : $contact["name"];
	$updateData[] = (isset($syncContact->last_name)) ? $syncContact->last_name : $contact["last_name"];
	if(isset($syncContact->telefono))
		$updateData[] = $syncContact->telefono;
	else{
		if(isset($syncContact->phone))
			$updateData[] = $syncContact->phone;
		else
			$contact["telefono"];
	}
	$updateData[] = (isset($syncContact->email)) ? $syncContact->email : $contact["email"];
	$updateData[] = $user;
	$updateData[] = (isset($syncContact->picture)) ? $syncContact->picture : $contact["picture"];
	$updateData[] = (isset($syncContact->address)) ? $syncContact->address : $contact["address"];
	$updateData[] = (isset($syncContact->company)) ? $syncContact->company :$contact["company"];
	$updateData[] = (isset($syncContact->notes)) ? $syncContact->notes :$contact["notes"];
	$updateData[] = (isset($syncContact->status)) ? $syncContact->status : $contact["status"];
	return $updateData;
    }

    function limpiezaDemonio()
    {
        
    }

}
?>
