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
  $Id: paloSantoNavigation.class.php,v 1.2 2007/09/07 00:20:03 gcarrillo Exp $ */

class paloSantoNavigation {

    var $defaultMenu;
    var $arrMenu;
    var $smarty;
    var $currMainMenu;
    var $currSubMenu;
    var $currSubMenu2;

    function paloSantoNavigation($arrConf, $arrMenu, &$smarty)
    {
        // El defaultManu deberia ser el primer submenu
        foreach($arrMenu as $idMenu=>$arrMenuItem) {
            if(empty($arrMenuItem['IdParent'])) {
                $this->defaultMenu = $idMenu;
                break;
            }
        }
        //$this->defaultMenu = $arrConf['defaultMenu'];
        $this->arrMenu = $arrMenu;
        $this->smarty  = &$smarty;
    }


    function getArrParentIds($idMenuSelected)  
    {
        $idMenuActual = $this->getIdParentMenu($idMenuSelected);
        $limite=10;
        $arrIdParentMenus=array();
        for($i=1; $i<=$limite; $i++) { // Le pongo un limite de 10 iteraciones. No creo que hayan mas de 10 menus padres
            if($idMenuActual=="") {
                break;
            } else {
                $arrIdParentMenus[]=$idMenuActual;
                $idMenuActual=$this->getIdParentMenu($idMenuActual);    
            }
            if($i==$limite) {
                // Si llego hasta aqui, probablemente no encontre el menu raiz
                return false; // Hmm... no estoy seguro de esto
            }
        }

       $arrResult=array_reverse($arrIdParentMenus);
       return $arrResult; 
    }

    function getArrChildrenIds($idMenuSelected)
    {
        $limite=10;
        $arrResult = array();
        $idSubMenu=$this->getIdFirstSubMenu($idMenuSelected);
        for($i=1; $i<=$limite; $i++) {
            if($idSubMenu==false) {
                break;  
            } else {
                $arrResult[]=$idSubMenu;
                $idSubMenu=$this->getIdFirstSubMenu($idSubMenu);
            }
        }
        return $arrResult;
    }

    // Esta funcion muestra el menu que se debe presentar si el menu idMenuSelected
    // ha sido cliqueado
    function showMenu($idMenuSelected)
    {
        $arrIds=array();
        // Valido el menu que se paso como argumento
        if(!empty($idMenuSelected) and $this->isValidMenu($idMenuSelected)) {

            // Debo encontrar entonces sus menus padres y sus menus hijos
    
            // Encuentro todos los menus padres
            $arrIdParentMenus=$this->getArrParentIds($idMenuSelected);
            if(is_array($arrIdParentMenus)) {
                $arrIds=$arrIdParentMenus;
            }
            // Le sumo el menu actual
            $arrIds[]=$idMenuSelected;
    
            // Encuentro los menus hijos. 
            $arrIdChildrenMenus=$this->getArrChildrenIds($idMenuSelected);

            if(is_array($arrIdChildrenMenus)) {
                foreach($arrIdChildrenMenus as $elemento) {
                    $arrIds[]=$elemento;
                }
            }
   
            //print_r($arrIds); 
            // En este punto $arrIds deberia contener los Ids activos de cada menu para los n niveles
            // Como por ahora manejamos hasta 3 niveles unicamente voy a mapear los 3 primeros elementos
           
            $currMainMenu=NULL;
            $currSubMenu =NULL;
            $currSubMenu2=NULL;

            if(count($arrIds)==1){
                $currMainMenu=$arrIds[0];
            }
            if(count($arrIds)==2){
                $currMainMenu=$arrIds[0];
                $currSubMenu =$arrIds[1];
            }
            if(count($arrIds)==3){
                $currMainMenu=$arrIds[0];
                $currSubMenu =$arrIds[1];
                $currSubMenu2=$arrIds[2];
            }

        } else {
            // Is not a valid menu
            $currMainMenu = $this->defaultMenu;
            $currSubMenu  = $this->getIdFirstSubMenu($currMainMenu);
            $currSubMenu2 = $this->getIdFirstSubMenu($currSubMenu);
        }

        $this->currMainMenu = $currMainMenu;
        $this->currSubMenu  = $currSubMenu;
        $this->currSubMenu2 = $currSubMenu2;

        // Get the main menu
        $arrMainMenu = $this->getArrSubMenu("");
        $this->smarty->assign("arrMainMenu", $arrMainMenu);

        // Get the submenu
        $arrSubMenu = $this->getArrSubMenu($currMainMenu); 
        $this->smarty->assign("arrSubMenu", $arrSubMenu);

        // Get the 3th level menu
        $arrSubMenu2 = $this->getArrSubMenu($currSubMenu); 
        $this->smarty->assign("arrSubMenu2", $arrSubMenu2);

        $this->smarty->assign("idMainMenuSelected",   $currMainMenu);
        $this->smarty->assign("idSubMenuSelected",    $currSubMenu);
        $this->smarty->assign("idSubMenu2Selected",    $currSubMenu2);
        $this->smarty->assign("nameMainMenuSelected", $arrMainMenu[$currMainMenu]['Name']);
        $this->smarty->assign("nameSubMenuSelected",  $arrSubMenu[$currSubMenu]['Name']);
        $this->smarty->assign("nameSubMenu2Selected",  $arrSubMenu2[$currSubMenu2]['Name']);

        /*************** Submenus para template elastix wine ********************/
        $arrSubMenuSystem = $this->getArrSubMenu("system");
        $this->smarty->assign("arrMenuSystem", $arrSubMenuSystem);
        $arrSubMenuPbx = $this->getArrSubMenu("pbxconfig");
        $this->smarty->assign("arrMenuPbx", $arrSubMenuPbx);
        $arrSubMenuFax = $this->getArrSubMenu("fax");
        $this->smarty->assign("arrMenuFax", $arrSubMenuFax);
        $arrSubMenuEmail = $this->getArrSubMenu("email");
        $this->smarty->assign("arrMenuEmail", $arrSubMenuEmail);
        $arrSubMenuIm = $this->getArrSubMenu("im");
        $this->smarty->assign("arrMenuIm", $arrSubMenuIm);
        $arrSubMenuReports = $this->getArrSubMenu("reports");
        $this->smarty->assign("arrMenuReports", $arrSubMenuReports);
        $arrSubMenuExtras = $this->getArrSubMenu("extras");
        $this->smarty->assign("arrMenuExtras", $arrSubMenuExtras);
        $arrSubMenuExtras = $this->getArrSubMenu("call_center");
        $this->smarty->assign("arrMenuCallCenter", $arrSubMenuExtras);
        /*************** Submenus para template elastix wine ********************/

        return $this->smarty->fetch("_common/_menu.tpl");
    }


