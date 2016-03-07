<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.0-58                                               |
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
  $Id: paloSantoPasswordConnection.class.php,v 1.1 2010-12-09 02:12:32 Eduardo Cueva ecueva@palosanto.com Exp $ */
class paloSantoPasswordConnection {
    var $_DB;
    var $errMsg;

    function paloSantoPasswordConnection(&$pDB)
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

    function getNumPasswordConnection($filter_field, $filter_value)
    {
        $where = "";
        if(isset($filter_field) & $filter_field !="")
            $where = "where $filter_field like '$filter_value%'";

        $query   = "SELECT COUNT(*) FROM table $where";

        $result=$this->_DB->getFirstRowQuery($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getPasswordConnection($limit, $offset, $filter_field, $filter_value)
    {
        $where = "";
        if(isset($filter_field) & $filter_field !="")
            $where = "where $filter_field like '$filter_value%'";

        $query   = "SELECT * FROM table $where LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getPasswordConnectionById($id)
    {
        $query = "SELECT * FROM table WHERE id=$id";

        $result=$this->_DB->getFirstRowQuery($query,true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function getPassConnect()
    {
        $query = "SELECT certificate FROM general";
        $result=$this->_DB->getFirstRowQuery($query,true);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function genRandomPassword($length = 32, $certificate)
    {
        $salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $len = strlen($salt);
        $makepass = '';
        mt_srand(10000000 * (double) microtime());

        for ($i = 0; $i < $length; $i ++) {
            $makepass .= $salt[mt_rand(0, $len -1)];
        }
        $makepass .= $certificate;
        $result = hash('whirlpool', $makepass);
        $this->updateSecret($result);
        return $result;
    }

    function getNameUsers($id_user,$db,$username)
    {
        $data = array($id_user);
        $query = "SELECT name FROM acl_user WHERE id=?";
        $result = $db->getFirstRowQuery($query,true,$data);
        if($result != FALSE || $result != "")
            return $result['name'];
        else
            return $username;
    }

    function getSecretPass()
    {
        $query = "SELECT secret FROM general WHERE id=1";
        $result = $this->_DB->getFirstRowQuery($query,true);
        if($result == FALSE || $result == ""){
            $key = $this->genRandomPassword(32,"");
            return $key;
        }else
            return $result['secret'];
    }

    function updateSecret($key)
    {
        $data = array($key);
        $query = "UPDATE general set secret=? WHERE id=1";
        $result = $this->_DB->genQuery($query, $data);
        if($result == FALSE || $result == "")
            return FALSE;
        return TRUE;
    }

    function statusGeneralRegistration()
    {
	$query = "SELECT * FROM general WHERE id=1";
        $result = $this->_DB->getFirstRowQuery($query,true);
	if($result != FALSE || $result != ""){
	    $cont = 0;
	    foreach($result as $key => $value){
		if($key != "certificate"){
		    if(!isset($value) || $value=="")
			return false;
		}
		$cont++;
	    }
	    if($cont==0)
		return false; //no hay registros en base
	    else
		return true;
        }else
	    return false;
    }
}
?>