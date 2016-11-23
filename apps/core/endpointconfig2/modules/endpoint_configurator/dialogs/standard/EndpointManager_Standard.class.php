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
require_once 'libs/paloSantoValidar.class.php';

class EndpointManager_Standard
{
    private $_db = NULL;
    private $_errMsg = NULL;

    // Propiedades de sólo lectura y de libre modificación
    private $_ro_properties = array('max_sip_accounts', 'max_iax2_accounts');
    private $_rw_properties = array('http_username', 'http_password', 'telnet_username',
            'telnet_password', 'ssh_username', 'ssh_password', 'dhcp', 'static_ip',
            'static_gw', 'static_mask', 'static_dns1', 'static_dns2');

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

    // Cargar los detalles del endpoint a configurar
    function cargarDetalles($id_endpoint)
    {
    	if (is_null($db = $this->_getDB())) return NULL;
        $detalles = array(
            'endpoint_property_overrides' => array(),
        );

        // Cargar propiedades asociadas al modelo
        $sql = <<<SQL_MODEL_PROPERTIES
SELECT property_key, property_value FROM model_properties, model, endpoint
WHERE model_properties.id_model = model.id AND model.id = endpoint.id_model
    AND endpoint.id = ?
ORDER BY property_key
SQL_MODEL_PROPERTIES;
        $recordset = $db->fetchTable($sql, TRUE, array($id_endpoint));
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read model properties: '.$db->errMsg;
            return NULL;
        }
        $detalles['endpoint_properties'] = array();
        foreach ($recordset as $row) {
            $detalles['endpoint_properties'][$row['property_key']] = $row['property_value'];
        }

        // Cargar propiedades asociadas al endpoint en particular
        $sql = 'SELECT property_key, property_value FROM endpoint_properties WHERE id_endpoint = ?';
        $recordset = $db->fetchTable($sql, TRUE, array($id_endpoint));
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoint properties: '.$db->errMsg;
            return NULL;
        }
        foreach ($recordset as $row) {
            if (!isset($detalles['endpoint_properties'][$row['property_key']]) || ($detalles['endpoint_properties'][$row['property_key']] != $row['property_key']))
                $detalles['endpoint_property_overrides'][] = $row['property_key'];
            $detalles['endpoint_properties'][$row['property_key']] = $row['property_value'];
        }

        // Conversión de tipos para propiedades conocidas
        foreach ($this->_ro_properties as $k)
            if (isset($detalles['endpoint_properties'][$k]))
                $detalles['endpoint_properties'][$k] = (int)$detalles['endpoint_properties'][$k];
        foreach (array('dhcp') as $k)
            if (isset($detalles['endpoint_properties'][$k]))
                $detalles['endpoint_properties'][$k] = (bool)$detalles['endpoint_properties'][$k];

        // Todas las propiedades estándar se transportan al primer nivel de detalle
        foreach (array_merge($this->_ro_properties, $this->_rw_properties) as $k) {
            $value = NULL;
            if (in_array($k, array('dhcp'))) $value = TRUE;
            if (isset($detalles['endpoint_properties'][$k])) {
            	$value = $detalles['endpoint_properties'][$k];
                unset($detalles['endpoint_properties'][$k]);
            }
            $detalles[$k] = $value;
        }

        // Convertir en tuplas de clave y valor para facilitar operación javascript
        $a = array();
        foreach ($detalles['endpoint_properties'] as $k => $v)
            $a[] = array('key' => $k, 'value' => $v);
        $detalles['endpoint_properties'] = $a;

        // Cargar cuentas asociadas al endpoint, de existir
        $db->genQuery('SET NAMES latin1');
        $sql = <<<SQL_ENDPOINT_ACCOUNTS
