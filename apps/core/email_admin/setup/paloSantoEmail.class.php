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
} else {
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoConfig.class.php");
    include_once("libs/misc.lib.php");
}

class paloEmail {

    private $_DB; // instancia de la clase paloDB
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
     * se especifica el id de dominio, el listado contendrá únicamente el dominio
     * indicado por su respectivo. De otro modo, se listarán todos los dominios.
     *
     * @param int   $id_domain    Si != NULL, indica el id del dominio a recoger
     *
     * @return array    Listado de dominios en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, domain_name),
     *      ...
     *  )
     */
    function getDomains($id_domain = NULL)
    {
        $this->errMsg = '';
        $arr_result = FALSE;
        if (!is_null($id_domain) && !ctype_digit("$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } else {
            $sPeticionSQL = 'SELECT id, domain_name FROM domain';
            $paramSQL = array();
            if (!is_null($id_domain)) {
            	$sPeticionSQL .= ' WHERE id = ?';
                $paramSQL[] = $id_domain;
            }
            $sPeticionSQL .= ' ORDER BY domain_name';
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL, FALSE, $paramSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear un nuevo dominio. La ejecución del script 
     * privilegiado ya se encarga de actualizar la base de datos así que no es
     * necesario hacerlo aquí.
     *
     * @param string    $domain_name       nombre para el dominio
     *
     * @return bool     VERDADERO si el dominio se crea correctamente, FALSO en error
     */
    function createDomain($domain_name)
    {
        $this->errMsg = '';
        $output = $retval = NULL;
        $sComando = '/usr/bin/elastix-helper email_account --createdomain '.
            escapeshellarg($domain_name).' 2>&1';
        exec($sComando, $output, $retval);
        if ($retval != 0) {
            foreach ($output as $s) {
                $regs = NULL;
                if (preg_match('/^ERR: (.+)$/', trim($s), $regs)) {
                    $this->errMsg = $regs[1];
                }
            }
            if ($this->errMsg == '')
                $this->errMsg = implode('<br/>', $output);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para borrar un dominio y todas sus cuentas asociadas.
     *
     * @param string    $domain_name       nombre para el dominio
     *
     * @return bool     VERDADERO si el dominio se borra correctamente, FALSO en error
     */
    function deleteDomain($domain_name)
    {
        $this->errMsg = '';
        $output = $retval = NULL;
        $sComando = '/usr/bin/elastix-helper email_account --deletedomain '.
            escapeshellarg($domain_name).' 2>&1';
        exec($sComando, $output, $retval);
        if ($retval != 0) {
            foreach ($output as $s) {
                $regs = NULL;
                if (preg_match('/^ERR: (.+)$/', trim($s), $regs)) {
                    $this->errMsg = $regs[1];
                }
            }
            if ($this->errMsg == '')
                $this->errMsg = implode('<br/>', $output);
            return FALSE;
        }
        return TRUE;
    }

    function getNumberOfAccounts($id_domain){
        $number =0;
        $sPeticionSQL = "SELECT count(*) FROM accountuser ".
                " WHERE id_domain = '$id_domain'";
        $arr_result =& $this->_DB->getFirstRowQuery($sPeticionSQL);
        if (is_array($arr_result) && count($arr_result)>0) {
                $number=$arr_result[0];
        }
        return $number;
    }


    private function deleteAliasesFromAccount($username)
    {
        $bExito = TRUE;
        $configPostfix2 = isPostfixToElastix2();// in misc.lib.php
        $regularExpresion = "";
        if($configPostfix2)
           $regularExpresion = '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/';
        else
           $regularExpresion = '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';
        if (!preg_match($regularExpresion, "$username")) {
            $this->errMsg = "Username is not valid";
            $bExito = FALSE;
        }
        else {
            $this->errMsg = "";
            $sPeticionSQL = 
                "DELETE FROM virtual WHERE username = '$username'";
            $bExito = $this->_DB->genQuery($sPeticionSQL);
            if (!$bExito) {
                $bExito = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $bExito;
    }


    function getAccount($username)
    {
        $arr_result = FALSE;
        $configPostfix2 = isPostfixToElastix2();// in misc.lib.php
        $regularExpresion = "";
        if($configPostfix2)
           $regularExpresion = '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/';
        else
           $regularExpresion = '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';
        if (!is_null($username) && !preg_match($regularExpresion, "$username")) {
            $this->errMsg = "Username is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT username, password, id_domain, quota FROM accountuser".
                (is_null($username) ? '' : " WHERE username = '$username'");
            $sPeticionSQL .=" ORDER BY username";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result) && count($arr_result)>0) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    function getAccountsByDomain($id_domain)
    {
        $this->errMsg = '';
        $arr_result = FALSE;
        if (!ctype_digit("$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } else {
            $sPeticionSQL = 'SELECT username, password, id_domain, quota FROM accountuser';
            $paramSQL = array();
            if (!is_null($id_domain)) {
                $sPeticionSQL .= ' WHERE id_domain = ?';
                $paramSQL[] = $id_domain;
            }
            $sPeticionSQL .= ' ORDER BY username';
            $arr_result = $this->_DB->fetchTable($sPeticionSQL, FALSE, $paramSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear una nueva cuenta
     *
     * @param string    $domain_name       nombre para el dominio
     *
     * @return bool     VERDADERO si el dominio se crea correctamente, FALSO en error
     */
    function createAccount($id_domain,$username,$password,$quota)
    {
        $bExito = FALSE;

        $sPeticionSQL = paloDB::construirInsert(
                    "accountuser",
                    array(
                        "id_domain"   =>  paloDB::DBCAMPO($id_domain),
                        "username"    =>  paloDB::DBCAMPO($username),
                        "password"    =>  paloDB::DBCAMPO($password),
                        "quota"       =>  paloDB::DBCAMPO($quota),
                    )
                );
        if ($this->_DB->genQuery($sPeticionSQL)) {
            $bExito = TRUE;
        } else {
            $this->errMsg = $this->_DB->errMsg;
        }
        return $bExito;
    }


    function deleteAccount($username)
    {
        $bExito = TRUE;
        $configPostfix2 = isPostfixToElastix2();// in misc.lib.php
        $regularExpresion = "";
        if($configPostfix2)
           $regularExpresion = '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/';
        else
           $regularExpresion = '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';
        if (!preg_match($regularExpresion, "$username")) {
            $this->errMsg = "Username is not valid";
            $bExito = FALSE;
        }
        else {
            $this->errMsg = "";
            $sPeticionSQL = 
                "DELETE FROM accountuser WHERE username = '$username'";
            $bExito = $this->_DB->genQuery($sPeticionSQL);
            if (!$bExito) {
                $bExito = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $bExito;
    }

    function createAliasAccount($username,$alias)
    {
        $bExito = FALSE;

        $sPeticionSQL = paloDB::construirInsert(
                    "virtual",
                    array(
                        "username"    =>  paloDB::DBCAMPO($username),
                        "alias"    =>  paloDB::DBCAMPO($alias),
                    )
                );
        if ($this->_DB->genQuery($sPeticionSQL)) {
            $bExito = TRUE;
        } else {
            $this->errMsg = $this->_DB->errMsg;
        }
        return $bExito;
    }


    private function getAliasAccount($username)
    {
        $arr_result = FALSE;
        $configPostfix2 = isPostfixToElastix2();// in misc.lib.php
        $regularExpresion = "";

        if($configPostfix2)
           $regularExpresion = '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/';
        else
           $regularExpresion = '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';

        if (!is_null($username) && !preg_match($regularExpresion, "$username")) {
            $this->errMsg = "Username is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, alias FROM virtual ".
                            "WHERE username = '$username' ";
            $sPeticionSQL .="ORDER BY alias";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    //***** new functions from email_functions.lib.php ***************************************************************/

    function eliminar_usuario_correo_sistema($username,$email,&$error){
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

    function eliminar_virtual_sistema($email,&$error){
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

    function crear_usuario_correo_sistema($email,$username,$clave,&$error,$virtual=TRUE){
        $output=array();
        $configPostfix2 = isPostfixToElastix2();
        if($configPostfix2){
            exec("echo ".escapeshellarg($clave)." | sudo -u root /usr/sbin/saslpasswd2 -c ".escapeshellarg($email),$output);
        }else{
            exec("echo ".escapeshellarg($clave)." | sudo -u root /usr/sbin/saslpasswd2 -c ".escapeshellarg($username)." -u ".SASL_DOMAIN,$output);
        }

        if(is_array($output) && count($output)>0){
            foreach($output as $linea_salida)
                $error.=$linea_salida."<br>";
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

    private function crear_virtual_sistema($email,$username,&$error){
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

    function eliminar_cuenta($db,$username,$errMsg, $virtual=TRUE){
        global $CYRUS;
        $arr_alias=array();

        //primero se obtienen las direcciones de mail del usuario (virtuales)
        $arrAlias = $this->getAliasAccount($username);
        if (is_array($arrAlias)){
            foreach ($arrAlias as $fila)
                $arr_alias[]=$fila[1];
        }
        $bExito = $this->deleteAliasesFromAccount($username); // elimina los aliases de la base de datos
        if($bExito){
            $bExito = $this->deleteAccount($username);
            if ($bExito){
                $cyr_conn = new cyradm;
                $bValido = $cyr_conn->imap_login();

                if ($bValido ===FALSE){
                    $errMsg = $cyr_conn->getMessage();
                    return FALSE;
                }

                $bValido=$cyr_conn->deletemb("user/".$username); // elimina los buzones de entrada
                if($bValido===FALSE){
                    $errMsg=$cyr_conn->getMessage();
                    return FALSE;
                }
                //$cyr_conn->deletemb("user/".$username)."<br>";

                foreach($arr_alias as $alias){
                    if(!$this->eliminar_usuario_correo_sistema($username,$alias,$errMsg)){ // elimina los usuarios del sistema
                        return FALSE;
                    }
                }
                if($virtual)
                    $this->eliminar_virtual_sistema($username,$errMsg); // elimina los alias en /etc/postfix/virtual
                return TRUE;
            }
        }else{
            $bExito = FALSE;
        }
        return $bExito;
    }

    function getListByDomain($id_domain)
    {
         $number = 0;
         $data = array($id_domain);
         $sPeticionSQL = "SELECT id FROM email_list WHERE id_domain = ?";
         $arr_result = $this->_DB->fetchTable($sPeticionSQL,TRUE,$data);
         if (is_array($arr_result) && count($arr_result)>0) {
             $number=$arr_result[0];
         }
         return $number;
    }

    function accountExists($account)
    {
        $query = "SELECT COUNT(*) FROM accountuser WHERE username=?";
        $result = $this->_DB->getFirstRowQuery($query,false,array($account));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($result[0] > 0)
            return true;
        else
            return false;
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
                                $this->errMsg = "Username format is not valid";
                        }else{
                                exec('/usr/bin/elastix-helper email_account --reconstruct_mailbox  --mailbox '.escapeshellarg($username).' 2>&1', $output, $retval);
                        }
                }else{
                        $this->errMsg = "Username must not be null";
                }

                if ($retval != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }

                return TRUE;
    }

    /**
     * Obtener la cuota del correo del usuario indicado.
     * 
     * @param string    $username   Correo completo usuario@dominio.com
     * 
     * @return mixed    Arreglo (used,qmax) o NULL en caso de error
     */
    function getAccountQuota($username)
    {
        $this->errMsg = '';
        $bPostfixElastix2 = isPostfixToElastix2();
        $regexp = $bPostfixElastix2
            ? '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/'
            : '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';
        if (!preg_match($regexp, $username)) {
            $this->errMsg = _tr('Username is not valid');
        	return NULL;
        }

        $cyr_conn = new cyradm;
        if (!$cyr_conn->imap_login()) {
            $this->errMsg = _tr('Failed to login to IMAP');
            return NULL;
        }
        $quota = $cyr_conn->getquota('user/'.$username);
        $cyr_conn->imap_logout();
        return $quota;
    }
    
    /**
     * Actualizar la cuota del usuario indicado, tanto en cyrus como en la DB.
     * 
     * @param string    $username   Correo completo usuario@dominio.com
     * @param int       $newquota   Nueva cuota de correo a asignar
     * 
     * @return bool     VERDADERO en caso de éxito, FALSO en caso de error.
     */
    function setAccountQuota($username, $newquota)
    {
        $this->errMsg = '';
        $bPostfixElastix2 = isPostfixToElastix2();
        $regexp = $bPostfixElastix2
            ? '/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,6})+$/'
            : '/^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$/';
        if (!preg_match($regexp, $username)) {
            $this->errMsg = _tr('Username is not valid');
            return FALSE;
        }

        $cyr_conn = new cyradm;
        if (!$cyr_conn->imap_login()) {
            $this->errMsg = _tr('Failed to login to IMAP');
            return NULL;
        }
        $bExito = $cyr_conn->setmbquota('user/'.$username, $newquota);
        if (!$bExito) $this->errMsg = $cyr_conn->getMessage();
        $cyr_conn->imap_logout();
        if ($bExito) {
        	$sPeticionSQL = 'UPDATE accountuser SET quota = ? WHERE username = ?';
            $bExito = $this->_DB->genQuery($sPeticionSQL, array($newquota, $username));
            if (!$bExito) $this->errMsg = $this->_DB->errMsg;
        }
        return $bExito;
    }
}
?>