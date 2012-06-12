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
require_once('AbstractProcess.class.php');
require_once 'DB.php';
require_once('Predictivo.class.php');
require_once('GestorLlamadasEntrantes.class.php');

require_once 'DialerServer.class.php';
require_once 'AMIClientConn.class.php';

// Número mínimo de muestras para poder confiar en predicciones de marcador
define('MIN_MUESTRAS', 10);

// Número de llamadas por campaña para las que se lleva la cuenta de cuánto tardó en ser contestada
define('NUM_LLAMADAS_HISTORIAL_CONTESTADA', 20);

// Enumeración para informar fuente de conexión Asterisk
define('ASTCONN_CRED_DESCONOCIDO', 0);  // No se ha seteado todavía
define('ASTCONN_CRED_CONF', 1);         // Credenciales provienen de manager.conf
define('ASTCONN_CRED_DB', 2);           // Credenciales provienen de DB

class DialerProcess extends AbstractProcess
{
    private $oMainLog;      // Log abierto por framework de demonio
    //private $_sRutaDB;      // Ruta a la base de datos sqlite3
    private $_dbHost;
    private $_dbUser;
    private $_dbPass;
    private $_dbConn;       // Conexión PEAR a la base de datos

    private $_sAsteriskHost;
    private $_sAsteriskUser;
    private $_sAsteriskPass;
    private $_astConn;      // Conexión al Asterisk Manager
    
    private $_momentoUltimaConnAsterisk;	// Timestamp de cuando se conectó por última vez al Asterisk
    private $_intervaloDesconexion;			// Intervalo de desconexión regular, o 0 para persistente (por omisión)
    
    private $_infoLlamadas;                 // Información sobre las campañas leídas, por iteración
    private $_iUmbralLlamadaCorta;          // Umbral por debajo del cual llamada es corta
    private $_bSobrecolocarLlamadas = FALSE;// VERDADERO si se intenta compensar por baja contestación mediante
                                            // colocar más llamadas de las predichas por estado de cola. 

    private $_oGestorEntrante;      // Gestor de llamadas entrantes
    
    private $_plantillasMarcado;
    
    // VERDADERO si tiene campo calls.agent para llamadas agendadas
    private $_tieneCallsAgent = FALSE;
    
    // VERDADERO si existe tabla asterisk.trunks y se deben buscar troncales allí
    private $_existeTrunksFPBX = FALSE;
    
    // VERDADERO si tiene campo calls.failure_cause_txt para registro de fallo
    private $_tieneCallsFailureCause = FALSE;

    // VERDADERO si se tiene campo calls.datetime_originate para registro de Originate
    private $_tieneCallsDatetimeOriginate = FALSE;
    
    private $_fuenteCredAst = ASTCONN_CRED_DESCONOCIDO;

    // Estimación de la versión de Asterisk que se usa
    private $_asteriskVersion = array(1, 4, 0, 0);

    var $DEBUG = FALSE;
    var $REPORTAR_TODO = FALSE;
    var $_iUltimoDebug = NULL;
    
    private $_agentContext = 'llamada_agendada';    // TODO: volver parametrizable
/*
[llamada_agendada]
exten => _X.,1,NoOP("NUMERO DE AGENTE -------------- ${EXTEN}")
exten => _X.,n,Dial(Agent/${EXTEN},300,t)
 */

    // Variables para soportar conexiones entrantes ECCP
    private $_dialSrv = NULL;
    private $_momentoUltimaRevisionCampanias = 0;
    /* Lista de agentes indexada por canal de agente (Agent/9000). Para cada
     * agente se mantienen estado, véase método generarEstadoInicialAgente() 
     */
    private $_infoAgentes = array(); 

    // Lista de intentos de login de agente. TODO: estudiar combinación con _infoAgentes
    private $_intentoLoginAgente = array();

    function inicioPostDemonio($infoConfig, &$oMainLog)
    {
        $bContinuar = TRUE;
        $this->_oGestorEntrante = NULL;
        $this->_plantillasMarcado = array();

        // Guardar referencias al log del programa
        $this->oMainLog =& $oMainLog;
        
        // Interpretar la configuración del demonio
        $this->interpretarParametrosConfiguracion($infoConfig);

        // TODO: interfaz y puerto configurables
        $this->_dialSrv = new DialerServer('tcp://0.0.0.0:20005', $oMainLog);
        $this->_dialSrv->setDialerProcess($this);

        if ($bContinuar) $bContinuar = $this->iniciarConexionBaseDatos();
        $infoConfigDB = $this->leerConfiguracionDesdeDB();
        $this->aplicarConfiguracionDB($infoConfigDB);
        if ($bContinuar) $bContinuar = $this->iniciarConexionAsterisk();

        // Cerrar DB si falla la conexión al Asterisk Manager
        if (!$bContinuar && !is_null($this->_dbConn)) {        	
            $this->_dbConn->disconnect();
            $this->_dbConn = NULL;
        }

        if ($bContinuar && !is_null($this->_dbConn)) {
        	// Recuperarse de cualquier fin anormal anterior
            $this->_dbConn->query('DELETE FROM current_calls WHERE 1');
            $this->_dbConn->query('DELETE FROM current_call_entry WHERE 1');

            // Verificar si la DB puede registrar agente para llamada agendada
            $recordset =& $this->_dbConn->query('DESCRIBE calls');
            if (DB::isError($recordset)) {
                $oLog->output("ERR: no se puede consultar soporte de agente para llamada agendada - ".$recordset->getMessage());
            } else {
                while ($tuplaCampo = $recordset->fetchRow(DB_FETCHMODE_OBJECT)) {
                    if ($tuplaCampo->Field == 'agent') $this->_tieneCallsAgent = TRUE;
                    if ($tuplaCampo->Field == 'failure_cause') $this->_tieneCallsFailureCause = TRUE;
                    if ($tuplaCampo->Field == 'datetime_originate') $this->_tieneCallsDatetimeOriginate = TRUE;
                }
                $this->oMainLog->output('INFO: sistema actual '.
                    ($this->_tieneCallsAgent ? 'sí puede' : 'no puede').
                    ' registrar agente para campaña agendada.');
                $this->oMainLog->output('INFO: sistema actual '.
                    ($this->_tieneCallsFailureCause ? 'sí puede' : 'no puede').
                    ' registrar causa extendida de fallo de llamada.');
                $this->oMainLog->output('INFO: sistema actual '.
                    ($this->_tieneCallsDatetimeOriginate ? 'sí puede' : 'no puede').
                    ' registrar timestamp de Originate.');
            }
    
            $this->_detectarTablaTrunksFPBX();
        }

        // Iniciar gestor de llamadas entrantes
        if ($bContinuar) {
            $this->_oGestorEntrante = new GestorLlamadasEntrantes(
                $this->_astConn, $this->_dbConn, $this->oMainLog);
            $this->_oGestorEntrante->setDialerProcess($this);
            $this->_oGestorEntrante->setDialSrv($this->_dialSrv);
            $this->_oGestorEntrante->DEBUG = $this->DEBUG;
        }

        $this->_iUltimoDebug = time();
        return $bContinuar;
    }

    /**
     * Procedimiento que detecta la existencia de la tabla asterisk.trunks. Si
     * existe, la información de troncales está almacenada allí, y no en la
     * tabla globals. Esto se cumple en versiones recientes de FreePBX.
     * 
     * @return void
     */
    private function _detectarTablaTrunksFPBX()
    {
        $dbConn = $this->_abrirConexionFreePBX();
        if (is_null($dbConn)) return;

        $item =& $dbConn->getOne("SHOW TABLES LIKE 'trunks'");
        if (DB::isError($item)) {
            $this->oMainLog->output("ERR: al consultar tabla de troncales: ".$item->getMessage());
        } elseif ($item != 'trunks') {
        	// Probablemente error de que asterisk.trunks no existe
            $this->oMainLog->output("INFO: tabla asterisk.trunks no existe, se asume FreePBX viejo.");
        } else {
        	// asterisk.trunks existe
            $this->oMainLog->output("INFO: tabla asterisk.trunks sí existe, se asume FreePBX reciente.");
            $this->_existeTrunksFPBX = TRUE;
        }
        
        $dbConn->disconnect();
    }

    /* Interpretar la configuración cuyo hash se indica en el parámetro. Los 
     * parámetros de la conexión a la base de datos se recogen, pero no se usan 
     * en este punto. Lo mismo con los parámetros de conexión al Asterisk Manager. 
     */
    private function interpretarParametrosConfiguracion(&$infoConfig)
    {
        $sRutaDB = NULL;
        
        // Recoger los parámetros para la conexión a la base de datos
        $this->_dbHost = 'localhost';
        $this->_dbUser = 'asterisk';
        $this->_dbPass = 'asterisk';
        if (isset($infoConfig['database']) && isset($infoConfig['database']['dbhost'])) {
        	$this->_dbHost = $infoConfig['database']['dbhost'];
            $this->oMainLog->output('Usando host de base de datos: '.$this->_dbHost);
        } else {
        	$this->oMainLog->output('Usando host (por omisión) de base de datos: '.$this->_dbHost);
        }
        if (isset($infoConfig['database']) && isset($infoConfig['database']['dbuser']))
            $this->_dbUser = $infoConfig['database']['dbuser'];
        if (isset($infoConfig['database']) && isset($infoConfig['database']['dbpass']))
            $this->_dbPass = $infoConfig['database']['dbpass'];
    }

    // Iniciar la conexión a la base de datos con los parámetros recogidos por
    // interpretarParametrosConfiguracion().
    private function iniciarConexionBaseDatos()
    {
        // La siguiente línea asume que el programa se conecta a una base sqlite3
        //$sConnStr = 'sqlite3:///'.$this->_sRutaDB;
        $sConnStr = 'mysql://'.$this->_dbUser.':'.$this->_dbPass.'@'.$this->_dbHost.'/call_center';
        $dbConn =  DB::connect($sConnStr);
        if (DB::isError($dbConn)) {
            $this->oMainLog->output("FATAL: no se puede conectar a DB - ".($dbConn->getMessage()));
            return FALSE;
        } else {
            $dbConn->setOption('autofree', TRUE);
            $this->_dbConn = $dbConn;
            $this->_dialSrv->setDbConn($this->_dbConn);
            return TRUE;
        }
    } 

	// Leer la configuración de la base de datos, validando los valores, pero sin comparar contra
	// el estado actual de configuración del programa
	private function leerConfiguracionDesdeDB()
	{
		$listaConfig =& $this->_dbConn->getAssoc('SELECT config_key, config_value FROM valor_config');
		if (DB::isError($listaConfig)) {
			$this->oMainLog->output('ERR: no se puede leer configuración actual - '.$listaConfig->getMessage());
			return NULL;
		}
		$infoConfig = array(
			'asterisk'	=>	array(
				'asthost'	=>	'127.0.0.1',
				'astuser'	=>	'',
				'astpass'	=>	'',
				'duracion_sesion' => 0,
			),
			'dialer'	=>	array(
				'llamada_corta'	=>	10,
				'tiempo_contestar' => 8,
				'debug'			=>	0,
				'allevents'		=>	0,
                'overcommit'    =>  0,
                'qos'           =>  0.97,
			),
		);
		foreach ($infoConfig as $seccion => $infoSeccion) {
			foreach ($infoSeccion as $clave => $valorOmision) {
				$sClaveDB = "$seccion.$clave";
				if (isset($listaConfig[$sClaveDB])) {
					$infoConfig[$seccion][$clave] = $listaConfig[$sClaveDB]; 
				}
			}
		}
        
        if (($infoConfig['asterisk']['asthost'] == '127.0.0.1' || $infoConfig['asterisk']['asthost'] == 'localhost') &&
            $infoConfig['asterisk']['astuser'] == '' && $infoConfig['asterisk']['astpass'] == '') {
            // Base de datos no tiene usuario explícito, se lee de manager.conf
            if ($this->_fuenteCredAst != ASTCONN_CRED_CONF)
                $this->oMainLog->output("INFO: AMI login no se ha configurado, se busca en configuración de Asterisk...");
            $amiConfig = $this->leerConfigManager();
            if (is_array($amiConfig)) {
                if ($this->_fuenteCredAst != ASTCONN_CRED_CONF)
                    $this->oMainLog->output("INFO: usando configuración de Asterisk para AMI login.");
                $infoConfig['asterisk']['astuser'] = $amiConfig[0];
                $infoConfig['asterisk']['astpass'] = $amiConfig[1];
                $this->_fuenteCredAst = ASTCONN_CRED_CONF;
            }
        } else {
            if ($this->_fuenteCredAst != ASTCONN_CRED_DB)
                $this->oMainLog->output("INFO: AMI login configurado en DB...");
            $this->_fuenteCredAst = ASTCONN_CRED_DB;
        }
		return $infoConfig;
	}
    
    // Leer el estado de /etc/asterisk/manager.conf y obtener el primer usuario que puede usar el dialer.
    // Devuelve NULL en caso de error, o tupla user,password para conexión en localhost.
    private function leerConfigManager()
    {
    	$sNombreArchivo = '/etc/asterisk/manager.conf';
        if (!file_exists($sNombreArchivo)) {
        	$this->oMainLog->output("WARN: $sNombreArchivo no se encuentra.");
            return NULL;
        }
        if (!is_readable($sNombreArchivo)) {
            $this->oMainLog->output("WARN: $sNombreArchivo no puede leerse por usuario de marcador.");
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
            $this->oMainLog->output("ERR: $sNombreArchivo no puede parsearse correctamente.");        	
        }
        return NULL;
    }

	// Aplicar la configuración leída desde la base de datos
	private function aplicarConfiguracionDB(&$infoConfig)
	{
		$bDesconectarAsterisk = FALSE;	// Seteado a TRUE si los parámetros Asterisk han cambiado

        // Recoger los parámetros para la conexión Asterisk
        if (isset($infoConfig['asterisk']) && isset($infoConfig['asterisk']['asthost'])) {
            if ($this->_sAsteriskHost != $infoConfig['asterisk']['asthost']) { 
            	$this->_sAsteriskHost = $infoConfig['asterisk']['asthost'];
            	$this->oMainLog->output("Usando host de Asterisk Manager: ".$this->_sAsteriskHost);
            	$bDesconectarAsterisk = TRUE;
            }
        } else {
        	if ($this->_sAsteriskHost != '127.0.0.1') {
        		$this->_sAsteriskHost = '127.0.0.1';
            	$this->oMainLog->output("Usando host (por omisión) de Asterisk Manager: ".$this->_sAsteriskHost);
            	$bDesconectarAsterisk = TRUE;
        	}
        }
        $sNuevoAsteriskUser = 
            (isset($infoConfig['asterisk']) && isset($infoConfig['asterisk']['astuser'])) 
            ? $infoConfig['asterisk']['astuser']
            : '';
        $sNuevoAsteriskPass = 
            (isset($infoConfig['asterisk']) && isset($infoConfig['asterisk']['astpass']))
            ? $infoConfig['asterisk']['astpass']
            : '';
        if ($this->_sAsteriskUser != $sNuevoAsteriskUser) $bDesconectarAsterisk = TRUE;
        if ($this->_sAsteriskPass != $sNuevoAsteriskPass) $bDesconectarAsterisk = TRUE;
        $this->_sAsteriskUser = $sNuevoAsteriskUser;
        $this->_sAsteriskPass = $sNuevoAsteriskPass;

		// Recoger parámetro de tiempo de desconexión
		$bSet = isset($this->_intervaloDesconexion);
		$this->_intervaloDesconexion = 0;
		if (isset($infoConfig['asterisk']) && isset($infoConfig['asterisk']['duracion_sesion'])) {
			$regs = NULL;
			if (ereg('^[[:space:]]*([[:digit:]]+)[[:space:]]*$', $infoConfig['asterisk']['duracion_sesion'], $regs)) {
				$this->_intervaloDesconexion = $regs[1];
                if (!$bSet) $this->oMainLog->output("Usando duración de sesión Asterisk de : ".$this->_intervaloDesconexion." segundos.");
			} else {
            	if (!$bSet) {
	            	$this->oMainLog->output("ERR: valor de ".$infoConfig['asterisk']['duracion_sesion']." no es válido para duración de sesión Asterisk.");
    	            $this->oMainLog->output("Usando duración de sesión Asterisk (por omisión): ".$this->_intervaloDesconexion." segundos.");
            	}
			}
        } else {
        	if (!$bSet) $this->oMainLog->output("Usando duración de sesión Asterisk (por omision): ".$this->_intervaloDesconexion." segundos.");
		}

		// Recoger parámetro de tiempo de contestado
		$bSet = isset($this->_iTiempoContestacion);
		$this->_iTiempoContestacion = 8;
        if (isset($infoConfig['dialer']) && isset($infoConfig['dialer']['tiempo_contestar'])) {
            $regs = NULL;
            if (ereg('^[[:space:]]*([[:digit:]]+)[[:space:]]*$', $infoConfig['dialer']['tiempo_contestar'], $regs)) {
                $this->_iTiempoContestacion = $regs[1];
                if (!$bSet) $this->oMainLog->output("Usando tiempo de contestado (inicial) de : ".$this->_iTiempoContestacion." segundos.");
            } else {
            	if (!$bSet) {
	            	$this->oMainLog->output("ERR: valor de ".$infoConfig['dialer']['tiempo_contestar']." no es válido para tiempo de contestado (inicial).");
    	            $this->oMainLog->output("Usando tiempo de contestado (inicial) (por omisión): ".$this->_iTiempoContestacion." segundos.");
            	}
            }
        } else {
        	if (!$bSet) $this->oMainLog->output("Usando tiempo de contestado (inicial) (por omision): ".$this->_iTiempoContestacion." segundos.");
        }

        // Recoger parámetro de llamada corta
        $bUmbralSet = isset($this->_iUmbralLlamadaCorta);
        $this->_iUmbralLlamadaCorta = 10;
        if (isset($infoConfig['dialer']) && isset($infoConfig['dialer']['llamada_corta'])) {
            $regs = NULL;
            if (ereg('^[[:space:]]*([[:digit:]]+)[[:space:]]*$', $infoConfig['dialer']['llamada_corta'], $regs)) {
                $this->_iUmbralLlamadaCorta = $regs[1];
                if (!$bUmbralSet) $this->oMainLog->output("Usando umbral de llamada corta: ".$this->_iUmbralLlamadaCorta." segundos.");
            } else {
            	if (!$bUmbralSet) {
	            	$this->oMainLog->output("ERR: valor de ".$infoConfig['dialer']['llamada_corta']." no es válido para umbral de llamada corta.");
    	            $this->oMainLog->output("Usando umbral de llamada corta (por omisión): ".$this->_iUmbralLlamadaCorta." segundos.");
            	}
            }
        } else {
        	if (!$bUmbralSet) $this->oMainLog->output("Usando umbral de llamada corta (por omisión): ".$this->_iUmbralLlamadaCorta." segundos.");
        }
        
        // Recoger parámetro de porcentaje de llamadas atendidas con predicción.
        $bUmbralSet = isset($this->_iPorcentajeAtencion);
        $this->_iPorcentajeAtencion = 0.97;
        if (isset($infoConfig['dialer']) && isset($infoConfig['dialer']['qos'])) {
            $regs = NULL;
            if (is_numeric($infoConfig['dialer']['qos']) && $infoConfig['dialer']['qos'] > 0 && $infoConfig['dialer']['qos'] < 1) {
                $this->_iPorcentajeAtencion = (float)$infoConfig['dialer']['qos'];
                if (!$bUmbralSet) $this->oMainLog->output("Usando porcentaje de atención: ".sprintf('%.1f %%', $this->_iPorcentajeAtencion * 100.0));
            } else {
                if (!$bUmbralSet) {
                    $this->oMainLog->output("ERR: valor de ".$infoConfig['dialer']['qos']." no es válido para porcentaje de atención.");
                    $this->oMainLog->output("Usando porcentaje de atención (por omisión): ".sprintf('%.1f %%', $this->_iPorcentajeAtencion * 100.0));
                }
            }
        } else {
            if (!$bUmbralSet) $this->oMainLog->output("Usando porcentaje de atención (por omisión): ".sprintf('%.1f %%', $this->_iPorcentajeAtencion * 100.0));
        }
        
        // Recoger estado de sobrecolocar llamadas
        $bSobreColocar = $this->_bSobrecolocarLlamadas;
        $this->_bSobrecolocarLlamadas = FALSE;
        if (isset($infoConfig['dialer']) && isset($infoConfig['dialer']['overcommit'])) {
            $this->_bSobrecolocarLlamadas = $infoConfig['dialer']['overcommit'] ? TRUE : FALSE;
            if (!$bSobreColocar && $this->_bSobrecolocarLlamadas) 
                $this->oMainLog->output("Sobre-colocación de llamadas está ACTIVADA.");
            if ($bSobreColocar && !$this->_bSobrecolocarLlamadas) 
                $this->oMainLog->output("Sobre-colocación de llamadas está DESACTIVADA.");
        }
        
        // Recoger nivel de depuración
        $bDebugSet = isset($this->DEBUG);
        $this->DEBUG = FALSE;
        if (isset($infoConfig['dialer']) && isset($infoConfig['dialer']['debug'])) {
        	$this->DEBUG = $infoConfig['dialer']['debug'] ? TRUE : FALSE;
        	if (!$bDebugSet && $this->DEBUG) $this->oMainLog->output("Información de depuración está ACTIVADA.");
        }
        if (!is_null($this->_oGestorEntrante)) $this->_oGestorEntrante->DEBUG = $this->DEBUG;        
        $bDebugSet = isset($this->REPORTAR_TODO);
        $this->REPORTAR_TODO = FALSE;
        if (isset($infoConfig['dialer']) && isset($infoConfig['dialer']['allevents'])) {
        	$this->REPORTAR_TODO = $infoConfig['dialer']['allevents'] ? TRUE : FALSE;
        	if (!$bDebugSet && $this->REPORTAR_TODO) $this->oMainLog->output("Se reportará información de todos los eventos Asterisk.");
        }
        
        if ($bDesconectarAsterisk && !is_null($this->_astConn)) {
            $this->oMainLog->output('INFO: Cambio de configuración, desconectando de sesión previa de Asterisk...');
            $this->_astConn->disconnect();
            $this->_astConn = NULL;            
        }
	}

    // Iniciar la conexión al Asterisk Manager
    private function iniciarConexionAsterisk()
    {
        if (!is_null($this->_astConn)) {
            $this->oMainLog->output('INFO: Desconectando de sesión previa de Asterisk...');
            $this->_astConn->disconnect();
            $this->_astConn = NULL;            
        }
        $astman = new AMIClientConn($this->_dialSrv, $this->oMainLog);
        $this->_momentoUltimaConnAsterisk = time();

        $this->oMainLog->output('INFO: Iniciando sesión de control de Asterisk...');
        if (!$astman->connect(
                $this->_sAsteriskHost, 
                $this->_sAsteriskUser,
                $this->_sAsteriskPass)) {
            $this->oMainLog->output("FATAL: no se puede conectar a Asterisk Manager\n");
            return FALSE;
        } else {
            // Averiguar la versión de Asterisk que se usa
            $this->_asteriskVersion = array(1, 4, 0, 0);
            $r = $astman->CoreSettings(); // Sólo disponible en Asterisk >= 1.6.0
            if ($r['Response'] == 'Success' && isset($r['AsteriskVersion'])) {
                $this->_asteriskVersion = explode('.', $r['AsteriskVersion']);
                $this->oMainLog->output("INFO: CoreSettings reporta Asterisk ".implode('.', $this->_asteriskVersion));
            } else {
                $this->oMainLog->output("INFO: no hay soporte CoreSettings en Asterisk Manager, se asume Asterisk 1.4.x.");
            }

            // Instalación de los manejadores de eventos
            if ($this->DEBUG && $this->REPORTAR_TODO)
                $astman->add_event_handler('*', array($this, 'OnDefault'));
            $astman->add_event_handler('Newchannel', array($this, 'OnNewchannel'));
            $astman->add_event_handler('Dial', array($this, 'OnDial'));
            $astman->add_event_handler('Join', array($this, 'OnJoin'));
            $astman->add_event_handler('Link', array($this, 'OnLink'));
            $astman->add_event_handler('Bridge', array($this, 'OnLink')); // Visto en Asterisk 1.6.2.x
            $astman->add_event_handler('Unlink', array($this, 'OnUnlink'));
            $astman->add_event_handler('Hangup', array($this, 'OnHangup'));
            $astman->add_event_handler('OriginateResponse', array($this, 'OnOriginateResponse'));
            $astman->add_event_handler('Agentlogin', array($this, 'OnAgentlogin'));
            $astman->add_event_handler('Agentlogoff', array($this, 'OnAgentlogoff'));

            $this->_astConn = $astman;
            if (!is_null($this->_oGestorEntrante)) { 
                $this->_oGestorEntrante->setAstConn($this->_astConn);
            }
            
            // Informar al servidor de conexiones de la conexión Asterisk
            $this->_dialSrv->setAstConn($this->_astConn);
            return TRUE;
        }
    }

    function _leerCampania($idCampania)
    {
    	$sPeticionCampania = 
            'SELECT id, name, trunk, context, queue, max_canales, num_completadas, '.
                'promedio, desviacion, retries, datetime_init, datetime_end, daytime_init, daytime_end '.
            'FROM campaign '.
            'WHERE id = ? ';
        $tupla = $this->_dbConn->getRow($sPeticionCampania, array($idCampania), DB_FETCHMODE_OBJECT);
        if (!DB::isError($tupla)) { $tupla->variancia = $tupla->desviacion * $tupla->desviacion; }
        return DB::isError($tupla) ? NULL : $tupla;
    }

