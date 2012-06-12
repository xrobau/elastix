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
  $Id: MultiplexServer.class.php,v 1.48 2009/03/26 13:46:58 alex Exp $ */

require_once ('MultiplexServer.class.php');
require_once 'XMLDialerConn.class.php';
/*
 * Esta clase (DialerServer) mantiene un listado de objetos de tipo DialerConn
 * que implementan alguna interacción de red. Por ahora se tienen dos tipos de
 * objetos: AMIClientConn que implementa el cliente de protocolo Asterisk AMI, y
 * XMLDialerConn que implementa el servidor de requerimientos y eventos. 
 * 
 * Cada objeto DialerConn se asume que controla un protocolo orientado a 
 * paquetes. Para que funcione correctamente el programa, cada objeto debe ser
 * capaz de acumular muchos paquetes de su protocolo, sin procesarlos, hasta que
 * se le mande explícitamente que procese un solo paquete a la vez. De esta 
 * forma se puede hacer que todas las instancias de DialerConn tengan 
 * oportunidad de procesar sus paquetes, hasta que ya no tengan datos que 
 * procesar. Para esto, el código llama al método parsearPaquetes de la instancia
 * que tenga datos. Más tarde, se llama al método procesarPaquete para que se
 * procese un paquete del protocolo. El método hayPaquetes devuelve VERDADERO
 * si hay paquetes pendientes de procesar.
 */

class DialerServer extends MultiplexServer
{
    private $_listaConn = array();
    private $_astConn;
    private $_dbConn;
    private $_dialProc;

    /**
     * Procedimiento para agregar un objeto instancia de DialerConn, que abre
     * un socket arbitrario y desea estar asociado con tal socket.
     * 
     * @param   object      $oNuevaConn Objeto que hereda de DialerConn
     * @param   resource    $hSock      Conexión a un socket TCP o UNIX
     * 
     * @return void
     */
    function agregarDialerConn($oNuevaConn, $hSock)
    {
        if (!is_a($oNuevaConn, 'DialerConn')) {
        	die('DialerServer::agregarDialerConn - $oNuevaConn no es subclase de DialerConn');
        }

    	$sKey = $this->agregarConexion($hSock);
        $oNuevaConn->dialSrv = $this;
        $oNuevaConn->sKey = $sKey;
        $this->_listaConn[$sKey] = $oNuevaConn;
        $this->_listaConn[$sKey]->procesarInicial();
    }

	/* Para una nueva conexión, siempre se instancia un XMLDialerConn */
    function procesarInicial($sKey)
	{
        $oNuevaConn = new XMLDialerConn($this->_oLog);
        $oNuevaConn->dialSrv = $this;
        $oNuevaConn->sKey = $sKey;
        $this->_listaConn[$sKey] = $oNuevaConn;
        $this->_listaConn[$sKey]->setAstConn($this->_astConn);
        $this->_listaConn[$sKey]->setDbConn($this->_dbConn);
        $this->_listaConn[$sKey]->setDialerProcess($this->_dialProc);
        $this->_listaConn[$sKey]->procesarInicial();
	}
	
    /* Enviar los datos recibidos para que sean procesados por la conexión */
	function procesarNuevosDatos($sKey)
	{
        if (isset($this->_listaConn[$sKey])) {
            $sDatos = $this->obtenerDatosLeidos($sKey);
            $iLongProcesado = $this->_listaConn[$sKey]->parsearPaquetes($sDatos);
            $this->descartarDatosLeidos($sKey, $iLongProcesado);
        }
	}
    
    function procesarCierre($sKey)
    {
    	if (isset($this->_listaConn[$sKey])) {
    		$this->_listaConn[$sKey]->procesarCierre();
            unset($this->_listaConn[$sKey]);
    	}
    }
    
    function procesarPaquetes()
    {
    	$bHayProcesados = FALSE;
        foreach ($this->_listaConn as &$oConn) {
        	if ($oConn->hayPaquetes()) {
        		$bHayProcesados = TRUE;
                $oConn->procesarPaquete();
        	}
        }
        return $bHayProcesados;
    }
    
    function setAstConn($astConn)
    {
        $this->_astConn = $astConn;
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'setAstConn')) {
                $oConn->setAstConn($this->_astConn);
            }
        }
    }
    
    function setDbConn($dbConn)
    {
        $this->_dbConn = $dbConn;
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'setDbConn')) {
                $oConn->setDbConn($this->_dbConn);
            }
        }
    }

    function setDialerProcess($dialProc)
    {
    	$this->_dialProc = $dialProc;
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'setDialerProcess')) {
                $oConn->setDialerProcess($this->_dialProc);
            }
        }
    }
    
    function finalizarServidor()
    {
    	foreach ($this->_listaConn as &$oConn) {
            $oConn->finalizarConexion();
        }
        $this->procesarActividad();
    }
    
    function notificarEvento_AgentLogin($sAgente, $listaColas, $bExitoLogin)
    {
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'notificarEvento_AgentLogin')) {
                $oConn->notificarEvento_AgentLogin($sAgente, $listaColas, $bExitoLogin);
            }
        }
    }

    function notificarEvento_AgentLogoff($sAgente, $listaColas)
    {
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'notificarEvento_AgentLogoff')) {
                $oConn->notificarEvento_AgentLogoff($sAgente, $listaColas);
            }
        }
    }

    function notificarEvento_AgentLinked($sAgente, $sRemChannel, $infoLlamada)
    {
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'notificarEvento_AgentLinked')) {
                $oConn->notificarEvento_AgentLinked($sAgente, $sRemChannel, $infoLlamada);
            }
        }
    }

    function notificarEvento_AgentUnlinked($sAgente, $infoLlamada)
    {
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'notificarEvento_AgentUnlinked')) {
                $oConn->notificarEvento_AgentUnlinked($sAgente, $infoLlamada);
            }
        }
    }

    function notificarEvento_PauseStart($sAgente, $infoPausa)
    {
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'notificarEvento_PauseStart')) {
                $oConn->notificarEvento_PauseStart($sAgente, $infoPausa);
            }
        }
    }

    function notificarEvento_PauseEnd($sAgente, $infoPausa)
    {
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'notificarEvento_PauseEnd')) {
                $oConn->notificarEvento_PauseEnd($sAgente, $infoPausa);
            }
        }
    }
}
?>