<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.3-2                                               |
  | http://www.elastix.com                                               |
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
  $Id: index.php,v 1.2 2010-11-29 15:09:50 Eduardo Cueva ecueva@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";
include_once "libs/paloSantoConfig.class.php";


function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoVoIPProvider.class.php";
    include_once "modules/$module_name/libs/paloSantoVP.class.php";
    include_once "libs/paloSantoACL.class.php";

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

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    $dns = generarDSNSistema('asteriskuser', 'asterisk');
    $pDB2 = new paloDB($dns);
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP = $pConfig->leer_configuracion(false);
    $dsn_agi_manager['password'] = $arrAMP['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrAMP['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = $arrAMP['AMPMGRUSER']['valor'];

    $pConfig2 = new paloConfig($arrAMP['ASTETCDIR']['valor'], "asterisk.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAST  = $pConfig2->leer_configuracion(false);
    //actions
    $action = getAction();
    $content = "";
    deleteNonExistentTrunks($pDB,$pDB2,$smarty);
    switch($action){
        case "view_new":
            $content = newFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "save_new":
            $content = saveNewVoIPProvider($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST);
            break;
        case "view_edit":
            $content = editFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager);
            break;
        case "save_edit":
            $content = saveEditVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST);
            break;
        case "delete":
            $content = deleteVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST);
            break;
        case "getInfoProvider":
            $content = getInfoVoIPProviderAccount($module_name, $pDB, $arrConf);
            break;
		case "activate":
			$content = activateVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST);
            break;
        default: // report
            $content = reportVoIPProvider($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function newFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $arrFormVoIPProvider = createFieldForm($pVoIPProvider);
    $oForm = new paloForm($smarty,$arrFormVoIPProvider);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");
    $smarty->assign("ID", $id); //persistence id with input hidden in tpl
    $smarty->assign("Module_name", $module_name);

    if($action=="view")
        $oForm->setViewMode();
    else if($action=="view_edit" || getParameter("save_edit"))
        $oForm->setEditMode();
    //end, Form data persistence to errors and other events.

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
        $dataVoIPProvider = $pVoIPProvider->getVoIPProviderAccountById($id);
        if(is_array($dataVoIPProvider) & count($dataVoIPProvider)>0){
            $name = $pVoIPProvider->getVoIPProviderById($dataVoIPProvider['id_provider']);
            $_DATA['type_provider_voip'] = $name;
            $_DATA = $dataVoIPProvider;
        }else{
            $smarty->assign("mb_title", _tr("Error get Data"));
            $smarty->assign("mb_message", $pVoIPProvider->errMsg);
        }
    }
    
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("General_Setting", _tr("General_Setting"));
    $smarty->assign("PEER_Details", _tr("PEER_Details"));
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("VoIP Provider"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewVoIPProvider($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $arrFormVoIPProvider = createFieldForm($pVoIPProvider);
    $oForm = new paloForm($smarty,$arrFormVoIPProvider);


    if(!$oForm->validateForm($_POST)) {
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        $arrErrores = $oForm->arrErroresValidacion;
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
            }
        }

        $smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", $strErrorMsg);

        return newFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }else {
        $type_provider = getParameter('type_provider_voip');
        $technology    = getParameter("technology");
        $id_provider   = null;
        $arrData       = getAllDataPOST();
     
        if($type_provider!="custom"){
            $arrProvider = $pVoIPProvider->getIdVoIPProvidersByName($type_provider);
            if(count($arrProvider)>0){
                $technology  = $arrProvider['type_trunk'];
                $id_provider = $arrProvider['id'];
            }
        }

        $arrData[] = $technology;
        $arrData[] = $id_provider;
            
        $pVP       = new paloSantoVP($pDB2);
        $id_trunk  = $pVP->getIdNextTrunk();
        $exito = $pVP->saveTrunk($arrData);
        if($exito){

            if(!$pVoIPProvider->insertAccount($arrData,$id_trunk)){
                $smarty->assign("mb_title", _tr("Validation Error"));
                $strErrorMsg  = "<b>"._tr('Internal Error')."</b><br/>".$pVoIPProvider->errMsg;
                $smarty->assign("mb_message", $strErrorMsg);
                return newFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            }
            else{
                //escritura en archivos de asterisk
                //$pVoIPProvider->setAsteriskFiles($dsn_agi_manager);
                $data_connection = array('host' =>  $dsn_agi_manager['host'], 'user' => $dsn_agi_manager['user'], 'password' => $dsn_agi_manager['password']);
                if($pVP->do_reloadAll($data_connection, $arrAST, $arrAMP)){
                    $smarty->assign("mb_title", _tr("Message"));
                    $smarty->assign("mb_message", _tr("The account was created successfully"));
                }
                else{
                    $smarty->assign("mb_title", _tr("ERROR"));
                    $smarty->assign("mb_message", $pVP->errMsg);
                }
                return reportVoIPProvider($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            }
        }
        else{
            $smarty->assign("mb_title", _tr("ERROR"));
            $strErrorMsg  = "<b>"._tr('Internal Error')."</b><br/>".$pVP->errMsg;
            $smarty->assign("mb_message", $strErrorMsg);
            return newFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        }
    }
}

function editFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $arrFormVoIPProvider = createFieldForm($pVoIPProvider);
    $oForm = new paloForm($smarty,$arrFormVoIPProvider);

    //begin, Form data persistence to errors and other events.
    $action = getParameter("action");
    $id     = getParameter("id");
    $id_trunk = getParameter("id_trunk");
    $smarty->assign("ID", $id); //persistence id with input hidden in tpl
    $smarty->assign("ID_TRUNK", $id_trunk);
    $dataVoIPProvider = $pVoIPProvider->getVoIPProviderAccountById($id);
    $name   = $pVoIPProvider->getVoIPProviderById($dataVoIPProvider['id_provider']);
    $_DATA  = $_POST;
    if($action=="view")
        $oForm->setViewMode();
    else if($action=="view_edit" || getParameter("save_edit"))
        $oForm->setEditMode();
    //end, Form data persistence to errors and other events.

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
        if(is_array($dataVoIPProvider) & count($dataVoIPProvider)>0){
            $_DATA = $dataVoIPProvider;
            $_DATA['type_provider_voip'] = $name;
        }else{
            $smarty->assign("mb_title", _tr("Error get Data"));
            $smarty->assign("mb_message", $pVoIPProvider->errMsg);
        }
    }

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("Module_name", $module_name);
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("General_Setting", _tr("General_Setting"));
    $smarty->assign("PEER_Details", _tr("PEER_Details"));
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("VoIP Provider"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveEditVoIPProviderAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $arrFormVoIPProvider = createFieldForm($pVoIPProvider);
    $oForm = new paloForm($smarty,$arrFormVoIPProvider);

    if(!$oForm->validateForm($_POST)) {
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        $arrErrores = $oForm->arrErroresValidacion;
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
            }
        }

        $smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", $strErrorMsg);
        return editFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager);
    }
    else{
        $type_provider = getParameter('type_provider_voip');
        $technology    = getParameter("technology");
        $statusAct     = getParameter("status");
        $id            = getParameter("id");
        $id_trunk      = getParameter("idTrunk");
        $arrData       = getAllDataPOST();
        if(empty($technology)){
            $dataVoIPProvider = $pVoIPProvider->getVoIPProviderAccountById($id);
            $technology       = $dataVoIPProvider['technology'];
        }
        $arrData[] = $technology;
        $arrData[] = $statusAct;
        $arrData[] = $id;
        $pVP       = new paloSantoVP($pDB2);
        if($pVP->updateTrunk($arrData,$id_trunk)){
            if(!$pVoIPProvider->updateAccount($arrData)){
                $smarty->assign("mb_title", _tr("Validation Error"));
                $strErrorMsg  = "<b>"._tr('Internal Error')."</b><br/>".$pVoIPProvider->errMsg;
                $smarty->assign("mb_message", $strErrorMsg);
                return editFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $dsn_agi_manager);
            }
            else{
                //escritura en archivos de asterisk
                //$pVoIPProvider->setAsteriskFiles($dsn_agi_manager);
                $data_connection = array('host' =>  $dsn_agi_manager['host'], 'user' => $dsn_agi_manager['user'], 'password' => $dsn_agi_manager['password']);
                if($pVP->do_reloadAll($data_connection, $arrAST, $arrAMP)){
                    $smarty->assign("mb_title", _tr("Message"));
                    $smarty->assign("mb_message", _tr("The account was edited successfully"));
                }
                else{
                    $smarty->assign("mb_title", _tr("ERROR"));
                    $smarty->assign("mb_message", $pVP->errMsg);
                }
                return reportVoIPProvider($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            }
        }
        else{
            $smarty->assign("mb_title", _tr("Validation Error"));
            $strErrorMsg  = "<b>"._tr('Internal Error')."</b><br/>".$pVP->errMsg;
            $smarty->assign("mb_message", $strErrorMsg);
            return newFormVoIPProviderAccount($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        }
    }
}

