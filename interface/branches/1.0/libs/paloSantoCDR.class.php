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

class paloSantoCDR {

    function paloSantoCDR(&$pDB)
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

    function obtenerCDRs($limit, $offset, $date_start="", $date_end="", $field_name="", $field_pattern="",$status="ALL",$calltype="",$troncales=NULL, $extension="")
    {
        $strWhere = "";
        if(!empty($date_start)) $strWhere .= "calldate>='$date_start' ";
        if(!empty($date_end))   $strWhere .= " AND calldate<='$date_end' ";

        if(!empty($field_name) and !empty($field_pattern)){
                  $arrPattern = explode(",",trim($field_pattern));
                  $condicion_pattern='';
                 foreach ($arrPattern as $pattern) {
                           $pattern = trim($pattern);
                       if ($pattern!="") {
                           if (ereg("/",$pattern)) {
                                  if (!ereg("-",$pattern)) $pattern=$pattern.'-';
                                  if ($field_name=='dst') continue;
                           } else if ($field_name=='dstchannel') continue;
                           $condicion_pattern.=!(empty($condicion_pattern))?' OR ':'';
                           $condicion_pattern .= (ereg("/",$pattern) && $field_name=='src'?'channel':$field_name)." like '%$pattern%'";
                       }
                  }
                  if ($condicion_pattern!="") $strWhere .= " AND ($condicion_pattern)";
        }
        if(!empty($status) && $status!="ALL") $strWhere .= " AND disposition = '$status' ";
        if(!empty($calltype) && $calltype=="outgoing"){
            if (is_array($troncales) && count($troncales)>0){
                 $condicion_troncal='';
                foreach ($troncales as $troncal){
                   $condicion_troncal.=!(empty($condicion_troncal))?' OR ':'';
                   $condicion_troncal.="dstchannel like '%$troncal-%'";
                }
                $strWhere .= " AND ($condicion_troncal)";
            }else{
                $strWhere .= " AND dstchannel like '%zap%' ";
            }
        }
        if(!empty($calltype) && $calltype=="incoming") $strWhere .= " AND channel like '%zap%' ";
        if(!empty($extension)) $strWhere .= " AND (src='$extension' OR dst='$extension') ";

        $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
        // Clausula WHERE aqui
        if(!empty($strWhere)) $query .= "WHERE $strWhere ";
        // Limit
        if(!empty($limit)) {
            $query  .= " LIMIT $limit OFFSET $offset";
        }

        $result=$this->_DB->fetchTable($query);
        $arrResult['Data'] = $result;

        $queryCount = "SELECT COUNT(*) FROM cdr ";
        // Clausula WHERE aqui
        if(!empty($strWhere)) $queryCount .= " WHERE $strWhere ";

        $arrResult['NumRecords'] = $this->_DB->getFirstRowQuery($queryCount);

        return $arrResult;
    }

    function Delete_All_CDRs($date_start="", $date_end="", $field_name="", $field_pattern="",$status="ALL",$calltype="",$troncales=NULL)
    {
        $strWhere = "";
        if(!empty($date_start)) $strWhere .= "calldate>='$date_start' ";
        if(!empty($date_end))   $strWhere .= " AND calldate<='$date_end' ";

        if(!empty($field_name) and !empty($field_pattern)) $strWhere .= " AND $field_name like '%$field_pattern%' ";
        if(!empty($status) && $status!="ALL") $strWhere .= " AND disposition = '$status' ";
        if(!empty($calltype) && $calltype=="outgoing"){
            if (is_array($troncales) && count($troncales)>0){
                 $condicion_troncal='';
                foreach ($troncales as $troncal){
                   $condicion_troncal.=!(empty($condicion_troncal))?' OR ':'';
                   $condicion_troncal.="dstchannel like '%$troncal%'";
                }
                $strWhere .= " AND ($condicion_troncal)";
            }else{
                $strWhere .= " AND dstchannel like '%zap%' ";
            }
        }
        if(!empty($calltype) && $calltype=="incoming") $strWhere .= " AND channel like '%zap%' ";


        $query = "DELETE FROM cdr ";

        if(!empty($strWhere)) $query .= "WHERE $strWhere ";

        $result = $this->_DB->genQuery($query);
        if($result[0] > 0)
            return true;
        else return false;
    }
}
?>
