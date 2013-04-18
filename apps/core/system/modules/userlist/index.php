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
include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name){
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoConfig.class.php");
    include_once("libs/paloSantoGrid.class.php");
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoOrganization.class.php";
    include_once("libs/paloSantoACL.class.php");
    include_once "modules/$module_name/configs/default.conf.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

        //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion elastix.db
    $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB);

    //verificar que tipo de usurio es: superadmin, admin o other
    $arrCredentiasls=getUserCredentials();
    $userLevel1=$arrCredentiasls["userlevel"];
    $userAccount=$arrCredentiasls["userAccount"];
    $idOrganization=$arrCredentiasls["id_organization"];


    $action = getAction();
    $content = "";

        switch($action){
        case "new_user":
            $content = viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view":
            $content = viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_edit":
            $content = viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_new":
            $content = saveNewUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_edit":
            $content = saveEditUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "delete":
            $content = deleteUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "getGroups":
            $content = getGroups($pDB);
            break;
        case "getImage":
            $content = getImage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        default: // report
            $content = reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;

}

function reportUser($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
    {
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);

    $idOrgFil=getParameter("idOrganization");
    if(!isset($idOrgFil)){
        $idOrgFil=0;
        $url = "?menu=$module_name";
    }else{
        $url = "?menu=$module_name&idOrganization=$idOrgFil";
    }

    $total=0;
    if($userLevel1=="superadmin"){
        if($idOrgFil!=0)
            $total=$pACL->getNumUsers($idOrgFil);
        else
            $total=$pACL->getNumUsers();
    }elseif($userLevel1=="admin"){
        $total=$pACL->getNumUsers($idOrganization);;
    }else
        $total=1;

    if($total===false){
        $total=0;
    }

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $arrGrid = array("title"    => _tr('User List'),
                "icon"     => "images/user.png",	
                "url"      => $url,
                "width"    => "99%",
                "start"    => ($total==0) ? 0 : $offset + 1,
                "end"      => $end,
                "total"    => $total,
                'columns'   =>  array(
                    array("name"      => _tr("Ursername"),),
                    array("name"      => _tr("Organization"),),
                    array("name"      => _tr("Name"),),
                    array("name"      => _tr("Group"),),
                    array("name"      => _tr("Extension")." / "._tr("Fax Extension"),)
                    ),
                );

    $arrUsers=array();
    $arrData = array();
    if($userLevel1=="superadmin"){
        if($idOrgFil!=0)
            $arrUsers = $pACL->getUsersPaging($limit, $offset, $idOrgFil);
        else
            $arrUsers = $pACL->getUsersPaging($limit, $offset);
    }elseif($userLevel1=="admin"){
        $arrUsers = $pACL->getUsersPaging($limit, $offset, $idOrganization);
    }else{
        $idUser=$pACL->getIdUser($userAccount);
        $arrUsers = $pACL->getUsers($idUser, $idOrganization, $limit, $offset);
    }

    IF($arrUsers===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pACL->errMsg));
    }
    //si es un usuario solo se ve a si mismo
    //si es un administrador ve a todo los usuarios de
    foreach($arrUsers as $user) {
        $arrTmp[0] = "&nbsp;<a href='?menu=userlist&action=view&id=$user[0]'>".$user[1]."</a>";    
        $arrOgz=$pORGZ->getOrganizationById($user[4]);
        $arrTmp[1] = htmlentities($arrOgz["name"], ENT_COMPAT, 'UTF-8');
        $arrTmp[2] = htmlentities($user[2], ENT_COMPAT, 'UTF-8');
        $gpTmp = $pACL->getGroupNameByid($user[7]);
        $arrTmp[3]=$gpTmp==("superadmin")?_tr("NONE"):$gpTmp;
        if(!isset($user[5]) || $user[5]==""){
            $ext=_tr("Not assigned");
        }else{
            $ext=$user[5];
        }
        if(!isset($user[6]) || $user[6]==""){
            $faxExt=_tr("Not assigned");
        }else{
            $faxExt=$user[6];
        }
        $arrTmp[4] = $ext." / ".$faxExt;
        $arrData[] = $arrTmp;
        $end++;
    }

    if($pORGZ->getNumOrganization() > 1){
        if(!($userLevel1 == "other"))
            $oGrid->addNew("create_user",_tr("Create New User"));

        if($userLevel1 == "superadmin"){
            $arrOrgz=array(0=>"all");
            foreach(($pORGZ->getOrganization()) as $value){
                if($value["id"]!=1)
                    $arrOrgz[$value["id"]]=$value["name"];
            }
            $arrFormElements = createFieldFilter($arrOrgz);
            $oFilterForm = new paloForm($smarty, $arrFormElements);
            $_POST["idOrganization"]=$idOrgFil;
            $oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization")." = ".$arrOrgz[$idOrgFil], $_POST, array("idOrganization" => 0),true);
            $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
            $oGrid->showFilter(trim($htmlFilter));
        }
    }else{
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create new user"));
    }

    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
    $mensaje=showMessageReload($module_name, $pDB, $userLevel1, $userAccount, $idOrganization);
    $contenidoModulo = $mensaje.$contenidoModulo;
    return $contenidoModulo;
}

