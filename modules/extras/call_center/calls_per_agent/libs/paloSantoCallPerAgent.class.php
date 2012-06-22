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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class paloSantoCallsAgent {

    function paloSantoCallsAgent(&$pDB)
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
    
    function obtenerCallsAgent($limit, $offset, $date_start="", $date_end="", $field_name, $field_pattern/*,$status="ALL"*/,$calltype="",$troncales=NULL)
    {
        $n_field = 0;
        $sqlQuery = "";
        $strWhereCalls = "";
        $strWheredateCallEn = "";

        if(!isset($field_name['field_name']))
            $field_name['field_name'] = "";

        $field_name_1 = $field_name['field_name'];

        if(!isset($field_name['field_name_1']))
            $field_name['field_name_1'] = "";

        $field_name_2 = $field_name['field_name_1'];

        if(!isset($field_pattern['field_pattern']))
            $field_pattern['field_pattern']="";
        if(!isset($field_pattern['field_pattern_1']))
            $field_pattern['field_pattern_1']="";

        $field_pattern_1 = strtoupper($field_pattern['field_pattern']);
        $field_pattern_2 = strtoupper($field_pattern['field_pattern_1']);
        
        //Campos diferentes en tablas 
        if(!empty($date_start)){
            $strWhereCalls .= "AND cal.start_time between '$date_start' ";
            $strWheredateCallEn .= " AND cale.datetime_entry_queue between '$date_start' ";
        }
        if(!empty($date_end)){
           $strWhereCalls .= " AND '$date_end' ";
            $strWheredateCallEn .= " AND '$date_end' ";
        }
        

        if(($field_name_1==$field_name_2)&&($field_pattern_1!="")&&($field_pattern_2!=""))
        { 
            $this->construirCondicionIguales($n_field,$field_name_1,$field_pattern_1,$strWhereCalls,$strWheredateCallEn);
            $this->construirCondicionIguales($n_field,$field_name_2,$field_pattern_2,$strWhereCalls,$strWheredateCallEn);
        } else {
            $this->construirCondicion($field_name_1,$field_pattern_1,$strWhereCalls,$strWheredateCallEn);
            $this->construirCondicion($field_name_2,$field_pattern_2,$strWhereCalls,$strWheredateCallEn);
        }


        //if(!empty($status) && $status!="ALL") $strWhere .= " AND disposition = '$status' ";

        $sqlQueryCalls = "select age.number,age.name,'Outbound' as type,cam.queue,count(*)            calls_answ ,sec_to_time(sum(duration)),                                                  sec_to_time(avg(duration)),sec_to_time(max(duration))
            from calls cal
            inner join campaign cam on cam.id=cal.id_campaign
            left join agent age on age.id=cal.id_agent
            where status='Success'";
        if(!empty($strWhereCalls)) $sqlQueryCalls .= " $strWhereCalls group by age.number"; 
         $sqlQueryCallEn = "select age.number,age.name,'Inbound' as type,que.queue,count(*)        calls_answ, sec_to_time(sum(duration)),                                                 sec_to_time(avg(duration)),sec_to_time(max(duration))
            from call_entry cale
            left join agent age on age.id=cale.id_agent 
            inner join queue_call_entry que on que.id=cale.id_queue_call_entry
            where status='terminada'";
        if(!empty($strWheredateCallEn)) $sqlQueryCallEn .= " $strWheredateCallEn group by age.number"; 
        
        if($field_name_1=="type")
        {
            if($field_pattern_1=="INBOUND" || $field_pattern_1=="IN"){   
                $sqlQuery .= $sqlQueryCallEn;
            }else if($field_pattern_1=="OUTBOUND"|| $field_pattern_1=="OUT"){
                $sqlQuery .= $sqlQueryCalls;
            }else if($field_pattern_1==""){
                $sqlQuery=$sqlQueryCalls." union ".$sqlQueryCallEn;
            }else $sqlQuery=$sqlQueryCalls." union ".$sqlQueryCallEn;
        } 
        if($field_name_2=="type"){
            if($field_pattern_2=="INBOUND" || $field_pattern_2=="IN"){   
                $sqlQuery .= $sqlQueryCallEn;
            }else if($field_pattern_2=="OUTBOUND" || $field_pattern_2=="OUT"){
                $sqlQuery .= $sqlQueryCalls;
            }else if($field_pattern_2==""){
                $sqlQuery=$sqlQueryCalls." union ".$sqlQueryCallEn;
            } else $sqlQuery=$sqlQueryCalls." union ".$sqlQueryCallEn;
        } 
        if($field_name_1=="type" && $field_name_2=="type"){
            $sqlQuery=$sqlQueryCalls." union ".$sqlQueryCallEn;
        }
        if($field_name_1!="type" && $field_name_2!="type"){
            $sqlQuery=$sqlQueryCalls." union ".$sqlQueryCallEn;
        }

        $sqlQuery .= " order by number";

        if(!empty($limit)) {
	        $sqlQuery  .= " LIMIT $limit OFFSET $offset";
        }
        //echo $sqlQuery;
        $result=$this->_DB->fetchTable($sqlQuery);
        $arrResult['Data'] = $result;

	$arrResult['NumRecords'] = count($arrResult['Data']);
//echo $sqlQuery."<br>";
        return $arrResult;
    }
    function construirCondicion($field_name,$field_pattern, &$strWhereCalls, &$strWheredateCallEn )
    {
        if(!empty($field_name) and !empty($field_pattern)){
            if ($field_name=="queue") {
                $strWhereCalls .= " AND cam.$field_name like '%$field_pattern%' ";
                $strWheredateCallEn .= " AND que.$field_name like '%$field_pattern%' ";
            }else if($field_name=="number"){
                 $strWhereCalls .= " AND age.$field_name like '%$field_pattern%' ";
                $strWheredateCallEn .= " AND age.$field_name like '%$field_pattern%' ";
            }else if($field_name=="type"){}
            else{
                $strWhereCalls .= " AND cal.$field_name like '%$field_pattern%' ";
                $strWheredateCallEn .= " AND cale.$field_name like '%$field_pattern%' ";
            }
        }
    }
    function construirCondicionIguales(&$n_field,$field_name,$field_pattern, &$strWhereCalls, &$strWheredateCallEn )
    {
        if(!empty($field_name) /*and !empty($field_pattern)*/){
            if ($field_name=="queue") {
                if($n_field==0){
                    $strWhereCalls .= " AND (cam.$field_name like '%$field_pattern%' ";
                    $strWheredateCallEn .= " AND (que.$field_name like '%$field_pattern%' ";
                    $n_field++;
                 } else {
                    $strWhereCalls .= " OR cam.$field_name like '%$field_pattern%') ";
                    $strWheredateCallEn .= " OR que.$field_name like '%$field_pattern%') ";
                    $n_field=0;
                 }
            }else if($field_name=="number"){
                if($n_field==0){
                    $strWhereCalls .= " AND (age.$field_name like '%$field_pattern%' ";
                    $strWheredateCallEn .= " AND (age.$field_name like '%$field_pattern%' ";
                    $n_field++;
                } else {
                    $strWhereCalls .= " OR age.$field_name like '%$field_pattern%') ";
                    $strWheredateCallEn .= " OR age.$field_name like '%$field_pattern%') ";
                    $n_field=0;
                }
            }else if($field_name=="type"){}
            else{
                if($n_field==0){
                    $strWhereCalls .= " AND (cal.$field_name like '%$field_pattern%' ";
                    $strWheredateCallEn .= " AND (cale.$field_name like '%$field_pattern%' ";
                    $n_field++;
                } else {
                    $strWhereCalls .= " OR cal.$field_name like '%$field_pattern%') ";
                    $strWheredateCallEn .= " OR cale.$field_name like '%$field_pattern%') ";
                    $n_field=0;
                }
            }
        }
    }

    /*
        Esta funcion recibe un dos tiempos y retorna la suma de ellos
    */
    function getTotalWaitTime($time1,$time2) {

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

    /*
        Esta funcion recibe la suma de las fechas y el numero de fechas que se han sumado
        y retorna el promedio de las fechas.
    */
    function getPromedioFecha($avgPromedio,$numRegistros){
        $valido = true;
        $SQLConsulta = "select time_to_sec('{$avgPromedio}') sec";
        $resConsulta = $this->_DB->getFirstRowQuery($SQLConsulta,true);

        if(!$resConsulta)  {
             $this->msgError = $this->errMsg;
 	    $valido = false;
 	} else {
 	   $sumaInSec = $resConsulta['sec'];
 	}

        if($valido && $numRegistros>0) {
            $sumaInSec = $sumaInSec /  $numRegistros ;

            $SQLConsulta = "select sec_to_time('{$sumaInSec}') date";
            $resConsulta = $this->_DB->getFirstRowQuery($SQLConsulta,true);

            if(!$resConsulta)  {
                $this->msgError = $this->errMsg;
                return false;
            } else {
 	      return $resConsulta['date'];
 	}
        }else {
            return false;
        }
    }

    /*
        Esta funcion recibe dos tiempos y retorna el tiempo mayor.
    */
    function getFechaMayor($time1,$time2){

        $SQLTime1 = "select time_to_sec('{$time1}') time1";
        $SQLTime2 = "select time_to_sec('{$time2}') time2";

        $resTime1 = $this->_DB->getFirstRowQuery($SQLTime1,true);
        $resTime2 = $this->_DB->getFirstRowQuery($SQLTime2,true);

        if(!$resTime1 || !$resTime2)  {
            $this->msgError = $this->errMsg;
            $valido = false;
 	} else {
            $time1Sec = $resTime1['time1'];
            $time2Sec = $resTime2['time2']; 

            if($time1Sec > $time2Sec) {
                return $time1;
            } elseif($time1Sec < $time2Sec) {
                return $time2;
            }else {
                return $time1;
            }
 	}

    }
}
?>
