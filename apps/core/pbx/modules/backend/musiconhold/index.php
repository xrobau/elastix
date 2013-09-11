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
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoConfig.class.php");
    include_once("libs/paloSantoGrid.class.php");
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoOrganization.class.php";
    include_once("libs/paloSantoACL.class.php"); 
    include_once "libs/paloSantoPBX.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
   
	 //folder path for custom templates
     $local_templates_dir=getWebDirModule($module_name);

	 //comprobacion de la credencial del usuario
    $arrCredentiasls=getUserCredentials();
	$userLevel1=$arrCredentiasls["userlevel"];
	$userAccount=$arrCredentiasls["userAccount"];
	$domain=$arrCredentiasls["domain"];
	
	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
	
	/*$finfo = new finfo(FILEINFO_MIME, "/usr/share/misc/magic.mgc");
	print_r($finfo->file("/var/lib/asterisk/moh/palosanto.com/ventas/01_Pacific_Coast_Highway_quiet-4db.mp3"));
	
	//$arrFile=pathinfo("/var/lib/asterisk/moh/palosanto.com/prueba11/org_01_Pacific_Coast_Highway_quiet-4db.mp3",3);
    //print_r($arrFile);*/
    
	$action = getAction();
    $content = "";
    
	switch($action){
        case "new_rg":
            $content = viewFormMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "view":
            $content = viewFormMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "view_edit":
            $content = viewFormMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_new":
            $content = saveNewMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_edit":
            $content = saveEditMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "delete":
            $content = deleteMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        default: // report
            $content = reportMoH($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $domain);
            break;
    }
    return $content;

}

