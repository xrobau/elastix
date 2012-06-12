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
CREATE TABLE info_fax_recvq
(
    id           INTEGER  PRIMARY KEY,
    pdf_file    varchar(255)   NOT NULL DEFAULT '',
    modemdev     varchar(255)   NOT NULL DEFAULT '',
    status       varchar(255)   NOT NULL DEFAULT '',
    commID       varchar(255)   NOT NULL DEFAULT '',
    errormsg     varchar(255)   NOT NULL DEFAULT '',
    company_name varchar(255)   NOT NULL DEFAULT '',
    company_fax  varchar(255)   NOT NULL DEFAULT '',
    fax_destiny_id       INTEGER NOT NULL DEFAULT 0,
    date	 timestamp 	NOT NULL ,
    FOREIGN KEY (fax_destiny_id)   REFERENCES fax(id)
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
        $this->dirIaxmodemConf = "/etc/iaxmodem";
        $this->dirHylafaxConf  = "/var/spool/hylafax/etc";
        $this->rutaDB="/var/www/db/fax.db";
        $this->firstPort=40000;
        $this->rutaFaxDispatch = "/var/spool/hylafax/etc/FaxDispatch";
        $this->rutaInittab = "/etc/inittab";
        $this->usuarioWeb = "asterisk";
        $this->grupoWeb   = "asterisk";
        //instanciar clase paloDB
        $pDB = new paloDB("sqlite3:///".$this->rutaDB);
	if(!empty($pDB->errMsg)) {
            echo "$pDB->errMsg <br>";
	}else{
	   $this->_db = $pDB;
	}
    }

    function createFaxExtension($virtualFaxName, $extNumber, $extSecret, $destinationEmail, $CIDName="", $CIDNumber="")
    {
        $errMsg = "";
        
        // 1) Verificar que las 2 carpetas donde residen los archivos de configuracion son escribibles
        if(!is_writable($this->dirIaxmodemConf) or !is_writable($this->dirHylafaxConf)) {
            $errMsg = "The directories \"" . $this->dirIaxmodemConf. "\" and \"" . $this->dirHylafaxConf . "\" must be writeable.";
        }
        
        // 2) Averiguar el numero de dispositivo que se puede usar y el numero de puerto
        $devId = $this->_getNextAvailableDevId();
        $nextPort=$this->_getNextAvailablePort(); 
        
        // 3) Creo la extension en la base de datos 
        $this->_createFaxIntoDB($virtualFaxName, $extNumber, $extSecret, $destinationEmail, $devId, $CIDName, $CIDNumber, $nextPort);
        
        // 4) Escribir el archivo de configuracion de iaxmodem
        $this->_configureIaxmodem($devId, $nextPort, $extNumber, $extSecret, $CIDName, $CIDNumber);
        
        // 5) Escribir el archivo de configuracion de hylafax
        $this->_configureHylafax($devId, $destinationEmail, $CIDNumber, $CIDName, "593", "04");
        
        // 6) Escribo el inittab
        $this->_writeInittab($devId);
        
        // 7) Acciones finales
        exec("sudo -u root init q");
        exec("sudo -u root service iaxmodem restart");
        exec("sudo -u root service hylafax restart");
    }

    function getFaxList()
    {
        $errMsg="";
		$sqliteError='';
		$arrReturn=array();
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "SELECT id, name, extension, secret, clid_name, clid_number, dev_id, date_creation, email FROM fax";
            $result = sqlite3_query($db, $query);
            while ($row = sqlite3_fetch_array($result)) {
                $arrReturn[]=$row;
            }
        } else {
            $errMsg = $sqliteError;
        }

        return $arrReturn;
    }

    function getFaxById($id)
    {
        $errMsg="";
        $sqliteError='';
        // El id es mayor a cero?
        if($id<=0) return false;

        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "SELECT id, name, extension, secret, clid_name, clid_number, dev_id, date_creation, email, port FROM fax WHERE id=$id";
            $result = @sqlite3_query($db, $query);
            return @sqlite3_fetch_array($result);
        } else {
            $errMsg = $sqliteError;
        }
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
            $this->_deleteLinesFromInittab($idFax); 

            // actualizo DB
            $this->_deleteFaxFromDB($idFax);

            // regenero el archivo FaxDispatch
            $this->_writeFaxDispatch();

            // Reinicio los servicios
            
            exec("sudo -u root service iaxmodem restart");
            exec("sudo -u root service hylafax restart");
            exec("sudo -u root init q");

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
            foreach($arrIds as $id) {
                $incremento = $id - $lastId;
                if($incremento>=2) {
                    break;
                }
                $lastId = $id;
            } 
            return $id+1;
    
        } else {
            return 0;
        }
    }
   
    // TODO: Hacer mejor manejo de errores 
    function _createFaxIntoDB($name, $extension, $secret, $email, $devId, $clidname, $clidnumber, $port)
    {
        $errMsg="";
        $dateNow=date("Y-m-d H:i:s");
        $query  = "INSERT INTO fax (name, extension, secret, clid_name, clid_number, dev_id, date_creation, email, port) ";
        $query .= "values ('$name','$extension', '$secret', '$clidname', '$clidnumber', '$devId', '$dateNow', '$email', '$port')";
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
                if(ereg("^(iaxmodem-cfg\.ttyIAX([[:digit:]]+))", $file, $arrReg)) {
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
                        "#CallIDPattern:          \"TIME=\"\n";

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
                if(ereg("^iaxmodem-cfg\.ttyIAX([[:digit:]]+)", $file)) {
                    // Abro el archivo $file
                    if($fh=@fopen("$this->dirIaxmodemConf/$file", "r")) {
                        while($linea=fgets($fh, 10240)) {
                            if(ereg("^port[[:space:]]+([[:digit:]]+)", $linea, $arrReg)) {
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

                if(ereg("^$strBloque", $linea)) {
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

                if(!(ereg("^(iax|fx)[[:alnum:]]{1,2}:2345:respawn", $linea) and ereg("ttyIAX$devId", $linea))) {
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
        $contenidoArchivoHylafaxConf=$this->_getHylafaxContents($CIDNumber, $CIDName, "593", "04");

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
            if(ereg("^Modem (ttyIAX[[:digit:]]{1,3})", $linea, $arrReg)) {
                list($modem, $status) = split(":", $linea);
                $arrStatus[$arrReg[1]] = $status; 
            }
        }

        return $arrStatus;
    }


    function editFaxExtension($idFax,$virtualFaxName, $extNumber, $extSecret, $destinationEmail, $CIDName, $CIDNumber, $devId, $port)
    {
        $errMsg = "";
        
        // 1) Verificar que las 2 carpetas donde residen los archivos de configuracion son escribibles
        if(!is_writable($this->dirIaxmodemConf) or !is_writable($this->dirHylafaxConf)) {
            $errMsg = "The directories \"" . $this->dirIaxmodemConf. "\" and \"" . $this->dirHylafaxConf . "\" must be writeable.";
        }
        
       
        // 2) Editar la extension en la base de datos 
        $this->_editFaxInDB($idFax,$virtualFaxName, $extNumber, $extSecret, $destinationEmail, $devId, $CIDName, $CIDNumber, $port);

        // 3) Modificar el archivo de configuracion de iaxmodem
        $this->_configureIaxmodem($devId, $port, $extNumber, $extSecret, $CIDName, $CIDNumber);
        
        // 4) Modificar el archivo de configuracion de hylafax
        $this->_configureHylafax($devId, $destinationEmail, $CIDNumber, $CIDName, "593", "04");
        
        
        // 5) Acciones finales
        exec("sudo -u root init q");
        exec("sudo -u root service iaxmodem restart");
        exec("sudo -u root service hylafax restart");
    }

 
    function _editFaxInDB($idFax, $name, $extension, $secret, $email, $devId, $clidname, $clidnumber, $port) {
        $errMsg="";
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "UPDATE fax set 
                            name='$name', 
                            extension='$extension',
                            secret='$secret',
                            clid_name='$clidname',
                            clid_number='$clidnumber',
                            dev_id='$devId',
                            email='$email',
                            port='$port' 
                        where id=$idFax;";
            $bExito = $this->_db->genQuery($query);
        	if (!$bExito) {
            	$this->errMsg = $this->_db->errMsg;
            	return false;
        	}
            
            
        } else {
            $errMsg = $sqliteError;
        }
    }

    function obtener_faxes($company_name,$company_fax,$fecha_fax,$offset,$cantidad)
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn=array();
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "
                    SELECT 
                        r.pdf_file,r.modemdev,r.commID,r.errormsg,r.company_name,r.company_fax,r.fax_destiny_id,r.date, f.name destiny_name,f.extension destiny_fax
                    FROM 
                        info_fax_recvq r inner join fax f on f.id = r.fax_destiny_id
                    where 
                        company_name like '%$company_name%' and company_fax like '%$company_fax%' and date like '%$fecha_fax%'
                    order by 
                        r.id desc 
                    limit 
                        $cantidad offset $offset
                    ";

            $result = @sqlite3_query($db, $query);
            if(count($result)>0){
                while ($row = @sqlite3_fetch_array($result)) {
                    $arrReturn[]=$row;
                }
            }
        } 
        else 
        {
            $errMsg = $sqliteError;
         }

        return $arrReturn;
    }

    function obtener_cantidad_faxes($company_name,$company_fax,$fecha_fax)
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn=-1;
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = "
                    select count(*) cantidad from (SELECT pdf_file,modemdev,commID,errormsg,company_name,company_fax,fax_destiny_id,date 
                    FROM info_fax_recvq
                    where company_name like '%$company_name%' and company_fax like '%$company_fax%' and date like '%$fecha_fax%')
                      ";

            $result = @sqlite3_query($db, $query);
            if(count($result)>0){
                while ($row = @sqlite3_fetch_array($result)) {
                    $arrReturn=$row['cantidad'];
                }
            }
        } 
        else 
        {
            $errMsg = $sqliteError;
        }

        return $arrReturn;
    }

    function getConfigurationSendingFaxMail()
    {
        $errMsg="";
        $sqliteError='';
        $arrReturn=-1;
        if ($db = sqlite3_open($this->rutaDB)) {
            $query  = " select 
                            remite,remitente,subject,content
                        from 
                            configuration_fax_mail
                        where 
                            id=1";

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

        return $arrReturn;
    }

    function setConfigurationSendingFaxMail($remite, $remitente, $subject, $content) {
        $errMsg="";
        $bExito = false;
        if ($db = sqlite3_open($this->rutaDB)) {
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
        } 
        else {
            $this->errMsg = $this->_db->errMsg;
        }
        return $bExito;
    }

    function deleteInfoFaxFromDB($pdfFileInfoFax) {
        
        $query  = "DELETE FROM info_fax_recvq WHERE pdf_file='$pdfFileInfoFax'";
        $bExito = $this->_db->genQuery($query);
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }

    function deleteInfoFaxFromPathFile($pdfFileInfoFax) {
        $path = "/var/www/html/faxes/recvq";
        $file = "$path/$pdfFileInfoFax";
        return unlink($file);
    }
}


//IMPLEMENTACION DE VISOR DE FAXES CON XAJAX EN EL MODULO EXTRAS
function ajax_faxes($arrConfig)
{
    $base_dir=dirname($_SERVER['SCRIPT_NAME']);
    if($base_dir=="/")
        $base_dir="";

    include_once $base_dir.$arrConfig['xajax_path_lib']."xajax.inc.php";
//     echo $base_dir.$arrConfig['xajax_path_lib']."xajax.inc.php";
     //instanciamos el objeto de la clase xajax
    $xajax = new xajax();
    //asociamos la función creada anteriormente al objeto xajax
    $xajax->registerFunction("faxes");
    $xajax->registerFunction("deleteFaxes");
//     if($xajax->canProcessRequests()){        
        //El objeto xajax tiene que procesar cualquier petición        
        $xajax->processRequests();  
//     }
    //En el {$javascript_xajax} indicamos al objeto xajax se encargue de generar el javascript necesario
    $javascript_xajax = $xajax->printJavascript($base_dir.$arrConfig['xajax_path_lib'],"xajax_js/xajax.js");
    return $javascript_xajax;
}

function faxes($company_name,$company_fax,$fecha_fax,$inicio_paginacion,$accion){
    $tamanio_busqueda = 20;
    $oFax = new paloFax(); 
    $cantidad_faxes = $oFax->obtener_cantidad_faxes($company_name,$company_fax,$fecha_fax);
    switch($accion)
    {
//         case 'search':
//             $offset = 0;
//             break;
        case 'next':
            $offset = $inicio_paginacion + $tamanio_busqueda;
            break;
        case 'previous':
            $offset = $inicio_paginacion - $tamanio_busqueda ;
            break;
//         case 'start':
//             $offset = 0;
//             break;
        case 'end':
            $pagina = floor($cantidad_faxes/$tamanio_busqueda);
            $offset = $pagina * $tamanio_busqueda; 
            break;
        default: //accion=search,start
            $offset = 0;
            break;
    } 
   
    
    $arr_faxes = $oFax->obtener_faxes($company_name,$company_fax,$fecha_fax,$offset,$tamanio_busqueda);

    $html_faxes = html_faxes($arr_faxes);
    $html_paginacion = html_paginacion_faxes($offset,$cantidad_faxes,$tamanio_busqueda,"crm/themes/Sugar/images");

    $respuesta = new xajaxResponse();
    $respuesta->addAssign("td_paginacion","innerHTML",$html_paginacion);
    $respuesta->addAssign("td_contenido","innerHTML",$html_faxes);

   //tenemos que devolver la instanciación del objeto xajaxResponse
    return $respuesta;
}

function html_faxes($arr_faxes)
{ 
    global $arrLang;
    $self=dirname($_SERVER['SCRIPT_NAME']);
    if($self=="/")
      $self="";

    $nodoTablaInicio = "<table border='0' cellspacing='0' cellpadding='0' width='100%' align='center'>
                            <tr class='table_title_row'>
                                <td class='table_title_row'><input type='button' name='faxes_delete' class='button' value='".$arrLang['Delete']."' onclick=\"if(confirmSubmit('{$arrLang["Are you sure you wish to delete fax (es)?"]}')) elimimar_faxes();\" /></td>
                                <td class='table_title_row'>".$arrLang['File']."</td>
                                <td class='table_title_row'>".$arrLang['Company Name']."</td>
                                <td class='table_title_row'>".$arrLang['Company Fax']."</td>
                                <td class='table_title_row'>".$arrLang['Fax Destiny']."</td>
                                <td class='table_title_row'><center>".$arrLang['Fax Date']."</center></td>
                            </tr>\n";
    $nodoTablaFin    = "</table>";
    $nodoContenido ="";

    if(is_array($arr_faxes)&& count($arr_faxes)>0)
    {
        foreach($arr_faxes as $key => $fax)
        {
            $nodoContenido .= "<tr style='background-color: rgb(255, 255, 255);' onmouseover="."this.style.backgroundColor='#f2f2f2';"." onmouseout="."this.style.backgroundColor='#ffffff';".">\n";
            $nodoContenido .= " <td class='table_data'><input type='checkbox' name='faxpdf_".$fax['pdf_file']."' id='faxpdf_".$fax['pdf_file']."' /></td>\n";
            $nodoContenido .= " <td class='table_data'><a href='".$self."/faxes/recvq/".$fax['pdf_file']."'".">".$fax['pdf_file']."</a></td>\n";
            $nodoContenido .= " <td class='table_data'>".$fax['company_name']."</td>\n";
            $nodoContenido .= " <td class='table_data'>".$fax['company_fax']."</td>\n";
            $nodoContenido .= " <td class='table_data'>".$fax['destiny_name']." - ".$fax['destiny_fax']."</td>\n";
            $nodoContenido .= " <td class='table_data'><center>".$fax['date']."</center></td>\n";
            $nodoContenido .= "</tr>\n";
        }
    }
    else
    {
         $nodoContenido .= "<tr><td colspan='6'><center>".$arrLang['No Data Found']."</center></td></tr>";
    }
    return $nodoTablaInicio.$nodoContenido.$nodoTablaFin;
}

function html_paginacion_faxes($regPrimeroMostrado,$regTotal,$tamanio_busqueda,$ruta_image='images')
{
    global $arrLang;
    
    if($regTotal <= $regPrimeroMostrado + $tamanio_busqueda)
        $regUltimoMostrado = $regTotal;
    else
        $regUltimoMostrado = $regPrimeroMostrado + $tamanio_busqueda;
    
    $pagTotal = ($regTotal / $tamanio_busqueda);
    $pagActual= ($regPrimeroMostrado / $tamanio_busqueda) + 1;

    
    if($pagActual > 1){
        $parteIzquierda  = "<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('start');   "."><img src='$ruta_image/start.gif' width='13' height='11' alt='' border='0' align='absmiddle' /></a>&nbsp;".$arrLang['Start']."&nbsp;";
        $parteIzquierda .= "<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('previous');"."><img src='$ruta_image/previous.gif' width='8' height='11' alt='' border='0' align='absmiddle' /></a>&nbsp;".$arrLang['Previous'];
    }
    else{
        $parteIzquierda  = "<img src='$ruta_image/start_off.gif' width='13'   height='11' alt='' align='absmiddle' />&nbsp;".$arrLang['Start']."&nbsp;";
        $parteIzquierda .= "<img src='$ruta_image/previous_off.gif' width='8' height='11' alt='' align='absmiddle' />&nbsp;".$arrLang['Previous'];
    }

    $search_title = "(".($regPrimeroMostrado + 1)." - ".$regUltimoMostrado." of ".$regTotal.")";
    $parteCentro  = "&nbsp;<span class='pageNumbers'>".$search_title."</span> 
     <input type='hidden' name='primer_registro_mostrado_paginacion' id='primer_registro_mostrado_paginacion' value='".$regPrimeroMostrado."' />
     <input type='hidden' name='ultimo_registro_mostrado_paginacion' id='ultimo_registro_mostrado_paginacion' value='".$regUltimoMostrado."' />
     <input type='hidden' name='total_registros_paginacion'          id='total_registros_paginacion'          value='".$regTotal."' />"; 

    if($pagActual < $pagTotal){
        $parteDerecha  = $arrLang['Next']."&nbsp;<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('next');"."><img src='$ruta_image/next.gif' width='8'  height='11' alt='' border ='0' align='absmiddle' /></a>&nbsp;";
        $parteDerecha .= $arrLang['End']."&nbsp;<a href='javascript:void(0);' onclick="."javascript:buscar_faxes_ajax('end');  "."><img src='$ruta_image/end.gif'  width='13' height='11' alt='' border ='0' align='absmiddle' /></a>";
    }
    else{
        $parteDerecha  = $arrLang['Next']."&nbsp;<img src='$ruta_image/next_off.gif' width='8'  height='11' alt='' align='absmiddle' />&nbsp;";
        $parteDerecha .= $arrLang['End']."&nbsp;<img src='$ruta_image/end_off.gif'  width='13' height='11' alt='' align='absmiddle' />";
    }
    return " <table class='table_navigation_text' align='center' cellspacing='0' cellpadding='0' width='100%' border='0'
                <tr><td align='right'>".$parteIzquierda.$parteCentro.$parteDerecha."</td></tr></table>";
}

function deleteFaxes($csv_files,$company_name,$company_fax,$fecha_fax,$inicio_paginacion)
{
    global $arrLang;
    $arrFaxes = explode(",",$csv_files); 
    $oFax = new paloFax(); 
    $respuesta = new xajaxResponse();

    if(is_array($arrFaxes) && count($arrFaxes) > 0){
        foreach($arrFaxes as $key => $pdf_file){
            if($oFax->deleteInfoFaxFromDB($pdf_file)){
                if($oFax->deleteInfoFaxFromPathFile($pdf_file)){
                    $respuesta = faxes($company_name,$company_fax,$fecha_fax,$inicio_paginacion,"search");
                }
                else{
                    $respuesta->addAlert($arrLang["Unable to eliminate pdf file from the path."]." /var/www/html/faxes/recvq/");
                }
            }
            else{
                $respuesta->addAlert($arrLang["Unable to eliminate pdf file from the database."]);
            }
        }
    }
    return $respuesta;
}
?>