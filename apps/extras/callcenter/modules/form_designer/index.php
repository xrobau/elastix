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

require_once "modules/agent_console/libs/elastix2.lib.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;

    // Se fusiona la configuración del módulo con la configuración global
    $arrConf = array_merge($arrConf, $arrConfModule);

    load_language_module($module_name);

    require_once "modules/$module_name/libs/paloSantoDataForm.class.php";
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];


    // Definición del formulario de nueva formulario
    $smarty->assign("MODULE_NAME", $module_name);
    $smarty->assign("REQUIRED_FIELD", _tr('Required field'));
    $smarty->assign("CANCEL", _tr('Cancel'));
    $smarty->assign("APPLY_CHANGES", _tr('Apply changes'));
    $smarty->assign("SAVE", _tr('Save'));
    $smarty->assign("EDIT", _tr('Edit'));
    $smarty->assign("DESCATIVATE", _tr('Desactivate'));
    $smarty->assign("DELETE", _tr('Delete'));
    $smarty->assign("CONFIRM_CONTINUE", _tr('Are you sure you wish to continue?'));
    $smarty->assign("new_field", _tr('New Field'));
    $smarty->assign("add_field", _tr('Add Field'));
    $smarty->assign("update_field", _tr('Update Field')); 
    $smarty->assign("CONFIRM_DELETE", _tr('Are you sure you wish to delete form?'));
   
// print_r($_POST);

    //Definicion de campos
    $formCampos = array(
        'form_nombre'    =>    array(
            "LABEL"                => _tr('Form Name'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("size" => "60"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
        'form_description'    =>    array(
            "LABEL"                => _tr('Form Description'),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXTAREA",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "COLS"                   => "33",
            "ROWS"                   => "2",
        ),
        'field_nombre'    =>    array(
            "LABEL"                => _tr('Field Name'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXTAREA",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "COLS"                   => "50",
            "ROWS"                   => "2",
        ),
        "order" => array(
            "LABEL"                  => _tr('Order'),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("size" => "3"),
            "VALIDATION_TYPE"        => "numeric",
            "VALIDATION_EXTRA_PARAM" => ""
        ), 
    );
    $smarty->assign("type", _tr('Type'));    
    $smarty->assign("select_type","type"); 

    $arr_type = array(
        "VALUE" => array (
                    "TEXT",
                    "LIST",
                    "DATE",
                    "TEXTAREA",
                    "LABEL",
                    ),
        "NAME"  => array (
                    _tr('Type Text'),
                    _tr('Type List'),
                    _tr('Type Date'),
                    _tr('Type Text Area'),
                    _tr('Type Label'),
                    ),
        "SELECTED" => "TEXT",     
        );


    $smarty->assign("option_type", $arr_type); 
    $smarty->assign("item_list", _tr('List Item'));
    $smarty->assign("agregar", _tr('Add Item')); 
    $smarty->assign("quitar", _tr('Remove Item')); 
    $oForm = new paloForm($smarty, $formCampos);     
// print_r($_SESSION['ayuda']);
    $xajax = new xajax();
    $xajax->registerFunction("agregar_campos_formulario");
    $xajax->registerFunction("cancelar_formulario_ingreso");
    $xajax->registerFunction("guardar_formulario");
    $xajax->registerFunction("eliminar_campos_formulario");
    $xajax->registerFunction("editar_campo_formulario");
    $xajax->registerFunction("update_campo_formulario");
    $xajax->registerFunction("cancel_campo_formulario");
    $xajax->registerFunction("desactivar_formulario");

    $xajax->processRequests();
    $smarty->assign("xajax_javascript",$xajax->printJavascript("libs/xajax/"));


    $pDB = new paloDB($arrConf['cadena_dsn']);
    if (!is_object($pDB->conn) || $pDB->errMsg!="") {
        $smarty->assign("mb_message", _tr('Error when connecting to database')." ".$pDB->errMsg);
    }
    if (isset($_POST['submit_create_form'])) {
        $contenidoModulo = new_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm); 
    } else if (isset($_POST['edit'])) {
        $contenidoModulo = edit_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    } else if (isset($_POST['delete'])) {
        $contenidoModulo = delete_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    } else if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action']=="view") {
        $contenidoModulo = view_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm); 
    } else if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action']=="activar") {
        $contenidoModulo = activar_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm); 
    } else {
        $contenidoModulo = listadoForm($pDB, $smarty, $module_name, $local_templates_dir); 
    }

    return $contenidoModulo;
}


function new_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {
    $oDataForm = new paloSantoDataForm($pDB);
    $id_nuevo_formulario = $oDataForm->proximo_id_formulario();
    $smarty->assign('FRAMEWORK_TIENE_TITULO_MODULO', existeSoporteTituloFramework());
    $smarty->assign("id_formulario_actual",$id_nuevo_formulario); // obtengo el id para crear el nuevo formulario
    $smarty->assign('icon', 'images/kfaxview.png');
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr('New Form'),$_POST);  
    return $contenidoModulo;
}

