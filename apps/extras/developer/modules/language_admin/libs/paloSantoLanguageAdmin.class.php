<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                                |
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
  $Id: default.conf.php,v 1.1 2008-07-08 11:07:07 jvega Exp $ */

class paloSantoLanguageAdmin {
    var $_DB;
    var $errMsg;

    function paloSantoLanguageAdmin()
    {
    }

    /*HERE YOUR FUNCTIONS*/

    /**
     * Procedimiento para contar el número de traducciones dentro de un archivo
     * de lenguaje.
     * 
     * @param   string  $module     FRAMEWORK para el framework, o el nombre del
     *                              módulo que se examina
     * @param   string  $language   Archivo de lenguaje (en.lang)
     * 
     * @return  int     Número de traducciones, o 0 en caso de algún error
     */
    function ObtainNumLanguages($module, $language)
    {
        $count = 0; $file = '';
        $arrData = array();

        if (!preg_match('/^\w+$/', $module)) return $count;
        if (!preg_match('/^\w+\.lang$/', $language)) return $count;

        if( strcmp($module,'FRAMEWORK') == 0 ){
            $file = "/var/www/html/lang/$language";

            if( file_exists($file) ){
                include_once $file;
                global $arrLang;
            
                if( is_array($arrLang) && count($arrLang) > 0 )  
                    $count = count($arrLang);
            }
        }
        else{
            $file = "/var/www/html/modules/$module/lang/$language";
    
            if( file_exists($file) ){
                include_once $file;
                global $arrLangModule;
            
                if( is_array($arrLangModule) && count($arrLangModule) > 0 )  
                    $count = count($arrLangModule);
            }
        }

        return $count;
    }
    
    /**
     * Procedimiento para leer el arreglo de traducciones dentro de un archivo 
     * de lenguaje.
     * 
     * @param   int     $limit      Número máximo de traducciones a devolver
     * @param   int     $offset     Offset desde el cual devolver traducciones
     * @param   string  $module     FRAMEWORK para el framework, o el nombre del
     *                              módulo que se examina
     * @param   string  $language   Archivo de lenguaje (en.lang)
     * 
     * @return  int     Número de traducciones, o 0 en caso de algún error
     */
    function obtainLanguages($limit, $offset, $module, $language)
    {
        $file = '';
        $arrData = array();

        if (!preg_match('/^\w+$/', $module)) return $arrData;
        if (!preg_match('/^\w+\.lang$/', $language)) return $arrData;

        if( strcmp($module,'FRAMEWORK') == 0 ){
            $file = "/var/www/html/lang/$language";

            if( file_exists($file) ){
                include_once $file;
                global $arrLang;
    
                $array_temp = array();
                if( is_array($arrLang) && count($arrLang) > 0 )
                    $arrData = array_slice($arrLang,  $offset, $limit);
            }
        }
        else{
            $file = "/var/www/html/modules/$module/lang/$language";

            if( file_exists($file) ){
                include_once $file;
                global $arrLangModule;
    
                $array_temp = array();
                if( is_array($arrLangModule) && count($arrLangModule) > 0 )
                    $arrData = array_slice($arrLangModule,  $offset, $limit);
            }
        }
        return $arrData;
    }

