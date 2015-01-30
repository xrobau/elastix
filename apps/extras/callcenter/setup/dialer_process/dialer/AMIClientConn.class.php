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
  $Id: DialerConn.class.php,v 1.48 2009/03/26 13:46:58 alex Exp $ */

if(!class_exists('AGI')) {
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpagi.php');
}

define('AMI_PORT', 5038);

class AMIClientConn extends MultiplexConn
{
    private $oLogger;
    private $server;
    private $port;
    private $_listaEventos = array();   // Eventos pendientes por procesar
    private $_response = NULL;          // Respuesta recibida del último comando
    
   /**
    * Event Handlers
    *
    * @access private
    * @var array
    */
    private $event_handlers;

    function AMIClientConn($dialSrv, $oMainLog)
    {
        $this->oLogger = $oMainLog;
        $this->multiplexSrv = $dialSrv;
    }
    
    // Datos a mandar a escribir apenas se inicia la conexión
    function procesarInicial() {}

    // Separar flujo de datos en paquetes, devuelve número de bytes de paquetes aceptados
    function parsearPaquetes($sDatos)
    {
        $iLongInicial = strlen($sDatos);

        // Encontrar los paquetes y determinar longitud de búfer procesado
        $listaPaquetes = $this->encontrarPaquetes($sDatos);
        $iLongFinal = strlen($sDatos);
        
        /* Paquetes Event se van a la lista de eventos. El paquete Response se
         * guarda individualmente. */ 
        foreach ($listaPaquetes as $paquete) {
        	if (isset($paquete['Event'])) {
                $e = strtolower($paquete['Event']);
                if (isset($this->event_handlers[$e]) || isset($this->event_handlers['*'])) {
                    $paquete['local_timestamp_received'] = microtime(TRUE);
                    $this->_listaEventos[] = $paquete;
                }
            } elseif (isset($paquete['Response'])) {
            	if (!is_null($this->_response)) {
            		$this->oLogger->output("ERR: segundo Response sobreescribe primer Response no procesado: ".
                        print_r($this->_response, 1));
            	}
                $this->_response = $paquete;
            } else {
            	$this->oLogger->output("ERR: el siguiente paquete no se reconoce como Event o Response: ".
                    print_r($paquete, 1));
            }
        }
        return $iLongInicial - $iLongFinal;
    }

    private function dividirLineas(&$sDatos)
    {
        /* Dividir el búfer por salto de línea. Si el último elemento es vacío, el
           búfer terminaba en \n. Luego se restaura el \n en cada línea para que se
           cumpla que implode("", $lineas) == $sDatos */
        $lineas = explode("\n", $sDatos);
        if (count($lineas) > 0) {
            for ($i = 0; $i < count($lineas) - 1; $i++) $lineas[$i] .= "\n";
            if($lineas[count($lineas) - 1] == '')
                array_pop($lineas);
        }
        assert('implode("", $lineas) == $sDatos');
        return $lineas;
    }

