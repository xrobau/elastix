<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
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
  | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

define('ELASTIX_BASE', '/var/www/html/');
require_once(ELASTIX_BASE.'libs/misc.lib.php');
require_once(ELASTIX_BASE.'configs/default.conf.php');
require_once(ELASTIX_BASE.'libs/paloSantoDB.class.php');

load_default_timezone();

if (!isset($_SERVER['PATH_INFO'])) {
    header('HTTP/1.1 404 Not Found');
    print 'No path info for phone resource! Expected /VENDOR/AUTHTOKEN/resource';
	exit;
}
$pathList = explode('/', $_SERVER['PATH_INFO']);
array_shift($pathList);

// Los primeros 2 elementos son VENDOR, AUTHTOKEN
if (count($pathList) < 2) {
    header('HTTP/1.1 404 Not Found');
    print 'No path info for phone resource! Expected /VENDOR/AUTHTOKEN/resource';
    exit;
}

$sManufacturer = array_shift($pathList);
$sAuthToken = array_shift($pathList);

if (!preg_match('/^\w+$/', $sManufacturer)) {
    header('HTTP/1.1 404 Not Found');
    print 'Unimplemented manufacturer';
    exit;
}

$sVendorPath = "vendor/$sManufacturer.class.php";
if (!file_exists($sVendorPath)) {
    header('HTTP/1.1 404 Not Found');
    print 'Unimplemented manufacturer';
    exit;
}
require_once $sVendorPath;

// Conexión a la base de datos
$dsn = generarDSNSistema('asteriskuser', 'endpointconfig', ELASTIX_BASE);
$db = new paloDB($dsn);
if ($db->errMsg != '') {
    header('HTTP/1.1 500 Internal Server Error');
    print $db->errMsg;
    exit;
}
$db->genQuery('SET NAMES utf8');

// Buscar el endpoint con el hash indicado
$endpoint_id = NULL;
if ($sAuthToken != 'GLOBAL') {
    $tupla = $db->getFirstRowQuery(
        'SELECT manufacturer.name, endpoint.id FROM manufacturer, endpoint '.
        'WHERE manufacturer.id = endpoint.id_manufacturer AND endpoint.authtoken_sha1 = ?',
        TRUE, array($sAuthToken));
    if (!is_array($tupla)) {
        header('HTTP/1.1 500 Internal Server Error');
        print $db->errMsg;
        exit;
    }
    if (count($tupla) <= 0) {
    	header('HTTP/1.1 403 Forbidden');
        print 'Invalid hash';
        exit;
    }
    if ($tupla['name'] != $sManufacturer) {
        header('HTTP/1.1 404 Not Found');
        print 'Manufacturer or model mismatch';
        exit;
    }
    $endpoint_id = $tupla['id'];
}

$vendor = new $sManufacturer($db, 'http://'.$_SERVER['SERVER_ADDR'].'/modules/endpoint_configurator/phonesrv/phonesrv.php/'.$sManufacturer.'/'.$sAuthToken);
$vendor->handle($endpoint_id, $pathList);
?>