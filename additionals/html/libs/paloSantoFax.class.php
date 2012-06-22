<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: paloSantoFax.class.php,v 1.1.1.1 2007/03/23 00:13:58 elandivar Exp $ */

/*-
CREATE TABLE fax 
(
    clid_name varchar(60), 
    clid_number varchar(60), 
    date_creation varchar(20), 
    dev_id varchar(20), 
    email varchar(120), 
    extension varchar(20), 
    id INTEGER PRIMARY KEY, 
    name varchar(60), 
    port varchar(60), 
    secret varchar(20)
);

CREATE TABLE SysLog (
  syslogid INTEGER PRIMARY KEY ,
  logdate timestamp NOT NULL ,
  logtext varchar(255) NOT NULL 
);

CREATE TABLE configuration_fax_mail (
  id         integer      primary key,
  remite     varchar(255) NOT NULL,
  remitente  varchar(255) NOT NULL,
  subject    varchar(255) NOT NULL,
  content    varchar(255)
);
*/

class paloFax {

    var $dirIaxmodemConf;
    var $dirHylafaxConf;
    var $rutaDB;
    var $firstPort;
    //var $rutaFaxDispatch;
    //var $rutaInittab;
    //var $usuarioWeb;
    //var $grupoWeb;
    var $_db;
    var $errMsg;

    function paloFax()
    {
        global $arrConf;
        
        $this->dirIaxmodemConf = "/etc/iaxmodem";
        $this->dirHylafaxConf  = "/var/spool/hylafax/etc";
        $this->rutaDB = "$arrConf[elastix_dbdir]/fax.db";
        $this->firstPort=40000;
        //instanciar clase paloDB
        $pDB = new paloDB("sqlite3:///".$this->rutaDB);
        if(!empty($pDB->errMsg)) {
                echo "$this->rutaDB: $pDB->errMsg <br>";
        } else{
           $this->_db = $pDB;
        }
    }

    function createFaxExtension($virtualFaxName, $extNumber, $extSecret, 
        $destinationEmail, $CIDName="", $CIDNumber="", $countryCode, $areaCode)
    {
        // 1) Averiguar el numero de dispositivo que se puede usar y el numero de puerto
        $devId = $this->_getNextAvailableDevId();
        $nextPort=$this->_getNextAvailablePort(); 

        // 2) Creo la extension en la base de datos
        $bExito = $this->_createFaxIntoDB($virtualFaxName, $extNumber, 
            $extSecret, $destinationEmail, $devId, $CIDName, $CIDNumber, 
            $nextPort, $countryCode, $areaCode);
        if (!$bExito) return FALSE;

        // 3) Refrescar la configuración
        return $this->refreshFaxConfiguration();
    }

