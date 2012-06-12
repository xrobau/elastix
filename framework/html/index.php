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

include_once("libs/misc.lib.php");
include_once "configs/default.conf.php";
include_once "libs/paloSantoNavigation.class.php"; 
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoMenu.class.php";
include_once("libs/paloSantoACL.class.php");// Don activate unless you know what you are doing. Too risky!

$developerMode=false;

session_name("elastixSession");
session_start();

if(isset($_GET['logout']) && $_GET['logout']=='yes') {
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"unknown";
    writeLOG("audit.log", "LOGOUT $user: Web Interface logout successful. Accepted logout for $user from $_SERVER[REMOTE_ADDR].");
    session_destroy();
    session_name("elastixSession");
    session_start();
    header("Location: index.php");
    exit;
}
//cargar el archivo de idioma
//$lang=isset($arrConf['language'])?$arrConf['language']:"en";
//include_once("lang/".$lang.".lang");
load_language();

$pDB = new paloDB($arrConf['elastix_dsn']['acl']);

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
         header("Location: index.php");
        writeLOG("audit.log", "LOGIN $_POST[input_user]: Web Interface login successful. Accepted password for $_POST[input_user] from $_SERVER[REMOTE_ADDR].");
        exit;
    } else {
        $user = urlencode(substr($_POST['input_user'],0,20));
        if(!$pACL->getIdUser($_POST['input_user'])) // not exists user?
            writeLOG("audit.log", "LOGIN $user: Authentication Failure to Web Interface login. Invalid user $user from $_SERVER[REMOTE_ADDR].");
        else
            writeLOG("audit.log", "LOGIN $user: Authentication Failure to Web Interface login. Failed password for $user from $_SERVER[REMOTE_ADDR].");
        // Debo hacer algo aquí?
    }
}

