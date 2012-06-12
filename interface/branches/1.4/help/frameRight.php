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
  $Id: frameRight.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */
include_once("../libs/misc.lib.php");
include_once "../configs/default.conf.php";


session_name("elastixSession");
session_start();

require_once("../libs/smarty/libs/Smarty.class.php");
$smarty = new Smarty();
$smarty->template_dir = "../themes/" . $arrConf['mainTheme'];
$smarty->compile_dir =  "../var/templates_c/";
$smarty->config_dir =   "../configs/";
$smarty->cache_dir =    "../var/cache/";
$smarty->assign("THEMENAME", $arrConf['mainTheme']);

if(!empty($_GET['id_nodo'])){
    $idMenuMostrar = $_GET['id_nodo'];
    if(!empty($_GET['name_nodo'])){
	    $nameMenuMostrar = $_GET['name_nodo'];
        $smarty->assign("node_name", $nameMenuMostrar);
    }
                
    // Si no existe el archivo de ayuda y se trata de un menu "padre",
    // muestro el menu hijo que encuentre primero
    if(existeArchivoAyuda($idMenuMostrar)==3 || existeArchivoAyuda($idMenuMostrar)==4)
		$idMenuMostrar = menuHijoPorOmision($idMenuMostrar);
    		
    if(existeArchivoAyuda($idMenuMostrar)==1) {
        $smarty->assign("node_id", $idMenuMostrar);     
        $smarty->display($_SERVER["DOCUMENT_ROOT"]."/modules/$idMenuMostrar/help/$idMenuMostrar.hlp");
        }else if(existeArchivoAyuda($idMenuMostrar)==2) {
        $smarty->assign("node_id", $idMenuMostrar);    
        $smarty->display($_SERVER["DOCUMENT_ROOT"]."/help/content/$idMenuMostrar.hlp");
    } else    
       echo "The help file for the selected menu does not exists";
} else {
    echo "The selected menu is not valid.";
}

function menuHijoPorOmision($idMenu)
{
    $arrMenu = array();
    if(isset($_SESSION['elastix_user_permission']))
        $arrMenu = $_SESSION['elastix_user_permission'];
    if(is_array($arrMenu))
    {
        foreach($arrMenu as $k => $menu) {
            if($menu['IdParent']==$idMenu) {
				echo "<h1>".$menu['Name']."</h1>";
                return $k;
                break;
            }
        }
    }
    return false;
}

function obtenerMenuPadre($idMenu)
{
    $arrMenu = $_SESSION['elastix_user_permission'];
    return $arrMenu[$idMenu]['IdParent'];
}

function existeArchivoAyuda($idMenu)
{
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/modules/$idMenu/help/$idMenu.hlp")) {
        return 1;
    } else if(file_exists($_SERVER["DOCUMENT_ROOT"]."/help/content/$idMenu.hlp")) {
        return 2;
    } else if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/help/content/$idMenu.hlp")){
        return 3;
    }else
        return 4;
}
?>
