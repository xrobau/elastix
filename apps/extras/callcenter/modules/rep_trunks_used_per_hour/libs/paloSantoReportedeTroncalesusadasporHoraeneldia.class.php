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
  $Id: paloSantoReportedeTroncalesusadasporHoraeneldia.class.php,v 1.1.1.1 2009/07/27 09:10:19 dlopez Exp $ */
class paloSantoReportedeTroncalesusadasporHoraeneldia {
    var $_DB;
    var $errMsg;

    function paloSantoReportedeTroncalesusadasporHoraeneldia(&$pDB)
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
    function ObtainReportedeTroncalesusadasporHoraeneldia($limit, $offset, $filter_field, $filter_value, $date_from, $date_to, $bExportando)
   {
        //Here your implementation
        $where = "";

        //PS Cambiamos el where por que vamos a  filtrar por troncales
        if(isset($filter_value) & $filter_value !="")
            $where = " trunk = '$filter_value'";

        if(isset($date_from) && $date_from !="" && isset($date_to) && $date_to !="") {
            if ($where!="") $where.=" and ";
             $where .= " datetime_entry_queue between '".$date_from." 00:00:00' and '".$date_to." 23:59:59'";
        }
        if ($where!="") {
            $where = " WHERE ".$where;
        }

        $query   = "SELECT
                        id_agent,
                        id_queue_call_entry,
                        DATE_FORMAT(datetime_entry_queue,'%H:%i:%S') as time_entry_queue,
                        datetime_entry_queue,
                        datetime_init,
                        status,
                        trunk
                    FROM call_entry
                    $where ORDER BY time_entry_queue";
        $result=$this->_DB->fetchTable($query, true);

        if(is_array($result)){
            $data=array();
            $total["terminada"]=$total["abandonada"]=$total["fin-monitoreo"]=$total["en-cola"]=0;

            // SE LEE TODO EL QUERY PARA AGRUPARLOS POR HORA A LOS RESULTUADOS 
            foreach($result as $key => $value){
                // ESTE FOR AYUDA A PREGUNTAR EN QUE HORA CAE LA LLAMADA, SE PREGUNTA DESDE 00:00:00
                // HASTA LA HORA EN QUE SE ENCUENTRE LA LLAMADA O HASTA LAS 11:59:59

                for($hora=0; $hora<24; $hora++) {
                    $horafin = $hora+1;
                    if ($hora < 10) $hora = "0".$hora;
                    if ($horafin < 10) $horafin = "0".$horafin;
                    if($value['time_entry_queue']>="$hora:00:00" && $value['time_entry_queue']<"$horafin:00:00"){
                        $data[$hora]['time_period'] = "$hora:00:00 - $horafin:00:00";
                        // LLAMADAS QUE INGRESARON A LA COLA
                        if(!isset($data[$hora]['entered']))
                            $data[$hora]['entered'] = 1;
                        else $data[$hora]['entered'] += 1;

                        // LLAMADAS QUE INGRESARON A LA COLA Y FUERON CONTESTADAS
                        if ($value['status']=="terminada" || ($value['status']=="fin-monitoreo" && !is_null($value['datetime_init'])) || $value['status']=="activa" || $value['status']=="hold") {
                            if(!isset($data[$hora]['terminada']))
                                $data[$hora]['terminada'] = 1;
                            else $data[$hora]['terminada'] += 1;
                            $total["terminada"]++;
                        // LLAMADAS QUE INGRESARON A LA COLA Y FUERON ABANDONADAS
                        } elseif ($value['status']=="abandonada") {
                            if(!isset($data[$hora]['abandonada']))
                                $data[$hora]['abandonada'] = 1;
                            else $data[$hora]['abandonada'] += 1;
                            $total["abandonada"]++;
                        } elseif ($value['status']=="en-cola") {
                            if(!isset($data[$hora]['en-cola']))
                                $data[$hora]['en-cola'] = 1;
                            else $data[$hora]['en-cola'] += 1;
                            $total["en-cola"]++;
                        } elseif ($value['status']=="fin-monitoreo") {
                            if(!isset($data[$hora]['fin-monitoreo']))
                                $data[$hora]['fin-monitoreo'] = 1;
                            else $data[$hora]['fin-monitoreo'] += 1;
                            $total["fin-monitoreo"]++;
                        } 
                        break;
                    }
                }
            } // fin del foreach
            $sTagInicio = (!$bExportando) ? '<b>' : '';
            $sTagFinal = ($sTagInicio != '') ? '</b>' : '';
            $data["total"]['time_period'] = $sTagInicio."TOTAL".$sTagFinal;
            $data["total"]['entered'] = $total["terminada"]+$total["abandonada"]+$total["en-cola"]+ $total["fin-monitoreo"];
            $data["total"]['entered'] = $sTagInicio.$data["total"]['entered'].$sTagFinal;
            $data["total"]['terminada'] = $sTagInicio.$total["terminada"].$sTagFinal;
            $data["total"]['abandonada'] = $sTagInicio.$total["abandonada"].$sTagFinal;
            $data["total"]['en-cola'] = $sTagInicio.$total["en-cola"].$sTagFinal;
            $data["total"]['fin-monitoreo'] = $sTagInicio.$total["fin-monitoreo"].$sTagFinal;

        } else {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
// echo "<pre>";
// print_r($data);
// echo "<pre>";
        return $data;
    }
}?>