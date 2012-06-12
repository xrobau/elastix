<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  $Id: index.php,v 1.1 2008/05/16 15:55:57 afigueroa Exp $ */

include_once "libs/xajax/xajax.inc.php";

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoBuildModule.class.php";
    global $arrConf;
    global $arrConfig;

    //include lang local module
    global $arrLangModule;
    $lang=get_language();
    $lang_file="modules/$module_name/lang/$lang.lang";
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    if (file_exists("$base_dir/$lang_file"))
        include_once($lang_file);
    else
        include_once("modules/$module_name/lang/en.lang");

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    require_once('libs/paloSantoDB.class.php');
    $pDB_acl = new paloDB("sqlite3:////var/www/db/acl.db");
    if(!empty($pDB_acl->errMsg)) {
        echo "ERROR DE DB: $pDB_acl->errMsg <br>";
    }

    $xajax = new xajax();
    $xajax->registerFunction("mostrar_menu");
    $xajax->registerFunction("save_module");
    $xajax->processRequests();

    $content = $xajax->printJavascript("libs/xajax/");

    $accion = "report_new_module";
    switch($accion)
    {
        default:
            $content .= new_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl);
            break;
    }

    return $content;
}

function new_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl)
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
    $smarty->assign("TITLE", $arrLangModule["Build Module"]);
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
    $smarty->assign("ip","$ip/");
    $smarty->assign("http",$arrLangModule["http"]);
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

    $html = $oForm->fetchForm("$local_templates_dir/new_module.tpl", "", $_POST);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$html."</form>";

    return $html;
}

