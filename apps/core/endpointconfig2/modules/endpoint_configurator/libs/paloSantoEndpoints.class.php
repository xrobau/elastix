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
require_once 'libs/misc.lib.php';

class paloSantoEndpoints
{
    private $_db = NULL;
    private $_errMsg = NULL;

    private function _getDB()
    {
        global $arrConf;

        if (is_null($this->_db)) {
            $dsn = generarDSNSistema('asteriskuser', 'endpointconfig');
            $this->_db = new paloDB($dsn);
            if ($this->_db->errMsg != '') {
                $this->_errMsg = $this->_db->errMsg;
                $this->_db = NULL;
            } else {
            	$this->_db->genQuery('SET NAMES utf8');
            }
        }
        return $this->_db;
    }

    function getErrMsg() { return $this->_errMsg; }

    function leerModelos()
    {
    	if (is_null($db = $this->_getDB())) return NULL;
        $sqlModels =
            'SELECT id_manufacturer, id AS id_model, name AS name_model, '.
                'description, max_accounts, static_ip_supported, '.
                'dynamic_ip_supported, static_prov_supported '.
            'FROM model';
        $recordset = $db->fetchTable($sqlModels, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read models: '.$db->errMsg;
            return NULL;
        }
        $models = array();
        foreach ($recordset as $row) {
            foreach (array('max_accounts') as $k) $row[$k] = (int)$row[$k];
            foreach (array('static_ip_supported', 'dynamic_ip_supported', 'static_prov_supported') as $k)
                $row[$k] = (bool)$row[$k];
        	if (!isset($models[$row['id_manufacturer']]))
                $models[$row['id_manufacturer']] = array(
                    'unknown' => array(
                        'id_model' => 'unknown',
                        'name_model' => _tr('(not detected)')
                    )
                );
            $models[$row['id_manufacturer']][$row['id_model']] = $row;
        }
        return $models;
    }

    function leerEndpoints()
    {
        // TODO: idear manera de obtener diálogo a usar
        if (is_null($db = $this->_getDB())) return NULL;
        $sqlEndpoints = <<<SQL_LEER_ENDPOINTS
SELECT endpoint.id AS id_endpoint, endpoint.id_manufacturer, IFNULL(endpoint.id_model, 'unknown') AS id_model,
    endpoint.mac_address, endpoint.last_known_ipv4, endpoint.last_scanned,
    endpoint.last_modified, endpoint.last_configured,
    manufacturer.name AS name_manufacturer,
    (SELECT COUNT(*) FROM endpoint_account WHERE endpoint_account.id_endpoint = endpoint.id) AS num_accounts,
    'standard' AS detail_dialog
FROM endpoint, manufacturer WHERE endpoint.id_manufacturer = manufacturer.id
ORDER BY endpoint.id
SQL_LEER_ENDPOINTS;
        $recordset = $db->fetchTable($sqlEndpoints, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoints: '.$db->errMsg;
        	$recordset = NULL;
        }
        return $recordset;
    }

