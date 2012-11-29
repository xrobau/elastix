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
    include_once "/var/www/html/modules/$module_name/configs/default.conf.php";
    include_once "/var/www/html/modules/$module_name/libs/paloSantoQueues.class.php";
    include_once "/var/www/html/libs/paloSantoDB.class.php";
    include_once "/var/www/html/libs/paloSantoGrid.class.php";
	include_once "/var/www/html/libs/paloSantoForm.class.php";
	include_once "/var/www/html/libs/paloSantoOrganization.class.php";
    
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
        case "new_queue":
            $content = viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view":
            $content = viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "view_edit":
            $content = viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_new":
            $content = saveNewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "save_edit":
            $content = saveEditQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        case "delete":
            $content = deleteQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
		case "reloadAasterisk":
			$content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        case "get_destination_category":
            $content = get_destination_category($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        default: // report
            $content = reportQueue($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;

}

function reportQueue($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization)
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
	
	if($userLevel1=="superadmin"){
        if(isset($domain) && $domain!="all"){
            $pQueue = new paloQueuePBX($pDB,$domain);
            $total=$pQueue->getTotalQueues();
        }else{
            $pQueue = new paloQueuePBX($pDB,$domain);
            $total=$pQueue->getTotalAllQueues();
        }
	}else{
        $pQueue = new paloQueuePBX($pDB,$domain);
        $total=$pQueue->getTotalQueues();
	}

	if($total===false){
		$error=$pQueue->errMsg;
		$total=0;
	}

	$limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
	
	$arrGrid = array("title"    => _tr('Queues List'),
                "url"      => $url,
                "width"    => "99%",
                "start"    => ($total==0) ? 0 : $offset + 1,
                "end"      => $end,
                "total"    => $total,
                'columns'   =>  array(
                    array("name"      => _tr("Queue Number"),),
                    array("name"      => _tr("Queue Name"),),
                    array("name"      => _tr("Password"),),
                    array("name"      => _tr("Record Call"),),
                    array("name"      => _tr("Strategy"),),
                    array("name"      => _tr("Timeout Queue"),),
                    array("name"      => _tr("Timeout Agent"),),
                    ),
                );

	$arrData = array();
	$arrQueues = array();
	if($total!=0){
        if($userLevel1=="superadmin"){
            if(isset($domain) && $domain!="all"){
                $arrQueues=$pQueue->getQueues($limit,$offset);
            }else{
                $arrQueues=$pQueue->getAllQueues(null,$limit,$offset);
            }
        }else{
            $arrQueues=$pQueue->getQueues($limit,$offset);
        }
	}
	
	if($arrQueues===false){
        $error=_tr("Error getting queue data. ").$pQueue->errMsg;
	}else{
        foreach($arrQueues as $queue){
            $arrTmp=array();
            $queunumber=$queue["queue_number"];
            if($userLevel1=="superadmin")
                $arrTmp[0] = $queunumber;
            else
                $arrTmp[0] = "&nbsp;<a href='?menu=queues&action=view&qname=".$queue['name']."'>".$queunumber."</a>";
            $arrTmp[1]=$queue["description"];
            $arrTmp[2]=$queue["password_detail"];
            $arrTmp[3]="yes";
            if(!isset($queue["monitor_format"])){
                $arrTmp[3]="no";
            }
            $arrTmp[4]=$queue["strategy"];
            $arrTmp[5]=($queue["timeout_detail"]=="0")?"unlimited":$queue["timeout_detail"];
            $arrTmp[6]=$queue["timeout"];
            /*$result=getInfoQueue();
            $arrTmp[6]=$result["logged"];
            $arrTmp[6]=$result["free"];*/
            $arrData[]=$arrTmp;
        }
	}
	
	$pQueue->getQueueMembers("test");

	if($pORGZ->getNumOrganization() > 1){
        if($userLevel1 == "admin")
            $oGrid->addNew("create_queue",_tr("Create New Queue"));

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
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create new extensions"));
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

function viewQueue($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pORGZ = new paloSantoOrganization($pDB2);
	$error="";

	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $arrQueue=array();
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
            $error=_tr("You need yo have at least one organization created before you can create a queue");
        }
        if($error!=""){
           $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",$error);
            return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization); 
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
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $qname=getParameter("qname");
    if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        if(!isset($qname)){
            $error=_tr("Invalid Queue");
        }else{
            /*if($userLevel1=="superadmin"){
                $pQueue=new paloQueuePBX($pDB,$domain);
                $result=$pQueue->getAllQueues($qname);
                $arrTmp=$result
                if($arrTmp!=false){
                    $arrTmp=$result[0];
                    $domain=$arrTmp["organization_domain"];
                    $pQueue=new paloQueuePBX($pDB,$domain);
                }
            }else{*/
                if($userLevel1=="admin"){
                    $pQueue=new paloQueuePBX($pDB,$domain);
                    $arrTmp=$pQueue->getQueueByName($qname);
                }
            //}
            if($arrTmp===false){
                $error=_tr("Error with database connection. ").$pQueue->errMsg;
            }elseif(count($arrTmp)==false){
                $error=_tr("Queue doesn't exist");
            }else{
                $smarty->assign("QUEUE", $arrTmp["queue_number"]);
                if(getParameter("save_edit")){
                    $arrQueue=$_POST;
                }else{
                    $arrMember=$pQueue->getQueueMembers($qname);
                    if($arrMember===false){
                        $error=_tr("Problems getting queue members. ").$pQueue->errMsg;
                        $arrMember=array();
                    }
                    $arrQueue=showQueueSetting($arrTmp,$arrMember);
                }
            }
        }
    }else{
        /*if($userLevel1=="superadmin"){
            $domain=getParameter("domain_org");
        }*/
        $pQueue=new paloQueuePBX($pDB,$domain);
        if(getParameter("create_queue")){
            $arrQueue=$pQueue->defaultOptions();
        }else{
            $arrQueue=$_POST;
        }
    }
    
    if($error!=""){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message",$error);
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    $category=$pQueue->getCategoryDefault($domain);
    if($category===false)
        $category=array();
    $res=$pQueue->getDefaultDestination($domain,$arrQueue["category"]);
    $destiny=($res==false)?array():$res;
    
    $arrForm = createFieldForm($arrOrgz,$pQueue->getRecordingsSystem($domain),getArrayExtens($pDB,$domain),$category,$destiny,$pQueue->getMoHClass($domain));
    $oForm = new paloForm($smarty,$arrForm);
    
    if($action=="view"){
        $oForm->setViewMode();
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
	$smarty->assign("MODULE_NAME",$module_name);
	$smarty->assign("qname", $qname);
	$smarty->assign("GENERAL",_tr("General"));
	$smarty->assign("MEMBERS",_tr("Queue Members"));
	$smarty->assign("ADVANCED",_tr("Advanced Options"));
	$smarty->assign("TIME_OPTIONS",_tr("Timing Options"));
	$smarty->assign("EMPTY_OPTIONS",_tr("Empty Options"));
	$smarty->assign("RECORDING",_tr("Recording Options"));
	$smarty->assign("ANN_OPTIONS",_tr("Announce Options"));
	$smarty->assign("PER_OPTIONS",_tr("Periodic Announce Options"));
	$smarty->assign("DEFAULT_DEST",_tr("Default Destination"));
	$smarty->assign("userLevel",$userLevel1);
	$htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("Queues"),$arrQueue);
	$content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;
}

function showQueueSetting($arrProp,$arrMember){
    if(!isset($arrProp["musicclass"])){
        $arrProp["musicclass"]="inherit";
    }
    if(isset($arrProp["ringing_detail"])){
        if($arrProp["ringing_detail"]=="yes"){
            $arrProp["musicclass"]="ring";
        }
    }
    if(!isset($arrProp["monitor_format"])){
        $arrProp["monitor_format"]="no";
    }
    if(isset($arrProp["retry_detail"])){
        if($arrProp["retry_detail"]=="no"){
            $arrProp["retry"]="no_retry";
        }
    }
    if(!isset($arrProp["min_announce_frequency"])){
        $arrProp["min_announce_frequency"]=0;
    }
    if(!isset($arrProp["announce_detail"])){
        $arrProp["announce_detail"]="none";
    }
    if(isset($arrProp["context"])){
        $arrProp["context"]=substr($arrProp["context"],16);
    }
    if(isset($arrProp["cid_prefix_detail"])){
        $arrProp["cid_prefix"]=$arrProp["cid_prefix_detail"];
    }
    if(isset($arrProp["cid_holdtime_detail"])){
        $arrProp["cid_holdtime"]=$arrProp["cid_holdtime_detail"];
    }
    
    $category="none";
    if(isset($arrProp['destination_detail'])){
        $tmp=explode(",",$arrProp['destination_detail']);
        if(count($tmp)==2){
            $category=$tmp[0];
        }
    }
    $arrProp["category"]=$category;
    $arrProp["destination"]=$arrProp['destination_detail'];
    
    $statics=$dynamics="";
    foreach($arrMember["statics"] as $value){
        $statics .=$value["exten"].",".$value["penalty"]."\n";
    }
    $arrProp["static_members"]=$statics;
    
    foreach($arrMember["dynamics"] as $value){
        $dynamics .=$value["exten"].",".$value["penalty"]."\n";
    }
    $arrProp["dynamic_members"]=$dynamics;
    
    //print_r($arrProp);
    
    return $arrProp;
}

function saveNewQueue($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    $error="";
    $exito=false;

    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    if($pORGZ->getNumOrganization() <=1){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create extension to this organization"));
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
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
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }elseif(count($orgTmp)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Organization doesn't exist"));
        /*if($userLevel1=="superadmin")
            return viewQueue($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
        else*/
            return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $arrOrgz[$orgTmp["domain"]]=$orgTmp["name"];
        $domain=$orgTmp["domain"];
    }
    
    $pQueue=new paloQueuePBX($pDB,$domain);

    $category=$pQueue->getCategoryDefault($domain);
    if($category===false)
        $category=array();
    $res=$pQueue->getDefaultDestination($domain,$_POST["category"]);
    $destiny=($res==false)?array():$res;
    
    $arrForm = createFieldForm($arrOrgz,$pQueue->getRecordingsSystem($domain),getArrayExtens($pDB,$domain),$category,$destiny,$pQueue->getMoHClass($domain));
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
        return viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $qname=getParameter("name");
        $password=getParameter("password_detail");
        $arrMembers=array('dynamic_members'=>getParameter("dynamic_members"),'static_members'=>getParameter("static_members"));
        if(!preg_match("/^[0-9]+$/",$qname)){
            $error .= _tr("Invalid queue number.");
        }elseif(isset($password)){
            if(!preg_match("/^[0-9]*$/",$password)){
                $error .= _tr("Password must only contain digits.");
            }
        }
        
        if($error==""){  
            $pDB->beginTransaction();
            $exito=$pQueue->createQueue(queueParams(),$arrMembers);
            if($exito){
                $pDB->commit();
            }else{
                $pDB->rollBack();
            }
            $error .=$pQueue->errMsg;
        }
    }
    
    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("Queue has been created successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        return viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    } 
}

function saveEditQueue($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    $error="";
    $exito=false;
    $qname=getParameter("qname");

    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    if(!isset($qname)){
        $error=_tr("Queue doesn't exist");
    }else{
        /*if($userLevel1=="superadmin"){
            $pQueue=new paloQueuePBX($pDB,$domain);
            $result=$pQueue->getAllQueues($qname);
            $arrTmp=$result
            if($arrTmp!=false){
                $arrTmp=$result[0];
                $domain=$arrTmp["organization_domain"];
                $pQueue=new paloQueuePBX($pDB,$domain);
            }
        }else{*/
            if($userLevel1=="admin"){
                $resultO=$pORGZ->getOrganizationById($idOrganization);
                $domain=$resultO["domain"];
                $pQueue=new paloQueuePBX($pDB,$domain);
                $arrTmp=$pQueue->getQueueByName($qname);
            }
        //}
        if($arrTmp===false){
            $error=_tr("Error with database connection. ").$pQueue->errMsg;
        }elseif(count($arrTmp)==false){
            $error=_tr("Queue doesn't exist");
        }else{
            //validamos los datos
            $category=$pQueue->getCategoryDefault($domain);
            if($category===false)
                $category=array();
            $res=$pQueue->getDefaultDestination($domain,$_POST["category"]);
            $destiny=($res==false)?array():$res;
            $arrForm = createFieldForm(array(),$pQueue->getRecordingsSystem($domain),getArrayExtens($pDB,$domain),$category,$destiny,$pQueue->getMoHClass($domain));
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
                return viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            }else{
                $password=getParameter("password_detail");
                if(isset($password)){
                    if(!preg_match("/^[0-9]*$/",$password)){
                        $smarty->assign("mb_title", _tr("ERROR"));
                        $smarty->assign("mb_message",_tr("Password must only contain digits."));
                        return viewQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
                    }
                }
                $arrMembers=array('dynamic_members'=>getParameter("dynamic_members"),'static_members'=>getParameter("static_members"));  
                $pDB->beginTransaction();
                $arrProp=queueParams();
                $arrProp["name"]=$qname;
                $exito=$pQueue->updateQueue($arrProp,$arrMembers);
                if($exito){
                    $pDB->commit();
                }else
                    $pDB->rollBack();
                $error .=$pQueue->errMsg;
            }
        }
    }
    
    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("Queue has been edited successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
    } 
    
    return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function deleteQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    $error="";
    $exito=false;
    $qname=getParameter("qname");

    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }
    
    if(!isset($qname)){
        $error=_tr("Queue doesn't exist");
    }else{
        /*if($userLevel1=="superadmin"){
            $pQueue=new paloQueuePBX($pDB,$domain);
            $result=$pQueue->getAllQueues($qname);
            $arrTmp=$result
            if($arrTmp!=false){
                $arrTmp=$result[0];
                $domain=$arrTmp["organization_domain"];
                $pQueue=new paloQueuePBX($pDB,$domain);
            }
        }else{*/
            if($userLevel1=="admin"){
                $resultO=$pORGZ->getOrganizationById($idOrganization);
                $domain=$resultO["domain"];
                $pQueue=new paloQueuePBX($pDB,$domain);
                $arrTmp=$pQueue->getQueueByName($qname);
            }
        //}
        if($arrTmp===false){
            $error=_tr("Error with database connection. ").$pQueue->errMsg;
        }elseif(count($arrTmp)==false){
            $error=_tr("Queue doesn't exist");
        }else{
            $pDB->beginTransaction();
            $exito=$pQueue->deleteQueue($qname);
            if($exito){
                $pDB->commit();
            }else
                $pDB->rollBack();
            $error .=$pQueue->errMsg;
        }
    }
    
    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("Queue has been deleted successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
    } 
    
    return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getArrayExtens($pDB,$domain){
    $pQueue=new paloQueuePBX($pDB,$domain);
    $arrExten=$pQueue->getAllDevice($domain);
    $extens=array("none"=>"select one");
    if($arrExten!=false){
        foreach($arrExten as $value){
            $extens[$value["exten"]]=$value["exten"]."(".$value["dial"].")";
        }
    }
    return $extens;  
}

