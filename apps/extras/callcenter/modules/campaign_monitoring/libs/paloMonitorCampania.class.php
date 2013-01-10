<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.8                                                  |
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
  $Id: default.conf.php,v 1.1.1.1 2007/03/23 00:13:58 elandivar Exp $ */

require_once "/opt/elastix/dialer/Predictivo.class.php";

class paloMonitorCampania
{
    private $_DB; // instancia de la clase paloDB
    private $_astConn = NULL;
    private $_predictivo = NULL;
    var $errMsg;

    function paloMonitorCampania(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
            }
        }
    }

    /**
     * Método para listar las campañas almacenadas en la base de datos, con la
     * indicación de su estado actual.
     *
     * @return mixed    NULL en error, o lista de tuplas (id,nombre,status)
     */
    function listarCampanias()
    {
        // Se aprovecha el hecho de que el estatus es A,I,T en el orden deseado
        // para listar en la lista desplegable de la interfaz.
        $sPeticionSQL = 'SELECT id, name, estatus FROM campaign ORDER BY estatus, datetime_init DESC';
        $recordset = $this->_DB->fetchTable($sPeticionSQL, TRUE);
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return $recordset;
    }
    
    /**
     * Método que devuelve un resumen de la información de una campaña saliente
     * para ser mostrada en la interfaz de monitoreo.
     *
     * @param   int     $idCampania     ID de la campaña a interrogar
     *
     * @return  mixed   NULL en error, o información de la campaña
     */
    function leerResumenCampania($idCampania)
    {
        // Leer la información en el propio registro de la campaña
        $sPeticionSQL = <<<LEER_RESUMEN_CAMPANIA
SELECT id, name, datetime_init, datetime_end, daytime_init, daytime_end, 
    retries, trunk, queue, estatus
FROM campaign WHERE id = ?
LEER_RESUMEN_CAMPANIA;
        $tupla = $this->_DB->getFirstRowQuery($sPeticionSQL, TRUE, array($idCampania));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }

        // Leer la clasificación por estado de las llamadas de la campaña
        $sPeticionSQL = 'SELECT COUNT(*) AS n, status FROM calls WHERE id_campaign = ? GROUP BY status';
        $recordset = $this->_DB->fetchTable($sPeticionSQL, TRUE, array($idCampania));
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        $tupla['status'] = array(
            'Pending'   =>  0,  // Llamada no ha sido realizada todavía

            'Placing'   =>  0,  // Originate realizado, no se recibe OriginateResponse
            'Ringing'   =>  0,  // Se recibió OriginateResponse, no entra a cola
            'OnQueue'   =>  0,  // Entró a cola, no se asigna a agente todavía
            'Success'   =>  0,  // Conectada y asignada a un agente
            'OnHold'    =>  0,  // Llamada fue puesta en espera por agente
            'Failure'   =>  0,  // No se puede conectar llamada
            'ShortCall' =>  0,  // Llamada conectada pero duración es muy corta
            'NoAnswer'  =>  0,  // Llamada estaba Ringing pero no entró a cola
            'Abandoned' =>  0,  // Llamada estaba OnQueue pero no habían agentes            
        );
        foreach ($recordset as $tuplaStatus) {
            if (is_null($tuplaStatus['status']))
                $tupla['status']['Pending'] = $tuplaStatus['n'];
            else $tupla['status'][$tuplaStatus['status']] = $tuplaStatus['n'];
        }

        return $tupla;
    }

    // Leer el estado de /etc/asterisk/manager.conf y obtener el primer usuario que puede usar el dialer.
    // Devuelve NULL en caso de error, o tupla user,password para conexión en localhost.
    private function _leerConfigManager()
    {
    	$sNombreArchivo = '/etc/asterisk/manager.conf';
        if (!file_exists($sNombreArchivo)) {
        	$this->errMsg = "WARN: $sNombreArchivo no se encuentra.";
            return NULL;
        }
        if (!is_readable($sNombreArchivo)) {
            $this->errMsg = "WARN: $sNombreArchivo no puede leerse por usuario de marcador.";
            return NULL;        	
        }
        $infoConfig = parse_ini_file($sNombreArchivo, TRUE);
        if (is_array($infoConfig)) {
            foreach ($infoConfig as $login => $infoLogin) {
            	if ($login != 'general') {
            		if (isset($infoLogin['secret']) && isset($infoLogin['read']) && isset($infoLogin['write'])) {
            			return array($login, $infoLogin['secret']);
            		}
            	}
            }
        } else {
            $this->errMsg = "ERR: $sNombreArchivo no puede parsearse correctamente.";
        }
        return NULL;
    }

    private function _iniciarConexionAsterisk()
    {
        if (is_null($this->_astConn)) {
            $infoLogin = $this->_leerConfigManager();
            if (is_null($infoLogin)) die($this->errMsg);
            $this->_astConn = new AGI_AsteriskManager();
            $r = $this->_astConn->connect('localhost', $infoLogin[0], $infoLogin[1]);
            if (!$r) die("No se puede conectar a AMI");
            $this->_predictivo = new Predictivo($this->_astConn);
        }
    }

    /**
     * Procedimiento que lee el estado de la cola indicada por el parámetro. 
     * Se invoca al método ya implementado para el marcador predictivo, y a 
     * continuación se agrega información para identificar: en caso de break,
     * intervalo del break y tipo del break; en caso de llamada ocupada, número,
     * tiempo y canal de la llamada.
     */
    function leerEstadoCola($idCola)
    {
        $this->_iniciarConexionAsterisk();
        $estadoCola = $this->_predictivo->leerEstadoCola($idCola);
        foreach ($estadoCola['members'] as $sNumAgente => $infoAgente) {
            if (in_array('paused', $infoAgente['attributes'])) {
                // El agente está en pausa. Se intenta identificar tipo de break
                $sqlBreak = <<<LEER_TIPO_BREAK
SELECT audit.datetime_init, break.name 
FROM agent, audit, break 
WHERE agent.number = ? AND agent.estatus = "A" AND agent.id = audit.id_agent 
    AND audit.datetime_end IS NULL AND audit.id_break = break.id
LEER_TIPO_BREAK;
                $tuplaBreak = $this->_DB->getFirstRowQuery($sqlBreak, TRUE, array($sNumAgente));
                if (is_array($tuplaBreak)) {
                    $estadoCola['members'][$sNumAgente]['datetime_init'] = $tuplaBreak['datetime_init'];
                    $estadoCola['members'][$sNumAgente]['break_name'] = $tuplaBreak['name'];
                }
            } elseif ($infoAgente['status'] == 'inUse') {
                $estadoCola['members'][$sNumAgente]['datetime_init'] = date('Y-m-d H:i:s', time() - $infoAgente['talkTime']);
            }
        }
        return $estadoCola;
    }
}
?>
