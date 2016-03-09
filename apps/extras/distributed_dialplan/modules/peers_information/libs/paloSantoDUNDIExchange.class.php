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
define('ASTERISK_KEYDIR', '/var/lib/asterisk/keys');

// Valores para action en request.php
define('PSDEX_REQUEST_NEW', 1);     // Nueva petición de intercambio de claves
define('PSDEX_REQUEST_ACCEPT', 2);  // Aceptada petición de intercambio de claves
define('PSDEX_REQUEST_REJECT', 3);  // Rechazada petición de intercambio de claves
define('PSDEX_REQUEST_DELETE', 4);  // Revocado el intercambio de claves
define('PSDEX_REQUEST_CONNECT', 5); // Conectar con claves previamente intercambiadas
define('PSDEX_REQUEST_DISCONNECT', 6); // Desconectar sin revocar intercambio

define('PSDEX_STATE_LOCAL_REQUESTING', 'Requesting connection');
define('PSDEX_STATE_LOCAL_WAITING', 'waiting response');
define('PSDEX_STATE_LOCAL_ACCEPTED', 'request accepted');
define('PSDEX_STATE_LOCAL_DISCONNECTED', 'disconnected');
define('PSDEX_STATE_LOCAL_REJECTED', 'request reject');
define('PSDEX_STATE_LOCAL_DELETED', 'request delete');
define('PSDEX_STATE_LOCAL_CONNECTED', 'connected');

define('PSDEX_STATE_REMOTE_REQUESTING', 'Requesting connection');
define('PSDEX_STATE_REMOTE_WAITING', 'waiting response');
define('PSDEX_STATE_REMOTE_DISCONNECTED', 'disconnected');
define('PSDEX_STATE_REMOTE_REJECTED', 'connection rejected');
define('PSDEX_STATE_REMOTE_DELETED', 'connection deleted');
define('PSDEX_STATE_REMOTE_CONNECTED', 'connected');

// TODO: $_SERVER[SERVER_ADDR] no es necesariamente la IP que se desea usar
// para el intercambio DUNDI. Puede ser necesario crear columna para almacenar
// la IP del intercambio DUNDI.

class paloSantoDUNDIExchange
{
    private $_DB;
    var $errMsg;

    function __construct($pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        $this->errMsg = '';
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    /**
     * Procedimiento para verificar si $testsecret es el secreto correcto para
     * aceptar una petición de intercambio nueva. Esta función se llama desde
     * request.php.
     *
     * @param   string  $testsecret Cadena en texto plano a verificar.
     *
     * @return  boolean VERDADERO si es secreto correcto, FALSO en error
     */
    function checkSecret($testsecret)
    {
        $sql = 'SELECT secret FROM general WHERE id = 1';
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE);
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        if (count($tupla) <= 0) {
            $this->errMsg = 'Local secret not set';
            return FALSE;
        }
        return ($tupla['secret'] == $testsecret);
    }

