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

/*
 * La clase definida a continuación implementa el monitoreo del estado de 
 * escaneo de la red por endpoints configurables. El estado consiste en:
 *  endpoints   =>  lista completa de los endpoints ya existentes en la base, 
 *                  tal como son devueltos por paloSantoEndpoints::leerEndpoints()
 *                  [id_endpoint, id_manufacturer, id_model, mac_address, 
 *                  last_known_ipv4, last_scanned, last_modified, last_configured, 
 *                  name_manufacturer, num_accounts ]
 *  scanSocket  =>  ruta completa al socket Unix de control del escaneo, si el
 *                  escaneo está activo, o NULL si inactivo o terminado.
 */
class paloEndpointScanStatus extends paloInterfaceSSE
{
    private $_scanSockPath = NULL;
    private $_scanSock = NULL;
    private $_db = NULL;
    private $_listaRegistros = array();

    // Constructor - abrir conexión a base de datos    
    function paloEndpointScanStatus()
    {
        $dsn = generarDSNSistema('asteriskuser', 'endpointconfig');
        $this->_db = new paloDB($dsn);
        if ($this->_db->errMsg != '') {
            $this->_errMsg = $this->_db->errMsg;
            $this->_db = NULL;            
        } else {
            $this->_db->genQuery('SET NAMES utf8');
        }
    }
    
    function createEmptyResponse() { return array('endpointchanges' => array()); }
    function isEmptyResponse($jsonResponse) { return (count($jsonResponse['endpointchanges']) <= 0); }
    
    function findInitialStateDifferences(&$currentClientState, &$jsonResponse)
    {
        // Se asume que la verificación inicial tiene un archivo de socket
        $errno = $errstr = NULL;
        $this->_scanSockPath = $currentClientState['scanSocket'];
        $this->_scanSock = @fsockopen('unix://'.$this->_scanSockPath, -1, $errno, $errstr);
        
        if (FALSE === $this->_scanSock) {
            /* La causa más probable de fallo en abrir es que el escaneo ya
             * terminó. Se verifica contra la base de datos por si hay 
             * diferencias, las cuales se anotan en $jsonResponse. */
        	$this->_scanSock = NULL;
            $currentClientState['scanSocket'] = $this->_scanSockPath = NULL;
            $this->_buscarCambioGlobalEndpoints($currentClientState['endpoints'], $jsonResponse['endpointchanges']);
            $currentClientState['endpoints'] = NULL;
            $jsonResponse['endpointchanges'][] = array('quit', NULL);
            return FALSE; // No hay razón para seguir esperando eventos
        } else {
            stream_set_blocking($this->_scanSock, 0);
            
            while ($s = fgets($this->_scanSock)) {
            	/* La línea leída puede ser 'quit' si el escaneo termina, o una
                 * tupla (insert|update|delete id_endpoint) */
                $s = trim($s);
                if ($s == 'quit') {
                    fclose($this->_scanSock);
                    $this->_scanSock = NULL;
                    $currentClientState['scanSocket'] = $this->_scanSockPath = NULL;
                    $currentClientState['endpoints'] = NULL;
                    $jsonResponse['endpointchanges'][] = array('quit', NULL);
                    return FALSE;
                }
                $regs = NULL;
                preg_match('/^(\w+) (\d+)/', $s, $regs);
                $this->_procesarRegistroCambio($regs[1], $regs[2], $currentClientState['endpoints'], $jsonResponse['endpointchanges']);
            }
            
            return TRUE;
        }
    }
    
    function waitForEvents()
    {
        if (is_null($this->_scanSock)) return TRUE;
        $read = array($this->_scanSock); $write = $except = NULL;
        $r = stream_select($read, $write, $except, 1);
        if ($r != 0 || count($read) > 0) {
            while ($s = fgets($this->_scanSock)) {
                /* La línea leída puede ser 'quit' si el escaneo termina, o una
                 * tupla (insert|update|delete id_endpoint) */
                $s = trim($s);
                if ($s == 'quit') {
                	// Se ha finalizado el escaneo
                    fclose($this->_scanSock);
                    $this->_scanSock = NULL;
                    // TODO: buscar cambios globales otra vez.
                    break;
                } else {
                    $regs = NULL;
                    preg_match('/^(\w+) (\d+)/', $s, $regs);
                    $this->_listaRegistros[] = array($regs[1], $regs[2]);
                }
            }
        }

    	return TRUE;
    }
    
    function findEventStateDifferences(&$currentClientState, &$jsonResponse)
    {
    	while ($tupla = array_shift($this->_listaRegistros)) {
    		$this->_procesarRegistroCambio($tupla[0], $tupla[1],
                $currentClientState['endpoints'],
                $jsonResponse['endpointchanges']);
    	}
        if (is_null($this->_scanSock)) {
            $currentClientState['scanSocket'] = $this->_scanSockPath = NULL;
            $this->_buscarCambioGlobalEndpoints($currentClientState['endpoints'], $jsonResponse['endpointchanges']);
            $currentClientState['endpoints'] = NULL;
            $jsonResponse['endpointchanges'][] = array('quit', NULL);
        	return FALSE;
        } else return TRUE;
    }
    