function save_module($new_module_name, $new_id_module, $selected_gp, $module_type, $your_name, $level, $exists_p1, $exists_p2, $parent_1_name, $parent_1_id, $parent_2_name, $parent_2_id, $selected_parent_1, $selected_parent_2, $arr_form, $email_module,$val_url)
{
    $ruta = "/var/www/html/modules";
    $this_module_name = "build_module";

    global $arrLangModule;
    $respuesta = new xajaxResponse();

    $errMsg = $arrLangModule["The following fields contain errors"].": ";
    $errTitle = "";
    $error = false;

    $db_menu = new paloDB("sqlite3:////var/www/db/menu.db");
    if(!empty($db_menu->errMsg)) {
        echo "ERROR DE DB: $db_menu->errMsg <br>";
    }

    $db_acl = new paloDB("sqlite3:////var/www/db/acl.db");
    if(!empty($db_acl->errMsg)) {
        echo "ERROR DE DB: $db_acl->errMsg <br>";
    }

    $db_settings = new paloDB("sqlite3:////var/www/db/settings.db");
    if(!empty($db_settings->errMsg)) {
        echo "ERROR DE DB: $db_settings->errMsg <br>";
    }

    $pNewMod_menu = new paloSantoBuildModule($db_menu);
    $pNewMod_acl = new paloSantoBuildModule($db_acl);
    $pNewMod_settings = new paloSantoBuildModule($db_settings);

    $respuesta->addAssign("error", "innerHTML", "");

    //Manejo de errores
    if($new_module_name == "")
    {
        $errMsg .= $arrLangModule["Module Name"].", ";
        $error = true;
    }
    if((count($arr_form) == 0) && (empty($arr_form)) && ($module_type == "form" || $module_type == "grid"))
    {
        $errMsg .= $arrLangModule["Module Description is empty"].", ";
        $error = true;
    }

    if($val_url == "" && $module_type == "framed")
    {   
        $errMsg .= $arrLangModule["URL is empty"].", ";
        $error = true;   
    }

    if($new_id_module == "" || strrpos($new_id_module, " ")!=false)
    {
        $errMsg .= $arrLangModule["Module Id"].", ";
        $error = true;
    }
    if($your_name == "")
    {
        $errMsg .= $arrLangModule["Your Name"].", ";
        $error = true;
    }
	if($email_module == "")
    {
        $errMsg .= $arrLangModule["Your e-mail"].", ";
        $error = true;
    }
    if($level == 0)
    {
        if($exists_p1 == 1)
        {
            if($parent_1_name == "")
            {
                $errMsg .= $arrLangModule["Level 1 Parent Name"].", ";
                $error = true;
            }
            if($parent_1_id == "")
            {
                $errMsg .= $arrLangModule["Level 1 Parent Id"].", ";
                $error = true;
            }
        }
    }
    else if($level == 1)
    {
        if($exists_p1 == 1)
        {
            if($parent_1_name == "")
            {
                $errMsg .= $arrLangModule["Level 1 Parent Name"].", ";
                $error = true;
            }
            if($parent_1_id == "")
            {
                $errMsg .= $arrLangModule["Level 1 Parent Id"].", ";
                $error = true;
            }
            if($parent_2_name == "")
            {
                $errMsg .= $arrLangModule["Level 2 Parent Name"].", ";
                $error = true;
            }
            if($parent_2_id == "")
            {
                $errMsg .= $arrLangModule["Level 2 Parent Id"].", ";
                $error = true;
            }
        }
        else if($exists_p1 == 0)
        {
            if($exists_p2 == 1)
            {
                if($parent_2_name == "")
                {
                    $errMsg .= $arrLangModule["Level 2 Parent Name"].", ";
                    $error = true;
                }
                if($parent_2_id == "")
                {
                    $errMsg .= $arrLangModule["Level 2 Parent Id"].", ";
                    $error = true;
                }
            }
        }
    }
    if (file_exists("/var/www/html/modules/$new_id_module")) {
        $errMsg .= "$new_id_module (". $arrLangModule["Folder already exists"]."), ";
        $error = true;
    }
    //Manejo de errores


    if($error)
    {
        $errTitle = $arrLangModule["Validation Error"];
    }
    else{

        if($pNewMod_menu->Existe_Id_Module($new_id_module))
        {
            $error = true;
            $errTitle = $arrLangModule["ERROR"];
            $errMsg = $arrLangModule['Module Id already exists']. ": $new_id_module";
        }
        else{
            $parent = "";
            $errTitle = $arrLangModule["ERROR"];
            if($level == 0)
            {
                if($exists_p1 == 0)
                    $parent = $selected_parent_1;
                else if($exists_p1 == 1){
                    //Insertar Menu de nivel 1
                    if($pNewMod_menu->Existe_Id_Module($parent_1_id))
                    {
                        $error = true;
                        $errMsg = $arrLangModule['Module Id already exists'].": $parent_1_id";
                    }else{
                        $parent = $parent_1_id;
                        if(!$pNewMod_menu->Insertar_Menu($parent_1_id, '', $parent_1_name, $module_type, $val_url))
                        {
                            $error = true;
                            $errMsg = $pNewMod_menu->errMsg;
                        }else{
                            $id_resource = $pNewMod_acl->Insertar_Resource($parent_1_id, $parent_1_name);
                            if($id_resource == 0)
                            {
                                $error = true;
                                $errMsg = $pNewMod_acl->errMsg;
                            }
                            else{
                                if(!$pNewMod_acl->Insertar_Group_Permissions($selected_gp, $id_resource))
                                {
                                    $error = true;
                                    $errMsg = $pNewMod_acl->errMsg;
                                }
                            }
                        }
                    }
                }
            }
            else if($level == 1)
            {
                if($exists_p1 == 0)
                {
                    if($exists_p2 == 0)
                        $parent = $selected_parent_2;
                    else if($exists_p2 == 1)
                    {
                        //Insertar Menu de nivel 2
                        if($pNewMod_menu->Existe_Id_Module($parent_2_id))
                        {
                            $error = true;
                            $errMsg = $arrLangModule['Module Id already exists'].": $parent_2_id";
                        }else{
                            $parent = $parent_2_id;
                            if(!$pNewMod_menu->Insertar_Menu($parent_2_id, $selected_parent_1, $parent_2_name, $module_type, $val_url))
                            {
                                $error = true;
                                $errMsg = $pNewMod_menu->errMsg;
                            }else{
                                $id_resource = $pNewMod_acl->Insertar_Resource($parent_2_id, $parent_2_name);
                                if($id_resource == 0)
                                {
                                    $error = true;
                                    $errMsg = $pNewMod_acl->errMsg;
                                }
                                else{
                                    if(!$pNewMod_acl->Insertar_Group_Permissions($selected_gp, $id_resource))
                                    {
                                        $error = true;
                                        $errMsg = $pNewMod_acl->errMsg;
                                    }
                                }
                            }
                        }
                    }
                }else if($exists_p1 == 1)
                {
                    //Insertar Menu de nivel 1
                    if($pNewMod_menu->Existe_Id_Module($parent_1_id))
                    {
                        $error = true;
                        $errMsg = $arrLangModule['Module Id already exists'].": $parent_1_id";
                    }else{
                        //$parent = $parent_1_id;
                        if(!$pNewMod_menu->Insertar_Menu($parent_1_id, '', $parent_1_name, $module_type, $val_url))
                        {
                            $error = true;
                            $errMsg = $pNewMod_menu->errMsg;
                        }else{
                            $id_resource = $pNewMod_acl->Insertar_Resource($parent_1_id, $parent_1_name);
                            if($id_resource == 0)
                            {
                                $error = true;
                                $errMsg = $pNewMod_acl->errMsg;
                            }
                            else{
                                if(!$pNewMod_acl->Insertar_Group_Permissions($selected_gp, $id_resource))
                                {
                                    $error = true;
                                    $errMsg = $pNewMod_acl->errMsg;
                                }
                            }
                        }
                    }

                    if(!$error)
                    {
                        //Insertar Menu de nivel 2
                        if($pNewMod_menu->Existe_Id_Module($parent_2_id))
                        {
                            $error = true;
                            $errMsg = $arrLangModule['Module Id already exists'].": $parent_2_id";
                        }else{
                            $parent = $parent_2_id;
                            if(!$pNewMod_menu->Insertar_Menu($parent_2_id, $parent_1_id, $parent_2_name, $module_type, $val_url))
                            {
                                $error = true;
                                $errMsg = $pNewMod_menu->errMsg;
                            }else{
                                $id_resource = $pNewMod_acl->Insertar_Resource($parent_2_id, $parent_2_name);
                                if($id_resource == 0)
                                {
                                    $error = true;
                                    $errMsg = $pNewMod_acl->errMsg;
                                }
                                else{
                                    if(!$pNewMod_acl->Insertar_Group_Permissions($selected_gp, $id_resource))
                                    {
                                        $error = true;
                                        $errMsg = $pNewMod_acl->errMsg;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //Insertar Menu Final que es el modulo en si
            if(!$error)
            {
                if(!$pNewMod_menu->Insertar_Menu($new_id_module, $parent, $new_module_name, $module_type, $val_url))
                {
                    $error = true;
                    $errMsg = $pNewMod_menu->errMsg;
                }else{
                    $id_resource = $pNewMod_acl->Insertar_Resource($new_id_module, $new_module_name);
                    if($id_resource == 0)
                    {
                        $error = true;
                        $errMsg = $pNewMod_acl->errMsg;
                    }
                    else{
                        if(!$pNewMod_acl->Insertar_Group_Permissions($selected_gp, $id_resource))
                        {
                            $error = true;
                            $errMsg = $pNewMod_acl->errMsg;
                        }else unset($_SESSION['elastix_user_permission']);
                    }
                }
            }
        }

        //Crear estructura de carpetas y archivos
        if(!$error && ($module_type == "form" || $module_type == "grid"))
        {
            $errTitle = $arrLangModule["ERROR"];
            //$type = "report";
            //$type = "grid";
            $type = $module_type;
            $arrForm = array();//agregado
            $arrForm = $arr_form;//agregado

            //Primero la carpeta principal del modulo
            $folder = "$new_id_module";
            $comando="mkdir $ruta/$folder";
            exec($comando,$output,$retval);
            if ($retval!=0){
                $error = true;
                $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
            }
            else{
                $elastix_Version = $pNewMod_settings->Query_Elastix_Version();

                //Crear index.php
                if(!$pNewMod_menu->Create_Index_File($new_module_name, $new_id_module, $your_name, $ruta, $elastix_Version, $arrLangModule, $type, $this_module_name, $arrForm, $email_module))
                {
                    $error = true;
                    $errMsg = $pNewMod_menu->errMsg;
                }

                //Carpetas comunes
                $folder = "$new_id_module/themes";
                $comando="mkdir $ruta/$folder";
                exec($comando,$output,$retval);
                if ($retval!=0){
                    $error = true;
                    $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                }else{
                    $folder = "$new_id_module/themes/default";
                    $comando="mkdir $ruta/$folder";
                    exec($comando,$output,$retval);
                    if ($retval!=0){
                        $error = true;
                        $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                    }else{
                        if(!$pNewMod_menu->Create_tpl_File($new_id_module, $ruta, $arrLangModule, $type, $this_module_name, $arrForm))
                        {
                            $error = true;
                            $errMsg = $pNewMod_menu->errMsg;
                        }
                    }
                }

                $folder = "$new_id_module/configs";
                $comando="mkdir $ruta/$folder";
                exec($comando,$output,$retval);
                if ($retval!=0){
                    $error = true;
                    $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                }else{
                    if(!$pNewMod_menu->Create_File_Config($new_id_module, $your_name, $ruta, $elastix_Version, $arrLangModule, $this_module_name, $email_module))
                    {
                        $error = true;
                        $errMsg = $pNewMod_menu->errMsg;
                    }
                }

                $folder = "$new_id_module/lang";
                $comando="mkdir $ruta/$folder";
                exec($comando,$output,$retval);
                if ($retval!=0){
                    $error = true;
                    $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                }else{
                    if(!$pNewMod_menu->Create_File_Lang($new_module_name, $new_id_module, $your_name, $ruta, $elastix_Version, $arrLangModule, $this_module_name, $arrForm, $email_module))
                    {
                        $error = true;
                        $errMsg = $pNewMod_menu->errMsg;
                    }
                }

                $folder = "$new_id_module/libs";
                $comando="mkdir $ruta/$folder";
                exec($comando,$output,$retval);
                if ($retval!=0){
                    $error = true;
                    $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                }else{
                    if(!$pNewMod_menu->Create_Module_Class_File($new_module_name, $new_id_module, $your_name, $ruta, $elastix_Version, $arrLangModule, $this_module_name,$email_module))
                    {
                        $error = true;
                        $errMsg = $pNewMod_menu->errMsg;
                    }
                }

                $folder = "$new_id_module/help";
                $comando="mkdir $ruta/$folder";
                exec($comando,$output,$retval);
                if ($retval!=0){
                    $error = true;
                    $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                }else{
                    if(!$pNewMod_menu->Create_File_Help($new_id_module, $your_name, $ruta, $elastix_Version, $arrLangModule, $this_module_name))
                    {
                        $error = true;
                        $errMsg = $pNewMod_menu->errMsg;
                    }
                }

                $folder = "$new_id_module/images";
                $comando="mkdir $ruta/$folder";
                exec($comando,$output,$retval);
                if ($retval!=0){
                    $error = true;
                    $errMsg = $arrLangModule["Folders can't be created"]." $folder, ";
                }
            }
        }
    }

    if($error)
    {
        $htmlError  = "<table width='99%' height='0px' border='0' cellspacing='0' cellpadding='0' align='center' class='message_board'>";
        $htmlError .= "<tr>";
        $htmlError .= "<td height='0px' valign='middle' id='mb_title' name='mb_title' class='mb_title'>".$errTitle."</td>";
        $htmlError .= "</tr>";
        $htmlError .= "<tr>";
        $htmlError .= "<td height='0px' valign='middle' id='mb_message' name='mb_message' class='mb_message'>".$errMsg."</td>";
        $htmlError .= "</tr>";
        $htmlError .= "</table>";
        $respuesta->addAssign("error", "innerHTML", $htmlError);
    }else{
        $respuesta->addAlert($arrLangModule["The module was crated successfully"]);
        $respuesta->addScript("document.location.href='?menu=$this_module_name'");
    }

    return $respuesta;
}

function mostrar_menu($level, $parent_1_existing, $parent_2_existing, $id_parent="")
{
    require_once('libs/paloSantoMenu.class.php');

    global $arrLangModule;

    $respuesta = new xajaxResponse();

    //Nivel 2
    if($level==0)
    {
        //Padre nivel 1 SI existe
        if($parent_1_existing==0)
        {
            $pDB_menu = new paloDB("sqlite3:////var/www/db/menu.db");
            if(!empty($pDB_menu->errMsg))
                echo "ERROR DE DB: $pDB_menu->errMsg <br>";

            $pMenu = new paloMenu($pDB_menu);
            $arrMenuOptions = $pMenu->getRootMenus();

            $parent_Menu = "<td align='left'><b>{$arrLangModule["Level 1 Parent"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left'>";
                $parent_Menu .= "<select name='parent_module' id='parent_module'>";
                foreach($arrMenuOptions as $key => $valor)
                    $parent_Menu .= "<option value='$key'>$valor</option>";
                $parent_Menu .= "</select>";
            $parent_Menu .= "</td>";
        }
        //Padre nivel 1 NO existe
        else{
            $parent_Menu  = "<td align='left'><b>{$arrLangModule["Level 1 Parent Name"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left'><input type='text' name='parent_1_name' id='parent_1_name' value='' ></td>";
            $parent_Menu .= "<td></td>";
            $parent_Menu .= "<td align='left'><b>{$arrLangModule["Level 1 Parent Id"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left'><input type='text' name='parent_1_id' id='parent_1_id' value='' ></td>";
        }
        $respuesta->addAssign("level2_exist","innerHTML", "");
        $respuesta->addAssign("parent_menu_2","innerHTML", "");
        $respuesta->addAssign("label_level2","innerHTML", "");
        $respuesta->addAssign("parent_menu_1","innerHTML", $parent_Menu);
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
                global $arrMenu;
                $pNav = new paloSantoNavigation(null, $arrMenu, $smarty);
                $arrMenuOptions = $pNav->getArrSubMenu($id_parent);

                $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td>";
                $parent_Menu2 .= "<select name='parent_module_2' id='parent_module_2'>";
                foreach($arrMenuOptions as $key => $valor)
                    $parent_Menu2 .= "<option value='$key'>{$valor['Name']}</option>";
                $parent_Menu2 .= "</select>";
                $parent_Menu2 .= "</td>";
                $parent_Menu2 .= "<td></td><td></td><td></td>";

                $respuesta->addAssign("parent_menu_2","innerHTML", $parent_Menu2);

            }
            //Padre nivel 2 NO existe
            else if($parent_2_existing==1)
            {
                $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent Name"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td align='left'><input type='text' name='parent_2_name' id='parent_2_name' value='' ></td>";
                $parent_Menu2 .= "<td></td>";
                $parent_Menu2 .= "<td align='left'><b>{$arrLangModule["Level 2 Parent Id"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td align='left'><input type='text' name='parent_2_id' id='parent_2_id' value='' ></td>";
                $respuesta->addAssign("parent_menu_2","innerHTML", $parent_Menu2);

            }
            //Aun no se a creado el select
            else{
                $pDB_menu = new paloDB("sqlite3:////var/www/db/menu.db");
                if(!empty($pDB_menu->errMsg))
                    echo "ERROR DE DB: $pDB_menu->errMsg <br>";

                $pMenu = new paloMenu($pDB_menu);
                $arrMenuOptions = $pMenu->getRootMenus();

                $parent_Menu = "<td align='left'><b>{$arrLangModule["Level 1 Parent"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu .= "<td align='left'>";
                    $parent_Menu .= "<select name='parent_module' id='parent_module' onchange='mostrar_menu()'>";
                    foreach($arrMenuOptions as $key => $valor)
                        $parent_Menu .= "<option value='$key'>$valor</option>";
                    $parent_Menu .= "</select>";
                $parent_Menu .= "</td>";
                $parent_Menu .= "<td></td><td></td><td></td>";
                $respuesta->addAssign("parent_menu_1","innerHTML", $parent_Menu);

                //$respuesta->addAssign("parent_menu_1","innerHTML", "");

                $parent_exist = "<b>{$arrLangModule["Level 2 Parent Exists"]}: <span  class='required'>*</span></b>";
                $respuesta->addAssign("label_level2","innerHTML", $parent_exist);

                $parent_option  = "<select id='parent_2_existing_option' name='parent_2_existing_option' onchange='mostrar_menu()'>";
                $parent_option .= "<option value='{$arrLangModule["Yes"]}'>{$arrLangModule["Yes"]}</option>";
                $parent_option .= "<option value='{$arrLangModule["No"]}' selected='selected'>{$arrLangModule["No"]}</option>";
                $parent_option .= "</select>";
                $respuesta->addAssign("level2_exist","innerHTML", $parent_option);

                $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent Name"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td align='left'><input type='text' name='parent_2_name' id='parent_2_name' value='' ></td>";
                $parent_Menu2 .= "<td></td>";
                $parent_Menu2 .= "<td align='left'><b>{$arrLangModule["Level 2 Parent Id"]}: <span  class='required'>*</span></b></td>";
                $parent_Menu2 .= "<td align='left'><input type='text' name='parent_2_id' id='parent_2_id' value='' ></td>";
                $respuesta->addAssign("parent_menu_2","innerHTML", $parent_Menu2);
            }
        }
        //Padre nivel 1 NO existe
        else{
            $parent_Menu  = "<td align='left'><b>{$arrLangModule["Level 1 Parent Name"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left'><input type='text' name='parent_1_name' id='parent_1_name' value='' ></td>";
            $parent_Menu .= "<td></td>";
            $parent_Menu .= "<td align='left'><b>{$arrLangModule["Level 1 Parent Id"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu .= "<td align='left'><input type='text' name='parent_1_id' id='parent_1_id' value='' ></td>";
            $respuesta->addAssign("parent_menu_1","innerHTML", $parent_Menu);

            $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent Name"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu2 .= "<td align='left'><input type='text' name='parent_2_name' id='parent_2_name' value='' ></td>";
            $parent_Menu2 .= "<td></td>";
            $parent_Menu2 .= "<td align='left'><b>{$arrLangModule["Level 2 Parent Id"]}: <span  class='required'>*</span></b></td>";
            $parent_Menu2 .= "<td align='left'><input type='text' name='parent_2_id' id='parent_2_id' value='' ></td>";
            $respuesta->addAssign("parent_menu_2","innerHTML", $parent_Menu2);
            $respuesta->addAssign("level2_exist","innerHTML", "");
            $respuesta->addAssign("label_level2","innerHTML", "");
        }
    }
    return $respuesta;
}
?>