    // Ejecutar la revisión periódica de las llamadas pendientes por timbrar
    function procedimientoDemonio()
    {
        // Si no se tiene una conexión a la base de datos, se intenta reabrir DB
        if (is_null($this->_dbConn)) {
        	$bContinuar = $this->iniciarConexionBaseDatos();
            if (!$bContinuar) {
            	// Todavía no se recupera la base de datos, se espera...
                $this->oMainLog->output("ERR: no se puede restaurar conexión a DB, se espera...");
                usleep(5000000);
                return TRUE;
            } else {
            	$this->_oGestorEntrante->setDBConn($this->_dbConn);
                $this->oMainLog->output("INFO: conexión a DB restaurada, se reinicia operación normal.");
            }
        }

        $iTimestamp = time();

        // Revisar las campañas cada 3 segundos
        if ($this->_momentoUltimaRevisionCampanias + 3 <= time()) {
            $this->_momentoUltimaRevisionCampanias = time();

            $sFecha = date('Y-m-d', $iTimestamp);
            $sHora = date('H:i:s', $iTimestamp);
            $sPeticionCampanias = 
                'SELECT id, name, trunk, context, queue, max_canales, num_completadas, '.
                    'promedio, desviacion, retries, datetime_init, datetime_end, daytime_init, daytime_end '.
                'FROM campaign '.
                'WHERE datetime_init <= ? '.
                    'AND datetime_end >= ? '.
                    'AND estatus = "A" '.
                    'AND ('.
                        '(daytime_init < daytime_end AND daytime_init <= ? AND daytime_end > ?) '.
                        'OR (daytime_init > daytime_end AND (? < daytime_init OR daytime_end < ?)))';
            $recordset = $this->_dbConn->query(
                $sPeticionCampanias, 
                array($sFecha, $sFecha, $sHora, $sHora, $sHora, $sHora));
            if (DB::isError($recordset)) {
                $sMensajeDB = $recordset->getMessage();
                $this->oMainLog->output("ERR: no se puede leer lista de campañas - $sMensajeDB");
                if (strstr($sMensajeDB, 'no database selected')) {
                	// Este es el error genérico que ocurre cuando se invalida la conexión a DB
                    $this->oMainLog->output("WARN: conexión a DB parece ser inválida, se cierra...");
                    if (!is_null($this->_dbConn)) {
                        $this->_dbConn->disconnect();
                        $this->_dbConn = NULL;
                    }
                }
                usleep(1000000);
                return TRUE;                
            } else {
                // Verificar si se tiene que actualizar la configuración
                $infoConfigDB = $this->leerConfiguracionDesdeDB();
                if (!is_null($infoConfigDB)) {
                	$this->aplicarConfiguracionDB($infoConfigDB);
                }
                
                if (is_null($this->_astConn)) {
                	$this->iniciarConexionAsterisk();
                } elseif ($this->_intervaloDesconexion > 0 && time() - $this->_momentoUltimaConnAsterisk >= $this->_intervaloDesconexion) {
    				$this->oMainLog->output("INFO: sesión de Asterisk excede {$this->_intervaloDesconexion} segundos, se desconecta...");
                	$this->iniciarConexionAsterisk();
                }
                if (!$this->_oGestorEntrante->isAstConnValid()) {
                	// La conexión al Asterisk se perdió en medio de proceso de llamadas 
                    // entrantes.                
                    $this->iniciarConexionAsterisk();
                }
    
                if (!is_null($this->_astConn)) {
                    $this->_oGestorEntrante->actualizarCacheAgentes();
                    
                    $listaCampanias = array();
                    while ($infoCampania = $recordset->fetchRow(DB_FETCHMODE_OBJECT)) {
                        $infoCampania->variancia = NULL;
                        if (!is_null($infoCampania->desviacion) && is_numeric($infoCampania->desviacion))
                            $infoCampania->variancia = $infoCampania->desviacion * $infoCampania->desviacion;
                        $listaCampanias[$infoCampania->id] = $infoCampania;
                    }
    
                    // Preparar la información a asignar a datos de app en astman
                    if (!is_array($this->_infoLlamadas)) $this->_infoLlamadas = array();
                    if (!isset($this->_infoLlamadas['historial_contestada'])) 
                        $this->_infoLlamadas['historial_contestada'] = array();
                    $this->_infoLlamadas['campanias'] = $listaCampanias;
                    if (!isset($this->_infoLlamadas['llamadas'])) $this->_infoLlamadas['llamadas'] = array();
                
                    // Agregar llamadas para todas las campañas activas
                    $bLlamadasAgregadas = FALSE;
                    foreach ($this->_infoLlamadas['campanias'] as $infoCampania) {
                        $bLlamadasAgregadas = $this->actualizarLlamadasCampania($infoCampania) || $bLlamadasAgregadas;
                    }
                    
                    // Remover del historial de tiempo de contestado, las campañas que ya no
                    // se están monitoreando
                    $listaCampaniasAusentes = array_diff(
                        array_keys($this->_infoLlamadas['historial_contestada']), 
                        array_keys($listaCampanias));
                    foreach ($listaCampaniasAusentes as $key) {
                    	unset($this->_infoLlamadas['historial_contestada'][$key]);
                    }
    
    /*
                    // Consumir todos los eventos de llamada durante 3 segundos
                    $iMaxEspera = 3;
                    $iTimestampInicioEspera = time();
                    $iEsperaActual = time() - $iTimestampInicioEspera;
                    while ($iEsperaActual <= $iMaxEspera) {
                        $this->_astConn->SetTimeout(1);
                        $r = $this->_astConn->wait_response(TRUE, FALSE, $iMaxEspera - $iEsperaActual);
                        if (is_null($r)) {
                            // Lo siguiente debería estar interno en AG_AsteriskManager
                            $metadata = stream_get_meta_data($this->_astConn->socket);
                            if (is_array($metadata) && !$metadata['timed_out']) {
                                $this->oMainLog->output("ERR: problema al esperar respuesta de Asterisk (en bucle de espera).");
                                $this->iniciarConexionAsterisk();
                                break;
                            }
                        }
                        $iEsperaActual = time() - $iTimestampInicioEspera;
                    }
                    if (!is_null($this->_astConn)) $this->_astConn->SetTimeout(10);
    */                
                } else {
                    $this->oMainLog->output("ERR: no se puede reconectar al Asterisk, esperando...");
                }
            }
    
            $this->_limpiarLlamadasViejasEspera();
        }

        if (!is_null($this->_astConn)) {
            /* El procedimiento principal del demonio será llamado sin demora 
             * repetidas veces mientras devuelva VERDADERO. El bucle estará ocupado
             * si hay paquetes pendientes por procesar, o si luego de que no hay
             * paquetes pendientes, se encuentra actividad en los sockets. */
            $bPendientesProcesados = $this->_dialSrv->procesarPaquetes();
            if (!$bPendientesProcesados) {
                if (!$this->_dialSrv->procesarActividad()) {
                    usleep(100000);
                }
            }
        } else {
            usleep(1000000);
        }

        // Si se habilita debug, se muestra estado actual de las llamadas
        if ($iTimestamp - $this->_iUltimoDebug > 30) {
        	$this->_iUltimoDebug = $iTimestamp;
            if ($this->DEBUG) {
            	$this->oMainLog->output("DEBUG: estado actual de campañas => ".print_r($this->_infoLlamadas, TRUE));
                $this->oMainLog->output("DEBUG: estado actual de agentes => ".print_r($this->_infoAgentes, TRUE));
            }
            $this->_listarCurrentCalls();
        }

        return TRUE;
    }

    private function _limpiarLlamadasViejasEspera()
    {
        // Remover llamadas viejas luego de 5 * 60 segundos de espera sin respuesta
        $listaClaves = array_keys($this->_infoLlamadas['llamadas']);
        foreach ($listaClaves as $k ) {
            $tupla = $this->_infoLlamadas['llamadas'][$k];
            if (is_null($tupla->OriginateEnd)) {
                $iEspera = time() - $tupla->OriginateStart;
                if ($iEspera > 5 * 60) {
                    $this->oMainLog->output("ERR:llamada $k espera respuesta desde hace $iEspera segundos, se elimina.");
                    $idCampania = $this->_infoLlamadas['llamadas'][$k]->id_campaign;
                    $infoCampania = $this->_leerCampania($idCampania);
                    if (!is_null($infoCampania)) {
                        // Marcar estado de fallo con esta llamada
                        $this->_infoLlamadas['llamadas'][$k]->status = 'Failure';
                        $result = $this->_dbConn->query(
                            'UPDATE calls SET status = ?, fecha_llamada = ?, start_time = NULL, end_time = NULL '.
                                'WHERE id_campaign = ? AND id = ?',
                            array('Failure', date('Y-m-d H:i:s'),
                                $infoCampania->id, $this->_infoLlamadas['llamadas'][$k]->id));
                        if (DB::isError($result)) {
                            $this->oMainLog->output(
                                "ERR: no se puede actualizar llamada con limpieza de llamadas perdidas ".
                                "[id_campaign=$infoCampania->id, id=".$this->_infoLlamadas['llamadas'][$k]->id."]".
                                $result->getMessage());
                        }
                    }
                    
                    unset($this->_infoLlamadas['llamadas'][$k]);
                }
            }
        }
    }

    private function generarEstadoInicialAgente()
    {
        return array(
            /*  Estado de la consola. Los valores posibles son 
                logged-out  No hay agente logoneado
                logging     Agente intenta autenticarse con la llamada
                logged-in   Agente fue autenticado y está logoneado en consola
             */
            'estado_consola'    =>  'logged-out',
    
            /* El número de la extensión interna que se logonea al agente. En estado
               logout la extensión es NULL 
             */
            'extension'         =>  NULL,
            
            /* El ID de la sesión de auditoría iniciada para este agente */
            'id_sesion'         =>  NULL,
            
            /* El ID del break en que se encuentre el agente, o NULL si no está en break */
            'id_break'          =>  NULL,
            
            /* El Uniqueid de la llamada que se usó para iniciar el login de agente */
            'Uniqueid'          =>  NULL,
            
            /* Info del hold en que se encuentra el agente, o NULL si no está en hold */
            'info_hold'         =>  NULL,

            /* Cuenta de pausas del agente. El agente debe estar pausado si 
             * distinto de cero. Por ahora se usa para break y para hold. */
            'num_pausas'        =>  0,
        );
    }


/*
- sea RESERVA segundos en el futuro

en actualización de llamadas
- listar todos los agentes de troncal, que tienen llamadas agendadas ahora, o hasta RESERVA segundos en el futuro, 
  que no se estén ya procesando
- quitar agentes que estén esperando respuesta una llamada agendada
- para cada agente, si no está en pausa, ponerlo en pausa.
- para cada agente de la cola, si no tiene llamadas agendadas ahora o en reserva, se saca de pausa
(meta: todos los agentes que tengan llamadas agendadas en RESERVA segundos, y no están esperando ya llamada, se reservan)
- para cada agente, 
- - listar sus llamadas agendadas AHORA, que no se estén ya procesando, en orden de número de reintentos y luego cronológico.
- - si hay alguna llamada, generarla.
- - si falla generación, y era la única llamada generable AHORA, y no hay ninguna llamada generable en RESERVA, se 
    quita al agente de pausa.

en OnOriginateResponse, si llamada era agendada
- ejecutar la redirección.
- si no hay más llamadas generables AHORA, y no hay ninguna llamada generable en RESERVA, se quita al agente de pausa.

en OnLink


*/

    /**
     * Procedimiento para obtener el número de segundos de reserva de una campaña
     */
    private function _getSegundosReserva($idCampaign)
    {
        return 30;	// TODO: volver configurable en DB o por campaña
    }

    /**
     * Función para listar todos los agentes que tengan al menos una llamada agendada, ahora, o
     * en los siguientes RESERVA segundos, donde RESERVA se reporta por getSegundosReserva().
     *
     * @return array	Lista de agentes (sin la cadena "Agent/")
     */
    private function _listarAgentesAgendadosReserva(&$infoCampania)
    {
        $listaAgentes = array();
        $iSegReserva = $this->_getSegundosReserva($infoCampania->id);
        $sFechaSys = date('Y-m-d');
        $iTimestamp = time();
        $sHoraInicio = date('H:i:s', $iTimestamp);
        $sHoraFinal = date('H:i:s', $iTimestamp + $iSegReserva);

        // Listar todos los agentes que tienen alguna llamada agendada dentro del horario
        $sPeticionAgentesAgendados = <<<PETICION_AGENTES_AGENDADOS
SELECT DISTINCT agent FROM calls
WHERE id_campaign = ?
    AND (status IS NULL OR status NOT IN ("Success", "Placing", "Ringing", "OnQueue", "OnHold"))
    AND dnc = 0
    AND date_init <= ? AND date_end >= ? AND time_init <= ? AND time_end >= ?
    AND retries < ?
    AND agent IS NOT NULL
PETICION_AGENTES_AGENDADOS;
        $recordset =& $this->_dbConn->query($sPeticionAgentesAgendados,
            array($infoCampania->id,
                $sFechaSys,
                $sFechaSys,
                $sHoraFinal,
                $sHoraInicio,
                $infoCampania->retries));
        if (DB::isError($recordset)) {
            $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue)  no se puede leer lista de agentes agendados - ".$recordset->getMessage());
        } else {
            $listaAgentes = array();
            while ($tupla = $recordset->fetchRow()) {
                $regs = NULL;
                if (eregi('^Agent/([[:digit:]]+)$', $tupla[0], $regs)) {
                    $listaAgentes[] = $regs[1];
                }
            }
        }
        return $listaAgentes;
    }

    /**
     * Función para contar todas las llamadas agendadas para el agente indicado,
     * clasificadas en llamadas agendables AHORA, y llamadas que caen en RESERVA.
     *
     * @return array Tupla de la forma array(AHORA => x, RESERVA => y)
     */
    private function _contarLlamadasAgendablesReserva(&$infoCampania, $sAgent)
    {
        $cuentaLlamadas = array('AHORA' => 0, 'RESERVA' => 0);
        $iSegReserva = $this->_getSegundosReserva($infoCampania->id);
        $sFechaSys = date('Y-m-d');
        $iTimestamp = time();
        $sHoraInicio = date('H:i:s', $iTimestamp);
        $sHoraFinal = date('H:i:s', $iTimestamp + $iSegReserva);
        
	$sPeticionLlamadasAgente = <<<PETICION_LLAMADAS_AGENTE
SELECT COUNT(*) AS TOTAL, SUM(IF(time_init > ?, 1, 0)) AS RESERVA 
FROM calls
WHERE id_campaign = ?
    AND agent = ?
    AND (status IS NULL OR status NOT IN ("Success", "Placing", "Ringing", "OnQueue", "OnHold"))
    AND dnc = 0
    AND date_init <= ? AND date_end >= ? AND time_init <= ? AND time_end >= ?
    AND retries < ?
PETICION_LLAMADAS_AGENTE;
        $recordset =& $this->_dbConn->query($sPeticionLlamadasAgente,
            array(
                $sHoraInicio,
                $infoCampania->id,
                "Agent/$sAgent",
                $sFechaSys,
                $sFechaSys,
                $sHoraFinal,
                $sHoraInicio,
                $infoCampania->retries));
        if (DB::isError($recordset)) {
            $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue)  no se puede leer cuenta de teléfonos agendados y en reserva - ".$recordset->getMessage());
        } else {
            $listaAgentes = array();
            while ($tupla = $recordset->fetchRow()) {
                $cuentaLlamadas['RESERVA'] = $tupla[1];
                $cuentaLlamadas['AHORA'] = $tupla[0] - $cuentaLlamadas['RESERVA'];
            }
        }
        return $cuentaLlamadas;
    }

    /**
     * Procedimiento para listar la primera llamada agendable para la campaña y el
     * agente indicados. 
     */
    private function _listarLlamadasAgendables(&$infoCampania, $sAgent)
    {
        $sFechaSys = date('Y-m-d');
        $sHoraSys = date('H:i:s');

        $sPeticionLlamadasAgente = <<<PETICION_LLAMADAS_AGENTE
SELECT id_campaign, id, phone, agent  
FROM calls
WHERE id_campaign = ?
    AND agent = ?
    AND (status IS NULL OR status NOT IN ("Success", "Placing", "Ringing", "OnQueue", "OnHold"))
    AND dnc = 0
    AND date_init <= ? AND date_end >= ? AND time_init <= ? AND time_end >= ?
    AND retries < ?
ORDER BY retries, date_end, time_end, date_init, time_init
LIMIT 0,1
PETICION_LLAMADAS_AGENTE;
        $recordset =& $this->_dbConn->query($sPeticionLlamadasAgente,
            array(
                $infoCampania->id,
                "Agent/$sAgent",
                $sFechaSys,
                $sFechaSys,
                $sHoraSys,
                $sHoraSys,
                $infoCampania->retries));
        if (DB::isError($recordset)) {
            $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue)  no se puede leer lista de teléfonos agendados - ".$recordset->getMessage());
            return NULL;
        } else {
            $tupla = $recordset->fetchRow(DB_FETCHMODE_OBJECT);
            return $tupla;
        }

    }

    // Construir la cadena de variables, con separador adecuado según versión Asterisk
    private function _construirCadenaVariables($listaVar)
    {
        // "ID_CAMPAIGN={$infoCampania->id}|ID_CALL={$tupla->id}|NUMBER={$tupla->phone}|QUEUE={$infoCampania->queue}|CONTEXT={$infoCampania->context}",
        $versionMinima = array(1, 6, 0);
        while (count($versionMinima) < count($this->_asteriskVersion))
            array_push($versionMinima, 0);
        while (count($versionMinima) > count($this->_asteriskVersion))
            array_push($this->_asteriskVersion, 0);
        $sSeparador = ($this->_asteriskVersion >= $versionMinima) ? ',' : '|';
        $sCadenaVar = '';
        foreach ($listaVar as $sKey => $sVal) {
            if ($sCadenaVar != '') $sCadenaVar .= $sSeparador;
            $sCadenaVar .= "{$sKey}={$sVal}";
        }
        return $sCadenaVar;
    }

    /**
     * Procedimiento que actualiza el número de llamadas que están siendo manejadas
     * por los agentes. A partir de MIN_MUESTRAS, se actualizan los valores de 
     * promedio y desviación estándar para implementar el algoritmo predictivo.
     *
     * @param object $infoCampania Información sobre la campaña
     *
     * @return bool VERDADERO si se agregaron llamadas a la campaña
     */
    private function actualizarLlamadasCampania($infoCampania)
    {
        $iNumLlamadasColocar = 0;

		// Construir patrón de marcado a partir de trunk de campaña
		$datosTrunk = $this->_construirPlantillaMarcado($infoCampania->trunk);
		if (is_null($datosTrunk)) {
			$this->oMainLog->output("ERR: no se puede construir plantilla de marcado a partir de trunk '{$infoCampania->trunk}'!");
			$this->oMainLog->output("ERR: Revise los mensajes previos. Si el problema es un tipo de trunk no manejado, ".
				"se requiere informar este tipo de trunk y/o actualizar su versión de CallCenter");
			return FALSE;
		}

        // Leer cuántas llamadas (como máximo) se pueden hacer por campaña
        $iNumLlamadasColocar = $infoCampania->max_canales;
        if ($iNumLlamadasColocar <= 0) return FALSE;

        // Averiguar cuantas llamadas se pueden hacer (por predicción), y tomar
        // el menor valor de entre máx campaña y predictivo
        $oPredictor = new Predictivo($this->_astConn);
        if (method_exists($oPredictor, 'setPromedioDuracion')) {
        	$oPredictor->setPromedioDuracion($infoCampania->queue, $infoCampania->promedio);
            $oPredictor->setDesviacionDuracion($infoCampania->queue, $infoCampania->desviacion);
            $oPredictor->setProbabilidadAtencion($infoCampania->queue, $this->_iPorcentajeAtencion);
            
            // Calcular el tiempo que se tarda desde Originate hasta Link con agente.
            $oPredictor->setTiempoContestar($infoCampania->queue, $this->_leerTiempoContestar($infoCampania->id));
        }
        
        // Intentar manejar primero las llamadas agendadas a agentes específicos.
        if ($this->_tieneCallsAgent && !is_null($this->_agentContext)) {
            $sFechaSys = date('Y-m-d');
            $sHoraSys = date('H:i:s');

        	// TODO: el siguiente query asume que si tiene agente asignado, también tiene
            // horario. Si esta suposición cambia, se debe de cambiar abajo.
            $listaAgentesAgendados =  $this->_listarAgentesAgendadosReserva($infoCampania);
            $estadoCola = $oPredictor->leerEstadoCola($infoCampania->queue);
            $listaAgentesConocidos = array_keys($estadoCola['members']);
            $listaAgentesSinAgendar = array_diff($listaAgentesConocidos, $listaAgentesAgendados);
            $listaAgentesColados = array_diff($listaAgentesAgendados, $listaAgentesConocidos);
            
            // Reportar agentes que aparecen como agendados, pero no en la cola
            if (count($listaAgentesColados) > 0) {
                if ($this->DEBUG) {
                	$this->oMainLog->output(
                        "DEBUG: (campania $infoCampania->id cola $infoCampania->queue) los siguientes agentes están agendados ".
                        "pero no aparecen en lista de agentes de la cola: ".join($listaAgentesColados, ' '));
                }
                $listaAgentesAgendados = array_diff($listaAgentesAgendados, $listaAgentesColados);
            }

            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: los siguientes agentes tienen llamadas agendadas: ".
                    join($listaAgentesAgendados, ' '));
            }
            
            
            
            /* En la lista agentes_reservados un agente puede estar ausente si no tiene llamadas agendadas, en
             * estado 1 si está reservado pero todavía no se le coloca llamadas 
             * y en estado 2 si se ha colocado una llamada o están atendiendo una llamada. */ 
            if (!isset($this->_infoLlamadas['agentes_reservados'])) $this->_infoLlamadas['agentes_reservados'] = array();
            
            // Quitar de la lista reservada todos los agentes libres
            foreach ($listaAgentesSinAgendar as $idAgente) {
            	if (isset($this->_infoLlamadas['agentes_reservados'][$idAgente]) && 
                    $this->_infoLlamadas['agentes_reservados'][$idAgente] == 1) {
                    if ($this->DEBUG) {
                    	$this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) el agente ".
                            "Agent/$idAgente ya no tiene llamadas agendadas o en reserva, se quita de lista agentes_reservados ...");
                    }
                    // TODO: verificar si en este punto se deben sacar a los agentes libres de la pausa.
                    unset($this->_infoLlamadas['agentes_reservados'][$idAgente]);
                }
            }
            
            // Agregar a la lista reservada todos los agentes agendados, que no estén ya, y ponerlos
            // en pausa si no están ya en ella.
            $listaLlamadas = array();            
            $listaLlamadasOriginadas = array();
            $pid = posix_getpid();
            foreach ($listaAgentesAgendados as $idAgente) {
                $bEnBreak = FALSE;

                /* Puede ocurrir que el agente esté en pausa propia al momento de verificar 
                 * si debe o no agendarse. Si está en pausa propia, entonces NO DEBE RECIBIR
                 * llamadas, incluso si está agendado. Se verifica en la tabla audit. */
                $sPeticionBreak = 
                    'SELECT COUNT(*) FROM agent, audit ' .
                    'WHERE agent.number = ? ' .
                        'AND agent.id = audit.id_agent ' .
                        'AND audit.id_break IS NOT NULL ' .
                        'AND datetime_init <= ? ' .
                        'AND datetime_end IS NULL';
                $tupla =& $this->_dbConn->getRow(
                    $sPeticionBreak, 
                    array($idAgente, date('Y-m-d H:i:s')));
                if (DB::isError($tupla)) {
                    $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede consultar estado de break para campaña - ".$tupla->getMessage());
                } else {
                    $bEnBreak = ($tupla[0] > 0);
                }                

                if ($bEnBreak) {
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) ".
                            "Agent/$idAgente está en BREAK, se ignora para agendamiento.");
                    }
                    unset($this->_infoLlamadas['agentes_reservados'][$idAgente]);
                    continue; // Analizar el siguiente agente
                }
                
                // Lo siguiente sólo se hace para agentes que NO están en BREAK
                if (!isset($this->_infoLlamadas['agentes_reservados'][$idAgente])) {
                    if (!in_array('paused', $estadoCola['members'][$idAgente]['attributes'])) {
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) ".
                                "Agent/$idAgente debe de ser reservado...");
                        }
                        $resultado = $this->_astConn->QueuePause($infoCampania->queue, "Agent/$idAgente", 'true');
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) " .
                                "resultado de QueuePause($infoCampania->queue, \"Agent/$idAgente\", 'true') : ".
                                print_r($resultado, TRUE));
                        }
                    } else {
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) ".
                                "Agent/$idAgente ya estaba en pausa antes de ser reservado.");
                        }                    	
                    }                	
                    $this->_infoLlamadas['agentes_reservados'][$idAgente] = 1;
                }
                
                // Marcar todos los agentes que están ocupados con una llamada
                if (in_array('In use', $estadoCola['members'][$idAgente]['attributes']) ||
                    in_array('Busy', $estadoCola['members'][$idAgente]['attributes']) ||
                    in_array('Ring+Inuse', $estadoCola['members'][$idAgente]['attributes'])) {
                                	
                    $this->_infoLlamadas['agentes_reservados'][$idAgente] = 2;
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) ".
                            "Agent/$idAgente no puede llamarse ahora, atributos ".join($estadoCola['members'][$idAgente]['attributes'], ' '));
                    }                       
                }
                
                // Llamar a todos los agentes que no estén en estado 2
                if ($this->_infoLlamadas['agentes_reservados'][$idAgente] == 1) {
                	$tupla = $this->_listarLlamadasAgendables($infoCampania, $idAgente);
                    if (!is_null($tupla)) {
                        $sKey = sprintf('%d-%d-%d', $pid, $infoCampania->id, $tupla->id);
                        $listaLlamadas[$sKey] = $tupla;
                    }

                    foreach ($listaLlamadas as $sKey => $tupla) {
                
                        /* Para poder monitorear el evento Onnewchannel, se depende de 
                         * la cadena de marcado para identificar cuál de todos los eventos
                         * es el correcto. Si una llamada generada produce la misma cadena
                         * de marcado que una que ya se monitorea, o que otra en la misma
                         * lista, ocurrirán confusiones entre los eventos. Se filtran las
                         * llamadas que tengan cadenas de marcado repetidas. */
                        $sCanalTrunk = str_replace('$OUTNUM$', $tupla->phone, $datosTrunk['TRUNK']);
                        $bLlamadaRepetida = FALSE;
                        foreach ($this->_infoLlamadas['llamadas'] as $infoLlamadaMonitoreada) {
                            if (isset($infoLlamadaMonitoreada->DialString) &&
                                !is_null($infoLlamadaMonitoreada->DialString) &&
                                $infoLlamadaMonitoreada->DialString == $sCanalTrunk) {
                                $this->oMainLog->output("INFO: se ignora llamada $sKey con DialString $sCanalTrunk - mismo DialString usado por llamada monitoreada.");
                                $bLlamadaRepetida = TRUE;
                            }
                        }
                        foreach ($listaLlamadasOriginadas as $infoLlamadaOriginada) {
                            if (isset($infoLlamadaOriginada->DialString) &&
                                !is_null($infoLlamadaOriginada->DialString) &&
                                $infoLlamadaOriginada->DialString == $sCanalTrunk) {
                                $this->oMainLog->output("INFO: se ignora llamada $sKey con DialString $sCanalTrunk - mismo DialString usado por llamada recién originada.");
                                $bLlamadaRepetida = TRUE;
                            }
                        }
                        if ($bLlamadaRepetida) continue;

                        $sCadenaVar = $this->_construirCadenaVariables(array(
                            'ID_CAMPAIGN'   =>  $infoCampania->id,
                            'ID_CALL'       =>  $tupla->id,
                            'NUMBER'        =>  $tupla->phone,
                            'QUEUE'         =>  $infoCampania->queue,
                            'CONTEXT'       =>  $infoCampania->context,
                        ));
                        $listaLlamadas[$sKey]->queue = $infoCampania->queue;
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: generando llamada agendada\n".
                                "\tClave....... $sKey\n" .
                                "\tAgente...... $tupla->agent\n" .
                                "\tDestino..... $tupla->phone\n" .
                                "\tCola (N/A).. $infoCampania->queue\n" .
                                "\tContexto (N/A) $infoCampania->context\n" .
                                "\tVar. Contexto $sCadenaVar\n" .
                                "\tTrunk....... ".(is_null($infoCampania->trunk) ? '(by dialplan)' : $infoCampania->trunk)."\n" .
                                "\tPlantilla... ".$datosTrunk['TRUNK']."\n" .
                                "\tCaller ID... ".(isset($datosTrunk['CID']) ? $datosTrunk['CID'] : "(no definido)")."\n".
                                "\tCadena de marcado $sCanalTrunk");
                        }

                        $resultado = $this->_astConn->Originate(
                            $sCanalTrunk, 
                            NULL, NULL, NULL,
                            "Wait" ,  "5" , NULL, 
                            (isset($datosTrunk['CID']) ? $datosTrunk['CID'] : NULL), 
                            $sCadenaVar,
                            NULL, 
                            TRUE, $sKey);

                        if (!is_array($resultado) || count($resultado) == 0) {
                            $this->oMainLog->output("ERR: problema al enviar Originate a Asterisk");
                            $this->iniciarConexionAsterisk();
                        }
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: llamada agendada generada: $sKey $sCanalTrunk\n");
                        }
                        if ($resultado['Response'] == 'Success') {
                            // Guardar el momento en que se originó la llamada
                            $listaLlamadas[$sKey]->OriginateStart = time();
                            $listaLlamadas[$sKey]->OriginateEnd = NULL;
                            $listaLlamadas[$sKey]->Channel = NULL;
                            $listaLlamadas[$sKey]->PendingEvents = NULL;
                            $listaLlamadas[$sKey]->OnHold = FALSE;
    
                            // Para llamadas por plan de marcado, se requiere guardar la 
                            // cadena de marcado para poder identificar los eventos Join
                            // y Link que se generen antes del OriginateResponse
                            $listaLlamadas[$sKey]->DialString = is_null($infoCampania->trunk) ? $sCanalTrunk : NULL;
                            
                            $listaLlamadas[$sKey]->status = 'Placing';
                            $bErrorLocked = FALSE;
                            do {
                                $bErrorLocked = FALSE;
                                if ($this->_tieneCallsDatetimeOriginate) {
                                    $sql = 'UPDATE calls SET status = ?, datetime_originate = ? WHERE id_campaign = ? AND id = ?';
                                    $sqlparams = array('Placing', date('Y-m-d H:i:s', $listaLlamadas[$sKey]->OriginateStart), $infoCampania->id, $tupla->id);
                                } else {
                                    $sql = 'UPDATE calls SET status = ? WHERE id_campaign = ? AND id = ?';
                                    $sqlparams = array('Placing', $infoCampania->id, $tupla->id);
                                }
                                $result = $this->_dbConn->query($sql, $sqlparams);
                                if (DB::isError($result)) {
                                    $bErrorLocked = ereg('database is locked', $result->getMessage());
                                    if ($bErrorLocked) {
                                        usleep(125000);
                                    } else {
                                        $this->oMainLog->output("ERR: no se puede actualizar llamada [id_campaign=$infoCampania->id, id=$tupla->id]".$result->getMessage());
                                    }
                                }                        
                            } while (DB::isError($result) && $bErrorLocked);
                            $listaLlamadasOriginadas[$sKey] = $listaLlamadas[$sKey];
                            $this->_infoLlamadas['agentes_reservados'][$idAgente] = 2;  // Se espera OriginateResponse
                        } else {
                            $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede llamar a número agendado - ".
                                print_r($resultado, TRUE));
                            
                            // TODO: Qué hacer con retries si falla la llamada?
                            $this->_infoLlamadas['agentes_reservados'][$idAgente] = 0;

                            $resultado = $this->_astConn->QueuePause($infoCampania->queue, $tupla->agent, 'false');
                            if ($this->DEBUG) {
                                $this->oMainLog->output("DEBUG: resultado de QueuePause($infoCampania->queue, $tupla->agent, 'false') : ".
                                    print_r($resultado, TRUE));
                            }                    
                        }
                    }
    
                }
            }

            // Agregar todas las llamadas agregadas a la lista de llamadas pendientes
            // por timbrar, para filtrar según el evento Link y guardar en la
            // base de datos.
            $this->_infoLlamadas['llamadas'] = array_merge($this->_infoLlamadas['llamadas'], $listaLlamadasOriginadas);
        } // Fin de procesamiento de llamadas agendadas a agente
        
        $iMaxPredecidos = $oPredictor->predecirNumeroLlamadas(
            $infoCampania->queue, 
            ($infoCampania->num_completadas >= MIN_MUESTRAS));
        if ($iNumLlamadasColocar > $iMaxPredecidos)
            $iNumLlamadasColocar = $iMaxPredecidos;

		$conflicto = $oPredictor->getAgentesConflicto();
		if (is_array($conflicto)) {
			$this->oMainLog->output(
				"WARN: los siguientes agentes están libres según 'agent show' pero ocupados según 'queue show' : ".
				join($conflicto, ' '));
            $this->oMainLog->output("WARN: se intenta resolver conflicto de agentes...");
            $this->_resolverConflictoAgentes($infoCampania->queue, $conflicto);

            // Intentar de nuevo calcular agentes libres
            $iMaxPredecidos = $oPredictor->predecirNumeroLlamadas(
                $infoCampania->queue, 
                ($infoCampania->num_completadas >= MIN_MUESTRAS));
            if ($iNumLlamadasColocar > $iMaxPredecidos)
                $iNumLlamadasColocar = $iMaxPredecidos;

            $conflicto = $oPredictor->getAgentesConflicto();
            if (is_array($conflicto)) {
                $this->oMainLog->output(
                    "WARN: a pesar de intento, los siguientes agentes están libres según 'agent show' pero ocupados según 'queue show' : ".
                    join($conflicto, ' '));
            }
		}

        $iNumEsperanRespuesta = $this->_contarLlamadasEsperandoRespuesta($infoCampania->queue);
        if ($this->DEBUG) {
            if ($iNumEsperanRespuesta > 0)
                $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) todavia quedan ".
                	$iNumEsperanRespuesta.
					" llamadas pendientes de OriginateResponse!");
			foreach ($this->_infoLlamadas['llamadas'] as $k => $tupla) {
				if (is_null($tupla->OriginateEnd) && $tupla->id_campaign == $infoCampania->id) {
					$iEspera = time() - $tupla->OriginateStart;
					$this->oMainLog->output("DEBUG:\tllamada $k espera respuesta desde hace $iEspera segundos.");
				}
			}
        }
        if ($iNumLlamadasColocar > $iNumEsperanRespuesta)
            $iNumLlamadasColocar -= $iNumEsperanRespuesta;
        else $iNumLlamadasColocar = 0;

        if ($iNumLlamadasColocar <= 0) {
            if ($this->DEBUG) {
            	$this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) no hay agentes libres ni a punto de desocuparse!");
            	// Se desactiva esto porque emite demasiada información y rellena el log
            	/*
            	$this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) estado de cola: ".
            		print_r($oPredictor->leerEstadoCola($infoCampania->queue), TRUE));
            	*/
            }
            return FALSE;	
        }

        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) se pueden colocar un máximo de $iNumLlamadasColocar llamadas...");	
        }
        
        if ($this->_bSobrecolocarLlamadas) {
            // Para compensar por falla de llamadas, se intenta colocar más de la cuenta. El porcentaje
            // de llamadas a sobre-colocar se determina a partir de la historia pasada de la campaña.
            $iVentanaHistoria = 60 * 30; // TODO: se puede autocalcular?
            $sPeticionASR = 
    			'SELECT COUNT(*) AS total, SUM(IF(status = "Failure" OR status = "NoAnswer", 0, 1)) AS exito ' .
    			'FROM calls ' .
    			'WHERE id_campaign = ? AND status IS NOT NULL ' .
    				'AND status <> "Placing" ' .
    				'AND fecha_llamada IS NOT NULL ' .
    				'AND fecha_llamada >= ?';
    		$tupla =& $this->_dbConn->getRow(
    			$sPeticionASR, 
    			array($infoCampania->id, date('Y-m-d H:i:s', time() - $iVentanaHistoria)), 
    			DB_FETCHMODE_OBJECT);
    		if (DB::isError($tupla)) {
    			$this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede consultar ASR para campaña - ".$tupla->getMessage());
    		} else {
    			// Sólo considerar para más de 10 llamadas colocadas durante ventana
    			if ($tupla->total >= 10 && $tupla->exito > 0) {
    				$ASR = $tupla->exito / $tupla->total;
    				$ASR_safe = $ASR;
    				if ($ASR_safe < 0.20) $ASR_safe = 0.20;
    				$iNumLlamadasColocar = (int)round($iNumLlamadasColocar / $ASR_safe); 
    				if ($this->DEBUG) {
    					$this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) ".
    							"en los últimos $iVentanaHistoria seg. tuvieron éxito " .
    							"$tupla->exito de $tupla->total llamadas colocadas (ASR=".(sprintf('%.2f', $ASR * 100))."%). Se colocan " .
    							"$iNumLlamadasColocar para compensar.");
    				}
    			}
    		}
        }
        
        // Leer tantas llamadas como fueron elegidas. Sólo se leen números con
        // status == NULL y bandera desactivada
        $sFechaSys = date('Y-m-d');
        $sHoraSys = date('H:i:s');
        $sPeticionLlamadas = <<<PETICION_LLAMADAS