SELECT ea.id AS id_account, ea.tech, ea.account, ad.id AS extension, ea.priority, ad.description
FROM endpoint_account ea, asterisk.devices ad
WHERE ea.id_endpoint = ? AND ea.account = ad.id
ORDER BY priority
SQL_ENDPOINT_ACCOUNTS;
        $recordset = $db->fetchTable($sql, TRUE, array($id_endpoint));
        $errMsg = $db->errMsg;
        $db->genQuery('SET NAMES UTF-8');
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoint accounts: '.$errMsg;
            return NULL;
        }
        $detalles['endpoint_account'] = $recordset;

        // Cargar propiedades asociadas a cada cuenta, de existir
        foreach (array_keys($detalles['endpoint_account']) as $i) {
            $sql =  'SELECT property_key, property_value FROM endpoint_account_properties '.
                    'WHERE id_endpoint_account = ?';
            $recordset = $db->fetchTable($sql, TRUE, array($detalles['endpoint_account'][$i]['id_account']));
            if (!is_array($recordset)) {
                $this->_errMsg = '(internal) Failed to read endpoint properties: '.$db->errMsg;
                return NULL;
            }
            $detalles['endpoint_account'][$i]['properties'] = array();
            foreach ($recordset as $row) {
                $detalles['endpoint_account'][$i]['properties'][] = array(
                    'key' => $row['property_key'],
                    'value' => $row['property_value']
                );
            }
        }

        return $detalles;
    }

    function guardarDetalles($id_endpoint, &$detalles)
    {
        if (is_null($db = $this->_getDB())) return NULL;
        $db->beginTransaction();

        $r = $this->_guardarDetalles($db, $id_endpoint, $detalles);

        if (!is_null($r))
            $db->commit();
        else $db->rollBack();

        return $r;
    }

    function guardarDetallesUpload($db, $id_endpoint, $endpoint)
    {
    	$detalles = array();
        foreach ($this->_rw_properties as $k) {
            if (isset($endpoint['properties'][$k])) {
        	   $detalles[$k] = $endpoint['properties'][$k];
               unset($endpoint['properties'][$k]);
            }
        }
        $detalles['endpoint_properties'] = array();
        foreach ($endpoint['properties'] as $k => $v) {
        	$detalles['endpoint_properties'][] = array('key' => $k, 'value' => $v);
        }
        $detalles['endpoint_account'] = array();
        foreach ($endpoint['accounts'] as $account) {
            $p = array();
            foreach ($account['properties'] as $k => $v) {
                $p[] = array('key' => $k, 'value' => $v);
            }
            $account['properties'] = $p;
            $detalles['endpoint_account'][] = $account;
        }

        return $this->_guardarDetalles($db, $id_endpoint, $detalles);
    }

    private function _guardarDetalles($db, $id_endpoint, &$detalles)
    {
        $bExito = FALSE;

        $detallesGuardar = array();
        foreach ($this->_rw_properties as $k) {
            $detallesGuardar[$k] = NULL;
            if (isset($detalles[$k]) && (!empty($detalles[$k]) || $detalles[$k] === '0')) {
                $detallesGuardar[$k] = $detalles[$k];
            }
        }

        // Verificaciones que no requieren acceso a DB
        $validador = new PaloValidar();
        if (!isset($detallesGuardar['dhcp'])) $detallesGuardar['dhcp'] = 'true';
        $detallesGuardar['dhcp'] = in_array($detallesGuardar['dhcp'], array('false', '0')) ? 0 : 1;
        if ($detallesGuardar['dhcp']) {
            foreach (array('static_ip', 'static_gw', 'static_mask', 'static_dns1', 'static_dns2') as $k)
                $detallesGuardar[$k] = NULL;
        } else {
            if (is_null($detallesGuardar['static_dns1']) && !is_null($detallesGuardar['static_dns2'])) {
                $detallesGuardar['static_dns1'] = $detallesGuardar['static_dns2'];
                $detallesGuardar['static_dns2'] = NULL;
            }
            foreach (array('static_ip', 'static_mask', 'static_dns1') as $k) {
                if (!isset($detallesGuardar[$k])) {
                    $this->_errMsg = _tr('Static configuration requires the following network parameter').
                        ': '.$k;
                    return NULL;
                }
            }
            foreach (array('static_ip', 'static_gw', 'static_mask', 'static_dns1', 'static_dns2') as $k) {
                if (!is_null($detallesGuardar[$k]) && !$validador->validar($k, $detallesGuardar[$k], 'ip')) {
                    $this->_errMsg = _tr('The following network parameter is not a valid IPv4 address').
                        ': '.$k;
                    return NULL;
                }
            }
        }

        // Verificar colisiones de propiedades personalizadas
        if (!isset($detalles['endpoint_properties']))
            $detalles['endpoint_properties'] = array();
        foreach ($detalles['endpoint_properties'] as $tupla) {
            if (in_array($tupla['key'], $this->_ro_properties)) {
                $this->_errMsg = _tr('Custom endpoint property collides with the following read-only property').
                    ': '.$tupla['key'];
                return NULL;
            }
            if (in_array($tupla['key'], $this->_rw_properties)) {
                $this->_errMsg = _tr('Custom endpoint property collides with the following read-write property').
                    ': '.$tupla['key'];
                return NULL;
            }
        }
        foreach ($detalles['endpoint_properties'] as $tupla) {
            if (trim($tupla['value']) != '') $detallesGuardar[$tupla['key']] = $tupla['value'];
        }

        // Verificar cuentas telefónicas asignadas. Es válido (por ahora) no tener cuentas.
        if (!isset($detalles['endpoint_account'])) $detalles['endpoint_account'] = array();
        $cuentaPedida = array();
        $countByTech = array();
        for ($i = 0; $i < count($detalles['endpoint_account']); $i++) {
            if (!isset($detalles['endpoint_account'][$i]['properties']))
                $detalles['endpoint_account'][$i]['properties'] = array();

            // Todos los parámetros presentes
            foreach (array('tech', 'account', 'priority') as $k) {
                if (!isset($detalles['endpoint_account'][$i][$k])) {
                    $this->_errMsg = _tr('Malformed request').' - '.
                        _tr('Account is missing required property').': '.$k;
                    return NULL;
                }
                for ($j = 0; $j < count($detalles['endpoint_account'][$i]['properties']); $j++) {
                    foreach (array('key', 'value') as $k2) {
                        if (!isset($detalles['endpoint_account'][$i]['properties'][$j][$k2])) {
                            $this->_errMsg = _tr('Malformed request').' - '.
                                _tr('Account is missing required property').': '.$k2;
                            return NULL;
                        }
                    }
                }
            }

            // Tecnología válida
            $tech = $detalles['endpoint_account'][$i]['tech'];
            if (!in_array($tech, array('sip', 'iax2'))) {
                $this->_errMsg = _tr('Unsupported tech').': '.$detalles['endpoint_account'][$i]['tech'];
                return NULL;
            }

            if (!isset($countByTech[$tech])) $countByTech[$tech] = 0;
            $countByTech[$tech]++;

            // Verificar duplicados dentro del endpoint
            $k = $detalles['endpoint_account'][$i]['tech'].'/'.$detalles['endpoint_account'][$i]['account'];
            if (in_array($k, $cuentaPedida)) {
                $this->_errMsg = _tr('Duplicate account').': '.$k;
                return NULL;
            }
            $cuentaPedida[] = $k;
        }
        $endpoint_account = $detalles['endpoint_account'];
        unset($cuentaPedida);

        // Ordenar y reasignar prioridades
        if (!function_exists('_guardarDetalles_cmp_account')) {
            function _guardarDetalles_cmp_account($a, $b){
                if ($a['priority'] != $b['priority']) return $a['priority'] - $b['priority'];
                if ($a['tech'] != $b['tech']) return strcmp($a['tech'], $b['tech']);
                return strcmp($a['account'], $b['account']);
            }
            usort($endpoint_account, '_guardarDetalles_cmp_account');
            for ($i = 0; $i < count($endpoint_account); $i++)
                $endpoint_account[$i]['priority'] = $i + 1;
        }

        $bExito = TRUE;

        /* Verificar si el endpoint tiene un modelo de teléfono asignado. Como
         * caso especial, si el endpoint no tiene modelo, es posible que la
         * detección de tal modelo esté bloqueada debido a credenciales. Por lo
         * tanto, se permitirá guardar únicamente credenciales si no se detectó
         * el modelo.
         */
        $bHayModelo = FALSE;
        if ($bExito) {
            $tupla = $db->getFirstRowQuery('SELECT id_model FROM endpoint WHERE id = ?',
                TRUE, array($id_endpoint));
            if (!is_array($tupla)) {
                $this->_errMsg = _tr('(internal) Failed to check model').' - '.$db->errMsg;
                $bExito = FALSE;
            } elseif (count($tupla) <= 0) {
                $this->_errMsg = _tr('Endpoint not found');
                $bExito = FALSE;
            } elseif (is_null($tupla['id_model'])) {
                $propSinModelo = array('http_username', 'http_password',
                    'telnet_username', 'telnet_password', 'ssh_username',
                    'ssh_password');
                foreach (array_keys($detallesGuardar) as $k) {
                    if (!in_array($k, $propSinModelo)) unset($detallesGuardar[$k]);
                }
            } else {
                $bHayModelo = TRUE;
            }
        }

        /* Verificar el total de cuentas por modelo. Ya que están en la misma
         * tabla, se verifica además las banderas de IP estática y dinámica. */
        if ($bExito && $bHayModelo) {
            $tupla = $db->getFirstRowQuery(
                'SELECT max_accounts, static_ip_supported, dynamic_ip_supported '.
                'FROM endpoint, model '.
                'WHERE endpoint.id = ? AND endpoint.id_model = model.id',
                TRUE, array($id_endpoint));
            if (!is_array($tupla)) {
                $this->_errMsg = _tr('(internal) Failed to check account limit').' - '.$db->errMsg;
                $bExito = FALSE;
            } elseif (count($tupla) <= 0) {
                $this->_errMsg = _tr('Endpoint not found');
                $bExito = FALSE;
            } elseif (count($endpoint_account) > $tupla['max_accounts']) {
                $this->_errMsg = _tr('Maximum number of accounts exceeded');
                $bExito = FALSE;
            } elseif (!((int)$tupla['static_ip_supported']) && !$detallesGuardar['dhcp']) {
                $this->_errMsg = _tr('Endpoint does not support static IP address');
                $bExito = FALSE;
            } elseif (!((int)$tupla['dynamic_ip_supported']) && $detallesGuardar['dhcp']) {
                $this->_errMsg = _tr('Endpoint does not support dynamic IP address');
                $bExito = FALSE;
            }
        }

        // Cargar las propiedades por omisión del modelo
        $modelProperties = array();
        if ($bExito && $bHayModelo) {
            $sql = <<<MODEL_PROPERTIES
SELECT model_properties.property_key, model_properties.property_value
FROM endpoint, model, model_properties
WHERE endpoint.id = ? AND endpoint.id_model = model.id AND model.id = model_properties.id_model
MODEL_PROPERTIES;
            $recordset = $db->fetchTable($sql, TRUE, array($id_endpoint));
            if (!is_array($recordset)) {
                $this->_errMsg = _tr('(internal) Failed to load model properties').' - '.$db->errMsg;
                $bExito = FALSE;
            }
            foreach ($recordset as $tupla) {
                $modelProperties[$tupla['property_key']] = $tupla['property_value'];
            }
        }

        /* Verificar el total de cuentas por tecnología soportada. Si no se ha
         * definido max_TECH_accounts para el modelo, se asume 0 */
        if ($bExito && $bHayModelo) {
            foreach ($countByTech as $tech => $count) {
                $maxtech = isset($modelProperties["max_{$tech}_accounts"])
                    ? (int)$modelProperties["max_{$tech}_accounts"]
                    : 0;
                if ($count > $maxtech) {
                    $this->_errMsg = _tr('Maximum number of accounts exceeded for tech').': '.$tech;
                    $bExito = FALSE;
                }
            }
        }

        if ($bExito) {
            /* Toda propiedad a guardar que exista con el mismo valor en la
             * lista por omisión con el mismo valor, se quita de la lista de
             * propiedades a guardar. */
            foreach (array_keys($detallesGuardar) as $k) {
                $v = $detallesGuardar[$k];
                if (isset($modelProperties[$k]) && $modelProperties[$k] == $v)
                    unset($detallesGuardar[$k]);
            }

            // Ejecutar el guardado de las propiedades
            if (!$db->genQuery('DELETE FROM endpoint_properties WHERE id_endpoint = ?', array($id_endpoint))) {
                $this->_errMsg = _tr('(internal) Failed to remove old properties').' - '.$db->errMsg;
                $bExito = FALSE;
            } else {
                foreach ($detallesGuardar as $k => $v) if (!is_null($v)) {
                    if (!$db->genQuery(
                        'INSERT INTO endpoint_properties (id_endpoint, property_key, property_value) '.
                        'VALUES (?, ?, ?)', array($id_endpoint, $k, $v))) {
                        $this->_errMsg = _tr('(internal) Failed to insert new property').' - '.$db->errMsg;
                        $bExito = FALSE;
                        break;
                    }
                }
            }
        }

        // Verificar que cuenta asignada a endpoint no esté ya tomada por otro endpoint
        if ($bExito && $bHayModelo) {
            // SQL dependiente de FreePBX
            $sql = <<<ENDPOINT_ACCOUNTS
SELECT ad.tech, ad.id AS account, ea.id_endpoint
FROM asterisk.devices ad
LEFT JOIN (endpoint_account ea) ON ea.account = ad.id
ENDPOINT_ACCOUNTS;
            $recordset = $db->fetchTable($sql, TRUE);
            if (!is_array($recordset)) {
                $this->_errMsg = _tr('(internal) Failed to read available accounts').' - '.$db->errMsg;
                $bExito = FALSE;
            } else {
                $availableAccounts = array();
                foreach ($recordset as $tupla) {
                    $availableAccounts[$tupla['tech']][$tupla['account']] = $tupla['id_endpoint'];
                }

                foreach ($endpoint_account as $account) {
                    if (!in_array($account['account'], array_keys($availableAccounts[$account['tech']]))) {
                        // La cuenta requerida no existe
                        $this->_errMsg = _tr('Account not found').': '.$account['tech'].'/'.$account['account'];
                        $bExito = FALSE;
                    } elseif (!is_null($availableAccounts[$account['tech']][$account['account']])
                        && $availableAccounts[$account['tech']][$account['account']] != $id_endpoint) {
                        // La cuenta requerida fue tomada por otro endpoint
                        $this->_errMsg = _tr('Account claimed by another endpoint').': '.$account['tech'].'/'.$account['account'];
                        $bExito = FALSE;
                    }
                }
            }
        }

        // Guardar los datos de las cuentas
        if ($bExito) {
            // Por DELETE CASCADE, también se borran las propiedades de la cuenta
            $r = $db->genQuery(
                'DELETE FROM endpoint_account WHERE id_endpoint = ?',
                array($id_endpoint));
            if (!$r) {
                $this->_errMsg = _tr('(internal) Failed to remove old account associations').' - '.$db->errMsg;
                $bExito = FALSE;
            } else {
                if ($bHayModelo) foreach ($endpoint_account as $account) {
                    $r = $db->genQuery(
                        'INSERT INTO endpoint_account (id_endpoint, tech, account, priority) '.
                        'VALUES (?, ?, ?, ?)',
                        array($id_endpoint, $account['tech'], $account['account'], $account['priority']));
                    if (!$r) {
                        $this->_errMsg = _tr('(internal) Failed to insert account association').' - '.$db->errMsg;
                        $bExito = FALSE;
                    } else {
                        $id_endpoint_account = $db->getLastInsertId();
                        foreach ($account['properties'] as $tupla) if (!is_null($tupla['value'])) {
                            $r = $db->genQuery(
                                'INSERT INTO endpoint_account_properties (id_endpoint_account, property_key, property_value) '.
                                'VALUES (?, ?, ?)',
                                array($id_endpoint_account, $tupla['key'], $tupla['value']));
                            if (!$r) {
                                $this->_errMsg = _tr('(internal) Failed to insert account property').' - '.$db->errMsg;
                                $bExito = FALSE;
                                break;
                            }
                        }
                    }

                    if (!$bExito) break;
                }
            }
        }

        $sFechaModificacion = NULL;
        if ($bExito) {
            // Actualizar la fecha de última modificación
            $r = $db->genQuery('UPDATE endpoint SET last_modified = NOW() WHERE id = ?', array($id_endpoint));
            if (!$r) {
                $this->_errMsg = _tr('(internal) Failed to update modification time').' - '.$db->errMsg;
                $bExito = FALSE;
            } else {
                $tupla = $db->getFirstRowQuery('SELECT last_modified FROM endpoint WHERE id = ?', TRUE, array($id_endpoint));
                $sFechaModificacion = $tupla['last_modified'];
            }
        }

        return $bExito ? $sFechaModificacion : NULL;
    }

    // TODO: implementar reseteo a defaults del teléfono en la base de datos
    // como DELETE FROM endpoint_properties/endpoint_account_properties WHERE id_endpoint = ...
}
?>
