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
  $Id: paloSantoMenu.class.php,v 1.2 2007/09/05 00:25:25 gcarrillo Exp $ */

if (isset($arrConf['basePath'])) {
    include_once($arrConf['basePath'] . "/libs/paloSantoDB.class.php");
} else {
    include_once("libs/paloSantoDB.class.php");
}

class paloMenu {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloMenu(&$pDB)
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

    function cargar_menu()
    {
       //leer el contenido de la tabla menu y devolver un arreglo con la estructura
        $menu = array ();
        $query="Select m1.id, m1.IdParent, m1.Link, m1.description, m1.Type, m1.order_no,".
               "(Select count(*) from acl_resource m2 where m2.IdParent=m1.id) as HasChild from acl_resource m1 order by order_no asc;";
        $oRecordset = $this->_DB->fetchTable($query, true);
        if ($oRecordset){
            foreach($oRecordset as $key => $value)
            {
                if($value['HasChild']>0)
                    $value['HasChild'] = true;
                else $value['HasChild'] = false;
                $menu[$value['id']]= $value;
            }
        }
        return $menu;
    }

    function filterAuthorizedMenus($idUser)
    {
    	global $arrConf;

        $uelastix = FALSE;
        if (isset($_SESSION)) {
            $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
            if (empty($pDB->errMsg)) {
                $uelastix = get_key_settings($pDB, 'uelastix');
                $uelastix = ((int)$uelastix != 0);
            }
            unset($pDB);
        }
        
        if ($uelastix && isset($_SESSION['elastix_user_permission']))
            return $_SESSION['elastix_user_permission'];

        // Obtener todos los módulos autorizados
       $sPeticionSQL = <<<INFO_AUTH_MODULO
SELECT ar.id, ar.IdParent, ar.Link, ar.description, ar.Type, ar.order_no
       FROM acl_resource ar where id in (
	   SELECT ogr.id_resource From organization_resource as ogr
			JOIN group_resource as gr on ogr.id=gr.id_org_resource
			where gr.id_group=(Select u.id_group from acl_user as u where u.id=?) )
	   ORDER BY order_no;
INFO_AUTH_MODULO;
        $arrMenuFiltered = array();

        $r = $this->_DB->fetchTable($sPeticionSQL, TRUE, array($idUser));
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
        	return NULL;
        }

        foreach ($r as $tupla) {
        	$tupla['HasChild'] = FALSE;
            $arrMenuFiltered[$tupla['id']] = $tupla;
        }

        // Leer los menús de primer nivel
        $r = $this->_DB->fetchTable(
            'SELECT id, IdParent, Link, description, Type, order_no, 1 AS HasChild '.
            'FROM acl_resource WHERE IdParent = "" ORDER BY order_no', TRUE);
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        $menuPrimerNivel = array();
        foreach ($r as $tupla) {
            $tupla['HasChild'] = (bool)$tupla['HasChild'];
            $menuPrimerNivel[$tupla['id']] = $tupla;
        }

        // Resolver internamente las referencias de menú superior
        $menuSuperior = array();
        foreach (array_keys($arrMenuFiltered) as $k) {
        	$kp = $arrMenuFiltered[$k]['IdParent'];
            if (isset($arrMenuFiltered[$kp])) {
            	$arrMenuFiltered[$kp]['HasChild'] = TRUE;
            } elseif (isset($menuPrimerNivel[$kp])) {
                $menuSuperior[$kp] = $kp;
            } else {
                // Menú es de segundo nivel y no estaba autorizado
                unset($arrMenuFiltered[$k]);
            }
        }

        // Copiar al arreglo filtrado los menús de primer nivel EN EL ORDEN LEÍDO
        $arrMenuFiltered = array_merge(
            $arrMenuFiltered,
            array_intersect_key($menuPrimerNivel, $menuSuperior));
                
        if ($uelastix) $_SESSION['elastix_user_permission'] = $arrMenuFiltered;
        return $arrMenuFiltered;
    }

    /**
     * Procedimiento para obtener el listado de los menus
     *
     * @return array    Listado de menus
     */
    function getRootMenus()
    {
        $this->errMsg = "";
        $listaMenus = array();
	$sQuery = "SELECT id, description FROM acl_resource WHERE IdParent=''";
	$arrMenus = $this->_DB->fetchTable($sQuery);
        if (is_array($arrMenus)) {
	   foreach ($arrMenus as $menu)
            {
                $listaMenus[$menu[0]]=$menu[1];
            }
        }else
        {
            $this->errMsg = $this->_DB->errMsg;
        }
        return $listaMenus;

    }

    
    /*********************************************************************************************/
    function updateItemMenu($name, $description, $id_parent, $type='module', $link='', $order=-1){
        $bExito = FALSE;
        if ($name == "" && $description == "") {
            $this->errMsg = "Name and description module can't be empty";
        }else{
            $query = "";
            if($order != -1){
                $query = "UPDATE acl_resource SET ".
                    "description=?, IdParent=?, Link=?, Type=?, order_no=?".
                    " WHERE id=?";
				$arrayParam=array($id_parent,$link,$type,$order,$name);
            }else{
                $query = "UPDATE acl_resource SET ".
                    "description=?, IdParent=?, Link=?, Type=?".
                    " WHERE id=?";
				$arrayParam=array($id_parent,$link,$type,$name);
            }
            $result=$this->_DB->genQuery($query,$arrayParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return 0;
            }
            return 1;
        }
    }


 /**
     * This function is for obtaining all the submenu from menu 
     *
     * @param string    $menu_name   The name of the main menu or menu father       
     *
     * @return array    $result      An array of children or submenu where the father or main menu is $menu_name
     */
   function getChilds($menu_name){
        $query   = "SELECT id, IdParent, Link, description, Type, order_no FROM acl_resource where IdParent=?";
        $result=$this->_DB->fetchTable($query, true, array($menu_name));
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
   }


/**
     * This function is a recursive function. The input is the name of main menu or father menu which will be removed from database with all children and the children of its children 
     *
     * @param string    $menu_name   The name of the main menu or menu father       
     * @param object    $acl   		 The class object ACL
     *  
     * @return $menu_name   The menu which will be removed
     */
    function deleteFather($name){
		$pACL = new paloACL($this->_DB);
        $childs = $this->getChilds($name);
        if(!$childs){
			//$id_resource = $pACL->getIdResource($name); // get id Resource
			if($pACL->deleteResource($name))
				return true;
			else{
				$this->errMsg=$pACL->errMsg;
				return false;
			}
        }
        else{
            foreach($childs as $key => $value){
                $ok = $this->deleteFather($value['id'],$acl);
                if(!$ok) return false;
            }

            //$id_resource = $pACL->getIdResource($name); // get id Resource
			if($pACL->deleteResource($name))
				return true;
			else{
				$this->errMsg=$pACL->errMsg;
				return false;
			}
        }
    }
}
?>