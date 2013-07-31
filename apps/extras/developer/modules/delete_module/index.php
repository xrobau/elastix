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

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/xajax/xajax.inc.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoDeleteModule.class.php";
    global $arrConf;

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

    $pDB_menu = new paloDB($arrConf['elastix_dsn']['menu']);
    if(!empty($pDB_menu->errMsg)) {
        echo "ERROR DE DB: $pDB_menu->errMsg <br>";
    }

    $xajax = new xajax();
    $xajax->registerFunction("mostrar_menu");
    $xajax->processRequests();

    $content = $xajax->printJavascript("libs/xajax/");

    $delete = isset($_POST['delete'])?$_POST['delete']:'';
    if($delete!='') $accion = 'delete_module';
    else $accion = "report_delete_module";
    switch($accion)
    {
        case 'delete_module':
            $content .= delete_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl, $pDB_menu);
            break;
        default:
            $content .= report_delete_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl);
            break;
    }

    return $content;
}

function delete_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl, $pDB_menu)
{
    $ruta = "/var/www/html/modules";

    $module_level_1 = isset($_POST['module_level_1'])?$_POST['module_level_1']:'';
    $module_level_2 = isset($_POST['module_level_2'])?$_POST['module_level_2']:'';
    $module_level_3 = isset($_POST['module_level_3'])?$_POST['module_level_3']:'';

    $delete_files = isset($_POST['delete_files'])?$_POST['delete_files']:'';
    $delete_menu  = isset($_POST['delete_menu'])?$_POST['delete_menu']:'';

    if(!$delete_files && !$delete_menu)
    {
        $smarty->assign("mb_title", $arrLangModule["ERROR"]);
        $smarty->assign("mb_message", $arrLangModule["You haven't selected any option to delete: Menu or Files"]);
        return report_delete_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl);
    }

    require_once('libs/paloSantoNavigation.class.php');
    global $arrConf;
    $pMenu = new paloMenu($arrConf['elastix_dsn']['menu']);
    $arrMenu = $pMenu->cargar_menu();
    $pNav = new paloSantoNavigation($arrMenu, $smarty);

    $arrBorrar_Level_2 = array();
    $arrBorrar_Level_3 = array();

    if($_POST['select_level']==3)
    {
        if($module_level_3 != '')
            $arrBorrar_Level_3[$module_level_3] = $module_level_3;
    }
    else if($_POST['select_level']==2)
    {
        $arrBorrar_Level_2[$module_level_2] = $module_level_2;
        $arrBorrar_Level_3 = $pNav->getArrSubMenu($module_level_2);
        if(!$arrBorrar_Level_3)
            $arrBorrar_Level_3 = array();
    }
    else if($_POST['select_level']==1)
    {
        $arrBorrar_Level_2 = $pNav->getArrSubMenu($module_level_1);
        if(!$arrBorrar_Level_2)
            $arrBorrar_Level_2 = array();
        foreach($arrBorrar_Level_2 as $key => $valor)
        {
            $arrTmp = $pNav->getArrSubMenu($key);
            if($arrTmp)
                $arrBorrar_Level_3 = array_merge($arrBorrar_Level_3, $arrTmp);
        }
    }

    $pDeleteModule_ACL = new paloSantoDeleteModule($pDB_acl);
    $pDeleteModule_Menu = new paloSantoDeleteModule($pDB_menu);
    $error = false;

    //Primero borro los de nivel 3
    foreach($arrBorrar_Level_3 as $key3 => $valor3)
    {
        if($delete_menu=='on'){
            if(!$pDeleteModule_Menu->Eliminar_Menu($key3))   $error = true;
            else if(!$pDeleteModule_ACL->Eliminar_Resource($key3))  $error = true;
        }
        if($delete_files=='on'){
            if(file_exists("$ruta/$key3"))
            {
                $output = $retval = NULL;
                exec('/usr/bin/elastix-helper develbuilder --deletemodule '.escapeshellarg($key3).' 2>&1',
                    $output, $retval);
                if ($retval!=0) $error = true;
            }
        }
    }

    if(!$error)
    {
        //Ahora borro nivel 2
        foreach($arrBorrar_Level_2 as $key2 => $valor2)
        {
            if($delete_menu=='on'){
                if(!$pDeleteModule_Menu->Eliminar_Menu($key2))   $error = true;
                else if(!$pDeleteModule_ACL->Eliminar_Resource($key2))  $error = true;
            }
            if($delete_files=='on'){
                if(file_exists("$ruta/$key2"))
                {
                    $output = $retval = NULL;
                    exec('/usr/bin/elastix-helper develbuilder --deletemodule '.escapeshellarg($key2).' 2>&1',
                        $output, $retval);
                    if ($retval!=0) $error = true;
                }
            }
        }

        if(!$error && $_POST['select_level']==1 && $delete_menu=='on')
        {
            //Finalmente borro nivel 1
            if(!$pDeleteModule_Menu->Eliminar_Menu($module_level_1))   $error = true;
            else if(!$pDeleteModule_ACL->Eliminar_Resource($module_level_1))  $error = true;
        }

        $smarty->assign("mb_message", $arrLangModule["The module was deleted"]);
        unset($_SESSION['elastix_user_permission']);
    }else{
        $smarty->assign("mb_title", $arrLangModule["ERROR"]);
        $smarty->assign("mb_message", $arrLangModule["The module couldn't be deleted"]);
    }

    return report_delete_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl);
}

