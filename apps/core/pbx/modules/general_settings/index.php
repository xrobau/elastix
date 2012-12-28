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
	include_once "modules/$module_name/libs/paloSantoGlobalsPBX.class.php";

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

	if($userLevel1!="admin"){
        header("Location: index.php?menu=system");
    }
    
	$pDB=new paloDB(generarDSNSistema("asteriskuser", "elxpbx"));

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

function viewGeneralSetting($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $userLevel1, $userAccount, $idOrganization){
    $error = "";
    //conexion elastix.db
    $pDB2 = new paloDB($arrConf['elastix_dsn']['elastix']);
    $pACL = new paloACL($pDB2);
    $pORGZ = new paloSantoOrganization($pDB2);

    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to view this module. ")._tr($pORGZ->errMsg));
        $arrForm = array();
        $resultO=false;
        $oForm = new paloForm($smarty,$arrForm);
        $arrSettings = array();
    }else{
        $resultO=$pORGZ->getOrganizationById($idOrganization);
    }
    
    if($resultO==FALSE){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg));
    }else{
        $domain=$resultO["domain"];
        $pGPBX = new paloGlobalsPBX($pDB,$domain);
        $arrTone = $pGPBX->getToneZonePBX();
        $arrForm = createFieldForm($arrTone);
        $oForm = new paloForm($smarty,$arrForm);
        $arrSettings = $pGPBX->getGeneralSettings();
        if($arrSettings==false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Error getting default settings. ")._tr($pGPBX->errMsg));
        }else{
            if(getParameter("save_edit")){
                $arrSettings=$_POST;
            }
        }
    }
    
	$oForm->setEditMode();
	
	$smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE",_tr("Delete"));
    $smarty->assign("GENERAL",_tr("General Settings"));
    $smarty->assign("SIP_GENERAL",_tr("Sip Settings"));
    $smarty->assign("IAX_GENERAL",_tr("Iax Settings"));
    $smarty->assign("VM_GENERAL",_tr("Voicemail Settings"));
    $smarty->assign("DIAL_OPTS",_tr("Dial Options"));
    $smarty->assign("CALL_RECORDING",_tr("Call Recording"));
    $smarty->assign("LOCATIONS",_tr("Locations"));
    $smarty->assign("DIRECTORY_OPTS",_tr("Directory Options"));
    $smarty->assign("EXT_OPTS",_tr("Create User Options"));
    $smarty->assign("QUALIFY",_tr("Qualify Seetings"));
    $smarty->assign("CODEC",_tr("Codec Selections"));
    $smarty->assign("RTP_TIMERS",_tr("RTP Timers"));
    $smarty->assign("VIDEO_OPTS",_tr("Video Support"));
    $smarty->assign("MOH",_tr("Music on Hold"));
    $smarty->assign("JITTER",_tr("Jitter Buffer Settings"));
    $smarty->assign("GENERAL_VM",_tr("Voicemail Gneral Settings"));
    $smarty->assign("VMX_OPTS",_tr("Voicemail VMX Locator"));
    $smarty->assign("OTHER",_tr("Advande Settings"));
    $smarty->assign("CONTEXT",_tr("context"));
    $smarty->assign("userLevel",$userLevel1);
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("General Settings"), $arrSettings);
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
    
    if($userLevel1!="admin"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to view this module. ")._tr($pORGZ->errMsg));
        return viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
    }else{
        $resultO=$pORGZ->getOrganizationById($idOrganization);
    }

    if($resultO==false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Organization doesn't exist. ")._tr($pORGZ->errMsg));
    }else{
        $domain=$resultO["domain"];
        $pGPBX = new paloGlobalsPBX($pDB,$domain);
        $arrTone = $pGPBX->getToneZonePBX();
        $arrForm = createFieldForm($arrTone);
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
        }else{
            $arrProp=getParameterGeneralSettings();
            $pDB->beginTransaction();
            $exito=$pGPBX->setGeneralSettings($arrProp);
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
                $smarty->assign("mb_message",_tr("Changes couldn't be applied. ").$pGPBX->errMsg);
            }
        }
    }
        
    $smarty->assign("userLevel",$userLevel1);
    return viewGeneralSetting($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $userLevel1, $userAccount, $idOrganization);
}