function getInfoVoIPProviderAccount($module_name, &$pDB, $arrConf)
{
    $jsonObject      = new PaloSantoJSON();
    $pVoIPProvider   = new paloSantoVoIPProvider($pDB);
    $nameProvider    = getParameter("type_provider");
    $response        = $pVoIPProvider->getInfoVoIPProvidersByName($nameProvider);
    $pACL            = new paloACL($arrConf['ACLdb']);
    $user            = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $esAdministrador = $pACL->isUserAdministratorGroup($user);

    if($esAdministrador){
        $msgResponse['type']        = $response['type'];
        $msgResponse['qualify']     = $response['qualify'];
        $msgResponse['insecure']    = $response['insecure'];
        $msgResponse['host']        = $response['host'];
        $msgResponse['fromuser']    = $response['fromuser'];
        $msgResponse['fromdomain']  = $response['fromdomain'];
        $msgResponse['dtmfmode']    = $response['dtmfmode'];
        $msgResponse['disallow']    = $response['disallow'];
        $msgResponse['context']     = $response['context'];
        $msgResponse['allow']       = $response['allow'];
        $msgResponse['trustrpid']   = $response['trustrpid'];
        $msgResponse['sendrpid']    = $response['sendrpid'];
        $msgResponse['canreinvite'] = $response['canreinvite'];
        $msgResponse['type_trunk']  = $response['type_trunk'];
    }else{
        $msgResponse = array();
    }

    $jsonObject->set_message($msgResponse);
    return $jsonObject->createJSON();
}

function deleteVoIPProviderAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $pACL            = new paloACL($arrConf['ACLdb']);
    $user            = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $esAdministrador = $pACL->isUserAdministratorGroup($user);
    if($esAdministrador){
        $result = "";
        $pVP     = new paloSantoVP($pDB2);
        foreach($_POST as $key => $values){
            $tmp = explode("_",$key);
            if($tmp[0] == "account")
                if($pVP->deleteTrunk($tmp[2]))
                    $pVoIPProvider->deleteAccount($tmp[1]);
        }
        //escritura en archivos de asterisk
        //$pVoIPProvider->setAsteriskFiles($dsn_agi_manager);
        $data_connection = array('host' =>  $dsn_agi_manager['host'], 'user' => $dsn_agi_manager['user'], 'password' => $dsn_agi_manager['password']);
        if($pVP->do_reloadAll($data_connection, $arrAST, $arrAMP)){
            $smarty->assign("mb_title", _tr("Message"));
            $smarty->assign("mb_message", _tr("The account was deleted successfully"));
        }
        else{
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message", $pVP->errMsg);
        }
    }else{
        $smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("User is not allowed to do this operation"));
    }
    return reportVoIPProvider($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);

}