function showMessageReload($module_name, &$pDB, $userLevel1, $userAccount, $idOrganization){
    $pDBMySQL=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    $pAstConf=new paloSantoASteriskConfig($pDBMySQL,$pDB);
    $params=array();
    $msgs="";

    $query = "SELECT domain, id from organization";
    //si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de mnarcada
    if($userLevel1!="superadmin"){
        $query .= " where id=?";
        $params[]=$idOrganization;
    }

    $mensaje=_tr("Click here to reload dialplan");
    $result=$pDB->fetchTable($query,false,$params);
    if(is_array($result)){
        foreach($result as $value){
            if($value[1]!=1){
                $showmessage=$pAstConf->getReloadDialplan($value[0]);
                if($showmessage=="yes"){
                    $append=($userLevel1=="superadmin")?" $value[0]":"";
                    $msgs .= "<div id='msg_status_$value[1]' class='mensajeStatus'><a href='?menu=$module_name&action=reloadAsterisk&organization_id=$value[1]'/><b>".$mensaje.$append."</b></a></div>";
                }
            }
        }
    }
    return $msgs;
}

function viewFormUser($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $arrFill=array();
    $action = getParameter("action");

    $arrOrgz=array(0=>"Select one Organization");
    if($userLevel1=="superadmin"){
        $orgTmp=$pORGZ->getOrganization("","","","");
    }else{
        $orgTmp=$pORGZ->getOrganization("","","id",$idOrganization);
    }

    if($orgTmp===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pORGZ->errMsg));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }elseif(count($orgTmp)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You need yo have at least one organization created before you can create a user"));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        if(($action=="new_user" || $action=="save_new")&& count($orgTmp)<=1){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create new user"));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
        foreach($orgTmp as $value){
            $arrOrgz[$value["id"]]=$value["name"];
        }
        $smarty->assign("ORGANIZATION",htmlentities($orgTmp[0]["name"], ENT_COMPAT, 'UTF-8'));
    }


    $idUser=getParameter("id");

    $arrFill=$_POST;

    if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        if(!isset($idUser)){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid User"));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else{
            if($userLevel1=="superadmin"){
                $arrUsers = $pACL->getUsers($idUser);
            }else if($userLevel1=="admin"){
                $arrUsers = $pACL->getUsers($idUser, $idOrganization, null, null);
            }else{
                $idUser=$pACL->getIdUser($userAccount);
                $arrUsers = $pACL->getUsers($idUser, $idOrganization, null, null);
            }
        }
        
        if($arrUsers===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pACL->errMsg));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else if(count($arrUsers)==0){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("User doesn't exist"));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else{
            $picture = $pACL->getUserPicture($idUser);
            if($picture!==false){
                $smarty->assign("ShowImg",1);
            }
            foreach($arrUsers as $value){
                $arrFill["username"]=$value[1];
                $arrFill["name"]=$value[2];
                $arrFill["password1"]="";
                $arrFill["password2"]="";
                $arrFill["organization"]=$value[4];
                $arrFill["group"]=$value[7];
                $extu=isset($value[5])?$value[5]:_tr("Not assigned yet");
                $extf=isset($value[6])?$value[6]:_tr("Not assigned yet");
                $arrFill["extension"]=$extu;
                $arrFill["fax_extension"]=$extf;
            }
            $smarty->assign("ORGANIZATION",htmlentities($arrOrgz[$arrFill["organization"]], ENT_COMPAT, 'UTF-8'));
            $smarty->assign("USERNAME",$arrFill["username"]);
            $nGroup=$pACL->getGroupNameByid($arrFill["group"]);
            if($nGroup=="superadmin");
                $nGroup=_tr("NONE");
            $smarty->assign("GROUP",$nGroup);
            $_POST["organization"]=$arrFill["organization"];
            //ahora obtenemos las propiedades del usuario
            $arrFill["country_code"]=$pACL->getUserProp($idUser,"country_code");
            $arrFill["area_code"]=$pACL->getUserProp($idUser,"area_code");
            $arrFill["clid_number"]=$pACL->getUserProp($idUser,"clid_number");
            $arrFill["clid_name"]=$pACL->getUserProp($idUser,"clid_name");
            $arrFill["email_quota"]=$pACL->getUserProp($idUser,"email_quota");
            if($idUser=="1")
                $arrFill["email_contact"]=$pACL->getUserProp($idUser,"email_contact");
            $smarty->assign("EMAILQOUTA",$arrFill["email_quota"]);
            $smarty->assign("EXTENSION",$extu);
            $smarty->assign("FAX_EXTENSION",$extf);
            if(getParameter("save_edit")){
                $arrFill=$_POST;
            }
        }
    }


    $idOrgSel=getParameter("organization");
    if(!isset($idOrgSel)){
        if($userLevel1!="superadmin"){
            $idOrgSel=$idOrganization;
        }else
            $idOrgSel=0;
    }

    if($idOrgSel==0){
        $arrGrupos=array();
    }else{
        $temp = $pACL->getGroupsPaging(null,null,$idOrgSel);
        if($temp===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pACL->errMsg));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
        foreach($temp as $value){
            $arrGrupos[$value[0]]=$value[1];
        }
    }

    if(getParameter("create_user")){
        $arrFill["country_code"]=$pORGZ->getOrganizationProp($idOrgSel,"country_code");
        $arrFill["area_code"]=$pORGZ->getOrganizationProp($idOrgSel,"area_code");
        $arrFill["email_quota"]=$pORGZ->getOrganizationProp($idOrgSel,"email_quota");
    }

    $arrOrgCombo=array();
    foreach($arrOrgz as $key => $value){
        if($key!="1")
            $arrOrgCombo[$key]=$value;
    }

    $arrFormOrgz = createFieldForm($arrGrupos,$arrOrgCombo);
    $oForm = new paloForm($smarty,$arrFormOrgz);

    $smarty->assign("HEIGHT","310px");
    $smarty->assign("MARGIN_PIC",'style="margin-top: 40px;"');
    $smarty->assign("MARGIN_TAB","");

    if($action=="view"){
        $smarty->assign("HEIGHT","220px");
        $smarty->assign("MARGIN_PIC","");
        $smarty->assign("MARGIN_TAB","margin-top: 10px;");
        $oForm->setViewMode();
        $arrFill["password1"]="*****";
        $arrFill["password2"]="*****";
        $smarty->assign("HEIGHT","220px");
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
    }

    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("icon","images/user.png");
    $smarty->assign("FAX_SETTINGS",_tr("Fax Settings"));
    $smarty->assign("EMAIL_SETTINGS",_tr("Email Settings"));
    $smarty->assign("MODULE_NAME",$module_name);
    $smarty->assign("userLevel", $userLevel1);
    $smarty->assign("id_user", $idUser);
    if(isset($arrUsers[0][1]))
        $smarty->assign("isSuperAdmin",$pACL->isUserSuperAdmin($arrUsers[0][1]));
    else
        $smarty->assign("isSuperAdmin",FALSE);

    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("User"), $arrFill);
    $content = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewUser($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $exito = false;
    $continuar=true;
    $errorImg="";
    $renameFile="";

    if($pORGZ->getNumOrganization() <=1){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create user"));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    if($userLevel1=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $arrOrgz=array(0=>"Select one Organization");
    if($userLevel1=="superadmin"){
        $orgTmp=$pORGZ->getOrganization("","","","");
    }else{
        $orgTmp=$pORGZ->getOrganization("","","id",$idOrganization);
    }

    if($orgTmp===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pORGZ->errMsg));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }elseif(count($orgTmp)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You need yo have at least one organization created before you can create a user"));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        foreach($orgTmp as $value){
            $arrOrgz[$value["id"]]=$value["name"];
        }
    }

    $idOrgSel=getParameter("organization");

    if($userLevel1!="superadmin"){
        $idOrgSel=$idOrganization;
    }else{
        if(!isset($idOrgSel)){
            $idOrgSel=0;
        }
    }

    if($idOrgSel==0){
        $arrGrupos=array();
    }else{
        $temp = $pACL->getGroups(null,$idOrgSel);
        if($temp===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pACL->errMsg));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
        foreach($temp as $value){
            $arrGrupos[$value[0]]=$value[1];
        }
    }

    $arrFormOrgz = createFieldForm($arrGrupos,$arrOrgz);
    $oForm = new paloForm($smarty,$arrFormOrgz);

    if(!$oForm->validateForm($_POST)){
        // Validation basic, not empty and VALIDATION_TYPE
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr("The following fields contain errors").":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "{$k} [{$v['mensaje']}], ";
        }
        $smarty->assign("mb_message", $strErrorMsg);
        return viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $password1=getParameter("password1");
        $password2=getParameter("password2");
        $organization=getParameter("organization");
        if($password1==""){
            $error=_tr("Password can not be empty");
        }else if($password1!=$password2){
            $error=_tr("Passwords don't match");
        }else{
            if(!isStrongPassword($password1)){
                $error=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
                $continuar=false;
            }
            
            if($userLevel1=="superadmin"){
                if($organization==0 || $organization==1){
                    $error=_tr("You must select a organization");
                    $continuar=false;
                }else
                    $idOrganization=$organization;
            }
            if($continuar){
                $renameFile="";
                //esta seccion es solo si el usuario quiere subir una imagen a su cuenta
                $pictureUpload = $_FILES['picture']['name'];
                if(isset($pictureUpload) && $pictureUpload != ""){
                    $idImg=date("Ymdhis");
                    if(!uploadImage($idImg,$renameFile,$errorImg)){
                        $smarty->assign("mb_title", _tr("ERROR"));
                        $smarty->assign("mb_message",$errorImg);
                        return viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
                    }
                }

                $username=getParameter("username");
                $name=getParameter("name");
                $idGrupo=getParameter("group");
                $extension=getParameter("extension");
                $fax_extension=getParameter("fax_extension");
                $md5password=md5($password1);
                $countryCode=getParameter("country_code");
                $areaCode=getParameter("area_code");
                $clidNumber=getParameter("clid_number");
                $cldiName=getParameter("clid_name");
                $quota=getParameter("quota");
                $exito=$pORGZ->createUserOrganization($idOrganization, $username, $name, $md5password, $password1, $idGrupo, $extension, $fax_extension,$countryCode, $areaCode, $clidNumber, $cldiName, $quota, $lastid);
                $error=$pORGZ->errMsg;
            }
        }
    }

    if($exito){
        //el archivo que se subio anteriormente cambia de nomber y ahora usa es idUser.ext
        //tambien ahi que actualizar la infomacion de la imagen en la base de datos
        if($renameFile!=""){
            $filename=basename($renameFile);
            $ext=explode(".",$filename);
            $picture=$lastid.".".$ext[count($ext)-1];
            if($pACL->setUserPicture($lastid,$picture))
                rename($renameFile,"/var/www/elastixdir/users_images/$picture");
            else
                unlink($renameFile);
        }
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("User has been created successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pDBMySQL=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
        $pAstConf=new paloSantoASteriskConfig($pDBMySQL,$pDB);
        $orgTmp2=$pORGZ->getOrganization("","","id",$idOrganization);
        $pAstConf->setReloadDialplan($orgTmp2[0]["domain"],true);
        $content = reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        if($renameFile!=""){
            unlink($renameFile);
        }
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    return $content;
}


function saveEditUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $exito = false;
    $idUser=getParameter("id");
    $errorImg="";
    $renameFile="";
    $reAsterisk=false;


    //un usuario que no es administrador no puede editar la informacion de otro usuario
    if($userLevel1=="other"){
        $id=$pACL->getIdUser($userAccount);
        if($idUser!=$id){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("You are not authorized to edit that information"));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
    }

    //obtenemos la informacion del usuario por el id dado, sino existe el usuario mostramos un mensaje de error
    if(!isset($idUser)){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid User"));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        if($userLevel1=="superadmin"){
            $arrUsers = $pACL->getUsers($idUser);
        }elseif($userLevel1=="admin"){
            $arrUsers = $pACL->getUsers($idUser, $idOrganization);
        }else{
            $idUser=$pACL->getIdUser($userAccount);
            $arrUsers = $pACL->getUsers($idUser, $idOrganization);
        }
    }

    if($arrUsers===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pACL->errMsg));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else if(count($arrUsers)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("User doesn't exist"));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $idOrgz=$arrUsers[0][4]; //una vez creado un usuario este no se puede cambiar de organizacion
        $arrOrgz=array();
        $temp = $pACL->getGroupsPaging(null,null,$idOrgz);
        if($temp===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pACL->errMsg));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
        foreach($temp as $value){
            $arrGrupos[$value[0]]=$value[1];
        }

        $arrFormOrgz = createFieldForm($arrGrupos,$arrOrgz);
        $oForm = new paloForm($smarty,$arrFormOrgz);

        if(!$oForm->validateForm($_POST)){
            // Validation basic, not empty and VALIDATION_TYPE
            $smarty->assign("mb_title", _tr("Validation Error"));
            $arrErrores = $oForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr("The following fields contain errors").":</b><br/>";
            if(is_array($arrErrores) && count($arrErrores) > 0){
                foreach($arrErrores as $k=>$v)
                    $strErrorMsg .= "{$k} [{$v['mensaje']}], ";
            }
            $smarty->assign("mb_message", $strErrorMsg);
            return viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else{
            $password1=getParameter("password1");
            $password2=getParameter("password2");
            $quota=getParameter("email_quota");
            $countryCode=getParameter("country_code");
            $areaCode=getParameter("area_code");
            $idGrupo=getParameter("group");
            $extension=getParameter("extension");
            $fax_extension=getParameter("fax_extension");
            $name=getParameter("name");
            $md5password=md5($password1);
            $clidNumber=getParameter("clid_number");
            $cldiName=getParameter("clid_name");
            if($userLevel1=="other"){
                $extension=$arrUsers[0][5];
                $fax_extension=$arrUsers[0][6];
                $quota=$pACL->getUserProp($idUser,"email_quota");
                $idGrupo=$arrUsers[0][7];
            }

            if($pACL->isUserSuperAdmin($arrUsers[0][1])){
                $idGrupo=$arrUsers[0][7];
                $email_contact=getParameter("email_contact");
                $exito=$pORGZ->updateUserSuperAdmin($idUser, $name, $md5password, $password1, $email_contact, $userLevel1);
                $error=$pORGZ->errMsg;
            }else{
                if($password1!=$password2){
                    $error=_tr("Passwords don't match");
                }elseif($password1!="" && !isStrongPassword($password1)){
                    $error=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
                }elseif(!isset($quota) || $quota==""){
                    $error=_tr("Qouta must not be empty");
                }elseif(!isset($countryCode) || $countryCode==""){
                    $error=_tr("Country Code must not be empty");
                }elseif(!isset($areaCode) || $areaCode==""){
                    $error=_tr("Area Code must not be empty");
                }elseif(!isset($clidNumber) || $clidNumber==""){
                    $error=_tr("Caller Id Number must not be empty");
                }elseif(!isset($cldiName) || $cldiName==""){
                    $error=_tr("Caller Id Name must not be empty");
                }else{
                    //esta seccion es solo si el usuario quiere subir una imagen a su cuenta
                    $pictureUpload = $_FILES['picture']['name'];
                    if(isset($pictureUpload) && $pictureUpload != ""){
                        $idImg=date("Ymdhis");
                        if(!uploadImage($idImg,$renameFile,$errorImg)){
                            $smarty->assign("mb_title", _tr("ERROR"));
                            $smarty->assign("mb_message",$errorImg);
                            return viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
                        }
                    }
                    
                    $exito=$pORGZ->updateUserOrganization($idUser, $name, $md5password, $password1, $extension, $fax_extension,$countryCode, $areaCode, $clidNumber, $cldiName, $idGrupo, $quota, $userLevel1, $reAsterisk);
                    $error=$pORGZ->errMsg;
                }
            }
        }
    }

    if($exito){
        //el archivo que se subio anteriormente cambia de nomber y ahora usa es idUser.ext
        //tambien ahi que actualizar la infomacion de la imagen en la base de datos
        if($renameFile!=""){
            $filename=basename($renameFile);
            $ext=explode(".",$filename);
            $picture=$idUser.".".$ext[count($ext)-1];
            if($pACL->setUserPicture($idUser,$picture))
                rename($renameFile,"/var/www/elastixdir/users_images/$picture");
            else
                unlink($renameFile);
        }
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("User has been edited successfully"));
        if($reAsterisk){
            //mostramos el mensaje para crear los archivos de ocnfiguracion
            $pDBMySQL=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
            $pAstConf=new paloSantoASteriskConfig($pDBMySQL,$pDB);
            $orgTmp2=$pORGZ->getOrganization("","","id",$pACL->getIdOrganizationUser($idUser));
            $pAstConf->setReloadDialplan($orgTmp2[0]["domain"],true);
        }
        $content = reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        if($renameFile!=""){
            unlink($renameFile);
        }
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    return $content;
}

function deleteUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $idUser=getParameter("id");
    if($userLevel1=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $idOrgReload=$pACL->getIdOrganizationUser($idUser);

    if($userLevel1=="superadmin"){
        if($idUser==1){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("The admin user cannot be deleted because is the default Elastix administrator. You can delete any other user."));
            return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else{
            $exito=$pORGZ->deleteUserOrganization($idUser);
        }
    }else if($userLevel1=="admin"){
        if($pACL->userBellowOrganization($idUser,$idOrganization)){
            $exito=$pORGZ->deleteUserOrganization($idUser);
        }else{
            $pORGZ->errMsg=$pACL->errMsg;
        }
    }

    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("The user was deleted successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pDBMySQL=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
        $pAstConf=new paloSantoASteriskConfig($pDBMySQL,$pDB);
        $orgTmp2=$pORGZ->getOrganization("","","id",$idOrgReload);
        $pAstConf->setReloadDialplan($orgTmp2[0]["domain"],true);
        $content = reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pORGZ->errMsg));
        $content = reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    return $content;
}

function getGroups(&$pDB){
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $jsonObject = new PaloSantoJSON();
    $idOrgSel = getParameter("idOrganization");
    $arrGrupos = array();
    if($idOrgSel==0){
        $arrGrupos=array();
    }else{
        $arrGrupos[0]=array("country_code",$pORGZ->getOrganizationProp($idOrgSel,"country_code"));
        $arrGrupos[1]=array("area_code",$pORGZ->getOrganizationProp($idOrgSel,"area_code"));
        $arrGrupos[2]=array("email_quota",$pORGZ->getOrganizationProp($idOrgSel,"email_quota"));
        $temp = $pACL->getGroupsPaging(null,null,$idOrgSel);
        if($temp===false){
            $jsonObject->set_error(_tr($pACL->errMsg));
        }else{
            $i=3;
            foreach($temp as $value){
                $arrGrupos[$i]=array($value[0],$value[1]);
                $i++;
            }
        }
    }
    $jsonObject->set_message($arrGrupos);
    return $jsonObject->createJSON();
}

