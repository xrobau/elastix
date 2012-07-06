<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2                                                |
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
  $Id: index.php,v 1.1 2009-05-06 04:05:41 Jonathan Vega jvega112@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoMenu.class.php";
include_once "libs/paloSantoOrganization.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoGroupPermission.class.php";

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

     //conexion acl.db
    $pDB = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB);

	 //comprobacion de la credencial del usuario, el usuario superadmin es el unica capaz de crear
	 //y borrar usuarios de todas las organizaciones
     //los usuarios de tipo administrador estan en la capacidad crear usuarios solo de sus organizaciones
    $userLevel1 = "";
    $userAccount = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";

	//verificar que tipo de usurio es: superadmin, admin o other
	if($userAccount!=""){
		$idOrganization = $pACL->getIdOrganizationUserByName($userAccount);
		if($pACL->isUserSuperAdmin($userAccount)){
			$userLevel1 = "superadmin";
		}else{
			if($pACL->isUserAdministratorGroup($userAccount))
				$userLevel1 = "admin";
			else
				$userLevel1 = "other";
		}
	}else
		$idOrganization="Error";
    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "apply":
            $content = applyGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1, $idOrganization,$lang,$arrLang);
            break;
        default:
            $content = reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1, $idOrganization,$lang,$arrLang);
            break;
    }
    return $content;
}

function applyGroupPermission($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userAccount, $userLevel1, $idOrganization,$lang,$arrLang)
{
    $pACL = new paloACL($pDB);

    $filter_resource = getParameter("resource_apply");
    $limit = getParameter("limit_apply");
    $offset = getParameter("offset_apply");

	$idOrgFil=getParameter("idOrganization");
	$idGroup = getParameter("filter_group");

	if(!isset($idOrgFil) || $idOrgFil===""){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You need have one organization selected"));
		return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
	}

	if(!isset($idGroup) || $idGroup===""){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not set a group"));
		return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
	}

	if($userLevel1!="superadmin"){
		if($idOrgFil!=$idOrganization){
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr("You are not set a group"));
			return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
		}
	}

	//valido exista una organizacion con dicho id y que no sea la organizacion 1
	$orgTmp=$pORGZ->getOrganizationById($idOrgFil);
	if($orgTmp===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pORGZ->errMsg));
		return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
	}elseif(count($orgTmp)<=0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Organization doesn't exist"));
		return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
	}

	if($idOrgFil==1){
		$error=true;
		$msg_error=_tr("Invalid Organization");
	}

	//valido que el grupo pertenezca a la organizacion
	if($pACL->getGroups($idGroup,$idOrgFil)==false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Group"));
		return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
	}

	//obtenemos las traducciones del parametro filtrado
	if($lang != "en"){
		$filter_value = strtolower(trim($filter_resource));
		foreach($arrLang as $key=>$value){
			$langValue    = strtolower(trim($value));
			if($filter_value!=""){
				if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
					if(preg_match("/$filter_value/",$langValue))
						$parameter_to_find[] = $key;
			}
		}
	}

	if(isset($filter_resource)){
		$parameter_to_find[] = $filter_resource;
	}else{
		$parameter_to_find=null;
	}

    $arrResources = $pACL->getResourcesByOrg($idOrgFil,  $parameter_to_find);
	if($arrResources===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pACL->errMsg));
		return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
	}

	$arrResources=array_slice($arrResources,$offset,$limit);
    //****************************************************************************************************
    // ACTION -> access
    //****************************************************************************************************

    //permisos recursos seleccionados en el grid
    $selectedAccess = isset( $_POST['groupPermission'] ) ? array_keys($_POST['groupPermission']) : array();

    $isAdministrator = ($pACL->getGroupNameByid($idGroup) == "administrator") ? true :false;

    if( $isAdministrator ){
        $selectedAccess[] = "usermgr";
        $selectedAccess[] = "grouplist";
        $selectedAccess[] = "userlist";
        $selectedAccess[] = "group_permission";
    }

    $listaPermisos = OrderResourceGroupPermissions( $pACL->loadGroupPermissions($idGroup) );

    $listaPermisosNuevos = array_diff( $selectedAccess, $listaPermisos);
    $listaPermisosAusentes = array_diff( $listaPermisos, $selectedAccess);
    $listaPermisosNuevosGrupo = array();
    $listaPermisosAusentesGrupo = array();

    foreach($arrResources as $resource) {
        if( in_array( $resource["id"], $listaPermisosNuevos) )    $listaPermisosNuevosGrupo[]   = $resource["id"];
        if( in_array( $resource["id"], $listaPermisosAusentes) )  $listaPermisosAusentesGrupo[] = $resource["id"];
    }

	$pACL->_DB->beginTransaction();
    if( count($listaPermisosAusentesGrupo) > 0 ){
        $bExito = $pACL->deleteGroupPermissions($idGroup, $listaPermisosAusentesGrupo);
        if (!$bExito){
            $msgError = "ERROR";
			$pACL->_DB->rollBack();
		}
    }

    if( count($listaPermisosNuevosGrupo) > 0 ){
        $bExito = $pACL->saveGroupPermissions($idGroup, $listaPermisosNuevosGrupo);
        if (!$bExito){
            $msgError = "ERROR";
			$pACL->_DB->rollBack();
		}
    }

    if (!empty($msgError)){
		$smarty->assign("mb_title", $msgError);
		$smarty->assign("mb_message",_tr("A error has been ocurred. ").$pACL->errMsg);
	}else{
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Successfull change"));
		$pACL->_DB->commit();
	}

    //borra los menus q tiene de permisos que estan guardados en la session, el index.php principal (html) volvera a generar esta arreglo de permisos.
    unset($_SESSION['elastix_user_permission']);
    return reportGroupPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userAccount, $userLevel1,$idOrganization,$lang,$arrLang);
}

