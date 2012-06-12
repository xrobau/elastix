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
  $Id: new_campaign.php $ */

include_once("libs/paloSantoDB.class.php");

/* Clase que implementa campaña (saliente por ahora) de CallCenter (CC) */
class paloSantoHoldTime
{
    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloSantoHoldTime(&$pDB)
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



     //Procedimiento para obtener el número de llamadas entrantes agrupadas por colas

    function getHoldTime($tipo='all',$entrantes='all', $salientes='all',$fecha_init, $fecha_end, $limit, $offset)
    {
        //validamos la fecha
        if($fecha_init!="" && $fecha_end!="" ) {
            $fecha_init = explode('-',$fecha_init);
            $fecha_end  = explode('-',$fecha_end);
        }else {
            $this->msgError .= "Debe ingresarse una fecha inicio y una fecha fin";
            return false;
        }
        // pregunto si la fecha inicial existe
        if( is_array( $fecha_init ) && count( $fecha_init )==3 && is_array( $fecha_end ) && count( $fecha_end )==3 ) {
            $year_init  = $fecha_init[0];
            $month_init = $fecha_init[1];
            $day_init   = $fecha_init[2];
            $year_end   = $fecha_end[0];
            $month_end  = $fecha_end[1];
            $day_end    = $fecha_end[2];
            $fechaInicial = $fecha_init[0]."-".$fecha_init[1]."-".$fecha_init[2]." "."00:00:00";
            $fechaFinal = $fecha_end[0]."-".$fecha_end[1]."-".$fecha_end[2]." "."23:59:59";
        //si fecha_init y fecha_end no existen envio un mensaje de error
        }else {
            $this->msgError .= "Fecha Inicio y/o Fecha Fin no valida";
            return false;
        }

        $arreglo = array();
        $where = "";
        if($tipo=='E'){//hacemos consulta en tabla call_entry
        //validamos las opciones del combo de ENTRANTES
            if($entrantes=='T')
                 $where .= " WHERE  (
                                    (datetime_init>='{$fechaInicial}' or datetime_entry_queue >='{$fechaInicial}')

                                           AND

                                    datetime_end<='{$fechaFinal}'
                                )";
            elseif($entrantes=='E')
                 $where .= "WHERE  (
                                    datetime_init>='{$fechaInicial}'

                                           AND

                                    datetime_end<='{$fechaFinal}'
                                ) and status='terminada'";
            elseif($entrantes=='A')
                 $where .= "WHERE  (
                                    datetime_entry_queue >='{$fechaInicial}'

                                           AND

                                    datetime_end<='{$fechaFinal}'
                                ) and status='abandonada'";

            $arr_result = FALSE;
            $this->errMsg = "";
            //Query para llamadas entrantes (call_entry)
            $sPeticionSQL = "SELECT  queue_ce.queue as queue, TIME(call_e.datetime_init) as hora,                   call_e.duration_wait as duration_wait, max(call_e.duration_wait) as                    maximo
                             FROM call_entry call_e, queue_call_entry queue_ce 
                             ".$where. "
                                AND call_e.id_queue_call_entry=queue_ce.id AND status is not null AND  duration_wait is not null GROUP BY call_e.id";
        }
        else if($tipo=='S'){//hacemos consulta en tabla calls
        //validamos las opciones del combo de SALIENTES
            if($salientes=='T')
                 $where .= " ";
            elseif($salientes=='E')
                 $where .= " and status='Success'";
            elseif($salientes=='N')
                 $where .= " and (status='NoAnswer' OR status='ShortCall')";
            elseif($salientes=='A')
                 $where .= " and status='Abandoned'";

            //Query para llamadas salientes (calls)
            $sPeticionSQL = "SELECT camp.queue as queue,  TIME(c.start_time) as hora,                               c.duration_wait as duration_wait, max(c.duration_wait) as                               maximo
                            FROM calls c , campaign camp
                             WHERE   (
                                    start_time>='{$fechaInicial}'

                                           AND
                                    end_time<='{$fechaFinal}'
                                )
                                AND c.id_campaign=camp.id AND c.status is not null AND  duration_wait is not null ".$where. " GROUP BY c.id";
        }