(SELECT 1 AS dummy_priority, id_campaign, id, phone, date_init AS dummy_date_init,
    time_init AS dummy_time_init, date_end AS dummy_date_end,
    time_end AS dummy_time_end, retries AS dummy_retries
FROM calls
WHERE id_campaign = ?
    AND (status IS NULL 
        OR status NOT IN ("Success", "Placing", "Ringing", "OnQueue", "OnHold")) 
    AND retries < ?
    AND dnc = 0
    AND (? BETWEEN date_init AND date_end AND ? BETWEEN time_init AND time_end)
    AND agent IS NULL)
UNION
(SELECT 2 AS dummy_priority, id_campaign, id, phone, date_init AS dummy_date_init,
    time_init AS dummy_time_init, date_end AS dummy_date_end,
    time_end AS dummy_time_end, retries AS dummy_retries
FROM calls
WHERE id_campaign = ?
    AND (status IS NULL 
        OR status NOT IN ("Success", "Placing", "Ringing", "OnQueue", "OnHold")) 
    AND retries < ?
    AND dnc = 0
    AND date_init IS NULL AND date_end IS NULL AND time_init IS NULL AND time_end IS NULL
    AND agent IS NULL)
ORDER BY dummy_priority, dummy_retries, dummy_date_end, dummy_time_end, dummy_date_init, dummy_time_init, id
LIMIT 0,?
PETICION_LLAMADAS;

        // Si no hay soporte para agente agendado, entonces se quita la cadena "AND agent IS NULL"
        if (!$this->_tieneCallsAgent) {
        	$sPeticionLlamadas = str_replace('AND agent IS NULL', '', $sPeticionLlamadas);
        }

        $recordset =& $this->_dbConn->query(
            $sPeticionLlamadas, 
            array(
                $infoCampania->id, 
                $infoCampania->retries,
                $sFechaSys, $sHoraSys,
                $infoCampania->id, 
                $infoCampania->retries,
                $iNumLlamadasColocar));
        if (DB::isError($recordset)) {
            $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede leer lista de teléfonos - ".$recordset->getMessage());
            return FALSE;
        }

        // Para cada llamada, su ID de ActionID es la combinación del PID del 
        // proceso, el ID de campaña y el ID de la llamada
        $listaLlamadas = array();
        $pid = posix_getpid();
        while ($tupla = $recordset->fetchRow(DB_FETCHMODE_OBJECT)) {
            $sKey = sprintf('%d-%d-%d', $pid, $infoCampania->id, $tupla->id);
            $listaLlamadas[$sKey] = $tupla;
        }

        if (count($listaLlamadas) == 0) {
        	/* Debido a que ahora las llamadas pueden agendarse a una hora específica, puede
             * ocurrir que la lista de llamadas por realizar esté vacía porque hay llamadas
             * agendadas, pero fuera del horario indicado por la hora del sistema. Si la
             * cuenta del query de abajo devuelve al menos una llamada, se interrumpe el
             * procesamiento y se sale 
             */
            $sPeticionTotal =
                'SELECT COUNT(*) AS N FROM calls '.
                'WHERE id_campaign = ? '.
                    'AND (status IS NULL OR status NOT IN ("Success", "Placing", "Ringing", "OnQueue", "OnHold")) '.
                    'AND retries < ? '.
                    'AND dnc = 0';
            $iNumTotal =& $this->_dbConn->getOne($sPeticionTotal, 
                array($infoCampania->id, $infoCampania->retries));
            if (DB::isError($iNumTotal)) {
                $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede leer cuenta de teléfonos - ".$iNumTotal->getMessage());
                return FALSE;
            }
            if (!is_null($iNumTotal) && $iNumTotal > 0) {
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) no hay llamadas a colocar; $iNumTotal llamadas agendadas pero fuera de horario.");
                }
            	return FALSE;
            }
        }
        
        if (count($listaLlamadas) > 0) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: (campania $infoCampania->id cola $infoCampania->queue) total de llamadas a generar: ".count($listaLlamadas));
            }

            // Colocar todas las llamadas elegidas para ser realizadas por el Asterisk.
            $listaLlamadasOriginadas = array();
            foreach ($listaLlamadas as $sKey => $tupla) {
                
                /* Para poder monitorear el evento Onnewchannel, se depende de 
                 * la cadena de marcado para identificar cuál de todos los eventos
                 * es el correcto. Si una llamada generada produce la misma cadena
                 * de marcado que una que ya se monitorea, o que otra en la misma
                 * lista, ocurrirán confusiones entre los eventos. Se filtran las
                 * llamadas que tengan cadenas de marcado repetidas. */
                $sCanalTrunk = str_replace('$OUTNUM$', $tupla->phone, $datosTrunk['TRUNK']);
                $bLlamadaRepetida = FALSE;
                foreach ($this->_infoLlamadas['llamadas'] as $infoLlamadaMonitoreada) {
                	if (isset($infoLlamadaMonitoreada->DialString) &&
                        !is_null($infoLlamadaMonitoreada->DialString) &&
                        $infoLlamadaMonitoreada->DialString == $sCanalTrunk) {
                        $this->oMainLog->output("INFO: se ignora llamada $sKey con DialString $sCanalTrunk - mismo DialString usado por llamada monitoreada.");
                        $bLlamadaRepetida = TRUE;
                    }
                }
                foreach ($listaLlamadasOriginadas as $infoLlamadaOriginada) {
                    if (isset($infoLlamadaOriginada->DialString) &&
                        !is_null($infoLlamadaOriginada->DialString) &&
                        $infoLlamadaOriginada->DialString == $sCanalTrunk) {
                        $this->oMainLog->output("INFO: se ignora llamada $sKey con DialString $sCanalTrunk - mismo DialString usado por llamada recién originada.");
                        $bLlamadaRepetida = TRUE;
                    }
                }
                if ($bLlamadaRepetida) continue;
                
                
                $sCadenaVar = $this->_construirCadenaVariables(array(
                    'ID_CAMPAIGN'   =>  $infoCampania->id,
                    'ID_CALL'       =>  $tupla->id,
                    'NUMBER'        =>  $tupla->phone,
                    'QUEUE'         =>  $infoCampania->queue,
                    'CONTEXT'       =>  $infoCampania->context,
                ));
                $listaLlamadas[$sKey]->queue = $infoCampania->queue;
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: generando llamada\n".
                        "\tClave....... $sKey\n" .
						"\tDestino..... $tupla->phone\n" .
						"\tCola........ $infoCampania->queue\n" .
						"\tContexto.... $infoCampania->context\n" .
                        "\tVar. Contexto $sCadenaVar\n" .
						"\tTrunk....... ".(is_null($infoCampania->trunk) ? '(by dialplan)' : $infoCampania->trunk)."\n" .
						"\tPlantilla... ".$datosTrunk['TRUNK']."\n" .
						"\tCaller ID... ".(isset($datosTrunk['CID']) ? $datosTrunk['CID'] : "(no definido)")."\n".
						"\tCadena de marcado $sCanalTrunk");
                }

                $resultado = $this->_astConn->Originate(
                    $sCanalTrunk, $infoCampania->queue, $infoCampania->context, 1,
                    NULL, NULL, NULL, 
                    (isset($datosTrunk['CID']) ? $datosTrunk['CID'] : NULL), 
                    $sCadenaVar,
                    NULL, 
                    TRUE, $sKey);

                if (!is_array($resultado) || count($resultado) == 0) {
                	$this->oMainLog->output("ERR: problema al enviar Originate a Asterisk");
                    $this->iniciarConexionAsterisk();
                }
                if ($this->DEBUG) {
                	$this->oMainLog->output("DEBUG: llamada generada: $sKey $sCanalTrunk\n");
                }

                if ($resultado['Response'] == 'Success') {
                    // Guardar el momento en que se originó la llamada
                    $listaLlamadas[$sKey]->OriginateStart = time();
                    $listaLlamadas[$sKey]->OriginateEnd = NULL;
                    $listaLlamadas[$sKey]->agent = NULL;    // Por esta ruta de código, la llamada no es agendada a agente.
                    $listaLlamadas[$sKey]->Channel = NULL;
                    $listaLlamadas[$sKey]->OnHold = FALSE;

                    // Para llamadas por plan de marcado, se requiere guardar la 
                    // cadena de marcado para poder identificar los eventos Join
                    // y Link que se generen antes del OriginateResponse
                    $listaLlamadas[$sKey]->DialString = is_null($infoCampania->trunk) ? $sCanalTrunk : NULL;
                    $listaLlamadas[$sKey]->PendingEvents = NULL;
                    
                    $listaLlamadas[$sKey]->status = 'Placing';
                    $bErrorLocked = FALSE;
                    do {
                    	$bErrorLocked = FALSE;
                        if ($this->_tieneCallsDatetimeOriginate) {
                            $sql = 'UPDATE calls SET status = ?, datetime_originate = ? WHERE id_campaign = ? AND id = ?';
                            $sqlparams = array('Placing', date('Y-m-d H:i:s', $listaLlamadas[$sKey]->OriginateStart), $infoCampania->id, $tupla->id);
                        } else {
                            $sql = 'UPDATE calls SET status = ? WHERE id_campaign = ? AND id = ?';
                            $sqlparams = array('Placing', $infoCampania->id, $tupla->id);
                        }
                        $result = $this->_dbConn->query($sql, $sqlparams);
                        if (DB::isError($result)) {
                            $bErrorLocked = ereg('database is locked', $result->getMessage());
                            if ($bErrorLocked) {
                                usleep(125000);
                            } else {
                                $this->oMainLog->output("ERR: no se puede actualizar llamada [id_campaign=$infoCampania->id, id=$tupla->id]".$result->getMessage());
                            }
                        }                        
                    } while (DB::isError($result) && $bErrorLocked);
                    $listaLlamadasOriginadas[$sKey] = $listaLlamadas[$sKey];
                } else {
                    // TODO: Qué hacer con retries si falla la llamada?
                    $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede llamar a número - ".
                    	print_r($resultado, TRUE));
                }
            }
            // Agregar todas las llamadas agregadas a la lista de llamadas pendientes
            // por timbrar, para filtrar según el evento Link y guardar en la 
            // base de datos.
            $this->_infoLlamadas['llamadas'] = array_merge($this->_infoLlamadas['llamadas'], $listaLlamadasOriginadas);
            return (count($listaLlamadas) > 0);            
        } else {
            /* Si se llega a este punto, se presume que, con agentes disponibles, y campaña
               activa, se terminaron las llamadas. Por lo tanto la campaña ya ha terminado */
            $result = $this->_dbConn->query('UPDATE campaign SET estatus = "T" WHERE id = ?',
                array($infoCampania->id));
            if (DB::isError($result)) {
                $this->oMainLog->output("ERR: (campania $infoCampania->id cola $infoCampania->queue) no se puede marcar campaña como terminada - ".$result->getMessage());
            }

            return FALSE;
        }
    }

    /* Contar el número de llamadas que se colocaron en la cola $queue y que han
     * sido originadas, pero todavía esperan respuesta */
    private function _contarLlamadasEsperandoRespuesta($queue)
    {
    	$iNumEspera = 0;
        
        foreach ($this->_infoLlamadas['llamadas'] as $tuplaLlamada) {
        	if ($tuplaLlamada->queue == $queue && 
                !is_null($tuplaLlamada->OriginateStart) && 
                is_null($tuplaLlamada->OriginateEnd))
                $iNumEspera++;
        }
        return $iNumEspera;
    }

	/**
	 * Procedimiento que construye una plantilla de marcado a partir de una 
	 * definición de trunk. Una plantilla de marcado es una cadena de texto de
	 * la forma 'blablabla$OUTNUM$blabla' donde $OUTNUM$ es el lugar en que
	 * debe constar el número saliente que va a marcarse. Por ejemplo, para
	 * trunks de canales ZAP, la plantilla debe ser algo como Zap/g0/$OUTNUM$
	 * 
	 * @param	string	$sTrunk		Patrón que define el trunk a usar por la campaña
	 * 
	 * @return	mixed	La cadena de plantilla de marcado, o NULL en error 
	 */
	private function _construirPlantillaMarcado($sTrunk)
	{
		if (is_null($sTrunk)) {
			return array('TRUNK' => 'Local/$OUTNUM$@from-internal');
		} elseif (stripos($sTrunk, '$OUTNUM$') !== FALSE) {
			// Este es un trunk personalizado que provee $OUTNUM$ ya preparado
			return array('TRUNK' => $sTrunk);
		} elseif (ereg('^SIP/', $sTrunk) 
			|| eregi('^Zap/.+', $sTrunk)
            || eregi('^DAHDI/.+', $sTrunk) 
			|| ereg('^IAX/', $sTrunk)
            || ereg('^IAX2/', $sTrunk)) {
			// Este es un trunk Zap o SIP. Se debe concatenar el prefijo de marcado 
			// (si existe), y a continuación el número a marcar.
			$infoTrunk = $this->_leerPropiedadesTrunk($sTrunk);
			if (is_null($infoTrunk)) return NULL;
			
			// SIP/TRUNKLABEL/<PREFIX>$OUTNUM$
			$sPlantilla = $sTrunk.'/';
			if (isset($infoTrunk['PREFIX'])) $sPlantilla .= $infoTrunk['PREFIX'];
			$sPlantilla .= '$OUTNUM$';

			// Agregar información de Caller ID, si está disponible
			$plantilla = array('TRUNK' => $sPlantilla);
			if (isset($infoTrunk['CID']) && trim($infoTrunk['CID']) != '')
				$plantilla['CID'] = $infoTrunk['CID'];
			return $plantilla;
		} else {
			$this->oMainLog->output("ERR: trunk '$sTrunk' es un tipo de trunk desconocido. Actualice su versión de CallCenter.");
			return NULL;
		}
	}

    private function _abrirConexionFreePBX()
    {
        $sNombreConfig = '/etc/amportal.conf';  // TODO: vale la pena poner esto en config?

        // De algunas pruebas se desprende que parse_ini_file no puede parsear 
        // /etc/amportal.conf, de forma que se debe abrir directamente.
        $dbParams = array();
        $hConfig = fopen($sNombreConfig, 'r');
        if (!$hConfig) {
            $this->oMainLog->output('ERR: no se puede abrir archivo '.$sNombreConfig.' para lectura de parámetros FreePBX.');
            return NULL;
        }
        while (!feof($hConfig)) {
            $sLinea = fgets($hConfig);
            if ($sLinea === FALSE) break;
            $sLinea = trim($sLinea);
            if ($sLinea == '') continue;
            if ($sLinea{0} == '#') continue;
            
            $regs = NULL;
            if (ereg('^([[:alpha:]]+)[[:space:]]*=[[:space:]]*(.*)$', $sLinea, $regs)) switch ($regs[1]) {
            case 'AMPDBHOST':
            case 'AMPDBUSER':
            case 'AMPDBENGINE':
            case 'AMPDBPASS':
                $dbParams[$regs[1]] = $regs[2];
                break;
            }
        }
        fclose($hConfig); unset($hConfig);
        
        // Abrir la conexión a la base de datos, si se tienen todos los parámetros
        if (count($dbParams) < 4) {
            $this->oMainLog->output('ERR: archivo '.$sNombreConfig.
                ' de parámetros FreePBX no tiene todos los parámetros requeridos para conexión.');
            return NULL;
        }
        if ($dbParams['AMPDBENGINE'] != 'mysql' && $dbParams['AMPDBENGINE'] != 'mysqli') {
            $this->oMainLog->output('ERR: archivo '.$sNombreConfig.
                ' de parámetros FreePBX especifica AMPDBENGINE='.$dbParams['AMPDBENGINE'].
                ' que no ha sido probado.');
            return NULL;
        }
        $sConnStr = 'mysql://'.$dbParams['AMPDBUSER'].':'.$dbParams['AMPDBPASS'].'@'.$dbParams['AMPDBHOST'].'/asterisk';
        $dbConn =  DB::connect($sConnStr);
        if (DB::isError($dbConn)) {
            $this->oMainLog->output("ERR: no se puede conectar a DB de FreePBX - ".($dbConn->getMessage()));
            return NULL;
        }
        $dbConn->setOption('autofree', TRUE);
    	
        return $dbConn;
    }

	/**
	 * Procedimiento que lee las propiedades del trunk indicado a partir de la
	 * base de datos de FreePBX. Este procedimiento puede tomar algo de tiempo,
	 * porque se requiere la información de /etc/amportal.conf para obtener las
	 * credenciales para conectarse a la base de datos.
	 * 
	 * @param	string	$sTrunk		Trunk sobre la cual leer información de DB
	 * 
	 * @return	mixed	NULL en caso de error, o arreglo de propiedades
	 */
	private function _leerPropiedadesTrunk($sTrunk)
	{
		/* Para evitar excesivas conexiones, se mantiene un cache de la información leída
		 * acerca de un trunk durante los últimos 30 segundos. 
		 */
		if (isset($this->_plantillasMarcado[$sTrunk])) {
			if (time() - $this->_plantillasMarcado[$sTrunk]['TIMESTAMP'] >= 30)
				unset($this->_plantillasMarcado[$sTrunk]);
		}
		if (isset($this->_plantillasMarcado[$sTrunk])) {
			return $this->_plantillasMarcado[$sTrunk]['PROPIEDADES'];
		}
		
        $dbConn = $this->_abrirConexionFreePBX();
        if (is_null($dbConn)) return NULL;

		$infoTrunk = NULL;
        $sTrunkConsulta = $sTrunk;
        
        
        if ($this->_existeTrunksFPBX) {
        	/* Consulta directa de las opciones del trunk indicado. Se debe 
             * separar la tecnología del nombre de la troncal, y consultar en
             * campos separados en la tabla asterisk.trunks */
            $camposTrunk = explode('/', $sTrunkConsulta, 2);
            if (count($camposTrunk) < 2) {
                $this->oMainLog->output("ERR: trunk '$sTrunkConsulta' no se puede interpretar, se espera formato TECH/CHANNELID");
                $dbConn->disconnect();
            	return NULL;
            }
            
            // Formas posibles de localizar la información deseada de troncales
            $listaIntentos = array(
                array(
                    'tech'      => strtolower($camposTrunk[0]),
                    'channelid' => $camposTrunk[1]
                ),
            );
            if ($listaIntentos[0]['tech'] == 'dahdi') {
            	$listaIntentos[] = array(
                    'tech'      => 'zap',
                    'channelid' => $camposTrunk[1]
                );
            }
            $sPeticionSQL = 
                'SELECT outcid AS CID, dialoutprefix AS PREFIX '.
                'FROM trunks WHERE tech = ? AND channelid = ?';
            foreach ($listaIntentos as $tuplaIntento) {
                $tupla = $dbConn->getRow($sPeticionSQL, 
                    array($tuplaIntento['tech'], $tuplaIntento['channelid']), 
                    DB_FETCHMODE_ASSOC);
                if (DB::isError($tupla)) {
                    $this->oMainLog->output(
                        "ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (1) - ".
                        ($tupla->getMessage()));
                    $dbConn->disconnect();
                    return NULL;
                } elseif (is_array($tupla) && count($tupla)) {
                    $infoTrunk = array();
                    if ($tupla['CID'] != '') $infoTrunk['CID'] = $tupla['CID'];
                    if ($tupla['PREFIX'] != '') $infoTrunk['PREFIX'] = $tupla['PREFIX'];
                    $this->_plantillasMarcado[$sTrunk] = array(
                        'TIMESTAMP'     =>  time(),
                        'PROPIEDADES'   =>  $infoTrunk,
                    );
                    break;
                }
            }
        } else {
    		/* Buscar cuál de las opciones describe el trunk indicado. En FreePBX,
             * la información de los trunks está guardada en la tabla 'globals',
             * donde globals.value tiene el nombre del trunk buscado, y 
             * globals.variable es de la forma OUT_NNNNN. El valor de NNN se usa
             * para consultar el resto de las variables 
    		 */
    		$regs = NULL;		 
    		$sPeticionSQL = "SELECT variable FROM globals WHERE value = ? AND variable LIKE 'OUT_%'";
    		$sVariable = $dbConn->getOne($sPeticionSQL, array($sTrunkConsulta));
    		if (DB::isError($sVariable)) {
    			$this->oMainLog->output("ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (1) - ".($sVariable->getMessage()));
                $dbConn->disconnect();
                return NULL;
    		} elseif (is_null($sVariable) && strpos($sTrunkConsulta, 'DAHDI') !== 0) {
    			$this->oMainLog->output("ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (1) - trunk no se encuentra!");
                $dbConn->disconnect();
                return NULL;
    		}
            
            if (is_null($sVariable) && strpos($sTrunkConsulta, 'DAHDI') === 0) {
                /* Podría ocurrir que esta versión de FreePBX todavía guarda la 
                 * información sobre troncales DAHDI bajo nombres ZAP. Para 
                 * encontrarla, se requiere de transformación antes de la consulta. 
                 */
                $sTrunkConsulta = str_replace('DAHDI', 'ZAP', $sTrunk);
                $sVariable = $dbConn->getOne($sPeticionSQL, array($sTrunkConsulta));
                if (DB::isError($sVariable)) {
                    $this->oMainLog->output("ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (1) - ".($sVariable->getMessage()));
                    $dbConn->disconnect();
                    return NULL;
                } elseif (is_null($sVariable)) {
                    $this->oMainLog->output("ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (1) - trunk no se encuentra!");
                    $dbConn->disconnect();
                    return NULL;
                }
            }
            
            if (!ereg('^OUT_([[:digit:]]+)$', $sVariable, $regs)) {
    			$this->oMainLog->output("ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (1) - se esperaba OUT_NNN pero se encuentra $sVariable - versión incompatible de FreePBX?");
    		} else {
    			$iNumTrunk = $regs[1];
    			
    			// Consultar todas las variables asociadas al trunk
    			$sPeticionSQL = 'SELECT variable, value FROM globals WHERE variable LIKE ?';
    			$recordset =& $dbConn->query($sPeticionSQL, array('OUT%_'.$iNumTrunk));
    			if (DB::isError($recordset)) {
    				$this->oMainLog->output("ERR: al consultar información de trunk '$sTrunkConsulta' en FreePBX (2) - ".($recordset->getMessage()));
    			} else {
    				$infoTrunk = array();
    				$sRegExp = '^OUT(.+)_'.$iNumTrunk.'$';
    				while ($tupla = $recordset->fetchRow(DB_FETCHMODE_ASSOC)) {
    					$regs = NULL;
    					if (ereg($sRegExp, $tupla['variable'], $regs)) {
    						$sValor = trim($tupla['value']);
    						if ($sValor != '') $infoTrunk[$regs[1]] = $sValor;
    					}
    				}
    				$this->_plantillasMarcado[$sTrunk] = array(
    					'TIMESTAMP'		=>	time(),
    					'PROPIEDADES'	=>	$infoTrunk,
    				);
    			}
    		}
        }

		$dbConn->disconnect();
		return $infoTrunk;
	}

    // Procedimiento que actualiza la lista de las últimas llamadas que fueron
    // contestadas o perdidas.
    private function _agregarTiempoContestar($idCampaign, $iMuestra)
    {
        if (!is_array($this->_infoLlamadas['historial_contestada'])) 
            $this->_infoLlamadas['historial_contestada'] = array();
    	if (!isset($this->_infoLlamadas['historial_contestada'][$idCampaign]) ||
            !is_array($this->_infoLlamadas['historial_contestada'][$idCampaign])) {
            $this->_infoLlamadas['historial_contestada'][$idCampaign] = array();
        }
        array_push($this->_infoLlamadas['historial_contestada'][$idCampaign], $iMuestra);
        while (count($this->_infoLlamadas['historial_contestada'][$idCampaign]) > NUM_LLAMADAS_HISTORIAL_CONTESTADA)
            array_shift($this->_infoLlamadas['historial_contestada'][$idCampaign]);
    }

    private function _leerTiempoContestar($idCampaign)
    {
        if (!is_array($this->_infoLlamadas['historial_contestada'])) 
            $this->_infoLlamadas['historial_contestada'] = array();
        if (!isset($this->_infoLlamadas['historial_contestada'][$idCampaign]) ||
            !is_array($this->_infoLlamadas['historial_contestada'][$idCampaign]))
            $this->_infoLlamadas['historial_contestada'][$idCampaign] = array();
    	$iNumElems = count($this->_infoLlamadas['historial_contestada'][$idCampaign]);
        $iSuma = array_sum($this->_infoLlamadas['historial_contestada'][$idCampaign]);
        if ($iNumElems < NUM_LLAMADAS_HISTORIAL_CONTESTADA) {
        	$iSuma += $this->_iTiempoContestacion * (NUM_LLAMADAS_HISTORIAL_CONTESTADA - $iNumElems);
            $iNumElems = NUM_LLAMADAS_HISTORIAL_CONTESTADA;
        }
        $iTiempoContestar = $iSuma / $iNumElems;
        if ($this->DEBUG) {
        	$this->oMainLog->output("DEBUG: con ".count($this->_infoLlamadas['historial_contestada'][$idCampaign]).
                " de ".NUM_LLAMADAS_HISTORIAL_CONTESTADA." muestras y {$this->_iTiempoContestacion} por omisión, ".
                "campaña $idCampaign tiene ".sprintf('%.2f', $iTiempoContestar)." segundos de marcado.");
        }
        return $iTiempoContestar;
    }

    private function _resolverConflictoAgentes($sNombreCola, $listaConflicto)
    {
    	$infoAgente = array();
        
        $dbConn = $this->_abrirConexionFreePBX();
        if (is_null($dbConn)) return NULL;

        // Averiguar qué tabla debe de usarse para consultar
        $tuplaTabla = $dbConn->getRow('SHOW TABLES LIKE "queues_details"');
        if (DB::isError($tuplaTabla)) {
        	$this->oMainLog->output("ERR: al verificar existencia de queues_details - ".$tuplaTabla->getMessage());
            $dbConn->disconnect();
            return FALSE;
        }
        if (is_array($tuplaTabla) && count($tuplaTabla) > 0 && $tuplaTabla[0] == 'queues_details') {
            $sTablaQueues = 'queues_details';
        } else {
            $sTablaQueues = 'queues';
        }

        // Guardar los datos del agente para restaurar luego de recargar.
        $sPeticionSQL = "SELECT data, flags FROM $sTablaQueues WHERE id = ? AND keyword = ? AND data LIKE ?";
        foreach ($listaConflicto as $idAgente) {
            $tupla = $dbConn->getRow($sPeticionSQL, 
                array($sNombreCola, 'member', "Agent/$idAgente,%"), 
                DB_FETCHMODE_ASSOC);
            if (DB::isError($tupla)) {
            	$this->oMainLog->output("ERR: al recordar valores de cola para Agent - ".$tupla->getMessage());
                $dbConn->disconnect();
                return FALSE;
            }
            $infoAgente[$idAgente] = $tupla;
        }
        
        // Borrar los datos del agente del conflicto
        $sPeticionSQL = "DELETE FROM $sTablaQueues WHERE id = ? AND keyword = ? AND data LIKE ?";
        foreach ($listaConflicto as $idAgente) {
            $result = $dbConn->query($sPeticionSQL, 
                array($sNombreCola, 'member', "Agent/$idAgente,%"));
            if (DB::isError($result)) {
                $this->oMainLog->output("ERR: al eliminar valores de cola para Agent - ".$result->getMessage());
                $dbConn->disconnect();
                return FALSE;
            }
        }

        system('/var/lib/asterisk/bin/retrieve_conf');
        
        $result = $this->_astConn->Command('reload');
        if ($this->DEBUG) {
        	$this->oMainLog->output("DEBUG: resultado de reload(1) es: ".print_r($result, TRUE));
        }
        
        // Volver a insertar los datos del agente eliminado
        $sPeticionSQL = "INSERT INTO $sTablaQueues (id, keyword, data, flags) VALUES (?, ?, ?, ?)";
        foreach ($infoAgente as $idAgente => $datosAgente) {
            $result = $dbConn->query($sPeticionSQL, 
                array($sNombreCola, 'member', $datosAgente['data'], $datosAgente['flags']));
            if (DB::isError($result)) {
                $this->oMainLog->output("ERR: al insertar valores de cola para Agent - ".$result->getMessage());
                $dbConn->disconnect();
                return FALSE;
            }
        }
        
        system('/var/lib/asterisk/bin/retrieve_conf');
        
        $result = $this->_astConn->Command('reload');
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: resultado de reload(2) es: ".print_r($result, TRUE));
        }
        
        $dbConn->disconnect();
        return TRUE;
    }

    // Callback invocado al recibir el evento Dial
    function OnDial($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnDial");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }

        /*
        Asterisk 1.4.x
        2010-05-20 16:01:38 : (DialerProcess) DEBUG: dial:
        params => Array
        (
            [Event] => Dial
            [Privilege] => call,all
            [Source] => Local/96350440@from-internal-a2b9,2
            [Destination] => SIP/telmex-0000004c
            [CallerID] => <unknown>
            [CallerIDName] => <unknown>
            [SrcUniqueID] => 1274385698.159
            [DestUniqueID] => 1274385698.160
        )
        
        Asterisk 1.6.2.x
        2010-10-08 18:49:18 : (DialerProcess) DEBUG: dial:
        params => Array
        (
            [Event] => Dial
            [Privilege] => call,all
            [SubEvent] => Begin
            [Channel] => Local/1065@from-internal-fd98;2
            [Destination] => SIP/1065-00000003
            [CallerIDNum] => <unknown>
            [CallerIDName] => <unknown>
            [UniqueID] => 1286581757.4
            [DestUniqueID] => 1286581758.5
            [Dialstring] => 1065
        )
        */

        $srcUniqueId = $destUniqueID = NULL;
        if (isset($params['SrcUniqueID']))
            $srcUniqueId = $params['SrcUniqueID'];
        elseif (isset($params['UniqueID']))
            $srcUniqueId = $params['UniqueID'];
        if (isset($params['DestUniqueID']))
            $destUniqueID = $params['DestUniqueID'];

        // Si el SrcUniqueID es alguno de los Uniqueid monitoreados, se añade
        // el DestUniqueID correspondiente. Potencialmente esto permite también
        // trazar la troncal por la cual salió la llamada.
        if (!is_null($srcUniqueId) && !is_null($destUniqueID)) {
            foreach (array_keys($this->_infoLlamadas['llamadas']) as $sKey) {
            	if ((isset($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid) && 
                    $srcUniqueId == $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid) ||
                    (isset($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels) && 
                     in_array($srcUniqueId, array_keys($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels)))) {
                    if (!isset($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels))
                        $this->_infoLlamadas['llamadas'][$sKey]->AuxChannels = array();
                    $this->_infoLlamadas['llamadas'][$sKey]->AuxChannels[$destUniqueID] = array(
                        'Dial'  =>  $params,
                    );
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: encontrado canal auxiliar para llamada: ".print_r($this->_infoLlamadas['llamadas'][$sKey], 1));         
                    }
                    break;
                }
            }
        }
    	
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnDial");         
        }
        return FALSE;
    }

    // Callback invocado al recibir el evento Newchannel
    function OnNewchannel($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnNewchannel");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }

        /* Para cada llamada en espera de responder, se verifica si el canal
           esperado corresponde al canal que se acaba de crear. Si es así, se
           registra el UniqueID para poder atrapar el resto de los eventos. 
         */
        $regs = NULL;
        if (isset($params['Channel']) && 
            preg_match('&^(Local/.+@from-internal)-[\dabcdef]+,(1|2)$&', $params['Channel'], $regs)) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: se ha creado pata {$regs[2]} de llamada {$regs[1]}");         
            }
            foreach (array_keys($this->_infoLlamadas['llamadas']) as $sKey) {
            	if (!is_null($this->_infoLlamadas['llamadas'][$sKey]->DialString) && 
                    $this->_infoLlamadas['llamadas'][$sKey]->DialString == $regs[1]) {
                    if ($regs[2] == '1') {
                        // Pata 1, se requiere para los eventos Link/Join
                		$this->_infoLlamadas['llamadas'][$sKey]->Uniqueid = $params['Uniqueid'];
                        $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents = array();
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: Llamada localizada, Uniqueid={$params['Uniqueid']}");
                        }
                        break;
                    } elseif ($regs[2] == '2') {
                    	// Pata 2, se requiere para recuperar razón de llamada fallida, 
                        // en caso de que se desconozca vía pata 1.
                        $this->_infoLlamadas['llamadas'][$sKey]->AuxChannels = array();
                        $this->_infoLlamadas['llamadas'][$sKey]->AuxChannels[$params['Uniqueid']] = array(); 
                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: Llamada localizada canal auxiliar Uniqueid={$params['Uniqueid']}");
                        }
                        break;
                    }
            	}
            }
        }
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnNewchannel");         
        }
        return FALSE;
    }

    // Callback invocado al recibir el evento OriginateResponse
    function OnOriginateResponse($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnOriginateResponse");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }
        if (!isset($params['ActionID'])) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: No hay ActionID, no es llamada monitoreada.");
                $this->oMainLog->output("DEBUG: EXIT OnOriginateResponse");
            }
            return FALSE;
        }
        $sKey = $params['ActionID'];
        
        // Se revisa si esta es una de las llamadas para logonear un agente estático
        $listaECCP = explode(':', $sKey);
        if ($listaECCP[0] == 'ECCP' && $listaECCP[2] == posix_getpid()) {
        	switch ($listaECCP[3]) {
            case 'AgentLogin':
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: AgentLogin({$listaECCP[4]}) detectado");
                }
                $this->quitarIntentoLoginAgente($listaECCP[4]);
                if ($params['Response'] == 'Success') {
                    $this->_infoAgentes[$listaECCP[4]] = $this->generarEstadoInicialAgente();
                    $this->_infoAgentes[$listaECCP[4]]['Uniqueid'] = $params['Uniqueid'];
                    $this->_infoAgentes[$listaECCP[4]]['estado_consola'] = 'logging';
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: AgentLogin({$listaECCP[4]}) llamada contestada, esperando clave de agente...");
                    }
                } else {
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: AgentLogin({$listaECCP[4]}) llamada ha fallado.");
                    }
                    $this->_dialSrv->notificarEvento_AgentLogin($listaECCP[4], NULL, FALSE);
                }
                if ($this->DEBUG) $this->oMainLog->output("DEBUG: EXIT OnOriginateResponse");
                return FALSE;
            case 'RedirectFromHold':
                /* Por ahora se ignora el OriginateResponse resultante del Originate
                 * para regresar de HOLD */
                if ($this->DEBUG) $this->oMainLog->output("DEBUG: EXIT OnOriginateResponse");
                return FALSE;
            default:
                $this->oMainLog->output("ERR: OriginateResponse: no se ha implementado soporte ECCP para: {$sKey}");
                if ($this->DEBUG) $this->oMainLog->output("DEBUG: EXIT OnOriginateResponse");
                return FALSE;
        	}
        }

        // Se revisa si esta es una de las llamadas salientes de las campañas
        if (isset($this->_infoLlamadas['llamadas'][$sKey])) {
            if (!is_null($this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd)) {
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: Llamada ya fue procesada con OriginateResponse sintético.");
                    $this->oMainLog->output("DEBUG: EXIT OnOriginateResponse");
                }
            	return FALSE;
            }

            $this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd = time();
            $idCampaign = $this->_infoLlamadas['llamadas'][$sKey]->id_campaign;
            if (isset($this->_infoLlamadas['campanias'][$idCampaign])) {
                $infoCampania = $this->_infoLlamadas['campanias'][$idCampaign];
            } else {
            	// Puede ocurrir que se hayan originado llamadas, pero en la
                // siguiente iteración la campaña haya terminado. Todavía
                // debe de seguirse la pista de la campaña.
                $infoCampania = $this->_leerCampania($idCampaign);
            }

            if ($this->DEBUG) {
            	$this->oMainLog->output("DEBUG: llamada identificada es: $sKey : ".
                    print_r($this->_infoLlamadas['llamadas'][$sKey], TRUE));
            }

            if (isset($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid) && 
                $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid != $params['Uniqueid'] &&
                $params['Uniqueid'] != '<null>') {
            
                $this->oMainLog->output("ERR: se procesó pata equivocada en evento Newchannel ".
                    "anterior, pata procesada es {$this->_infoLlamadas['llamadas'][$sKey]->Uniqueid}, ".
                    "pata real es {$params['Uniqueid']}");
                $this->oMainLog->output("ERR: desechando eventos inválidos...");      
                $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents = array();
                $this->_infoLlamadas['llamadas'][$sKey]->AuxChannels = array();
            }                    

            /* Se ha observado que algunos Asterisk mienten sobre el estado de
               la llamada, y reportan Response=Success mientras que ya han 
               colgado la llamada. Si ha ocurrido este caso, la pata principal
               tiene el Hangup pendiente incluso cuando el estado es Success
             */
            if ($params['Response'] == 'Success' && 
                isset($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Hangup']) &&
                !isset($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Link'])) {
                $this->oMainLog->output("ERR: Asterisk reporta éxito de llamada ya colgada, se cambia a Failure");
                $params['Response'] = 'Failure';
            }
            // También hay caso de pata auxiliar con Hangup
            if ($params['Response'] == 'Success' &&
                isset($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels)) {
                $bHayHangup = FALSE;
                foreach (array_keys($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels) as $auxKey) {
                    if (isset($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels[$auxKey]['Hangup']))
                        $bHayHangup = TRUE;                        
                }
                if ($bHayHangup) {
                    $this->oMainLog->output("ERR: Asterisk reporta éxito de llamada ya colgada (canal auxiliar), se sospecha colgado próximo.");
                    //$params['Response'] = 'Failure';
                }
            }
            
            $sStatus = $params['Response'];
            if ($params['Uniqueid'] == '<null>') $params['Uniqueid'] = NULL;
            if ($sStatus == 'Success') $sStatus = 'Ringing';
            $this->_infoLlamadas['llamadas'][$sKey]->status = $sStatus;

            $sQuery = <<<UPDATE_CALLS_ORIGINATE_RESPONSE
UPDATE calls 
SET status = ?, Uniqueid = ?, fecha_llamada = ?, start_time = NULL, 
    end_time = NULL, retries = retries + ? 
WHERE id_campaign = ? AND id = ?
UPDATE_CALLS_ORIGINATE_RESPONSE;
            $queryParams = array($sStatus, $params['Uniqueid'], date('Y-m-d H:i:s'), (($sStatus == 'Failure') ? 1 : 0),
                    $infoCampania->id, $this->_infoLlamadas['llamadas'][$sKey]->id);
            
            $result = $this->_dbConn->query($sQuery, $queryParams);
            if (DB::isError($result)) {
                $this->oMainLog->output(
                    "ERR: no se puede actualizar llamada con OriginateResponse ".
                    "[id_campaign=$infoCampania->id, id=".$this->_infoLlamadas['llamadas'][$sKey]->id."]".
                    $result->getMessage());
            }
            
            // En faso de fallo, se almacena el código y descripción de causa
            if ($this->_tieneCallsFailureCause) {
            	$iCause = NULL; $sCauseTxt = NULL;
                if ($sStatus == 'Failure') {
                	$iCause = 0; $sCauseTxt = 'Unknown';
                    
                    // Código y descripción está en el evento Hangup recogido
                    if (isset($this->_infoLlamadas['llamadas'][$sKey]) && 
                        is_array($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents) &&
                        isset($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Hangup'])) {
                        $iCause = $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Hangup']['Cause'];
                        $sCauseTxt = $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Hangup']['Cause-txt'];
                    }
                    
                    // Si el evento recogido tiene causa 0, se busca evento de
                    // canal auxiliar que tenga información distinta
                    // $this->_infoLlamadas['llamadas'][$key]->AuxChannels[$params['Uniqueid']]['Hangup'] = $params;
                    if ($iCause == 0 && isset($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels)) {
                    	foreach ($this->_infoLlamadas['llamadas'][$sKey]->AuxChannels as $eventosAuxiliares) {
                    		if (isset($eventosAuxiliares['Hangup']) && $eventosAuxiliares['Hangup']['Cause'] != 0) {
                    			$iCause = $eventosAuxiliares['Hangup']['Cause'];
                                $sCauseTxt = $eventosAuxiliares['Hangup']['Cause-txt'];
                    		}
                    	}
                    }
                }
                $result = $this->_dbConn->query(
                    'UPDATE calls SET failure_cause = ?, failure_cause_txt = ? WHERE id_campaign = ? AND id = ?',
                    array($iCause, $sCauseTxt, $infoCampania->id, $this->_infoLlamadas['llamadas'][$sKey]->id));
                if (DB::isError($result)) {
                    $this->oMainLog->output(
                        "ERR: no se puede actualizar llamada con causa de fallo de OriginateResponse ".
                        "[id_campaign=$infoCampania->id, id=".$this->_infoLlamadas['llamadas'][$sKey]->id."]".
                        $result->getMessage());
                }
            }                        
            
            if ($params['Response'] == 'Success') {
                if (isset($this->_infoLlamadas['llamadas'][$sKey])) {
                    if (isset($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid) && 
                        $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid != $params['Uniqueid']) {
                    
                        $this->oMainLog->output("ERR: se procesó pata equivocada en evento Newchannel ".
                            "anterior, pata procesada es {$this->_infoLlamadas['llamadas'][$sKey]->Uniqueid}, ".
                            "pata real es {$params['Uniqueid']}");    	
                    }                    
                    $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid = $params['Uniqueid'];
                    $this->_infoLlamadas['llamadas'][$sKey]->Response = $params['Response'];
                    $this->_infoLlamadas['llamadas'][$sKey]->queue = $infoCampania->queue;
                    $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp = NULL;
                    $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp = NULL;
                    $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp = NULL;
                }
                if ($this->DEBUG) {
                	$iSegundosEspera = 
                		$this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd - 
                		$this->_infoLlamadas['llamadas'][$sKey]->OriginateStart;
                	$this->oMainLog->output("DEBUG: llamada colocada luego de $iSegundosEspera s. de espera."); 
                }
                
                if (!is_null($this->_infoLlamadas['llamadas'][$sKey]->agent)) {
                    $sAgent = $this->_infoLlamadas['llamadas'][$sKey]->agent;
                    $this->_infoLlamadas['llamadas'][$sKey]->Channel = $params["Channel"];
                    if ($this->DEBUG) {
                    	$this->oMainLog->output("DEBUG: llamada agendada a $sAgent, redirigiendo a $params[Channel] ...");
                    }
                    
                    // TODO: POSIBLE PUNTO DE REENTRANCIA
                    $resultado = $this->_astConn->Redirect($params['Channel'], '',substr($sAgent,6), $this->_agentContext, 1);

                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: resultado de Redirect($params[Channel], '', $sAgent, 'from-internal', 1) : ".
                            print_r($resultado, TRUE));
                    }                    
                }
            } else {
                $this->_agregarTiempoContestar(
                    $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                    $this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd - $this->_infoLlamadas['llamadas'][$sKey]->OriginateStart);

				// Reportar tiempo transcurrido hasta fallo
                if ($this->DEBUG) {
                	$iSegundosEspera = 
                		$this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd - 
                		$this->_infoLlamadas['llamadas'][$sKey]->OriginateStart;
                	$this->oMainLog->output("DEBUG: llamada falla en ser colocada luego de $iSegundosEspera s. de espera."); 
                }                    

                // Sacar de pausa al agente cuya llamada no ha sido contestada.
                $sAgent = $this->_infoLlamadas['llamadas'][$sKey]->agent;
                if (!is_null($sAgent)) {
                    $regs = NULL;
                    ereg('^Agent/([[:digit:]]+)$', $sAgent, $regs);
                    $idAgente = $regs[1];
                    if (isset($this->_infoLlamadas['agentes_reservados'][$idAgente])) {
                        $this->_infoLlamadas['agentes_reservados'][$idAgente] = 1;

                        // El agente debe ser sacado de pausa sólo si no hay más llamadas en reserva
                        $l = $this->_contarLlamadasAgendablesReserva($infoCampania, $idAgente);
                        if ($l['AHORA'] == 0 && $l['RESERVA'] == 0) {
                            
                            // TODO: POSIBLE PUNTO DE REENTRANCIA
                            $resultado = $this->_astConn->QueuePause($infoCampania->queue, $sAgent, 'false');

                            if ($this->DEBUG) {
                                $this->oMainLog->output("DEBUG: OnOriginateResponse: $sAgent no tiene más llamadas agendadas, se quita pausa...");
                                $this->oMainLog->output("DEBUG: resultado de QueuePause($infoCampania->queue, $sAgent, 'false') : ".
                                    print_r($resultado, TRUE));
                            }
                            unset($this->_infoLlamadas['agentes_reservados'][$idAgente]);
                        }
                    } else {
                    	$this->oMainLog->output("ERR: OnOriginateResponse: no se encuentra agente $sAgent en lista de reservas de agentes");
                    }
                }

                // Remover llamada que no se pudo colocar
                unset($this->_infoLlamadas['llamadas'][$sKey]);
                $sMensaje = print_r($params, TRUE);
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: Información sobre no-éxito de OriginateResponse: \n$sMensaje");
                }
            }
        }
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnOriginateResponse");        	
        }

        /* Buscar el posible evento Join/Link que se haya almacenado previamente */
        if (isset($this->_infoLlamadas['llamadas'][$sKey]) && 
            is_array($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents)) {
        	// Hay eventos pendientes para esta llamada...
            if (isset($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Join'])) {
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: volviendo a ejecutar el evento Join almacenado");
                }
                $this->OnJoin('join', $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Join'], $sServer, $iPort);
        	}
            if (isset($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Link'])) {
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: volviendo a ejecutar el evento Link almacenado");
                }
                $this->OnLink('link', $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Link'], $sServer, $iPort);
            }
        }

        return FALSE;
    }

    // Callback invocado al llegar el evento Join
    function OnJoin($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnJoin");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }
        
        // Verificar si es una llamada entrante monitoreada. Si lo es, 
        // se termina el procesamiento sin hacer otra cosa
        if ($this->_oGestorEntrante->notificarJoin($params)) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: llamada manejada por GestorLlamadasEntrantes::notificarJoin");
                $this->oMainLog->output("DEBUG: EXIT OnJoin");
            }
            return FALSE;
        }
        
        // Buscar llamada entre llamadas monitoreadas
        $sKey = NULL;
        foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
            if (isset($tupla->Uniqueid) && $tupla->Uniqueid == $params['Uniqueid']) {
                $sKey = $key;
            }
        }

        if (!is_null($sKey) && 
            is_null($this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd) && 
            is_array($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents)) {
                
            /* Este event Join se recibió antes de tiempo. Se lo debe almacenar */
            $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Join'] = $params;

            $sKey = NULL;
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: EXIT OnJoin");                  
            }
            return FALSE;
        }

        if (!is_null($sKey)) {
            $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp = time();
            $this->_infoLlamadas['llamadas'][$sKey]->status = 'OnQueue';
            $sLlamadaEnCola = 
                'UPDATE calls SET status = "OnQueue", datetime_entry_queue = ?, '.
                    'duration_wait = NULL, duration = NULL, start_time = NULL, '.
                    'end_time = NULL '.
                'WHERE id_campaign = ? AND id = ?';
            $result =& $this->_dbConn->query(
                $sLlamadaEnCola,
                array(
                    date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp),
                    $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                    $this->_infoLlamadas['llamadas'][$sKey]->id,
                    ));
            if (DB::isError($result)) {
                $this->oMainLog->output("ERR: $sEvent: no se puede actualizar fecha inicio llamada actual - ".$result->getMessage());
            }
        }

        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnJoin");
        }
    }

    // Callback invocado al llegar el evento Link
    function OnLink($sEvent, $params, $sServer, $iPort)
    {    
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnLink");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }
        
        // Verificar si es una llamada entrante monitoreada. Si lo es, 
        // se termina el procesamiento sin hacer otra cosa
        if ($this->_oGestorEntrante->notificarLink($params)) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: llamada manejada por GestorLlamadasEntrantes::notificarLink");
                $this->oMainLog->output("DEBUG: EXIT OnLink");
            }
            return FALSE;
        }

        // Recuperar el agente local y el canal remoto
        $regs = NULL;
        $sAgentNum = NULL;
        $sChannel = NULL;
        $sRemChannel = NULL;
        if (preg_match('|^Agent/(\d+)$|', $params['Channel1'], $regs)) {
            $sAgentNum = $regs[1];
            $sChannel = $params['Channel1'];
            $sRemChannel = $params['Channel2'];
        }
        if (preg_match('|^Agent/(\d+)$|', $params['Channel2'], $regs)) {
            $sAgentNum = $regs[1];
            $sChannel = $params['Channel2'];
            $sRemChannel = $params['Channel1'];
        }

        
        $sKey = NULL;
        foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
            if (isset($tupla->Uniqueid)) {
                if ($tupla->Uniqueid == $params['Uniqueid1']) $sKey = $key;
                if ($tupla->Uniqueid == $params['Uniqueid2']) $sKey = $key;
            }
            if (!is_null($sKey)) break;
        }

        if (!is_null($sKey) && is_null($sAgentNum) && 
            !is_null($this->_infoLlamadas['llamadas'][$sKey]->start_timestamp)) {
            /* Si la llamada ya ha sido enlazada previamente, y ahora se enlaza 
             * a un canal distinto de un agente, se asume que ha sido transferida 
             * a una extensión fuera de monitoreo, y ya no debe de monitorearse.
             * Ya que Asterisk no ejecuta un Hangup en este caso, se lo debe 
             * simular. */

            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: Sintetizando Hangup...");                  
            }
            $paramsEvento = array(
                'Event' =>  'Hangup',
                'Privilege' =>  $params['Privilege'],
                'Channel'   =>  $sRemChannel,
                'Uniqueid'  =>  $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid,
                'Cause'     =>  16,
                'Cause-txt' =>  'Normal Clearing',
                'local_timestamp_received'  =>  time(),
                'original_event'    =>  $params, 
            );
            $this->OnHangup('hangup', $paramsEvento, $sServer, $iPort);
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: Fin de Hangup sintetizado.");                  
                $this->oMainLog->output("DEBUG: EXIT OnLink");                  
            }
            $sKey = NULL;
            return FALSE;
        }

        if (is_null($sKey)) {
            // Si no se tiene clave, todavía puede ser llamada agendada
            // que debe buscarse por nombre de canal.
            foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
                if (!is_null($tupla->Channel)) {
                    if ($tupla->Channel == $params["Channel1"]) {
                        $sKey = $key;
                        $tupla->Uniqueid = $params["Uniqueid1"];
                    }
                    if ($tupla->Channel == $params["Channel2"]) {
                        $sKey = $key;
                        $tupla->Uniqueid = $params["Uniqueid2"];
                    }
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: identificada llamada agendada $tupla->Channel, cambiado Uniqueid a $tupla->Uniqueid ");
                    }
                }
                if (!is_null($sKey)) break;
            }
        }

        $sNuevo_Uniqueid = NULL;
        if (is_null($sKey)) {
        	/* Si no se tiene clave, todavía puede ser llamada dialplan que 
             * regresa de HOLD. Entonces OnHold es VERDADERO y ActualChannel
             * coincide con alguno de Channel1 o Channel2 */
            foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
            	if ($tupla->OnHold) {
            		// Se toma el UniqueID contrario al canal pasado.
                    if ($tupla->ActualChannel == $params["Channel1"]) {
                    	$sKey = $key;
                        $sNuevo_Uniqueid = $params["Uniqueid2"];
                    }
                    if ($tupla->ActualChannel == $params["Channel2"]) {
                        $sKey = $key;
                        $sNuevo_Uniqueid = $params["Uniqueid1"];
                    }
                    if ($this->DEBUG && !is_null($sKey)) {
                        $this->oMainLog->output("DEBUG: identificada llamada $tupla->ActualChannel que regresa de HOLD, antiguo Uniqueid es $tupla->Uniqueid");                    	
                    }
            	}
                if (!is_null($sKey)) break;
            }
        }

        if (!is_null($sKey) && 
            is_null($this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd) && 
            is_array($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents)) {
            	
            /* Este event Link se recibió antes de tiempo. Se lo debe almacenar */
            $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Link'] = $params;

            // Generar un OriginateResponse lo antes posible.
            if (!is_null($sAgentNum)) {
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: Sintetizando OriginateResponse...");                  
                }
                $paramsEvento = array(
                    'Event' =>  'OriginateResponse',
                    'Privilege' =>  $params['Privilege'],
                    'ActionID'  =>  $sKey,
                    'Response'  =>  'Success',
                    'Channel'   =>  $sRemChannel,
                    'Uniqueid'  =>  $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid, 
                );
                $this->OnOriginateResponse('originateresponse', $paramsEvento, $sServer, $iPort);
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: Fin de OriginateResponse sintetizado.");                  
                }
            }

            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: EXIT OnLink");                  
            }
            $sKey = NULL;
            return FALSE;
        }

        if (!is_null($sKey)) {
        	/* Si una llamada regresa de HOLD a activa, se recibe un evento Link,
             * pero la llamada ya se encuentra en current_calls. */
            $iCuenta = $this->_dbConn->getOne(
                "SELECT COUNT(*) FROM current_calls, calls " .
                "WHERE current_calls.Uniqueid = ? " .
                    "AND current_calls.id_call = calls.id " .
                    "AND calls.status = 'OnHold'",
                array($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid));
            if (DB::isError($iCuenta)) {
            	$this->oMainLog->output("ERR: $sEvent: no se puede consultar si llamada está activa - ".$iCuenta->getMessage());
            } elseif ($iCuenta > 0) {
            	/* La llamada ha sido ya ingresada en current_calls, y se omite 
                 * procesamiento futuro. */
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: $sEvent: llamada ".
                        ($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid).
                        " regresa de HOLD, se omite procesamiento futuro.");
                }
                $this->_infoLlamadas['llamadas'][$sKey]->status = 'Success';
                $result =& $this->_dbConn->query(
                    "UPDATE calls SET status = 'Success' WHERE id = ? AND status = 'OnHold'",
                    array($this->_infoLlamadas['llamadas'][$sKey]->id));
                if (DB::isError($result)) {
                    $this->oMainLog->output("ERR: $sEvent: no se puede actualizar estado de llamada actual a HOLD - ".$result->getMessage());
                }
                
                // Verificar si también se debe actualizar el Uniqueid en calls y current_calls
                $this->_infoLlamadas['llamadas'][$sKey]->OnHold = FALSE;
                if (!is_null($sNuevo_Uniqueid)) {
                    $result = $this->_dbConn->query(
                        'UPDATE current_calls SET Uniqueid = ? WHERE Uniqueid = ?',
                        array($sNuevo_Uniqueid, $tupla->Uniqueid));
                    if (DB::isError($result)) {
                        $this->oMainLog->output("ERR: $sEvent: no se puede actualizar Uniqueid en current_calls - ".$result->getMessage());
                    }
                    
                    $result = $this->_dbConn->query(
                        'UPDATE calls SET uniqueid = ? WHERE id = ?',
                        array($sNuevo_Uniqueid, $this->_infoLlamadas['llamadas'][$sKey]->id));
                    if (DB::isError($result)) {
                        $this->oMainLog->output("ERR: $sEvent: no se puede actualizar Uniqueid en calls - ".$result->getMessage());
                    }
                    
                    $tupla->Uniqueid = $sNuevo_Uniqueid;
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: llamada que regresa de HOLD, nuevo Uniqueid es $tupla->Uniqueid");                     
                    }
                }
                
                $sKey = NULL;


                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: EXIT OnLink");                	
                }
                return FALSE;
            }
        }
        if (!is_null($sKey) && is_null($this->_infoLlamadas['llamadas'][$sKey]->start_timestamp)) {
            $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp = time();
            $this->_infoLlamadas['llamadas'][$sKey]->AgentChannel = $sChannel;
            
            if ($this->DEBUG) {
            	$this->oMainLog->output("DEBUG: $sEvent: llamada $sKey => ".
                    print_r($this->_infoLlamadas['llamadas'][$sKey], TRUE));
            }

            $this->_agregarTiempoContestar(
                $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp - $this->_infoLlamadas['llamadas'][$sKey]->OriginateStart);

            if (!is_null($sAgentNum)) {
                if ($this->DEBUG) {
                	$this->oMainLog->output("DEBUG: $sEvent: identificado agente $sAgentNum");
                }

                // Borrado de la llamada para el agente antiguo. Esto es por 
                // precaución, porque no debería ocurrir en funcionamiento correcto.
                $sBorrado = 'DELETE FROM current_calls WHERE agentnum = ?';
                $bErrorLocked = FALSE;
                do {
                    $bErrorLocked = FALSE;
                    $result =& $this->_dbConn->query($sBorrado, array($sAgentNum));
                    if (DB::isError($result)) {
                        $bErrorLocked = (strpos('database is locked', $result->getMessage()) !== FALSE);
                        if ($bErrorLocked) {
                            usleep(125000);
                        } else {
                            $this->oMainLog->output("ERR: $sEvent: no se puede purgar agente $sAgentNum - ".$result->getMessage());
                        }
                    }
                } while (DB::isError($result) && $bErrorLocked);
                
                $sFechaActual = date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp);

                if ($this->DEBUG) {
                	$this->oMainLog->output("DEBUG: $sEvent: llamada $sKey asignada a agente $sAgentNum");
                }

                /* Se consulta el canal físico que está hablando con el agente,
                 * para poder usarlo al mandar la llamada a hold, y para reconocer
                 * la llamada que hay que recoger de vuelta. */
                $oPredictor = new Predictivo($this->_astConn);
                $estadoCola = $oPredictor->leerEstadoCola(''); // El parámetro vacío lista todas las colas
                if (isset($estadoCola['members'][$sAgentNum]) && 
                    isset($estadoCola['members'][$sAgentNum]['clientchannel'])) {
                	$this->_infoLlamadas['llamadas'][$sKey]->ActualChannel = $estadoCola['members'][$sAgentNum]['clientchannel']; 
                } else {
                    $this->oMainLog->output("WARN: $sEvent: no se puede reconocer clientchannel para ".
                        "agente $sAgentNum: estado cola es: ".print_r($estadoCola, 1));
                	$this->_infoLlamadas['llamadas'][$sKey]->ActualChannel = NULL;
                }
                
                // El canal verdadero es más util que Local/XXX para las operaciones
                if (strpos($sRemChannel, 'Local/') === 0 && !is_null($this->_infoLlamadas['llamadas'][$sKey]->ActualChannel))
                    $sRemChannel = $this->_infoLlamadas['llamadas'][$sKey]->ActualChannel;
                
                
                // Inserción de la llamada nueva
                $sInsercionEvent = 
                    'INSERT INTO current_calls (fecha_inicio, Uniqueid, queue, agentnum, id_call, event, Channel, ChannelClient) '.
                    'VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                do {
                    $bErrorLocked = FALSE;
                    $result =& $this->_dbConn->query(
                        $sInsercionEvent,
                        array($sFechaActual, 
                        $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid,
                        $this->_infoLlamadas['llamadas'][$sKey]->queue,
                        $sAgentNum,
                        $this->_infoLlamadas['llamadas'][$sKey]->id,
                        'Link',                        
                        $sChannel, 
                        $sRemChannel));
                    if (DB::isError($result)) {
                        $bErrorLocked = (strpos('database is locked', $result->getMessage()) !== FALSE);
                        if ($bErrorLocked) {
                            usleep(125000);
                        } else {
                            $this->oMainLog->output("ERR: $sEvent: no se puede insertar llamada actual - ".$result->getMessage());
                        }
                    }
                } while (DB::isError($result) && $bErrorLocked);

                if ($this->DEBUG && !DB::isError($result)) {
                	$this->oMainLog->output("DEBUG: $sEvent: llamada $sKey para agente $sAgentNum acaba de ser insertada en current_calls");
                }

                // Obtengo los datos del agente 
                $sDatosAgente = 
                    'SELECT id '.
                    'FROM agent '.
                    'WHERE number = ? ';
                $tupla = $this->_dbConn->getRow($sDatosAgente, array($sAgentNum), DB_FETCHMODE_OBJECT);
                if (DB::isError($tupla)) {
                    $this->oMainLog->output("ERR: $sEvent: no se puede consultar los datos del agente $sAgentNum- ".$tupla->getMessage());
                    $idAgente = NULL;
                }
                else {
                    $idAgente = $tupla->id;
                }

                // Actualización de la fecha de inicio de la llamada
                $this->_infoLlamadas['llamadas'][$sKey]->status = 'Success';
                if (is_null($this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp)) {
                    $this->oMainLog->output(
                        "ERR: $sEvent: se ha perdido evento OnJoin para llamada antes de OnLink, ".
                        "no se puede calcular el periodo de espera.\nparams => ".print_r($params, TRUE));
                    $sInicioLlamada = 
                        'UPDATE calls SET status = "Success", id_agent = ?, start_time = ?, end_time = NULL, '.
                            'retries = retries + 1 '.
                        'WHERE id_campaign = ? AND id = ?';
                    $result =& $this->_dbConn->query(
                        $sInicioLlamada,
                        array(
                            $idAgente,
                            $sFechaActual, 
                            $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                            $this->_infoLlamadas['llamadas'][$sKey]->id,
                            ));
                } else {
                    $sInicioLlamada = 
                        'UPDATE calls SET status = "Success", id_agent = ?, start_time = ?, end_time = NULL, '.
                            'retries = retries + 1, datetime_entry_queue = ?, duration_wait = ? '.
                        'WHERE id_campaign = ? AND id = ?';
                    $result =& $this->_dbConn->query(
                        $sInicioLlamada,
                        array(
                            $idAgente,
                            $sFechaActual, 
                            date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp),
                            $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp - $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp,
                            $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                            $this->_infoLlamadas['llamadas'][$sKey]->id,
                            ));
                }
                if (DB::isError($result)) {
                	$this->oMainLog->output("ERR: $sEvent: no se puede actualizar fecha inicio llamada actual - ".$result->getMessage());
                }
                
                // Leer la información de la llamada, y reportar la conexión
                $infoLlamada = $this->leerInfoLlamada('outgoing',
                    $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                    $this->_infoLlamadas['llamadas'][$sKey]->id);
                if (!is_null($infoLlamada)) {
                    if ($this->DEBUG) {
                        $this->oMainLog->output('DEBUG: OnLink: notificando evento AgentLinked');
                    }                
                    $this->_dialSrv->notificarEvento_AgentLinked($sChannel, $sRemChannel, $infoLlamada);
                }
                
                $this->_listarCurrentCalls();
                
            } else {
            	$this->oMainLog->output("ERR: no se puede identificar agente asignado a llamada $sKey!");
            }
        } elseif (!is_null($sKey)) {
            // Llamada ya estaba siendo monitoreada anteriormente.
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: $sEvent: (re-link) llamada $sKey => ".
                    print_r($this->_infoLlamadas['llamadas'][$sKey], TRUE));
            }
        } else {
            if ($this->DEBUG) {
                // Ocurre un evento Link que no corresponde a las llamadas en curso
                $this->oMainLog->output("DEBUG: $sEvent: evento no corresponde a llamadas monitoreadas!");
            }
        }
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnLink");
        }
        return FALSE;
    }

    private function _listarCurrentCalls()
    {
    	if ($this->DEBUG) {
    		$sListarCurrentCalls = 
                'SELECT fecha_inicio, Uniqueid, queue, agentnum, id_call, event, Channel, ChannelClient '.
                'FROM current_calls ORDER BY fecha_inicio';
            $recordset = $this->_dbConn->query($sListarCurrentCalls);
            if (DB::isError($recordset)) {
            	$this->oMainLog->output("DEBUG: no se puede listar llamadas actuales en current_calls - ".$recordset->getMessage());
            } else {
                $this->oMainLog->output("DEBUG: current_calls:");
                $this->oMainLog->output("DEBUG: fecha_inicio\tUniqueid\tqueue\tagentnum\tid_call\tevent\tChannel\tChannelClient:");
            	while ($tupla = $recordset->fetchRow(DB_FETCHMODE_ASSOC)) {
            		$this->oMainLog->output("DEBUG: ".
                        $tupla['fecha_inicio']."\t".
                        $tupla['Uniqueid']."\t".
                        $tupla['queue']."\t".
                        $tupla['agentnum']."\t".
                        $tupla['id_call']."\t".
                        $tupla['event']."\t".
                        $tupla['Channel']."\t".
                        $tupla['ChannelClient']
                        );
            	}
            }
    	}
    }

    /**
     * Procedimiento que consulta toda la información de la base de datos sobre
     * una llamada de campaña. Se usa para el evento agentlinked, así como para 
     * el requerimiento getcallinfo.
     * 
     * @param   string  $sTipoLlamada   Uno de 'incoming', 'outgoing'
     * @param   integer $idCampania     ID de la campaña, puede ser NULL para incoming
     * @param   integer $idLlamada      ID de la llamada dentro de la campaña
     *    
     */
    function leerInfoLlamada($sTipoLlamada, $idCampania, $idLlamada)
    {
    	switch ($sTipoLlamada) {
        case 'incoming':
            return $this->_leerInfoLlamadaIncoming($idCampania, $idLlamada);
        case 'outgoing':
            return $this->_leerInfoLlamadaOutgoing($idCampania, $idLlamada);
        default:
            return NULL;
    	}
    }

    // Leer la información de una llamada saliente. La información incluye lo
    // almacenado en la tabla calls, más los atributos asociados a la llamada
    // en la tabla call_attribute, y los datos ya recogidos en las tablas 
    // form_data_recolected y form_field
    private function _leerInfoLlamadaOutgoing($idCampania, $idLlamada)
    {
    	// Leer información de la llamada principal
        $sPeticionSQL = <<<INFO_LLAMADA
SELECT 'outgoing' AS calltype, calls.id AS call_id, id_campaign AS campaign_id, phone, status, uniqueid, 
    duration, datetime_originate, fecha_llamada AS datetime_originateresponse, 
    datetime_entry_queue AS datetime_join, start_time AS datetime_linkstart, 
    end_time AS datetime_linkend, retries, failure_cause, failure_cause_txt,
    agent.number AS agent_number 
FROM (calls) 
LEFT JOIN agent ON agent.id = calls.id_agent 
WHERE id_campaign = ? AND calls.id = ?
INFO_LLAMADA;
        $tuplaLlamada = $this->_dbConn->getRow($sPeticionSQL, 
            array($idCampania, $idLlamada), DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaLlamada)) {
            $this->oMainLog->output("ERR: no se puede leer información de llamada saliente - ".
                $tuplaLlamada->getMessage());
        	return NULL;
        } elseif (count($tuplaLlamada) <= 0) {
        	// No se encuentra la llamada indicada
            return array();
        }
        if (!is_null($tuplaLlamada['agent_number']))
            $tuplaLlamada['agent_number'] = 'Agent/'.$tuplaLlamada['agent_number'];

        // Leer información de los atributos de la llamada
        $sPeticionSQL = <<<INFO_ATRIBUTOS
