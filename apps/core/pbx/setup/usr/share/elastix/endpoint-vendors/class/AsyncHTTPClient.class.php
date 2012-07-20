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

class AsyncHTTPClient extends MultiplexConn
{
    private $_endpoint;
    private $_bPendingOutput = FALSE;
    private $_current_sock_url = NULL;
    private $_requestProtocolVersion = '1.0';
    private $_requestHeaders = array();
    private $_postData = NULL;
    private $_responseHeaders = array();
    private $_responseData = '';
    private $_responseCode = NULL;
    private $_responseProtocolVersion = NULL;
    private $_readingData = FALSE;
    private $_headerSeparator = NULL;
    private $_responseComplete = FALSE;

    function AsyncHTTPClient($multiplex, $endpoint)
    {
        $this->multiplexSrv = $multiplex;
        $this->_endpoint = $endpoint;
    }

    // Datos a mandar a escribir apenas se inicia la conexión
    function procesarInicial() {}

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

    function addRequestHeaders($headers)
    {
        if (!is_array($headers)) return;
    	$this->_requestHeaders = array_merge($this->_requestHeaders, $headers);
    }
    
    function setPostData($s) { $this->_postData = $s; }
    
    function setRequestProtocolVersion($s) { $this->_requestProtocolVersion = $s; }
    
    function GET($url = NULL)
    {
    	return $this->doRequest('GET', $url);
    }
    
    function POST($url = NULL)
    {
    	return $this->doRequest('POST', $url);
    }
    
    private function _closeAfterResponse()
    {
        if (isset($this->_responseHeaders['Connection'])) {
            if ($this->_responseHeaders['Connection'] == 'close') return TRUE;
            if ($this->_responseHeaders['Connection'] == 'keep-alive') return FALSE;
        }  
    	if ($this->_responseProtocolVersion != '1.1') return TRUE;
        return FALSE;
    }
    
