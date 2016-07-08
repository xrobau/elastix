<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-18                                               |
  | http://www.elastix.com                                               |
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
  $Id: paloSantoMonitoring.class.php,v 1.1 2010-03-22 05:03:48 Eduardo Cueva ecueva@palosanto.com Exp $ */

define ('DEFAULT_ASTERISK_RECORDING_BASEDIR', '/var/spool/asterisk/monitor');

class paloSantoMonitoring
{
    var $_DB;
    var $errMsg;

    function paloSantoMonitoring(&$pDB)
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

    private function _construirWhereMonitoring($param)
    {
        $condSQL = array();
        $paramSQL = array();

        if (!is_array($param)) {
            $this->errMsg = '(internal) invalid parameter array';
            return NULL;
        }
        if (!function_exists('_construirWhereMonitoring_notempty')) {
            function _construirWhereMonitoring_notempty($x) { return ($x != ''); }
        }
        $param = array_filter($param, '_construirWhereMonitoring_notempty');

        // La columna recordingfile debe estar no-vacía
        $condSQL[] = 'recordingfile <> ""';

        // Fecha y hora de inicio y final del rango
        $sRegFecha = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
        if (isset($param['date_start'])) {
            if (preg_match($sRegFecha, $param['date_start'])) {
                $condSQL[] = 'calldate >= ?';
                $paramSQL[] = $param['date_start'];
            } else {
                $this->errMsg = '(internal) Invalid start date, must be yyyy-mm-dd hh:mm:ss';
                return NULL;
            }
        }
        if (isset($param['date_end'])) {
            if (preg_match($sRegFecha, $param['date_end'])) {
                $condSQL[] = 'calldate <= ?';
                $paramSQL[] = $param['date_end'];
            } else {
                $this->errMsg = '(internal) Invalid end date, must be yyyy-mm-dd hh:mm:ss';
                return NULL;
            }
        }

        // Extensión de fuente o destino, copiada de paloSantoCDR.class.php
        if (isset($param['extension'])) {
            $condSQL[] = <<<SQL_COND_EXTENSION
(
       src = ?
    OR dst = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(channel,'-',1),'/',-1) = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1) = ?
)
SQL_COND_EXTENSION;
            array_push($paramSQL, $param['extension'], $param['extension'],
                $param['extension'], $param['extension']);
        }

        foreach (array('src', 'dst') as $sCampo) if (isset($param[$sCampo])) {
            $listaPat = array_filter(
                array_map('trim',
                    is_array($param[$sCampo])
                        ? $param[$sCampo]
                        : explode(',', trim($param[$sCampo]))),
                '_construirWhereMonitoring_notempty');

            if (!function_exists('_construirWhereMonitoring_troncal2like2')) {
                function _construirWhereMonitoring_troncal2like2($s) { return '%'.$s.'%'; }
            }
            $paramSQL = array_merge($paramSQL, array_map('_construirWhereMonitoring_troncal2like2', $listaPat));
            $fieldSQL = array_fill(0, count($listaPat), "$sCampo LIKE ?");

            /* Caso especial: si se especifica field_pattern=src|dst, también
             * debe buscarse si el canal fuente o destino contiene el patrón
             * dentro de su especificación de canal. */
            if ($sCampo == 'src' || $sCampo == 'dst') {
                if ($sCampo == 'src') $chanexpr = "SUBSTRING_INDEX(SUBSTRING_INDEX(channel,'-',1),'/',-1)";
                if ($sCampo == 'dst') $chanexpr = "SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1)";
                $paramSQL = array_merge($paramSQL, array_map('_construirWhereMonitoring_troncal2like2', $listaPat));
                $fieldSQL = array_merge($fieldSQL, array_fill(0, count($listaPat), "$chanexpr LIKE ?"));
            }

            $condSQL[] = '('.implode(' OR ', $fieldSQL).')';
        }

        // Tipo de grabación según nombre de archivo
        $prefixByType = array(
            'outgoing'  =>  array('O', 'o'),
            'group'     =>  array('g', 'r'),
            'queue'     =>  array('q'),
        );
        if (isset($param['recordingfile']) && isset($prefixByType[$param['recordingfile']])) {
            $fieldSQL = array();
            foreach ($prefixByType[$param['recordingfile']] as $p) {
                $fieldSQL[] = 'recordingfile LIKE ?';
                $paramSQL[] = $p.'%';
                $fieldSQL[] = 'recordingfile LIKE ?';
                $paramSQL[] = DEFAULT_ASTERISK_RECORDING_BASEDIR.'%/'.$p.'%';
            }

            $condSQL[] = '('.implode(' OR ', $fieldSQL).')';
        }

        // Construir fragmento completo de sentencia SQL
        $where = array(implode(' AND ', $condSQL), $paramSQL);
        if ($where[0] != '') $where[0] = 'WHERE '.$where[0];
        return $where;
    }

    function getNumMonitoring($param)
    {
        list($sWhere, $paramSQL) = $this->_construirWhereMonitoring($param);
        if (is_null($sWhere)) return NULL;

        $query = 'SELECT COUNT(*) FROM cdr '.$sWhere;
        $r = $this->_DB->getFirstRowQuery($query, FALSE, $paramSQL);
        if (!is_array($r)){
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $r[0];
    }

    function getMonitoring($param, $limit = NULL, $offset = 0)
    {
        list($sWhere, $paramSQL) = $this->_construirWhereMonitoring($param);
        if (is_null($sWhere)) return NULL;

        // TODO: paloSantoCDR ordena por calldate DESC. ¿Debería ser concordante?
        $query = 'SELECT * FROM cdr '.$sWhere.' ORDER BY uniqueid DESC';
        if (!empty($limit)) {
            $query .= " LIMIT ? OFFSET ?";
            array_push($paramSQL, $limit, $offset);
        }
        $r = $this->_DB->fetchTable($query, TRUE, $paramSQL);
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $r;
    }

    function deleteRecordFile($id)
    {
        $result = $this->_DB->genQuery(
            'UPDATE cdr SET recordingfile = ? WHERE uniqueid = ?',
            array('deleted', $id));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return true;
    }

    function getRecordName($id)
    {
        $query = "SELECT recordingfile FROM cdr WHERE uniqueid=?";
        $result = $this->_DB->getFirstRowQuery($query,true,array($id));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result['recordingfile'];
    }

    function getAudioByUniqueId($id, $namefile = NULL)
    {
        $query = 'SELECT recordingfile FROM cdr WHERE uniqueid = ?';
        $parame = array($id);
        if (!is_null($namefile)) {
            $query .= ' AND recordingfile LIKE ?';
            $parame[] = '%'.$namefile.'%';
        }
        $result=$this->_DB->getFirstRowQuery($query, true, $parame);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }

        return $result;
    }

    function recordBelongsToUser($uniqueid, $extension)
    {
        $sql = <<<RECORD_BELONGS_TO_EXTENSION
SELECT COUNT(*) FROM cdr
WHERE uniqueid = ? AND (
       src = ?
    OR dst = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(channel,'-',1),'/',-1) = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1) = ?
)
RECORD_BELONGS_TO_EXTENSION;
        $result = $this->_DB->getFirstRowQuery($sql, FALSE,
            array($uniqueid, $extension, $extension, $extension, $extension));
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return ($result[0] > 0);
    }
}