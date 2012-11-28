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
  $Id: index.php,v 1.1.1.1 2012/07/30 rocio mera rmera@palosanto.com Exp $ */
include_once "/var/www/html/libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    include_once("/var/www/html/libs/paloSantoDB.class.php");
    include_once("/var/www/html/libs/paloSantoConfig.class.php");
    include_once("/var/www/html/libs/paloSantoGrid.class.php");
    include_once "/var/www/html/libs/paloSantoForm.class.php";
    include_once "/var/www/html/libs/paloSantoOrganization.class.php";
    include_once("/var/www/html/libs/paloSantoACL.class.php");
    include_once "/var/www/html/modules/$module_name/configs/default.conf.php";
    include_once "/var/www/html/modules/$module_name/libs/paloSantoRG.class.php";
    include_once "/var/www/html/libs/paloSantoPBX.class.php";
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

	 //comprobacion de la credencial del usuario
    $arrCredentiasls=getUserCredentials();
	$userLevel1=$arrCredentiasls["userlevel"];
	$userAccount=$arrCredentiasls["userAccount"];
	$idOrganization=$arrCredentiasls["id_organization"];
	$domain=$arrCredentiasls["domain"];

	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    
	$action = getAction();
    $content = "";
    
	switch($action){
        case "new_rg":
            $content = viewFormRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "view":
            $content = viewFormRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "view_edit":
            $content = viewFormRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_new":
            $content = saveNewRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_edit":
            $content = saveEditRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "delete":
            $content = deleteRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization, $domain);
            break;
        default: // report
            $content = reportRG($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $domain);
            break;
    }
    return $content;

}

function reportRG($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain)
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
		$domain=$org_domain;
		$url = "?menu=$module_name";
	}
	
	if($userLevel1=="superadmin"){
	  if(isset($domain) && $domain!="all"){
	      $pRG = new paloSantoRG($pDB,$domain);
	      $total=$pRG->getNumRG($domain);
	  }else{
	      $pRG = new paloSantoRG($pDB,$domain);
	      $total=$pRG->getNumRG();
	  }
	}else{
	    $pRG = new paloSantoRG($pDB,$domain);
	    $total=$pRG->getNumRG($domain);
	}

	if($total===false){
		$error=$pRG->errMsg;
		$total=0;
	}

	$limit=20;

	$oGrid = new paloSantoGrid($smarty);
	$oGrid->setLimit($limit);
	$oGrid->setTotal($total);
	$offset = $oGrid->calculateOffset();

	$end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    
    $oGrid->setTitle(_tr('RG Routes List'));
    //$oGrid->setIcon('url de la imagen');
    $oGrid->setWidth("99%");
    $oGrid->setStart(($total==0) ? 0 : $offset + 1);
    $oGrid->setEnd($end);
    $oGrid->setTotal($total);
    $oGrid->setURL($url);

    $arrColum=array(); 
    if($userLevel1=="superadmin"){
        $arrColum[]=_tr("Organization");
    }
    $arrColum[]=_tr("Number");
    $arrColum[]=_tr("Name");
    $arrColum[]=_tr("Strategy");
    $arrColum[]=_tr("Ring Time");
    $arrColum[]=_tr("Ignore CF");
    $arrColum[]=_tr("Skip Busy Extensions");
    $arrColum[]=_tr("Default Destination");
    $oGrid->setColumns($arrColum);

    $arrRG=array();
    $arrData = array();
    if($userLevel1=="superadmin"){
        if($domain!="all")
           $arrRG = $pRG->getRGs($domain,$limit,$offset);
        else
            $arrRG = $pRG->getRGs(null,$limit,$offset);
    }else{
        if($userLevel1=="admin"){
            $arrRG = $pRG->getRGs($domain,$limit,$offset);
        }
    }

	if($arrRG===false){
		$error=_tr("Error to obtain Ring Groups").$pRG->errMsg;
        $arrRG=array();
	}

	foreach($arrRG as $rg) {
        $arrTmp=array();
        if($userLevel1=="superadmin"){
            $arrTmp[] = $rg["organization_domain"];
            $arrTmp[] = $rg["rg_number"];
        }else
            $arrTmp[] = "&nbsp;<a href='?menu=ring_group&action=view&id_rg=".$rg['id']."'>".$rg['rg_number']."</a>";
        
        $arrTmp[]=$rg["rg_name"];
        $arrTmp[]=$rg["rg_strategy"];
        $arrTmp[]=$rg["rg_time"];
        $arrTmp[]=$rg["rg_cf_ignore"];
        $arrTmp[]=$rg["rg_skipbusy"];
        $arrTmp[]=$rg["destination"];

        $arrData[] = $arrTmp;
    }
			
	if($pORGZ->getNumOrganization() > 1){
		if($userLevel1 == "admin")
			$oGrid->addNew("create_rg",_tr("Create New Ring Group"));

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
		$smarty->assign("mb_message",_tr("At least one organization must exist before you can create a new Ring Group."));
	}

	if($error!=""){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",$error);
	}
	$contenidoModulo = $oGrid->fetchGrid(array(), $arrData);
	$mensaje=showMessageReload($module_name, $arrConf, $pDB, $userLevel1, $userAccount, $org_domain);
	$contenidoModulo = $mensaje.$contenidoModulo;
    return $contenidoModulo;
}

