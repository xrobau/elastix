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
$elxPath="/usr/share/elastix";
include_once "$elxPath/libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    include_once "$elxPath/libs/paloSantoDB.class.php";
    include_once "$elxPath/libs/paloSantoConfig.class.php";
    include_once "$elxPath/libs/paloSantoGrid.class.php";
	include_once "$elxPath/libs/paloSantoForm.class.php";
	include_once "$elxPath/libs/paloSantoOrganization.class.php";
    include_once "$elxPath/libs/paloSantoACL.class.php";
    include_once "$elxPath/modules/apps/configs/default.conf.php";
	include_once "$elxPath/modules/apps/libs/paloSantoFeaturesCode.class.php";

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

	if($userLevel1=="superadmin"){
        header("Location: index.php?menu=system");
    }
	
	$pDB=new paloDB($arrConf["elastix_dsn"]["elastix"]);

	$action = getAction();
    $content = "";

	 switch($action){
		case "reloadAasterisk":
			$content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
		case "apply":
			$content = applyChanges($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        case "get_default_code": // report
            $content = get_default_code($smarty, $module_name, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
        default: // view
            $content = viewFeatures($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;

}

function showMessageReload($module_name,$arrConf, &$pDB, $userLevel1, $userAccount, $idOrganization){
	$pAstConf=new paloSantoASteriskConfig($pDB);
	$params=array();
	$msgs="";
    $mensaje=_tr("Click here to reload dialplan");
	
	$query = "SELECT domain, id from organization";
	//si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de marcado
	if($userLevel1!="superadmin"){
		$query .= " where id=?";
		$params[]=$idOrganization;
	}
	$result=$pAstConf->_DB->fetchTable($query,false,$params);
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

function viewFeatures($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization, $action=""){
    $error = "";
    $pORGZ = new paloSantoOrganization($pDB);
    
    $arrForm = createFieldForm();
    if($userLevel1=="superadmin"){
        $resultO=false;
        $oForm = new paloForm($smarty,$arrForm);
    }else{
        $resultO=$pORGZ->getOrganizationById($idOrganization);
        $oForm = new paloForm($smarty,$arrForm);
    }
    
    if($resultO==FALSE){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg));
    }else{
        $domain=$resultO["domain"];
        $pFC = new paloFeatureCodePBX($pDB,$domain);
        $arrFC = $pFC->getAllFeaturesCode($domain);
        if($arrFC===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pFC->errMsg));
        }else{
            foreach($arrFC as $feature){
                $name=$feature["name"];
                $disabled_sel="disabled";
                if($action=="edit"){
                    $data[$name]=$_POST[$name];
                    if(isset($_POST[$name."_stat"]))
                        $estado=$_POST[$name."_stat"];
                }else{
                    if($feature["estado"]!="enabled")
                        $estado="disabled";
                    else{
                        if(!is_null($feature["code"]) && $feature["code"]!=""){
                            $code=$feature["code"];
                            $estado="ena_custom";
                        }else{
                            $code=$feature["default_code"];
                            $estado="ena_default";
                        }
                    }
                    $data[$feature["name"]]=$code;
                }
                if($name!="pickup" && $name!="blind_transfer" && $name!="attended_transfer" && $name!="one_touch_monitor" 
                && $name!="disconnect_call"){
                    if(getParameter("edit") || $action=="edit"){
                        $disabled_sel="";
                    }
                    $smarty->assign($feature["name"]."_stat",crearSelect($feature["name"],$estado,$disabled_sel));
                }
            }
        }
    }
	
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE",_tr("Delete"));
    $smarty->assign("BLACKLIST",_tr("BLACKLIST"));
    $smarty->assign("CALLFORWARD",_tr("CALLFORWARD"));
    $smarty->assign("CALLWAITING",_tr("CALLWAITING"));
    $smarty->assign("CORE",_tr("CORE"));
    $smarty->assign("DICTATION",_tr("DICTATION"));
    $smarty->assign("DND",_tr("DND"));
    $smarty->assign("INFO",_tr("INFO"));
    $smarty->assign("RECORDING",_tr("RECORDING"));
    $smarty->assign("SPEEDDIAL",_tr("SPEEDDIAL"));
    $smarty->assign("VOICEMAIL",_tr("VOICEMAIL"));
    $smarty->assign("FOLLOWME",_tr("FOLLOWME"));
    $smarty->assign("QUEUE",_tr("QUEUE"));
    $smarty->assign("userLevel",$userLevel1);
    if(getParameter("edit") || $action=="edit"){
        $oForm->setEditMode();
    }else{
        $oForm->setViewMode();
    }
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("Features Code"), $data);
    $mensaje=showMessageReload($module_name, $arrConf, $pDB, $userLevel1, $userAccount, $idOrganization);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$mensaje.$htmlForm."</form>";
    return $content;
}


