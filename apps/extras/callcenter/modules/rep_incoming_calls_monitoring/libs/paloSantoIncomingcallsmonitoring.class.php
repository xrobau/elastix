<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2-3.1                                               |
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
  $Id: paloSantoIncomingcallsmonitoring.class.php,v 1.1.1.1 2009/07/27 09:10:19 dlopez Exp $ */
class paloSantoIncomingcallsmonitoring {
    var $_DB;
    var $errMsg;

    function paloSantoIncomingcallsmonitoring(&$pDB)
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

    function ObtainNumIncomingcallsmonitoring($filter_field, $filter_value)
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

    function ObtainIncomingcallsmonitoring($limit, $offset, $arrLang, $filter_field, $filter_value)
    {
        //Here your implementation
        $date = date("Y-m-d");

        $query = "
            SELECT queue.queue,
                call_entry.status,
                count(call_entry.id) cantidad_llamadas
            FROM
                call_entry,
                queue_call_entry queue
            WHERE
                call_entry.id_queue_call_entry = queue.id 
                and call_entry.datetime_entry_queue like '$date%'
            GROUP BY
                call_entry.id_queue_call_entry,
                call_entry.status
            ORDER BY queue.queue, call_entry.status";
//echo $query."<br>";
        $result_queue=$this->_DB->fetchTable($query, true);

        if($result_queue===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        $query = "
            SELECT
                queue.queue, 
                count(datetime_init) cantidad_llamadas
            FROM 
                call_entry, 
                queue_call_entry queue 
            WHERE 
                call_entry.id_queue_call_entry = queue.id 
                and call_entry.datetime_entry_queue like '$date%' 
                and status='fin-monitoreo' 
            GROUP BY call_entry.id_queue_call_entry 
            ORDER BY queue.queue";

        $result_finmonitoreo=$this->_DB->fetchTable($query, true);

        if($result_finmonitoreo===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        if (is_array($result_queue)) {
            $records= array();
//             $data_temp = $data;
//             $primer_record = array_shift($data_temp);
//             $cola_quiebre = $primer_record["queue"];
            $totales["entered"]=0; 
            $totales["abandoned"]=0;
            $totales["waiting_calls"]=0;
            $totales["without_monitoring"]=0;
            $totales["answered"]=0;
            foreach($result_queue as $key=>$data) {
                if (!isset($records[$data["queue"]]["queue"]))
                    $records[$data["queue"]]["queue"] = $data["queue"];

                if (!isset($records[$data["queue"]]["entered"]))
                    $records[$data["queue"]]["entered"] = $data["cantidad_llamadas"];
                else {
                    $records[$data["queue"]]["entered"] += $data["cantidad_llamadas"];
                }
                $totales["entered"] += $data["cantidad_llamadas"];
                switch (strtoupper($data["status"])) {
                    case "ABANDONADA";
                        $records[$data["queue"]]["abandoned"] = $data["cantidad_llamadas"];
                        $totales["abandoned"] = $totales["abandoned"] + $data["cantidad_llamadas"];
                    break;
                    case "EN-COLA";
                        $records[$data["queue"]]["waiting_calls"] = $data["cantidad_llamadas"];
                        $totales["waiting_calls"] = $totales["waiting_calls"] + $data["cantidad_llamadas"];
                    break;
                    case "FIN-MONITOREO";
                        $records[$data["queue"]]["without_monitoring"] = $data["cantidad_llamadas"];
                        $totales["without_monitoring"] = $totales["without_monitoring"] + $data["cantidad_llamadas"];
                    break;
                    case "TERMINADA";
                    case "ACTIVA";
                    case "HOLD";
                        if (!isset($records[$data["queue"]]["answered"]))
                            $records[$data["queue"]]["answered"] = $data["cantidad_llamadas"];
                        else $records[$data["queue"]]["answered"] += $data["cantidad_llamadas"];//se agregó +
                        $totales["answered"] = $totales["answered"]+$data["cantidad_llamadas"];
                }
            } // fin del foreach
        }

        if (is_array($result_finmonitoreo)) {
            unset($data);
            foreach($result_finmonitoreo as $key=>$data) {

                $records[$data["queue"]]["without_monitoring"] = $records[$data["queue"]]["without_monitoring"] - $data["cantidad_llamadas"];

                $totales["without_monitoring"] = $totales["without_monitoring"]-$data["cantidad_llamadas"];

                if (!isset($records[$data["queue"]]["answered"]))
                    $records[$data["queue"]]["answered"] = $data["cantidad_llamadas"];
                else
                    $records[$data["queue"]]["answered"] += $data["cantidad_llamadas"];

                $totales["answered"] = $totales["answered"]+$data["cantidad_llamadas"];
            }
        }


        // seteando los totales
        $records["TOTALES"]["queue"] = "<b>".strtoupper($arrLang["Total"])."</b>";
        $records["TOTALES"]["entered"] = "<b>".strtoupper($totales["entered"])."</b>";
        $records["TOTALES"]["abandoned"] = "<b>".strtoupper($totales["abandoned"])."</b>";
        $records["TOTALES"]["waiting_calls"] = "<b>".strtoupper($totales["waiting_calls"])."</b>";
        $records["TOTALES"]["without_monitoring"] = "<b>".strtoupper($totales["without_monitoring"])."</b>";
        $records["TOTALES"]["answered"] = "<b>".strtoupper($totales["answered"])."</b>";

        return $records;
    }
}?>
