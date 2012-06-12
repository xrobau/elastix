<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-3                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-01 10:09:57 jjvega Exp $ */

//include_once "libs/paloSantoQueue.class.php";

class paloSantoExtention {
    var $_DB;
    var $errMsg;

    function paloSantoExtention(&$pDB)
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

    private function _src_ext($ext)
    {
        return "substring_index(channel,'-',1) regexp '^[A-Za-z0-9]+/$ext$'";
    }

    private function _dst_ext($ext)
    {
        return "substring_index(dstchannel,'-',1) regexp '^[A-Za-z0-9]+/$ext$'";
    }

    function ObtainNumExtention($date_ini, $date_fin, $ext, $calls_io)
    {
        if( strlen($ext) == 0 )
            return 0;

        $query = "SELECT count(*) FROM cdr";

        if($calls_io=="Incoming_Calls")
            $query .= " WHERE ".$this->_dst_ext($ext) ;
        else if($calls_io=="Outcoming_Calls")
            $query .= " WHERE ".$this->_src_ext($ext) ;
        else
            $query .= " WHERE ((".$this->_src_ext($ext).") OR (".$this->_dst_ext($ext)."))" ;

      
        if( strlen($date_ini) >= 5 ){
            if( strlen($date_fin) <= 5 )
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )";
            else{
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )  ";
                $query .= " and ( TO_DAYS( DATE(calldate) ) < TO_DAYS( '$date_fin') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_fin') ) ";
            }
        }

        $result = $this->_DB->getFirstRowQuery($query);

        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
    }

    function ObtainNumExtentionByIOrO($date_ini, $date_fin, $ext, $io)
    {
        if( strlen($ext) == 0 )
            return 0;

        if( $io == "in" )
            $query = "SELECT count(*) FROM cdr WHERE ".$this->_dst_ext($ext)." ";
        else//if( $io == "in" )
            $query = "SELECT count(*) FROM cdr WHERE ".$this->_src_ext($ext)." ";
      
        if( strlen($date_ini) >= 5 ){
            if( strlen($date_fin) <= 5 )
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )";
            else{
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )  ";
                $query .= " and ( TO_DAYS( DATE(calldate) ) < TO_DAYS( '$date_fin') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_fin') ) ";
            }
        }

        $result = $this->_DB->getFirstRowQuery($query);

        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
    }

    function ObtainExtention($limit, $offset, $date_ini, $date_fin, $ext, $calls_io)
    {
        if( strlen($ext) == 0 )
            return 0;

        $query = "SELECT *
                  FROM cdr";
        
        if($calls_io=="Incoming_Calls")
            $query .= " WHERE dst = '$ext'" ;
        else if($calls_io=="Outcoming_Calls")
            $query .= " WHERE src = '$ext'" ;
        else
            $query .= " WHERE (src = '$ext' OR dst = '$ext')" ;

        if( strlen($date_ini) >= 5 ){
            if( strlen($date_fin) <= 5 )
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )";
            else{
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )  ";
                $query .= " and ( TO_DAYS( DATE(calldate) ) < TO_DAYS( '$date_fin') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_fin') ) ";
            }
        }

        $query .= " ORDER BY calldate desc ";
        $query .= " LIMIT $limit OFFSET $offset ";

        $result = $this->_DB->fetchTable($query, true);

        if($result==FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    function loadExtentions()
    {
        $query = "SELECT id, user FROM devices ORDER BY 1 asc";

        $result = $this->_DB->fetchTable($query, true);

        if($result == FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    function loadCdrByExtencion($ext)
    {
        $query = "SELECT *
                  FROM cdr
                  WHERE src like '%$ext%' OR dst '%$ext%' ";

        $result = $this->_DB->fetchTable($query, true);

        if($result == FALSE)
        {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    function countQueue($queue, $date_ini, $date_fin)
    {
        $query = "SELECT count(*) FROM cdr WHERE dst='$queue' ";

        if( strlen($date_ini) >= 5 ){
            if( strlen($date_fin) <= 5 )
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )";
            else{
                $query .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )  ";
                $query .= " and ( TO_DAYS( DATE(calldate) ) < TO_DAYS( '$date_fin') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_fin') ) ";
            }
        }

        $result = $this->_DB->getFirstRowQuery($query);

        if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }

        return $result;
    }

    /**
     * Procedimiento para consultar estadísticas sobre los CDRs de Asterisk, 
     * clasificados por troncal.
     *
     * @param   mixed   $trunk  Nombre de la troncal, o arreglo de troncales.
     * @param   string  $sTipoReporte   'min' para total de segundos entrantes y
     *                                  salientes, o 'numcall' para número de 
     *                                  llamadas entrantes y salientes
     * @param   string  $sFechaInicial  Fecha de inicio de rango yyyy-mm-dd
     * @param   string  $sFechaFinal    Fecha de final de rango yyyy-mm-dd
     *
     * @result  mixed   NULL en caso de error, o una tupla con 1 elemento que es
     *                  tupla de 2 valores para (entrante,saliente)
     */
    function loadTrunks($trunk, $sTipoReporte, $sFechaInicial, $sFechaFinal)
    {
        if (!is_array($trunk)) $trunk = array($trunk);
        $sCondicionSQL_channel = implode(' OR ', array_fill(0, count($trunk), 'channel LIKE ?'));
        $sCondicionSQL_dstchannel = implode(' OR ', array_fill(0, count($trunk), 'dstchannel LIKE ?'));

        /* Se asume que la lista de troncales es válida, y que todo canal
           empieza con la troncal correspondiente */
        if (!function_exists('loadTrunks_troncal2like')) {
            // Búsqueda por DAHDI/1 debe ser 'DAHDI/1-%'
            function loadTrunks_troncal2like($s) { return $s.'-%'; }
        }
        $paramTrunk = array_map('loadTrunks_troncal2like', $trunk);
        
        // Construir la sentencia SQL correspondiente
        switch ($sTipoReporte) {
        case 'min':
            $sPeticionSQL = <<<SQL_LOADTRUNKS_MIN
SELECT 
    IFNULL(SUM(IF(($sCondicionSQL_channel), duration, 0)), 0) AS totIn,
    IFNULL(SUM(IF(($sCondicionSQL_dstchannel), duration, 0)), 0) AS totOut
FROM cdr
WHERE calldate >= ? AND calldate <= ?
SQL_LOADTRUNKS_MIN;
            $paramSQL = array_merge($paramTrunk, $paramTrunk, array($sFechaInicial.' 00:00:00', $sFechaFinal.' 23:59:59'));
            break;
        case 'numcall':
            $sPeticionSQL = <<<SQL_LOADTRUNKS_NUMCALL
SELECT 
    IFNULL(SUM(IF(($sCondicionSQL_channel), 1, 0)), 0) AS numIn,
    IFNULL(SUM(IF(($sCondicionSQL_dstchannel), 1, 0)), 0) AS numOut
FROM cdr
WHERE calldate >= ? AND calldate <= ?
SQL_LOADTRUNKS_NUMCALL;
            $paramSQL = array_merge($paramTrunk, $paramTrunk, array($sFechaInicial.' 00:00:00', $sFechaFinal.' 23:59:59'));
            break;
        default:
            $this->errMsg = '(internal) Invalid report type';
            return NULL;
        }
        $result = $this->_DB->fetchTable($sPeticionSQL, FALSE, $paramSQL);
        if (!is_array($result)) {
            $this->errMsg = '(internal) Failed to fetch stats - '.$this->_DB->errMsg;
            return array();
        }
        return $result;
    }
}
?>
