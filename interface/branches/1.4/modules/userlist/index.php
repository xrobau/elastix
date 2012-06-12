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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoConfig.class.php");
    include_once("libs/paloSantoGrid.class.php");
    include_once("libs/paloSantoACL.class.php");
    global $arrLang;
    $pDB = new paloDB("sqlite3:////var/www/db/acl.db");

/////conexion a php
//include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" . $arrConfig['AMPDBHOST']['valor'] . "/asterisk";
    $pDBa     = new paloDB($dsn);

////////////////////

    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $arrData = array();
    $pACL = new paloACL($pDB);
    if(!empty($pACL->errMsg)) {
        echo "ERROR DE ACL: $pACL->errMsg <br>";
    }



    $sQuery="select extension from users order by extension";
    if (!$arrayResult = $pDBa->fetchTable($sQuery,true)){
        $error = $pDBa->errMsg;
    }else{  
    if (is_array($arrayResult) && count($arrayResult)>0) {
        //$arrData[$item["null"]] = "No extension";
        foreach($arrayResult as $item) {
                $arrData[$item["extension"]] = $item["extension"];  
            }
    }
    }

    $arrGruposACL=$pACL->getGroups();
    for($i=0; $i<count($arrGruposACL); $i++)
    {
        if($arrGruposACL[$i][1]=='administrator')
            $arrGruposACL[$i][1] = $arrLang['administrator'];
        else if($arrGruposACL[$i][1]=='operator')
            $arrGruposACL[$i][1] = $arrLang['operator'];
        else if($arrGruposACL[$i][1]=='extension')
            $arrGruposACL[$i][1] = $arrLang['extension'];

        $arrGrupos[$arrGruposACL[$i][0]] = $arrGruposACL[$i][1];
    }

    $arrFormElements = array("description" => array("LABEL"                  => "{$arrLang['Name']} (Ex. John Doe)",
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "name"       => array("LABEL"                   => $arrLang["Login"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "EDITABLE"               => "no"),
                             "password1"   => array("LABEL"                  => $arrLang["Password"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "password2"   => array("LABEL"                  => $arrLang["Retype password"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "group"       => array("LABEL"                  => $arrLang["Group"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrGrupos,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "extension"   => array("LABEL"                  => "Extension",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrData,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "webmailpassword1"   => array("LABEL"                  => $arrLang["Webmail Password"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "webmailpassword2"   => array("LABEL"                  => $arrLang["Retype Webmail password"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "webmailuser"       => array("LABEL"                  => $arrLang["Webmail User"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "webmaildomain"       => array("LABEL"                  => $arrLang["Webmail Domain"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
    );


    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("CONFIRM_CONTINUE", $arrLang["Are you sure you wish to continue?"]);
    $smarty->assign("title_webmail", $arrLang["Mail Profile"]);
    if(isset($_POST['submit_create_user'])) {
        // Implementar
        include_once("libs/paloSantoForm.class.php");
        $arrFillUser['description'] = '';
        $arrFillUser['name']        = '';
        $arrFillUser['group']       = '';
        $arrFillUser['extension']   = '';
        $arrFillUser['password1']   = '';
        $arrFillUser['password2']   = '';
        $oForm = new paloForm($smarty, $arrFormElements);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["New User"],$arrFillUser);

    } else if(isset($_POST['edit'])) {

        // Tengo que recuperar la data del usuario
        $pACL = new paloACL($pDB);

        $arrUser = $pACL->getUsers($_POST['id_user']);

        $arrFillUser['name'] = $arrUser[0][1];
        $arrFillUser['description'] = $arrUser[0][2];

        // Lleno el grupo
        $arrMembership  = $pACL->getMembership($_POST['id_user']);
        $id_group="";
        if(is_array($arrMembership)) {
            foreach($arrMembership as $groupName=>$groupId) {
                $id_group =  $groupId;
                // Asumo que cada usuario solo puede pertenecer a un grupo
                break;
            }
        }
        $arrFillUser['group'] = $id_group;
        $arrFillUser['extension'] = $arrUser[0][3];

        // Implementar
        include_once("libs/paloSantoForm.class.php");
        $arrFormElements['password1']['REQUIRED']='no';
        $arrFormElements['password2']['REQUIRED']='no';
        $oForm = new paloForm($smarty, $arrFormElements);

        $listaPropiedades = leerPropiedadesWebmail($pDB, $smarty, $_POST['id_user']);
        if (isset($listaPropiedades['login'])) $arrFillUser['webmailuser'] = $listaPropiedades['login'];
        if (isset($listaPropiedades['domain'])) $arrFillUser['webmaildomain'] = $listaPropiedades['domain'];
        if (isset($listaPropiedades['password'])) $arrFillUser['webmailpassword1'] = $arrFillUser['webmailpassword2'] = $listaPropiedades['password'];
        //if (isset($listaPropiedades['imapsvr'])) $arrFillUser['webmailimapsvr'] = $listaPropiedades['imapsvr'];

        $oForm->setEditMode();
        $smarty->assign("id_user", $_POST['id_user']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", "{$arrLang['Edit User']} \"" . $arrFillUser['name'] . "\"", $arrFillUser);

    } else if(isset($_POST['submit_save_user'])) {

        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $pACL = new paloACL($pDB);

            if((empty($_POST['password1']) or ($_POST['password1']!=$_POST['password2'])) 
                ||
                (!empty($_POST['webmailpassword1']) && ($_POST['webmailpassword1']!=$_POST['webmailpassword2']))) {
                // Error claves
                $smarty->assign("mb_message", $arrLang["The passwords are empty or don't match"]);
                $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["New User"], $_POST);
            } else {

                // Creo al usuario
                $md5_password = md5($_POST['password1']);
                $pACL->createUser($_POST['name'], $_POST['description'], $md5_password,$_POST['extension']);
                // Creo la membresia
                $idUser = $pACL->getIdUser($_POST['name']);
                $pACL->addToGroup($idUser, $_POST['group']);

                $bExito = TRUE;
                if (empty($pACL->errMsg)) {
                    $nuevasPropiedades = array();
                    if (!empty($_POST['webmailuser'])) $nuevasPropiedades['login'] = $_POST['webmailuser'];
                    if (!empty($_POST['webmailpassword1'])) $nuevasPropiedades['password'] = $_POST['webmailpassword1'];
                    if (!empty($_POST['webmaildomain'])) $nuevasPropiedades['domain'] = $_POST['webmaildomain'];
                    $bExito = actualizarPropiedades($pDB, $smarty, $idUser, 'webmail', 'default', $nuevasPropiedades);
                }

                if(!empty($pACL->errMsg)) {
                    // Ocurrio algun error aqui
                    $smarty->assign("mb_message", "ERROR: $pACL->errMsg");
                    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["New User"], $_POST);
                } else if ($bExito) {
                    header("Location: ?menu=userlist");
                }
            }
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["New User"], $_POST);
        }

    } else if(isset($_POST['submit_apply_changes'])) {

        $arrUser = $pACL->getUsers($_POST['id_user']);
        $username = $arrUser[0][1];
        $description = $arrUser[0][2]; 
        $arrFormElements['password1']['REQUIRED']='no';
        $arrFormElements['password2']['REQUIRED']='no';
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        // Leer valores originales de propiedades
        $listaPropiedades = leerPropiedadesWebmail($pDB, $smarty, $_POST['id_user']);

        $oForm->setEditMode();
        if($oForm->validateForm($_POST)) {

            if((!empty($_POST['password1']) && ($_POST['password1']!=$_POST['password2'])) 
                ||
                (!empty($_POST['webmailpassword1']) && ($_POST['webmailpassword1']!=$_POST['webmailpassword2']))) {
                // Error claves
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $smarty->assign("mb_message", $arrLang["The passwords are empty or don't match"]);
                $smarty->assign("id_user", $_POST['id_user']);
                $arrFillUser['description'] = $_POST['description'];
                $arrFillUser['name']        = $username;
                $arrFillUser['group']       = $_POST['group'];
                $arrFillUser['extension']   = $_POST['extension'];

                if (isset($listaPropiedades['login'])) $arrFillUser['webmailuser'] = $listaPropiedades['login'];
                if (isset($listaPropiedades['domain'])) $arrFillUser['webmaildomain'] = $listaPropiedades['domain'];
                if (isset($listaPropiedades['password'])) $arrFillUser['webmailpassword1'] = $arrFillUser['webmailpassword2'] = $listaPropiedades['password'];
        
                $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["Edit User"], $arrFillUser);
            } else {

                // Exito, puedo procesar los datos ahora.
                $pACL = new paloACL($pDB);

                // Lleno el grupo
                $arrMembership  = $pACL->getMembership($_POST['id_user']);
                $id_group="";
                if(is_array($arrMembership)) {
                    foreach($arrMembership as $groupName=>$groupId) {
                        $id_group =  $groupId;
                        // Asumo que cada usuario solo puede pertenecer a un grupo
                        break;
                    }
                }

                // El usuario trato de cambiar de grupo
                if($id_group!=$_POST['group']) {
                    $pACL->delFromGroup($_POST['id_user'], $id_group);
                    $pACL->addToGroup($_POST['id_user'], $_POST['group']);
                }

                //- La updateUser no es la adecuada porque pide el username. Deberia
                //- hacer una que no pida username en la proxima version
                $pACL->updateUser($_POST['id_user'], $username, $_POST['description'],$_POST['extension']);
                //si se ha puesto algo en passwor se actualiza el password
                if (!empty($_POST['password1']))
                    $pACL->changePassword($_POST['id_user'], md5($_POST['password1']));
                    
                $nuevasPropiedades = array(
                    'login'     =>  $_POST['webmailuser'],
                    'domain'    =>  $_POST['webmaildomain'],
                    'password'  =>  $_POST['webmailpassword1'],
                    //'imapsvr'   =>  $_POST['']
                );
                if (empty($_POST['webmailpassword1'])) unset($nuevasPropiedades['password']);
                $bExito = actualizarPropiedades($pDB, $smarty, $_POST['id_user'], 'webmail', 'default', $nuevasPropiedades);
    
                if ($bExito) header("Location: ?menu=userlist");
            }

        } else {
            // Manejo de Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);

            $arrFillUser['description'] = $_POST['description'];
            $arrFillUser['name']        = $username;
            $arrFillUser['group']       = $_POST['group'];
            $arrFillUser['extension']   = $_POST['extension'];
            foreach (array('webmailuser', 'webmaildomain', 'webmailpassword1', 'webmailpassword2') as $key) 
                $arrFillUser[$key] = $_POST[$key];
            $smarty->assign("id_user", $_POST['id_user']);
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["Edit User"], $arrFillUser);
            /////////////////////////////////
        }

    } else if(isset($_GET['action']) && $_GET['action']=="view") {

        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error

        $oForm->setViewMode(); // Esto es para activar el modo "preview"
        $arrUser = $pACL->getUsers($_GET['id']);

        // Conversion de formato
        $arrTmp['name']        = $arrUser[0][1];
        $arrTmp['description'] = $arrUser[0][2];
        $arrTmp['password1'] = "****";
        $arrTmp['password2'] = "****";
        $arrTmp['extension'] = $arrUser[0][3];
        //- TODO: Falta llenar el grupo
        $arrMembership  = $pACL->getMembership($_GET['id']);
        $id_group="";
        if(is_array($arrMembership)) {
            foreach($arrMembership as $groupName=>$groupId) {
                $id_group =  $groupId;
                // Asumo que cada usuario solo puede pertenecer a un grupo
                break;
            }
        }
        $arrTmp['group'] = $id_group;

        $listaPropiedades = leerPropiedadesWebmail($pDB, $smarty, $_GET['id']);
        if (isset($listaPropiedades['login'])) $arrTmp['webmailuser'] = $listaPropiedades['login'];
        if (isset($listaPropiedades['domain'])) $arrTmp['webmaildomain'] = $listaPropiedades['domain'];
        if (isset($listaPropiedades['password'])) $arrTmp['webmailpassword1'] = $arrTmp['webmailpassword2'] = '****';
        //if (isset($listaPropiedades['imapsvr'])) $arrTmp['webmailimapsvr'] = $listaPropiedades['imapsvr'];

        $smarty->assign("id_user", $_GET['id']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["View User"], $arrTmp); // hay que pasar el arreglo

    } else {

        if (isset($_POST['delete'])) {
           //- TODO: Validar el id de user
            if(isset($_POST['id_user']) && $_POST['id_user']=='1') {
                // No se puede elimiar al usuario admin
                $smarty->assign("mb_message", $arrLang["The admin user cannot be deleted because is the default Elastix administrator. You can delete any other user."]);
            } else {
                $pACL->deleteUser($_POST['id_user']);
            }
        }

        $arrUsers = $pACL->getUsers();

        $end = count($arrUsers);
        $arrData = array();
        foreach($arrUsers as $user) {
            $arrMembership  = $pACL->getMembership($user[0]);

            $group="";
            if(is_array($arrMembership)) {
                foreach($arrMembership as $groupName=>$groupId) {
                    if($groupName == 'administrator')
                        $groupName = $arrLang['administrator'];
                    else if($groupName == 'operator')
                        $groupName = $arrLang['operator'];
                    else if($groupName == 'extension')
                        $groupName = $arrLang['extension'];

                    $group .= ucfirst($groupName) . " ";
                }
            }

            $arrTmp    = array();
            //$arrTmp[0] = "&nbsp;<a href='?menu=usernew&action=view&id=" . $user['id'] . "'>" . $user['name'] . "</a>";
            //$arrTmp[1] = $user['description'];
            $arrTmp[0] = "&nbsp;<a href='?menu=userlist&action=view&id=" . $user[0] . "'>" . $user[1] . "</a>";
            $arrTmp[1] = $user[2];
            $arrTmp[2] = $group;
            $arrTmp[3] = is_null($user[3])?"No Extension":$user[3];
            $arrData[] = $arrTmp;

        }

        $arrGrid = array("title"    => $arrLang["User List"],
                         "icon"     => "images/user.png",
                         "width"    => "99%",
                         "start"    => ($end==0) ? 0 : 1,
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => $arrLang["Login"],
                                                        "property1" => ""),
                                             1 => array("name"      => $arrLang["Real Name"], 
                                                        "property1" => ""),
                                             2 => array("name"      => $arrLang["Group"], 
                                                        "property1" => ""),
                                             3 => array("name"      => $arrLang["Extension"], 
                                                        "property1" => "")
                                            )
                        );

        $oGrid = new paloSantoGrid($smarty);
        $oGrid->showFilter("<form style='margin-bottom:0;' method='POST' action='?menu=userlist'>" .
                           "<input type='submit' name='submit_create_user' value='{$arrLang['Create New User']}' class='button'></form>");
        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }

    return $contenidoModulo;
}

function leerPropiedadesWebmail(&$pDB, &$smarty, $idUser)
{
    // Obtener la información del usuario con respecto al perfil "default" del módulo "webmail"
    $sPeticionPropiedades = 
        'SELECT pp.property, pp.value '.
        'FROM acl_profile_properties pp, acl_user_profile up, acl_resource r '.
    	'WHERE up.id_user = ? '.
            'AND up.profile = "default" '.
            'AND up.id_profile = pp.id_profile '.
            'AND up.id_resource = r.id '.
            'AND r.name = "webmail"';
    $listaPropiedades = array();
    $tabla = $pDB->fetchTable($sPeticionPropiedades, FALSE, array($idUser));
    if ($tabla === FALSE) {
      print "ERROR DE DB: ".$pDB->errMsg;
    } else {
      foreach ($tabla as $tupla) {
        $listaPropiedades[$tupla[0]] = $tupla[1];
      }
    }

    return $listaPropiedades;
}

function actualizarPropiedades(&$pDB, &$smarty, $idUser, $sModulo, $sPerfil, $propiedades)
{
//    $oDBConn =& $pDB->conn;
    // Verificar que existe realmente un perfil $sPerfil para el usuario $idUser y el módulo $sModulo,
    // y crearlo si es necesario
    
    $sPeticionID = 
        'SELECT up.id_profile '.
        'FROM acl_user_profile up, acl_resource r '.
        'WHERE up.id_user = ? AND up.id_resource = r.id AND r.name = ? AND up.profile = ?';
    $tupla = $pDB->getFirstRowQuery($sPeticionID, FALSE, array($idUser, $sModulo, $sPerfil));
    if ($tupla === FALSE) {
        $smarty->assign("mb_message", "ERROR DE DB: ".$pDB->errMsg);
        return FALSE;
    } elseif (count($tupla) == 0) {
        $idPerfil = NULL;
    } else {
        $idPerfil = $tupla[0];
    }
    if (is_null($idPerfil)) {
        // La combinación de usuario/módulo/perfil no existe y hay que crearla
        $pACL = new paloACL($pDB);
        
        // TODO: agregar función a paloACL para obtener ID de recurso, dado el nombre
        $listaRecursos = $pACL->getResources();
        $idRecurso = NULL;
        foreach ($listaRecursos as $tuplaRecurso)
        {
            if ($tuplaRecurso[1] == $sModulo) {
                $idRecurso = $tuplaRecurso[0];
                break;
            }
        }
        if (is_null($idRecurso)) {
            $smarty->assign("mb_message", '(internal) No resource found for: '.$sModulo);
            return FALSE;
        }
        
        // Crear el nuevo perfil para el usuario indicado...
        $sPeticionNuevoPerfil = 'INSERT INTO acl_user_profile (id_user, id_resource, profile) VALUES (?, ?, ?)';
        $r = $pDB->genQuery($sPeticionNuevoPerfil, array($idUser, $idRecurso, $sPerfil));
        if (!$r) {
            $smarty->assign("mb_message", "ERROR DE DB: ".$pDB->errMsg);
            return FALSE;
        }
        
        // Una vez creado el perfil, el query de ID de perfil debe de funcionar
        $tupla = $pDB->getFirstRowQuery($sPeticionID, FALSE, array($idUser, $sModulo, $sPerfil));
        if ($tupla === FALSE) {
            $smarty->assign("mb_message", "ERROR DE DB: ".$pDB->errMsg);
            return FALSE;
        } elseif (count($tupla) == 0) {
            $smarty->assign("mb_message", '(internal) Unable to find just-inserted profile ID');
            return FALSE;
        } else {
            $idPerfil = $tupla[0];
        }
    }
    
    // Aquí ya se tiene el ID del perfil a actualizar. Las propiedades deben de reemplazarse, o 
    // crearse si no existen. Por ahora no deben borrarse en ausencia de la lista
    $sPeticionPropiedades = 
        'SELECT property, value '.
        'FROM acl_profile_properties '.
    	'WHERE id_profile = ?';
    $listaPropiedades = array();
    $tabla = $pDB->fetchTable($sPeticionPropiedades, FALSE, array($idPerfil));
    if ($tabla === FALSE) {
      $smarty->assign("mb_message", "ERROR DE DB (1): ".$pDB->errMsg);
    } else {
      foreach ($tabla as $tupla) {
        $listaPropiedades[$tupla[0]] = $tupla[1];
      }
    }

    foreach ($propiedades as $k => $v) {
        $sPeticionSQL = NULL;
        $params = NULL;
        if (isset($listaPropiedades[$k])) {
            $sPeticionSQL = 'UPDATE acl_profile_properties SET value = ? WHERE id_profile = ? AND property = ?';
            $params = array($v, $idPerfil, $k);
        } else {
            $sPeticionSQL = 'INSERT INTO acl_profile_properties (id_profile, property, value) VALUES (?, ?, ?)';
            $params = array($idPerfil, $k, $v);
        }
        $r = $pDB->genQuery($sPeticionSQL, $params);
        if (!$r) {
            $smarty->assign("mb_message", "ERROR DE DB (2): ".$pDB->errMsg);
            return FALSE;
        }
    }
    return TRUE;
}

?>