function activateVoIPProviderAccount($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $dsn_agi_manager, $pDB2, $arrAMP, $arrAST)
{
    $pVoIPProvider   = new paloSantoVoIPProvider($pDB);
    $pACL            = new paloACL($arrConf['ACLdb']);
    $user            = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $esAdministrador = $pACL->isUserAdministratorGroup($user);
    if($esAdministrador){
	$id      = getParameter("id");
	$arrData = $pVoIPProvider->getVoIPProviderAccountById($id);
	$status  = "";  
        $pVP     = new paloSantoVP($pDB2);
        $id_trunk = getParameter("id_trunk");
	if($arrData['status']=="desactivate"){
	    if($pVP->disableTrunk($id_trunk,'off'))
		$status = "activate";
	    else
		$status = "desactivate";
        }
	else{
            if($pVP->disableTrunk($id_trunk,'on'))
		$status = "desactivate";
            else
                $status = "activate";
        }	
	$sal = $pVoIPProvider->changeStatus($id, $status);
	if($sal){
            $data_connection = array('host' =>  $dsn_agi_manager['host'], 'user' => $dsn_agi_manager['user'], 'password' => $dsn_agi_manager['password']);
            if($pVP->do_reloadAll($data_connection, $arrAST, $arrAMP)){
                $smarty->assign("mb_title", _tr("Message"));
                $smarty->assign("mb_message", _tr("The account was $status successfully"));
            }
            else{
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message", $pVP->errMsg);
            }
			//$pVoIPProvider->setAsteriskFiles($dsn_agi_manager);
	}else{
	    $smarty->assign("mb_title", _tr("ERROR"));
	    $smarty->assign("mb_message", _tr("Internal Error"));
	}
    }else{
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("User is not allowed to do this operation"));
    }
    return reportVoIPProvider($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function reportVoIPProvider($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");
    $filter_valueTMP = $filter_value;
    $filterValue = "";
    $allowSelection = array("provider", "account_name");
    if(isset($filter_value) & $filter_value !=""){
        if(!in_array($filter_field, $allowSelection))
            $filter_field = "provider";
        $filterValue = $filter_value;
        $filter_value    = $pDB->DBCAMPO('%'.$filter_value.'%');
    }

    $url = array(
        'menu'          =>  $module_name,
        'filter_field'  =>  $filter_field,
        'filter_value'  =>  $filter_valueTMP,
    );

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->enableExport();   // enable csv export.
    $oGrid->pagingShow(true); // show paging section.
    $oGrid->setTitle(_tr("VoIP Provider"));
    $oGrid->setNameFile_Export("VoIP_Provider");
    $oGrid->setURL($url);
    $oGrid->addNew("new_account",_tr("New Account"));
    $oGrid->deleteList("Are you sure you wish to delete the accounts selected.?","delete",_tr("Delete"));

    $totalVoipProviders = $pVoIPProvider->getNumVoIPProvider($filter_field, $filter_value);

    $arrData = null;
    if($oGrid->isExportAction()) {
        $limit  = $totalVoipProviders;
        $offset = 0;

        $arrResult =$pVoIPProvider->getVoIPProviderData($limit, $offset, $filter_field, $filter_value);

        if(is_array($arrResult) && $totalVoipProviders>0){
            foreach($arrResult as $key => $value){
                $arrTmp[0] = $value['account_name'];
                if(isset($value['id_provider']) && $value['id_provider'] != ""){
                    $name = $pVoIPProvider->getVoIPProviderById($value['id_provider']);
                    $arrTmp[1] = $name['name'];
                }else
                    $arrTmp[1] = _tr("Custom");
                $arrTmp[2] = $value['callerID'];
                $arrTmp[3] = $value['type_trunk'];
                if($value['status'] == "activate")
                    $arrTmp[4] = _tr('Enable');
                else
                    $arrTmp[4] = _tr('Disable');
                $arrData[] = $arrTmp;
            }
        }

        $arrColumns  = array(_tr("Account Name"), _tr("VoIP Provider"), _tr("Outbound CallerID"), _tr("Type Trunk"), _tr("Status"));
    }
    else{
        $limit  = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($totalVoipProviders);
        $offset = $oGrid->calculateOffset();

        $arrResult =$pVoIPProvider->getVoIPProviderData($limit, $offset, $filter_field, $filter_value);

        if(is_array($arrResult) && $totalVoipProviders>0){
            foreach($arrResult as $key => $value){
                $arrTmp[0] = "<input type='checkbox' name='account_{$value['id']}_{$value['id_trunk']}'  />";
                $arrTmp[1] = $value['account_name'];
                if(isset($value['id_provider']) && $value['id_provider'] != ""){
                    $name = $pVoIPProvider->getVoIPProviderById($value['id_provider']);
                    $arrTmp[2] = $name['name'];
                }else
                    $arrTmp[2] = _tr("Custom");
                $arrTmp[3] = $value['callerID'];
                $arrTmp[4] = $value['type_trunk'];
                if($value['status'] == "activate")
                    $arrTmp[5] = "<a href=?menu=$module_name&action=activate&id={$value['id']}&id_trunk={$value['id_trunk']}>"._tr('Disable')."</a>";
                else
                    $arrTmp[5] = "<a href=?menu=$module_name&action=activate&id={$value['id']}&id_trunk={$value['id_trunk']}>"._tr('Enable')."</a>";
                $arrTmp[6] = "<a href=?menu=$module_name&action=view_edit&id={$value['id']}&id_trunk={$value['id_trunk']}>"._tr('Edit')."</a>";
                $arrData[] = $arrTmp;
            }
        }

        $arrColumns  = array("", _tr("Account Name"), _tr("VoIP Provider"), _tr("Outbound CallerID"), _tr("Type Trunk"), _tr("Status"), _tr("Edit"));
    }

    $oGrid->setColumns($arrColumns);
    $oGrid->setData($arrData);

    //begin section filter
    $arrFormFilterprueba = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormFilterprueba);
    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("Module_name", $module_name);
    if(!is_null($filter_field)){
        $valueFilterField = $arrFormFilterprueba["filter_field"]["INPUT_EXTRA_PARAM"][$filter_field];
    }else{
        $valueFilterField = "";
    }

    $oGrid->addFilterControl(_tr("Filter applied: ")."$valueFilterField = $filterValue", $_POST, array("filter_field" => "", "filter_value"=>""));
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}

