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
  $Id: GestorLlamadasEntrantes.class.php,v 1.8 2009/03/06 15:10:53 alex Exp $ */

/**
 * Esta clase es un gestor de llamadas entrantes. Luego de ser instanciada,
 * se espera que con cada event Link que reciba la aplicación, se pasen los
 * parámetros del evento al método notificarLink(). De forma análoga, se
 * espera que con cada evento Unlink, se pasen los parámetros del evento
 * al método notificarUnlink(). Esta clase encapsula el ingreso y remoción
 * en la tabla de llamadas actuales entrantes, consultada por la interfaz
 * web. Aunque la clase puede ser instanciada con cada evento Link, es más
 * eficiente conservar una instancia de la clase activa por toda la vida
 * de la aplicación, ya que la clase intenta llevar un cache de la cola a la
 * que pertenece cada agente, para ahorrar consultas a la base de datos.
 */

define ('MAX_TTL_CACHE_AGENTE', 5);

class GestorLlamadasEntrantes
{
    private $_astConn;  // Conexión al Asterisk
    private $_dbConn;   // Conexión a la base de datos
    private $_dialProc; // Referencia al DialerProcess
    private $_dialSrv;  // Referencia al DialerServer
    private $oMainLog; // Objeto de administración de log

    private $_timestampCache;   // Momento en que se leyó la info del caché
    private $_cacheAgentesCola; // Cache de a qué cola pertenece cada agente
    private $_cacheColasMonitoreadas;   // Cache de las colas monitoreadas
    
    private $_tieneCampaignEntry;	// VERDADERO si hay soporte para campañas de llamadas entrantes
    private $_tieneTrunk;           // VERDADERO si hay soporte para registrar trunk de llamadas entrantes

    private $_mapaUID;  // Lista de tuplas [CID] UniqueID de llamada entrante, [AID] UniqueID de llamada a agente

    var $DEBUG = FALSE;

    /**
     * Constructor. Requiere una conexión ya realizada al Asterisk, así como
     * una conexión a la base de datos. 
     */
    function GestorLlamadasEntrantes(&$astman, &$dbConn, &$oLog)
    {
        $this->setAstConn($astman);
        if (!DB::isConnection($dbConn)) {
        	throw new Exception('Not a valid database connection!');
        }
        if (!($oLog instanceof AppLogger)) {
        	throw new Exception('Not a subclass of AppLogger!');
        }
        $this->_dbConn = $dbConn;
        $this->oMainLog = $oLog;
        $this->_dialProc = NULL;
        $this->_dialSrv = NULL;
        $this->_timestampCache = NULL;
        $this->_cacheAgentesCola = NULL;
        $this->_cacheColasMonitoreadas = NULL;
        $this->_tieneCampaignEntry = FALSE;
        $this->_tieneTrunk = FALSE;
        $this->_mapaUID = array();

		// Verificar si el esquema de base de datos tiene soporte de campaña entrante
		$recordset =& $dbConn->query('DESCRIBE call_entry');
		if (DB::isError($recordset)) {
			$oLog->output("ERR: no se puede consultar soporte de campaña entrante - ".$recordset->getMessage());
		} else {
			while ($tuplaCampo = $recordset->fetchRow(DB_FETCHMODE_OBJECT)) {
				if ($tuplaCampo->Field == 'id_campaign') $this->_tieneCampaignEntry = TRUE;
                if ($tuplaCampo->Field == 'trunk') $this->_tieneTrunk = TRUE;
			}
			$oLog->output('INFO: sistema actual '.
				($this->_tieneCampaignEntry ? 'sí puede' : 'no puede').
				' registrar ID de campaña entrante.');
            $oLog->output('INFO: sistema actual '.
                ($this->_tieneTrunk ? 'sí puede' : 'no puede').
                ' registrar troncal de campaña entrante.');
		}

        // Llenar el cache de datos de los agentes
        $this->actualizarCacheAgentes();
        
        // Limpiar los datos de las llamadas que no se alcanzaron a marcar término
        $this->finalizarLlamadasEntrantesEnCurso();
    }

    /**
     * Función para interrogar si la conexión al Asterisk es válida
     * 
     * @return bool VERDADERO si la conexión es válida, FALSO si no.
     */
    function isAstConnValid()
    {
    	return !is_null($this->_astConn);
    }

    /**
     * Procedimiento que asigna una nueva conexión Asterisk al gestor de llamadas
     * entrantes. Este método existe para que al desechar una conexión inválida al
     * Asterisk, el objeto llamador pueda comunicar la nueva conexión al gestor de
     * llamadas entrantes, sin tener que re-instanciar el objeto.
     * 
     * @param object $astman Conexión al Asterisk a usar en lugar de la actual.
     * 
     * @return void
     */
    function setAstConn(&$astman)
    {
        $this->_astConn = $astman;
    }

    function setDBConn(&$dbConn)
    {
    	if (!DB::isConnection($dbConn)) {
    		throw new Exception('Not a valid PEAR DB connection!');
    	}
        $this->_dbConn = $dbConn;
    }

    function setDialerProcess($dialProc)
    {
        $this->_dialProc = $dialProc;
    }

    function setDialSrv($dialSrv)
    {
        $this->_dialSrv = $dialSrv;
    }


    /**
     * Procedimiento que lee la lista de agentes que pertenecen a cada cola, 
     * parsea la información disponible, y construye la lista de cola a la 
     * que pertenece cada agente. Sólo se almacena la información de los
     * miembros que tienen la forma "Agent/DDDDDD". También se actualiza
     * la información de las colas monitoreadas.
     */
    function actualizarCacheAgentes()
    {
        if (is_null($this->_timestampCache) || time() - $this->_timestampCache >= MAX_TTL_CACHE_AGENTE) {
            $this->_leerColasMonitoreadas();
            $this->_leerListaAgentes();
            $this->_timestampCache = time();
        }
    }
    
