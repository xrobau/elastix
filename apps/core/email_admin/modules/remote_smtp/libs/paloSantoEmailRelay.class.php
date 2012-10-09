<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.6-6                                               |
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
  $Id: paloSantoEmailRelay.class.php,v 1.1 2010-07-21 01:08:56 Bruno Macias bmacias@palosanto.com Exp $ */
class paloSantoEmailRelay {
    var $_DB;
    var $errMsg;

    function paloSantoEmailRelay(&$pDB)
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

    function getMainConfigByAll()
    {
        $query  = "SELECT id, name, value FROM email_relay ";
        $result = $this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }

        $arrData = null;
        if(is_array($result) && count($result)>0){
            foreach($result as $k => $data)
                $arrData[$data['name']] = $data['value'];
        }
        return $arrData;
    }

    /**
     * Método para actualizar la configuración de SMTP remoto.
     * 
     * @param   array   $arrData    Arreglo con los parámetros de configuración:
     *  status          'on' para activar SMTP remoto, 'off' para desactivar
     *  relayhost       nombre de host del SMTP remoto
     *  port            puerto TCP a contactar en SMTP remoto
     *  user            nombre de usuario para autenticación
     *  password        contraseña para autenticación
     *  autentification 'on' para activar TLS, 'off' para desactivar
     */
    function processUpdateConfiguration($arrData)
    {
        $this->_DB->beginTransaction();

        if($this->processUpdateConfigurationDB($arrData)){
            if($this->processUpdateConfigurationFile($arrData)){
                $this->_DB->commit();
                return true;
            }
            else{
                $this->_DB->rollBack();
                return false;
            }
        }
        else{
            $this->_DB->rollBack();
            return false;
        }
    }

    private function processUpdateConfigurationDB($arrData)
    {
        if(is_array($arrData) && count($arrData)>0){
            $query = "delete from email_relay;";
            $ok = $this->_DB->genQuery($query);

            if(!$ok){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
            foreach($arrData as $name => $value){
                $query = "insert into email_relay(name,value) values('$name','$value');";
                $ok = $this->_DB->genQuery($query);

                if(!$ok){
                    $this->errMsg = $this->_DB->errMsg;
                    return false;
                }
            }
        }
        return true;
    }

    private function processUpdateConfigurationFile($arrData)
    {
        if(is_array($arrData) && count($arrData)>0){
            $activated = $arrData['status'];

            $arrReplaces['relayhost'] = ($activated == "on")?$arrData['relayhost']:"";

            if($arrData['port']!="")
                $arrReplaces['relayhost'] = ($activated == "on")?"$arrData[relayhost]:$arrData[port]":"";

            if($arrData['user'] && $arrData['password']){
                $arrReplaces['smtp_sasl_auth_enable']      = ($activated =="on")?"yes":"no"; // default no
                $arrReplaces['smtp_sasl_password_maps']    = ($activated =="on")?"hash:/etc/postfix/sasl/passwd":""; // default ""
                $arrReplaces['smtp_sasl_security_options'] = ($activated =="on")?"":"noplaintext, noanonymous"; //default noplaintext, noanonymous
                $arrReplaces['broken_sasl_auth_clients']   = ($activated =="on")?"yes":"no";// default no
                   if($arrData['autentification']=="on"){
                        $this->createCert();
                        $arrReplaces['smtpd_tls_auth_only'] = ($activated =="on")?"no":"no";
                        $arrReplaces['smtp_use_tls'] = ($activated =="on")?"yes":"no";
                        $arrReplaces['smtp_tls_note_starttls_offer'] = ($activated =="on")?"yes":"no";
                        $arrReplaces['smtp_tls_CAfile'] = ($activated =="on")?"/etc/postfix/tls/tlscer.crt":"";
                        $arrReplaces['smtpd_tls_loglevel'] =($activated =="on")?2:"0";
                        $arrReplaces['smtpd_tls_received_header'] = ($activated =="on")? "yes":"no";
                        $arrReplaces['tls_random_source'] = ($activated =="on")? "dev:/dev/urandom":"";
                        $arrReplaces['smtp_sasl_security_options'] = ($activated =="on")?"noanonymous":"";
                    }else{
                        $arrReplaces['smtpd_tls_auth_only'] = "no";
                        $arrReplaces['smtp_use_tls'] = "no"; 
                        $arrReplaces['smtp_tls_note_starttls_offer'] = "no";
                        $arrReplaces['smtpd_tls_loglevel'] = "2";
                        $arrReplaces['smtpd_tls_received_header'] = "no";
                        $arrReplaces['tls_random_source'] = "";
                        $arrReplaces['smtp_tls_CAfile'] = "";
                    }
                $this->createSASL();
                $data = ($activated =="on")?"$arrData[relayhost]:$arrData[port] $arrData[user]:$arrData[password]":"";
                $this->writeSASL($data);
            }
            else{
                $arrReplaces['smtp_sasl_auth_enable']      = "no";
                $arrReplaces['smtp_sasl_password_maps']    = "";
                $arrReplaces['smtp_sasl_security_options'] = "noplaintext, noanonymous";
                $arrReplaces['broken_sasl_auth_clients']   = "yes";
                $arrReplaces['smtpd_tls_auth_only'] = "no";
                $arrReplaces['smtp_use_tls'] = "no"; 
                $arrReplaces['smtp_tls_note_starttls_offer'] = "no";
                $arrReplaces['smtpd_tls_loglevel'] = "0";
                $arrReplaces['smtpd_tls_received_header'] = "no";
                $arrReplaces['tls_random_source'] = "";
                $arrReplaces['smtp_tls_CAfile'] = "";
                $this->createSASL();
                $data = "";
                $this->writeSASL($data);
            }

            $conf_file = new paloConfig("/etc/postfix","main.cf"," = ","[[:space:]]*=[[:space:]]*");
            $bValido   = $conf_file->escribir_configuracion($arrReplaces);

            if($bValido){
                $this->restartingServices();
            }
        }
        return true;
    }

    function setStatus($status)
    {
        // Existe name status
        $query  = "select count(*) existe from email_relay where name='status';";
        $result = $this->_DB->getFirstRowQuery($query,true);

        if(is_array($result) && count($result) >0){
            if($result['existe']==1){
                $query = "update email_relay set value='$status' where name='status';";
                $ok = $this->_DB->genQuery($query);
            }
            else{
                $query = "insert into email_relay(name,value) values('status','$status');";
                $ok = $this->_DB->genQuery($query);
            }

            if(!$ok){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }
            return true;
        }
        else{
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
    }

    function getStatus()
    {
        // Existe name status
        $query  = "select value from email_relay where name='status';";
        $result = $this->_DB->getFirstRowQuery($query,true);

        if(is_array($result) && count($result) >0)
            return $result['value'];
        else return 0;
    }

    private function createSASL()
    {
        exec("sudo -u root chown -R asterisk.asterisk /etc/postfix/");
        exec("mkdir /etc/postfix/sasl");
        exec("touch /etc/postfix/sasl/passwd");
        exec("chmod 600 /etc/postfix/sasl/passwd");
        exec("sudo -u root chown -R root.root /etc/postfix/");
    }

    private function createCert(){
        exec("postfix reload");
        exec("sudo -u root chown -R asterisk.asterisk /etc/postfix/");
        if(!is_file("/etc/postfix/tls/tlscer.crt")){
            exec("mkdir /etc/postfix/tls");
            exec("/etc/pki/tls/certs/make-dummy-cert /etc/postfix/tls/tlscer.crt");
        }
        exec("sudo -u root chown -R root.root /etc/postfix/");
    }

    private function writeSASL($data)
    {
        exec("sudo -u root chown -R asterisk.asterisk /etc/postfix/");
        exec("echo '$data' > /etc/postfix/sasl/passwd");
        exec("postmap hash:/etc/postfix/sasl/passwd");
        exec("rm -rf /etc/postfix/sasl/passwd");
        exec("sudo -u root chown -R root.root /etc/postfix/");
    }

    private function restartingServices(){
        //se ejecuta de esa forma porque es usuario asterisk el que corre el programa de elastix
        exec("sudo /sbin/service generic-cloexec saslauthd restart");
        exec("sudo /sbin/service generic-cloexec postfix restart");
    }

    function checkSMTP($smtp_server, $smtp_port=25, $username, $password, $auth_enabled=false, $tls_enabled=true)
    {
        require_once("libs/phpmailer/class.smtp.php");

        $smtp = new SMTP();
        $smtp->Connect($smtp_server,$smtp_port);

        if(!$smtp->Connected()){
            return array("ERROR" => "Failed to connect to server", "SMTP_ERROR" => $smtp->getError());
        }

        if(!$smtp->Hello()){
            return array("ERROR" => "Failed to send hello command", "SMTP_ERROR" => $smtp->getError());
        }

        if($tls_enabled){
            if(!$smtp->StartTLS())
                return array("ERROR" => "Failed to start TLS", "SMTP_ERROR" => $smtp->getError());
        }

        if($auth_enabled){
            if(!$smtp->Authenticate($username,$password)){
                $error = $smtp->getError();
                if(preg_match("/STARTTLS/",$error['smtp_msg']))
                    return array("ERROR" => "Authenticate Error, TLS must be activated", "SMTP_ERROR" => $smtp->getError());
                else
                    return array("ERROR" => "Authenticate not accepted from server", "SMTP_ERROR" => $smtp->getError());
            }
        }

        return true;
    }
}
?>