    // Parsear indicación de cambio en la base de datos
    private function _procesarRegistroCambio($sCambio, $idEndpoint, &$currentEndpoints, &$endpointChanges)
    {
        switch ($sCambio) {
        case 'insert':
        case 'update':
            // TODO: idear manera de obtener diálogo a usar 
            $sqlEndpoint = <<<SQL_LEER_ENDPOINT
SELECT endpoint.id AS id_endpoint, endpoint.id_manufacturer, IFNULL(endpoint.id_model, 'unknown') AS id_model,
    endpoint.mac_address, endpoint.last_known_ipv4, endpoint.last_scanned,
    endpoint.last_modified, endpoint.last_configured,
    manufacturer.name AS name_manufacturer,
    (SELECT COUNT(*) FROM endpoint_account WHERE endpoint_account.id_endpoint = endpoint.id) AS num_accounts,
    'standard' AS detail_dialog
FROM endpoint, manufacturer
WHERE endpoint.id_manufacturer = manufacturer.id AND endpoint.id = ?
SQL_LEER_ENDPOINT;
            $tupla = $this->_db->getFirstRowQuery($sqlEndpoint, TRUE, array($idEndpoint));
            if (!is_array($tupla)) return;
            $iPos = NULL;
            foreach (array_keys($currentEndpoints) as $k) {
            	if ($currentEndpoints[$k]['id_endpoint'] == $idEndpoint) {
            		$iPos = $k;
                    break;
            	}                
            }
            
            if (is_null($iPos)) {
            	$currentEndpoints[] = $tupla;
                $endpointChanges[] = array('insert', $tupla);
            } elseif ($currentEndpoints[$iPos] != $tupla) {
            	$currentEndpoints[$iPos] = $tupla;
                $endpointChanges[] = array('update', $tupla);
            }
            break;
        case 'delete':
            $iPos = NULL;
            foreach (array_keys($currentEndpoints) as $k) {
                if ($currentEndpoints[$k]['id_endpoint'] == $idEndpoint) {
                    $iPos = $k;
                    break;
                }                
            }
            if (!is_null($iPos)) {
            	unset($currentEndpoints[$iPos]);
                $endpointChanges[] = array('delete', array('id_endpoint' => $idEndpoint));
            }
            break;
        }
    }
    
    private function _buscarCambioGlobalEndpoints(&$currentEndpoints, &$endpointChanges)
    {
        if (!is_array($currentEndpoints)) {
            $this->_errMsg = '(internal) global endpoint scan called without endpoint array';
        	return;
        }

        $this->_revisarCuentasRegistradasAsterisk();

        // Leer estado completo de la base de datos e indexar por id_endpoint
        // TODO: idear manera de obtener diálogo a usar 
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
        $recordset = $this->_db->fetchTable($sqlEndpoints, TRUE);
        if (!is_array($recordset)) {
            $this->_errMsg = '(internal) Failed to read endpoints: '.$this->_db->errMsg;
            return;
        }
        $listaEndpoints = array();
        foreach ($recordset as $tupla) {
        	$listaEndpoints[$tupla['id_endpoint']] = $tupla;
        }
        $recordset = NULL;
        
        // Revisar cada tupla actual, quitando una vez revisada de la lista nueva
        foreach (array_keys($currentEndpoints) as $k) {
            if (isset($listaEndpoints[$currentEndpoints[$k]['id_endpoint']])) {
                $nuevaTupla = $listaEndpoints[$currentEndpoints[$k]['id_endpoint']];
                unset($listaEndpoints[$currentEndpoints[$k]['id_endpoint']]);
        		if ($nuevaTupla != $currentEndpoints[$k]) {
                    $currentEndpoints[$k] = $nuevaTupla;
        			$endpointChanges[] = array('update', $nuevaTupla);
        		}
        	} else {
        		// Endpoint ha desaparecido
                $endpointChanges[] = array('delete', array('id_endpoint' => $currentEndpoints[$k]['id_endpoint']));
        	}
        }
        
        // Todas las tuplas nuevas que queden deben insertarse
        foreach ($listaEndpoints as $nuevaTupla) {
        	$currentEndpoints[] = $nuevaTupla;
            $endpointChanges[] = array('insert', $nuevaTupla);
        }
    }
    