function reportGroupPermission($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userAccount, $userLevel1, $idOrganization,$lang="en",$arrLang)
{
	$pACL = new paloACL($pDB);
	$pORGZ = new paloSantoOrganization($pDB);
	$arrGroups=array();
	$arrOrgz=array();

	$idOrgFil=getParameter("idOrganization");

	if($userLevel1=="superadmin")
	{
		$orgTmp=$pORGZ->getOrganization("","","","");
	}else{
		$orgTmp=$pORGZ->getOrganization("","","id",$idOrganization);
	}

	//valido que al menos exista una organizacion creada
	if($orgTmp===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pORGZ->errMsg));
	}else{
		foreach($orgTmp as $value){
			if($value["id"]!="1")
				$arrOrgz[$value["id"]]=$value["name"];
		}
	}


	$arrIdOrg=array_keys($arrOrgz);
	if($userLevel1!="superadmin"){
		$idOrgFil=$idOrganization;
	}else{
		if(!isset($idOrgFil)){
			if($arrIdOrg!=false)
				$idOrgFil=$arrIdOrg[0];
			else
				$idOrgFil=0;
		}else{
			if(!in_array($idOrgFil,$arrIdOrg)){
				$idOrgFil=0;
				$smarty->assign("mb_title", _tr("EROOR"));
				$smarty->assign("mb_message",_tr("INVALID ORGANIZATION"));
			}
		}
	}

	if(count($arrOrgz)>0){
		$temp = $pACL->getGroupsPaging(null,null,$idOrgFil);
		if($temp===false){
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr($pACL->errMsg));
		}else{
			foreach($temp as $value){
				$arrGroups[$value[0]]=$value[1];
			}
		}
	}

    $filter_group = getParameter("filter_group");

	$defaulGroup=array_keys($arrGroups);
	if($defaulGroup==false)
		$defaulGroup=array("any");

	if(getParameter("show") || isset($_GET["nav"]) || getParameter("apply"))
	{
		$filter_group = isset( $filter_group ) ? $filter_group : $defaulGroup[0];
	}else{
		$filter_group=$defaulGroup[0];
	}

	//valido que el grupo pertenzca a la organizacion
	if($pACL->getGroups($filter_group,$idOrgFil)==false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Group"));
		$filter_group=$defaulGroup[0];
	}

	$filter_resource = getParameter("filter_resource");
	$filter_resource = htmlentities($filter_resource);

	if($lang != "en"){
		$filter_value = strtolower(trim($filter_resource));
		foreach($arrLang as $key=>$value){
			$langValue    = strtolower(trim($value));
			if($filter_value!=""){
				if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
					if(preg_match("/$filter_value/",$langValue))
						$parameter_to_find[] = $key;
			}
		}
	}

	if(isset($filter_resource)){
		$parameter_to_find[] = $filter_resource;
	}else{
		$parameter_to_find=null;
	}

	$arrResource=$pACL->getResourcesByOrg($idOrgFil, $parameter_to_find);
	$totalGroupPermission = count($arrResource);

	//begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $parameter_to_find = array();

    $limit  = 25;
    $total  = $totalGroupPermission;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

	$offset = $oGrid->calculateOffset();
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $arrData = null;
	if($filter_group!="any"){
		$arrResult = array_slice($arrResource,$offset,$limit);
		$idGroup = $filter_group;
		$arrPermisos = $pACL->loadGroupPermissions($idGroup);
		$arrPermisos = OrderGroupPermissions($arrPermisos);
	}else{
		$arrResult = array();
		$total=0;
		$idGroup = "any";
	}


	$url = "?menu=$module_name&idOrganization=$idOrgFil&filter_group=$filter_group&filter_resource=$filter_resource";
	
	$isAdministrator = ($pACL->getGroupNameByid($idGroup) == "administrator") ? true :false;

    if( is_array($arrResult) && $total > 0){
        foreach( $arrResult as $key => $resource ){
            $disabled = "";
            if( ( $resource["id"] == 'usermgr'   || $resource["id"] == 'grouplist' || $resource["id"] == 'userlist'  ||
                  $resource["id"] == 'group_permission') & $isAdministrator ){
                $disabled = "disabled='disabled'";
            }

            $checked0 = "";

            if(in_array($resource["id"],$arrPermisos)){
				$checked0 = "checked";
            }

            $arrTmp[0] = "<input type='checkbox' $disabled name='groupPermission[".$resource["id"]."][".$resource["id"]."]' $checked0>";
            $arrTmp[1] = _tr($resource["description"]);
            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array(   "title"    => _tr("Group Permission"),
                        "icon"     => "images/list.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => $url,
                        "columns"  => array(0 => array("name"      => _tr("Permit Access"),
                                                        "property1" => ""),
                                            1 => array("name"      => _tr("Resource"),
                                                        "property1" => ""),
                        ));

    //begin section filter
    $arrFormFilterGroupPermission = createFieldFilter($arrGroups, $arrOrgz);
    $oFilterForm = new paloForm($smarty, $arrFormFilterGroupPermission);

    $smarty->assign("SHOW", _tr("Show"));
	$smarty->assign("userLevel", $userLevel1);
	$smarty->assign("resource_apply", $filter_resource);
	$smarty->assign("limit_apply", htmlspecialchars($limit, ENT_COMPAT, 'UTF-8'));
	$smarty->assign("offset_apply", htmlspecialchars($offset, ENT_COMPAT, 'UTF-8'));

    $_POST["filter_group"] = $filter_group;
    $_POST["filter_resource"] = $filter_resource;
	$_POST["idOrganization"] = $idOrgFil;

	if(count($arrOrgz)>0){
		$nameGroup=isset($arrGroups[$filter_group])?$arrGroups[$filter_group]:"";
		$nameOrganization=isset($arrOrgz[$idOrgFil])?$arrOrgz[$idOrgFil]:"";
		$oGrid->addFilterControl(_tr("Filter applied ")._tr("Group")." = $nameGroup", $_POST, array("filter_group" => $defaulGroup[0]),true);
		$oGrid->addFilterControl(_tr("Filter applied ")._tr("Resource")." = $filter_resource", $_POST, array("filter_resource" =>""));
		if($userLevel1=="superadmin"){
			$oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization")." = ".$nameOrganization, $_POST, array("idOrganization" =>1),true);
		}
		$oGrid->addSubmitAction("apply",_tr("Save"));
		$htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
		$oGrid->showFilter(trim($htmlFilter));
	}else{
		$smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("You haven't created any organization"));
	}

    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
    //end grid parameters

    return $contenidoModulo;
}