function getImage($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
    $pACL       = new paloACL($pDB);
    $ruta_destino = "/var/www/elastixdir/users_images";
    $imgDefault = $_SERVER['DOCUMENT_ROOT']."/modules/$module_name/images/Icon-user.png";
    $id_user="";

    if($userLevel1=="superadmin"){
        $id_user = getParameter("ID");
    }else if($userLevel1=="admin"){
        $idTemp = getParameter("ID");
        if($pACL->userBellowOrganization($idTemp,$idOrganization)){
            $id_user=$idTemp;
        }
    }else{
        $id_user=$pACL->getIdUser($userAccount);
    }

    $picture = $pACL->getUserPicture($id_user);
    $image = $ruta_destino."/".$picture[0];
    $arrIm = explode(".",$picture[0]);
    $typeImage = $arrIm[count($arrIm)-1];

    // Creamos la imagen a partir de un fichero existente
    if(is_file($image) && $picture!==false){
        if(strtolower($typeImage) == "png"){
            Header("Content-type: image/png");
            $im = imagecreatefromPng($image);
            ImagePng($im); // Mostramos la imagen
            ImageDestroy($im); // Liberamos la memoria que ocupaba la imagen
        }else{
            Header("Content-type: image/jpeg");
            $im = imagecreatefromJpeg($image);
            ImageJpeg($im); // Mostramos la imagen
            ImageDestroy($im); // Liberamos la memoria que ocupaba la imagen
        }
    }else{
        Header("Content-type: image/png");
        $im = file_get_contents($imgDefault);
        echo $im;
    }
    return;
}

