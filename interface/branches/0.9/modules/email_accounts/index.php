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
  $Id: index.php,v 1.2 2007/08/10 01:32:53 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoEmail.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/cyradm.php";
    include_once "configs/email.conf.php";
 //   require_once("libs/sieve-php.lib.php");
    //require_once('libs/sieve_strs.php');

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    $pDB = new paloDB("sqlite3:////var/www/db/email.db");
    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }
    $error="";
    $arrData = array();
    $pEmail = new paloEmail($pDB);
    if(!empty($pEmail->errMsg)) {
        echo "{$arrLang["ERROR"]}: {$arrLang[$pEmail->errMsg]} <br>";
    }

    $bMostrarListado=TRUE;

    $arrFormElements = array(
                             "address"       => array("LABEL"                   => $arrLang["Email Address"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^([a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*)$"),
                             "quota"   => array("LABEL"                  => $arrLang["Quota (Kb)"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
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
                         );

    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("CONFIRM_CONTINUE", $arrLang["Are you sure you wish to continue?"]);
    $verListado=TRUE;
    if(isset($_POST['submit_create_account'])) { 
         //AGREGAR NUEVA CUENTA
        //ASEGURARSE QUE HAY SELECCIONADO UN DOMINIO
        if ($_POST['domain']>0){
            $verListado=FALSE;
            $oForm = new paloForm($smarty, $arrFormElements);
            $smarty->assign("id_domain", $_POST['domain']);
            //obtener el nombre del dominio
            $arrDomain= $pEmail->getDomains($_POST['domain']);
            $domain_name="@".$arrDomain[0][1];
            $smarty->assign("domain_name", $domain_name);
            $arrTmp['address']   = "";
        	$arrTmp['password1'] = "";
        	$arrTmp['password2'] = "";
        	$arrTmp['quota']     = "";
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_account.tpl", $arrLang["New Email Account"],$arrTmp);
        }else{
               $smarty->assign("mb_message", $arrLang["You must select a domain to create an account"]);
        }

    } else if(isset($_POST['edit'])) {
        $verListado=FALSE;
        //EDITAR TARIFA
        // Tengo que recuperar los datos del ACCOUNT
        $arrAccount= $pEmail->getAccount($_POST['username']);

        $arrTmp['username']        = $arrAccount[0][0];
        $arrTmp['password1']        = "";
        $arrTmp['password2']        = "";
        $arrTmp['quota']        = $arrAccount[0][3];
        $id_domain        = $arrAccount[0][2];
        $oForm = new paloForm($smarty, $arrFormElements);
        $oForm->setEditMode();

        $smarty->assign("username", $_POST['username']);
        $smarty->assign("id_domain", $id_domain);
        $smarty->assign("old_quota", $arrTmp['quota']);
        $smarty->assign("account_name_label", $arrLang['Account Name']);

        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_account.tpl", $arrLang["Edit Account"], $arrTmp); // hay que pasar el arreglo


    } else if(isset($_POST['save'])) { 
        //GUARDAR NUEVA CUENTA
        $oForm = new paloForm($smarty, $arrFormElements);
        $arrDomain= $pEmail->getDomains($_POST['id_domain']);
        $domain_name=$arrDomain[0][1];
        
        $bMostrarForm=FALSE;
        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            //validar campos de password
            if(empty($_POST['password1']) or ($_POST['password1']!=$_POST['password2'])) {
                // Error claves
                $smarty->assign("mb_message", $arrLang["The passwords are empty or don't match"]);
                $bMostrarForm=TRUE;

            }else{
                $bExito=create_email_account($pDB,$domain_name,$error);
                if (!$bExito){
                    $smarty->assign("mb_message", $error);
                    $bMostrarForm=TRUE;
                }
                else
                    header("Location: ?menu=email_accounts&id_domain=$_POST[id_domain]");
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
            $bMostrarForm=TRUE;
        }
        if ($bMostrarForm){
              
               $smarty->assign("id_domain", $_POST['id_domain']);
               $smarty->assign("domain_name", "@".$domain_name);
               $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_account.tpl", $arrLang["New Email Account"], $_POST);
               $verListado=FALSE;
        }

    } else if(isset($_POST['apply_changes'])) {
        $verListado=FALSE;
        $noCambioPass=FALSE;
        $oForm = new paloForm($smarty, $arrFormElements);
        if (empty($_POST['password1']) && empty($_POST['password2'])){
           $noCambioPass=TRUE;
           $_POST['password1']=$_POST['password2']='x';
        }

        $oForm->setEditMode();
        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            //validar campos de password
            if ($noCambioPass) $_POST['password1']=$_POST['password2']='';
            if($_POST['password1']!=$_POST['password2']) {
                // Error claves
                $smarty->assign("mb_message", $arrLang["The passwords don't match"]);
                $bMostrarForm=TRUE;

            }else{
                $bExito=edit_email_account($pDB,$error);
                if (!$bExito || ($bExito && !empty($error))){
                    $smarty->assign("mb_message", $error);
                    $bMostrarForm=TRUE;
                }
                else
                    header("Location: ?menu=email_accounts&id_domain=$_POST[id_domain]");
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
            $bMostrarForm=TRUE;

            /////////////////////////////////
        }
        if ($bMostrarForm){
              
               $smarty->assign("id_domain", $_POST['id_domain']);
               $smarty->assign("username", $_POST['username']);
               $smarty->assign("account_name_label", $arrLang['Account Name']);
               $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_account.tpl", $arrLang["Edit Account"], $_POST);
               $verListado=FALSE;
        }

    } else if(isset($_GET['action']) && $_GET['action']=="view") {
        $verListado=FALSE;
        $oForm = new paloForm($smarty, $arrFormElements);
        
        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error

        $oForm->setViewMode(); // Esto es para activar el modo "preview"
        $arrAccount = $pEmail->getAccount($_GET['username']);
//username, password, id_domain, quota
        $arrTmp['username']        = $arrAccount[0][0];
        $arrTmp['password1']        = "";
        $arrTmp['password2']        = "";
        $arrTmp['quota']        = $arrAccount[0][3];
        $id_domain        = $arrAccount[0][2];



        $smarty->assign("username", $_GET['username']);
        $smarty->assign("id_domain", $id_domain);
        $smarty->assign("account_name_label", $arrLang['Account Name']);

        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/form_account.tpl", $arrLang["View Account"], $arrTmp); // hay que pasar el arreglo

    }else if (isset($_POST['delete'])){
        $verListado=FALSE;
        $bExito=eliminar_cuenta($pDB,$_POST['username'],$errMsg);
          
        if (!$bExito) $smarty->assign("mb_message", $errMsg);
        else header("Location: ?menu=email_accounts&id_domain=$_POST[id_domain]");
    }

    if ($verListado){

       $id_domain=0;
       if (isset($_POST['domain'])) $id_domain=$_POST['domain'];
       if (isset($_GET['id_domain'])) $id_domain=$_GET['id_domain'];
       $_POST['domain']=$id_domain;
       $arrDominios    = array("0"=>'-- '.$arrLang["Select a domain"].' --');
        //LISTADO DE CUENTAS
       //FILTRO PARA LISTADO
       $arrDomains = $pEmail->getDomains();
       foreach($arrDomains as $domain) {
            $arrDominios[$domain[0]]    = $domain[1];
       }
       $arrFormElements = array(
                                 "domain"  => array("LABEL"                  => $arrLang["Domain"],
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "SELECT",
                                                        "INPUT_EXTRA_PARAM"      => $arrDominios,
                                                        "VALIDATION_TYPE"        => "integer",
                                                        "VALIDATION_EXTRA_PARAM" => ""),
                                 
                                 );
    
        $oFilterForm = new paloForm($smarty, $arrFormElements);
        $smarty->assign("SHOW", $arrLang["Show"]);
        $smarty->assign("CREATE_ACCOUNT", $arrLang["Create Account"]);
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/accounts_filter.tpl", "", $_POST);

////////////////////////////////////////////////////////////////////////////////////////
        $arrData=array();
 
		$end=0;
        if ($id_domain>0){
            $arrAccounts = $pEmail->getAccountsByDomain($id_domain);
 //username, password, id_domain, quota

            $end = count($arrAccounts);
           // $arrAccounts[]=array("gladys.gelicar.com","gladys",1,20000);
            foreach($arrAccounts as $account) {
                $arrTmp    = array();
                $username=$account[0];
                $arrAlias=$pEmail->getAliasAccount($username);
                $direcciones=''; 
                if(is_array($arrAlias) && count($arrAlias)>0){
                   foreach($arrAlias as $fila){
                        $direcciones.=(empty($direcciones))?'':'<br>';
                        $direcciones.=$fila['1']; 
                   }
                }
                $id_domain=$account[2];
                $arrTmp[0]=$direcciones;
                $arrTmp[1] = "&nbsp;<a href='?menu=email_accounts&action=view&username=".$username."'>$username</a>";

                $arrTmp[2]=obtener_quota_usuario($username);
                $link_agregar_direccion="<a href=\"?action=add_address&id_domain=$id_domain&username=$username\">"."Add Address"."</a>";
                $link_modificar_direccion="<a href=\"?action=edit_addresses&id_domain=$id_domain&username=$username\">"."Addresses"."</a>";   
             //   $arrTmp[3]=$link_agregar_direccion."&nbsp;&nbsp; ".$link_modificar_direccion;;
                $arrData[] = $arrTmp;
            }
        }
        $arrGrid = array("title"    => $arrLang["Email Account List"],
                         "icon"     => "images/list.png",
                         "width"    => "99%",
                         "start"    => ($end==0) ? 0 : 1,
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => $arrLang["Email Address"],
                                                        "property1" => ""),
                                             1 => array("name"      => $arrLang["Account Name"], 
                                                        "property1" => ""),
                                             2 => array("name"      => $arrLang["Used Space"], 
                                                        "property1" => ""),
                                        //     3 => array("name"      => $arrLang["Options"], 
                                           //             "property1" => ""),

                                            )
                        );

        $oGrid = new paloSantoGrid($smarty);

        $oGrid->showFilter(trim($htmlFilter));
        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }

    return $contenidoModulo;
}

//funciones separadas

function create_email_account($pDB,$domain_name,&$errMsg)
{
    $bReturn=FALSE;
    global $arrLang;
    $pEmail = new paloEmail($pDB);
    //creo la cuenta
    // -- valido que el usuario no exista
    // -- si no existe creo el usuario en el sistema con sasldbpasswd2
    // -- inserto el usuario en la base de datos
    // -- si hay error al insertarlo en la bd lo elimino del sistema
    // -- creo el mailbox para la cuenta (si hay error deshacer lo realizado)

    $username=$_POST['address'].'.'.$domain_name;
    $arrAccount=$pEmail->getAccount($username);
    
    if (is_array($arrAccount) && count($arrAccount)>0 ){
       //YA EXISTE ESA CUENTA
        $errMsg=$arrLang["The e-mail address already exists"].": $_POST[address]@$domain_name";
        return FALSE;
    }
    $email=$_POST['address'].'@'.$domain_name;
    //crear la cuenta de usuario en el sistema

    $bExito=crear_usuario_correo_sistema($email,$username,$_POST['password1'],$errMsg);
    if(!$bExito) return FALSE;
    //inserto la cuenta de usuario en la bd
     $bExito=$pEmail->createAccount($_POST['id_domain'],$username,$_POST['password1'],$_POST['quota']);
    if ($bExito){
        
        //crear el mailbox para la nueva cuenta
        $bReturn=crear_mailbox_usuario($pDB,$email,$username,$errMsg); 
    }else{ 
        //tengo que borrar el usuario creado en el sistema
        $bReturn=eliminar_usuario_correo_sistema($username,$email,$errMsg);
        $errMsg= (isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg;
    }
    return $bReturn;

}



function crear_mailbox_usuario($db,$email,$username,&$error_msg){
    global $CYRUS;
    global $arrLang;
    $pEmail = new paloEmail($db);
    $cyr_conn = new cyradm;
    $error=$cyr_conn->imap_login();
    if ($error===FALSE){
        $error_msg.="IMAP login error: $error <br>";
    }
    else{
        $seperator	= '/';
        $bValido=$cyr_conn->createmb("user" . $seperator . $username);
        if(!$bValido)
            $error_msg.="Error creating user:".$cyr_conn->getMessage()."<br>";
        else{
            $bValido=$cyr_conn->setacl("user" . $seperator . $username, $CYRUS['ADMIN'], "lrswipcda");
            if(!$bValido)
                $error_msg.="error:".$cyr_conn->getMessage()."<br>";
            else{
                $bValido = $cyr_conn->setmbquota("user" . $seperator . $username, $_POST['quota']);
                if(!$bValido)
                    $error_msg.="error".$cyr_conn->getMessage()."<br>";
            }
        }
                    
        //Ahora se tiene que setear el script default de sieve para el usuario (defaultbc)
        /*$daemon = new sieve("localhost","2000", $username, $CYRUS['PASS'], $CYRUS['ADMIN']);
        if ($daemon->sieve_login()){
            $script=DEFAULT_SCRIPT; //El script inicial cuando se crea una cuenta de mail
            if ($daemon->sieve_sendscript('sieve', $script) ){
                if($daemon->sieve_setactivescript('sieve'))
                    $error= "Forward set";
                else
                    $error_msg.="Error setting script as active<br>";
            }
            else 
                $error_msg .= "Failure in setting forward<br>";    
                     
        } 
        else {
            $error_msg.="Sieve: Incorrect Password<br>";
        }*/
    }
    if($error_msg!=""){
        //Si hay error se trata de borrar la fila ingresada
        $bValido=$pEmail->deleteAccount($username);
        if(!$bValido) $error_msg=(isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg;
        //borrar la cuenta del sistema
        eliminar_usuario_correo_sistema($username,$email,$error_msg);
        return FALSE;
    }
    else{
        $bValido=$pEmail->createAliasAccount($username,$email);
        if(!$bValido){
            $error_msg=$arrLang["The account was created but could not add record for the e-mail in alias table"];
            return FALSE;
        }
    }
    return TRUE;         
}





function obtener_quota_usuario($username)
{
    global $CYRUS;
    global $arrLang;
    $cyr_conn = new cyradm;
    $cyr_conn->imap_login();

    $quota = $cyr_conn->getquota("user/" . $username);
    $tamano_usado=$arrLang["Could not query used disc space"];
            
    if(is_array($quota) && count($quota)>0){
            
        if ($quota['used'] != "NOT-SET"){
            $q_used  = $quota['used'];
            $q_total = $quota['qmax'];
            if (! $q_total == 0){
                $q_percent = number_format((100*$q_used/$q_total),2);
                $tamano_usado="$quota[used] Kb / $quota[qmax] Kb ($q_percent%)";
            } 
            else {
                $tamano_usado=$arrLang["Could not obtain used disc space"];
            }
        } else {
            $tamano_usado=$arrLang["Size is not set"];
        }      
    }
    return $tamano_usado;
}


   


function edit_email_account($pDB,$error)
{
    global $CYRUS;
    global $arrLang;
    $bExito=TRUE;
    $error_pwd='';
    $pEmail = new paloEmail($pDB);
    if (isset($_POST['password1']) && trim($_POST['password1'])!="")
    {   
        $username=$_POST['username'];
        $bool=crear_usuario_correo_sistema("",$username,$_POST['password1'],$error,FALSE); //False al final para indicar que no cree virtual
        if(!$bool){
          $error_pwd=$arrLang["Password could not be changed"];
        }
    }
    if($_POST['old_quota']!=$_POST['quota']){
        $cyr_conn = new cyradm;
        $cyr_conn->imap_login();
        $bContinuar=$cyr_conn->setmbquota("user" . "/".$_POST['username'], $_POST['quota']);
        if ($bContinuar){
           //actualizar en la base de datos
            $bExito=$pEmail->updateAccount($_POST['username'], $_POST['quota']);
            if (!$bExito) $error=(isset($arrLang[$pEmail->errMsg]))?$arrLang[$pEmail->errMsg]:$pEmail->errMsg;
        }else{ $error=$cyr_conn->getMessage();}
    }

    if ($bExito && !empty($error_pwd))
        $error=$error_pwd;

    return $bExito;
}

?>