function applyChanges($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
    $action = "";
    $pORGZ = new paloSantoOrganization($pDB);
    
    $resultO=$pORGZ->getOrganizationById($idOrganization);
    
    $arrForm = createFieldForm();
    $oForm = new paloForm($smarty,$arrForm);
    
    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
    }else{
        if($resultO==false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg));
        }else{
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
                $action="edit";
            }else{
                $domain=$resultO["domain"];
                $pFC = new paloFeatureCodePBX($pDB,$domain);
                $arrFC = $pFC->getAllFeaturesCode($domain);
                if($arrFC===false){
                    $smarty->assign("mb_title", _tr("ERROR"));
                    $smarty->assign("mb_message",_tr($pFC->errMsg));
                }else{
                    $arrData=array();
                    //obtengo las entradas
                    foreach($arrFC as $feature){
                        $code=null;
                        $name=$feature["name"];
                        if($name!="pickup" && $name!="blind_transfer" && $name!="attended_transfer" && $name!="one_touch_monitor" 
                        && $name!="disconnect_call"){
                            $estado=getParameter($name."_stat"); //si esta o no habilitado el feature
                            if($estado=="ena_custom")
                                $code=getParameter($name); //el code altenativo en caso de que no se quiera usar el de po default
                        }else{
                            $estado=$feature["estado"]; //si esta o no habilitado el feature
                        }
                        $arrData[]=array("name"=>$name,"default_code"=>$feature["default_code"],"code"=>$code,"estado"=>$estado);
                    }
                    $pDB->beginTransaction();
                    $exito=$pFC->editPaloFeatureDB($arrData);
                    if($exito===true){
                        $pDB->commit();
                        $smarty->assign("mb_title", _tr("MESSAGE"));
                        $smarty->assign("mb_message",_tr("Changes applied successfully. "));
                        //mostramos el mensaje para crear los archivos de ocnfiguracion
                        $pAstConf=new paloSantoASteriskConfig($pDB);
                        $pAstConf->setReloadDialplan($domain,true);
                    }else{
                        $pDB->rollBack();
                        $smarty->assign("mb_title", _tr("ERROR"));
                        $smarty->assign("mb_message",_tr("Changes couldn't be applied. ").$pFC->errMsg);
                        $action="edit";
                    }
                }
            }
        }
    }
    $smarty->assign("userLevel",$userLevel1);
    return viewFeatures($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization, $action);
}

function crearSelect($name,$option,$disabled){
    $opt1 = $opt2 = $opt3 = "";
    switch($option){
        case "ena_default":
            $opt1="selected";
            break;
        case "ena_custom":
            $opt2="selected";
            break;
        default:
            $opt3="selected";
            break;
    }
    $select="<select $disabled name='".$name."_stat' class='select'>";
    $select .="<option $opt1 value='ena_default'>Enabled Default</option>";
    $select .="<option $opt2 value='ena_custom'>Enabled Custom</option>";
    $select .="<option $opt3 value='disabled'>Disabled</option>";
    $select .="</select>";
    return $select; 
}

function get_default_code($smarty, $module_name, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $jsonObject = new PaloSantoJSON();
    $feature=getParameter("fc_name");
    $pORGZ = new paloSantoOrganization($pDB);
    $resultO=$pORGZ->getOrganizationById($idOrganization);
    if($resultO==FALSE){
        $jsonObject->set_error(_tr(_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg)));
    }else{
        $pFC = new paloFeatureCodePBX($pDB,$resultO["domain"]);
        $arrFC = $pFC->getFeaturesCode($resultO["domain"],$feature);
        if($arrFC==FALSE){
            $jsonObject->set_error(_tr($pFC->errMsg));
        }else{
            $jsonObject->set_message($arrFC);
        }
    }
    return $jsonObject->createJSON();
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

