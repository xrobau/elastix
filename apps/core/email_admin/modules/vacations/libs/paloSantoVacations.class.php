<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-23                                               |
  | http://www.elastix.com                                               |
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

    function getNumVacations($filter_field, $filter_value)
    {
        $where = "";
        $arrParam = array();

        if(isset($filter_field) && $filter_field !="" && $filter_value != ""){
            if($filter_field == "username")
                $filter_field = "a.$filter_field";
            else{
                $filter_field = "v.$filter_field";
            }
            if($filter_field == "v.vacation" && strtolower($filter_value) == _tr("no")){
                $where = " WHERE $filter_field ISNULL OR $filter_field like ? ";
                $filter_value = "no";
            }else{
                $where = " WHERE $filter_field like ? ";
                if(strtolower($filter_value) === _tr("yes"))
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

    function getVacations($limit, $offset, $filter_field, $filter_value)
    {
        $where = "";
        $arrParam = array();
        if(isset($filter_field) && $filter_field !="" && $filter_value != ""){
            if($filter_field === "username")
                $filter_field = "a.$filter_field";
            else{
                $filter_field = "v.$filter_field";
            }
            if($filter_field === "v.vacation" && strtolower($filter_value) === _tr("no")){
                $where = " WHERE $filter_field ISNULL OR $filter_field like ? ";
                $filter_value = "no";
            }else{
                $where = " WHERE $filter_field like ? ";
                if(strtolower($filter_value) === _tr("yes"))
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

    /*********************************************************************************
    /* Funcion para subir un script de vacaciones dado los siguientes parametros:
    /* - $email:        cuenta de email a la cual se subira el script de vacaciones
    /* - $subject:      titulo del mensaje que se envia como respuesta
    /* - $body:         cuerpo o contenido del mensaje que se enviara
    /* - $spamCapture   boleano que indica si esta activo el eveto de captura de spam
    /*
    /*********************************************************************************/
    private function uploadVacationScript($email, $subject, $body, $spamCapture){

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
            $contentSpamFilter = $this->_getContentScript();
            $contentSpamFilter = str_replace("require \"fileinto\";", "require [\"fileinto\",\"vacation\"];", $contentSpamFilter);
        }else{
            $contentSpamFilter =  "require [\"fileinto\",\"vacation\"];";
        }
        $content = $contentSpamFilter."\n".$contentVacations;
        $fileScript = "/tmp/vacations.sieve";
        $fp = fopen($fileScript,'w');
        fwrite($fp,$content);
        fclose($fp);

        exec("echo ".escapeshellarg($SIEVE['PASS'])." | sieveshell ".escapeshellarg("--username=".$SIEVE['USER']).
        " --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT'].
        " -e 'put $fileScript'",$flags, $status);
        if($status!=0){
            $this->errMsg = _tr("Error: Impossible upload ")."vacations.sieve";
            return false;
        }else{
            exec("echo ".escapeshellarg($SIEVE['PASS'])." | sieveshell ".escapeshellarg("--username=".$SIEVE['USER']).
            " --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT'].
            " -e 'activate vacations.sieve'",$flags, $status);
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
    /* - $spamCapture   boleano que indica si esta activo el eveto de captura de spam
    /*
    /*********************************************************************************/
    private function deleteVacationScript($email, $spamCapture){

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

        exec("echo ".escapeshellarg($SIEVE['PASS'])." | sieveshell ".escapeshellarg("--username=".$SIEVE['USER']).
        " --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT'].
        " -e 'deactivate\ndelete vacations.sieve'",$flags, $status);

        if($status!=0){
            $this->errMsg = _tr("Error: Impossible remove ")."vacations.sieve";
            return false;
        }

        if($spamCapture){
            $contentSpamFilter = $this->_getContentScript();
            $fileScript = "/tmp/scriptTest.sieve";
            $fp = fopen($fileScript,'w');
            fwrite($fp,$contentSpamFilter);
            fclose($fp);

            exec("echo ".escapeshellarg($SIEVE['PASS'])." | sieveshell ".escapeshellarg("--username=".$SIEVE['USER']).
            " --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT'].
            " -e 'put $fileScript'",$flags, $status);

            if($status!=0){
                $this->errMsg = _tr("Error: Impossible upload ")."scriptTest.sieve";
                return false;
            }else{
                exec("echo ".$SIEVE['PASS']." | sieveshell ".escapeshellarg("--username=".$SIEVE['USER']).
            " --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT'].
            " -e 'activate scriptTest.sieve'",$flags, $status);
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
    private function getVacationScript($subject, $body){
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

    function setMessageAccount($email, $subject, $body, $ini_date, $end_date, $status)
    {
        $sqls = array(
            array(
                'DELETE FROM messages_vacations WHERE account = ?',
                array($email)),
            array(
                'INSERT INTO messages_vacations(account, subject, body, vacation, ini_date, end_date) '.
                    'VALUES(?,?,?,?,?,?)',
                array($email, $subject, $body, $status, $ini_date, $end_date)),
        );
        foreach ($sqls as $sql) {
            $r = $this->_DB->genQuery($sql[0], $sql[1]);
            if (!$r) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
        }
        return TRUE;
    }

    /*********************************************************************************
    /* Funcion que verifica si el sieve esta corriendo.
    /* Parametros de entrada:
    /*
    /* Retorna:
    /*  - $result:       El resultado de la consulta realizada
    /*********************************************************************************/
    function verifySieveStatus()
    {
        $response = array();

        exec("sudo /sbin/service generic-cloexec cyrus-imapd status",$arrConsole,$flagStatus);
        if($flagStatus != 0){
            $response['response'] = false;
            $response['message'] = _tr("Cyrus Imap is down");
        }else{
            $response['response'] = true;
            $response['message'] = _tr("Cyrus Imap is up");
        }
        return $response;
    }

    /*********************************************************************************
    /* Funcion que verifica si existe el archivo de cron del script de vacaciones:
    /*
    /* Retorna:
    /* - $result:     Un arreglo con los emails con el script de vacaciones activo
    /*********************************************************************************/
    private function existCronFile()
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
    private function createCronFile()
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

    /**
     * Método para evaluar el estado de vacaciones de todas las cuentas con
     * vacación activa, y decidir, según la fecha, si finalizar la vacación.
     *
     * @param   string  $date           Fecha a evaluar, o NULL para hoy
     *
     * @return  boolean FALSE en error, o TRUE para éxito
     */
    function updateVacationMessageAll($date = NULL)
    {
        if (is_null($date)) $date = date('Y-m-d');

        $sql = 'SELECT account, ini_date, end_date, subject, body, vacation '.
            'FROM messages_vacations WHERE vacation = "yes"';
        $rs = $this->_DB->fetchTable($sql, TRUE);
        if (!is_array($rs)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        foreach ($rs as $tupla) {
            if (!$this->_updateVacationMessageTupla($tupla, $date))
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Método para evaluar el estado de vacaciones de la cuenta indicada, y
     * decidir, según la fecha, si iniciar o finalizar la vacación.
     *
     * @param   string  $email          Correo a evaluar para vacaciones
     * @param   string  $date           Fecha a evaluar, o NULL para hoy
     *
     * @return  boolean FALSE en error, o TRUE para éxito
     */
    function updateVacationMessageAccount($email, $date = NULL)
    {
        if (is_null($date)) $date = date('Y-m-d');

        $sql = 'SELECT account, ini_date, end_date, subject, body, vacation '.
            'FROM messages_vacations WHERE account = ?';
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, array($email));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        if (count($tupla) <= 0) {
            $this->errMsg = 'Email account not found';
            return FALSE;
        }
        return $this->_updateVacationMessageTupla($tupla, $date);
    }

    private function _updateVacationMessageTupla($tupla, $date)
    {
        // Listar script activo. Se asume antispam si scriptTest.sieve presente.
        // TODO: reescribir con uso de objeto sieve-php
        $scripts = $this->existScriptSieve($tupla['account'], 'scriptTest.sieve');
        $bVacacionActiva = (strpos($scripts['actived'], 'vacations.sieve') !== FALSE);
        $bAntispamActivo = $scripts['status'];

        // Evaluar plantillas de fechas
        foreach (array('subject', 'body') as $k) {
            $tupla[$k] = str_replace("{END_DATE}", $tupla['end_date'], $tupla[$k]);
        }

        // Reformatear fechas a yyyy-mm-dd para comparar
        $tupla['ini_date'] = date('Y-m-d', strtotime($tupla['ini_date']));
        $tupla['end_date'] = date('Y-m-d', strtotime($tupla['end_date']));

        // Nuevo estado de vacaciones
        $bActivarVacacion = ($tupla['vacation'] == 'yes' &&
            $tupla['ini_date'] <= $date && $date <= $tupla['end_date']);

        // Finalizar vacación en DB si ya expiró
        if ($tupla['vacation'] == 'yes' && $date > $tupla['end_date']) {
            $sql = 'UPDATE messages_vacations SET vacation = ? WHERE account = ?';
            $r = $this->_DB->genQuery($sql, array('no', $tupla['account']));
            if (!$r) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
        }

        $r = TRUE;
        if (!$bVacacionActiva && $bActivarVacacion) {
            $r = $this->uploadVacationScript(
                $tupla['account'], $tupla['subject'], $tupla['body'],
                $bAntispamActivo);
        } elseif ($bVacacionActiva && !$bActivarVacacion) {
            $r = $this->deleteVacationScript(
                $tupla['account'],
                $bAntispamActivo);
        }
        return $r;
    }

    private function _getContentScript()
    {
        $script = <<<SCRIPT
require "fileinto";
if exists "X-Spam-Flag" {
    if header :is "X-Spam-Flag" "YES" {
        fileinto "Spam";
        stop;
    }
}
if exists "X-Spam-Status" {
    if header :contains "X-Spam-Status" "Yes," {
        fileinto "Spam";
        stop;
    }
}
SCRIPT;
        return $script;
    }

    function existScriptSieve($email, $search)
    {
        $SIEVE  = array();
        $SIEVE['HOST'] = "localhost";
        $SIEVE['PORT'] = 4190;
        $SIEVE['USER'] = "";
        $SIEVE['PASS'] = obtenerClaveCyrusAdmin("/var/www/html/");
        $SIEVE['AUTHTYPE'] = "PLAIN";
        $SIEVE['AUTHUSER'] = "cyrus";
        $SIEVE['USER'] = $email;
        $result['status']  = false;
        $result['actived'] = "";

        exec("echo ".$SIEVE['PASS']." | sieveshell --username=".$SIEVE['USER']." --authname=".$SIEVE['AUTHUSER']." ".$SIEVE['HOST'].":".$SIEVE['PORT']." -e 'list'",$flags, $status);

        if($status != 0){
            return null;
        }else{
            for($i=0; $i<count($flags); $i++){
                $value = trim($flags[$i]);
                if(preg_match("/$search/", $value)){
                    $result['status'] = true;
                }
                if(preg_match("/active script/", $value)){
                    $result['actived'] = $value;
                }
            }
        }
        return $result;
    }
}
