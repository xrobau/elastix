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
  $Id: index.php,v 1.1.1.1 2012/09/07 German Macas gmacas@palosanto.com Exp $ */
    include_once "libs/paloSantoJSON.class.php";
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoOrganization.class.php";
function _moduleContent(&$smarty, $module_name)
{
    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
   
	 //folder path for custom templates
     $local_templates_dir=getWebDirModule($module_name);

	 //comprobacion de la credencial del usuario, el usuario superadmin es el unica capaz de crear
	 //y borrar usuarios de todas las organizaciones
     //los usuarios de tipo administrador estan en la capacidad crear usuarios solo de sus organizaciones
    $arrCredentiasls=getUserCredentials();
	$userLevel1=$arrCredentiasls["userlevel"];
	$userAccount=$arrCredentiasls["userAccount"];
	$idOrganization=$arrCredentiasls["id_organization"];
    $orgDomain=$arrCredentiasls["domain"];
	
	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    
	$action = getAction();
    $content = "";
       
	switch($action){
        case "new_ivr":
            $content = viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view":
            $content = viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_edit":
            $content = viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_new":
            $content = saveNewIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_edit":
            $content = saveEditIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "delete":
            $content = deleteIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
                break;
        case "get_destination_category":
            $content = get_destination_category($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $orgDomain);
            break;
        default: // report
            $content = reportIVR($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;

}

function reportIVR($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);

	$domain=getParameter("organization");
	if($userLevel1=="superadmin"){
		if(!empty($domain)){
			$url = "?menu=$module_name&organization=$domain";
		}else{
			$domain = "all";
			$url = "?menu=$module_name";
		}
	}else{
		$arrOrg=$pORGZ->getOrganizationById($idOrganization);
		$domain=$arrOrg["domain"];
		$url = "?menu=$module_name";
	}
	
	$pIVR = new paloIvrPBX($pDB,$domain);
	if($userLevel1=="superadmin"){
        if(isset($domain) && $domain!="all"){
            $total=$pIVR->getTotalIvr();
        }else{
            $total=$pIVR->getTotalAllIvr();
        }
    }else{
        $total=$pIVR->getTotalIvr();
    }

	if($total===false){
        $error=$pIVR->errMsg;
        $total=0;
    }

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
	
	$arrGrid = array("title"    => _tr('Ivrs List'),
                "url"      => $url,
                "width"    => "99%",
                "start"    => ($total==0) ? 0 : $offset + 1,
                "end"      => $end,
                "total"    => $total,
                'columns'   =>  array(
                    array("name"      => _tr("Ivr Name"),),
                    array("name"      => _tr("Timeout"),),
                    array("name"      => _tr("Enable Call Extensions"),),
                    array("name"      => _tr("# Loops"),)
                    ),
                );

                
    $arrData = array();
    $arrIVR = array();
    if($total!=0){
        if($userLevel1=="superadmin"){
            if(isset($domain) && $domain!="all"){
                $arrIVR=$pIVR->getIvrs($limit,$offset);
            }else{
                $arrIVR=$pIVR->getAllIvrs(null,$limit,$offset);
            }
        }else{
            $arrIVR=$pIVR->getIvrs($limit,$offset);
        }
    }
    

	if($arrIVR===false){
		$error=_tr("Error getting ivr data.").$pIVR->errMsg;
	}else{
        foreach($arrIVR as $ivr) {
            if($userLevel1=="superadmin")
                $arrTmp[0] = $ivr["name"];
            else
                $arrTmp[0] = "&nbsp;<a href='?menu=ivr&action=view&id_ivr=".$ivr['id']."'>".$ivr["name"]."</a>";
            $arrTmp[1]=$ivr["timeout"];
            $arrTmp[2]=$ivr["directdial"];
            $arrTmp[3]=$ivr["loops"];
            $arrData[] = $arrTmp;
        }
	}

	if($pORGZ->getNumOrganization(array()) >= 1){
		if($userLevel1 == "admin")
			$oGrid->addNew("create_ivr",_tr("Create New IVR"));

		if($userLevel1 == "superadmin"){
			$arrOrgz=array("all"=>"all");
			foreach(($pORGZ->getOrganization()) as $value){
				if($value["id"]!=1)
					$arrOrgz[$value["domain"]]=$value["name"];
			}
			$arrFormElements = createFieldFilter($arrOrgz);
			$oFilterForm = new paloForm($smarty, $arrFormElements);
			$_POST["organization"]=$domain;
			$oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization")." = ".$arrOrgz[$domain], $_POST, array("organization" => "all"),true);
			$htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
			$oGrid->showFilter(trim($htmlFilter));
		}
	}else{
		$smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create new ivr"));
	}

	if($error!=""){
		$smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",$error);
	}
	$contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
	$mensaje=showMessageReload($module_name, $arrConf, $pDB, $userLevel1, $userAccount, $idOrganization);
	$contenidoModulo = $mensaje.$contenidoModulo;
    return $contenidoModulo;
}

function showMessageReload($module_name,$arrConf, &$pDB, $userLevel1, $userAccount, $idOrganization){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
	$params=array();
	$msgs="";

	$query = "SELECT domain, id from organization";
	//si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de mnarcada
	if($userLevel1!="superadmin"){
		$query .= " where id=?";
		$params[]=$idOrganization;
	}

	$mensaje=_tr("Click here to reload dialplan");
	$result=$pDB2->fetchTable($query,false,$params);
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

function viewFormIVR($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization, $arrDestine=array()){
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);

	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	$arrIvr=array();
    $action = getParameter("action");
    
    $arrOrgz=array(0=>"Select one Organization");
    /*if($userLevel1=="superadmin"){
        $orgTmp=$pORGZ->getOrganization("","","","");
        $smarty->assign("isSuperAdmin",TRUE);
    }else{*/
        $orgTmp=$pORGZ->getOrganization("","","id",$idOrganization);
        $smarty->assign("isSuperAdmin",FALSE);
    //}
    
    if($orgTmp===false){
        $error=_tr($pORGZ->errMsg);
    }elseif(count($orgTmp)==0){
        $error=_tr("Organization doesn't exist");
    }else{
        if($userLevel1=="superadmin" && count($orgTmp)<=1){
            $error=_tr("You need yo have at least one organization created before you can create a Ivr");
        }
        if($error!=""){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",$error);
            return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
        foreach($orgTmp as $value){
            if($value['id']!=1)
                $arrOrgz[$value["domain"]]=$value["name"];
        }
        $domain=$orgTmp[0]["domain"];
    }
	
	if($error!=""){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message",$error);
        return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	$idIVR=getParameter("id_ivr");

	if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
		if(!isset($idIVR)){
            $error=_tr("Invalid IVR");
		}else{
			/*if($userLevel1=="superadmin"){
                $pIVR=new paloIvrPBX($pDB,$domain);
                $result=$pIVR->getAllIvrs($idIVR);
                $arrTmp=$result
                if($arrTmp!=false){
                    $arrIVR=$result[0];
                    $domain=$arrTmp["organization_domain"];
                    $pIVR=new paloIvrPBX($pDB,$domain);
                }
            }else{*/
                if($userLevel1=="admin"){
                    $pIVR=new paloIvrPBX($pDB,$domain);
                    $arrIVR=$pIVR->getIvrById($idIVR);
                }
            //}
            if($arrIVR===false){
                $error=_tr($pIVR->errMsg);
            }else if(count($arrIVR)==0){
                $error=_tr("IVR doesn't exist");
            }else{
                $smarty->assign("IVR",$arrIVR["name"]);
                //para que se muestren los destinos
                $smarty->assign('j',0);
                $arrGoTo=$pIVR->getCategoryDefault($domain);
                $smarty->assign('arrGoTo',$arrGoTo);
                
                if($action=="view" || getParameter("edit") ){
                    $arrDestine = $pIVR->getArrDestine($idIVR);
                }
                
                $smarty->assign('items',$arrDestine);
                
                if(getParameter("save_edit")){
                    $arrIVR=$_POST;
                }
                
                $arrIVR["mesg_invalid"]=(is_null($arrIVR["mesg_invalid"]))?"none":$arrIVR["mesg_invalid"];
                $arrIVR["mesg_timeout"]=(is_null($arrIVR["mesg_timeout"]))?"none":$arrIVR["mesg_timeout"];
                $arrIVR["announcement"]=(is_null($arrIVR["announcement"]))?"none":$arrIVR["announcement"];
                
                if(isset($arrIVR["retvm"])){
                    if($arrIVR["retvm"]=="yes"){
                        $smarty->assign("CHECKED","checked");
                    }
                }
                if(getParameter("retvm")){
                    $smarty->assign("CHECKED","checked");
                }
            }
		}
	}else{
        $pIVR=new paloIvrPBX($pDB,$domain);
         //para que se muestren los destinos
        $smarty->assign('j',0);
        $arrGoTo=$pIVR->getCategoryDefault($domain);
        $smarty->assign('arrGoTo',$arrGoTo);
        $smarty->assign('items',$arrDestine);
        
        if(getParameter("create_ivr")){
            $arrIVR["timeout"]="10";
            $arrIVR["loops"]="2";
            $arrIVR["directdial"]="no";
        }else{
            $arrIVR=$_POST;
        }
	}
	
    $arrGoTo=$pIVR->getCategoryDefault($domain);
	$arrFormOrgz = createFieldForm($arrOrgz,$pIVR->getRecordingsSystem($domain),$arrGoTo);
    $oForm = new paloForm($smarty,$arrFormOrgz);

	if($action=="view"){
        $oForm->setViewMode();
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
            $oForm->setEditMode();
    }

	$smarty->assign("ERROREXT",_tr($pIVR->errMsg));
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("CANCEL", _tr("Cancel"));
	$smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
	$smarty->assign("SAVE", _tr("Save"));
	$smarty->assign("EDIT", _tr("Edit"));
	$smarty->assign("DELETE", _tr("Delete"));
	$smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
	$smarty->assign("MODULE_NAME",$module_name);
	$smarty->assign("id_ivr", $idIVR);
	$smarty->assign("userLevel",$userLevel1);
    $smarty->assign("RETIVR", _tr("Return to IVR"));
    $smarty->assign("DIGIT", _tr("Exten"));
    $smarty->assign("OPTION", _tr("Option"));
    $smarty->assign("DESTINE", _tr("Destine"));
    $smarty->assign("GENERAL", _tr("General"));

	$htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl","IVR", $arrIVR);
	$content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewIVR($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$exito=false;

	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
	
	if($pORGZ->getNumOrganization() <=1){
		$smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create ivr to this organization"));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}
	
	/*$domain=getParameter("domain_org");
	if($userLevel1=="superadmin"){
		if(empty($domain)){
			$domain=0;
		}
	}*/

	/*$arrOrgz=array(0=>"Select one Organization");
	if($userLevel1=="superadmin"){
		$orgTmp=$pORGZ->getOrganizationByDomain_Name($domain);
	}else{*/
		$orgTmp=$pORGZ->getOrganizationById($idOrganization);
	//}

	if($orgTmp===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pORGZ->errMsg));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}elseif(count($orgTmp)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Organization doesn't exist"));
		/*if($userLevel1=="superadmin")
			return viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		else*/
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		foreach($orgTmp as $value){
			$arrOrgz[$orgTmp["domain"]]=$orgTmp["name"];
			$domain=$orgTmp["domain"];
		}
	}

	$pIVR = new paloIvrPBX($pDB,$domain);
	
	$arrGoTo=$pIVR->getCategoryDefault($domain);
    $arrFormOrgz = createFieldForm($arrOrgz,$pIVR->getRecordingsSystem($domain),$arrGoTo);
    $oForm = new paloForm($smarty,$arrFormOrgz);

    //destinos del ivr
    $arrDestine = getParameter("arrDestine");
    $tmpstatus = explode(",",$arrDestine);
    $arrDestine = array_values(array_diff($tmpstatus, array('')));
    $tmp_destine=array();
    foreach($arrDestine as $destine){
        $ivr_ret = getParameter("ivrret".$destine);
        $option = getParameter("option".$destine);
        $goto = getParameter("goto".$destine);
        $destine = getParameter("destine".$destine);
        $val=($ivr_ret=="on")?"yes":"no";
        $tmp_destine[]=array("0",$option,$goto,$destine,$val);
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
        return viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$tmp_destine);
    }else{
        //seteamos un arreglo con los parametros configurados
        $arrProp=array();
        $arrProp["name"]=getParameter("name");
        $arrProp['announcement']=getParameter("announcement");
        $arrProp['retvm'] = (getParameter("retvm")) ? "yes" : "no";
        $arrProp['directdial'] = getParameter("directdial");
        $arrProp['mesg_timeout']=getParameter("mesg_timeout");
        $arrProp['mesg_invalid']=getParameter("mesg_invalid");
        $arrProp['loops']=getParameter("loops");
        $arrProp['timeout']=getParameter("timeout");

        $pDB->beginTransaction();
        $successIVR=$pIVR->insertIVRDB($arrProp,$tmp_destine);
        //$successDest=$pIVR->insertDestineDB($arrDestine,$arrProp["displayname"],$domain);
        if($successIVR)
            $pDB->commit();
        else
            $pDB->rollBack();
        $error .=$pIVR->errMsg;
	}

	if($successIVR){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("IVR has been created successfully."));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		$pAstConf->setReloadDialplan($domain,true);
		$content = reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$tmp_destine);
	}
	return $content;
}

function saveEditIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$successIVR=false;
	$idIVR=getParameter("id_ivr");

	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

	//obtenemos la informacion del usuario por el id dado, sino existe el ivr mostramos un mensaje de error
	if(!isset($idIVR)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid IVR"));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		/*if($userLevel1=="superadmin"){
            $pIVR = new paloIvrPBX($pDB,$domain);
            $result=$pIVR->getAllIvrs($idIVR);
            $arrTmp=$result
            if($arrTmp!=false){
                $arrIVR=$result[0];
                $domain=$arrTmp["organization_domain"];
                $pIVR = new paloIvrPBX($pDB,$domain);
            }
        }else{*/
            if($userLevel1=="admin"){
                $resultO=$pORGZ->getOrganizationById($idOrganization);
                $domain=$resultO["domain"];
                $pIVR = new paloIvrPBX($pDB,$domain);
                $arrIVR = $pIVR->getIVRById($idIVR, $domain);
            }
        //}
	}

	if($arrIVR===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pIVR->errMsg));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else if(count($arrIVR)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("IVR doesn't exist"));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
        //seteamos un arreglo con los parametros configurados
        //seteamos un arreglo con los parametros configurados
        $arrProp=array();
        $arrProp["name"]=getParameter("name");
        $arrProp['announcement']=getParameter("announcement");
        $arrProp['retvm'] = (getParameter("retvm")) ? "yes" : "no";
        $arrProp['directdial'] = getParameter("directdial");
        $arrProp['mesg_timeout']=getParameter("mesg_timeout");
        $arrProp['mesg_invalid']=getParameter("mesg_invalid");
        $arrProp['loops']=getParameter("loops");
        $arrProp['timeout']=getParameter("timeout");
        
        //destinos del ivr
        $arrDestine = getParameter("arrDestine");
        $tmpstatus = explode(",",$arrDestine);
        $arrDestine = array_values(array_diff($tmpstatus, array('')));
        $tmp_destine=array();
        foreach($arrDestine as $destine){
            $ivr_ret = getParameter("ivrret".$destine);
            $option = getParameter("option".$destine);
            $goto = getParameter("goto".$destine);
            $destine = getParameter("destine".$destine);
            $val=($ivr_ret=="on")?"yes":"no";
            $tmp_destine[]=array("0",$option,$goto,$destine,$val);
        }
        
        if($arrProp["name"]=="" || !isset($arrProp["name"])){
            $error="Field "._tr('Display Name')." can't be empty";
            $continue=false;
        }
        
        if(!preg_match("/^[0-9]+$/",$arrProp['timeout'])){
            $error=_tr("Invalid field Timeout");
            $continue=false;
        }
        
        if(!preg_match("/^[0-9]+$/",$arrProp['timeout'])){
            $error=_tr("Invalid field Repeat Loops");
            $continue=false;
        }
        
		if($continue){
			$pDB->beginTransaction();
			$successIVR=$pIVR->updateIVRDB($arrProp,$idIVR,$tmp_destine);
			if($successIVR)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pIVR->errMsg;
		}
	}

	$smarty->assign("id_ivr", $idIVR);

	if($successIVR){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("IVR has been edited successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		$pAstConf->setReloadDialplan($domain,true);
		$content = reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$tmp_destine);
	}
	return $content;
}

function deleteIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$successIVR=false;
	$idIVR=getParameter("id_ivr");


	if($userLevel1!="admin"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	//obtenemos la informacion del ivr por el id dado
	if(!isset($idIVR)){
        $error=_tr("Ivr doesn't exist");
    }else{
        /*if($userLevel1=="superadmin"){
            $pIVR = new paloIvrPBX($pDB,$domain);
            $result=$pIVR->getAllIvrs($idIVR);
            $arrTmp=$result
            if($arrTmp!=false){
                $arrIVR=$result[0];
                $domain=$arrTmp["organization_domain"];
                $pIVR = new paloIvrPBX($pDB,$domain);
            }
        }else{*/
            if($userLevel1=="admin"){
                $resultO=$pORGZ->getOrganizationById($idOrganization);
                $domain=$resultO["domain"];
                $pIVR = new paloIvrPBX($pDB,$domain);
                $arrIVR = $pIVR->getIVRById($idIVR, $domain);
            }
        //}
        if($arrIVR===false){
            $error=_tr("Error with database connection. ").$pIVR->errMsg;
        }elseif(count($arrIVR)==false){
            $error=_tr("Ivr doesn't exist");
        }else{
            $pDB->beginTransaction();
            $exito=$pIVR->deleteIVRDB($idIVR);
            if($exito){
                $pDB->commit();
            }else
                $pDB->rollBack();
            $error .=$pIVR->errMsg;
        }
    }

	if($successIVR){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("The IVR was deleted successfully"));
		//mostramos el mensaje para crear los archivos de configuracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		$pAstConf->setReloadDialplan($domain,true);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($error));
	}

	return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);;
}

