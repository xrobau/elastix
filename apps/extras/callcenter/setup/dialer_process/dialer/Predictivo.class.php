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
  $Id: Predictivo.class.php,v 1.5 2008/12/04 19:11:21 alex Exp $ */

class Predictivo
{
    private $_astConn;  // Conexión al Asterisk

    private $_estadisticasCola = NULL;
    private $_conflictReport = array();
    
    function Predictivo(&$astman)
    {
        $this->_estadisticasCola = array();
        $this->_astConn = $astman;
    }

	/**
	 * Procedimiento para reportar el bug de que el estado de 'agent show' difiere de 'queue show'
	 * y lista los agentes para los cuales se detecta discrepancia.
	 */
	function getAgentesConflicto()
	{
		return $this->_conflictReport;
	}	

    /**
     * Procedimiento para recuperar las estadísticas y parámetros de predicción
     * para la cola.
     * 
     * @param string    $sNombreCola    Cola sobre la cual hacer la predicción
     *
     * @return mixed    NULL si la cola no se conoce, o parámetros
     */
    function getEstadisticasCola($sNombreCola)
    {
    	return isset($this->_estadisticasCola[$sNombreCola]) ? $this->_estadisticasCola[$sNombreCola] : NULL;
    }

    private function _iniciarParamCola($sNombreCola)
    {
        $this->_estadisticasCola[$sNombreCola] = array(
            'TIEMPO_CONTESTAR'  =>  8,          // Tiempo que tarda abonado en contestar (segundos)
            'PROBABILIDAD_ATENCION' =>  0.97,   // Probabilidad de que usuario encolado sea atendido
            'PROMEDIO_DURACION'     =>  75.0,   // Promedio de duración de llamada callcenter (segundos)
            'DESVIACION_DURACION'   =>  17.0,   // Desviación estándar de llamada callcenter (segundos)
        );
    } 

    function setTiempoContestar($sNombreCola, $iTiempoContestar)
    {
    	if (is_numeric($iTiempoContestar) && $iTiempoContestar >= 0) {
            if (!isset($this->_estadisticasCola[$sNombreCola])) {
        		$this->_iniciarParamCola($sNombreCola);            
            }
            $this->_estadisticasCola[$sNombreCola]['TIEMPO_CONTESTAR'] = $iTiempoContestar;
        }
    }

    function setProbabilidadAtencion($sNombreCola, $iProbAtencion)
    {
        if (is_numeric($iProbAtencion) && $iProbAtencion >= 0) {
            if (!isset($this->_estadisticasCola[$sNombreCola])) {
                $this->_iniciarParamCola($sNombreCola);            
            }
            $this->_estadisticasCola[$sNombreCola]['PROBABILIDAD_ATENCION'] = $iProbAtencion;
        }
    }

    function setPromedioDuracion($sNombreCola, $iPromedioDuracion)
    {
        if (is_numeric($iPromedioDuracion) && $iPromedioDuracion >= 0) {
            if (!isset($this->_estadisticasCola[$sNombreCola])) {
                $this->_iniciarParamCola($sNombreCola);            
            }
            $this->_estadisticasCola[$sNombreCola]['PROMEDIO_DURACION'] = $iPromedioDuracion;
        }
    }

    function setDesviacionDuracion($sNombreCola, $iDesviacionDuracion)
    {
        if (is_numeric($iDesviacionDuracion) && $iDesviacionDuracion > 0) {
            if (!isset($this->_estadisticasCola[$sNombreCola])) {
                $this->_iniciarParamCola($sNombreCola);            
            }
            $this->_estadisticasCola[$sNombreCola]['DESVIACION_DURACION'] = $iDesviacionDuracion;
        }
    }