    /**
     * Procedimiento que intenta descomponer el búfer de lectura indicado por $sDatos
     * en una secuencia de paquetes de AMI (Asterisk Manager Interface). La lista de
     * paquetes obtenida se devuelve como una lista. Además el búfer de lectura se
     * modifica para eliminar los datos que fueron ya procesados como parte de los
     * paquetes. Esta función sólo devuelve paquetes completos, y deja cualquier
     * fracción de paquetes incompletos en el búfer.
     *
     * @param   string  $sDatos     Cadena de datos a procesar
     *
     * @return  array   Lista de paquetes que fueron extraídos del texto.
     */
    private function encontrarPaquetes(&$sDatos)
    {
        $lineas = $this->dividirLineas($sDatos);
    
        $listaPaquetes = array();
        $paquete = array();
        $bIncompleto = FALSE;
        $iLongPaquete = 0;
        while (!$bIncompleto && count($lineas) > 0) {
            $s = array_shift($lineas);
            $iLongPaquete += strlen($s);
            if (substr($s, strlen($s) - 1, 1) != "\n") {
                /* A la última línea le falta el salto de línea - búfer termina en 
                   medio de la línea */            
                $bIncompleto = TRUE;
            } else {
                $s = trim($s);  // Remover salto de línea al final
                $a = strpos($s, ':');
                if ($a) {
                    $sClave = substr($s, 0, $a);
                    $sValor = substr($s, $a + 2);
                    // Si hay una respuesta Follows, es la primera línea
                    if (!count($paquete)) {
                        if ($sValor == 'Follows') {
                            $paquete['data'] = '';
                            while (!$bIncompleto && substr($s, 0, 6) != '--END ') {
                                if (count($lineas) <= 0) {
                                    $bIncompleto = TRUE;
                                } else {
                                    $s = array_shift($lineas);
                                    $iLongPaquete += strlen($s);
                                    if (substr($s, 0, 6) != '--END ') {
                                        $paquete['data'] .= $s;
                                    }
                                }
                            }
                        }
                    }
                    $paquete[$sClave] = $sValor;
                } elseif ($s == "") {
                    // Se ha encontrado el final de un paquete
                    if (count($paquete)) $listaPaquetes[] = $paquete;
                    $paquete = array();
                    $sDatos = substr($sDatos, $iLongPaquete);
                    $iLongPaquete = 0;
                }
            }
        }
    
        return $listaPaquetes;
    }
    
    
    // Procesar cierre de la conexión
    function procesarCierre()
    {
        $this->oLogger->output("INFO: detectado cierre de conexión Asterisk.");
        $this->sKey = NULL;
    }

    // Preguntar si hay paquetes pendientes de procesar
    function hayPaquetes() { return (count($this->_listaEventos) > 0); }

    // Procesar un solo paquete de la cola de paquetes
    function procesarPaquete()
    {
    	$paquete = array_shift($this->_listaEventos);
        $this->process_event($paquete);
    }
    
    // Implementación de send_request para compatibilidad con phpagi-asmanager
    private function send_request($action, $parameters=array())
    {
        if (!is_null($this->sKey)) {
            $req = "Action: $action\r\n";
            foreach($parameters as $var => $val) $req .= "$var: $val\r\n";
            $req .= "\r\n";
            $this->multiplexSrv->encolarDatosEscribir($this->sKey, $req);
            return $this->wait_response();
        } else return NULL;
    }

    // Implementación de wait_response para compatibilidad con phpagi-asmanager
    private function wait_response()
    {
        while (!is_null($this->sKey) && is_null($this->_response)) {
            if (!$this->multiplexSrv->procesarActividad()) {
                usleep(100000);
            }
        }
        if (!is_null($this->_response)) {
        	$r = $this->_response;
            $this->_response = NULL;
            return $r;
        }
        if (is_null($this->sKey)) {
            $this->oLogger->output("ERR: conexión AMI cerrada mientras se esperaba respuesta.");
        	return NULL;
        }
    }
    
    function connect($server, $username, $secret)
    {
    	// Determinar servidor y puerto a usar
        $iPuerto = AMI_PORT;
        if(strpos($server, ':') !== false) {
            $c = explode(':', $server);
            $server = $c[0];
            $iPuerto = $c[1];
        }
        $this->server = $server;
        $this->port = $iPuerto;
        
        // Iniciar la conexión
        $errno = $errstr = NULL;
        $sUrlConexion = "tcp://$server:$iPuerto";
        $hConn = @stream_socket_client($sUrlConexion, $errno, $errstr);
        if (!$hConn) {
            $this->oLogger->output("ERR: no se puede conectar a puerto AMI en $sUrlConexion: ($errno) $errstr");
        	return FALSE;
        }
        
        // Leer la cabecera de Asterisk
        $str = fgets($hConn);
        if ($str == false) {
            $this->oLogger->output("ERR: No se ha recibido la cabecera de Asterisk Manager");
            return false;
        }
        //$this->oLogger->output("DEBUG: cabecera recibida es: $str");
        
        // Registrar el socket con el objeto de conexiones
        $this->multiplexSrv->agregarNuevaConexion($this, $hConn);

        // Iniciar login con Asterisk
        $res = $this->send_request('login', array('Username'=>$username, 'Secret'=>$secret));
        if($res['Response'] != 'Success') {
            $this->oLogger->output("ERR: Fallo en login de AMI.");
            $this->disconnect();
            return false;
        }
        return true;
    }
    
