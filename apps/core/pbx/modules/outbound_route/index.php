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
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoConfig.class.php");
    include_once("libs/paloSantoGrid.class.php");
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoOrganization.class.php";
    include_once("libs/paloSantoACL.class.php");
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoOutbound.class.php";
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

	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    
	$action = getAction();
    $content = "";
       
	switch($action){
        case "new_outbound":
            $content = viewFormOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view":
            $content = viewFormOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_edit":
            $content = viewFormOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_new":
            $content = saveNewOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_edit":
            $content = saveEditOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "delete":
            $content = deleteOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "checkName":
            $content = checkName($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        case "ordenRoute":
            $content = ordenRoute($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        default: // report
            $content = reportOutbound($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;

}

function reportOutbound($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$reorderRoute=false)
{
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
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

    if($userLevel1=="superadmin"){
        if(isset($domain) && $domain!="all"){
            $pOutbound = new paloSantoOutbound($pDB,$domain);
            $total=$pOutbound->getNumOutbound($domain);
        }else{
            $pOutbound = new paloSantoOutbound($pDB,"");
            $total=$pOutbound->getNumOutbound();
        }
    }else{
        $pOutbound = new paloSantoOutbound($pDB,$domain);
        $total=$pOutbound->getNumOutbound($domain);
    }

    if($total===false){
        $error=$pOutbound->errMsg;
        $total=0;
    }

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

    $arrGrid = array("title"    => _tr('Outbound Routes List'),
                "url"      => $url,
                "width"    => "99%",
                "start"    => ($total==0) ? 0 : $offset + 1,
                "end"      => $end,
                "total"    => $total,
                );

    $arrColumns=array();
    $arrColumns[]=_tr("Order");
    $arrColumns[]=_tr("Route Name");
    if($userLevel1=="superadmin"){
        $arrColumns[]=_tr("Organization");
    }
    $arrColumns[]=_tr("Route CID");
    $arrColumns[]=_tr("Route Password");
    $arrColumns[]=_tr("Time Group");
    $oGrid->setColumns($arrColumns);
    
    $arrOutbound=array();
    $arrData = array();
    if($userLevel1=="superadmin"){
        if($domain!="all")
            $arrOutbound = $pOutbound->getOutbounds($domain,$limit,$offset);
        else
            $arrOutbound = $pOutbound->getOutbounds(null,$limit,$offset);
    }else{
        if($userLevel1=="admin")
            $arrOutbound = $pOutbound->getOutbounds($domain,$limit,$offset);
    }

    if($arrOutbound===false){
        $error=_tr("Error to obtain outbounds").$pOutbound->errMsg;
        $arrOutbound=array();
    }

    foreach($arrOutbound as $outbound) {
        $arrTmp=array();
        if($userLevel1=="superadmin"){
            $arrTmp[] = $outbound["seq"];
            $arrTmp[] = $outbound["routename"];
            $arrTmp[] = $outbound["organization_domain"];
        }else{
            $arrTmp[] = fieldOrden($arrOutbound,$outbound["seq"],$outbound["id"]);
            $arrTmp[] = "&nbsp;<a href='?menu=outbound_route&action=view&id_outbound=".$outbound['id']."'>".$outbound['routename']."</a>";
        }
        $arrTmp[]=$outbound["outcid"];
        $arrTmp[]=$outbound["routepass"];
        if(isset($outbound["time_group_id"])){
            $query="SELECT name from time_group where id=?";
            $result=$pDB->getFirstRowQuery($query,true,array($outbound["time_group_id"]));
            if($result!=false){
                $arrTmp[]=$result["name"];
            }else
                $arrTmp[]="";
        }
        $arrData[] = $arrTmp;
    }

    if($pORGZ->getNumOrganization() > 1){
        if($userLevel1 == "admin")
            $oGrid->addNew("create_outbound",_tr("Create New Outbound Route"));

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
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create new Outbound Route"));
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

function fieldOrden($arrOutbound,$seq,$id){
    $field="<select id='ordenR$id' name='ordenR' class='seq_route' >";
    for($j=0;$j<count($arrOutbound);$j++){
        $select="";
        if($seq==$arrOutbound[$j]["seq"])
            $select="selected";
        $field .="<option value='".$arrOutbound[$j]["seq"]."' $select>".$arrOutbound[$j]["seq"]."</option>";
    }
    $field .="</select>";
    return $field;
}

function ordenRoute($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
    $jsonObject = new PaloSantoJSON();
    $seq=getParameter("seq");
    $out_id=getParameter("out_id");
    
    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $domain=getOrgDomainUser();
    if($domain==false){
        $jsonObject->set_error(_tr("You are not authorized to perform this action"));
    }
    
    $pOutbound = new paloSantoOutbound($pDB,$domain);
    $pDB->beginTransaction();
    $result=$pOutbound->reorderRoute($out_id,$seq);
    if($result==false){
        $pDB->rollBack();
        $jsonObject->set_error(_tr($pOutbound->errMsg));
    }else{
        $pDB->commit();
        $pAstConf=new paloSantoASteriskConfig($pDB, new paloDB($arrConf['elastix_dsn']['elastix']));
        $pAstConf->setReloadDialplan($domain,true);
        $content=reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        $jsonObject->set_message(array(_tr("Changes Applied"),$content));
    }
    
    return $jsonObject->createJSON();
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

function viewFormOutbound($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$arrDialPattern=array()){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);

	$arrOutbound=array();
	$action = getParameter("action");
       
	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $domain=getOrgDomainUser();
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	$idOutbound=getParameter("id_outbound");
	
	if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){     
		if(!isset($idOutbound)){
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr("Invalid Outbound"));
			return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		}else{
            if($userLevel1=="admin"){
                $pOutbound = new paloSantoOutbound($pDB,$domain);
                $arrOutbound = $pOutbound->getOutboundById($idOutbound);
            }else{
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
                return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            }
		}
		
		if($arrOutbound===false){
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr($pOutbound->errMsg));
			return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		}else if(count($arrOutbound)==0){
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr("Outbound doesn't exist"));
			return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
		}else{
            if($arrOutbound["outcid_mode"]=="on")
                $check_mode = "CHECKED";
            else
                $check_mode = "";

			$smarty->assign("CHECKED_MODE",$check_mode);
			$smarty->assign('j',0);
       		$smarty->assign('k',0);
			
		    $arrAllTrunks = $arrTrunks = $pOutbound->getTrunks();
			$arrTrunk =  getParameter("arrTrunks");
            if(sizeof($arrTrunk)==0||$arrTrunk==""||(!isset($arrTrunk))){ 
			   $arrTrunkPriority = $pOutbound->getArrTrunkPriority($idOutbound);
			   $smarty->assign('trunks',$arrTrunkPriority);
                if((is_array($arrAllTrunks)) && (is_array($arrTrunkPriority)))
                    $arrDif = array_diff_assoc($arrAllTrunks,$arrTrunkPriority);
                else
                    $arrDif = array();
                $smarty->assign('arrDif',$arrDif); 
			}else{
			   $arrTrunks = array();
			   $tmp = explode(",",$arrTrunk);
               $arrTrunk = array_values(array_diff($tmp, array('')));
               if(is_array($arrTrunk)&&is_array($arrAllTrunks))
                    $arrTrunks=array_intersect($arrTrunk,array_keys($arrAllTrunks));    
               $arrTrunkPriority = array();
               foreach($arrTrunks as $trunk){
                   $val = $pOutbound->getTrunkById($trunk);   
                   $arrTrunkPriority[$val["trunkid"]]=$val["name"]."/".strtoupper($val["tech"]);
               }
			   $smarty->assign('trunks',$arrTrunkPriority);
               $arrDif = array_diff_assoc($arrAllTrunks,$arrTrunkPriority);
			   $smarty->assign('arrDif',$arrDif); 
			}
			
			if($action=="view"|| getParameter("edit") ){
               $arrDialPattern = $pOutbound->getArrDestine($idOutbound);
            }
            $smarty->assign('items',$arrDialPattern);

			if(getParameter("save_edit"))
				$arrOutbound=$_POST;
		}
	}else{ 
        $arrOutbound=$_POST;
        $pOutbound = new paloSantoOutbound($pDB,$domain);
        $smarty->assign('j',0);
        $smarty->assign('items',$arrDialPattern);
        $arrTrunks=array();
        //todas las truncales que han sido asignadas a la organizacion
        $arrAllTrunks = $pOutbound->getTrunks();
        
        //conjunto de id de las truncales que han sido seleccionadas para la ruta
        $arrTrunk =  getParameter("arrTrunks");
        
        $tmp = explode(",",$arrTrunk);
        $arrTrunk = array_values(array_diff($tmp, array('')));

        //verifico que los indices de las truncales dadas existan y que hayan sido asiganados a la organizacion
        if(is_array($arrTrunk)&&is_array($arrAllTrunks))
            $arrTrunks=array_intersect($arrTrunk,array_keys($arrAllTrunks));
        
        $arrTrunkPriority = array();
        foreach($arrTrunks as $trunk){
            $val = $pOutbound->getTrunkById($trunk);   
            $arrTrunkPriority[$val["trunkid"]]=$val["name"]."/".strtoupper($val["tech"]);
        }
        
        $smarty->assign('trunks',$arrTrunkPriority);
        $arrDif = array_diff_assoc($arrAllTrunks,$arrTrunkPriority);
        $smarty->assign('arrDif',$arrDif); 
	}

	$arrForm= createFieldForm($pDB,$domain);
    $oForm = new paloForm($smarty,$arrForm);

	if($action=="view"){
        $oForm->setViewMode();
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
    }
	
	//$smarty->assign("ERROREXT",_tr($pTrunk->errMsg));
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("CANCEL", _tr("Cancel"));
	$smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
	$smarty->assign("SAVE", _tr("Save"));
	$smarty->assign("EDIT", _tr("Edit"));
	$smarty->assign("DELETE", _tr("Delete"));
	$smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
	$smarty->assign("MODULE_NAME",$module_name);
	$smarty->assign("id_outbound", $idOutbound);
	$smarty->assign("userLevel",$userLevel1);
	$smarty->assign("CALLERID", _tr("Caller Id"));
    $smarty->assign("PREPEND", _tr("Prepend"));
    $smarty->assign("PREFIX", _tr("Prefix"));
    $smarty->assign("MATCH_PATTERN", _tr("Match Pattern"));
    $smarty->assign("RULES", _tr("Dial Patterns"));
    $smarty->assign("GENERAL", _tr("General"));
	$smarty->assign("TRUNK_SEQUENCE", _tr("Trunk Sequence for Matched Routes"));
	$smarty->assign("DRAGANDDROP", _tr("Drag and Drop Trunk into Sequence Trunk Area"));
	$smarty->assign("TRUNKS", _tr("TRUNKS"));
	$smarty->assign("SEQUENCE", _tr("TRUNKS SEQUENCE"));
	$smarty->assign("SETTINGS", _tr("Settings"));
	$smarty->assign("PEERDETAIL", _tr("PEER Details"));
	$smarty->assign("USERDETAIL", _tr("USER Details"));
	$smarty->assign("REGISTRATION", _tr("Registration"));
    $smarty->assign("OUTGOING_SETTINGS", _tr("Outgoing Settings"));
	$smarty->assign("INCOMING_SETTINGS", _tr("Incoming Settings"));
	$smarty->assign("OVEREXTEN", _tr("Override Extension"));
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("Outbound Route"), $arrOutbound);
	$content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewOutbound($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	//$pTrunk = new paloSantoTrunk($pDB);
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$success=false;

	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
	
	$domain=getOrgDomainUser();
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportInbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }

	$arrForm = createFieldForm($pDB,$domain);
    $oForm = new paloForm($smarty,$arrForm);

    $arrDialPattern = getParameter("arrDestine");
    $tmpstatus = explode(",",$arrDialPattern);
    $arrDialPattern = array_values(array_diff($tmpstatus, array('')));
    $tmp_dial=array();
    foreach($arrDialPattern as $pattern){
        $prepend = getParameter("prepend_digit".$pattern);
        $prefix = getParameter("pattern_prefix".$pattern);
        $cid = getParameter("match_cid".$pattern);
        $pattern = getParameter("pattern_pass".$pattern);
        $tmp_dial[]=array(0,$prepend,$prefix,$pattern,$cid);
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
        return viewFormOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$tmp_dial);
    }else{
		$routename = getParameter("routename");
		if($routename==""){
			$error=_tr("Route Name can not be empty.");
			$continue=false;
		}
		    
		if($continue){
			//seteamos un arreglo con los parametros configurados
			$arrProp=array();
			$arrProp["routename"]=getParameter("routename");
			$arrProp['outcid']=getParameter("outcid");
            $arrProp['routepass']=getParameter("routepass");
			$arrProp['mohsilence']=getParameter("mohsilence");
            $arrProp['outcid_mode'] = (getParameter("over_exten")) ? "on" : "off";
			$arrProp['time_group_id']=getParameter("time_group_id");
			
			$arrTrunkPriority = getParameter("arrTrunks");
            $tmpstatusT = explode(",",$arrTrunkPriority);
			$arrTrunkPriority = array_values(array_diff($tmpstatusT, array('')));
		}

		if($continue){
            $pOutbound=new paloSantoOutbound($pDB,$domain);
			$pDB->beginTransaction();
			$success=$pOutbound->createNewOutbound($arrProp,$tmp_dial,$arrTrunkPriority);
			if($success)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pOutbound->errMsg;
		}
	}

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Outbound has been created successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
		$pAstConf->setReloadDialplan($domain,true);
		$content = reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization,$tmp_dial);
	}
	return $content;
}

function saveEditOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$success=false;
	$idOutbound=getParameter("id_outbound");

	if($userLevel1!="admin"){
	  $smarty->assign("mb_title", _tr("ERROR"));
	  $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	  return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $domain=getOrgDomainUser();
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportInbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
	//obtenemos la informacion de la ruta por el id dado, sino existe la ruta mostramos un mensaje de error
	if(!isset($idOutbound)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Outbound"));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	$pOutbound = new paloSantoOutbound($pDB,$domain);
    $arrOutbound = $pOutbound->getOutboundById($idOutbound);
	if($arrOutbound===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pOutbound->errMsg));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else if(count($arrOutbound)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Outbound doesn't exist"));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		if($continue){
			//seteamos un arreglo con los parametros configurados
			$arrProp=array();
			$arrProp["routename"]=getParameter("routename");
			$arrProp['outcid']=getParameter("outcid");
            $arrProp['routepass']=getParameter("routepass");
			$arrProp['mohsilence']=getParameter("mohsilence");
            $arrProp['outcid_mode'] = (getParameter("over_exten")) ? "on" : "off";
			$arrProp['time_group_id']=getParameter("time_group_id");
			
			$arrDialPattern = getParameter("arrDestine");
            $tmpstatus = explode(",",$arrDialPattern);
            $arrDialPattern = array_values(array_diff($tmpstatus, array('')));
            $tmp_dial=array();
            foreach($arrDialPattern as $pattern){
                $prepend = getParameter("prepend_digit".$pattern);
                $prefix = getParameter("pattern_prefix".$pattern);
                $cid = getParameter("match_cid".$pattern);
                $pattern = getParameter("pattern_pass".$pattern);
                $tmp_dial[]=array(0,$prepend,$prefix,$pattern,$cid);
            }

			$arrTrunkPriority = getParameter("arrTrunks");
            $tmpstatusT = explode(",",$arrTrunkPriority);
			$arrTrunkPriority = array_values(array_diff($tmpstatusT, array('')));
		}

		if($continue){
			$pDB->beginTransaction();
			$success=$pOutbound->updateOutboundPBX($arrProp,$tmp_dial,$idOutbound,$arrTrunkPriority);
			if($success)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pOutbound->errMsg;
		}
	}
	$smarty->assign("id_outbound", $idOutbound);

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Outbound has been edited successfully"));
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization, $tmp_dial);
	}
	return $content;
}

function deleteOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$continue=true;
	$success=false;
	$idOutbound=getParameter("id_outbound");

	if($userLevel1!="admin"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	//obtenemos la informacion del outbound por el id dado, 
	if(!isset($idOutbound)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Outbound"));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
        if($userLevel1=="admin"){
            $resultO=$pORGZ->getOrganizationById($idOrganization);
            $domain=$resultO["domain"];
            $pOutbound=new paloSantoOutbound($pDB,$domain);
            $arrOutbound = $pOutbound->getOutboundById($idOutbound);
        }
	}

	if($arrOutbound===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pOutbound->errMsg));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else if(count($arrOutbound)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Outbound doesn't exist"));
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$pDB->beginTransaction();
		$success = $pOutbound->deleteOutbound($idOutbound);
		if($success)
		    $pDB->commit();
		else
		    $pDB->rollBack();
		$error .=$pOutbound->errMsg;
	}

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("The Outbound Route was deleted successfully"));
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($error));
	}

	return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);;
}

function createFieldForm($pDB,$domain)
{
    $pOutbound = new paloSantoOutbound($pDB,$domain);
    $arrMusic=$pOutbound->getMoHClass($domain);
    if($arrMusic==false)
        $arrMusic=array("default"=>_tr("Default"));
        
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
   
    //time_group
    $query="SELECT name,id from time_group where organization_domain=?";
    $result=$pDB->fetchTable($query,true,array($domain));
    $arrtg=array(""=>"-- Permanent Route --");
    if($result!=false){
        foreach($result as $value){
            $arrtg[$value["id"]]=$value["name"];
        }
    }
    $arrLang=getLanguagePBX();
    $arrFormElements = array("routename"	=> array("LABEL"             => _tr('Route Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "outcid"   	=> array("LABEL"             => _tr("Route CID"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "routepass" 	=> array("LABEL"             => _tr("Route Password"),
                                                     "REQUIRED"              => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "mohsilence"    	=> array("LABEL"             => _tr("Music On Hold"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMusic,
                                                    "VALIDATION_TYPE"        => "",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "time_group_id" 	=> array("LABEL"         => _tr("Time Group"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrtg,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "prepend_digit__" 	=> array("LABEL"               => _tr("prepend digit"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:60px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pattern_prefix__" 	=> array("LABEL"               => _tr("pattern prefix"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:30px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pattern_pass__" 	=> array("LABEL"               => _tr("pattern pass"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:150px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "match_cid__" 	=> array("LABEL"               => _tr("match cid"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:150px;text-align:center;"),
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
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization==1){
		return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
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

	return reportOutbound($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getAction(){
    if(getParameter("create_outbound"))
        return "new_outbound";
    else if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("edit"))
        return "view_edit";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("action")=="ordenR")
        return "ordenRoute";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view";
    else if(getParameter("action")=="checkName")      //Get parameter by GET (command pattern, links)
        return "checkName";
    else if(getParameter("action")=="view_edit")
        return "view_edit";
	else if(getParameter("action")=="reloadAsterisk")
		return "reloadAasterisk";
    else
        return "report"; //cancel
}
?>
