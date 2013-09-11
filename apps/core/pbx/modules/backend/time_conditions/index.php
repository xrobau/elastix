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
	$idOrganization=$arrCredentiasls["id_organization"];
	$domain=$arrCredentiasls["domain"];

	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));
    
	$action = getAction();
    $content = "";
    
	switch($action){
        case "new_tc":
            $content = viewFormTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "view":
            $content = viewFormTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "view_edit":
            $content = viewFormTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_new":
            $content = saveNewTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "save_edit":
            $content = saveEditTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "delete":
            $content = deleteTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        case "reloadAasterisk":
            $content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1,$idOrganization,$domain);
                break;
        case "get_destination_category":
            $content = get_destination_category($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $domain);
            break;
        default: // report
            $content = reportTC($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $userLevel1, $userAccount, $domain);
            break;
    }
    return $content;

}

function reportTC($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain)
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
          $pTC = new paloSantoTC($pDB,$domain);
          $total=$pTC->getNumTC($domain);
      }else{
          $pTC = new paloSantoTC($pDB,$domain);
          $total=$pTC->getNumTC();
      }
    }else{
        $pTC = new paloSantoTC($pDB,$domain);
        $total=$pTC->getNumTC($domain);
    }

    if($total===false){
        $error=$pTC->errMsg;
        $total=0;
    }

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    
    $oGrid->setTitle(_tr('Time Conditions List'));
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
    $arrColum[]=_tr("Time Group");
    $arrColum[]=_tr("Destination Match");
    $arrColum[]=_tr("Destination Fail");
    $oGrid->setColumns($arrColum);
    
    $arrTC=array();
    $arrData = array();
    if($userLevel1=="superadmin"){
        if($domain!="all")
            $arrTC = $pTC->getTCs($domain);
        else
            $arrTC = $pTC->getTCs();
    }else{
        if($userLevel1=="admin"){
            $arrTC = $pTC->getTCs($domain);
        }
    }

    if($arrTC===false){
        $error=_tr("Error to obtain Time Conditions").$pTC->errMsg;
        $arrTC=array();
    }

    foreach($arrTC as $tc) {
        $arrTmp=array();
        if($userLevel1=="superadmin"){
            $arrTmp[] = $tg["organization_domain"];
            $arrTmp[] = $tg["name"];
        }else
            $arrTmp[] = "&nbsp;<a href='?menu=$module_name&action=view&id_tc=".$tc['id']."'>".$tc['name']."</a>";
        
        $arrTmp[] = $tc["tg_name"];
        $arrTmp[] = $tc["destination_m"];
        $arrTmp[] = $tc["destination_f"];
        $arrData[] = $arrTmp;
    }
            
    if($pORGZ->getNumOrganization(array()) >= 1){
        if($userLevel1 == "admin")
            $oGrid->addNew("create_tc",_tr("Create New Time Conditions"));
            

        if($userLevel1 == "superadmin"){
            $arrOrgz=array("all"=>"all");
            foreach(($pORGZ->getOrganization(array())) as $value){
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
        $smarty->assign("mb_message",_tr("It's necesary you create a new organization so you can create new Time Conditions"));
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
    //si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de marcado
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

function viewFormTC($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);

    $arrTC=array();
    $action = getParameter("action");
       
    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }

    $idTC=getParameter("id_tc");
    if($action=="view" || getParameter("edit") || getParameter("save_edit")){
        if(!isset($idTC)){
            $error=_tr("Invalid Time Conditions");
        }else{
            if($userLevel1=="admin"){
                $pTC = new paloSantoTC($pDB,$domain);
                $arrTC = $pTC->getTCById($idTC);
            }else{
                $error=_tr("You are not authorized to perform this action");
            }
        }
        
        if($error==""){
            if($arrTC===false){
                $error=_tr($pTC->errMsg);
            }else if(count($arrTC)==0){
                $error=_tr("TC doesn't exist");
            }else{
                if(getParameter("save_edit"))
                    $arrTC=$_POST;
            }
        }
        
        if($error!=""){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",$error);
            return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
        }
    }else{
        $pTC = new paloSantoTC($pDB,$domain);
        if(getParameter("create_tc")){
            $arrTC["goto_m"]="";
            $arrTC["goto_f"]="";
        }else
            $arrTC=$_POST; 
    }
    
    $goto=$pTC->getCategoryDefault($domain);
    if($goto===false)
        $goto=array();
    $res1=$pTC->getDefaultDestination($domain,$arrTC["goto_m"]);
    $destiny1=($res1==false)?array():$res1;
    $res2=$pTC->getDefaultDestination($domain,$arrTC["goto_f"]);
    $destiny2=($res2==false)?array():$res2;
    $arrForm = createFieldForm($goto,$destiny1,$destiny2,$pTC->getTimeGroup());
    $oForm = new paloForm($smarty,$arrForm);

    if($action=="view"){
        $oForm->setViewMode();
    }else if(getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
    }
    
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("OPTIONS", _tr("Options"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("MODULE_NAME",$module_name);
    $smarty->assign("id_tc", $idTC);
    $smarty->assign("userLevel",$userLevel1);
    $smarty->assign("SETDESTINATION_M", _tr("Destination If Match"));
    $smarty->assign("SETDESTINATION_F", _tr("Destination If Fail"));
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("TC Route"), $arrTC);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewTC($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $continue=true;
    $success=false;

    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $pTC = new paloSantoTC($pDB,$domain);
    $goto=$pTC->getCategoryDefault($domain);

    //validations parameters
    $name = getParameter("name");
    if($name==""){
        $error=_tr("Field Name can not be empty.");
        $continue=false;
    }
    
    if($pTC->validateDestine($domain,getParameter("destination_m"))==false){
        $error=_tr("You must select a destination if match");
        $continue=false;
    }
    
    if($pTC->validateDestine($domain,getParameter("destination_f"))==false){
        $error=_tr("You must select a destination if fail");
        $continue=false;
    }
            
    if($continue){
        //seteamos un arreglo con los parametros configurados
        $arrProp=array();
        $arrProp["name"]=getParameter("name");
        $arrProp['id_tg']=getParameter("id_tg");
        $arrProp['goto_m']=getParameter("goto_m");
        $arrProp['destination_m']=getParameter("destination_m");
        $arrProp['goto_f']=getParameter("goto_f");
        $arrProp['destination_f']=getParameter("destination_f");
    }

    if($continue){
        $pDB->beginTransaction();
        $success=$pTC->createNewTC($arrProp);
        if($success)
            $pDB->commit();
        else
            $pDB->rollBack();
        $error .=$pTC->errMsg;
    }

    if($success){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("TC has been created successfully"));
         //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    return $content;
}

function saveEditTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
    
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $continue=true;
    $success=false;
    $idTC=getParameter("id_tc");

    if($userLevel1!="admin"){
      $smarty->assign("mb_title", _tr("ERROR"));
      $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
      return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    $domain=$org_domain;
    if($domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Action"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    
    if(!isset($idTC)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid TC"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }

    $pTC = new paloSantoTC($pDB,$domain);
    $arrTC = $pTC->getTCById($idTC, $domain);
    if($arrTC===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pTC->errMsg));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else if(count($arrTC)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("TC doesn't exist"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else{
        //validations parameters
        $name = getParameter("name");
        if($name==""){
            $error=_tr("Field Name can not be empty.");
            $continue=false;
        }
        
        if($pTC->validateDestine($domain,getParameter("destination_m"))==false){
            $error=_tr("You must select a destination if match");
            $continue=false;
        }
        
        if($pTC->validateDestine($domain,getParameter("destination_f"))==false){
            $error=_tr("You must select a destination if fail");
            $continue=false;
        }
        
        if($continue){
            //seteamos un arreglo con los parametros configurados
            $arrProp=array();
            $arrProp['id']=$idTC;
            $arrProp["name"]=getParameter("name");
            $arrProp['id_tg']=getParameter("id_tg");
            $arrProp['goto_m']=getParameter("goto_m");
            $arrProp['destination_m']=getParameter("destination_m");
            $arrProp['goto_f']=getParameter("goto_f");
            $arrProp['destination_f']=getParameter("destination_f");
        }

        if($continue){
            $pDB->beginTransaction();
            $success=$pTC->updateTCPBX($arrProp);
            if($success)
                $pDB->commit();
            else
                $pDB->rollBack();
            $error .=$pTC->errMsg;
        }
    }

    $smarty->assign("id_tc", $idTC);

    if($success){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("TC has been edited successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }
    return $content;
}

function deleteTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain){
    
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $continue=true;
    $success=false;
    $idTC=getParameter("id_tc");

    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }

    $domain=$org_domain;
    if(!isset($idTC) || $domain==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid TC"));
        return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
    }

    $pTC=new paloSantoTC($pDB,$domain);
    $pDB->beginTransaction();
    $success = $pTC->deleteTC($idTC);
    if($success)
        $pDB->commit();
    else
        $pDB->rollBack();
    $error .=$pTC->errMsg;

    if($success){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("The TC was deleted successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
        $pAstConf->setReloadDialplan($domain,true);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($error));
    }

    return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);;
}

function get_destination_category($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $domain){
    $jsonObject = new PaloSantoJSON();
    $categoria=getParameter("option");
    //conexion elastix.db
    $pTC = new paloSantoTC($pDB,$domain);
    if($domain==false){
        $jsonObject->set_error(_tr("Organization doesn't exist. "));
    }else{
        $arrDestine=$pTC->getDefaultDestination($domain,$categoria);
        if($arrDestine==FALSE){
            $jsonObject->set_error(_tr($pIVR->errMsg));
        }else{
            $jsonObject->set_message($arrDestine);
        }
    }
    return $jsonObject->createJSON();
}

function createFieldForm($goto,$destination1,$destination2,$time_group)
{
    
    $arrFormElements = array("name"	=> array("LABEL"             => _tr('Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "id_tg" => array("LABEL"               => _tr("Time Group"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $time_group,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "goto_m"   => array("LABEL"             => _tr("Destine"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $goto,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                             "destination_m"   => array("LABEL"             => _tr(""),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $destination1,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                             "goto_f"   => array("LABEL"             => _tr("Destine"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $goto,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""), 
                             "destination_f"   => array("LABEL"             => _tr(""),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $destination2,
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
		return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization=="1"){
		return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
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

	return reportTC($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $org_domain);
}

function getAction(){
    if(getParameter("create_tc"))
        return "new_tc";
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
    else if(getParameter("action")=="reloadAsterisk")
        return "reloadAasterisk";
    else if(getParameter("action")=="get_destination_category")
        return "get_destination_category";
    else
        return "report"; //cancel
}
?>