    function disconnect()
    {
        $this->logoff();
        $this->multiplexSrv->marcarCerrado($this->sKey);
    }

    function finalizarConexion()
    {
    	if (!is_null($this->sKey)) {
    		$this->disconnect();
    	}
    }

   // *********************************************************************************************************
   // **                       COMMANDS                                                                      **
   // *********************************************************************************************************

   /**
    * Set Absolute Timeout
    *
    * Hangup a channel after a certain time.
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+AbsoluteTimeout
    * @param string $channel Channel name to hangup
    * @param integer $timeout Maximum duration of the call (sec)
    */
    function AbsoluteTimeout($channel, $timeout)
    {
      return $this->send_request('AbsoluteTimeout', array('Channel'=>$channel, 'Timeout'=>$timeout));
    }

    /**
     * Initiate an attended transfer
     * 
     * @param string $channel The transferer channel's name
     * @param string $exten The extension to transfer to
     * @param string $context The context to transfer to
     * @param string $priority The priority to transfer to
     */
    function Atxfer($channel, $exten, $context, $priority)
    {
      return $this->send_request('Atxfer', array('Channel'=>$channel, 'Exten'=>$exten, 'Priority'=>$priority, 'Context'=>$context));
    }

   /**
    * Change monitoring filename of a channel
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ChangeMonitor
    * @param string $channel the channel to record.
    * @param string $file the new name of the file created in the monitor spool directory.
    */
    function ChangeMonitor($channel, $file)
    {
      return $this->send_request('ChangeMonitor', array('Channel'=>$channel, 'File'=>$file));
    }

   /**
    * Execute Command
    *
    * @example examples/sip_show_peer.php Get information about a sip peer
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Command
    * @link http://www.voip-info.org/wiki-Asterisk+CLI
    * @param string $command
    * @param string $actionid message matching variable
    */
    function Command($command, $actionid=NULL)
    {
      $parameters = array('Command'=>$command);
      if($actionid) $parameters['ActionID'] = $actionid;
      return $this->send_request('Command', $parameters);
    }

   /**
    * Enable/Disable sending of events to this manager
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Events
    * @param string $eventmask is either 'on', 'off', or 'system,call,log'
    */
    function Events($eventmask)
    {
      return $this->send_request('Events', array('EventMask'=>$eventmask));
    }

   /**
    * Check Extension Status
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ExtensionState
    * @param string $exten Extension to check state on
    * @param string $context Context for extension
    * @param string $actionid message matching variable
    */
    function ExtensionState($exten, $context, $actionid=NULL)
    {
      $parameters = array('Exten'=>$exten, 'Context'=>$context);
      if($actionid) $parameters['ActionID'] = $actionid;
      return $this->send_request('ExtensionState', $parameters);
    }

   /**
    * Gets a Channel Variable
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+GetVar
    * @link http://www.voip-info.org/wiki-Asterisk+variables
    * @param string $channel Channel to read variable from
    * @param string $variable
    * @param string $actionid message matching variable
    */
    function GetVar($channel, $variable, $actionid=NULL)
    {
      $parameters = array('Channel'=>$channel, 'Variable'=>$variable);
      if($actionid) $parameters['ActionID'] = $actionid;
      return $this->send_request('GetVar', $parameters);
    }

   /**
    * Hangup Channel
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Hangup
    * @param string $channel The channel name to be hungup
    */
    function Hangup($channel)
    {
      return $this->send_request('Hangup', array('Channel'=>$channel));
    }

   /**
    * List IAX Peers
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+IAXpeers
    */
    function IAXPeers()
    {
      return $this->send_request('IAXPeers');
    }

