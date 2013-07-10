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
include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    global $arrConf;
    //include module files
    include_once "libs/paloSantoOrganization.class.php";
    
    //folder path for custom templates
    $local_templates_dir=getWebDirModule($module_name);

    //conexion resource
    $pDB = new paloDB($arrConf['elastix_dsn']["elastix"]);

    //comprobacion de la credencial del usuario, el usuario superadmin es el unica capaz de dar 
    //y eliminar permisos de recursos a las organizaciones
    $arrCredentiasls=getUserCredentials();
    $userLevel1=$arrCredentiasls["userlevel"];
    $userAccount=$arrCredentiasls["userAccount"];
    $idOrganization=$arrCredentiasls["id_organization"];

    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "apply":
            $content = applyOrgPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        case "getSelected":
            $content = getSelected($pDB, $userLevel1);
            break;
        default:
            $content = reportOrgPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
    }
    return $content;
}

function applyOrgPermission($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization,$lang,$arrLang)
{
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $arrGroups=array();
    $arrOrgz=array();
    $idOrgFil=getParameter("idOrganization");
    $filter_resource = getParameter("resource_apply");
    $error=false;

    if($userLevel1!="superadmin"){
        $error=true;
        $msg_error=_tr("You are not authorized to perform this action");
    }

    $orgTmp=$pORGZ->getOrganizationById($idOrgFil);
    //valido exista una organizacion con dicho id
    if($orgTmp===false){
        $error=true;
        $msg_error=_tr($pORGZ->errMsg);
    }elseif(count($orgTmp)<=0){
        $error=true;
        $msg_error=_tr("Organization doesn't exist");
    }

    if($idOrgFil==1){
        $error=true;
        $msg_error=_tr("Invalid Organization");
    }

    //obtenemos las traducciones del parametro filtrado
    if($lang != "en"){
        $filter_value = strtolower(trim($filter_resource));
        foreach($arrLang as $key=>$value){
            $langValue    = strtolower(trim($value));
            if($filter_value!=""){
                if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
                    if(strpos($langValue, $filter_value) !== FALSE)
                        $parameter_to_find[] = $key;
            }
        }
    }

    if(isset($filter_resource)){
        $parameter_to_find[] = $filter_resource;
    }else{
        $parameter_to_find=null;
    }

    $pACL->_DB->beginTransaction();
    if(!$error){
        $oGrid  = new paloSantoGrid($smarty);
        $total=$pACL->getNumResources($parameter_to_find);
        $limit=25;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);
        $offset = $oGrid->calculateOffset();

        $tmpResource=$pACL->getListResources($limit, $offset,$parameter_to_find);//todos los recursos
        $tmpResourceOrg=$pACL->getResourcesByOrg($idOrgFil,$parameter_to_find);//los recuros a los que tiene permiso actualmente la organizacion
        $tmpGroup=$pACL->getGroups(null,$idOrgFil);

        if($tmpResourceOrg===false || $tmpGroup===false || $tmpResource===false){
            $error=true;
            $msg_error=$msg_error.""._tr($pACL->errMsg);
        }else{
            $arrPermissionAct=array();
            //los recursos seleccionados a los que se le va a dar acceso
            $selectedResource = isset($_POST['resource'])?array_keys($_POST['resource']):array();
            //validamos que los recursos seleccionados realmente existan

            foreach($tmpResourceOrg as $value){
                $arrPermissionAct[]=$value["id"];
            }

            $selectedResource[]='usermgr';
            $selectedResource[]='grouplist';
            $selectedResource[]='userlist';
            $selectedResource[]='group_permission';

            //hacemos una lista de los permisos que debemos eliminar y de los que debemos añadir
            $saveAcc=array_diff($selectedResource,$arrPermissionAct); //permisos que debemos añadir
            $delAcc=array_diff($arrPermissionAct,$selectedResource); //permisos que debemos eliminar
            $delAcc[]='organization_permission';
            $arrSave=array();
            $arrDelete=array();
            $arrSelected=array();
            //nos aseguramos que los recursos existan y cogemos los que se visualizan en el modulo al dar click en save
            foreach($tmpResource as $resource){
                if(in_array($resource["id"],$saveAcc))
                    $arrSave[]=$resource["id"];
                if(in_array($resource["id"],$delAcc))
                    $arrDelete[]=$resource["id"];
                if(in_array($resource["id"],$selectedResource))
                    $arrSelected[]=$resource["id"];
            }

            if(!$pACL->saveOrgPermission($idOrgFil, $arrSave) || !$pACL->deleteOrgPermissions($idOrgFil, $arrDelete)){
                $error=true;
                $msg_error=_tr($pACL->errMsg);
            }

            if(count($arrSelected)>0 && !$error){
                //obtengo los grupos de la organizacion
                foreach($tmpGroup as $value){
                    if($value["1"]=="administrator" || $value["1"]=="operator" || $value["1"]=="extension"){
                        $arrGrp[$value["0"]]=$value["1"];
                    }
                }
                //obtengo los permisos asignados a los grupos
                $selectedGroup = isset($_POST['group'])?($_POST['group']):array();
                foreach($selectedGroup as $idGroup => $value){
                    if(in_array($idGroup,array_keys($arrGrp))){ //valido que el grupo exista dentro de la organizacion
                        $arrGroupRes=array();$arrSave=array();$arrDelG=array();
                        foreach($pACL->loadGroupPermissions($idGroup) as $resource){
                            $arrGroupRes[]=$resource["id"]; //recursos a los que el grupo de la organizacion tenia acceso
                        }

                        $arrResourceSel=array_keys(isset($value)?$value:array());
                        if($arrGrp[$idGroup]=="administrator"){
                            $arrResourceSel[]='usermgr';
                            $arrResourceSel[]='grouplist';
                            $arrResourceSel[]='userlist';
                            $arrResourceSel[]='group_permission';
                        }

                        $savePersmissionG=array_diff($arrResourceSel,$arrGroupRes);
                        $delPermissionG=array_diff($arrGroupRes,$arrResourceSel);

                        $arrSaveG=array();
                        $arrDelG=array();
                        foreach($arrSelected as $value){
                            if(in_array($value,$savePersmissionG))
                                $arrSaveG[]=$value;
                            if(in_array($value,$delPermissionG))
                                $arrDelG[]=$value;
                        }
                        if(!$pACL->saveGroupPermissions($idGroup,$arrSaveG) || !$pACL->deleteGroupPermissions($idGroup, $arrDelG)){
                            $msg_error=_tr("Error saving groups permissions changes")._tr($pACL->errMsg);
                            $error=true;
                            break;
                        }
                    }
                }
            }
        }
    }
    //verificamos si todo salio bien
    if($error){
        $pACL->_DB->rollBAck();
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Error saving changes").$msg_error);
    }else{
        $pACL->_DB->commit();
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("Changes were applied successfully"));
    }

    unset($_SESSION['elastix_user_permission']);
    return reportOrgPermission($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
}


