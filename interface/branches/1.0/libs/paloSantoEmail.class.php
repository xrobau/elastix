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
} else {
    include_once("libs/paloSantoDB.class.php");
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
        $arr_result = FALSE;
        if (!is_null($id_domain) && !ereg('^[[:digit:]]+$', "$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, domain_name FROM domain".
                (is_null($id_domain) ? '' : " WHERE id = $id_domain");
            $sPeticionSQL .=" ORDER BY domain_name";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear un nuevo dominio 
     *
     * @param string    $domain_name       nombre para el dominio

     *
     * @return bool     VERDADERO si el dominio se crea correctamente, FALSO en error
     */
    function createDomain($domain_name)
    {
        $bExito = FALSE;
        //el campo ya viene validado del formulario
        //verificar que no exista ya un dominio con ese nombre
            $sPeticionSQL = "SELECT id FROM domain ".
                " WHERE domain_name = '$domain_name'";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (is_array($arr_result) && count($arr_result)>0) {
                $bExito = FALSE;
                $this->errMsg = "Domain name already exists";
            }else{
                $sPeticionSQL = paloDB::construirInsert(
                    "domain",
                    array(
                        "domain_name"       =>  paloDB::DBCAMPO($domain_name),
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            }


        return $bExito;
    }


    function updateDomain($id_domain, $domain_name)
    {
        $bExito = FALSE;
        if (!ereg("^[[:digit:]]+$", "$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } else {
            //modificar rate
                    $sPeticionSQL = paloDB::construirUpdate(
                        "domain",
                        array(
                            "domain_name"          =>  paloDB::DBCAMPO($domain_name)

                         ),
                        array(
                            "id"  => $id_domain)
                        );
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
        }
        return $bExito;
    }


    function deleteDomain($id_domain)
    {
        $bExito = TRUE;
        if (!ereg('^[[:digit:]]+$', "$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = 
                "DELETE FROM domain WHERE id = '$id_domain'";
            $bExito = $this->_DB->genQuery($sPeticionSQL);
            if (!$bExito) {
                $bExito = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
            

        }
        return $bExito;
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


    function deleteAccountsFromDomain($id_domain)
    {
        $bExito = TRUE;
        if (!ereg('^[[:digit:]]+$', "$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = 
                "DELETE FROM accountuser WHERE id_domain = '$id_domain'";
            $bExito = $this->_DB->genQuery($sPeticionSQL);
            if (!$bExito) {
                $bExito = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
            

        }
        return $bExito;
    }

    function deleteAliasesFromAccount($username)
    {
        $bExito = TRUE;
        if (!ereg('^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$', "$username")) {
            $this->errMsg = "Username is not valid";
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
        if (!is_null($username) && !ereg('^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$', "$username")) {
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
        $arr_result = FALSE;
        if (!ereg("^[[:digit:]]+$", "$id_domain")) {
            $this->errMsg = "Domain ID is not valid";
        } 
        else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT username, password, id_domain, quota FROM accountuser".
                (is_null($id_domain) ? '' : " WHERE id_domain = '$id_domain'");
            $sPeticionSQL .=" ORDER BY username";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
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
        if (!ereg('^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$', "$username")) {
            $this->errMsg = "Username is not valid";
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


    function getAliasAccount($username)
    {
        $arr_result = FALSE;
        if (!is_null($username) && !ereg('^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$', "$username")) {
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


    function updateAccount($username, $quota)
    {
        $bExito = FALSE;
        if (!is_null($username) && !ereg('^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$', "$username")) {
            $this->errMsg = "Username is not valid";
        }  else {
            //modificar cuenta
            $sPeticionSQL = paloDB::construirUpdate(
                        "accountuser",
                        array(
                            "quota"          =>  paloDB::DBCAMPO($quota)

                         ),
                        array(
                            "username"  => paloDB::DBCAMPO($username))
                        );
            if ($this->_DB->genQuery($sPeticionSQL)) {
                $bExito = TRUE;
            } else {
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $bExito;
    }
}
?>