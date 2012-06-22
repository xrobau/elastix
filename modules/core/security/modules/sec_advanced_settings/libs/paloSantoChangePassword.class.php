<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-5                                               |
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
  $Id: paloSantoAdvancedSecuritySettings.class.php,v 1.1 2011-05-13 11:05:31 Estefanía Morán Meza emoran@palosanto.com Exp $ */
  
  
class paloSantoAdvancedSecuritySettings{
    var $_DB;
    var $errMsg;
    var $arrConf;

    function paloSantoAdvancedSecuritySettings($arrConf, &$pDB=null)
    {
	$this->arrConf = $arrConf;
        // Se recibe como parámetro una referencia a una conexión paloDB
	if(isset($pDB)){
	    if (is_object($pDB)) {
		$this->_DB =& $pDB;
		$this->errMsg = $this->_DB->errMsg;
	    } else {
		$dsn = (string)$pDB;
		$this->_DB = new paloDB($dsn);

		if (!$this->_DB->connStatus) {
		    $this->errMsg = $this->_DB->errMsg;
		    // debo llenar alguna variable de error
		} else {
		    // debo llenar alguna variable de error
		}
	    }
	}
    }
    
    function changeFreePBXPassword($fpbx_password, $arrConf)
    {
      //--------------------------- Begin Transaction --------------------------------
      //Paso #1: Actualizar la clave del usuario Admin.
      $this->_DB->beginTransaction();
      $resultUpdatePass = $this->updateFreePBXPasswordAdmin($fpbx_password);
      if(!$resultUpdatePass){
	  $this->_DB->rollBack();
          return false;
      }
      //Paso #2: Crear el usuario asteriskuser y asignarle el password ingresado.
      $resultCreateUser = $this->createAsteriskUser($fpbx_password);
      if(!$resultCreateUser){
	  $this->_DB->rollBack();
          return false;
      }
      //Paso #3: Actualizar los archivos de configuración.
      $resultUpdateConfFiles = $this->updateConfFiles($fpbx_password,$arrConf);
      if(!$resultUpdateConfFiles['result']){
	 $this->_DB->rollBack();
         return $arrResult;
      }
      $this->_DB->commit();
      //--------------------------- End Transaction --------------------------------
      return $resultUpdateConfFiles;
    }

    function updateFreePBXPasswordAdmin($fpbx_password)
    {
      $arrParam[] = $fpbx_password;
      $query = "UPDATE ampusers SET password_sha1=sha1(?) WHERE username = 'admin' ";

      $result=$this->_DB->genQuery($query,$arrParam);
      if($result == FALSE){
	  $this->errMsg = $this->_DB->errMsg;
      }
      return $result;
    }
    
   function createAsteriskUser($fpbx_password)
   {
        $query = "GRANT USAGE ON *.* TO 'asteriskuser'@'localhost' IDENTIFIED BY '$fpbx_password' ";

        $result=$this->_DB->genExec($query);
        if($result == FALSE){
            $this->errMsg = $this->_DB->errMsg;
        }
        return $result;
   }
    
   function updateConfFiles($fpbx_password,$arrConf){
      $arrUpdateFiles = null;
      if(is_array($arrConf['arr_conf_file']) && count($arrConf['arr_conf_file']) > 0){
	foreach($arrConf['arr_conf_file'] as $file){
	    $conf_file      = new paloConfig($file['path'],$file['name']," = ","[[:space:]]*=[[:space:]]*");
	    $param          = $file['pass_name'];
	    $arr_reemplazos = array("$param" => $fpbx_password);
	    $resultUpdate   = $conf_file->escribir_configuracion($arr_reemplazos);
	    if($resultUpdate)
		$arrUpdateFiles[] = $file['name'];
	    else{
	      $arrResult = array('result'=>false, 'arrUpdateFiles'=>$arrUpdateFiles);
	      return $arrResult;
	    }
	}
      }
      $arrResult = array('result'=>true);
      return $arrResult;
   }

   function updateStatusFreePBXFrontend($status_fpbx_frontend)
   {
      //Actualizar la clave ActivatedFreePBX.
      $pDBSettings = new paloDB($this->arrConf['elastix_dsn']["settings"]);
      return (set_key_settings($pDBSettings,"activatedFreePBX",$status_fpbx_frontend));
   }

   function isActivatedFreePBXFrontend()
   {
      $pDBSettings = new paloDB($this->arrConf['elastix_dsn']["settings"]);
      return (get_key_settings($pDBSettings,"activatedFreePBX"));
   }

}



?>