    /**
     * Leer las colas monitoreadas desde la base de datos.
     */
    private function _leerColasMonitoreadas()
    {
    	$lista =& $this->_dbConn->getAssoc(
            'SELECT id, queue FROM queue_call_entry WHERE estatus = "A" ORDER BY queue');
        if (!DB::isError($lista)) {
        	$this->_cacheColasMonitoreadas = $lista;
        } else {
        	$this->oMainLog->output('ERR: no se puede leer lista de colas - '.$lista->getMessage());
        }
    }
    
    /**
     * Leer la cola a la que pertenece cada agente.
     */
    private function _leerListaAgentes()
    {
        $listaAgentes = NULL;

        if (is_null($this->_astConn)) {
        	$this->oMainLog->output('ERR: ya no se dispone de una conexión válida al Asterisk.');
            $this->oMainLog->output('ERR: se requiere que se indique una conexión nueva.');
        } else {
        	// Leer la información de todas las colas...
            $respuestaCola = $this->_astConn->Command('queue show');
            if (is_array($respuestaCola)) {
                if (isset($respuestaCola['data'])) {
                    $listaAgentes = array();
                    $lineasRespuesta = explode("\n", $respuestaCola['data']);
                    $sColaActual = NULL;
                    foreach ($lineasRespuesta as $sLinea) {
                    	$regs = NULL;
                        if (ereg('^([[:digit:]]+)[[:space:]]+has[[:space:]]+[[:digit:]]+[[:space:]]+calls', $sLinea, $regs)) {
                    	   // Se ha encontrado el inicio de una descripción de cola
                            $sColaActual = $regs[1];
                        } elseif (ereg('^[[:space:]]+(Agent/[[:digit:]]+)', $sLinea, $regs)) {
                        	// Se ha encontrado el agente en una cola en particular
                            if (!is_null($sColaActual)) {
                                if (!isset($listaAgentes[$regs[1]]))
                                	$listaAgentes[$regs[1]] = array();
                               	array_push($listaAgentes[$regs[1]], $sColaActual);
                            }
                        }
                    }
                    $this->_cacheAgentesCola = $listaAgentes;
                } else {
                	$this->oMainLog->output('ERR: lost synch with Asterisk AMI ("queue show" response lacks "data").');
                }
            } else {
                /* Al gestor de llamadas entrantes no le compete reiniciar la 
                 * conexión al Asterisk. Lo que se puede hacer es olvidar la
                 * referencia al objeto de conexión que ahora es inválido, y
                 * esperar a que el objeto llamador actualice una nueva conexión
                 * a usar en lugar de la que se ha desechado.
                 */             
                $this->oMainLog->output('ERR: no se puede enviar petición de listado de colas al Asterisk, se elimina referencia a conexión!');
                $this->oMainLog->output('ERR: cache de agentes en colas puede estar desfasado.');
                $this->_astConn = NULL;
            }
        }
    }