    function getFaxList()
    {
        $query  = 
            'SELECT id, name, extension, secret, clid_name, clid_number, '.
                'dev_id, date_creation, email, country_code, area_code '.
            'FROM fax';
        $arrReturn = $this->_db->fetchTable($query, true);
        if($arrReturn == FALSE) {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
    }

    function getFaxById($id)
    {
        // El id es mayor a cero?
        if ($id <= 0) return false;

        $arrReturn = $this->_db->getFirstRowQuery(
            'SELECT id, name, extension, secret, clid_name, clid_number, '.
                'dev_id, date_creation, email, port, country_code, area_code '.
            'FROM fax WHERE id = ?',
            true, array($id));
        if($arrReturn == FALSE) {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
    }
   
    function deleteFaxExtensionById($idFax)
    {
        $this->_deleteFaxFromDB($idFax);
        return $this->refreshFaxConfiguration();
    }

    // Esta funcion compara los archivos de configuracion de iaxmodem y hylafax
    // y sugiere un ID entero que puede usarse.
    // TODO: Debo hacer mejor manejo de errores
    private function _getNextAvailableDevId()
    {
        $arrConfIaxmodem = $this->_getConfigFiles($this->dirIaxmodemConf, "iaxmodem-cfg.ttyIAX");
        $arrConfHylafax  = $this->_getConfigFiles($this->dirHylafaxConf, "config.ttyIAX");

        if(is_array($arrConfIaxmodem) and is_array($arrConfHylafax)) {
            $arrIds = array_merge($arrConfIaxmodem, $arrConfHylafax);
            sort($arrIds);

            $lastId = 0;
            $id = 0;
            foreach($arrIds as $id) {
                $incremento = $id - $lastId;
                //Si hubo salto >1 significa q hay al menos un eliminado => retornar ese hueco
                if($incremento>1)
                    return $lastId+1;

                $lastId = $id;
            }
            //Si no hubo hueco retorna el ultimo +1
            return $id+1;
        } else {
            return 0;
        }
    }

    // TODO: Hacer mejor manejo de errores 
    private function _createFaxIntoDB($name, $extension, $secret, $email,
        $devId, $clidname, $clidnumber, $port, $countryCode, $areaCode)
    {
        $bExito = $this->_db->genQuery(
            'INSERT INTO fax (name, extension, secret, clid_name, clid_number, '.
                'dev_id, date_creation, email, port, country_code, area_code) '.
                'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array($name, $extension, $secret, $clidname, $clidnumber, $devId, 
                date('Y-m-d H:i:s'), $email, $port, $countryCode, $areaCode));
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }
   
    private function _deleteFaxFromDB($idFax)
    {
        $bExito = $this->_db->genQuery(
            'DELETE FROM fax WHERE id = ?', 
            array($idFax));
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    private function _getConfigFiles($folder, $filePrefix)
    {
        $arrReg    = array();
        $arrSalida = array();
        $pattern   = "^" . str_replace(".", "\.", $filePrefix) . "([[:digit:]]+)";
    
        // TODO: Falta revisar si tengo permisos para revisar este directorio
    
        if($handle = opendir($folder)) {
            while (false !== ($file = readdir($handle))) {
                if(preg_match("/^(iaxmodem-cfg\.ttyIAX([[:digit:]]+))/", $file, $arrReg)) {
                    $arrSalida[$arrReg[1]] = $arrReg[2];
                }
            }
            return $arrSalida;
        } else {
            return false;
        }
    }

    // TODO: Por ahora busco siempre el puerto mayor pero tambien tengo que
    //       buscar si existen huecos.
    private function _getNextAvailablePort()
    {
        $arrPorts=array();

        // Tengo que abrir todos los archivos de configuracion de iaxmodem y
        // hacer una lista de todos los puertos asignados.

        if($handle = opendir($this->dirIaxmodemConf)) {
            while (false !== ($file = readdir($handle))) {
                if(preg_match("/^iaxmodem-cfg\.ttyIAX([[:digit:]]+)/", $file)) {
                    // Abro el archivo $file
                    if($fh=@fopen("$this->dirIaxmodemConf/$file", "r")) {
                        while($linea=fgets($fh, 10240)) {
                            if(preg_match("/^port[[:space:]]+([[:digit:]]+)/", $linea, $arrReg)) {
                                $arrPorts[] = $arrReg[1];
                            }
                        }
                        fclose($fh);
                    }
                }
            }

            //- Hasta este punto ya he obtenido una lista de puertos usados
            //- y se encuentran almacenados en el arreglo $arrPorts

            if(is_array($arrPorts) and count($arrPorts)>0) {
                // Encuentro el puerto mayor            
                sort($arrPorts);
                $maxPuerto=array_pop($arrPorts);
                if($maxPuerto>=$this->firstPort) {
                    $puertoDisponible=$maxPuerto+1;
                } else {
                    $puertoDisponible=$this->firstPort;
                }
            } else {
                $puertoDisponible=$this->firstPort;
            }

            return $puertoDisponible;
        } else {
            return false;
        }
    }

    function getFaxStatus()
    {
        $arrStatus = array();
        exec("/usr/bin/faxstat", $arrOutCmd);

        foreach($arrOutCmd as $linea) {
            if(preg_match("/^Modem (ttyIAX[[:digit:]]{1,3})/", $linea, $arrReg)) {
                list($modem, $status) = explode(":", $linea);
                $arrStatus[$arrReg[1]] = $status; 
            }
        }

        return $arrStatus;
    }

    function editFaxExtension($idFax,$virtualFaxName, $extNumber, $extSecret, $destinationEmail, $CIDName, $CIDNumber, $devId, $port,$countryCode, $areaCode)
    {
        // 2) Editar la extension en la base de datos 
        $bExito = $this->_editFaxInDB($idFax,$virtualFaxName, $extNumber, $extSecret, $destinationEmail, $devId, $CIDName, $CIDNumber, $port,$countryCode, $areaCode);
        if (!$bExito) return FALSE;
        return $this->refreshFaxConfiguration();
    }

    private function _editFaxInDB($idFax, $name, $extension, $secret, $email, 
        $devId, $clidname, $clidnumber, $port, $countryCode, $areaCode)
    {
        $bExito = $this->_db->genQuery(
            'UPDATE fax SET name = ?, extension = ?, secret = ?, clid_name = ?, '.
                'clid_number = ?, dev_id = ?, email = ?, port = ?, '.
                'area_code = ?, country_code = ? '.
            'WHERE id = ?', 
            array($name, $extension, $secret, $clidname, $clidnumber, $devId, 
                $email, $port, $areaCode, $countryCode, $idFax));
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return TRUE;
    }

    function getConfigurationSendingFaxMail()
    {
        $arrReturn = $this->_db->getFirstRowQuery(
            'SELECT remite, remitente, subject, content '.
            'FROM configuration_fax_mail WHERE id = 1', true);
        if($arrReturn == FALSE) {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
    }

    function setConfigurationSendingFaxMail($remite, $remitente, $subject, $content)
    {
        $bExito = false;
        $bExito = $this->_db->genQuery(
            'UPDATE configuration_fax_mail SET remite = ?, remitente = ?, '.
                'subject = ?, content = ? WHERE id = 1',
            array($remite, $remitente, $subject, $content));
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
        }
        return $bExito; 
    }

    /**
     * Procedimiento que llama al ayudante faxconfig para que modifique la
     * información de faxes virtuales para que se ajuste a lo almacenado en la
     * base de datos.
     * 
     * @return bool VERDADERO en caso de éxito, FALSO en error
     */
    function refreshFaxConfiguration()
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper faxconfig --refresh 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }
}
?>