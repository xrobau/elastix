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
  $Id: DialerProcess.class.php,v 1.48 2009/03/26 13:46:58 alex Exp $ */

require_once 'ECCPHelper.lib.php';

class ECCPProcess extends TuberiaProcess
{
    private $DEBUG = FALSE; // VERDADERO si se activa la depuración

    private $_log;      // Log abierto por framework de demonio
    private $_dsn;      // Cadena que representa el DSN, estilo PDO
    private $_db;       // Conexión a la base de datos, PDO
    private $_configDB; // Objeto de configuración desde la base de datos

    // Contadores para actividades ejecutadas regularmente
    private $_iTimestampUltimaRevisionConfig = 0;       // Última revisión de configuración

    /* Si se pone a VERDADERO, el programa intenta finalizar y no deben
     * aceptarse conexiones nuevas. Todas las conexiones existentes serán
     * desconectadas. */
    private $_finalizandoPrograma = FALSE;

    public function inicioPostDemonio($infoConfig, &$oMainLog)
    {
    	$this->_log = $oMainLog;
        $this->_multiplex = new ECCPServer('tcp://0.0.0.0:20005', $this->_log, $this->_tuberia);
        $this->_tuberia->registrarMultiplexHijo($this->_multiplex);
        $this->_tuberia->setLog($this->_log);

        // Interpretar la configuración del demonio
        $this->_dsn = $this->_interpretarConfiguracion($infoConfig);
        if (!$this->_iniciarConexionDB()) return FALSE;

        // Leer el resto de la configuración desde la base de datos
        try {
            $this->_configDB = new ConfigDB($this->_db, $this->_log);
        } catch (PDOException $e) {
            $this->_log->output("FATAL: no se puede leer configuración DB - ".$e->getMessage());
        	return FALSE;
        }

        // Registro de manejadores de eventos
        foreach (array('emitirEventos',) as $k)
            $this->_tuberia->registrarManejador('SQLWorkerProcess', $k, array($this, "msg_$k"));
        foreach (array('recordingMute', 'recordingUnmute') as $k)
            $this->_tuberia->registrarManejador('AMIEventProcess', $k, array($this, "msg_$k"));
        foreach (array('eccpresponse') as $k)
            $this->_tuberia->registrarManejador('*', $k, array($this, "msg_$k"));

        // Registro de manejadores de eventos desde HubProcess
        $this->_tuberia->registrarManejador('HubProcess', 'finalizando', array($this, "msg_finalizando"));

        $this->DEBUG = $this->_configDB->dialer_debug;

        // Se ha tenido éxito si se están escuchando conexiones
        return $this->_multiplex->escuchaActiva();
    }

    private function _interpretarConfiguracion($infoConfig)
    {
        $dbHost = 'localhost';
        $dbUser = 'asterisk';
        $dbPass = 'asterisk';
        if (isset($infoConfig['database']) && isset($infoConfig['database']['dbhost'])) {
            $dbHost = $infoConfig['database']['dbhost'];
            $this->_log->output('Usando host de base de datos: '.$dbHost);
        } else {
            $this->_log->output('Usando host (por omisión) de base de datos: '.$dbHost);
        }
        if (isset($infoConfig['database']) && isset($infoConfig['database']['dbuser']))
            $dbUser = $infoConfig['database']['dbuser'];
        if (isset($infoConfig['database']) && isset($infoConfig['database']['dbpass']))
            $dbPass = $infoConfig['database']['dbpass'];

        return array("mysql:host=$dbHost;dbname=call_center", $dbUser, $dbPass);
    }

    private function _iniciarConexionDB()
    {
        try {
            $this->_db = new PDO($this->_dsn[0], $this->_dsn[1], $this->_dsn[2]);
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
            return TRUE;
        } catch (PDOException $e) {
            $this->_db = NULL;
            $this->_log->output("FATAL: no se puede conectar a DB - ".$e->getMessage());
            return FALSE;
        }
    }