    /**
     * Procedimiento para calcular cuántas llamadas nuevas deben colocarse
     * según el estado actual de las llamadas.
     * 
     * @param string    $sNombreCola    Cola sobre la cual hacer la predicción
     * @param boolean   $bPredecir      Si VERDADERO (por omisión), usar algoritmo predictivo
     *                                  Si FALSO, sólo devuelve número de agentes ociosos
     * 
     * @return mixed    FALSE en caso de error, o número de llamadas a colocar
     */
    function predecirNumeroLlamadas($sNombreCola, $bPredecir = TRUE)
    {
    	if (!isset($this->_estadisticasCola[$sNombreCola])) {
    		// Inventarse parámetros. Lo correcto es que la aplicación realice
            // mediciones y especifique los verdaderos parámetros
            $this->_iniciarParamCola($sNombreCola);
    	}
        
        $estadoCola = $this->leerEstadoCola($sNombreCola);
        if (!is_array($estadoCola)) return FALSE;
        
        // Obtener número de ociosos más número de llamadas a punto de terminar
        $iNumLlamadasColocar = 0;
        foreach ($estadoCola['members'] as $infoAgente) {
        	// Ociosos
            if ($infoAgente['status'] == 'canBeCalled') $iNumLlamadasColocar++;
            
            // Llamadas a punto de terminar. Puede ocurrir que no se pueda alcanzar a
            // identificar el tiempo de habla de un agente, así que se verifica aquí
            // que se tenga un tiempo válido.
            if ($infoAgente['status'] == 'inUse' && $bPredecir && 
                !is_null($infoAgente['talkTime'])) {
            	$iTiempoTotal = 
                    $this->_estadisticasCola[$sNombreCola]['TIEMPO_CONTESTAR'] + 
                    $infoAgente['talkTime'];

                // Probabilidad de que 1 llamada haya terminado al cabo de $iTiempoTotal s.
                $iProbabilidad = $this->_probabilidadErlangAcumulada(
                    $iTiempoTotal,
                    1,
                    1 / $this->_estadisticasCola[$sNombreCola]['PROMEDIO_DURACION']);                    
                if ($iProbabilidad >= $this->_estadisticasCola[$sNombreCola]['PROBABILIDAD_ATENCION']) {
                	$iNumLlamadasColocar++;
                }
            }
        }
        
        // Restar del número de llamadas a colocar, el número de llamadas encoladas
        if ($iNumLlamadasColocar >= count($estadoCola['callers'])) {
        	$iNumLlamadasColocar -= count($estadoCola['callers']);
        } else {
        	$iNumLlamadasColocar = 0;
        }

        return $iNumLlamadasColocar;
    }

