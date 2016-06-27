<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  $Id: index.php,v 1.1 2008/05/16 15:55:57 afigueroa Exp $ */

include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    load_language_module($module_name);

    //include elastix framework
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoBuildModule.class.php";
    global $arrConf;
    global $arrConfig;

    //include lang local module
    global $arrLangModule;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    require_once('libs/paloSantoDB.class.php');
    $pDB_acl = new paloDB($arrConf['elastix_dsn']['acl']);
    if(!empty($pDB_acl->errMsg)) {
        echo "ERROR DE DB: $pDB_acl->errMsg <br>";
    }

    $accion = getAction();
    switch($accion)
    {
        case "mostrar_menu":
            $content = mostrar_menu();
            break;
        case "save_module":
            $content = save_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl, $arrConf);
            break;
        case "check_errors":
            $content = check_errors();
            break;
        default:
            $content = new_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl);
            break;
    }

    return $content;
}

function new_module($smarty, $module_name, $local_templates_dir, $arrLangModule, &$pDB_acl)
{
    require_once('libs/paloSantoACL.class.php');
    global  $arrConfig;
    
    $pACL = new paloACL($pDB_acl);
    $groups = $pACL->getGroups();
    $ip = $_SERVER["SERVER_ADDR"];

    foreach($groups as $value)
        $arrGroups[$value[0]] = $value[1];

    $arrFormElements = array(
                    "group_permissions" => array(   "LABEL"                  => $arrLangModule["Group Permission"],
                                                    "REQUIRED"              => "yes",
                                                    "INPUT_TYPE"            => "SELECT",
                                                    "INPUT_EXTRA_PARAM"     => $arrGroups,
                                                    "VALIDATION_TYPE"       => "text",
                                                    "VALIDATION_EXTRA_PARAM"=> "",
                                                    "EDITABLE"              => "no",
                                                    "SIZE"                  => "3",
                                                    "MULTIPLE"              => true,
                                                ),
                            );

    $oForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SAVE", $arrLangModule["Save"]);
    $smarty->assign("REQUIRED_FIELD", $arrLangModule["Required field"]);

    $smarty->assign("general_information", $arrLangModule["General Information"]);
    $smarty->assign("location", $arrLangModule["Location"]);
    $smarty->assign("module_description", $arrLangModule["Module Description"]);
    $smarty->assign("option_type",$arrConfig['arr_type']);
    $smarty->assign("email", $arrLangModule["Your e-mail"]); 

    $smarty->assign("module_name_label", $arrLangModule["Module Name"]);
    $smarty->assign("id_module_label", $arrLangModule["Module Id"]);

    $smarty->assign("arrGroups", $arrGroups);
    $smarty->assign("your_name_label", $arrLangModule["Your Name"]);

    $smarty->assign("module_type", $arrLangModule["Module Type"]);
    $smarty->assign("type_grid", $arrLangModule["Grid"]);
    $smarty->assign("type_form", $arrLangModule["Form"]);
    $smarty->assign("type_framed", $arrLangModule["Framed"]);
    $smarty->assign("Field_Name",$arrLangModule["Field Name"]);
    $smarty->assign("Type_Field",$arrLangModule["Type Field"]);
    $smarty->assign("Url",$arrLangModule["Url"]);

    $smarty->assign("level_2", $arrLangModule["Level 2"]);
    $smarty->assign("level_3", $arrLangModule["Level 3"]);

    $smarty->assign("parent_1_exists", $arrLangModule["Level 1 Parent Exists"]);
    $smarty->assign("parent_2_exists", $arrLangModule["Level 2 Parent Exists"]);
    $smarty->assign("peYes", $arrLangModule["Yes"]);
    $smarty->assign("peNo", $arrLangModule["No"]);
    $smarty->assign("module_level", $arrLangModule["Module Level"]);
    $smarty->assign("level_1_parent_name", $arrLangModule["Level 1 Parent Name"]);
    $smarty->assign("level_1_parent_id", $arrLangModule["Level 1 Parent Id"]);
    $smarty->assign("icon", "modules/$module_name/images/developer.png");

    $html = $oForm->fetchForm("$local_templates_dir/new_module.tpl", $arrLangModule["Build Module"], $_POST);

   //$contenidoModulo = "<form method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$html."</form>";

    return $html;
}

// Return a string that can be used as a PHP identifier for a class name
function phpidentifier($s)
{
    $s = preg_replace('/\W/', '_', $s);
    $s = preg_replace('/_+/', '_', $s);
    $s = preg_replace("/_$/", '', $s);
    return $s;
}

