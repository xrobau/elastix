<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.com                                               |
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
  $Id: index.php,v 1.2 2007/08/10 01:32:53 gcarrillo Exp $
  $Id: index.php,v 1.3 2011/06/21 17:30:33 Eduardo Cueva ecueva@palosanto.com Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoEmail.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/cyradm.php";
    include_once "configs/email.conf.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pDB = new paloDB($arrConf['dsn_conn_database']);

    $error="";
    $errMsg = "";
    $contenidoModulo = "";
    $arrData = array();


    $virtual_postfix = FALSE; // indica si se debe escribir el archivo /etc/postfix/virtual

    $bMostrarListado=TRUE;

    $content = "";
    $accion = getAction();
    switch($accion)
    {
        case "new":
            $content = viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "save":
            $content = saveAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "delete":
            $content = deleteAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "edit":
            $content = viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "apply_changes":
            $content = saveAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "view":
            $content = viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "export":
            $content = exportAccounts($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "viewFormEditQuota":
            $content = viewFormEditQuota($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "edit_quota":
            $content = edit_quota($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "reconstruir":
                $content = reconstruir_mailBox($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
                $content = viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
                break;
    }

    return $content;
}


function viewFormAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $oGrid = new paloSantoGrid($smarty);
    $id_domain=0;

    /*if (isset($_POST['domain'])) $id_domain=$_POST['domain'];
    if (isset($_GET['id_domain'])) $id_domain=$_GET['id_domain'];*/
    if(isset($_POST['domain']) || isset($_GET['domain'])){
        $id_domain=getParameter('domain');
        if($id_domain==null)
            $id_domain=0;
    }

    $_POST['domain']=$id_domain;

    $arrDominios    = array("0"=>'-- '._tr("Select a domain").' --');

    $arrDomains = $pEmail->getDomains();
    foreach($arrDomains as $domain) {
        $arrDominios[$domain[0]] = $domain[1];
    }

    $arrFormElements = createFieldFormAccount($arrDominios);

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("CREATE_ACCOUNT", _tr("Create Account"));

   // $oGrid->pagingShow(true);
    $url = array("menu" => $module_name);
    if($id_domain == 0)
       $url = array("menu" => $module_name);
    else
       $url = array("menu" => $module_name, "domain" => $id_domain);
    $oGrid->setURL($url);
    $oGrid->setTitle(_tr("Email Account List"));


////////////////////////////////////////////////////////////////////////////////////////////////////////

    $arrData = array();

    $pACL = new paloACL(new paloDB($arrConf['elastix_dsn']['acl']));
    $userAccount = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";

    $reconstruir = _tr("Reconstruct");

    if ($id_domain>0){
        $arrAccounts = $pEmail->getAccountsByDomain($id_domain);
    }else{
        $arrAccounts = $pEmail->getAccountsByDomain();
    }

    if(is_array($arrAccounts)){
        //username, password, id_domain, quota
        $end = count($arrAccounts);
        //$configPostfix2 = isPostfixToElastix2();// in misc.lib.php
        foreach($arrAccounts as $account) {
            $arrTmp    = array();
            $username=$account[0];
            $arrTmp[0] = "&nbsp;<a href='?menu=$module_name&action=view&username=$username'>$username</a>";
            $arrTmp[1] = obtener_quota_usuario($pEmail, $username,$module_name,$id_domain);
            $arrTmp[2] = "&nbsp;<a href='?menu=$module_name&action=reconstruir&username=$username&domain=$id_domain'>$reconstruir</a>";
            $link_agregar_direccion="<a href='?action=add_address&id_domain=$id_domain&username=$username'>Add Address</a>";
            $link_modificar_direccion="<a href='?action=edit_addresses&id_domain=$id_domain&username=$username'>Addresses</a>";
            //$arrTmp[3]=$link_agregar_direccion."&nbsp;&nbsp; ".$link_modificar_direccion;
            $arrData[] = $arrTmp;
        }
    }else{
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", _tr($pEmail->errMsg));
    }

    $total = count($arrData);
    $limit = 20;

    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $inicio = ($total == 0) ? 0 : $offset + 1;
    $fin = ($offset+$limit) <= $total ? $offset+$limit : $total;
    $leng = $fin - $inicio;

    $arrDatosGrid = array_slice($arrData, $inicio-1, $leng+1);

    $oGrid->setColumns(array(_tr("Account Name"),_tr("Used Space"),_tr("Reconstruct MailBox")));

    $arrGrid = array(
        "width"    => "99%",
        "start"    => $inicio,
        "end"      => $fin,
        "total"    => $total,
            );

    $oGrid->addComboAction("domain",_tr("Create Account"), $arrDominios,$id_domain,"submit_create_account", "this.form.submit();");
    if ($id_domain != 0) {
        $oGrid->addLinkAction("?menu=$module_name&action=export&domain=$id_domain&rawmode=yes", _tr("Export Accounts"));
        $smarty->assign("id_domain",$id_domain);
    }

    $content = $oGrid->fetchGrid($arrGrid,$arrDatosGrid);
    return $content;
}

function reconstruir_mailBox($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pACL = new paloACL(new paloDB($arrConf['elastix_dsn']['acl']));
    $pEmail = new paloEmail($pDB);

    $userAccount = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";

    if($pEmail->resconstruirMailBox(getParameter("username"))){
        $smarty->assign("mb_title", _tr('MESSAGE').":");
        $smarty->assign("mb_message", _tr("The MailBox was reconstructed succefully"));
    }else{
        $smarty->assign("mb_title", _tr('ERROR').":");
        $smarty->assign("mb_message",_tr("The MailBox couldn't be reconstructed.\n".$pEmail->errMsg));
    }

    unset($_GET['action']);

    return viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function viewDetailAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $arrFormElements = createFieldFormNewAccount();
    $oForm    = new paloForm($smarty, $arrFormElements);
    //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error
    $typeForm    = _tr("View Account");
    $arrTmp      = array();
    $userName    = getParameter('username');
    $quota       = getParameter("quota");
    $id_domain   = getParameter("id_domain");
    $domain_name = getParameter("domain");
    $address     = getParameter("address");

    if(getParameter("option_create_account") && getParameter("option_create_account")=="by_file"){
        $smarty->assign("check_file", "checked");
        $smarty->assign("DISPLAY_SAVE_ACCOUNT", "style=display:none;");
    }
    else{
        $smarty->assign("check_record", "checked");
        $smarty->assign("DISPLAY_FILE_UPLOAD", "style=display:none;");
    }
    if(getParameter("action") == "view"){
        $oForm->setViewMode(); // Esto es para activar el modo "preview"
    }elseif(getParameter("submit_create_account") || getParameter("save")){
        //nothing
        $typeForm = _tr("Create Account");
        //obtener el nombre del dominio
        $domain_name = isset($domain_name)?$domain_name:$id_domain;
        $id_domain = $domain_name;
        $arrDomain= $pEmail->getDomains($domain_name);
        if(!is_array($arrDomain) || count($arrDomain)==0 || $domain_name==0){
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", _tr("You must select a domain to create an account"));
            $content = viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            return $content;
        }
        $domain_name="@".$arrDomain[0][1];
        $smarty->assign("domain_name", $domain_name);
        $smarty->assign("domainName", $arrDomain[0][1]);
        $arrTmp['address']   = isset($address)?$address:"";
        $arrTmp['password1'] = "";
        $arrTmp['password2'] = "";
        $arrTmp['quota']     = isset($quota)?$quota:"";
        $smarty->assign("old_quota", $arrTmp['quota']);
    }else{
        $oForm->setEditMode();
        $typeForm = _tr("Edit Account");
    }

    if($oForm->modo == "view" || $oForm->modo == "edit"){
        $arrAccount = $pEmail->getAccount($userName);
        //username, password, id_domain, quota
        $arrTmp['username']  = $arrAccount[0][0];
        if($oForm->modo == "view"){
            $arrTmp['password1'] = "****";
            $arrTmp['password2'] = "****";
        }else{
            $arrTmp['password1'] = "";
            $arrTmp['password2'] = "";
        }
        $arrTmp['quota']     = isset($quota)?$quota:$arrAccount[0][3];
        $id_domain           = $arrAccount[0][2];
        $smarty->assign("username", $userName);
        $smarty->assign("id_domain", $id_domain);
        $smarty->assign("old_quota", $arrTmp['quota']);
    }

    $smarty->assign("id_domain", $id_domain);
    $smarty->assign("account_name_label", _tr('Account Name'));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("account", _tr("Account"));
    $smarty->assign("file_upload", _tr("File Upload"));
    $smarty->assign("file_Label", _tr("File Upload"));
    $smarty->assign("INFO", _tr("The format of the file must be csv (file.csv), like the following").":<br /><br /><b>"._tr("Username1,Password1,Quota1(Kb)")."</b><br /><b>"._tr("Username2,Password2,Quota2(Kb)")."</b><br /><br />"._tr("The value of Quota(Kb) must be a number, like 1000 or 2000, etc"));
    $content = $oForm->fetchForm("$local_templates_dir/form_account.tpl", $typeForm, $arrTmp); // hay que pasar el arreglo
    return $content;
}


function saveAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $domain = getParameter("domain_name");
    $pEmail = new paloEmail($pDB);
    if(getParameter("option_create_account") && getParameter("option_create_account")=="by_file"){
        if(isset($_FILES["file_accounts"])){
            if($_FILES["file_accounts"]["name"] != ""){
                $smarty->assign("file_accounts_name", $_FILES['file_accounts']['name']);
                if (!preg_match("/^(\w|-|\.|\(|\)|\s)+\.(csv)$/",$_FILES['file_accounts']['name'])){
                    $smarty->assign("mb_title", _tr('ERROR').":");
                    $smarty->assign("mb_message", _tr("Possible file upload attack. The file must end in .csv"));
                    return viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
                }
                if(!move_uploaded_file($_FILES['file_accounts']['tmp_name'], "/tmp/$_FILES[file_accounts][name]")){
                    $smarty->assign("mb_title", _tr('ERROR').":");
                    $smarty->assign("mb_message", _tr("Possible file upload attack. The file must end in .csv"));
                    return viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
                }
                $handler = fopen("/tmp/$_FILES[file_accounts][name]","r");
                $arrErrorAccounts = array();
                $arrAccounts = array();
                if($handler !== false){
                    while(($data = fgetcsv($handler,10000)) !== false){
                        if(count($data) >= 3){
                            $_POST["address"] = $data[0];
                            $_POST["password1"] = $data[1];
                            $_POST["password2"] = $data[1];
                            $_POST["quota"] = (int)$data[2];
                            $quotaIsTooGreat = false;
                            if($_POST["quota"] > 5242880){
                                $quotaIsTooGreat = true;
                                $_POST["quota"] = 5242880;
                            }
                            $configPostfix2 = isPostfixToElastix2();// in misc.lib.php
                            if($configPostfix2)
                                $username=$_POST['address'].'@'.$domain;
                            else
                                $username=$_POST['address'].'.'.$domain;
                            $arrAccount=$pEmail->getAccount($username);
                            if (is_array($arrAccount) && count($arrAccount)>0 )
                                $arrErrorAccounts[] = $data[0]."@$domain : "._tr("The e-mail address already exists");
                            else{
                                if(saveOneAccount($smarty, $pDB, true)){
                                    if($quotaIsTooGreat)
                                        $arrAccounts[] = $data[0]."@$domain : "._tr("The quota was reduced to the maximum of 5242880KB, if you want to more than this, edit this account");
                                    else
                                        $arrAccounts[] = $data[0]."@$domain";
                                }
                                else
                                    $arrErrorAccounts[] = $data[0]."@$domain : "._tr("Error saving the account");
                            }
                        }
                        else
                            $arrErrorAccounts[] = $data[0]."@$domain : "._tr("At least three parameters are needed");
                    }
                }
                else{
                    $smarty->assign("mb_title", _tr('ERROR').":");
                    $smarty->assign("mb_message", _tr("The file could not be opened"));
                    return viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
                }
                $message = "";
                if(count($arrAccounts)>0){
                    $message .= "<b>"._tr("The following accounts were created").":</b><br />";
                    foreach($arrAccounts as $account)
                        $message .= htmlentities($account)."<br />";
                }
                if(count($arrErrorAccounts)>0){
                    $message .= "<b>"._tr("The following accounts could not be created").":</b><br />";
                    foreach($arrErrorAccounts as $errAccounts)
                        $message .= $errAccounts."<br />";
                }
                $smarty->assign("mb_message",$message);
                unlink("/tmp/$_FILES[file_accounts][name]");
                return viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            }
            else{
                $smarty->assign("mb_title", _tr('ERROR').":");
                $smarty->assign("mb_message", _tr("Error reading the file"));
                return viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            }
        }
        else{
            $smarty->assign("mb_title", _tr('ERROR').":");
            $smarty->assign("mb_message", _tr("Error reading the file"));
            return viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        }
    }
    else{
        if(saveOneAccount($smarty, $pDB, false))
            return viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        else
            return viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
}

function saveOneAccount($smarty, &$pDB, $isFromFile)
{
    $pEmail = new paloEmail($pDB);
    $arrFormElements = createFieldFormNewAccount();
    $noCambioPass = FALSE;
    $oForm = new paloForm($smarty, $arrFormElements);

    $password1   = getParameter("password1");
    $password2   = getParameter("password2");
    $id_domain   = getParameter("id_domain");
    $userName    = getParameter("username");
    $domain_name = getParameter("domain_name");
    $address     = getParameter("address");
    $quota       = getParameter("quota");
    $error = "";
    $bExito = FALSE;

    if (empty($password1) && empty($password2)){
        $noCambioPass = TRUE;
        $password1 = $password2 = 'x';
    }

    $oForm->setEditMode();

    if(!$oForm->validateForm($_POST)) {
        // Manejo de Error
        if(!$isFromFile){
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_title", _tr("Validation Error"));
            $smarty->assign("mb_message", $strErrorMsg);
        }
        $content = false;
    }elseif(!preg_match("/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*$/",$address) && isset($address) && $address!=""){
        if(!$isFromFile){
            $smarty->assign("mb_title", _tr("Validation Error"));
            $smarty->assign("mb_message", _tr("Wrong format for username"));
        }
        $content = false;
    }elseif($quota <= 0){
        if(!$isFromFile){
            $smarty->assign("mb_title", _tr("Validation Error"));
            $smarty->assign("mb_message", _tr("Quota must be greater than 0"));
        }
        $content = false;
    }
    else{
        if($noCambioPass) $password1 = $password2 = '';
        if($password1 != $password2) {
            // Error claves
                $smarty->assign("mb_title", _tr("Error"));
                $smarty->assign("mb_message", _tr("The passwords don't match"));

            $content = false;
        }else{
            if(getParameter("save")){
                if($password1==""){
                    if(!$isFromFile){
                        $smarty->assign("mb_title", _tr("Error"));
                        $smarty->assign("mb_message", _tr("The passwords must not be empty"));
                        $smarty->assign("id_domain", $id_domain);
                        $smarty->assign("username", $userName);
                        $smarty->assign("old_quota", getParameter("quota"));
                        $smarty->assign("account_name_label", _tr('Account Name'));
                    }
                    return false;
                }else
                    $bExito = create_email_account($pDB,$domain_name,$error);
            }else
                $bExito = edit_email_account($pDB,$error);
            if (!$bExito || ($bExito && !empty($error))){
                if(!$isFromFile){
                    $smarty->assign("mb_title", _tr('ERROR').":");
                    $smarty->assign("mb_message", _tr("Error applying changes").". ".$error);
                }
            }
            else{
                if(!$isFromFile){
                    $smarty->assign("mb_title", _tr('MESSAGE').":");
                    $smarty->assign("mb_message", _tr("Changes Applied successfully"));
                }
                $content = true;
            }
        }
        /////////////////////////////////
    }

    if(!$isFromFile){
        $smarty->assign("id_domain", $id_domain);
        $smarty->assign("username", $userName);
        $smarty->assign("old_quota", getParameter("quota"));
        $smarty->assign("account_name_label", _tr('Account Name'));
    }
    return $content;
}

function viewFormEditQuota($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $username = getParameter("username");
    $quota    = getParameter("quota");
    if(!$pEmail->accountExists($username)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message", _tr("The following account does not exist").": $username");
        return viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $arrFormElements = createFieldFormEditQuota();
    $oForm = new paloForm($smarty, $arrFormElements);
    $arrAccount = $pEmail->getAccount($username);
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("username", $username);
    $smarty->assign("old_quota", $arrAccount[0][3]);
    $smarty->assign("icon", "images/list.png");

    $id_domain=getParameter('domain');
    if($id_domain==null)
        $id_domain=0;
    $smarty->assign('domain', $id_domain);

    $_POST["quota"] = (isset($quota))?$quota:$arrAccount[0][3];
    $htmlForm = $oForm->fetchForm("$local_templates_dir/edit_quota.tpl", _tr("Edit quota to").": $username", $_POST);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;
}

function edit_quota($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $username = getParameter("username");
    $quota    = getParameter("quota");
    $old_quota = getParameter("old_quota");
    if(!$pEmail->accountExists($username)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message", _tr("The following account does not exist").": $username");
        return viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $arrFormElements = createFieldFormEditQuota();
    $oForm = new paloForm($smarty, $arrFormElements);
    if(!$oForm->validateForm($_POST)) {
        // Falla la validación básica del formulario
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        $arrErrores = $oForm->arrErroresValidacion;
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
            }
        }
        $smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", $strErrorMsg);
        return viewFormEditQuota($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $bExito=TRUE;
    $error = "";
    if ($old_quota != $quota) {
        $bExito = $pEmail->setAccountQuota($username, $quota);
        if (!$bExito) $error = _tr($pEmail->errMsg);
    }
    if(!$bExito){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message", _tr("Error applying changes").". $error");
    }else
        $smarty->assign("mb_message", _tr("Changes Applied successfully"));
    return viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function exportAccounts($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $id_domain = getParameter("domain");
    $arrAccounts = $pEmail->getAccountsByDomain($id_domain);
    $domainName = $pEmail->getDomains($id_domain);
    if(isset($domainName[0][1]))
        $domainName = $domainName[0][1];
    else
        $domainName = "no_domain";
    $text = "";

    if($arrAccounts===false){
        $text = $domainName.","._tr("A Error has ocurred when tryed to obtain emails accounts data. ").$pEmail->errMsg;
    }elseif(count($arrAccounts)==0){
        $text = $domainName.","._tr("There aren't emails accounts associted with the domain");
    }else{
        foreach($arrAccounts as $account){
            if($text != "")
                $text .= "\n";
            $user = explode("@",$account[0]);
            $text .= $user[0].",".$account[1].",".$account[3];
        }
    }


    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: csv file");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=$domainName"."_accounts.csv");
    header("Content-Transfer-Encoding: binary");
    header("Content-length: ".strlen($text));
    echo $text;
}

function deleteAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail   = new paloEmail($pDB);
    $username = getParameter("username");
    $errMsg = "";
    $bExito = $pEmail->deleteAccount($username);
    if (!$bExito){
        $smarty->assign("mb_message", _tr("Error appliying changes").". ".$errMsg);
        $content = viewDetailAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    else{
        $smarty->assign("mb_message", _tr("Account deleted successfully"));
        $content = viewFormAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    return $content;
}


//funciones separadas

function create_email_account($pDB,$domain_name,&$errMsg)
{
    $pEmail = new paloEmail($pDB);
    //creo la cuenta
    // -- valido que el usuario no exista
    // -- si no existe creo el usuario en el sistema con sasldbpasswd2
    // -- inserto el usuario en la base de datos
    // -- si hay error al insertarlo en la bd lo elimino del sistema
    // -- creo el mailbox para la cuenta (si hay error deshacer lo realizado)
    $username = "";
    $configPostfix2 = isPostfixToElastix2();// in misc.lib.php

    if($configPostfix2)
        $username=$_POST['address'].'@'.$domain_name;
    else
        $username=$_POST['address'].'.'.$domain_name;

    $arrAccount=$pEmail->getAccount($username);

    if (is_array($arrAccount) && count($arrAccount)>0 ){
       //YA EXISTE ESA CUENTA
        $errMsg = _tr('The e-mail address already exists').": $_POST[address]@$domain_name";
        return FALSE;
    }
    $bReturn = $pEmail->createAccount($domain_name, $_POST['address'],
        $_POST['password1'], $_POST['quota']);
    if (!$bReturn) {
    	$errMsg = $pEmail->errMsg;
    }
    return $bReturn;
}

function obtener_quota_usuario($pEmail, $username,$module_name,$id_domain)
{
    $edit_quota = _tr("Edit quota");
    $quota = $pEmail->getAccountQuota($username);
    $tamano_usado=_tr("Could not query used disc space");
    if(is_array($quota) && count($quota)>0){
        if ($quota['used'] != "NOT-SET"){
            $q_used  = $quota['used'];
            $q_total = $quota['qmax'];
            if (! $q_total == 0){
                $q_percent = number_format((100*$q_used/$q_total),2);
                $tamano_usado="$quota[used] KB / <a href='?menu=$module_name&action=viewFormEditQuota&username=$username&domain=$id_domain' title='$edit_quota'>$quota[qmax] KB</a> ($q_percent%)";
            }
            else {
                $tamano_usado=_tr("Could not obtain used disc space");
            }
        } else {
            $tamano_usado=_tr("Size is not set");
        }
    }
    return $tamano_usado;
}


function edit_email_account($pDB,&$error)
{
    $bExito=TRUE;
    $error_pwd='';
    $virtual = FALSE;
    $pEmail = new paloEmail($pDB);
    if (isset($_POST['password1']) && trim($_POST['password1'])!="")
    {
        $username = $_POST['username'];
        $bool = $pEmail->setAccountPassword($username, $_POST['password1']);
        if(!$bool){
            $error_pwd = _tr('Password could not be changed').': '.$pEmail->errMsg;
            $bExito = FALSE;
        }
    }
    if ($_POST['old_quota'] != $_POST['quota']) {
        $bExito = $pEmail->setAccountQuota($_POST['username'], $_POST['quota']);
        if (!$bExito) {
        	$error = _tr($pEmail->errMsg);
        }
    }
    if ($bExito && !empty($error_pwd))
        $error=$error_pwd;
    return $bExito;
}

function createFieldFormNewAccount()
{
    $arrFields = array(
                             "address"       => array("LABEL"                   => _tr("Email Address"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*$"),
                             "quota"   => array("LABEL"                  => _tr("Quota (Kb)"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "password1"   => array("LABEL"                  => _tr("Password"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "password2"   => array("LABEL"                  => _tr("Retype password"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                         );

    return $arrFields;
}

function createFieldFormEditQuota()
{
    $arrFields = array(
                             "quota"   => array("LABEL"                  => _tr("Quota (Kb)"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                         );

    return $arrFields;
}

function createFieldFormAccount($arrDominios)
{
    $arrFields = array(
                "domain"  => array("LABEL"                  => _tr("Domain"),
                                      "REQUIRED"               => "no",
                                      "INPUT_TYPE"             => "SELECT",
                                      "INPUT_EXTRA_PARAM"      => $arrDominios,
                                      "VALIDATION_TYPE"        => "integer",
                                      "VALIDATION_EXTRA_PARAM" => "",
                                      "ONCHANGE"               => "javascript:submit();"),
                );
    return $arrFields;
}

function getAction()
{
    if(getParameter("submit_create_account")) //Get parameter by POST (submit)
        return "new";
    if(getParameter("save"))
        return "save";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("edit"))
        return "edit";
    else if(getParameter("apply_changes"))
        return "apply_changes";
    else if(getParameter("cancel"))
        return "report";
    else if(getParameter("edit_quota"))
        return "edit_quota";
    else if(getParameter("action")=="view") //Get parameter by GET (command pattern, links)
        return "view";
    else if(getParameter("action")=="export")
                return "export";
    else if(getParameter("action")=="viewFormEditQuota")
                return "viewFormEditQuota";
        else if(getParameter("action")=="reconstruir")
                return "reconstruir";
    else
        return "report";
}

?>