function getParameterGeneralSettings(){
    //general settings
        $arrPropGen["DIAL_OPTIONS"]=getParameter("DIAL_OPTIONS");
        $arrPropGen["TRUNK_OPTIONS"]=getParameter("TRUNK_OPTIONS");
        $arrPropGen["RECORDING_STATE"]=getParameter("RECORDING_STATE");
        $arrPropGen["RINGTIMER"]=getParameter("RINGTIMER");
        if(isset($arrPropGen["RINGTIMER"])){
            if($arrPropGen["RINGTIMER"]==0)
                $arrPropGen["RINGTIMER"]=="";
        }
        $arrPropGen["TONEZONE"]=getParameter("TONEZONE");
        $arrPropGen["LANGUAGE"]=getParameter("LANGUAGE");
        $arrPropGen["DIRECTORY"]=getParameter("DIRECTORY");
        $arrPropGen["DIRECTORY_OPT_EXT"]=getParameter("DIRECTORY_OPT_EXT");
        $arrPropGen["CREATE_VM"]=getParameter("CREATE_VM");
        $arrPropGen["VM_PREFIX"]=getParameter("VM_PREFIX");
        $arrPropGen["VM_DDTYPE"]=getParameter("VM_DDTYPE");
        $arrPropGen["VM_GAIN"]=getParameter("VM_GAIN");
        $arrPropGen["VM_OPTS"]=getParameter("VM_OPTS");
        $arrPropGen["OPERATOR_XTN"]=getParameter("OPERATOR_XTN");
        $arrPropGen["VMX_CONTEXT"]=getParameter("VMX_CONTEXT");
        $arrPropGen["VMX_PRI"]=getParameter("VMX_PRI");
        $arrPropGen["VMX_TIMEDEST_CONTEXT"]=getParameter("VMX_TIMEDEST_CONTEXT");
        $arrPropGen["VMX_TIMEDEST_EXT"]=getParameter("VMX_TIMEDEST_EXT");
        $arrPropGen["VMX_TIMEDEST_PRI"]=getParameter("VMX_TIMEDEST_PRI");
        $arrPropGen["VMX_LOOPDEST_CONTEXT"]=getParameter("VMX_LOOPDEST_CONTEXT");
        $arrPropGen["VMX_LOOPDEST_EXT"]=getParameter("VMX_LOOPDEST_EXT");
        $arrPropGen["VMX_LOOPDEST_PRI"]=getParameter("VMX_LOOPDEST_PRI");
        $arrPropGen["VMX_OPTS_TIMEOUT"]=getParameter("VMX_OPTS_TIMEOUT");
        $arrPropGen["VMX_OPTS_LOOP"]=getParameter("VMX_OPTS_LOOP");
        $arrPropGen["VMX_OPTS_DOVM"]=getParameter("VMX_OPTS_DOVM");
        $arrPropGen["VMX_TIMEOUT"]=getParameter("VMX_TIMEOUT");
        $arrPropGen["VMX_REPEAT"]=getParameter("VMX_REPEAT");
        $arrPropGen["VMX_LOOPS"]=getParameter("VMX_LOOPS");
    //sip settings
        $arrPropSip["context"]=getParameter("sip_context");
        $arrPropSip['dtmfmode']=getParameter("sip_dtmfmode");
        $arrPropSip['host']=getParameter("sip_host");
        $arrPropSip['type']=getParameter("sip_type");
        $arrPropSip['port']=getParameter("sip_port");
        $arrPropSip['qualify']=getParameter("sip_qualify");
        $arrPropSip['nat']=getParameter("sip_nat");
        $arrPropSip['disallow']=getParameter("sip_disallow");
        $arrPropSip['allow']=getParameter("sip_allow");
        $arrPropSip['canreinvite']=getParameter("sip_canreinvite");
        $arrPropSip['allowtransfer']=getParameter("sip_allowtransfer");
        $arrPropSip["vmexten"]=getParameter("sip_vmexten");
        $arrPropSip['mohinterpret']=getParameter("sip_mohinterpret");
        $arrPropSip['mohsuggest']=getParameter("sip_mohsuggest");
        $arrPropSip['useragent']=getParameter("sip_useragent");
        $arrPropSip['directmedia']=getParameter("sip_directmedia");
        $arrPropSip['callcounter']=getParameter("sip_callcounter");
        $arrPropSip['busylevel']=getParameter("sip_busylevel");
        $arrPropSip['videosupport']=getParameter("sip_videosupport");
        $arrPropSip['qualifyfreq']=getParameter("sip_qualifyfreq");
        $arrPropSip['rtptimeout']=getParameter("sip_rtptimeout");
        $arrPropSip['rtpholdtimeout']=getParameter("sip_rtpholdtimeout");
        $arrPropSip['rtpkeepalive']=getParameter("sip_rtpkeepalive");
        $arrPropSip['progressinband']=getParameter("sip_progressinband");
        $arrPropSip['g726nonstandard']=getParameter("sip_g726nonstandard");
        $arrPropSip['callingpres']=getParameter("sip_callingpres");
        $arrPropSip['language']=getParameter("LANGUAGE");
    //iax settings
        $arrPropIax["context"]=getParameter("iax_context");
        $arrPropIax['host']=getParameter("iax_host");
        $arrPropIax['type']=getParameter("iax_type");
        $arrPropIax['port']=getParameter("iax_port");
        $arrPropIax['qualify']=getParameter("iax_qualify");
        $arrPropIax['disallow']=getParameter("iax_disallow");
        $arrPropIax['allow']=getParameter("iax_allow");
        $arrPropIax['transfer']=getParameter("iax_transfer");
        $arrPropIax['requirecalltoken']=getParameter("iax_requirecalltoken");
        $arrPropIax['defaultip']=getParameter("iax_defaultip");
        $arrPropIax['mask']=getParameter("iax_mask");
        $arrPropIax['mohinterpret']=getParameter("iax_mohinterpret");
        $arrPropIax['mohsuggest']=getParameter("iax_mohsuggest");
        $arrPropIax['jitterbuffer']=getParameter("iax_jitterbuffer");
        $arrPropIax['forcejitterbuffer']=getParameter("iax_forcejitterbuffer");
        $arrPropIax['codecpriority']=getParameter("iax_codecpriority");
        $arrPropIax['qualifysmoothing']=getParameter("iax_qualifysmoothing");
        $arrPropIax['qualifyfreqok']=getParameter("iax_qualifyfreqok");
        $arrPropIax['qualifyfreqnotok']=getParameter("iax_qualifyfreqnotok");
        $arrPropIax['encryption']=getParameter("iax_encryption");
        $arrPropIax['sendani']=getParameter("iax_sendani");
        $arrPropIax['adsi']=getParameter("iax_adsi");
        $arrPropIax['language']=getParameter("LANGUAGE");
    //voicemail settings
        $arrPropVM["attach"]=getParameter("vm_attach");
        $arrPropVM["maxmsg"]=getParameter("vm_maxmsg");
        $arrPropVM["saycid"]=getParameter("vm_saycid");
        $arrPropVM["sayduration"]=getParameter("vm_sayduration");
        $arrPropVM["envelope"]=getParameter("vm_envelope");
        $arrPropVM["context"]=getParameter("vm_context");
        $arrPropVM["tz"]=getParameter("vm_tz");
        $arrPropVM["review"]=getParameter("vm_review");
        $arrPropVM["operator"]=getParameter("vm_operator");
        $arrPropVM["forcename"]=getParameter("vm_forcename");
        $arrPropVM["forcegreetings"]=getParameter("vm_forcegreetings");
        $arrPropVM['language']=getParameter("LANGUAGE");
        $arrPropVM['volgain']=getParameter("VM_GAIN");
    return array("gen"=>$arrPropGen,"sip"=>$arrPropSip,"iax"=>$arrPropIax,"vm"=>$arrPropVM);
}