    /**
     * Procedimiento para interrogar al Asterisk sobre el estado de los agentes 
     * logoneados en una cola de código $sNombreCola. Con respecto a los agentes,
     * la intención final es la de averiguar desde cuándo han estado hablando, o
     * si están ociosos.
     * 
     * @param string    $sNombreCola    Código de la cola a interrogar
     * 
     * @result mixed    NULL en caso de error, o un arreglo en el sig. formato:
     * array(
     *      members =>  array(
     *          <CODIGO_AGENTE> =>  array(
     *              sourceline  =>  <linea parseada para agente>
     *              attributes  =>  array('dynamic', 'busy', ...),
     *              status      =>  {canBeCalled|inUse|unAvailable}
     *              talkTime    =>  {NULL|<segundos_hablando>}
     *          ),
     *      ),
     *      callers =>  array(
     *          <lista_llamadas_encoladas>
     *      ),
     * )
     */
    function leerEstadoCola($sNombreCola)
    {
    	$iTimestampActual = time();
        $estadoCola = NULL;
        $this->_conflictReport = NULL;
    
    	// TODO: validar formato de $sNombreCola
        $respuestaCola = NULL;
        $respuestaListaAgentes = NULL;
        $listaAgentesLibres = array();
                
        // Leer información inmediata (que no depende de canal)
        $respuestaListaAgentes = $this->_astConn->Command('agent show');
        if (is_array($respuestaListaAgentes))
            $respuestaCola = $this->_astConn->Command("queue show $sNombreCola");        

        $estadoCola = array(
            'members'   =>  array(),
            'callers'   =>  array(),
            'agent_show_output' => $respuestaListaAgentes,
            'show_queue_output' => $respuestaCola,
        );
            
        if (is_array($respuestaListaAgentes) && is_array($respuestaCola)) {

        	// Averiguar qué canal (si alguno) usa cada agente
            $lineasRespuesta = explode("\n", $respuestaListaAgentes['data']);
            $tiempoAgente = array();
            foreach ($lineasRespuesta as $sLinea) {
            	$regs = NULL;
                // 9000         (Over 9000!!!!!) logged in on SIP/1064-00000001 talking to DAHDI/1-1 (musiconhold is 'default')
                if (preg_match('/^\s*(\d{2,})/', $sLinea, $regs)) {
            		$sAgente = $regs[1];  // Agente ha sido identificado
                    $regs = NULL;
                    if (preg_match('|talking to (\w+/\S{2,})|', $sLinea, $regs)) {
                    	$sCanalAgente = $regs[1];
                        $tiempoAgente[$sAgente]['clientchannel'] = $sCanalAgente;
                        
                        // Para el canal, averiguar el momento de inicio de llamada
                        $respuestaCanal = $this->_astConn->Command("core show channel $sCanalAgente");
                        if (!is_array($respuestaCanal)) return NULL;
                        $lineasCanal = explode("\n", $respuestaCanal['data']);
                        foreach ($lineasCanal as $sLineaCanal) {
                        	$regs = NULL;
                            if (preg_match('/level \d+: start=(.*)/', $sLineaCanal, $regs)) {
                            	$sFechaInicio = $regs[1];
                                $iTimestampInicio = strtotime($sFechaInicio);
                                $tiempoAgente[$sAgente]['talkTime'] = $iTimestampActual - $iTimestampInicio;
                            }
                            /*
                            if (preg_match('/^NUMBER=(.+)$/', $sLineaCanal, $regs)) {
                                $tiempoAgente[$sAgente]['dialnumber'] = $regs[1];
                            }
                            */
                        }

                        if ($sNombreCola == '') {
                            $estadoCola['members'][$sAgente] = array(
                                'sourceline'    =>  $sLinea,
                                'attributes'    =>  array('Busy'),
                                'status'        =>  'inUse',
                                'talkTime'      =>  isset($tiempoAgente[$sAgente]['talkTime']) 
                                    ? $tiempoAgente[$sAgente]['talkTime'] : NULL,
                                'penalty'       =>  NULL,
                                'clientchannel' =>  $sCanalAgente,
                            );
                        }
                    } elseif (strpos($sLinea, 'is idle')) {
                        $listaAgentesLibres[] = $sAgente;
                        if ($sNombreCola == '') {
                            $estadoCola['members'][$sAgente] = array(
                                'sourceline'    =>  $sLinea,
                                'attributes'    =>  array('Not in use'),
                                'status'        =>  'canBeCalled',
                                'talkTime'      =>  NULL,
                                'penalty'       =>  NULL,
                                //'clientchannel' =>  NULL,
                            );
                        }
                    } elseif (strpos($sLinea, 'not logged in')) {
                        if ($sNombreCola == '') {
                            $estadoCola['members'][$sAgente] = array(
                                'sourceline'    =>  $sLinea,
                                'attributes'    =>  array('Unavailable'),
                                'status'        =>  'unAvailable',
                                'talkTime'      =>  NULL,
                                'penalty'       =>  NULL,
                                //'clientchannel' =>  NULL,
                            );
                        }
                    }
            	}
            }

            // Parsear la salida de la lista de colas
            $lineasRespuesta = explode("\n", $respuestaCola['data']);
            $sSeccionActual = NULL;
            foreach ($lineasRespuesta as $sLinea) {
                if (preg_match('/^\s*Members:/', $sLinea)) {
                    $sSeccionActual = "members";
                } else if (preg_match('/^\s*Callers:/', $sLinea)) {
                    $sSeccionActual = "callers";
                } else if (!is_null($sSeccionActual)) {
                     switch ($sSeccionActual) {
                     case 'members':
                        $sLinea = trim($sLinea);
                        $regs = NULL;
                        if (preg_match('|^Agent/(\d+)@?\s*(.*)$|', $sLinea, $regs)) {
                        	$sCodigoAgente = $regs[1];
                            $sInfoAgente = $regs[2];
                            $estadoCola['members'][$sCodigoAgente] = array(
                                'sourceline'    =>  $sLinea,
                                'attributes'    =>  array(),
                                'status'        =>  NULL,
                                'talkTime'      =>  NULL,
                                'penalty'		=>	NULL,
                            );
                            
                            // Extraer la información de penalización, si existe
                            if (preg_match('/^with penalty (\d+)\s+(.*)/', $sInfoAgente, $regs)) {
                            	$sInfoAgente = $regs[2];
                            	$estadoCola['members'][$sCodigoAgente]['penalty'] = $regs[1];
                            }
                            
                            // Separar todos los atributos del agente en la cola
                            // ej: "(dynamic) (Unavailable) has taken..."
                            $regs = NULL;
                            while (preg_match('/^\(([^)]+)\)\s+(.*)/', $sInfoAgente, $regs)) {
                            	$estadoCola['members'][$sCodigoAgente]['attributes'][] = $regs[1];
                                $sInfoAgente = $regs[2];                                
                                $regs = NULL;
                            }
                            
                      /* Fragmento del archivo main/devicestate.c
                         0 AST_DEVICE_UNKNOWN       "Unknown",      Valid, but unknown state
                         1 AST_DEVICE_NOT_INUSE     "Not in use",   Not used 
                         2 AST_DEVICE IN USE        "In use",       In use 
                         3 AST_DEVICE_BUSY          "Busy",         Busy 
                         4 AST_DEVICE_INVALID       "Invalid",      Invalid - not known to Asterisk 
                         5 AST_DEVICE_UNAVAILABLE   "Unavailable",  Unavailable (not registred) 
                         6 AST_DEVICE_RINGING       "Ringing",      Ring, ring, ring 
                         7 AST_DEVICE_RINGINUSE     "Ring+Inuse",   Ring and in use 
                         8 AST_DEVICE_ONHOLD        "On Hold"       On Hold */

                            // Decidir estado de agente en base a atributos presentes
                            if (in_array('paused', $estadoCola['members'][$sCodigoAgente]['attributes'])) {
                                // Agente está pausado y no disponible para ser llamado
                                $estadoCola['members'][$sCodigoAgente]['status'] = 'unAvailable';
                            } elseif (in_array('Not in use', $estadoCola['members'][$sCodigoAgente]['attributes']) ||
                                in_array('Ringing', $estadoCola['members'][$sCodigoAgente]['attributes'])) {
                                
                                // Agente está disponible para ser llamado
                                $estadoCola['members'][$sCodigoAgente]['status'] = 'canBeCalled';
                            } elseif (in_array('In use', $estadoCola['members'][$sCodigoAgente]['attributes']) ||
                                in_array('Busy', $estadoCola['members'][$sCodigoAgente]['attributes']) ||
                                in_array('Ring+Inuse', $estadoCola['members'][$sCodigoAgente]['attributes'])) {
                            	
                            	if (in_array('In use', $estadoCola['members'][$sCodigoAgente]['attributes']) &&
                            		in_array($sCodigoAgente, $listaAgentesLibres)) {
                            		// BUG: reporte de 'agent show' difiere de 'queue show'
                            		$estadoCola['members'][$sCodigoAgente]['status'] = 'canBeCalled';
                            		$estadoCola['members'][$sCodigoAgente]['conflictBug'] = TRUE;
                            		
                            		if (is_null($this->_conflictReport)) $this->_conflictReport = array();
                            		$this->_conflictReport[] = $sCodigoAgente;
                            	} else {
	                                // Agente está ocupado con una llamada
	                                $estadoCola['members'][$sCodigoAgente]['status'] = 'inUse';
                            	}
                            } else {
                            	// Agente no está disponible
                                $estadoCola['members'][$sCodigoAgente]['status'] = 'unAvailable';
                            }
                            if (isset($tiempoAgente[$sCodigoAgente])) {
                                if (isset($tiempoAgente[$sCodigoAgente]['talkTime'])) 
                                    $estadoCola['members'][$sCodigoAgente]['talkTime'] = $tiempoAgente[$sCodigoAgente]['talkTime']; 
                                if (isset($tiempoAgente[$sCodigoAgente]['dialnumber'])) 
                                    $estadoCola['members'][$sCodigoAgente]['dialnumber'] = $tiempoAgente[$sCodigoAgente]['dialnumber']; 
                                if (isset($tiempoAgente[$sCodigoAgente]['clientchannel'])) 
                                    $estadoCola['members'][$sCodigoAgente]['clientchannel'] = $tiempoAgente[$sCodigoAgente]['clientchannel']; 
                            }
                        }
                        break;
                     case 'callers':
                        $estadoCola['callers'][] = trim($sLinea);
                        break;
                     }	
                } 
                
                
            }
        }
        return $estadoCola;
    }

