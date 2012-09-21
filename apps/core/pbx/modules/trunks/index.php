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
include_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoOrganization.class.php";
    include_once "libs/paloSantoACL.class.php";
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoTrunk.class.php";
    include_once "libs/paloSantoPBX.class.php";
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

	 //comprobacion de la credencial del usuario, el usuario superadmin es el unica capaz de crear
	 //y borrar usuarios de todas las organizaciones
     //los usuarios de tipo administrador estan en la capacidad crear usuarios solo de sus organizaciones
    $arrCredentiasls=getUserCredentials();
	$userLevel1=$arrCredentiasls["userlevel"];
	$userAccount=$arrCredentiasls["userAccount"];
	$idOrganization=$arrCredentiasls["id_organization"];

	//comprobacion de la credencial del usuario, el usuario superadmin es el unico capaz de crear truncales
    if($userLevel1!="superadmin"){
        header("Location: index.php?menu=system");
    }
    
	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    
	$action = getAction();
    $content = "";
       
	switch($action){
        case "new_trunk":
            $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view":
            $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_edit":
            $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_new":
            $content = saveNewTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_edit":
            $content = saveEditTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "delete":
            $content = deleteTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        default: // report
            $content = reportTrunks($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;
}

function reportTrunks($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
{
    $pTrunk = new paloSantoTrunk($pDB);
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);

    if($userLevel1!="superadmin"){
        header("Location: index.php?menu=system");
    }
    
	$domain=getParameter("organization");
	if($userLevel1=="superadmin"){
		if(!empty($domain)){
			$url = "?menu=$module_name&organization=$domain";
		}else{
			$domain = "all";
			$url = "?menu=$module_name";
		}
	}
	
	if($userLevel1=="superadmin"){
        if(isset($domain) && $domain!="all"){
            $total=$pTrunk->getNumTrunks($domain);
        }else{
            $total=$pTrunk->getNumTrunks();
        }
	}

	if($total===false){
		$error=$pTrunk->errMsg;
		$total=0;
	}

	$limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
	
	$arrGrid = array("title"    => _tr('Trunks List'),
                "url"      => $url,
                "width"    => "99%",
                "start"    => ($total==0) ? 0 : $offset + 1,
                "end"      => $end,
                "total"    => $total,
                'columns'   =>  array(
                    array("name"      => _tr("Name"),),
                    array("name"      => _tr("Technology"),),
                    array("name"      => _tr("Channel / Peer Name"),),
                    array("name"      => _tr("Max. Channels"),),
                    array("name"      => _tr("Disabled"),),
                    ),
                );

	$arrTrunks=array();
	$arrData = array();
	if($userLevel1=="superadmin"){
	    if($domain!="all")
            $arrTrunks=$pTrunk->getTrunks($domain);
	    else
            $arrTrunks=$pTrunk->getTrunks();
	}

	if($arrTrunks===false){
		$error=_tr("Error to obtain trunks").$pTrunk->errMsg;
        $arrTrunks=array();
	}

    foreach($arrTrunks as $trunk){
        $arrTmp[0] = "&nbsp;<a href='?menu=trunks&action=view&id_trunk=".$trunk['trunkid']."&tech_trunk=".$trunk["tech"]."'>".$trunk['trunk_name']."</a>";
        $arrTmp[1] = strtoupper($trunk['tech']);
        $arrTmp[2] = $trunk['channelid'];
        $arrTmp[3] = $trunk['maxchans'];
        $arrTmp[4] = $trunk['disabled'];
        $arrData[] = $arrTmp;
    }
    
	$arrTech = array("sip"=>_tr("SIP"),"dahdi"=>_tr("DAHDI"), "iax2"=>_tr("IAX2"));

    if($userLevel1 == "superadmin"){
        $oGrid->addComboAction($name_select="tech_trunk",_tr("Create New Trunk"), $arrTech, $selected=null, $task="create_trunk", $onchange_select=null);
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

function viewFormTrunk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$arrDialPattern=array()){
	
	$error = "";
	$pTrunk = new paloSantoTrunk($pDB);
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);

	$arrTrunks=array();
	$action = getParameter("action");

	if($userLevel1!="superadmin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	$idTrunk=getParameter("id_trunk");
        
	if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
		if(!isset($idTrunk)){
            $error=_tr("Invalid Trunk");
		}else{
            if($userLevel1=="superadmin"){
                $arrTrunks = $pTrunk->getTrunkById($idTrunk, $domain);
            }
            if($arrTrunks===false){
                $error=_tr($pTrunk->errMsg);
            }else if(count($arrTrunks)==0){
                $error=_tr("Trunk doesn't exist");
            }else{  
                $marty->assign('NAME',$arrTrunks("name"));
                $smarty->assign('j',0);
                $tech=$arrTrunks("tech");
                
                if($action=="view"|| getParameter("edit") ){
                    $arrDialPattern = $pTrunk->getArrDestine($idTrunk);
                }
                
                $smarty->assign('items',$arrDialPattern);
                
                if(getParameter("save_edit"))
                    $arrTrunks=$_POST;
            }
		}
	}else{
        $tech  = getParameter("tech_trunk");
        $smarty->assign('j',0);
        $smarty->assign('items',$arrDialPattern);
        
        if(getParameter("create_trunk")){
            $arrTrunks=$pTrunk->getDefaultConfig($tech);
        }else{
            $arrTrunks=$_POST;
        }
	}
	
	if(!preg_match("/^(sip|iax2|dahdi){1}$/",$tech)){
        $error=_tr("Invalid Technology");
    }
	
    if($error!=""){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message",$error);
        return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	$arrForm = createFieldForm($tech);
    $oForm = new paloForm($smarty,$arrForm);

	if($action=="view"){
        $oForm->setViewMode();
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
        $mostrar=getParameter("mostra_adv");
        if(isset($mostrar)){
            if($mostrar=="yes"){
                $smarty->assign("SHOW_MORE","style='visibility: visible;'");
                $smarty->assign("mostra_adv","yes");
            }else{
                $smarty->assign("SHOW_MORE","style='display: none;'");
                $smarty->assign("mostra_adv","no");
            }
        }else{
            $smarty->assign("SHOW_MORE","style='display: none;'");
            $smarty->assign("mostra_adv","yes");
        }
    }
    
    $smarty->assign("DAHDI_CHANNEL",_tr("DHADI Channel"));
	$smarty->assign("TECH",strtoupper($tech));
	$smarty->assign("PEER_Details",_tr("Peer Details"));
	$smarty->assign("ADV_OPTIONS",_tr("Advanced Settings"));
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("CANCEL", _tr("Cancel"));
	$smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
	$smarty->assign("SAVE", _tr("Save"));
	$smarty->assign("EDIT", _tr("Edit"));
	$smarty->assign("DELETE", _tr("Delete"));
	$smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
	$smarty->assign("MODULE_NAME",$module_name);
	$smarty->assign("id_trunk", $idTrunk);
	$smarty->assign("tech_trunk", $tech);
	$smarty->assign("userLevel",$userLevel1);
	$smarty->assign("PREPEND", _tr("Prepend"));
	$smarty->assign("PREFIX", _tr("Prefix"));
	$smarty->assign("MATCH_PATTERN", _tr("Match Pattern"));
	$smarty->assign("RULES", _tr("Dialed Number Manipulation Rules"));
	$smarty->assign("GENERAL", _tr("General"));
	$smarty->assign("SETTINGS", _tr("Peer Settings"));
	$smarty->assign("REGISTRATION", _tr("Registration"));
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("Trunk")." ".strtoupper($tech), $arrTrunks);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewTrunk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	$pTrunk = new paloSantoTrunk($pDB);
	$error = "";
	//conexion elastix.db
	$continue=true;
	$successTrunk=false;

	if($userLevel1!="superadmin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

    $tech  = getParameter("tech_trunk");
    if(!preg_match("/^(sip|iax2|dahdi){1}$/",$tech)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Trunk Technology"));
        return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	$arrForm = createFieldForm($tech);
    $oForm = new paloForm($smarty,$arrForm);

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
        return viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $arrProp=array();
        $arrProp["tech"]=$tech;
        $arrProp["trunk_name"]=getParameter("trunk_name");
        $arrProp['outcid']=getParameter("outcid");
        $arrProp['cid_options']=getParameter("keepcid");
        $arrProp['max_chans']=getParameter("maxchans");
        $arrProp['disabled'] = (getParameter("disabled")) ? "on" : "off";
        $arrProp['dialout_prefix']=getParameter("dialoutprefix");
        
        $arrDialPattern = getParameter("arrDestine");
        $tmpstatus = explode(",",$arrDialPattern);
        $arrDialPattern = array_values(array_diff($tmpstatus, array('')));
        
        if($tech=="dahdi"){
            $arrProp["channelid"]=getParameter("channelid");
            if(preg_match("/^(g|r){0,1}[0-9]+$/")){
                $error=_tr("Field DAHDI Identifier can't be empty and must be a dahdi number or channel number");
                $continue=false;
            }
        }elseif($tech=="sip" || $tech=="iax2"){
            $arrProp["secret"]=getParameter("secret");
            if(strlen($arrProp["secret"])<6 || !preg_match("/^[[:alnum:]]+$/",$arrProp["secret"])){
                $error=_tr("Secret must be at least 6 characters and contain digits and letters");
                $continue=false;
            }
            $arrProp=array_merge(getSipIaxParam($tech),$arrProp);
        }

		if($continue){
			$pDB->beginTransaction();
			$successTrunk=$pTrunk->createNewTrunk($arrProp,$arrDialPattern);
			if($successTrunk)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pTrunk->errMsg;
		}
	}

	if($successTrunk){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Trunk has been created successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		//$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		//$pAstConf->setReloadDialplan($domain,true);
		$content = reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}
	return $content;
}

function saveEditTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	
	$error = "";
	//conexion elastix.db
        $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$successTrunk=false;
	$idTrunk=getParameter("id_trunk");

	if($userLevel1=="superadmin"){
	  $smarty->assign("mb_title", _tr("ERROR"));
	  $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	  return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        }
	
	//un usuario que no es administrador no puede editar la extension de otro usuario
	if($userLevel1=="other"){
		$idUser=$pACL->getIdUser($userAccount);
		$arrUserExt=$pACL->getExtUser($idUser);
		if($arrUserExt["id"]!=$idExten){
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr("You are not authorized to edit"));
			return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		}
	}

	//obtenemos la informacion del usuario por el id dado, sino existe el trunk mostramos un mensaje de error
	if(!isset($idTrunk)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Trunk"));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		/*if($userLevel1=="superadmin"){
			$arrExten = $pExten->getExtensionById($idExten);
			$domain=$arrExten["domain"];
		}else{*/
			$resultO=$pORGZ->getOrganizationById($idOrganization);
			$domain=$resultO["domain"];
			if($userLevel1=="admin"){
				$pTrunk = new paloSantoTrunk($pDB,$domain);
				$arrTrunks = $pTrunk->getTrunkById($idTrunk, $domain);
			}else{
				$pTrunk = new paloSantoTrunk($pDB,$domain);
				$arrTrunks = $pTrunk->getTrunkById($arrUserExt["id"], $domain);
			}
		//}
	}

	if($arrTrunks===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pTrunk->errMsg));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else if(count($arrTrunks)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Trunk doesn't exist"));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$domain=$arrTrunks["domain"];
		
		if($continue){
			//seteamos un arreglo con los parametros configurados
			$arrProp=array();
			$arrProp["name"]=getParameter("name");
			$arrProp['outcid']=getParameter("outcid");
		        $arrProp['cid_options']=getParameter("keepcid");
			$arrProp['max_chans']=getParameter("maxchans");
		        $arrProp['disabled'] = (getParameter("disabled")) ? "on" : "off";
			$arrProp['dialout_prefix']=getParameter("dialoutprefix");
			$arrProp['channelid']=getParameter("channelid");
			$arrProp['domain']=$domain;
			
			
			$arrDialPattern = getParameter("arrDestine");
                        $tmpstatus = explode(",",$arrDialPattern);
            		$arrDialPattern = array_values(array_diff($tmpstatus, array('')));

		}

		if($continue){
			$pTrunkPBX=new paloTrunkPBX($domain,$pDB);
			$pDB->beginTransaction();
			$successTrunk=$pTrunkPBX->updateTrunkPBX($arrProp,$arrDialPattern,$idTrunk);
			
			if($successTrunk)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pTrunkPBX->errMsg;
	      
			
		}
	}

	//$smarty->assign("mostra_adv",getParameter("mostra_adv"));
	$smarty->assign("id_trunk", $idTrunk);

	if($successTrunk){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Trunkhas been edited successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		//$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		//$pAstConf->setReloadDialplan($domain,true);
		$content = reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}
	return $content;
}