SELECT columna AS `label`, value, column_number AS `order`
FROM call_attribute
WHERE id_call = ?
ORDER BY column_number
INFO_ATRIBUTOS;
        $tuplaLlamada['call_attributes'] = $this->_dbConn->getAll($sPeticionSQL,
            array($idLlamada), DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaLlamada['call_attributes'])) {
            $this->oMainLog->output("ERR: no se puede leer atributos de llamada saliente - ".
                $tuplaLlamada['call_attributes']->getMessage());
        	return NULL;
        }

        // Leer información de los datos recogidos vía formularios
        $sPeticionSQL = <<<INFO_FORMULARIOS
SELECT form_field.id_form, form_field.id, form_field.etiqueta AS label, 
    form_data_recolected.value
FROM form_data_recolected, form_field
WHERE form_data_recolected.id_calls = ?
    AND form_data_recolected.id_form_field = form_field.id
ORDER BY form_field.id_form, form_field.orden
INFO_FORMULARIOS;
        $datosFormularios = $this->_dbConn->getAll($sPeticionSQL,
            array($idLlamada), DB_FETCHMODE_ASSOC);
        if (DB::isError($datosFormularios)) {
            $this->oMainLog->output("ERR: no se puede leer datos de formulario de llamada saliente - ".
                $datosFormularios->getMessage());
        	return NULL;
        }
        $tuplaLlamada['call_survey'] = array();
        foreach ($datosFormularios as $tuplaFormulario) {
        	$tuplaLlamada['call_survey'][$tuplaFormulario['id_form']][] = array(
                'id'    => $tuplaFormulario['id'],
                'label' => $tuplaFormulario['label'],
                'value' => $tuplaFormulario['value'],
            );
        }

        return $tuplaLlamada;
    }

    // Leer la información de la llamada entrante. En esta implementación, a
    // diferencia de las llamadas salientes, las llamadas entrantes tienen un
    // solo formulario, y su conjunto de atributos es fijo.
    private function _leerInfoLlamadaIncoming($idCampania, $idLlamada)
    {
        // Leer información de la llamada principal
        $sPeticionSQL = <<<INFO_LLAMADA
SELECT 'incoming' AS calltype, call_entry.id AS call_id, id_campaign AS campaign_id,
    callerid AS phone, status, uniqueid, duration, datetime_entry_queue AS datetime_join, 
    datetime_init AS datetime_linkstart, datetime_end AS datetime_linkend, 
    trunk, queue, id_contact, agent.number AS agent_number
FROM (call_entry, queue_call_entry)
LEFT JOIN agent ON agent.id = call_entry.id_agent
WHERE call_entry.id = ? AND call_entry.id_queue_call_entry = queue_call_entry.id
INFO_LLAMADA;
        $tuplaLlamada = $this->_dbConn->getRow($sPeticionSQL, 
            array($idLlamada), DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaLlamada)) {
            $this->oMainLog->output("ERR: no se puede leer información de llamada entrante - ".
                $tuplaLlamada->getMessage());
            return NULL;
        } elseif (count($tuplaLlamada) <= 0) {
            // No se encuentra la llamada indicada
            return array();
        }
        if (!is_null($tuplaLlamada['agent_number']))
            $tuplaLlamada['agent_number'] = 'Agent/'.$tuplaLlamada['agent_number'];

        // Leer información de los atributos de la llamada
        // TODO: agregar información de múltiples contactos con el mismo teléfono
        // TODO: expandir cuando se tenga tabla de atributos arbitrarios
        $idContact = $tuplaLlamada['id_contact'];
        unset($tuplaLlamada['id_contact']);
        $tuplaLlamada['call_attributes'] = array();
        if (!is_null($idContact)) {
            $sPeticionSQL = <<<INFO_ATRIBUTOS
SELECT name AS first_name, apellido AS last_name, telefono AS phone, cedula_ruc, origen AS contact_source
FROM contact WHERE id = ?
INFO_ATRIBUTOS;
            $atributosLlamada = $this->_dbConn->getRow($sPeticionSQL,
                array($idContact), DB_FETCHMODE_ASSOC);
            if (DB::isError($atributosLlamada)) {
                $this->oMainLog->output("ERR: no se puede leer atributos de llamada entrante - ".
                    $atributosLlamada->getMessage());
                return NULL;
            }
            $tuplaLlamada['call_attributes'] = array(
                array(
                    'label' =>  'first_name',
                    'value' =>  $atributosLlamada['first_name'],
                    'order' =>  1,
                ),
                array(
                    'label' =>  'last_name',
                    'value' =>  $atributosLlamada['last_name'],
                    'order' =>  2,
                ),
                array(
                    'label' =>  'phone',
                    'value' =>  $atributosLlamada['phone'],
                    'order' =>  3,
                ),
                array(
                    'label' =>  'cedula_ruc',
                    'value' =>  $atributosLlamada['cedula_ruc'],
                    'order' =>  4,
                ),
                array(
                    'label' =>  'contact_source',
                    'value' =>  $atributosLlamada['contact_source'],
                    'order' =>  5,
                ),
            );
        }

        // Leer información de todos los contactos que coincidan en callerid
        $tuplaLlamada['matching_contacts'] = array();
        $sPeticionSQL = <<<INFO_ATRIBUTOS
