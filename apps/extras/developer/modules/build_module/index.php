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

include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
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

function save_module($smarty, $module_name, $local_templates_dir, $arrLangModule, &$pDB_acl, $arrConf)
{
    $ruta = "$arrConf[basePath]/modules";

    $jsonObject = new PaloSantoJSON();

    $new_module_name = getParameter("module_name");
    $new_module_name = (isset($new_module_name)) ? $new_module_name : "";
    $new_id_module = preg_replace("/\W/","_",strtolower($new_module_name));
    $new_id_module = preg_replace("/_+/","_",$new_id_module);
    $new_id_module = preg_replace("/_$/","",$new_id_module);

    $parent_1_name = getParameter("parent_1_name");
    $parent_1_name = (isset($parent_1_name)) ? $parent_1_name : "";
    $parent_1_id = preg_replace("/\W/","_",strtolower($parent_1_name));
    $parent_1_id = preg_replace("/_+/","_",$parent_1_id);
    $parent_1_id = preg_replace("/_$/","",$parent_1_id);

    $parent_2_name = getParameter("parent_2_name");
    $parent_2_name = (isset($parent_2_name)) ? $parent_2_name : "";
    $parent_2_id = preg_replace("/\W/","_",strtolower($parent_2_name));
    $parent_2_id = preg_replace("/_+/","_",$parent_2_id);
    $parent_2_id = preg_replace("/_$/","_",$parent_2_id);

    $selected_gp = getParameter("group_permissions");
    $selected_gp = explode("\n",$selected_gp);
    array_pop($selected_gp);
    $module_type = getParameter("module_type");
    $your_name = getParameter("your_name");
    $level = getParameter("module_level_options");
    $exists_p1 = getParameter("parent_1_existing_option");
    $exists_p2 = getParameter("parent_2_existing_option");
    $selected_parent_1 = getParameter("parent_module");
    $selected_parent_1 = (isset($selected_parent_1)) ? $selected_parent_1 : "";
    $selected_parent_2 = getParameter("parent_module_2");
    $selected_parent_2 = (isset($selected_parent_2)) ? $selected_parent_2 : "";
    $arr_form = getParameter("arr_form");
    $arr_form = explode("\n",$arr_form);
    array_pop($arr_form);
    $email_module = getParameter("email_module");
    $val_url = getParameter("valor_url");
    $val_url = (isset($val_url)) ? $val_url : "";

    $errMsg = $arrLangModule["The following fields contain errors"].": ";
    $errTitle = "";
    $error = false;

    $db_menu = new paloDB($arrConf['elastix_dsn']['menu']);
    if(!empty($db_menu->errMsg)) {
	$jsonObject->set_error("ERROR DE DB: {$db_menu->errMsg} <br>");
	return $jsonObject->createJSON();
    }

    $db_acl = new paloDB($arrConf['elastix_dsn']['acl']);
    if(!empty($db_acl->errMsg)) {
	$jsonObject->set_error("ERROR DE DB: {$db_acl->errMsg} <br>");
	return $jsonObject->createJSON();
    }

    $db_settings = new paloDB($arrConf['elastix_dsn']['settings']);
    if(!empty($db_settings->errMsg)) {
	$jsonObject->set_error("ERROR DE DB: {$db_settings->errMsg} <br>");
	return $jsonObject->createJSON();
    }

    $pNewMod_menu = new paloSantoBuildModule($db_menu);
    $pNewMod_acl = new paloSantoBuildModule($db_acl);
    $pNewMod_settings = new paloSantoBuildModule($db_settings);

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
        $errMsg .= $arrLangModule["Module Id"]." (".$arrLangModule["Module Id is empty"]."), ";
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
                $errMsg .= $arrLangModule["Level 1 Parent Id"]." (".$arrLangModule["Level 1 Parent Id is empty"]."), ";
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
                $errMsg .= $arrLangModule["Level 1 Parent Id"]." (".$arrLangModule["Level 1 Parent Id is empty"]."), ";
                $error = true;
            }
            if($parent_2_name == "")
            {
                $errMsg .= $arrLangModule["Level 2 Parent Name"].", ";
                $error = true;
            }
            if($parent_2_id == "")
            {
                $errMsg .= $arrLangModule["Level 2 Parent Id"]." (".$arrLangModule["Level 2 Parent Id is empty"]."), ";
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
                    $errMsg .= $arrLangModule["Level 2 Parent Id"]." (".$arrLangModule["Level 2 Parent Id is empty"]."), ";
                    $error = true;
                }
            }
        }
    }
    if (file_exists("$ruta/$new_id_module") && !empty($new_id_module)) {
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

            $fieldList = NULL;
            if ($module_type == 'form') {
            	$fieldList = array();
                foreach ($arr_form as $s) $fieldList[] = explode('/', $s);
            } else $fieldList = $arr_form;
                
            $bExito = $pNewMod_settings->createModuleFiles($new_id_module,
                $new_module_name, $your_name, $email_module, $module_type,
                $fieldList);
            if (!$bExito) {
            	$error = TRUE;
                $errMsg = _tr("Folders can't be created").' - '.$pNewMod_settings->errMsg;
            }
        }
    }
    $response = array();
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
	$response["message"] = $htmlError;
	$jsonObject->set_status("ERROR");
	$jsonObject->set_message($response);
    }else{
	$message = $arrLangModule["The module was crated successfully"].". ".$arrLangModule["If you are not redirected to your new module in a few seconds, you can click"]." <a href=?menu=$new_id_module>{$arrLangModule["here"]}</a>.";
	$htmlMessage  = "<table width='99%' height='0px' border='0' cellspacing='0' cellpadding='0' align='center' class='message_board'>";
        $htmlMessage .= "<tr>";
        $htmlMessage .= "<td height='0px' valign='middle' id='mb_title' name='mb_title' class='mb_title'>".$arrLangModule["Message"]."</td>";
        $htmlMessage .= "</tr>";
        $htmlMessage .= "<tr>";
        $htmlMessage .= "<td height='0px' valign='middle' id='mb_message' name='mb_message' class='mb_message'>".$message."</td>";
        $htmlMessage .= "</tr>";
        $htmlMessage .= "</table>";
	$response["message"] = $htmlMessage;
	$response["moduleId"] = $new_id_module;
	$jsonObject->set_message($response);
    }

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
                    $parent_Menu .= "<option value='$key'>$valor</option>";
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
                $pNav = new paloSantoNavigation(null, $arrMenu, $smarty);
                $arrMenuOptions = $pNav->getArrSubMenu($id_parent);
		if(is_array($arrMenuOptions)){
		    $parent_Menu2  = "<td align='left'><b>{$arrLangModule["Level 2 Parent"]}: <span  class='required'>*</span></b></td>";
		    $parent_Menu2 .= "<td>";
		    $parent_Menu2 .= "<select name='parent_module_2' id='parent_module_2'>";
		    foreach($arrMenuOptions as $key => $valor)
			$parent_Menu2 .= "<option value='$key'>{$valor['Name']}</option>";
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
                        $parent_Menu .= "<option value='$key'>$valor</option>";
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
