<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

class BaseVendorResource
{
    protected $_db;
    protected $_baseurl;

    function BaseVendorResource($db, $baseurl)
    {
        $this->_db = $db;
        $this->_baseurl = $baseurl;
    }
    
    /**
     * Procedimiento para intentar obtener un usuario de Elastix que esté 
     * asociado a una cuenta del endpoint indicado. Para endpoints con múltiples
     * cuentas, se buscan usuarios por orden de prioridad de la cuenta en el 
     * endpoint y se devuelve el primero que se encuentre.
     * 
     * Esta implementación asume el esquema de Elastix 2.
     * 
     * @param   int $id_endpoint    ID del endpoint
     * 
     * @return  NULL si no se encuentra un usuario, o arreglo(id_user, username)
     */
    protected function obtenerUsuarioElastix($id_endpoint)
    {
        // Lista de cuentas del endpoint, por orden de prioridad
    	$recordset = $this->_db->fetchTable(
            'SELECT account FROM endpoint_account WHERE id_endpoint = 2 ORDER BY priority',
            TRUE, array($id_endpoint));
        if (!is_array($recordset)) return NULL;
        $accounts = array();
        foreach ($recordset as $tupla) $accounts[] = $tupla['account'];
        
        global $arrConf;
        $pdbACL = new paloDB($arrConf['elastix_dsn']['acl']);
        $sql = 'SELECT id AS id_user, name AS name_user FROM acl_user WHERE extension = ? ORDER BY id';
        foreach ($accounts as $account) {
            $tupla = $pdbACL->getFirstRowQuery($sql, TRUE, array($account));
            if (!is_array($tupla)) return NULL;
            if (count($tupla) > 0) return $tupla;
        }
        return NULL;
    }
}
?>