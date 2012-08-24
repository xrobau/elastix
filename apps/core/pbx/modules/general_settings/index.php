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
	include_once "modules/$module_name/libs/paloSantoFeaturesCode.class.php";

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

	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elx_pbx"));

	$action = getAction();
    $content = "";

	 switch($action){
		case "reloadAasterisk":
			$content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
		case "apply":
			$content = applyChanges($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userAccount, $userLevel1, $idOrganization);
            break;
        default: // view
            $content = viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
            break;
    }
    return $content;

}

function showMessageReload($module_name,$arrConf, &$pDB, $userLevel1, $userAccount, $idOrganization){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
	$params=array();
	$msgs="";

	$query = "SELECT domain, id from organization";
	//si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de marcado
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

function viewGeneralSetting($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization, $action=""){
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    
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
                $checked="";
                $disabled=$disabled_sel="disabled";
                $name=$feature["name"];
                
                if($action=="edit"){
                    $data[$name]=$_POST[$name];
                    if(isset($_POST[$name."_stat"]))
                        $estado=$_POST[$name."_stat"];
                    if(isset($_POST[$name."_chk"]))
                        if($_POST[$name."_chk"]=="on")
                            $checked="checked";  
                }else{
                    if(!is_null($feature["code"]) && $feature["code"]!=""){
                        $code=$feature["code"];
                    }else{
                        $code=$feature["default_code"];
                        $checked="checked";
                    }
                        
                    if($feature["estado"]=="enabled")
                        $estado="enabled";
                    else
                        $estado="disabled";
                    
                    $data[$feature["name"]]=$code;
                }
                if($name!="pickup" && $name!="blind_transfer" && $name!="attended_transfer" && $name!="one_touch_monitor" 
                && $name!="disconnect_call"){
                    if(getParameter("edit") || $action=="edit"){
                        $disabled_sel=$disabled="";
                    }
                    $checkbox="<input type='checkbox' $disabled class='check' name=".$feature["name"]."_chk $checked onclick='fc_use_deafault();' >";
                    $smarty->assign($feature["name"]."_chk",$checkbox);
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
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("General Settings"), $data);
    $mensaje=showMessageReload($module_name, $arrConf, $pDB, $userLevel1, $userAccount, $idOrganization);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$mensaje.$htmlForm."</form>";
    return $content;
}


function applyChanges($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
    $action = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);
    
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
                        $strErrorMsg .= "$k, ";
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
                            $use_default=getParameter($name."_chk");
                            if($use_default!="on")
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
                        $pAstConf=new paloSantoASteriskConfig($pDB,$pDB2);
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
    return viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization, $action);
}

function crearSelect($name,$option,$disabled){
    $opt1 = $opt2 = "";
    if($option=="enabled")
        $opt1="selected";
    else    
        $opt2="selected";
    $select="<select $disabled name='".$name."_stat' class='select'>";
    $select .="<option $opt1 value='enabled'>Enabled</option>";
    $select .="<option $opt2 value='disabled'>Disabled</option>";
    $select .="</select>";
    return $select; 
}

