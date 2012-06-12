<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |f
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
  $Id: new_campaign.php $ */

/**
 * Clase que contiene la funcionalidad principal de la consola de agente.
 */

require_once 'libs/paloSantoDB.class.php';
require_once 'ECCP.class.php';
require_once '/opt/elastix/dialer/phpagi-asmanager-elastix.php';

class PaloSantoConsola
{
    var $errMsg = '';    // Mensajes de error
    private $_oDB_asterisk = NULL;     // Conexión a base de datos asterisk (FreePBX)
    private $_oDB_call_center = NULL;  // Conexión a base de datos call_center
    private $_astman = NULL;
    private $_eccp = NULL;

    private $_agent = NULL;     // Si se ha elegido un agente, es de forma Agent/9000

    function PaloSantoConsola($sAgent = NULL)
    {
        if (!is_null($sAgent)) $this->_agent = $sAgent;
    }

    // Obtener la conexión requerida, iniciándola si es necesario
    private function _obtenerConexion($sConn)
    {
        global $arrConf;

        switch ($sConn) {
        case 'asterisk':
            if (!is_null($this->_oDB_asterisk)) return $this->_oDB_asterisk;
            $sDSN = generarDSNSistema('asteriskuser', 'asterisk');
            $oDB = new paloDB($sDSN);
            if ($oDB->connStatus) {
                $this->_errMsg = '(internal) Unable to create asterisk DB conn - '.$oDB->errMsg;
                die($this->_errMsg);
            }
            $this->_oDB_asterisk = $oDB;
            return $this->_oDB_asterisk;
            break;
        case 'call_center':
            if (!is_null($this->_oDB_call_center)) return $this->_oDB_call_center;
            $sDSN = $arrConf['cadena_dsn'];
            $oDB = new paloDB($sDSN);
            if ($oDB->connStatus) {
                $this->_errMsg = '(internal) Unable to create asterisk DB conn - '.$oDB->errMsg;
                die($this->_errMsg);
            }
            $this->_oDB_asterisk = $oDB;
            return $this->_oDB_asterisk;
            break;
        case 'AMI':
            if (!is_null($this->_astman)) return $this->_astman;
            $oAst = new AGI_AsteriskManager();
            $tuplaLogin = $this->_leerConfigManager();
            if (!is_array($tuplaLogin)) die($this->_errMsg);
            if (!$oAst->connect('127.0.0.1', $tuplaLogin[0], $tuplaLogin[1]))
                die('(internal) Cannot connect to AMI');
            $this->_astman = $oAst;
            return $this->_astman;
            break;
        case 'ECCP':
            if (!is_null($this->_eccp)) return $this->_eccp;

            $sUsernameECCP = 'agentconsole';
            $sPasswordECCP = 'agentconsole';
            
            // Verificar si existe la contraseña de ECCP, e insertar si necesario
            $dbConnCC = $this->_obtenerConexion('call_center');
            $md5_passwd = $dbConnCC->getFirstRowQuery(
                'SELECT md5_password FROM eccp_authorized_clients WHERE username = ?',
                TRUE, array($sUsernameECCP));
            if (is_array($md5_passwd)) {
            	if (count($md5_passwd) <= 0) {
            		$dbConnCC->genQuery(
                        'INSERT INTO eccp_authorized_clients (username, md5_password) VALUES(?, md5(?))',
                        array($sUsernameECCP, $sPasswordECCP));
            	}
            }

            $oECCP = new ECCP();
            
            // TODO: configurar credenciales
            $oECCP->connect("localhost", $sUsernameECCP, $sPasswordECCP);
            if (!is_null($this->_agent)) {
            	$oECCP->setAgentNumber($this->_agent);
                
                // El siguiente código asume agente Agent/9000
                if (preg_match('|^Agent/(\d+)$|', $this->_agent, $regs))
                    $sAgentNumber = $regs[1];
                else $sAgentNumber = $this->_agent;
                
                /* Privilegio de localhost - se puede recuperar la clave del
                 * agente sin tener que pedirla explícitamente */                
                $tupla = $dbConnCC->getFirstRowQuery(
                    'SELECT eccp_password FROM agent WHERE number = ?', 
                    FALSE, array($sAgentNumber));
                if (!is_array($tupla))
                    throw new ECCPConnFailedException(_tr('Failed to retrieve agent password'));
                if (count($tupla) <= 0)
                    throw new ECCPUnauthorizedException(_tr('Agent not found'));
                if (is_null($tupla[0]))
                    throw new ECCPUnauthorizedException(_tr('Agent not authorized for ECCP - ECCP password not set'));
                $oECCP->setAgentPass($tupla[0]);
                
                // Filtrar los eventos sólo para el agente actual
                $oECCP->filterbyagent();
            }
               
            $this->_eccp = $oECCP;
            return $this->_eccp;
            break;
        }        
        return NULL;
    }