    /* Método para ingresar las cuentas ya registradas en asterisk como cuentas 
     * registradas como parte del endpoint en la base de datos.
     * 
     * Luego de obtener la lista de cuentas SIP e IAX registradas, se verifica:
     * - Si la IP corresponde a un endpoint en la DB, se leen sus cuentas.
     * - Si la cuenta leída está registrada en la IP, se marca para preservar
     * - Si la cuenta leída NO está registrada en la IP, se marca para /posible/ eliminar
     * - Si la cuenta registrada no está entre las leídas, se debe de agregar
     * - Para cada cuenta que debe de agregarse, se verifica si hay otro endpoint
     *   que la reclama. Si es así, se quita de ese endpoint.
     * - Las cuentas para preservar se deben de modificar su prioridad para ser
     *   las primeras que aparecen. Luego deben constar las cuentas agregables,
     *   y luego las cuentas borrables, en orden de prioridad.
     * - Se recorta la lista para respetar el número máximo de cuentas por 
     *   tecnología y global. Las cuentas se quitan desde el final de la lista.
     *   Por lo tanto, primero serán quitadas las borrables, luego las agregables,
     *   y por último las preservables (que no debería pasar).
     */
    private function _revisarCuentasRegistradasAsterisk()
    {
        $cuentasRegistradas = $this->_recogerCuentasRegistradas();
        if (!is_array($cuentasRegistradas)) return;        
        
        $this->_db->beginTransaction();
        foreach ($cuentasRegistradas as $ip => $cuentas) {
            $tupla = $this->_db->getFirstRowQuery(
                'SELECT endpoint.id AS endpoint_id, model.id AS model_id, model.max_accounts '.
                'FROM (endpoint, model) '.
                'WHERE endpoint.id_model = model.id AND endpoint.last_known_ipv4 = ?',
                TRUE, array($ip));
            if (!is_array($tupla)) {
                $this->_db->rollBack();
            	return;
            }
            
            $maxaccounts = array(
                'global'    =>  0,
                'sip'       =>  0,
                'iax2'      =>  0,
            );
            
            // IP no es de un endpoint detectado, o su modelo no fue detectado
            if (count($tupla) <= 0) continue;
            $maxaccounts['global'] = $tupla['max_accounts'];
            $endpoint_id = $tupla['endpoint_id'];
            $model_id = $tupla['model_id'];
            
            $recordset = $this->_db->fetchTable(
                'SELECT property_key, property_value FROM model_properties '.
                'WHERE property_key in ("max_sip_accounts", "max_iax2_accounts") '.
                    'AND id_model = ?',
                TRUE, array($model_id));
            if (!is_array($recordset)) {
                $this->_db->rollBack();
                return;
            }
            
            foreach ($recordset as $tupla) {
            	if ($tupla['property_key'] == 'max_sip_accounts') $maxaccounts['sip'] = $tupla['property_value'];
                if ($tupla['property_key'] == 'max_iax2_accounts') $maxaccounts['iax2'] = $tupla['property_value'];
            }
            
            // Leer las cuentas existentes para este endpoint
            $recordset = $this->_db->fetchTable(
                'SELECT id, tech, account, priority FROM endpoint_account '.
                'WHERE id_endpoint = ? ORDER BY priority',
                TRUE, array($endpoint_id));
            if (!is_array($recordset)) {
                $this->_db->rollBack();
                return;
            }
            $cuentaPreservar = array();
            $cuentaAgregar = array();
            $cuentaBorrable = array();
            foreach ($recordset as $tupla) {
            	$tupla['mod'] = FALSE;
                $i = array_search($tupla['account'], $cuentas[$tupla['tech']]);
                if ($i !== FALSE) {
                    $cuentaPreservar[] = $tupla;
                    unset($cuentas[$tupla['tech']][$i]);
                } else {
                	$cuentaBorrable[] = $tupla;
                }
            }
            foreach ($cuentas as $tech => $accts) {
            	foreach ($accts as $acct) {
            		// Leer endpoints que reclamen esta cuenta
                    $recordset = $this->_db->fetchTable(
                        'SELECT endpoint.id AS endpoint_id, endpoint_account.id AS endpoint_account_id '.
                        'FROM endpoint, endpoint_account '.
                        'WHERE endpoint.id = endpoint_account.id_endpoint '.
                            'AND (endpoint.last_known_ipv4 IS NULL OR endpoint.last_known_ipv4 <> ?) '.
                            'AND endpoint_account.tech = ? '.
                            'AND endpoint_account.account = ?',
                        TRUE, array($ip, $tech, $acct));
                    if (!is_array($recordset)) {
                        $this->_db->rollBack();
                        return;
                    }
                    foreach ($recordset as $tupla) {
                    	$r = $this->_db->genQuery(
                            'DELETE FROM endpoint_account WHERE id = ?',
                            array($tupla['endpoint_account_id']));
                        if (!$r) {
                            $this->_db->rollBack();
                            return;
                        }
                        $r = $this->_db->genQuery(
                            'UPDATE endpoint SET last_modified = NOW() WHERE id = ?',
                            array($tupla['endpoint_id']));
                        if (!$r) {
                            $this->_db->rollBack();
                            return;
                        }
                    }
                    
                    $cuentaAgregar[] = array(
                        'id'        =>  NULL,
                        'tech'      =>  $tech,
                        'account'   =>  $acct,
                        'priority'  =>  NULL,
                        'mod'       =>  TRUE,
                    );
            	}
            }
            
            // Si no hay cambios, se pasa a la siguiente IP
            $cuentasGuardar = array_merge($cuentaPreservar, $cuentaAgregar, $cuentaBorrable);
            $mods = FALSE;
            for ($i = 0; $i < count($cuentasGuardar); $i++) {
            	if ($cuentasGuardar[$i]['mod']) $mods = TRUE;
            }
            if (!$mods) continue;
            
            $numaccounts = array(
                'global'    =>  count($cuentasGuardar),
                'sip'       =>  0,
                'iax2'      =>  0,
            );

            // Reordenar las prioridades de las cuentas
            for ($i = 0; $i < count($cuentasGuardar); $i++) {
                $prio = $i + 1;
                if (is_null($cuentasGuardar[$i]['priority']) || 
                    $cuentasGuardar[$i]['priority'] != $prio) {
                	$cuentasGuardar[$i]['priority'] = $prio;
                    $cuentasGuardar[$i]['mod'] = TRUE;
                }
                
                $numaccounts[$cuentasGuardar[$i]['tech']]++;
            }
            
            // Quitar cuentas de la lista y de la DB hasta cumplir con máximos
            if (!function_exists('_test_maxaccount_overflow')) {
            	function _test_maxaccount_overflow(&$cur, &$max)
                {
                	foreach ($cur as $k => $v) if ($k != 'global') {
                		 if ($v > $max[$k]) return $k;
                	}
                    return ($cur['global'] > $max['global']) ? 'global' : NULL;
                }
            }
            while (!is_null($overflow = _test_maxaccount_overflow($numaccounts, $maxaccounts))) {
            	// Se usa array_reverse de array_keys porque unset produce indices no-contiguos
                $idx_remover = NULL;
                foreach (array_reverse(array_keys($cuentasGuardar)) as $i) {
            		if ($overflow == 'global' || $cuentasGuardar[$i]['tech'] == $overflow) {
            			$idx_remover = $i;
                        break;
            		}
            	}
                if (!is_null($idx_remover)) {
                    if (!is_null($cuentasGuardar[$idx_remover]['id'])) {
                    	$r = $this->_db->genQuery(
                            'DELETE FROM endpoint_account WHERE id = ?',
                            array($cuentasGuardar[$idx_remover]['id']));
                        if (!$r) {
                            $this->_db->rollBack();
                            return;
                        }
                    }
                    
                    $numaccounts['global']--;
                    $numaccounts[$cuentasGuardar[$idx_remover]['tech']]--;
                    unset($cuentasGuardar[$idx_remover]);
                }
            }
            
            // Reordenar las prioridades de las cuentas, otra vez
            $cuentasGuardar = array_merge($cuentasGuardar); // Para reindexar
            for ($i = 0; $i < count($cuentasGuardar); $i++) {
                $prio = $i + 1;
                if ($cuentasGuardar[$i]['priority'] != $prio) {
                    $cuentasGuardar[$i]['priority'] = $prio;
                    $cuentasGuardar[$i]['mod'] = TRUE;
                }
            }

            // Guardar las cuentas preservadas o agregadas
            foreach ($cuentasGuardar as $tupla) if ($tupla['mod']) {
            	if (is_null($tupla['id'])) {
            		$sql = 'INSERT INTO endpoint_account (tech, account, priority, id_endpoint) '.
                        'VALUES (?, ?, ?, ?)';
                    $param = array($tupla['tech'], $tupla['account'], $tupla['priority'], $endpoint_id);
            	} else {
            		$sql = 'UPDATE endpoint_account SET priority = ? WHERE id = ?';
                    $param = array($tupla['priority'], $tupla['id']);
            	}
                $r = $this->_db->genQuery($sql, $param);
                if (!$r) {
                    $this->_db->rollBack();
                    return;
                }
            }
            
            $r = $this->_db->genQuery(
                'UPDATE endpoint SET last_modified = NOW() WHERE id = ?',
                array($endpoint_id));
            if (!$r) {
                $this->_db->rollBack();
                return;
            }
        }
        $this->_db->commit();
    }
    
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
                    $cuentasRegistradas[$ip]['sip'][] = $extArray[0];
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
                    $cuentasRegistradas[$ip]['iax2'][] = $l[0];
                }
            }
        }
        
        $astman->disconnect();
        return $cuentasRegistradas;
    }
}
?>