function createFieldForm($arrTone)
{
    $arrRCstat=array("ENABLED"=>_tr("Enabled"),"DISABLED"=>_tr("Disabled"));
    $arrRings=array(""=>_tr("Default"),"1"=>1,"2"=>2,"3"=>3,"4"=>4,"5"=>5,"6"=>6,"7"=>7,"8"=>8,"9"=>9,"10"=>10,"11"=>11,"12"=>12,"13"=>13,"14"=>14,"15"=>15,"16"=>16,"17"=>17,"18"=>18,"19"=>19,"20"=>20,"21"=>21,"22"=>22,"23"=>23,"24"=>24,"25"=>25,"26"=>26,"27"=>27,"28"=>28,"29"=>29,"30"=>30,"31"=>31,"32"=>32,"33"=>33,"34"=>34,"35"=>35,"36"=>36,"37"=>37,"38"=>38,"39"=>39,"40"=>40,"41"=>41,"42"=>42,"43"=>43,"44"=>44,"45"=>45,"46"=>46,"47"=>47,"48"=>48,"49"=>49,"50"=>50,"51"=>51,"52"=>52,"53"=>53,"54"=>54,"55"=>55,"56"=>56,"57"=>57,"58"=>58,"59"=>59,"60"=>60,"61"=>61,"62"=>62,"63"=>63,"64"=>64,"65"=>65,"66"=>66,"67"=>67,"68"=>68,"69"=>69,"70"=>70,"71"=>71,"72"=>72,"73"=>73,"74"=>74,"75"=>75,"76"=>76,"77"=>77,"78"=>78,"79"=>79,"80"=>80,"81"=>81,"82"=>82,"83"=>83,"84"=>84,"85"=>85,"86"=>86,"87"=>87,"88"=>88,"89"=>89,"90"=>90,"91"=>91,"92"=>92,"93"=>93,"94"=>94,"95"=>95,"96"=>96,"97"=>97,"98"=>98,"99"=>99,"100"=>100,"101"=>101,"102"=>102,"103"=>103,"104"=>104,"105"=>105,"106"=>106,"107"=>107,"108"=>108,"109"=>109,"
110"=>110,"111"=>111,"112"=>112,"113"=>113,"114"=>114,"115"=>115,"116"=>116,"117"=>117,"118"=>118,"119"=>119,"120"=>120);
    //TODO: obtener la lista de codecs de audio soportados por el servidor
    //se los puede hacer con el comando en consola de asterisk "module show like format" or "core show codecs audio"
    //por ahora se pone los que vienes con la instalacion de asterisk
    $arrRCFormat=array("WAV"=>"WAV","wav"=>"wav","ulaw"=>"ulaw","alaw"=>"alaw","sln"=>"sln","gsm"=>"gsm","g729"=>"g729");
    $arrYesNO=array("yes"=>"YES","no"=>"NO");
    $arrLng=getLanguagePBX();
    
    $arrFormElements = array("DIAL_OPTIONS" => array("LABEL"                  => _tr('Asterisk Dial Options'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "TRUNK_OPTIONS" => array("LABEL"                  => _tr('Asterisk Dial Options in Trunk'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
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
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRings,
                                                    "VALIDATION_TYPE"        => "numeric",
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
                                                    "INPUT_EXTRA_PARAM"      => array("first"=>"surname","last"=>"first name","both"=>"both"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "DIRECTORY_OPT_EXT" => array("LABEL"            => _tr('Say Extension with name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("e" => "Yes", "" => "No"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "CREATE_VM" => array("LABEL"            => _tr('Create Voicemail with extension'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNO,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                        );
    $arrFormElements = array_merge(createSipForm(),$arrFormElements);
    $arrFormElements = array_merge(createIaxForm(),$arrFormElements);
    $arrFormElements = array_merge(createVMForm(),$arrFormElements);
    return $arrFormElements;
}

function createSipForm(){
    $arrNat=array("yes"=>"Yes","no"=>"No","never"=>"never","route"=>"route");
    $arrCallingpres=array('allowed_not_screened'=>'allowed_not_screened','allowed_passed_screen'=>'allowed_passed_screen','allowed_failed_screen'=>'allowed_failed_screen','allowed'=>'allowed','prohib_not_screened'=>'prohib_not_screened','prohib_passed_screen'=>'prohib_passed_screen','prohib_failed_screen'=>'prohib_failed_screen','prohib'=>'prohib');
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrYesNod=array("noset"=>"noset","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrType=array("friend"=>"friend","user"=>"user","peer"=>"peer");
    $arrDtmf=array('rfc2833'=>'rfc2833','info'=>"info",'shortinfo'=>'shortinfo','inband'=>'inband','auto'=>'auto');
    $arrMedia=array("noset"=>"noset",'yes'=>'yes','no'=>'no','nonat'=>'nonat','update'=>'update');
    $arrFormElements = array("sip_type"  => array("LABEL"                  => _tr("type"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrType,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_context"  => array("LABEL"                  => _tr("context"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_host"   => array("LABEL"                  => _tr("host"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_port"   => array("LABEL"                  => _tr("port"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_qualify"       => array("LABEL"           => _tr("qualify"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_allow"   => array("LABEL"                  => _tr("allow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_disallow"   => array("LABEL"                  => _tr("disallow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_nat"  => array("LABEL"                  => _tr("nat"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrNat,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_dtmfmode"   => array( "LABEL"                  => _tr("dtmfmode"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDtmf,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_canreinvite"   => array( "LABEL"                  => _tr("canreinvite"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "sip_vmexten" => array("LABEL"             => _tr("vmexten"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_mohinterpret"   => array( "LABEL"                  => _tr("mohinterpret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_mohsuggest" => array("LABEL"             => _tr("mohsuggest"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_allowtransfer"   => array( "LABEL"              => _tr("allowtransfer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "sip_directmedia"   => array( "LABEL"              => _tr("directmedia"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMedia,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_useragent" => array("LABEL"             => _tr("useragent"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_busylevel" => array("LABEL"             => _tr("busylevel"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_callcounter"   => array( "LABEL"              => _tr("callcounter"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "sip_callingpres"   => array( "LABEL"              => _tr("callingpres"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCallingpres,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_videosupport"   => array( "LABEL"              => _tr("videosupport"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "sip_maxcallbitrate" => array("LABEL"             => _tr("maxcallbitrate"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_qualifyfreq" => array("LABEL"             => _tr("qualifyfreq"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_rtptimeout" => array("LABEL"             => _tr("rtptimeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_rtpholdtimeout" => array("LABEL"             => _tr("rtpholdtimeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_rtpkeepalive" => array("LABEL"             => _tr("rtpkeepalive"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_progressinband" => array("LABEL"             => _tr("progressinband"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sip_g726nonstandard" => array("LABEL"             => _tr("g726nonstandard"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
    );
    return $arrFormElements;
}

function createIaxForm(){
    $arrTrans=array("yes"=>"yes","no"=>"no","mediaonly"=>"mediaonly");
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrYesNod=array("noset"=>"noset","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrType=array("friend"=>"friend","user"=>"user","peer"=>"peer");
    $arrCallTok=array("yes"=>"yes","no"=>"no","auto"=>"auto");
    $arrCodecPrio=array("noset"=>"noset","host"=>"host","caller"=>"caller","disabled"=>"disabled","reqonly"=>"reqonly");
    $encryption=array("noset"=>"noset","aes128"=>"aes128","yes"=>"yes","no"=>"no");
    $arrFormElements = array("iax_transfer"  => array("LABEL"                  => _tr("transfer"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrTrans,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_type"  => array("LABEL"                  => _tr("type"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrType,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_context"  => array("LABEL"                  => _tr("context"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_host"   => array("LABEL"                  => _tr("host"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_port"   => array("LABEL"                  => _tr("port"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_qualify"=> array("LABEL"           => _tr("qualify"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_allow"   => array("LABEL"                  => _tr("allow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_disallow"   => array("LABEL"                  => _tr("disallow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_requierecalltoken" => array("LABEL"             => _tr("requierecalltoken"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCallTok,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_mask"     => array("LABEL"                   => _tr("mask"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_mohinterpret"   => array( "LABEL"                  => _tr("mohinterpret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_mohsuggest" => array("LABEL"             => _tr("mohsuggest"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_timezone"   => array( "LABEL"                  => _tr("timezone"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_sendani" => array("LABEL"             => _tr("sendani"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "iax_adsi" => array("LABEL"             => _tr("adsi"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "iax_encryption" => array("LABEL"             => _tr("encryption"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $encryption,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_jitterbuffer" => array("LABEL"             => _tr("jitterbuffer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "iax_forcejitterbuffer" => array("LABEL"             => _tr("forcejitterbuffer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "iax_codecpriority" => array("LABEL"             => _tr("codecpriority"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCodecPrio,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "iax_qualifysmoothing" => array("LABEL"             => _tr("qualifysmoothing"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "iax_qualifyfreqok" => array("LABEL"             => _tr("qualifyfreqok"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "iax_qualifyfreqnotok" => array("LABEL"             => _tr("qualifyfreqnotok"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "")
    );
    return $arrFormElements;
}

function createVMForm()
{
    $arrVMesg=array(""=>_tr("Default"),"u"=>_tr("Unavailable"),"b"=>_tr("Busy"),"s"=>("No Message"));
    $arrYesNo=array("yes"=>"Yes","no"=>"No");
    $arrOptions=array(""=>_tr("Standard Message"),"s"=>_tr("Beep only"));
    $arrTries=array("1","2","3","4");
    $arrTime=array("1","2","3","4","5","6","7","8","9","10");
    $arrZoneMessage = array();
    
    $arrFormElements = array("VM_PREFIX" => array("LABEL"                  => _tr('Voicemail Prefix'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[0-9\#\*]+$"),
                              "VM_DDTYPE" => array("LABEL"                  => _tr('Voicemail Message type'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrVMesg,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VM_GAIN" => array("LABEL"                  => _tr('Voicemail Gain'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VM_OPTS" => array("LABEL"                  => _tr('Play "please leave message after tone" to caller'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("s"=>"Yes",""=>"No"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "OPERATOR_XTN" => array("LABEL"                  => _tr('Operator Extension'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:80px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_CONTEXT" => array("LABEL"                  => _tr('Default Context & Pri'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
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
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
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
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
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
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOptions,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_OPTS_DOVM" => array("LABEL"            => _tr('Direct VM Option'),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOptions,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_TIMEOUT" => array("LABEL"        => _tr('Msg Timeout'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTime,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_REPEAT" => array("LABEL"            => _tr("Msg Play"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTries,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                              "VMX_LOOPS" => array("LABEL"            => _tr('Error Re-tries'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTries,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vm_attach"   => array("LABEL"               => _tr("Email Attachment"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_maxmsg"   => array("LABEL"               => _tr("Maximum # of message per Folder"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vm_saycid"   => array("LABEL"               => _tr("Play CID"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_sayduration"   => array("LABEL"               => _tr("Say Duration"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_envelope"   => array("LABEL"            => _tr("Play Envelope"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_delete"   => array("LABEL"               => _tr("Delete Voicemail"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_context"   => array("LABEL"               => _tr("Voicemail Context"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vm_tz"   => array("LABEL"               => _tr("Time Zone"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrZoneMessage,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vm_review"   => array("LABEL"               => _tr("Review Message"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_operator"   => array("LABEL"               => _tr("Operator"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_forcename"   => array("LABEL"               => _tr("Force to record name"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vm_forcegreetings" => array("LABEL"            => _tr("Force to record greetings"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
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
		if($pAstConf->generateDialplan($domain,true)===false){
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
    else
        return "view"; //cancel
}
?>