function reportOrgPermission($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1,  $idOrganization)
{
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $arrGroups=array();
    $arrOrgz=array();
    $filter_resource=getParameter("filter_resource");
    $idOrgFil=getParameter("idOrganization");

    //obtenemos la lista de las organizaciones creadas
    //y validamos que el usuairo haya seleccionado una organizacion
    if($userLevel1=="superadmin"){
        $orgTmp=$pORGZ->getOrganization(array());
        if($orgTmp===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pORGZ->errMsg));
        }elseif(count($orgTmp)==0){
            $smarty->assign("mb_title", _tr("MESSAGE"));
            $msg=(empty($idOrgFil))?_tr("You haven't created any organization"):_tr("Organization does not exist");
            $smarty->assign("mb_message",$msg);
        }else{
            $flag=false;
            foreach($orgTmp as $value){
                $arrOrgz[$value["id"]]=$value["name"];
                if($value["id"]==$idOrgFil)
                    $flag=true;
            }
            if(!$flag)
                $idOrgFil=$orgTmp[0]['id'];
        }
    }

    $filter_resource = htmlentities($filter_resource);
    
    //buscamos en el arreglo del lenguaje la traduccion del recurso en caso de que exista
    $parameter_to_find=null;
    if(isset($filter_resource)){
        if(trim($filter_resource)!=""){
            global $arrLang;
            $filter_value = strtolower(trim($filter_resource));
            $parameter_to_find[]=$filter_value; //parametro de busqueda sin traduccion
            foreach($arrLang as $key=>$value){
                $langValue=strtolower(trim($value));
                if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
                    if(strpos($langValue, $filter_value) !== FALSE)
                        $parameter_to_find[] = $key;
            }
        }
    }

    //obtenemos el numero de recursos disponibles del sistema
    $total=0;
    if(count($arrOrgz)>0){
        $total=$pACL->getNumResources($parameter_to_find,'yes','yes');
    }
    
    if($total==false && $pACL->errMsg!=""){
        $total=0;
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("An error has ocurred to retrieved resources data"));
    }

    $limit=20;
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;    
    $url["menu"]=$module_name;
    $url["filter_resource"]=$filter_resource;
    $url["idOrganization"]=$idOrgFil;
    
    $oGrid->setTitle(_tr("Organization Permission"));
    $oGrid->setURL($url);
    $oGrid->setWidth("99%");
    $oGrid->setStart(($total==0) ? 0 : $offset + 1);
    $oGrid->setEnd($end);
    $oGrid->setTotal($total);
    $arrColumn=array(_tr("Resource"),"<input type='checkbox' name='selectAll' id='selectAll' />"._tr('Permit Access'));
    
   /* //obtenemos los grupos comunes de todas las organizaciones
    $tempGrp=array();
    $arrGroup=$pACL->getGroups(null,$idOrgFil);
    if($arrGroup===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Error getting groups data"));
    }else{
        $arrColumn=array(_tr("Resource"),"<input type='checkbox' name='selectAll' id='selectAll' />"._tr('Permit Access'));
        foreach($arrGroup as $value){
            if($value["1"]=="administrator" || $value["1"]=='supervisor' || $value["1"]=="operator"){
                $arrColumn[]=_tr($value["1"]);
                $tempGrp[$value["0"]]=$value["1"];
            }
        }
    }*/
    $oGrid->setColumns($arrColumn);

    $arrData=array();
    if(count($arrOrgz)>0 && $total>0){
        //obtengo una lista con todos los recursos a los que una organizacion puede tener acceso
        // y que son de tipo administrativo. Los recursos que no son de tipo no administrativo 
        // siempre van a estar disponibles para los usuarios y no forman parte de los permisos 
        // por grupos
        $arrResource=$pACL->getListResources($limit,$offset,$parameter_to_find,'yes','yes');
        
        //lista de los recursos permitidos a la organizacion seleccionada organizacion
        $arrResourceOrg=$pACL->getResourcesByOrg($idOrgFil,$parameter_to_find,'yes','yes');
        if($arrResourceOrg===false || $arrResource===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Error getting resource")._tr($pACL->errMsg));
        }else{
            $temp=array();
            foreach($arrResourceOrg as $value){
                $temp[]=$value["id"];
            }
            if(is_array($arrResource) && count($arrResource) > 0){
                /*foreach($tempGrp as $key =>$value){
                    $arrGroupRes=$pACL->loadGroupPermissions($key);
                    $arr1=array();
                    foreach($arrGroupRes as $resGpr){
                        $arr1[]=$resGpr["id"];
                    }
                    $tmpGrp[$key]=$arr1;
                }*/

                foreach( $arrResource as $resource ){
                    $disabled = "";
                    if( ( $resource["id"] == 'usermgr'   || $resource["id"] == 'grouplist' || $resource["id"] == 'userlist'  ||
                        $resource["id"] == 'group_permission' || $resource["id"] == 'organization_permission')){
                        $disabled = "disabled='disabled'";
                    }

                    $checked0 = "";
                    if(in_array($resource["id"],$temp)){
                        $checked0 = "checked";
                    }

                    $arrTmp[0] = _tr($resource["description"]);
                    $arrTmp[1] = "<input type='checkbox' $disabled name='resource[".$resource["id"]."]' id='".$resource["id"]."' class='resource' $checked0>"." "._tr("Permit");

                    $i=2;
                    /*foreach($tmpGrp as $key => $value){
                        $disabled1="";$checked1="";
                        if(in_array($resource["id"],$value))
                            $checked1 = "checked";
                        if($checked0=="")
                            $disabled1 = "disabled='disabled'";
                        if( ($resource["id"] == 'usermgr'   || $resource["id"] == 'grouplist' || $resource["id"] == 'userlist'  ||
                            $resource["id"] == 'group_permission') && $tempGrp[$key]=="administrator"){
                                $disabled1 = "disabled='disabled'";
                        }
                        $arrTmp[$i++] = "<input type='checkbox' $disabled1 name='group[".$key."][".$resource["id"]."]' class='group' $checked1>";
                    }
                    reset($tmpGrp);*/
                    $arrData[] = $arrTmp;
                }
            }
        }
    }


    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("resource_apply", $filter_resource);

    if(count($arrOrgz)>0){
        if($userLevel1=="superadmin"){
            $oGrid->addSubmitAction("apply",_tr("Save"));
            $oGrid->addComboAction("idOrganization",_tr("Organization"),$arrOrgz,$idOrgFil,"report");
            $arrFormFilter = createFieldFilter();
            $oFilterForm = new paloForm($smarty, $arrFormFilter);
            $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
            $oGrid->addFilterControl(_tr("Filter applied ")._tr("Resource")." = $filter_resource", $_POST, array("filter_resource" =>""));
            $oGrid->showFilter(trim($htmlFilter));
        }
    }

    $contenidoModulo = $oGrid->fetchGrid(array(), $arrData);
    //end grid parameters
    return $contenidoModulo;
}


