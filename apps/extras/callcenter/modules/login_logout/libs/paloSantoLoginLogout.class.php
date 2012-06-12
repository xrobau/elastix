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
class paloSantoLoginLogout
{
    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloSantoLoginLogout(&$pDB)
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

    function getRegistersLoginLogout($tipo='all',$fecha_init,$fecha_final,$limit, $offset)
    {
        global $arrLang;
        global $arrLangModule;

        //validamos la fecha
        if($fecha_init!="" && $fecha_final !="" ) {
            $fecha_init = explode('-',$fecha_init);
            $fecha_final = explode('-',$fecha_final);
        }else {
            $this->msgError .= "Debe ingresarse una fecha inicio y una fecha fin";
            return false;
        }
        // pregunto si la fecha inicial existe
        if( is_array( $fecha_init ) && count( $fecha_init )==3 ) {
            $year_init  = $fecha_init[0];
            $month_init = $fecha_init[1];
            $day_init   = $fecha_init[2];
            $fechaInicial = $fecha_init[0]."-".$fecha_init[1]."-".$fecha_init[2]." "."00:00:00";
            //$fechaFinal = $fecha_init[0]."-".$fecha_init[1]."-".$fecha_init[2]." "."23:59:59";
            $fechaFinal = $fecha_final[0]."-".$fecha_final[1]."-".$fecha_final[2]." "."23:59:59";
        //si fecha_init y fecha_end no existen envio un mensaje de error
        }else {
            $this->msgError .= "Fecha Inicio y/o Fecha Fin no valida";
            return false;
        }

        $arreglo = array();
        //para filtrar por fechas
        $where = " AND datetime_init between '{$fechaInicial}' AND '{$fechaFinal}'  ";
        if($tipo=='D'){//hacemos consulta DETALLADA

            $arr_result = FALSE;
            $this->errMsg = "";

            $sPeticionSQL = "SELECT
                agent.number as number,
                agent.name as name,
                a.id,
                agent.name as name,
                (a.datetime_init) as datetime_init,
                if((a.datetime_end) is null, now(), a.datetime_end) as datetime_end,
                TIME_TO_SEC(TIMEDIFF((if((a.datetime_end) is null, now(), a.datetime_end)),(datetime_init))) as total_sesion,
                ((
                    if((select sum(ce.duration) from call_entry as ce where ce.datetime_init between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end)) and ce.id_agent=agent.id) is null, 0, 
                    (select sum(ce.duration) from call_entry as ce where ce.datetime_init between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end)) and ce.id_agent=agent.id)
                    ) +
                    if((select sum(duration) from calls where start_time between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end)) and id_agent=agent.id) is null,0,(select sum(duration) from calls where start_time between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end)) and id_agent=agent.id ))
                    ) / 
                    TIME_TO_SEC(TIMEDIFF((if((a.datetime_end)is null,now(),a.datetime_end)),a.datetime_init))
                ) * 100 as service,
                
                (
                        if((select sum(duration) from call_entry where datetime_init between a.datetime_init and  (if((a.datetime_end)is null,now(),a.datetime_end)) and id_agent=agent.id) is null, 0, (select sum(duration) from call_entry where datetime_init between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end))  and id_agent=agent.id)
                        ) +
                        if((select sum(duration) from calls where start_time between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end)) and id_agent=agent.id) is null, 0 , (select sum(duration) from calls where start_time between a.datetime_init and (if((a.datetime_end)is null,now(),a.datetime_end)) and id_agent=agent.id))
                ) as total_sumas_in_out,
                if((a.datetime_end) is null, 'En Linea', '') as estado 
                FROM 
                    audit a, 
                    agent 
                WHERE a.datetime_init between '{$fechaInicial}' AND '{$fechaFinal}'                
                        and a.id_agent = agent.id and id_break is null
		ORDER BY agent.name, a.datetime_init" ;

        }
        else if($tipo=='G'){//hacemos consulta GENERAL
            $sPeticionSQL =  "SELECT
    agent.number as number,
    agent.id,
    agent.name,
    min(audit.datetime_init) as datetime_init,

    if (
        (select count(au.datetime_end) from audit au, agent ag where au.datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and au.id_agent=agent.id and au.datetime_end is null group by au.id_agent) is null,
        max(audit.datetime_end),
        now()
    ) as datetime_end,

    (
        if(
            (select sum(duration) from call_entry where datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and call_entry.id_agent=agent.id) is null,
            0,
            (select sum(duration) from call_entry where datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and call_entry.id_agent=agent.id)
        ) +
        if(
            (select sum(duration) from calls where start_time between '{$fechaInicial}' AND '{$fechaFinal}' and calls.id_agent=agent.id)is null,
            0,
            (select sum(duration) from calls where start_time between '{$fechaInicial}' AND '{$fechaFinal}' and calls.id_agent=agent.id)
        )
    ) as  total_sumas_in_out,

    (
        TIME_TO_SEC(
            sec_to_time(
                if(
                    (select sum(duration) from call_entry where datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and call_entry.id_agent=agent.id) is null,
                    0,
                    (select sum(duration) from call_entry where datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and call_entry.id_agent=agent.id)
                ) +
                if(
                    (select sum(duration) from calls where start_time between '{$fechaInicial}' AND '{$fechaFinal}' and calls.id_agent=agent.id)is null,
                    0,
                    (select sum(duration) from calls where start_time between '{$fechaInicial}' AND '{$fechaFinal}' and calls.id_agent=agent.id)
                )
            )
        )
        /
        TIME_TO_SEC(
            TIMEDIFF(
                if (
                    (select count(au.datetime_end) from audit au, agent ag where au.datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and au.id_agent=agent.id and au.datetime_end is null group by au.id_agent) is null,
                    max(audit.datetime_end),
                    now()
                ),
                min(audit.datetime_init)
            )
        )
    ) * 100 as service,

    TIME_TO_SEC(TIMEDIFF(
        if (
            (select count(au.datetime_end) from audit au, agent ag where au.datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and au.id_agent=agent.id and au.datetime_end is null group by au.id_agent) is null,
            max(audit.datetime_end),
            now()
        ),
        min(audit.datetime_init)
    ))  as total_sesion,

    if (
        (select count(au.datetime_end) from audit au, agent ag where au.datetime_init between '{$fechaInicial}' AND '{$fechaFinal}' and au.id_agent=agent.id and au.datetime_end is null group by au.id_agent) is null,
        '',
        'En linea'
    ) as estado

FROM
    audit, agent

WHERE
    audit.id_agent=agent.id
    and audit.datetime_init between '{$fechaInicial}' AND '{$fechaFinal}'
    AND audit.id_break is null

GROUP BY agent.id

ORDER BY agent.name";

//echo $sPeticionSQL."<br>";

        }
        if(!empty($limit)) {
	        $sPeticionSQL  .= " LIMIT $limit OFFSET $offset";
        }

//echo $sPeticionSQL."<br><br>";

        $arr_result =& $this->_DB->fetchTable($sPeticionSQL, true);
        if (!is_array($arr_result)) {
            $arr_result = FALSE;
            $this->errMsg = $this->_DB->errMsg;
        }

//print_r($arr_result);


    $arrResult['Data'] = $arr_result;//toda la data
    $arrResult['NumRecords'] = count($arrResult['Data']); //contabilizamos la cantidad de datos

    return $arrResult;//retorno el arreglo

    }

    /*
        Esta funcion recibe un dos tiempos y retorna la suma de ellos
    */
    function getSumTime($time1,$time2) {

 	if( is_null($time1) ) {
 	    $time1 = "00:00:00";
 	}
 	if( is_null($time2) ) {
 	    $time2 = "00:00:00";
 	}
 
 	$SQLConsulta = "select addtime('{$time1}','{$time2}') duracion";
 	$resConsulta = $this->_DB->getFirstRowQuery($SQLConsulta,true); 

 	if(!$resConsulta)  {
             $this->msgError = $this->errMsg;
 	    return false;
 	} else {
 	   return $resConsulta['duracion'];
 	}
    }


}

?>
