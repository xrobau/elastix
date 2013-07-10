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

$elxPath="/usr/share/elastix";
include_once "$elxPath/libs/paloSantoACL.class.php";

class paloFax {

    public $dirIaxmodemConf;
    public $dirHylafaxConf;
    public $rutaDB;
    public $firstPort;
    public $_DB;
    public $errMsg;

    function paloFax(&$pDB)
    {
        global $arrConf;
        
        $this->dirIaxmodemConf = "/etc/iaxmodem";
        $this->dirHylafaxConf  = "/var/spool/hylafax/etc";
        $this->rutaDB = $arrConf['elastix_dsn']['elastix'];
        $this->firstPort=40000;
        
        if (is_object($pDB)) {
            $this->_DB=& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB= new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    function createFax($idUser,$country_code,$area_code,$clid_name,$clid_number,$extension,$secret,$email, $nextPort, $devId=null)
    {
		$pACL=new paloACL($this->_DB);
        // 1) Averiguar el numero de dispositivo que se puede usar
		if(is_null($devId))
			$devId = $this->getNewDevID();

		// 2) seteo la propiedades de fax que corresponde a port y dev_id. Esatas propiedades de setean en
		//user_properties
		$arrayProp = array("port"=>$nextPort,"dev_id"=>$devId);
		foreach($arrayProp as $key => $value){
			$bExito = $pACL->setUserProp($idUser,$key,$value,"fax");
			if($bExito===false)
			{
				$this->errMsg=$pACL->errMsg;
				break;
			}
		}

        // 3) Añadir el fax a los archivos de configuración
		if($bExito===false){
			return false;
		}else{
			 if($this->addFaxConfiguration($nextPort,$devId,$country_code,$area_code,$clid_name,$clid_number,$extension,$secret,$email))
				return true;
			 else{
				$this->errMsg=_tr("Error refreshing configuration")." ".$this->errMsg;
				return false;
			 }
		}
    }

	//esta funcion se utiliza para obetener todos los faxes configurados en el sistema
    function getFaxList($idOrganization=null,$idUser=null, $offset=null, $limit=null)
    {
        $OFFSET = $LIMIT = "";
        if($offset!=null) $OFFSET = "OFFSET $offset";
        if($limit!=null) $LIMIT = "LIMIT $limit";
		$param=array();
		$where="";
		$whereU="";

		if(isset($idUser)){
			if(preg_match("/^[[:digit:]]+$/","$idUser")){
				$whereU=" and id=?";
				$param[]=$idUser;
			}else{
				$this->errMsg = _tr("User ID is not valid");
				return false;
			}
		}

		if(isset($idOrganization)){
			if(preg_match("/^[[:digit:]]+$/","$idOrganization")){
				$where="where ag.id_organization=?";
				$param[]=$idOrganization;
			}else{
				$this->errMsg = _tr("Organization ID is not valid");
				return false;
			}
		}

		$query = "SELECT id, fax_extension as extension, username as email from acl_user JOIN user_properties on id=id_user where property='dev_id' $whereU INTERSECT select au.id, au.fax_extension as extension, au.username as email from acl_user as au JOIN acl_group ag on ag.id=au.id_group $where $LIMIT $OFFSET";

		$arrReturn = $this->_DB->fetchTable($query, true, $param);
		$arrtmp=$arrReturn;
        if($arrReturn === FALSE) {
            $this->errMsg = $this->_DB->errMsg;
			return $arrReturn; 
        }else if(count($arrReturn)==0){
			$this->errMsg = _tr("Don't exist fax created");
			return $arrReturn;
		}else{
			foreach ($arrtmp as $key => $valor) {
				$query="SELECT property, value from user_properties where category='fax' and id_user=?";
				$recordProp = $this->_DB->fetchTable($query, true,array($valor['id']));
				if(count($recordProp)>0){
					foreach($recordProp as $arrayProp){
							$arrReturn[$key][$arrayProp["property"]]=$arrayProp["value"];
					}
				}
			}
		}
        return $arrReturn;
    }

	function getTotalFax($idOrganization=null){
		$param=array();
		$where="";

		if(isset($idOrganization)){
			if(preg_match("/^[[:digit:]]+$/","$idOrganization")){
				$where="where ag.id_organization=?";
				$param[0]=$idOrganization;
			}else{
				$this->errMsg = _tr("Organization ID is not valid");
				return false;
			}
		}

		$query = "SELECT id from acl_user JOIN user_properties on id=id_user where property='dev_id' INTERSECT select au.id from acl_user as au JOIN acl_group ag on ag.id=au.id_group $where";
		$arrReturn = $this->_DB->getFirstRowQuery($query, false, $param);
		if($arrReturn === FALSE) {
			$this->errMsg = $this->_DB->errMsg;
			return $arrReturn;
		}else{
			return count($arrReturn);
		}
	}

    function getFaxById($id)
    {
		$pACL = new paloACL($this->_DB);
		$faxParameters=array("country_code","area_code","clid_name","clid_number","dev_id");

		$query = "SELECT id, name, fax_extension as extension, md5_password as secret, username as email from acl_user where id=?";
		$arrReturn = $this->_DB->fetchTable($query, true,array($id));
        if($arrReturn === FALSE) {
            $this->errMsg = $this->_DB->errMsg;
			return $arrReturn;
        }else if(count($arrReturn)==0){
			$this->errMsg = _tr("Don't exist fax created");
			return $arrReturn;
		}else{
			foreach($faxParameters as $key){
				$valor=$pACL->getUserProp($id,$key);
				if($valor!==false){
					$arrReturn[0][$key]=$valor;
				}
			}
		}
        return $arrReturn;
    }
   
    function deleteFax($devId)
    {
        return $this->deleteFaxConfiguration($devId);
    }

	function editFax($idUser,$country_code,$area_code,$clid_name,$clid_number,$extension,$secret,$email)
    {
		$pACL = new paloACL($this->_DB);
		$devId=$pACL->getUserProp($idUser,"dev_id");
		$Port=$pACL->getUserProp($idUser,"port");
        return $this->editFaxConfiguration($Port,$devId,$country_code,$area_code,$clid_name,$clid_number,$extension,$secret,$email);
    }
	

	private function getNewDevID()
    {
		$chars = "abcdefghijkmnpqrstuvwxyz23456789";
		$existDevId=false;
		do{
			srand((double)microtime()*1000000);
			$pass="";
			// Genero los 10 caracteres mas
			while (strlen($pass) < 3) {
					$num = rand() % 33;
					$tmp = substr($chars, $num, 1);
					$pass .= $tmp;
			}
		$existDevId = false; //$this->existsDevId($pass);
		}while ($existDevId);

		return $pass;
    }

	function existsDevId($devId){
		$query="Select count(id_user) from user_properties where property='dev_id' and value=?";
		$result=$this->_DB->getFirstRowQuery($query,false,array($devId));
		if($result===false){
			$this->errMsg=$this->_DB->errMsg;
			return true;
		}if($result[0]==1){
			return true;
		}else{
			//comprobamos que no exista en el archivo inittab un dev con ese id
			foreach (file('/etc/inittab') as $sLinea) {
				$cadena='/^f'.$devId.':2345:respawn/';
				if((preg_match("$cadena", $sLinea))){
					return true;
				}
			}
		}
		return false;
	}
   

    private function _getConfigFiles($folder, $filePrefix)
    {
        $arrReg    = array();
        $arrSalida = array();
        $pattern   = "^" . str_replace(".", "\.", $filePrefix) . "([[:alnum:]]+)";
    
        // TODO: Falta revisar si tengo permisos para revisar este directorio
    
        if($handle = opendir($folder)) {
            while (false !== ($file = readdir($handle))) {
                if(preg_match("/^(iaxmodem-cfg\.ttyIAX([[:alnum:]]+))/", $file, $arrReg)) {
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
    function getNextAvailablePort()
    {
        $arrPorts=array();

        // Tengo que abrir todos los archivos de configuracion de iaxmodem y
        // hacer una lista de todos los puertos asignados.

        if($handle = opendir($this->dirIaxmodemConf)) {
            while (false !== ($file = readdir($handle))) {
                if(preg_match("/^iaxmodem-cfg\.ttyIAX([[:alnum:]]+)/", $file)) {
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
			$this->errMsg = _tr("Don't exist directory iaxmodem");
            return false;
        }
    }

    function getFaxStatus()
    {
        $arrStatus = array();
        exec("/usr/bin/faxstat", $arrOutCmd);

        foreach($arrOutCmd as $linea) {
            if(preg_match("/^Modem (ttyIAX[[:alnum:]]{1,3})/", $linea, $arrReg)) {
                list($modem, $status) = explode(":", $linea);
                $arrStatus[$arrReg[1]] = $status; 
            }
        }

        return $arrStatus;
    }

    //Obtener estado en el momento de enviar Fax
    function getSendStatus($destine)
    {
        $arrStatus = array();
        $status = array();
        $cont = 0;
        
        exec("/usr/bin/faxstat -s", $arrOutCmd);

        foreach($arrOutCmd as $linea) {
                 if($linea==""||(preg_match("/^Modem/", $linea, $arrReg))||(preg_match("/^HylaFAX/", $linea, $arrReg))||(preg_match("/^JID/", $linea, $arrReg))) {
                 }else{
                        $tmpstatus = explode(" ",$linea);
                        $arrDestine = array_values(array_diff($tmpstatus, array('')));
                        if($arrDestine[4]==$destine){
                        $status["dial"][] = $arrDestine[6];
                        $status["jid"][] = $arrDestine[0];
                        $status["status"][]=$arrDestine[8]." ".$arrDestine[9]." ".$arrDestine[10];
               }

               }
            }

        return $status;
    }
    
    //Obtener El estado de un fax dado el jid
    function getStateFax($jid)
    {
        $arrStatus = array();
        $status = array();
        $cont = 0;

        exec("/usr/bin/faxstat -sdl output", $arrOutCmd);

        foreach($arrOutCmd as $linea) {
                 if($linea==""||(preg_match("/^Modem/", $linea, $arrReg))||(preg_match("/^HylaFAX/", $linea, $arrReg))||(preg_match("/^JID/", $linea, $arrReg))) {
                 }else{
                        $tmpstatus = explode(" ",$linea);
                        $arrDestine = array_values(array_diff($tmpstatus, array('')));
                        if($arrDestine[0]==$jid){
                           $status["state"][]=$arrDestine;
                        }

               }
            }

        return $status;
    }
     //Obtener el estado de todos los faxes enviados
    function setFaxMsg()
    {
        $arrStatus = array();
        $status = array();
        $cont = 0;

        exec("/usr/bin/faxstat -d", $arrOutCmd);

        foreach($arrOutCmd as $linea) {
                 if($linea==""||(preg_match("/^Modem/", $linea, $arrReg))||(preg_match("/^HylaFAX/", $linea, $arrReg))||(preg_match("/^JID/", $linea, $arrReg))) {
                 }else{
                        $tmpstatus = explode(" ",$linea);
                        $arrDestine = array_values(array_diff($tmpstatus, array('')));
                        $id = $arrDestine[0];
                        // if($arrDestine[0]==$jid){
                           $status["state"][$id]=$arrDestine;
                       // }

               }
            }
        //$status["jid"][]=$jid;
        return $status;
    }
    //Obtener el Estado de los IAX2
    function checkFaxStatus($destine){
        global $arrConf;
        
        $apDB = new paloDB($arrConf['elastix_dsn']['elastix']);
        $pACL = new paloACL($apDB);   
        $adestine = explode(",",$destine);   
        foreach ($adestine  as $destine){
            $query="select name from iax where cid_number=?";
            $arrReturn = $pACL->_DB->getFirstRowQuery($query,true,array($destine));     
            $status = array();
            $iaxname[] = $arrReturn['name'];
        }
 
        //   $arrReturn = $pACL->_DB->getFirstRowQuery($query,true,array($destine));     
        //   $status = array();
        //   $iaxname = $arrReturn['name'];
        foreach($iaxname as $iaxname2){
            exec("/usr/sbin/asterisk -rx 'iax2 show peer $iaxname2'", $output, $retval);

            foreach($output as $linea) {
                //if((preg_match("/^Status/", $linea, $arrReg))) {

                // }else{
                        $flag = explode("_", $iaxname2);
                        $tmpstatus = explode(" ",$linea);
                        $arrDestine = array_values(array_diff($tmpstatus, array('')));
                        $sizeArr = count($arrDestine);
                        if(isset($arrDestine[0]))

                        if($arrDestine[0]=="Status")
                            $status[$flag[1]]="Fax ".$arrDestine[2]."_". $iaxname2; 
                //      }
                    }
        }
       return $status;
    }

    function getConfigurationSendingFaxMail($id_user)
    {
		$arrayProp = array("fax_subject","fax_content");
		$pACL = new paloACL($this->_DB);
		$query="select name as remitente, username as remite from acl_user where id=?";
        $arrReturn = $this->_DB->getFirstRowQuery($query,true,array($id_user));
        if($arrReturn === FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }else if(count($arrReturn)==0){
			$this->errMsg = _tr("Don't exist configuration fax associated with this user");
		}else{
			foreach($arrayProp as $key){
				$valor=$pACL->getUserProp($id_user,$key);
				if($valor!==false){
					$arrReturn[$key]=$valor;
				}
			}
		}
        return $arrReturn;
    }

	//se actualiza los campos en user_properties con key fax_subject y fax_content pertenecientes
	//a la categoria fax_content
	//los campos anterioeres llamados remite y remitente se obtienen de los campos name y username de
	//la tabla acl_usr
    function setConfigurationSendingFaxMail($id_user, $subject, $content)
    {
        $bExito = false;
		$pACL = new paloACL($this->_DB);
		$arrayProp = array("fax_subject"=>$subject,"fax_content"=>$content);
		foreach($arrayProp as $key => $value){
			$bExito = $pACL->setUserProp($id,$key,$value,"fax");
			if($bExito===false)
			{
				break;
			}
		}
        return $bExito; 
    }

    /**
     * Procedimiento que llama al ayudante faxconfig para que modifique la
     * información de faxes virtuales creando uno nuevo con los datos dados
     * 
     * @return bool VERDADERO en caso de éxito, FALSO en error
     */
    function addFaxConfiguration($nextPort,$devId,$country_code,$area_code,
        $clid_name,$clid_number,$extension,$secret,$email)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper faxconfig add'.
            ' '.escapeshellarg($devId).
            ' '.escapeshellarg($nextPort).
            ' '.escapeshellarg($country_code).
            ' '.escapeshellarg($area_code).
            ' '.escapeshellarg($clid_number).
            ' '.escapeshellarg($extension).
            ' '.escapeshellarg($secret).
            ' '.escapeshellarg($email).
            ' '.escapeshellarg($clid_name).
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

	 /**
     * Procedimiento que llama al ayudante faxconfig para que modifique la
     * información de faxes virtuales creando uno nuevo con los datos dados
     *
     * @return bool VERDADERO en caso de éxito, FALSO en error
     */
    function editFaxConfiguration($nextPort,$devId,$country_code,$area_code,
        $clid_name,$clid_number,$extension,$secret,$email)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper faxconfig edit'.
            ' '.escapeshellarg($devId).
            ' '.escapeshellarg($nextPort).
            ' '.escapeshellarg($country_code).
            ' '.escapeshellarg($area_code).
            ' '.escapeshellarg($clid_number).
            ' '.escapeshellarg($extension).
            ' '.escapeshellarg($secret).
            ' '.escapeshellarg($email).
            ' '.escapeshellarg($clid_name).
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

	/**
     * Procedimiento que llama al ayudante faxconfig para que modifique la
     * información de faxes virtuales para borrar un fax dado su dev_id
     *
     * @return bool VERDADERO en caso de éxito, FALSO en error
     */
    function deleteFaxConfiguration($dev_id)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper faxconfig delete '.escapeshellarg($dev_id).'  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    function restartService(){
        $sComando ='/usr/bin/elastix-helper faxconfig restartService  2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    // esta funcion es utilizada para escribir los archivos
    // /etc/init/elastix_fax.config y /var/spool/hylafax/etc/FaxDispatch
    function writeFilesFax(){
        $sComando ='/usr/bin/elastix-helper faxconfig rewriteFileFax 2>&1';
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
