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

define('MENUTAG', 'description');

class paloSantoNavigation {

    private $defaultMenu;
    private $arrMenu;
    private $smarty;
    private $currMainMenu;
    var $currSubMenu;
    private $currSubMenu2;
    private $currSubMenuByParents;

    function paloSantoNavigation($arrConf, $arrMenu, &$smarty)
    {
        // El defaultMenu deberia ser el primer submenu
        foreach($arrMenu as $idMenu=>$arrMenuItem) {
            if(empty($arrMenuItem['IdParent'])) {
                $this->defaultMenu = $idMenu;
                break;
            }
        }
        $this->arrMenu = $arrMenu;
        $this->smarty  = &$smarty;
    }


    private function getArrParentIds($idMenuSelected)
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

    private function getArrChildrenIds($idMenuSelected)
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
		$isThirdLevel = "off";////////////////////////////////////////////////////////
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

            // En este punto $arrIds deberia contener los Ids activos de cada menu para los n niveles
            // Como por ahora manejamos hasta 3 niveles unicamente voy a mapear los 3 primeros elementos

            $currMainMenu=NULL;
            $currSubMenu =NULL;
            $currSubMenu2=NULL;
            $currSubMenuByParents=NULL;

            if(count($arrIds)==1){
                $currMainMenu=$arrIds[0];
            }
            if(count($arrIds)==2){
                $currMainMenu=$arrIds[0];
                $currSubMenu =$arrIds[1];
                $currSubMenuByParents=$arrIds[1];
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
            $currSubMenuByParents = $this->getIdFirstSubMenu($currMainMenu);
        }

        $this->currMainMenu = $currMainMenu;
        $this->currSubMenu  = $currSubMenu;
        $this->currSubMenu2 = $currSubMenu2;
        $this->currSubMenuByParents  = $currSubMenuByParents;

        // Get the main menu
        $arrMainMenu = $this->getArrSubMenu("");

		/************* para elastixneo**********/
		global $arrConf;
		if($arrConf['mainTheme']=="elastixneo"){
            /* El tema elastixneo muestra hasta 7 items de menú de primer nivel,
             * y coloca el resto en una lista desplegable a la derecha del último
             * item. Se debe de garantizar que el item actualmente seleccionado
             * aparezca en un menú de primer nivel que esté entre los 7 primeros,
             * reordenando los items si es necesario. */
            $MAX_ITEMS_VISIBLES = 7;
            if (count($arrMainMenu) > $MAX_ITEMS_VISIBLES) {
                // Se transfiere a arreglo numérico para manipular orden de enumeración
                $tempMenulist = array();
                $idxMainMenu = NULL;
                foreach ($arrMainMenu as $key => $value) {
                    if ($key == $currMainMenu) $idxMainMenu = count($tempMenulist); 
                    $tempMenulist[] = array($key, $value);
                }
                if (!is_null($idxMainMenu) && $idxMainMenu >= $MAX_ITEMS_VISIBLES) {
                    $menuitem = array_splice($tempMenulist, $idxMainMenu, 1);
                    array_splice($tempMenulist, $MAX_ITEMS_VISIBLES - 1, 0, $menuitem);
                    $arrMainMenu = array();
                    foreach ($tempMenulist as $menuitem) $arrMainMenu[$menuitem[0]] = $menuitem[1];
                }
                unset($tempMenulist);
            }

			if(!isset($_SESSION['menu']) || $_SESSION['menu'] == "")
				$_SESSION['menu'] = $currMainMenu;

			$arrParentMenuId = $this->getIdParentMenu($_SESSION['menu']);
			$menuBookmark = $_SESSION['menu'];
			if($arrParentMenuId == ""){ // no tiene padre entonces es un menu de 1 nivel
				$menuBookmark = $this->getIdFirstSubMenu($_SESSION['menu']);
				$salRes = $this->getIdFirstSubMenu($menuBookmark);
				if($salRes !== FALSE)
					$menuBookmark = $salRes;
			}else{ // tiene padre entonces puede ser un menu de 2 o 3 nivel
				// se pregunta si tiene un primer hijo
				$salRes = $this->getIdFirstSubMenu($_SESSION['menu']);
				if($salRes !== FALSE){ // si no tiene un hijo entonces es de 2 nivel
					$menuBookmark = $salRes;
				}else{ // es de 3 nivel
					$menuBookmark = $_SESSION['menu'];
				}
			}
			putMenuAsHistory($menuBookmark);
			$this->smarty->assign("SHORTCUT", loadShortcut($this->smarty));///////////////////////////
		}
		/************* para elastixneo**********/

