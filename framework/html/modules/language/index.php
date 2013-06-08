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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
	//include module files
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoForm.class.php";
    include      "configs/languages.conf.php";
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    $error_msg='';
    $msgError='';
    $contenido='';
    $archivos=array();
    $langElastix=array();
    $arrDefaultRate=array();
    $conexionDB=FALSE;
    //leer los archivos disponibles
  /*  $languages=array(
                    "en"=>"English",
                    "es"=>"Español",
                    "fr"=>"Français",
                    );*/
    leer_directorio("lang",$error_msg,$archivos);
    if (count($archivos)>0)
    {
        foreach ($languages as $lang=>$lang_name)
        {
            if (in_array("$lang.lang",$archivos))
               $langElastix[$lang]=$lang_name;
        }
    }

    if (count($langElastix)>0){

        //si no me puedo conectar a la base de datos
//       debo presentar un mensaje en vez del boton cambiar
//       un
        $arrForm  = array("language"  => array("LABEL"                  => _tr("Select language"),
                                               "REQUIRED"               => "yes",
                                               "INPUT_TYPE"             => "SELECT",
                                               "INPUT_EXTRA_PARAM"      => $langElastix,
                                               "VALIDATION_TYPE"        => "text",
                                               "VALIDATION_EXTRA_PARAM" => ""),);
        $oForm = new paloForm($smarty, $arrForm);
        $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
        if(empty($pDB->errMsg)) {
            $conexionDB=TRUE;

        if(isset($_POST['save_language'])) {
        //guardar el nuevo valor
            $lang = $_POST['language'];
            
            $bExito=set_key_settings($pDB,'language',$lang);
        //redirigir a la pagina nuevamente
            if ($bExito)
            header("Location: index.php?menu=language");
            else
               $smarty->assign("mb_message", "Error");
        }
    //obtener el valor de la tarifa por defecto
            $defLang=get_key_settings($pDB,'language');
            if (empty($defLang)) $defLang="en";
            $arrDefaultRate['language']=$defLang;
        }
        else
             $msgError=_tr("You can't change language").'.-'._tr("ERROR").":".$pDB->errMsg;
       // $arrDefaultRate['language']="es";
        $smarty->assign("CAMBIAR", _tr("Save"));
        $smarty->assign("MSG_ERROR",$msgError);
        $smarty->assign("conectiondb",$conexionDB);
	$smarty->assign("icon","modules/$module_name/images/system_preferencies_language.png");
        $contenido = $oForm->fetchForm("$local_templates_dir/language.tpl", _tr("Language"), $arrDefaultRate);
    }
    return $contenido;
}

function leer_directorio($directorio,$error_msg,&$archivos)
{
    $bExito=FALSE;
    $archivos=array();
    if (file_exists($directorio)) {
        if ($handle = opendir($directorio)) {
            $bExito=true;
            while (false !== ($file = readdir($handle))) {
               //no tomar en cuenta . y ..
                if ($file!="." && $file!=".." )
                    $archivos[]=$file;
            }
            closedir($handle);
        }

     }else
        $error_msg ="No existe directorio";

     return $bExito;
}
?>