function get_default_code($smarty, $module_name, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $jsonObject = new PaloSantoJSON();
    $feature=getParameter("fc_name");
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pORGZ = new paloSantoOrganization($pDB2);
    $resultO=$pORGZ->getOrganizationById($idOrganization);
    if($resultO==FALSE){
        $jsonObject->set_error(_tr(_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg)));
    }else{
        $pFC = new paloFeatureCodePBX($pDB,$resultO["domain"]);
        $arrFC = $pFC->getFeaturesCode($resultO["domain"],$feature);
        if($arrFC==FALSE){
            $jsonObject->set_error(_tr(_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg)));
        }else{
            $arrData=$arrFC["default_code"];
            $jsonObject->set_message($arrData);
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

function createFieldForm($arrTone)
{
    $arrTZ=getToneZonePBX();
    $arrRCstat=array("ENABLED"=>_tr("Enabled"),"DISABLED"=>_tr("Disabled"));
    //TODO: obtener la lista de codecs de audio soportados por el servidor
    //se los puede hacer con el comando en consola de asterisk "module show like format" or "core show codecs audio"
    //por ahora se pone los que vienes con la instalacion de asterisk
    $arrRCFormat=array("WAV"=>"WAV","wav"=>"wav","ulaw"=>"ulaw","alaw"=>"alaw","sln"=>"sln","gsm"=>"gsm","g729"=>"g729");
    $arrVMesg=array(""=>_tr("Default"),"u"=>_tr("Unavailable"),"b"=>_tr("Busy"),"s"=>("No Message"));
    $arrYesNO=array("yes"=>"YES","no"=>"NO");
    $arrOptions=array(""=>_tr("Standard Message"),""=>_tr("Beep only"));
    $arrTries=array("1","2","3","4");
    $arrTime=array("1","2","3","4","5","6","7","8","9","10");
    $arrLng=getLanguagePBX();
    
    $arrFormElements = array("DIAL_OPTIONS" => array("LABEL"                  => _tr('Asterisk Dial Options'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "TRUNK_OPTIONS" => array("LABEL"                  => _tr('Asterisk Dial Options in Trunk'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:50px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "RECORDING_STATE" => array("LABEL"                  => _tr('Enabled/Disabled Call Recording'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRCstat,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "MIXMON_FORMAT" => array("LABEL"                  => _tr('Call Recording Format'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRCFormat,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "RINGTIMER" => array("LABEL"                  => _tr('Ringtime before Voicemail'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VM_PREFIX" => array("LABEL"                  => _tr('Voicemail Prefix'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "VM_DDTYPE" => array("LABEL"                  => _tr('Voicemail Message type'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrVMesg,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VM_GAIN" => array("LABEL"                  => _tr('Voicemail Gain'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VM_OPTS" => array("LABEL"                  => _tr('Play "please leave message after tone" to caller'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNO,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "OPERATOR_XTN" => array("LABEL"                  => _tr('Operator Extension'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_CONTEXT" => array("LABEL"                  => _tr('Default Context & Pri'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_PRI" => array("LABEL"                  => _tr('pri'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_TIMEDEST_CONTEXT" => array("LABEL"        => _tr('Timeout / #press'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_TIMEDEST_EXT" => array("LABEL"            => _tr("exten"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_TIMEDEST_PRI" => array("LABEL"            => _tr('pri'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_LOOPDEST_CONTEXT" => array("LABEL"        => _tr('Loop exceed Default'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_LOOPDEST_EXT" => array("LABEL"            => _tr("exten"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_LOOPDEST_PRI" => array("LABEL"            => _tr('pri'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_OPTS_TIMEOUT" => array("LABEL"        => _tr('Timeout VM Msg'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOptions,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_OPTS_LOOP" => array("LABEL"            => _tr("Max Loop VM msg"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOptions,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_OPTS_DOVM" => array("LABEL"            => _tr('Direct VM Option'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOptions,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_TIMEOUT" => array("LABEL"        => _tr('Msg Timeout'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrtime,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_REPEAT " => array("LABEL"            => _tr("Msg Play"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTries,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_LOOPS" => array("LABEL"            => _tr('Error Re-tries'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTries,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "TONEZONE" => array("LABEL"        => _tr('Country Tonezone'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTone,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "LANGUAGE" => array("LABEL"            => _tr('Language'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrLng,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "DIRECTORY" => array("LABEL"        => _tr('Search in Directory by'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("last"=>"last","first"=>"first name","both","both"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "DIRECTORY_OPT_EXT" => array("LABEL"            => _tr('Say Extension with name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNO,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                        );
    return $arrFormElements;
}

function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userAccount, $userLevel1, $idOrganization){
	$pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
	$pACL = new paloACL($pDB2);
	$continue=false;

	if($userLevel1=="other"){
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
		return viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
	}

	if($userLevel1=="superadmin"){
		$idOrganization = getParameter("organization_id");
	}

	if($idOrganization==1){
		return viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
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
		}else{
			$pAstConf->setReloadDialplan($domain);
			$smarty->assign("mb_title", _tr("MESSAGE"));
			$smarty->assign("mb_message",_tr("Asterisk was reloaded correctly. "));
		}
	}
    return viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
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