   /**
    * List available manager commands
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ListCommands
    * @param string $actionid message matching variable
    */
    function ListCommands($actionid=NULL)
    {
      if($actionid)
        return $this->send_request('ListCommands', array('ActionID'=>$actionid));
      else
        return $this->send_request('ListCommands');
    }

   /**
    * Logoff Manager
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Logoff
    */
    function Logoff()
    {
      return $this->send_request('Logoff');
    }

   /**
    * Check Mailbox Message Count
    *
    * Returns number of new and old messages.
    *   Message: Mailbox Message Count
    *   Mailbox: <mailboxid>
    *   NewMessages: <count>
    *   OldMessages: <count>
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+MailboxCount
    * @param string $mailbox Full mailbox ID <mailbox>@<vm-context>
    * @param string $actionid message matching variable
    */
    function MailboxCount($mailbox, $actionid=NULL)
    {
      $parameters = array('Mailbox'=>$mailbox);
      if($actionid) $parameters['ActionID'] = $actionid;
      return $this->send_request('MailboxCount', $parameters);
    }

   /**
    * Check Mailbox
    *
    * Returns number of messages.
    *   Message: Mailbox Status
    *   Mailbox: <mailboxid>
    *   Waiting: <count>
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+MailboxStatus
    * @param string $mailbox Full mailbox ID <mailbox>@<vm-context>
    * @param string $actionid message matching variable
    */
    function MailboxStatus($mailbox, $actionid=NULL)
    {   
      $parameters = array('Mailbox'=>$mailbox);
      if($actionid) $parameters['ActionID'] = $actionid;
      return $this->send_request('MailboxStatus', $parameters);
    }

   /**
    * Monitor a channel
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Monitor
    * @param string $channel
    * @param string $file
    * @param string $format
    * @param boolean $mix
    */
    function Monitor($channel, $file=NULL, $format=NULL, $mix=NULL)
    {
      $parameters = array('Channel'=>$channel);
      if($file) $parameters['File'] = $file;
      if($format) $parameters['Format'] = $format;
      if(!is_null($file)) $parameters['Mix'] = ($mix) ? 'true' : 'false';
      return $this->send_request('Monitor', $parameters);
    }

   /**
    * Originate Call
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Originate
    * @param string $channel Channel name to call
    * @param string $exten Extension to use (requires 'Context' and 'Priority')
    * @param string $context Context to use (requires 'Exten' and 'Priority')
    * @param string $priority Priority to use (requires 'Exten' and 'Context')
    * @param string $application Application to use
    * @param string $data Data to use (requires 'Application')
    * @param integer $timeout How long to wait for call to be answered (in ms)
    * @param string $callerid Caller ID to be set on the outgoing channel
    * @param string $variable Channel variable to set (VAR1=value1|VAR2=value2)
    * @param string $account Account code
    * @param boolean $async true fast origination
    * @param string $actionid message matching variable
    */
    function Originate($channel,
                       $exten=NULL, $context=NULL, $priority=NULL,
                       $application=NULL, $data=NULL,
                       $timeout=NULL, $callerid=NULL, $variable=NULL, $account=NULL, $async=NULL, $actionid=NULL)
    {
      $parameters = array('Channel'=>$channel);

      if($exten) $parameters['Exten'] = $exten;
      if($context) $parameters['Context'] = $context;
      if($priority) $parameters['Priority'] = $priority;

      if($application) $parameters['Application'] = $application;
      if($data) $parameters['Data'] = $data;

      if($timeout) $parameters['Timeout'] = $timeout;
      if($callerid) $parameters['CallerID'] = $callerid;
      if($variable) $parameters['Variable'] = $variable;
      if($account) $parameters['Account'] = $account;
      if(!is_null($async)) $parameters['Async'] = ($async) ? 'true' : 'false';
      if($actionid) $parameters['ActionID'] = $actionid;

      return $this->send_request('Originate', $parameters);
    }   

