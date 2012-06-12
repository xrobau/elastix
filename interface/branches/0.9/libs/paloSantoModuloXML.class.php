<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2003 Palosanto Solutions S. A.                    |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  +----------------------------------------------------------------------+
  | Este archivo fuente está sujeto a las políticas de licenciamiento    |
  | de Palosanto Solutions S. A. y no está disponible públicamente.      |
  | El acceso a este documento está restringido según lo estipulado      |
  | en los acuerdos de confidencialidad los cuales son parte de las      |
  | políticas internas de Palosanto Solutions S. A.                      |
  | Si Ud. está viendo este archivo y no tiene autorización explícita    |
  | de hacerlo, comuníquese con nosotros, podría estar infringiendo      |
  | la ley sin saberlo.                                                  |
  +----------------------------------------------------------------------+
  | Autores: Gladys Carrillo B.   <gcarrillo@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: paloSantoModuloXML.class.php,v 1.1 2007/09/05 00:25:25 gcarrillo Exp $
*/


class ModuloXML
{
    var $_arbolMenu;// Árbol de menú construido a partir de archivo XML
    
    var $_tempMenuList;
    var $_rutaArchivo;
    var $_errMsg;
    /**
     * Constructor del objeto ModuloXML
     * 
     * @param string    $sRutaArchivo   Ruta al archivo donde se encuentra el menú XML
     */
    function ModuloXML($sRutaArchivo)
    {
        $this->_rutaArchivo=$sRutaArchivo;
        $this->_privado_construirArbolMenu();
    }
    
    // Procedimiento que construye el árbol de menú a partir del archivo XML.
    function _privado_construirArbolMenu()
    {
        $this->_arbolMenu = array();
        $sDocumento = file_get_contents($this->_rutaArchivo);
        if ($sDocumento == '') {
            $this->_errMsg='documento no se puede leer, o está vacío';

        } else {
            $xmlParser = xml_parser_create();
            xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, FALSE);
            xml_set_element_handler($xmlParser, 
                array(&$this, '_privado_startElement'), 
                array(&$this, '_privado_endElement'));
            if (!xml_parse($xmlParser, $sDocumento, TRUE)) {
                $sMensaje = "Linea  ".xml_get_current_line_number($xmlParser)." - ".xml_error_string(xml_get_error_code($xmlParser));
                xml_parser_free($xmlParser);
                die ($sMensaje);
            }
            xml_parser_free($xmlParser);
        }
    }
    
    function _privado_startElement($xmlParser, $sName, $atributos)
    {
        switch ($sName)
        {
        case 'module':
            $this->_tempMenuList = NULL;
            break;
        case 'menulist':
            $this->_tempMenuList = array(
                'MENUID'  =>  $atributos['menuid'],
                'TAG'   =>  $atributos['tag'],
                'DESC'  =>  $atributos['desc'],
                'ITEMS' =>  array(),
            );
            break;
        case 'menuitem':
            if (!is_null($this->_tempMenuList)) {
                $subItem = array(
                    'MENUID'  =>  $atributos['menuid'],
                    'TAG'   =>  $atributos['tag'],
                    'DESC'  =>  $atributos['desc'],
                );
                $this->_tempMenuList['ITEMS'][] = $subItem;
            }
            break;
        }
    }    

    function _privado_endElement($xmlParser, $sName)
    {
        switch ($sName)
        {
        case 'menulist':
            if (!is_null($this->_tempMenuList)) {
                $this->_arbolMenu[$this->_tempMenuList['MENUID']] = $this->_tempMenuList;
                $this->_tempMenuList = NULL;
            }
            break;
        }
    }
    
}
?>
