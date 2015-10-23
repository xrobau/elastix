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
 * La clase definida a continuación implementa el monitoreo del estado de la
 * configuración de todos los endpoints seleccionados a partir del archivo de
 * log del proceso python. El estado consiste en:
 *  endpoints   =>  lista completa de los endpoints ya existentes en la base, 
 *                  tal como son devueltos por paloSantoEndpoints::leerEndpoints()
 *                  [id_endpoint, id_manufacturer, id_model, mac_address, 
 *                  last_known_ipv4, last_scanned, last_modified, last_configured, 
 *                  name_manufacturer, num_accounts ]
 *  configLog   =>  Ruta completa al archivo del progreso escrito por python
 *  logOffset   =>  Posición en el archivo que se ha leído hasta ahora
 */
class paloEndpointConfigStatus extends paloInterfaceSSE
{
    private $_db = NULL;
    private $_logfd = NULL;

    function paloEndpointConfigStatus()
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

/*
    function createEmptyResponse()
    {
        return array(
            'totalsteps'        =>  0,
            'completedsteps'    =>  0,
            'founderror'        =>  false,
            'endpointchanges'   =>  array()
        );
    }
    function isEmptyResponse($jsonResponse) {
        return (count($jsonResponse['endpointchanges']) <= 0);
    }
*/

    function findInitialStateDifferences(&$currentClientState, &$jsonResponse)
    {
    	$this->_logfd = @fopen($currentClientState['configLog'], 'r');
        if (!$this->_logfd) {
        	// Archivo no existe, no se puede monitorear
            $jsonResponse['endpointchanges'][] = array(
                'quit',
                _tr('(internal) No configuration log found - configuration process not started!'));
            $currentClientState['logOffset'] = 0;
            $currentClientState['configLog'] = NULL;
            return FALSE;
        }
        set_file_buffer($this->_logfd, 0);
        
        return $this->findEventStateDifferences($currentClientState, $jsonResponse);
    }
    
    function waitForEvents()
    {
    	$a = microtime(TRUE);
        $curpos = ftell($this->_logfd);
        do {
    		clearstatcache();
            $fstat = fstat($this->_logfd);
            if ($fstat['size'] > $curpos) {
            	break;
            } else {
            	usleep(250 * 1000);
            }
    	} while (microtime(TRUE) - $a < 1.0);
        return TRUE;
    }
    
    function findEventStateDifferences(&$currentClientState, &$jsonResponse)
    {
    	$r = $this->_procesarLineasCambio($currentClientState, $jsonResponse);
        if (!$r) {
            // Leer todo el contenido y guardarlo en la base de datos
            rewind($this->_logfd);
            $s = stream_get_contents($this->_logfd);            
            $this->shutdown();
            unlink($currentClientState['configLog']);
            $currentClientState['logOffset'] = 0;
            $currentClientState['configLog'] = NULL;
            
            // Contar los mensajes de advertencias y errores
            $iQuitMsg = count($jsonResponse['endpointchanges']) - 1;
            if ($iQuitMsg >= 0 && $jsonResponse['endpointchanges'][$iQuitMsg][0] == 'quit') {
            	$lineas = explode("\n", $s);
                $warnings = $errors = 0;
                foreach ($lineas as $l) {
                	if (strpos($l, 'WARNING: ') !== FALSE) $warnings++;
                    if (strpos($l, 'ERROR: ') !== FALSE) $errors++;
                }
                if ($warnings + $errors > 0) {
                	$jsonResponse['endpointchanges'][$iQuitMsg][1] = sprintf(
                        _tr('Endpoint configuration completed with %d warnings and %d errors. Please examine the log for details.'),
                        $warnings, $errors);
                }
            }
            
            $this->_db->genQuery('DELETE FROM configlog WHERE 1');
            $this->_db->genQuery('INSERT INTO configlog (lastlog) VALUES (?)', array($s));
        }
        return $r;
    }
    
    private function _procesarLineasCambio(&$currentClientState, &$jsonResponse)
    {
        // Se requiere el fseek para limpiar un EOF previo
        fseek($this->_logfd, $currentClientState['logOffset'], SEEK_SET);

        // Leer todas las líneas nuevas
        while (!feof($this->_logfd)) {
            $s = fgets($this->_logfd);
            if ($s === FALSE) break;            // Fin de archivo luego de una línea completa
            
            // Fin de archivo luego de línea truncada
            if (substr($s, -1) != "\n") {
                fseek($this->_logfd, $currentClientState['logOffset'], SEEK_SET);
            	break;
            }

            if (!isset($jsonResponse['endpointchanges']))
                $jsonResponse['endpointchanges'] = array();

            if (strpos($s, 'END ENDPOINT CONFIGURATION') !== FALSE) {
                $jsonResponse['endpointchanges'][] = array('quit', NULL);
                $currentClientState['endpoints'] = NULL;
                return FALSE;
            }
            $currentClientState['logOffset'] = ftell($this->_logfd);
            
            $regs = NULL;
            // 2013-07-03 12:24:25 : INFO: (elastix-endpointconfig) (1/3) global configuration update for VOPTech...
            if (preg_match('|^(\S+ \S+) : \w+: \(elastix-endpointconfig\) \((\d+)/(\d+)\) global configuration update (failed)?\s*for|', $s, $regs)) {
                $logdate = $regs[1];
                $curstep = $regs[2];
                $totalstep = $regs[3];
                $failed = isset($regs[4]) ? $regs[4] : NULL;
                
                $jsonResponse['totalsteps'] = (int)$totalstep;
                $jsonResponse['completedsteps'] = (int)$curstep;
                if ($failed == 'failed') {
                	$jsonResponse['founderror'] = TRUE;
                }
                
            // 2013-07-03 12:24:25 : INFO: (elastix-endpointconfig) (2/3) starting configuration for endpoint VOPTech@192.168.254.245 (1)...
            } elseif (preg_match('|^(\S+ \S+) : \w+: \(elastix-endpointconfig\) \((\d+)/(\d+)\) (\w+) configuration for endpoint \S+@(\S+) \((\d+)\)|', $s, $regs)) {
            	$logdate = $regs[1];
                $curstep = $regs[2];
                $totalstep = $regs[3];
                $type = $regs[4];
                $current_ip = $regs[5];
                $id_endpoint = $regs[6];

                $jsonResponse['totalsteps'] = (int)$totalstep;
                $jsonResponse['completedsteps'] = (int)$curstep;
                if ($type == 'finished') {
                    $this->_procesarRegistroCambio('update', $id_endpoint, $currentClientState['endpoints'], $jsonResponse['endpointchanges']);
                } elseif ($type == 'failed') {
                	$jsonResponse['founderror'] = TRUE;
                }
            }
        }
        return TRUE;
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
    
    function shutdown()
    {
    	if ($this->_logfd) fclose($this->_logfd);
        $this->_logfd = NULL;
    }
}
?>