function queueParams(){
    $arrProp=array();
    $arrProp["name"]=getParameter("name");
    $arrProp["description"]=getParameter("description");
    $arrProp['cid_prefix_detail']=getParameter("cid_prefix");
    $arrProp['cid_holdtime_detail']=getParameter("cid_holdtime");
    $arrProp['alert_info_detail']=getParameter("alert_info");
    $arrProp['musicclass']=getParameter("musicclass");
    $arrProp['announce_caller_detail']=getParameter("announce_caller_detail");
    $arrProp['announce_detail']=getParameter("announce_detail");
    $arrProp['reportholdtime']=getParameter("reportholdtime");
    $arrProp['strategy']=getParameter("strategy");
    $arrProp['maxlen']=getParameter("maxlen");
    $arrProp['monitor_format']=getParameter("monitor_format");
    $arrProp['timeout_detail']=getParameter("timeout_detail");
    $arrProp['timeout']=getParameter("timeout");
    $arrProp['retry']=getParameter("retry");
    $arrProp['timeoutpriority']=getParameter("timeoutpriority");
    $arrProp['joinempty']=getParameter("joinempty");
    $arrProp['leavewhenempty']=getParameter("leavewhenempty");
    $arrProp["skip_busy_detail"]=getParameter("skip_busy_detail");
    $arrProp['password_detail']=getParameter("password_detail");
    $arrProp['servicelevel']=getParameter("servicelevel");
    $arrProp['context']=getParameter("context");
    $arrProp['weight']=getParameter("weight");
    $arrProp['wrapuptime']=getParameter("wrapuptime");
    $arrProp['autofill']=getParameter("autofill");
    $arrProp['autopausedelay']=getParameter("autopausedelay");
    $arrProp['autopause']=getParameter("autopause");
    $arrProp['announce_frequency']=getParameter("announce_frequency");
    $arrProp['min_announce_frequency']=getParameter("min_announce_frequency");
    $arrProp['announce_holdtime']=getParameter("announce_holdtime");
    $arrProp['announce_position']=getParameter("announce_position");
    $arrProp['announce_position_limit']=getParameter("announce_position_limit");
    $arrProp['periodic_announce']=getParameter("periodic_announce");
    $arrProp['periodic_announce_frequency']=getParameter("periodic_announce_frequency");
    $arrProp['restriction_agent']=getParameter("restriction_agent");
    $arrProp['calling_restriction']=getParameter("calling_restriction");
    $arrProp['destination_detail']=getParameter("destination");
    return $arrProp; 
}