SELECT id, name AS first_name, apellido AS last_name, telefono AS phone, cedula_ruc
FROM contact WHERE telefono = ?
INFO_ATRIBUTOS;
        $listaContactos = $this->_dbConn->getAll($sPeticionSQL, 
            array($tuplaLlamada['phone']), DB_FETCHMODE_ASSOC);
        if (DB::isError($listaContactos)) {
            $this->oMainLog->output("ERR: no se puede leer posibles contactos de llamada entrante - ".
                $listaContactos->getMessage());
        	return NULL;
        }
        foreach ($listaContactos as $tuplaContacto) {
        	$tuplaLlamada['matching_contacts'][$tuplaContacto['id']] = array(
                array(
                    'label' =>  'first_name',
                    'value' =>  $tuplaContacto['first_name'],
                    'order' =>  1,
                ),
                array(
                    'label' =>  'last_name',
                    'value' =>  $tuplaContacto['last_name'],
                    'order' =>  2,
                ),
                array(
                    'label' =>  'phone',
                    'value' =>  $tuplaContacto['phone'],
                    'order' =>  3,
                ),
                array(
                    'label' =>  'cedula_ruc',
                    'value' =>  $tuplaContacto['cedula_ruc'],
                    'order' =>  4,
                ),
            );
        }

        // Leer información de los datos recogidos vía formularios
        $idCampaniaTupla = $tuplaLlamada['campaign_id'];
        $sPeticionSQL = <<<INFO_FORMULARIOS
SELECT form_field.id_form, form_field.id, form_field.etiqueta AS label, 
    form_data_recolected_entry.value
FROM form_data_recolected_entry, form_field
WHERE form_data_recolected_entry.id_call_entry = ?
    AND form_data_recolected_entry.id_form_field = form_field.id
