<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-28                                               |
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
  $Id: paloSantoEmailList2.class.php,v 1.1 2011-07-27 05:07:46 Alberto Santos asantos@palosanto.com Exp $ */
class paloSantoEmailList {
    private $_DB;
    private $errMsg;

    function paloSantoEmailList(&$pDB)
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

    function getError()
    {
	return $this->errMsg;
    }

    /*HERE YOUR FUNCTIONS*/

    function getNumEmailList($id_domain)
    {
	$arrParam = null;
	$where = "";
	if($id_domain != "all"){
	    $where = "where id_domain=?";
	    $arrParam = array($id_domain);
	}
        $query   = "SELECT COUNT(*) FROM email_list $where";

        $result=$this->_DB->getFirstRowQuery($query,false,$arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getEmailList($id_domain, $limit, $offset)
    {
        $where = "";
	if($id_domain != "all"){
	    $where = "where id_domain=?";
	    $arrParam = array($id_domain);
	}
	$arrParam[] = $limit;
	$arrParam[] = $offset;
        $query   = "SELECT * FROM email_list $where LIMIT ? OFFSET ?";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getTotalMembers($id)
    {
        $query = "SELECT COUNT(*) FROM member_list WHERE id_emaillist=?";

        $result=$this->_DB->getFirstRowQuery($query,false,array($id));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function isMailmanListCreated()
    {
	$sComando = '/usr/bin/elastix-helper mailman_config list_lists';
	$output = $ret = NULL;
        exec($sComando, $output, $ret);
	if($ret != 0){
	    $this->errMsg = _tr("Could not execute command list_lists");
	    return null;
	}
	else{
	    foreach($output as $list){
		if(preg_match("/^mailman[[:space:]]+\-[[:space:]]+.+$/",trim(strtolower($list))))
		    return true;
	    }
	    return false;
	}
    }

    function checkPostfixFile()
    {
        $output = $ret = NULL;
        exec('/usr/bin/elastix-helper mailman_config check_postfix_file', $output, $ret);
    }

    function domainExists($id_domain)
    {
	$query = "SELECT COUNT(*) FROM domain WHERE id=?";

        $result=$this->_DB->getFirstRowQuery($query,false,array($id_domain));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($result[0] > 0)
	    return true;
	else
	    return false;
    }

    function saveEmailList($id_domain,$namelist,$password,$emailadmin)
    {
	$query = "INSERT INTO email_list (id_domain,listname,password,mailadmin) VALUES (?,?,?,?)";
	$result = $this->_DB->genQuery($query,array($id_domain,$namelist,$password,$emailadmin));
	if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function mailmanCreateList($listName,$emailAdmin,$password,$domain="")
    {
	$sComando = "/usr/bin/elastix-helper mailman_config newlist ".escapeshellarg($listName)." ".escapeshellarg($emailAdmin)." ".escapeshellarg($password)." ".escapeshellarg($domain);
	$output = $ret = NULL;
        exec($sComando, $output, $ret);
	if($ret == 0)
	    return true;
	else
	    return false;
    }

    function getDomainName($id)
    {
	$query = "SELECT domain_name FROM domain where id=?";
	$result = $this->_DB->getFirstRowQuery($query,true,array($id));
	if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
	return $result["domain_name"];
    }

    function mailmanCreateVirtualAliases($listName,$domainName)
    {
	$sComando = "/usr/bin/elastix-helper mailman_config virtual_aliases ".escapeshellarg($listName)." ".escapeshellarg($domainName);
	$output = $ret = NULL;
        exec($sComando, $output, $ret);
	if($ret == 0)
	    return true;
	else
	    return false;
    }

    function listExistsbyName($listName)
    {
	$query = "SELECT COUNT(*) FROM email_list WHERE listname=?";

        $result=$this->_DB->getFirstRowQuery($query,false,array($listName));

        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($result[0] > 0)
	    return true;
	else
	    return false;
    }

    function listExistsbyId($id)
    {
	$query = "SELECT COUNT(*) FROM email_list WHERE id=?";

        $result=$this->_DB->getFirstRowQuery($query,false,array($id));

        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        if($result[0] > 0)
	    return true;
	else
	    return false;
    }

    function saveMember($emailMember,$idEmailList,$namemember)
    {
	$query = "INSERT INTO member_list (mailmember,id_emaillist,namemember) VALUES (?,?,?)";
	$result = $this->_DB->genQuery($query,array($emailMember,$idEmailList,$namemember));
	if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function getListName($idList)
    {
	$query  = "SELECT * FROM email_list WHERE id=?";
	$result = $this->_DB->getFirstRowQuery($query,true,array($idList));
	if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
	if(is_array($result) && isset($result['listname']))
	    return $result['listname'];
	else
	    return null;
    }

    function mailmanAddMembers($arrMembers,$id_list)
    {
	$listName = $this->getListName($id_list);
	if(is_null($listName))
	    return false;
	$listOfMembers = "";
	foreach($arrMembers as $member)
	    $listOfMembers .= $member["member"]."\n";

	$sComando = "/usr/bin/elastix-helper mailman_config add_members ".escapeshellarg($listOfMembers)." ".escapeshellarg($listName);
	$output = $ret = NULL;
        exec($sComando, $output, $ret);
	if($ret == 0)
	    return true;
	else
	    return false;
    }

    function isAMemberOfList($emailMember,$id_list)
    {
	$query = "SELECT COUNT(*) FROM member_list WHERE mailmember=? AND id_emaillist=?";

        $result=$this->_DB->getFirstRowQuery($query,false,array($emailMember,$id_list));

        if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }

        if($result[0] > 0)
	    return true;
	else
	    return false;
    }

    function deleteEmailList($id)
    {
	$query = "DELETE FROM member_list WHERE id_emaillist=?";
	$result = $this->_DB->genQuery($query,array($id));
	if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }

	$query = "DELETE FROM email_list WHERE id=?";
	$result = $this->_DB->genQuery($query,array($id));
	if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function getIdDomainofList($id_list)
    {
	$query  = "SELECT id_domain FROM email_list WHERE id=?";
	$result = $this->_DB->getFirstRowQuery($query,true,array($id_list));
	if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
	if(is_array($result) && isset($result['id_domain']))
	    return $result['id_domain'];
	else
	    return null;
    }

    function mailmanRemoveList($listName,$domainName)
    {
	$sComando = "/usr/bin/elastix-helper mailman_config remove_list ".escapeshellarg($listName)." ".escapeshellarg($domainName);
	$output = $ret = NULL;
        exec($sComando, $output, $ret);
	if($ret == 0)
	    return true;
	else
	    return false;
    }

    function removeMember($emailMember,$idEmailList)
    {
	$query = "DELETE FROM member_list WHERE mailmember=? AND id_emaillist=?";
	$result = $this->_DB->genQuery($query,array($emailMember,$idEmailList));
	if( $result == false ){
            $this->errMsg = $this->_DB->errMsg;
            return false;
        }
        return true;
    }

    function mailmanRemoveMembers($arrMembers,$id_list)
    {
	$listName = $this->getListName($id_list);
	if(is_null($listName))
	    return false;
	$listOfMembers = "";
	foreach($arrMembers as $member)
	    $listOfMembers .= $member["member"]."\n";

	$sComando = "/usr/bin/elastix-helper mailman_config remove_members ".escapeshellarg($listOfMembers)." ".escapeshellarg($listName);
	$output = $ret = NULL;
        exec($sComando, $output, $ret);
	if($ret == 0)
	    return true;
	else
	    return false;
    }

    function getMembers($limit,$offset,$id_list,$field_type,$field_pattern)
    {
	$query = "SELECT * FROM member_list WHERE id_emaillist=? ";
	$arrParam = array($id_list);
	if(strlen($field_pattern)!=0){
	    if($field_type == "name")
		$query .= "AND namemember like ? ";
	    else
		$query .= "AND mailmember like ? ";
	    $arrParam[] = "%$field_pattern%";
	}
	$query .= "LIMIT ? OFFSET ?";
	$arrParam[] = $limit;
	$arrParam[] = $offset;
	$result=$this->_DB->fetchTable($query, true, $arrParam);
	if($result===FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }
}
?>