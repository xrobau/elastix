<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4                                                |
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
  $Id: core_PBX.class.php,v 1.0 2012-05-23 11:30:00 Alberto Santos F.  asantos@palosanto.com Exp $*/

$root = $_SERVER["DOCUMENT_ROOT"];
require_once("$root/libs/misc.lib.php");
require_once("$root/configs/default.conf.php");
require_once("$root/libs/paloSantoACL.class.php");
require_once("$root/libs/paloSantoDB.class.php");
require_once("$root/libs/paloSantoConfig.class.php");

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
    require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

class core_PBX
{
    /**
     * Description error message
     *
     * @var array
     */
    private $errMsg;

    /**
     * Description error message from a database
     *
     * @var string
     */
    private $DBerrMsg;

    /**
     * ACL User ID for authenticated user
     *
     * @var integer
     */
    private $_id_user;

    /**
     * DSN for connection to asterisk database
     *
     * @var string
     */
    private $_astDSN;

    /**
     * Object paloACL
     *
     * @var object
     */
    private $_pACL;

    /**
     * Array that contains a paloDB Object, the key is the DSN of a specific database
     *
     * @var array
     */

    private $_dbCache;

    /**
     * Constructor
     *
     */
    public function core_PBX()
    {
        $this->_id_user = NULL;
        $this->_pACL = NULL;
	$this->DBerrMsg = NULL;
        $this->errMsg = NULL;
        $this->_dbCache = array();
	$this->_astDSN = generarDSNSistema('asteriskuser', 'asterisk', $_SERVER['DOCUMENT_ROOT'].'/');
    }

    /**
     * Function that gets the extension of the login user, that assumed is on $_SERVER['PHP_AUTH_USER']
     *
     * @return  string   extension of the login user, or NULL if the user in $_SERVER['PHP_AUTH_USER'] does not have an extension     *                   assigned
     */
    private function _leerExtension()
    {
        // Identificar el usuario para averiguar el número telefónico origen
        $id_user = $this->_leerIdUser();

        $pACL = $this->_getACL();        
        $user = $pACL->getUsers($id_user);
        if ($user == FALSE) {
            $this->errMsg["fc"] = 'ACL';
            $this->errMsg["fm"] = 'ACL lookup failed';
            $this->errMsg["fd"] = 'Unable to read information from ACL - '.$pACL->errMsg;
            $this->errMsg["cn"] = get_class($pACL);
            return NULL;
        }

        // Verificar si tiene una extensión
        $extension = $user[0][3];
        if ($extension == "") {
            $this->errMsg["fc"] = 'EXTENSION';
            $this->errMsg["fm"] = 'Extension lookup failed';
            $this->errMsg["fd"] = 'No extension has been set for user '.$_SERVER['PHP_AUTH_USER'];
            $this->errMsg["cn"] = get_class($pACL);
            return NULL;
        }

        return $extension;
    }

    /**
     * Function that creates, if do not exist in the attribute dbCache, a new paloDB object for the given DSN
     *
     * @param   string   $sDSN   DSN of a specific database
     * @return  object   paloDB object for the entered database
     */
    private function & _getDB($sDSN)
    {
        if (!isset($this->_dbCache[$sDSN])) {
            $this->_dbCache[$sDSN] = new paloDB($sDSN);
        }
        return $this->_dbCache[$sDSN];
    }

    /**
     * Function that creates, if do not exist in the attribute _pACL, a new paloACL object
     *
     * @return  object   paloACL object
     */
    private function & _getACL()
    {
        global $arrConf;

        if (is_null($this->_pACL)) {
            $pDB_acl = $this->_getDB($arrConf['elastix_dsn']['acl']);
            $this->_pACL = new paloACL($pDB_acl);
        }
        return $this->_pACL;
    }