function report_delete_module($smarty, $module_name, $local_templates_dir, $arrLangModule, $pDB_acl)
{
    require_once('libs/paloSantoMenu.class.php');
    global $arrConf;

    $pDB_menu = new paloDB($arrConf['elastix_dsn']['menu']);
    if(!empty($pDB_menu->errMsg))
        echo "ERROR DE DB: $pDB_menu->errMsg <br>";

    $pMenu = new paloMenu($pDB_menu);
    $arrMenuOptions = $pMenu->getRootMenus();

    $level_1 = "<td align='left'><b>{$arrLangModule["Level 1"]}:</b></td>";
    $level_1 .= "<td align='left'>";
        $level_1 .= "<select onchange='mostrar_menu()' name='module_level_1' id='module_level_1'>";
        foreach($arrMenuOptions as $key => $valor)
            $level_1 .= "<option value='$key'>$valor</option>";
        $level_1 .= "</select>";
    $level_1 .= "</td>";


    $oForm = new paloForm($smarty, array());
    $smarty->assign("DELETE", $arrLangModule["Delete"]);

    $smarty->assign("REQUIRED_FIELD", $arrLangModule["Required field"]);

    $smarty->assign("Level", $arrLangModule["Level"]);
    $smarty->assign("level_1", $level_1);

    $smarty->assign("Delete_Menu", $arrLangModule['Delete Menu']);
    $smarty->assign("Delete_Files", $arrLangModule['Delete Files']);

    $smarty->assign("CONFIRM_CONTINUE", $arrLangModule["Are you sure you wish to continue?"]);
    $smarty->assign("icon","images/conference.png");

    $html = $oForm->fetchForm("$local_templates_dir/delete_module.tpl", $arrLangModule["Delete Module"], $_POST);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$html."</form>";

    return $contenidoModulo;
}

function mostrar_menu($level, $id_module_level_1, $id_module_level_2, $id_module_level_3)
{
    require_once('libs/paloSantoMenu.class.php');

    global $arrLangModule;
    global $arrConf;
    $respuesta = new xajaxResponse();

    //Nivel 1
    if($level==1)
    {
        $respuesta->addAssign("level_2","innerHTML", "");
        $respuesta->addAssign("level_3","innerHTML", "");
    }
    //Nivel 2
    if($level==2 || $level==3)
    {
        require_once('libs/paloSantoNavigation.class.php');
        $pMenu = new paloMenu($arrConf['elastix_dsn']['menu']);
        $arrMenu = $pMenu->cargar_menu();
        $smarty = NULL;
        $pNav = new paloSantoNavigation($arrMenu, $smarty);
        $arrMenuLevel_2 = $pNav->getArrSubMenu($id_module_level_1);

        $level_2  = "<td align='left'><b>{$arrLangModule["Level 2"]}:</b></td>";
        $level_2 .= "<td>";
            $level_2 .= "<select onchange='mostrar_menu()' name='module_level_2' id='module_level_2'>";
            $tmp_level_2 = "";
            $tmp_id_module_level_2 = "";
            $i=0;
            foreach($arrMenuLevel_2 as $key => $valor)
            {
                if($i==0)
                    $tmp_id_module_level_2 = $key;
                if($key == $id_module_level_2)
                {
                    $tmp_level_2 = $key;
                    $level_2 .= "<option value='$key' selected>{$valor['Name']}</option>";
                }
                else
                    $level_2 .= "<option value='$key'>{$valor['Name']}</option>";
                $i++;
            }
            if($tmp_level_2 == "")
                $id_module_level_2 = $tmp_id_module_level_2;
            $level_2 .= "</select>";
        $level_2 .= "</td>";

        $respuesta->addAssign("level_2","innerHTML", $level_2);
        $respuesta->addAssign("level_3","innerHTML", "");
    }
    //Nivel 3
    if($level==3)
    {
        $level_3  = "<td align='left'><b>{$arrLangModule["Level 3"]}:</b></td>";

        require_once('libs/paloSantoNavigation.class.php');
        $pMenu = new paloMenu($arrConf['elastix_dsn']['menu']);
        $arrMenu = $pMenu->cargar_menu();
        $smarty = NULL;
        $pNav = new paloSantoNavigation($arrMenu, $smarty);
        $arrMenuLevel_3 = $pNav->getArrSubMenu($id_module_level_2);

        if($arrMenuLevel_3 && count($arrMenuLevel_3)>0)
        {
            $level_3  = "<td align='left'><b>{$arrLangModule["Level 3"]}:</b></td>";
            $level_3 .= "<td>";
                $level_3 .= "<select onchange='mostrar_menu()' name='module_level_3' id='module_level_3'>";
                foreach($arrMenuLevel_3 as $key2 => $valor2)
                {
                    if($key2 == $id_module_level_3)
                        $level_3 .= "<option value='$key2' selected>{$valor2['Name']}</option>";
                    else
                        $level_3 .= "<option value='$key2'>{$valor2['Name']}</option>";
                }
                $level_3 .= "</select>";
        }else $level_3 .= "<td align='left'>".$arrLangModule["This module don't have level 3"]."</td>";

        $level_3 .= "</td>";
        $respuesta->addAssign("level_3","innerHTML", $level_3);
    }

    return $respuesta;
}
?>