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
        $query="Select m1.*, (Select count(*) from menu m2 where m2.IdParent=m1.id) as HasChild from menu m1 order by order_no asc;";
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
            $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
            if (empty($pDB->errMsg)) {
                $uelastix = get_key_settings($pDB, 'uelastix');
                $uelastix = ((int)$uelastix != 0);
            }
            unset($pDB);
        }
        
        if ($uelastix && isset($_SESSION['elastix_user_permission']))
            return $_SESSION['elastix_user_permission'];

        // Adjuntar base de datos de ACL para acelerar búsqueda
        $bExito = $this->_DB->genQuery('ATTACH DATABASE ? AS acl', 
            array(str_replace('sqlite3:////', '/', $arrConf['elastix_dsn']['acl'])));
        if (!$bExito) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }

        // Obtener todos los módulos autorizados
        $sPeticionSQL = <<<INFO_AUTH_MODULO
SELECT id, IdParent, Link, Name, Type, order_no
FROM menu, (
    SELECT acl_resource.name AS acl_resource_name, acl_group.name AS acl_name
    FROM acl_membership, acl_group, acl_group_permission, acl_resource
    WHERE acl_membership.id_user = ?
        AND acl_membership.id_group = acl_group.id 
        AND acl_group.id = acl_group_permission.id_group 
        AND acl_group_permission.id_resource = acl_resource.id 
    UNION
    SELECT acl_resource.name AS acl_resource_name, acl_user.name AS acl_name
    FROM acl_user, acl_user_permission, acl_resource
    WHERE acl_user_permission.id_user = ?
        AND acl_user_permission.id_resource = acl_resource.id 
)
WHERE acl_resource_name = id ORDER BY order_no;
INFO_AUTH_MODULO;
        $arrMenuFiltered = array();
        $r = $this->_DB->fetchTable($sPeticionSQL, TRUE, array($idUser, $idUser));
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
        	return NULL;
        }
        $this->_DB->genQuery('DETACH DATABASE acl');
        foreach ($r as $tupla) {
        	$tupla['HasChild'] = FALSE;
            $arrMenuFiltered[$tupla['id']] = $tupla;
        }

        // Leer los menús de primer nivel
        $r = $this->_DB->fetchTable(
            'SELECT id, IdParent, Link, Name, Type, order_no, 1 AS HasChild '.
            'FROM menu WHERE IdParent = "" ORDER BY order_no', TRUE);
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
	$sQuery = "SELECT Id, Name FROM menu WHERE IdParent=''";
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

    /**
     * Procedimiento para crear un nuevo menu 
     *
     * @param string    $id       
     * @param string    $name   
     * @param string    $id_parent       
     * @param string    $type         
     * @param string    $link  
     * @param string    $order
     *
     * @return bool     VERDADERO si el menu se crea correctamente, FALSO en error
     */

    function createMenu($id,$name, $id_parent, $type='module', $link='', $order=-1)
    {
        $bExito = FALSE;
        if ($id == "" && $name == "") {
            $this->errMsg = "ID and module name can't be empty";
        } else {
            //verificar que no exista el mismo menu
            $sPeticionSQL = "SELECT id FROM menu ".
                " WHERE id = '$id' AND Name='$name' AND IdParent='$id_parent'";
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (is_array($arr_result) && count($arr_result)>0) {
                $bExito = FALSE;
                $this->errMsg = "Menu already exists";
            }else{
		if($order!=-1){
                  $sPeticionSQL = paloDB::construirInsert("menu",
                    array(
                        "id"        =>  paloDB::DBCAMPO($id),
                        "Name"      =>  paloDB::DBCAMPO($name),
                        "Type"      =>  paloDB::DBCAMPO($type),
                        "Link"      =>  paloDB::DBCAMPO($link),
                        "IdParent"  =>  paloDB::DBCAMPO($id_parent),
                        "order_no"  =>  paloDB::DBCAMPO($order),
                      )
                    );

                }
                else{
                  $sPeticionSQL = paloDB::construirInsert("menu",
                    array(
                        "id"        =>  paloDB::DBCAMPO($id),
                        "Name"      =>  paloDB::DBCAMPO($name),
                        "Type"      =>  paloDB::DBCAMPO($type),
                        "Link"      =>  paloDB::DBCAMPO($link),
                        "IdParent"  =>  paloDB::DBCAMPO($id_parent),
                      )
                    );
                }

                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            }
        }

        return $bExito;
    }
    
    /*********************************************************************************************/
    function updateItemMenu($id, $name, $id_parent, $type='module', $link='', $order=-1){
        $bExito = FALSE;
        if ($id == "" && $name == "") {
            $this->errMsg = "ID and module name can't be empty";
        }else{
            $query = "";
            if($order != -1){
                $query = "UPDATE menu SET ".
                    "Name='$name', IdParent='$id_parent', Link='$link', Type='$type', order_no='$order'".
                    " WHERE id = '$id'";
            }else{
                $query = "UPDATE menu SET ".
                    "Name='$name', IdParent='$id_parent', Link='$link', Type='$type'".
                    " WHERE id = '$id'";
            }
            $result=$this->_DB->genQuery($query);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return 0;
            }
            return 1;
        }
    }



