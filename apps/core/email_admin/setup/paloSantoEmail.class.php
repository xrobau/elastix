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
  $Id: paloSantoEmail.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

if (isset($arrConf['basePath'])) {
    include_once($arrConf['basePath'] . "/libs/paloSantoDB.class.php");
    include_once($arrConf['basePath'] . "/libs/paloSantoConfig.class.php");
    include_once($arrConf['basePath'] . "/libs/misc.lib.php");
    include_once($arrConf['basePath'] . "/libs/cyradm.php");
} else {
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoConfig.class.php");
    include_once("libs/misc.lib.php");
    include_once("libs/cyradm.php");
}


class paloEmail {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloEmail(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
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

    /**
     * Procedimiento para obtener el listado de los dominios existentes. Si
     * se especifica el nombre de dominio, el listado contendrá únicamente el dominio
     * indicado por su respectivo nombre. De otro modo, se listarán todos los dominios.
     *
     * @param int   $id_domain    Si != NULL, indica el id del dominio a recoger
     *
     * @return array    Listado de dominios en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, domain_name),
     *      ...
     *  )
     */
    function getDomains($idOrganization = NULL,$domainName=null)
    {
        $arr_result = FALSE;
        $where="";
        $arrParams = array();
        if (!is_null($idOrganization) && !preg_match('/^[[:digit:]]+$/', $idOrganization)) {
            $this->errMsg = _tr("Organization ID is not valid");
        } 
        else {
                if($idOrganization!=null){
                    $where = "where id=?";
                    $arrParams[] = $idOrganization;
                }else if($domainName!=null){
                    $where = "where domain=?";
                    $arrParams[] = $domainName;
                }
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, domain FROM organization $where ORDER BY domain";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL,false,$arrParams);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento saber si undominio existe 
     *
     * @param string    $domain_name       nombre para el dominio

     *
     * @return bool     VERDADERO si el dominio existe, FALSO caso contrario
     */
    function domainExist($domain)
    {
        $bExito = FALSE;
        //el campo ya viene validado del formulario
        //verificar que no exista ya un dominio con ese nombre en la base
		$sPeticionSQL = "SELECT id FROM organization WHERE domain = ?";
		$arr_result =& $this->_DB->fetchTable($sPeticionSQL,false,array($domain));
		if (is_array($arr_result) && count($arr_result)>0) {
			$bExito = true;
			$this->errMsg = _tr("Domain name already exists");
		}
        return $bExito;
    }

    /**
     * Procedimiento que crea un dominio en la base de datos y en el sistema
     *
     * @param string    $domain_name       nombre para el dominio
     * @param string  $mensaje de error si algo sale mal
     * @return bool     VERDADERO si el dominio se crea correctamente, FALSO en error
     */

    function createDomain($domain, &$errMsg)
    {
        $bReturn=FALSE;
        //creo el dominio en la base de datos
		if($domain==""){
			$errMsg= _tr("Domain must no be empty");
		}
	
        $bExito = $this->domainExist($domain);
		//si es que existe el dominio en la base de datos lo creo en el sistema
        if($bExito){
            $bReturn = $this->guardar_dominio_sistema($domain,$errMsg);
            if($bReturn)
                $bReturn = TRUE;
        }else{
            $errMsg= _tr($this->errMsg);
        }
        return $bReturn;
    }

	function getListByDomain($idOrganization)
    {
		$number = 0;
		$data = array($idOrganization);
		$sPeticionSQL = "SELECT id FROM email_list WHERE id_organization = ?";
		$arr_result = $this->_DB->fetchTable($sPeticionSQL,TRUE,$data);
		if (is_array($arr_result) && count($arr_result)>0) {
			$number=$arr_result[0];
		}
			return $number;
    }

	function accountExists($account)
    {
		$query = "SELECT COUNT(*) FROM acl_user WHERE username=?";
		$result = $this->_DB->getFirstRowQuery($query,false,array($account));
		if($result===FALSE){
			$this->errMsg = $this->_DB->errMsg;
			return true;
		}
		if($result[0] > 0)
			return true;
		else
			return false;
    }

	function edit_email_account($username,$password,$quota)
	{
		global $CYRUS;
		global $arrLang;
		$bExito=TRUE;
		$error_pwd='';
		$error="";
		$virtual = FALSE;
		if(!$this->updateQuota($old_quota,$quota)){
			$bExito=false;
		}
		if(!$this->updatePassword($username,$password)){
			$bExito=false;
		}
		return $bExito;
	}

	//pendiente
	function obtener_quota_usuario($username,$module_name,$arrLang,$id_domain)
	{
		global $CYRUS;
		global $arrLang;
		$cyr_conn = new cyradm;
		$cyr_conn->imap_login();
		$arrQuota=array();
		//retorna un arreglo con la informacion de la cuota del usuario
		$quota = $cyr_conn->getquota("user/" . $username);
		if(is_array($quota) && count($quota)>0){
			if ($quota['used'] != "NOT-SET"){
				$q_used  = $quota['used'];
				$q_total = $quota['qmax'];
				if (! $q_total == 0){
					$q_percent = number_format((100*$q_used/$q_total),2);
					$tamano_usado="$quota[used] KB / <a href='?menu=$module_name&action=viewFormEditQuota&username=$username&domain=$id_domain' title='$edit_quota'>$quota[qmax] KB</a> ($q_percent%)";
				}else {
					$tamano_usado=_tr("Could not obtain used disc space");
				}
			} else {
				$tamano_usado=_tr("Size is not set");
			}
		}
		return $quota;
	}

	//esta funcion actualiza la quota en el sistema
	function updateQuota($old_quota,$quota,$username)
	{
		$bExito=true;
		if(!preg_match('/^[[:digit:]]+$/', "$old_quota")) {
			$this->errMsg=_tr("Quota must be numeric");
			$bExito=false;
		}elseif(!preg_match('/^[[:digit:]]+$/', "$quota")){
			$this->errMsg=_tr("Quota must be numeric");
			$bExito=false;
		}

		 if($old_quota!=$quota){
			$cyr_conn = new cyradm;
			$cyr_conn->imap_login();
			$bContinuar=$cyr_conn->setmbquota("user" . "/".$username, $quota);
			if (!$bContinuar){
				$this->errMsg=_tr("Quota could not be changed.")." ".$cyr_conn->getMessage();
				$bExito=FALSE;
			}
		}
		return $bExito;
	}

	function updatePassword($username,$password)
	{
		global $CYRUS;
		global $arrLang;
		$bExito=TRUE;
		$error="";
		$virtual = FALSE;
		if (isset($password) && trim($password)!="")
		{
			$bool = $this->crear_usuario_correo_sistema($username,$username,$password,$error,$virtual); //False al final para indicar que no cree virtual
			if(!$bool){
				$this->errMsg=_tr("Password could not be changed.")." ".$error;
				$bExito=FALSE;
			}
		}
		return $bExito;
	}

	//no pueden existir cuentas de mail sin que exista un usuario de elastix asociado a ellas
    function create_email_account($username,$password,$idOrganization,$quota,$virtual=TRUE)
    {
        $bReturn=FALSE;
        $virtual = FALSE;
        //creo la cuenta
        // -- usuario debe existir el la base acl_user y no existir en el sistema
        // -- si no existe creo el usuario en el sistema con sasldbpasswd2
        // -- creo el mailbox para la cuenta (si hay error deshacer lo realizado)
		$arrUser=$this->accountExists($username);
		if(array($arrUser) && count($arrUser)>0){
			$bExito = $this->crear_usuario_correo_sistema($username,$username,$password,$this->errMsg, $virtual);
			if ($bExito){
				//crear el mailbox para la nueva cuenta
				$bReturn = $this->crear_mailbox_usuario($username,$username,$this->errMsg, $quota);
			}
			if(!$bReturn){
				//tengo que borrar el usuario creado en el sistema
				$bReturn = $this->eliminar_usuario_correo_sistema($username,$username,$this->errMsg);
				$this->errMsg = _tr($this->errMsg);
				if($bReturn && $virtual){
					$bReturn = $this->eliminar_virtual_sistema($email,$this->errMsg);
					$this->errMsg = _tr($this->errMsg);
				}
				return false;
			}
		}else{
			$this->errMsg=_tr("User doesn't exist");
		}
			
    return $bReturn;
    }


    function crear_mailbox_usuario($email,$username,&$error_msg, $quota){
        global $CYRUS;
        $cyr_conn = new cyradm;
        $error=$cyr_conn->imap_login();
        $virtual = FALSE;
        $error_msg=="";
        if ($error===FALSE){
            $error_msg=_tr("IMAP login error: $error");
            print_r($error_msg);
        }
        else{
            $seperator  = '/';
            $bValido=$cyr_conn->createmb("user" . $seperator . $username);
            if(!$bValido){
                $error_msg =_tr("Error creating user:".$cyr_conn->getMessage());
                print_r($error_msg);
            }else{
                $bValido=$cyr_conn->setacl("user" . $seperator . $username, $CYRUS['ADMIN'], "lrswipcda");
                if(!$bValido){
                    $error_msg =_tr("Error:".$cyr_conn->getMessage());
                    print_r($error_msg);
                }else{
                    $bValido = $cyr_conn->setmbquota("user" . $seperator . $username, $quota);
                    if(!$bValido)
                        $error_msg =_tr("error ".$cyr_conn->getMessage());
                }
            }
        }

        if($error_msg!=""){
			return FALSE;
        }
        return TRUE;
    }


    //***** new functions from email_functions.lib.php ***************************************************************/
    function guardar_dominio_sistema($domain,&$errMsg)
    {
		$continuar=FALSE;
		global $arrLang;
		$configPostfix2 = isPostfixToElastix2();
		$param1 = ""; // virtual_mailbox_domains or mydomain2
		//Se debe modificar el archivo /etc/postfix/main.cf para agregar el dominio a la variable
		//virtual_mailbox_domains if $configPostfix2=TRUE or mydomain2 if $configPostfix2=FALSE
		if($configPostfix2)
			$param1 = "virtual_mailbox_domains";
		else
			$param1 = "mydomain2";
		$conf_file = new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
		$contenido = $conf_file->leer_configuracion();
		$valor_anterior = $conf_file->privado_get_valor($contenido,$param1);
		$valor_nuevo =$this->construir_valor_nuevo_postfix($valor_anterior,$domain);
		$arr_reemplazos = array("$param1"=>$valor_nuevo);
		$bValido = $conf_file->escribir_configuracion($arr_reemplazos);
		if($bValido){
			//Se deben recargar la configuracion de postfix
			$retval = $output = "";
			exec("sudo -u root postfix reload",$output,$retval);
			if($retval == 0)
			$continuar = TRUE;
			else
			$errMsg = $arrLang["main.cf file was updated successfully but when restarting the mail service failed"];
		}
		return $continuar;
    }


    function construir_valor_nuevo_postfix($valor_anterior,$dominio,$eliminar_dominio=FALSE){
	$valor_nuevo=$valor_anterior;

	if(is_null($valor_anterior)){
	    $elemento=(!$eliminar_dominio)?"$dominio":"";
	    $valor_nuevo="$elemento";
	}
	else{
	    if(preg_match('/^(.*)$/',$valor_anterior,$regs)){
		$arr_valores=explode(',',$regs[1]);
		if(!$eliminar_dominio)
		    $arr_valores[]="$dominio";

		$valor_nuevo="";
		for($i=0;$i<count($arr_valores);$i++){
		    $valor_nuevo.=$arr_valores[$i];
		    if($i<(count($arr_valores)-1))
			$valor_nuevo.=",";
		}

		if($eliminar_dominio==TRUE){
		    $valor_nuevo=str_replace(",$dominio","",$valor_nuevo);
		}
	    }
	}
	return $valor_nuevo;
    }

    function eliminarDominio($id,$domain,&$errMsg, $virtual=TRUE)
    {
		$total_cuentas=0;
		$output="";
		$configPostfix2 = isPostfixToElastix2();
		$param1 = "";

		global $CYRUS;
		global $arrLang;
		$cyr_conn = new cyradm;
		$continuar = $cyr_conn->imap_login();

		if($configPostfix2)
			$param1 = "virtual_mailbox_domains";
		else
			$param1 = "mydomain2";

	//reviso que no haya lista de emails creadas
		$arrList = $this->getListByDomain($id);
		if(is_array($arrList) && count($arrList)>0){
			$errMsg=_tr("Domain <b>$domain<b> cant not be deleted because exist Email List associated whit the domain.");
		}else{
			//Se elimina el dominio del archivo main.cf y se recarga la configuracion
			$continuar=FALSE;
			//Se debe modificar el archivo /etc/postfix/main.cf para borrar el dominio a la variable
			//virtual_mailbox_domains if $configPostfix2=TRUE or mydomain2 if $configPostfix2=FALSE
			$conf_file=new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
			$contenido=$conf_file->leer_configuracion();
			$valor_anterior=$conf_file->privado_get_valor($contenido,$param1);
			$valor_nuevo=$this->construir_valor_nuevo_postfix($valor_anterior,$domain,TRUE);
			$arr_reemplazos=array("$param1"=>$valor_nuevo);
			$bValido=$conf_file->escribir_configuracion($arr_reemplazos);

			if($bValido){
				//Se deben recargar la configuracion de postfix
				$retval=$output="";
				exec("sudo -u root postfix reload",$output,$retval);
				if($retval==0)
					$continuar=TRUE;
				else
					$errMsg=_tr("main.cf file was updated successfully but when restarting the mail service failed")." : $retval";
			}
		}
		return $continuar;
    }


    function eliminar_usuario_correo_sistema($username,$email,&$error)
	{
		$output=array();
		$configPostfix2 = isPostfixToElastix2();
		if($configPostfix2)
			exec("sudo -u root /usr/sbin/saslpasswd2 -d ".escapeshellarg($email),$output);
		else
			exec("sudo -u root /usr/sbin/saslpasswd2 -d ".escapeshellarg($username)."@".SASL_DOMAIN,$output);
		if(is_array($output) && count($output)>0){
			foreach($output as $linea)
			$error.=$linea."<br>";
		}
		if($error!="")
			return FALSE;
		else
			return TRUE;
    }

    function eliminar_virtual_sistema($email,&$error)
	{
		$config=new paloConfig("/etc/postfix","virtual","\t","[[:space:]?\t[:space:]?]");
		$arr_direcciones=$config->leer_configuracion();

		$eliminado=FALSE;
		foreach($arr_direcciones as $key=>$fila){
			if(isset($fila['clave']) && $fila['clave']==$email){
			unset($arr_direcciones[$key]);
			$eliminado=TRUE;
			}
		}

		if($eliminado){
			$bool=$config->escribir_configuracion($arr_direcciones,true);
			if($bool){
			exec("sudo -u root postmap /etc/postfix/virtual",$output);
			if(is_array($output) && count($output)>0)
				foreach($output as $linea)
				$error.=$linea."<br>";
			}
			else{
			$error.=$config->getMessage();
			return FALSE;
			}
		}
	return TRUE;
    }

    function crear_usuario_correo_sistema($email,$username,$clave,&$error,$virtual=TRUE)
	{
		$output=array();
		$configPostfix2 = isPostfixToElastix2();

		if($configPostfix2){
			exec("echo ".escapeshellarg($clave)." | sudo -u root /usr/sbin/saslpasswd2 -c ".escapeshellarg($email),$output);
		}else{
			exec("echo ".escapeshellarg($clave)." | sudo -u root /usr/sbin/saslpasswd2 -c ".escapeshellarg($username)." -u ".SASL_DOMAIN,$output);
		}

		if(is_array($output) && count($output)>0){
			foreach($output as $linea_salida)
			$error=$linea_salida."<br>";
		}

		if($configPostfix2){
			if($error!="")
			return FALSE;
		}else{
			if($error!="")
			return FALSE;
		}

		// escribir aliases
		if($virtual){
			$bool=$this->crear_virtual_sistema($email,$username,$error);
			if(!$bool)
			return FALSE;
		}

		return TRUE;
    }

    function crear_virtual_sistema($email,$username,&$error)
	{
		$output=array();
		$configPostfix2 = isPostfixToElastix2();
		if($configPostfix2){
			$username = $email;
		}else{
			$username.='@'.SASL_DOMAIN;
		}
		exec("sudo -u root chown asterisk /etc/postfix/virtual");
		exec("echo ".escapeshellarg("$email \t $username")." >> /etc/postfix/virtual",$output);

		if(is_array($output) && count($output)>0){
			foreach($output as $linea)
			$error.=$linea."<br>";
		}
		exec("sudo -u root chown root /etc/postfix/virtual");

		exec("sudo -u root postmap /etc/postfix/virtual",$output);
		if(is_array($output) && count($output)>0){
			foreach($output as $linea)
			$error.=$linea."<br>";
		}
		if($error!="")
			return FALSE;
		else
			return TRUE;
    }

    function eliminar_cuenta($username, $virtual=TRUE)
	{
		global $CYRUS;
		$arr_alias=array();
		$errMsg="";
		
		$cyr_conn = new cyradm;
		$bValido = $cyr_conn->imap_login();

		if ($bValido ===FALSE){
			$this->errMsg = $cyr_conn->getMessage();
			return FALSE;
		}

		$bValido=$cyr_conn->deletemb("user/".$username); // elimina los buzones de entrada
		if($bValido===FALSE){
			$this->errMsg=$cyr_conn->getMessage();
			return FALSE;
		}
		//$cyr_conn->deletemb("user/".$username)."<br>";

		if(!$this->eliminar_usuario_correo_sistema($username,$username,$errMsg)){ // elimina los usuarios del sistema
			$this->errMsg=$errMsg;
			return FALSE;
		}

		if($virtual){
			$this->eliminar_virtual_sistema($username,$errMsg); // elimina los alias en /etc/postfix/virtual
			$this->errMsg=$errMsg;
		}
		return TRUE;
    }

    function resconstruirMailBox($username)
    {
        $output = $retval = NULL;
        $configPostfix2 = isPostfixToElastix2();// in misc.lib.php
        $regularExpresion = "";
        if($configPostfix2)
           $regularExpresion = '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/';
        else
           $regularExpresion = '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';

        if(!is_null($username)){
           if(!preg_match($regularExpresion,$username)){
                $this->errMsg = _tr("Username is not valid");
           }else{
                exec('/usr/bin/elastix-helper email_account --reconstruct_mailbox  --mailbox '.escapeshellarg($username).' 2>&1', $output, $retval);
           }
        }else{
           $this->errMsg = _tr("Username can't be empty");
        }

        if ($retval != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }

        return TRUE;
    }
}
?>