    private function _probabilidadErlangAcumulada($x, $k, $lambda)
    {
        $iSum = 0;
        $iTerm = 1;
        for ($n = 0; $n < $k; $n++) {
            if ($n > 0) $iTerm *= $lambda * $x / $n;
            $iSum += $iTerm;
        }

        return 1 - exp(-$lambda * $x) * $iSum;    	
    }
}

/*
elastix*CLI> agent show
5000         (DUEÑA LUIS) not logged in (musiconhold is 'default')
5001         (LEON JOSE) not logged in (musiconhold is 'default')
5002         (ALAVA MARIO) not logged in (musiconhold is 'default')
5003         (MOLINA MOISES) not logged in (musiconhold is 'default')
5004         (ALAVA DIEGO) not logged in (musiconhold is 'default')
5005         (GARCIA DANNY) not logged in (musiconhold is 'default')
5006         (GUANANGA FREDDY) not logged in (musiconhold is 'default')
5007         (MARTINEZ GERMAN) not logged in (musiconhold is 'default')
5008         (MEGA JOSE) not logged in (musiconhold is 'default')
5009         (REYES ALVARO) not logged in (musiconhold is 'default')
5010         (RODRIGUEZ AMALIA) not logged in (musiconhold is 'default')
5011         (RON JORGE) not logged in (musiconhold is 'default')
5012         (SALINAS MARIA BELEN) not logged in (musiconhold is 'default')
5013         (SERRANO LUIS) not logged in (musiconhold is 'default')
5014         (BELTRAN DENNISSE) not logged in (musiconhold is 'default')
5015         (BUSTAMANTE MAYIN) not logged in (musiconhold is 'default')
5016         (CARRILLO RONNY) not logged in (musiconhold is 'default')
5017         (FERNANDEZ JONATHAN) not logged in (musiconhold is 'default')
5018         (GARCIA ALEX) not logged in (musiconhold is 'default')
5019         (GUILLEN RAFAEL) not logged in (musiconhold is 'default')
5020         (MERA RUTH) not logged in (musiconhold is 'default')
5021         (SALVATIERRA DANNY) not logged in (musiconhold is 'default')
5022         (SWETT JORGE) not logged in (musiconhold is 'default')
5023         (VARGAS JOSE) not logged in (musiconhold is 'default')
24 agents configured [0 online , 24 offline]

elastix*CLI> show queue 801
801          has 0 calls (max unlimited) in 'ringall' strategy (0s holdtime), W:0, C:1544, A:35, SL:14.2% within 0s
   Members: 
      Agent/5011 (dynamic) (Unavailable) has taken no calls yet
      Agent/5013 (dynamic) (Unavailable) has taken no calls yet
      Agent/5009 (dynamic) (Unavailable) has taken 1 calls (last was 7072 secs ago)
      Agent/5012 (dynamic) (Unavailable) has taken 9 calls (last was 10571 secs ago)
      Agent/5004 (dynamic) (Unavailable) has taken no calls yet
      Agent/5005 (dynamic) (Unavailable) has taken 3 calls (last was 11143 secs ago)
      Agent/5007 (dynamic) (Unavailable) has taken no calls yet
      Agent/5006 (dynamic) (Unavailable) has taken 5 calls (last was 11254 secs ago)
      Agent/5021 (dynamic) (Unavailable) has taken 1 calls (last was 69834 secs ago)
      Agent/5023 (dynamic) (Unavailable) has taken 2 calls (last was 69892 secs ago)
      Agent/5020 (dynamic) (Unavailable) has taken 3 calls (last was 69881 secs ago)
      Agent/5022 (dynamic) (Unavailable) has taken 20 calls (last was 69415 secs ago)
      Agent/5019 (dynamic) (Unavailable) has taken 27 calls (last was 69906 secs ago)
      Agent/5015 (dynamic) (Unavailable) has taken 25 calls (last was 69752 secs ago)
      Agent/5016 (dynamic) (Unavailable) has taken 34 calls (last was 69896 secs ago)
      Agent/5018 (dynamic) (Unavailable) has taken 22 calls (last was 69818 secs ago)
      Agent/5002 (Unavailable) has taken no calls yet
      Agent/5001 (Unavailable) has taken no calls yet
      Agent/5000 (Unavailable) has taken no calls yet
   No Callers

elastix*CLI> core show channels
Channel              Location             State   Application(Data)             
SIP/212-08e36b20     (None)               Up      Bridged Call(Zap/2-1)         
Zap/2-1              s@macro-dial:10      Up      Dial(SIP/212|15|trTwW)        
2 active channels
1 active call

 elastix*CLI> core show channel SIP/212-08e36b20
 -- General --
           Name: SIP/212-08e36b20
           Type: SIP
       UniqueID: 1189016147.976
      Caller ID: 212
 Caller ID Name: (N/A)
    DNID Digits: (N/A)
          State: Up (6)
          Rings: 0
  NativeFormats: 0x4 (ulaw)
    WriteFormat: 0x4 (ulaw)
     ReadFormat: 0x4 (ulaw)
 WriteTranscode: No
  ReadTranscode: No
1st File Descriptor: 36
      Frames in: 30926
     Frames out: 30777
 Time to Hangup: 0
   Elapsed Time: 0h10m25s
  Direct Bridge: Zap/2-1
Indirect Bridge: Zap/2-1
 --   PBX   --
        Context: from-internal
      Extension: 
       Priority: 1
     Call Group: 2
   Pickup Group: 2
    Application: Bridged Call
           Data: Zap/2-1
    Blocking in: ast_waitfor_nandfds
      Variables:
BRIDGEPEER=Zap/2-1
DIALEDPEERNUMBER=212
SIPCALLID=5f326b3978e782ee706168b4722a911b@192.168.1.160
KEEPCID=TRUE
TTL=64
IVR_CONTEXT=ivr-2
IVR_CONTEXT_ivr-2=
DIR-CONTEXT=default
FROM_DID=s

  CDR Variables:
level 1: clid=212
level 1: src=212
level 1: dst=s
level 1: dcontext=from-internal
level 1: channel=SIP/212-08e36b20
level 1: start=2007-09-05 13:15:47
level 1: answer=2007-09-05 13:15:56
level 1: end=2007-09-05 13:15:56
level 1: duration=0
level 1: billsec=0
level 1: disposition=ANSWERED
level 1: amaflags=DOCUMENTATION
level 1: uniqueid=1189016147.976

 
 */
?>
