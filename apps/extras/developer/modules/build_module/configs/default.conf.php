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
  $Id: default.conf.php,v 1.1 2008/01/04 15:55:57 afigueroa Exp $ */

    $lang=get_language();
    $script_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$script_dir/$lang_file"))
        include_once($lang_file);
    else
        include_once("modules/$module_name/lang/en.lang");

global $arrLangModule;
global $arrConfig; 

$arrConfig['module_name'] = 'build_module';
$arrConfig['templates_dir'] = 'themes';
$arrConfig['arr_type'] = array(
        "VALUE" => array (
                    "text",
                    "select",
                    "date",
                    "textarea",
                    "checkbox",
                    "radio",
                    "password",
                    "hidden",
                    "file"),
        "NAME"  => array (
                    $arrLangModule["Type Text"],
                    $arrLangModule["Type Select"],
                    $arrLangModule["Type Date"],
                    $arrLangModule["Type Text Area"],
                    $arrLangModule["Type CheckBox"],
                    $arrLangModule["Type Radio"],
                    $arrLangModule["Type Password"],
                    $arrLangModule["Type Hidden"],
                    $arrLangModule["Type File"]),
        "SELECTED" => "Text",     
        );
?>