function redimensionarImagen($ruta1,$ruta2,$ancho,$alto){

    # se obtene la dimension y tipo de imagen
    $datos=getimagesize($ruta1);

    if(!$datos)
        return false;

    $ancho_orig = $datos[0]; # Anchura de la imagen original
    $alto_orig = $datos[1];    # Altura de la imagen original
    $tipo = $datos[2];
    $img = "";
    if ($tipo==1){ # GIF
        if (function_exists("imagecreatefromgif"))
            $img = imagecreatefromgif($ruta1);
        else
            return false;
    }
    else if ($tipo==2){ # JPG
        if (function_exists("imagecreatefromjpeg"))
            $img = imagecreatefromjpeg($ruta1);
        else
            return false;
    }
    else if ($tipo==3){ # PNG
        if (function_exists("imagecreatefrompng"))
            $img = imagecreatefrompng($ruta1);
        else
            return false;
    }

    $anchoTmp = imagesx($img);
    $altoTmp = imagesy($img);
    if(($ancho > $anchoTmp || $alto > $altoTmp)){
        ImageDestroy($img);
        return true;
    }

    # Se calculan las nuevas dimensiones de la imagen
    if ($ancho_orig>$alto_orig){
        $ancho_dest=$ancho;
        $alto_dest=($ancho_dest/$ancho_orig)*$alto_orig;
    }else{
        $alto_dest=$alto;
        $ancho_dest=($alto_dest/$alto_orig)*$ancho_orig;
    }

    // imagecreatetruecolor, solo estan en G.D. 2.0.1 con PHP 4.0.6+
    $img2=@imagecreatetruecolor($ancho_dest,$alto_dest) or $img2=imagecreate($ancho_dest,$alto_dest);

    // Redimensionar
    // imagecopyresampled, solo estan en G.D. 2.0.1 con PHP 4.0.6+
    @imagecopyresampled($img2,$img,0,0,0,0,$ancho_dest,$alto_dest,$ancho_orig,$alto_orig) or imagecopyresized($img2,$img,0,0,0,0,$ancho_dest,$alto_dest,$ancho_orig,$alto_orig);

    // Crear fichero nuevo, según extensión.
    if ($tipo==1) // GIF
    if (function_exists("imagegif"))
        imagegif($img2, $ruta2);
    else
        return false;

    if ($tipo==2) // JPG
    if (function_exists("imagejpeg"))
        imagejpeg($img2, $ruta2);
    else
        return false;

    if ($tipo==3)  // PNG
    if (function_exists("imagepng"))
        imagepng($img2, $ruta2);
    else
        return false;

    return true;
}

