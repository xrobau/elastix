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
    include_once("libs/paloSantoGrid.class.php");
	include_once "libs/paloSantoForm.class.php";
	include_once "libs/paloSantoOrganization.class.php";

function _moduleContent(&$smarty, $module_name)
{
    global $arrConf;
    
     //folder path for custom templates
    $local_templates_dir=getWebDirModule($module_name);

    //conexion resource
    $pDB = new paloDB($arrConf['elastix_dsn']["elastix"]);

    //user credentials
    global $arrCredentials;
        
    $action = getAction();
    $content = "";
       
	 switch($action){
        case "new_exten":
            $content = viewFormExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "view":
            $content = viewFormExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "view_edit":
            $content = viewFormExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "save_new":
            $content = saveNewExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "save_edit":
            $content = saveEditExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "delete":
            $content = deleteExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
		case "reloadAasterisk":
			$content = reloadAasterisk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        default: // report
            $content = reportExten($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $arrCredentials);
            break;
    }
    return $content;

}

function reportExten($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials)
{
    global $arrPermission;
    $pExten = new paloSantoExtensions($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $error='';

    $domain=getParameter("organization");
    $domain=empty($domain)?'all':$domain;
    if($credentials['userlevel']!="superadmin"){
        $domain=$credentials['domain'];
    }
    
    $extension=getParameter("extension");
    if(isset($extension) && $extension!=''){
        $pPBX= new paloAsteriskDB($pDB);
        $expression=$pPBX->getRegexPatternFromAsteriskPattern($extension);
        if($expression===false)
            $extension='';
    }
    
    $url['menu']=$module_name;
    $url['organization']=$domain;
    $url['extension']=$extension;
    
    $total=$pExten->getNumExtensions($domain,$extension);
    
    $arrOrgz=array();
    if($credentials['userlevel']=="superadmin"){
        $arrOrgz=array("all"=>"all");
        foreach(($pORGZ->getOrganization(array())) as $value){
            $arrOrgz[$value["domain"]]=$value["name"];
        }
    }

    if($total===false){
        $error=$pExten->errMsg;
        $total=0;
    }

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $oGrid->setTitle(_tr('Extensions List'));
    //$oGrid->setIcon('url de la imagen');
    $oGrid->setWidth("99%");
    $oGrid->setStart(($total==0) ? 0 : $offset + 1);
    $oGrid->setEnd($end);
    $oGrid->setTotal($total);
    $oGrid->setURL($url);

    if($credentials['userlevel']=="superadmin")
        $arrColum[]=_tr("Organization");
    $arrColum[]=_tr("Extension");
    $arrColum[]=_tr("Caller ID");
    $arrColum[]=_tr("Technology");
    $arrColum[]=_tr("Device");
    $arrColum[]=_tr("Context");
    $arrColum[]=_tr("User");
    $arrColum[]=_tr("Voicemail");
    $arrColum[]=_tr("Recording In")." / "._tr("Recording Out");
    $oGrid->setColumns($arrColum);

    $arrExtens=array();
    $arrData = array();
    if($total!=0){
        $arrExtens=$pExten->getExtensions($domain,$extension,$limit,$offset);
    }
    
    if($arrExtens===false){
        $error=_tr("Error to obtain extensions").$pExten->errMsg;
        $arrExtens=array();
    }else{
        foreach($arrExtens as $exten) {
            $arrTmp=array();
            if($credentials['userlevel']=="superadmin"){
                $arrTmp[] = $arrOrgz[$exten["organization_domain"]];
            }
            $arrTmp[] = "&nbsp;<a href='?menu=extensions&action=view&id_exten=".$exten['id']."&organization={$exten['organization_domain']}'>".$exten["exten"]."</a>";
            $arrTmp[] = $exten['clid_name']." <{$exten['clid_number']}>";
            $arrTmp[] = strtoupper($exten['tech']);
            $arrTmp[] = $exten['device'];
            $arrTmp[] = $exten['context'];
           
            $query = "Select username from acl_user where extension=? and id_group in (select g.id from acl_group g join organization o on g.id_organization=o.id where o.domain=?)";
            $result=$pDB->getFirstRowQuery($query,false,array($exten["exten"],$exten["organization_domain"]));
            if($result!=false)
                $arrTmp[] = $result[0];
            else
                $arrTmp[] = _tr("Nobody");
            
            if(isset($exten["voicemail"])){
                if($exten["voicemail"]!="novm")
                    $arrTmp[] = "yes";
                else
                    $arrTmp[] = "no";
            }else
                $arrTmp[] = "no";
                
            $arrTmp[] = _tr($exten["record_in"])." / "._tr($exten["record_out"]);
            $arrData[] = $arrTmp;
        }
    }

    $smarty->assign("USERLEVEL",$credentials['userlevel']);
    $smarty->assign("SEARCH","<input type='submit' class='button' value='"._tr('Search')."' name='report'>");
    if($pORGZ->getNumOrganization(array()) >= 1){
        if(in_array('create',$arrPermission)){
            if($credentials['userlevel']=='superadmin'){
                $oGrid->addComboAction("organization_add",_tr("Create New Extension"), array_slice($arrOrgz,1), $selected=null, "create_exten", $onchange_select=null);
            }else{
                $oGrid->addNew("create_exten",_tr("Create New Extension"));
            }   
        }
        if($credentials['userlevel']=='superadmin'){
            $_POST["organization"]=$domain;
            $oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization")." = ".$arrOrgz[$domain], $_POST, array("organization" => "all"),true);
        }
        $_POST["extension"]=$extension; // patter to filter estension number
        $oGrid->addFilterControl(_tr("Filter applied ")._tr("Extension")." = ".$extension, $_POST, array("extension" => "")); 
        $arrFormElements = createFieldFilter($arrOrgz);
        $oFilterForm = new paloForm($smarty, $arrFormElements);
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
        $oGrid->showFilter(trim($htmlFilter));
    }else{
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("It's necesary you create at least one organization so you can use this module"));
    }

    if($error!=""){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",$error);
    }

    $contenidoModulo = $oGrid->fetchGrid(array(), $arrData);
    $mensaje=showMessageReload($module_name, $pDB, $credentials);
    $contenidoModulo = $mensaje.$contenidoModulo;
    return $contenidoModulo;
}

