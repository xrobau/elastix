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

require_once 'libs/paloSantoDB.class.php';

class paloSantoBuildModule {
    //var $_DB;
    private $_DB_settings;
    private $_DB_menu;
    private $_DB_acl;
    var $errMsg;

    function __construct($dsnSettings, $dsnMenu, $dsnACL)
    {
    	$this->errMsg = '';
        $this->_initDB($this->_DB_settings, $dsnSettings);
        $this->_initDB($this->_DB_menu, $dsnMenu);
        $this->_initDB($this->_DB_acl, $dsnACL);
    }

    private function _initDB(&$db, $dsn)
    {
    	$db = new paloDB($dsn);
        if (!$db->connStatus) $this->errMsg = $db->errMsg;
    } 

    /**
     * Procedimiento para crear una especificación de base de datos que incluye
     * a un nuevo módulo, y a continuación crear los archivos del módulo en sí.
     * 
     * @param   array   $moduleBranch   Lista de tuplas que describe la rama de
     *  niveles a crear o usar. Cada tupla puede ser ('existing' => idmodulo), 
     *  o ('create' => idmodulo, 'name' => nombremodulo). El último elemento 
     *  debe ser siempre de tipo create. Todos los 'existing' deben estar antes
     *  de todos los 'create'.
     * @param   array   $groupList      Lista de todos los grupos autorizados
     * @param   string  $sAuthorName    Nombre del autor, se incluye en cabeceras
     * @param   string  $sEmail         Correo electrónico del autor
     * @param   string  $sModuleType    grid|form
     * @param   array   $fieldList      Para grid, lista de etiquetas de columnas 
     *                                  a crear. Para form, lista de tuplas de 
     *                                  (etiqueta, tipo).
     * 
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function createModuleFormGrid($moduleBranch, $groupList, $sAuthorName, $sEmail,
        $sModuleType, $fieldList)
    {
        $l = $this->_insertModuleBranchTransaction($moduleBranch, $groupList, 'module');
        if (!is_array($l)) return FALSE;
        list($sModuleID, $sModuleName) = $l;
        
        if (!$this->createModuleFiles($sModuleID, $sModuleName, $sAuthorName, $sEmail,
            $sModuleType, $fieldList)) {
            // errMsg ya fue asignado por createModuleFiles
            $this->errMsg = _tr("Folders can't be created").' - '.$this->errMsg;
            $this->_DB_acl->rollBack();
            $this->_DB_menu->rollBack();
            return FALSE;
        }
        
        $this->_DB_acl->commit();
        $this->_DB_menu->commit();
        return TRUE;
    }

    /**
     * Procedimiento para crear una especificación de base de datos que incluye
     * a un nuevo módulo, de tipo enlace externo.
     * 
     * @param   array   $moduleBranch   Lista de tuplas que describe la rama de
     *  niveles a crear o usar. Cada tupla puede ser ('existing' => idmodulo), 
     *  o ('create' => idmodulo, 'name' => nombremodulo). El último elemento 
     *  debe ser siempre de tipo create. Todos los 'existing' deben estar antes
     *  de todos los 'create'.
     * @param   array   $groupList      Lista de todos los grupos autorizados
     * @param   string  $sURL           Enlace externo a usar para el módulo
     * 
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function createModuleURL($moduleBranch, $groupList, $sURL)
    {
        $l = $this->_insertModuleBranchTransaction($moduleBranch, $groupList, 'framed', $sURL);
        if (!is_array($l)) return FALSE;
        
        $this->_DB_acl->commit();
        $this->_DB_menu->commit();
        return TRUE;
    }

    private function _insertModuleBranchTransaction($moduleBranch, $groupList, 
        $sInternalModuleType, $sURL = NULL)
    {
        // Validación de la rama de módulos
        if (!is_array($moduleBranch) || count($moduleBranch) <= 0) {
            $this->errMsg = '(internal) Invalid or empty module branch';
            return FALSE;
        }
        $bCrear = FALSE;
        foreach ($moduleBranch as $moduleLevel) {
            if (isset($moduleLevel['create'])) {
                $bCrear = TRUE;
                if (!isset($moduleLevel['name'])) {
                    $this->errMsg = '(internal) Missing name on create level';
                    return FALSE;
                }
            } elseif (isset($moduleLevel['existing'])) {
                if ($bCrear) {
                    $this->errMsg = '(internal) Existing level found past create level';
                    return FALSE;
                }
            } else {
                $this->errMsg = '(internal) Invalid or unsupported level type';
                return FALSE;
            }
        }
        if (!$bCrear) {
            $this->errMsg = '(internal) Missing create level at end of branch';
            return FALSE;
        }
        
        $this->_DB_acl->beginTransaction();
        $this->_DB_menu->beginTransaction();
        
        $sModuleID = ''; $sModuleName = NULL;
        for ($i = 0; $i < count($moduleBranch); $i++) {
            $moduleLevel = $moduleBranch[$i];
            $bUltimoNivel = ($i + 1 == count($moduleBranch));
            
            if (isset($moduleLevel['existing'])) {
                $sModuleID = $moduleLevel['existing'];
            } elseif (isset($moduleLevel['create'])) {
                // El nivel insertado no debe existir previamente
                $tupla = $this->_DB_menu->getFirstRowQuery(
                    'SELECT COUNT(*) FROM menu WHERE id = ?', FALSE,
                    array($moduleLevel['create']));
                if ($tupla[0] > 0) {
                    $this->errMsg = _tr('Module Id already exists').': '.$moduleLevel['create'];
                    $this->_DB_acl->rollBack();
                    $this->_DB_menu->rollBack();
                    return FALSE;
                }
                $r = $this->_DB_menu->genQuery(
                    'INSERT INTO menu (id, IdParent, Link, Name, Type, order_no) '.
                    "VALUES (?,?,?,?,?,'')",
                    array(
                        $moduleLevel['create'],
                        $sModuleID,
                        ($bUltimoNivel ? $sURL : NULL),
                        $moduleLevel['name'],
                        (($i != 0) ? $sInternalModuleType : '')));
                if (!$r) {
                    $this->errMsg = $this->_DB_menu->errMsg;
                    $this->_DB_acl->rollBack();
                    $this->_DB_menu->rollBack();
                    return FALSE;
                }
                
                // Insertar el recurso de ACL y otorgar los permisos
                $r = $this->_DB_acl->genQuery(
                    'INSERT INTO acl_resource (name, description) VALUES (?,?)',
                    array($moduleLevel['create'], $moduleLevel['name']));
                if (!$r) {
                    $this->errMsg = $this->_DB_acl->errMsg;
                    $this->_DB_acl->rollBack();
                    $this->_DB_menu->rollBack();
                    return FALSE;
                }
                $idResource = $this->_DB_acl->getLastInsertId();
                foreach ($groupList as $idGroup) {
                    $r = $this->_DB_acl->genQuery(
                        'INSERT INTO acl_group_permission (id_action, id_group, id_resource) VALUES (1,?,?)',
                        array($idGroup, $idResource));
                    if (!$r) {
                        $this->errMsg = $this->_DB_acl->errMsg;
                        $this->_DB_acl->rollBack();
                        $this->_DB_menu->rollBack();
                        return FALSE;
                    }
                }

                // Sobreescribir el ID de padre para siguiente nivel
                $sModuleID = $moduleLevel['create'];
                $sModuleName = $moduleLevel['name'];
            }
        }

        return array($sModuleID, $sModuleName);
    }

    private function Query_Elastix_Version()
    {
        $query = "SELECT value FROM settings WHERE key='elastix_version_release'";
        $result = $this->_DB_settings->getFirstRowQuery($query);
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
    private function createModuleFiles($sModuleId, $sModuleName, $sAuthorName, $sEmail,
        $sModuleType, $fieldList)
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