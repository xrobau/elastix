<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: paloSantoFaxVisor.class.php,v 1.1.1.1 2008/12/09 18:00:00 aflores Exp $ */

/*-
CREATE TABLE info_fax_recvq
(
    id           INTEGER  PRIMARY KEY,
    pdf_file    varchar(255)   NOT NULL DEFAULT '',
    modemdev     varchar(255)   NOT NULL DEFAULT '',
    status       varchar(255)   NOT NULL DEFAULT '',
    commID       varchar(255)   NOT NULL DEFAULT '',
    errormsg     varchar(255)   NOT NULL DEFAULT '',
    company_name varchar(255)   NOT NULL DEFAULT '',
    company_fax  varchar(255)   NOT NULL DEFAULT '',
    fax_destiny_id       INTEGER NOT NULL DEFAULT 0,
    date     timestamp  NOT NULL ,
    FOREIGN KEY (fax_destiny_id)   REFERENCES fax(id)
);
*/

class paloFaxVisor
{
    private $_db;
    var $errMsg;

    function paloFaxVisor()
    {
        global $arrConf;
        
        //instanciar clase paloDB
        $pDB = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/fax.db");
    	if (!empty($pDB->errMsg)) {
            $this->errMsg = $pDB->errMsg;
    	} else{
       		$this->_db = $pDB;
    	}
    }

    function obtener_faxes($company_name, $company_fax, $fecha_fax, $offset, $cantidad, $type)
    {
        if (empty($company_name)) $company_name = NULL;
        if (empty($company_fax)) $company_fax = NULL;
        if (empty($fecha_fax)) $fecha_fax = NULL;
        if (empty($type)) $type = NULL;
        if (!is_null($fecha_fax) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fax)) {
            $this->errMsg = '(internal) Invalid date for query, expected yyyy-mm-dd';
        	return NULL;
        }
        if (!ctype_digit("$offset") || !ctype_digit("$cantidad")) {
        	$this->errMsg = '(internal) Invalid offset/limit';
            return NULL;
        }
        if (!is_null($type)) {
        	$type = strtolower($type);
            if (!in_array($type, array('in', 'out'))) $type = NULL;
        }
        
        $sPeticionSQL = 
            'SELECT r.id, r.pdf_file, r.modemdev, r.commID, r.errormsg, '.
                'r.company_name, r.company_fax, r.fax_destiny_id, r.date, '.
                'r.type, r.faxpath, f.name destiny_name, f.extension destiny_fax, '.
                'r.status '.
            'FROM info_fax_recvq r LEFT JOIN fax f ON f.id = r.fax_destiny_id';
        $listaWhere = array();
        $paramSQL = array();
        if (!is_null($company_name)) {
        	$listaWhere[] = 'company_name LIKE ?';
            $paramSQL[] = "%$company_name%";
        }
        if (!is_null($company_fax)) {
            $listaWhere[] = 'company_fax LIKE ?';
            $paramSQL[] = "%$company_fax%";
        }
        if (!is_null($fecha_fax)) {
            $listaWhere[] = 'date BETWEEN ? AND ?';
            $paramSQL[] = "$fecha_fax 00:00:00";
            $paramSQL[] = "$fecha_fax 23:59:59";
        }
        if (!is_null($type)) {
        	$listaWhere[] = 'type = ?';
            $paramSQL[] = $type;
        }
        if (count($listaWhere) > 0)
            $sPeticionSQL .= ' WHERE '.implode(' AND ', $listaWhere);
        $sPeticionSQL .= ' ORDER BY r.id desc LIMIT ? OFFSET ?';
        $paramSQL[] = $cantidad; $paramSQL[] = $offset;
        
        $arrReturn = $this->_db->fetchTable($sPeticionSQL, TRUE, $paramSQL);
        if ($arrReturn == FALSE) {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
    }

    function obtener_fax($idFax)
    {
        $arrReturn = $this->_db->getFirstRowQuery(
            'SELECT * FROM info_fax_recvq WHERE id = ?', 
            TRUE, array($idFax));
        if ($arrReturn == FALSE){
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
    }

    function obtener_cantidad_faxes($company_name, $company_fax, $fecha_fax, $type)
    {
        if (empty($company_name)) $company_name = NULL;
        if (empty($company_fax)) $company_fax = NULL;
        if (empty($fecha_fax)) $fecha_fax = NULL;
        if (empty($type)) $type = NULL;
        if (!is_null($fecha_fax) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fax)) {
            $this->errMsg = '(internal) Invalid date for query, expected yyyy-mm-dd';
            return NULL;
        }
        if (!is_null($type)) {
            $type = strtolower($type);
            if (!in_array($type, array('in', 'out'))) $type = NULL;
        }

        $sPeticionSQL = 'SELECT COUNT(*) cantidad FROM info_fax_recvq';
        $listaWhere = array();
        $paramSQL = array();
        if (!is_null($company_name)) {
            $listaWhere[] = 'company_name LIKE ?';
            $paramSQL[] = "%$company_name%";
        }
        if (!is_null($company_fax)) {
            $listaWhere[] = 'company_fax LIKE ?';
            $paramSQL[] = "%$company_fax%";
        }
        if (!is_null($fecha_fax)) {
            $listaWhere[] = 'date BETWEEN ? AND ?';
            $paramSQL[] = "$fecha_fax 00:00:00";
            $paramSQL[] = "$fecha_fax 23:59:59";
        }
        if (!is_null($type)) {
            $listaWhere[] = 'type = ?';
            $paramSQL[] = $type;
        }
        if (count($listaWhere) > 0) $sPeticionSQL .= ' WHERE '.implode(' AND ', $listaWhere);
        
        $arrReturn = $this->_db->getFirstRowQuery($sPeticionSQL, TRUE, $paramSQL);

        if ($arrReturn == FALSE) {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn['cantidad'];
    }

    function updateInfoFaxFromDB($idFax, $company_name, $company_fax)
    {
        if (!$this->_db->genQuery(
            'UPDATE info_fax_recvq SET company_name = ?, company_fax = ? WHERE id = ?', 
            array($company_name, $company_fax, $idFax))) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function deleteInfoFax($idFax)
    {
        $this->errMsg = '';
        $bExito = TRUE;
        
        // Leer la información del fax
        $infoFax = $this->obtener_fax($idFax);
        if (count($infoFax) == 0) return ($this->errMsg == '');

        // Borrar la información y el documento asociado
        $this->_db->conn->beginTransaction();
        $bExito = $this->_db->genQuery(
            'DELETE FROM info_fax_recvq WHERE id = ?',
            array($infoFax['id']));
        if (!$bExito) $this->errMsg = $this->_db->errMsg;
        if ($bExito) {
            $file = "/var/www/faxes/{$infoFax['faxpath']}/fax.pdf";
            $bExito = file_exists($file) ? unlink($file) : TRUE;
        } 
        if ($bExito)
            $this->_db->conn->commit();
        else $this->_db->conn->rollback();
        return $bExito;
    }
}
?>