        $this->smarty->assign("arrMainMenu", $arrMainMenu);

        // Get the submenu
        $arrSubMenu = $this->getArrSubMenu($currMainMenu);
        $this->smarty->assign("arrSubMenu", $arrSubMenu);

        // Get the 3th level menu
        $arrSubMenu2 = $this->getArrSubMenu($currSubMenu);
        $this->smarty->assign("arrSubMenu2", $arrSubMenu2);
		if(is_array($arrSubMenu2) && !empty($arrSubMenu2))////////////////////////////////////////////
			$isThirdLevel = "on";//////////////////////////////////////////////

        $arrSubMenuByParents = $this->getArrSubMenuByParents($currMainMenu); // added by eduardo
        $this->smarty->assign("arrSubMenuByParents", $arrSubMenuByParents);   // added by eduardo

        $this->smarty->assign("idMainMenuSelected",   $currMainMenu);
        $this->smarty->assign("idSubMenuSelected",    $currSubMenu);
        $this->smarty->assign("idSubMenu2Selected",    $currSubMenu2);
        $this->smarty->assign("nameMainMenuSelected", $arrMainMenu[$currMainMenu][MENUTAG]);
        $this->smarty->assign("nameSubMenuSelected",  $arrSubMenu[$currSubMenu][MENUTAG]);
        $this->smarty->assign("nameSubMenu2Selected",  $arrSubMenu2[$currSubMenu2][MENUTAG]);
		$this->smarty->assign("isThirdLevel", $isThirdLevel);//////////////////////////////////////////////////////////

	if(isset($_GET) && count($_GET) == 1 && isset($_GET['menu'])){
	  $navigation  = $arrMainMenu[$currMainMenu][MENUTAG]." >> ".$arrSubMenu[$currSubMenu][MENUTAG];
	  $navigation .= isset($arrSubMenu2[$currSubMenu2])?" >> ".$arrSubMenu2[$currSubMenu2][MENUTAG]:"";

	  $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"unknown";
	  writeLOG("audit.log","NAVIGATION $user: User $user visited \"{$navigation}\" from $_SERVER[REMOTE_ADDR].");
	}

        /*************** Submenus para template elastix wine ********************/
        $arrMenuTotal = array();
        foreach($arrMainMenu as $key => $valor)
        {
            $idMenu = $valor['id'];
            $arrTmp = $this->getArrSubMenu($idMenu);

            $arrMenuTotal[$idMenu] = "";
            foreach($arrTmp as $keySub => $valorSub)
            {
                $idSub = $valorSub['id'];
                //ALL: with this function getArrSubMenu our can to add the third level.
                $arrTmp2 =$this->getArrSubMenu($idSub);
                if($arrTmp2)$valorSub[MENUTAG] = $valorSub[MENUTAG].'...';
                $arrMenuTotal[$idMenu] .= "<tr><td>";
                $arrMenuTotal[$idMenu] .= "<a href=\"index.php?menu={$valorSub['id']}\">{$valorSub[MENUTAG]}</a>";
                $arrMenuTotal[$idMenu] .= "</td></tr>";
            }
            $this->smarty->assign("arrMenuTotal", $arrMenuTotal);
        }
        /*************** Submenus para template elastix wine ********************/
    }


    private function getIdParentMenu($id)
    {
        // verificar que $this->arrMenu[$id] exista
		if(isset($this->arrMenu[$id]))
			return $this->arrMenu[$id]['IdParent'];
		else
			return NULL;
    }

    private function isValidMenu($id)
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