function getSipIaxParam($tech,$edit=false){
    $arrProp=array();
    $arrProp["context"]=getParameter("context");
    $arrProp["name"]=getParameter("name");
    $arrProp["register"]=getParameter("register");
    $arrProp["type"]=getParameter("type");
    $arrProp["username"]=getParameter("username");
    $arrProp["host"]=getParameter("host");
    $arrProp["qualify"]=getParameter("qualify");
    $arrProp["disallow"]=getParameter("disallow");
    $arrProp["allow"]=getParameter("allow");
    $arrProp["amaflags"]=getParameter("amaflags");
    $arrProp["deny"]=getParameter("deny");
    $arrProp["permit"]=getParameter("permit");
    if($tech=="sip"){
        $arrProp["insecure"]=getParameter("insecure");
        $arrProp["nat"]=getParameter("nat");
        $arrProp["dtmfmode"]=getParameter("dtmfmode");
        if($edit){
            $arrProp["fromuser"]=getParameter("fromuser");
            $arrProp["fromdomain"]=getParameter("fromdomain");
            $arrProp["sendrpid"]=getParameter("sendrpid");
            $arrProp["canreinvite"]=getParameter("canreinvite");
            $arrProp["useragent"]=getParameter("useragent");
            $arrProp["videosupport"]=getParameter("videosupport");
            $arrProp["maxcallbitrate"]=getParameter("maxcallbitrate");
            $arrProp["qualifyfreq"]=getParameter("qualifyfreq");
            $arrProp["rtptimeout"]=getParameter("rtptimeout");
            $arrProp["rtpholdtimeout"]=getParameter("rtpholdtimeout");
            $arrProp["rtpkeepalive"]=getParameter("rtpkeepalive");
        }
    }elseif($tech=="iax2"){
        $arrProp["auth"]=getParameter("auth");
        $arrProp["trunk"]=getParameter("trunk");
        if($edit){
            $arrProp["trunkfreq"]=getParameter("trunkfreq");
            $arrProp["trunktimestamps"]=getParameter("trunktimestamps");
            $arrProp["sendani"]=getParameter("sendani");
            $arrProp["adsi"]=getParameter("adsi");
            $arrProp["requirecalltoken"]=getParameter("requirecalltoken");
            $arrProp["encryption"]=getParameter("encryption");
            $arrProp["jitterbuffer"]=getParameter("jitterbuffer");
            $arrProp["forcejitterbuffer"]=getParameter("forcejitterbuffer");
            $arrProp["codecpriority"]=getParameter("codecpriority");
            $arrProp["qualifysmoothing"]=getParameter("qualifysmoothing");
            $arrProp["qualifyfreqok"]=getParameter("qualifyfreqok");
            $arrProp["qualifyfreqnotok"]=getParameter("qualifyfreqnotok");
        }
    }
    return $arrProp;
}

function deleteTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	
	$error = "";
	//conexion elastix.db
        $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$successTrunk=false;
	$idTrunk=getParameter("id_trunk");

	if($userLevel1!="admin"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	//obtenemos la informacion del trunk por el id dado, en caso de que la extension pertenzca a un usuario activo
	//esta no puede volver a ser borrada
	if(!isset($idTrunk)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Trunk"));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		/*if($userLevel1=="superadmin"){
			$pTrunk=new paloTrunkPBX($domain,$pDB);
			$arrTrunks = $pTrunk->getTrunkById($idTrunk);
			$domain = $arrTrunks["domain"];
		}else{
			$resultO=$pORGZ->getOrganizationById($idOrganization);
			$domain=$resultO["domain"];*/
			if($userLevel1=="admin"){
				  $resultO=$pORGZ->getOrganizationById($idOrganization);
				  $domain=$resultO["domain"];
				  $pTrunk=new paloSantoTrunk($pDB,$domain);
				  $arrTrunks = $pTrunk->getTrunkById($idTrunk, $domain);
			
			}
		//}
	}

	if($arrTrunks===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pTrunk->errMsg));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else if(count($arrTrunks)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Trunk doesn't exist"));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$pTrunkPBX=new paloTrunkPBX($domain, $pDB);
		$pDB->beginTransaction();
		$successTrunk = $pTrunkPBX->deleteTrunk($idTrunk);
		if($successTrunk)
		    $pDB->commit();
		else
		    $pDB->rollBack();
		$error .=$pTrunkPBX->errMsg;
	}

	if($successTrunk){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("The Trunk was deleted successfully"));
		//mostramos el mensaje para crear los archivos de configuracion
		//$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		//$pAstConf->setReloadDialplan($domain,true);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($error));
	}

	return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);;
}

