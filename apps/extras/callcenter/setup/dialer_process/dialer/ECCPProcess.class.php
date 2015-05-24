<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: DialerProcess.class.php,v 1.48 2009/03/26 13:46:58 alex Exp $ */

require_once 'ECCPHelper.lib.php';

class ECCPProcess extends TuberiaProcess
{
    private $DEBUG = FALSE; // VERDADERO si se activa la depuración

    private $_log;      // Log abierto por framework de demonio
    private $_dsn;      // Cadena que representa el DSN, estilo PDO
    private $_db;       // Conexión a la base de datos, PDO
    private $_ami = NULL;       // Conexión AMI a Asterisk
    private $_configDB; // Objeto de configuración desde la base de datos

    // Contadores para actividades ejecutadas regularmente
    private $_iTimestampUltimaRevisionConfig = 0;       // Última revisión de configuración

    /* Si se pone a VERDADERO, el programa intenta finalizar y no deben
     * aceptarse conexiones nuevas. Todas las conexiones existentes serán
     * desconectadas. */
    private $_finalizandoPrograma = FALSE;

    private $_iTimestampInicioProceso;

    public function inicioPostDemonio($infoConfig, &$oMainLog)
    {
    	$this->_log = $oMainLog;
        $this->_multiplex = new ECCPServer('tcp://0.0.0.0:20005', $this->_log, $this->_tuberia);
        $this->_multiplex->setProcess($this);
        $this->_tuberia->registrarMultiplexHijo($this->_multiplex);
        $this->_tuberia->setLog($this->_log);
        $this->_iTimestampInicioProceso = time();

        // Interpretar la configuración del demonio
        $this->_dsn = $this->_interpretarConfiguracion($infoConfig);
        if (!$this->_iniciarConexionDB()) return FALSE;
        $this->_multiplex->setDBConn($this->_db);

        // Leer el resto de la configuración desde la base de datos
        try {
            $this->_configDB = new ConfigDB($this->_db, $this->_log);
        } catch (PDOException $e) {
            $this->_log->output("FATAL: no se puede leer configuración DB - ".$e->getMessage());
        	return FALSE;
        }

        $this->_repararAuditoriasIncompletas();

        // Iniciar la conexión Asterisk
        if (!$this->_iniciarConexionAMI()) return FALSE;

        // Registro de manejadores de eventos
        foreach (array('notificarProgresoLlamada') as $k)
            $this->_tuberia->registrarManejador('CampaignProcess', $k, array($this, "msg_$k"));
        foreach (array('AgentLogin', 'AgentLogoff', 'AgentLinked',
            'AgentUnlinked', 'marcarFinalHold', 'notificarProgresoLlamada',
            'nuevaMembresiaCola') as $k)
            $this->_tuberia->registrarManejador('AMIEventProcess', $k, array($this, "msg_$k"));

        // Registro de manejadores de eventos desde HubProcess
        $this->_tuberia->registrarManejador('HubProcess', 'finalizando', array($this, "msg_finalizando"));

        $this->DEBUG = $this->_configDB->dialer_debug;

        // Se ha tenido éxito si se están escuchando conexiones
        $this->_multiplex->setDEBUG($this->_configDB->dialer_debug);
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

    /**
     * Procedimiento que intenta reparar los registros de auditoría que no están
     * correctamente cerrados, es decir, que tiene NULL como fecha de cierre.
     * Primero se identifican los agentes para los cuales existen auditorías
     * incompletas, y luego se intenta reparar para cada agente. Se asume que
     * este método se invoca ANTES de empezar a escuchar peticiones ECCP, y que
     * la base de datos es modificada únicamente por este proceso, y no por
     * otras copias concurrentes del dialer (lo cual no está soportado
     * actualmente).
     */
    private function _repararAuditoriasIncompletas()
    {
    	try {
    		$sPeticionSQL = <<<AGENTES_AUDIT_INCOMPLETO
SELECT DISTINCT agent.id, agent.type, agent.number, agent.name, agent.estatus
FROM audit, agent
WHERE agent.id = audit.id_agent AND audit.id_break IS NULL AND audit.datetime_end IS NULL
ORDER BY agent.id
AGENTES_AUDIT_INCOMPLETO;
            $recordset = $this->_db->prepare($sPeticionSQL);
            $recordset->execute();
            $agentesReparar = $recordset->fetchAll(PDO::FETCH_ASSOC);
            $recordset->closeCursor();
            foreach ($agentesReparar as $row) {
            	$this->_log->output('INFO: se ha detectado auditoría incompleta '.
                    "para {$row['type']}/{$row['number']} - {$row['name']} ".
                    "(id_agent={$row['id']} ".(($row['estatus'] == 'A') ? 'ACTIVO' : 'INACTIVO').")");
                $this->_repararAuditoriaAgente($row['id']);
            }
    	} catch (PDOException $e) {
    		$this->_stdManejoExcepcionDB($e, 'no se puede terminar de reparar auditorías');
    	}
    }

    private function _repararAuditoriaAgente($idAgente)
    {
        // Listar todas las auditorías incompletas para este agente
    	$sPeticionAuditorias = <<<LISTA_AUDITORIAS_AGENTE
SELECT id, datetime_init FROM audit
WHERE id_agent = ? AND id_break IS NULL AND datetime_end IS NULL
ORDER BY datetime_init
LISTA_AUDITORIAS_AGENTE;
        $recordset = $this->_db->prepare($sPeticionAuditorias);
        $recordset->execute(array($idAgente));
        $listaAudits = $recordset->fetchAll(PDO::FETCH_ASSOC);
        $recordset->closeCursor();

        foreach ($listaAudits as $auditIncompleto) {
        	/* Se intenta examinar la base de datos para obtener la fecha
             * máxima para la cual hay evidencia de actividad entre el inicio
             * de este registro y el inicio del siguiente registro. */
            $this->_log->output("INFO:\tSesión ID={$auditIncompleto['id']} iniciada en {$auditIncompleto['datetime_init']}");

            $sFechaSiguienteSesion = NULL;
            $idUltimoBreak = NULL;
            $sFechaInicioBreak = NULL;
            $sFechaFinalBreak = NULL;
            $sFechaInicioLlamada = NULL;
            $sFechaFinalLlamada = NULL;

            // El inicio de la siguiente sesión es un tope máximo para el final de la sesión incompleta.
            $recordset = $this->_db->prepare(
                'SELECT datetime_init FROM audit WHERE id_agent = ? AND id_break IS NULL '.
                'AND datetime_init > ? ORDER BY datetime_init LIMIT 0,1');
            $recordset->execute(array($idAgente, $auditIncompleto['datetime_init']));
            $tupla = $recordset->fetch(PDO::FETCH_ASSOC);
            $recordset->closeCursor();
            if (!$tupla) {
                $this->_log->output("INFO:\tNo hay sesiones posteriores a esta sesión incompleta.");
            } else {
            	$this->_log->output("INFO:\tSiguiente sesión iniciada en {$tupla['datetime_init']}");
                $sFechaSiguienteSesion = $tupla['datetime_init'];
            }

            /* La sesión sólo puede extenderse hasta el final de la pausa antes de
             * la siguiente sesión, o la fecha actual */
            $recordset = $this->_db->prepare(
                'SELECT id, datetime_init, datetime_end FROM audit WHERE id_agent = ? '.
                    'AND id_break IS NOT NULL AND datetime_init > ? AND datetime_init < ? ' .
                'ORDER BY datetime_init DESC LIMIT 0,1');
            $recordset->execute(array($idAgente, $auditIncompleto['datetime_init'],
                (is_null($sFechaSiguienteSesion) ? date('Y-m-d H:i:s') : $sFechaSiguienteSesion)));
            $tupla = $recordset->fetch(PDO::FETCH_ASSOC);
            $recordset->closeCursor();
            if (!$tupla) {
                $this->_log->output("INFO:\tNo hay breaks pertenecientes a esta sesión incompleta.");
            } else {
                $this->_log->output("INFO:\tÚltimo break de sesión incompleta inicia en {$tupla['datetime_init']}, ".
                    (is_null($tupla['datetime_end']) ? 'está incompleto' : 'termina en '.$tupla['datetime_end']));
                $idUltimoBreak = $tupla['id'];
                $sFechaInicioBreak = $tupla['datetime_init'];
                $sFechaFinalBreak = $tupla['datetime_end'];
            }

            /* La sesión sólo puede extenderse hasta el final de la última llamada
             * atendida antes de la siguiente sesión, si existe, o hasta la fecha
             * actual */
            $recordset = $this->_db->prepare(
                'SELECT start_time, end_time FROM calls '.
                'WHERE id_agent = ? AND start_time >= ? AND start_time < ? '.
                'ORDER BY start_time DESC LIMIT 0,1');
            $recordset->execute(array($idAgente, $auditIncompleto['datetime_init'],
                (is_null($sFechaSiguienteSesion) ? date('Y-m-d H:i:s') : $sFechaSiguienteSesion)));
            $tupla = $recordset->fetch(PDO::FETCH_ASSOC);
            $recordset->closeCursor();
            if (!$tupla) {
                $this->_log->output("INFO:\tNo hay llamadas salientes pertenecientes a esta sesión incompleta.");
            } else {
                $this->_log->output("INFO:\tÚltima llamada saliente de sesión incompleta inicia en {$tupla['start_time']}, ".
                    (is_null($tupla['end_time']) ? 'está incompleta' : 'termina en '.$tupla['end_time']));
                $sFechaInicioLlamada = $tupla['start_time'];
                $sFechaFinalLlamada = $tupla['end_time'];
            }
            $recordset = $this->_db->prepare(
                'SELECT datetime_init, datetime_end FROM call_entry '.
                'WHERE id_agent = ? AND datetime_init >= ? AND datetime_init < ? '.
                'ORDER BY datetime_init DESC LIMIT 0,1');
            $recordset->execute(array($idAgente, $auditIncompleto['datetime_init'],
                (is_null($sFechaSiguienteSesion) ? date('Y-m-d H:i:s') : $sFechaSiguienteSesion)));
            $tupla = $recordset->fetch(PDO::FETCH_ASSOC);
            $recordset->closeCursor();
            if (!$tupla) {
                $this->_log->output("INFO:\tNo hay llamadas entrantes pertenecientes a esta sesión incompleta.");
            } else {
                $this->_log->output("INFO:\tÚltima llamada entrante de sesión incompleta inicia en {$tupla['datetime_init']}, ".
                    (is_null($tupla['datetime_end']) ? 'está incompleta' : 'termina en '.$tupla['datetime_end']));
                if (is_null($sFechaInicioLlamada) || $sFechaInicioLlamada < $tupla['datetime_init'])
                    $sFechaInicioLlamada = $tupla['datetime_init'];
                if (is_null($sFechaFinalLlamada) || $sFechaFinalLlamada < $tupla['datetime_end'])
                    $sFechaFinalLlamada = $tupla['datetime_end'];
            }

            /* De entre todas las fecha recogidas, se elige la más reciente como
             * la fecha de final de auditoría. Esto incluye a la fecha de inicio
             * de auditoría, con lo que una auditoría sin otros indicios quedará
             * de longitud cero. */
            $sFechaFinal = $auditIncompleto['datetime_init'];
            if (!is_null($sFechaInicioBreak) && $sFechaInicioBreak > $sFechaFinal)
                $sFechaFinal = $sFechaInicioBreak;
            if (!is_null($sFechaFinalBreak) && $sFechaFinalBreak > $sFechaFinal)
                $sFechaFinal = $sFechaFinalBreak;
            if (!is_null($sFechaInicioLlamada) && $sFechaInicioLlamada > $sFechaFinal)
                $sFechaFinal = $sFechaInicioLlamada;
            if (!is_null($sFechaFinalLlamada) && $sFechaFinalLlamada > $sFechaFinal)
                $sFechaFinal = $sFechaFinalLlamada;

            $this->_log->output("INFO:\t\\--> Fecha estimada de final de sesión es $sFechaFinal, se actualiza...");
            $sth = $this->_db->prepare(
                'UPDATE audit SET datetime_end = ?, duration = TIMEDIFF(?, datetime_init) WHERE id = ?');
            if (!is_null($idUltimoBreak) && is_null($sFechaFinalBreak)) {
                $sth->execute(array($sFechaFinal, $sFechaFinal, $idUltimoBreak));
            }
            $sth->execute(array($sFechaFinal, $sFechaFinal, $auditIncompleto['id']));
        }
    }

    public function procedimientoDemonio()
    {
        // Verificar posible desconexión de la base de datos
        if (!$this->_multiplex->dbValido()) {
        	$this->_db = NULL;
        }
        if (is_null($this->_db)) {
            $this->_log->output('INFO: intentando volver a abrir conexión a DB...');
            if (!$this->_iniciarConexionDB()) {
            	$this->_log->output('ERR: no se puede restaurar conexión a DB, se espera...');
                usleep(5000000);
            } else {
            	$this->_log->output('INFO: conexión a DB restaurada, se reinicia operación normal.');
                $this->_configDB->setDBConn($this->_db);
                $this->_multiplex->setDBConn($this->_db);
            }
        }

        // Verificar si la conexión AMI sigue siendo válida
        if (!is_null($this->_ami) && is_null($this->_ami->sKey)) {
            $this->_ami = NULL;
            $this->_multiplex->setAstConn(NULL);
        }
        if (is_null($this->_ami) && !$this->_finalizandoPrograma) {
            if (!$this->_iniciarConexionAMI()) {
                $this->_log->output('ERR: no se puede restaurar conexión a Asterisk, se espera...');
                if (!is_null($this->_db)) {
                    if ($this->_multiplex->procesarPaquetes())
                        $this->_multiplex->procesarActividad(0);
                    else $this->_multiplex->procesarActividad(5);
                } else {
                    usleep(5000000);
                }
            } else {
                $this->_log->output('INFO: conexión a Asterisk restaurada, se reinicia operación normal.');
            }
        }

        if (!is_null($this->_db) && !is_null($this->_ami) && !$this->_finalizandoPrograma) {
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

    private function _iniciarConexionAMI()
    {
        if (!is_null($this->_ami)) {
            $this->_log->output('INFO: Desconectando de sesión previa de Asterisk...');
            $this->_ami->disconnect();
            $this->_ami = NULL;
            $this->_multiplex->setAstConn(NULL, NULL);
        }
        $astman = new AMIClientConn($this->_multiplex, $this->_log);
        //$this->_momentoUltimaConnAsterisk = time();

        $this->_log->output('INFO: Iniciando sesión de control de Asterisk...');
        if (!$astman->connect(
                $this->_configDB->asterisk_asthost,
                $this->_configDB->asterisk_astuser,
                $this->_configDB->asterisk_astpass)) {
            $this->_log->output("FATAL: no se puede conectar a Asterisk Manager");
            return FALSE;
        } else {
            // Averiguar la versión de Asterisk que se usa
            $astVersion = array(1, 4, 0, 0);
            $r = $astman->CoreSettings(); // Sólo disponible en Asterisk >= 1.6.0
            if ($r['Response'] == 'Success' && isset($r['AsteriskVersion'])) {
                $astVersion = explode('.', $r['AsteriskVersion']);
                $this->_log->output("INFO: CoreSettings reporta Asterisk ".implode('.', $astVersion));
            } else {
                $this->_log->output("INFO: no hay soporte CoreSettings en Asterisk Manager, se asume Asterisk 1.4.x.");
            }

            // ECCPProcess no tiene manejadores de eventos AMI

            $this->_multiplex->setAstConn($astman, $astVersion);
            $this->_ami = $astman;
            return TRUE;
        }
    }

    private function _verificarCambioConfiguracion()
    {
        $iTimestamp = time();
        if ($iTimestamp - $this->_iTimestampUltimaRevisionConfig > 3) {
            $this->_configDB->leerConfiguracionDesdeDB();
            $listaVarCambiadas = $this->_configDB->listaVarCambiadas();
            if (count($listaVarCambiadas) > 0) {
                if (in_array('dialer_debug', $listaVarCambiadas)) {
                    $this->DEBUG = $this->_configDB->dialer_debug;
                    $this->_multiplex->setDEBUG($this->_configDB->dialer_debug);
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
            $this->_multiplex->setDBConn(NULL);
        }
    }

    /**
     * Método para marcar en las tablas de auditoría que el agente ha iniciado
     * la sesión. Esta implementación verifica si el agente ya ha sido marcado
     * previamente como que inició la sesión, y sólo marca el inicio si no está
     * ya marcado antes.
     *
     * @param   string  $sAgente    Canal del agente que se verifica sesión
     * @param   int     $id_agent   ID en base de datos del agente
     * @param   float   $iTimestampLogin timestamp devuelto por microtime() de login
     *
     * @return  mixed   NULL en error, o el ID de la auditoría de inicio de sesión
     */
    private function _marcarInicioSesionAgente($idAgente, $iTimestampLogin)
    {
        try {
            // Verificación de sesión activa
            $sPeticionExiste = <<<SQL_EXISTE_AUDIT
SELECT id FROM audit
WHERE id_agent = ? AND datetime_init >= ? AND datetime_end IS NULL
    AND duration IS NULL AND id_break IS NULL
ORDER BY datetime_init DESC
SQL_EXISTE_AUDIT;
            $recordset = $this->_db->prepare($sPeticionExiste);
            $recordset->execute(array($idAgente, date('Y-m-d H:i:s', $this->_iTimestampInicioProceso)));
            $tupla = $recordset->fetch();
            $recordset->closeCursor();

            // Se indica éxito de inmediato si ya hay una sesión
            $idAudit = NULL;
            if ($tupla) {
                $idAudit = $tupla['id'];
                $this->_log->output('WARN: '.__METHOD__.": id_agente={$idAgente} ".
                    'inició sesión en '.date('Y-m-d H:i:s', $iTimestampLogin).
                    " pero hay sesión abierta ID={$idAudit}, se reusa.");
            } else {
                // Ingreso de sesión del agente
                $sTimeStamp = date('Y-m-d H:i:s', $iTimestampLogin);
                $sth = $this->_db->prepare('INSERT INTO audit (id_agent, datetime_init) VALUES (?, ?)');
                $sth->execute(array($idAgente, $sTimeStamp));
                $idAudit = $this->_db->lastInsertId();
            }

            return $idAudit;
        } catch (PDOException $e) {
            $this->_stdManejoExcepcionDB($e, 'no se puede registrar inicio de sesión de agente');
        	return NULL;
        }
    }

    /**
     * Método para marcar en las tablas de auditoría que el agente ha terminado
     * su sesión principal y está haciendo logout.
     *
     * @param   int     $idAuditSesion  ID de sesión devuelto por marcarInicioSesionAgente()
     * @param   int     $idAuditBreak   ID del break devuelto por marcarInicioBreakAgente().
     *                                  Si es NULL, se ignora (no estaba en break).
     *
     * @return  bool    VERDADERO en éxito, FALSE en error.
     */
    private function _marcarFinalSesionAgente($sAgente, $iTimestampLogout, $idAuditSesion, $idAuditBreak)
    {
        // Quitar posibles pausas sobre el agente
        $this->_ami->QueuePause(NULL, $sAgente, 'false');

        if (!is_null($idAuditBreak))
            marcarFinalBreakAgente($this->_db, $idAuditBreak, $iTimestampLogout);
        $sTimeStamp = date('Y-m-d H:i:s', $iTimestampLogout);
        $sth = $this->_db->prepare(
            'UPDATE audit SET datetime_end = ?, duration = TIMEDIFF(?, datetime_init) WHERE id = ?');
        $sth->execute(array($sTimeStamp, $sTimeStamp, $idAuditSesion));
    }

    /**************************************************************************/

    public function msg_AgentLogin($sFuente, $sDestino, $sNombreMensaje,
        $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
    	list($sAgente, $iTimestampLogin, $id_agent) = $datos;

    	try {
            if (is_null($id_agent)) {
            	// Ha fallado un intento de login
                $this->_multiplex->notificarEvento_AgentLogin($sAgente, NULL, FALSE);
            } else {
                // Si el agente está en pausa, se la quita ahora
                $this->_ami->QueuePause(NULL, $sAgente, 'false');

            	$id_sesion = $this->_marcarInicioSesionAgente($id_agent, $iTimestampLogin);
                if (!is_null($id_sesion)) {
                    $this->_tuberia->msg_AMIEventProcess_idNuevaSesionAgente($sAgente, $id_sesion);

                    // Notificar a todas las conexiones abiertas
                    $this->_multiplex->notificarEvento_AgentLogin($sAgente, TRUE);
                }
            }
    	} catch (PDOException $e) {
    	    $this->_stdManejoExcepcionDB($e, 'no se puede registrar inicio de sesión de agente');
    	}
    }

    public function msg_AgentLogoff($sFuente, $sDestino, $sNombreMensaje,
        $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($sAgente, $iTimestampLogout, $id_agent, $id_sesion, $id_break, $id_hold) = $datos;

        try {
            $eventos = array();

            // Escribir la información de auditoría en la base de datos
            $this->_db->beginTransaction();
            if (!is_null($id_hold)) {
                // TODO: ¿Qué ocurre con la posible llamada parqueada?
                marcarFinalBreakAgente($this->_db, $id_hold, $iTimestampLogout);
                $eventos[] = construirEventoPauseEnd($this->_db, $sAgente, $id_hold, 'hold');
            }
            $this->_marcarFinalSesionAgente($sAgente, $iTimestampLogout, $id_sesion, $id_break);
            if (!is_null($id_break))
                $eventos[] = construirEventoPauseEnd($this->_db, $sAgente, $id_break, 'break');

            // Notificar a todas las conexiones abiertas
            $eventos[] = array('AgentLogoff', array($sAgente));
            $this->_lanzarEventos($eventos);

            $this->_db->commit();
        } catch (PDOException $e) {
            $this->_db->rollBack();
            $this->_stdManejoExcepcionDB($e, 'no se puede registrar final de sesión de agente');
        }
    }

    public function msg_AgentLinked($sFuente, $sDestino, $sNombreMensaje,
        $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($sTipoLlamada, $idCampania, $idLlamada, $sChannel, $sRemChannel,
            $sFechaLink, $id_agent, $trunk, $queue) = $datos;

        try {
        	$infoLlamada = leerInfoLlamada($this->_db, $sTipoLlamada, $idCampania, $idLlamada);
            /* Ya que la escritura a la base de datos es asíncrona, puede
             * ocurrir que se lea la llamada en el estado OnQueue y sin fecha
             * de linkstart. */
            if ($infoLlamada['calltype'] == 'incoming')
                $infoLlamada['status'] = 'activa';
            if ($infoLlamada['calltype'] == 'outgoing')
                $infoLlamada['status'] = 'Success';
            if (!isset($infoLlamada['queue']) && !is_null($queue))
                $infoLlamada['queue'] = $queue;
            $infoLlamada['datetime_linkstart'] = $sFechaLink;
            if (!isset($infoLlamada['trunk']) || is_null($infoLlamada['trunk']))
                $infoLlamada['trunk'] = $trunk;

            // Notificar el progreso de la llamada
            $paramProgreso = array(
                'datetime_entry'    =>  $sFechaLink,
                'new_status'        =>  'Success',
                'id_agent'          =>  $id_agent,
            );
            if ($sTipoLlamada == 'outgoing') {
                $paramProgreso['id_call_outgoing'] = $idLlamada;
                $paramProgreso['id_campaign_outgoing'] = $idCampania;
            } else {
                $paramProgreso['id_call_incoming'] = $idLlamada;
                if (!is_null($idCampania)) $paramProgreso['id_campaign_incoming'] = $idCampania;
            }

            $infoLlamada['campaignlog_id'] = $this->_notificarProgresoLlamada($paramProgreso);
            $this->_multiplex->notificarEvento_AgentLinked($sChannel, $sRemChannel, $infoLlamada);
        } catch (PDOException $e) {
        	$this->_stdManejoExcepcionDB($e, 'no se puede leer información de llamada para AgentLinked');
        }
    }

    public function msg_AgentUnlinked($sFuente, $sDestino, $sNombreMensaje,
        $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($sAgente, $sTipoLlamada, $idCampaign, $idLlamada, $sPhone,
                $sFechaFin, $iDuracion, $bShortFlag, $paramProgreso) = $datos;

        try {
            $campaignlog_id = $this->_notificarProgresoLlamada($paramProgreso);
            $this->_multiplex->notificarEvento_AgentUnlinked($sAgente, array(
                'calltype'      =>  $sTipoLlamada,
                'campaign_id'   =>  $idCampaign,
                'call_id'       =>  $idLlamada,
                'phone'         =>  $sPhone,
                'datetime_linkend'  =>  $sFechaFin,
                'duration'      =>  $iDuracion,
                'shortcall'     =>  $bShortFlag ? 1 : 0,
                'campaignlog_id'=>  $campaignlog_id,
                'queue'         =>  $paramProgreso['queue'],
            ));
        } catch (PDOException $e) {
            $this->_stdManejoExcepcionDB($e, 'no se puede leer información de llamada para AgentUnlinked');
        }
    }

    public function msg_marcarFinalHold($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($iTimestampFinalPausa, $sAgente, $infoLlamada, $infoSeguimiento) = $datos;

        try {
            // Quitar la pausa del agente si es necesario
            if ($infoSeguimiento['num_pausas'] == 1) {
                // La única pausa que quedaba era la del hold
                $r = $this->_ami->QueuePause(NULL, $sAgente, 'false');
                if ($r['Response'] != 'Success') {
                    $this->_log->output('ERR: '.__METHOD__.' (internal) no se puede sacar al agente de pausa: '.
                        $sAgente.' - '.$r['Message']);
                }
            }

            // Actualizar las tablas de calls y current_calls
            $this->_db->beginTransaction();
            if ($infoLlamada['calltype'] == 'incoming') {
                $sth = $this->_db->prepare(
                    'UPDATE current_call_entry SET hold = ? WHERE id = ?');
                $sth->execute(array('N', $infoLlamada['currentcallid']));
                $sth = $this->_db->prepare('UPDATE call_entry set status = ? WHERE id = ?');
                $sth->execute(array('activa', $infoLlamada['callid']));
            } else {
                $sth = $this->_db->prepare(
                    'UPDATE current_calls SET hold = ? WHERE id = ?');
                $sth->execute(array('N', $infoLlamada['currentcallid']));
                $sth = $this->_db->prepare('UPDATE calls set status = ? WHERE id = ?');
                $sth->execute(array('Success', $infoLlamada['callid']));
            }

            // Auditoría del fin del hold
            marcarFinalBreakAgente($this->_db, $infoSeguimiento['id_audit_hold'], $iTimestampFinalPausa);
            $eventos[] = construirEventoPauseEnd($this->_db, $sAgente, $infoSeguimiento['id_audit_hold'], 'hold');
            $this->_lanzarEventos($eventos);

            $this->_db->commit();
        } catch (PDOException $e) {
            $this->_db->rollBack();
            $this->_stdManejoExcepcionDB($e, 'no se puede actualizar el final de HOLD');
        }
    }

    public function msg_finalizando($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        $this->_log->output('INFO: recibido mensaje de finalización, se desconectan conexiones...');
        $this->_finalizandoPrograma = TRUE;
        $this->_multiplex->finalizarConexionesECCP();
        $this->_tuberia->msg_HubProcess_finalizacionTerminada();
    }

    public function msg_notificarProgresoLlamada($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($prop) = $datos;

        try {
            $this->_notificarProgresoLlamada($prop);
        } catch (PDOException $e) {
            $this->_stdManejoExcepcionDB($e, 'no se puede escribir bitácora de estado de llamada');
        }
    }

    private function _notificarProgresoLlamada($prop)
    {
        list($id_campaignlog, $ev) = construirEventoProgresoLlamada($this->_db, $prop);
        $eventos = array($ev);
        $this->_lanzarEventos($eventos);
        return $id_campaignlog;
    }

    public function msg_nuevaMembresiaCola($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        if ($this->DEBUG) {
            $this->_log->output('DEBUG: '.__METHOD__.' - '.print_r($datos, 1));
        }
        list($sAgente, $infoSeguimiento, $listaColas) = $datos;

        try {
            cargarInfoPausa($this->_db, $infoSeguimiento);
            $this->_multiplex->notificarEvento_QueueMembership($sAgente, $infoSeguimiento, $listaColas);
        } catch (PDOException $e) {
            $this->_stdManejoExcepcionDB($e, 'no se puede cargar información de pausa');
        }
    }

    // TODO: este evento es público sólo hasta completar migración a workers
    public function _lanzarEventos(&$eventos)
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