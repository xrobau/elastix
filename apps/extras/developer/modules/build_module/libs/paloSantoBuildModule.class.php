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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/05/16 17:31:55 afigueroa Exp $ */

class paloSantoBuildModule {
    var $_DB;
    var $errMsg;

    function paloSantoBuildModule(&$pDB)
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

    function Existe_Id_Module($id_module)
    {
        $query = "SELECT count(*) FROM menu WHERE id=?";
        $result = $this->_DB->getFirstRowQuery($query,false,array($id_module));
        if($result[0] > 0)
            return true;
        else return false;
    }

    function Insertar_Menu($id_module, $parent, $module_name, $module_type, $url="")
    {
        $type = "";
        if($module_type == "form" || $module_type == "grid")
           $type = "module";
        else
           $type = "framed";
                   
        $query = "INSERT INTO menu values(?,?,?,?,?,'')";
        $result = $this->_DB->genQuery($query,array($id_module,$parent,$url,$module_name,$type));
        if($result)
            return true;
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function Insertar_Resource($id_module, $module_name)
    {
        $query = "Insert into  acl_resource (name, description) values(?,?)";
        $result = $this->_DB->genQuery($query,array($id_module,$module_name));
        if($result)
        {
            $result = $this->_DB->getLastInsertId();
            return $result;
        }
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function Insertar_Group_Permissions($selected_gp, $id_resource)
    {
        $error = false;
        foreach($selected_gp as $value)
        {
            $query = "Insert into acl_group_permission (id_action, id_group, id_resource) values(1,?,?)";
            $result = $this->_DB->genQuery($query,array($value,$id_resource));
            if(!$result)
            {
                $error = true;
                $this->errMsg = $this->_DB->errMsg;
            }
        }

        if($error) return false;
        else return true;
    }

    private function Query_Elastix_Version()
    {
        $query = "SELECT value FROM settings WHERE key='elastix_version_release'";
        $result = $this->_DB->getFirstRowQuery($query);
        return $result[0];
    }

    /**
     * Procedimiento para crear la estructura de archivos de un nuevo módulo.
     * 
     * @param   string  $sModuleID  ID del módulo a guardar, y directorio base
     * @param   string  $sModuleName    Nombre del módulo que aparece en GUI
     * @param   string  $sAuthorName    Nombre del autor, se incluye en cabeceras
     * @param   string  $sEmail         Correo electrónico del autor
     * @param   string  $sModuleType    grid|form
     * @param   array   $fieldList      Para grid, lista de etiquetas de columnas 
     *                                  a crear. Para form, lista de tuplas de 
     *                                  (etiqueta, tipo).
     * 
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function createModuleFiles($sModuleId, $sModuleName, $sAuthorName, $sEmail, $sModuleType, $fieldList)
    {
        if (!in_array($sModuleType, array('grid', 'form'))) {
            $this->errMsg = '(internal) Unsupported module type, must be grid or form';
        	return FALSE;
        }
        
    	// Generación de archivo XML temporal para script privilegiado
        $xml_modulespec = new SimpleXMLElement('<modulespec />');
        $xml_modulespec->addChild('name', str_replace('&', '&amp;', $sModuleName));
        $xml_modulespec->addChild('id', $sModuleId);
        $xml_modulespec->addChild('author', str_replace('&', '&amp;', $sAuthorName));
        $xml_modulespec->addChild('email', str_replace('&', '&amp;', $sEmail));
        $xml_modulespec->addChild('elastixversion', $this->Query_Elastix_Version());
        $xml_fields = $xml_modulespec->addChild($sModuleType);
        if ($sModuleType == 'grid') {
        	foreach ($fieldList as $k => $field) {
                $xml_field = $xml_fields->addChild('column', str_replace('&', '&amp;', $field));
                $xml_field->addAttribute('key', $k);
            }
        } elseif ($sModuleType == 'form') {
            foreach ($fieldList as $field) {
                $xml_field = $xml_fields->addChild('field', str_replace('&', '&amp;', $field[0]));
                $xml_field->addAttribute('type', $field[1]);
            }
        }
        $sTempFile = tempnam('/tmp', 'modulespec_');
        if (!$xml_modulespec->asXML($sTempFile)) {
            $this->errMsg = '(internal) Failed to write temporary module specification';
        	return FALSE;
        }
        
        // Invocación del script privilegiado
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper develbuilder --createmodule '.escapeshellarg($sTempFile).' 2>&1',
            $output, $retval);
        unlink($sTempFile);
        if ($retval != 0) {
            $this->errMsg = '(internal) Failed to create module files - '.implode('<br/>', $output);
        	return FALSE;
        }
        return TRUE;
    }
}
?>