    /**
     * Function that reads the login user ID, that assumed is on $_SERVER['PHP_AUTH_USER']
     *
     * @return  integer   ACL User ID for authenticated user, or NULL if the user in $_SERVER['PHP_AUTH_USER'] does not exist
     */
    private function _leerIdUser()
    {
        if (!is_null($this->_id_user)) return $this->_id_user;

        $pACL = $this->_getACL();        
        $id_user = $pACL->getIdUser($_SERVER['PHP_AUTH_USER']);
        if ($id_user == FALSE) {
            $this->errMsg["fc"] = 'INTERNAL';
            $this->errMsg["fm"] = 'User-ID not found';
            $this->errMsg["fd"] = 'Could not find User-ID in ACL for user '.$_SERVER['PHP_AUTH_USER'];
            $this->errMsg["cn"] = get_class($this);
            return NULL;
        }
        $this->_id_user = $id_user;
        return $id_user;    
    }

    private function AsteriskManager_Originate($host, $user, $password, $command_data) {
        global $arrLang;
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg["fc"] = 'INTERNAL';
            $this->errMsg["fm"] = 'Asterisk Connection';
            $this->errMsg["fd"] = 'Could not connect to Asterisk Manager';
            $this->errMsg["cn"] = get_class($this);
	    return false;
        } else{
            $parameters = $this->Originate($command_data['origen'], $command_data['destino'], $command_data['channel'], $command_data['description']);

            $salida = $astman->send_request('Originate', $parameters);

            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else{
		$this->errMsg["fc"] = 'INTERNAL';
		$this->errMsg["fm"] = 'Asterisk Response';
		$this->errMsg["fd"] = 'Could not generate the call, Asterisk Manager is sending an error. Nobody answered the phone or it is busy or it is not registered to this server.';
		$this->errMsg["cn"] = get_class($this);
		return false;
	    }
        }
        return true;
    }

    private function Originate($origen, $destino, $channel="", $description="")
    {
        $parameters = array();
        $parameters['Channel']      = $channel;
        $parameters['CallerID']     = "$description <$origen>";
        $parameters['Exten']        = $destino;
        $parameters['Context']      = "";
        $parameters['Priority']     = 1;
        $parameters['Application']  = "";
        $parameters['Data']         = "";

        return $parameters;
    }

    private function Obtain_Protocol_from_Ext($id)
    {
        $pDB = new paloDB($this->_astDSN);

        $query = "SELECT dial, description FROM devices WHERE id=?";
        $result = $pDB->getFirstRowQuery($query, TRUE, array($id));
        if($result !== FALSE)
            return $result;
        else{
            $this->DBerrMsg = $pDB->errMsg;
            return FALSE;
        }
    }

    /**
     *  Function that makes a call to the specified number from the extension of the logged user
     * 
     *  @param   integer   number to call
     *  @return  mixed     Return an array indicating that the call taked place or FALSE in case of error
     */

    public function makeCall($number)
    {
	// Obtener el ID del usuario logoneado
        $id_user = $this->_leerIdUser();
        if (is_null($id_user)) return false;
	
	$extension = $this->_leerExtension();
	if (is_null($extension)) return false;

	$pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
	$arrConfig = $pConfig->leer_configuracion(false);

	$password = $arrConfig['AMPMGRPASS']['valor'];
	$host = $arrConfig['AMPDBHOST']['valor'];
	$user = 'admin';

	$extension_data = $this->Obtain_Protocol_from_Ext($extension);

	if($extension_data === FALSE){
	    $this->errMsg["fc"] = 'DBERROR';
            $this->errMsg["fm"] = 'Database operation failed';
            $this->errMsg["fd"] = 'Unable to read data from asterisk database - '.$this->DBerrMsg;
            $this->errMsg["cn"] = get_class($this);
            return false;
	}
	elseif(count($extension_data) == 0){
	    $this->errMsg["fc"] = 'ERROR';
            $this->errMsg["fm"] = 'Extension Error';
            $this->errMsg["fd"] = "Can not get more data in database from extension $extension";
            $this->errMsg["cn"] = get_class($this);
            return false;
	}

	$command_data['origen'] = $extension;
        $command_data['destino'] = $number;
        $command_data['channel'] = $extension_data["dial"];
        $command_data['description'] = $extension_data["description"];

	$result = $this->AsteriskManager_Originate($host,$user,$password,$command_data);
	if(!$result)
	    return false;
	else{
	    $message["message"] = "Calling from extension $extension to number $number";
	    return $message;
	}
    }

    /**
     * 
     * Function that returns the error message
     *
     * @return  string   Message error if had an error.
     */
    public function getError()
    {
        return $this->errMsg;
    }
}

?>