function createFieldFilter($arrGrupos, $arrOrgz)
{
    $arrFormElements = array(
            "filter_group" => array(    "LABEL"                  => _tr("Group"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => $arrGrupos,
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "filter_resource" => array( "LABEL"                  => _tr("Resource"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
			"idOrganization" => array( "LABEL"                  => _tr("Organization"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => $arrOrgz,
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => "",
										"ONCHANGE"	       => "javascript:submit();"),
                    );
    return $arrFormElements;
}

function getAction()
{
    if(getParameter("apply")) //Get parameter by POST (submit)
        return "apply";
    //else if(getParameter("new"))
    //    return "new";
    //else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
    //    return "show";
    else
        return "report";
}

//**************************************************************************************************************************

//FUNCIONES DE AYUDA

function OrderResourceGroupPermissions( $arrPermisos )
{
    $arrResult = array();

    //Array ( [0] => Array ( [resource_name] => bib_consultaLibro )
    //        [1] => Array ( [resource_name] => build_module )
    //        [2] => Array ( [resource_name] => con )

    foreach( $arrPermisos as $num => $data )
        $arrResult[] = $data["id"];

    return $arrResult;
}

function OrderGroupPermissions($arrPermisos)
{
    $result = array();

    foreach($arrPermisos as $num => $data)
    {
        $result[]=$data["id"];
    }

    return $result;
}
?>