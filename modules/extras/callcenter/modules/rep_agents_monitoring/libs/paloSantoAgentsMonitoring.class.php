<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2-3.1                                                                   |
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
  $Id: paloSantoAgentsMonitoring.class.php,v 1.1.1.1 2009/07/27 09:10:19 dlopez Exp $ */
class paloSantoAgentsMonitoring {
    var $_DB;
    var $errMsg;

    function paloSantoAgentsMonitoring(&$pDB)
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

    function ObtainNumAgentsMonitoring($filter_field, $filter_value)
    {
        //Here your implementation
        $where = "";
        if(isset($filter_field) & $filter_field !="")
            $where = "where $filter_field like '$filter_value%'";

        $query   = "SELECT COUNT(*) FROM table $where";

        $result=$this->_DB->getFirstRowQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function ObtainAgentsMonitoring($limit, $offset, $arrLang, $filter_field, $filter_value)
    {

        //Here your implementation
        $where = "";
        if(isset($filter_field) & $filter_field !="")
            $where = "where $filter_field like '$filter_value%'";

        $date_from=$date_to=date("Y-m-d");
        $now = "now()";
//         $date_from=$date_to="2009-06-10";
//         $now = "'2009-06-10 23:".date("i:s")."'";

        $where1 = $where2 ="";
        if(isset($date_from) && $date_from !="" && isset($date_to) && $date_to !="") {
             $where1 = " and call_entry.datetime_init between '$date_from 00:00:00' and '$date_to 23:59:59'";
             $where2 = " and audit.datetime_init between '$date_from 00:00:00' and '$date_to 23:59:59'";
        }

        // SELECT PARA OBTENER LAS COLUMNAS QUEUE, AGENTS, TOTAL CALLS, TOTAL TALK TIME. AQUI SE SUMAN VALORES
        // DE LA TABLA DE LLAMADAS ENTRANTES (CALL_ENTRY).
        $query = "
                SELECT
                    call_entry.id_queue_call_entry id_queue,
                    queue_call_entry.queue queue,
                    agent.id id_agent,
                    agent.number agent_number,
                    agent.name agent_name,
                    count(call_entry.id) total_calls,
                    sec_to_time(sum(call_entry.duration)) total_talk_time
                FROM
                    queue_call_entry,
                    agent LEFT JOIN call_entry ON (agent.id = call_entry.id_agent $where1)
                WHERE
                    agent.estatus = 'A'
                    and queue_call_entry.id = call_entry.id_queue_call_entry
                GROUP BY queue_call_entry.queue, call_entry.id_agent
                ORDER BY queue_call_entry.queue, agent.name";

        $result_calls=$this->_DB->fetchTable($query, true);
// echo "1) $query<br><br>";
        if($result_calls==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        // ESTE SELECT ES PARA OBTENER EL TOTAL TALK TIME DE LAS LLAMADAS ACTIVAS, LAS CUALES A� NO TIENEN
        // UN TIEMPO DE DURACI�, PORQUE A� NO SE HA CERRADO LA LLAMADA. CON ESTE QUERY SE OBTIEN EL TIEMPO
        // DE LAS LLAMADAS ACTIVAS DESDE QUE INICIARON HASTA EL MOMENTO ACTUAL.
        // ESTO HACE QUE LOS TIEMPOS DE LAS LLAMADAS ACTIVAS SIEMPRE EST� CAMBIANDO EN LA PANTALLA
        $query = "
                SELECT
                    call_entry.id_queue_call_entry id_queue,
                    agent.id id_agent,
                    TIMEDIFF($now,call_entry.datetime_init) total_talk_time
                FROM
                    agent LEFT JOIN call_entry ON (agent.id = call_entry.id_agent $where1)
                WHERE
                    agent.estatus = 'A'
                    and call_entry.datetime_end is NULL
                    and call_entry.status='activa'
                GROUP BY call_entry.id_agent
                ORDER BY agent.name";

        $result_calls_no_terminadas=$this->_DB->fetchTable($query, true);
// echo "2) $query<br><br>";
        if($result_calls_no_terminadas===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        // CON ESTE FOREACH SE HACE QUE EL ARREGLO RESULTADO DE LAS LLAMADAS QUE NO TIENEN TIEMPO DE DURACION,
        // GRABE COMO INDICE EL ID DE LA COLA Y EL AGENTE.
        if (is_array($result_calls_no_terminadas)) {
            foreach($result_calls_no_terminadas as $key=>$data_calls_no_terminadas){
                $id_queue_agent = $data_calls_no_terminadas["id_queue"]."-".$data_calls_no_terminadas["id_agent"];
                $calls_no_terminadas[$id_queue_agent] = array_merge($data_calls_no_terminadas);
            }
        }




        // SELECT PARA OBTENER LAS COLUMNAS TOTAL LOGIN TIME
        // NOTA: No se pudo hacer un solo select con el primero xq se perd�n datos de login, en el caso de que
        // no se encontraran llamadas en call_entry
        $query = "
                SELECT
                    agent.id id_agent,
                    agent.number agent_number,
                    agent.name agent_name,
                    sec_to_time(sum(TIME_TO_SEC(audit.duration))) total_login_time 
                FROM 
                    agent,
                    audit 
                WHERE
                    agent.id = audit.id_agent
                    and audit.id_break is null
                    and agent.estatus = 'A'
                    $where2
                GROUP BY audit.id_agent
                ORDER BY agent.id";
//  echo "3) ".$query."<br><br>";
         $result_audit=$this->_DB->fetchTable($query, true);
        if($result_audit==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }


        $query = "
                SELECT
                    agent.id id_agent,
                    agent.number agent_number,
                    agent.name agent_name,
                    TIMEDIFF($now,audit.datetime_init) total_login_time 
                FROM 
                    agent,
                    audit 
                WHERE
                    agent.id = audit.id_agent
                    and audit.id_break is null
                    and agent.estatus = 'A'
                    and datetime_end is null
                    $where2
                GROUP BY audit.id_agent
                ORDER BY agent.id";
// echo "4) ".$query."<br>";
         $result_audit_no_terminado=$this->_DB->fetchTable($query, true);
        if($result_audit_no_terminado===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        // CON ESTE FOREACH SE HACE QUE EL ARREGLO RESULTADO DE LOS LOGIN QUE NO TIENEN TIEMPO DE DURACION,
        // GRABE COMO ID O INDICE EL ID DEL AGENTE.
        if (is_array($result_audit_no_terminado)) {
            foreach($result_audit_no_terminado as $key=>$data_audit_no_terminado){
                $audit_no_terminado[$data_audit_no_terminado["id_agent"]] = array_merge($data_audit_no_terminado);
            }
            unset($data_queue);
        }

// echo "<pre>";
// echo "<br><br><u><b>RESULTADO DE LLAMADAS</b></u><BR>";
// print_r($audit_no_terminado);
// echo "<br><br><u><b>RESULTADO DE AUDIT</b></u><BR>";
// print_r($result_audit);
// echo "</pre>";


        // Se hace un foreach para hacer que el arreglo $result_calls, guarde en calls todos los records y que el indice sea el id del agente. Esto es para luego hacer un merge con el resultado del 1er y 2do select.
        if (is_array($result_calls)) {
            // SE GRABA EN CALLS el indice total_login_time como No registered, el cual debe ser reeemplazado en el sgte foreach por el valor del real del tiempo que ha estado en login. Pero se toma esta accion preventiva, porque puede haber ocaciones que no se grabe en la tabla audit que el agente hizo login.
            $no_register["total_login_time"] = $arrLang["No registered"];
            foreach($result_calls as $key=>$data_calls){

                $id_queue_agent = $data_calls["id_queue"]."-".$data_calls["id_agent"];
                if (isset($calls_no_terminadas[$id_queue_agent])) {
                    $hora1 = explode(":",$data_calls["total_talk_time"]);
                    $hora2 = explode(":",$calls_no_terminadas[$id_queue_agent]["total_talk_time"]);
                    $sum_hora = date("H:i:s", mktime ($hora1[0]+$hora2[0],$hora1[1]+$hora2[1],$hora1[2]+$hora2[2],6,14,2005));
                    $data_calls["total_talk_time"] = $sum_hora;//." - ".$data_calls["total_talk_time"]." - ".$calls_no_terminadas[$data_calls["id_agent"]]["total_talk_time"];
                }
                $calls[$id_queue_agent] = array_merge($data_calls,$no_register);
            }
        }
// echo "<pre>";
// echo "<br><br><u><b>RESULTADO DE LLAMADAS DESPUES DE UNIR CON LAS NO TERMINADAS</b></u><BR>";
// print_r($calls);
// echo "</pre>";
        // En este foreach se recorre el $result_audit, para hacer un merge con el arreglo calls y dejar como id del arreglo el id del agente. ESto es con el fin de unir toda la informaci� 
        if (is_array($result_audit)) {
            foreach($result_audit as $key=>$data_audit){

                if (isset($audit_no_terminado[$data_audit["id_agent"]])) {
                    $arr_hora1 = explode(":",$audit_no_terminado[$data_audit["id_agent"]]["total_login_time"]);
                    $arr_hora2 = explode(":",$data_audit["total_login_time"]);
                    if (is_array($arr_hora1) && count($arr_hora1)==3) {
                        $hora["hora"] = $arr_hora1[0] + $arr_hora2[0];
                        $hora["min"] = $arr_hora1[1] + $arr_hora2[1];
                        $hora["seg"] = $arr_hora1[2] + $arr_hora2[2];
                        $tiempo = $this->obtener_tiempo($hora);
                        $data_audit["total_login_time"] = $tiempo["hora"].":".$tiempo["min"].":".$tiempo["seg"];
                    }
                }
                $ids_queue_agent = $this->existe_agent_calls($calls, $data_audit["id_agent"]);
                if (isset($ids_queue_agent)) {
                    if(is_array($ids_queue_agent) && count($id_queue_agent)>0) {
                        foreach($ids_queue_agent as $key => $id_queue_agent) {
                            $calls[$id_queue_agent] = array_merge($calls[$id_queue_agent], $data_audit);
                        }
                    } else {
                        $queue_default["queue"] = " ";
                        $calls[$data_audit["id_agent"]] = array_merge($queue_default,$data_audit);
                    }
                } else {
                    $queue_default["queue"] = " ";
                    $calls[$data_audit["id_agent"]] = array_merge($queue_default,$data_audit);
                }
            }
        }
// echo "<pre>";
// echo "<br><br><u><b>RESULTADO DE UNIR LLAMADAS CON AUDIT</b></u><BR>";
// print_r($calls);
// echo "</pre>";

        $calls_temp = $calls;
        $calls_final = $calls;
        $first_record = array_shift($calls_final);
        $queue_temp_quiebre=$first_record["queue"];
        unset($calls_final);
        $calls_final=array();
/*
echo "<pre>";
print_r($calls_temp);
echo "</pre>";*/

        if (is_array($calls_temp)) {

            $totales["num_records"] = 0;
            $totales["total_calls"] = 0;

            foreach($calls_temp as $id_agent=>$data_call){
                // haciendo el quiebre para poner los totales por cola
                if (!isset($data_call["queue"]) || $queue_temp_quiebre <> $data_call["queue"]) {
//echo $queue_temp_quiebre." ".$totales["num_records"]."<br>";
                    // si es un agente que diga "Agent" pero si son m� o cero que diga "Agents"
                    if($totales["num_records"]==1) $agentes=$arrLang["Agent"];
                    else $agentes=$arrLang["Agents"];

                    $calls_final["TOTALES".$queue_temp_quiebre]["queue"] = "<b>".strtoupper($arrLang["Total"])."</b>";
                    $calls_final["TOTALES".$queue_temp_quiebre]["agent_name"] = "<b>".$totales["num_records"]." ".strtoupper($agentes)."</b>";
                    $calls_final["TOTALES".$queue_temp_quiebre]["total_calls"] = "<b>".$totales["total_calls"]."</b>";
                    if(isset($totales["total_login_time"])) {
                        $tiempo_login = $this->obtener_tiempo($totales["total_login_time"]);
                        $calls_final["TOTALES".$queue_temp_quiebre]["total_login_time"] = "<b>".$tiempo_login["hora"].":".$tiempo_login["min"].":".$tiempo_login["seg"]."</b>";
                    }
                    if(isset($totales["total_talk_time"])) {
                        $tiempo_talk = $this->obtener_tiempo($totales["total_talk_time"]);
                        $calls_final["TOTALES".$queue_temp_quiebre]["total_talk_time"] = "<b>".$tiempo_talk["hora"].":".$tiempo_talk["min"].":".$tiempo_talk["seg"]."</b>";
                    }

                    $queue_temp_quiebre = isset($data_call["queue"])?$data_call["queue"]:"";

                    // se encera el arreglo totales para iniciar la suma para la nueva cola
                    unset($totales);
                    $totales["num_records"] = 0;
                    $totales["total_calls"] = 0;
                }

                // SACANDO LOS TOTALES POR COLAS
                $totales["num_records"] = $totales["num_records"]+1;
//echo "-> ".$totales["num_records"]."<br>";
                if(isset($data_call["total_calls"])) {
                    $totales["total_calls"] = $totales["total_calls"]+$data_call["total_calls"];
                }
                $hora_login = explode(":",$data_call["total_login_time"]);
                if (is_array($hora_login) && count($hora_login)==3) {
                    if (isset($totales["total_login_time"]['hora'])) {
                        $totales["total_login_time"]['hora'] += $hora_login[0];
                        $totales["total_login_time"]['min'] += $hora_login[1];
                        $totales["total_login_time"]['seg'] += $hora_login[2];
                    } else {
                        $totales["total_login_time"]['hora'] = $hora_login[0];
                        $totales["total_login_time"]['min'] = $hora_login[1];
                        $totales["total_login_time"]['seg'] = $hora_login[2];
                    }
                }

                if (isset($data_call["total_talk_time"])){
                    $hora_talk = explode(":",$data_call["total_talk_time"]);
                    if (is_array($hora_talk) && count($hora_talk)==3) {
                        if (isset($totales["total_talk_time"]['hora'])) {
                            $totales["total_talk_time"]['hora'] += $hora_talk[0];
                            $totales["total_talk_time"]['min'] += $hora_talk[1];
                            $totales["total_talk_time"]['seg'] += $hora_talk[2];
                        } else {
                            $totales["total_talk_time"]['hora'] = $hora_talk[0];
                            $totales["total_talk_time"]['min'] = $hora_talk[1];
                            $totales["total_talk_time"]['seg'] = $hora_talk[2];
                        }
                    }
                }


                // ESTE SELECT ES PARA VERIFICAR SI EL AGENTE EST�ATENDIENDO UNA LLAMADA EN ESTE MOMENTO
                $query = "
                        SELECT datetime_init, TIMEDIFF($now, datetime_init) tiempo_llamada
                        FROM call_entry
                        WHERE id_agent = ".$data_call["id_agent"].
                            " $where1 and datetime_end is null
                            and status='activa'";

                $result_estatus=$this->_DB->getFirstRowQuery($query, true);
                if($result_estatus===FALSE){
                    $this->errMsg = $this->_DB->errMsg;
                    return array();
                }
                // ESTE IF ES PARA VALIDAR (PREGUNTAR) SI EST�ATENDIENDO UNA LLAMADA EN ESTE MOMENTO
                if (is_array($result_estatus) && count($result_estatus)>0) {
                    $estado["current_status"] = "CALL"; 
                    $estado["time_current_status"] = $result_estatus["tiempo_llamada"];
                    $calls_final[$id_agent] = array_merge($calls[$id_agent], $estado);
                } else {
                    // ESTE SELECT ES PARA VERIFICAR SI EL AGENTE EST�EN BREAK EN ESTE MOMENTO
                    $query = "
                            SELECT datetime_init, TIMEDIFF($now,datetime_init) tiempo_break
                            FROM audit
                            WHERE id_agent = ".$data_call["id_agent"].
                                " $where2 and datetime_end is null
                                and id_break is not null";

                    $result_estatus=$this->_DB->getFirstRowQuery($query, true);
                    if($result_estatus===FALSE){
                        $this->errMsg = $this->_DB->errMsg;
                        return array();
                    }
                    // ESTE IF ES PARA VALIDAR (PREGUNTAR) SI EST�EN BREAK EN ESTE MOMENTO
                    if (is_array($result_estatus) && count($result_estatus)>0) {
                        $estado["current_status"] = "BREAK"; 
                        $estado["time_current_status"] = $result_estatus["tiempo_break"];
                        $calls_final[$id_agent] = array_merge($calls[$id_agent], $estado);
                    } else {
                            // ESTE SELECT ES PARA VERIFICAR SI SE EST�EN LOGOUT O ESPERANDO LLAMADA
                            $query = "
                                SELECT datetime_init, datetime_end,
                                    TIMEDIFF($now,datetime_init) tiempo_ready_ini,
                                    TIMEDIFF($now,datetime_end) tiempo_ready_end
                                FROM audit
                                WHERE
                                    id_agent=".$data_call["id_agent"]."
                                    and id_break is null
                                    $where2
                                ORDER BY id DESC LIMIT 1";

                            $result_estatus2=$this->_DB->getFirstRowQuery($query, true);
                            if($result_estatus2===FALSE){
                                $this->errMsg = $this->_DB->errMsg;
                                return array();
                            }

                            if (is_array($result_estatus2) && count($result_estatus2)>0) {
                                if (!isset($result_estatus2["datetime_end"]) || $result_estatus2["datetime_end"]=="") {

                                    // ESTE SELECT ES PARA VERIFICAR SI ESTA READY ESPERANDO POR UNA LLAMADA
                                    $query = "
                                            SELECT datetime_end, TIMEDIFF($now,datetime_end) tiempo_ready
                                            FROM call_entry
                                            WHERE id_agent = ".$data_call["id_agent"].
                                                " $where1 and status='terminada'
                                            ORDER BY datetime_init DESC limit 1";
            
                                    $result_estatus=$this->_DB->getFirstRowQuery($query, true);
// print_r($result_estatus);
// echo "<br><br>";
                                    if($result_estatus===FALSE){
                                        $this->errMsg = $this->_DB->errMsg;
                                        return array();
                                    }
                                    // si el la hora de login (estatus2) 

                                    if ($result_estatus["tiempo_ready"]!="" && $result_estatus["tiempo_ready"]<$result_estatus2["tiempo_ready_ini"]) {
                                        $estado["time_current_status"] = $result_estatus["tiempo_ready"];
                                    } else {
                                        $estado["time_current_status"] = $result_estatus2["tiempo_ready_ini"];
                                    }
                                    $estado["current_status"] = "READY";
                                    $calls_final[$id_agent] = array_merge($calls[$id_agent], $estado);

                                } else {
                                    $estado["current_status"] = "LOGOUT"; 
                                    $estado["time_current_status"] = $result_estatus2["tiempo_ready_end"];
                                    $calls_final[$id_agent] = array_merge($calls[$id_agent], $estado);
                                }
                            } else {
                                $estado["current_status"] = "LOGOUT"; 
                                $calls_final[$id_agent] = array_merge($calls[$id_agent], $estado);
                            }
                        } 
                    } //2
            } // fin foreach

            if($totales["num_records"]==1) $agentes=$arrLang["Agent"];
            else $agentes=$arrLang["Agents"];

            $calls_final["TOTALES".$queue_temp_quiebre]["queue"] = "<b>".strtoupper($arrLang["Total"])."</b>";
            $calls_final["TOTALES".$queue_temp_quiebre]["agent_name"] = "<b>".$totales["num_records"]." ".strtoupper($agentes)."</b>";
            $calls_final["TOTALES".$queue_temp_quiebre]["total_calls"] = "<b>".$totales["total_calls"]."</b>";

            if (isset($totales["total_login_time"])) {
                $tiempo_login = $this->obtener_tiempo($totales["total_login_time"]);
                $calls_final["TOTALES".$queue_temp_quiebre]["total_login_time"] = "<b>".$tiempo_login["hora"].":".$tiempo_login["min"].":".$tiempo_login["seg"]."</b>";
            }
            if (isset($totales["total_talk_time"])) {
                $tiempo_talk = $this->obtener_tiempo($totales["total_talk_time"]);
                $calls_final["TOTALES".$queue_temp_quiebre]["total_talk_time"] = "<b>".$tiempo_talk["hora"].":".$tiempo_talk["min"].":".$tiempo_talk["seg"]."</b>";
            }
        }
// echo "<pre>";
// print_r($calls_final);
// echo "</pre>";
        return $calls_final;
    }


    function existe_agent_calls($calls, $id_agent) {
        $ids_queue_entry = null;
        if (is_array($calls)) {
            foreach($calls as $key => $data) {
                if ($data["id_agent"] == $id_agent) {
                    $ids_queue_entry[] = $key;
                }
            }
        }
        return $ids_queue_entry;
    }

    function obtener_tiempo($tiempo) {
        $hora=$min=$seg="00";
        if (is_array($tiempo) && count($tiempo)==3) {
//            $seg = $tiempo["seg"]; $min = $tiempo["min"]; $hora = $tiempo["hora"];
            $seg = $tiempo['seg'] % 60;
            $min = $tiempo['min'] + floor($tiempo['seg'] / 60);
            $min = $min % 60;
            $hora = $tiempo['hora'] + floor($tiempo['min'] / 60);

            if ($hora<10) $hora="0".$hora;
            if ($min<10) $min="0".$min;
            if ($seg<10) $seg="0".$seg;
        }
        $formato_hora["hora"] = $hora;
        $formato_hora["min"] = $min;
        $formato_hora["seg"] = $seg;
        return $formato_hora;
    }

}?>