function get_destination_category($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $orgDomain){
    $jsonObject = new PaloSantoJSON();
    $categoria=getParameter("option");
    $pIVR=new paloIvrPBX($pDB,$orgDomain);
    $arrDestine=$pIVR->getDefaultDestination($orgDomain,$categoria);
    if($arrDestine==FALSE){
        $jsonObject->set_error(_tr($pIVR->errMsg));
    }else{
        $jsonObject->set_message($arrDestine);
    }
    return $jsonObject->createJSON();
}

function createFieldForm($arrOrgz,$recordings,$arrGoTo)
{
    $arrRecordings=array("none"=>"None");
    if(is_array($recordings)){
        foreach($recordings as $key => $value){
            $arrRecordings[$key] = $value;
        }
    }
    
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $loops=array(0,1,2,3,4,5,6,7,8,9);
    $arrFormElements = array("name" => array("LABEL"                  => _tr('Display Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "announcement"  => array("LABEL"                => _tr("Announcement"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecordings,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),//accion en javascript
                             "timeout"   => array("LABEL"                  => _tr("Timeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "retvm"   => array("LABEL"                  => _tr("VM Return to IVR"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "CHECKBOX",
                                                    "INPUT_EXTRA_PARAM"      => array(),
                                                    "VALIDATION_TYPE"        => "",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "directdial"       => array("LABEL"             => _tr("Enable Direct Dial"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "mesg_timeout"       => array("LABEL"             => _tr("Timeout Message"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecordings,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "mesg_invalid"  => array("LABEL"                  => _tr("Invalid Message"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecordings,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "loops"       => array("LABEL"                  => _tr("Repeat Loops"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $loops,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
			    "goto__"      => array("LABEL"             => _tr("goto"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrGoTo,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
			    "option__"    => array("LABEL"                  => _tr("option"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
			    "destine__"    => array("LABEL"       => _tr(""),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                           "ivrret__"   => array("LABEL"        => _tr("Return to IVR"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "CHECKBOX",
                                                    "INPUT_EXTRA_PARAM"      => array(),
                                                    "VALIDATION_TYPE"        => "",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
			
    );
	return $arrFormElements;
}



function createFieldFilter($arrOrgz)
{
    $arrFields = array(
		"organization"  => array("LABEL"                  => _tr("Organization"),
				      "REQUIRED"               => "no",
				      "INPUT_TYPE"             => "SELECT",
				      "INPUT_EXTRA_PARAM"      => $arrOrgz,
				      "VALIDATION_TYPE"        => "domain",
				      "VALIDATION_EXTRA_PARAM" => "",
				      "ONCHANGE"	       => "javascript:submit();"),
		);
    return $arrFields;
}


function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$continue=false;

	if($userLevel1=="other"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization==1){
		return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	$query="select domain from organization where id=?";
	$result=$pACL->_DB->getFirstRowQuery($query, false, array($idOrganization));
	if($result===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Asterisk can't be reloaded. ")._tr($pACL->_DB->errMsg));
	}elseif(count($result)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Asterisk can't be reloaded. "));
	}else{
		$domain=$result[0];
		$continue=true;
	}

	if($continue){
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
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

	return reportIVR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getAction(){
    if(getParameter("create_ivr"))
        return "new_ivr";
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
	else if(getParameter("action")=="reloadAsterisk")
		return "reloadAasterisk";
    else if(getParameter("action")=="get_destination_category")
        return "get_destination_category";
    else
        return "report"; //cancel
}
?>
