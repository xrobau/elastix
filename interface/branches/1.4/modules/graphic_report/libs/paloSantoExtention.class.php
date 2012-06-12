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

    function ObtainNumExtention($date_ini, $date_fin, $ext, $calls_io)
    {
        if( strlen($ext) == 0 )
            return 0;

        $query = "SELECT count(*) FROM cdr";

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
            $query = "SELECT count(*) FROM cdr WHERE dst = '$ext' ";
        else//if( $io == "in" )
            $query = "SELECT count(*) FROM cdr WHERE src = '$ext' ";
      
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

    function loadTrunks($trunk, $min_call, $date_ini, $date_fin)
    {
        $str = "";
        if( strlen($date_ini) >= 5 ){
            if( strlen($date_fin) <= 5 )
                $str .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )";
            else{
                $str .= " and ( TO_DAYS( DATE(calldate) ) > TO_DAYS( '$date_ini') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_ini') )  ";
                $str .= " and ( TO_DAYS( DATE(calldate) ) < TO_DAYS( '$date_fin') OR TO_DAYS( DATE(calldate) ) = TO_DAYS( '$date_fin') ) ";
            }
        }

        $query = "";
        if( $min_call == "min" ){// # minutos
            $query = "SELECT entrante.totIN, saliente.totOut
                      FROM (SELECT sum(duration) as totIN
                            FROM cdr
                            WHERE channel LIKE '%$trunk%' ".$str." ) as entrante,
                           (SELECT sum(duration) as totOut
                            FROM cdr
                            WHERE dstchannel LIKE '%$trunk%' ".$str." ) as saliente ";
        }
        else{// # llamadas
            $query = "SELECT entrante.numIN, saliente.numOut
                      FROM (SELECT count(duration) as numIN
                            FROM cdr
                            WHERE channel LIKE '%$trunk%' ".$str." ) as entrante,
                           (SELECT count(duration) as numOut
                            FROM cdr
                            WHERE dstchannel LIKE '%$trunk%' ".$str." ) as saliente ";
        }

        $result = $this->_DB->fetchTable($query, false);
        if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }
}
?>