ORDER BY form_field.id_form, form_field.orden
INFO_FORMULARIOS;
        $datosFormularios = $this->_dbConn->getAll($sPeticionSQL,
            array($idLlamada), DB_FETCHMODE_ASSOC);
        if (DB::isError($datosFormularios)) {
            $this->oMainLog->output("ERR: no se puede leer datos de formulario de llamada saliente - ".
                $datosFormularios->getMessage());
            return NULL;
        }
        $tuplaLlamada['call_survey'] = array();
        foreach ($datosFormularios as $tuplaFormulario) {
            $tuplaLlamada['call_survey'][$tuplaFormulario['id_form']][] = array(
                'id'    => $tuplaFormulario['id'],
                'label' => $tuplaFormulario['label'],
                'value' => $tuplaFormulario['value'],
            );
        }

        return $tuplaLlamada;
    }

    // Callback invocado al llegar el evento Unlink
    function OnUnlink($sEvent, $params, $sServer, $iPort)
    {    
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnUnlink");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }

        $sKey = NULL;
        foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
            if (isset($tupla->Uniqueid)) {
                if (isset($params['Uniqueid']) && $tupla->Uniqueid == $params['Uniqueid']) $sKey = $key;
                if (isset($params['Uniqueid1']) && $tupla->Uniqueid == $params['Uniqueid1']) $sKey = $key;
                if (isset($params['Uniqueid2']) && $tupla->Uniqueid == $params['Uniqueid2']) $sKey = $key;
            }
        }
        if (!is_null($sKey) && $sEvent == 'unlink') {
        	/* En caso de que la llamada haya sido puesta en espera, la llamada 
             * se transfiere a la cola de parqueo. Esto ocasiona un evento Unlink
             * sobre la llamada, pero no debe de considerarse como el cierre de
             * la llamada.
             */
            $hold = $this->_dbConn->getOne(
                'SELECT hold FROM current_calls WHERE id_call = ?',
                array($this->_infoLlamadas['llamadas'][$sKey]->id),
                DB_FETCHMODE_ASSOC);
            if (DB::isError($hold)) {
                $this->oMainLog->output("ERR: $sEvent: no se puede consultar petición HOLD de llamada - ".$hold->getMessage());
            } elseif ($hold == 'S') {
            	/* Llamada ha sido puesta en hold. Se omite procesamiento futuro */
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: $sEvent: llamada ".($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid).
                        " ha sido puesta en HOLD en vez de colgada.");
                }
                $this->_infoLlamadas['llamadas'][$sKey]->status = 'OnHold';
                $result =& $this->_dbConn->query(
                    "UPDATE calls SET status = 'OnHold' WHERE id = ?",
                    array($this->_infoLlamadas['llamadas'][$sKey]->id));
                if (DB::isError($result)) {
                    $this->oMainLog->output("ERR: $sEvent: no se puede actualizar estado de llamada actual a HOLD - ".$result->getMessage());
                }
                $sKey = NULL;
            }
        }
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnUnlink");        	
        }
        return FALSE;
    }

    // Callback invocado al llegar el evento Hangup
    function OnHangup($sEvent, $params, $sServer, $iPort)
    {    
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnHangup");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }

        // Verificar si este es un colgado de una llamada para login de agente
        // estático, que fue contestada pero no se logoneó.
        if (isset($params['Uniqueid'])) {
            $sAgente = FALSE;
            foreach ($this->_infoAgentes as $sKey => $infoAgente) {
            	if ($infoAgente['Uniqueid'] == $params['Uniqueid']) {
            		$sAgente = $sKey;
                    break;
            	}
            }
            if ($sAgente !== FALSE) {
            	// Agente colgó antes de logonearse con contraseña
                $this->quitarIntentoLoginAgente($sAgente);
                $this->_dialSrv->notificarEvento_AgentLogin($sAgente, NULL, FALSE);
                unset($this->_infoAgentes[$sAgente]);
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: AgentLogin($sAgente) cuelga antes de introducir contraseña");
                    $this->oMainLog->output("DEBUG: EXIT OnHangup");
                }
                return FALSE;
            }
        }

        // Verificar si es una llamada entrante monitoreada. Si lo es, 
        // se termina el procesamiento sin hacer otra cosa
        if ($this->_oGestorEntrante->notificarHangup($params)) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: llamada manejada por GestorLlamadasEntrantes::notificarHangup");
                $this->oMainLog->output("DEBUG: EXIT OnHangup");
            }
            return FALSE;
        }

        // Verificar si es llamada entrante puesta en hold y luego colgada
        foreach (array_keys($this->_infoAgentes) as $sAgente) {
        	if (!is_null($this->_infoAgentes[$sAgente]['info_hold']) &&
                $this->_infoAgentes[$sAgente]['info_hold']['tabla'] == 'current_call_entry' &&
                $this->_infoAgentes[$sAgente]['info_hold']['ActualChannel'] == $params['Channel']) {
                
                if ($this->DEBUG) {
                    $this->oMainLog->output('DEBUG: hangup de canal correspondiente a HOLD huérfano entrante');
                }
                
                // Mandar a actualizar el mapa de UID para evitar fugas de memoria
                $id_viejo = $this->_dbConn->getOne(
                    'SELECT uniqueid FROM call_entry WHERE id = ?', 
                    array($this->_infoAgentes[$sAgente]['info_hold']['id_call']));
                if (DB::isError($id_viejo)) {
                	$this->oMainLog->output('ERR: no se puede consultar uniqueid viejo para llamada entrante - '.$id_viejo->getMessage());
                    $id_viejo = NULL;
                } else {
                	$this->_oGestorEntrante->actualizarMapaUID($id_viejo, $params['Uniqueid']);
                }
                
                /* Quitar las banderas de hold para que notificarHangup no 
                 * crea que la llamada recién entra en hold. */
                $result =& $this->_dbConn->query(
                    'UPDATE call_entry SET status = "activa", uniqueid = ? WHERE id = ?', 
                    array($params['Uniqueid'], $this->_infoAgentes[$sAgente]['info_hold']['id_call']));
                if (DB::isError($result)) {
                    $this->oMainLog->output(
                        "ERR: no se puede actualizar estado de llamada entrante (hold->activa) (1) - ".
                        $result->getMessage());
                }
                $result =& $this->_dbConn->query(
                    'UPDATE current_call_entry SET hold = "N", uniqueid = ? WHERE id = ?',
                    array($params['Uniqueid'], $this->_infoAgentes[$sAgente]['info_hold']['id_current_call']));
                if (DB::isError($result)) {
                    $this->oMainLog->output(
                        "ERR: no se puede actualizar estado de llamada entrante (hold->activa) (2) - ".
                        $result->getMessage());
                }

                if ($this->_oGestorEntrante->notificarHangup($params)) {
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: llamada manejada por GestorLlamadasEntrantes::notificarHangup");
                    }
                } else {
                	$this->oMainLog->output('ERR: GestorLlamadasEntrantes::notificarHangup no reconoce llamada entrante.');
                }
                
                // En cualquier caso el hold ya no es válido en este punto.

                // Ejecutar realmente el retiro de la pausa en todas las colas
                $this->_quitarPausaAgente($sAgente);

                // Auditoría del fin del hold
                $this->marcarFinalBreakAgente($this->_infoAgentes[$sAgente]['info_hold']['id_hold']);

                $tuplaHold = $this->_dbConn->getRow(
                    'SELECT datetime_init, datetime_end, TIME_TO_SEC(duration) AS duration_sec FROM audit WHERE id = ?', 
                    array($this->_infoAgentes[$sAgente]['info_hold']['id_hold']),
                    DB_FETCHMODE_ASSOC);
                if (DB::isError($tuplaHold)) {
                    $this->oMainLog->output('ERR: (internal) al consultar fecha final hold - '.$tuplaHold->getMessage());
                    $tuplaHold = array(
                        'datetime_init' =>  date('Y-m-d H:i:s'),
                        'datetime_end'  =>  date('Y-m-d H:i:s'),
                        'duration_sec'  =>  0,
                    );
                }
                $this->_dialSrv->notificarEvento_PauseEnd($sAgente, array(
                    'pause_class'   =>  'hold',
                    'pause_start'   =>  $tuplaHold['datetime_init'],
                    'pause_end'     =>  $tuplaHold['datetime_end'],
                    'pause_duration'=>  $tuplaHold['duration_sec'],
                ));

                $this->_infoAgentes[$sAgente]['info_hold'] = NULL;
                break;
            }
        }
        
        // Revisar si la llamada es alguna de las llamadas salientes monitoreadas
        $sKey = NULL;
        foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
            if (isset($tupla->Uniqueid)) {
                if (isset($params['Uniqueid']) && $tupla->Uniqueid == $params['Uniqueid']) $sKey = $key;
                if (isset($params['Uniqueid1']) && $tupla->Uniqueid == $params['Uniqueid1']) $sKey = $key;
                if (isset($params['Uniqueid2']) && $tupla->Uniqueid == $params['Uniqueid2']) $sKey = $key;
            }
        }

        /* Si no se encuentra la llamada, es posible que se haya recibido Hangup del canal
         * de una llamada que se ha enviado a HOLD, y que ya ha expirado su espera y debe
         * finalizarse. Entonces OnHold será verdadero, y Channel coincidirá con el 
         * ActualChannel de una de las llamadas. Además debe de localizarse el agente ECCP
         * que mandó a HOLD la llamada y quitarle la pausa. */
        if (is_null($sKey)) {
        	foreach ($this->_infoLlamadas['llamadas'] as $key => $tupla) {
                if ($tupla->OnHold && $tupla->ActualChannel == $params['Channel']) {
                    $sKey = $key;

                    if ($this->DEBUG) {
                    	$this->oMainLog->output('DEBUG: hangup de canal correspondiente a HOLD huérfano: '.
                            $sKey.' => '.print_r($this->_infoLlamadas['llamadas'][$sKey], 1));
                    }
                    $this->_infoLlamadas['llamadas'][$sKey]->OnHold = FALSE;

                    // Se busca el agente ECCP que haya puesto en HOLD la llamada
                    foreach (array_keys($this->_infoAgentes) as $sAgente) {
                        if (!is_null($this->_infoAgentes[$sAgente]['info_hold']) &&
                            $this->_infoAgentes[$sAgente]['info_hold']['ActualChannel'] == $params['Channel'] &&
                            $this->_infoAgentes[$sAgente]['info_hold']['tabla'] == 'current_calls') {

                            if ($this->DEBUG) {
                            	$this->oMainLog->output('DEBUG: llamada fue puesta en HOLD por '.$sAgente.
                                    ', se termina pausa y auditoría de HOLD.');
                            }

                            // Ejecutar realmente el retiro de la pausa en todas las colas
                            $this->_quitarPausaAgente($sAgente);

                            // Restaurar Success en lugar de OnHold para estado de llamada
                            $this->_infoLlamadas['llamadas'][$sKey]->status = 'Success';
                            $sPeticionSQL = 'UPDATE calls SET status = ? WHERE id = ? and status = ?';
                            $r = $this->_dbConn->query($sPeticionSQL, 
                                array('Success', $this->_infoAgentes[$sAgente]['info_hold']['id_call'], 'OnHold'));
                            if (DB::isError($r)) {
                                $this->oMainLog->output('ERR: al terminar hold: no se puede marcar la llamada como fuera de hold - '.$r->getMessage());
                            }

                            // Auditoría del fin del hold
                            $this->marcarFinalBreakAgente($this->_infoAgentes[$sAgente]['info_hold']['id_hold']);

                            $tuplaHold = $this->_dbConn->getRow(
                                'SELECT datetime_init, datetime_end, TIME_TO_SEC(duration) AS duration_sec FROM audit WHERE id = ?', 
                                array($this->_infoAgentes[$sAgente]['info_hold']['id_hold']),
                                DB_FETCHMODE_ASSOC);
                            if (DB::isError($tuplaHold)) {
                                $this->oMainLog->output('ERR: (internal) al consultar fecha final hold - '.$tuplaHold->getMessage());
                                $tuplaHold = array(
                                    'datetime_init' =>  date('Y-m-d H:i:s'),
                                    'datetime_end'  =>  date('Y-m-d H:i:s'),
                                    'duration_sec'  =>  0,
                                );
                            }
                            $this->_dialSrv->notificarEvento_PauseEnd($sAgente, array(
                                'pause_class'   =>  'hold',
                                'pause_start'   =>  $tuplaHold['datetime_init'],
                                'pause_end'     =>  $tuplaHold['datetime_end'],
                                'pause_duration'=>  $tuplaHold['duration_sec'],
                            ));

                            $this->_infoAgentes[$sAgente]['info_hold'] = NULL;
                            break;
                        }
                    }
                    break;
                }
            }
        }

        if (!is_null($sKey)) {

            // Revisar si la llamada es una llamada vía dialplan que se está mandando a HOLD 
            if ($this->_infoLlamadas['llamadas'][$sKey]->OnHold && 
                $params['Uniqueid'] == $this->_infoLlamadas['llamadas'][$sKey]->Uniqueid) {
            	if ($this->DEBUG) {
                    $this->oMainLog->output('DEBUG: se ignora Hangup para llamada que se envía a HOLD: '.
                        $sKey.' => '.print_r($this->_infoLlamadas['llamadas'][$sKey], 1));
            	}
                return FALSE;
            }

            $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp = time();
            
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: $sEvent: llamada $sKey => ".
                    print_r($this->_infoLlamadas['llamadas'][$sKey], TRUE));
            }

            // Leer el agente que fue asignado a esta llamada
            $sLeerAgenteLlamada = "SELECT agentnum FROM current_calls WHERE Uniqueid = ?";
            $idAgente =& $this->_dbConn->getOne($sLeerAgenteLlamada, array($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid));
            if (DB::isError($idAgente)) $idAgente = NULL;

            // Borrado de la llamada objetivo
            $sBorradoLlamada = 'DELETE FROM current_calls WHERE Uniqueid = ?';
            
            $bErrorLocked = FALSE;
            do {
                $bErrorLocked = FALSE;
                $result =& $this->_dbConn->query($sBorradoLlamada, array($this->_infoLlamadas['llamadas'][$sKey]->Uniqueid));
                if (DB::isError($result)) {
                    $bErrorLocked = ereg('database is locked', $result->getMessage());
                    if ($bErrorLocked) {
                        usleep(125000);
                    } else {
                        $this->oMainLog->output("ERR: $sEvent: no se puede purgar llamada - ".$result->getMessage());
                    }
                }        
            } while (DB::isError($result) && $bErrorLocked);

            // Se ha observado que ocasionalmente se pierde el evento Link
            $idCampaign = $this->_infoLlamadas['llamadas'][$sKey]->id_campaign;
            if (!isset($this->_infoLlamadas['llamadas'][$sKey]->start_timestamp) || 
                is_null($this->_infoLlamadas['llamadas'][$sKey]->start_timestamp)) {
                if (!is_null($this->_infoLlamadas['llamadas'][$sKey]->Channel) &&
                    strpos($params["Channel"], $this->_infoLlamadas['llamadas'][$sKey]->Channel) !== false) {
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: detectada Hangup intermedio de llamada agendada.");
                        $this->oMainLog->output("DEBUG: EXIT OnHangup");
                    }
                    return FALSE;
                }

                /* Si se detecta el Hangup antes del OriginateResponse, se 
                 * recoge el evento para que el OriginateResponse pueda reportar
                 * información adicional sobre el fallo de la llamada.
                 */
                if (is_null($this->_infoLlamadas['llamadas'][$sKey]->OriginateEnd)) {
                	if ($this->DEBUG) {
                		$this->oMainLog->output("DEBUG: Hangup de llamada por fallo de Originate");                        
                	}
                    if (!is_array($this->_infoLlamadas['llamadas'][$sKey]->PendingEvents))
                        $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents = array();
                    $this->_infoLlamadas['llamadas'][$sKey]->PendingEvents['Hangup'] = $params;
                    if ($this->DEBUG) {
                    	$this->oMainLog->output("DEBUG: EXIT OnHangup");
                    }
                    return FALSE;
                }

                $this->oMainLog->output("ERR: $sEvent: Hangup sin Link para llamada $sKey => ".
                    print_r($this->_infoLlamadas['llamadas'][$sKey], TRUE));
                
                $this->_agregarTiempoContestar(
                    $this->_infoLlamadas['llamadas'][$sKey]->id_campaign, 
                    $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp - $this->_infoLlamadas['llamadas'][$sKey]->OriginateStart);

                // Resetear estado de llamada, para volver a intentarla
                $sActualizarLlamada = 
                    'UPDATE calls SET datetime_entry_queue = ?, duration_wait = ?, '.
                        'start_time = NULL, end_time = ?, duration = NULL, '.
                        'status = ?, retries = retries + 1 '.
                    'WHERE id = ?';
                if (!isset($this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp) || 
                    is_null($this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp)) {
                    // Escenario en que llamada nunca fue respondida
                    $this->_infoLlamadas['llamadas'][$sKey]->status = 'NoAnswer';
                    $updateParams = array(
                        NULL, 
                        NULL, 
                        NULL, 
                        'NoAnswer', 
                        $this->_infoLlamadas['llamadas'][$sKey]->id);
                } else {
                    // Escenario en que llamada fue respondida y entró a cola, pero
                    // ningún agente se desocupó a tiempo para atenderla.
                    $this->_infoLlamadas['llamadas'][$sKey]->status = 'Abandoned';
                    $updateParams = array(
                        date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp), 
                        $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp - $this->_infoLlamadas['llamadas'][$sKey]->enterqueue_timestamp, 
                        date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp), 
                        'Abandoned', 
                        $this->_infoLlamadas['llamadas'][$sKey]->id);
                }
                $result =& $this->_dbConn->query($sActualizarLlamada, $updateParams);
                if (DB::isError($result)) {
                    $this->oMainLog->output("ERR: $sEvent: no se puede resetear llamada actual - ".$result->getMessage());
                }
            } else {

                // Calcular duración de llamada, para poder actualizar promedio y desviación estándar
                $iDuracionLlamada = $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp -
                    $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp;
                if ($this->DEBUG) {
                    $this->oMainLog->output("DEBUG: duración de la llamada fue de $iDuracionLlamada s.");
                }

                $bLlamadaCorta = ($iDuracionLlamada <= $this->_iUmbralLlamadaCorta);                
                if ($bLlamadaCorta) {
                    // Llamada corta que no se ha podido empezar a hablar
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: llamada fue identificada como llamada corta!");
                    }
                    $this->_infoLlamadas['llamadas'][$sKey]->status = 'ShortCall';
                    $sActualizarLlamada = 'UPDATE calls SET end_time = ?, duration = ?, start_time = ?, status = "ShortCall" WHERE id = ?';
                    $result =& $this->_dbConn->query($sActualizarLlamada, 
                        array(date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp), 
                            $iDuracionLlamada,
                            date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->start_timestamp), 
                            $this->_infoLlamadas['llamadas'][$sKey]->id));
                    if (DB::isError($result)) {
                        $this->oMainLog->output("ERR: $sEvent: no se puede actualizar fecha fin llamada actual - ".$result->getMessage());
                    }
                } else {
                    // Actualización de momento de fin de llamada y duración
                    $sActualizarLlamada = 'UPDATE calls SET end_time = ?, duration = ? WHERE id = ?';
                    $result =& $this->_dbConn->query($sActualizarLlamada, 
                        array(date('Y-m-d H:i:s', $this->_infoLlamadas['llamadas'][$sKey]->end_timestamp), 
                            $iDuracionLlamada, 
                            $this->_infoLlamadas['llamadas'][$sKey]->id));
                    if (DB::isError($result)) {
                        $this->oMainLog->output("ERR: $sEvent: no se puede actualizar fecha fin llamada actual - ".$result->getMessage());
                    }

                    // Puede ocurrir que se haya parado la campaña, y ya no esté en el
                    // arreglo, pero las llamadas generadas bajo esta campaña todavía 
                    // estén rezagadas.
                    if (!isset($this->_infoLlamadas['campanias'][$idCampaign])) {
                        $tuplaCampaign = $this->_leerCampania($idCampaign);
                        if (!is_null($tuplaCampaign)) $this->_infoLlamadas['campanias'][$idCampaign] = $tuplaCampaign;
                    }
                    
                    // Calcular promedio y desviación estándar
                    if (is_null($this->_infoLlamadas['campanias'][$idCampaign]->num_completadas))
                        $this->_infoLlamadas['campanias'][$idCampaign]->num_completadas = 0;

                    // Calcular nuevo promedio
                    if ($this->_infoLlamadas['campanias'][$idCampaign]->num_completadas > 0) {
                        $iNuevoPromedio = $this->_nuevoPromedio(
                            $this->_infoLlamadas['campanias'][$idCampaign]->promedio, 
                            $this->_infoLlamadas['campanias'][$idCampaign]->num_completadas, 
                            $iDuracionLlamada);
                    } else {
                        $iNuevoPromedio = $iDuracionLlamada;
                    }

                    // Calcular nueva desviación estándar
                    if ($this->_infoLlamadas['campanias'][$idCampaign]->num_completadas > 1) {
                        $iNuevaVariancia = $this->_nuevaVarianciaMuestra(
                            $this->_infoLlamadas['campanias'][$idCampaign]->promedio,
                            $iNuevoPromedio,
                            $this->_infoLlamadas['campanias'][$idCampaign]->num_completadas, 
                            $this->_infoLlamadas['campanias'][$idCampaign]->variancia,
                            $iDuracionLlamada);
                    } else if ($this->_infoLlamadas['campanias'][$idCampaign]->num_completadas == 1) {
                        $iViejoPromedio = $this->_infoLlamadas['campanias'][$idCampaign]->promedio;
                        $iNuevaVariancia = 
                            ($iViejoPromedio - $iNuevoPromedio) * ($iViejoPromedio - $iNuevoPromedio) + 
                            ($iDuracionLlamada - $iNuevoPromedio) * ($iDuracionLlamada - $iNuevoPromedio);
                    } else {
                        $iNuevaVariancia = NULL;                
                    }            
                    $this->_infoLlamadas['campanias'][$idCampaign]->num_completadas++;
                    $this->_infoLlamadas['campanias'][$idCampaign]->promedio = $iNuevoPromedio;
                    $this->_infoLlamadas['campanias'][$idCampaign]->variancia = $iNuevaVariancia;
                    $this->_infoLlamadas['campanias'][$idCampaign]->desviacion = sqrt($iNuevaVariancia);

                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: luego de ".($this->_infoLlamadas['campanias'][$idCampaign]->num_completadas)." llamadas: ".
                            sprintf('prom: %.2f var: %.2f std.dev: %.2f', 
                                $this->_infoLlamadas['campanias'][$idCampaign]->promedio,
                                $this->_infoLlamadas['campanias'][$idCampaign]->variancia,
                                $this->_infoLlamadas['campanias'][$idCampaign]->desviacion));
                    }

                    // Actualizar datos estadísticos de campaña
                    $sActualizarCampania = 'UPDATE campaign SET num_completadas = ?, promedio = ?, desviacion = ? WHERE id = ?';
                    do {
                        $bErrorLocked = FALSE;
                        $result =& $this->_dbConn->query(
                            $sActualizarCampania,
                            array(
                                $this->_infoLlamadas['campanias'][$idCampaign]->num_completadas,
                                $this->_infoLlamadas['campanias'][$idCampaign]->promedio,
                                $this->_infoLlamadas['campanias'][$idCampaign]->desviacion,
                                $idCampaign));
                        if (DB::isError($result)) {
                            $bErrorLocked = ereg('database is locked', $result->getMessage());
                            if ($bErrorLocked) {
                                usleep(125000);
                            } else {
                                $this->oMainLog->output("ERR: $sEvent: no se puede insertar llamada actual - ".$result->getMessage());
                            }
                        }
                    } while (DB::isError($result) && $bErrorLocked);
                    
                    // Notificar que la llamada ha sido colgada
                    // FIXME: esto asume formato Agent/9000
                    if ($this->DEBUG) {
                        $this->oMainLog->output('DEBUG: OnHangup: notificando evento AgentUnlinked');
                    }                
                    $this->_dialSrv->notificarEvento_AgentUnlinked("Agent/$idAgente", array(
                        'calltype'      =>  'outgoing',
                        'campaign_id'   =>  $idCampaign,
                        'call_id'       =>  $this->_infoLlamadas['llamadas'][$sKey]->id,
                        'phone'         =>  $this->_infoLlamadas['llamadas'][$sKey]->phone,
                    ));
                    
                } /* !$bLlamadaCorta */
            } /* is_null(start_timestamp) */

            // Sacar de pausa al agente cuya llamada ha terminado
            $infoCampania = $this->_infoLlamadas['campanias'][$idCampaign];
            if (!is_null($idAgente)) {
                $sAgent = "Agent/$idAgente";
                if (isset($this->_infoLlamadas['agentes_reservados'][$idAgente])) {
                    $this->_infoLlamadas['agentes_reservados'][$idAgente] = 1;

                    // El agente debe ser sacado de pausa sólo si no hay más llamadas en reserva
                    $l = $this->_contarLlamadasAgendablesReserva($infoCampania, $idAgente);
                    if ($l['AHORA'] == 0 && $l['RESERVA'] == 0) {
                        
                        // TODO: POSIBLE PUNTO DE REENTRANCIA
                        $resultado = $this->_astConn->QueuePause($infoCampania->queue, $sAgent, 'false');

                        if ($this->DEBUG) {
                            $this->oMainLog->output("DEBUG: OnHangup: $sAgent no tiene más llamadas agendadas, se quita pausa...");
                            $this->oMainLog->output("DEBUG: resultado de QueuePause($infoCampania->queue, $sAgent, 'false') : ".
                                print_r($resultado, TRUE));
                        }
                        unset($this->_infoLlamadas['agentes_reservados'][$idAgente]);
                    }
                }
            }

            
            // Al fin, quitar la llamada del arreglo de llamadas
            unset($this->_infoLlamadas['llamadas'][$sKey]);
        } else {
        	/* No se encuentra la llamada entre las monitoreadas. Puede ocurrir
             * que este sea el Hangup de un canal auxiliar que tiene información
             * de la falla de la llamada */
            foreach (array_keys($this->_infoLlamadas['llamadas']) as $key) {
                if (is_null($this->_infoLlamadas['llamadas'][$key]->OriginateEnd) &&
                    isset($this->_infoLlamadas['llamadas'][$key]->AuxChannels) && 
                    isset($this->_infoLlamadas['llamadas'][$key]->AuxChannels[$params['Uniqueid']])) {
                	$this->_infoLlamadas['llamadas'][$key]->AuxChannels[$params['Uniqueid']]['Hangup'] = $params;
                    if ($this->DEBUG) {
                        $this->oMainLog->output("DEBUG: Hangup de canal auxiliar de llamada por fallo de Originate para llamada ".
                            $this->_infoLlamadas['llamadas'][$key]->Uniqueid." canal auxiliar ".$params['Uniqueid']);                        
                    }
                    break;
                }
            }
        }
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnHangup");
        }
        return FALSE;
    }

    // Función para llevar la cuenta de un intento de login vía Originate
    function agregarIntentoLoginAgente($sAgente, $sExtension)
    {
        $this->_intentoLoginAgente[$sAgente] = $sExtension;
    }
    
    function quitarIntentoLoginAgente($sAgente)
    {
    	unset($this->_intentoLoginAgente[$sAgente]);
    }

    function obtenerIntentoLoginAgente($sAgente)
    {
        return isset($this->_intentoLoginAgente[$sAgente]) 
            ? $this->_intentoLoginAgente[$sAgente] : NULL;
    }

    // Callback llamado cuando un agente se logonea a una cola
    function OnAgentlogin($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnAgentlogin");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }

        // Verificar que este evento corresponde a un Agentlogin iniciado por este programa
        $sAgente = 'Agent/'.$params['Agent'];
        if (!isset($this->_infoAgentes[$sAgente])) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: AgentLogin($sAgente) no iniciado por programa, no se hace nada.");
                $this->oMainLog->output("DEBUG: EXIT OnAgentlogin");
            }
            return FALSE;
        }
        $this->quitarIntentoLoginAgente($sAgente);
        $this->_infoAgentes[$sAgente]['estado_consola'] = 'logged-in';
        
        // Escribir la información de auditoría en la base de datos
        $this->_infoAgentes[$sAgente]['id_sesion'] = $this->marcarInicioSesionAgente($sAgente);
        if (is_null($this->_infoAgentes[$sAgente]['id_sesion'])) {
            // Ha fallado la inserción del registro de auditoría
            unset($this->_infoAgentes[$sAgente]);
            $this->_astConn->Agentlogoff($params['Agent']);
            $this->_dialSrv->notificarEvento_AgentLogin($sAgente, NULL, FALSE);
        }

        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnAgentlogin");
        }
        return FALSE;
    }

    /**
     * Método de conveniencia para verificar si el agente está en pausa, y 
     * quitar la pausa en caso de encontrarla.
     *
     * @param   string  Canal del agente que se está logoneando: "Agent/9000"
     *
     * @return  VERDADERO en éxito, FALSE en error
     */
    private function quitarPosiblePausa($sAgente)
    {
        if ($this->estadoAgentePausa($sAgente)) {
            $r = $this->_astConn->QueuePause(NULL, $sAgente, "false");
            // TODO: funcionó la operación de QueuePause ? $r['Response']
        }
        return TRUE;
    }

    /**
     * Método para verificar el estado de pausa de un agente.
     *
     * @param   string  Canal del agente que se está logoneando: "Agent/9000"
     *
     * @return  VERDADERO si está en pausa, FALSE si no, o si agente no existe.
     */
    private function estadoAgentePausa($sAgente)
    {
        $r = $this->_astConn->Command("queue show");
        $listaLineas = explode("\n", $r['data']);
        foreach ($listaLineas as $sLinea) {
            $regs = NULL;
            if (preg_match('|^\s*(\w+/\d{2,})|', $sLinea, $regs)) {
                if ($sAgente == $regs[1]) {
                    // Se ha localizado el agente
                    return (strpos($sLinea, "(paused)") !== FALSE);
                }
            }
        }
        return FALSE; // No se encontró al agente
    }

    /**
     * Método para marcar en las tablas de auditoría que el agente ha iniciado
     * la sesión. Esta implementación verifica si el agente ya ha sido marcado
     * previamente como que inició la sesión, y sólo marca el inicio si no está
     * ya marcado antes.
     *
     * @param   string  $sAgente    Canal del agente que se verifica sesión
     *
     * @return  mixed   NULL en error, o el ID de la auditoría de inicio de sesión
     */
    private function marcarInicioSesionAgente($sAgente)
    {
        // Si el agente está en pausa, se la quita ahora
        $this->quitarPosiblePausa($sAgente);
        
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return NULL;
        }

        // Se averigua el ID del agente, dado el número
        $tupla = $this->_dbConn->getRow(
            "SELECT id FROM agent WHERE number = ? AND estatus = 'A'", 
            array($sNumAgente), DB_FETCHMODE_ASSOC);
        if (DB::isError($tupla)) {
            $this->oMainLog->output('ERR: (internal) Unable to fetch agent ID - '.$tupla->getMessage());
            return NULL;
        } elseif (count($tupla) <= 0) {
            $this->oMainLog->output('ERR: Invalid or inactive agent: '.$sNumAgente);
            return NULL;
        }
        $idAgente = $tupla['id'];

        // Verificación de sesión activa
        $sPeticionExiste = <<<SQL_EXISTE_AUDIT
