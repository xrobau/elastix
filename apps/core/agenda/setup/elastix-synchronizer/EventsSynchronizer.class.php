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
  $Id: EventsSynchronizer.class.php,v 1.48 2012/06/06 13:46:58 Alberto Santos asantos@palosanto.com Exp $ */
$base_dir="/var/www/html";
$module_name="calendar";
require_once('AbstractProcess.class.php');
require_once("$base_dir/libs/misc.lib.php");
require_once("$base_dir/configs/default.conf.php");
require_once("$base_dir/libs/paloSantoDB.class.php");
require_once("$base_dir/modules/$module_name/configs/default.conf.php");
require_once("$base_dir/modules/$module_name/libs/paloSantoCalendar.class.php");
require_once("$base_dir/libs/JSON.php");

class EventsSynchronizer extends AbstractProcess
{
    private $oMainLog;      	// Log abierto por framework de demonio
    private $_pCalendar;        // Objeto instanciado a la clase paloSantoCalendar

    function inicioPostDemonio($infoConfig, &$oMainLog)
    {
	global $arrConfModule;
        // Guardar referencias al log del programa
        $this->oMainLog =& $oMainLog;
	$pDBCalendar = new paloDB($arrConfModule["dsn_conn_database"]);
	$this->_pCalendar = new paloSantoCalendar($pDBCalendar);
	
        return TRUE;
    }
    