    // Leer el estado de /etc/asterisk/manager.conf y obtener el primer usuario 
    // que puede usar el dialer. Devuelve NULL en caso de error, o tupla 
    // user,password para conexión en localhost.
    private function _leerConfigManager()
    {
    	$sNombreArchivo = '/etc/asterisk/manager.conf';
        if (!file_exists($sNombreArchivo)) {
        	$this->_errMsg = "(internal) $sNombreArchivo no se encuentra.";
            return NULL;
        }
        if (!is_readable($sNombreArchivo)) {
            $this->_errMsg = "(internal) $sNombreArchivo no puede leerse por usuario de marcador.";
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
            $this->_errMsg = "(internal) $sNombreArchivo no puede parsearse correctamente.";
        }
        return NULL;
    }


    /**
     * Método que desconecta todas las conexiones a base de datos y Asterisk que
     * mantenga conectado el objeto.
     *
     * @return  null
     */
    function desconectarTodo()
    {
        $this->desconectarEspera();
        if (!is_null($this->_eccp)) {
            try {
                $this->_eccp->disconnect();
            } catch (Exception $e) {}
            $this->_eccp = NULL;
        }
    }

    /**
     * Método que desconecta todas las conexiones a bases de datos y a Asterisk,
     * pero mantiene la conexión activa a ECCP. El uso esperado es 
     * inmediatamente antes de la espera larga de la interfaz web, donde no 
     * se esperan futuras consultas a la base de datos.
     *
     * @return  null
     */
    function desconectarEspera()
    {
        if (!is_null($this->_oDB_asterisk)) {
            $this->_oDB_asterisk->disconnect();
            $this->_oDB_asterisk = NULL;
        }
        if (!is_null($this->_oDB_call_center)) {
            $this->_oDB_call_center->disconnect();
            $this->_oDB_call_center = NULL;
        }
        if (!is_null($this->_astman)) {
            $this->_astman->disconnect();
            $this->_astman = NULL;
        }
    }

    private function _formatoErrorECCP($x)
    {
    	if (isset($x->failure)) {
    		return (int)$x->failure->code.' - '.(string)$x->failure->message;
    	} else {
    		return '';
    	}
    }

    /**
     * Método que lista todas las extensiones SIP e IAX que están definidas en
     * el sistema. Estas extensiones pueden ser usadas por el agente para 
     * logonearse en el sistema. La lista se devuelve de la forma 
     * (1000 => 'SIP/1000'), ...
     *
     * @return  mixed   La lista de extensiones.
     */
    function listarExtensiones()
    {
        // TODO: verificar si esta manera de consultar funciona para todo 
        // FreePBX. Debe de poder identificarse extensiones sin asumir una 
        // tecnología en particular. 
        $oDB = $this->_obtenerConexion('asterisk');
        $sPeticion = <<<LISTA_EXTENSIONES
SELECT extension,
    (SELECT COUNT(*) FROM iax WHERE iax.id = users.extension) AS iax,
    (SELECT COUNT(*) FROM sip WHERE sip.id = users.extension) AS sip
FROM users ORDER BY extension
LISTA_EXTENSIONES;
        $recordset = $oDB->fetchTable($sPeticion, TRUE);
        if (!is_array($recordset)) die('(internal) Cannot list extensions - '.$oDB->errMsg);

        $listaExtensiones = array();
        foreach ($recordset as $tupla) {
            $sTecnologia = NULL;
            if ($tupla['iax'] > 0) $sTecnologia = 'IAX2/';
            if ($tupla['sip'] > 0) $sTecnologia = 'SIP/';
            
            // Cómo identifico las otras tecnologías?
            if (!is_null($sTecnologia)) {
                $listaExtensiones[$tupla['extension']] = $sTecnologia.$tupla['extension'];
            }
        }
        return $listaExtensiones;
    }
    