function showMessageReload($module_name,$arrConf, &$pDB, $userLevel1, $userAccount, $org_domain){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
	$params=array();
	$msgs="";

	$query = "SELECT domain, id from organization";
	//si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de mnarcada
	if($userLevel1!="superadmin"){
		$query .= " where domain=?";
		$params[]=$org_domain;
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

function viewFormRG($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);

	$arrRG=array();
	$action = getParameter("action");
       
	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }

	$idRG=getParameter("id_rg");
	if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
		if(!isset($idRG)){
            $error=_tr("Invalid Ring Group");
		}else{
			if($userLevel1=="admin"){
                $pRG = new paloSantoRG($pDB,$domain);
				$arrRG = $pRG->getRGById($idRG);
			}else{
                $error=_tr("You are not authorized to perform this action");
			}
		}
		
		if($error==""){
            if($arrRG===false){
                $error=_tr($pRG->errMsg);
            }else if(count($arrRG)==0){
                $error=_tr("RG doesn't exist");
            }else{
                if(getParameter("save_edit"))
                    $arrRG=$_POST;
                else{
                    if($action!="view"){
                        $tmpExt=explode("-",$arrRG["rg_extensions"]);
                        $arrRG["rg_extensions"]="";
                        foreach($tmpExt as $value){
                            $arrRG["rg_extensions"] .=$value."\n";
                        }
                    }
                    if($arrRG["rg_play_moh"]!="yes"){
                        $arrRG["rg_moh"]=$arrRG["rg_play_moh"];
                    }
                }
                $smarty->assign("confirm",$arrRG["rg_confirm_call"]);
            }
        }
        
        if($error!=""){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",$error);
            return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
	}else{
        $pRG = new paloSantoRG($pDB,$domain);
		if(getParameter("create_rg")){
            $arrRG["rg_strategy"]="ringall";
            $arrRG["rg_moh"]="ring";
            $arrRG["rg_recording"]="none";
            $arrRG["rg_cf_ignore"]="no";
            $arrRG["rg_skipbusy"]="no";
            $arrRG["rg_confirm_call"]="no";
            $arrRG["rg_time"]="20";
            $arrRG["goto"]="";
        }else
            $arrRG=$_POST; 
	}
	
	$goto=$pRG->getCategoryDefault($domain);
    if($goto===false)
        $goto=array();
    $res=$pRG->getDefaultDestination($domain,$arrRG["goto"]);
    $destiny=($res==false)?array():$res;
    
	$arrFormOrgz = createFieldForm($goto,$destiny,$pDB,$domain);
    $oForm = new paloForm($smarty,$arrFormOrgz);

	if($action=="view"){
        $oForm->setViewMode();
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
    }
	
	//$smarty->assign("ERROREXT",_tr($pTrunk->errMsg));
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("CANCEL", _tr("Cancel"));
	$smarty->assign("OPTIONS", _tr("Options"));
	$smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
	$smarty->assign("SAVE", _tr("Save"));
	$smarty->assign("EDIT", _tr("Edit"));
	$smarty->assign("DELETE", _tr("Delete"));
	$smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
	$smarty->assign("MODULE_NAME",$module_name);
	$smarty->assign("id_rg", $idRG);
	$smarty->assign("userLevel",$userLevel1);
	$smarty->assign("SETDESTINATION", _tr("Final Destination"));
        
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("RG Route"), $arrRG);
	$content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewRG($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	//$pTrunk = new paloSantoTrunk($pDB);
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;

	if($userLevel1!="admin"){
	    $smarty->assign("mb_title", _tr("ERROR"));
	    $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	    return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
	
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
	
    $pRG=new paloSantoRG($pDB,$domain);
	$goto=$pRG->getCategoryDefault($domain);
    if($goto===false)
        $goto=array();
    $res=$pRG->getDefaultDestination($domain,getParameter("goto"));
    $destiny=($res==false)?array():$res;
    
    $arrFormOrgz = createFieldForm($goto,$destiny,$pDB,$domain);
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
        return viewFormRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else{
		$name = getParameter("rg_name");
		if($name==""){
			$error=_tr("Field 'Name' can't be empty.");
			$continue=false;
		}
		
		if($pRG->validateDestine($domain,getParameter("destination"))==false){
            $error=_tr("You must select a default destination.");
            $continue=false;
		}
		    
		if($continue){
			//seteamos un arreglo con los parametros configurados
			$arrProp=array();
			$arrProp["rg_name"]=getParameter("rg_name");
			$arrProp["rg_number"]=getParameter("rg_number");
            $arrProp['rg_strategy']=getParameter("rg_strategy");
			$arrProp['rg_time']=getParameter("rg_time");
            $arrProp['rg_alertinfo']=getParameter("rg_alertinfo");
			$arrProp['rg_cid_prefix']=getParameter("rg_cid_prefix");
			$arrProp['rg_recording'] = getParameter("rg_recording");
			$arrProp['rg_moh']=getParameter("rg_moh");
			$arrProp['rg_cf_ignore'] = getParameter("rg_cf_ignore");
			$arrProp['rg_skipbusy'] = getParameter("rg_skipbusy");
			$arrProp['rg_confirm_call'] = getParameter("rg_confirm_call");
			$arrProp['rg_extensions'] = getParameter("rg_extensions");
			if($arrProp['rg_confirm_call']=="yes"){
			    $arrProp['rg_record_remote']=getParameter("rg_record_remote");
			    $arrProp['rg_record_toolate']=getParameter("rg_record_toolate");
			}
			$arrProp['goto']=getParameter("goto");
			$arrProp['destination']=getParameter("destination");
		}

		if($continue){
			$pDB->beginTransaction();
			$success=$pRG->createNewRG($arrProp);
			if($success)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pRG->errMsg;
		}
	}

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Ring Group has been created successfully"));
		 //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
		$content = reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	return $content;
}

function saveEditRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;
	$idRG=getParameter("id_rg");

	if($userLevel1!="admin"){
	  $smarty->assign("mb_title", _tr("ERROR"));
	  $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	  return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
	//obtenemos la informacion del ring_group por el id dado, sino existe el ring_group mostramos un mensaje de error
	if(!isset($idRG)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Ring Group"));
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}

	$pRG = new paloSantoRG($pDB,$domain);
    $arrRG = $pRG->getRGById($idRG);
	if($arrRG===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pRG->errMsg));
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else if(count($arrRG)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("RG doesn't exist"));
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		if($pRG->validateDestine($domain,getParameter("destination"))==false){
            $error=_tr("You must select a destination for this ring_group.");
            $continue=false;
        }
        
		if($continue){
			//seteamos un arreglo con los parametros configurados
			$arrProp=array();
			$arrProp["id_rg"]=$idRG;
			$arrProp["rg_name"]=getParameter("rg_name");
            $arrProp["rg_number"]=getParameter("rg_number");
            $arrProp['rg_strategy']=getParameter("rg_strategy");
            $arrProp['rg_time']=getParameter("rg_time");
            $arrProp['rg_alertinfo']=getParameter("rg_alertinfo");
            $arrProp['rg_cid_prefix']=getParameter("rg_cid_prefix");
            $arrProp['rg_recording'] = getParameter("rg_recording");
            $arrProp['rg_moh']=getParameter("rg_moh");
            $arrProp['rg_cf_ignore'] = getParameter("rg_cf_ignore");
            $arrProp['rg_skipbusy'] = getParameter("rg_skipbusy");
            $arrProp['rg_confirm_call'] = getParameter("rg_confirm_call");
            $arrProp['rg_extensions'] = getParameter("rg_extensions");
            if($arrProp['rg_confirm_call']=="yes"){
                $arrProp['rg_record_remote']=getParameter("rg_record_remote");
                $arrProp['rg_record_toolate']=getParameter("rg_record_toolate");
            }
            $arrProp['goto']=getParameter("goto");
            $arrProp['destination']=getParameter("destination");
		}

		if($continue){
			$pDB->beginTransaction();
			$success=$pRG->updateRGPBX($arrProp);
			
			if($success)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pRG->errMsg;
		}
	}

	$smarty->assign("id_inbound", $idRG);

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Ring Group has been edited successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	return $content;
}

function deleteRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;
	$idRG=getParameter("id_rg");

	if($userLevel1!="admin"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}

	//obtenemos la informacion del inbound por el id dado, 
	if(!isset($idRG)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid RG"));
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
        if($userLevel1=="admin"){
            $domain=$org_domain;
            $pRG=new paloSantoRG($pDB,$domain);
            $arrRG = $pRG->getRGById($idRG);
        }
	}

    $pDB->beginTransaction();
    $success = $pRG->deleteRG($idRG);
    if($success)
        $pDB->commit();
    else
        $pDB->rollBack();
    $error .=$pRG->errMsg;

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("The Ring Group was deleted successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($error));
	}

	return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);;
}

function generateOptionNum($start, $end){
    $arr = array();
    for($i=$start;$i<=$end;$i++){
        $arr[$i]=$i;
    }
    return $arr;
}

function createFieldForm($goto,$destination,$pDB,$domain)
{
    $pRG=new paloSantoRG($pDB,$domain);
    $strategy = array('ringall'=>'ringall','leastrecent'=>'leastrecent','fewestcalls'=>'fewestcalls','random'=>'random','rrmemory'=>'rrmemory','rrordered'=>'rrordered','linear'=>'linear','leastrecent'=>'leastrecent');
    $time = generateOptionNum(1, 60);
    $arrYesNo = array("yes" => _tr("Yes"), "no" => "No");
    
    $arrRecording=$pRG->getRecordingsSystem($domain);
    $arrMoH=$pRG->getMoHClass($domain);
    $arrExten=$pRG->getAllDevice($domain);
    
    $recording = array("none"=>"None");
    $recording2 = array("default"=>"Default");
    if(is_array($arrRecording)){
        foreach($arrRecording as $key => $value){
            $recording[$key] = $value;
            $recording2[$key] = $value;
        }
    }
    
    $arrMusic=array("ring"=>_tr("Only Ring"));
    if(is_array($arrMoH)){
        foreach($arrMoH as $key => $value){
            $arrMusic[$key] = $value;
        }
    }
    
    $extens=array("none"=>"select one");
    if($arrExten!=false){
        foreach($arrExten as $value){
            $extens[$value["exten"]]=$value["exten"]." (".$value["dial"].")";
        }
    }
    
    $arrFormElements = array("rg_name"	=> array("LABEL"             => _tr('Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rg_number"   	=> array("LABEL"             => _tr("Number"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rg_strategy" 	=> array("LABEL"             => _tr("Strategy"),
                                                    "REQUIRED"              => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $strategy,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rg_alertinfo"    	=> array("LABEL"             => _tr("Alert Info"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rg_cid_prefix" 	=> array("LABEL"             => _tr("CID Name Prefix"),
                                                    "REQUIRED"               => "no",
                                                     "INPUT_TYPE"            => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rg_moh"	    	=> array("LABEL"             => _tr("Music On Hold"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMusic,
                                                    "VALIDATION_TYPE"        => "",
                                                    "VALIDATION_EXTRA_PARAM" => ""),  
                            "rg_time" 	=> array("LABEL"             => _tr("Ring Time"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"            => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $time,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "goto"  	=> array("LABEL"             => _tr("Destine"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $goto,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                            "destination"  	=> array("LABEL"             => _tr(""),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $destination,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                            "rg_cf_ignore"     => array("LABEL"             => _tr("Ignore CF"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rg_skipbusy"     => array("LABEL"             => _tr("Skip Busy Extensions"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rg_confirm_call"     => array("LABEL"           => _tr("Confirm Call"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                            "rg_recording"     => array("LABEL"           => _tr("Recording"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $recording,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                            "rg_record_remote"     => array("LABEL"           => _tr("Recording Remote"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $recording2,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                            "rg_record_toolate"     => array("LABEL"           => _tr("Recording Too Late"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $recording2,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                            "rg_extensions" => array("LABEL"               => _tr("Extensions List"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXTAREA",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px;resize:none"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "ROWS"                   => "5",
                                                    "COLS"                   => "2"),
                            "pickup_extensions"   => array("LABEL"                => _tr(""),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $extens,
                                                    "VALIDATION_TYPE"        => "text",
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


function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization,$org_domain){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$continue=false;

	if($userLevel1=="other"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization=="1"){
		return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
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

	return reportRG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
}

function getAction(){
    if(getParameter("create_rg"))
        return "new_rg";
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
    else
        return "report"; //cancel
}
?>