function get_destination_category($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $jsonObject = new PaloSantoJSON();
    $categoria=getParameter("category");
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pORGZ = new paloSantoOrganization($pDB2);
    $resultO=$pORGZ->getOrganizationById($idOrganization);
    if($resultO==FALSE){
        $jsonObject->set_error(_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg));
    }else{    
        $pQueue=new paloQueuePBX($pDB,$resultO["domain"]);
        $arrDestine=$pQueue->getDefaultDestination($resultO["domain"],$categoria);
        if($arrDestine==FALSE){
            $jsonObject->set_error(_tr($pQueue->errMsg));
        }else{
            $jsonObject->set_message($arrDestine);
        }
    }
    return $jsonObject->createJSON();
}

function createFieldForm($arrOrg,$Recordings,$extens,$category,$destiny,$arrMusic)
{   
    $arrRecordings=array("none"=>"None");
    if(is_array($Recordings)){
        foreach($Recordings as $key => $value){
            $arrRecordings[$key] = $value;
        }
    }
    
    $music=array("ring"=>"ring");
    foreach($arrMusic as $key => $value){
        $music[$key]=$value;
    }
    
    $arrTime=range(0,120);
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrMonitor=array("no"=>"no","gsm"=>"gsm","wav"=>"wav","wav49"=>"wav49");
    $arrLen=array("0"=>"unlimited",1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60);
	$arrPosition=array(""=>"none","1"=>1,"2"=>2,"3"=>3,"4"=>4,"5"=>5,"6"=>6,"7"=>7,"8"=>8,"9"=>9,"10"=>10,"15"=>15,"20"=>20);	$arrTimeF=array("0"=>"desactivate",1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120);
    $arrBusy=array("No","Yes","Yes + (ringuse=no)","Queues Calls only + (ringuse=no)");    
    $strategy=array('ringall'=>'ringall','leastrecent'=>'leastrecent','fewestcalls'=>'fewestcalls','random'=>'random','rrmemory'=>'rrmemory','rrordered'=>'rrordered','linear'=>'linear','leastrecent'=>'leastrecent');
	$arrMaxTimeOut=array("0"=>"unlimited","10"=>"10 seconds","20"=>"20 seconds","30"=>"30 seconds","40"=>"40 seconds","50"=>"50 seconds","60"=>"1 minute","90"=>"1 min 30\"","120"=>"2 mins","150"=>"2 mins 30\"","180"=>"3 mins","210"=>"3 mins 30\"","240"=>"4 mins","270"=>"4 mins 30\"","300"=>"5 mins","600"=>"10 mins","900"=>"15 mins","1200"=>"20 mins","1500"=>"25 mins","1800"=>"30 mins","2100"=>"35 mins","2400"=>"40 mins","2700"=>"45 mins","3000"=>"50 mins","3300"=>"55 mins","3600"=>"1 hour");
    $retry=array("no_retry"=>"no retry",0=>"0 seconds","1"=>"1 seconds","2"=>"2 seconds","3"=>"3 seconds","4"=>"4 seconds","5"=>"5 seconds","6"=>"6 seconds","7"=>"7 seconds","8"=>"8 seconds","9"=>"9 seconds","10"=>"10 seconds","11"=>"11 seconds","12"=>"12 seconds","13"=>"13 seconds","14"=>"14 seconds","15"=>"15 seconds","16"=>"16 seconds","17"=>"17 seconds","18"=>"18 seconds","19"=>"19 seconds","20"=>"20 seconds");
    $arrFormElements = array("description" => array("LABEL"                  => _tr('Description'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "name" => array("LABEL"                  => _tr('Queue Number'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "cid_prefix"  => array("LABEL"                  => _tr("Cid Prefix"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "cid_holdtime"   => array("LABEL"                  => _tr("Cid Prefix Holdtime"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                             "alert_info"   => array("LABEL"                  => _tr("Alert Info"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
							 "musicclass"       => array("LABEL"           => _tr("Music On Hold"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $music,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "announce_caller_detail"       => array("LABEL"        => _tr("Announce Caller"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecordings,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "announce_detail"       => array("LABEL"            => _tr("Announce Agent"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecordings,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "reportholdtime"       => array("LABEL"         => _tr("report agent caller's hold time"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                             "strategy"       => array("LABEL"              => _tr("Strategy "),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $strategy,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "maxlen"   => array("LABEL"                  => _tr("Max Number Caller"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrLen,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
							 "monitor_format"   => array("LABEL"               => _tr("Record Call"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMonitor,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(wav|no|gsm|wav49){1}$"),
                             "timeout_detail"   => array("LABEL"               => _tr("Max time caller in queue"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMaxTimeOut,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "timeout"   => array("LABEL"            => _tr("Agent Timeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrLen,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "retry"   => array("LABEL"               => _tr("Retry"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $retry,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "timeoutpriority" => array("LABEL"             => _tr("Timeout Priority"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("app"=>"app","conf"=>"conf"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(app|conf){1}$"),
                             "joinempty"   => array("LABEL"               => _tr("Joinempty"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"yes","no"=>"no","strict"=>"strict","loose"=>"loose"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|strict|loose){1}$"),
                             "leavewhenempty"   => array("LABEL"              => _tr("Leavewhenempty"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"yes","no"=>"no","strict"=>"strict","loose"=>"loose"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|strict|loose){1}$"),
                             "skip_busy_detail" => array("LABEL"             => _tr("Skip Busy Agent"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrBusy,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
							 "password_detail"       => array("LABEL"               => _tr("Password"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "servicelevel"       => array("LABEL"              => _tr("Service Level"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTime,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "context"       => array("LABEL"               => _tr("exit context"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
							 "weight"       => array("LABEL"               => _tr("Queue Weight"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTime,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "wrapuptime"       => array("LABEL"              => _tr("Wrap-up-Time"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTime,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "autofill"       => array("LABEL"              => _tr("Autofill"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                             "autopausedelay"       => array("LABEL"         => _tr("autopausedelay"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTime,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "autopause"       => array("LABEL"              => _tr("Autopause"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("no"=>"No","yes"=>"Yes","all"=>"All"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|all){1}$"),
                             "announce_frequency" => array("LABEL"            => _tr("Announce Frecuency"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTimeF,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "min_announce_frequency" => array("LABEL"            => _tr("Min Announce Frecuency"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTimeF,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "announce_holdtime"       => array("LABEL"       => _tr("Announce Holdtime"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"yes","no"=>"no","once"=>"once"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|once){1}$"),
                            "announce_position"       => array("LABEL"       => _tr("Announce Position"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"yes","no"=>"no","more"=>"more","limit"=>"limit"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|more|limit){1}$"),
                            "announce_position_limit" => array("LABEL"            => _tr("Announce Position Limit"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrPosition,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "periodic_announce" => array("LABEL"            => _tr("Periodic Announce"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "periodic_announce_frequency" => array("LABEL"   => _tr("Periodic Announce Frecuency"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTime,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "dynamic_members" => array("LABEL"               => _tr("Dynamic Members"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXTAREA",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:400px;resize:none"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "ROWS"                   => "5",
                                                    "COLS"                   => "2"),
                            "static_members" => array("LABEL"               => _tr("Static Members"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXTAREA",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:400px;resize:none"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "ROWS"                   => "5",
                                                    "COLS"                   => "2"),
                            "pickup_dynamic"   => array("LABEL"                => _tr("Estension List"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $extens,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pickup_static"   => array("LABEL"                => _tr("Estension List"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $extens,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "restriction_agent"   => array("LABEL"          => _tr("Only Dynamic Agents Listed"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "calling_restriction" => array("LABEL"          => _tr("Agent Restrinctions"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("as called","no followme","only extension"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "category"     => array("LABEL"          => _tr("Default Destination"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $category,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "destination" => array("LABEL"                  => _tr(""),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $destiny,
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
		return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization==1){
		return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
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

	return reportQueue($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getAction(){
    if(getParameter("create_queue"))
        return "new_queue";
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