function createFieldForm($tech)
{
    $arrCid=array("off"=>_tr("Allow Any CID"), "on"=>_tr("Block Foreign CIDs"), "cnum"=>_tr("Remove CNAM"), "all"=>_tr("Force Trunk CID"));
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrAmaflag=array("noset"=>"noset","default"=>"default","omit"=>"omit","billing"=>"billing","documentation"=>"documentation");
    $auth=array("md5"=>"md5","plaintext"=>"plaintext","rsa"=>"rsa");
    $arrNat=array("yes"=>"Yes","no"=>"No","never"=>"never","route"=>"route");
    $arrType=array("friend"=>"friend","peer"=>"peer");
    $arrDtmf=array('rfc2833'=>'rfc2833','info'=>"info",'shortinfo'=>'shortinfo','inband'=>'inband','auto'=>'auto');

    $arrFormElements = array("trunk_name"	=> array("LABEL"                  => _tr('Descriptive Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "keepcid" 	=> array("LABEL"                => _tr("CID Options"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCid,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),//accion en javascript
                             "outcid"   => array("LABEL"                  => _tr("Outbound Caller ID"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "maxchans" => array("LABEL"                   => _tr("Maximum Channels"),
                                                     "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "dialoutprefix" => array("LABEL"         => _tr("Outbound Dial Prefix"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "disabled" => array("LABEL"         => _tr("Disabled"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "CHECKBOX",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "prepend_digit__" => array("LABEL"               => _tr("prepend digit"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:60px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pattern_prefix__" => array("LABEL"               => _tr("pattern prefix"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:30px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pattern_pass__" => array("LABEL"               => _tr("pattern pass"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:150px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            );
                            
    if($tech=="dahdi"){
        $arrFormElements["channelid"] = array("LABEL"                  => _tr("DAHDI Identifier"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
    }else{
        $arrFormElements["name"] =  array("LABEL"                 => _tr("Name Peer"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["register"] =  array("LABEL"                 => _tr("Register String"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["type"] =  array("LABEL"                       => _tr("type"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrType,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(friend|peer){1}$");
        $arrFormElements["secret"] =  array("LABEL"                  => _tr("secret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["username"] =  array("LABEL"                  => _tr("username"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["host"] =  array("LABEL"                  => _tr("host"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["qualify"] =  array("LABEL"                  => _tr("qualify"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["context"] =  array("LABEL"                  => _tr("context"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");     
        $arrFormElements["allow"] =  array("LABEL"                  => _tr("allow"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["disallow"] =  array("LABEL"                  => _tr("disallow"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["deny"] =  array("LABEL"                  => _tr("deny"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["permit"] =  array("LABEL"                  => _tr("permit"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["amaflags"] =  array("LABEL"                  => _tr("amaflags"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrAmaflag,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        if($tech=="sip"){
        $arrFormElements["insecure"] =  array("LABEL"                  => _tr("insecure"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("invite"=>"invite","port"=>"port","port,invite"=>"port,invite"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(invite|port|port,invite){1}$");
        $arrFormElements["nat"] =  array("LABEL"                  => _tr("nat"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrNat,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["dtmfmode"] =  array("LABEL"                  => _tr("dtmfmode"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDtmf,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements=array_merge($arrFormElements,createSipFrom());
        }elseif($tech=="iax2"){
        $arrFormElements["auth"] =  array("LABEL"                  => _tr("auth"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $auth,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(md5|plaintext|rsa){1}$");
        $arrFormElements["trunk"] =  array("LABEL"                  => _tr("trunk"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$");
        $arrFormElements=array_merge($arrFormElements,createIaxFrom());
        }
    }
	
	return $arrFormElements;
}

function createSipFrom(){
    $arrYesNod=array("noset"=>"noset","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrMedia=array("noset"=>"noset",'yes'=>'yes','no'=>'no','nonat'=>'nonat','update'=>'update');
    $arrFormElements = array("canreinvite"   => array( "LABEL"                  => _tr("canreinvite"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "directmedia"   => array( "LABEL"              => _tr("directmedia"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMedia,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "useragent" => array("LABEL"             => _tr("useragent"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "videosupport"   => array( "LABEL"              => _tr("videosupport"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "maxcallbitrate" => array("LABEL"             => _tr("maxcallbitrate"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "qualifyfreq" => array("LABEL"             => _tr("qualifyfreq"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rtptimeout" => array("LABEL"             => _tr("rtptimeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rtpholdtimeout" => array("LABEL"             => _tr("rtpholdtimeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rtpkeepalive" => array("LABEL"             => _tr("rtpkeepalive"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "progressinband" => array("LABEL"             => _tr("progressinband"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "g726nonstandard" => array("LABEL"             => _tr("g726nonstandard"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
    );
    return $arrFormElements;
}

function createIaxFrom(){
    $arrYesNod=array("noset"=>"noset","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrCallTok=array("yes"=>"yes","no"=>"no","auto"=>"auto");
    $arrCodecPrio=array("noset"=>"noset","host"=>"host","caller"=>"caller","disabled"=>"disabled","reqonly"=>"reqonly");
    $encryption=array("noset"=>"noset","aes128"=>"aes128","yes"=>"yes","no"=>"no");
    
    $arrFormElements = array("requirecalltoken" => array("LABEL"             => _tr("requirecalltoken"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCallTok,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "encryption" => array("LABEL"             => _tr("encryption"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $encryption,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "jitterbuffer" => array("LABEL"             => _tr("jitterbuffer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "forcejitterbuffer" => array("LABEL"             => _tr("forcejitterbuffer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "codecpriority" => array("LABEL"             => _tr("codecpriority"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCodecPrio,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "qualifysmoothing" => array("LABEL"             => _tr("qualifysmoothing"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "qualifyfreqok" => array("LABEL"             => _tr("qualifyfreqok"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "qualifyfreqnotok" => array("LABEL"             => _tr("qualifyfreqnotok"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sendani" => array("LABEL"             => _tr("sendani"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"Yes","no"=>"No"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "adsi" => array("LABEL"             => _tr("adsi"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"Yes","no"=>"No"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "trunkfreq" => array("LABEL"             => _tr("trunkfreq"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "trunktimestamps" => array("LABEL"             => _tr("trunktimestamps"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"Yes","no"=>"No"),
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


function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$continue=false;

	if($userLevel1=="other"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization==1){
		return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
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

	return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getAction(){
    if(getParameter("create_trunk"))
        return "new_trunk";
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
