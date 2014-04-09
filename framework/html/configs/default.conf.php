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
  $Id: default.conf.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

global $arrConf;

$arrConf['basePath'] = (!empty($_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT'] : "/var/www/html";
$arrConf['elastix_dbdir'] = $arrConf['basePath'].'/../db';
$arrConf['elastix_dsn'] = array(
                                "acl"       =>  "sqlite3:///$arrConf[elastix_dbdir]/acl.db",
                                "settings"  =>  "sqlite3:///$arrConf[elastix_dbdir]/settings.db",
                                "menu"      =>  "sqlite3:///$arrConf[elastix_dbdir]/menu.db",
                                "samples"   =>  "sqlite3:///$arrConf[elastix_dbdir]/samples.db",
                            );
$arrConf['theme'] = 'default'; //theme personal para los modulos esencialmente

// Verifico si las bases del framework están, debido a la migración de dichas bases como archivos .db a archivos .sql
checkFrameworkDatabases($arrConf['elastix_dbdir']);

$arrConf['mainTheme'] = load_theme($arrConf['basePath']."/"); //theme para la parte plantilla principal del elastix (se usa para la inclusion de los css)
$arrConf['elastix_version'] = load_version_elastix($arrConf['basePath']."/"); //la version y le release  del sistema elastix
$arrConf['defaultMenu'] = 'config';
$arrConf['language'] = 'en';
$arrConf['cadena_dsn'] = "mysql://asterisk:asterisk@localhost/call_center";
?>