function uploadImage($idImg,&$fileUpload,&$error){
    $pictureUpload = $_FILES['picture']['name'];
    $file_upload = "";
    $ruta_destino = "/var/www/elastixdir/users_images";
    $Exito=false;

    //valido el tipo de archivo
    // \w cualquier caracter, letra o guion bajo
    // \s cualquier espacio en blanco
    if (!preg_match("/^(\w|-|\.|\(|\)|\s)+\.(png|PNG|JPG|jpg|JPEG|jpeg)$/",$pictureUpload)) {
        $error=_tr("Invalid file extension.- It must be png or jpg or jpeg");
    }elseif(preg_match("/(\.php)/",$pictureUpload)){
        $error=_tr("Possible file upload attack.");
    }else{
        if(is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $file_upload = basename($_FILES['picture']['tmp_name']); // verificando que solo tenga la ruta al archivo
            $file_name = basename("/tmp/".$_FILES['picture']['name']);
            $ruta_archivo = "/tmp/$file_upload";
            $arrIm = explode(".",$pictureUpload);
            $renameFile = "$ruta_destino/$idImg.".$arrIm[count($arrIm)-1];
            $file_upload = $idImg.".".$arrIm[count($arrIm)-1];
            $filesize = $_FILES['picture']['size'];
            $filetype = $_FILES['picture']['type'];

            $sizeImgUp=getimagesize($ruta_archivo);
            if(!$sizeImgUp){
                $error=_tr("Possible file upload attack. Filename")." : ". $pictureUpload;
            }
            //realizar acciones
            if(!rename($ruta_archivo, $renameFile)){
                $error=_("Error to Upload")." : ". $pictureUpload;
            }else{ //redimensiono la imagen
                $ancho = 240;
                $alto = 200;
                if(is_file($renameFile)){
                    if(!redimensionarImagen($renameFile,$renameFile,$ancho,$alto)){
                        $error=_tr("Possible file upload attack. Filename")." : ". $pictureUpload;
                    }else
                        $Exito=true;
                }
            }
        }else {
            $error=_tr("Possible file upload attack. Filename")." : ". $pictureUpload;
        }
    }
    if($Exito){
        $fileUpload=$renameFile;
    }else{
        $fileUpload="";
    }
    return $Exito;
}