function reportMoH($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain)
{
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$pOMoHZ = new paloSantoOrganization($pDB2);

	$domain=getParameter("organization");
	if($userLevel1=="superadmin"){
        if(preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/",$domain)){
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
	      $pMoH = new paloSantoMoH($pDB,$domain);
	      $total=$pMoH->getNumMoH($domain);
	  }else{
	      $pMoH = new paloSantoMoH($pDB,$domain);
	      $total=$pMoH->getNumMoH();
	  }
	}else{
	    $pMoH = new paloSantoMoH($pDB,$domain);
	    $total=$pMoH->getNumMoH($domain);
	}

	if($total===false){
		$error=$pMoH->errMsg;
		$total=0;
	}

	$limit=20;

	$oGrid = new paloSantoGrid($smarty);
	$oGrid->setLimit($limit);
	$oGrid->setTotal($total);
	$offset = $oGrid->calculateOffset();

	$end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
	
	$oGrid->setTitle(_tr('MoH Class List'));
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
    $arrColum[]=_tr("Type");
    $arrColum[]=_tr("Sort");
    $arrColum[]=_tr("Directory");
    $arrColum[]=_tr("Aplication");
    $oGrid->setColumns($arrColum);
    
    
	$arrMoH=array();
	$arrData = array();
	if($userLevel1=="superadmin"){
	    if($domain!="all")
            $arrMoH = $pMoH->getMoHs($domain,$limit,$offset);
	    else
            $arrMoH = $pMoH->getMoHs(null,$limit,$offset);
	}else{
        if($userLevel1=="admin"){
            $arrMoH = $pMoH->getMoHs($domain,$limit,$offset);
        }
    }

	if($arrMoH===false){
		$error=_tr("Error to obtain MoH Class").$pMoH->errMsg;
        $arrMoH=array();
	}

	$arrData=array();
	foreach($arrMoH as $moh) {
        $arrTmp=array();
        if($userLevel1=="superadmin"){
            $arrTmp[0] = $moh["organization_domain"];
            if(empty($moh["organization_domain"]))
                $arrTmp[] = "&nbsp;<a href='?menu=$module_name&action=view&id_moh=".$moh["name"]."'>".$moh["description"]."</a>";
            else
                $arrTmp[] = $moh["description"];
        }else
            $arrTmp[0] = "&nbsp;<a href='?menu=$module_name&action=view&id_moh=".$moh["name"]."'>".$moh["description"]."</a>";
        
        $arrTmp[]=$moh["mode"];
        $arrTmp[]=$moh["sort"];
        $arrTmp[]=$moh["directory"];
        $arrTmp[]=$moh["application"];

        $arrData[] = $arrTmp;
    }
			

    if($userLevel1 != "other")
        $oGrid->addNew("create_moh",_tr("Create New Class MoH"));

    if($userLevel1 == "superadmin"){
        $arrOrgz=array("all"=>"all");
        foreach(($pOMoHZ->getOrganization(array())) as $value){
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
	
	$contenidoModulo = $oGrid->fetchGrid(array(), $arrData);
    return $contenidoModulo;
}

function viewFormMoH($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain,$arrFiles=array()){
	$error = "";

	$arrMoH=array();
	$action = getParameter("action");
       
	if($userLevel1=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    if($userLevel1=="admin"){
        $domain=$org_domain;
        if($domain==false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid Action"));
            return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
    }

	$idMoH=getParameter("id_moh");
	if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
		if(!isset($idMoH)){
            $error=_tr("Invalid Music on Hold Class");
		}else{
			if($userLevel1=="admin"){
                $pMoH = new paloSantoMoH($pDB,$domain);
				$arrMoH = $pMoH->getMoHByClass($idMoH);
			}else{
                $pMoH = new paloSantoMoH($pDB,"");
                $arrMoH = $pMoH->getMoHByClass($idMoH);
			}
		}
		
		$smarty->assign('NAME_MOH',$arrMoH["name"]);
		$smarty->assign('MODE_MOH',$arrMoH["mode_moh"]);
		
		if($error==""){
            if($arrMoH===false){
                $error=_tr($pMoH->errMsg);
            }else if(count($arrMoH)==0){
                $error=_tr("MoH doesn't exist");
            }else{
                $smarty->assign('j',0);
                $smarty->assign('items',$arrMoH["listFiles"]);
                if(getParameter("save_edit"))
                    $arrMoH=$_POST;
            }
        }
        
        if($error!=""){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",$error);
            return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
	}else{
        if($userLevel1=="admin"){
            $pMoH = new paloSantoMoH($pDB,$domain);
        }else{
            $pMoH = new paloSantoMoH($pDB,"");
        }
        
        $smarty->assign('j',0);
        $smarty->assign('items',$arrFiles);
        $smarty->assign('arrFiles',"1");
        
        $arrMoH=$_POST; 
	}
	
	$arrFormOrgz = createFieldForm($pMoH->getAudioFormatAsterisk());
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
	$smarty->assign("id_moh", $idMoH);
	$smarty->assign("userLevel",$userLevel1);
    $smarty->assign("ADD_FILE",_tr("Add New file"));
        
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("MoH Route"), $arrMoH);
    $content = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    
    return $content;
}

function saveNewMoH($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;

	if($userLevel1=="other"){
	    $smarty->assign("mb_title", _tr("ERROR"));
	    $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
	    return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
	
	if($userLevel1=="admin"){
        $domain=$org_domain;
        if($domain==false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid Action"));
            return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
    }
    
    $arrFormOrgz = createFieldForm(array());
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
        return viewFormMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else{
		$name = getParameter("name");
		if($name==""){
			$error=_tr("Field 'Name' can't be empty.");
			$continue=false;
		}
		
		if($continue){
			//seteamos un arreglo con los parametros configurados
			$arrProp=array();
			$arrProp["name"]=getParameter("name");
			$arrProp["mode"]=getParameter("mode_moh");
			$arrProp["application"]=getParameter("application");
			$arrProp["sort"]=getParameter("sort");
			$arrProp["format"]=getParameter("format");
		}

		if($continue){
			$pDB->beginTransaction();
			if($userLevel1=="admin"){
                $pMoH = new paloSantoMoH($pDB,$domain);
            }else{
                $pMoH = new paloSantoMoH($pDB,"");
            }
			$success=$pMoH->createNewMoH($arrProp);
			if($success){
				$pDB->commit();
				if($arrProp["mode"]=="files")
                    $pMoH->uploadFile($arrProp["name"]);
			}else{
				$pDB->rollBack();
            }
			$error .=$pMoH->errMsg;
		}
	}

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("New MoH Class has been created successfully")." ".$error);
		$content = reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	return $content;
}

function saveEditMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	
	$error = "";
	$success=false;
	$idMoH=getParameter("id_moh");

	if($userLevel1=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    if($userLevel1=="admin"){
        $domain=$org_domain;
        if($domain==false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid Action"));
            return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
    }
    
	//obtenemos la informacion del ring_group por el id dado, sino existe el ring_group mostramos un mensaje de error
	if(!isset($idMoH)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid MoH Class"));
		return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	
	if($userLevel1=="admin"){
        $pMoH = new paloSantoMoH($pDB,$domain);
        $arrMoH = $pMoH->getMoHByClass($idMoH);
    }else{
        $pMoH = new paloSantoMoH($pDB,"");
        $arrMoH = $pMoH->getMoHByClass($idMoH);
    }

	if($arrMoH===false){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($pMoH->errMsg));
		return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else if(count($arrMoH)==0){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("MoH doesn't exist"));
		return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
        $arrProp=array();
        $arrProp["class"]=$arrMoH["class"];
        $arrProp["application"]=getParameter("application");
        $arrProp["sort"]=getParameter("sort");
        $arrProp["format"]=getParameter("format");
        if(!isset($_POST['current_File']))
            $arrProp["remain_files"]=array();
        else
            $arrProp["remain_files"]=$_POST['current_File'];
	
        //rint_r($arrProp["remain_files"]);
        $pDB->beginTransaction();
        $success=$pMoH->updateMoHPBX($arrProp);
        
        if($success){
            $pDB->commit();
            if($arrMoH["mode_moh"]=="files")
                $pMoH->uploadFile($arrMoH["name"]);
        }else
            $pDB->rollBack();
        $error .=$pMoH->errMsg;
	}

	$smarty->assign("id_moh", $idMoH);

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("MoH Class has been edited successfully")." ".$error);
        $content = reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}
	return $content;
}

function deleteMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
	
	$error = "";
	//conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$continue=true;
	$success=false;
	$idMoH=getParameter("id_moh");
	
    if($userLevel1=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    if($userLevel1=="admin"){
        $domain=$org_domain;
        if($domain==false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid Action"));
            return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
    }

	if(!isset($idMoH)){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("Invalid MoH"));
		return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}else{
        if($userLevel1=="admin"){
            $pMoH = new paloSantoMoH($pDB,$domain);
        }else{
            $pMoH = new paloSantoMoH($pDB,"");
        }
	}

    $pDB->beginTransaction();
    $success = $pMoH->deleteMoH($idMoH);
    if($success)
        $pDB->commit();
    else
        $pDB->rollBack();
    $error .=$pMoH->errMsg;

	if($success){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		$smarty->assign("mb_message",_tr("MoH class was deleted successfully"));
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr($error));
	}

	return reportMoH($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);;
}


function createFieldForm($arrFormats)
{
    if(!is_array($arrFormats)){
        $arrFormats=array("WAV"=>"WAV","wav"=>"wav","ulaw"=>"ulaw","alaw"=>"alaw","sln"=>"sln","gsm"=>"gsm","g729"=>"g729");
    }
    $arrFormElements = array("name"     => array("LABEL"             => _tr('Class Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:300px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "mode_moh"     => array("LABEL"             => _tr("Type"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("files"=>"files", "custom"=>"custom"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "application" 	=> array("LABEL"             => _tr("Application"),
                                                    "REQUIRED"              => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:300px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sort"     => array("LABEL"             => _tr("Sort Music"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("alpha"=>"alpha", "random"=>"random"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "format"     => array("LABEL"             => _tr("Format"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrFormats,
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


function getAction(){
    if(getParameter("create_moh"))
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
    else
        return "report"; //cancel
}
?>
