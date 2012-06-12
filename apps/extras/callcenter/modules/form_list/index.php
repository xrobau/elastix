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
  $Id: data_fom $ */

require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoGrid.class.php";
require_once "libs/misc.lib.php";
require_once "libs/xajax/xajax.inc.php";
//require "configs/db.conf.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    require_once "modules/$module_name/configs/default.conf.php";

    #incluir el archivo de idioma de acuerdo al que este seleccionado
    #si el archivo de idioma no existe incluir el idioma por defecto
    $lang=get_language();

    $script_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$script_dir/$lang_file"))
        include_once($lang_file);
    else
        include_once("modules/$module_name/lang/en.lang");
    global $arrLangModule;
    require_once "modules/$module_name/libs/paloSantoDataFormList.class.php";
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConfig['theme'];

    // Definición del formulario de nueva formulario
    $smarty->assign("MODULE_NAME", $module_name);
    $script="<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"libs/js/jscalendar/calendar-win2k-2.css\" />
    <script type=\"text/javascript\" src=\"libs/js/jscalendar/calendar.js\"></script>
    <script type=\"text/javascript\" src=\"libs/js/jscalendar/lang/calendar-en.js\"></script>
    <script type=\"text/javascript\" src=\"libs/js/jscalendar/calendar-setup.js\"></script>";
    $smarty->assign("HEADER", $script);
//     $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
//     $smarty->assign("CANCEL", $arrLang["Cancel"]);
//     $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    
//print_r($arrLangModule);
    //Definicion de campos
    $formCampos = array(
        'form_nombre'    =>    array(
            "LABEL"                => $arrLangModule["Form Name"],
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("size" => "40"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
        'form_description'    =>    array(
            "LABEL"                => $arrLangModule["Form Description"],
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXTAREA",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "COLS"                   => "33",
            "ROWS"                   => "2",
        ), 
    );
    $smarty->assign("type",$arrLangModule['Type']);    
    $smarty->assign("select_type","type"); 
    $smarty->assign("option_type",
        array(
        "VALUE" => array (
                    "LABEL",
                    "TEXT",
                    "LIST",
                    "DATE",
                    "TEXTAREA"),
        "NAME"  => array (
                    $arrLangModule["Type Label"],
                    $arrLangModule["Type Text"],
                    $arrLangModule["Type List"],
                    $arrLangModule["Type Date"],
                    $arrLangModule["Type Text Area"]),
        "SELECTED" => "Text",     
        )
    ); 
    $smarty->assign("item_list",$arrLangModule['List Item']);    
    $oForm = new paloForm($smarty, $formCampos);     
// print_r($_SESSION['ayuda']);


    $pDB = new paloDB($arrConfig['cadena_dsn']);
    if (!is_object($pDB->conn) || $pDB->errMsg!="") {
        $smarty->assign("mb_message", $arrLang["Error when connecting to database"]." ".$pDB->errMsg);
    }
    if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action']=="preview") {
        $contenidoModulo = preview_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm,$arrLangModule); 
    } else {
        $contenidoModulo = listadoForm($pDB, $smarty, $module_name, $local_templates_dir,$arrLangModule); 
    }

    return $contenidoModulo;
}


function preview_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm,$arrLangModule) {

    $oForm->setViewMode(); // Esto es para activar el modo "preview"

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        return false;
    }
    $oDataForm = new paloSantoDataForm($pDB);
    $arrDataForm = $oDataForm->getFormularios($_GET['id']);
    $arrFieldForm = $oDataForm->obtener_campos_formulario($_GET['id']);
    //echo $arrFieldForm;
   
    // Conversion de formato
    $arrTmp['form_nombre']       = $arrDataForm[0]['nombre'];
    $arrTmp['form_description']    = $arrDataForm[0]['descripcion'];  
    $smarty->assign("title",$arrLangModule['Form']);
    $smarty->assign("form_name_lbl", $arrLangModule['Form Name']);
    $smarty->assign("form_description_lbl", $arrLangModule['Form Description']);
    $smarty->assign("form_name_val", $arrTmp['form_nombre']);
    $smarty->assign("form_description_val", $arrTmp['form_description']);
    $smarty->assign("id_formulario_actual", $_GET['id']);
    $smarty->assign("style_field","style='display:none;'");
    $smarty->assign("formulario",$arrFieldForm);
 
    //$html_campos = html_campos_formulario($arrFieldForm,false);
    //$smarty->assign("solo_contenido_en_vista",$html_campos);
    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/preview.tpl", $arrLangModule['Form'], $arrTmp); // hay que pasar el arreglo
    return $contenidoModulo;
}

