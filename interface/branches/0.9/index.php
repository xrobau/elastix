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
  $Id: index.php,v 1.3 2007/07/17 00:03:42 gcarrillo Exp $ */

dl('sqlite3.so');
include_once("libs/misc.lib.php");
include_once "configs/default.conf.php";
include_once "libs/paloSantoNavigation.class.php"; 
include_once "libs/paloSantoDB.class.php";
include_once("libs/paloSantoACL.class.php");// Don activate unless you know what you are doing. Too risky!
$developerMode=false;

session_name("elastixSession");
session_start();

if(isset($_GET['logout']) && $_GET['logout']=='yes') {
    session_destroy();
    session_name("elastixSession");
    session_start();
}
//cargar el archivo de idioma
//$lang=isset($arrConf['language'])?$arrConf['language']:"en";
//include_once("lang/".$lang.".lang");
load_language();

$pDB = new paloDB("sqlite3:////var/www/db/acl.db");

if(!empty($pDB->errMsg)) {
    echo "ERROR DE DB: $pDB->errMsg <br>";
}

$pACL = new paloACL($pDB);

if(!empty($pACL->errMsg)) {
    echo "ERROR DE DB: $pACL->errMsg <br>";
}

// Load smarty 
require_once("libs/smarty/libs/Smarty.class.php");
$smarty = new Smarty();

$smarty->template_dir = "themes/" . $arrConf['mainTheme'];
$smarty->compile_dir =  "var/templates_c/";
$smarty->config_dir =   "configs/";
$smarty->cache_dir =    "var/cache/";
//$smarty->debugging =    true;


//- 1) SUBMIT. Si se hizo submit en el formulario de ingreso
//-            autentico al usuario y lo ingreso a la sesion

if(isset($_POST['submit_login']) and !empty($_POST['input_user'])) {
    $pass_md5 = md5($_POST['input_pass']);
    if($pACL->authenticateUser($_POST['input_user'], $pass_md5)) {
        $_SESSION['elastix_user'] = $_POST['input_user'];
        $_SESSION['elastix_pass'] = $pass_md5;
    } else {
        // Debo hacer algo aquí?
    }
}

//include_once("configs/menu.php"); 
$pDBMenu = new paloDB("sqlite3:////var/www/db/menu.db");
$arrMenu = cargar_menu($pDBMenu) ;

// 2) Autentico usuario
if(isset($_SESSION['elastix_user']) && isset($_SESSION['elastix_pass']) && $pACL->authenticateUser($_SESSION['elastix_user'], $_SESSION['elastix_pass']) or $developerMode==true) {
    $idUser = $pACL->getIdUser($_SESSION['elastix_user']);

    if(!isset($_SESSION['elastix_user_permission'])){
        if($developerMode!=true) {
            $arrMenuFiltered=array();
            //- TODO: Mejorar el siguiente bloque. Seguro debe de haber una forma mas 
            //-       eficiente de hacerlo
            //- Primero me barro todos los submenus
            $arrSubmenu=array();
            foreach($arrMenu as $idMenu=>$arrMenuItem) {
                if(!empty($arrMenuItem['IdParent'])) {
                    if ($pACL->isUserAuthorizedById($idUser, "access", $idMenu)) {
                        $arrSubmenu[$idMenu] = $arrMenuItem;
                        $arrMenuFiltered[$idMenu] = $arrMenuItem;
                    }
                }
            }
            //- Ahora me barro el menu principal
            foreach($arrMenu as $idMenu=>$arrMenuItem) {
                if(empty($arrMenuItem['IdParent'])) {
                    foreach($arrSubmenu as $idSubMenu=>$arrSubMenuItem) {
                        if($arrSubMenuItem['IdParent']==$idMenu) {
                            $arrMenuFiltered[$idMenu] = $arrMenuItem;
                        }
                    }
                }
            }
        } else {    
            $arrMenuFiltered = $arrMenu;
        }
        //Guardo en la session los menus q tiene con permisos el usuario logoneado, esto se implementó para mejorar 
        //el proceso del httpd ya que consumia mucho recurso. Reportado por Ana Vivar <avivar@palosanto.com>
        //Una vez q exista en la session solo se lo sacara de ahi y no se vovera a consultar a la base.
        $_SESSION['elastix_user_permission']= $arrMenuFiltered;
    }

    $arrMenuFiltered = $_SESSION['elastix_user_permission'];

    //traducir el menu al idioma correspondiente
    foreach($arrMenuFiltered as $idMenu=>$arrMenuItem) {
        $arrMenuFiltered[$idMenu]['Name']=isset($arrLang[$arrMenuItem['Name']])?$arrLang[$arrMenuItem['Name']]:$arrMenuItem['Name'];
    }
    $oPn = new paloSantoNavigation($arrConf, $arrMenuFiltered, $smarty);

    $smarty->assign("THEMENAME", $arrConf['mainTheme']);
    $smarty->assign("ABOUT_ELASTIX",$arrLang['About Elastix']." ".$arrConf['elastix_version']);
    $smarty->assign("ABOUT_ELASTIX_CONTENT",$arrLang['About Elastix Content']);
    $smarty->assign("ABOUT_CLOSED",$arrLang['About Elastix Closed']);
    $smarty->assign("LOGOUT",    $arrLang['Logout']);

    //$menu= (isset($_GET['menu']))?$_GET['menu']:'';
    if(isset($_GET['menu'])) $menu=$_GET['menu'];
    elseif(empty($menu) and !empty($_SESSION['menu'])) $menu=$_SESSION['menu'];
    else $menu='';

    $_SESSION['menu']=$menu; 
 
    if (count($arrMenuFiltered)>0)
        $smarty->assign("MENU", $oPn->showMenu($menu));
    else
        $smarty->assign("MENU", "No modules");


    // rawmode es un modo de operacion que pasa directamente a la pantalla la salida
    // del modulo. Esto es util en ciertos casos.
    if(isset($_GET['rawmode']) && $_GET['rawmode']=='yes') {
    
         // Autorizacion
        if($pACL->isUserAuthorizedById($idUser, "access", $oPn->currSubMenu) or $developerMode==true) {
            echo $oPn->showContent();
        }

    } else {
       // Autorizacion
        if($pACL->isUserAuthorizedById($idUser, "access", $oPn->currSubMenu) or $developerMode==true) {
            $smarty->assign("CONTENT",   $oPn->showContent());
        }
                
        $smarty->display("_common/index.tpl");

    }

} else {
    $smarty->assign("PAGE_NAME",$arrLang['Login page']);
    $smarty->assign("WELCOME",$arrLang['Welcome to Elastix']);
    $smarty->assign("ENTER_USER_PASSWORD",$arrLang['Please enter your username and password']);
    $smarty->assign("USERNAME",$arrLang['Username']);
    $smarty->assign("PASSWORD",$arrLang['Password']);
    $smarty->assign("SUBMIT",$arrLang['Submit']);

    $smarty->display("_common/login.tpl");

}
?>