    /**
     * Procedimiento para verificar si se ha introducido previamente una
     * petición de intercambio de claves correspondiente a $mac. Esta función se
     * llama desde request.php.
     *
     * @param string $mac   MAC a verificar para existencia
     *
     * @return boolean
     */
    function existsExchangeRequest($mac)
    {
        $sql = 'SELECT host FROM peer WHERE mac = ?';
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, array($mac));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return (count($tupla) > 0);
    }

    /**
     * Procedimiento para encolar una petición entrante de intercambio de claves
     * para DUNDI. La petición recibida por request.php se asume ya autenticada.
     *
     * @param   string  $mac        MAC reportada por host remoto
     * @param   string  $ip         IP reportada por host remoto
     * @param   string  $company    Razón social reportada por host remoto
     * @param   string  $comment    Comentario asociado a petición
     * @param   string  $public_key Contenido de clave pública para host remoto
     * @param   string  $inkey      Nombre de certificado, de la forma CERaabbccddeeff
     *
     * @return  boolean VERDADERO en caso de éxito, FALSO en error.
     */
    function enqueueExchangeRequest($mac, $ip, $company, $comment, $public_key, $inkey)
    {
        // Validación de formato de MAC
        if (!preg_match('/^([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}/', $mac)) {
            $this->errMsg = 'Invalid MAC';
            return FALSE;
        }
        $mac = strtolower($mac);

        // Validación de nombre de inkey
        $expected_inkey =  $this->_certnameFromMac($mac);
        if ($expected_inkey != $inkey) {
            $this->errMsg = 'MAC/inkey mismatch';
            return FALSE;
        }

        // Validación de IP
        if (!preg_match('/^([[:digit:]]{1,3}\.){3}[[:digit:]]{1,3}$/', $ip)) {
            $this->errMsg = 'Invalid IP';
            return FALSE;
        }

        // Validación de clave pública
        if (!$this->_keyIsValid($public_key)) {
            $this->errMsg = 'Invalid public key';
            return FALSE;
        }

        $sql = 'INSERT INTO '.
            'peer (mac, host, company, comment, inkey, key, model, outkey, status, his_status) '.
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $params = array($mac, $ip, $company, $comment, $inkey, $public_key,
            'symmetric', '', PSDEX_STATE_LOCAL_REQUESTING, PSDEX_STATE_REMOTE_WAITING);
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para marcar como aceptada una petición previamente enviada
     * al host remoto con PSDEX_REQUEST_NEW. Luego de esto, la conexión queda
     * lista para conectarse. Esta función se llama desde request.php.
     *
     * @param   string  $mac        MAC reportada por host remoto
     * @param   string  $ip         IP reportada por host remoto
     * @param   string  $company    Razón social reportada por host remoto
     * @param   string  $comment    Comentario asociado a petición
     * @param   string  $public_key Contenido de clave pública para host remoto
     *
     * @return  boolean VERDADERO en caso de éxito, FALSO en error.
     */
    function updateAcceptedRequest($mac, $ip, $company, $comment, $public_key)
    {
        // Validación de formato de MAC
        if (!preg_match('/^([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}/', $mac)) {
            $this->errMsg = 'Invalid MAC';
            return FALSE;
        }
        $mac = strtoupper($mac);

        // Validación de IP
        if (!preg_match('/^([[:digit:]]{1,3}\.){3}[[:digit:]]{1,3}$/', $ip)) {
            $this->errMsg = 'Invalid IP';
            return FALSE;
        }

        // Validación de clave pública
        if (!$this->_keyIsValid($public_key)) {
            $this->errMsg = 'Invalid public key';
            return FALSE;
        }

        // Buscar los datos de la petición a partir de IP
        $id = $this->_getIdPeerbyRemoteHost($ip);
        if (is_null($id)) return FALSE;

        $inkey = $this->_certnameFromMac($mac);
        if (!$this->_savePublicKeyFile($inkey.'.pub', $public_key)) {
            $this->errMsg = 'Failed to write public key';
            return FALSE;
        }

        // Actualizar clave pública de peer y propiedades
        $bExito = TRUE;
        $this->_DB->beginTransaction();
        $sql =
            'UPDATE peer SET mac = ?, company = ?, comment = ?, inkey = ?, key=?, status = ?, his_status = ? '.
            'WHERE id = ? and status = ?';
        $params = array($mac, $company, $comment, $inkey, $public_key, PSDEX_STATE_LOCAL_ACCEPTED, PSDEX_STATE_REMOTE_DISCONNECTED,
            $id, PSDEX_STATE_LOCAL_WAITING);
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = $this->_DB->errMsg;
            $bExito = FALSE;
        }
        if ($bExito) {
            // Inicializar propiedades estándar de peer
            $bExito = $this->_initializeStdPeerProperties($id);
        }
        if ($bExito)
            $this->_DB->commit();
        else
            $this->_DB->rollBack();
        return $bExito;
    }

    private function _updatePeerStatuses($ip, $local, $remote)
    {
        // Buscar los datos de la petición a partir de IP
        $id = $this->_getIdPeerbyRemoteHost($ip);
        if (is_null($id)) return FALSE;

        $sql = 'UPDATE peer SET status = ?, his_status = ? WHERE id = ?';
        $params = array($local, $remote, $id);
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    private function _updatePeerRemoteStatus($ip, $remote)
    {
        // Buscar los datos de la petición a partir de IP
        $id = $this->_getIdPeerbyRemoteHost($ip);
        if (is_null($id)) return FALSE;

        $sql = 'UPDATE peer SET his_status = ? WHERE id = ?';
        $params = array($remote, $id);
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para marcar como rechazada una petición previamente enviada
     * al host remoto con PSDEX_REQUEST_NEW. Esta función se llama desde
     * request.php.
     *
     * @param   string  $ip         IP reportada por host remoto
     *
     * @return  boolean VERDADERO en caso de éxito, FALSO en error.
     */
    function updateRejectedRequest($ip)
    {
        return $this->_updatePeerStatuses($ip, PSDEX_STATE_LOCAL_REJECTED, PSDEX_STATE_REMOTE_REJECTED);
    }

    /**
     * Procedimiento para marcar como revocada una petición previamente
     * aceptada. Esta función se llama desde request.php.
     *
     * @param   string  $ip         IP reportada por host remoto
     *
     * @return  boolean VERDADERO en caso de éxito, FALSO en error.
     */
    function updateDeletedRequest($ip)
    {
        return $this->_updatePeerStatuses($ip, PSDEX_STATE_LOCAL_DELETED, PSDEX_STATE_REMOTE_DELETED);
    }

    /**
     * Procedimiento para actualizar localmente que el lado remoto avisa que
     * activó la conexión DUNDI. Esta función se llama desde request.php.
     *
     * @param   string  $ip         IP reportada por host remoto
     *
     * @return  boolean VERDADERO en caso de éxito, FALSO en error.
     */
    function updateRemoteConnectedStatus($ip)
    {
        return $this->_updatePeerRemoteStatus($ip, PSDEX_STATE_REMOTE_CONNECTED);
    }

    /**
     * Procedimiento para actualizar localmente que el lado remoto avisa que
     * desactivó la conexión DUNDI. Esta función se llama desde request.php.
     *
     * @param   string  $ip         IP reportada por host remoto
     *
     * @return  boolean VERDADERO en caso de éxito, FALSO en error.
     */
    function updateRemoteDisconnectedStatus($ip)
    {
        return $this->_updatePeerRemoteStatus($ip, PSDEX_STATE_REMOTE_DISCONNECTED);
    }

    private function _getIdPeerbyRemoteHost($host)
    {
        $sql = 'SELECT id from peer where host = ?';
        $result = $this->_DB->getFirstRowQuery($sql, TRUE, array($host));
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        } elseif (count($result) <= 0) {
            $this->errMsg = 'Peer not found by IP';
            return NULL;
        }
        return $result['id'];
    }

    private function _initializeStdPeerProperties($id)
    {
        $stdprop = array(
            'include'   =>  'priv',
            'permit'    =>  'priv',
            'qualify'   =>  'yes',
            'order'     =>  'primary',
        );
        foreach ($stdprop as $k => $v) {
            $sql = 'INSERT INTO parameter(name, value, id_peer) VALUES(?, ?, ?)';
            if (!$this->_DB->genQuery($sql, array($k, $v, $id))) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
        }
        return TRUE;
    }

    private function _certnameFromMac($mac)
    {
        return 'CER'.str_replace(':', '', $mac);
    }

    private function _keyIsValid($key)
    {
        // primero verificar si la primera linea contiene -----BEGIN PUBLIC KEY----- | -----BEGIN RSA PRIVATE KEY-----
        if (!preg_match("/^(.|\n)*-----BEGIN PUBLIC KEY-----(.|\n)*$/",$key)) {
            return FALSE;
        }
        $tmp = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        // segundo verificar si la segunda linea contiene -----END PUBLIC KEY----- | -----END RSA PRIVATE KEY-----
        if(!preg_match("/^(.|\n)*-----END PUBLIC KEY-----(.|\n)*$/", $key)) {
            return FALSE;
        }
        $tmp = str_replace("-----END PUBLIC KEY-----", "", $tmp);
        $tmp = str_replace("\n", "", $tmp);
        // tercero hacer un decode_base64 de lo que se encuentre entre -----BEGIN PUBLIC KEY----- y -----END PUBLIC KEY-----
        $tmpDecode = base64_decode($tmp);
        if (!$tmpDecode) return FALSE;

        // cuarto si no hubo error anterior de ese salida del paso 3 hacer un encode_base64
        $tmpEncode = base64_encode($tmpDecode);// quinto comparar la salida del paso cuatro con $key
        return ($tmpEncode == $tmp);
    }

    /**
     * Procedimiento para guardar el contenido como un archivo de clave pública
     * debajo del directorio de claves públicas de Asterisk. La clave y el
     * nombre de archivo se asumen válidos. Además se asume que el código tiene
     * permisos de escritura sobre el directorio de claves de Asterisk.
     *
     * @param string $basename  Nombre base del archivo
     * @param string $contents  Contenido de la clave pública
     *
     * @return  boolean VERDADERO en éxito, FALSO en error
     */
    private function _savePublicKeyFile($basename, $contents)
    {
        return file_put_contents(ASTERISK_KEYDIR.'/'.$basename, $contents);
    }

    /**
     * Procedimiento para generar una nueva petición de intercambio de claves.
     *
     * @param string $local_ip  IP de la interfaz local a usar como identidad
     * @param string $comment   Comentario asociado a la petición
     * @param string $remote_ip IP del equipo remoto al cual enviar la petición
     * @param string $secret    Contraseña para que equipo remoto acepte petición
     *
     * @return
     */
    function createExchangeRequest($local_ip, $comment, $remote_ip, $secret)
    {
        // Validación de IP
        if (!preg_match('/^([[:digit:]]{1,3}\.){3}[[:digit:]]{1,3}$/', $local_ip)) {
            $this->errMsg = 'Invalid local IP';
            return NULL;
        }
        if (!preg_match('/^([[:digit:]]{1,3}\.){3}[[:digit:]]{1,3}$/', $remote_ip)) {
            $this->errMsg = 'Invalid remote IP';
            return NULL;
        }

        // No enviar petición a host ya conocido.
        $tupla = $this->_DB->getFirstRowQuery(
            'SELECT status FROM peer WHERE host = ?',
            TRUE, array($remote_ip));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        if (count($tupla) > 0 && in_array($tupla['status'], array(
            PSDEX_STATE_LOCAL_WAITING, PSDEX_STATE_LOCAL_ACCEPTED,
            PSDEX_STATE_LOCAL_CONNECTED, PSDEX_STATE_LOCAL_DISCONNECTED,
            PSDEX_STATE_LOCAL_DELETED))) {
            $this->errMsg = _tr('Request to this host has already been sent');
            return NULL;
        }

        // Cargar nombre de organización a partir de tabla general
        $org = $this->_loadOrganizationGeneral();
        if (is_null($org)) return NULL;

        // Averiguar MAC de la interfaz identificada por $local_ip
        $mac = $this->_getMACLocalIP($local_ip);
        if (is_null($mac)) return NULL;

        $vars = array(
            'action'                =>  PSDEX_REQUEST_NEW,
            'secret'                =>  $secret,
            'mac_request'           =>  $mac,
            'ip_request'            =>  $local_ip,
            'company_request'       =>  $org,
            'comment_request'       =>  $comment,
            'certificate_request'   =>  $this->_certnameFromMac($mac),
            'key_request'           =>  '', // A leer a continuación
        );
        $certfile_path = ASTERISK_KEYDIR.'/'.$vars['certificate_request'].'.pub';
        if (!file_exists($certfile_path)) {
            $this->errMsg = 'Certificate file not found: '.$certfile_path;
            return NULL;
        }
        /* No se usa el formato @archivo soportado por cURL porque causa un
         * verdadero upload de archivo que aparece del lado remoto en $_FILES
         * en lugar de $_POST. */
        $vars['key_request'] = file_get_contents($certfile_path);
        $r =  $this->_sendPostRequest($remote_ip, $vars);

        // Guardar la información de peer si petición fue encolada
        if ($r['result'] == 'request') {
            $sql = 'INSERT INTO '.
                'peer (mac, model, host, inkey, outkey, status, his_status) '.
                'VALUES (?, ?, ?, ?, ?, ?, ?)';
            $params = array('', 'symmetric', $remote_ip, '', $vars['certificate_request'],
                PSDEX_STATE_LOCAL_WAITING, PSDEX_STATE_REMOTE_REQUESTING);
            if (!$this->_DB->genQuery($sql, $params)) {
                $this->errMsg = $this->_DB->errMsg;
                $r['posterror'] = TRUE;
            }
        }
        return $r;
    }

    /**
     * Procedimiento para aprobar una petición de intercambio de claves.
     *
     * @param string    $local_ip   IP local a usar para respuesta.
     * @param integer   $id         ID del peer a aprobar
     *
     * @return
     */
    function acceptExchangeRequest($local_ip, $id)
    {
        // Cargar datos del peer
        $dataPeer = $this->loadPeerDataById($id);
        if (!is_array($dataPeer)) return FALSE;
        if (count($dataPeer) <= 0) {
            $this->errMsg = _tr('Peer not found by ID');
            return NULL;
        }

        // Estado del peer tiene que ser PSDEX_STATE_LOCAL_REQUESTING
        if ($dataPeer['status'] != PSDEX_STATE_LOCAL_REQUESTING) {
            $this->errMsg = _tr('Peer not in REQUESTING state');
            return NULL;
        }

        // Cargar nombre de organización a partir de tabla general
        $org = $this->_loadOrganizationGeneral();
        if (is_null($org)) return NULL;

        // Guardar clave pública remota
        if (!$this->_savePublicKeyFile($this->_certnameFromMac($dataPeer['mac']).'.pub', $dataPeer['key'])) {
            $this->errMsg = _tr('Failed to write public key');
            return NULL;
        }

        // Averiguar MAC de la interfaz identificada por $local_ip
        $mac = $this->_getMACLocalIP($local_ip);
        if (is_null($mac)) return NULL;

        $outkey = $this->_certnameFromMac($mac);
        $certfile_path = ASTERISK_KEYDIR.'/'.$outkey.'.pub';
        if (!file_exists($certfile_path)) {
            $this->errMsg = _tr('Certificate file not found').': '.$certfile_path;
            return NULL;
        }
        /* No se usa el formato @archivo soportado por cURL porque causa un
         * verdadero upload de archivo que aparece del lado remoto en $_FILES
         * en lugar de $_POST. */
        $vars = array(
            'action'            =>  PSDEX_REQUEST_ACCEPT,
            'mac_answer'        =>  $mac,
            'ip_answer'         =>  $local_ip,
            'company_answer'    =>  $org,
            'comment_answer'    =>  _tr('accepted connection'),
            'key_answer'        =>  file_get_contents($certfile_path),
        );
        $r =  $this->_sendPostRequest($dataPeer['host'], $vars);

        // Actualizar la información del peer si se acepta la respuesta
        if ($r['result'] == 'accept') {
            // Actualizar clave pública de peer y propiedades
            $bExito = TRUE;
            $this->_DB->beginTransaction();
            $sql = 'UPDATE peer SET outkey = ?, status = ? WHERE id = ?';
            $params = array($outkey, PSDEX_STATE_LOCAL_DISCONNECTED, $id);
            if (!$this->_DB->genQuery($sql, $params)) {
                $this->errMsg = $this->_DB->errMsg;
                $bExito = FALSE;
            }
            if ($bExito) {
                // Inicializar propiedades estándar de peer
                $bExito = $this->_initializeStdPeerProperties($id);
            }
            if ($bExito)
                $this->_DB->commit();
            else
                $this->_DB->rollBack();
            $r['posterror'] = !$bExito;
        }
        return $r;
    }

    private function _loadOrganizationGeneral()
    {
        $sql = 'SELECT organization FROM general ORDER BY id LIMIT 1';
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE);
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        } elseif (count($tupla) <= 0) {
            $this->errMsg = _tr('General information not filled out');
            return NULL;
        }
        return $tupla['organization'];
    }

    /**
     * Procedimiento para rechazar una petición de intercambio de claves.
     *
     * @param string    $local_ip   IP local a usar para respuesta.
     * @param integer   $id         ID del peer a aprobar
     *
     * @return
     */
    function rejectExchangeRequest($local_ip, $id)
    {
        return $this->_rejectDeleteExchangeRequest($local_ip, $id, PSDEX_REQUEST_REJECT);
    }

    /**
     * Procedimiento para revocar un intercambio de claves.
     *
     * @param string    $local_ip   IP local a usar para respuesta.
     * @param integer   $id         ID del peer a aprobar
     *
     * @return
     */
    function deleteExchangeRequest($local_ip, $id)
    {
        return $this->_rejectDeleteExchangeRequest($local_ip, $id, PSDEX_REQUEST_DELETE);
    }

    private function _rejectDeleteExchangeRequest($local_ip, $id, $action)
    {
        // Cargar datos del peer
        $dataPeer = $this->loadPeerDataById($id);
        if (!is_array($dataPeer)) return FALSE;
        if (count($dataPeer) <= 0) {
            $this->errMsg = _tr('Peer not found by ID');
            return NULL;
        }

        if ($action == PSDEX_REQUEST_REJECT) {
            // Estado del peer tiene que ser PSDEX_STATE_LOCAL_REQUESTING
            if ($dataPeer['status'] != PSDEX_STATE_LOCAL_REQUESTING) {
                $this->errMsg = _tr('Peer not in REQUESTING state');
                return NULL;
            }
        }

        $vars = array(
            'action'            =>  $action,
            'ip_answer'         =>  $local_ip,
        );
        $r =  $this->_sendPostRequest($dataPeer['host'], $vars);

        // Actualizar la información del peer si se acepta la respuesta
        if ($r['result'] == 'reject') {
            $bExito = TRUE;
            $this->_DB->beginTransaction();
            $sqls = array(
                'DELETE FROM parameter WHERE id_peer = ?',
                'DELETE FROM peer WHERE id = ?',
            );
            foreach ($sqls as $sql) {
                if (!$this->_DB->genQuery($sql, array($id))) {
                    $this->errMsg = $this->_DB->errMsg;
                    $bExito = FALSE;
                }
                if (!$bExito) break;
            }
            if ($bExito) {
                $this->_DB->commit();

                $certfile_path = ASTERISK_KEYDIR.'/'.basename($dataPeer['inkey']).'.pub';
                if (file_exists($certfile_path)) unlink($certfile_path);
            } else
                $this->_DB->rollBack();
            $r['posterror'] = !$bExito;
        }
        return $r;
    }

    /**
     * Procedimiento para cambiar el estado de conexión de un peer y actualizar
     * el estado DUNDI.
     *
     * @param string    $local_ip       IP local a usar para conexión
     * @param integer   $id             ID del peer modificado
     * @param boolean   $bNewState      Nuevo estado conectado/desconectado
     *
     * @return
     */
    function setPeerConnectedState($local_ip, $id, $bNewState)
    {
        $bNewState = (bool)$bNewState;
        $action = $bNewState ? PSDEX_REQUEST_CONNECT : PSDEX_REQUEST_DISCONNECT;

        // Cargar datos del peer
        $dataPeer = $this->loadPeerDataById($id);
        if (!is_array($dataPeer)) return FALSE;
        if (count($dataPeer) <= 0) {
            $this->errMsg = _tr('Peer not found by ID');
            return NULL;
        }

        if ($bNewState) {
            if (!in_array($dataPeer['status'], array(PSDEX_STATE_LOCAL_ACCEPTED, PSDEX_STATE_LOCAL_DISCONNECTED))) {
                $this->errMsg = _tr('Peer not ACCEPTED or DISCONNECTED');
                return NULL;
            }
        } else {
            if ($dataPeer['status'] != PSDEX_STATE_LOCAL_CONNECTED) {
                $this->errMsg = _tr('Peer not CONNECTED');
                return NULL;
            }
            if (!in_array($dataPeer['his_status'], array(PSDEX_STATE_REMOTE_DISCONNECTED, PSDEX_STATE_REMOTE_CONNECTED))) {
                $this->errMsg = _tr('Peer not CONNECTED remotely');
                return NULL;
            }
        }

        $vars = array(
            'action'            =>  $action,
            'ip_answer'         =>  $local_ip,
        );
        $r =  $this->_sendPostRequest($dataPeer['host'], $vars);

        // Actualizar la información del peer si se acepta la respuesta
        // ATENCIÓN: connected se recibe también para desconexión.
        if ($r['result'] == 'connected') {
            $newlocalstate = $bNewState ? PSDEX_STATE_LOCAL_CONNECTED : PSDEX_STATE_LOCAL_DISCONNECTED;

            $bExito = TRUE;
            $sql = 'UPDATE peer SET status = ? WHERE id = ?';
            $params = array($newlocalstate, $id);
            if (!$this->_DB->genQuery($sql, $params)) {
                $this->errMsg = $this->_DB->errMsg;
                $bExito = FALSE;
            }
            $r['posterror'] = !$bExito;
        }
        return $r;
    }

    /**
     * Cargar los datos del peer a partir del ID de peer.
     *
     */
    function loadPeerDataById($id)
    {
        $sql = 'SELECT * FROM peer WHERE id = ?';
        $result = $this->_DB->getFirstRowQuery($sql, TRUE, array($id));
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $result;
    }

    /**
     * Procedimiento para escribir la configuración DUNDI a partir de la base
     * de datos y recargar Asterisk para que la configuración tome efecto.
     *
     * @return  bool    VERDADERO en éxito, FALSO en error.
     */
    function refreshDUNDIConfig()
    {
        // Leer todos los campos que se destinan al archivo
        $sql = 'SELECT id, mac, model, host, inkey, outkey FROM peer WHERE status = ?';
        $params = array(PSDEX_STATE_LOCAL_CONNECTED);
        $result = $this->_DB->fetchTable($sql, TRUE, $params);
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        $peerlist = array();
        foreach ($result as $tupla) {
            $peerlist[$tupla['id']] = array(
                'mac'   =>  $tupla['mac'],
                'params'=>  array(
                    'host'  =>  $tupla['host'],
                    'model' =>  $tupla['model'],
                    'inkey' =>  $tupla['inkey'],
                    'outkey' =>  $tupla['outkey'],
                ),
            );
        }

        // Leer propiedades adicionales del peer
        $sql = 'SELECT peer.id AS id, parameter.name AS name, parameter.value AS value '.
            'FROM peer, parameter WHERE peer.id = parameter.id_peer AND peer.status = ?';
        $params = array(PSDEX_STATE_LOCAL_CONNECTED);
        $result = $this->_DB->fetchTable($sql, TRUE, $params);
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        foreach ($result as $tupla) if (isset($peerlist[$tupla['id']])) {
            $peerlist[$tupla['id']]['params'][$tupla['name']] = $tupla['value'];
        }

        // Construir contenido requerido del archivo
        $peerfile = '';
        foreach ($peerlist as $peerdata) {
            $peerfile .= "[{$peerdata['mac']}]\n";
            foreach ($peerdata['params'] as $k => $v) $peerfile .= "$k=$v\n";
            $peerfile .= "\n\n";
        }
        if (FALSE === file_put_contents('/etc/asterisk/dundi_peers_custom_elastix.conf', $peerfile)) {
            $this->errMsg = _tr('Failed to write DUNDI configuration');
            return FALSE;
        }

        require_once 'libs/paloSantoConfig.class.php';
        require_once '/var/lib/asterisk/agi-bin/phpagi-asmanager.php';

        // Leer credenciales AMI para reiniciar Asterisk
        $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
        $arrConfig = $pConfig->leer_configuracion(false);
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect($arrConfig['AMPDBHOST']['valor'], 'admin', $arrConfig['AMPMGRPASS']['valor'])) {
            $this->errMsg = _tr('Error when connecting to Asterisk Manager');
            return FALSE;
        }
        $salida = $astman->Command('reload');
        $astman->disconnect();
        return (strtoupper($salida["Response"]) != "ERROR");
    }

    private function _getMACLocalIP($local_ip)
    {
        require_once 'libs/paloSantoNetwork.class.php';

        $pNet = new paloNetwork();
        $arrEths = $pNet->obtener_interfases_red_fisicas();
        foreach ($arrEths as $idEth => $arrEth){
            if($arrEth['Inet Addr'] == $local_ip) {
                /* En caso de que exista eth0, se devuelve siempre la MAC de
                 * esta interfaz, para mantener consistencia de identidad en
                 * caso de múltiples IPs. Pero si no existe, se devuelve la
                 * MAC de la IP identificada. */
                return isset($arrEths['eth0'])
                    ? $arrEths['eth0']['HWaddr']
                    : $arrEth['HWaddr'];
            }
        }
        $this->errMsg = 'Unable to identify MAC for IP';
        return NULL;
    }

    private function _sendPostRequest($ip, $vars)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$ip}/elastixConnection/request.php");
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        $result = array(
            'body'          =>  curl_exec($ch),
            'diagnostics'   =>  'cURL error',
            'result'        =>  'invalid body',
            'posterror'     =>  FALSE,
        );
        $result['errno'] = curl_errno($ch);
        $result['error'] = curl_error($ch);
        $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Parsear el cuerpo devuelto, si existe
        if ($result['body'] !== FALSE) {
            $result['diagnostics'] = 'invalid body';
            $regs = NULL;
            if (preg_match('/^(.*)BEGIN\s+(.+)\s+END/s', $result['body'], $regs)) {
                $result['diagnostics'] = $regs[1];
                $result['result'] = $regs[2];
            }
        }
        return $result;
    }

    function isLocalCertificateCreated($local_ip)
    {
        // Averiguar MAC de la interfaz identificada por $local_ip
        $mac = $this->_getMACLocalIP($local_ip);
        if (is_null($mac)) return FALSE;

        $certfile_path = ASTERISK_KEYDIR.'/'.$this->_certnameFromMac($mac).'.pub';
        if (!file_exists($certfile_path)) {
            $this->errMsg = 'Certificate file not found: '.$certfile_path;
            return FALSE;
        }
        return TRUE;
    }

    function countExchangedPeers()
    {
        $tupla = $this->_DB->getFirstRowQuery('SELECT COUNT(*) AS N FROM peer', TRUE);
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return $tupla['N'];
    }

    function listExchangedPeers($limit=null, $offset=null)
    {
        $sql = 'SELECT id, host, status, his_status, company FROM peer';
        $params = array();
        if (!is_null($limit) && !is_null($offset)) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;
        }
        $result = $this->_DB->fetchTable($sql, TRUE, $params);
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $result;
    }
}

?>