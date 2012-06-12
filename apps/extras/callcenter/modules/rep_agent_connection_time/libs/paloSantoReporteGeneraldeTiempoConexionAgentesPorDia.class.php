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
  $Id: paloSantoReporteGeneraldeTiempoConexionAgentesPorDia.class.php,v 1.1.1.1 2009/07/27 09:10:19 dlopez Exp $ */
class paloSantoReporteGeneraldeTiempoConexionAgentesPorDia {
    var $_DB;
    var $errMsg;

    function paloSantoReporteGeneraldeTiempoConexionAgentesPorDia(&$pDB)
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

    function ObtainNumReporteGeneraldeTiempoConexionAgentesPorDia($filter_field, $filter_value,  $filter_field_tipo, $filter_value_tipo,$date_from, $date_to)
    {
        //Here your implementation
                $where = $group_by = $order_by = "";

//palosanto detallado o general
        if(isset($filter_value_tipo) & $filter_value_tipo !=""){
            if($filter_value_tipo=='detallado'){
                $group_by = ' GROUP BY audit.id ';
                $order_by = ' ORDER BY agent.name, audit.datetime_init ';
            }else{
                $group_by = ' GROUP BY agent.id ';
                $order_by = ' ORDER BY agent.name ';
            }
        }

//palosanto cola y fechas
        if(isset($filter_value) & $filter_value !="")
            $where = " cale.id_queue_call_entry = '$filter_value'";

        if(isset($date_from) && $date_from !="" && isset($date_to) && $date_to !="") {
            if ($where!="") $where.=" and ";
             $where .= "  audit.datetime_init between '".$date_from." 00:00:00' and '".$date_to." 23:59:59'";
        }


        if ($where!="") {
            $where = " WHERE ".$where;
        }

        //si es reporte general
        $query   = "SELECT  
                        count(*) cant
                        FROM 
                        audit, agent, call_entry cale 
                        $where 
                        AND audit.id_agent=agent.id 
                        AND  audit.id_break is null 
                        AND cale.id_agent=agent.id 
                        $group_by $order_by ";

        $result=$this->_DB->fetchTable($query, true);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        $total = count($result);
        if($total==1){
            if($result[0]['cant']==0) $total = 0;
        }
        return $total;
    }

