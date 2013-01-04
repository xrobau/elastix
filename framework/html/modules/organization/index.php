<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-29                                               |
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
  $Id: index.php,v 1.1 2012-02-07 11:02:12 Rocio Mera rmera@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/did/libs/paloSantoDID.class.php";
    include_once "libs/paloSantoOrganization.class.php";
    include_once "libs/paloSantoACL.class.php";

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

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $pACL = new paloACL($pDB);   

    //comprobacion de la credencial del usuario, el usuario admin es el unica capaz de crear y borrar entidades
    //los usuarios de tipo administrador estan en la capacidad de editar sus propias entidades nada mas
    $userLevel1 = "";
    $userAccount = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";

	//verificar que tipo de usurio es: superadmin, admin o other
	$arrCredentiasls=getUserCredentials();
    $userLevel1=$arrCredentiasls["userlevel"];
    $userAccount=$arrCredentiasls["userAccount"];
    $idOrganization=$arrCredentiasls["id_organization"];

	if($userLevel1=="other"){
        header("Location: index.php?menu=system");
    }
		
    $action = getAction();
    $content = "";

    switch($action){
        case "new_organization":
            $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view":
            $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_edit":
            $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_new":
            $content = saveNewOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_edit":
            $content = saveEditOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "delete":
            $content = deleteOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_users":
            $content = viewUsersOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "get_country_code":
            $content=get_country_code();
            break;
        case "didAssign":
            $content=didAssign($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "saveDidAssign":
            $content=saveDidAssign($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        default: // report
            $content = reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;
}
            

function reportOrganization($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $pOrganization = new paloSantoOrganization($pDB);
    $pACL = new paloACL($pDB);
    $arrData = array();
    $arrayOrganization = false;

	$total=0;
    if($userLevel1=="superadmin"){
        $total=$pOrganization->getNumOrganization("","");
		if($total!=0)//la superorganizacion no se muestra
			$total=$total-1;
    }else if($userLevel1=="admin"){
        $total=$pOrganization->getNumOrganization("id",$idOrganization);
	}

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $url = "?menu=$module_name";

    $arrDatosGrid=array();
    if($userLevel1=="superadmin"){
        $arrayOrganization = $pOrganization->getOrganization($limit, $offset,"","");
    }else if($userLevel1=="admin")
        $arrayOrganization = $pOrganization->getOrganization($limit, $offset,"id",$idOrganization);

    $arrGrid = array("title"    => _tr('Organization List'),
                "url"      => $url,
                "width"    => "99%",
                "start"    => ($total==0) ? 0 : $offset + 1,
                "end"      => $end,
                "total"    => $total,
                );
    
    $arrColumns=array();
    if($userLevel1=="superadmin"){
        $arrColumns[]="";
    }
    
    $arrColumns[]=_tr("Organization");
    $arrColumns[]=_tr("Domain");
    $arrColumns[]=_tr("Number of Users");
    $arrColumns[]=_tr("Country Code")." / "._tr("Area Code");
    $arrColumns[]=_tr("Email Qouta")." (MB)";
                
    $oGrid->setColumns($arrColumns);

    if($arrayOrganization===FALSE)
    {
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message",_tr($pOrganization->errMsg));
    }else{
        if(is_array($arrayOrganization) && count($arrayOrganization)>0){
            foreach($arrayOrganization as $value)
            {
				if($value['id']!=1){
					$arrTmp = array();
					if($userLevel1=="superadmin"){
                        $arrTmp[] = "&nbsp;<a href='?menu=$module_name&action=assignDIDs&id=".$value['id']."'>"._tr("Assign DIDs")."</a>";
                    }
					$arrTmp[] = "&nbsp;<a href='?menu=$module_name&action=view&id=".$value['id']."'>".$value['name']."</a>";
					$arrTmp[] = ($value['domain']==false)?_tr("NONE"):$value['domain'];
					if($pOrganization->getNumUserByOrganization($value['id'])>0)
					{
						$arrTmp[] = "&nbsp;<a href='?menu=$module_name&action=viewUsers&id=".$value['id']."'>".$pOrganization->getNumUserByOrganization($value['id'])."</a>";
					}
					else
					{
						$arrTmp[] = 0;
					}
					$cCode=$pOrganization->getOrganizationProp($value['id'],"country_code");
					$aCode=$pOrganization->getOrganizationProp($value['id'],"area_code");
					$eQuota=$pOrganization->getOrganizationProp($value['id'],"email_quota");
					$tmpcode = ($cCode===false)?_tr("NONE"):$cCode;
					$tmpcode .=($aCode===false)?_tr("NONE"):" / ".$aCode;
					$arrTmp[] = $tmpcode;
					$arrTmp[] = ($eQuota===false)?_tr("NONE"):$eQuota;
					$arrDatosGrid[] = $arrTmp;
				}
            }
        }
    }

	//mensaje que aparece la primera vez que el usuario entra al modulo organization
	if($total==0){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("HERE YOU CAN CREATE NEW ORGANIZATION. "));
	}

	if($userLevel1=="superadmin")
        $oGrid->addNew("new_organization",_tr("Create Organization"));

    $content = $oGrid->fetchGrid($arrGrid,$arrDatosGrid);
    return $content;
}


function viewFormOrganization($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $pOrganization = new paloSantoOrganization($pDB);
    $arrFormOrgz = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormOrgz);
    $arrFill = array();
    $dataOrgz = false;

	 //begin, Form data persistence to errors and other events.
    $arrFill = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");

    $smarty->assign("edit_entity",0);
    if($userLevel1!="superadmin" && $userLevel1!="admin"){
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else if($userLevel1!="superadmin" && ($id!=$idOrganization) ){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message", _tr("Invalid ID Organization"));
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $smarty->assign("ID", $id); //persistence id with input hidden in tpl

    if($userLevel1=="superadmin"){
        $smarty->assign("level_user","super_admin");}

    if($action=="view"){
        $oForm->setViewMode();
        $smarty->assign("edit_entity",1);
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
        $smarty->assign("edit_entity",1);
    }

    //end, Form data persistence to errors and other events.
    if(getParameter("new_organization")){
        $arrFill['quota'] = 30;
    }


	if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){ // the action is to view or view_edit.
		if($id=="1"){//no se puede editar ni observar la organizacion principal
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message", _tr("Invalid ID Organization"));
			return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		}

		if($userLevel1=="superadmin" || $userLevel1=="admin")
			$dataOrgz = $pOrganization->getOrganizationById($id);

		if(is_array($dataOrgz) & count($dataOrgz)>0){
			if(!getParameter("save_edit")){
				$arrFill['name'] = $dataOrgz['name'];
				$arrFill['country'] = $dataOrgz['country'];
				$arrFill['city'] = $dataOrgz['city'];
				$arrFill['address'] = $dataOrgz['address'];
				$arrFill['email_contact'] = $dataOrgz['email_contact'];
				if($id!=1){
					$arrFill['country_code'] = $pOrganization->getOrganizationProp($id ,"country_code");
					$arrFill['area_code'] = $pOrganization->getOrganizationProp($id ,"area_code");
					$arrFill['quota'] = $pOrganization->getOrganizationProp($id ,"email_quota");
					$arrFill['domain'] = $dataOrgz['domain'];
				}
			}
			if($id!=1){
				$smarty->assign("domain_name", $dataOrgz['domain']);
			}
		}else{
			$smarty->assign("mb_title", _tr("Error get Data"));
			$smarty->assign("mb_message", $pOrganization->errMsg);
			return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		}
	}

	$smarty->assign("APLICAR_CAMBIOS", _tr("Apply Changes"));
	$smarty->assign("SAVE", _tr("Save"));
	$smarty->assign("DELETE", _tr("Delete"));
	$smarty->assign("EDIT", _tr("Edit"));
	$smarty->assign("CANCEL", _tr("Cancel"));
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
	$smarty->assign("icon", "images/list.png");
	if($id=="1")
		$smarty->assign("isMainOrg",true);
	else
		$smarty->assign("isMainOrg",false);

	$htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Organization"), $arrFill);
	$content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}


function saveNewOrganization($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $pOrganization = new paloSantoOrganization($pDB);
    $arrFormOrgz = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormOrgz);
	$error="";

    if($userLevel1!="superadmin"){
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }


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
        $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    else{
        $name = trim(getParameter("name"));
        $domain = trim(getParameter("domain"));
        $country = trim(getParameter("country"));
        $state = trim(getParameter("city"));
        $address = trim(getParameter("address"));
        $country_code = trim(getParameter("country_code"));
        $area_code = trim(getParameter("area_code"));
        $quota = trim(getParameter("quota"));
		$email_contact = trim(getParameter("email_contact"));
        $exito=$pOrganization->createOrganization($name,$domain,$country,$state,$address,$country_code,$area_code,$quota,$email_contact, $error);
        if($country=="0" || !isset($country)){
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", _tr("You must select a country"));
            $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else{
            if($exito)
            {
                $smarty->assign("mb_title", _tr("Message"));
                $smarty->assign("mb_message", _tr("The organization was created successfully"));
                $content = reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            }else{
                $smarty->assign("mb_title", _tr("Error"));
                $smarty->assign("mb_message",_tr($error)._tr($pOrganization->errMsg));
                $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            }
        }
    }
    return $content;
}

function saveEditOrganization($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $pOrganization = new paloSantoOrganization($pDB);
    $arrFormOrgz = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormOrgz);

    if($userLevel1!="superadmin" && $userLevel1!="admin"){
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else if($userLevel1!="superadmin" && (getParameter("id")!=$idOrganization) ){
		$smarty->assign("mb_title", _tr("Error"));
		$smarty->assign("mb_message", _tr("Invalid ID organization"));
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

	$id = getParameter("id");
	if(!isset($id) || $id=="1"){
		$smarty->assign("mb_title", _tr("Error"));
		$smarty->assign("mb_message", _tr("Invalid ID organization"));
		return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

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
        $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    else{
        $name = trim(getParameter("name"));
        $country = trim(getParameter("country"));
        $city = trim(getParameter("city"));
        $address = trim(getParameter("address"));
        $country_code = trim(getParameter("country_code"));
        $area_code = trim(getParameter("area_code"));
        $quota = trim(getParameter("quota"));
		$email_contact = trim(getParameter("email_contact"));
        if($country=="0" || !isset($country)){
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", _tr("You must select a country"));
            $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }else{
			$exito=$pOrganization->setOrganization($id,$name,$country,$city,$address,$country_code,$area_code,$quota,$email_contact);
            if($exito)
            {
                $smarty->assign("mb_title", _tr("Message"));
                $smarty->assign("mb_message", _tr("The organization was edit successfully"));
                $content = reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            }else{
                $smarty->assign("mb_title", _tr("Error"));
                $smarty->assign("mb_message", _tr($pOrganization->errMsg));
                $content = viewFormOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            }
        }
    }
    return $content;
}

function viewUsersOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $pOrganization = new paloSantoOrganization($pDB);

    $arrData = array();

    $id=getParameter("id");

    if($userLevel1!="superadmin" && $userLevel1!="admin"){
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else if($userLevel1!="superadmin" && ($id!=$idOrganization)){
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $total=$pOrganization->getNumUserByOrganization($id);
    $arrOrgz=$pOrganization->getOrganizationById($id);
    if($arrOrgz==FALSE){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", _tr($pOrganization->errMsg));
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $domainOrgz=$arrOrgz['domain'];
    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $url = "?menu=$module_name&action=viewUsers&id=$id";

    $arrDatosGrid=array();
    $arrayUsrOrgz = $pOrganization->getUsersByOrganization($id);
    if($arrayUsrOrgz===FALSE)
    {
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message",_tr($pOrganization->errMsg));
    }

    foreach($arrayUsrOrgz as $value)
    {
        $arrTmp = array();
        $arrTmp[0] = $value['username'];
        $arrTmp[1] = $value['name'];
        $arrTmp[2] = $domainOrgz;
        if($value['extension']=="" || $value['extension']==NULL)
            $ext= "Don't extension associated";
        else{
			$ext=$value['extension'];
		}
        //obtener la extension de fax asociada con el usuario
        if($value['fax_extension']=="" || $value['fax_extension']==NULL)
            $ext_fax .= "Don't fax extension associated";
        else
			$ext_fax = $value['fax_extension'];
        $arrTmp[3] = $ext[1]." / ".$ext_fax[1];
        $arrDatosGrid[] = $arrTmp;
    }

    $arrGrid = array("title"    => _tr('Entity List'),
                    "url"      => $url,
                    "width"    => "99%",
                    "start"    => ($total==0) ? 0 : $offset + 1,
                    "end"      => $end,
                    "total"    => $total,
                    'columns'   =>  array(
                        array("name"      => _tr("User Name"),),
                        array("name"      => _tr("Name"),),
                        array("name"      => _tr("Organization Domain"),),
                        array("name"      => _tr("Extension / fax extension"),)
                    ),
                );

    $oGrid->customAction("?menu=$module_name",_tr("<< Come Back"),"",true);
    $content = $oGrid->fetchGrid($arrGrid,$arrDatosGrid);
    return $content;
}

function deleteOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pOrganization = new paloSantoOrganization($pDB);
    $action = getParameter("action");
    $id     = getParameter("id");
    $smarty->assign("ID", $id);
    if($userLevel1!="superadmin"){
        return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

	if($id==1){
		$smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", _tr("Main Organization can not be deleted"));
		return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

    $exito=$pOrganization->deleteOrganization($id);
    if($exito)
    {
        $smarty->assign("mb_title", _tr("Message"));
        $smarty->assign("mb_message", _tr("The organization was deleted successfully"));
    }else{
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", _tr($pOrganization->errMsg));
    }
    return reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function get_country_code(){
    $jsonObject = new PaloSantoJSON();
    $country=getParameter("country");
    $arrSettings=getCountrySettings($country);
    if($arrSettings==false){
        $jsonObject->set_message("");
    }else{
        $jsonObject->set_message($arrSettings["code"]);
    }
    return $jsonObject->createJSON();
}

function createFieldForm()
{
    $arrCountry = array(_tr("Select a country").' --');
    $arrCountry = array_merge($arrCountry,getCountry());

    $arrFields = array(
            "name"   => array(      "LABEL"                  => _tr("Organization"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:297px","maxlength" =>"100"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "domain"   => array(      "LABEL"                  => _tr("Domain Name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:290px","maxlength" =>"50"),
                                            "VALIDATION_TYPE"        => "domain",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
			"email_contact"   => array( "LABEL"                  => _tr("Email Contact"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:297px","maxlength" =>"100"),
                                            "VALIDATION_TYPE"        => "email",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "country"   => array(      "LABEL"                  => _tr("Country"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrCountry,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "ONCHANGE"         => "select_country();"
                                            ),
            "city"   => array(      "LABEL"                  => _tr("City"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:290px","maxlength" =>"100"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "address"   => array(      "LABEL"                  => _tr("Address"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:828px","maxlength" =>"1000"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
           "country_code" => array(     "LABEL"                  => _tr('Country Code'),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:297px","maxlength" =>"100"),
                                        "VALIDATION_TYPE"        => "numeric",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "area_code"   => array(     "LABEL"                  => _tr('Area Code'),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:290px","maxlength" =>"100"),
                                        "VALIDATION_TYPE"        => "numeric",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "quota"   => array(     "LABEL"                  => _tr('Default Email Quota (MB)'),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:290px","maxlength" =>"100"),
                                        "VALIDATION_TYPE"        => "numeric",
                                        "VALIDATION_EXTRA_PARAM" => ""),

            );
    return $arrFields;
}

function didAssign($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pORGZ = new paloSantoOrganization($pDB);
    $error = "";
    
    //conexion elxpbx
    $pDB2 = new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    $pDID=new paloDidPBX($pDB2);

    if($userLevel1!="superadmin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportDID($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $arrDID=array();
    $action = getParameter("action");
    
    $idOrg=getParameter("id");
    if($userLevel1!="superadmin"){
        $idOrg=$idOrganization;
    }
    
    if(!isset($idOrg)){
        $error=_tr("Invalid Organization");
    }else{
        $dataOrgz = $pORGZ->getOrganizationById($idOrg);
        if($dataOrgz==false){
            $error=_tr("Organization doesn't exist").$pORGZ->errMsg ;
        }else{
            $domain=$dataOrgz["domain"];
            $smarty->assign("DOMAIN",$domain);
            $data=$pDID->getDIDs($domain);
            if($data===false){
                $error=_tr($pDID->errMsg);
            }else{
                $arrDID=$_POST;
                if(getParameter("save_did")){
                    if(isset($arrDID["select_dids"]))
                        $smarty->assign("DIDS",$arrDID["select_dids"]);
                }else{
                    $select_did="";
                    foreach($data as $value){
                        $select_did .=$value["did"].",";
                    }
                    $smarty->assign("DIDS",$select_did);
                }
            }
        }
    }
    
    $DIDs=array("none"=>_tr("--Select one--"));
    $tmpDIDs=$pDID->getDIDFree();
    if($tmpDIDs!=false){
        foreach($tmpDIDs as $key => $value){
            $DIDs[$key]=$value;
        }
    }
    $arrForm = createDidForn($DIDs);
    $oForm = new paloForm($smarty,$arrForm);

    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("MODULE_NAME",$module_name);
    $smarty->assign("Organization",_tr("Organization"));
    $smarty->assign("ID", $idOrg);
    $smarty->assign("userLevel",$userLevel1);
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/organization_did.tpl","Organization DID", $arrDID);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveDidAssign($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){

    $error = "";    
    //conexion elxpbx
    $pDB2 = new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    $pDID=new paloDidPBX($pDB2);

    if($userLevel1!="superadmin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportDID($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    
    $idOrg=getParameter("id");
    $select_dids=getParameter("select_dids");
    
    $pDB->beginTransaction();
    $exito=$pDID->saveOrgDID($idOrg,$select_dids);
    if($exito)
        $pDB->commit();
    else
        $pDB->rollBack();
    $error .=$pDID->errMsg;
    
    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        if(writeDHADIDidFile($error)){
            $smarty->assign("mb_message",_tr("DID was assignment successfully"));
        }else{
            $smarty->assign("mb_message",_tr("DID was assignment successfully").$error);
        }
        $content = reportOrganization($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = didAssign($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    return $content;
}

function writeDHADIDidFile(&$error){
    $sComando = '/usr/bin/elastix-helper asteriskconfig createFileDahdiChannelAdd 2>&1';
    $output = $ret = NULL;
    exec($sComando, $output, $ret);
    if ($ret != 0) {
        $error = _tr("Error writing did file").implode('', $output);
        return FALSE;
    }
    
    return true;
}

function createDidForn($arrDID){
    $arrFormElements = array("did"   => array("LABEL"                => _tr("DIDs"),
                                                "REQUIRED"               => "yes",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrDID,
                                                "VALIDATION_TYPE"        => "numeric",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                        );
    return $arrFormElements;
}

function getAction()
{

    if(getParameter("new_organization"))
        return "new_organization";
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
    else if(getParameter("action")=="viewUsers")
        return "view_users";
    else if(getParameter("action")=="get_country_code")
        return "get_country_code";
    else if(getParameter("action")=="assignDIDs")
        return "didAssign";
    else if(getParameter("save_did"))
        return "saveDidAssign";
    else
        return "report"; //cancel
}
?>