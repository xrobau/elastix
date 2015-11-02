<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.2 2007/08/10 01:32:53 gcarrillo Exp $
  $Id: index.php,v 1.3 2011/06/21 17:30:33 Eduardo Cueva ecueva@palosanto.com Exp $ */


function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoEmail.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/cyradm.php";
    include_once "configs/email.conf.php";
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pDB = new paloDB($arrConf['dsn_conn_database']);

    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $virtual_postfix = FALSE; // indica si se debe escribir el archivo /etc/postfix/virtual

    $content = "";
    $accion = getAction();
    switch($accion)
    {

        case "submit_create_domain":
            $content = newDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
	case "save":
	    $content = saveDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "delete":
	    $content = deleteDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $virtual_postfix);
	    break;
	case "edit":
	    $content = newDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "view":
	    $content = newDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
        default:
            $content = viewFormDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }

    return $content;

}


function viewFormDomain($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $oGrid  = new paloSantoGrid($smarty);
    $arrDomains = $pEmail->getDomains();
    $end = count($arrDomains);

    $arrData = array();
    $oGrid->pagingShow(true);
    $url = array("menu" => $module_name);
    $oGrid->setURL($url);
    $oGrid->setTitle(_tr("Domain List"));
    $oGrid->setIcon("modules/$module_name/images/email_domains.png");
    $arrColumns = array(_tr("Domain"),_tr("Number of Accounts"),);
    $oGrid->setColumns($arrColumns);
    $total = 0;
    $limit  = 20;
    $limitInferior = "";
    $limitSuperior = "";
    $oGrid->setLimit($limit);
    $oGrid->addNew("submit_create_domain",_tr('Create Domain'));
    if(is_array($arrDomains) && $end>0){
	$oGrid->setTotal($end);
	$offset = $oGrid->calculateOffset();
	$cont = 0;
        $limitInferior = $offset;
	$limitSuperior = $offset + $limit -1;
	foreach($arrDomains as $domain) {
	    $arrTmp = array();
	    if($cont > $limitSuperior)
		break;
	    if($cont >= $limitInferior & $cont <= $limitSuperior){
		$arrTmp[0] = "&nbsp;<a href='?menu=email_domains&action=view&id=".$domain[0]."'>$domain[1]</a>";
		//obtener el numero de cuentas que posee ese email
		$arrTmp[1] = $pEmail->getNumberOfAccounts($domain[0]);
		$arrData[] = $arrTmp;
	    }
	    $cont++;
	}
    }else{
	$oGrid->setTotal($total);
	$offset = $oGrid->calculateOffset();
    }

    $oGrid->setData($arrData);
    $content = $oGrid->fetchGrid();
    return $content;
}


function newDomain($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $arrFormElements = createFieldForm();
    $oForm = new paloForm($smarty, $arrFormElements);

    $_DATA  = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");

    if($action=="view" && !getParameter("submit_create_domain"))
        $oForm->setViewMode();
    else
	$action = "new";
    //else if($action=="view_edit" || getParameter("save_edit"))
        //$oForm->setEditMode();

    $formValues['domain_name']='';

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
	 $arrDomain = $pEmail->getDomains($id);
        // Conversion de formato
        $arrTmp['domain_name']  = $arrDomain[0][1];
        $arrTmp['id_domain']    = $arrDomain[0][0];
	$formValues = $arrTmp;
    }

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));

    $content = $oForm->fetchForm("$local_templates_dir/form_domain.tpl", _tr("New Domain"),$formValues);
    return $content;
}


function deleteDomain($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $virtual_postfix)
{
    $pEmail = new paloEmail($pDB);
    $id     = (int)getParameter("id");
    $arrDomain = $pEmail->getDomains($id);

    if (!is_array($arrDomain) || count($arrDomain) <= 0) {
    	$smarty->assign('mb_title', _tr('Error'));
        $smarty->assign('mb_message', _tr('Domain not found by ID'));
    } else {
        $sNombreDominio  = $arrDomain[0][1];

        /*** preguntar si el domino que se desea eliminar tiene cuentas o listas de correos creadas ***/
        $arrList = $pEmail->getListByDomain($id);
        $arrAccounts = $pEmail->getAccountsByDomain($id);

        if (is_array($arrList) && count($arrList) > 0) {
            /*** 1) Existen listas creadas asignadas a ese dominio **/
            $smarty->assign("mb_title",_tr("Error"));
            $smarty->assign("mb_message", _tr("Please before to delete a domain delete all email lists asociated to ").$sNombreDominio);
        } elseif(is_array($arrAccounts) && count($arrAccounts) > 0) {
            /*** 2) Existen creada cuentas de correos que corresponden a ese dominio ***/
            $smarty->assign("mb_title",_tr("Error"));
            $smarty->assign("mb_message", _tr("Please before to delete a domain delete all email accounts asociated to ").$sNombreDominio);
        } else {
            $bExito = $pEmail->deleteDomain($sNombreDominio);
            if (!$bExito) {
        	   $smarty->assign("mb_title",_tr("Error"));
        	   $smarty->assign("mb_message", $pEmail->errMsg);
            } else {
        	   $smarty->assign("mb_message", _tr("Domain has been deleted"));
            }
        }
    }
    return viewFormDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function saveDomain($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $arrFormElements = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormElements);

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));

    if(!$oForm->validateForm($_POST)){
        // Validation basic, not empty and VALIDATION_TYPE
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr("The following fields contain errors").":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";
        }
        $smarty->assign("mb_message", $strErrorMsg);
        $content = $oForm->fetchForm("$local_templates_dir/form_domain.tpl", _tr("New Domain"), $_POST);
    }
    else{
	$pDB->beginTransaction();
	$bExito=create_email_domain($pDB,$error);
	if (!$bExito){
	    $pDB->rollBack();
	    $smarty->assign("mb_title",_tr("Error"));
	    $smarty->assign("mb_message", $error);
	    $content = $oForm->fetchForm("$local_templates_dir/form_domain.tpl", _tr("New Domain"), $_POST);
	}
	else{
	    $pDB->commit();
	    $smarty->assign("mb_message", _tr("Domain has been created"));
	    $content = viewFormDomain($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	}
    }
    return $content;
}


function createFieldForm()
{
    $arrFields = array(
                             "domain_name"       => array("LABEL"                   => _tr("Domain name"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "domain",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                      );
    return $arrFields;
}



//funciones separadas

function create_email_domain($pDB,&$errMsg)
{
    $bReturn=FALSE;
    $pEmail = new paloEmail($pDB);

    $bExito = $pEmail->createDomain($_POST['domain_name']);
    if (!$bExito) $errMsg = _tr($pEmail->errMsg);
    return $bExito;
}

function getAction()
{
    if(getParameter("submit_create_domain")) //Get parameter by POST (submit)
        return "submit_create_domain";
    if(getParameter("save"))
        return "save";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("cancel"))
        return "report";
    else if(getParameter("apply_changes"))
        return "apply_changes";
    else if(getParameter("action")=="view") //Get parameter by GET (command pattern, links)
        return "view";
    else
        return "report";
}


?>