/**********************************************************************************************/

    function existeMenu($id_menu){
        $bExiste=false;
            //verificar que no exista el mismo menu
        $sPeticionSQL = "SELECT id FROM menu WHERE id = '$id_menu'";
        $arr_result =& $this->_DB->getFirstRowQuery($sPeticionSQL);
        if (count($arr_result)>0)
        {
            $bExiste=true;
        }
        return $bExiste;
    }

 /**
     * This function is for obtaining all the submenu from menu 
     *
     * @param string    $menu_name   The name of the main menu or menu father       
     *
     * @return array    $result      An array of children or submenu where the father or main menu is $menu_name
     */
   function getChilds($menu_name){
        $query   = "SELECT * FROM menu where IdParent='$menu_name'";
        $result=$this->_DB->fetchTable($query, true);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
   }

 /**
     * This function delete a specific menu from database 
     *
     * @param string    $menu_name   The name of the main menu or menu father       
     *  
     * @return int      An integer where such integer let us know if the menu was or not was removed from database
     */

    function deleteChilds($menu_name){
        $query   = "DELETE FROM menu where Id='$menu_name'";
        $result=$this->_DB->genQuery($query);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return 1;
    }

/**
     * This function is a recursive function. The input is the name of main menu or father menu which will be removed from database with all children and the children of its children 
     *
     * @param string    $menu_name   The name of the main menu or menu father       
     * @param object    $acl   		 The class object ACL
     *  
     * @return $menu_name   The menu which will be removed
     */

    function deleteFather($menu_name,&$acl){
        $childs = $this->getChilds($menu_name);
        if(!$childs){
            $id_resource = $acl->getIdResource($menu_name); // get id Resource
            $ok1 = $acl->deleteIdGroupPermission($id_resource); // remove group permission
            $ok2 = $acl->deleteIdResource($id_resource); // remove resource
            $ok3 = $this->deleteChilds($menu_name); // remove child
            return ($ok1 and $ok2 and $ok3);
        }
        else{
            foreach($childs as $key => $value){
                $ok = $this->deleteFather($value['id'],$acl);
                if(!$ok) return false;
            }

            $id_resource = $acl->getIdResource($menu_name); // get id Resource
            $ok1 = $acl->deleteIdGroupPermission($id_resource); // remove group permission
            $ok2 = $acl->deleteIdResource($id_resource); // remove resource
            $ok3 = $this->deleteChilds($menu_name); // remove child*/
            return ($ok1 and $ok2 and $ok3);
        }
    }
}
?>
