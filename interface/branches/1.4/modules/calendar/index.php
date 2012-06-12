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
  $Id: index.php,v 1.1 2008/05/12 15:55:57 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    global $db, $phpc_root_path, $Elastix_Document_Root, $action;
    /*
    $phpc_root_path gives the location of the base calendar install.
    if you move this file to a new location, modify $phpc_root_path to point
    to the location where the support files for the callendar are located.
    */
    $phpc_root_path = "modules/$module_name/";
    $Elastix_Document_Root = "/var/www/html";

    require_once('libs/paloSantoDB.class.php');
    $db = new paloDB("sqlite3:////var/www/db/calendar.db");
    if(!empty($db->errMsg)) {
        echo "ERROR DE DB: $db->errMsg <br>";
    }
    /*
    You can modify the following defines to change the color scheme of the
    calendar
    */
    define('SEPCOLOR',     '#000000');
    define('BG_COLOR1',    '#FFFFFF');
    define('BG_COLOR2',    'gray');
    define('BG_COLOR3',    'silver');
    define('BG_COLOR4',    '#CCCCCC');
    define('BG_PAST',      'silver');
    define('BG_FUTURE',    'white');
    define('TEXTCOLOR1',   '#000000');
    define('TEXTCOLOR2',   '#FFFFFF');

    /*
    * Do not modify anything under this point
    */

    define('IN_PHPC', true);

    if(!empty($_GET['action']) && $_GET['action'] == 'style') {
        require_once($phpc_root_path . 'libs/style.php');
        exit;
    }

    require_once($phpc_root_path . 'libs/calendar.php');
    require_once($phpc_root_path . 'libs/setup.php');
    require_once($phpc_root_path . 'libs/globals.php');

    $legal_actions = array( 'event_form', 'event_delete', 'display',
                            'event_submit', 'search');

    if(!in_array($action, $legal_actions, true)) {
        //soft_error(_('Invalid action'));
        global $arrLang;
        require_once "display.php";
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang['Invalid action']);
        return display();
    }

    require_once($phpc_root_path . "libs/$action.php");
    eval("\$output = $action();");

    $calendar = create_xhtml($output);

    /*ELASTIX*/
    require_once "modules/$module_name/configs/default.conf.php";
    global $arrConf, $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    require_once("libs/paloSantoForm.class.php");
    $oForm = new paloForm($smarty, "");
    $oForm->arrFormElements = array();
    $smarty->assign('calendar', $calendar);
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/calendar.tpl", $arrLang["Calendar"]);
    return $contenidoModulo;
}
?>