    /**
     * Método que lista todos los agentes registrados en la base de datos. La
     * lista se devuelve de la forma (9000 => 'Over 9000!!!'), ...
     *
     * @return  mixed   La lista de agentes activos
     */
    function listarAgentes()
    {
        $oDB = $this->_obtenerConexion('call_center');
        $sPeticion = "SELECT number, name FROM agent WHERE estatus = 'A' ORDER BY number";
        $recordset = $oDB->fetchTable($sPeticion, TRUE);
        if (!is_array($recordset)) die('(internal) Cannot list agents - '.$oDB->errMsg);
        
        $listaAgentes = array();
        foreach ($recordset as $tupla) {
            $listaAgentes[$tupla['number']] = $tupla['number'].' - '.$tupla['name'];
        }        
        return $listaAgentes;
    }

    /**
     * Método para iniciar el login del agente con la extensión y el número de
     * agente que se indican. 
     *
     * @param   string  Extensión que está usando el agente, como "SIP/1064"
     * @param   string  Número del agente que se está logoneando: "9000"
     *
     * @return  VERDADERO en éxito, FALSE en error
     */
    function loginAgente($sExtension)
    {
        $regs = NULL;
        if (preg_match('|^\w+/(\d+)$|', $sExtension, $regs))
            $sNumero = $regs[1];
        else $sNumero = $sExtension;
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $loginResponse = $oECCP->loginagent($sNumero);
            if (isset($loginResponse->failure))
                $this->errMsg = '(internal) loginagent: '.$this->_formatoErrorECCP($loginResponse);
            return ($loginResponse->status == 'logged-in' || $loginResponse->status == 'logging');
        } catch (Exception $e) {
            $this->errMsg = '(internal) loginagent: '.$e->getMessage();
            return FALSE;
        }
    }

    /**
     * Método para esperar 1 segundo por el resultado del login del agente 
     * asociado con esta consola de agente. Se asume que previamente se ha
     * iniciado un login de agente con la función loginAgente().
     * 
     * @return  string  Uno de logged-in logging logged-out mismatch error
     */
    function esperarResultadoLogin()
    {
        $this->errMsg = '';
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $oECCP->wait_response(1);
            while ($e = $oECCP->getEvent()) {
                foreach ($e->children() as $ee) $evt = $ee;

                if ($evt->getName() == 'agentloggedin' && $evt->agent == $this->_agent)
                    return 'logged-in';
                if ($evt->getName() == 'agentfailedlogin' && $evt->agent == $this->_agent)
                    return 'logged-out';
                // TODO: devolver mismatch si logoneo con éxito a consola equivocada.
            }
            return 'logging';   // No se recibieron eventos relevantes
        } catch (Exception $e) {
            $this->errMsg = '(internal) esperarResultadoLogin: '.$e->getMessage();
        	return 'error';
        }
    }

    /**
     * Método para terminar el login de un agente cuyo número se indica. Esta
     * operación también termina cualquier pausa en la que esté puesto el 
     * agente.
     *
     * @param   string  Número del agente que se está logoneando: "9000"
     *
     * @return  VERDADERO en éxito, FALSE en error
     */
    function logoutAgente()
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $response = $oECCP->logoutagent();
            if (isset($response->failure)) {
                $this->errMsg = '(internal) logoutagent: '.$this->_formatoErrorECCP($response);
                return FALSE;
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errMsg = '(internal) logoutagent: '.$e->getMessage();
            return FALSE;
        }
    }

    /**
     * Método para verificar el estado de logoneo de agente a través de 
     * 'agent show online'. Este método es el principal mecanismo para mantener
     * la sesión activa del agente en el navegador.
     *
     * @param   string  Número del agente que se está logoneando: "9000"
     * @param   string  Extensión que está usando el agente, como "SIP/1064"
     * 
     * @return  string  Uno de logged-in logging logged-out mismatch error
     */
    function estadoAgenteLogoneado($sExtension, $sAgente)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $connStatus = $oECCP->getagentstatus();
            if (isset($connStatus->failure)) {
                $this->errMsg = '(internal) getagentstatus: '.$this->_formatoErrorECCP($connStatus);
            	return array('estadofinal' => 'error');
            }
            
            $estado = array(
                'estadofinal'       =>  'logged-in',    // A modificar por condiciones
                'status'            =>  (string)$connStatus->status,
                'channel'           =>  isset($connStatus->channel) ? (string)$connStatus->channel : NULL,
                'extension'         =>  isset($connStatus->extension) ? (string)$connStatus->extension : NULL,
                'onhold'            =>  isset($connStatus->onhold) ? ($connStatus->onhold == 1) : FALSE,
                'remote_channel'    =>  isset($connStatus->remote_channel) ? (string)$connStatus->remote_channel : NULL,
                'pauseinfo'         =>  isset($connStatus->pauseinfo) ? array(
                    'pauseid'       =>  (int)$connStatus->pauseinfo->pauseid,
                    'pausename'     =>  (string)$connStatus->pauseinfo->pausename,
                    'pausestart'    =>  (string)$connStatus->pauseinfo->pausestart,
                ) : NULL,
                'callinfo'          =>  isset($connStatus->callinfo) ? array(
                    'calltype'      =>  (string)$connStatus->callinfo->calltype,
                    'campaign_id'   =>  isset($connStatus->callinfo->campaign_id) ? (int)$connStatus->callinfo->campaign_id : NULL,
                    'callid'        =>  (int)$connStatus->callinfo->callid,
                    'callnumber'    =>  (string)$connStatus->callinfo->callnumber,
                    'dialstart'     =>  isset($connStatus->callinfo->dialstart) ? (string)$connStatus->callinfo->dialstart : NULL,
                    'dialend'       =>  isset($connStatus->callinfo->dialend) ? (string)$connStatus->callinfo->dialend : NULL,
                    'queuestart'    =>  (string)$connStatus->callinfo->queuestart,
                    'linkstart'     =>  (string)$connStatus->callinfo->linkstart,
                ) : NULL,
            );
            
            if (!is_null($estado['pauseinfo'])) foreach (array('pausestart') as $k) {
            	if (!is_null($estado['pauseinfo'][$k]) && preg_match('/^\d+:\d+:\d+$/', $estado['pauseinfo'][$k]))
                    $estado['pauseinfo'][$k] = date('Y-m-d ').$estado['pauseinfo'][$k];
            }
            if (!is_null($estado['callinfo'])) foreach (array('dialstart', 'dialend', 'queuestart', 'linkstart') as $k) {
                if (!is_null($estado['callinfo'][$k]) && preg_match('/^\d+:\d+:\d+$/', $estado['callinfo'][$k]))
                    $estado['callinfo'][$k] = date('Y-m-d ').$estado['callinfo'][$k];
            }
            
            if ($estado['status'] == 'offline') {
            	$estado['estadofinal'] = is_null($estado['channel']) ? 'logged-out' : 'logging';
            } elseif ($estado['extension'] != $sExtension) {
                $estado['estadofinal'] = 'mismatch';
                $this->errMsg = _tr('Specified agent already connected to extension').
                    ': '.(string)$connStatus->extension;
            }
            return $estado;
        } catch (Exception $e) {
        	$this->errMsg = '(internal) getagentstatus: '.$e->getMessage();
            return array('estadofinal' => 'error');
        }
    }
    
    /**
     * Método para calcular un intervalo razonable de espera durante una petición
     * larga de AJAX. 
     *
     * @return  integer     El valor en segundos recomendado según el navegador.
     */
    static function recomendarIntervaloEsperaAjax()
    {
        $iTimeoutPoll = 2 * 60;
/*
        // Problemas con MSIE al haber más de un AJAX con respuesta larga
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE ') !== false) {
            $iTimeoutPoll = 2;
        }
*/
        return $iTimeoutPoll;
    }
    

    /**
     * Método para mandar a ejecutar el colgado de la llamada activa.
     * 
     * @return  bool  TRUE para llamada colgada, o FALSE si error
     */
    function colgarLlamada()
    {
    	try {
            $oECCP = $this->_obtenerConexion('ECCP');
    		$respuesta = $oECCP->hangup();
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to hangup call').' - '.$this->_formatoErrorECCP($respuesta);
                return FALSE;
            }
            return TRUE;
    	} catch (Exception $e) {
            $this->errMsg = '(internal) hangup: '.$e->getMessage();
    		return FALSE;
    	}
    }
    
    /**
     * Método para listar los breaks conocidos en el sistema.
     * 
     * @return NULL en caso de éxito, o lista en la forma 
     *      array([breakid]=>"breakname - breakdesc")
     */
    function listarBreaks()
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->getpauses();
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to fetch break types').' - '.$this->_formatoErrorECCP($respuesta);
                return NULL;
            }
            $listaPausas = array();
            foreach ($respuesta->pause as $xml_pause) {
            	$listaPausas[(int)$xml_pause['id']] = (string)$xml_pause->name.' - '.(string)$xml_pause->description;
            }
            return $listaPausas;
        } catch (Exception $e) {
            $this->errMsg = '(internal) getpauses: '.$e->getMessage();
            return NULL;
        }
    }
    
    /**
     * Método para iniciar el break del agente actualmente logoneado
     * 
     * @param   int $idBreak    ID del break a usar para el agente
     * 
     * @return  TRUE en caso de éxito, FALSE en caso de error. 
     */
    function iniciarBreak($idBreak)
    {
    	try {
    		$oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->pauseagent($idBreak);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to start break').' - '.$this->_formatoErrorECCP($respuesta);
            	return FALSE;
            }
            return TRUE;
    	} catch (Exception $e) {
            $this->errMsg = '(internal) pauseagent: '.$e->getMessage();
    		return FALSE;
    	}
    }
    
    /**
     * Método para terminar el break del agente actualmente logoneado
     * 
     * @return  TRUE en caso de éxito, FALSE en caso de error. 
     */
    function terminarBreak()
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->unpauseagent();
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to stop break').' - '.$this->_formatoErrorECCP($respuesta);
                return FALSE;
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errMsg = '(internal) unpauseagent: '.$e->getMessage();
            return FALSE;
        }
    }
    
    function transferirLlamada($sTransferExt)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->transfercall($sTransferExt);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to transfer call').' - '.$this->_formatoErrorECCP($respuesta);
                return FALSE;
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errMsg = '(internal) transfercall: '.$e->getMessage();
            return FALSE;
        }
    }
    
    function leerInfoCampania($sCallType, $iCampaignId)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->getcampaigninfo($sCallType, $iCampaignId);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to read call information').' - '.$this->_formatoErrorECCP($respuesta);
                return NULL;
            }

            $reporte = array();
            foreach ($respuesta->children() as $xml_node) {
                switch ($xml_node->getName()) {
                case 'forms':
                    $reporte['forms'] = array();
                    foreach ($xml_node->form as $xml_form) {
                        $campos = array();
                        foreach ($xml_form->field as $xml_field) {
                            $descCampo = array(
                                'id'    =>  (int)$xml_field['id'],
                                'order' =>  (int)$xml_field['order'],
                                'label' =>  (string)$xml_field->label,
                                'type'  =>  (string)$xml_field->type,
                                'maxsize'   =>  isset($xml_field->maxsize) ? (int)$xml_field->maxsize : NULL,
                            );
                            if (isset($xml_field->default_value))
                                $descCampo['default_value'] = (string)$xml_field->default_value;
                            if (isset($xml_field->options)) foreach ($xml_field->options->value as $xml_option_value) {
                            	$descCampo['options'][] = (string)$xml_option_value;
                            } 
                            $campos[(int)$xml_field['order']] = $descCampo;
                        }
                        ksort($campos);
                        $reporte['forms'][(int)$xml_form['id']]['fields'] = $campos;
                        $reporte['forms'][(int)$xml_form['id']]['name'] = (string)$xml_form['name'];
                        $reporte['forms'][(int)$xml_form['id']]['description'] = (string)$xml_form['description'];
                    }
                    break;
                default:
                    $reporte[$xml_node->getName()] = (string)$xml_node;
                    break;
                }
            }
            foreach (array('name', 'type', 'startdate', 'enddate', 
                'working_time_starttime', 'working_time_endtime', 'queue', 
                'retries', 'context', 'maxchan', 'status', 'script', 'forms') as $k)
                if (!isset($reporte[$k])) $reporte[$k] = NULL;
            return $reporte;
        } catch (Exception $e) {
            $this->errMsg = '(internal) getcampaigninfo: '.$e->getMessage();
            return NULL;
        }
    }
    
    function leerInfoLlamada($sCallType, $iCampaignId, $iCallId)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->getcallinfo($sCallType, $iCampaignId, $iCallId);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to read call information').' - '.$this->_formatoErrorECCP($respuesta);
                return NULL;
            }

            $reporte = array();
            foreach ($respuesta->children() as $xml_node) {
            	switch ($xml_node->getName()) {
            	case 'call_attributes':
                    $reporte['call_attributes'] = $this->_traducirCallAttributes($xml_node);
                    break;
                case 'matching_contacts':
                    $reporte['matching_contacts'] = $this->_traducirMatchingContacts($xml_node);
                    break;
                case 'call_survey':
                    $reporte['call_survey'] = $this->_traducirCallSurvey($xml_node);
                    break;
                default:
                    $reporte[$xml_node->getName()] = (string)$xml_node;
                    break;
            	}
            }
            foreach (array('calltype', 'call_id', 'campaign_id', 'phone', 'status',
                'uniqueid', 'datetime_join', 'datetime_linkstart', 'trunk', 'queue',
                'agent_number', 'datetime_originate', 'datetime_originateresponse', 
                'retries', 'call_attributes', 'matching_contacts', 'call_survey') as $k)
                if (!isset($reporte[$k])) $reporte[$k] = NULL;
            return $reporte;
        } catch (Exception $e) {
            $this->errMsg = '(internal) getcallinfo: '.$e->getMessage();
            return NULL;
        }
    }
    
    private function _traducirCallAttributes($xml_node)
    {
        $reporte = array();
        foreach ($xml_node->attribute as $xml_attribute) {
            $reporte[(int)$xml_attribute->order] = array(
                'label' =>  (string)$xml_attribute->label,
                'value' =>  (string)$xml_attribute->value,
            );
        }
        ksort($reporte);
        return $reporte;
    }
    
    private function _traducirMatchingContacts($xml_node)
    {
        $reporte = array();
        foreach ($xml_node->contact as $xml_contact) {
            $atributos = array();
            foreach ($xml_contact->attribute as $xml_attribute) {
                $atributos[(int)$xml_attribute->order] = array(
                    'label' =>  (string)$xml_attribute->label,
                    'value' =>  (string)$xml_attribute->value,
                );
            }
            ksort($atributos);
            $reporte[(int)$xml_contact['id']] = $atributos;
        }
        return $reporte;
    }
    
    private function _traducirCallSurvey($xml_node)
    {
        $reporte = array();
        foreach ($xml_node->form as $xml_form) {
            $atributos = array();
            foreach ($xml_form->field as $xml_field) {
                $atributos[(int)$xml_field['id']] = array(
                    'label' =>  (string)$xml_field->label,
                    'value' =>  (string)$xml_field->value,
                );
            }
            ksort($atributos);
            $reporte[(int)$xml_form['id']] = $atributos;
        }
    	return $reporte;
    }
    
    function leerScriptCola($queue)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->getqueuescript($queue);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to read queue script').' - '.$this->_formatoErrorECCP($respuesta);
                return NULL;
            }
            return (string)$respuesta->script;
        } catch (Exception $e) {
            $this->errMsg = '(internal) getqueuescript: '.$e->getMessage();
            return NULL;
        }
    }
    
    function confirmarContacto($idCall, $idContact)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->setcontact($idCall, $idContact);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to set contact').' - '.$this->_formatoErrorECCP($respuesta);
                return FALSE;
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errMsg = '(internal) setcontact: '.$e->getMessage();
            return FALSE;
        }
    }
    
    function agendarLlamada($schedule, $sameagent, $newphone, $newcontactname)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->schedulecall($schedule, $sameagent, $newphone, $newcontactname);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to schedule call').' - '.$this->_formatoErrorECCP($respuesta);
                return FALSE;
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errMsg = '(internal) schedulecall: '.$e->getMessage();
            return FALSE;
        }
    }
    
    function guardarDatosFormularios($sTipoLlamada, $idLlamada, $datosForm)
    {
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $respuesta = $oECCP->saveformdata($sTipoLlamada, $idLlamada, $datosForm);
            if (isset($respuesta->failure)) {
                $this->errMsg = _tr('Unable to save form data').' - '.$this->_formatoErrorECCP($respuesta);
                return FALSE;
            }
            return TRUE;
        } catch (Exception $e) {
            $this->errMsg = '(internal) saveformdata: '.$e->getMessage();
            return FALSE;
        }
    }
    
    function esperarEventoSesionActiva()
    {
        $this->errMsg = '';
        try {
            $oECCP = $this->_obtenerConexion('ECCP');
            $oECCP->wait_response(1);
            $listaEventos = array();
            while ($e = $oECCP->getEvent()) {
                foreach ($e->children() as $ee) $evt = $ee;
                $sNombreEvento = (string)$evt->getName();
                $evento = array(
                    'event' =>  $sNombreEvento,
                );
                if (isset($evt->agent)) $evento['agent_number'] = (string)$evt->agent;
                if (isset($evt->agent_number)) $evento['agent_number'] = (string)$evt->agent_number;
                switch ($sNombreEvento) {
                case 'agentloggedin':
                    // TODO: implementar para consola de monitoreo
                    break;
                case 'agentfailedlogin':
                    // TODO: implementar para consola de monitoreo
                    break;
                case 'agentloggedout':
                    // TODO: no se devuelve la lista de colas reportada
                    break;
                case 'agentlinked':
                    $evento['remote_channel'] = (string)$evt->remote_channel;
                    $evento['status'] = (string)$evt->status;
                    $evento['uniqueid'] = (string)$evt->uniqueid;
                    $evento['datetime_join'] = (string)$evt->datetime_join;
                    $evento['datetime_linkstart'] = (string)$evt->datetime_linkstart;
                    $evento['queue'] = isset($evt->queue) ? (string)$evt->queue : NULL;
                    $evento['trunk'] = isset($evt->trunk) ? (string)$evt->trunk : NULL;
                    $evento['retries'] = isset($evt->retries) ? (int)$evt->retries : NULL;
                    $evento['datetime_originateresponse'] = isset($evt->datetime_originateresponse) ? (string)$evt->datetime_originateresponse : NULL;                    
                    $evento['datetime_originate'] = isset($evt->datetime_originate) ? (string)$evt->datetime_originate : NULL;
                    $evento['call_attributes'] = isset($evt->call_attributes) ? $this->_traducirCallAttributes($evt->call_attributes) : NULL;
                    $evento['matching_contacts'] = isset($evt->matching_contacts) ? $this->_traducirMatchingContacts($evt->matching_contacts) : NULL;
                    $evento['call_survey'] = isset($evt->call_survey) ? $this->_traducirCallSurvey($evt->call_survey) : NULL;
                    // Cae al siguiente caso
                case 'agentunlinked':
                    $evento['call_type'] = (string)$evt->call_type;
                    $evento['campaign_id'] = isset($evt->campaign_id) ? (int)$evt->campaign_id : NULL;
                    $evento['call_id'] = (int)$evt->call_id;
                    $evento['phone'] = (string)$evt->phone;
                    break;
                case 'pauseend':
                    $evento['pause_end'] = (string)$evt->pause_end;
                    $evento['pause_duration'] = (int)$evt->pause_duration;
                    // Cae al siguiente caso
                case 'pausestart':
                    $evento['pause_class'] = (string)$evt->pause_class;
                    $evento['pause_type'] = isset($evt->pause_type) ? (int)$evt->pause_type : NULL;
                    $evento['pause_name'] = isset($evt->pause_name) ? (string)$evt->pause_name : NULL;
                    $evento['pause_start'] = (string)$evt->pause_start;
                    break;
                }
                $listaEventos[] = $evento;
            }
            return $listaEventos;
        } catch (Exception $e) {
            $this->errMsg = '(internal) esperarEventoSesionActiva: '.$e->getMessage();
            return NULL;
        }
    }
    
}

?>