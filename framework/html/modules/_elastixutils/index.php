<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0-16                                               |
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
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/
require_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/elastixutils.lib.php";

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    load_language_module($module_name);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/" . $templates_dir . '/' . $arrConf['theme'];

    $smarty->assign('module_name', $module_name);
    $sFuncName = 'handleJSON_'.getParameter('action');
    if (function_exists($sFuncName))
        return $sFuncName($smarty, $local_templates_dir, $module_name);

    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status('false');
    $jsonObject->set_error(_tr('Undefined utility action'));
    return $jsonObject->createJSON();
}

function handleJSON_dialogRPM($smarty, $local_templates_dir, $module_name)
{
    Header('Content-Type: application/json');

    $smarty->assign(array(
        'VersionDetails'    =>  _tr('VersionDetails'),
        'VersionPackage'    =>  _tr('VersionPackage'),
        'textMode'          =>  _tr('textMode'),
        'htmlMode'          =>  _tr('htmlMode'),
    ));

    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_message(array(
        'title' =>  _tr('VersionPackage'),
        'html'  =>  $smarty->fetch("$local_templates_dir/_rpms_version.tpl"),
    ));
    return $jsonObject->createJSON();
}

function handleJSON_versionRPM($smarty, $local_templates_dir, $module_name)
{
    Header('Content-Type: application/json');
	$json = new Services_JSON();
    return $json->encode(obtenerDetallesRPMS());
}

function handleJSON_changePasswordElastix($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = setUserPassword();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    $jsonObject->set_error($output['msg']);
    return $jsonObject->createJSON();
}

function handleJSON_search_module($smarty, $local_templates_dir, $module_name)
{
    return searchModulesByName();
}

function handleJSON_changeColorMenu($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = changeMenuColorByUser();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    $jsonObject->set_error($output['msg']);
    return $jsonObject->createJSON();
}

function handleJSON_addBookmark($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('false');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $output = putMenuAsBookmark($id_menu);
        if(getParameter('action') == 'deleteBookmark') $output["data"]["menu_url"] = $id_menu;
        $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
        $jsonObject->set_error($output['msg']);
        $jsonObject->set_message($output['data']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_deleteBookmark($smarty, $local_templates_dir, $module_name)
{
    // La función subyacente agrega el bookmark si no existe, o lo quita si existe
    return handleJSON_addBookmark($smarty, $local_templates_dir, $module_name);
}

function handleJSON_save_sticky_note($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('ERROR');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $description_note = getParameter("description");
        $popup_note = getParameter("popup");
        $output = saveStickyNote($id_menu, $description_note, $popup_note);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'OK' : 'ERROR');
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_get_sticky_note($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('ERROR');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        global $arrConf;

        $pdbACL = new paloDB($arrConf['elastix_dsn']['acl']);
        $pACL = new paloACL($pdbACL);
        $idUser = $pACL->getIdUser($_SESSION['elastix_user']);

        $output = getStickyNote($pdbACL, $idUser, $id_menu);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'OK' : 'ERROR');
        $jsonObject->set_error($output['msg']);
        $jsonObject->set_message($output['data']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_saveNeoToggleTab($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('false');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $statusTab  = getParameter("statusTab");
        $output = saveNeoToggleTabByUser($id_menu, $statusTab);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_showAboutUs($smarty, $local_templates_dir, $module_name)
{
    global $arrConf;

    Header('Content-Type: application/json');

    $jsonObject = new PaloSantoJSON();
    $smarty->assign('ABOUT_ELASTIX_CONTENT', _tr('About Elastix Content'));
    $jsonObject->set_message(array(
        'title' =>  (in_array($arrConf['mainTheme'], array('elastixwave', 'elastixneo'))
                ? _tr('About Elastix2')
                : _tr('About Elastix') . " " . $arrConf['elastix_version']),
        'html'  =>  $smarty->fetch("$local_templates_dir/_aboutus.tpl"),
    ));
    return $jsonObject->createJSON();
}
?>