function createFieldForm()
{
    $arrFormElements = array("blacklist_num" => array("LABEL"                  => _tr('Blacklist a number'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "blacklist_lcall" => array("LABEL"                  => _tr('Blacklist the last caller'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "blacklist_rm" => array("LABEL"                  => _tr('Remove a number from the blacklist'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_all_act" => array("LABEL"                  => _tr('Call Forward All Activate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_all_desact" => array("LABEL"                  => _tr('Call Forward All Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_all_promp" => array("LABEL"                  => _tr('Call Forward All Prompting Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_busy_act" => array("LABEL"                  => _tr('Call Forward Busy Activate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_busy_desact" => array("LABEL"                  => _tr('Call Forward Busy Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_busy_promp" => array("LABEL"                  => _tr('Call Forward Busy Prompting Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),     
                              "cf_nu_act" => array("LABEL"                  => _tr('Call Forward No Answer/Unavailable Activate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_nu_desact" => array("LABEL"                  => _tr('Call Forward No Answer/Unavailable Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "cf_toggle" => array("LABEL"                  => _tr('Call Forward Toggle'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"), 
                              "cw_act" => array("LABEL"                  => _tr('Call Waiting Activate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),     
                              "cw_desact" => array("LABEL"                  => _tr('Call Waiting Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "dictation_email" => array("LABEL"                  => _tr('Email completed dictation'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "dictation_perform" => array("LABEL"                  => _tr('Perform dictation'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"), 
                              "dnd_act" => array("LABEL"                  => _tr('DND Activate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),     
                              "dnd_desact" => array("LABEL"                  => _tr('DND Deactivate'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "dnd_toggle" => array("LABEL"                  => _tr('DND Toggle'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "fm_toggle" => array("LABEL"                  => _tr('Findme Follow Toggle'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"), 
                              "call_trace" => array("LABEL"                  => _tr('Call Trace'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),     
                              "directory" => array("LABEL"                  => _tr('Directory'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "echo_test" => array("LABEL"                  => _tr('Echo Test'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "speak_u_exten" => array("LABEL"                  => _tr('Speak Your Exten Number'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),   
                              "speak_clock" => array("LABEL"                  => _tr('Speaking Clock'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "pbdirectory" => array("LABEL"                  => _tr('Phonebook dial-by-name directory'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "queue_toggle" => array("LABEL"                  => _tr('Queue Toggle'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),   
                              "speeddial_set" => array("LABEL"                  => _tr('Set user speed dial'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),        
                              "speeddial_prefix" => array("LABEL"                  => _tr('Speeddial prefix'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "voicemail_dial" => array("LABEL"                  => _tr('Dial Voicemail'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "voicemail_mine" => array("LABEL"                  => _tr('My Voicemail'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),     
                              "sim_in_call" => array("LABEL"                  => _tr('Simulate Incoming Call'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "direct_call_pickup" => array("LABEL"                  => _tr('Directed Call Pickup'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "pickup" => array("LABEL"                  => _tr('Asterisk General Call Pickup'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "blind_transfer" => array("LABEL"              => _tr('In-Call Asterisk Blind Transfer'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),        
                              "attended_transfer" => array("LABEL"           => _tr('In-Call Asterisk Attended Transfer'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "one_touch_monitor" => array("LABEL"           => _tr('In-Call Asterisk Toggle Call Recording'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "disconnect_call" => array("LABEL"             => _tr('In-Call Asterisk Disconnect Code'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px","class"=>"feature_val"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"), 
                        );
    return $arrFormElements;
}

function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
	$continue=false;

	if($userLevel1=="other"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return viewFeatures($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization==1){
		return viewFeatures($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	$query="select domain from organization where id=?";
	$result=$pDB->getFirstRowQuery($query, false, array($idOrganization));
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
		$pAstConf=new paloSantoASteriskConfig($pDB);
		if($pAstConf->generateDialplan($domain)===false){
			$pAstConf->setReloadDialplan($domain,true);
			$smarty->assign("mb_title", _tr("ERROR"));
			$smarty->assign("mb_message",_tr("Asterisk can't be reloaded. ").$pAstConf->errMsg);
		}else{
			$pAstConf->setReloadDialplan($domain);
			$smarty->assign("mb_title", _tr("MESSAGE"));
			$smarty->assign("mb_message",_tr("Asterisk was reloaded correctly. "));
		}
	}
    return viewFeatures($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getAction(){
    if(getParameter("save_edit"))
        return "apply";
    else if(getParameter("edit"))
        return "view_edit";
	elseif(getParameter("action")=="reloadAsterisk")
		return "reloadAasterisk";
    elseif(getParameter("action")=="fc_get_default_code")
        return "get_default_code";
    else
        return "view"; //cancel
}
?>