    function leerEndpointsDescarga()
    {
        if (is_null($db = $this->_getDB())) return NULL;
        $sqlEndpoints = <<<SQL_LEER_ENDPOINTS
SELECT endpoint.id, endpoint.id_manufacturer, manufacturer.name AS name_manufacturer,
    endpoint.id_model, model.name AS name_model, endpoint.mac_address
FROM (endpoint, manufacturer)
LEFT JOIN (model) ON endpoint.id_model = model.id
WHERE endpoint.id_manufacturer = manufacturer.id
SQL_LEER_ENDPOINTS;
        $recordset = $db->fetchTable($sqlEndpoints, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoints: '.$db->errMsg;
            $recordset = NULL;
            return NULL;
        }
        $endpoints = array();
        foreach ($recordset as $tupla) {
        	$endpoints[$tupla['id']] = array(
                'id_manufacturer'   =>  $tupla['id_manufacturer'],
                'name_manufacturer' =>  $tupla['name_manufacturer'],
                'id_model'          =>  $tupla['id_model'],
                'name_model'        =>  $tupla['name_model'],
                'mac_address'       =>  $tupla['mac_address'],
                'properties'        =>  array(),
                'accounts'          =>  array(),
            );
        }
        unset($recordset);

        // Leer las propiedades de cada endpoint
        $sqlLeerProp = <<<SQL_LEER_PROP
SELECT id_endpoint, property_key, property_value FROM endpoint_properties;
SQL_LEER_PROP;
        $recordset = $db->fetchTable($sqlLeerProp, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoint properties: '.$db->errMsg;
            $recordset = NULL;
            return NULL;
        }
        foreach ($recordset as $tupla) if (isset($endpoints[$tupla['id_endpoint']])) {
        	$endpoints[$tupla['id_endpoint']]['properties'][$tupla['property_key']] = $tupla['property_value'];
        }
        unset($recordset);

        // Leer las cuentas asociadas a cada endpoint
        $sqlLeerAcc = <<<SQL_LEER_ACC
SELECT id, id_endpoint, tech, account, priority FROM endpoint_account
ORDER BY id_endpoint, priority
SQL_LEER_ACC;
        $recordset = $db->fetchTable($sqlLeerAcc, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoint accounts: '.$db->errMsg;
            $recordset = NULL;
            return NULL;
        }
        foreach ($recordset as $tupla) if (isset($endpoints[$tupla['id_endpoint']])) {
            $key = $tupla['tech'].'/'.$tupla['account'];
            $endpoints[$tupla['id_endpoint']]['accounts'][] = array(
                'id'        =>  $tupla['id'],
                'tech'      =>  $tupla['tech'],
                'account'   =>  $tupla['account'],
                'priority'  =>  $tupla['priority'],
                'properties'=>  array(),
            );
        }
        unset($recordset);

        // Leer y almacenar las propiedades de la cuenta
        $sqlLeerAccProp = <<<SQL_LEER_ACC_PROP
SELECT endpoint_account.id_endpoint, endpoint_account_properties.id_endpoint_account,
    endpoint_account_properties.property_key, endpoint_account_properties.property_value
FROM endpoint_account_properties, endpoint_account
WHERE endpoint_account_properties.id_endpoint_account = endpoint_account.id
SQL_LEER_ACC_PROP;
        $recordset = $db->fetchTable($sqlLeerAccProp, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoint account properties: '.$db->errMsg;
            $recordset = NULL;
            return NULL;
        }
        foreach ($recordset as $tupla) {
        	if (isset($endpoints[$tupla['id_endpoint']])) {
        		for ($i = 0; $i < count($endpoints[$tupla['id_endpoint']]['accounts']); $i++) {
        			if ($endpoints[$tupla['id_endpoint']]['accounts'][$i]['id'] == $tupla['id_endpoint_account']) {
        				$endpoints[$tupla['id_endpoint']]['accounts'][$i]['properties'][$tupla['property_key']] = $tupla['property_value'];
        			}
        		}
        	}
        }
        unset($recordset);

        return $endpoints;
    }

    function iniciarScanRed($netmask)
    {
    	$sComando = '/usr/bin/elastix-helper detect_endpoints '.escapeshellarg($netmask).' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->_errMsg = implode('', $output);
        	return NULL;
        }
        if (count($output) <= 0) {
            $this->_errMsg = '(internal) failed to find scan monitor socket: '.implode('', $output);
        	return NULL;
        }
        $sockFile = trim($output[0]);