function view_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {
    $smarty->assign('FRAMEWORK_TIENE_TITULO_MODULO', existeSoporteTituloFramework());

    $oForm->setViewMode(); // Esto es para activar el modo "preview"

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        return false;
    }
    $oDataForm = new paloSantoDataForm($pDB);
    $arrDataForm = $oDataForm->getFormularios($_GET['id']);
    $arrFieldForm = $oDataForm->obtener_campos_formulario($_GET['id']);
    
    // Conversion de formato
    $arrTmp['form_nombre']       = $arrDataForm[0]['nombre'];
    $arrTmp['form_description']    = $arrDataForm[0]['descripcion'];  

    $smarty->assign("id_formulario_actual", $_GET['id']);
    $smarty->assign("style_field","style='display:none;'");
    $html_campos = html_campos_formulario($arrFieldForm,false);
    $smarty->assign("solo_contenido_en_vista",$html_campos);
    $smarty->assign('icon', 'images/kfaxview.png');
    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form.tpl", _tr('View Form'), $arrTmp); // hay que pasar el arreglo
    return $contenidoModulo;
}

function edit_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {
    $smarty->assign('FRAMEWORK_TIENE_TITULO_MODULO', existeSoporteTituloFramework());

    // Tengo que recuperar los datos del formulario
    $oDataForm = new paloSantoDataForm($pDB);
    $arrDataForm = $oDataForm->getFormularios($_GET['id']); 
    $arrFieldForm = $oDataForm->obtener_campos_formulario($_GET['id']);

    $arrTmp['form_nombre']       = $arrDataForm[0]['nombre'];
    $arrTmp['form_description']    = $arrDataForm[0]['descripcion'];   

    $oForm = new paloForm($smarty, $formCampos);
    $oForm->setEditMode();
    $smarty->assign("id_formulario_actual", $_GET['id']);
    $html_campos = html_campos_formulario($arrFieldForm);
    $smarty->assign("solo_contenido_en_vista",$html_campos);
    $smarty->assign('icon', 'images/kfaxview.png');
    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form.tpl", _tr('Edit Form')." \"".$arrTmp['form_nombre']."\"", $arrTmp);
    return $contenidoModulo;
}

function listadoForm($pDB, $smarty, $module_name, $local_templates_dir) {
    global $arrLang;

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
                $arrTmp[2] = _tr('Inactive');
                $arrTmp[3] = "&nbsp;<a href='?menu=$module_name&action=activar&id=".$DataForm['id']."'>"._tr('Activate')."</a>";
            }
            else{
                $arrTmp[2] = _tr('Active');
                $arrTmp[3] = "&nbsp;<a href='?menu=$module_name&action=view&id=".$DataForm['id']."'>"._tr('View')."</a>";
            }
            $arrData[] = $arrTmp;
        }
    }

    $url = construirUrl(array('menu' => $module_name), array('nav', 'start'));
    $arrGrid = array("title"    => _tr('Form List'),
        "url"      => $url,
        "icon"     => "images/list.png",
        "width"    => "99%",
        "start"    => ($end==0) ? 0 : 1,
        "end"      => $end,
        "total"    => $end,
        "columns"  => array(0 => array("name"      => _tr('Form Name'),
                                       "property1" => ""),
                            1 => array("name"      => _tr('Form Description'), 
                                       "property1" => ""),
                            2 => array("name"      => _tr('Status'), 
                                       "property1" => ""),
                            3 => array("name"      => _tr('Options'), 
                                       "property1" => "")));

    $estados = array("all"=> _tr('All'), "A"=> _tr('Active'), "I"=> _tr('Inactive'));
    $combo_estados = "<select name='cbo_estado' id='cbo_estado' onChange='submit();'>".combo($estados,$_POST['cbo_estado'])."</select>";
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->showFilter(
              "<table width='100%' border='0'><tr>".
              "<td><input type='submit' name='submit_create_form' value='"._tr('Create New Form')."' class='button'></td>".
              "<td class='letra12' align='right'><b>"._tr('Status').":</b>&nbsp;$combo_estados</td>".
              "</tr></table>");
//print_r($arrData);
    $sContenido = $oGrid->fetchGrid($arrGrid, $arrData, $arrLang);
    if (strpos($sContenido, '<form') === FALSE)
        $sContenido = "<form  method=\"POST\" style=\"margin-bottom:0;\" action=\"$url\">$sContenido</form>";
    return $sContenido;
}

function activar_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm)
{
     if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        return false;
    }
    $oDataForm = new paloSantoDataForm($pDB);
    if($oDataForm->activar_formulario($_GET['id']))
        header("Location: ?menu=$module_name");
    else
    {
        $smarty->assign("mb_title", _tr('Activate Error'));
        $smarty->assign("mb_message", _tr('Error when Activating the form'));
    }
}

function delete_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {
    if (!isset($_POST['id_formulario']) || !is_numeric($_POST['id_formulario'])) {
        return false;
    }

    $oDataForm = new paloSantoDataForm($pDB);
    if($oDataForm->delete_form($_POST['id_formulario'])) {
        if ($oDataForm->errMsg!="") {
            $smarty->assign("mb_title", _tr('Validation Error'));
            $smarty->assign("mb_message",$oDataForm->errMsg);
        } else {
            header("Location: ?menu=form_designer");
        }
    } else {
        $msg_error = ($oDataForm->errMsg!="")?"<br>".$oDataForm->errMsg:"";
        $smarty->assign("mb_title", _tr('Delete Error'));
        $smarty->assign("mb_message", _tr('Error when deleting the Form').$msg_error);
    }
    $sContenido = view_form($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    return $sContenido;
}
?>
