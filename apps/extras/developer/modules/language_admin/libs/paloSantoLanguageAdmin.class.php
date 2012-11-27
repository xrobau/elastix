<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                                |
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

    function saveTraslate($module_name, $lang_name, $lang_english, $lang_traslate)
    {
        if( strcmp($module_name,'FRAMEWORK') == 0 ){
            $folder_fram = "/var/www/html/lang";

            include_once "$folder_fram/$lang_name";
            global $arrLang;

            if( array_key_exists($lang_english, $arrLang) ){
                $tmpError['head'] = 'ERROR';
                $tmpError['body'] = "Just it have traslate: ENGLISH: $lang_english - TRASLATE: $arrLang[$lang_english]";
                $this->errMsg = $tmpError;
                return false;
            }

            $arrLang[$lang_english] = $lang_traslate;
            $list = '';
            foreach($arrLang as $key => $cont)
                $list = $list.'"'.str_replace('"','\\"',$key).'"'." => ".'"'.str_replace('"','\\"',$cont).'"'.",\n";

            $file = fopen("$folder_fram/$lang_name","w");
    
            if($file == false){
                $tmpError['head'] = $arrLang['ERROR'];
                $tmpError['body'] = $arrLangModule["Can't be open file"].": $lang_nam";
                $this->errMsg = $tmpError;
                return false;
            }
    
            fwrite($file, $this->load_Template($list,0));
            fclose($file);
    
            return true;
            
        }
        else{
            $folder_lang = "/var/www/html/modules/$module_name/lang/";
            
            if( !is_dir($folder_lang) && !mkdir($folder_lang, 0755, true) ){
                $tmpError['head'] = 'ERROR';
                $tmpError['body'] = "Folder no exist:"." $module_name/lang/";
                $this->errMsg = $tmpError;
                return false;
            }
    
            if( strlen($lang_english) == 0 || strlen($lang_traslate) == 0 ){
                $bandera = false;
                $str_error = '';
                if( strlen($lang_english) == 0 )
                {
                    $str_error = $str_error."Input Language English "; 
                    $bandera = true;
                }
    
                if( strlen($lang_traslate) == 0 ){
                    if( $bandera ) $str_error = $str_error."AND "; 
                    $str_error = $str_error."Input Traslate"; 
                }
                $tmpError['head'] = 'ERROR';
                $tmpError['body'] = "In ".$str_error;
                $this->errMsg = $tmpError;
                return false;
            }
    
            $list = '';
            if( !file_exists("$folder_lang/$lang_name") ) {
                $list = '"'."$lang_english".'"'." => ".'"'."$lang_traslate".'"'.",\n";
            }
            else{
                include_once "$folder_lang/$lang_name";
                global $arrLangModule;
    
                if( array_key_exists($lang_english, $arrLangModule) ){
                    $tmpError['head'] = 'ERROR';
                    $tmpError['body'] = "Just it have traslate: ENGLISH: $lang_english - TRASLATE: $arrLangModule[$lang_english]";
                    $this->errMsg = $tmpError;
                    return false;
                }
    
                $arrLangModule[$lang_english] = $lang_traslate;
                
                foreach($arrLangModule as $key => $cont)
                    $list = $list.'"'.str_replace('"','\\"',$key).'"'." => ".'"'.str_replace('"','\\"',$cont).'"'.",\n";
            }
    
            $file = fopen("$folder_lang/$lang_name","w");
    
            if($file == false){
                $tmpError['head'] = $arrLang['ERROR'];
                $tmpError['body'] = $arrLangModule["Can't be open file"].": $lang_nam";
                $this->errMsg = $tmpError;
                return false;
            }
    
            fwrite($file, $this->load_Template($list,1));
            fclose($file);
    
            return true;
        }
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
    
    function saveAll($arrayLangTrasl, $module, $language)
    {
        $folder = '';
        $file = "";
        $list = '';

        if( strcmp($module,'FRAMEWORK') == 0 ){
            $file = "/var/www/html/lang/$language"; 

            include_once $file;
            global $arrLang;

            foreach($arrayLangTrasl as $key => $value)
            {
                if( strlen($arrayLangTrasl[$key] ) == 0 ){
                    $tmpError['head'] = 'ERROR';
                    $tmpError['body'] = "Existent values empty";
                    $this->errMsg = $tmpError;
                    return false;
                }
                
                $arrLang[$key] = $value;
            }

            foreach($arrLang as $key => $value )
                $list = $list.'"'.str_replace('"','\\"',$key).'"'." => ".'"'.str_replace('"','\\"',$value).'"'.",\n";

            $file_FRAM = fopen($file,"w");
            fwrite($file_FRAM, $this->load_Template($list, 0));
            fclose($file_FRAM);
        }
        else{
            $file = "/var/www/html/modules/$module/lang/$language";

            include_once $file;
            global $arrLangModule;

            foreach($arrayLangTrasl as $key => $value)
            {
                if( strlen($arrayLangTrasl[$key] ) == 0 ){
                    $tmpError['head'] = 'ERROR';
                    $tmpError['body'] = "Existent values empty";
                    $this->errMsg = $tmpError;
                    return false;
                }
                
                $arrLangModule[$key] = $value;
            }

            foreach($arrLangModule as $key => $value )
                $list = $list.'"'.str_replace('"','\\"',$key).'"'." => ".'"'.str_replace('"','\\"',$value).'"'.",\n";

            $file_MOD = fopen($file,"w");
            fwrite($file_MOD, $this->load_Template($list, 1));
            fclose($file_MOD);
        }

        return true;
    }

    private function load_Template($content, $modo)
    {
        //$modo == 0 => FRAMEWORK
        //$modo == 1 => MODULES
        $str_modo = '';
        $str_modo2 = '';
        if($modo == 0){
            $str_modo = '$arrLang';
            $str_modo2 = '$arrLang=array(';
        }
        else{
            $str_modo = '$arrLangModule';
            $str_modo2 = '$arrLangModule=array(';
        }

        $version = "1.0";
        $name_Translate_by = "Jonathan Vega";
        $email = "jvega@palosanto.com";
        $date = "2008/02/18 09:49:00";
        $user = "jvega";
        
        $template =
            "<?php\n".
            "/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:\n".
            "   Codificación: UTF-8\n".
            "  +----------------------------------------------------------------------+\n".
            "  | Elastix version $version                                             |\n".
            "  | http://www.elastix.org                                               |\n".
            "  +----------------------------------------------------------------------+\n".
            "  | Copyright (c) 2006 Palosanto Solutions S. A.                         |\n".
            "  +----------------------------------------------------------------------+\n".
            "  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |\n".
            "  | Telfs. 2283-268, 2294-440, 2284-356                                  |\n".
            "  | Guayaquil - Ecuador                                                  |\n".
            "  | http://www.palosanto.com                                             |\n".
            "  +----------------------------------------------------------------------+\n".
            "  | The contents of this file are subject to the General Public License  |\n".
            "  | (GPL) Version 2 (the \"License\"); you may not use this file except in |\n".
            "  | compliance with the License. You may obtain a copy of the License at |\n".
            "  | http://www.opensource.org/licenses/gpl-license.php                   |\n".
            "  |                                                                      |\n".
            "  | Software distributed under the License is distributed on \"AS IS\"     |\n".
            "  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |\n".
            "  | the License for the specific language governing rights and           |\n".
            "  | limitations under the License.                                       |\n".
            "  +----------------------------------------------------------------------+\n".
            "  | The Original Code is: Elastix Open Source.                           |\n".
            "  | The Initial Developer of the Original Code is PaloSanto Solutions    |\n".
            "  |                                                                      |\n".
            "  | Translate by: $name_Translate_by                                     |\n".
            "  | Email: $email                                                        |\n".
            "  +----------------------------------------------------------------------+\n".
            '  $Id'.": en.lang,v 1.7 $date $user Exp $ */"."\n".
            "global $str_modo;"."\n".
            $str_modo2."\n".
            $content.
            ");\n".
            "?>";
    
        return $template;
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