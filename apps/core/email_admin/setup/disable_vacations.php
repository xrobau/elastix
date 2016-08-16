#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
Codificación: UTF-8
+----------------------------------------------------------------------+
| Elastix version 1.2-2                                               |
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
$Id: disable_vacations.php,v 1.1 2011-05-01 05:09:57 Eduardo Cueva <ecueva@palosanto.com> Exp $ */

// script para eliminar el script de vacaciones si ya se ha pasado el periodo de vacaciones

if (!($documentRoot = getenv('ELASTIX_ROOT'))) $documentRoot="/var/www/html";
require_once("$documentRoot/libs/misc.lib.php");
require_once("$documentRoot/configs/default.conf.php");

//global variables framework
global $arrConf;
global $arrLang;
$module_name = "vacations";
require_once "$arrConf[basePath]/libs/paloSantoDB.class.php";
require_once "$arrConf[basePath]/configs/email.conf.php";
require_once "$arrConf[basePath]/modules/$module_name/libs/paloSantoVacations.class.php";

load_default_timezone();

$pDB = new paloDB("sqlite3:////var/www/db/email.db");
$pVacations  = new paloSantoVacations($pDB);

load_language("/var/www/html/");

if (!$pVacations->updateVacationMessageAll()) {
    fputs(STDERR, 'ERR:'.$pVacations->errMsg."\n");
    exit(1);
}
exit(0);