function showMessageReload($module_name, &$pDB, $credentials){
    $pAstConf=new paloSantoASteriskConfig($pDB);
    $params=array();
    $msgs="";

    $query = "SELECT domain, id from organization";
    //si es superadmin aparece un link por cada organizacion que necesite reescribir su plan de marcado
    if($credentials["userlevel"]!="superadmin"){
        $query .= " where id=?";
        $params[]=$credentials["id_organization"];
    }

    $mensaje=_tr("Click here to reload dialplan");
    $result=$pDB->fetchTable($query,false,$params);
    if(is_array($result)){
        foreach($result as $value){
            if($value[1]!=1){
                $showmessage=$pAstConf->getReloadDialplan($value[0]);
                if($showmessage=="yes"){
                    $append=($credentials["userlevel"]=="superadmin")?" $value[0]":"";
                    $msgs .= "<div id='msg_status_$value[1]' class='mensajeStatus'><a href='?menu=$module_name&action=reloadAsterisk&organization_id=$value[1]'/><b>".$mensaje.$append."</b></a></div>";
                }
            }
        }
    }
    return $msgs;
}

function viewFormExten($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials){
    global $arrPermission;
    $pExten = new paloSantoExtensions($pDB);
    $error = "";

    $arrExten=array();
    $action = getParameter("action");
    
    $smarty->assign("DIV_VM","yes");
    $idExten=getParameter("id_exten");

    if($action=="view" || getParameter("edit") || getParameter("save_edit")){
        if(!isset($idExten)){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Invalid Exten"));
            return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
        }
        
        $domain=getParameter('organization');
        if($credentials['userlevel']!='superadmin'){
            $domain=$credentials['domain'];
        }
        $arrExten = $pExten->getExtensionById($idExten, $domain);
                
        if($arrExten===false){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr($pExten->errMsg));
            return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
        }else if(count($arrExten)==0){
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message",_tr("Extension doesn't exist"));
            return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
        }else{
            $smarty->assign("EXTEN",$arrExten["exten"]);
            if($arrExten["technology"]=="iax2"){
                $tech="iax2";
                $smarty->assign("isIax",TRUE);
                $smarty->assign("TECHNOLOGY",strtoupper("Iax2"));
            }elseif($arrExten["technology"]=="sip"){
                $tech="sip";
                $smarty->assign("TECHNOLOGY",strtoupper("Sip"));
            }else
                $tech=null;

            if(getParameter("save_edit"))
                $arrExten=$_POST;
            

            $smarty->assign("DISPLAY_VM","style='display: none;'");
            if(isset($arrExten["create_vm"])){
                if($arrExten["create_vm"]=="yes"){
                    $smarty->assign("VALVM","value='yes'");
                    $smarty->assign("CHECKED","checked");
                    $smarty->assign("DISPLAY_VM","style='visibility: visible;'");
                }else{
                    if($action=="view"){
                        $smarty->assign("DIV_VM","no");
                    }else{
                        $arrVM=$pExten->getVMdefault($domain);
                        $arrExten["vmcontext"]=$arrVM["vmcontext"];
                        $arrExten["vmattach"]=$arrVM["vmattach"];
                        $arrExten["vmdelete"]=$arrVM["vmdelete"];
                        $arrExten["vmsaycid"]=$arrVM["vmsaycid"];
                        $arrExten["vmenvelope"]=$arrVM["vmenvelope"];
                        $arrExten["vmemailsubject"]=$arrVM["vmemailsubject"];
                        $arrExten["vmemailbody"]=$arrVM["vmemailbody"];
                        $arrExten["vmx_locator"]="enabled";
                        $arrExten["vmx_use"]="both";
                        $arrExten["vmx_operator"]="on";
                    }
                }
            }
        }
    }else{
        $tech=null;
        if($credentials['userlevel']=='superadmin'){
            if(getParameter("create_exten")){
                $domain=getParameter('organization_add'); //este parametro solo es selecionable cuando es el superadmin quien crea la ruta
            }else
                $domain=getParameter('organization');
        }else{
            $domain=$credentials['domain'];
        }
        
        if(getParameter("create_exten")){
            $arrExten["technology"]="sip";
            $arrExten=$pExten->getDefaultSettings($domain,"sip");
        }else{
            $arrExten=$_POST;
        }

        if(isset($_POST["create_vm"])){
            $smarty->assign("VALVM","value='yes'");
            $smarty->assign("CHECKED","checked");
        }
    }

    $arrFormOrgz = createFieldForm($tech);
    $oForm = new paloForm($smarty,$arrFormOrgz);

    if($action=="view"){
        $oForm->setViewMode();
    }else if($action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        $oForm->setEditMode();
        $mostrar=getParameter("mostra_adv");
        if(isset($mostrar)){
            if($mostrar=="yes"){
                $smarty->assign("SHOW_MORE","style='visibility: visible;'");
                $smarty->assign("mostra_adv","yes");
            }else{
                $smarty->assign("SHOW_MORE","style='display: none;'");
                $smarty->assign("mostra_adv","no");
            }
        }else{
            $smarty->assign("SHOW_MORE","style='display: none;'");
            $smarty->assign("mostra_adv","yes");
        }
    }

    //permission
    $smarty->assign("EDIT_EXTEN",in_array('edit',$arrPermission));
    $smarty->assign("CREATE_EXTEN",in_array('create',$arrPermission));
    $smarty->assign("DEL_EXTEN",in_array('delete',$arrPermission));
    
    $smarty->assign("ERROREXT",_tr($pExten->errMsg));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("VOICEMAIL_SETTINGS",_tr("Voicemail Settings"));
    $smarty->assign("MODULE_NAME",$module_name);
    $smarty->assign("id_exten", $idExten);
    $smarty->assign("USERLEVEL",$credentials['userlevel']);
    $smarty->assign("ORGANIZATION_LABEL",_tr("Organization Domain"));
    $smarty->assign("ORGANIZATION",$domain);
    $smarty->assign("CREATE_VM",_tr("Enabled Voicemail"));
    $smarty->assign("DEV_OPTIONS",_tr("Device Settings"));
    $smarty->assign("ADV_OPTIONS",_tr("Advanced Settings"));
    $smarty->assign("DICT_OPTIONS",_tr("Dictation Settings"));
    $smarty->assign("REC_OPTIONS",_tr("Recording Settings"));
    $smarty->assign("VM_OPTIONS",_tr("Voicemail Settings"));
    $smarty->assign("EXTENSION",_tr("GENERAL"));
    $smarty->assign("DEVICE",_tr("DEVICE"));
    $smarty->assign("VOICEMAIL",_tr("VOICEMAIL"));
    $smarty->assign("LOCATOR",_tr("Vmx Locator"));
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("Extensions"), $arrExten);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewExten($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials){
    $pExten = new paloSantoExtensions($pDB);
    $error = "";
    $continuar=true;
    $exito=false;

    $domain=getParameter('organization'); //este parametro solo es selecionable cuando es el superadmin quien crea la extension
    if($credentials['userlevel']!='superadmin'){
        $domain=$credentials['domain'];
    }

    $arrForm = createFieldForm();
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
        return viewFormExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else{
        $secret=getParameter("secret");
        if(!isStrongPassword($secret)){
            $error .=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
            $continuar=false;
        }

        $type=getParameter("technology");
        if(!isset($type) || !($type=="sip" || $type=="iax2")){
            $error .=_tr("You must select a technology");
            $continuar=false;
        }

        //no puede contener caracteres esoeciales ni salto de lineas
        $arrProp["fullname"]=getParameter("clid_name");
        if($arrProp["fullname"]!=''){
            if(!preg_match("/^[[:alnum:]_[[:space:]]]+$/",$arrProp["fullname"])){
                $error .=_tr("CID Name is invalid");
                $continuar=false;
            }
        }else{
            $arrProp["fullname"]=$exten;
        }
        
        $arrProp["clid_number"]=getParameter('clid_number');
        if($arrProp["clid_number"]!=''){
            if(!preg_match("/^[[:alnum:]_[[:space:]]]+$/",$arrProp["clid_number"])){
                $error .=_tr("CID Number is invalid");
                $continuar=false;
            }
        }else{
           $arrProp["clid_number"]=$exten;
        }
            
        if($continuar){
            //seteamos un arreglo con los parametros configurados
            $arrProp=array();
            $exten=getParameter("exten");
            $arrProp["name"]=getParameter("exten"); //nombre del device al que se le agrega como prefijo orgcode_
            $arrProp["exten"]=getParameter("exten");
            $arrProp['secret']=getParameter("secret");
            $arrProp['rt']=getParameter("ring_timer");
            $arrProp['record_in']=getParameter("record_in");
            $arrProp['record_out']=getParameter("record_out");
            $arrProp['language']=getParameter("language");
            $arrProp['out_clid']=getParameter("out_clid");
            $arrProp['callwaiting']=getParameter("call_waiting");
            $arrProp['screen']=getParameter("screen");
            $arrProp['dictate']=getParameter("dictate");
            $arrProp['dictformat']=getParameter("dictformat");
            $arrProp['dictemail']=getParameter("dictemail");
            //obtenemos los datos para la creacion de voicemail
            if(getParameter("create_vm")=="yes"){
                $vmpassword=getParameter("vmpassword");
                if(!preg_match('/^[[:digit:]]+$/',"$vmpassword")){
                    $error=_tr("Voicemail password cannot be empty and must only contain digits");
                    $continuar=false;
                }else{
                    $arrProp["create_vm"]="yes";
                    $arrProp["vmpassword"]=$vmpassword;
                    $arrProp["vmemail"]=getParameter("vmemail");
                    $arrProp["vmattach"]=getParameter("vmattach");
                    $arrProp["vmsaycid"]=getParameter("vmsaycid");
                    $arrProp["vmdelete"]=getParameter("vmdelete");
                    $arrProp["vmenvelope"]=getParameter("vmenvelope");
                    $arrProp["vmcontext"]=getParameter("vmcontext");
                    $arrProp["vmoptions"]=getParameter("vmoptions");
                    $arrProp["vmemailsubject"]=getParameter("vmemailsubject");
                    $arrProp["vmemailbody"]=getParameter("vmemailbody");
                    //vmx_locator settings
                    $arrProp["vmx_locator"]=getParameter("vmx_locator");
                    $arrProp["vmx_use"]=getParameter("vmx_use");
                    $arrProp["vmx_extension_0"]=getParameter("vmx_extension_0");
                    $arrProp["vmx_extension_1"]=getParameter("vmx_extension_1");
                    $arrProp["vmx_extension_2"]=getParameter("vmx_extension_2");
                    $arrProp["vmx_operator"]=getParameter("vmx_operator");
                    
                }
            }else{
                $arrProp["create_vm"]="no";
            }
        }

        if($continuar){
            $pDevice=new paloDevice($domain,$type,$pDB);
            $pDB->beginTransaction();
            $exito=$pDevice->createNewDevice($arrProp,$type);
            if($exito)
                $pDB->commit();
            else
                $pDB->rollBack();
            $error .=$pDevice->errMsg;
        }
    }

    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("Extension has been created successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }
    return $content;
}


function saveEditExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials){
    $pExten = new paloSantoExtensions($pDB);
    $error = "";
    $continuar=true;
    $exito=false;
    
    $idExten=getParameter("id_exten");

    //obtenemos la informacion del usuario por el id dado, sino existe la extension mostramos un mensaje de error
    if(!isset($idExten)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Exten"));
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }
    
    $domain=getParameter('organization');
    if($credentials['userlevel']!='superadmin'){
        $domain=$credentials['domain'];
    }
    $arrExten = $pExten->getExtensionById($idExten, $domain);
    
    if($arrExten===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pExten->errMsg));
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else if(count($arrExten)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Extension doesn't exist"));
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else{
        //comprobamos si la extension le pertenece a algun usuario
        //si le pertenece a un usuario el secret no puede ser editado
        $query = "Select username from acl_user where extension=? and id_group in (select g.id from acl_group g join organization o on g.id_organization=o.id where o.domain=?)";
        $result=$pDB->getFirstRowQuery($query,false,array($arrExten["exten"],$arrExten["domain"]));
        if($result===false){
            $error .="An error has ocurred to retrieved Extension data."." "._tr("DATABASE ERROR");
            $continuar=false;
        }elseif(count($result)>0){
            $secret="";
        }else{
            $secret=getParameter("secret");
        }
    
        $exten=$arrExten["exten"];
        if(isset($secret) && $secret!=""){
            if(!isStrongPassword($secret)){
                $error .=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
                $continuar=false;
            }
        }

        $type=$arrExten["technology"];
        if(!isset($type) || !($type=="sip" || $type=="iax2")){
            $error .=_tr("Invalid technology");
            $continuar=false;
        }

        //no puede contener caracteres esoeciales ni salto de lineas
        $arrProp["fullname"]=getParameter("clid_name");
        if($arrProp["fullname"]!=''){
            if(!preg_match("/^[[:alnum:]_[[:space:]]]+$/",$arrProp["fullname"])){
                $error .=_tr("CID Name is invalid");
                $continuar=false;
            }
        }else{
            $arrProp["fullname"]=$exten;
        }
        
        $arrProp["clid_number"]=getParameter('clid_number');
        if($arrProp["clid_number"]!=''){
            if(!preg_match("/^[[:alnum:]_[[:space:]]]+$/",$arrProp["clid_number"])){
                $error .=_tr("CID Number is invalid");
                $continuar=false;
            }
        }else{
           $arrProp["clid_number"]=$exten;
        }
        
        if($continuar){
            //seteamos un arreglo con los parametros configurados
            $arrProp=array();
            $arrProp["exten"]=$exten;
            $arrProp["name"]=$arrExten["device"];
            $arrProp["dial"]=$arrExten["dial"];
            $arrProp['secret']=getParameter("secret");
            $arrProp['rt']=getParameter("ring_timer");
            $arrProp['record_in']=getParameter("record_in");
            $arrProp['record_out']=getParameter("record_out");
            $arrProp['language']=getParameter("language");
            $arrProp['out_clid']=getParameter("out_clid");
            $arrProp['callwaiting']=getParameter("call_waiting");
            $arrProp['screen']=getParameter("screen");
            $arrProp['dictate']=getParameter("dictate");
            $arrProp['dictformat']=getParameter("dictformat");
            $arrProp['dictemail']=getParameter("dictemail");
            //obtenemos los datos para la creacion de voicemail
            if(getParameter("create_vm")=="yes"){
                $vmpassword=getParameter("vmpassword");
                if(!preg_match('/^[[:digit:]]+$/',"$vmpassword")){
                    $error=_tr("Voicemail password cannot be empty and must only contain digits");
                    $continuar=false;
                }else{
                    $arrProp["create_vm"]="yes";
                    $arrProp["vmpassword"]=$vmpassword;
                    $arrProp["vmemail"]=getParameter("vmemail");
                    $arrProp["vmattach"]=getParameter("vmattach");
                    $arrProp["vmsaycid"]=getParameter("vmsaycid");
                    $arrProp["vmdelete"]=getParameter("vmdelete");
                    $arrProp["vmenvelope"]=getParameter("vmenvelope");
                    $arrProp["vmcontext"]=getParameter("vmcontext");
                    $arrProp["vmoptions"]=getParameter("vmoptions");
                    $arrProp["vmemailsubject"]=getParameter("vmemailsubject");
                    $arrProp["vmemailbody"]=getParameter("vmemailbody");
                    //vmx_locator settings
                    $arrProp["vmx_locator"]=getParameter("vmx_locator");
                    $arrProp["vmx_use"]=getParameter("vmx_use");
                    $arrProp["vmx_extension_0"]=getParameter("vmx_extension_0");
                    $arrProp["vmx_extension_1"]=getParameter("vmx_extension_1");
                    $arrProp["vmx_extension_2"]=getParameter("vmx_extension_2");
                    $arrProp["vmx_operator"]=getParameter("vmx_operator");
                }
            }else{
                $arrProp["create_vm"]="no";
            }
        }

        if($continuar){
            $arrPropT=array_merge(propersParamByTech($type),$arrProp);
            $pDevice=new paloDevice($domain,$type,$pDB);
            $pDB->beginTransaction();
            $exito=$pDevice->editDevice($arrPropT);
            if($exito){
                $pDB->commit();
                //recargamos la configuracion en realtime para que tomen efecto los cambios hechos en el dispositivo
                $pDevice->tecnologia->prunePeer($arrExten["device"],$type);
                $pDevice->tecnologia->loadPeer($arrExten["device"],$type);
            }else{
                $pDB->rollBack();
            }
            $error .=$pDevice->errMsg;
        }
    }

    $smarty->assign("mostra_adv",getParameter("mostra_adv"));
    $smarty->assign("id_exten", $idExten);

    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("Extension has been edited successfully"));
        //mostramos el mensaje para crear los archivos de ocnfiguracion
        $pAstConf=new paloSantoASteriskConfig($pDB);
        $pAstConf->setReloadDialplan($domain,true);
        $content = reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }
    return $content;
}