SELECT id FROM audit
WHERE id_agent = ? AND datetime_end IS NULL AND duration IS NULL AND id_break IS NULL
ORDER BY datetime_init DESC
SQL_EXISTE_AUDIT;
        $tupla = $this->_dbConn->getRow($sPeticionExiste, array($idAgente), DB_FETCHMODE_ASSOC);
        if (DB::isError($tupla)) {
            $this->oMainLog->output('ERR: (internal) Unable to check agent session - '.$tupla->getMessage());
            return NULL;
        }
        
        // Se indica éxito de inmediato si ya hay una sesión
        $idAudit = NULL;
        if (count($tupla) > 0) {
            $idAudit = $tupla['id'];
        } else {
            // Ingreso de sesión del agente
            $sTimeStamp = date('Y-m-d H:i:s');
            $r = $this->_dbConn->query(
                'INSERT INTO audit (id_agent, datetime_init) VALUES (?, ?)', 
                array($idAgente, $sTimeStamp));
            if (DB::isError($r)) {
                $this->oMainLog->output('(internal) Unable to start agent session - '.$r->getMessage());
                return NULL;
            }
    
            // Se recoge la tupla con el ID de inserción antes de que se sobreescriba
            $tupla = $this->_dbConn->getRow('SELECT LAST_INSERT_ID()');
            $idAudit = $tupla[0];
        }

        // Reportar todas las colas a la que pertenece un agente
        $listaColas = $this->_listarColasAgente($sAgente);

        // Notificar a todas las conexiones abiertas
        $this->_dialSrv->notificarEvento_AgentLogin($sAgente, $listaColas, TRUE);

        return $idAudit;
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
    private function marcarFinalSesionAgente($sAgente, $idAuditSesion, $idAuditBreak)
    {
        // Quitar posibles pausas sobre el agente
        $this->quitarPosiblePausa($sAgente);
        
        if (!is_null($idAuditBreak))
            $this->marcarFinalBreakAgente($idAuditBreak);
        $sTimeStamp = date('Y-m-d H:i:s');
        $r = $this->_dbConn->query(
            'UPDATE audit SET datetime_end = ?, duration = TIMEDIFF(?, datetime_init) WHERE id = ?',
            array($sTimeStamp, $sTimeStamp, $idAuditSesion));
        if (DB::isError($r)) {
            $this->oMainLog->output('ERR: (internal) Unable to end agent session - '.$r->getMessage());
            return FALSE;
        }

        // Reportar todas las colas a la que pertenece un agente
        $listaColas = $this->_listarColasAgente($sAgente);

        // Notificar a todas las conexiones abiertas
        $this->_dialSrv->notificarEvento_AgentLogoff($sAgente, $listaColas);

        return TRUE;
    }
    
    /**
     * Método para marcar en las tablas de auditoría que el agente ha entrado
     * en hold o break. 
     *
     * @param   string  $sAgente    Número del agente que se verifica sesión
     * @param   int     $idBreak    ID del break que el agente va a iniciar
     *
     * @return  mixed   NULL en error, o el ID de la auditoría de inicio de break
     */
    private function marcarInicioBreakAgente($sAgente, $idBreak)
    {
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return NULL;
        }

        // Se averigua el ID del agente, dado el número
        $tupla = $this->_dbConn->getRow(
            "SELECT id FROM agent WHERE number = ? AND estatus = 'A'", 
            array($sNumAgente), DB_FETCHMODE_ASSOC);
        if (DB::isError($tupla)) {
            $this->oMainLog->output('ERR: (internal) Unable to fetch agent ID - '.$tupla->getMessage());
            return NULL;
        } elseif (count($tupla) <= 0) {
            $this->oMainLog->output('ERR: Invalid or inactive agent: '.$sAgente);
            return NULL;
        }
        $idAgente = $tupla['id'];

        // Ingreso de sesión del agente
        $sTimeStamp = date('Y-m-d H:i:s');
        $r = $this->_dbConn->query(
            'INSERT INTO audit (id_agent, id_break, datetime_init) VALUES (?, ?, ?)',
            array($idAgente, $idBreak, $sTimeStamp));
        if (DB::isError($r)) {
            $this->oMainLog->output('ERR: (internal) Unable to start agent break - '.$r->getMessage());
            return NULL;
        }
        $tupla = $this->_dbConn->getRow('SELECT LAST_INSERT_ID()');
        return $tupla[0];
    }
    
    /**
     * Método para marcar en las tablas de auditoría que el agente ha terminado
     * su hold o break.
     *
     * @param   int     $idAuditBreak   ID del break devuelto por marcarInicioBreakAgente()
     *
     * @return  bool    VERDADERO en éxito, FALSE en error.
     */
    private function marcarFinalBreakAgente($idAuditBreak)
    {
        $sTimeStamp = date('Y-m-d H:i:s');
        $r = $this->_dbConn->query(
            'UPDATE audit SET datetime_end = ?, duration = TIMEDIFF(?, datetime_init) WHERE id = ?',
            array($sTimeStamp, $sTimeStamp, $idAuditBreak));
        if (DB::isError($r)) {
            $this->oMainLog->output('ERR: (internal) Unable to end agent break - '.$r->getMessage());
            return FALSE;
        }
        return TRUE;
    }

    private function _agregarPausaAgente($sAgente)
    {
        if ($this->_infoAgentes[$sAgente]['num_pausas'] == 0) {
            $r = $this->_astConn->QueuePause(NULL, $sAgente, 'true');
            if ($r['Response'] != 'Success') {
                $this->oMainLog->output('ERR: (internal) no se puede poner al agente en pausa: '.
                    $sAgente.' - '.$r['Message']);
                return FALSE;
            }
        }
        $this->_infoAgentes[$sAgente]['num_pausas']++;
        return TRUE;
    }

    private function _quitarPausaAgente($sAgente)
    {
        $this->_infoAgentes[$sAgente]['num_pausas']--;
        if ($this->_infoAgentes[$sAgente]['num_pausas'] <= 0) {
            $this->_infoAgentes[$sAgente]['num_pausas'] = 0;
            $r = $this->_astConn->QueuePause(NULL, $sAgente, 'false');
            if ($r['Response'] != 'Success') {
                $this->oMainLog->output('ERR: (internal) no se puede sacar al agente de pausa: '.
                    $sAgente.' - '.$r['Message']);
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Procedimiento para poner en pausa a un agente y asociar esta pausa a un
     * break en particular.
     * 
     * @param   string  $sAgente    Agente de la forma Agent/9000
     * @param   int     $idBreak    ID del break a usar para el agente
     * 
     * @return  bool    VERDADERO en caso de éxito, FALSO en error.
     */
    public function iniciarBreakAgente($sAgente, $idBreak)
    {
        // TODO: planear integración con agendamiento de llamadas

    	if (!isset($this->_infoAgentes[$sAgente])) {
            $this->oMainLog->output('ERR: (internal) al iniciar break: no se hace seguimiento ECCP al agente: '.$sAgente);
    		return FALSE;
    	}
        if ($this->_infoAgentes[$sAgente]['estado_consola'] != 'logged-in') {
            $this->oMainLog->output('ERR: (internal) al iniciar break: agente no está (todavía) logoneado: '.$sAgente);
            return FALSE;
        }
        if (!is_null($this->_infoAgentes[$sAgente]['id_break']) && 
            $this->_infoAgentes[$sAgente]['id_break'] != $idBreak) {
            $this->oMainLog->output('ERR: (internal) al iniciar break: agente ya está en break: '.$sAgente);
            return FALSE;
        }
        
        // Si el agente ya estaba en el break indicado, se devuelve éxito sin hacer nada más
        if (!is_null($this->_infoAgentes[$sAgente]['id_break']) && $this->_infoAgentes[$sAgente]['id_break'] == $idBreak)
            return TRUE;
        
        // Ejecutar realmente la pausa del agente indicado en todas las colas
        if (!$this->_agregarPausaAgente($sAgente)) return FALSE;
        
        // Auditoría del inicio del break
        $idAuditBreak = $this->marcarInicioBreakAgente($sAgente, $idBreak);
        if (is_null($idAuditBreak)) {
        	$this->oMainLog->output('ERR: (internal) no se puede auditar el inicio del break, se deshace pausa: '.$sAgente);
            $r = $this->_astConn->QueuePause(NULL, $sAgente, 'false');
            return FALSE;
        }
        $this->_infoAgentes[$sAgente]['id_break'] = $idAuditBreak;
        $infoPausa = $this->reportarEstadoPausaAgente($sAgente);
        $this->_dialSrv->notificarEvento_PauseStart($sAgente, array(
            'pause_class'   =>  'break',
            'pause_type'    =>  $idBreak,
            'pause_name'    =>  $infoPausa['break_name'],
            'pause_start'   =>  $infoPausa['datetime_breakstart'],
        ));
        return TRUE;
    }
    
    /**
     * Procedimiento para quitar de pausa a un agente y terminar el break
     * 
     * @param   string  $sAgente    Agente de la forma Agent/9000
     * 
     * @return  bool    VERDADERO en caso de éxito, FALSO en error.
     */
    public function terminarBreakAgente($sAgente)
    {
    	// TODO: planear integración con agendamiento de llamadas

        if (!isset($this->_infoAgentes[$sAgente])) {
            $this->oMainLog->output('ERR: (internal) al terminar break: no se hace seguimiento ECCP al agente: '.$sAgente);
            return FALSE;
        }
        if ($this->_infoAgentes[$sAgente]['estado_consola'] != 'logged-in') {
            $this->oMainLog->output('ERR: (internal) al terminar break: agente no está (todavía) logoneado: '.$sAgente);
            return FALSE;
        }
        
        // Si el agente no estaba en break, se devuelve éxito sin hacer nada
        if (is_null($this->_infoAgentes[$sAgente]['id_break'])) return TRUE;

        // Ejecutar realmente el retiro de la pausa en todas las colas
        if (!$this->_quitarPausaAgente($sAgente)) return FALSE;

        // Auditoría del fin del break
        $this->marcarFinalBreakAgente($this->_infoAgentes[$sAgente]['id_break']);
        $tuplaBreak = $this->_dbConn->getRow(
            'SELECT break.id AS break_id, break.name AS break_name, '.
                'audit.datetime_init AS datetime_breakstart, audit.datetime_end AS datetime_breakend, '.
                'TIME_TO_SEC(audit.duration) AS duration_sec '.
            'FROM audit, break '.
            'WHERE audit.id = ? AND audit.id_break = break.id', 
            array($this->_infoAgentes[$sAgente]['id_break']),
            DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaBreak)) {
        	$this->oMainLog->output('ERR: (internal) al terminar break: no se puede leer resumen de break - '.
                $tuplaBreak->getMessage());
            $tuplaBreak = array(
                'break_id'              =>  0,
                'break_name'            =>  '(unavailable)',
                'datetime_breakstart'   =>  date('Y-m-d H:i:s'),
                'datetime_breakend'     =>  date('Y-m-d H:i:s'),
                'duration_sec'          =>  0,
            );
        }
        $this->_dialSrv->notificarEvento_PauseEnd($sAgente, array(
            'pause_class'   =>  'break',
            'pause_type'    =>  $tuplaBreak['break_id'],
            'pause_name'    =>  $tuplaBreak['break_name'],
            'pause_start'   =>  $tuplaBreak['datetime_breakstart'],
            'pause_end'     =>  $tuplaBreak['datetime_breakend'],
            'pause_duration'=>  $tuplaBreak['duration_sec'],
        ));
        $this->_infoAgentes[$sAgente]['id_break'] = NULL;
        return TRUE;
    }

    public function reportarEstadoPausaAgente($sAgente)
    {
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return FALSE;
        }
        if (!isset($this->_infoAgentes[$sAgente])) {
        	return NULL;
        }

        $estadoPausa = array(
            'on_hold'   =>  !is_null($this->_infoAgentes[$sAgente]['info_hold']),
        );
        if (!is_null($this->_infoAgentes[$sAgente]['id_break'])) {
        	$sqlBreak = <<<LEER_TIPO_BREAK
SELECT audit.datetime_init, break.name, break.id 
FROM audit, break 
WHERE audit.id = ? AND audit.id_break = break.id
LEER_TIPO_BREAK;
            $tuplaBreak = $this->_dbConn->getRow($sqlBreak, array($this->_infoAgentes[$sAgente]['id_break']), DB_FETCHMODE_ASSOC);
            if (DB::isError($tuplaBreak)) {
                $this->oMainLog->output('ERR: no se puede leer información de agentes - '.$tuplaBreak->getMessage());
            } elseif (is_array($tuplaBreak)) {
                $estadoPausa['datetime_breakstart'] = $tuplaBreak['datetime_init'];
                $estadoPausa['break_name'] = $tuplaBreak['name'];
                $estadoPausa['break_id'] = $tuplaBreak['id'];
            }
        }
        
        return $estadoPausa;
    }

    public function transferirLlamadaAgente($sAgente, $sExtension)
    {
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return FALSE;
        }

        if (!isset($this->_infoAgentes[$sAgente])) {
            $this->oMainLog->output('ERR: (internal) al iniciar hold: no se hace seguimiento ECCP al agente: '.$sAgente);
            return FALSE;
        }
        if ($this->_infoAgentes[$sAgente]['estado_consola'] != 'logged-in') {
            $this->oMainLog->output('ERR: (internal) al iniciar hold: agente no está (todavía) logoneado: '.$sAgente);
            return FALSE;
        }

        /* Leer de la base de datos el canal que está conversando con el agente.
         * También debe de obtenerse el ID del registro en la tabla de 
         * current_calls o current_call_entry, según la que se descubra primero.
         */
        // Verificar si llamada es entrante...
        $tuplaLlamada = $this->_dbConn->getRow(
            'SELECT current_call_entry.id AS id_current_call, id_call_entry AS id_call, ChannelClient '.
            'FROM current_call_entry, agent '.
            'WHERE current_call_entry.id_agent = agent.id '.
                'AND agent.estatus = "A" AND agent.number = ?',
            array($sNumAgente), DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaLlamada)) {
            $this->oMainLog->output('ERR: al transferir llamada: no se puede verificar llamada entrante - '.
                $tuplaLlamada->getMessage());
            return FALSE;
        } elseif (count($tuplaLlamada) > 0) {
            // Llamada es entrante
            $tuplaLlamada['tabla'] = 'current_call_entry';
        }
        if (is_null($tuplaLlamada)) {
            // Verificar si llamada es saliente...
            $tuplaLlamada = $this->_dbConn->getRow(
                'SELECT id AS id_current_call, id_call, ChannelClient '.
                'FROM current_calls WHERE agentnum = ?',
                array($sNumAgente), DB_FETCHMODE_ASSOC);
            if (DB::isError($tuplaLlamada)) {
                $this->oMainLog->output('ERR: al transferir llamada: no se puede verificar llamada saliente - '.
                    $tuplaLlamada->getMessage());
                return FALSE;
            } elseif (count($tuplaLlamada) > 0) {
                // Llamada es saliente
                $tuplaLlamada['tabla'] = 'current_calls';
            }
        }
        if (is_null($tuplaLlamada)) {
        	// Preguntar el canal de la llamada directamente
            $oPredictor = new Predictivo($this->_astConn);
            $estadoCola = $oPredictor->leerEstadoCola(''); // El parámetro vacío lista todas las colas
            if (!isset($estadoCola['members'][$sNumAgente])) {
                $this->oMainLog->output('WARN: al transferir llamada: el siguiente agente no se encuentra: '.$sAgente);
            	return FALSE;
            }
            if (!isset($estadoCola['members'][$sNumAgente]['clientchannel'])) {
                $this->oMainLog->output('WARN: al transferir llamada: el siguiente agente no está atendiendo llamada: '.$sAgente);
                return FALSE;
            }
            $tuplaLlamada['ChannelClient'] = $estadoCola['members'][$sNumAgente]['clientchannel'];
            $tuplaLlamada['ActualChannel'] = $estadoCola['members'][$sNumAgente]['clientchannel'];
        }
        
        /* Los canales de tipo Local/XXX@from-internal no sirven para realizar
         * transferencias de llamada. Se debe de averiguar el verdadero canal que 
         * aparece en el reporte de "agent show" */
        if (strpos($tuplaLlamada['ChannelClient'], 'Local/') === 0) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: agente $sAgente conectado con ".
                    "ChannelClient={$tuplaLlamada['ChannelClient']}, buscando ".
                    "verdadero canal...");
            }
            $oPredictor = new Predictivo($this->_astConn);
            $estadoCola = $oPredictor->leerEstadoCola(''); // El parámetro vacío lista todas las colas
            if (!isset($estadoCola['members'][$sNumAgente])) {
                $this->oMainLog->output('ERR: (internal) al transferir llamada: agente no se encuentra: '.$sAgente);
                return FALSE;
            }
            if ($estadoCola['members'][$sNumAgente]['status'] != 'inUse') {
                $this->oMainLog->output('ERR: (internal) al transferir llamada: agente no está atendiendo llamada: '.$sAgente);
                return FALSE;
            }
            if (!isset($estadoCola['members'][$sNumAgente]['clientchannel'])) {
                $this->oMainLog->output('ERR: (internal) al transferir llamada: no se puede identificar canal remoto para agente: '.$sAgente);
                return FALSE;
            }
            $tuplaLlamada['ActualChannel'] = $estadoCola['members'][$sNumAgente]['clientchannel'];
        } else {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: agente $sAgente conectado con ".
                    "ChannelClient={$tuplaLlamada['ChannelClient']}, se asume ".
                    "canal correcto.");
            }
            $tuplaLlamada['ActualChannel'] = $tuplaLlamada['ChannelClient'];
        }
         
        // Ejecutar realmente la redirección al hold
        $r = $this->_astConn->Redirect(
            $tuplaLlamada['ActualChannel'], // channel 
            '',                             // extrachannel
            $sExtension,                   // exten
            'from-internal',                // context
            1);                             // priority
        if ($r['Response'] != 'Success') {
            $this->oMainLog->output('ERR: al transferir llamada: no se puede transferir - '.$r['Message']);
            return FALSE;
        }

        // Al transferir llamada, la llamada será desconectada del agente.
        return TRUE;
    }

    // Leer el estado de /etc/asterisk/features_general_additional.conf y obtener la
    // extensión de parqueo configurada. Devuelve NULL en caso de error o si la 
    // característica de extensión de parqueo no está configurada, o la extensión
    // numérica en caso contrario.
    public function leerConfigExtensionParqueo()
    {
        $sNombreArchivo = '/etc/asterisk/features_general_additional.conf';
        if (!file_exists($sNombreArchivo)) {
            $this->oMainLog->output("WARN: $sNombreArchivo no se encuentra.");
            return NULL;
        }
        if (!is_readable($sNombreArchivo)) {
            $this->oMainLog->output("WARN: $sNombreArchivo no puede leerse por usuario de marcador.");
            return NULL;            
        }
        $infoConfig = parse_ini_file($sNombreArchivo, TRUE);
        if (is_array($infoConfig)) {
            $sExtensionParqueo = isset($infoConfig['parkext']) ? $infoConfig['parkext'] : '';
            return (preg_match('/^\d+$/', $sExtensionParqueo)) ? $sExtensionParqueo : NULL;
        } else {
            $this->oMainLog->output("ERR: $sNombreArchivo no puede parsearse correctamente.");          
        }
        return NULL;
    }

    /* Obtener la información de la llamada que está atendiendo el agente cuyo
     * número se indica. Se devuelve la siguiente información sobre la llamada:
     * 
     * tabla            current_calls para llamadas salientes, o 
     *                  current_call_entry para llamadas entrantes
     * id_current_call  ID en la tabla de llamadas actuales
     * id_call          ID en la tabla de llamadas de la campaña
     * ChannelClient    Canal que corresponde a la llamada remota
     * ActualChannel    Canal verdadero, si ChannelClient es Local/xxx
     * 
     */
    private function _obtenerLlamadaAtendidaAgente($sNumAgente)
    {
        // Este código asume formato Agent/9000
        $sAgente = 'Agent/'.$sNumAgente;

        /* Leer de la base de datos el canal que está conversando con el agente.
         * También debe de obtenerse el ID del registro en la tabla de 
         * current_calls o current_call_entry, según la que se descubra primero.
         */
        // Verificar si llamada es entrante...
        $tuplaLlamada = $this->_dbConn->getRow(
            'SELECT current_call_entry.id AS id_current_call, id_call_entry AS id_call, ChannelClient '.
            'FROM current_call_entry, agent '.
            'WHERE current_call_entry.id_agent = agent.id '.
                'AND agent.estatus = "A" AND agent.number = ?',
            array($sNumAgente), DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaLlamada)) {
            $this->oMainLog->output('ERR: al obtener información de llamada: no se puede verificar llamada entrante - '.
                $tuplaLlamada->getMessage());
            return FALSE;
        } elseif (count($tuplaLlamada) > 0) {
            // Llamada es entrante
            $tuplaLlamada['tabla'] = 'current_call_entry';
        } else {
            // Verificar si llamada es saliente...
            $tuplaLlamada = $this->_dbConn->getRow(
                'SELECT id AS id_current_call, id_call, ChannelClient '.
                'FROM current_calls WHERE agentnum = ?',
                array($sNumAgente), DB_FETCHMODE_ASSOC);
            if (DB::isError($tuplaLlamada)) {
                $this->oMainLog->output('ERR: al obtener información de llamada: no se puede verificar llamada saliente - '.
                    $tuplaLlamada->getMessage());
                return FALSE;
            } elseif (count($tuplaLlamada) > 0) {
                // Llamada es saliente
                $tuplaLlamada['tabla'] = 'current_calls';
            } else {
                $this->oMainLog->output('WARN: al obtener información de llamada: el siguiente '.
                    'agente no está atendiendo llamada: '.$sAgente);
                return FALSE;
            }
        }
        
        /* Los canales de tipo Local/XXX@from-internal no sirven para realizar
         * redirección a hold. Se debe de averiguar el verdadero canal que 
         * aparece en el reporte de "agent show" */
        if (strpos($tuplaLlamada['ChannelClient'], 'Local/') === 0) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: agente $sAgente conectado con ".
                    "ChannelClient={$tuplaLlamada['ChannelClient']}, buscando ".
                    "verdadero canal...");
            }
            $oPredictor = new Predictivo($this->_astConn);
            $estadoCola = $oPredictor->leerEstadoCola(''); // El parámetro vacío lista todas las colas
            if (!isset($estadoCola['members'][$sNumAgente])) {
                $this->oMainLog->output('ERR: (internal) al obtener información de llamada: agente no se encuentra: '.$sAgente);
                return FALSE;
            }
            if (!isset($estadoCola['members'][$sNumAgente]['clientchannel'])) {
                $this->oMainLog->output('ERR: (internal) al obtener información de llamada: agente no está atendiendo llamada: '.$sAgente);
                return FALSE;
            }
            $tuplaLlamada['ActualChannel'] = $estadoCola['members'][$sNumAgente]['clientchannel'];
        } else {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: agente $sAgente conectado con ".
                    "ChannelClient={$tuplaLlamada['ChannelClient']}, se asume ".
                    "canal correcto.");
            }
            $tuplaLlamada['ActualChannel'] = $tuplaLlamada['ChannelClient'];
        }
        return $tuplaLlamada;
    }

    public function iniciarHoldAgente($sAgente)
    {
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return FALSE;
        }

        if (!isset($this->_infoAgentes[$sAgente])) {
            $this->oMainLog->output('ERR: (internal) al iniciar hold: no se hace seguimiento ECCP al agente: '.$sAgente);
            return FALSE;
        }
        if ($this->_infoAgentes[$sAgente]['estado_consola'] != 'logged-in') {
            $this->oMainLog->output('ERR: (internal) al iniciar hold: agente no está (todavía) logoneado: '.$sAgente);
            return FALSE;
        }

        /* Verificar en el archivo features_general_additional.conf si la 
         * funcionalidad de parqueo está habilitada. Se considera que está
         * habilitada si existe la clave parkext . Esto indica además la 
         * extensión de parqueo. */
        $sExtParqueo = $this->leerConfigExtensionParqueo();
        if (is_null($sExtParqueo)) {
            $this->oMainLog->output('ERR: al iniciar hold: funcionalidad de hold no está activa');
        	return FALSE;
        }
         
        // Si el agente ya estaba en hold, se debería validar si la llamada sigue activa 
        if (!is_null($this->_infoAgentes[$sAgente]['info_hold'])) {
            $sExtLlamadaParqueada = $this->_buscarExtensionParqueo(
                $this->_infoAgentes[$sAgente]['info_hold']['ActualChannel']);
            if (!is_null($sExtLlamadaParqueada)) {
                // La llamada sigue en hold. No se requiere hacer nada
                return TRUE;
            } else {
                // El abonado se cansó de esperar y ha colgado. Se termina el
                // hold anterior y se marca en la base de datos.
                $this->marcarFinalBreakAgente($this->_infoAgentes[$sAgente]['info_hold']['id_hold']);
                $this->_infoAgentes[$sAgente]['info_hold'] = NULL;
                
                // TODO: evento OnHangup debería revisar info_hold de todos los
                // agentes para eliminar los holds y pausas de las llamadas que
                // han colgado.
            }
        }

        // Obtener el ID del break que corresponde al hold
        $idHold = $this->_dbConn->getOne('SELECT id FROM break WHERE tipo = "H" AND status = "A"');
        if (DB::isError($idHold)) {
            $this->oMainLog->output('ERR: al iniciar hold: no se puede leer ID de hold para auditoría - '.$idHold->getMessage());
        	return FALSE;
        } elseif (is_null($idHold)) {
            $this->oMainLog->output('ERR: al iniciar hold: no se puede leer ID de hold para auditoría - no hay hold válido y activo');
            return FALSE;
        }
        
        // Obtener la información de la llamada atendida por el agente
        $tuplaLlamada = $this->_obtenerLlamadaAtendidaAgente($sNumAgente);
        if (!is_array($tuplaLlamada)) return FALSE;
        
        // En este punto, $tuplaLlamada tiene la información para iniciar hold
        
        // Ejecutar realmente la pausa del agente indicado en todas las colas
        if (!$this->_agregarPausaAgente($sAgente)) return FALSE;

        /* Marcar la llamada para hold, para compatibilidad con el código de
         * hold del evento OnHangup. Esta actualización debe ser visible cuando
         * se reciba el evento Hangup resultante de la redirección hold. */
        $sqlHold[] = array(
            'UPDATE '.$tuplaLlamada['tabla'].' SET hold = ? WHERE id = ?',
            array(
                'marcar_hold'   =>  array('S', $tuplaLlamada['id_current_call']),
                'deshacer_hold' =>  array('N', $tuplaLlamada['id_current_call']),
            )
        );
        if ($tuplaLlamada['tabla'] == 'current_calls') {
            $sqlHold[] = array(
                'UPDATE calls set status = ? WHERE id = ?',
                array(
                    'marcar_hold'   =>  array('OnHold', $tuplaLlamada['id_call']),
                    'deshacer_hold' =>  array('Success', $tuplaLlamada['id_call']),
                ),
            );
        } elseif ($tuplaLlamada['tabla'] == 'current_call_entry') {
            // FIXME: estado de call_entry se asigna en GestorLlamadasEntrantes::notificarHangup()
        }
        foreach ($sqlHold as $sqlIndex => $sqlInfo) {
        	$r = $this->_dbConn->query($sqlInfo[0], $sqlInfo[1]['marcar_hold']);
            if (DB::isError($r)) {
                $this->oMainLog->output(
                    "ERR: al iniciar hold: no se puede marcar la llamada como hold ($sqlIndex) - ".
                    $r->getMessage());
                return FALSE;
            }
        }
        
        // Ejecutar realmente la redirección al hold
        $r = $this->_astConn->Redirect(
            $tuplaLlamada['ActualChannel'], // channel 
            '',                             // extrachannel
            $sExtParqueo,                   // exten
            'from-internal',                // context
            1);                             // priority
        if ($r['Response'] != 'Success') {
        	$this->oMainLog->output('ERR: al iniciar hold: no se puede ejecutar hold - '.$r['Message']);
            foreach ($sqlHold as $sqlIndex => $sqlInfo) {
                $r = $this->_dbConn->query($sqlInfo[0], $sqlInfo[1]['deshacer_hold']);
                if (DB::isError($r)) {
                    $this->oMainLog->output(
                        "ERR: al iniciar hold: no se puede marcar la llamada como hold ($sqlIndex) - ".
                        $r->getMessage());
                }
            }
            $this->_quitarPausaAgente($sAgente);
            return FALSE;
        }
        
        // Auditoría del inicio del hold
        $idAuditHold = $this->marcarInicioBreakAgente($sAgente, $idHold);
        if (is_null($idAuditHold)) {
            $this->oMainLog->output('ERR: (internal) no se puede auditar el inicio del hold: '.$sAgente);
            return FALSE;
        }
        $tuplaLlamada['id_hold'] = $idAuditHold;
        $this->_infoAgentes[$sAgente]['info_hold'] = $tuplaLlamada;
        
        // Se marca la llamada correspondiente al canal para hold
        foreach (array_keys($this->_infoLlamadas['llamadas']) as $sKey) {
        	if ($this->_infoLlamadas['llamadas'][$sKey]->ActualChannel == $tuplaLlamada['ActualChannel']) {
                $this->_infoLlamadas['llamadas'][$sKey]->OnHold = TRUE;
                $this->_infoLlamadas['llamadas'][$sKey]->status = 'OnHold';
                
                if ($this->DEBUG) {
                	$this->oMainLog->output('DEBUG: la siguiente llamada será puesta en HOLD vía ECCP: '.
                        $sKey.' => '.
                        print_r($this->_infoLlamadas['llamadas'][$sKey], 1));
                }
            }
        }
        
        $inicioHold = $this->_dbConn->getOne('SELECT datetime_init FROM audit WHERE id = ?', array($idAuditHold));
        if (DB::isError($inicioHold)) {
            $this->oMainLog->output('ERR: (internal) al consultar fecha inicio hold - '.$inicioHold->getMessage());
        	$inicioHold = date('Y-m-d H:i:s');
        }
        $this->_dialSrv->notificarEvento_PauseStart($sAgente, array(
            'pause_class'   =>  'hold',
            'pause_start'   =>  $inicioHold,
        ));
        return TRUE;
    }
    
    // Ejecutar el comando adecuado según la versión de Asterisk para listar
    // las extensiones de llamadas parqueadas. Se devuelve el número de 
    // extensión que contiene el canal que se ha pasado como parámetro, o NULL
    // si ha ocurrido un problema o si no se encuentra el canal.
    private function _buscarExtensionParqueo($sCanal)
    {
        $versionMinima = array(1, 6, 0);
        while (count($versionMinima) < count($this->_asteriskVersion))
            array_push($versionMinima, 0);
        while (count($versionMinima) > count($this->_asteriskVersion))
            array_push($this->_asteriskVersion, 0);
        $sComandoParqueo = ($this->_asteriskVersion >= $versionMinima) 
            ? 'parkedcalls show' 
            : 'show parkedcalls';
        $r = $this->_astConn->Command($sComandoParqueo);
        if (!isset($r['data'])) return NULL;

/*
Privilege: Command
 Num                   Channel (Context         Extension    Pri ) Timeout 
*** Parking lot: default
71                 SIP/1065-00000014 (from-internal   s            1   )     38s
---
1 parked call in total.
 */
        $lineas = explode("\n", $r['data']); $regs = NULL;
        foreach ($lineas as $sLinea) {
            if (preg_match('/^\s*(\d{2,})\s*(\S+)/', $sLinea, $regs)) {
            	if ($regs[2] == $sCanal) return $regs[1];
            }
        }
        return NULL;
    }

    public function terminarHoldAgente($sAgente)
    {
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return FALSE;
        }

        if (!isset($this->_infoAgentes[$sAgente])) {
            $this->oMainLog->output('ERR: (internal) al terminar hold: no se hace seguimiento ECCP al agente: '.$sAgente);
            return FALSE;
        }
        if ($this->_infoAgentes[$sAgente]['estado_consola'] != 'logged-in') {
            $this->oMainLog->output('ERR: (internal) al terminar hold: agente no está (todavía) logoneado: '.$sAgente);
            return FALSE;
        }

        // Si el agente no estaba en hold, se devuelve éxito sin hacer nada más
        if (is_null($this->_infoAgentes[$sAgente]['info_hold'])) {
            return TRUE;
        }
        
        /* Verificar en el archivo features_general_additional.conf si la 
         * funcionalidad de parqueo está habilitada. Se considera que está
         * habilitada si existe la clave parkext . Esto indica además la 
         * extensión de parqueo. */
        $sExtParqueo = $this->leerConfigExtensionParqueo();
        if (is_null($sExtParqueo)) {
            $this->oMainLog->output('ERR: al terminar hold: funcionalidad de hold no está activa');
            return FALSE;
        }

    	/* ID de hold está en $this->_infoAgentes[$sAgente]['info_hold']['id_hold'] .
         * Según $this->_infoAgentes[$sAgente]['info_hold']['tabla'], es current_calls
         * para llamada saliente, current_call_entry para llamada entrante.
         * El ID de la llamada actual de current_call está en 
         * $this->_infoAgentes[$sAgente]['info_hold']['id_current_call'] .
         * Canal de break está en $this->_infoAgentes[$sAgente]['info_hold']['ChannelClient']
         * y debe de coincidir con canal recuperado por el comando parkedcalls show.
         */
        $sExtLlamadaParqueada = $this->_buscarExtensionParqueo(
            $this->_infoAgentes[$sAgente]['info_hold']['ActualChannel']);
        if (!is_null($sExtLlamadaParqueada)) {
        	$sActionID = 'ECCP:1.0:'.posix_getpid().':RedirectFromHold';
            if ($this->DEBUG) {
        		$this->oMainLog->output("DEBUG: intentando recuperar llamada:\n".
                    "\tChannel      =>  $sAgente\n".
                    "\tExten        =>  $sExtLlamadaParqueada\n".
                    "\tContext      =>  from-internal\n".
                    "\tActionID     =>  $sActionID");
        	}
            
            // Sacar la llamada del parqueo y redirigirla al agente pausado
            $r = $this->_astConn->Originate(
                $sAgente,               // channel
                $sExtLlamadaParqueada,  // extension
                'from-internal',        // context
                '1',                    // priority
                NULL, NULL, NULL, NULL, NULL, NULL,
                TRUE,                   // async
                $sActionID
                );
            if ($r['Response'] != 'Success') {
            	$this->oMainLog->output('ERR: al terminar hold: no se puede retomar llamada - '.$r['Message']);
            }
            if ($this->DEBUG) {
            	$this->oMainLog->output('DEBUG: Originate para recuperar llamada devuelve: '.print_r($r));
            }
        }

        // Ejecutar realmente el retiro de la pausa en todas las colas
        if (!$this->_quitarPausaAgente($sAgente)) return FALSE;

        // Marcar la llamada como sacada de hold en la base de datos
        $sPeticionSQLMarcado = 'UPDATE '.$this->_infoAgentes[$sAgente]['info_hold']['tabla'].' SET hold = ? WHERE id = ?';
        $r = $this->_dbConn->query($sPeticionSQLMarcado, array('N', $this->_infoAgentes[$sAgente]['info_hold']['id_current_call']));
        if (DB::isError($r)) {
            $this->oMainLog->output('ERR: al terminar hold: no se puede marcar la llamada como fuera de hold - '.$r->getMessage());
            //return FALSE;
        }

        // Auditoría del fin del hold
        $this->marcarFinalBreakAgente($this->_infoAgentes[$sAgente]['info_hold']['id_hold']);


        $tuplaHold = $this->_dbConn->getRow(
            'SELECT datetime_init, datetime_end, TIME_TO_SEC(duration) AS duration_sec FROM audit WHERE id = ?', 
            array($this->_infoAgentes[$sAgente]['info_hold']['id_hold']),
            DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaHold)) {
            $this->oMainLog->output('ERR: (internal) al consultar fecha final hold - '.$tuplaHold->getMessage());
            $tuplaHold = array(
                'datetime_init' =>  date('Y-m-d H:i:s'),
                'datetime_end'  =>  date('Y-m-d H:i:s'),
                'duration_sec'  =>  0,
            );
        }
        $this->_dialSrv->notificarEvento_PauseEnd($sAgente, array(
            'pause_class'   =>  'hold',
            'pause_start'   =>  $tuplaHold['datetime_init'],
            'pause_end'     =>  $tuplaHold['datetime_end'],
            'pause_duration'=>  $tuplaHold['duration_sec'],
        ));

        $this->_infoAgentes[$sAgente]['info_hold'] = NULL;
        return TRUE;
    }

    // Método que lista las colas a las cuales está suscrito un agente
    private function _listarColasAgente($sAgente)
    {
    	/* Por ahora se asume que $sAgente es de la forma Agent/9000 y que 
         * aparece de esta misma manera en el reporte de "queue show" 
         *  5000 has 0 calls (max unlimited) in 'ringall' strategy (0s holdtime, 0s talktime), W:0, C:0, A:0, SL:0.0% within 60s
         *     No Members
         *     No Callers
         *  
         *  8001 has 0 calls (max unlimited) in 'ringall' strategy (0s holdtime, 0s talktime), W:0, C:0, A:0, SL:0.0% within 60s
         *     Members: 
         *        Agent/9000 (Unavailable) has taken no calls yet
         *     No Callers
         *  
         *  8000 has 0 calls (max unlimited) in 'ringall' strategy (0s holdtime, 0s talktime), W:0, C:0, A:0, SL:0.0% within 60s
         *     Members: 
         *        Agent/9000 (Unavailable) has taken no calls yet
         *     No Callers
         *
         */
        $respuestaCola = $this->_astConn->Command('queue show');
        if (isset($respuestaCola['data'])) {
            $listaColas = array();
            $lineasRespuesta = explode("\n", $respuestaCola['data']);
            $sColaActual = NULL;
            foreach ($lineasRespuesta as $sLinea) {
                $regs = NULL;
                //if (ereg('^([[:digit:]]+)[[:space:]]+has[[:space:]]+[[:digit:]]+[[:space:]]+calls', $sLinea, $regs)) {
                if (preg_match('/^(\d+) has \d+ calls/', $sLinea, $regs)) {
                   // Se ha encontrado el inicio de una descripción de cola
                    $sColaActual = $regs[1];
                //} elseif (ereg('^[[:space:]]+(Agent/[[:digit:]]+)', $sLinea, $regs)) {
                } elseif (preg_match('|^\s+(\w+/\d+)|', $sLinea, $regs)) {
                    if (!is_null($sColaActual) && $sAgente == $regs[1]) {
                        // Se ha encontrado el agente en una cola en particular
                        $listaColas[] = $sColaActual;
                    }
                }
            }
            return $listaColas;
        } else {
            $this->oMainLog->output('ERR: lost synch with Asterisk AMI ("queue show" response lacks "data").');
            return NULL;
        }
    }

    function infoSeguimientoAgente($sAgente)
    {
    	return isset($this->_infoAgentes[$sAgente]) ? $this->_infoAgentes[$sAgente] : NULL;
    }

    function OnAgentlogoff($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: ENTER OnAgentlogoff");
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }

        // Verificar que este evento corresponde a un Agentlogin iniciado por este programa
        $sAgente = 'Agent/'.$params['Agent'];
        if (!isset($this->_infoAgentes[$sAgente])) {
            if ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: logoff AgentLogin($sAgente) no iniciado por programa, no se hace nada.");
                $this->oMainLog->output("DEBUG: EXIT OnAgentlogoff");
            }
            return FALSE;
        }

        // Escribir la información de auditoría en la base de datos
        if (!is_null($this->_infoAgentes[$sAgente]['info_hold'])) {
            // TODO: ¿Qué ocurre con la posible llamada parqueada?
            $this->marcarFinalBreakAgente($this->_infoAgentes[$sAgente]['info_hold']['id_hold']);
        }
        $this->marcarFinalSesionAgente(
            $sAgente,
            $this->_infoAgentes[$sAgente]['id_sesion'],
            $this->_infoAgentes[$sAgente]['id_break']);

        unset($this->_infoAgentes[$sAgente]);

        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: EXIT OnAgentlogoff");
        }
        return FALSE;
    }

    // Callback llamado para todos los eventos no manejados por otro callback
    function OnDefault($sEvent, $params, $sServer, $iPort)
    {
        if ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: $sEvent:\nparams => ".print_r($params, TRUE));
        }
        return FALSE;
    }

    function & reportarEstadoCampania($idCampania)
    {
    	// Estado de campaña - llamadas realizadas y pendientes
        $resumen = $this->_leerResumenCampania($idCampania);
        if (!is_null($resumen) && isset($resumen['queue'])) {
        	$resumen['queuestatus'] = $this->_leerEstadoColaConBreaks($resumen['queue']);
            $resumen['activecalls'] = array();
            foreach ($this->_infoLlamadas['llamadas'] as $sKey => $infoLlamada) {
            	if ($infoLlamada->id_campaign == $idCampania && 
                    in_array($infoLlamada->status, array('Placing', 'Ringing', 'OnQueue'))) {
                    $callStatus = array(
                        'dialnumber'    =>  $infoLlamada->phone,
                        'callid'        =>  $infoLlamada->id,
                        'callstatus'    =>  strtolower($infoLlamada->status),
                    );

                    if (isset($infoLlamada->OriginateStart) && !is_null($infoLlamada->OriginateStart))
                        $callStatus['datetime_dialstart'] = date('Y-m-d H:i:s', $infoLlamada->OriginateStart); 
                    if (isset($infoLlamada->OriginateEnd) && !is_null($infoLlamada->OriginateEnd))
                        $callStatus['datetime_dialend'] = date('Y-m-d H:i:s', $infoLlamada->OriginateEnd); 
                    if (isset($infoLlamada->enterqueue_timestamp) && !is_null($infoLlamada->enterqueue_timestamp))
                        $callStatus['datetime_enterqueue'] = date('Y-m-d H:i:s', $infoLlamada->enterqueue_timestamp); 
                    $resumen['activecalls'][] = $callStatus;
                }
            }
        }
        return $resumen;
    }
    
    function & reportarEstadoCampaniaEntrante($idCampania)
    {
    	return $this->_oGestorEntrante->reportarEstadoCampania($idCampania);
    }

    /**
     * Método que devuelve un resumen de la información de una campaña saliente
     * para ser mostrada en la interfaz de monitoreo.
     *
     * @param   int     $idCampania     ID de la campaña a interrogar
     *
     * @return  mixed   NULL en error, o información de la campaña
     */
    private function _leerResumenCampania($idCampania)
    {
        // Leer la información en el propio registro de la campaña
        $sPeticionSQL = <<<LEER_RESUMEN_CAMPANIA
SELECT id, name, datetime_init, datetime_end, daytime_init, daytime_end, 
    retries, trunk, queue, estatus
FROM campaign WHERE id = ?
LEER_RESUMEN_CAMPANIA;
        $tupla = $this->_dbConn->getRow($sPeticionSQL, array($idCampania), DB_FETCHMODE_ASSOC);
        if (DB::isError($tupla)) {
            //$this->errMsg = $this->_DB->errMsg;
            $this->oMainLog->output('ERR: no se puede leer información de campaña - '.$tupla->getMessage());
            return NULL;
        } elseif (count($tupla) <= 0) {
        	return array();
        }

        // Leer la clasificación por estado de las llamadas de la campaña
        $sPeticionSQL = 'SELECT COUNT(*) AS n, status FROM calls WHERE id_campaign = ? GROUP BY status';
        $recordset = $this->_dbConn->getAll($sPeticionSQL, array($idCampania), DB_FETCHMODE_ASSOC);
        if (DB::isError($recordset)) {
            $this->oMainLog->output('ERR: no se puede leer estado de llamadas de campaña - '.$recordset->getMessage());
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

    /**
     * Procedimiento que lee el estado de la cola indicada por el parámetro. 
     * Se invoca al método ya implementado para el marcador predictivo, y a 
     * continuación se agrega información para identificar: en caso de break,
     * intervalo del break y tipo del break; en caso de llamada ocupada, número,
     * tiempo y canal de la llamada.
     */
    private function _leerEstadoColaConBreaks($idCola)
    {
        $oPredictor = new Predictivo($this->_astConn);
        $estadoCola = $oPredictor->leerEstadoCola($idCola);
        foreach ($estadoCola['members'] as $sNumAgente => $infoAgente) {
            if (in_array('paused', $infoAgente['attributes'])) {
                // El agente está en pausa. Se intenta identificar tipo de break
                $sqlBreak = <<<LEER_TIPO_BREAK
SELECT audit.datetime_init, break.name, break.id 
FROM agent, audit, break 
WHERE agent.number = ? AND agent.estatus = "A" AND agent.id = audit.id_agent 
    AND audit.datetime_end IS NULL AND audit.id_break = break.id
ORDER BY audit.datetime_init DESC
LIMIT 0,1
LEER_TIPO_BREAK;
                $tuplaBreak = $this->_dbConn->getRow($sqlBreak, array($sNumAgente), DB_FETCHMODE_ASSOC);
                if (DB::isError($tuplaBreak)) {
                    $this->oMainLog->output('ERR: no se puede leer información de agentes - '.$tuplaBreak->getMessage());
                } elseif (is_array($tuplaBreak)) {
                    $estadoCola['members'][$sNumAgente]['datetime_breakstart'] = $tuplaBreak['datetime_init'];
                    $estadoCola['members'][$sNumAgente]['break_name'] = $tuplaBreak['name'];
                    $estadoCola['members'][$sNumAgente]['break_id'] = $tuplaBreak['id'];
                }
            } elseif ($infoAgente['status'] == 'inUse') {
                //$estadoCola['members'][$sNumAgente]['datetime_init'] = date('Y-m-d H:i:s', time() - $infoAgente['talkTime']);
            }
            
            // TODO: reportar número que fue marcado para llamada saliente
            foreach ($this->_infoLlamadas['llamadas'] as $infoLlamada) {
            	// La siguiente comparación asume agente de forma Agent/9000
                if (isset($infoLlamada->AgentChannel) && $infoLlamada->AgentChannel == 'Agent/'.$sNumAgente) {
                    $estadoCola['members'][$sNumAgente]['dialnumber'] = $infoLlamada->phone;
                    $estadoCola['members'][$sNumAgente]['callid'] = $infoLlamada->id;
                    $estadoCola['members'][$sNumAgente]['datetime_dialstart'] = date('Y-m-d H:i:s', $infoLlamada->OriginateStart);
                    $estadoCola['members'][$sNumAgente]['datetime_dialend'] = date('Y-m-d H:i:s', $infoLlamada->OriginateEnd);
                    $estadoCola['members'][$sNumAgente]['datetime_enterqueue'] = date('Y-m-d H:i:s', $infoLlamada->enterqueue_timestamp);
                    $estadoCola['members'][$sNumAgente]['datetime_linkstart'] = date('Y-m-d H:i:s', $infoLlamada->start_timestamp);
            	}
            }
        }
        ksort($estadoCola['members']);
        return $estadoCola;
    }

    function reportarInfoLlamadaAtendida($sAgente)
    {
        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: No se ha implementado este tipo de agente - '.$sAgente);
            return NULL;
        }
        foreach ($this->_infoLlamadas['llamadas'] as $infoLlamada) {
            if (isset($infoLlamada->AgentChannel) && $infoLlamada->AgentChannel == 'Agent/'.$sNumAgente) {
            	return array(
                    'calltype'     =>  'outgoing',
                    'campaign_id'   =>  $infoLlamada->id_campaign,
                    'dialnumber'    =>  $infoLlamada->phone,
                    'callid'       =>  $infoLlamada->id,
                    'datetime_dialstart' => date('Y-m-d H:i:s', $infoLlamada->OriginateStart),
                    'datetime_dialend' => date('Y-m-d H:i:s', $infoLlamada->OriginateEnd),
                    'datetime_enterqueue' => date('Y-m-d H:i:s', $infoLlamada->enterqueue_timestamp),
                    'datetime_linkstart' => date('Y-m-d H:i:s', $infoLlamada->start_timestamp),
                );
            }
        }
        return NULL; // El agente indicado no está atendiendo una llamada
    }
    
    function reportarInfoLlamadaAtendidaEntrante($sAgente)
    {
    	return $this->_oGestorEntrante->reportarInfoLlamadaAtendida($sAgente);
    }

    /**
     * Procedimiento que crea una nueva llamada agendada en base a la llamada
     * que está atendiendo el agente indicado por el parámetro.
     * 
     * @param   string  $sAgente        Agente en formato Agent/9000
     * @param   mixed   $horario        Arreglo que define el horario como sigue:
     *          date_init               Fecha en inicio de horario en formato YYYY-MM-DD
     *          date_end                Fecha de fin de horario en formato YYYY-MM-DD
     *          time_init               Hora de inicio de horario en formato HH:MM:SS
     *          time_end                Hora de fin de horario en formato HH:MM:SS
     *                                  NULL para agendar llamada al final de campaña
     *                                  a cualquier fecha y hora
     * @param   bool    $bMismoAgente   FALSO si se asigna llamada a cualquier agente
     *                                  VERDADERO para que el mismo agente deba atenderla
     *                                  Si VERDADERO, se requiere $horario.
     * @param   mixed   $sNuevoTelefono Teléfono nuevo al cual marcar llamada, o NULL para mismo anterior
     * @param   mixed   $sNuevoNombre   Nombre del nuevo contacto para llamada, o NULL para mismo anterior
     * 
     * @return bool VERDADERO en caso de éxito, FALSO en caso de error
     */
    function agendarLlamadaAgente($sAgente, $horario, $bMismoAgente, 
        $sNuevoTelefono, $sNuevoNombre, &$errcode, &$errdesc)
    {
        $errcode = 0; $errdesc = 'Success';

        // Esto asume formato Agent/9000
        $sNumAgente = NULL;
        if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
            $sNumAgente = $regs[1];
        } else {
            $this->oMainLog->output('ERR: (internal) No se ha implementado este tipo de agente - '.$sAgente);
            return FALSE;
        }

        if (!isset($this->_infoAgentes[$sAgente])) {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: no se hace seguimiento ECCP al agente: '.$sAgente);
            return FALSE;
        }
        if ($this->_infoAgentes[$sAgente]['estado_consola'] != 'logged-in') {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: agente no está (todavía) logoneado: '.$sAgente);
            return FALSE;
        }

        // Revisar teléfono nuevo, si existe
        if (!is_null($sNuevoTelefono) && !preg_match('/^\d+$/', $sNuevoTelefono)) {
            $errcode = 400; $errdesc = 'Bad request: invalid new phone';
        	return FALSE;
        }

        // Revisar horarios
        if (is_array($horario)) {
        	// Formatos correctos de fecha
            if (!isset($horario['date_init']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $horario['date_init'])) {
                $this->oMainLog->output('ERR: al agendar llamada: fecha de inicio inválida, se espera YYYY-MM-DD');
                $errcode = 400; $errdesc = 'Bad request: invalid date_init';
        		return FALSE;
        	} elseif (!isset($horario['date_end']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $horario['date_end'])) {
                $this->oMainLog->output('ERR: al agendar llamada: fecha de fin inválida, se espera YYYY-MM-DD');
                $errcode = 400; $errdesc = 'Bad request: invalid date_end';
        		return FALSE;
            } elseif (!isset($horario['time_init']) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $horario['time_init'])) {
                $this->oMainLog->output('ERR: al agendar llamada: hora de inicio inválida, se espera HH:MM:SS');
                $errcode = 400; $errdesc = 'Bad request: invalid time_init';
                return FALSE;
            } elseif (!isset($horario['time_end']) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $horario['time_end'])) {
                $this->oMainLog->output('ERR: al agendar llamada: hora de fin inválida, se espera HH:MM:SS');
                $errcode = 400; $errdesc = 'Bad request: invalid time_end';
                return FALSE;
        	}
            
            // Ordenamiento correcto
            if ($horario['date_init'] > $horario['date_end']) {
            	$t = $horario['date_init'];
                $horario['date_init'] = $horario['date_end'];
                $horario['date_end'] = $t;
            }
            
            // Fecha debe estar en el futuro
            if ($horario['date_init'] < date('Y-m-d')) {
                $this->oMainLog->output('ERR: al agendar llamada: fecha de inicio anterior a fecha actual');
                $errcode = 400; $errdesc = 'Bad request: date_init before current date';
                return FALSE;
            }
        } elseif (!is_null($horario)) {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: horario no es un arreglo');
        	return FALSE;
        }

    	// Información de la llamada atendida por el agente
        $tuplaLlamada = $this->_obtenerLlamadaAtendidaAgente($sNumAgente);
        if (!is_array($tuplaLlamada)) {
            $errcode = 417; $errdesc = 'Not in outgoing call';
            return FALSE;
        }
        if ($tuplaLlamada['tabla'] != 'current_calls') {
            //$this->oMainLog->output('ERR: al agendar llamada: no se puede agendar llamada entrante: '.$sAgente);
            $errcode = 417; $errdesc = 'Not in outgoing call';
        	return FALSE;
        }
        
        // Leer toda la información de la campaña y la cola
        $sqlLlamadaCampania = <<<SQL_LLAMADA_CAMPANIA_AGENDAMIENTO