function createFieldForm($pVoIPProvider)
{
    $result = $pVoIPProvider->getVoIPProviders();//Obtiene la lista para ser seteado en el listbox
    foreach($result as $values){
        $arrProviders[$values['name']] = $values['name'];
    }
    $arrProviders["custom"] = _tr("Custom");
    $arrSelectForm = array("no" => _tr("no"), "yes" => _tr("yes"));
	$arrStatus     = array("activate" => _tr("Enable"), "desactivate" => _tr("Disable"));
    $arrSelectTech = array("SIP" => "SIP", "IAX2" => "IAX2");
	$arrSelectType = array("friend" => "friend", "peer" => "peer");

    $arrSelectCareInvite = array("no" => _tr("no"), "yes" => _tr("yes"), "nonat" => "nonat", "update" => "update");
    $arrSelectInsecure   = array("very" => "very", "yes" => "yes", "no" => "no", "invite" => "invite", "port" => "port");
    $arrSelectdtmf       = array("rfc2833" => "rfc2833", "inband" => "inband", "info" => "info", "auto" => "auto");

    $arrFields = array(
            "type_provider_voip"   => array(      "LABEL"            => _tr("VoIP Provider"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrProviders,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "",
                                            ),
			"status"   => array(      "LABEL"          				 => _tr("Status"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrStatus,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "",
                                            ),
            "account_name"   => array(      "LABEL"                  => _tr("Account Name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "account_name", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "username"   => array(      "LABEL"                  => _tr("Username"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "username", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "secret"   => array(      "LABEL"                  => _tr("Secret"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "secret", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "callerID"   => array(          "LABEL"                  => _tr("Outbound CallerID"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "callerID", "size" => "20"),
                                            "VALIDATION_TYPE"        => "numeric",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "type"   => array(      "LABEL"                  => _tr("Type"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectType,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "qualify"   => array(      "LABEL"                  => _tr("Qualify"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectForm,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "insecure"   => array(      "LABEL"                  => _tr("Insecure"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectInsecure,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "host"   => array(      "LABEL"                  => _tr("Host"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "host", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "fromuser"   => array(      "LABEL"                  => _tr("Fromuser"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "fromuser", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "fromdomain"   => array(      "LABEL"                  => _tr("Fromdomain"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "fromdomain", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "dtmfmode"   => array(      "LABEL"                  => _tr("Dtmfmode"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectdtmf,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "disallow"   => array(      "LABEL"                  => _tr("Disallow"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "disallow", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "context"   => array(      "LABEL"                  => _tr("Context"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "context", "size" => "20"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "allow"   => array(      "LABEL"                  => _tr("Allow"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("id" => "allow", "size" => "20"),
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "trustrpid"   => array(      "LABEL"                  => _tr("Trustrpid"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectForm,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "sendrpid"   => array(      "LABEL"                  => _tr("Sendrpid"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectForm,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "canreinvite"   => array(      "LABEL"                  => _tr("Canreinvite"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectCareInvite,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "technology"   => array(      "LABEL"                  => _tr("Technology"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelectTech,
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "advanced"     => array(      "LABEL"                  => _tr("Advanced"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            );
    return $arrFields;
}

function createFieldFilter(){
    $arrFilter = array(
        "account_name" => _tr("Account Name"),
        "provider" => _tr("VoIP Provider"),
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Search"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function deleteNonExistentTrunks(&$pDB, $pDB2, $smarty)
{
    $pVoIPProvider = new paloSantoVoIPProvider($pDB);
    $pVP    	   = new paloSantoVP($pDB2);
    $trunks 	   = $pVoIPProvider->getAllTrunks();
    if(is_array($trunks) && count($trunks)>0){
	foreach($trunks as $trunk){
	    $exist = $pVP->trunkExists($trunk['id_trunk']);
	    if(!isset($exist)){
		 $smarty->assign("mb_title", _tr("ERROR"));
		 $smarty->assign("mb_message", $pVP->errMsg);
		 break;
	    }
	    elseif(!$exist)
		 $pVoIPProvider->deleteAccount($trunk['id']);
	}
    }
}

function getAllDataPOST()
{
    $arrData[] = getParameter("account_name");
    $arrData[] = getParameter("username");
    $arrData[] = getParameter("secret");
    $arrData[] = getParameter("callerID");
    $arrData[] = getParameter("type");
    $arrData[] = getParameter("qualify");
    $arrData[] = getParameter("insecure");
    $arrData[] = getParameter("host");
    $arrData[] = getParameter("fromuser");
    $arrData[] = getParameter("fromdomain");
    $arrData[] = getParameter("dtmfmode");
    $arrData[] = getParameter("disallow");
    $arrData[] = getParameter("context");
    $arrData[] = getParameter("allow");
    $arrData[] = getParameter("trustrpid");
    $arrData[] = getParameter("sendrpid");
    $arrData[] = getParameter("canreinvite");
    return $arrData;
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_account")) 
        return "view_new";
    else if(getParameter("action")=="getInfoProvider")      //Get parameter by GET (command pattern, links)
        return "getInfoProvider";
    else if(getParameter("action")=="view_edit")
        return "view_edit";
	else if(getParameter("action")=="activate")
        return "activate";
    else
        return "report"; //cancel
}
?>
