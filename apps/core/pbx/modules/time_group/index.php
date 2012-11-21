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
    include_once "/var/www/html/modules/$module_name/libs/paloSantoTG.class.php";
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
        case "new_tg":
            $content = viewFormTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "edit":
            $content = viewFormTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_new":
            $content = saveNewTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_edit":
            $content = saveEditTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "delete":
            $content = deleteTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization, $domain);
            break;
        default: // report
            $content = reportTG($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $domain);
            break;
    }
    return $content;

}

function reportTG($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain)
{
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pOTGZ = new paloSantoOrganization($pDB2);

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
	      $pTG = new paloSantoTG($pDB,$domain);
	      $total=$pTG->getNumTG($domain);
	  }else{
	      $pTG = new paloSantoTG($pDB,$domain);
	      $total=$pTG->getNumTG();
	  }
	}else{
	    $pTG = new paloSantoTG($pDB,$domain);
	    $total=$pTG->getNumTG($domain);
	}

	if($total===false){
		$error=$pTG->errMsg;
		$total=0;
	}

	$limit=20;

	$oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    
    $oGrid->setTitle(_tr('Time Groups List'));
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
    $arrColum[]=_tr("Name");
/*    $arrColum[]=_tr("Times");
    $arrColum[]=_tr("Days of week");
    $arrColum[]=_tr("Days of Month");
    $arrColum[]=_tr("Months");*/
    $oGrid->setColumns($arrColum);

	$arrTG=array();
	$arrData = array();
	if($userLevel1=="superadmin"){
	    if($domain!="all")
            $arrTG = $pTG->getTGs($domain);
	    else
            $arrTG = $pTG->getTGs();
	}else{
        if($userLevel1=="admin"){
            $arrTG = $pTG->getTGs($domain);
        }
    }

	if($arrTG===false){
		$error=_tr("Error to obtain Time Groups").$pTG->errMsg;
        $arrTG=array();
	}

	$arrData=array();
	foreach($arrTG as $tg) {
        $arrTmp=array();
        if($userLevel1=="superadmin")
            $arrTmp[0] = $tg["name"];
        else
            $arrTmp[0] = "&nbsp;<a href='?menu=$module_name&action=edit&id_tg=".$tg['id']."'>".$tg['name']."</a>";
        $arrData[] = $arrTmp;
    }
			
	if($pOTGZ->getNumOrganization() > 1){
		if($userLevel1 == "admin")
			$oGrid->addNew("create_tg",_tr("Create New Time Group"));

		if($userLevel1 == "superadmin"){
			$arrOrgz=array("all"=>"all");
			foreach(($pOTGZ->getOrganization()) as $value){
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
		$smarty->assign("mb_message",_tr("At least one organization must exist before you can create a new Time Group."));
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

function viewFormTG($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain, $arrItems=array()){
	$error = "";

	$arrTG=array();
	$action = getParameter("action");
       
	if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
	$idTG=getParameter("id_tg");
	if($action=="edit" || getParameter("save_edit")){
		if(!isset($idTG)){
            $error=_tr("Invalid Time Group");
		}else{
			if($userLevel1=="admin"){
                $pTG = new paloSantoTG($pDB,$domain);
				$arrTG = $pTG->getTGById($idTG);
			}else{
                $error=_tr("You are not authorized to perform this action");
			}
		}
		
		if($error==""){
            if($arrTG===false){
                $error=_tr($pTG->errMsg);
            }else if(count($arrTG)==0){
                $error=_tr("TG doesn't exist");
            }else{
                $smarty->assign('j',0);
                if(getParameter("save_edit"))
                    $arrTG=$_POST;
                if($action=="edit")    
                    $pTG->getParametersTG($idTG,$smarty);
            }
        }
        
        if($error!=""){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",$error);
            return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
	}else{
        $pTG = new paloSantoTG($pDB,$domain);
        $smarty->assign('j',1);
        
		if(getParameter("create_tg")){
            $smarty->assign('j',1);
            $smarty->assign('arrItems',$arrItems);
        }else{
            $smarty->assign('j',0);
            $arrTG=$_POST;
        }
	}
	
    
	$arrFormOrgz = createFieldForm($smarty);
    $oForm = new paloForm($smarty,$arrFormOrgz);

	if($action=="edit" ||  getParameter("save_edit")){
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
	$smarty->assign("id_tg", $idTG);
	$smarty->assign("userLevel",$userLevel1);
    $smarty->assign("ADD_GROUP", _tr("Add Conditions"));
    $smarty->assign("DELETE_GROUP", _tr("Delete Conditions"));
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("TG Route"), $arrTG);
	$content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewTG($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	//$pTrunk = new paloSantoTrunk($pDB);
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;

	if($userLevel1!="admin"){
	    $smarty->assign("mb_title", _tr("ERROR"));
	    $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	    return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
	
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
	
    //seteamos un arreglo con los parametros configurados
    $arrProp=array();
    $arrProp["name"]=getParameter("name");
    $pDB->beginTransaction();
    $pTG=new paloSantoTG($pDB,$domain);
    $success=$pTG->createNewTG($arrProp,$smarty);
    if($success)
        $pDB->commit();
    else
        $pDB->rollBack();
    $error .=$pTG->errMsg;


	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Ring Group has been created successfully"));
		 //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
		$content = reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	return $content;
}

function saveEditTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;
	$idTG=getParameter("id_tg");

	if($userLevel1!="admin"){
	  $smarty->assign("mb_title", _tr("ERROR"));
	  $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	  return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
	//obtenemos la informacion del ring_group por el id dado, sino existe el ring_group mostramos un mensaje de error
	if(!isset($idTG)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid Time Group"));
		return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
    
    $pTG=new paloSantoTG($pDB,$domain);
    $arrTG=$pTG->getTGById($idTG);
    if($arrTG==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Time Group doens't exist. ").$pTG->errMsg);
        return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else{
        //seteamos un arreglo con los parametros configurados
        $arrProp=array();
        $arrProp["name"]=getParameter("name");
        $arrProp["id"]=$idTG;
        $pDB->beginTransaction();
        $success=$pTG->updateTGPBX($arrProp,$smarty);
        if($success)
            $pDB->commit();
        else
            $pDB->rollBack();
        $error .=$pTG->errMsg;
    }

	$smarty->assign("id_tg", $idTG);

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("Time Group has been edited successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	return $content;
}

function deleteTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;
	$idTG=getParameter("id_tg");

	if($userLevel1!="admin"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	
	$domain=$org_domain;
    if($domain==false || !isset($idTG)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
 
	$pTG=new paloSantoTG($pDB,$domain);
    $pDB->beginTransaction();
    $success = $pTG->deleteTG($idTG);
    if($success)
        $pDB->commit();
    else
        $pDB->rollBack();
    $error .=$pTG->errMsg;

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("The Time Group was deleted successfully"));
		//mostramos el mensaje para crear los archivos de ocnfiguracion
		$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($error));
	}

	return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);;
}

function generateHours($start, $end){
    $arr = array();
    for($i=$start;$i<=$end;$i++){
        if($i<10)
            $arr["0".$i]="0".$i;
        else
            $arr[$i]=$i;
    }
    return $arr;
}

function createFieldForm($smarty)
{
    $hours=array_merge(array("*"=>"*"),generateHours(0,23));
    $minutes=array_merge(array("*"=>"*"),generateHours(0,59));
    $day_week=array("*"=>"*","mon"=>"Monday","tue"=>"Tuesday","wed"=>"Wenesday","thu"=>"Thursday","fri"=>"Friday","sat"=>"Saturday","sun"=>"Sunday");
    $day_month=array("*"=>"*");
    for($i=1;$i<=31;$i++){
        if($i<10)
            $day_month["0".$i]="0".$i;
        else
            $day_month[$i]=$i;
    }
    $month=array("*"=>"*","jan"=>_tr("January"),"feb"=>_tr("February"),"mar"=>_tr("March"),"apr"=>_tr("April"),"may"=>"May","jun"=>"June","jul"=>"July","aug"=>_tr("August"),"sep"=>_tr("September"),"oct"=>_tr("October"),"nov"=>_tr("November"),"dec"=>_tr("December"));
    
    $smarty->assign('dayWeek',$day_week);
    $smarty->assign('dayMonth',$day_month);
    $smarty->assign('MONTH',$month);
    
    $arrFormElements = array("name"	=> array("LABEL"             => _tr('Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "Stime__" 	=> array("LABEL"             => _tr("Time"),
                                                    "REQUIRED"              => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("class"=>"hasDatepicker"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "Sday_week__"      => array("LABEL"               => _tr("Day of the week"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $day_week,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "Fday_week__"      => array("LABEL"              => _tr(""),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $day_week,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "Sday_month__"    => array("LABEL"               => _tr("Day of the Month"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $day_month,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "Fday_month__"     => array("LABEL"              => _tr(""),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $day_month,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "Smonth__"    => array("LABEL"               => _tr("Month"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $month,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "Fmonth__"     => array("LABEL"              => _tr(""),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $month,
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
		return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization=="1"){
		return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
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

	return reportTG($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
}

function getAction(){
    if(getParameter("create_tg"))
        return "new_tg";
    else if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("action")=="edit")
        return "edit";
	else if(getParameter("action")=="reloadAsterisk")
		return "reloadAasterisk";
    else
        return "report"; //cancel
}
?>