//echo $sPeticionSQL."<br><br>";
        $arr_result =& $this->_DB->fetchTable($sPeticionSQL, true);
        if (!is_array($arr_result)) {
            $arr_result = FALSE;
            $this->errMsg = $this->_DB->errMsg;
        }


        $resultado = array();
        //armamos el arreglo de todos los datos a presentar clasificandolos por cola e intervalo de duración
    if(is_array($arr_result)){
        foreach($arr_result as $intervalo){
            if(!isset($resultado[$intervalo['queue']]['cola']))
                $resultado[$intervalo['queue']]['cola']="";
            if(!isset($intervalo['queue']))
                $intervalo['queue'] = "";
            if(!isset($resultado[$intervalo['queue']]['nuevo_valor_maximo']))
                $resultado[$intervalo['queue']]['nuevo_valor_maximo']=0;
            if(!isset($resultado[$intervalo['queue']][0]))
                    $resultado[$intervalo['queue']][0] =0;
            if(!isset($resultado[$intervalo['queue']][1]))
                    $resultado[$intervalo['queue']][1] =0;
            if(!isset($resultado[$intervalo['queue']][2]))
                    $resultado[$intervalo['queue']][2] =0;
            if(!isset($resultado[$intervalo['queue']][3]))
                    $resultado[$intervalo['queue']][3] =0;
            if(!isset($resultado[$intervalo['queue']][4]))
                    $resultado[$intervalo['queue']][4] =0;
            if(!isset($resultado[$intervalo['queue']][5]))
                    $resultado[$intervalo['queue']][5] =0;
            if(!isset($resultado[$intervalo['queue']][6]))
                    $resultado[$intervalo['queue']][6] =0;
            if(!isset($resultado[$intervalo['queue']]['suma_duracion']))
                $resultado[$intervalo['queue']]['suma_duracion']=0;

            if($intervalo['duration_wait']>="0" && $intervalo['duration_wait']<"11"){
                $resultado[$intervalo['queue']][0] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            if($intervalo['duration_wait']>="11" && $intervalo['duration_wait']<"21"){
                $resultado[$intervalo['queue']][1] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            if($intervalo['duration_wait']>="21" && $intervalo['duration_wait']<"31") {
                $resultado[$intervalo['queue']][2] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            if($intervalo['duration_wait']>="31" && $intervalo['duration_wait']<"41"){
                $resultado[$intervalo['queue']][3] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            if($intervalo['duration_wait']>="41" && $intervalo['duration_wait']<"51"){
                $resultado[$intervalo['queue']][4] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            if($intervalo['duration_wait']>="51" && $intervalo['duration_wait']<"61"){
                $resultado[$intervalo['queue']][5] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            if($intervalo['duration_wait']>="61"){
                $resultado[$intervalo['queue']][6] += 1;
                $resultado[$intervalo['queue']]['cola'] = $intervalo['queue'];
                $resultado[$intervalo['queue']]['suma_duracion'] += $intervalo['maximo'];
                //para obtener el valor mayor de los segundos
                $resultado[$intervalo['queue']]['valor_maximo'] = $intervalo['maximo'];
                if($resultado[$intervalo['queue']]['valor_maximo']>$resultado[$intervalo['queue']]['nuevo_valor_maximo'])
                    $resultado[$intervalo['queue']]['nuevo_valor_maximo'] = $resultado[$intervalo['queue']]['valor_maximo'];
            }
            //suma de todos los intervalos clasificados por cola
            $resultado[$intervalo['queue']]['cantidad_intervalos'] =  $resultado[$intervalo['queue']][0]+$resultado[$intervalo['queue']][1]+$resultado[$intervalo['queue']][2]+$resultado[$intervalo['queue']][3]+$resultado[$intervalo['queue']][4]+$resultado[$intervalo['queue']][5]+$resultado[$intervalo['queue']][6];
           //promedio de los intervalos por cola
            $resultado[$intervalo['queue']]['tiempo_promedio'] =  $resultado[$intervalo['queue']]['suma_duracion']/$resultado[$intervalo['queue']]['cantidad_intervalos'];
        }
    }//fin de si existe al arreglo


    //convertimos los indices desde "0" ordenados
    sort($resultado);
    reset($resultado);

    $arrResult['Data'] = $resultado;//toda la data
    $arrResult['NumRecords'] = count($arrResult['Data']); //contabilizamos la cantidad de datos
    $arrResult['Data'] = array_slice($arrResult['Data'], $offset, $limit);//para presentar segun el limit y offset enviado

    return $arrResult;//retorno el arreglo

    }

    function getMaxWait($time1,$time2){
        if($time1 > $time2) {
            return $time1;
        }elseif($time1 < $time2){
            return $time2;
        }else {
            return $time1;
        }
    }


}

?>