// added by eduardo
    private function getArrSubMenuByParents($idParent)
    {
        $arrSubMenu = array();
		global $arrConf;
		$themeName = $arrConf['mainTheme'];
        foreach($this->arrMenu as $id => $element) {
            if($element['IdParent']==$idParent) {
                if($this->getArrSubMenu($element['id'])!=false){
					if($themeName != "elastixneo")
						$img = "<img alt='' src='images/miniArrowDown.png' align='absmiddle' style='border:0;'/>";
					else
						$img = "";
                    $element[MENUTAG] = $element[MENUTAG]." ".$img;
                    $arrSubMenu[$id] = $element;
                }else{
                    $arrSubMenu[$id] = $element;
                }
            }
        }
        if(count($arrSubMenu)<=0) return false;
        return $arrSubMenu;
    }

    private function getIdFirstSubMenu($idParent)
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
        $this->putHEAD_JQUERY_HTML();
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
			$title = $bSubMenu2Framed?$this->arrMenu[$this->currSubMenu2][MENUTAG]:$this->arrMenu[$this->currSubMenu][MENUTAG];
			$this->smarty->assign("title",$title);
			$link=$bSubMenu2Framed?$this->arrMenu[$this->currSubMenu2]['Link']:$this->arrMenu[$this->currSubMenu]['Link'];
            $link = str_replace("{NAME_SERVER}", $_SERVER['SERVER_NAME'], $link);
            $link = str_replace("{IP_SERVER}", $_SERVER['SERVER_ADDR'], $link);
            $retVar  = "<iframe marginwidth=\"0\" marginheight=\"0\" class=\"frameModule\"";
            $retVar .= "\" src=\"" . $link . "\" name=\"myframe\" id=\"myframe\" frameborder=\"0\"";
            $retVar .= " width=\"100%\" onLoad=\"calcHeight();\"></iframe>";
        }
        return $retVar;
    }

    private function includeModule($module)
    {
        if(file_exists("modules/$module/index.php")) {
            include "modules/$module/index.php";
            if(function_exists("_moduleContent")) {
                $this->putHEAD_MODULE_HTML($module);
                return _moduleContent($this->smarty,$module);
            } else {
                return "Wrong module: modules/$module/index.php";
            }
        } else {
            return "Error: The module <b>modules/$module/index.php</b> could not be found<br>";
        }
    }

    /**
    *
    * Description:
    *   This function put the tags css and js per each module and the libs of the framework
    *
    * Example:
    *   $array = putHEAD_MODULE_HTML('calendar');
    *
    * Developer:
    *   Eduardo Cueva
    *
    * e-mail:
    *   ecueva@palosanto.com
    */
    private function putHEAD_MODULE_HTML($menuLibs)  // add by eduardo
    {
        // get the header with scripts and links(css)
        $documentRoot = $_SERVER["DOCUMENT_ROOT"];

        //STEP 1: include file of module
        $directory = "$documentRoot/modules/".$menuLibs;
        $HEADER_MODULES = array();
        if(is_dir($directory)){
            // FIXED: The theme default shouldn't be static.
            $directoryScrips = "$documentRoot/modules/$menuLibs/themes/default/js/";
            $directoryCss = "$documentRoot/modules/$menuLibs/themes/default/css/";
            if(is_dir($directoryScrips)){
                $arr_js = $this->obtainFiles($directoryScrips,"js");
                if($arr_js!=false && count($arr_js)>0){
                    for($i=0; $i<count($arr_js); $i++){
                        $dir_script = "modules/$menuLibs/themes/default/js/".$arr_js[$i];
                        $HEADER_MODULES[] = "<script type='text/javascript' src='$dir_script'></script>";
                    }
                }
            }
            if(is_dir($directoryCss)){
                $arr_css = $this->obtainFiles($directoryCss,"css");
                if($arr_css!=false && count($arr_css)>0){
                    for($i=0; $i<count($arr_css); $i++){
                        $dir_css = "modules/$menuLibs/themes/default/css/".$arr_css[$i];
                        $HEADER_MODULES[] = "<link rel='stylesheet' href='$dir_css' />";
                    }
                }
            }
            //$HEADER_MODULES
        }
        $this->smarty->assign("HEADER_MODULES", implode("\n", $HEADER_MODULES));
    }

    function putHEAD_JQUERY_HTML()
    {
        $documentRoot = $_SERVER["DOCUMENT_ROOT"];
        // include file of framework
        $HEADER_LIBS_JQUERY = array();
        $JQqueryDirectory = "$documentRoot/libs/js/jquery";
        // it to load libs JQuery
        if(is_dir($JQqueryDirectory)){
            $directoryScrips = "$documentRoot/libs/js/jquery/";
            if(is_dir($directoryScrips)){
                $arr_js = $this->obtainFiles($directoryScrips,"js");
                if($arr_js!=false && count($arr_js)>0){
                    for($i=0; $i<count($arr_js); $i++){
                        $dir_script = "libs/js/jquery/".$arr_js[$i];
                        $HEADER_LIBS_JQUERY[] = "<script type='text/javascript' src='$dir_script'></script>";
                    }
                }
            }

            // FIXED: The css ui-lightness shouldn't be static.
            $directoryCss = "$documentRoot/libs/js/jquery/css/ui-lightness/";
            if(is_dir($directoryCss)){
                $arr_css = $this->obtainFiles($directoryCss,"css");
                if($arr_css!=false && count($arr_css)>0){
                    for($i=0; $i<count($arr_css); $i++){
                        $dir_css = "libs/js/jquery/css/ui-lightness/".$arr_css[$i];
                        $HEADER_LIBS_JQUERY[] = "<link rel='stylesheet' href='$dir_css' />";
                    }
                }
            }
            //$HEADER_LIBS_JQUERY
        }
        $this->smarty->assign("HEADER_LIBS_JQUERY", implode("\n", $HEADER_LIBS_JQUERY));
    }

    /**
    *
    * Description:
    *   This function Obtain all name files into of a directory where $type is the extension of the file
    *
    * Example:
    *   $array = obtainFiles('/var/www/html/modules/calendar/themes/default/js/','js');
    *
    * Developer:
    *   Eduardo Cueva
    *
    * e-mail:
    *   ecueva@palosanto.com
    */
    private function obtainFiles($dir,$type){
		$files =  glob($dir."/{*.$type}",GLOB_BRACE);
		$names ="";
		foreach ($files as $ima)
			$names[]=array_pop(explode("/",$ima));
		if(!$names) return false;
		return $names;
    }

	function getFirstChildOfMainMenuByBookmark($menu_session)
	{
		$arrParentMenuId = $this->getIdParentMenu($menu_session);
		$menuBookmark = $menu_session;
		if($arrParentMenuId == "" || !isset($arrParentMenuId)){ // no tiene padre entonces es un menu de 1 nivel
			$menuBookmark = $this->getIdFirstSubMenu($menu_session);
			$salRes = $this->getIdFirstSubMenu($menuBookmark);
			if($salRes !== FALSE)
				$menuBookmark = $salRes;
		}else{ // tiene padre entonces puede ser un menu de 2 o 3 nivel
			// se pregunta si tiene un primer hijo
			$salRes = $this->getIdFirstSubMenu($menu_session);
			if($salRes !== FALSE){ // si no tiene un hijo entonces es de 2 nivel
				$menuBookmark = $salRes;
			}else{ // es de 3 nivel
				$menuBookmark = $menu_session;
			}
		}
		return $menuBookmark;
	}
}
?>