function propersParamByTech($tech){
    if($tech=="sip"){
        $arrProp["context"]=getParameter("context");
        $arrProp['dtmfmode']=getParameter("dtmfmode");
        $arrProp['host']=getParameter("host");
        $arrProp['type']=getParameter("type");
        $arrProp['port']=getParameter("port");
        $arrProp['qualify']=getParameter("qualify");
        $arrProp['nat']=getParameter("nat");
        $arrProp['accountcode']=getParameter("accountcode");
        $arrProp['disallow']=getParameter("disallow");
        $arrProp['allow']=getParameter("allow");
        $arrProp['allowtransfer']=getParameter("allowtransfer");
        $arrProp['deny']=getParameter("deny");
        $arrProp['permit']=getParameter("permit");
        //$arrProp['mailbox']=getParameter("mailbox");
        $arrProp["vmexten"]=getParameter("vmexten");
        $arrProp['username']=getParameter("username");
        $arrProp['amaflags']=getParameter("amaflags");
        $arrProp['defaultuser']=getParameter("defaultuser");
        $arrProp['defaultip']=getParameter("defaultip");
        $arrProp['mohinterpret']=getParameter("mohinterpret");
        $arrProp['mohsuggest']=getParameter("mohsuggest");
        $arrProp['directmedia']=getParameter("directmedia");
        $arrProp['trustrpid']=getParameter("trustrpid");
        $arrProp['sendrpid']=getParameter("sendrpid");
        $arrProp['transport']=getParameter("transport");
        $arrProp['callcounter']=getParameter("callcounter");
        $arrProp['busylevel']=getParameter("busylevel");
        $arrProp['subscribecontext']=getParameter("subscribecontext");
        $arrProp['videosupport']=getParameter("videosupport");
        $arrProp['qualifyfreq']=getParameter("qualifyfreq");
        $arrProp['pickupgroup']=getParameter("pickupgroup");
        $arrProp['rtptimeout']=getParameter("rtptimeout");
        $arrProp['rtpholdtimeout']=getParameter("rtpholdtimeout");
        $arrProp['rtpkeepalive']=getParameter("rtpkeepalive");
        $arrProp['progressinband']=getParameter("progressinband");
        $arrProp['g726nonstandard']=getParameter("g726nonstandard");
        $arrProp['namedcallgroup']=getParameter("namedcallgroup");
        $arrProp['namedpickupgroup']=getParameter("namedpickupgroup");
    }else{
        $arrProp["context"]=getParameter("context");
        $arrProp['host']=getParameter("host");
        $arrProp['type']=getParameter("type");
        $arrProp['port']=getParameter("port");
        $arrProp['qualify']=getParameter("qualify");
        $arrProp['disallow']=getParameter("disallow");
        $arrProp['allow']=getParameter("allow");
        $arrProp['transfer']=getParameter("transfer");
        $arrProp['deny']=getParameter("deny");
        $arrProp['permit']=getParameter("permit");
        $arrProp["accountcode"]=getParameter("accountcode");
        $arrProp['requirecalltoken']=getParameter("requirecalltoken");
        $arrProp['username']=getParameter("username");
        $arrProp['amaflags']=getParameter("amaflags");
        $arrProp['defaultip']=getParameter("defaultip");
        $arrProp['mask']=getParameter("mask");
        $arrProp['mohinterpret']=getParameter("mohinterpret");
        $arrProp['mohsuggest']=getParameter("mohsuggest");
        $arrProp['jitterbuffer']=getParameter("jitterbuffer");
        $arrProp['forcejitterbuffer']=getParameter("forcejitterbuffer");
        $arrProp['codecpriority']=getParameter("codecpriority");
        $arrProp['qualifysmoothing']=getParameter("qualifysmoothing");
        $arrProp['qualifyfreqok']=getParameter("qualifyfreqok");
        $arrProp['qualifyfreqnotok']=getParameter("qualifyfreqnotok");
        $arrProp['encryption']=getParameter("encryption");
        $arrProp['timezone']=getParameter("timezone");
        $arrProp['sendani']=getParameter("sendani");
        $arrProp['adsi']=getParameter("adsi");
    }
    return $arrProp;
}

function deleteExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials){
    $pExten = new paloSantoExtensions($pDB);
    $error = "";
    $continuar=true;
    $exito=false;
    $idExten=getParameter("id_exten");

    //obtenemos la informacion de la extension por el id dado, en caso de que la extensionpertenzca a un usuario activo
    //esta no puede volver a ser borrada
    if(!isset($idExten)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Exten"));
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }
    
    $domain=getParameter('organization');
    if($credentials['userlevel']!='superadmin'){
        $domain=$credentials['domain'];
    }
    $arrExten = $pExten->getExtensionById($idExten, $domain);

    if($arrExten===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($pExten->errMsg));
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else if(count($arrExten)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Extension doesn't exist"));
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else{
        //commprobamos que la extension no le pertenezca a nigun usuario
        $query = "Select username from acl_user where extension=? and id_group in (select g.id from acl_group g join organization o on g.id_organization=o.id where o.domain=?)";
        $result=$pDB->getFirstRowQuery($query,false,array($arrExten["exten"],$arrExten["domain"]));
        if($result===false)
            $error=$pDB->errMsg;
        elseif(count($result)>0)
            $error=_tr("Extension can't be deleted because bellow to user ").$result[0];
        else{
            $pDevice=new paloDevice($domain,$arrExten["technology"],$pDB);
            $pDB->beginTransaction();
            $exito=$pDevice->deleteExtension($arrExten["exten"]);
            if($exito){
                $pDB->commit();
                //recargamos la configuracion en realtime para que tomen efecto los cambios hechos en el dispositivo
                $pDevice->tecnologia->prunePeer($arrExten["device"],$arrExten["technology"]);
            }else
                $pDB->rollBack();
        }
    }

    if($exito){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",_tr("The extensions was deleted successfully"));
        //mostramos el mensaje para crear los archivos de configuracion
        $pAstConf=new paloSantoASteriskConfig($pDB);
        $pAstConf->setReloadDialplan($domain,true);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($error));
    }

    return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
}