    /**
     * Procedimiento que debe ser llamado para notificar un evento Join. 
     * Como parte de los parámetros, se espera que exista un Queue que 
     * indique cuál es la cola a la que ha ingresado la llamada. También
     * se espera que aparezca un CallerID con el número que llama, y un
     * Uniqueid que contiene el código de la llamada a almacenar.
     * 
     * @param   array   eventParams Parámetros que fueron pasados al evento 
     * 
     * @return bool     VERDADERO si esta llamada fue ingresada a la tabla de 
     *                  llamadas en curso, FALSO si la llamada fue ignorada. 
     */
    function notificarJoin($eventParams)
    {
        if ($this->DEBUG) $this->oMainLog->output("DEBUG: ENTER notificarJoin");
        
        $bLlamadaManejada = FALSE;

        // Asegurarse de que el caché está fresco
        // TODO: POSIBLE PUNTO DE REENTRANCIA
        $this->actualizarCacheAgentes();
        
        if (in_array($eventParams['Queue'], $this->_cacheColasMonitoreadas)) {
        	// Esta es una llamada entrante que debe de ser registrada
        	$idCampania = NULL;

			if ($this->_tieneCampaignEntry) {
	            // Buscar la campaña que está asociada a la cola actual
	            $iTimestamp = time();
	            $sFecha = date('Y-m-d', $iTimestamp);
	            $sHora = date('H:i:s', $iTimestamp);
	            $sPeticionCampania = 
	                'SELECT campaign_entry.id '.
	                'FROM campaign_entry, queue_call_entry '.
	                'WHERE campaign_entry.id_queue_call_entry = queue_call_entry.id '.
	                    'AND queue_call_entry.queue = ? '.
	                    'AND datetime_init <= ? '.
	                    'AND datetime_end >= ? '.
	                    'AND campaign_entry.estatus = "A" '.
	                    'AND queue_call_entry.estatus = "A" '.
	                    'AND ('.
	                        '(daytime_init < daytime_end AND daytime_init <= ? AND daytime_end > ?) '.
	                        'OR (daytime_init > daytime_end AND (? < daytime_init OR daytime_end < ?)))';
	            $idCampania = $this->_dbConn->getOne($sPeticionCampania, 
	                array($eventParams['Queue'], $sFecha, $sFecha, $sHora, $sHora, $sHora, $sHora));            
	            // ATENCION: $idCampania puede ser nulo
	            if (DB::isError($idCampania)) {
	                $this->oMainLog->output("ERR: no se puede consultar posible campaña para llamada entrante - ".
	                    $idCampania->getMessage());
	                $this->oMainLog->output('DEBUG: '.print_r($idCampania, 1));
	                $idCampania = NULL;
	            }
			}
            
            $sTrunkLlamada = '';
            if ($this->_tieneTrunk) {
                if ($this->DEBUG) {
                    $this->oMainLog->output('DEBUG: OnJoin: se tiene Channel='.$eventParams['Channel']);
                }
                $regs = NULL;
                if (!ereg('^(.+)-[0-9a-fA-F]+$', $eventParams['Channel'], $regs)) {
                	$this->oMainLog->output('ERR: no se puede extraer trunk a partir de Channel='.$eventParams['Channel']);
                } else {
                	$sTrunkLlamada = $regs[1];
                    if ($this->DEBUG) {
                        $this->oMainLog->output('DEBUG: OnJoin: se tiene trunk='.$sTrunkLlamada);
                    }
                }
            }
            
            // Llevar el registro del Uniqueid de la llamada que entra
            $this->_mapaUID[] = array(
                'CID'   =>  $eventParams['Uniqueid'],
                'AID'   =>  NULL,
            );
            if ($this->DEBUG) {
            	$this->oMainLog->output('DEBUG: OnJoin: registrado mapa Uniqueid CID='.$eventParams['Uniqueid'].' AID=NULL');
                $this->oMainLog->output('DEBUG: OnJoin: mapa tiene ahora '.count($this->_mapaUID).' elementos');
            }

            // Asterisk 1.6.2.x usa CallerIDNum y Asterisk 1.4.x usa CallerID
            $sCallerID = '';
            if (isset($eventParams['CallerIDNum'])) $sCallerID = $eventParams['CallerIDNum'];
            if (isset($eventParams['CallerID'])) $sCallerID = $eventParams['CallerID'];

            /* Se consulta el posible contacto en base al caller-id. Si hay 
             * exactamente un contacto, su ID se usa para la inserción. */
            $idContact = NULL;
            $listaIdContactos = $this->_dbConn->getCol(
                'SELECT id FROM contact WHERE telefono = ?', 0, array($sCallerID));
            if (DB::isError($listaIdContactos)) {
            	$this->oMainLog->output('ERR: no se puede consultar contacto para llamada entrante - '.
                    $listaIdContactos->getMessage());
            } elseif (count($listaIdContactos) == 1) {
            	$idContact = $listaIdContactos[0];
            }
            
            // Insertar la información de la llamada entrante en el registro
            $idCola = array_search($eventParams['Queue'], $this->_cacheColasMonitoreadas);
            $camposSQL = array(
                array('id_agent',               'NULL',         null),
                array('id_queue_call_entry',    '?',            $idCola),
                array('id_contact',             (is_null($idContact) ? 'NULL' : '?'), $idContact),
                array('callerid',               '?',            $sCallerID),
                array('datetime_entry_queue',   'NOW()',        null),
                array('datetime_init',          'NULL',         null),
                array('datetime_end',           'NULL',         null),
                array('duration_wait',          'NULL',         null),
                array('duration',               'NULL',         null),
                array('status',                 "'en-cola'",    null),
                array('uniqueid',               '?',            $eventParams['Uniqueid']),
            );
            if ($this->_tieneCampaignEntry && !is_null($idCampania))
                $camposSQL[] = array('id_campaign', '?', $idCampania);
            if ($this->_tieneTrunk)
                $camposSQL[] = array('trunk', '?', $sTrunkLlamada);
            
            $sListaCampos = $sListaValores = '';
            $queryParams = array();
            foreach ($camposSQL as $tuplaCampo) {
            	if (strlen($sListaCampos) > 0) $sListaCampos .= ', ';
                if (strlen($sListaValores) > 0) $sListaValores .= ', ';
                $sListaCampos .= $tuplaCampo[0];
                $sListaValores .= $tuplaCampo[1];
                if (!is_null($tuplaCampo[2])) $queryParams[] = $tuplaCampo[2];
            }
            $sQueryInsert = sprintf('INSERT INTO call_entry (%s) VALUES (%s)', $sListaCampos, $sListaValores);
            if ($this->DEBUG) {
            	$this->oMainLog->output('DEBUG: OnJoin: a punto de ejecutar ['.
                    $sQueryInsert.'] con valores ['.join($queryParams, ',').']...');
            }
            
            $resultado =& $this->_dbConn->query(
                $sQueryInsert, 
                $queryParams);
            if (DB::isError($resultado)) {
                $this->oMainLog->output(
                    'ERR: no se puede insertar registro de llamada (log) - '.
                    $resultado->getMessage());
            }
        }

        if ($this->DEBUG) $this->oMainLog->output("DEBUG: EXIT notificarJoin");
        return $bLlamadaManejada;    	
    }

    function actualizarMapaUID($id_viejo, $id_nuevo)
    {
        for ($i = 0; $i < count($this->_mapaUID); $i++) {
            if ($this->_mapaUID[$i]['CID'] == $id_viejo) {
                $this->_mapaUID[$i]['CID'] = $id_nuevo;
                if ($this->DEBUG) {
                    $this->oMainLog->output('DEBUG: actualizarMapaUID: asociado para CID='.$id_viejo.' cambiado a CID='.$id_nuevo);
                }
            }
        }
    }
    
