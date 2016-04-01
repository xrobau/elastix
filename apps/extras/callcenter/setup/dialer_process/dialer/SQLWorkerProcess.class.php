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
require_once "/var/www/html/libs/callCenterProUtils.class.php";
require_once 'ECCPHelper.lib.php';

class SQLWorkerProcess extends TuberiaProcess
{
    private $DEBUG = FALSE; // VERDADERO si se activa la depuración

    private $_log;      // Log abierto por framework de demonio
    private $_dsn;      // Cadena que representa el DSN, estilo PDO
    private $_db;       // Conexión a la base de datos, PDO
    private $_configDB; // Objeto de configuración desde la base de datos

    // Contadores para actividades ejecutadas regularmente
    private $_iTimestampActualizacion = 0;          // Última actualización remota
    private $_iTimestampUltimaRevisionConfig = 0;   // Última revisión de configuración

    /* Lista de acciones pendientes encargadas por otros procesos. Cada elemento
     * de este arreglo es una tupla cuyo primer elemento es callable y el segundo
     * elemento es la lista de parámetros con los que se debe invocar el callable.
     * Ya que todos los callables usan la base de datos, es posible que la
     * ejecución arroje excepciones PDOException. Todos los callables se invocan
     * dentro de una transacción de la base de datos, la cual se hará commit()
     * en caso de que no se arrojen excepciones. De lo contrario, y si la conexión
     * sigue siendo válida, se realizará un rollback() y se reintentará la operación
     * en un momento posterior. Todos los callables deben de devolver un arreglo
     * que contiene los eventos a ser lanzados como resultado de haber completado
     * las operaciones correspondientes.
     */
    private $_accionesPendientes = array();

    private $_finalizandoPrograma = FALSE;

    public function inicioPostDemonio($infoConfig, &$oMainLog)
    {
    	$this->_log = $oMainLog;
        $this->_multiplex = new MultiplexServer(NULL, $this->_log);
        $this->_tuberia->registrarMultiplexHijo($this->_multiplex);
        $this->_tuberia->setLog($this->_log);

        // Interpretar la configuración del demonio
        $this->_dsn = $this->_interpretarConfiguracion();
        if (!$this->_iniciarConexionDB()) return FALSE;

        // Leer el resto de la configuración desde la base de datos
        try {
            $this->_configDB = new ConfigDB($this->_db, $this->_log);
        } catch (PDOException $e) {
            $this->_log->output("FATAL: no se puede leer configuración DB - ".$e->getMessage());
            return FALSE;
        }

        // Registro de manejadores de eventos desde AMIEventProcess
        foreach (array('sqlinsertcalls', 'sqlupdatecalls',
            'sqlinsertcurrentcalls', 'sqldeletecurrentcalls',
            'sqlupdatecurrentcalls', 'sqlupdatestatcampaign', 'finalsql',
            'verificarFinLlamadasAgendables', 'agregarArchivoGrabacion') as $k)
            $this->_tuberia->registrarManejador('AMIEventProcess', $k, array($this, "msg_$k"));

        // Registro de manejadores de eventos desde ECCPWorkerProcess
        foreach (array('requerir_nuevaListaAgentes') as $k)
            $this->_tuberia->registrarManejador('*', $k, array($this, "msg_$k"));

        // Registro de manejadores de eventos desde HubProcess
        $this->_tuberia->registrarManejador('HubProcess', 'finalizando', array($this, "msg_finalizando"));

        $this->DEBUG = $this->_configDB->dialer_debug;

        // Informar a AMIEventProcess la configuración de Asterisk
        $this->_tuberia->AMIEventProcess_informarCredencialesAsterisk(array(
            'asterisk'  =>  array(
                'asthost'           =>  $this->_configDB->asterisk_asthost,
                'astuser'           =>  $this->_configDB->asterisk_astuser,
                'astpass'           =>  $this->_configDB->asterisk_astpass,
                'duracion_sesion'   =>  $this->_configDB->asterisk_duracion_sesion,
            ),
            'dialer'    =>  array(
                'llamada_corta'     =>  $this->_configDB->dialer_llamada_corta,
                'tiempo_contestar'  =>  $this->_configDB->dialer_tiempo_contestar,
                'debug'             =>  $this->_configDB->dialer_debug,
                'allevents'         =>  $this->_configDB->dialer_allevents,
            ),
        ));

        return TRUE;
    }