        // Esperar hasta 5 segundos a que aparezca el socket
        $s = microtime(TRUE);
        while (microtime(TRUE) - $s < 5.0 && !file_exists($sockFile)) {
        	usleep(100000);
        }
        if (!file_exists($sockFile)) {
            $this->_errMsg = '(internal) failed to find scan monitor socket: '.$sockFile;
        	return NULL;
        }
        return $sockFile;
    }

    function cancelarScanRed($socket)
    {
    	$errno = $errstr = NULL;
        $h = @fsockopen('unix://'.$socket, -1, $errno, $errstr);
        if ($h === FALSE) {
            if ($errno == 2) return TRUE;   // ENOENT
            $this->_errMsg = '(internal) failed to cancel scan: ('.$errno.') '.$errstr;
        	return FALSE;
        }
        fwrite($h, "quit\n");
        while ($s = fgets($h)) {
        	if ($s == "quit\n") break;
        }
        fclose($h);
        return TRUE;
    }

    /**
     * Procedimiento para asignar que un endpoint en particular es un nuevo
     * modelo.
     *
     * @param   int $id_endpoint    ID en la base de datos del endpoint
     * @param   int $id_model       ID en la base de datos del modelo
     *
     * @return  mixed   NULL en caso de error, 'unchanged' si el nuevo modelo es
     * el mismo que estaba asignado antes, o la fecha actual (de modificación).
     */
    function asignarModeloEndpoint($id_endpoint, $id_model)
    {
        if (is_null($db = $this->_getDB())) return FALSE;
        $db->beginTransaction();
        $r = $this->_asignarModeloEndpoint($db, $id_endpoint, $id_model);
        if (is_null($r))
            $db->rollBack();
        else $db->commit();
        return $r;
    }

    private function _asignarModeloEndpoint($db, $id_endpoint, $id_model)
    {
        if (!is_null($id_model)) {
            $sqlFiltroEndpoint = <<<SQL_FILTRO_ENDPOINT
SELECT endpoint.id AS id_endpoint, endpoint.id_model AS id_old_model, model.id AS id_new_model, model.max_accounts
FROM (endpoint)
    LEFT JOIN (model) ON model.id_manufacturer = endpoint.id_manufacturer AND model.id = ?
WHERE endpoint.id = ?
SQL_FILTRO_ENDPOINT;
            $tupla = $db->getFirstRowQuery($sqlFiltroEndpoint, TRUE, array($id_model, $id_endpoint));
            if (!is_array($tupla)) {
                $this->_errMsg = '(internal) Failed to check model for endpoint: '.$db->errMsg;
                return NULL;
            }

            if (count($tupla) <= 0) {
                $this->_errMsg = _tr('Invalid endpoint ID');
            	return NULL;
            }

            if (is_null($tupla['id_new_model'])) {
                $this->_errMsg = _tr('Invalid model ID');
                return NULL;
            }

            // Si se especifica el mismo modelo, no se hace nada
            if ($tupla['id_old_model'] == $id_model) return 'unchanged';

            $iMaxCuentas = $tupla['max_accounts'];

            // Si el nuevo modelo permite menos cuentas que el anterior, se
            // quitan según la tecnología
            foreach (array('sip', 'iax2') as $tech) {
            	$sModelProperty = 'max_'.$tech.'_accounts';
                $tupla = $db->getFirstRowQuery(
                    'SELECT property_value FROM model_properties WHERE id_model = ? AND property_key = ?',
                    TRUE, array($id_model, $sModelProperty));
                if (!is_array($tupla)) {
                    $this->_errMsg = '(internal) Failed to check accounts for endpoint: '.$db->errMsg;
                    return NULL;
                }
                $iMaxTech = (count($tupla) > 0) ? (int)$tupla['property_value'] : 0;

                $listaEndpoints = $db->fetchTable(
                    'SELECT id FROM endpoint_account WHERE id_endpoint = ? AND tech = ? ORDER BY priority',
                    TRUE, array($id_endpoint, $tech));
                if (!is_array($listaEndpoints)) {
                    $this->_errMsg = '(internal) Failed to check accounts for endpoint: '.$db->errMsg;
                    return NULL;
                }
                while (count($listaEndpoints) > $iMaxTech) {
                    $tupla = array_pop($listaEndpoints);
                	$r = $db->genQuery('DELETE FROM endpoint_account WHERE id = ?', array($tupla['id']));
                    if (!$r) {
                        $this->_errMsg = '(internal) Failed to remove excess accounts for endpoint: '.$db->errMsg;
                        return NULL;
                    }
                }
            }

            // Si el número total todavía excede el máximo total, se quitan
            $listaEndpoints = $db->fetchTable(
                'SELECT id FROM endpoint_account WHERE id_endpoint = ? ORDER BY priority',
                TRUE, array($id_endpoint));
            if (!is_array($listaEndpoints)) {
                $this->_errMsg = '(internal) Failed to check accounts for endpoint: '.$db->errMsg;
                return NULL;
            }
            while (count($listaEndpoints) > $iMaxCuentas) {
                $tupla = array_pop($listaEndpoints);
                $r = $db->genQuery('DELETE FROM endpoint_account WHERE id = ?', array($tupla['id']));
                if (!$r) {
                    $this->_errMsg = '(internal) Failed to remove excess accounts for endpoint: '.$db->errMsg;
                    return NULL;
                }
            }

            $r = $db->genQuery('UPDATE endpoint SET id_model = ?, last_modified = NOW() WHERE id = ?',
                array($id_model, $id_endpoint));
            if (!$r) {
                $this->_errMsg = '(internal) Failed to update model for endpoint: '.$db->errMsg;
                return NULL;
            }
        } else {
        	// Quitar todas las cuentas asociadas con el endpoint
            $r = $db->genQuery('DELETE FROM endpoint_account WHERE id_endpoint = ?', array($id_endpoint));
            if (!$r) {
                $this->_errMsg = '(internal) Failed to remove excess accounts for endpoint: '.$db->errMsg;
                return NULL;
            }

            // Anular el modelo asociado al endpoint
            $r = $db->genQuery(
                'UPDATE endpoint SET id_model = NULL, last_modified = NOW() '.
                'WHERE id = ? AND id_model IS NOT NULL',
                array($id_endpoint));
            if (!$r) {
                $this->_errMsg = '(internal) Failed to update model for endpoint: '.$db->errMsg;
                return NULL;
            }
        }

        return date('Y-m-d H:i:s');
    }

    function leerCuentasNoAsignadas()
    {
        if (is_null($db = $this->_getDB())) return NULL;
        $this->_errMsg = NULL;
        $db->genQuery('SET NAMES latin1');  // Cambiar a latin1 para leer descripción de FreePBX
        $sql = <<<SQL_CUENTAS_NO_ASIGNADAS
SELECT ad.id AS extension, ad.id AS account, ad.tech, ad.description, NULL as registerip
FROM asterisk.devices ad
LEFT JOIN (endpoint_account ea) ON ea.account = ad.id
WHERE ea.account IS NULL ORDER BY extension
SQL_CUENTAS_NO_ASIGNADAS;
        $recordset = $db->fetchTable($sql, TRUE);
        $errMsg = $db->errMsg;
        $db->genQuery('SET NAMES utf8');    // Restaurar UTF-8
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read accounts: '.$errMsg;
            return NULL;
        }

        $cuentasRegistradas = $this->_recogerCuentasRegistradas();
        if (!is_null($cuentasRegistradas)) {
            for ($i = 0; $i < count($recordset); $i++) {
                if (isset($cuentasRegistradas[$recordset[$i]['tech']][$recordset[$i]['account']])) {
                    $recordset[$i]['registerip'] = $cuentasRegistradas[$recordset[$i]['tech']][$recordset[$i]['account']];
                }
            }
        }

        return $recordset;
    }

    private function _marcarSeleccion($selection)
    {
    	if (count($selection) <= 0) {
            $this->_errMsg = _tr('Empty selection');
    		return FALSE;
    	}
        if (is_null($db = $this->_getDB())) return FALSE;
        if (!$db->genQuery('UPDATE endpoint SET selected = 0')) {
            $this->_errMsg = '(internal) Failed to reset selection: '.$db->errMsg;
        	return FALSE;
        }
        foreach ($selection as $id_endpoint) {
        	$r = $db->genQuery(
                'UPDATE endpoint SET selected = 1 WHERE id = ? AND last_known_ipv4 IS NOT NULL',
                array($id_endpoint));
            if (!$r) {
                $this->_errMsg = '(internal) Failed to set selection: '.$db->errMsg;
                return FALSE;
        	}
        }
        return TRUE;
    }

    // TODO: combinar esta implementación con paloEndpointScanStatus::_recogerCuentasRegistradas()
    private function _recogerCuentasRegistradas()
    {
        if (!file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php"))
            return NULL;
        require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect('localhost', 'admin', obtenerClaveAMIAdmin()))
            return NULL;
        $cuentasRegistradas = array();

        $r = $astman->Command('sip show peers');
        if ($r['Response'] != 'Error') {
            foreach (explode("\n", $r['data']) as $s) {
                // 1064/1064    192.168.3.1  D   N  A  5060  OK (13 ms)
                $l = preg_split('/\s+/', $s);
                if (count($l) > 6 && preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $l[1])) {
                    $ip = $l[1];
                    $extArray = explode('/', $l[0]);
                    if (!isset($cuentasRegistradas[$ip]))
                        $cuentasRegistradas[$ip] = array('sip' => array(), 'iax2' => array());
                    $cuentasRegistradas['sip'][$extArray[0]] = $ip;
                }
            }
        }

        $r = $astman->Command('iax2 show peers');
        if ($r['Response'] != 'Error') {
            foreach (explode("\n", $r['data']) as $s) {
                // 2002   127.0.0.1  (D)  255.255.255.255  40001    OK (1 ms)
                $l = preg_split('/\s+/', $s);
                if (count($l) > 5 && preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $l[1]) && $l[5] == 'OK') {
                    $ip = $l[1];
                    if (!isset($cuentasRegistradas[$ip]))
                        $cuentasRegistradas[$ip] = array('sip' => array(), 'iax2' => array());
                    $cuentasRegistradas['iax2'][$l[0]] = $ip;
                }
            }
        }

        $astman->disconnect();
        return $cuentasRegistradas;
    }


    function olvidarSeleccionEndpoints($selection)
    {
    	if (!$this->_marcarSeleccion($selection)) return FALSE;
        if (is_null($db = $this->_getDB())) return FALSE;

        // Invocar el programa python para borrar los archivos de configuración
        $output = $ret = NULL;
        exec('/usr/bin/elastix-endpointconfig --clearconfig 2>&1', $output, $ret);
        if ($ret != 0) {
            $this->_errMsg = implode("\n", $output);
            return NULL;
        }

        foreach ($selection as $id_endpoint) {
            // Debido a DELETE CASCADE, esto borra todo lo asociado al endpoint
            if (!$db->genQuery('DELETE FROM endpoint WHERE id = ?', array($id_endpoint))) {
            $this->_errMsg = '(internal) Failed to delete endpoint information: '.$db->errMsg;
                return FALSE;
            }
        }
        return TRUE;
    }

    function iniciarConfiguracionEndpoints($selection)
    {
        if (!$this->_marcarSeleccion($selection)) return NULL;

        // Invocar el programa python para ejecutar la configuración de endpoints
        $logfile = tempnam('/tmp', 'endpointconfig-');
        $output = $retval = NULL;
        exec('/usr/bin/elastix-endpointconfig --applyconfig --progressfile '.escapeshellarg($logfile).' 2>&1',
            $output, $retval);
        if ($retval != 0) {
            $this->_errMsg = implode("\n", $output);
        	return NULL;
        }
        return $logfile;
    }

    function leerLogConfiguracion()
    {
    	if (is_null($db = $this->_getDB())) return NULL;

        $tupla = $db->getFirstRowQuery('SELECT lastlog FROM configlog LIMIT 1', TRUE);
        if (!is_array($tupla)) {
            $this->_errMsg = '(internal) Failed to read log: '.$db->errMsg;
            return FALSE;
        }
        return (count($tupla) > 0) ? $tupla['lastlog'] : '';
    }

    function ingresarEndpoints($module_name, &$listaEndpoints)
    {
        if (is_null($db = $this->_getDB())) return FALSE;
        $db->beginTransaction();
        $r = $this->_ingresarEndpoints($module_name, $db, $listaEndpoints);
        if (!is_null($r))
            $db->commit();
        else $db->rollBack();
        return $r;
    }

    private function _ingresarEndpoints($module_name, $db, &$listaEndpoints)
    {
        // Paso 1: verificación de que todos los fabricantes y modelos existen
        $sql = <<<SQL_DETAIL_DIALOG
SELECT manufacturer.id AS id_manufacturer, model.id AS id_model, 'standard' AS detail_dialog
FROM manufacturer, model
WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = ?
AND model.name = ?
SQL_DETAIL_DIALOG;
        foreach ($listaEndpoints as &$endpoint) {
            if (empty($endpoint['name_manufacturer'])) {
                $this->_errMsg = _tr('Manufacturer not specified for endpoint');
                if (isset($endpoint['source']))
                    $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
            	return NULL;
            }

            if (empty($endpoint['name_model'])) {
                $this->_errMsg = _tr('Model not specified for endpoint');
                if (isset($endpoint['source']))
                    $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                return NULL;
            }

            $tupla = $db->getFirstRowQuery($sql, TRUE,
                array($endpoint['name_manufacturer'], $endpoint['name_model']));
            if (!is_array($tupla)) {
                $this->_errMsg = '(internal) Failed to check model: '.$db->errMsg;
                return NULL;
            }
            if (count($tupla) <= 0) {
                $this->_errMsg = _tr('The following manufacturer/model combination is unsupported').
                    ": {$endpoint['name_manufacturer']} {$endpoint['name_model']}";
                if (isset($endpoint['source']))
                    $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                return NULL;
            }
            $endpoint['id_manufacturer'] = $tupla['id_manufacturer'];
            $endpoint['id_model'] = $tupla['id_model'];
            $endpoint['detail_dialog'] = $tupla['detail_dialog'];
        }

        // Paso 2: validación de consistencia interna de la lista
        $macs = array();
        $validador = new PaloValidar();
        foreach ($listaEndpoints as &$endpoint) {
            // MAC válida
            if (!preg_match('/^([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}/', $endpoint['mac_address'])) {
                $this->_errMsg = _tr('Invalid MAC').': '.$endpoint['mac_address'];
                if (isset($endpoint['source']))
                    $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                return NULL;
            }

            // Verificación de unicidad de MAC
            if (in_array($endpoint['mac_address'], $macs)) {
                $this->_errMsg = _tr('Duplicate MAC').': '.$endpoint['mac_address'];
                if (isset($endpoint['source']))
                    $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                return NULL;
            }
        }

        /* Paso 3: ingreso o modificación del registro principal a la tabla de
         * endpoints. En caso de modificación, se actualiza únicamente el modelo,
         * y un cambio de vendedor es un error. */
        $nuevosEndpoints = array();
        $sql = 'SELECT id, id_manufacturer, id_model FROM endpoint WHERE mac_address = ?';
        foreach ($listaEndpoints as &$endpoint) {
        	$tupla = $db->getFirstRowQuery($sql, TRUE, array($endpoint['mac_address']));
            if (!is_array($tupla)) {
                $this->_errMsg = '(internal) Failed to check endpoint: '.$db->errMsg;
                return NULL;
            }
            if (count($tupla) <= 0) {
                // Endpoint no existe, hay que insertar
                $r = $db->genQuery(
                    'INSERT INTO endpoint (id_manufacturer, id_model, mac_address, last_modified) '.
                    'VALUES (?, ?, ?, NOW())',
                    array($endpoint['id_manufacturer'], $endpoint['id_model'], $endpoint['mac_address']));
                if (!$r) {
                    $this->_errMsg = '(internal) Failed to insert endpoint: '.$db->errMsg;
                	return NULL;
                }
                $endpoint['id_endpoint'] = $db->getLastInsertId();
                $nuevosEndpoints[] = $endpoint['id_endpoint'];
            } else {
            	// Endpoint existe, hay que actualizar
                $endpoint['id_endpoint'] = $tupla['id'];
                if ($tupla['id_manufacturer'] != $endpoint['id_manufacturer']) {
                    $this->_errMsg = _tr('Manufacturer switching is not supported');
                    if (isset($endpoint['source']))
                        $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                	return NULL;
                }
                $r = $db->genQuery('UPDATE endpoint SET id_model = ?, last_modified = NOW() WHERE id = ?',
                    array($endpoint['id_model'], $tupla['id']));
                if (!$r) {
                    $this->_errMsg = '(internal) Failed to update model for endpoint: '.$db->errMsg;
                    return NULL;
                }

                // Borrar todas las cuentas asociadas al endpoint
                $r = $db->genQuery('DELETE FROM endpoint_account WHERE id_endpoint = ?',
                    array($tupla['id']));
                if (!$r) {
                    $this->_errMsg = '(internal) Failed to clear accounts for endpoint: '.$db->errMsg;
                    return NULL;
                }

                // Borrar todas las propiedades asociadas al endpoint
                $r = $db->genQuery('DELETE FROM endpoint_properties WHERE id_endpoint = ?',
                    array($tupla['id']));
                if (!$r) {
                    $this->_errMsg = '(internal) Failed to clear properties for endpoint: '.$db->errMsg;
                    return NULL;
                }
            }
        }

        /* Paso 4: verificación de que todas las cuentas referenciadas existen.
         * Si no se sabía previamente la tecnología, se carga aquí. */
        // SQL dependiente de FreePBX
        $sql = 'SELECT tech FROM asterisk.devices WHERE id = ?';
        foreach ($listaEndpoints as &$endpoint) {
            foreach ($endpoint['accounts'] as &$account) {
            	$tupla = $db->getFirstRowQuery($sql, TRUE, array($account['account']));
                if (!is_array($tupla)) {
                    $this->_errMsg = '(internal) Failed to check account: '.$db->errMsg;
                	return NULL;
                }
                if (count($tupla) <= 0) {
                    $this->_errMsg = _tr('Could not find account for endpoint').': '.$account['account'];
                    if (isset($endpoint['source']))
                        $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                    return NULL;
                }
                if (is_null($account['tech'])) {
                	$account['tech'] = $tupla['tech'];
                } elseif ($account['tech'] != $tupla['tech']) {
                    $this->_errMsg = _tr('Account tech mismatch for endpoint').': '.$account['account'];
                    if (isset($endpoint['source']))
                        $this->_errMsg .= ' '._tr('Source').': '.$endpoint['source'];
                	return NULL;
                }
            }
        }

        // Paso 5: combinación con los datos existentes en la base de datos
        $managers = array();
        foreach ($listaEndpoints as &$endpoint) {
            if (!isset($managers[$endpoint['detail_dialog']])) {
            	$classname = 'EndpointManager_'.ucfirst($endpoint['detail_dialog']);
                require_once "modules/$module_name/dialogs/{$endpoint['detail_dialog']}/{$classname}.class.php";
                $managers[$endpoint['detail_dialog']] = new $classname;
            }
            $manager = $managers[$endpoint['detail_dialog']];

            $r = $manager->guardarDetallesUpload($db, $endpoint['id_endpoint'], $endpoint);
            if (is_null($r)) {
                $this->_errMsg = _tr('Failed to save endpoint details').': '.$manager->getErrMsg();
            	return NULL;
            }
        }

        // Paso 6: resumen de los endpoints configurados
        $sqlEndpoints = <<<SQL_LEER_ENDPOINTS
SELECT endpoint.id AS id_endpoint, endpoint.id_manufacturer, IFNULL(endpoint.id_model, 'unknown') AS id_model,
    endpoint.mac_address, endpoint.last_known_ipv4, endpoint.last_scanned,
    endpoint.last_modified, endpoint.last_configured,
    manufacturer.name AS name_manufacturer,
    (SELECT COUNT(*) FROM endpoint_account WHERE endpoint_account.id_endpoint = endpoint.id) AS num_accounts,
    'standard' AS detail_dialog
FROM endpoint, manufacturer WHERE endpoint.id_manufacturer = manufacturer.id
ORDER BY endpoint.id
SQL_LEER_ENDPOINTS;
        $recordset = $db->fetchTable($sqlEndpoints, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoints: '.$db->errMsg;
            return;
        }
        $endpointChanges = array();
        foreach ($recordset as $tupla) {
        	if (in_array($tupla['id_endpoint'], $nuevosEndpoints)) {
        		$endpointChanges[] = array('insert', $tupla);
        	} else {
        		$endpointChanges[] = array('update', $tupla);
        	}
        }
        return $endpointChanges;
    }
}
?>