function listadoForm($pDB, $smarty, $module_name, $local_templates_dir,$arrLangModule) {

    //global $arrLang;
    //global $arrLangModule;

    $oDataForm = new paloSantoDataForm($pDB);
    // preguntando por el estado del filtro
    if (!isset($_POST['cbo_estado']) || $_POST['cbo_estado']=="") {
        $_POST['cbo_estado'] = "A";
    }
    $arrDataForm = $oDataForm->getFormularios(NULL, $_POST['cbo_estado']);
    $end = count($arrDataForm);

    $arrData = array();
    if (is_array($arrDataForm)) {
        foreach($arrDataForm as $DataForm) {
            $arrTmp    = array();
            $arrTmp[0] = $DataForm['nombre'];
            if(!isset($DataForm['descripcion']) || $DataForm['descripcion']=="")
                $DataForm['descripcion']="&nbsp;";
            $arrTmp[1] = $DataForm['descripcion'];
            if($DataForm['estatus']=='I'){
                $arrTmp[2] = $arrLangModule['Inactive'];
                $arrTmp[3] = "&nbsp;<a href='?menu=$module_name&action=preview&id=".$DataForm['id']."'>{$arrLangModule['Preview']}</a>";
            } else {
                $arrTmp[2] = $arrLangModule['Active'];
                $arrTmp[3] = "&nbsp;<a href='?menu=$module_name&action=preview&id=".$DataForm['id']."'>{$arrLangModule['Preview']}</a>";
            }
            $arrData[] = $arrTmp;
        }
    }

    $url = construirUrl(array('menu' => $module_name), array('nav', 'start'));
    $arrGrid = array("title"    => $arrLangModule["Form List"],
        "url"      => $url,
        "icon"     => "images/list.png",
        "width"    => "99%",
        "start"    => ($end==0) ? 0 : 1,
        "end"      => $end,
        "total"    => $end,
        "columns"  => array(0 => array("name"      => $arrLangModule["Form Name"],
                                       "property1" => ""),
                            1 => array("name"      => $arrLangModule["Form Description"], 
                                       "property1" => ""),
                            2 => array("name"      => $arrLangModule["Status"], 
                                       "property1" => ""),
                            3 => array("name"      => $arrLangModule["Options"], 
                                       "property1" => "")));

    $estados = array("all"=>$arrLangModule["All"], "A"=>$arrLangModule["Active"], "I"=>$arrLangModule["Inactive"]);
    $combo_estados = "<select name='cbo_estado' id='cbo_estado' onChange='submit();'>".combo($estados,$_POST['cbo_estado'])."</select>";
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->showFilter(
              "<table width='100%' border='0'><tr>".
              "<td>".$arrLangModule["Forms"]."</td>".
              "<td class='letra12' align='right'><b>".$arrLangModule["Status"].":</b>&nbsp;$combo_estados</td>".
              "</tr></table>");
//print_r($arrData);
    $sContenido = $oGrid->fetchGrid($arrGrid, $arrData, $arrLangModule);
    if (strpos($sContenido, '<form') === FALSE)
        $sContenido = "<form  method=\"POST\" style=\"margin-bottom:0;\" action=\"$url\">$sContenido</form>";
    return $sContenido;
}
?>
