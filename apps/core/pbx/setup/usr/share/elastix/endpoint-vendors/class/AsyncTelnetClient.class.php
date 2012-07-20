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

require_once 'MultiplexConn.class.php';

define('TELNET_SUBNEG_END', "\xf0");
define('TELNET_NOP',        "\xf1");
define('TELNET_DATA_MARK',  "\xf2");
define('TELNET_BREAK',      "\xf3");
define('TELNET_INTERRUPT',  "\xf4");
define('TELNET_ABORT',      "\xf5");
define('TELNET_AYT',        "\xf6");
define('TELNET_ERASE_CHAR', "\xf7");
define('TELNET_ERASE_LINE', "\xf8");
define('TELNET_GO_AHEAD',   "\xf9");
define('TELNET_SUBNEG',     "\xfa");
define('TELNET_WILL',       "\xfb");
define('TELNET_WONT',       "\xfc");
define('TELNET_DO',         "\xfd");
define('TELNET_DONT',       "\xfe");
define('TELNET_IAC',        "\xff");

class AsyncTelnetClient extends MultiplexConn
{
    private $_endpoint;
    private $_bPendingOutput = FALSE;
    private $_receivedLines = array('');
    private $_receivedCommands = array();

    function AsyncTelnetClient($multiplex, $endpoint)
    {
        $this->multiplexSrv = $multiplex;
        $this->_endpoint = $endpoint;
    }

    // Datos a mandar a escribir apenas se inicia la conexión
    function procesarInicial() {
        // Enable echo.
        $sCommand = TELNET_IAC.TELNET_DO."\x01";
        $this->multiplexSrv->encolarDatosEscribir($this->sKey, $sCommand);    	
    }

    function finalizarConexion()
    {
        if (!is_null($this->sKey)) {
            $this->multiplexSrv->marcarCerrado($this->sKey);
        }
    }

    // Procesar cierre de la conexión
    function procesarCierre()
    {
        $this->sKey = NULL;
        $this->_endpoint->updateLocalConfig($this, TRUE);
        $this->_endpoint = NULL;
    }

    function hayPaquetes()
    {
        return $this->_bPendingOutput;
    }
    
    function procesarPaquete()
    {
        $this->_bPendingOutput = FALSE;
        $this->_endpoint->updateLocalConfig($this, FALSE);
    }
    
    function connect($server, $port = 23)
    {
        $errno = $errstr = NULL;
        $hConn = @stream_socket_client("tcp://$server:$port", $errno, $errstr, 
            2, STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
        if (!$hConn) {
            return array($errno, $errstr);
        }
        $this->multiplexSrv->agregarNuevaConexion($this, $hConn);
        return TRUE;
    }

    // Separar flujo de datos en paquetes, devuelve número de bytes de paquetes aceptados
    function parsearPaquetes($sDatos)
    {
        $iLongitud = strlen($sDatos);
        $iLongProc = 0;
        $this->_bPendingOutput = TRUE;
        
        while ($iLongProc < $iLongitud) {
        	$iCmdPos = strpos($sDatos, TELNET_IAC);
            if ($iCmdPos === FALSE) $iCmdPos = strlen($sDatos);
            $sText = substr($sDatos, 0, $iCmdPos);
            $sDatos = substr($sDatos, $iCmdPos);
            
            // Add output lines to the end of output
            if ($sText != '') {
                $l = preg_split("/(\r\n|\r|\n)/", $sText);
                $s = array_shift($l);
                $this->_receivedLines[count($this->_receivedLines) - 1] .= $s;
                $this->_receivedLines = array_merge($this->_receivedLines, $l);
                $iLongProc += strlen($sText);
            }
            
            // Keep track of telnet commands, but separate them from text
            if ($sDatos != '') {
            	if (strlen($sDatos) < 2) break;    // Telnet command missing
                switch ($sDatos[1]) {
                case TELNET_SUBNEG:
                    // Look for end of subnegotiation
                    $iPosSE = strpos($sDatos, TELNET_IAC.TELNET_SUBNEG_END);
                    if ($iPosSE === FALSE) break 2;
                    $this->_receivedCommands[] = substr($sDatos, 0, $iPosSE + 2);
                    $sDatos = substr($sDatos, $iPosSE + 2);
                    $iLongProc += $iPosSE + 2;
                    break;
                case TELNET_WILL:
                case TELNET_WONT:
                case TELNET_DO:
                case TELNET_DONT:
                    if (strlen($sDatos) < 3) break 2;    // Telnet command option missing
                    $this->_receivedCommands[] = substr($sDatos, 0, 3);
                    $sDatos = substr($sDatos, 3);
                    $iLongProc += 3;
                    break;
                default:
                    $this->_receivedCommands[] = substr($sDatos, 0, 2);
                    $sDatos = substr($sDatos, 2);
                    $iLongProc += 2;
                    break;
                }
            }
        }
        return $iLongProc;
    }
    
    function fetchOutput()
    {
    	return $this->_receivedLines;
    }
    
    function fetchCommands()
    {
    	return $this->_receivedCommands;
    }
    
    function appendLines($l)
    {
    	$s = implode("\r\n", $l)."\r\n";
        $this->multiplexSrv->encolarDatosEscribir($this->sKey, $s);
    }
}
?>