function save_module($smarty, $module_name, $local_templates_dir, $arrLangModule, &$pDB_acl, $arrConf)
{
    $errMsg = '';
    $error = FALSE;
    
    $jsonObject = new PaloSantoJSON();
    $pNewMod = new paloSantoBuildModule(
        $arrConf['elastix_dsn']['settings'],
        $arrConf['elastix_dsn']['menu'],
        $arrConf['elastix_dsn']['acl']);
    if(!empty($pNewMod->errMsg)) {
        $jsonObject->set_error("ERROR DE DB: {$pNewMod->errMsg} <br>");
        return $jsonObject->createJSON();
    }

    // Nivel al cual crear. Valores son 0 para nivel-2 y 1 para nivel-3
    $iNivel = getParameter('module_level_options');

    // Este arreglo representará la rama de módulos intermedios y finales
    $moduleBranch = array();
    if (getParameter('parent_1_existing_option') == 0) {
    	$moduleBranch[] = array('existing' => getParameter('parent_module'));
    } else {
    	$parentName = getParameter('parent_1_name');
        $level = array('create' => phpidentifier(strtolower($parentName)), 'name' => $parentName);
        if ($level['name'] == '') $errMsg .= _tr('Level 1 Parent Name').', ';
        if (trim($level['create']) == '')
            $errMsg .= _tr('Level 1 Parent Id').' ('._tr('Level 1 Parent Id is empty').'), ';
        $moduleBranch[] = $level;
    }
    if ($iNivel == 1) {
        if (getParameter('parent_2_existing_option') == 0) {
        	$moduleBranch[] = array('existing' => getParameter('parent_module_2'));
        } else {
            $parentName = getParameter('parent_2_name');
            $level = array('create' => phpidentifier(strtolower($parentName)), 'name' => $parentName);
            if ($level['name'] == '') $errMsg .= _tr('Level 2 Parent Name').', ';
            if (trim($level['create']) == '')
                $errMsg .= _tr('Level 2 Parent Id').' ('._tr('Level 2 Parent Id is empty').'), ';
            $moduleBranch[] = $level;
        }
    }
    $moduleName = getParameter('module_name');
    $level = array('create' => phpidentifier(strtolower($moduleName)), 'name' => $moduleName);
    if ($level['name'] == '') $errMsg .= _tr('Module Name').', ';
    if (trim($level['create']) == '')
        $errMsg .= _tr('Module Id').' ('._tr('Module Id is empty').'), ';
    elseif (file_exists($arrConf['basePath'].'/modules/'.$level['create']))
        $errMsg .= $level['create'].' ('._tr('Folder already exists').'),';
    $moduleBranch[] = $level;

    // Listado de IDs de grupos a autorizar para nuevo módulo
    $groupList = explode("\n", trim(getParameter('group_permissions')));

    // Validación según tipo de módulo
    $sAutor = $sEmail = $sUrl = $fieldList = NULL;
    $sModuleType = getParameter('module_type');
    if ($sModuleType == 'framed') {
    	$sUrl = getParameter('valor_url');
        if ($sUrl == '') $errMsg .= _tr('URL is empty').', ';
    } else { // form|grid
    	$arr_form = explode("\n", trim(getParameter('arr_form')));
        if (count($arr_form) <= 0) $errMsg .= _tr('Module Description is empty').', ';
        $sAutor = getParameter('your_name');
        if ($sAutor == '') $errMsg .= _tr('Your Name').', ';
        $sEmail = getParameter('email_module');
        if ($sEmail == '') $errMsg .= _tr('Your e-mail').', ';

        if ($sModuleType == 'form') {
            $fieldList = array();
            foreach ($arr_form as $s) $fieldList[] = explode('/', $s);
        } else $fieldList = $arr_form;
    }

    if ($errMsg != '') {
    	$errMsg = _tr('The following fields contain errors').': '.$errMsg;
        $errTitle = _tr('Validation Error');
        $error = TRUE;
    } else {
    	$errTitle = _tr('ERROR');
        $r = ($sModuleType == 'framed') 
            ? $pNewMod->createModuleURL($moduleBranch, $groupList, $sUrl)
            : $pNewMod->createModuleFormGrid($moduleBranch, $groupList,
                $sAutor, $sEmail, $sModuleType, $fieldList);
        if (!$r) {
        	$error = TRUE;
            $errMsg = $pNewMod->errMsg;
        }
    }

    $response = array();
    if($error) {
        $jsonObject->set_status('ERROR');
        $msgTitle = $errTitle;
        $message = $errMsg;
    } else {
        $new_id_module = $moduleBranch[count($moduleBranch) - 1]['create'];
        $response["moduleId"] = $new_id_module;
        $msgTitle = _tr('Message');
        $message = _tr('The module was crated successfully').'. '.
            _tr('If you are not redirected to your new module in a few seconds, you can click').
            " <a href=?menu=$new_id_module>"._tr('here')."</a>.";
    }
    $htmlMessage  = "<table width='99%' height='0px' border='0' cellspacing='0' cellpadding='0' align='center' class='message_board'>";
    $htmlMessage .= "<tr>";
    $htmlMessage .= "<td height='0px' valign='middle' id='mb_title' name='mb_title' class='mb_title'>".$msgTitle."</td>";
    $htmlMessage .= "</tr>";
    $htmlMessage .= "<tr>";
    $htmlMessage .= "<td height='0px' valign='middle' id='mb_message' name='mb_message' class='mb_message'>".$message."</td>";
    $htmlMessage .= "</tr>";
    $htmlMessage .= "</table>";
    $response["message"] = $htmlMessage;
    $jsonObject->set_message($response);

    return $jsonObject->createJSON();
}

