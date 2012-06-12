<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-18                                               |
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
  $Id: paloSantoMissedCalls.class.php,v 1.1 2011-04-25 09:04:41 Eduardo Cueva ecueva@palosanto.com Exp $ */
class paloSantoMissedCalls{
    var $_DB;
    var $errMsg;

    function paloSantoMissedCalls(&$pDB)
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

    function getNumCallingReport($date_start, $date_end, $filter_field, $filter_value, $sExtension)
    {
	$where = "";
	$arrParam = array();
        if(isset($filter_field) & $filter_field !=""){
            $where = " AND $filter_field like ? ";
	    $arrParam = array("$filter_value%");
	}
	$dates = array($date_start, $date_end);
	$arrParam = array_merge($dates,$arrParam);

	$query   = "select COUNT(*) from cdr where (lastapp = 'Dial' OR lastapp = 'Hangup' OR lastapp = 'Voicemail') AND calldate >= ? AND calldate <= ? $where order by calldate desc;";
        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getCallingReport($date_start, $date_end, $filter_field, $filter_value, $sExtension)
    {
	$where    = "";
        $arrParam = array();
        if(isset($filter_field) & $filter_field !=""){
            $where    = " AND $filter_field like ? ";
            $arrParam = array("$filter_value%");
        }
	$dates = array($date_start, $date_end);
	$arrParam = array_merge($dates,$arrParam);

        $query   = "select 
		      calldate, 
		      clid, 
		      src, 
		      dst, 
		      lastapp,
		      lastdata,
		      duration,
		      billsec,
		      disposition,
		      userfield
		    from 
		      cdr 
		    where 
		      (lastapp = 'Dial' OR lastapp = 'Hangup' OR lastapp = 'Voicemail') AND
		      calldate >= ? AND 
		      calldate <= ? $where 
		    order by 
		      calldate desc";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    /**********************************************************************************/
    /*    Manejo de estados:
	  1)    ext1 => call => ext2   
			* lastapp = Dial
			* billsec != 0
			* disposition = ANSWERED
			* status = CONTESTADA
	  2)	ext1 => call => ext2
			* lastapp = Dial
			* billsec = 0
			* disposition = NO ANSWER
			* status = NO CONTESTADA SIN DEJAR VOICEMAIL
	  3)	ext1 => call => ext2
			* lastapp = Hangup
			* billsec <> 0
			* disposition = ANSWERED
			* status = NO CONTESTADA Y DEJANDO VOICEMAIL
	  4)	ext1 => call => ext2
			* lastapp = VoiceMail
			* billsec <> 0
			* disposition = ANSWERED
			* status = NO CONTESTADA Y COLGANDO CUANDO ENTRA EN EL VOICEMAIL
	  5)    ext1 => call => ext2
			* lastapp = Dial
			* billsec = 0
			* disposition = ANSWERED
			* status = SE CONTESTA UNAS MILESIMAS DE SEGUNDOS ANTES DE CERRAR
	  6)	ext1 => call => ext2
			* lastapp = Hangup
			* billsec = 0
			* disposition = BUSY
			* status = SE CONTESTA UNAS MILESIMAS DE SEGUNDOS ANTES DE CERRAR
    /*
    /* funcion que recibe el arreglo de datos de llamadas y retorna el arreglo con los
    /* datos que seran mostrados en el reporte
    /**********************************************************************************/

     function showDataReport($arrData, $total)
    {	
	$result = array();
	$result2 = array();
	$arrTmpData = array();
	$arrCallsNoAnswer = array();
	//obteniendo el arreglo de combinaciones: array("412-410","412-420","420-412")
	if(is_array($arrData) && $total>0){
	    foreach($arrData as $key => $value){
		$arrTmp[0] = $value['calldate'];
		$arrTmp[1] = $value['src'];
		$arrTmp[2] = $value['dst'];
		$arrTmp[3] = $value['lastapp'];
		$arrTmp[4] = $value['billsec'];
		$arrTmp[5] = $value['disposition'];

		$keyTmp = $value['src']."-".$value['dst'];
		if(empty($arrTmpData))
		    $arrTmpData[] = $keyTmp;
		else{
		    if(!in_array($keyTmp, $arrTmpData))
			$arrTmpData[] = $keyTmp;
		}
	    }
	}
	$size = count($arrTmpData);
	$arrSal = array();
	//agregado cada registo al arreglo de combinaciones: 
	//array("412-410" => array("calldate"=>"25-10-2011","src"=>"412","dst"=>"410","lastapp"=>"Dial","billsec"=>"96","disposition"=>"ANSWERED"))
	if($size > 0){
	    foreach($arrData as $key => $value){
		$arrTmp[0] = $value['calldate'];
		$arrTmp[1] = $value['src'];
		$arrTmp[2] = $value['dst'];
		$arrTmp[3] = $value['lastapp'];
		$arrTmp[4] = $value['billsec'];
		$arrTmp[5] = $value['disposition'];
		$keyTmp = $value['src']."-".$value['dst'];
		$arrSal["$keyTmp"][] = $arrTmp;
	    }
	}
	//segmentar la información a lo deseado
	for($i=0; $i<$size; $i++){
	    $cont  = 0; 
	    $calls = $arrTmpData[$i];
	    $timeLimit = $this->getTimeLastCallDestination($arrSal,$calls);
	    // se recorre cada arreglo de llamadas de $calls en particular
	    foreach($arrSal[$calls] as $key => $value){
		$arrTmp2[0] = date('d-M-Y H:i:s',strtotime($value[0]));//calldate
		// TODO es posible tratar de sacar el canal de origen o destino si es que no existe el origen o destino
		$arrTmp2[1] = trim(($value[1]!="")?$value[1]:_tr("UNKNOWN"));//src
		$arrTmp2[2] = trim(($value[2]!="")?$value[2]:_tr("UNKNOWN"));//dst
		$lastapp = trim(strtoupper($value[3]));//lastapp
		$billsec = trim($value[4]);//billsec
		$disposition = trim(strtoupper($value[5]));//disposition
		if($lastapp === "DIAL" & $disposition == "ANSWERED" & $billsec > 0){
		    $arrCallsNoAnswer[] = $arrTmp2;
		    break;
		}else{ 
		    if($arrTmp2[0] > $timeLimit)
			$cont++;
		    if($lastapp === "DIAL" & $disposition == "NO ANSWER" & $billsec == 0)
			$arrTmp2[5] = _tr("NO ANSWER");
		    elseif($lastapp === "HANGUP" & $disposition == "ANSWERED" & $billsec > 0)
			$arrTmp2[5] = _tr("NO ANSWER - VOICEMAIL");
		    elseif($lastapp === "VOICEMAIL" & $disposition == "ANSWERED" & $billsec > 0)
			$arrTmp2[5] = _tr("NO ANSWER - VOICEMAIL");
		    elseif($lastapp === "DIAL" & $disposition == "ANSWERED" & $billsec == 0)
			$arrTmp2[5] = _tr("NO ANSWER");
		    elseif($disposition == "BUSY")
			$arrTmp2[5] = _tr("NO ANSWER");
		    else
			$arrTmp2[5] = _tr($disposition);
		    $arrTmp2[3] = $this->getTimeToLastCall($arrTmp2[0]);
		    $result2[$calls][] = $arrTmp2;
		}
	    }
	    if($cont != 0){
		$result2[$calls][0][4] = $cont;
		$result[] = $result2[$calls][0];
	    }
	}
	// verificando si existe alguna llamada por parte del destino hacia la fuente y esta haya
        // sido contestada pra removerla del arreglo princial, debido a que se dio una respuesta 
        // de llamada por parte del destino.
	foreach($arrCallsNoAnswer as $key => $value){
	    $date = $value[0];
	    $src  = $value[1];
	    $dst  = $value[2];
	    $time = strtotime($date);
	    foreach($result as $key2 => $value2){
		$date2 = $value2[0];
		$src2  = $value2[1];
		$dst2  = $value2[2];
		$time2 = strtotime($date2);
		if($src2 == $dst && $dst2 == $src){
		    if($time > $time2){// entonces se remueve el elemento de result ya que ya fue devuelta la llamada
			unset($result[$key2]);
		    }
		}
	    }
	}

	return $result;
    }

    // se debe obtener en base al arreglo de objetos de conbinacion la fecha de la ultima llamada exitosa desde el
    // destino hasta la fuente, para ello se debe pasar el arreglo de combinacion junto con la extension fuente y destino
    // la salida de respuesta mostrará la fecha de la ultima llamada exitosa que realizo el destino
    function getTimeLastCallDestination($arrData, $callsid)
    {
	$exts = explode("-",$callsid);
	$src  = $exts[0];
	$dst  = $exts[1];
	$timeLimit = "";
	$calls = "$dst-$src";
	if(in_array("$dst-$src",$arrData)){
	  foreach($arrData[$calls] as $key => $value){
	      $calldate    = date('d-M-Y H:i:s',strtotime($value[0]));//calldate
	      $src2        = trim(($value[1]!="")?$value[1]:_tr("UNKNOWN"));//src
	      $dst2        = trim(($value[2]!="")?$value[2]:_tr("UNKNOWN"));//dst
	      $lastapp     = trim(strtoupper($value[3]));//lastapp
	      $billsec     = trim($value[4]);//billsec
	      $disposition = trim(strtoupper($value[5]));//disposition
	      if($lastapp === "DIAL" & $disposition == "ANSWERED" & $billsec > 0){
		  if($src == $dst2 && $dst == $src2){
		      $timeLimit = $calldate;
		      break;
		  }
	      }
	  }
	}
	return $timeLimit;
    }

    /**********************************************************************************/
    /* Escala de tiempos en seguntos:
    /*		1 minuto => 60 segundos
    /*		1 hora   => 3600 segundos
    /*		1 dia    => 86400 segundos
    /*		1 mes	 => 2592000 segundos
    /*		1 año	 => 31104000 segundos
    /**********************************************************************************/
    function getTimeToLastCall($time)
    {
	$anios    = "";
	$meses    = "";
	$dias     = "";
	$horas    = "";
	$minutos  = "";
	$segundos = "";
	$result   = "";
	$now = strtotime(date('Y-m-d H:i:s'));
	$time = $now - strtotime($time);
	if($time >= 31104000){//esta en años
	    //convirtiendo segundos en años
	    $anios    = ($time/31104000);
	    //convirtiendo años decimales a meses
	    $meses    = ($anios - floor($anios)) * 12;
	    //convirtiendo meses decimales a dias
	    $dias     = ($meses - floor($meses)) * 30;
	    //convirtiendo dias decimales a horas
	    $horas    = ($dias - floor($dias)) * 24;
	    //convirtiendo horas decimales a minutos
	    $minutos  = ($horas - floor($horas)) * 60;
	    //convirtiendo minutos decimales a segundos
	    $segundos = ($minutos - floor($minutos)) * 60;
	    $result   = floor($anios)." "._tr("year(s)")." ".floor($meses)." "._tr("month(s)")." ".floor($dias)." "._tr("day(s)")." ".floor($horas)." "._tr("hour(s)")." ".floor($minutos)." "._tr("minute(s)")." ".floor($segundos)." "._tr("second(s)");
	}elseif($time < 31104000 && $time >= 2592000){//esta en meses
	    //convirtiendo segundos a meses
	    $meses    = ($time/2592000);
	    //convirtiendo meses decimales a dias
	    $dias     = ($meses - floor($meses)) * 30;
	    //convirtiendo dias decimales a horas
	    $horas    = ($dias - floor($dias)) * 24;
	    //convirtiendo horas decimales a minutos
	    $minutos  = ($horas - floor($horas)) * 60;
	    //convirtiendo minutos decimales a segundos
	    $segundos = ($minutos - floor($minutos)) * 60;
	    $result   = floor($meses)." "._tr("month(s)")." ".floor($dias)." "._tr("day(s)")." ".floor($horas)." "._tr("hour(s)")." ".floor($minutos)." "._tr("minute(s)")." ".floor($segundos)." "._tr("second(s)");
	}elseif($time < 2592000 && $time >= 86400){//esta en dias
	    //convirtiendo segundos a dias
	    $dias     = ($time/86400);
	    //convirtiendo dias decimales a horas
	    $horas    = ($dias - floor($dias)) * 24;
	    //convirtiendo horas decimales a minutos
	    $minutos  = ($horas - floor($horas)) * 60;
	    //convirtiendo minutos decimales a segundos
	    $segundos = ($minutos - floor($minutos)) * 60;
	    $result   = floor($dias)." "._tr("day(s)")." ".floor($horas)." "._tr("hour(s)")." ".floor($minutos)." "._tr("minute(s)")." ".floor($segundos)." "._tr("second(s)");
	}elseif($time < 86400 && $time >= 3600){//esta en horas
	    //convirtiendo segundos a horas
	    $horas    = ($time/3600);
	    //convirtiendo horas decimales a minutos
	    $minutos  = ($horas - floor($horas)) * 60;
	    //convirtiendo minutos decimales a segundos
	    $segundos = ($minutos - floor($minutos)) * 60;
	    $result   = floor($horas)." "._tr("hour(s)")." ".floor($minutos)." "._tr("minute(s)")." ".floor($segundos)." "._tr("second(s)");
	}elseif($time < 3600 && $time >= 60){//esta en minutos
	    //convirtiendo segundos a minutos
	    $minutos  = ($time/60);
	    //convirtiendo minutos decimales a segundos
	    $segundos = ($minutos - floor($minutos)) * 60;
	    $result   = floor($minutos)." "._tr("minute(s)")." ".floor($segundos)." "._tr("second(s)");
	}else{//esta en segundo
	    $result   = floor($time)." "._tr("second(s)");
	}
	return $result;
    }

    function is_date($str) 
    { 
	$stamp = strtotime($str); 
	if (!is_numeric($stamp))
	    return FALSE; 

	$month = date('m', $stamp); 
	$day   = date('d', $stamp); 
	$year  = date('Y', $stamp); 
	if (checkdate($month, $day, $year)) 
	    return TRUE; 

	return FALSE; 
    }

    function getDataByPagination($arrData, $limit, $offset)
    {
	$arrResult = array();
	$limitInferior = "";
	$limitSuperior = "";
	if($offset == 0){
	    $limitInferior = $offset;
	    $limitSuperior = $offset + $limit -1;
	}else{
	    $limitInferior = $offset + 1;
	    $limitSuperior = $offset + $limit + 1;
	}
	$cont = 0;
	foreach($arrData as $key => $value){
	    if($key > $limitSuperior){
		$cont = 0;
		break;
	    }
	    if($key >= $limitInferior & $key <= $limitSuperior){
		$arrResult[]=$arrData[$key]; //echo $key."<br />";
	    }

	}
	//echo "limit: $limit , offset $offset , $limitInferior-$limitSuperior   ";
	//echo count($arrResult);
	return $arrResult;
    }
}
?>
