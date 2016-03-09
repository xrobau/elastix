<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
 Codificación: UTF-8
 +----------------------------------------------------------------------+
 | Elastix version 1.4-1                                               |
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
*/

$sDocumentRoot = '/var/www/html';
$module_name = 'peers_information';

// Agregar directorio libs de script a la lista de rutas a buscar para require()
ini_set('include_path', "$sDocumentRoot:".ini_get('include_path'));

require_once('libs/misc.lib.php');
require_once('configs/default.conf.php');
require_once("modules/$module_name/configs/default.conf.php");
require_once("modules/$module_name/libs/paloSantoDUNDIExchange.class.php");

/**
 * Procedimiento para recoger una lista de variables desde $_POST, o reemplazar
 * por cadena vacía en caso de que no existan.
 *
 * @param   array   $varlist    Lista de claves a extraer de POST
 * @return  array   Arreglo asociativo con las claves indicadas
 */
function recogerVariablesPOST($varlist)
{
    $vals = array();
    foreach ($varlist as $k) $vals[$k] = isset($_POST[$k]) ? $_POST[$k] : '';
    return $vals;
}

/**
 * Procedimiento para evaluar la petición de DUNDI a realizar y extraer los
 * parámetros requeridos para su ejecución.
 *
 * @param   object  $oDEX   Objeto paloSantoDUNDIExchange
 * @param   string  $action Número de la acción a realizar
 * @return  string  Cadena a devolver como respuesta oficial.
 */
function evalRequest($oDEX, $action)
{
    // Elegir acción a realizar
    switch ($action) {
    case PSDEX_REQUEST_NEW:
        $v = recogerVariablesPOST(array('mac_request', 'ip_request', 'company_request',
            'comment_request', 'certificate_request', 'key_request', 'secret'));
        if (!$oDEX->checkSecret($v['secret']))
            return 'nosecret';
        if ($oDEX->existsExchangeRequest($v['mac_request']))
            return 'exist';
        if (!$oDEX->enqueueExchangeRequest($v['mac_request'], $v['ip_request'],
            $v['company_request'], $v['comment_request'], $v['key_request'],
            $v['certificate_request'])) {
            print "{$oDEX->errMsg}\n";
            return 'norequest';
        }
        return 'request';
    case PSDEX_REQUEST_ACCEPT:
        $v = recogerVariablesPOST(array('ip_answer', 'mac_answer', 'key_answer',
            'company_answer', 'comment_answer'));
        if (!$oDEX->updateAcceptedRequest($v['mac_answer'], $v['ip_answer'],
            $v['company_answer'], $v['comment_answer'], $v['key_answer'])) {
            print "{$oDEX->errMsg}\n";
            return 'norequest';
        }
        return 'accept';
    case PSDEX_REQUEST_REJECT:
        $v = recogerVariablesPOST(array('ip_answer'));
        if (!$oDEX->updateRejectedRequest($v['ip_answer'])) {
            print "{$oDEX->errMsg}\n";
            return ($oDEX->errMsg == 'Peer not found by IP') ? 'reject' : 'norequest';
        }
        return 'reject';
    case PSDEX_REQUEST_DELETE:
        $v = recogerVariablesPOST(array('ip_answer'));
        if (!$oDEX->updateDeletedRequest($v['ip_answer'])) {
            print "{$oDEX->errMsg}\n";
            return ($oDEX->errMsg == 'Peer not found by IP') ? 'reject' : 'norequest';
        }
        return 'reject';
    case PSDEX_REQUEST_CONNECT:
        $v = recogerVariablesPOST(array('ip_answer'));
        if (!$oDEX->updateRemoteConnectedStatus($v['ip_answer'])) {
            print "{$oDEX->errMsg}\n";
            return 'norequest';
        }
        return 'connected';
    case PSDEX_REQUEST_DISCONNECT:
        $v = recogerVariablesPOST(array('ip_answer'));
        if (!$oDEX->updateRemoteDisconnectedStatus($v['ip_answer'])) {
            print "{$oDEX->errMsg}\n";
            return 'norequest';
        }
        return 'connected'; // <-- Sí, devuelve connected para desconexión.
    default:
        return 'norequest';
    }
}

Header('Content-Type: text/plain');
$oDEX = new paloSantoDUNDIExchange($arrConfModule['dsn_conn_database']);
if ($oDEX->errMsg != '') {
    Header('HTTP/1.1 503 Internal Server Error');
    print $oDEX->errMsg;
} else {
    /* La función evalRequest puede escribir a la salida un mensaje de error
     * adicional a la respuesta oficial, y este mensaje aparecerá ANTES de la
     * secuencia BEGIN...END que indica la respuesta de la petición. La
     * implementación anterior ignora todo texto anterior al BEGIN...END, y la
     * implementación nueva puede mostrar mensajes adicionales de diagnóstico
     * con esta cadena. */
    print 'BEGIN '.evalRequest($oDEX, (int)(isset($_POST['action']) ? $_POST['action'] : 0))." END\n";
}
?>