function check_errors()
{
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_message("sss");
    return $jsonObject->createJSON();
}

function mostrar_menu()
{
    global $arrLangModule;
    global $arrConf;

    $jsonObject = new PaloSantoJSON();
    $respuesta = array();

    $level = getParameter("level");
    $parent_1_existing = getParameter("parent_1_existing");
    $parent_2_existing = getParameter("parent_2_existing");
    $id_parent = getParameter("id_parent");
    //Nivel 2
    if($level==0)
    {
        //Padre nivel 1 SI existe
        if($parent_1_existing==0)
        {
            $pDB_menu = new paloDB($arrConf['elastix_dsn']['menu']);
            if(!empty($pDB_menu->errMsg)){
                  $jsonObject->set_error("ERROR DE DB: {$pDB_menu->errMsg}");
                  return $jsonObject->createJSON();
            }

            $pMenu = new paloMenu($pDB_menu);
            $arrMenuOptions = $pMenu->getRootMenus();

            $parent_Menu = "<td align='left'><b>{$arrLangModule["Level 1 Parent"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left'>";
                $parent_Menu .= "<select name='parent_module' id='parent_module'>";
                foreach($arrMenuOptions as $key => $valor)
                    $parent_Menu .= "<option value='$key'>"._tr($valor)."</option>";
                $parent_Menu .= "</select>";
            $parent_Menu .= "</td>";
        }
        //Padre nivel 1 NO existe
        else{
            $parent_Menu  = "<td align='left'><b>{$arrLangModule["Level 1 Parent Name"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left' width='21%'><input type='text' name='parent_1_name' id='parent_1_name' value='' onkeyup='generateId(this,\"parent_1_id\")'></td>";
            $parent_Menu .= "<td align='left' width='11%'><b>{$arrLangModule["Level 1 Parent Id"]}: </b></td>";
            $parent_Menu .= "<td align='left'><i id='parent_1_id'></i></td>";
        }
        $respuesta["parent_menu_1"] = $parent_Menu;
        $respuesta["level2_exist"] = "";
        $respuesta["parent_menu_2"] = "";
        $respuesta["label_level2"] = "";
    }
    //Nivel 3
    else
    {
        //Padre nivel 1 SI existe
        if($parent_1_existing==0)
        {
            //Padre nivel 2 SI existe
            if($parent_2_existing==0)
            {
                require_once('libs/paloSantoNavigation.class.php');
                $pMenu = new paloMenu($arrConf['elastix_dsn']['menu']);
                $arrMenu = $pMenu->cargar_menu();
                $smarty = NULL;
                $pNav = new paloSantoNavigation($arrMenu, $smarty);
                $arrMenuOptions = $pNav->getArrSubMenu($id_parent);
                if(is_array($arrMenuOptions)){
                    $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent"]}: <span  class='required'>*</span></b></td>";
                    $parent_Menu2 .= "<td>";
                    $parent_Menu2 .= "<select name='parent_module_2' id='parent_module_2'>";
                    foreach($arrMenuOptions as $key => $valor)
                        $parent_Menu2 .= "<option value='$key'>"._tr($valor['Name'])."</option>";
                    $parent_Menu2 .= "</select>";
                    $parent_Menu2 .= "</td>";
                    $parent_Menu2 .= "<td></td><td></td><td></td>";
                    $respuesta["parent_menu_2"] = $parent_Menu2;
                }
            }
            //Padre nivel 2 NO existe
            else if($parent_2_existing==1)
            {
                $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent Name"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td align='left' width='21%'><input type='text' name='parent_2_name' id='parent_2_name' value='' onkeyup='generateId(this,\"parent_2_id\")'></td>";
                $parent_Menu2 .= "<td align='left' width='11%'><b>{$arrLangModule["Level 2 Parent Id"]}: </b></td>";
                $parent_Menu2 .= "<td align='left'><i id='parent_2_id'></i></td>";
                $respuesta["parent_menu_2"] = $parent_Menu2;
            }
            //Aun no se a creado el select
            else{
                $pDB_menu = new paloDB($arrConf['elastix_dsn']['menu']);
                if(!empty($pDB_menu->errMsg)){
                    $jsonObject->set_error("ERROR DE DB: {$pDB_menu->errMsg}");
                    return $jsonObject->createJSON();
                }

                $pMenu = new paloMenu($pDB_menu);
                $arrMenuOptions = $pMenu->getRootMenus();

                $parent_Menu = "<td align='left'><b>{$arrLangModule["Level 1 Parent"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu .= "<td align='left'>";
                    $parent_Menu .= "<select name='parent_module' id='parent_module' onchange='mostrar_menu()'>";
                    foreach($arrMenuOptions as $key => $valor)
                        $parent_Menu .= "<option value='$key'>"._tr($valor)."</option>";
                    $parent_Menu .= "</select>";
                $parent_Menu .= "</td>";
                $parent_Menu .= "<td></td><td></td><td></td>";
                $respuesta["parent_menu_1"] = $parent_Menu;

                //$respuesta->addAssign("parent_menu_1","innerHTML", "");

                $parent_exist = "<b>{$arrLangModule["Level 2 Parent Exists"]}: <span  class='required'>*</span></b>";
                $respuesta["label_level2"] = $parent_exist;

                $parent_option  = "<select id='parent_2_existing_option' name='parent_2_existing_option' onchange='mostrar_menu()'>";
                $parent_option .= "<option value='{$arrLangModule["Yes"]}'>{$arrLangModule["Yes"]}</option>";
                $parent_option .= "<option value='{$arrLangModule["No"]}' selected='selected'>{$arrLangModule["No"]}</option>";
                $parent_option .= "</select>";
                $respuesta["level2_exist"] = $parent_option;

                $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent Name"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td align='left' width='22%'><input type='text' name='parent_2_name' id='parent_2_name' value='' onkeyup='generateId(this,\"parent_2_id\")'></td>";
                $parent_Menu2 .= "<td align='left' width='11%'><b>{$arrLangModule["Level 2 Parent Id"]}: </b></td>";
                $parent_Menu2 .= "<td align='left'><i id='parent_2_id'></i></td>";
                $respuesta["parent_menu_2"] = $parent_Menu2;
            }
        }
        //Padre nivel 1 NO existe
        else{
            $parent_Menu  = "<td align='left'><b>{$arrLangModule["Level 1 Parent Name"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left' width='22%'><input type='text' name='parent_1_name' id='parent_1_name' value='' onkeyup='generateId(this,\"parent_1_id\")'></td>";
            $parent_Menu .= "<td align='left' width='11%'><b>{$arrLangModule["Level 1 Parent Id"]}: </b></td>";
            $parent_Menu .= "<td align='left'><i id='parent_1_id'></i></td>";
            $respuesta["parent_menu_1"] = $parent_Menu;

            $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent Name"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu2 .= "<td align='left' width='22%'><input type='text' name='parent_2_name' id='parent_2_name' value='' onkeyup='generateId(this,\"parent_2_id\")'></td>";
            $parent_Menu2 .= "<td align='left' width='11%'><b>{$arrLangModule["Level 2 Parent Id"]}: </b></td>";
            $parent_Menu2 .= "<td align='left'><i id='parent_2_id'></i></td>";
            $respuesta["parent_menu_2"] = $parent_Menu2;
            $respuesta["level2_exist"] = "";
            $respuesta["label_level2"] = "";
        }
    }
    $jsonObject->set_message($respuesta);
    return $jsonObject->createJSON();
}

function getAction()
{
    if(getParameter("action") == "mostrar_menu")
        return "mostrar_menu";
    elseif(getParameter("action") == "save_module")
        return "save_module";
    elseif(getParameter("action") == "check_errors")
        return "check_errors";
    else
        return "new_module";
}
?>