function createFieldForm($tech=null)
{
    $arrTech=array("sip"=>strtoupper("Sip"),"iax2"=>strtoupper("Iax2"));
    $arrRings=range("1","120");
    $arrRings[""]=_tr("Default");
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrYesNod=array("noset"=>"","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrWait=array("no"=>_tr("Disabled"),"yes"=>_tr("Enabled"));
    $arrRecord=array("on_demand"=>_tr("On demand"),"always"=>_tr("Always"),"never"=>_tr("Never"));
    $arrScreen=array("no"=>"disabled","memory"=>"memory","nomemory"=>"nomemory");
    $arrDictate=array("no"=>"disabled","yes"=>"enabled");
    $arrDictFor=array("ogg"=>"ogg","gsm"=>"gsm","wav"=>"wav");
    $arrLang=getLanguagePBX();
    $arrFormElements = array("exten" => array("LABEL"                  => _tr('Extension'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                "technology"  => array("LABEL"                  => _tr("Technology"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrTech,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),//accion en javascript
                                "secret"   => array("LABEL"                  => _tr("Secret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                "out_clid"   => array("LABEL"                  => _tr("Outbound CID"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                "language"       => array("LABEL"           => _tr("Language Code"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrLang,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                "ring_timer"       => array("LABEL"             => _tr("Ringtimer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRings,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "call_waiting"       => array("LABEL"            => _tr("Call Waiting"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrWait,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "record_in"       => array("LABEL"               => _tr("Record Incoming"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecord,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "record_out"       => array("LABEL"              => _tr("Record Outgoing"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrRecord,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmpassword"   => array("LABEL"                  => _tr("Voicemail Password"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmemail"   => array( "LABEL"                    => _tr("Voicemail Email"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "email",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmattach"   => array("LABEL"               => _tr("Email Attachment"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vmsaycid"   => array("LABEL"               => _tr("Play CID"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vmenvelope"   => array("LABEL"            => _tr("Play Envelope"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vmdelete"   => array("LABEL"               => _tr("Delete Voicemail"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "vmoptions"   => array("LABEL"               => _tr("Voicemail Options"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXTAREA",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:737px;resize:none"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "ROWS"                   => "2",
                                                    "COLS"                   => "1"),
                            "vmcontext"   => array("LABEL"               => _tr("Voicemail Context"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmemailsubject"   => array("LABEL"               => _tr("Email Subject"),
                                                    "DESCRIPTION"            => _tr("Email subject used at moment to send the email."),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:300px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmemailbody"   => array("LABEL"               => _tr("Email Body"),
                                                    "DESCRIPTION"            => _tr("Email Body. Until 512 characters"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXTAREA",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:500px;resize:none"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "ROWS"                   => "4",
                                                    "COLS"                   => "1"),
                            "clid_name"   => array("LABEL"               => _tr("CID Name"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "clid_number"   => array("LABEL"               => _tr("CID Number"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "dictate"       => array("LABEL"               => _tr("Dictate Service"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDictate,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "dictformat"       => array("LABEL"              => _tr("Dictate Format"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDictFor,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "dictemail"       => array("LABEL"               => _tr("Dictate Email"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "email",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "screen"       => array("LABEL"              => _tr("Screen Call"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrScreen,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmx_operator"   => array("LABEL"               => _tr("Go to Operator"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "CHECKBOX",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmx_extension_0"   => array("LABEL"               => _tr("Opcion 0"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmx_extension_1"   => array("LABEL"               => _tr("Opcion 1"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmx_extension_2"   => array("LABEL"               => _tr("Opcion 2"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmx_use" => array("LABEL"              => _tr("Use When"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("unavailable"=>_tr("Unavailable"),"busy"=>_tr("Busy"),"both"=>_tr("Unavailable & busy")),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmx_locator"       => array("LABEL"              => _tr("Use Locator"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("enabled"=>_tr("Enabled"),"disabled"=>_tr("Disabled")),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            
    );
    if(isset($tech)){
        if($tech=="sip"){
            $arrFormElements=array_merge($arrFormElements,createSipForm());
        }elseif($tech=="iax2"){
            $arrFormElements=array_merge($arrFormElements,createIaxForm());
        }
    }
    return $arrFormElements;
}

function createSipForm(){
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrYesNod=array("noset"=>"","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrType=array("friend"=>"friend","user"=>"user","peer"=>"peer");
    $arrDtmf=array('rfc2833'=>'rfc2833','info'=>"info",'shortinfo'=>'shortinfo','inband'=>'inband','auto'=>'auto');
    $arrMedia=array("noset"=>"",'yes'=>'yes','no'=>'no','nonat'=>'nonat','update'=>'update',"update,nonat"=>"update,nonat","outgoing"=>"outgoing");
    $arrAmaflag=array("noset"=>"","default"=>"default","omit"=>"omit","billing"=>"billing","documentation"=>"documentation");
    $transport=array("noset"=>"","udp"=>"UDP Only","tcp"=>"TCP Only","tls"=>"TLS Only","udp,tcp,tls"=>strtoupper("udp,tcp,tls"),"udp,tls,tcp"=>strtoupper("udp,tls,tcp"),"tcp,udp,tls"=>strtoupper("tcp,udp,tls"),"tcp,tls,udp"=>strtoupper("tcp,tls,udp"),"tls,udp,tcp"=>strtoupper("tls,udp,tcp"),"tls,tcp,udp"=>strtoupper("tls,tcp,udp"));
    $arrFormElements = array("type"  => array("LABEL"                  => _tr("type"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrType,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "context"  => array("LABEL"                  => _tr("context"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "host"   => array("LABEL"                  => _tr("host"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "port"   => array("LABEL"                  => _tr("port"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "qualify"       => array("LABEL"           => _tr("qualify"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "accountcode"   => array("LABEL"            => _tr("accountcode"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "allow"   => array("LABEL"                  => _tr("allow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "disallow"   => array("LABEL"                  => _tr("disallow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "nat"  => array("LABEL"                  => _tr("nat"),
                                                "DESCRIPTION"            => _tr("Address NAT-related issues in incoming SIP or media sessions.\nnat = no; Use rport if the remote side says to use it.\nnat = force_rport ; Pretend there was an rport parameter even if there wasn't.\nnat = comedia; Use rport if the remote side says to use it and perform comedia RTP handling.\nnat = auto_force_rport  ; Set the force_rport option if Asterisk detects NAT (default)\nnat = auto_comedia      ; Set the comedia option if Asterisk detects NAT\nNAT settings are a combinable list of options.\n The equivalent of the deprecated nat=yes is nat=force_rport,comedia.\nNot set this field if you do not know what are you doing"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => '',
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "dtmfmode"   => array( "LABEL"                  => _tr("dtmfmode"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDtmf,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "trustrpid"    =>  array("LABEL"        => _tr("trustrpid"),
                                                "DESCRIPTION"            => _tr("If Remote-Party-ID should be trusted"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                "VALIDATION_TYPE"        => "text", //yes
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "sendrpid"    =>  array("LABEL"        => _tr("sendrpid"),
                                                "DESCRIPTION"            => _tr("If Remote-Party-ID should be sent"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => array("no"=>"no","yes"=>"yes", "pai"=>"pai","yes,pai"=>"yes,pai"),
                                                "VALIDATION_TYPE"        => "text", //no
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "transport"    =>  array("LABEL"        => _tr("transport"),
                                                "DESCRIPTION"            => _tr("This sets the default transport type for outgoing.\nThe order determines the primary default transport.\nThe default transport type is only used for\noutbound messages until a Registration takes place.  During the\npeer Registration the transport type may change to another supported\ntype if the peer requests so.\nThe 'transport' part defaults to 'udp' but may also be 'tcp', 'tls', 'ws', or 'wss'\n"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => '',
                                                "VALIDATION_TYPE"        => "text", //no
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "mailbox"   => array( "LABEL"                  => _tr("mailbox"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px;","disabled"=>"disabled"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "vmexten" => array("LABEL"             => _tr("vmexten"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "mohinterpret"   => array( "LABEL"                  => _tr("mohinterpret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "mohsuggest" => array("LABEL"             => _tr("mohsuggest"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "allowtransfer"   => array( "LABEL"              => _tr("allowtransfer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "directmedia"   => array( "LABEL"              => _tr("directmedia"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMedia,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "amaflags"   => array( "LABEL"              => _tr("amaflags"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrAmaflag,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "username" => array("LABEL"             => _tr("username"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "defaultuser" => array("LABEL"             => _tr("defaultuser"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "defaultip" => array("LABEL"             => _tr("defaultip"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "busylevel" => array("LABEL"             => _tr("busylevel"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "callcounter"   => array( "LABEL"              => _tr("callcounter"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "subscribecontext"   => array( "LABEL"           => _tr("subscribecontext"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "videosupport"   => array( "LABEL"              => _tr("videosupport"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "maxcallbitrate" => array("LABEL"             => _tr("maxcallbitrate"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "qualifyfreq" => array("LABEL"             => _tr("qualifyfreq"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rtptimeout" => array("LABEL"             => _tr("rtptimeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rtpholdtimeout" => array("LABEL"             => _tr("rtpholdtimeout"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "rtpkeepalive" => array("LABEL"             => _tr("rtpkeepalive"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "progressinband" => array("LABEL"             => _tr("progressinband"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "g726nonstandard" => array("LABEL"             => _tr("g726nonstandard"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "namedcallgroup" => array("LABEL"             => _tr("Named Call Group"),
                                                    "DESCRIPTION"            => _tr("It works like callgroup parameter. The different is that parameter is not limit to number from 0 to 63"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "namedpickupgroup" => array("LABEL"             => _tr("Named PickUp Group"),
                                                    "DESCRIPTION"            => _tr("It works like pickupgroup parameter. The different is that parameter is not limit to number from 0 to 63"),
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
    $arrYesNod=array("noset"=>"","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrType=array("friend"=>"friend","user"=>"user","peer"=>"peer");
    $arrCallTok=array("yes"=>"yes","no"=>"no","auto"=>"auto");
    $arrCodecPrio=array("noset"=>"","host"=>"host","caller"=>"caller","disabled"=>"disabled","reqonly"=>"reqonly");
    $encryption=array("noset"=>"","aes128"=>"aes128","yes"=>"yes","no"=>"no");
    $arrAmaflag=array("noset"=>"","default"=>"default","omit"=>"omit","billing"=>"billing","documentation"=>"documentation");
    $arrFormElements = array("transfer"  => array("LABEL"                  => _tr("transfer"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrTrans,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "type"  => array("LABEL"                  => _tr("type"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "SELECT",
                                                "INPUT_EXTRA_PARAM"      => $arrType,
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "context"  => array("LABEL"                  => _tr("context"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "host"   => array("LABEL"                  => _tr("host"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "port"   => array("LABEL"                  => _tr("port"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "qualify"       => array("LABEL"           => _tr("qualify"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "accountcode"   => array("LABEL"            => _tr("accountcode"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "allow"   => array("LABEL"                  => _tr("allow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "disallow"   => array("LABEL"                  => _tr("disallow"),
                                                "REQUIRED"               => "no",
                                                "INPUT_TYPE"             => "TEXT",
                                                "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                "VALIDATION_TYPE"        => "text",
                                                "VALIDATION_EXTRA_PARAM" => ""),
                            "requirecalltoken" => array("LABEL"             => _tr("requirecalltoken"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCallTok,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "defaultip"   => array( "LABEL"                  => _tr("defaultip"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "mask"     => array("LABEL"                   => _tr("mask"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "mohinterpret"   => array( "LABEL"                  => _tr("mohinterpret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "mohsuggest" => array("LABEL"             => _tr("mohsuggest"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "username"   => array( "LABEL"                  => _tr("username"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "timezone"   => array( "LABEL"                  => _tr("timezone"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sendani" => array("LABEL"             => _tr("sendani"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "adsi" => array("LABEL"             => _tr("adsi"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "amaflags" => array("LABEL"             => _tr("amaflags"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrAmaflag,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "encryption" => array("LABEL"             => _tr("encryption"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $encryption,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "jitterbuffer" => array("LABEL"             => _tr("jitterbuffer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "forcejitterbuffer" => array("LABEL"             => _tr("forcejitterbuffer"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "codecpriority" => array("LABEL"             => _tr("codecpriority"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCodecPrio,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "qualifysmoothing" => array("LABEL"             => _tr("qualifysmoothing"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNod,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no|noset){1}$"),
                            "qualifyfreqok" => array("LABEL"             => _tr("qualifyfreqok"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "qualifyfreqnotok" => array("LABEL"             => _tr("qualifyfreqnotok"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "")
    );
    return $arrFormElements;
}

function createFieldFilter($arrOrgz)
{
    $arrFields = array(
        "organization"  => array("LABEL"         => _tr("Organization"),
                        "REQUIRED"               => "no",
                        "INPUT_TYPE"             => "SELECT",
                        "INPUT_EXTRA_PARAM"      => $arrOrgz,
                        "VALIDATION_TYPE"        => "domain",
                        "VALIDATION_EXTRA_PARAM" => ""),
        "extension"  => array("LABEL"            => _tr("Extension"),
                        "REQUIRED"               => "no",
                        "INPUT_TYPE"             => "TEXT",
                        "INPUT_EXTRA_PARAM"      => "",
                        "VALIDATION_TYPE"        => "text",
                        "VALIDATION_EXTRA_PARAM" => ""),
        );
    return $arrFields;
}


function reloadAasterisk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials){
    $showMsg=false;
    $continue=false;

    /*if($arrCredentiasls['userlevel']=="other"){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("You are not authorized to perform this action"));
    }*/

    $idOrganization=$credentials['id_organization'];
    if($credentials['userlevel']=="superadmin"){
        $idOrganization = getParameter("organization_id");
    }

    if($idOrganization==1){
        return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }

    $query="select domain from organization where id=?";
    $result=$pDB->getFirstRowQuery($query, false, array($idOrganization));
    if($result===false){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Asterisk can't be reloaded. ")._tr($pDB->errMsg));
        $showMsg=true;
    }elseif(count($result)==0){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Asterisk can't be reloaded. ")._tr("Invalid Organization. "));
        $showMsg=true;
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
            $showMsg=true;
        }else{
            $pAstConf->setReloadDialplan($domain);
            $smarty->assign("mb_title", _tr("MESSAGE"));
            $smarty->assign("mb_message",_tr("Asterisk was reloaded correctly. "));
        }
    }

    return reportExten($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
}

function getAction(){
    global $arrPermission;
    if(getParameter("create_exten"))
        return (in_array('create',$arrPermission))?'new_exten':'report';
    else if(getParameter("save_new")) //Get parameter by POST (submit)
        return (in_array('create',$arrPermission))?'save_new':'report';
    else if(getParameter("save_edit"))
        return (in_array('edit',$arrPermission))?'save_edit':'report';
    else if(getParameter("edit"))
        return (in_array('edit',$arrPermission))?'view_edit':'report';
    else if(getParameter("delete"))
        return (in_array('delete',$arrPermission))?'delete':'report';
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view";
    else if(getParameter("action")=="reloadAsterisk")
        return "reloadAasterisk";
    else
        return "report"; //cancel
}
?>