    function doRequest($method, $url = NULL)
    {
    	$url_components = parse_url($url);
        if (!is_array($url_components)) return array(0, 'Invalid URL');
        $is_https = (isset($url_components['scheme']) && $url_components['scheme'] == 'https'); 
        if (isset($url_components['port']))
            $port = $url_components['port'];
        elseif ($is_https)
            $port = 443;
        else $port = 80;
        
        if (is_null($this->sKey)) $this->_current_sock_url = NULL;
        $sock_url = ($is_https ? 'ssl://' : 'tcp://').$url_components['host'].':'.$port;
        if (!is_null($this->_current_sock_url) && $this->_current_sock_url != $sock_url) {
        	return array(0, 'Cannot switch to another connection with previous connection open.');
        }
        
        $request_path = isset($url_components['path']) ? $url_components['path'] : '/';
        if (isset($url_components['query']))
            $request_path .= '?'.$url_components['query'];
        $this->_requestHeaders['Host'] = $url_components['host'];
        
        if (isset($url_components['user']) && isset($url_components['pass']))
            $this->_requestHeaders['Authorization'] = 'Basic '.base64_encode($url_components['user'].':'.$url_components['pass']);

        if (!is_null($this->_postData)) {
        	if (!isset($this->_requestHeaders['Content-Type']))
                $this->_requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
            $this->_requestHeaders['Content-Length'] = strlen($this->_postData);
        }
        if (!isset($this->_requestHeaders['Connection']) && $this->_requestProtocolVersion == '1.1')
            $this->_requestHeaders['Connection'] = 'close';
        if (!isset($this->_requestHeaders['User-Agent']))
            $this->_requestHeaders['User-Agent'] = 'Elastix-AsyncHTTPClient/1.0';
        
        if (is_null($this->_current_sock_url)) {
            $errno = $errstr = NULL;
            $hConn = @stream_socket_client($sock_url, $errno, $errstr, 2,
                STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
            if (!$hConn) {
                return array($errno, $errstr);
            }        
            $this->multiplexSrv->agregarNuevaConexion($this, $hConn);
        }
        
        $this->_current_sock_url = $sock_url;
        
        $sRequestData = "{$method} $request_path HTTP/{$this->_requestProtocolVersion}\r\n";
        foreach ($this->_requestHeaders as $k => $v) {
            $sRequestData .= "$k: $v\r\n";
        }
        $sRequestData .= "\r\n";
        $this->multiplexSrv->encolarDatosEscribir($this->sKey, $sRequestData);
        if (!is_null($this->_postData)) 
            $this->multiplexSrv->encolarDatosEscribir($this->sKey, $this->_postData);
        $this->_requestHeaders = array();
        $this->_postData = NULL;
        $this->_responseComplete = FALSE;
        $this->_responseHeaders = array();
        $this->_responseData = '';
        $this->_responseCode = NULL;
        $this->_responseProtocolVersion = NULL;
        $this->_readingData = FALSE;
        return TRUE;
    }
    
    // Separar flujo de datos en paquetes, devuelve número de bytes de paquetes aceptados
    function parsearPaquetes($sDatos)
    {
        $iLongProcesada = 0;
        while (!$this->_readingData && strlen($sDatos) > 0) {
        	// Currently reading headers
            
            if (is_null($this->_headerSeparator)) {
                /* Need to identify either the next header, or the empty line
                   that separates headers from data. It is assumed that there 
                   should be at least the response code, or a partial read. */
                $iHeaderLength = strcspn($sDatos, "\r\n");
                $iNextHeader = $iHeaderLength;
                if ($iNextHeader < strlen($sDatos) && $sDatos[$iNextHeader] == "\r")
                    $iNextHeader++;
                if ($iNextHeader < strlen($sDatos) && $sDatos[$iNextHeader] == "\n")
                    $iNextHeader++;
                if ($iHeaderLength == $iNextHeader) {
                    // Partial header, should keep reading later
                    break;
                }
                if ($iHeaderLength + 2 == $iNextHeader) {
                    // Compliant \r\n header separator
                    $this->_headerSeparator = "\r\n";
                } elseif ($iNextHeader == strlen($sDatos)) {
                    // Cannot tell apart partial header from noncompliant server,
                    // need more data to tell them apart
                    break;
                } else {
                	// Noncompliant single-character separator
                    $this->_headerSeparator = substr($sDatos, $iHeaderLength, $iNextHeader - $iHeaderLength);
                }
            }
                        
        	// Header separator has been identified
            $sHeader = NULL;
            $iHeaderLength = strpos($sDatos, $this->_headerSeparator);
            if ($iHeaderLength === FALSE) {
                // Partial header, should keep reading later
                break;
            } else {
            	$iNextHeader = $iHeaderLength + strlen($this->_headerSeparator);
                $sHeader = substr($sDatos, 0, $iHeaderLength);
                $sDatos = substr($sDatos, $iNextHeader);
                $iLongProcesada += $iNextHeader;
            }
            
            $regs = NULL;
            if (is_null($this->_responseCode)) {
            	// First header line is the response code: HTTP/1.1 200 OK
                if (preg_match('|^HTTP/(\d+\.\d+) (\d+)|', $sHeader, $regs)) {
                    $this->_responseProtocolVersion = $regs[1];
                	$this->_responseCode = $regs[2];
                } else {
                    // Unrecognized response pattern
                	$this->_responseCode = 0;
                }
            } elseif ($sHeader == '') {
                // Found separator
                $this->_readingData = TRUE;
            } else {
            	// Ordinary HTTP header
                if (preg_match('/^(.+?): (.*)$/', $sHeader, $regs)) {
            		$this->_responseHeaders[$regs[1]] = $regs[2];
            	}
            }
        }
        
        if ($this->_readingData) {
        	// Currently reading data
            if (isset($this->_responseHeaders['Transfer-Encoding']) && 
                $this->_responseHeaders['Transfer-Encoding'] == 'chunked') {
            	// Decode all complete chunks
                $bHaveCompleteChunk = TRUE;
                while (strlen($sDatos) > 0 && $bHaveCompleteChunk) {
                	$iHeaderLength = strpos($sDatos, $this->_headerSeparator);
                    if ($iHeaderLength === FALSE) {
                        $bHaveCompleteChunk = FALSE;
                    } elseif ($iHeaderLength > 10) {
                    	// Invalid chunk
                        $this->finalizarConexion();
                        $bHaveCompleteChunk = FALSE;
                    } elseif ($iHeaderLength + strlen($this->_headerSeparator) >= strlen($sDatos)) {
                    	$bHaveCompleteChunk = FALSE;
                    } else {
                    	// Read the length of this chunk
                        $sHeader = substr($sDatos, 0, $iHeaderLength);
                        list($iChunkLength) = sscanf($sHeader, "%x");
                        if (is_null($iChunkLength)) {
                        	// Invalid chunk
                            $this->finalizarConexion();
                            $bHaveCompleteChunk = FALSE;
                        }
                        // Check if there is enough data for the chunk
                        if ($iHeaderLength + strlen($this->_headerSeparator) + 
                            $iChunkLength +  strlen($this->_headerSeparator) 
                            > strlen($sDatos)) {
                        	$bHaveCompleteChunk = FALSE;
                        } else {
                        	$this->_responseData .= substr($sDatos, $iHeaderLength + strlen($this->_headerSeparator), $iChunkLength);
                            $iLongProcesada += 
                                $iHeaderLength + strlen($this->_headerSeparator) + 
                                $iChunkLength +  strlen($this->_headerSeparator);
                            $sDatos = substr($sDatos,
                                $iHeaderLength + strlen($this->_headerSeparator) + 
                                $iChunkLength +  strlen($this->_headerSeparator));
                            if ($iChunkLength == 0) {
                            	// Zero-length chunk marks end of data
                                $this->_responseComplete = TRUE;
                                if ($this->_closeAfterResponse()) {
                                    // Delayed call to updateLocalConfig()
                                    $this->finalizarConexion();
                                }
                            }
                        }
                    }
                }
            } else {
            	// Add remainder of data to response
                $this->_responseData .= $sDatos;
                $iLongProcesada += strlen($sDatos);
                if (isset($this->_responseHeaders['Content-Length']) && 
                    strlen($this->_responseData) >= $this->_responseHeaders['Content-Length']) {
                	$this->_responseComplete = TRUE;
                    
                    if ($this->_closeAfterResponse()) {
                        // Delayed call to updateLocalConfig()
                        $this->finalizarConexion();
                    }
                }
            }
        }
        if ($iLongProcesada > 0) $this->_bPendingOutput = TRUE;
        return $iLongProcesada;
    }

    function getResponseCode() { return $this->_responseCode; }
    function getResponseHeaders() { return $this->_responseHeaders; }
    function getResponseData() { return $this->_responseData; }
    function getResponseComplete() {
        return ($this->_responseComplete || 
            (isset($this->_responseHeaders['Connection']) && 
                $this->_responseHeaders['Connection'] == 'close' && 
                is_null($this->sKey)));
    }
}
?>