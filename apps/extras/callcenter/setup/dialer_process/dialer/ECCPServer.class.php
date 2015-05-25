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

class ECCPServer extends MultiplexServer
{
    private $DEBUG = FALSE;

    private $_tuberia = NULL;
    private $_dbConn = NULL;        // Conexión a la base de datos
    private $_astConn = NULL;       // Conexión a Asterisk
    private $_astVersion = NULL;    // Versión de Asterisk
    private $_eccpProcess = NULL;   // Proceso ECCPProcess que tiene rutinas de auditoría

    // Constructor con objeto adicional de tubería
    function __construct($sUrlSocket, &$oLog, $tuberia)
    {
    	parent::__construct($sUrlSocket, $oLog);
        $this->_tuberia = $tuberia;
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

    function setAstConn($astConn, $astVersion)
    {
        $this->_astConn = $astConn;
        $this->_astVersion = $astVersion;
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'setAstConn')) {
                $oConn->setAstConn($this->_astConn, $this->_astVersion);
            }
        }
    }

    function setProcess($proc)
    {
        $this->_eccpProcess = $proc;
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, 'setProcess')) {
                $oConn->setProcess($this->_eccpProcess);
            }
        }
    }

    function setDEBUG($d)
    {
        $this->DEBUG = (bool)$d;
        foreach ($this->_listaConn as &$oConn) {
            $oConn->DEBUG = $this->DEBUG;
        }
    }

    function dbValido() { return !is_null($this->_dbConn); }

    /* Para una nueva conexión, siempre se instancia un ECCPConn */
    function procesarInicial($sKey)
    {
        $oNuevaConn = new ECCPProxyConn($this->_oLog, $this->_tuberia);
        $oNuevaConn->multiplexSrv = $this;
        $oNuevaConn->sKey = $sKey;
        $this->_listaConn[$sKey] = $oNuevaConn;
        $this->_listaConn[$sKey]->setDbConn($this->_dbConn);
        $this->_listaConn[$sKey]->setAstConn($this->_astConn, $this->_astVersion);
        $this->_listaConn[$sKey]->setProcess($this->_eccpProcess);
        $this->_listaConn[$sKey]->DEBUG = $this->DEBUG;
        $this->_listaConn[$sKey]->procesarInicial();
    }

    function finalizarConexionesECCP()
    {
        if ($this->_hEscucha !== FALSE) {
            fclose($this->_hEscucha);
            $this->_hEscucha = FALSE;
        }
        foreach ($this->_listaConn as $oConn) {
            if (is_a($oConn, 'ECCPConn')) {
            	$oConn->finalizarConexion();
            }
        }
    }

    /*
     * Definición para propagar la notificación a todas las conexiones activas.
     * Todas las notificaciones a propagar son métodos que empiezan con la
     * cadena "notificarEvento_".
     */
    function __call($sMetodo, $args)
    {
        if (strpos($sMetodo, 'notificarEvento_') !== 0) {
            $this->_oLog->output("ERR: no se reconoce método $sMetodo como una notificación");
            return;
        }
        foreach ($this->_listaConn as &$oConn) {
            if (method_exists($oConn, $sMetodo)) {
                call_user_func_array(array($oConn, $sMetodo), $args);
            }
        }
    }
}
?>