    /**
     * Procedimiento que debe ser llamado para notificar un evento Link. Como
     * parte de los parámetros, se espera que exista un Channel1 o Channel2
     * que contenga un agente. Según el lado que contenga el agente, se
     * examina Uniqueid[1|2] y CallerID[1|2]. Con esto se consigue el CallerID
     * y el UniqueID para ingresar a la tabla de llamadas en curso y de 
     * llamadas recibidas.
     * 
     * @param   array   eventParams Parámetros que fueron pasados al evento 
     * 
     * @return bool     VERDADERO si esta llamada fue ingresada a la tabla de 
     *                  llamadas en curso, FALSO si la llamada fue ignorada. 
     */
    function notificarLink($eventParams)
    {
        if ($this->DEBUG) $this->oMainLog->output("DEBUG: ENTER notificarLink");
        $bLlamadaManejada = FALSE;

        // Asegurarse de que el caché está fresco
        // TODO: POSIBLE PUNTO DE REENTRANCIA
        $this->actualizarCacheAgentes();

        // Nótese que para canal 1, se requiere ID y CID 2, y viceversa.
        $sKey_Uniqueid = NULL;
        $sKey_Uniqueid_Agente = NULL;
        $sKey_CallerID = NULL;
        $listaColasCandidatas = NULL;
        $sNombreAgente = NULL;
        $sRemChannel = NULL;
        if (isset($eventParams['Channel1']) &&            
            isset($this->_cacheAgentesCola[$eventParams['Channel1']])) {
            $sNombreAgente = $eventParams['Channel1'];
            $sRemChannel = $eventParams['Channel2'];
            $listaColasCandidatas = $this->_cacheAgentesCola[$sNombreAgente];
            $sKey_Uniqueid = 'Uniqueid2';
            $sKey_CallerID = 'CallerID2';
            $sKey_Uniqueid_Agente = 'Uniqueid1';
        } elseif (isset($eventParams['Channel2']) && 
            isset($this->_cacheAgentesCola[$eventParams['Channel2']])) {
            $sNombreAgente = $eventParams['Channel2'];
            $sRemChannel = $eventParams['Channel1'];
            $listaColasCandidatas = $this->_cacheAgentesCola[$sNombreAgente];
            $sKey_Uniqueid = 'Uniqueid1';
            $sKey_CallerID = 'CallerID1';
            $sKey_Uniqueid_Agente = 'Uniqueid2';
        } elseif ($this->DEBUG) {
            $this->oMainLog->output("DEBUG: no se encuentra un agente llamado ".
                "$eventParams[Channel1] ni uno llamado $eventParams[Channel2] en cache de agentes : ".
                print_r($this->_cacheAgentesCola, TRUE));
        }

        /* Esta llamada puede ser una llamada que regresa de HOLD. Entonces 
         * current_call_entry.status debe tener "hold", y ChannelClient coincide
         * con $sRemChannel */
        $tuplaCallEntry = $this->_dbConn->getRow(
                'SELECT current_call_entry.id_call_entry AS id_call_entry, current_call_entry.id AS id '.
                'FROM current_call_entry, call_entry '.
                'WHERE current_call_entry.id_call_entry = call_entry.id '.
                    'AND call_entry.status = "hold" '.
                    'AND current_call_entry.ChannelClient = ?', 
                array($sRemChannel), 
                DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaCallEntry)) {
            $this->oMainLog->output("ERR: no se puede consultar estado HOLD en llamadas entrantes - ".
                $tuplaCallEntry->getMessage());
        } elseif (!is_null($tuplaCallEntry)) {
            /* La llamada ha sido ya ingresada en current_calls, y se omite 
             * procesamiento futuro. */
            if ($this->DEBUG)
                $this->oMainLog->output("DEBUG: notificarLink(): llamada ".
                    $eventParams['Uniqueid1'].'/'.$eventParams['Uniqueid2'].
                    " regresa de HOLD, se omite procesamiento futuro.");
            $result =& $this->_dbConn->query(
                'UPDATE call_entry SET status = "activa", uniqueid = ? WHERE id = ?',
                array($eventParams[$sKey_Uniqueid], $tuplaCallEntry['id_call_entry']));
            if (DB::isError($result)) {
                $this->oMainLog->output(
                    "ERR: no se puede actualizar estado de llamada entrante (hold->activa) (1) - ".
                    $result->getMessage());
            }
            $result =& $this->_dbConn->query(
                'UPDATE current_call_entry SET uniqueid = ? WHERE id = ?',
                array($eventParams[$sKey_Uniqueid], $tuplaCallEntry['id']));
            if (DB::isError($result)) {
                $this->oMainLog->output(
                    "ERR: no se puede actualizar estado de llamada entrante (hold->activa) (2) - ".
                    $result->getMessage());
            }
            $listaColasCandidatas = NULL;
        }

