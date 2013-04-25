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

    $sFuncName = 'handleJSON_'.getParameter('action');
    if (function_exists($sFuncName))
        return $sFuncName($smarty, $module_name);
    
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status('false');
    $jsonObject->set_error(_tr('Undefined utility action'));
    return $jsonObject->createJSON();
}

function handleJSON_versionRPM($smarty, $module_name)
{
	$json = new Services_JSON();
    return $json->encode(obtenerDetallesRPMS());
}

function handleJSON_changePasswordElastix($smarty, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = setUserPassword();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    $jsonObject->set_error($output['msg']);
    return $jsonObject->createJSON();
}
?>