   /**
    * List parked calls
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ParkedCalls
    * @param string $actionid message matching variable
    */
    function ParkedCalls($actionid=NULL)
    {
      if($actionid)
        return $this->send_request('ParkedCalls', array('ActionID'=>$actionid));
      else
        return $this->send_request('ParkedCalls');
    }

   /**
    * Ping
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Ping
    */
    function Ping()
    {
      return $this->send_request('Ping');
    }

   /**
    * Queue Add
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+QueueAdd
    * @param string $queue
    * @param string $interface
    * @param integer $penalty
    */
    function QueueAdd($queue, $interface, $penalty=0)
    {
      $parameters = array('Queue'=>$queue, 'Interface'=>$interface, 'Paused'=>'false');
      if($penalty) $parameters['Penalty'] = $penalty;
      return $this->send_request('QueueAdd', $parameters);
    }

   /**
    * Queue Remove
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+QueueRemove
    * @param string $queue
    * @param string $interface
    */
    function QueueRemove($queue, $interface)
    {
      return $this->send_request('QueueRemove', array('Queue'=>$queue, 'Interface'=>$interface));
    }

   /**
    * Queues
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Queues
    */
    function Queues()
    {
      return $this->send_request('Queues');
    }

   /**
    * Queue Pause
    *
    * @link http://www.voipinfo.org/wiki/index.php?page=Asterisk+Manager+API+Action+QueuePause
    * Se asume que el formato de la variable member es por ej: Agent/2000
    */
    function QueuePause($queue=NULL, $member=NULL, $paused=true)
    {
      if(!empty($member) and !empty($queue))
        return $this->send_request('QueuePause', array('Queue'=>$queue, 'Interface'=>$member, 'Paused' => $paused));
      elseif(!empty($member) and empty($queue))
        return $this->send_request('QueuePause', array('Interface'=>$member, 'Paused' => $paused));
      else
        return false;
    }

   /**
    * Queue Status
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+QueueStatus
    * @param string $actionid message matching variable
    */
    function QueueStatus($actionid=NULL)
    {
      if($actionid)
        return $this->send_request('QueueStatus', array('ActionID'=>$actionid));
      else
        return $this->send_request('QueueStatus');
    }

   /**
    * Redirect
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Redirect
    * @param string $channel
    * @param string $extrachannel
    * @param string $exten
    * @param string $context
    * @param string $priority
    */
    function Redirect($channel, $extrachannel, $exten, $context, $priority)
    {
      return $this->send_request('Redirect', array('Channel'=>$channel, 'ExtraChannel'=>$extrachannel, 'Exten'=>$exten,
                                                   'Context'=>$context, 'Priority'=>$priority));
    }

   /**
    * Set the CDR UserField
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+SetCDRUserField
    * @param string $userfield
    * @param string $channel
    * @param string $append
    */
    function SetCDRUserField($userfield, $channel, $append=NULL)
    {
      $parameters = array('UserField'=>$userfield, 'Channel'=>$channel);
      if($append) $parameters['Append'] = $append;
      return $this->send_request('SetCDRUserField', $parameters);
    }

   /**
    * Set Channel Variable
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+SetVar
    * @param string $channel Channel to set variable for
    * @param string $variable name
    * @param string $value
    */
    function SetVar($channel, $variable, $value)
    {
      return $this->send_request('SetVar', array('Channel'=>$channel, 'Variable'=>$variable, 'Value'=>$value));
    }

   /**
    * Channel Status
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+Status
    * @param string $channel
    * @param string $actionid message matching variable
    */
    function Status($channel, $actionid=NULL)
    {
      $parameters = array('Channel'=>$channel);
      if($actionid) $parameters['ActionID'] = $actionid;
      return $this->send_request('Status', $parameters);
    }

   /**
    * Stop monitoring a channel
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+StopMonitor
    * @param string $channel
    */
    function StopMontor($channel)
    {
      return $this->send_request('StopMonitor', array('Channel'=>$channel));
    }

