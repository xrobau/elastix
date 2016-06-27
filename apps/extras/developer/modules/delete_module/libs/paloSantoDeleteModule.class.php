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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/05/16 17:31:55 afigueroa Exp $ */

class paloSantoDeleteModule {
    var $_DB;
    var $errMsg;

    function paloSantoDeleteModule(&$pDB)
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

    function Eliminar_Menu($id_module)
    {
        $query = "DELETE FROM menu WHERE id='$id_module';";
        $result = $this->_DB->genQuery($query);
        if($result)
            return true;
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function Eliminar_Resource($id_module)
    {
        $query = "SELECT id FROM acl_resource WHERE name='$id_module';";
        $result_select_acl = $this->_DB->getFirstRowQuery($query,true);
        if($result_select_acl)
        {   
            $result = $this->Eliminar_Group_Permissions($result_select_acl['id']);
            if(!$result)
            {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
            $query = "DELETE FROM acl_resource WHERE id={$result_select_acl['id']};";
            $result = $this->_DB->genQuery($query);
            if(!$result)
            {
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
        }
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }

        return true;
    }

    function Eliminar_Group_Permissions($id_resource)
    {
        $error = false;
        $query = "DELETE FROM acl_group_permission WHERE id_resource=$id_resource;";
        $result = $this->_DB->genQuery($query);
        if(!$result)
        {
            $error = true;
            $this->errMsg = $this->_DB->errMsg;
        }

        if($error) return false;
        else return true;
    }
}
?>