    public function procedimientoDemonio()
    {
        // Verificar posible desconexión de la base de datos
        if (is_null($this->_db)) {
            $this->_log->output('INFO: intentando volver a abrir conexión a DB...');
            if (!$this->_iniciarConexionDB()) {
            	$this->_log->output('ERR: no se puede restaurar conexión a DB, se espera...');
                usleep(5000000);
            } else {
            	$this->_log->output('INFO: conexión a DB restaurada, se reinicia operación normal.');
                $this->_configDB->setDBConn($this->_db);
            }
        }

        if (!is_null($this->_db) && !$this->_finalizandoPrograma) {
            try {
                $this->_verificarCambioConfiguracion();
            } catch (PDOException $e) {
                $this->_stdManejoExcepcionDB($e, 'no se puede verificar cambio en configuración');
            }
        }

        // Rutear los mensajes si hay DB
        if (!is_null($this->_db)) {
            // Rutear todos los mensajes pendientes entre tareas y agentes
            if ($this->_multiplex->procesarPaquetes())
                $this->_multiplex->procesarActividad(0);
            else $this->_multiplex->procesarActividad(1);
        }

    	return TRUE;
    }

    public function limpiezaDemonio($signum)
    {

        // Mandar a cerrar todas las conexiones activas
        $this->_multiplex->finalizarServidor();

        // Desconectarse de la base de datos
        $this->_configDB = NULL;
        if (!is_null($this->_db)) {
            $this->_log->output('INFO: desconectando de la base de datos...');
            $this->_db = NULL;
        }
    }

    /**************************************************************************/

    private function _verificarCambioConfiguracion()
    {
        $iTimestamp = time();
        if ($iTimestamp - $this->_iTimestampUltimaRevisionConfig > 3) {
            $this->_configDB->leerConfiguracionDesdeDB();
            $listaVarCambiadas = $this->_configDB->listaVarCambiadas();
            if (count($listaVarCambiadas) > 0) {
                if (in_array('dialer_debug', $listaVarCambiadas)) {
                    $this->DEBUG = $this->_configDB->dialer_debug;
                }
                $this->_configDB->limpiarCambios();
            }
            $this->_iTimestampUltimaRevisionConfig = $iTimestamp;
        }
    }

    private function _stdManejoExcepcionDB($e, $s)
    {
        $this->_log->output('ERR: '.__METHOD__. ": $s: ".implode(' - ', $e->errorInfo));
        $this->_log->output("ERR: traza de pila: \n".$e->getTraceAsString());
        if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 2006) {
            // Códigos correspondientes a pérdida de conexión de base de datos
            $this->_log->output('WARN: '.__METHOD__.
                ': conexión a DB parece ser inválida, se cierra...');
            $this->_db = NULL;
        }
    }


    /**************************************************************************/

    public function msg_emitirEventos($sFuente, $sDestino, $sNombreMensaje,
        $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($eventos) = $datos;

        $this->_lanzarEventos($eventos);
    }

    public function msg_finalizando($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        $this->_log->output('INFO: recibido mensaje de finalización, se desconectan conexiones...');
        $this->_finalizandoPrograma = TRUE;
        $this->_multiplex->finalizarConexionesECCP();
        $this->_tuberia->msg_HubProcess_finalizacionTerminada();
    }

    public function msg_recordingMute($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($sAgente, $sTipoLlamada, $idCampaign, $idLlamada) = $datos;

        $this->_multiplex->notificarEvento_RecordingMute($sAgente, $sTipoLlamada, $idCampaign, $idLlamada);
    }

    public function msg_recordingUnmute($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($sAgente, $sTipoLlamada, $idCampaign, $idLlamada) = $datos;

        $this->_multiplex->notificarEvento_RecordingUnmute($sAgente, $sTipoLlamada, $idCampaign, $idLlamada);
    }

    public function msg_eccpresponse($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }

        list($sKey, $s, $nuevos_valores, $eventos) = $datos;

        if (!is_null($eventos)) $this->_lanzarEventos($eventos);

        $oConn = $this->_multiplex->getConn($sKey);
        if (is_null($oConn)) {
            $this->_log->output("ERR: ".__METHOD__." ECCP connection $sKey no longer present, cannot deliver ECCP response.");
            return;
        }
        $oConn->do_eccpresponse($s, $nuevos_valores);
    }

    private function _lanzarEventos(&$eventos)
    {
        foreach ($eventos as $ev) {
            if (!is_null($ev)) call_user_func_array(
                array(
                    $this->_multiplex,
                    'notificarEvento_'.$ev[0]),
                $ev[1]);
        }
    }
}
?>