function createFieldForm($arrGrupos,$arrOrgz){
    $arrFormElements = array("name" => array("LABEL"                  => _tr('Name').'(Ex. John Doe)',
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "username"       => array("LABEL"                => _tr("Username"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "EDITABLE"               => "no"),
                                "password1"   => array("LABEL"                  => _tr("Password"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                "password2"   => array("LABEL"                  => _tr("Retype password"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "PASSWORD",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                "organization"       => array("LABEL"           => _tr("Organization"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOrgz,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "ONCHANGE"	       => "select_organization();"),
                                "group"       => array("LABEL"                  => _tr("Group"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrGrupos,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "extension"   => array("LABEL"                   => _tr("Extension"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "fax_extension"   => array("LABEL"               => _tr("Fax Extension"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "country_code"   => array("LABEL"               => _tr("Country Code"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "area_code"   => array("LABEL"               => _tr("Area Code"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "clid_name"   => array("LABEL"               => _tr("Cid Name"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "clid_number" => array("LABEL"               => _tr("Cid Number"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "email_quota" => array("LABEL"               => _tr("Email Quota")." (MB)",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "email_contact"   => array( "LABEL"                  => _tr("Email Contact"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "email",
                                                    "VALIDATION_EXTRA_PARAM" => ""
                                                    ),
                            "picture"  	 => array("LABEL"                  => _tr("Picture"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "FILE",
                                                    "INPUT_EXTRA_PARAM"      => array("id" => "picture"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
    );
    return $arrFormElements;
}

function createFieldFilter($arrOrgz){
    $arrFields = array(
        "idOrganization"  => array("LABEL"                  => _tr("Organization"),
                        "REQUIRED"               => "no",
                        "INPUT_TYPE"             => "SELECT",
                        "INPUT_EXTRA_PARAM"      => $arrOrgz,
                        "VALIDATION_TYPE"        => "numeric",
                        "VALIDATION_EXTRA_PARAM" => "",
                        "ONCHANGE"	       => "javascript:submit();"),
        );
    return $arrFields;
}


function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
    $pACL = new paloACL($pDB);
    $showMsg=false;
    $continue=false;

    if($userLevel1=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
    }

    if($userLevel1=="superadmin"){
        $idOrganization = getParameter("organization_id");
    }

    if($idOrganization==1){
        return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $query="select domain from organization where id=?";
    $result=$pACL->_DB->getFirstRowQuery($query, false, array($idOrganization));
    if($result===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Asterisk can't be reloaded. ")._tr($pACL->_DB->errMsg));
        $showMsg=true;
    }elseif(count($result)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Asterisk can't be reloaded. "));
        $showMsg=true;
    }else{
        $domain=$result[0];
        $continue=true;
    }

    if($continue){
        $pDBMySQL=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
        $pAstConf=new paloSantoASteriskConfig($pDBMySQL,$pACL->_DB);
        if($pAstConf->generateDialplan($domain)===false){
            $pAstConf->setReloadDialplan($domain,true);
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Asterisk can't be reloaded. ").$pAstConf->errMsg);
            $showMsg=true;
        }else{
            $pAstConf->setReloadDialplan($domain);
            $smarty->assign("mb_title", _tr("MESSAGE"));
            $smarty->assign("mb_message",_tr("Asterisk was reloaded correctly. "));
        }
    }

    return reportUser($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getAction(){
    if(getParameter("create_user"))
        return "new_user";
    else if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("edit"))
        return "view_edit";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view";
    else if(getParameter("action")=="view_edit")
        return "view_edit";
    else if(getParameter("action")=="get_groups")
        return "getGroups";
    else if(getParameter("action")=="getImage")
        return "getImage";
    else if(getParameter("action")=="reloadAsterisk")
        return "reloadAasterisk";
    else
        return "report"; //cancel
}
?>