        if (!is_null($listaColasCandidatas)) {
            // Verificar que la cola se encuentra entre las colas monitoreadas
            if (count(array_intersect($listaColasCandidatas, $this->_cacheColasMonitoreadas)) > 0) {            	
                // Esta es una llamada entrante que debe de ser registrada
                
                // Obtener el ID del agente en la base, dado su identificación
                $regs = NULL;
                ereg('^[[:alnum:]]+/([[:digit:]]+)$', $sNombreAgente, $regs);
                $sNumeroAgente = $regs[1];
                $idAgente =& $this->_dbConn->getOne(
                    "SELECT id FROM agent WHERE number = ? AND estatus = 'A'",
                    array($sNumeroAgente));
                if (!DB::isError($idAgente) && is_numeric($idAgente)) {
                    $bLlamadaManejada = TRUE;
                    // Recolectar los índices de las colas monitoreadas que constan en las
                    // colas a las que pertenece el agente
                    $listaIdCola = array();
                    foreach ($this->_cacheColasMonitoreadas as $keyCola => $sColaMonitoreada) {
                    	if (in_array($sColaMonitoreada, $listaColasCandidatas)) $listaIdCola[] = $keyCola;
                    }
                    if (count($listaIdCola) == 0) {
                    	$this->oMainLog->output(
                    		"BUG: se supone que hay al menos una cola candidada, pero no hay índices:\n".
                    			print_r($this->_cacheColasMonitoreadas, TRUE)."\n".
                    			print_r($listaColasCandidatas, TRUE));
                    	die();
                    }
                    
                    // Buscar el ID de base de datos de la llamada a partir de su Uniqueid
                    $tuplaLlamada =& $this->_dbConn->getRow(
                        'SELECT id, id_queue_call_entry, callerid FROM call_entry '.
                        'WHERE uniqueid = ? AND datetime_end IS NULL ORDER BY id DESC',
                        array($eventParams[$sKey_Uniqueid]),
                        DB_FETCHMODE_OBJECT);
                    if (DB::isError($tuplaLlamada)) {
                        $this->oMainLog->output(
                            'ERR: no se puede leer registro de llamada (log) - '.
                            $tuplaLlamada->getMessage());
                    } elseif (is_null($tuplaLlamada)) {
                        if ($this->DEBUG) {
                            $this->oMainLog->output(
                                "WARN: no se encuentra registro de llamada {$eventParams[$sKey_Uniqueid]} (log) - se asume agente pertenece a más de una cola.");
                        }
                        $bLlamadaManejada = FALSE;
                    } else {
                        // Recoger el ID de la llamada al agente que fue enlazada
                        // con esta llamada entrante. Esto es necesario para 
                        // marcar la llamada como cerrada al transferir.
                        for ($i = 0; $i < count($this->_mapaUID); $i++) {
                            if ($this->_mapaUID[$i]['CID'] == $eventParams[$sKey_Uniqueid]) {
                                $this->_mapaUID[$i]['AID'] = $eventParams[$sKey_Uniqueid_Agente];
                                if ($this->DEBUG) {
                                	$this->oMainLog->output('DEBUG: OnLink: asociado para CID='.
                                        $eventParams[$sKey_Uniqueid].
                                        ' AID='.$eventParams[$sKey_Uniqueid_Agente]);
                                }
                            }
                        }

                        // Verificaciones de depuración                        
                    	$idCola = NULL;
                    	if (in_array($tuplaLlamada->id_queue_call_entry, $listaIdCola))
                    		$idCola = $tuplaLlamada->id_queue_call_entry;
                    	if ($tuplaLlamada->id_queue_call_entry != $idCola) {
                            $this->oMainLog->output(
                                "ERR: registro de llamada {$tuplaLlamada->id} ".
                                "uniqueid={$eventParams[$sKey_Uniqueid]} indica ".
                                "ID de cola {$tuplaLlamada->id_queue_call_entry} vs. $idCola!");                    		
                    	}
                        if ($this->DEBUG && $tuplaLlamada->callerid != $eventParams[$sKey_CallerID]) {
                            $this->oMainLog->output(
                                "ERR: registro de llamada {$tuplaLlamada->id} ".
                                "uniqueid={$eventParams[$sKey_Uniqueid]} indica ".
                                "callerid {$tuplaLlamada->callerid} vs. {$eventParams[$sKey_CallerID]}!");                            
                        }

                        // Actualización de la tabla de llamadas entrantes
                        $resultado =& $this->_dbConn->query(
                            'UPDATE call_entry SET id_agent = ?, datetime_init = NOW(), '.
                                'duration_wait = UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(datetime_entry_queue), '.
                                "status = 'activa' ".
                            'WHERE id = ?',
                            array($idAgente, $tuplaLlamada->id));
                        if (!DB::isError($resultado)) {
                            /* En el transcurso de una llamada, pueden haber múltiples eventos Link.
                             * Si este no es el primer event Link para la llamada entrante, puede 
                             * que haya ya en current_call_entry un registro para la llamada de
                             * interés. Sólo debe de insertarse si no existe un registro previo. */
                            $cuentaLlamada =& $this->_dbConn->getOne(
                                'SELECT COUNT(*) FROM current_call_entry WHERE uniqueid = ?',
                                array($eventParams[$sKey_Uniqueid]));
                            if (DB::isError($cuentaLlamada)) {
                            	$this->oMainLog->output(
                                    'ERR: no se puede verificar duplicidad de registro de llamada (actual) - '.
                                    $cuentaLlamada->getMessage());
                                $cuentaLlamada = 0;
                            }
                            if ($cuentaLlamada <= 0) {
                                $resultado =& $this->_dbConn->query(
                                    'INSERT INTO current_call_entry (id_agent, id_queue_call_entry, '.
                                        'id_call_entry, callerid, datetime_init, uniqueid, ChannelClient) '.
                                    'VALUES (?, ?, ?, ?, NOW(), ?, ?)',
                                    array($idAgente, $idCola, $tuplaLlamada->id, $eventParams[$sKey_CallerID], 
                                        $eventParams[$sKey_Uniqueid], $sRemChannel));
                                if (DB::isError($resultado)) {
                                    $this->oMainLog->output(
                                        'ERR: no se puede insertar registro de llamada (actual) - '.
                                        $resultado->getMessage());
                                } else {
                                	$infoLlamada = $this->_dialProc->leerInfoLlamada('incoming',
                                        NULL, $tuplaLlamada->id);
                                    if (!is_null($infoLlamada)) {
                                        if ($this->DEBUG) {
                                        	$this->oMainLog->output('DEBUG: notificarLink: notificando evento AgentLinked');
                                        }
                                        $this->_dialSrv->notificarEvento_AgentLinked(
                                            $sNombreAgente, $sRemChannel, $infoLlamada);
                                    }
                                }
                            } else {
                            	if ($this->DEBUG) $this->oMainLog->output('DEBUG: llamada entrante ya consta en registro de llamadas en curso.');
                            }
                        } else {
                            $this->oMainLog->output(
                                'ERR: no se puede actualizar registro de llamada (log) - '.
                                $resultado->getMessage());
                        }
                    }
                } else if (DB::isError($idAgente)) {
                	$this->oMainLog->output(
                        'ERR: no se puede leer lista de agentes activos - '.
                        $idAgente->getMessage());
                }
            } elseif ($this->DEBUG) {
                $this->oMainLog->output("DEBUG: cola(s) candidata(s) [".(join($listaColasCandidatas, ' '))."] no se ".
                    "encuentra en cache de colas monitoreadas: ".
                    print_r($this->_cacheColasMonitoreadas, TRUE));
            }
        }
        