SELECT campaign.datetime_init, campaign.datetime_end, campaign.daytime_init, 
    campaign.daytime_end, calls.id_campaign, calls.phone
FROM campaign, calls
WHERE campaign.id = calls.id_campaign AND calls.id = ?
SQL_LLAMADA_CAMPANIA_AGENDAMIENTO;
        $tuplaCampania = $this->_dbConn->getRow($sqlLlamadaCampania, array($tuplaLlamada['id_call']), DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaCampania)) {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: no se '.
                'puede leer información de campaña - '.$tuplaCampania->getMessage());
            $errcode = 500; $errdesc = 'Failed to fetch campaign information';
        	return FALSE;
        }
        
        // Validar que el rango de fecha y hora requerido es compatible con campaña
        if (is_array($horario)) {
        	if (!($tuplaCampania['datetime_init'] <= $horario['date_init'] && 
                $horario['date_end'] <= $tuplaCampania['datetime_end'])) {
                $errcode = 417; $errdesc = 'Supplied date range outside campaign range';
                return FALSE;
            }
            if (!($tuplaCampania['daytime_init'] <= $horario['time_init'] &&
                $horario['time_end'] <= $tuplaCampania['daytime_end'])) {
                $errcode = 417; $errdesc = 'Supplied time range outside campaign range';
                return FALSE;
            }
        }

        // Acumular los parámetros de la nueva llamada por insertar
        // DEBEN PERMANECER EN ESTE ORDEN
        $paramNuevaLlamadaSQL = array(
            $tuplaCampania['id_campaign'],  // TODO: se puede mandar llamada a otra campaña...
            is_null($sNuevoTelefono) ? $tuplaCampania['phone'] : $sNuevoTelefono,
            is_null($horario) ? NULL : $horario['date_init'],
            is_null($horario) ? NULL : $horario['date_end'],
            is_null($horario) ? NULL : $horario['time_init'],
            is_null($horario) ? NULL : $horario['time_end'],            
        );

        // Leer los atributos a heredar de la llamada, para (opcionalmente) modificarlos
        $sqlLlamadaAtributos = <<<SQL_LLAMADA_ATRIBUTOS_AGENDAMIENTO
SELECT column_number, columna, value FROM call_attribute 
WHERE id_call = ?
ORDER BY column_number
SQL_LLAMADA_ATRIBUTOS_AGENDAMIENTO;
        $attrLlamada = $this->_dbConn->getAssoc($sqlLlamadaAtributos, false, 
            array($tuplaLlamada['id_call']));
        if (DB::isError($attrLlamada)) {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: no se '.
                'puede leer atributos de llamadas - '.$attrLlamada->getMessage());
            $errcode = 500; $errdesc = 'Failed to fetch call attributes';
        	return FALSE;
        }
        if (!is_null($sNuevoNombre)) {
            // Columnas de propiedades se numeran desde 1
            if (!isset($attrLlamada[1])) $attrLlamada[1] = array('Campo1', $sNuevoNombre);
            $attrLlamada[1][1] = $sNuevoNombre;
        }

        // Validar que no exista una llamada por agendar al mismo número
        $sqlExistenciaLlamadaPrevia = <<<SQL_LLAMADA_PREVIA
SELECT COUNT(*) FROM calls 
WHERE id_campaign = ? AND phone = ? AND date_init = ? AND date_end = ? 
    AND time_init = ? AND time_end = ?
SQL_LLAMADA_PREVIA;
        $existe = $this->_dbConn->getOne($sqlExistenciaLlamadaPrevia, $paramNuevaLlamadaSQL);
        if (DB::isError($existe)) {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: no se '.
                'puede verificar llamada duplicada - '.$existe->getMessage());
            $errcode = 500; $errdesc = 'Failed to check call duplicity';
        	return FALSE;
        } elseif ($existe > 0) {
            $errcode = 417; $errdesc = 'Found duplicate scheduled call';
        	return FALSE;
        }
        
        // Inicio de transacción
        $this->_dbConn->autoCommit(false);

        // Agregar agente a agendar, si es necesario, e insertar
        $paramNuevaLlamadaSQL[] = $bMismoAgente ? $sAgente : NULL;
        $sqlInsertarLlamadaAgendada = <<<SQL_INSERTAR_AGENDAMIENTO
INSERT INTO calls (id_campaign, phone, date_init, date_end, time_init, time_end, agent)
VALUES (?, ?, ?, ?, ?, ?, ?)
SQL_INSERTAR_AGENDAMIENTO;
        $r = $this->_dbConn->query($sqlInsertarLlamadaAgendada, $paramNuevaLlamadaSQL);
        if (DB::isError($r)) {
            $this->oMainLog->output('ERR: (internal) al agendar llamada: no se '.
                'puede insertar llamada agendada - '.$r->getMessage());
            $errcode = 500; $errdesc = 'Failed to insert scheduled call';
            $this->_dbConn->rollback();
            $this->_dbConn->autoCommit(true);
        	return FALSE;
        }
        $idNuevaLlamada = $this->_dbConn->getOne('SELECT LAST_INSERT_ID()');
        
        // Insertar atributos para la nueva llamada
        foreach ($attrLlamada as $iColNum => $tuplaAttr) {
        	// Se asume elemento 0 es 'columna', 1 es 'value' en call_attribute
            $tuplaAttr[] = $iColNum;        // Debería ser posición 2
            $tuplaAttr[] = $idNuevaLlamada; // Debería ser posición 3
            $r = $this->_dbConn->query(
                'INSERT INTO call_attribute (columna, value, column_number, id_call) '.
                'VALUES (?, ?, ?, ?)',
                $tuplaAttr);
            if (DB::isError($r)) {
                $this->oMainLog->output('ERR: (internal) al agendar llamada: no se '.
                    'puede insertar atributo de llamada agendada - '.$r->getMessage());
                $errcode = 500; $errdesc = 'Failed to insert scheduled call attributes';
                $this->_dbConn->rollback();
                $this->_dbConn->autoCommit(true);
            	return FALSE;
            }
        }
        
        // Final de transacción
        $this->_dbConn->commit();
        $this->_dbConn->autoCommit(true);
        return TRUE;
    }

    function _nuevoPromedio($iViejoProm, $n, $x)
    {
    	return $iViejoProm + ($x - $iViejoProm) / ($n + 1);
    }
    
    function _nuevaVarianciaMuestra($iViejoProm, $iNuevoProm, $n, $iViejaVar, $x) 
    {
        return ($n * $iViejaVar + ($x - $iNuevoProm) * ($x - $iViejoProm)) / ($n + 1);
    }

    // Al terminar el demonio, se desconecta Asterisk y base de datos
    function limpiezaDemonio($signum)
    {
        // Marcar como inválidas las llamadas que sigan en curso
        if (!is_null($this->_oGestorEntrante)) $this->_oGestorEntrante->finalizarLlamadasEntrantesEnCurso();

        // Deslogonear a todos los agentes que sigan activos
        foreach ($this->_infoAgentes as $sAgente => $infoAgente) {
            $regs = NULL;
            if (preg_match('|^Agent/(\d+)$|', $sAgente, $regs)) {
                $sNumAgente = $regs[1];
                $r = $this->_astConn->Agentlogoff($sNumAgente);
                $this->marcarFinalSesionAgente(
                    $sAgente,
                    $infoAgente['id_sesion'],
                    $infoAgente['id_break']);
            }
        }

        // Mandar a cerrar todas las conexiones activas
        $this->_dialSrv->finalizarServidor();
/*
        if (!is_null($this->_astConn)) {
        	$this->_astConn->disconnect();
            $this->_astConn = NULL;
        }
*/
        if (!is_null($this->_dbConn)) {
        	$this->_dbConn->disconnect();
            $this->_dbConn = NULL;
        }
    }
}
?>