   /**
    * Dial over Zap channel while offhook
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ZapDialOffhook
    * @param string $zapchannel
    * @param string $number
    */
    function ZapDialOffhook($zapchannel, $number)
    {
      return $this->send_request('ZapDialOffhook', array('ZapChannel'=>$zapchannel, 'Number'=>$number));
    }

   /**
    * Toggle Zap channel Do Not Disturb status OFF
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ZapDNDoff
    * @param string $zapchannel
    */
    function ZapDNDoff($zapchannel)
    {
      return $this->send_request('ZapDNDoff', array('ZapChannel'=>$zapchannel));
    }

   /**
    * Toggle Zap channel Do Not Disturb status ON
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ZapDNDon
    * @param string $zapchannel
    */
    function ZapDNDon($zapchannel)
    {
      return $this->send_request('ZapDNDon', array('ZapChannel'=>$zapchannel));
    }

   /**
    * Hangup Zap Channel
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ZapHangup
    * @param string $zapchannel
    */
    function ZapHangup($zapchannel)
    {
      return $this->send_request('ZapHangup', array('ZapChannel'=>$zapchannel));
    }

   /**
    * Transfer Zap Channel
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ZapTransfer
    * @param string $zapchannel
    */
    function ZapTransfer($zapchannel)
    {
      return $this->send_request('ZapTransfer', array('ZapChannel'=>$zapchannel));
    }

   /**
    * Zap Show Channels
    *
    * @link http://www.voip-info.org/wiki-Asterisk+Manager+API+Action+ZapShowChannels
    * @param string $actionid message matching variable
    */
    function ZapShowChannels($actionid=NULL)
    {
      if($actionid)
        return $this->send_request('ZapShowChannels', array('ActionID'=>$actionid));
      else
        return $this->send_request('ZapShowChannels');
    }

   /**
    * Agent Logoff
    *
    * @link http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+AgentLogoff
    * @param Agent: Agent ID of the agent to login 
    */
    function Agentlogoff($agent)
    {
      return $this->send_request('Agentlogoff', array('Agent'=>$agent));
    }
    
    function Hold()
    {
      return $this->send_request('Hold',array());
    }

    function AgentCallbackLogin($agent,$exten,$context)
    {
        $this->send_request('AgentCallbackLogin', array('Agent'=>$agent, 'Exten'=>$exten, 'Context'=>$context, 'AckCall'=>'true'));
    }

    function AgentLogin($agent,$canal)
    {
        $this->send_request('AgentLogin', array('Agent'=>$agent,'Channel'=>$canal));
    }


   // *********************************************************************************************************
   // **                       MISC                                                                          **
   // *********************************************************************************************************


   /**
    * Add event handler
    *
    * Known Events include ( http://www.voip-info.org/wiki-asterisk+manager+events )
    *   Link - Fired when two voice channels are linked together and voice data exchange commences.
    *   Unlink - Fired when a link between two voice channels is discontinued, for example, just before call completion.
    *   Newexten -
    *   Hangup -
    *   Newchannel -
    *   Newstate -
    *   Reload - Fired when the "RELOAD" console command is executed.
    *   Shutdown -
    *   ExtensionStatus -
    *   Rename -
    *   Newcallerid -
    *   Alarm -
    *   AlarmClear -
    *   Agentcallbacklogoff -
    *   Agentcallbacklogin -
    *   Agentlogoff -
    *   MeetmeJoin -
    *   MessageWaiting -
    *   join -
    *   leave -
    *   AgentCalled -
    *   ParkedCall - Fired after ParkedCalls
    *   Cdr -
    *   ParkedCallsComplete -
    *   QueueParams -
    *   QueueMember -
    *   QueueStatusEnd -
    *   Status -
    *   StatusComplete -
    *   ZapShowChannels - Fired after ZapShowChannels
    *   ZapShowChannelsComplete -
    *
    * @param string $event type or * for default handler
    * @param string $callback function
    * @return boolean sucess
    */
    function add_event_handler($event, $callback)
    {
      $event = strtolower($event);
      if(isset($this->event_handlers[$event]))
      {
        $this->oLogger->output("WARN: $event handler is already defined, not over-writing.");
        return false;
      }
      $this->event_handlers[$event] = $callback;
      return true;
    }