    function ObtainReporteGeneraldeTiempoConexionAgentesPorDia($limit, $offset, $filter_field, $filter_value,  $filter_field_tipo, $filter_value_tipo,$date_from, $date_to)
    {
        //Here your implementation
        $where = $group_by = $order_by = $last_conecction = $tiempo_total_sesion = $total_llamadas = $servicio = $estado = "";

        //validacion para csv
        if(!isset($_GET['exportcsv'])){
            $limit = " LIMIT $limit OFFSET $offset ";
        }else{
            $limit = " ";
        }

        //para q aparezca x defecto tipo General
        if($filter_value_tipo=="")
            $filter_value_tipo= 'general';

        //palosanto TIPO combo detallado o general
        if(isset($filter_value_tipo) & $filter_value_tipo !=""){
            if($filter_value_tipo=='detallado'){//DETALLADO
                // clausulas group by y order by para el query general
                $group_by = ' GROUP BY audit.id ';
                $order_by = ' ORDER BY agent.name, audit.datetime_init ';

                // ultimo logeo del agente
                $last_conecction = ' if((audit.datetime_end) is null, now(), audit.datetime_end) ';

                // tiempo total de la sesion del agente
                    $tiempo_total_sesion = ' audit.duration ';


                //total de tiempo de las llamadas
                $total_llamadas = " sec_to_time((select sum(ca.duration) from call_entry ca where ca.id_agent=agent.id  and ca.id_queue_call_entry='$filter_value' and ca.datetime_init between audit.datetime_init and (if((audit.datetime_end)is null,now(),audit.datetime_end)) group by ca.id_agent)) ";

                // % de servicio
                $servicio = 
                            "(
                                (
                                if((select sum(ce.duration) from call_entry as ce where ce.datetime_init between audit.datetime_init and (if((audit.datetime_end)is null,now(),audit.datetime_end)) and ce.id_agent=agent.id and ce.id_queue_call_entry='$filter_value' ) is null, 0, 
                                (select sum(ce.duration) from call_entry as ce where ce.datetime_init between audit.datetime_init and (if((audit.datetime_end)is null,now(),audit.datetime_end)) and ce.id_agent=agent.id and ce.id_queue_call_entry='$filter_value' )
                                )) 
                                / 
                                TIME_TO_SEC(audit.duration)
                             ) * 100 ";

                // estatus del agente : para saber si esta en linea
                $estado = " if((audit.datetime_end)  is null, 'En linea', '') ";

            }else{//GENERAL
                // clausulas group by y order by para el query general
                $group_by = ' GROUP BY agent.id ';
                $order_by = ' ORDER BY agent.name ';

                // ultimo logeo del agente
                $last_conecction = 
                                " if( (select count(*) from audit au where au.datetime_init  between '".$date_from." 00:00:00' AND '".$date_to." 23:59:59' and au.id_agent=agent.id and au.id_break  is null and au.datetime_end is null) >'0', '-', max(audit.datetime_end) ) ";

                // tiempo total de la sesion del agente
                $tiempo_total_sesion =
                                " (select sec_to_time(sum(time_to_sec(au.duration))) from audit au where au.datetime_init  between '".$date_from." 00:00:00' AND '".$date_to." 23:59:59' and au.id_agent=agent.id) ";

                //total de tiempo de las llamadas
                $total_llamadas = " sec_to_time((select sum(ca.duration) from call_entry ca where ca.id_agent=agent.id and ca.datetime_init between '".$date_from." 00:00:00' AND '".$date_to." 23:59:59' and ca.id_queue_call_entry='$filter_value' group by ca.id_agent)) ";

                // % de servicio
                $servicio = 
                            "(
                                ((select sum(ce.duration) from call_entry ce where ce.datetime_init between '".$date_from." 00:00:00' AND '".$date_to." 23:59:59' and ce.id_agent=agent.id and ce.id_queue_call_entry='$filter_value'  ))
                                /
                                TIME_TO_SEC(
                                     (select sec_to_time(sum(time_to_sec(au.duration))) from audit au where au.datetime_init  between '".$date_from." 00:00:00' AND '".$date_to." 23:59:59' and au.id_agent=agent.id) 
                                )
                             ) * 100 ";

                // estatus del agente : para saber si esta en linea
                $estado = 
                            " if( (select count(*) from audit au where au.datetime_init  between '".$date_from." 00:00:00' AND '".$date_to." 23:59:59' and au.id_agent=agent.id and au.id_break  is null and au.datetime_end is null) >'0', 'En linea', '' ) ";
            }
        }

    //palosanto filtros de cola y fechas
        if(isset($filter_value) & $filter_value !="")
            $where = " cale.id_queue_call_entry = '$filter_value'";

        if(isset($date_from) && $date_from !="" && isset($date_to) && $date_to !="") {
            if ($where!="") $where.=" and ";
             $where .= "  audit.datetime_init between '".$date_from." 00:00:00' and '".$date_to." 23:59:59'";
        }

        if ($where!="") {
            $where = " WHERE ".$where;
        }

        //si es reporte general
        $query   = "SELECT  
                        agent.number number_agent, 
                        agent.name name,
                        min(audit.datetime_init) first_conecction,
                        $last_conecction as last_conecction,
                        $tiempo_total_sesion  as tiempo_total_sesion,
                        $total_llamadas as tiempo_llamadas,
                        $servicio as porcentaje_servicio,
                        $estado as estado
                        FROM 
                        audit, agent, call_entry cale 
                        $where 
                        AND audit.id_agent=agent.id 
                        AND  audit.id_break is null 
                        AND cale.id_agent=agent.id 
                        $group_by $order_by $limit ";

        $result=$this->_DB->fetchTable($query, true);
//echo $query."<br><br>";
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }
}?>