    private function _interpretarConfiguracion()
    {
        $dbConfig = getCallCenterDBString(TRUE);
        $this->_log->output('Usando host de base de datos: '.$dbConfig["host"]);
        return array("mysql:host={$dbConfig["host"]};dbname=call_center_pro", $dbConfig["user"], $dbConfig["password"]);
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
        // Lo siguiente NO debe de iniciar operaciones DB, sólo acumular acciones
        $bPaqProcesados = $this->_multiplex->procesarPaquetes();
        $this->_multiplex->procesarActividad(($bPaqProcesados || (count($this->_accionesPendientes) > 0)) ? 0 : 1);

        // Verificar posible desconexión de la base de datos
        if (is_null($this->_db)) {
            if (count($this->_accionesPendientes) > 0) {
                $this->_log->output('INFO: falta conexión DB y hay '.count($this->_accionesPendientes).' acciones pendientes.');
                if ($this->DEBUG) {
                    foreach ($this->_accionesPendientes as $accion)
                        $this->_volcarAccion($accion);
                }
            }
            $this->_log->output('INFO: intentando volver a abrir conexión a DB...');
            if (!$this->_iniciarConexionDB()) {
                $this->_log->output('ERR: no se puede restaurar conexión a DB, se espera...');

                $t1 = time();
                do {
                    $this->_multiplex->procesarPaquetes();
                    $this->_multiplex->procesarActividad(1);
                } while (time() - $t1 < 5);
            } else {
                $this->_log->output('INFO: conexión a DB restaurada, se reinicia operación normal.');
                $this->_configDB->setDBConn($this->_db);
            }
        } else {
            $this->_procesarUnaAccion();
        }

        return TRUE;
    }

    private function _procesarUnaAccion()
    {
        try {
            if (!$this->_finalizandoPrograma) {
                // Verificar si se ha cambiado la configuración
                $this->_verificarCambioConfiguracion();

                // Verificar si hay que refrescar agentes disponibles
                $this->_verificarActualizacionAgentes();
            }

            /* Por ahora se intenta ejecutar todas las operaciones, incluso
             * si se intenta finalizar el programa. */
            if (count($this->_accionesPendientes) > 0) {
                $this->_db->beginTransaction();

                if ($this->DEBUG) {
                    $this->_volcarAccion($this->_accionesPendientes[0]);
                }
                $eventos = call_user_func_array(
                    $this->_accionesPendientes[0][0],
                    $this->_accionesPendientes[0][1]);
                if ($this->DEBUG) {
                    $this->_log->output('DEBUG: acción ejecutada correctamente.');
                }

                array_shift($this->_accionesPendientes);
                $this->_lanzarEventos($eventos);

                $this->_db->commit();
            }
        } catch (PDOException $e) {
            if ($this->DEBUG || !esReiniciable($e)) {
                $this->_log->output('ERR: '.__METHOD__.
                    ': no se puede realizar operación de base de datos: '.
                    implode(' - ', $e->errorInfo));
                $this->_log->output("ERR: traza de pila: \n".$e->getTraceAsString());
            }
            if ($e->errorInfo[0] == 'HY000' && $e->errorInfo[1] == 2006) {
                // Códigos correspondientes a pérdida de conexión de base de datos
                $this->_log->output('WARN: '.__METHOD__.
                    ': conexión a DB parece ser inválida, se cierra...');
                $this->_db = NULL;
            } else {
                $this->_db->rollBack();
            }
        }
    }

    private function _volcarAccion(&$accion)
    {
        $this->_log->output('DEBUG: acción pendiente '.$accion[0][1].': '.print_r($accion[1], TRUE));
    }

    private function _lanzarEventos(&$eventos)
    {
        foreach ($eventos as $ev) {
            list($target, $msg, $args) = $ev;
            call_user_func_array(
                array($this->_tuberia, 'msg_'.$target.'_'.$msg),
                $args);
        }
    }

    public function limpiezaDemonio($signum)
    {
        // Mandar a cerrar todas las conexiones activas
        $this->_multiplex->finalizarServidor();

        // Se intentan evacuar acciones pendientes
        if (count($this->_accionesPendientes) > 0)
            $this->_log->output('WARN: todavía hay '.count($this->_accionesPendientes).' acciones pendientes.');
        $t1 = time();
        while (time() - $t1 < 10 && !is_null($this->_db) &&
            count($this->_accionesPendientes) > 0) {
            $this->_procesarUnaAccion();

            // No se hace I/O y por lo tanto no se lanzan eventos
        }
        if (count($this->_accionesPendientes) > 0)
            $this->_log->output('ERR: no se pueden evacuar las siguientes acciones: '.
                print_r($this->_accionesPendientes, TRUE));

        // Desconectarse de la base de datos
        $this->_configDB = NULL;
        if (!is_null($this->_db)) {
            $this->_log->output('INFO: desconectando de la base de datos...');
            $this->_db = NULL;
        }
    }