    function procedimientoDemonio()
    {
	$json = new Services_JSON();
	$arrQueues = $this->_pCalendar->getUnsolvedQueues();
	if($arrQueues === FALSE){
	    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
	    return FALSE;
	}
	foreach($arrQueues as $queue){
	    $dataResponse = array();
	    $data = $json->decode($queue["data"]);
	    if(!isset($data->last_sync) || !isset($data->events)){
		$this->oMainLog->output("The data of the ticket {$queue["id"]} is wrong or corrupted. This data has to be a JSON string containing the keywords \"last_sync\" and \"events\".");
		$result = $this->_pCalendar->changeStatusQueue($queue["id"],"ERROR");
		if($result === FALSE){
		    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
		    return FALSE;
		}			
	    }
	    elseif(!is_array($data->events)){
		$this->oMainLog->output("The data of the events in ticket {$queue["id"]} is wrong or corrupted. It has to be an array.");
		$result = $this->_pCalendar->changeStatusQueue($queue["id"],"ERROR");
                if($result === FALSE){
		    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
                    return FALSE;
                }
	    }
	    else{
		$arrEvents = $data->events;
		foreach($arrEvents as $syncEvent){
		    if(!isset($syncEvent->last_update)){
			$this->oMainLog->output("The data of a event in ticket {$queue["id"]} is wrong or corrupted. It must have the field \"last_update\".");	
		    }
		    else{
			if(isset($syncEvent->delete))
			    $delete = $syncEvent->delete;
			else
			    $delete = "no";
			if(isset($syncEvent->id) && !empty($syncEvent->id)){ //Es una actualización de un evento
			    $event = $this->_pCalendar->getEventById($syncEvent->id,$queue["user"]);
			    if($event === FALSE){
				$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
				return FALSE;
			    }
			    $event = $this->defineData($event);
			    $updateData = $this->setUpdateData($syncEvent,$event);
			    if(isset($event["id"]) && !empty($event["id"])){ //El evento si existe en el servidor
				if($syncEvent->last_update > $event["last_update"]){ //Se debe actualizar el evento ya que es nueva data
				    if($delete == "yes"){
					$result = $this->_pCalendar->deleteEvent($event["id"],$queue["user"]);
					if($result === FALSE){
					    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
					    return FALSE;
					}
					else
					    $this->oMainLog->output("Event with id = $event[id] was deleted by sync.");
				    }
				    else{
					$result = $this->_pCalendar->updateEvent($event["id"],$updateData["startdate"],$updateData["enddate"],$updateData["starttime"],$updateData["eventtype"],$updateData["subject"],$updateData["description"],$updateData["asterisk_call"],$updateData["recording"],$updateData["call_to"],$updateData["notification"],$updateData["emails_notification"],$updateData["endtime"],$updateData["each_repeat"],$updateData["days_repeat"],$updateData["reminderTimer"],$updateData["color"],$queue["user"]);
					if($result === FALSE){
					    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
					    return FALSE;
					}
					else
					    $this->oMainLog->output("Event with id = $event[id] was updated by sync.");
				    }
				}
				else{ //Se guarda en el historial
				    if($delete == "yes"){
					$description = "An event was deleted";
					$action = "delete";
				    }
				    else{
					$description = "An event was modified: id=$event[id], startdate=$updateData[startdate], enddate=$updateData[enddate], starttime=$updateData[starttime], eventtype=$updateData[eventtype], subject=$updateData[subject], description=$updateData[description], asterisk_call=$updateData[asterisk_call], recording=$updateData[recording], call_to=$updateData[call_to], notification=$updateData[notification], emails_notification=$updateData[emails_notification], endtime=$updateData[endtime], each_repeat=$updateData[each_repeat], days_repeat=$updateData[days_repeat], reminderTimer=$updateData[reminderTimer], color=$updateData[color]";
					$action = "modify";
				    }
				    $result = $this->_pCalendar->addHistory($event["id"],$queue["user"],"","event",$syncEvent->last_update,$action,$description);
				    if($result === FALSE){
					$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
					return FALSE;
				    }
				    else
					$this->oMainLog->output("Event with id = $event[id] was added to the history by sync.");
				}
			    }
			    else{ //El evento no existe por lo que hay que verificar si ha sido eliminado
				if($delete != "yes"){ //Si el campo delete es yes, no hay que hacer nada.
				    $eventDeleted = $this->_pCalendar->eventDeleted($syncEvent->id,$queue["user"]);
				    if($eventDeleted === FALSE){
					$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
					return FALSE;
				    }
				    elseif(count($eventDeleted) == 0){//Algo extraño pasó ya que el cliente tiene un id de un evento que no existe en el servidor, se lo vuelve a crear
					//Verificamos que el id del evento no esté creado ya y pertenezca a otro usuario
					$idExists = $this->_pCalendar->eventExists($syncEvent->id);
					if(is_null($idExists)){
					    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
					    return FALSE;
					}
					elseif($idExists === FALSE){ //Lo creamos
					    $result = $this->_pCalendar->addEventWithId($syncEvent->id,$queue["user"],$updateData["startdate"],$updateData["enddate"],$updateData["starttime"],$updateData["eventtype"],$updateData["subject"],$updateData["description"],$updateData["asterisk_call"],$updateData["recording"],$updateData["call_to"],$updateData["notification"],$updateData["emails_notification"],$updateData["endtime"],$updateData["each_repeat"],$updateData["days_repeat"],$updateData["reminderTimer"],$updateData["color"]);
					    if($result === FALSE){
						$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
						return FALSE;
					    }
					    else
						$this->oMainLog->output("Event with id = {$syncEvent->id} was created by sync.");
					}
					else{
					    //Quiere decir que el evento si existe pero le pertenece a otro usuario. Por el momento no se hará nada.
					}
				    }
				    else{
					//El evento fue eliminado, por lo que hay que verificar la fecha
					if($syncEvent->last_update > $eventDeleted["timestamp"]){ // Hay que volverlo a crear
					    $result = $this->_pCalendar->addEventWithId($syncEvent->id,$queue["user"],$updateData["startdate"],$updateData["enddate"],$updateData["starttime"],$updateData["eventtype"],$updateData["subject"],$updateData["description"],$updateData["asterisk_call"],$updateData["recording"],$updateData["call_to"],$updateData["notification"],$updateData["emails_notification"],$updateData["endtime"],$updateData["each_repeat"],$updateData["days_repeat"],$updateData["reminderTimer"],$updateData["color"]);
					    if($result === FALSE){
						$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
						return FALSE;
					    }
					    else
						$this->oMainLog->output("Event with id = {$syncEvent->id} was recreated by sync.");
					}
					else{ //Se guarda en el historial el cambio
					    $result = $this->_pCalendar->addHistory($syncEvent->id,$queue["user"],"","event",$syncEvent->last_update,"modify","An event was modified: id={$syncEvent->id}, startdate=$updateData[startdate], enddate=$updateData[enddate], starttime=$updateData[starttime], eventtype=$updateData[eventtype], subject=$updateData[subject], description=$updateData[description], asterisk_call=$updateData[asterisk_call], recording=$updateData[recording], call_to=$updateData[call_to], notification=$updateData[notification], emails_notification=$updateData[emails_notification], endtime=$updateData[endtime], each_repeat=$updateData[each_repeat], days_repeat=$updateData[days_repeat], reminderTimer=$updateData[reminderTimer], color=$updateData[color]");
					    if($result === FALSE){
						$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
						return FALSE;
					    }
					    else
						$this->oMainLog->output("Event with id = {$syncEvent->id} was added to the history by sync.");
					}
				    }
				}
			    }
			}
			else{ //Evento nuevo creado por el cliente
			    if($delete != "yes"){ //Si el campo delete es yes, no hay que hacer nada.
				$event = $this->defineData();
				$updateData = $this->setUpdateData($syncEvent,$event);
				$id_event = $this->_pCalendar->insertEvent($queue["user"],$updateData["startdate"],$updateData["enddate"],$updateData["starttime"],$updateData["eventtype"],$updateData["subject"],$updateData["description"],$updateData["asterisk_call"],$updateData["recording"],$updateData["call_to"],$updateData["notification"],$updateData["emails_notification"],$updateData["endtime"],$updateData["each_repeat"],$updateData["days_repeat"],$updateData["reminderTimer"],$updateData["color"],TRUE);
				if($id_event === FALSE){
				    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
				    return FALSE;
				}
				$syncEvent->id = $id_event;
				$dataResponse[] = $syncEvent;
				$this->oMainLog->output("Event with id = $id_event was created by sync.");
			    }
			}
		    }
		}
		if(count($dataResponse) > 0){
		    $dataResponse = $json->encode($dataResponse);
		    $result = $this->_pCalendar->setQueueDataResponse($queue["id"],$dataResponse);
		    if($result === FALSE){
			$this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
			return FALSE;
		    }
		}
		$this->oMainLog->output("The ticket {$queue["id"]} has been resolved.");
		$result = $this->_pCalendar->changeStatusQueue($queue["id"],"OK");
                if($result === FALSE){
		    $this->oMainLog->output("Database Error: ".$this->_pCalendar->errMsg);
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
	    $event["id"] 		  = "";
	    $event["startdate"] 	  = "";
	    $event["enddate"]		  = "";
	    $event["starttime"] 	  = "";
	    $event["eventtype"] 	  = "";
	    $event["subject"] 		  = "";
	    $event["description"] 	  = "";
	    $event["asterisk_call"] 	  = "";
	    $event["recording"] 	  = "";
	    $event["call_to"] 		  = "";
	    $event["notification"] 	  = "";
	    $event["emails_notification"] = "";
	    $event["endtime"] 		  = "";
	    $event["each_repeat"] 	  = "";
	    $event["days_repeat"] 	  = "";
	    $event["reminderTimer"] 	  = "";
	    $event["color"] 		  = "#3366CC";
	    return $event;
	}
    }

    protected function setUpdateData($syncEvent,$event)
    {
	$updateData = array();

	$updateData["startdate"] 	   = (isset($syncEvent->startdate)) 	      ? $syncEvent->startdate : $event["startdate"];
	$updateData["enddate"] 		   = (isset($syncEvent->enddate)) 	      ? $syncEvent->enddate : $event["enddate"];
	$updateData["starttime"] 	   = (isset($syncEvent->starttime)) 	      ? $syncEvent->starttime : $event["starttime"];
	$updateData["eventtype"] 	   = (isset($syncEvent->eventtype)) 	      ? $syncEvent->eventtype : $event["eventtype"];
	$updateData["subject"] 		   = (isset($syncEvent->subject)) 	      ? $syncEvent->subject : $event["subject"];
	$updateData["description"] 	   = (isset($syncEvent->description)) 	      ? $syncEvent->description : $event["description"];
	$updateData["asterisk_call"] 	   = (isset($syncEvent->asterisk_call))       ? $syncEvent->asterisk_call :$event["asterisk_call"];
	$updateData["recording"] 	   = (isset($syncEvent->recording)) 	      ? $syncEvent->recording :$event["recording"];
	$updateData["call_to"] 		   = (isset($syncEvent->call_to)) 	      ? $syncEvent->call_to : $event["call_to"];
	$updateData["notification"]	   = (isset($syncEvent->notification))	      ? $syncEvent->notification : $event["notification"];
	$updateData["emails_notification"] = (isset($syncEvent->emails_notification)) ? $syncEvent->emails_notification : $event["emails_notification"];
	$updateData["endtime"] 		   = (isset($syncEvent->endtime)) 	      ? $syncEvent->endtime : $event["endtime"];
	$updateData["each_repeat"]	   = (isset($syncEvent->each_repeat)) 	      ? $syncEvent->each_repeat : $event["each_repeat"];
	$updateData["days_repeat"] 	   = (isset($syncEvent->days_repeat))	      ? $syncEvent->days_repeat : $event["days_repeat"];
	$updateData["reminderTimer"] 	   = (isset($syncEvent->reminderTimer))	      ? $syncEvent->reminderTimer : $event["reminderTimer"];
	$updateData["color"] 		   = (isset($syncEvent->color)) 	      ? $syncEvent->color : $event["color"];

	return $updateData;
    }

    function limpiezaDemonio()
    {
        
    }

}
?>
