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

include_once("../configs/menu.php");

if(!empty($_GET['id_nodo']) and esMenuValido($_GET['id_nodo'])) {
    $idMenuMostrar = $_GET['id_nodo'];
    
    if(existeArchivoAyuda($idMenuMostrar)) {
        include_once("content/$idMenuMostrar.hlp");

    // Si no existe el archivo de ayuda y se trata de un menu "padre",
    // muestro el menu hijo que encuentre primero
    } else {
        $idParent = obtenerMenuPadre($_GET['id_nodo']);
        // Es menu de primer nivel, entonces busco el menu hijo por omision
        if($idParent=="") {
            $idMenuMostrar = menuHijoPorOmision($_GET['id_nodo']);
        }

        if(existeArchivoAyuda($idMenuMostrar)) {
            include_once("content/$idMenuMostrar.hlp");
        } else {    
            echo "The help file for the selected menu does not exists";
        }
    }

} else {
    echo "The selected menu is not valid.";
}

function menuHijoPorOmision($idMenu)
{
    global $arrMenu;

    foreach($arrMenu as $k => $menu) {
        if($menu['IdParent']==$idMenu) {
            return $k;
            break;
        }
    }
    return false;
}

function obtenerMenuPadre($idMenu)
{
    global $arrMenu;
    return $arrMenu[$idMenu]['IdParent'];
}

function esMenuValido($idMenu)
{
    global $arrMenu;
    if(array_key_exists($idMenu, $arrMenu)) {
        return true;
    } else {
        return false;
    }
}

function existeArchivoAyuda($idMenu)
{
    if(file_exists("content/$idMenu.hlp")) {
        return true;
    } else {
        return false;
    }
}
?>