    /**
     * Procedimiento para agregar una nueva cadena de texto y su correspondiente
     * traducción en el archivo de idioma y módulo indicados. No se admite el
     * reemplazo de cadenas de texto existentes en el archivo indicado. Si el 
     * archivo de idioma del módulo no existe, se lo crea.
     * 
     * @param   string  $module_name    FRAMEWORK, o módulo a modificar
     * @param   string  $lang_name      Nombre de archivo de idioma (es.lang)
     * @param   string  $lang_english   Texto en inglés que debe ser traducido
     * @param   string  $lang_traslate  Texto traducido en el idioma elegido
     * 
     * @return  VERDADERO en éxito, o FALSE en error.
     */
    function saveTraslate($module_name, $lang_name, $lang_english, $lang_traslate)
    {
        $regs = NULL;
        if (!preg_match('/^(\w+)$/', $module_name, $regs)) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Invalid module name',
            );
            return FALSE;
        }
        if (!preg_match('/^(\w+)\.lang$/', $lang_name, $regs)) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Invalid file name for language',
            );
            return FALSE;
        }
        $lang_name = $regs[1];
        
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper develbuilder --addtranslation'.
            ' --language '.escapeshellarg($lang_name).
            ' --module '.escapeshellarg($module_name).
            ' --string-en '.escapeshellarg($lang_english).
            ' --string-tr '.escapeshellarg($lang_traslate).
            ' 2>&1', $output, $retval);
        if ($retval != 0) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Failed to add translation: <br/>'.implode("<br/>\n", $output),
            );
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Procedimiento para crear los archivos de lenguaje para un nuevo lenguaje.
     * Los archivos se copian a partir de en.lang en el framework y en cada uno
     * de los módulos instalados. El lenguaje nuevo NO se agrega a la lista de
     * lenguajes conocidos, sino que se tiene que agregar a mano. Sin embargo,
     * el nuevo lenguaje podrá editarse en el módulo language_admin.
     * 
     * @param string $sNewLang Nombre del nuevo archivo de idioma
     * 
     * @return VERDADERO en éxito, o FALSO en error
     */
    function saveNewLanguage($sNewLang)
    {    	
        $regs = NULL;
        if (!preg_match('/^(\w+)\.lang$/', $sNewLang, $regs)) {
    		$this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Invalid file name for language',
            );
            return FALSE;
    	}
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper develbuilder --createlanguage '.
            escapeshellarg($regs[1]).' 2>&1', $output, $retval);
        if ($retval != 0) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Failed to create language: <br/>'.implode("<br/>\n", $output),
            );
        	return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Procedimiento para reemplazar todas las traducciones en un módulo en 
     * particular con las versiones indicadas en la lista proporcionada.
     * 
     * @param   array   $arrayLangTrasl Lista de traducciones
     * @param   string  $module         FRAMEWORK, o nombre del módulo a afectar
     * @param   string  $language       Nombre del archivo de idioma (es.lang)
     * 
     * @return VERDADERO en éxito, FALSO en error
     */
    function saveAll($arrayLangTrasl, $module, $language)
    {
        $sTempFile = $this->_writeLanguageSpec($arrayLangTrasl, $module, $language);
        if (is_null($sTempFile)) return FALSE;
        
        // Invocación del script privilegiado
        $output = $retval = NULL;
        exec('/usr/bin/elastix-helper develbuilder --savetranslation '.escapeshellarg($sTempFile).' 2>&1',
            $output, $retval);
        unlink($sTempFile);
        if ($retval != 0) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  '(internal) Failed to save translations - '.implode('<br/>', $output),
            );
            return FALSE;
        }
        return TRUE;
    }
    
    private function _writeLanguageSpec($arrayLangTrasl, $module, $language)
    {
        $regs = NULL;
        if (!preg_match('/^(\w+)$/', $module, $regs)) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Invalid module name',
            );
            return NULL;
        }
        if (!preg_match('/^(\w+)\.lang$/', $language, $regs)) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  'Invalid file name for language',
            );
            return NULL;
        }
        $language = $regs[1];
        
        // Generación de archivo XML temporal para script privilegiado
        $xml_languagespec = new SimpleXMLElement('<languagespec />');
        $xml_languagespec->addChild('language', str_replace('&', '&amp;', $language));
        $xml_languagespec->addChild('module', str_replace('&', '&amp;', $module));
        foreach ($arrayLangTrasl as $k => $v) {
            $xml_tr = $xml_languagespec->addChild('translation');
            $xml_tr->addChild('original', str_replace('&', '&amp;', $k));
            $xml_tr->addChild('translate', str_replace('&', '&amp;', $v));
        }
        $sTempFile = tempnam('/tmp', 'languagespec_');
        if (!$xml_languagespec->asXML($sTempFile)) {
            $this->errMsg = array(
                'head'  =>  'ERROR',
                'body'  =>  '(internal) Failed to write temporary language specification',
            );
            return NULL;
        }
        return $sTempFile;
    }
    
    function leer_directorio_modulos()
    {
        $folder = "/var/www/html/modules/";
    
        $bExito=FALSE;
        $arrScanModule=array();
        if (file_exists($folder))
            $arrScanModule = scandir($folder);
    
        $arrModules = array();
        $arrModules["FRAMEWORK"] = "FRAMEWORK";
        if (is_array($arrScanModule) && count($arrScanModule) > 0) {
            foreach($arrScanModule as $key => $module){
                if(is_dir($folder.$module) && $module!="." && $module!=".." )
                    $arrModules[$module] = $module;
            }
        }
    
        return $arrModules;
    }
    
    function leer_directorio_lenguajes()
    {
        $directorio = "/var/www/html/lang/";
    
        $bExito=FALSE;
        $archivos_language=array();
        if (file_exists($directorio))
            $archivos_language = scandir($directorio);
    
        $arr_respuesta = array();
    
        if(is_array($archivos_language) && count($archivos_language) > 0) {
            foreach($archivos_language as $key => $repositorio){
                if(!is_dir($repositorio) && $repositorio!="." && $repositorio!=".." )
                    $arr_respuesta[$repositorio] = $repositorio;
            }
        }
    
        return $arr_respuesta;
    }
}
?>