    function remove_event_handler($event)
    {
    	if (isset($this->event_handlers[$event])) {
    		unset($this->event_handlers[$event]);
    	}
    }

   /**
    * Process event
    *
    * @access private
    * @param array $parameters
    * @return mixed result of event handler or false if no handler was found
    */
    function process_event($parameters)
    {
      $ret = false;
      $e = strtolower($parameters['Event']);
      //$this->log("Got event.. $e");       

      $handler = '';
      if(isset($this->event_handlers[$e])) $handler = $this->event_handlers[$e];
      elseif(isset($this->event_handlers['*'])) $handler = $this->event_handlers['*'];

      if ((is_array($handler) && count($handler) >= 2 && is_object($handler[0]) && 
        method_exists($handler[0], $handler[1])) || function_exists($handler))
      {
        //$this->log("Execute handler $handler");
        $ret = call_user_func($handler, $e, $parameters, $this->server, $this->port);
      }
      /*
      else
        $this->log("No event handler for event '$e'");
      */
      return $ret;
    }

    /** Show all entries in the asterisk database
     * @return Array associative array of key=>value
     */
    function database_show($family = NULL, $keytree = NULL) {
        $c = 'database show';
        if (!is_null($family)) $c .= ' '.$family;
        if (!is_null($keytree)) $c .= ' '.$keytree;
        $r = $this->command($c);
        
        $data = explode("\n",$r["data"]);
        $db = array();
        
        foreach ($data as $line) {
            $temp = explode(":",$line);
            if (count($temp) >= 2) $db[ trim($temp[0]) ] = trim($temp[1]);
        }
        return $db;
    }

    function database_showkey($key) 
    {  
        $r = $this->command("database showkey $key");     
        $data = explode("\n",$r["data"]);
        $db = array();
        
        foreach ($data as $line) {
            $temp = explode(":",$line);
            if (count($temp) >= 2) $db[ trim($temp[0]) ] = trim($temp[1]);        
        }
        return $db;
    }
    
    /** Add an entry to the asterisk database
     * @param string $family    The family name to use
     * @param string $key       The key name to use
     * @param mixed $value      The value to add
     * @return bool True if successful
     */
    function database_put($family, $key, $value) {
        $r = $this->command("database put ".str_replace(" ","/",$family)." ".str_replace(" ","/",$key)." ".$value);
        return (bool)strstr($r["data"], "success");
    }
    
    /** Get an entry from the asterisk database
     * @param string $family    The family name to use
     * @param string $key       The key name to use
     * @return mixed Value of the key, or false if error
     */
    function database_get($family, $key) {
        $r = $this->command("database get ".str_replace(" ","/",$family)." ".str_replace(" ","/",$key));
        $lineas = explode("\r\n", $r["data"]);
        while (count($lineas) > 0) {
            if (substr($lineas[0],0,6) == "Value:") {
                return trim(substr(join("\r\n", $lineas),6));
            }
            array_shift($lineas);
        }
        return false;
    }
    
    /** Delete an entry from the asterisk database
     * @param string $family    The family name to use
     * @param string $key       The key name to use
     * @return bool True if successful
     */
    function database_del($family, $key) {
        $r = $this->command("database del ".str_replace(" ","/",$family)." ".str_replace(" ","/",$key));
        return (bool)strstr($r["data"], "removed");
    }

    /** 
     * Fetch core settings from the running Asterisk server.
     * Only available in Asterisk 1.6.0 and later.
     *
     * @return array Response with requested data, if successful
     */
    function CoreSettings()
    {
        return $this->send_request('CoreSettings');
    }
    
    /**
     * Fetch core status from the running Asterisk server.
     * 
     * @return array Response with requested data, if successful
     */
    function CoreStatus()
    {
    	return $this->send_request('CoreStatus');
    }
}
?>