function getSelected(&$pDB, $userLevel1){
    $jsonObject = new PaloSantoJSON();
    $pACL = new paloACL($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $arrData = array();

    $idOrg=getParameter("idOrg");
    //validamos que la organization exista
    $orgTmp=$pORGZ->getOrganization(array("id"=>$idOrg));

    //valido que al menos exista una organizacion creada
    if($orgTmp===false){
        $jsonObject->set_error(_tr($pORGZ->errMsg));
    }elseif(count($orgTmp)<=0){
        $jsonObject->set_error(_tr("Organization doesn't exist"));
    }else{
        //obtengo los recursos asignados a la organizacion
        $arrResourceOrg=$pACL->getResourcesByOrg($idOrg);
        if($userLevel1!="superadmin"){
            $jsonObject->set_error("You are not authorized to perform this action. ");
        }elseif($arrResourceOrg===false){
            $jsonObject->set_error(_tr($pACL->errMsg));
        }else{
            foreach($arrResourceOrg as $resource){
                $arrData[]=$resource["id"];
            }
            $jsonObject->set_message($arrData);
        }
    }
    return $jsonObject->createJSON();
}

function getAction()
{
    if(getParameter("apply")) //Get parameter by POST (submit)
        return "apply";
	if(getParameter("action") == "getSelected")
		return "getSelected";
    else
        return "report";
}

function createFieldFilter()
{
    $arrFormElements = array(
            "filter_resource" => array( "LABEL"                  => _tr("Resource"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}
?>