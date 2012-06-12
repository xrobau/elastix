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
    var $rutaFaxDispatch;
    var $rutaInittab;
    var $usuarioWeb;
    var $grupoWeb;
    var $_db;
    var $errMsg;

    function paloFax()
    {
        global $arrConf;
        
        $this->dirIaxmodemConf = "/etc/iaxmodem";
        $this->dirHylafaxConf  = "/var/spool/hylafax/etc";
        $this->rutaDB = "$arrConf[elastix_dbdir]/fax.db";
        $this->firstPort=40000;
        $this->rutaFaxDispatch = "/var/spool/hylafax/etc/FaxDispatch";
        $this->rutaInittab = "/etc/inittab";
        $this->usuarioWeb = "asterisk";
        $this->grupoWeb   = "asterisk";
        //instanciar clase paloDB
        $pDB = new paloDB("sqlite3:///".$this->rutaDB);
        if(!empty($pDB->errMsg)) {
                echo "$this->rutaDB: $pDB->errMsg <br>";
        } else{
           $this->_db = $pDB;
        }
    }

    function createFaxExtension($virtualFaxName, $extNumber, $extSecret, $destinationEmail, $CIDName="", $CIDNumber="",$countryCode, $areaCode)
    {
        // 1) Averiguar el numero de dispositivo que se puede usar y el numero de puerto
        $devId = $this->_getNextAvailableDevId();
        $nextPort=$this->_getNextAvailablePort(); 

        // 2) Creo la extension en la base de datos
        $this->_createFaxIntoDB($virtualFaxName, $extNumber, $extSecret, $destinationEmail, $devId, $CIDName, $CIDNumber, $nextPort,$countryCode, $areaCode);

        // 3) Create fax system
        $this->_createFaxSystem($devId, $nextPort, $extNumber, $extSecret, $CIDName, $CIDNumber, $destinationEmail, $countryCode, $areaCode);
    }

    function _createFaxSystem($devId, $nextPort, $extNumber, $extSecret, $CIDName, $CIDNumber, $destinationEmail, $countryCode, $areaCode)
    {
        $errMsg = "";

        // 1) Verificar que las 2 carpetas donde residen los archivos de configuracion son escribibles
        if(!is_writable($this->dirIaxmodemConf) or !is_writable($this->dirHylafaxConf)) {
            $errMsg = "The directories \"" . $this->dirIaxmodemConf. "\" and \"" . $this->dirHylafaxConf . "\" must be writeable.";
        }

        // 2) Escribir el archivo de configuracion de iaxmodem
        $this->_configureIaxmodem($devId, $nextPort, $extNumber, $extSecret, $CIDName, $CIDNumber);

        // 3) Escribir el archivo de configuracion de hylafax
        $this->_configureHylafax($devId, $destinationEmail, $CIDNumber, $CIDName, $countryCode, $areaCode);

        // 4) Escribo el inittab
        $this->_writeInittab($devId);
    }

    function getFaxList()
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn=array();
        //if ($db = sqlite3_open($this->rutaDB)) {
        $query  = "SELECT id, name, extension, secret, clid_name, clid_number, dev_id, date_creation, email, country_code, area_code FROM fax";
        $arrReturn = $this->_db->fetchTable($query, true);
        if($arrReturn==FALSE)
        {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
            //$result = sqlite3_query($db, $query);
/*
            if(isset($result))
            {
                while ($row = sqlite3_fetch_array($result)) {
                    $arrReturn[]=$row;
                }
            }
*/
        /*} else {
            $errMsg = $sqliteError;
        }*/

        return $arrReturn;
    }

    function getFaxById($id)
    {
        $errMsg="";
        $sqliteError='';
        // El id es mayor a cero?
        if($id<=0) return false;

        //if ($db = sqlite3_open($this->rutaDB)) {
        $query  = "SELECT id, name, extension, secret, clid_name, clid_number, dev_id, date_creation, email, port, country_code, area_code FROM fax WHERE id=$id";
        $arrReturn = $this->_db->getFirstRowQuery($query, true);
        if($arrReturn==FALSE)
        {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrReturn;
        /*    $result = @sqlite3_query($db, $query);
            return @sqlite3_fetch_array($result);
        } else {
            $errMsg = $sqliteError;
        }*/
    }
   
    function deleteFaxExtensionById($idFax)
    {
        // consulto a la DB para obtener el devId 
        $arrFax = $this->getFaxById($idFax);
        $devId  = $arrFax['dev_id'];
        //- OJO: Aqui estoy suponiendo que los dispositivos comenzaran en ttyIAX1
        if($devId>0) {
            // Elimino el archivo de configuracion de Iaxmodem        
            $archivoIaxmodem    = $this->dirIaxmodemConf . "/iaxmodem-cfg.ttyIAX" . $devId;
            exec("sudo -u root chmod 757 $this->dirIaxmodemConf");
            exec("sudo -u root chmod 646 $archivoIaxmodem");
            @unlink($archivoIaxmodem);    
            exec("sudo -u root chmod 755 $this->dirIaxmodemConf");

            // Elimino el archivo de configuracin de Hylafax
            $archivoHylafax     = $this->dirHylafaxConf . "/config.ttyIAX" . $devId;
            exec("sudo -u root chmod 757 $this->dirHylafaxConf");
            exec("sudo -u root chmod 646 $archivoHylafax");
            @unlink($archivoHylafax);    
            exec("sudo -u root chmod 755 $this->dirHylafaxConf");
 
            // Elimino las lineas respectivas en el archivo inittab
            $this->_deleteLinesFromInittab($devId);

            // actualizo DB
            $this->_deleteFaxFromDB($idFax);

            // regenero el archivo FaxDispatch
            $this->_writeFaxDispatch();

        } else {
            // Error
        }
    }

    // Esta funcion compara los archivos de configuracion de iaxmodem y hylafax
    // y sugiere un ID entero que puede usarse.
    // TODO: Debo hacer mejor manejo de errores
    function _getNextAvailableDevId()
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
    function _createFaxIntoDB($name, $extension, $secret, $email, $devId, $clidname, $clidnumber, $port,$countryCode, $areaCode)
    {
        $errMsg="";
        $dateNow=date("Y-m-d H:i:s");
        $query  = "INSERT INTO fax (name, extension, secret, clid_name, clid_number, dev_id, date_creation, email, port,country_code, area_code) ";
        $query .= "values ('$name','$extension', '$secret', '$clidname', '$clidnumber', '$devId', '$dateNow', '$email', '$port','$countryCode', '$areaCode')";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;

    }
   
    function _deleteFaxFromDB($idFax) {
        
        $query  = "DELETE FROM fax WHERE id=$idFax";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }

        return true;


    }

    function _getConfigFiles($folder, $filePrefix)
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

    function _getIaxcomContents($devId, $port, $iaxExtension, $iaxSecret, $CLIDName, $CLIDNumber)
    {
        $strContents =  "device          /dev/$devId\n" .
                        "owner           uucp:uucp\n" .
                        "mode            660\n" .
                        "port            $port\n" .
                        "refresh         300\n" .
                        "server          127.0.0.1\n" .
                        "peername        $iaxExtension\n" .
                        "secret          $iaxSecret\n" .
                        "cidname         $CLIDName\n" .
                        "cidnumber       $CLIDNumber\n" .
                        "codec           slinear";

        return $strContents;
    }

    function _getHylafaxContents($CLIDNumber, $CLIDName, $countryCode, $areaCode)
    {
        $strContents = 
                        "CountryCode:            $countryCode\n" .
                        "AreaCode:               $areaCode\n" .
                        "FAXNumber:              $CLIDNumber\n" .
                        "LongDistancePrefix:     1\n" .
                        "InternationalPrefix:    011\n" .
                        "DialStringRules:        etc/dialrules\n" .
                        "ServerTracing:          0xFFF\n" .
                        "SessionTracing:         0xFFF\n" .
                        "RecvFileMode:           0600\n" .
                        "LogFileMode:            0600\n" .
                        "DeviceMode:             0600\n" .
                        "RingsBeforeAnswer:      1\n" .
                        "SpeakerVolume:          off\n" .
                        "GettyArgs:              \"-h %l dx_%s\"\n" . 
                        "LocalIdentifier:        \"$CLIDName\"\n" .
                        "TagLineFont:            etc/lutRS18.pcf\n" .
                        "TagLineFormat:          \"From %%l|%c|Page %%P of %%T\"\n" .
                        "MaxRecvPages:           200\n" .
                        "#\n" .
                        "#\n" .
                        "# Modem-related stuff: should reflect modem command interface\n" .
                        "# and hardware connection/cabling (e.g. flow control).\n" .
                        "#\n" .
                        "ModemType:              Class1          # use this to supply a hint\n" .
                        "\n" .
                        "#\n" .
                        "# Enabling this will use the hfaxd-protocol to set Caller*ID\n" .
                        "#\n" .
                        "#ModemSetOriginCmd:     AT+VSID=\"%s\",\"%d\"\n" .
                        "\n" .
                        "#\n" .
                        "# If \"glare\" during initialization becomes a problem then take\n" .
                        "# the modem off-hook during initialization, and then place it\n" .
                        "# back on-hook when done.\n" .
                        "#\n" .
                        "#ModemResetCmds:        \"ATH1\\nAT+VCID=1\"       # enables CallID display\n" .
                        "#ModemReadyCmds:        ATH0\n" .
                        "\n" . 
                        "\n" .
                        "Class1AdaptRecvCmd:     AT+FAR=1\n" .
                        "Class1TMConnectDelay:   400             # counteract quick CONNECT response\n" .
                        "\n" .
                        "Class1RMQueryCmd:       \"!24,48,72,96\"  # enable this to disable V.17\n" .
                        "\n" .
                        "#\n" .
                        "# You'll likely want Caller*ID display (also displays DID) enabled.\n" .
                        "#\n" .
                        "ModemResetCmds:         AT+VCID=1       # enables CallID display\n" .
                        "\n" .
                        "#\n" .
                        "# If you are \"missing\" Caller*ID data on some calls (but not all)\n" .
                        "# and if you do not have adequate glare protection you may want to\n" .
                        "# not answer based on RINGs, but rather enable the CallIDAnswerLength\n" .
                        "# for NDID, disable AT+VCID=1 and do this:\n" .
                        "#\n" .
                        "#RingsBeforeAnswer: 0\n" .
                        "#ModemRingResponse: AT+VRID=1\n" .
                        "\n" .
                        "CallIDPattern:          \"NMBR=\"\n" .
                        "CallIDPattern:          \"NAME=\"\n" .
                        "CallIDPattern:          \"ANID=\"\n" .
                        "CallIDPattern:          \"NDID=\"\n" .
                        "#CallIDAnswerLength:    4\n" .
                        "# Uncomment these if you really want them, but you probably don't.\n" .
                        "#CallIDPattern:          \"DATE=\"\n" .
                        "#CallIDPattern:          \"TIME=\"\n" .
                        "FaxRcvdCmd:              bin/faxrcvd.php\n" .
                        "UseJobTSI:               true\n";

        return $strContents;
    }

    // TODO: Por ahora busco siempre el puerto mayor pero tambien tengo que
    //       buscar si existen huecos.
    function _getNextAvailablePort()
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

    // Esta funcion aniade unas lineas en el archivo inittab.
    // Debe buscar antes un bloque identificador, si no existe tal bloque
    // entonces aniade las lineas al final del archivo
    function _writeInittab($devId)
    {
        // Bloque identificador
        $strBloque = "# Don't remove or modify this comment. The following block is for fax setup.";
        $contenidoInittab = "";
        $bloqueEncontrado = false;
        $rutaInittabBackup = "/var/www/html/var/backups/inittab.old";

        // $strNuevasLineas  = "iax:2345:respawn:/usr/local/bin/iaxmodem ttyIAX &> /var/log/iaxmodem-ttyIAX$devId\n";
        // $strNuevasLineas .= "fax:2345:respawn:/usr/sbin/faxgetty ttyIAX$devId\n";

        // Nota: El id de la línea del inittab, que se encuentra compuesto de "fx$devIdHex" no puede ser de más
        //       de 4 caracteres. Es una limitante del inittab. Esto quiere decir que despues de poner el prefijo
        //       fx solo tenemos 2 caracteres más. Para aprovecharlos mejor usamos un número en hexadecimal en lugar
        //       de decimal. Esto nos deja 255 (FF) posibilidades o un número máximo de 255 faxes virtuales que se 
        //       pueden crear.

        $devIdHex = dechex($devId);
        $strNuevasLineas = "fx$devIdHex:2345:respawn:/usr/sbin/faxgetty ttyIAX$devId\n";

        if($fh=fopen($this->rutaInittab, "r")) {
            while(!feof($fh)) {
                $linea = fgets($fh, 10240);
                $contenidoInittab .= $linea;

                if(preg_match("/^$strBloque/", $linea)) {
                    $contenidoInittab .= $strNuevasLineas;                    
                    $bloqueEncontrado  = true;
                }
            }
            fclose($fh);
            if($bloqueEncontrado==false) {
                $contenidoInittab .= "\n$strBloque\n";
                $contenidoInittab .= $strNuevasLineas;
            }

            // Respaldamos el inittab antes de escribirlo
            @copy($this->rutaInittab, $rutaInittabBackup);

            // Ahora abrimos el inittab pero para escribirlo
            exec("sudo -u root chmod 646 $this->rutaInittab");
            if($fh=fopen($this->rutaInittab, "w")) {
                fwrite($fh, $contenidoInittab);
                fclose($fh);
            } else {
                // Error
            }
            exec("sudo -u root chmod 644 $this->rutaInittab");
        } else {
            // Error
        }      
    }

    function _deleteLinesFromInittab($devId)
    {
        $contenidoInittab = "";
        $rutaInittabBackup = "/var/www/html/var/backups/inittab.old";

        if($fh=fopen($this->rutaInittab, "r")) {
            while(!feof($fh)) {
                $linea = fgets($fh, 10240);

                if(!(preg_match("/^(iax|fx)[[:alnum:]]{1,2}:2345:respawn/", $linea) and preg_match("/ttyIAX$devId/", $linea))) {
                    $contenidoInittab .= $linea;
                }
            }
            fclose($fh);

            // Respaldamos el inittab antes de escribirlo
            @copy($this->rutaInittab, $rutaInittabBackup);

            // Ahora abrimos el inittab pero para escribirlo
            exec("sudo -u root chmod 646 $this->rutaInittab");
            if($fh=fopen($this->rutaInittab, "w")) {
                fwrite($fh, $contenidoInittab);
                fclose($fh);
            } else {
                // Error
            }
            exec("sudo -u root chmod 644 $this->rutaInittab");
        } else {
            // Error
        }
    }

    // TODO: Seria bueno que la funcion no tome parametros
    function _writeFaxDispatch()
    {
        $strFaxDispatch  = "SENDTO=root;\n" .
                           "FILETYPE=pdf;\n" .
                           "\n" .
                           "case \"\$DEVICE\" in\n";
        $arrFax = array();
        $arrFax = $this->getFaxList(); 
        if(is_array($arrFax) and count($arrFax)>0) {
           foreach($arrFax as $fax) {
               $strFaxDispatch .= "  ttyIAX" . $fax['dev_id'] . ") SENDTO=" . $fax['email'] . ";;\n";
            }
        }
        $strFaxDispatch .= "esac\n";

        // Ya tengo el contenido, ahora escribo el archivo
        // Lo sobreescribo nomas. Si no existe lo creo con touch
        if(!file_exists($this->rutaFaxDispatch)) {
            exec("sudo -u root touch $this->rutaFaxDispatch");
        }

        exec("sudo -u root chmod 646 $this->rutaFaxDispatch");            
        if($fh=fopen($this->rutaFaxDispatch, "w")) {
            fwrite($fh, $strFaxDispatch);
            fclose($fh);
        } else {
            // Error
        }
        exec("sudo -u root chmod 644 $this->rutaFaxDispatch");
    }

    function _configureIaxmodem($devId, $nextPort, $extNumber, $extSecret, $CIDName, $CIDNumber)
    {
        $nextIaxmodemConfFilename = $this->dirIaxmodemConf . "/iaxmodem-cfg.ttyIAX" . $devId;
        $contenidoArchivoIaxcomConf=$this->_getIaxcomContents("ttyIAX" . $devId, $nextPort, $extNumber, $extSecret, $CIDName, $CIDNumber);

        exec("sudo -u root touch $nextIaxmodemConfFilename");
        exec("sudo -u root chmod 646 $nextIaxmodemConfFilename");

        if($fh = fopen($nextIaxmodemConfFilename, "w")) {
            fwrite($fh, $contenidoArchivoIaxcomConf);
            fclose($fh);
        } else {
            // Error
        }

        // Debo reiniciar Iaxmodem?
    }

    function _configureHylafax($devId, $destinationEmail, $CIDNumber, $CIDName, $countryCode, $areaCode)
    {
        // Escribo el archivo FaxDispatch
        $this->_writeFaxDispatch();

        $nextHylafaxConfFilename = $this->dirHylafaxConf . "/config.ttyIAX" . $devId;
        $contenidoArchivoHylafaxConf=$this->_getHylafaxContents($CIDNumber, $CIDName, $countryCode, $areaCode);

        exec("sudo -u root touch $nextHylafaxConfFilename");
        exec("sudo -u root chmod 646 $nextHylafaxConfFilename");
        exec("sudo -u root chown uucp.uucp $nextHylafaxConfFilename");

        if($fh = fopen($nextHylafaxConfFilename, "w")) {
            fwrite($fh, $contenidoArchivoHylafaxConf);
            fclose($fh);
        } else {
            // Error
        }

        exec("sudo -u root chmod 644 $nextHylafaxConfFilename");
        
        // Debo reiniciar Hylafax
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
        $errMsg = "";
        
        // 1) Verificar que las 2 carpetas donde residen los archivos de configuracion son escribibles
        if(!is_writable($this->dirIaxmodemConf) or !is_writable($this->dirHylafaxConf)) {
            $errMsg = "The directories \"" . $this->dirIaxmodemConf. "\" and \"" . $this->dirHylafaxConf . "\" must be writeable.";
        }
        
       
        // 2) Editar la extension en la base de datos 
        $this->_editFaxInDB($idFax,$virtualFaxName, $extNumber, $extSecret, $destinationEmail, $devId, $CIDName, $CIDNumber, $port,$countryCode, $areaCode);

        // 3) Modificar el archivo de configuracion de iaxmodem
        $this->_configureIaxmodem($devId, $port, $extNumber, $extSecret, $CIDName, $CIDNumber);
        
        // 4) Modificar el archivo de configuracion de hylafax
        $this->_configureHylafax($devId, $destinationEmail, $CIDNumber, $CIDName, $countryCode, $areaCode);
    }

    function _editFaxInDB($idFax, $name, $extension, $secret, $email, $devId, $clidname, $clidnumber, $port,$countryCode, $areaCode) {
        $errMsg="";
        //if ($db = sqlite3_open($this->rutaDB)) {
        $query  = "UPDATE fax set
                        name='$name',
                        extension='$extension',
                        secret='$secret',
                        clid_name='$clidname',
                        clid_number='$clidnumber',
                        dev_id='$devId',
                        email='$email',
                        port='$port',
                        area_code='$areaCode',
                        country_code='$countryCode'
                    where id=$idFax;";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        /*} else {
            $errMsg = $sqliteError;
        }*/
    }

    function getConfigurationSendingFaxMail()
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn=-1;
        //if ($db = sqlite3_open($this->rutaDB)) {
        $query  = " select
                        remite,remitente,subject,content
                    from
                        configuration_fax_mail
                    where
                        id=1";

        $arrReturn = $this->_db->getFirstRowQuery($query, true);
        if($arrReturn==FALSE)
        {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
/*
            $result = @sqlite3_query($db, $query);
            if(count($result)>0){
                while ($row = @sqlite3_fetch_array($result)) {
                    $arrReturn=$row;
                }
            }
        } 
        else 
        {
            $errMsg = $sqliteError;
        }
*/
        return $arrReturn;
    }

    function setConfigurationSendingFaxMail($remite, $remitente, $subject, $content) {
        $errMsg="";
        $bExito = false;
        //if ($db = sqlite3_open($this->rutaDB)) {
        $query  = " update
                        configuration_fax_mail
                    set
                        remite='$remite',
                        remitente='$remitente',
                        subject='$subject',
                        content='$content'
                    where
                        id=1;";

        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
        }
        return $bExito; 
/*        } 
        else {
            $this->errMsg = $this->_db->errMsg;
        }*/
        return $bExito;
    }

    function restartFax() {
        exec("sudo -u root init q");
        exec("sudo -u root service generic-cloexec iaxmodem restart");
        exec("sudo -u root service generic-cloexec hylafax restart");
    }
}
?>