        if ($this->DEBUG) $this->oMainLog->output("DEBUG: EXIT notificarLink");
        return $bLlamadaManejada;
    }
    
    /**
     * Procedimiento que remueve una llamada ya terminada de la lista de las 
     * llamadas en curso en current_call_entry, en base a los parámetros en
     * $eventParams. 
     * 
     * @param array eventParams Parámetros que fueron pasados al evento 
     * 
     * @return bool VERDADERO si la llamada fue reconocida y procesada
     */    
    function notificarHangup($eventParams)
    {
        if ($this->DEBUG) $this->oMainLog->output("DEBUG: ENTER notificarHangup");
        $bLlamadaManejada = FALSE;
        $tuplaLlamada = NULL;

        // Buscar el Uniqueid de la llamada recibida
        $tuplaLlamada =& $this->_dbConn->getRow(
            'SELECT id, id_call_entry, hold FROM current_call_entry WHERE uniqueid = ?',
            array($eventParams['Uniqueid']),
            DB_FETCHMODE_ASSOC);
        if (DB::isError($tuplaLlamada)) {
            $this->oMainLog->output(
                'ERR: no se puede buscar registro de llamada (actual) - '.
                $tuplaLlamada->getMessage());
            $tuplaLlamada = NULL;                	
        } elseif (is_array($tuplaLlamada)) {
            if ($this->DEBUG) {
            	$this->oMainLog->output(
                    'DEBUG: notificarHangup: encontrada información de llamada directa : '.
                    print_r($tuplaLlamada, 1));
            }
        } else {
        	$tuplaLlamada = NULL;
        }

        if (is_null($tuplaLlamada)) {
        	// Caso Hangup/abandonada - también se debe buscar en call_entry
            $tuplaLlamada =& $this->_dbConn->getRow(
                'SELECT current_call_entry.id AS id, call_entry.id AS id_call_entry, '.
                    'current_call_entry.hold AS hold FROM call_entry '.
                'LEFT JOIN current_call_entry ON current_call_entry.id_call_entry = call_entry.id '.
                'WHERE call_entry.uniqueid = ? AND call_entry.datetime_end IS NULL',
                array($eventParams['Uniqueid']),
                DB_FETCHMODE_ASSOC);
            if (DB::isError($tuplaLlamada)) {
                $this->oMainLog->output(
                    'ERR: no se puede buscar registro de llamada (actual) - '.
                    $tuplaLlamada->getMessage());
                $tuplaLlamada = NULL;                   
            } elseif (is_array($tuplaLlamada)) {
                if ($this->DEBUG) {
                    $this->oMainLog->output(
                        'DEBUG: notificarHangup: encontrada información de llamada abandonada : '.
                        print_r($tuplaLlamada, 1));
                }
            }
        }
        
        if (is_null($tuplaLlamada)) {
            /* Si la llamada ha sido transferida, la porción que está siguiendo
               el marcador todavía está activa, pero transferida a otra persona.
               Sin embargo, el agente está ahora libre y recibirá otra llamada.
               El hangup de aquí podría ser para la parte de la llamada del 
               agente. */
            for ($i = 0; $i < count($this->_mapaUID); $i++) {
                if ($this->_mapaUID[$i]['AID'] == $eventParams['Uniqueid']) {
                    if ($this->DEBUG) {
                    	$this->oMainLog->output('DEBUG: OnHangup: para AID='.$eventParams['Uniqueid'].' CID='.$this->_mapaUID[$i]['CID']);
                    }
                    $tuplaLlamada =& $this->_dbConn->getRow(
                        'SELECT current_call_entry.id AS id, call_entry.id AS id_call_entry, '.
                            'current_call_entry.hold AS hold FROM call_entry '.
                        'LEFT JOIN current_call_entry ON current_call_entry.id_call_entry = call_entry.id '.
                        'WHERE call_entry.uniqueid = ? AND call_entry.datetime_end IS NULL',
                        array($this->_mapaUID[$i]['CID']),
                        DB_FETCHMODE_ASSOC);
                    if (DB::isError($tuplaLlamada)) {
                        $this->oMainLog->output(
                            'ERR: no se puede buscar registro de llamada (actual) - '.
                            $tuplaLlamada->getMessage());
                        $tuplaLlamada = NULL;                   
                    } elseif (is_array($tuplaLlamada)) {
                        if ($this->DEBUG) {
                            $this->oMainLog->output(
                                'DEBUG: OnHangup: encontrada información de llamada por agente : '.
                                print_r($tuplaLlamada, 1));
                        }                    	
                    }
                }
            }
        }

        if (!is_null($tuplaLlamada)) {
        	$bLlamadaManejada = TRUE;
            
            if (!is_null($tuplaLlamada['hold']) && $tuplaLlamada['hold'] == 'S') {
                /* En caso de que la llamada haya sido puesta en espera, la llamada 
                 * se transfiere a la cola de parqueo. Esto ocasiona un evento Unlink
                 * sobre la llamada, pero no debe de considerarse como el cierre de
                 * la llamada.
                 */
                if ($this->DEBUG)
            	   $this->oMainLog->output("DEBUG: notificarUnlink - llamada ha sido puesta en HOLD en vez de colgada.");
                $result =& $this->_dbConn->query(
                    "UPDATE call_entry SET status = 'hold' WHERE id = ?",
                    array($tuplaLlamada['id_call_entry']));
                if (DB::isError($result)) {
                    $this->oMainLog->output(
                        'ERR: no se puede actualizar registro de llamada en HOLD (log) - '.
                        $result->getMessage());
                }
                $tuplaLlamada = NULL;                   
            }
        }
        if (!is_null($tuplaLlamada)) {
            $bLlamadaManejada = TRUE;
        	if (!is_null($tuplaLlamada['id'])) {
                $result =& $this->_dbConn->query(
                    'DELETE FROM current_call_entry WHERE id = ?',
                    array($tuplaLlamada['id']));            
                if (DB::isError($result)) {
                    $this->oMainLog->output(
                        'ERR: no se puede remover registro de llamada (actual) - '.
                        $result->getMessage());
                }
            }                   
            $result =& $this->_dbConn->query(
                'UPDATE call_entry SET datetime_end = NOW(), '.
                    'duration_wait = IF(datetime_init IS NULL, '.
                        'UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(datetime_entry_queue), '.
                        'duration_wait), '.
                    'duration = IF(datetime_init IS NULL, '.
                        'NULL, '.
                        'UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(datetime_init)), '.
                    "status = IF(datetime_init IS NULL, 'abandonada', 'terminada') ".
                'WHERE id = ?',
                array($tuplaLlamada['id_call_entry']));
            if (DB::isError($result)) {
                $this->oMainLog->output(
                    'ERR: no se puede actualizar registro de llamada (log) - '.
                    $result->getMessage());
            }
            
            // Remover rastro de la llamada del arreglo _mapaUID
            $temp = array();
            foreach ($this->_mapaUID as $tupla) {
                if ($tupla['CID'] != $eventParams['Uniqueid'] && $tupla['AID'] != $eventParams['Uniqueid'])
                    $temp[] = $tupla;
            }
            $this->_mapaUID = $temp;
            if ($this->DEBUG) {
            	$this->oMainLog->output('DEBUG: notificarHangup: quitado (CID|AID)='.$eventParams['Uniqueid'].
                    ' mapa tiene ahora '.count($this->_mapaUID).' elementos');
            }
            
            // Consultar la campaña a la que pertenece la llamada
            $idCampaign = NULL;
            if ($this->_tieneCampaignEntry) {
            	$idCampaign = $this->_dbConn->getOne(
                    'SELECT id_campaign FROM call_entry WHERE id = ?',
                    array($tuplaLlamada['id_call_entry']));
                if (DB::isError($idCampaign)) {
                	$this->oMainLog->output('ERR: no se puede consultar campaña de llamada: '.$idCampaign->getMessage());
                    $idCampaign = NULL;
                }
            }

            // Consultar callerid y número de agente
            $tuplaAgente = $this->_dbConn->getRow(
                'SELECT callerid, number FROM call_entry, agent '.
                'WHERE call_entry.id = ? AND call_entry.id_agent = agent.id',
                array($tuplaLlamada['id_call_entry']),
                DB_FETCHMODE_ASSOC
            );
            if (DB::isError($tuplaAgente)) {
            	$this->oMainLog->output('ERR: no se puede consultar callerid/agente de llamada: '.$tuplaAgente->getMessage());
            } elseif (is_null($tuplaAgente)) {
                $this->oMainLog->output('ERR: no se encuentra el agente que atendió la llamada ID='.
                    $tuplaLlamada['id_call_entry'].' id_campaign='.(is_null($idCampaign) ? 'NULL' : $idCampaign));
            } else{
                // Reportar que se ha cerrado la llamada
                if ($this->DEBUG) {
                    $this->oMainLog->output('DEBUG: notificarHangup: notificando evento AgentUnlinked');
                }                
                $this->_dialSrv->notificarEvento_AgentUnlinked("Agent/".$tuplaAgente['number'], array(
                    'calltype'      =>  'incoming',
                    'campaign_id'   =>  $idCampaign,
                    'call_id'       =>  $tuplaLlamada['id_call_entry'],
                    'phone'         =>  $tuplaAgente['callerid'],
                ));
            }
        }
        
        if ($this->DEBUG) $this->oMainLog->output("DEBUG: EXIT notificarHangup");
        return $bLlamadaManejada;
    }
    
    /**
     * Procedimiento que intenta limpiar la tabla de llamadas en curso entrantes,
     * y marca las llamadas no terminadas en el log, como marcadas sin 
     * finalización, para mantener consistencia en el log. Este método sólo debe
     * ser llamado al construir el objeto (automáticamente), o cuando se está a 
     * punto de terminar el programa.
     * 
     * @return void
     */
    function finalizarLlamadasEntrantesEnCurso()
    {
        // Remover rastro de llamadas en la lista de llamadas actuales
        $result =& $this->_dbConn->query('DELETE FROM current_call_entry');
        if (DB::isError($result)) {
            $this->oMainLog->output(
                'ERR: no se puede limpiar registro de llamada (actual) - '.
                $result->getMessage());
        }
        
        // Marcar toda llamada sin fecha de finalización, como inválida
        $result =& $this->_dbConn->query(
            "UPDATE call_entry SET status = 'fin-monitoreo' WHERE datetime_end IS NULL");                   
        if (DB::isError($result)) {
            $this->oMainLog->output(
                'ERR: no se puede marcar registro de llamada (log) - '.
                $result->getMessage());
        }
    }
    
    function & reportarEstadoCampania($idCampania)
    {
    	$resumen = $this->_leerResumenCampania($idCampania);
        if (!is_null($resumen) && isset($resumen['queue'])) {
            $resumen['queuestatus'] = $this->_leerEstadoColaConBreaks($resumen['queue']);
            $resumen['activecalls'] = $this->_dbConn->getAll(
                'SELECT id AS callid, callerid AS dialnumber, "OnQueue" AS callstatus, '.
                    'datetime_entry_queue AS datetime_enterqueue '.
                'FROM call_entry '.
                'WHERE status = "en-cola" AND id_campaign = ? '.
                'ORDER BY datetime_entry_queue', 
                array($idCampania), DB_FETCHMODE_ASSOC);
            if (DB::isError($resumen['activecalls'])) {
                $this->oMainLog->output('ERR: no se puede leer llamadas activas - '.$resumen['activecalls']);
                $resumen['activecalls'] = array();
            }
        }        
        return $resumen;
    }
    
    private function _leerResumenCampania($idCampania)
    {
        // Leer la información en el propio registro de la campaña
        $sPeticionSQL = <<<LEER_RESUMEN_CAMPANIA
SELECT ce.id, ce.name, ce.datetime_init, ce.datetime_end, ce.daytime_init, 
    ce.daytime_end, qce.queue, ce.estatus 
FROM campaign_entry ce, queue_call_entry qce 
WHERE ce.id = ? AND ce.id_queue_call_entry = qce.id
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
        $sPeticionSQL = 'SELECT COUNT(*) AS n, status FROM call_entry WHERE id_campaign = ? GROUP BY status';
        $recordset = $this->_dbConn->getAll($sPeticionSQL, array($idCampania), DB_FETCHMODE_ASSOC);
        if (DB::isError($recordset)) {
            $this->oMainLog->output('ERR: no se puede leer estado de llamadas de campaña - '.$recordset->getMessage());
            return NULL;
        }
        $tupla['status'] = array(
            //'Pending'   =>  0,  // Llamada no ha sido realizada todavía

            //'Placing'   =>  0,  // Originate realizado, no se recibe OriginateResponse
            //'Ringing'   =>  0,  // Se recibió OriginateResponse, no entra a cola
            'OnQueue'   =>  0,  // Entró a cola, no se asigna a agente todavía
            'Success'   =>  0,  // Conectada y asignada a un agente
            'OnHold'    =>  0,  // Llamada fue puesta en espera por agente
            //'Failure'   =>  0,  // No se puede conectar llamada
            //'ShortCall' =>  0,  // Llamada conectada pero duración es muy corta
            //'NoAnswer'  =>  0,  // Llamada estaba Ringing pero no entró a cola
            'Abandoned' =>  0,  // Llamada estaba OnQueue pero no habían agentes
            'Finished'  =>  0,  // Llamada ha terminado luego de ser conectada a agente
            'LostTrack' =>  0,  // Programa fue terminado mientras la llamada estaba activa            
        );
        $mapaEstados = array(
            'en-cola'       =>  'OnQueue',
            'activa'        =>  'Success',
            'hold'          =>  'OnHold',
            'abandonada'    =>  'Abandoned',             
            'terminada'     =>  'Finished',
            'fin-monitoreo' =>  'LostTrack',
        );
        foreach ($recordset as $tuplaStatus) {
            $tupla['status'][$mapaEstados[$tuplaStatus['status']]] = $tuplaStatus['n'];
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

            // Listar información para llamada activa
            if (isset($estadoCola['members'][$sNumAgente]['clientchannel'])) {
                $sqlInfoLlamada = 
                    'SELECT ce.callerid AS dialnumber, ce.id AS callid, '.
                        'ce.datetime_entry_queue AS datetime_enterqueue, '.
                        'ce.datetime_init AS datetime_linkstart '.
                    'FROM current_call_entry cce, call_entry ce, agent a '.
                    'WHERE cce.id_call_entry = ce.id AND cce.ChannelClient = ? '.
                        'AND ce.id_agent = a.id AND a.number = ? '.
                    'ORDER BY ce.datetime_init DESC LIMIT 0,1';
                $infoLlamada = $this->_dbConn->getRow($sqlInfoLlamada, 
                    array($estadoCola['members'][$sNumAgente]['clientchannel'], $sNumAgente), 
                    DB_FETCHMODE_ASSOC);
                if (DB::isError($infoLlamada)) {
                	$this->oMainLog->output('ERR: no se puede leer información de llamada activa - '.$infoLlamada->getMessage());
                } else {
                    $estadoCola['members'][$sNumAgente]['dialnumber'] = $infoLlamada['dialnumber'];
                    $estadoCola['members'][$sNumAgente]['callid'] = $infoLlamada['callid'];
                    $estadoCola['members'][$sNumAgente]['datetime_enterqueue'] = $infoLlamada['datetime_enterqueue'];
                    $estadoCola['members'][$sNumAgente]['datetime_linkstart'] = $infoLlamada['datetime_linkstart'];
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

        $sqlInfoLlamada = 
            'SELECT ce.callerid AS dialnumber, ce.id AS callid, '.
                'ce.datetime_entry_queue AS datetime_enterqueue, '.
                'ce.datetime_init AS datetime_linkstart, ' .
                'ce.id_campaign '.
            'FROM current_call_entry cce, call_entry ce, agent a '.
            'WHERE cce.id_call_entry = ce.id '.
                'AND ce.id_agent = a.id AND a.number = ? '.
            'ORDER BY ce.datetime_init DESC LIMIT 0,1';
        $infoLlamada = $this->_dbConn->getRow($sqlInfoLlamada, 
            array($sNumAgente), 
            DB_FETCHMODE_ASSOC);
        if (DB::isError($infoLlamada)) {
            $this->oMainLog->output('ERR: no se puede leer información de llamada activa - '.$infoLlamada->getMessage());
        } elseif (count($infoLlamada) <= 0) {
            return NULL;
        } else {
            return array(
                'calltype'             =>  'incoming',
                'campaign_id'           =>  $infoLlamada['id_campaign'],
                'dialnumber'            =>  $infoLlamada['dialnumber'],
                'callid'                =>  $infoLlamada['callid'],
                'datetime_enterqueue'   =>  $infoLlamada['datetime_enterqueue'],
                'datetime_linkstart'    =>  $infoLlamada['datetime_linkstart'],
            );
        }
    }
}
?>