$pDBMenu = new paloDB($arrConf['elastix_dsn']['menu']);
$arrMenu = cargar_menu($pDBMenu);
$pMenu = new paloMenu($pDBMenu);

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
                    if($pACL->isUserAuthorizedById($idUser, "access", $arrMenuItem['IdParent']) || empty($arrMenu[$arrMenuItem['IdParent']]['IdParent'])){
			if ($pACL->isUserAuthorizedById($idUser, "access", $idMenu)) {
			    $arrSubmenu[$idMenu] = $arrMenuItem;
			    $arrMenuFiltered[$idMenu] = $arrMenuItem;
			}
		    }
		    else{ // En caso de que no se tenga acceso al padre, entonces no se tendrá acceso a este menú ni a sus hijos
			$childs = $pMenu->getChilds($idMenu);
			if(is_array($childs) && count($childs)>0){
			    foreach($childs as $child)
				unset($arrMenuFiltered[$child['id']]);
			}
		    }
                }
            }

	    // Ahora pregunto por los menus que tienen hijos, en caso de que no se tenga acceso a ningún hijo, entonces es innecesario mostrar la pestaña del padre
	    foreach($arrMenuFiltered as $idMenu => $menuFiltered){
		$childs = $pMenu->getChilds($idMenu);
		if(is_array($childs) && count($childs)>0){
		    $noActiveChilds = true;
		    foreach($childs as $child){
			if(array_key_exists($child['id'],$arrMenuFiltered)){
			    $noActiveChilds = false;
			    break;
			}
		    }
		    if($noActiveChilds)
			unset($arrMenuFiltered[$idMenu]);
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
    verifyTemplate_vm_email(); // para cambiar el template del email ue se envia al recibir un voicemail
    $arrMenuFiltered = $_SESSION['elastix_user_permission'];

    //traducir el menu al idioma correspondiente
    foreach($arrMenuFiltered as $idMenu=>$arrMenuItem) {
        $arrMenuFiltered[$idMenu]['Name']=isset($arrLang[$arrMenuItem['Name']])?$arrLang[$arrMenuItem['Name']]:$arrMenuItem['Name'];
    }
    $oPn = new paloSantoNavigation($arrConf, $arrMenuFiltered, $smarty);

    $smarty->assign("THEMENAME", $arrConf['mainTheme']);

    /*agregado para register*/
    
    $smarty->assign("Register", _tr("Register"));
    $smarty->assign("lblRegisterCm", _tr("Register"));
    $smarty->assign("lblRegisteredCm", _tr("Registered"));
    if(!is_file("/etc/elastix.key")){
	$smarty->assign("Registered", _tr("Register"));
    	$smarty->assign("ColorRegister", "#FF0000"); 
    }else{
	$smarty->assign("Registered", _tr("Registered"));
    	$smarty->assign("ColorRegister", "#008800");
    }

    /*agregado para register*/
	$menuColor = getMenuColorByMenu();

    $smarty->assign("md_message_title",$arrLang['md_message_title']);
    $smarty->assign("currentyear",date("Y"));
	if($arrConf['mainTheme']=="elastixwave" || $arrConf['mainTheme']=="elastixneo"){
		$smarty->assign("ABOUT_ELASTIX2",$arrLang['About Elastix2']);
    	$smarty->assign("HELP",$arrLang['HELP']);
        $smarty->assign("USER_LOGIN",$_SESSION['elastix_user']);
		$smarty->assign("CHANGE_PASSWORD", _tr("Change Password"));
		$smarty->assign("CURRENT_PASSWORD_ALERT", _tr("Please write your current password."));
		$smarty->assign("NEW_RETYPE_PASSWORD_ALERT", _tr("Please write the new password and confirm the new password."));
		$smarty->assign("PASSWORDS_NOT_MATCH", _tr("The new password doesn't match with retype password."));
		$smarty->assign("CHANGE_PASSWORD", _tr("Change Elastix Password"));
		$smarty->assign("CURRENT_PASSWORD", _tr("Current Password"));
		$smarty->assign("NEW_PASSWORD", _tr("New Password"));
		$smarty->assign("RETYPE_PASSWORD", _tr("Retype New Password"));
		$smarty->assign("CHANGE_PASSWORD_BTN", _tr("Change"));
		$smarty->assign("MENU_COLOR", $menuColor);
		$smarty->assign("MODULES_SEARCH", _tr("Search modules"));
	}
	else{
		$smarty->assign("ABOUT_ELASTIX",$arrLang['About Elastix']." ".$arrConf['elastix_version']);
	}
    $smarty->assign("ABOUT_ELASTIX_CONTENT",$arrLang['About Elastix Content']);
    $smarty->assign("ABOUT_CLOSED",$arrLang['About Elastix Closed']);
    $smarty->assign("LOGOUT",$arrLang['Logout']);
    $smarty->assign("VersionDetails",$arrLang['VersionDetails']);
    $smarty->assign("VersionPackage",$arrLang['VersionPackage']);
	$smarty->assign("textMode",$arrLang['textMode']);
    $smarty->assign("htmlMode",$arrLang['htmlMode']);
    //$menu= (isset($_GET['menu']))?$_GET['menu']:'';
    if (isset($_POST['menu'])) $menu = $_POST['menu'];
    elseif (isset($_GET['menu'])) $menu=$_GET['menu'];
    elseif(empty($menu) and !empty($_SESSION['menu'])) $menu=$_SESSION['menu'];
    else $menu='';

    $_SESSION['menu']=$menu;

	if(getParameter("action")=="versionRPM"){
        $arrDetails = obtenerDetallesRPMS(); // obtain RPMs Details
        require_once("libs/JSON.php");
        $json = new Services_JSON(); 
        echo $json->encode($arrDetails);
        return;
    }
	if(getParameter("action")=="changePasswordElastix"){
		include_once "libs/paloSantoJSON.class.php";
		$jsonObject = new PaloSantoJSON();
		$output = setUserPassword();
		if($output['status'] === TRUE){
			$jsonObject->set_status("true");
		}else
		  $jsonObject->set_status("false");
		$jsonObject->set_error($output['msg']);
		echo $jsonObject->createJSON();
		return;
	}

	if(getParameter("action")=="search_module"){
		echo searchModulesByName();
		return;
	}

	if(getParameter("action")=="changeColorMenu"){
		include_once "libs/paloSantoJSON.class.php";
		$jsonObject = new PaloSantoJSON();
		$output = changeMenuColorByUser();
		if($output['status'] === TRUE){
			$jsonObject->set_status("true");
		}else
		  $jsonObject->set_status("false");
		$jsonObject->set_error($output['msg']);
		echo $jsonObject->createJSON();
		return;
	}


    // Inicializa el objeto palosanto navigation
    if (count($arrMenuFiltered)>0)
        $oPn->showMenu($menu);

    // rawmode es un modo de operacion que pasa directamente a la pantalla la salida
    // del modulo. Esto es util en ciertos casos.
    $rawmode = getParameter("rawmode");
    if(isset($rawmode) && $rawmode=='yes') {
         // Autorizacion
        if($pACL->isUserAuthorizedById($idUser, "access", $oPn->currSubMenu) or $developerMode==true) {
            echo $oPn->showContent();
        }
    } else {
       // Autorizacion
        if($pACL->isUserAuthorizedById($idUser, "access", $oPn->currSubMenu) or $developerMode==true) {
            $smarty->assign("CONTENT",   $oPn->showContent());

            if (count($arrMenuFiltered)>0){
                $menu_html = $smarty->fetch("_common/_menu.tpl");
                $smarty->assign("MENU",$menu_html);
            }
            else{
                $smarty->assign("MENU","No modules");
            }
        }

        $smarty->display("_common/index.tpl");
    }

} else {
    $smarty->assign("THEMENAME", $arrConf['mainTheme']);
    $smarty->assign("currentyear",date("Y"));
    $smarty->assign("PAGE_NAME",$arrLang['Login page']);
    $smarty->assign("WELCOME",$arrLang['Welcome to Elastix']);
    $smarty->assign("ENTER_USER_PASSWORD",$arrLang['Please enter your username and password']);
    $smarty->assign("USERNAME",$arrLang['Username']);
    $smarty->assign("PASSWORD",$arrLang['Password']);
    $smarty->assign("SUBMIT",$arrLang['Submit']);

    $smarty->display("_common/login.tpl");

}
?>
