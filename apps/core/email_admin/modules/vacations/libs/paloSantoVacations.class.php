<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-23                                               |
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
  $Id: paloSantoVacations.class.php,v 1.1 2011-06-07 12:06:29 Eduardo Cueva ecueva@palosanto.com Exp $ */
class paloSantoVacations {
    var $_DB;
    var $errMsg;

    function paloSantoVacations(&$pDB)
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

    /*HERE YOUR FUNCTIONS*/

    function getNumVacations($filter_field, $filter_value, $arrLang)
    {
        $where = "";
	$arrParam = array();

        if(isset($filter_field) && $filter_field !="" && $filter_value != ""){
	    if($filter_field == "username")
		$filter_field = "a.$filter_field";
	    else{
		$filter_field = "v.$filter_field";
	    }
	    if($filter_field == "v.vacation" && strtolower($filter_value) == $arrLang["no"]){
		$where = " WHERE $filter_field ISNULL OR $filter_field like ? ";
		$filter_value = "no";
	    }else{
		$where = " WHERE $filter_field like ? ";
		if(strtolower($filter_value) === $arrLang["yes"])
		    $filter_value = "yes";
	    }

	    $arrParam = array("$filter_value%");
	}

        $query   = "SELECT
		      COUNT(*)
		    FROM
		      accountuser a LEFT JOIN messages_vacations v ON a.username=v.account
		    $where
		    ORDER BY a.username;";

        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getVacations($limit, $offset, $filter_field, $filter_value, $arrLang)
    {
        $where = "";
	$arrParam = array();
        if(isset($filter_field) && $filter_field !="" && $filter_value != ""){
	    if($filter_field === "username")
		$filter_field = "a.$filter_field";
	    else{
		$filter_field = "v.$filter_field";
	    }
	    if($filter_field === "v.vacation" && strtolower($filter_value) === $arrLang["no"]){
		$where = " WHERE $filter_field ISNULL OR $filter_field like ? ";
		$filter_value = "no";
	    }else{
		$where = " WHERE $filter_field like ? ";
		if(strtolower($filter_value) === $arrLang["yes"])
		    $filter_value = "yes";
	    }

	    $arrParam = array("$filter_value%");
	}

        $query   = "SELECT
		      a.username as username,
		      v.vacation as vacation,
		      v.subject as subject,
		      v.body as body,
		      v.ini_date as ini_date,
		      v.end_date as end_date
		   FROM accountuser a LEFT JOIN messages_vacations v ON a.username=v.account
		   $where
		   ORDER BY a.username
		   LIMIT $limit OFFSET $offset";
        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getVacationsById($id)
    {
	$data = array($id);
        $query = "SELECT * FROM getVacationsById WHERE id=?";
        $result=$this->_DB->getFirstRowQuery($query,true,$data);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }


    /*********************************************************************************
    /* Funcion para subir un script de vacaciones dado los siguientes parametros:
    /* - $email:        cuenta de email a la cual se subira el script de vacaciones
    /* - $subject:      titulo del mensaje que se envia como respuesta
    /* - $body:         cuerpo o contenido del mensaje que se enviara
    /* - $objAntispam   objeto Antispam
    /* - $spamCapture   boleano que indica si esta activo el eveto de captura de spam
    /*
    /*********************************************************************************/
    function uploadVacationScript($email, $subject, $body, $objAntispam, $spamCapture, $arrLang){

	$SIEVE  = array();
        $SIEVE['HOST'] = "localhost";
        $SIEVE['PORT'] = 4190;
        $SIEVE['USER'] = "";
        $SIEVE['PASS'] = obtenerClaveCyrusAdmin("/var/www/html/");
        $SIEVE['AUTHTYPE'] = "PLAIN";
        $SIEVE['AUTHUSER'] = "cyrus";
	$SIEVE['USER'] = $email;

	$existCron = $this->existCronFile();
	if(!$existCron)
	    $this->createCronFile();

        $contentVacations  = $this->getVacationScript($subject, $body);
	$contentSpamFilter = "";

	// si esta activada la captura de spam entonces se deber reemplazar <require "fileinto";> por require ["fileinto","vacation"];
	if($spamCapture){
	    $contentSpamFilter = $objAntispam->getContentScript();
	    $contentSpamFilter = str_replace("require \"fileinto\";", "require [\"fileinto\",\"vacation\"];", $contentSpamFilter);
	}else{
	    $contentSpamFilter =  "require [\"fileinto\",\"vacation\"];";
	}
	$content = $contentSpamFilter."\n".$contentVacations;
        $fileScript = "/tmp/vacations.sieve";
        $fp = fopen($fileScript,'w');
        fwrite($fp,$content);
        fclose($fp);

	exec("echo ".$SIEVE['PASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'put $fileScript'",$flags, $status);
	if($status!=0){
	    $this->errMsg = _tr("Error: Impossible upload ")."vacations.sieve";
	    return false;
	}else{
	    exec("echo ".$SIEVE['PASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'activate vacations.sieve'",$flags, $status);
	    if($status!=0){
		$this->errMsg = _tr("Error: Impossible activate ")."vacations.sieve";
		return false;
	    }
	}

        if(is_file($fileScript))
            unlink($fileScript);
	return true;
    }


    /*********************************************************************************
    /* Funcion para eliminar un script de vacaciones dado los siguientes parametros:
    /* - $email:        cuenta de email a la cual se subira el script de vacaciones
    /* - $objAntispam   objeto Antispam
    /* - $spamCapture   boleano que indica si esta activo el eveto de captura de spam
    /*
    /*********************************************************************************/
    function deleteVacationScript($email, $objAntispam, $spamCapture, $arrLang){

        $SIEVE  = array();
        $SIEVE['HOST'] = "localhost";
        $SIEVE['PORT'] = 4190;
        $SIEVE['USER'] = "";
        $SIEVE['PASS'] = obtenerClaveCyrusAdmin("/var/www/html/");
        $SIEVE['AUTHTYPE'] = "PLAIN";
        $SIEVE['AUTHUSER'] = "cyrus";
	$SIEVE['USER'] = $email;

	$existCron = $this->existCronFile();
	if(!$existCron)
	    $this->createCronFile();

	exec("echo ".$SIEVE['PASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'delete vacations.sieve'",$flags, $status);

	if($status!=0){
	    $this->errMsg = _tr("Error: Impossible remove ")."vacations.sieve";
	    return false;
	}

	if($spamCapture){
	    $contentSpamFilter = $objAntispam->getContentScript();
	    $fileScript = "/tmp/scriptTest.sieve";
	    $fp = fopen($fileScript,'w');
	    fwrite($fp,$contentSpamFilter);
	    fclose($fp);

	    exec("echo ".$SIEVE['PASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'put $fileScript'",$flags, $status);

	    if($status!=0){
		$this->errMsg = _tr("Error: Impossible upload ")."scriptTest.sieve";
		return false;
	    }else{
		exec("echo ".$SIEVE['PASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'activate scriptTest.sieve'",$flags, $status);
		if($status!=0){
		    $this->errMsg = _tr("Error: Impossible activate ")."scriptTest.sieve";
		    return false;
		}
	    }
	    if(is_file($fileScript))
		unlink($fileScript);
	}
	return true;
    }

    /*********************************************************************************
    /* Funcion retorna la plantilla basica del script de vacaciones:
    /* - $subject:      titulo del mensaje que se envia como respuesta
    /* - $body:         cuerpo o contenido del mensaje que se enviara
    /*
    /*********************************************************************************/
    function getVacationScript($subject, $body){
        $script = <<<SCRIPT

 vacation
        # Reply at most once a day to a same sender
        :days 1

        # Currently, encode subject, so you can't use
        # Non-English characters in subject field.
        # The easiest way is let your webmail do that.
        :subject "$subject"

        # Use 'mime' parameter to compose utf-8 message, you can use
        # Non-English characters in mail body.
        :mime
"MIME-Version: 1.0
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: 8bit
$body
";

SCRIPT;
        return $script;
    }

    /*********************************************************************************
    /* Funcion retorna la plantilla basica del script de vacaciones:
    /* - $idUserInt:      id del usuario elastix en session
    /* - $pDBACL:         conexion a la base de datos acl
    /*
    /*********************************************************************************/
    function getAccountByIdUser($idUserInt, $pDBACL)
    {
	$data = array($idUserInt);
	$account = "";
	$query   = "select app.id_profile, app.property, app.value from acl_profile_properties app, acl_user_profile aup where aup.id_user=? AND app.id_profile=aup.id_profile order by property DESC";
	$result  = $pDBACL->fetchTable($query, true, $data);

        if($result==FALSE){
            $this->errMsg = $pDBACL->errMsg;
            return false;
        }
	
	foreach($result as $key => $value){
	    $propiedad = $value['property'];
	    $valor     = $value['value'];
	    if($propiedad=="login")
		$account .= $valor;
	    if($propiedad=="domain")
		$account .= "@$valor";
	}
	return $account;
    }


    /*********************************************************************************
    /* Funcion retorna el mensaje de vacaciones que se ha guardado dado un email:
    /* - $email:      email del usuario elastix en session
    /*
    /* Retorna:
    /* - $result:     El resultado de la consulta realizada
    /*********************************************************************************/
    function getMessageVacationByUser($email)
    {
	$data = array($email);
	$query = "select * from messages_vacations where account=?";
	$result=$this->_DB->getFirstRowQuery($query,true,$data);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	return $result;
    }

    /*********************************************************************************
    /* Funcion retorna si ya existe un mensage almacenado por un usuario dado:
    /* - $email:      email del usuario elastix en session
    /* - $id:         id del mensage
    /*
    /* Retorna:
    /* - $result:     Un booleano con el resultado si existe un registro de un usuario
    /*********************************************************************************/
    function existMessage($email)
    {
	$data = array($email);
	$query = "select * from messages_vacations where account=?";
	$result=$this->_DB->getFirstRowQuery($query,true,$data);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	if(is_array($result) && count($result) > 0)
	    return true;
	else
	    return false;
    }

    /*********************************************************************************
    /* Funcion que inserta un mensaje dado los siguientes parametros:
    /* - $email:      email del usuario elastix en session
    /* - $subject:    Titulo del mensaje
    /* - $body:       Cuerpo del mensaje
    /*
    /* Retorna:
    /* - $result:     Un booleano con el resultado si se inserto el registro
    /*********************************************************************************/
    function insertMessageByUser($email, $subject, $body, $ini_date, $end_date, $status)
    {
	$data = array();
	$query = "";
	$query = "insert into messages_vacations(account,subject,body,vacation,ini_date,end_date) values(?,?,?,?,?,?)";
	$data = array($email, $subject, $body, $status, $ini_date, $end_date);

	$result=$this->_DB->genQuery($query,$data);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	return true;
    }

    /*********************************************************************************
    /* Funcion que actualiza un mensaje dado los siguientes parametros:
    /* - $email:      email del usuario elastix en session
    /* - $subject:    Titulo del mensaje
    /* - $body:       Cuerpo del mensaje
    /* - $id:         id del mensage
    /*
    /* Retorna:
    /* - $result:     Un booleano con el resultado si se actualizo el registro
    /*********************************************************************************/
    function updateMessageByUser($email, $subject, $body, $ini_date, $end_date, $status=null)
    {
	$data = array($subject, $body, $status, $ini_date, $end_date, $email);
	$query = "update messages_vacations set subject=?,  body=? , vacation=?, ini_date=?, end_date=?  where account=?";
	$result=$this->_DB->genQuery($query,$data);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	return true;
    }

    /*********************************************************************************
    /* Funcion que elimina los registros excesivos que se encuentran en una base de datos:
    /* - $email:      email del usuario elastix en session
    /* - $subject:    Titulo del mensaje
    /* - $body:       Cuerpo del mensaje
    /* - $id:         id del mensage
    /*
    /* Retorna:
    /* - $result:     Un booleano con el resultado si se actualizo el registro
    /*********************************************************************************/
    function deleteMessagesByUser($email, $subject, $body, $ini_date, $end_date, $status=null)
    {
	$data = array($email);
	$query = "delete from messages_vacations where account=?";
	$result=$this->_DB->genQuery($query,$data);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	return true;
    }



    /*********************************************************************************
    /* Funcion que inserta un mensaje dado los siguientes parametros:
    /* - $email:      email del usuario elastix en session
    /* - $subject:    Titulo del mensaje
    /* - $body:       Cuerpo del mensaje
    /*
    /* Retorna:
    /* - $result:     Un booleano con el resultado si se inserto el registro
    /*********************************************************************************/
    function setStatusMessageByUser($email, $id, $vacation)
    {
	$data = array($vacation, $id, $email);
	$query = "update messages_vacations set vacation=? where id=? and account=?";
	$result=$this->_DB->genQuery($query,$data);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	return true;
    }


    /*********************************************************************************
    /* Funcion que verifica si el sieve esta corriendo.
    /* Parametros de entrada:
    /*  - $arrLang:      arreglo de lenguaje
    /*
    /* Retorna:
    /*  - $result:       El resultado de la consulta realizada
    /*********************************************************************************/
    function verifySieveStatus($arrLang)
    {
	$response = array();

	exec("sudo /sbin/service generic-cloexec cyrus-imapd status",$arrConsole,$flagStatus);
        if($flagStatus != 0){
	    $response['response'] = false;
	    $response['message'] = $arrLang["Cyrus Imap is down"];
        }else{
	    $response['response'] = true;
	    $response['message'] = $arrLang["Cyrus Imap is up"];
	}
        return $response;
    }

    /*********************************************************************************
    /* Funcion que devuelve todos los correos electronicos con el script de vacaciones
    /* activado:
    /*
    /* Retorna:
    /* - $result:     Un arreglo con los emails con el script de vacaciones activo
    /*********************************************************************************/
    function getEmailsVacationON()
    {
	$query = "SELECT id, account, ini_date, end_date, subject, body FROM messages_vacations WHERE vacation = 'yes'";
	$result=$this->_DB->fetchTable($query,true);
	if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
	return $result;
    }


    /*********************************************************************************
    /* Funcion que verifica si existe el archivo de cron del script de vacaciones:
    /*
    /* Retorna:
    /* - $result:     Un arreglo con los emails con el script de vacaciones activo
    /*********************************************************************************/
    function existCronFile()
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper vacationconfig exist_cron';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    /*********************************************************************************
    /* Funcion para crear el cron de eliminacion de script de vacaciones automatica:
    /*
    /* Retorna:
    /* - $result:     Un arreglo con los emails con el script de vacaciones activo
    /*********************************************************************************/
    function createCronFile()
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/elastix-helper vacationconfig create_cron';
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