    function getIdParentMenu($id)
    {
        // verificar que $this->arrMenu[$id] exista
        return $this->arrMenu[$id]['IdParent'];
    }

    function isValidMenu($id)
    {
        return array_key_exists($id, $this->arrMenu);
    }

    function getArrSubMenu($idParent)
    {
        $arrSubMenu = array();
        foreach($this->arrMenu as $id => $element) {
            if($element['IdParent']==$idParent) {
                $arrSubMenu[$id] = $element;
            }
        }
        if(count($arrSubMenu)<=0) return false;
        return $arrSubMenu;
    }

    function getIdFirstSubMenu($idParent)
    {
        $arrSubMenu=$this->getArrSubMenu($idParent);
        if($arrSubMenu==false) return false;
        list($id, $value) = each($arrSubMenu);
        return $id;
    }

    function showContent()
    {
	$bMostrarModulo = false;
	$bSubMenu2Framed = false;
        if($this->arrMenu[$this->currSubMenu]['Type']=='module') {
	    $bMostrarModulo = true;

            if(!empty($this->currSubMenu2) && $this->arrMenu[$this->currSubMenu2]['Type']=='module') {
                $ultimoMenu=$this->currSubMenu2;
            } elseif (empty($this->currSubMenu2)) {
                $ultimoMenu=$this->currSubMenu;
            }else{
		 $bSubMenu2Framed = true;
		 $bMostrarModulo = false;
            }
        }
	 if ($bMostrarModulo){
            return $this->includeModule($ultimoMenu);
        }
        else {
/*
            $retVar .= "<iframe id=\"myframe\" src=\"" . $this->arrMenu[$this->currSubMenu]['Link'] . "\" scrolling=\"no\" marginwidth=\"0\" ";
            $retVar .= " marginheight=\"0\" ";
            $retVar .= " frameborder=\"0\" vspace=\"0\" hspace=\"0\" style=\"overflow:visible; width:100%; display:none\"></iframe>";
*/
/*
            $retVar  = "<iframe marginwidth=\"0\" marginheight=\"0\" style=\"border: 1px solid rgb(200, 200, 200); background-color: rgb(255, 255, 255);";
            $retVar .= "\" src=\"" . $this->arrMenu[$this->currSubMenu]['Link'] . "\" name=\"myframe\" id=\"myframe\" frameborder=\"0\"";
            $retVar .= " width=\"100%\"></iframe>";
*/ 
            /*Version 0.9 agregado variable $ip*/
            $name_server = $this->obtenerNameServer();	
            $ip_server   = $this->obtenerIpServer("eth0");
            if($ip_server==null)
                $ip_server="127.0.0.1";
	    $link=$bSubMenu2Framed?$this->arrMenu[$this->currSubMenu2]['Link']:$this->arrMenu[$this->currSubMenu]['Link'];
            $link = str_replace("{NAME_SERVER}",$name_server,$link);
            $link = str_replace("{IP_SERVER}",$ip_server,$link);

            $retVar  = "<iframe marginwidth=\"0\" marginheight=\"0\" style=\"border: 1px solid rgb(200, 200, 200); background-color: rgb(255, 255, 255);";
            $retVar .= "\" src=\"" . $link . "\" name=\"myframe\" id=\"myframe\" frameborder=\"0\"";
            $retVar .= " width=\"100%\" onLoad=\"calcHeight();\"></iframe>"; 
        }
        return $retVar;
    }

    function includeModule($module)
    {
        if(file_exists("modules/$module/index.php")) {
            include "modules/$module/index.php";
            if(function_exists("_moduleContent")) {
                return _moduleContent($this->smarty,$module);
            } else {
                return "Wrong module: modules/$module/index.php";
            }
        } else {
            return "Error: The module <b>modules/$module/index.php</b> could not be found<br>";
        }
    }

    function obtenerIpServer($eth)
    {
        exec("which ifconfig 2>/dev/null||echo /sbin/ifconfig",$arrSalidaIfConfig,$flagSalidaIfConfig);
        if($flagSalidaIfConfig==0 && is_array($arrSalidaIfConfig)  && count($arrSalidaIfConfig)>0){
            exec("$arrSalidaIfConfig[0] $eth|gawk '/inet addr/{print $2}'|gawk -F: '{print $2}'",$arrSalidaIpServer,$flagSalidaIpServer);
            if($flagSalidaIpServer==0 && is_array($arrSalidaIpServer)  && count($arrSalidaIpServer)>0){
                return $arrSalidaIpServer[0];
            }
            return false;
        }
        return false;
    }

    function obtenerNameServer()
    {
        return $_SERVER['SERVER_NAME']; 
    }

    function ObtenerMenu()
    {
    }
}
?>