    private function _verificarCambioConfiguracion()
    {
        $iTimestamp = time();
        if ($iTimestamp - $this->_iTimestampUltimaRevisionConfig > 3) {
            $this->_configDB->leerConfiguracionDesdeDB();
            $listaVarCambiadas = $this->_configDB->listaVarCambiadas();
            if (count($listaVarCambiadas) > 0) {
                foreach ($listaVarCambiadas as $k) {
                    if (in_array($k, array('asterisk_asthost', 'asterisk_astuser', 'asterisk_astpass'))) {
                        $this->_tuberia->msg_AMIEventProcess_actualizarConfig(
                            'asterisk_cred', array(
                                $this->_configDB->asterisk_asthost,
                                $this->_configDB->asterisk_astuser,
                                $this->_configDB->asterisk_astpass,
                            ));
                    } elseif (in_array($k, array('asterisk_duracion_sesion',
                        'dialer_llamada_corta', 'dialer_tiempo_contestar',
                        'dialer_debug', 'dialer_allevents'))) {
                        $this->_tuberia->msg_AMIEventProcess_actualizarConfig(
                            $k, $this->_configDB->$k);
                    }
                }

                if (in_array('dialer_debug', $listaVarCambiadas))
                    $this->DEBUG = $this->_configDB->dialer_debug;
                $this->_configDB->limpiarCambios();
            }
            $this->_iTimestampUltimaRevisionConfig = $iTimestamp;
        }
    }

    /* Mandar a los otros procedimientos la información que no pueden leer
     * directamente porque no tienen conexión de base de datos. */
    private function _verificarActualizacionAgentes()
    {
        $iTimestamp = time();
        if ($iTimestamp - $this->_iTimestampActualizacion >= 5 * 60) {
            $this->_actualizarInformacionRemota_agentes();

            $this->_iTimestampActualizacion = $iTimestamp;
        }
    }

    function _actualizarInformacionRemota_agentes()
    {
        $eventos = $this->_requerir_nuevaListaAgentes();
        $this->_lanzarEventos($eventos);
    }

    /**************************************************************************/

    public function msg_requerir_nuevaListaAgentes($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        $this->_log->output("INFO: $sFuente requiere refresco de lista de agentes");
        array_push($this->_accionesPendientes, array(
            array($this, '_requerir_nuevaListaAgentes'),    // callable
            array(),    // params
        ));
    }

    public function msg_finalizando($sFuente, $sDestino, $sNombreMensaje, $iTimestamp, $datos)
    {
        $this->_log->output('INFO: recibido mensaje de finalización...');
        $this->_finalizandoPrograma = TRUE;

        // TODO: mover a manejador de finalsql cuando se migre a esta clase
        $this->_tuberia->msg_HubProcess_finalizacionTerminada();
    }

    /**************************************************************************/

    // Mandar a AMIEventProcess una lista actualizada de los agentes activos
    private function _requerir_nuevaListaAgentes()
    {
        // El ORDER BY del query garantiza que estatus A aparece antes que I
        $recordset = $this->_db->query(
            'SELECT id, number, name, estatus, type FROM agent ORDER BY number, estatus');
        $lista = array(); $listaNum = array();
        foreach ($recordset as $tupla) {
            if (!in_array($tupla['number'], $listaNum)) {
                $lista[] = array(
                    'id'        =>  $tupla['id'],
                    'number'    =>  $tupla['number'],
                    'name'      =>  $tupla['name'],
                    'estatus'   =>  $tupla['estatus'],
                    'type'      =>  $tupla['type'],
                );
                $listaNum[] = $tupla['number'];
            }
        }

        /* Leer el estado de las banderas de activación de eventos de las colas
         * a partir del archivo de configuración. El código a continuación
         * depende de la existencia de queues_additional.conf de una instalación
         * FreePBX, y además asume Asterisk 11 o inferior. Se debe modificar
         * esto cuando se migre a una versión superior de Asterisk que siempre
         * emite los eventos. */
        $queueflags = array();
        if (file_exists('/etc/asterisk/queues_additional.conf')) {
            $queue = NULL;
            foreach (file('/etc/asterisk/queues_additional.conf') as $s) {
                $regs = NULL;
                if (preg_match('/^\[(\S+)\]/', $s, $regs)) {
                    $queue = $regs[1];
                    $queueflags[$queue]['eventmemberstatus'] = FALSE;
                    $queueflags[$queue]['eventwhencalled'] = FALSE;
                } elseif (preg_match('/^(\w+)\s*=\s*(.*)/', trim($s), $regs)) {
                    if (in_array($regs[1], array('eventmemberstatus', 'eventwhencalled'))) {
                        $queueflags[$queue][$regs[1]] = in_array($regs[2], array('yes', 'true', 'y', 't', 'on', '1'));
                    } elseif ($regs[1] == 'member' && (stripos($regs[2], 'SIP/') === 0 || stripos($regs[2], 'IAX2/') === 0)) {
                        $this->_log->output('WARN: '.__METHOD__.': agente estático '.
                            $regs[2].' encontrado en cola '.$queue.' - puede causar problemas.');
                    }
                }
            }
        }

        // Mandar el recordset a AMIEventProcess como un mensaje
        return array(
            array('AMIEventProcess', 'nuevaListaAgentes', array($lista, $queueflags)),
        );
    }

}
