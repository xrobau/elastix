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
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoOrganization.class.php";

//TODO: la seccion en la que se asignan las organizaciones que tienen permitida salir por la troncal
// debe ser impletada en un funcion aparte yno dentro de edit trunk
// el proposito de esto es evitar hacer un dialplan reload innecesario
// el cambiar las organizaciones permitidas no necesita madar a recargar el plan de marcado

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
        case "new_trunk":
            $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "view":
            $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "view_edit":
            $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "save_new":
            $content = saveNewTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "save_edit":
            $content = saveEditTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "delete":
            $content = deleteTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrCredentials);
            break;
        case "get_num_calls":
            $content = get_num_calls($smarty,$pDB,$arrCredentials);
            break;
        case "actDesactTrunk":
            $content = actDesactTrunk($smarty,$pDB);
            break;
        default: // report
            $content = reportTrunks($smarty, $module_name, $local_templates_dir, $pDB,$arrConf, $arrCredentials);
            break;
    }
    return $content;
}

function reportTrunks($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials)
{
    global $arrPermission;
    $pTrunk = new paloSantoTrunk($pDB);
    $pORGZ = new paloSantoOrganization($pDB);
    $error = "";
    
    $domain=getParameter("organization");
    $technology=getParameter("technology");
    $status=getParameter("status");
    
    $url['menu']=$module_name;
    $url['organization']=$domain;
    $url['technology']=$technology;
    $url['status']=$status;
    
    $total=$pTrunk->getNumTrunks($domain,$technology,$status);

    if($total===false){
        $error=$pTrunk->errMsg;
        $total=0;
    }

    $limit=20;

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();
    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $oGrid->setTitle(_tr('Trunks List'));
    $oGrid->setWidth("99%");
    $oGrid->setStart(($total==0) ? 0 : $offset + 1);
    $oGrid->setEnd($end);
    $oGrid->setTotal($total);
    $oGrid->setURL($url);

    $arrColum[]=_tr("Name");
    $arrColum[]=_tr("Technology");
    $arrColum[]=_tr("Channel / Peer Name");
    $arrColum[]=_tr("Max. Channels");
    $arrColum[]=_tr("Current # of calls by period");
    $arrColum[]=_tr("Status");
    $oGrid->setColumns($arrColum);

    $edit=in_array('edit',$arrPermission);
    $arrData = array();    
    if($total!=0){
        $arrTrunks=$pTrunk->getTrunks($domain,$technology,$status,$limit,$offset);
        if($arrTrunks===false){
            $error=_tr("Error to obtain trunks").$pTrunk->errMsg;
            $arrTrunks=array();
        }
        foreach($arrTrunks as $trunk){
            $arrTmp[0] = "&nbsp;<a href='?menu=trunks&action=view&id_trunk=".$trunk['trunkid']."&tech_trunk=".$trunk["tech"]."'>".$trunk['name']."</a>";
            $arrTmp[1] = strtoupper($trunk['tech']);
            $arrTmp[2] = $trunk['channelid'];
            $arrTmp[3] = $trunk['maxchans'];
            $state="";
            if($trunk['sec_call_time']=="yes"){
                $arrTmp[4] = createDivToolTip($trunk['trunkid'],$pTrunk,$state);
            }else
                $arrTmp[4] = _tr("Feature don't Set");     
                
            if($trunk['disabled']=="on" || $state=="YES")
                $disabled = "on";
            else
                $disabled = "off";
            if($edit)
                $arrTmp[5]=createSelect($trunk['trunkid'],$disabled);
            else{
                $arrTmp[5]=($disabled=='on')?_tr('Disabled'):_tr('Enabled');
            }
            $arrData[] = $arrTmp;
        }
    }

    if(in_array('create',$arrPermission)){
        $arrTech = array("sip"=>_tr("SIP"),"dahdi"=>_tr("DAHDI"), "iax2"=>_tr("IAX2"), "custom"=>_tr("CUSTOM"));
        $oGrid->addComboAction($name_select="tech_trunk",_tr("Create New Trunk"), $arrTech, $selected=null, $task="create_trunk", $onchange_select=null);
    }
    $arrOrgz=array(""=>"all");
    foreach(($pORGZ->getOrganization(array())) as $value){
        $arrOrgz[$value["domain"]]=$value["name"];
    }
    $_POST["organization"]=$domain;
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Organization Allow")." = ".$arrOrgz[$domain], $_POST, array("organization" => ""),true); //organization allow
    
    $techFilter=array(''=>'All',"sip"=>_tr("SIP"),"dahdi"=>_tr("DAHDI"), "iax2"=>_tr("IAX2"), "custom"=>_tr("CUSTOM"));
    $_POST["technology"]=$technology;
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Type")." = ".$techFilter[$technology], $_POST, array("technology" => ""),true); //technology
    
    $arrStatus=array(''=>'','off'=>_tr('Enabled'),'on'=>_tr('Disabled'));
    $_POST["status"]=$status;
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Status")." = ".$arrStatus[$status], $_POST, array("status" => ""),true); //status
    
    $smarty->assign("SEARCH","<input type='submit' class='button' value='"._tr('Search')."' name='search'>");
    
    $arrFormElements = createFieldFilter($arrOrgz,$techFilter,$arrStatus);
    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
    $oGrid->showFilter(trim($htmlFilter));

    if($error!=""){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        $smarty->assign("mb_message",$error);
    }

    $contenidoModulo = $oGrid->fetchGrid(array(), $arrData);
    $contenidoModulo .="<input type='hidden' name='grid_limit' id='grid_limit' value='$limit'>";
    $contenidoModulo .="<input type='hidden' name='grid_offset' id='grid_offset' value='$offset'>";
    return $contenidoModulo;
}

function createDivToolTip($trunkid,$pTrunk,&$block){
    $arrSec=array();
    $res=$pTrunk->getSecTimeASTDB($trunkid);
    
    $block="NO";
    $fail=0;
    $style=$tmp_block="";
    if($res['BLOCK']=="true"){
        $block="YES";
        $tmp_block=" / BLOCKED";
        $fail=(int)$res['NUM_FAIL'];
        $style = "style='color: red; font-weight:bold'";
    }
    $count=$res["COUNT"];
    
    $start="--";
    if(isset($res["START_TIME"])){
        $start=strftime("%D - %T",(int)$res["START_TIME"]);
    }
    
    //recibimos el periodo en minutos y lo llevamos asegundos
    $perid_sec=(int)$res["period_time"]*60;  
    if((int)$res["period_time"]<59){
        $period=$res["period_time"]." min.";
    }else{
        $period=($res["period_time"] / 60)." h.";
    }
    
    $elapsed_sec=fmod((time()-(int)$res["START_TIME"]),$perid_sec);
    $elap_h=floor($elapsed_sec / 3600);
    $elap_m=floor(fmod($elapsed_sec,3600) / 60);
    $elap=$elap_h.":".$elap_m;
    
    $max=$res["maxcalls_time"];
    $count=$res["COUNT"];
    
    $div ="<div class='trunk_tooltip'>
        <p class='start_point'><label>"._tr("Applied Since").": </label><span>$start</span></p>
        <p class='time_period'><label>"._tr("Period Duration").": </label><span>$period</span></p>
        <p class='elapsed_time'><label>"._tr("Elapsed Time Since Last Period").": </label><span>$elap</span></p>
        <p class='max_calls'><label>"._tr("Max Number of Calls").": </label><span>$max</span></p>
        <p class='count_calls'><label>"._tr("Current Number of Calls").": </label><span>$count</span></p>
        <p class='state'><label>"._tr("Blocked").": </label><span>$block</span></p>
        <p class='fail_calls'><label>"._tr("Number of Fail Calls").": </label><span>$fail</span></p>
     </div>";
     
     return "<div class='sec_trunk'><p class='num_calls' id='".$trunkid."' $style>".$count.$tmp_block."</p>".$div."</div>";
}

function createSelect($id,$disabled){
    $arr=array("on"=>_tr('Disabled'),"off"=>_tr('Enabled')); //la logica es invertida
    $field="<select id='sel_$id' name='state_trunk' class='state_trunk' >";
    foreach($arr as $key => $value){
        $select="";
        if($disabled==$key)
            $select="selected";
        $field .="<option value='$key' $select>$value</option>";
    }
    $field .="</select>";
    return $field;
}

function get_num_calls($smarty,&$pDB,$credentials){
    $pTrunk = new paloSantoTrunk($pDB);
    $error=$pagging="";
    $arrParam=$arrTrunk=array();
    $jsonObject=new PaloSantoJSON();
    $limit=getParameter("limit");
    $offset=getParameter("offset");
    
    if(preg_match("/^[0-9]+$/",$limit) && preg_match("/^[0-9]+$/",$offset)){
        $pagging=" limit ? offset ?";
        $arrParam[]=(int)$limit;
        $arrParam[]=(int)$offset;
    }
    
    $query="SELECT trunkid,sec_call_time from trunk $pagging";
    $result=$pDB->fetchTable($query,true,$arrParam);
    if($result==false){
        if($result===false)
            $jsonObject->set_error($pDB->errMsg);
        else
            $jsonObject->set_error("There aren't trunks");
    }else{
        foreach($result as $value){
            if($value["sec_call_time"]=="yes"){
                $block=$style="";
                $res=$pTrunk->getSecTimeASTDB($value["trunkid"]);
                $fail=0;
                if($res['BLOCK']=="true"){
                    $block = " / BLOCKED";
                    $style = "style='color: red; font-weight:bold'";
                    $fail=(int)$res['NUM_FAIL'];
                }
                $arrTrunk[$value["trunkid"]]["p"]="<p class='num_calls' id='".$value['trunkid']."' $style>".$res['COUNT'].$block."</p>";
                
                //tiempo transcurrido desde el ultimo periodo
                $elapsed_sec=fmod((time()-(int)$res["START_TIME"]),(int)$res["period_time"]*60);
                $elap_h=floor($elapsed_sec / 3600);
                $elap_m=floor(fmod($elapsed_sec,3600) / 60);
                $elap=$elap_h.":".$elap_m;
                
                $arrTrunk[$value["trunkid"]]["elapsed_time"]=$elap;
                $arrTrunk[$value["trunkid"]]["count_calls"]=$res['COUNT'];
                $arrTrunk[$value["trunkid"]]["state"]=($block=="")?"NO":"YES";
                $arrTrunk[$value["trunkid"]]["fail_calls"]=$fail;
            }else
                $arrTrunk[$value["trunkid"]]["p"]="";
        }
        $jsonObject->set_message($arrTrunk);
    }
    
    sleep(2);
    return $jsonObject->createJSON();
}

function actDesactTrunk($smarty,&$pDB){
    $pTrunk=new paloSantoTrunk($pDB);
    $error="";
    $idTrunk=getParameter("id_trunk");
    $action=getParameter("trunk_action");
    $jsonObject=new PaloSantoJSON();
    
    if(!preg_match('/^[[:digit:]]+$/', "$idTrunk")){
        $error=_tr("Invalid Trunk Id");
    }else{
        $pDB->beginTransaction();
        $result=$pTrunk->actDesacTrunk($idTrunk,$action);
        if($result==true){
            $pDB->commit();
        }else{
            $error=$pTrunk->errMsg();
            $pDB->rollBack();
        }
    }
    
    if($error!=""){
        $jsonObject->set_error($error);
    }else{
        $state=($action=="on")?"desactivated":"activated";    
        $jsonObject->set_message(_tr("Trunk have been $state successfully"));
    }
    return $jsonObject->createJSON();
}


function viewFormTrunk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials,$arrDialPattern=array()){
    global $arrPermission;
    $error = "";
    $pTrunk = new paloSantoTrunk($pDB);
    $pORGZ = new paloSantoOrganization($pDB);

    $arrTrunks=array();
    $action = getParameter("action");
    
    $idTrunk=getParameter("id_trunk");
    if($action=="view" || $action=="view_edit" || getParameter("edit") || getParameter("save_edit")){
        if(!isset($idTrunk)){
            $error=_tr("Invalid Trunk");
        }else{
            $arrTrunks = $pTrunk->getTrunkById($idTrunk);
            if($arrTrunks===false){
                $error=_tr($pTrunk->errMsg);
            }else if(count($arrTrunks)==0){
                $error=_tr("Trunk doesn't exist");
            }else{
                if($error!=""){
                    $smarty->assign("mb_title", _tr("ERROR"));
                    $smarty->assign("mb_message",$error);
                    return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf,$credentials);
                }
                $tech=$arrTrunks["tech"];
                if($tech=="sip" || $tech=="iax2")
                    $smarty->assign('NAME',$arrTrunks["name"]);
                
                $smarty->assign('j',0);
                if($action=="view"|| getParameter("edit") ){
                    $arrDialPattern = $pTrunk->getArrDestine($idTrunk);
                }
                $smarty->assign('items',$arrDialPattern);
                
                if(getParameter("save_edit")){
                    if(isset($_POST["select_orgs"]))
                        $smarty->assign("ORGS",$_POST["select_orgs"]);
                    $arrTrunks=$_POST;
                }else{
                    $select_orgs=implode(",",$arrTrunks["select_orgs"]);
                    if(isset($arrTrunks["select_orgs"]))
                        $smarty->assign("ORGS",$select_orgs.",");
                }
                
                if($arrTrunks["sec_call_time"]=="yes")
                    $smarty->assign("SEC_TIME","yes");
                    
                if($action=="view"){
                    $smarty->assign("ORGS",$select_orgs);
                }
            }
		}
	}else{
        $tech = getParameter("tech_trunk");
        $smarty->assign('j',0);
        $smarty->assign('items',$arrDialPattern);
        
        if(getParameter("create_trunk")){
            $arrTrunks=$pTrunk->getDefaultConfig($tech);
        }else{
            if(isset($_POST["select_orgs"]))
                $smarty->assign("ORGS",$_POST["select_orgs"]);
            $arrTrunks=$_POST;
        }
    }
    
    $smarty->assign("EDIT",in_array('edit',$arrPermission));
    $smarty->assign("DELETE",in_array('delete',$arrPermission));

    /*	if(!preg_match("/^(sip|iax2|dahdi|custom){1}$/",$tech)){
        $error=_tr("Invalid Technology");
    }*/

    if($error!=""){
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message",$error." ".$pTrunk->errMsg);
        return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }
    
    $arrTmp=$pORGZ->getOrganization(array());
    $arrOrgz=array(0=>"--pickup organizations--");
    foreach($arrTmp as $value){
        $arrOrgz[$value["domain"]]=$value["domain"];
    }
    $arrForm = createFieldForm($tech,$arrOrgz);
    $oForm = new paloForm($smarty,$arrForm);

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
    
    if($tech=="dahdi")
        $smarty->assign("NAME_CHANNEL",_tr("DHADI Channel"));
    else
        $smarty->assign("NAME_CHANNEL",_tr("CUSTOM Channel"));
        
    $smarty->assign("TECH",strtoupper($tech));
    $smarty->assign("PEER_Details",_tr("Peer Details"));
    $smarty->assign("ADV_OPTIONS",_tr("Advanced Settings"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("MODULE_NAME",$module_name);
    $smarty->assign("id_trunk", $idTrunk);
    $smarty->assign("tech_trunk", $tech);
    $smarty->assign("PREPEND", _tr("Prepend"));
    $smarty->assign("PREFIX", _tr("Prefix"));
    $smarty->assign("MATCH_PATTERN", _tr("Match Pattern"));
    $smarty->assign("RULES", _tr("Dialed Number Manipulation Rules"));
    $smarty->assign("GENERAL", _tr("General"));
    $smarty->assign("SETTINGS", _tr("Peer Settings"));
    $smarty->assign("REGISTRATION", _tr("Registration"));
    $smarty->assign("SEC_SETTINGS", _tr("Security Settings"));
    $smarty->assign("ORGANIZATION_PERM",_tr("Organizations Allowed"));
    
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl",_tr("Trunk")." ".strtoupper($tech), $arrTrunks);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewTrunk($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $credentials){
    $pTrunk = new paloSantoTrunk($pDB);
    $error = "";
    $continue=true;
    $successTrunk=false;

    $tech  = getParameter("tech_trunk");
    if(!preg_match("/^(sip|iax2|dahdi|custom){1}$/",$tech)){
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr("Invalid Trunk Technology"));
        return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }
    
	$arrForm = createFieldForm($tech,array());
    $oForm = new paloForm($smarty,$arrForm);

    $arrDialPattern = getParameter("arrDestine");
    $tmpstatus = explode(",",$arrDialPattern);
    $arrDialPattern = array_values(array_diff($tmpstatus, array('')));
    $tmp_dial=array();
    foreach($arrDialPattern as $pattern){
        $prepend = getParameter("prepend_digit".$pattern);
        $prefix = getParameter("pattern_prefix".$pattern);
        $pattern = getParameter("pattern_pass".$pattern);
        $tmp_dial[]=array(0,$prefix,$pattern,$prepend);
    }
    
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
        return viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials,$tmp_dial);
    }else{
        $arrProp=array();
        $arrProp["tech"]=$tech;
        $arrProp["trunk_name"]=getParameter("trunk_name");
        $arrProp['outcid']=getParameter("outcid");
        $arrProp['keepcid']=getParameter("keepcid");
        $arrProp['max_chans']=getParameter("maxchans");
        $arrProp['disabled'] = getParameter("disabled");
        $arrProp['dialout_prefix']=getParameter("dialoutprefix");
        $arrProp["select_orgs"]=getParameter("select_orgs");
        $arrProp["sec_call_time"]=getParameter("sec_call_time");
        $arrProp["maxcalls_time"]=getParameter("maxcalls_time");
        $arrProp["period_time"]=getParameter("period_time");
        
        if($arrProp["sec_call_time"]=="yes"){
            if(!preg_match("/^[0-9]+$/",$arrProp["maxcalls_time"])){
                $error=_tr("Field 'Max Num Calls' can't be empty");
                $continue=false;
            }
            if(!preg_match("/^[0-9]+$/",$arrProp["period_time"])){
                $error=_tr("Field 'Period of Time' can't be empty");
                $continue=false;
            }
        }
        
        if($tech=="dahdi" || $tech=="custom"){
            $arrProp["channelid"]=getParameter("channelid");
            $ttt=($tech=="dahdi")?_tr("DAHDI Identifier"):_tr("Dial String");
            if(empty($arrProp["channelid"])){
                $error=_tr("Field $ttt can't be empty");
                $continue=false;
            }
            if($tech=="dahdi"){
                if(!preg_match("/^(g|r){0,1}[0-9]+$/",$arrProp["channelid"])){
                    $error=_tr("Field DAHDI Identifier can't be empty and must be a dahdi number or channel number")._tr(" Ex: g0");
                    $continue=false;
                }
            }
        }elseif($tech=="sip" || $tech=="iax2"){
            $arrProp["secret"]=getParameter("secret");
            if(!isStrongPassword($arrProp["secret"])){
                $error=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
                $continuar=false;
            }
            $arrProp=array_merge(getSipIaxParam($tech),$arrProp);
        }

		if($continue){
			$pDB->beginTransaction();
			$successTrunk=$pTrunk->createNewTrunk($arrProp,$tmp_dial);
			if($successTrunk)
				$pDB->commit();
			else
				$pDB->rollBack();
			$error .=$pTrunk->errMsg;
		}
	}

	if($successTrunk){
		$smarty->assign("mb_title", _tr("MESSAGE"));
		if(writeAsteriskFile($error,$tech)==true)
            $smarty->assign("mb_message",_tr("Trunk has been created successfully"));
        else
            $smarty->assign("mb_message",_tr("Error: Trunk has been created. ").$error);
        $content = reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
	}else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message",$error);
		$content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials,$tmp_dial);
	}
	return $content;
}

function saveEditTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials){
    $pTrunk = new paloSantoTrunk($pDB);
    $error = "";
    //conexion elastix.db
    $continue=true;
    $successTrunk=false;
    
    $idTrunk=getParameter("id_trunk");

    //obtenemos la informacion del usuario por el id dado, sino existe el trunk mostramos un mensaje de error
    if(!isset($idTrunk)){
        $error=_tr("Invalid Trunk");
    }else{
        $arrTrunks = $pTrunk->getTrunkById($idTrunk);
        if($arrTrunks===false){
            $error=_tr($pTrunk->errMsg);
        }else if(count($arrTrunks)==0){
            $error=_tr("Trunk doesn't exist");
        }else{
            if($error!=""){
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message",$error);
                return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
            }

            $arrDialPattern = getParameter("arrDestine");
            $tmpstatus = explode(",",$arrDialPattern);
            $arrDialPattern = array_values(array_diff($tmpstatus, array('')));
            $tmp_dial=array();
            foreach($arrDialPattern as $pattern){
                $prepend = getParameter("prepend_digit".$pattern);
                $prefix = getParameter("pattern_prefix".$pattern);
                $pattern = getParameter("pattern_pass".$pattern);
                $tmp_dial[]=array(0,$prefix,$pattern,$prepend);
            }
            
            $tech=$arrTrunks["tech"];
            
            $arrProp=array();
            $arrProp["id_trunk"]=$idTrunk;
            $arrProp["trunk_name"]=getParameter("trunk_name");
            $arrProp['outcid']=getParameter("outcid");
            $arrProp['keepcid']=getParameter("keepcid");
            $arrProp['max_chans']=getParameter("maxchans");
            $arrProp['disabled'] = getParameter("disabled");
            $arrProp['dialout_prefix']=getParameter("dialoutprefix");
            $arrProp["select_orgs"]=getParameter("select_orgs");
            $arrProp["sec_call_time"]=getParameter("sec_call_time");
            $arrProp["maxcalls_time"]=getParameter("maxcalls_time");
            $arrProp["period_time"]=getParameter("period_time");
            
            if($arrProp["sec_call_time"]=="yes"){
                if(!preg_match("/^[0-9]+$/",$arrProp["maxcalls_time"])){
                    $error=_tr("Field 'Max Num Calls' can't be empty");
                    $continue=false;
                }
                if(!preg_match("/^[0-9]+$/",$arrProp["period_time"])){
                    $error=_tr("Field 'Period of Time' can't be empty");
                    $continue=false;
                }
            }
            
            if($tech=="dahdi" || $tech=="custom"){
                $arrProp["channelid"]=getParameter("channelid");
                if($tech=="dahdi"){
                    if(!preg_match("/^(g|r){0,1}[0-9]+$/",$arrProp["channelid"])){
                        $error=_tr("Field DAHDI Identifier can't be empty and must be a dahdi number or channel number");
                        $continue=false;
                    }
                }
            }elseif($tech=="sip" || $tech=="iax2"){
                $arrProp["secret"]=getParameter("secret");
                if($arrProp["secret"]!=""){
                    if(!isStrongPassword($arrProp["secret"])){
                        $error=_tr("Secret can not be empty, must be at least 10 characters, contain digits, uppers and little case letters");
                        $continuar=false;
                    }
                }
                $arrProp=array_merge(getSipIaxParam($tech,true),$arrProp);
            }

            if($continue){
                $pDB->beginTransaction();
                $successTrunk=$pTrunk->updateTrunkPBX($arrProp,$tmp_dial);
                
                if($successTrunk)
                    $pDB->commit();
                else
                    $pDB->rollBack();
            }
            $error .=$pTrunk->errMsg;
        }
	}

    //$smarty->assign("mostra_adv",getParameter("mostra_adv"));
    $smarty->assign("id_trunk", $idTrunk);

    if($successTrunk){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        //recargamos la configuracion del peer en caso que la truncal haya sido iax o sip
        if($arrTrunks["tech"]=="sip" || $arrTrunks["tech"]=="iax2"){
            $pTrunk->prunePeer($arrTrunks["name"],$arrTrunks["tech"]);
            $pTrunk->loadPeer($arrTrunks["name"],$arrTrunks["tech"]);
        }
        if(writeAsteriskFile($error,$tech)==true)
            $smarty->assign("mb_message",_tr("Trunk has been edited successfully"));
        else
            $smarty->assign("mb_message",_tr("Error: Trunk has been edited. ").$error);
        $content = reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",$error);
        $content = viewFormTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials,$tmp_dial);
    }
	return $content;
}

function getSipIaxParam($tech,$edit=false){
    $arrProp=array();
    $arrProp["context"]=getParameter("context");
    $arrProp["name"]=getParameter("name");
    $arrProp["register"]=getParameter("register");
    $arrProp["type"]=getParameter("type");
    $arrProp["username"]=getParameter("username");
    $arrProp["host"]=getParameter("host");
    $arrProp["qualify"]=getParameter("qualify");
    $arrProp["disallow"]=getParameter("disallow");
    $arrProp["allow"]=getParameter("allow");
    $arrProp["amaflags"]=getParameter("amaflags");
    $arrProp["deny"]=getParameter("deny");
    $arrProp["permit"]=getParameter("permit");
    if($tech=="sip"){
        $arrProp["insecure"]=getParameter("insecure");
        $arrProp["nat"]=getParameter("nat");
        $arrProp["dtmfmode"]=getParameter("dtmfmode");
        if($edit){
            $arrProp["fromuser"]=getParameter("fromuser");
            $arrProp["fromdomain"]=getParameter("fromdomain");
            $arrProp["sendrpid"]=getParameter("sendrpid");
            $arrProp["directmedia"]=getParameter("directmedia");
            $arrProp["useragent"]=getParameter("useragent");
            $arrProp["videosupport"]=getParameter("videosupport");
            $arrProp["maxcallbitrate"]=getParameter("maxcallbitrate");
            $arrProp["qualifyfreq"]=getParameter("qualifyfreq");
            $arrProp["rtptimeout"]=getParameter("rtptimeout");
            $arrProp["rtpholdtimeout"]=getParameter("rtpholdtimeout");
            $arrProp["rtpkeepalive"]=getParameter("rtpkeepalive");
        }
    }elseif($tech=="iax2"){
        $arrProp["auth"]=getParameter("auth");
        $arrProp["trunk"]=getParameter("trunk");
        if($edit){
            $arrProp["trunkfreq"]=getParameter("trunkfreq");
            $arrProp["trunktimestamps"]=getParameter("trunktimestamps");
            $arrProp["sendani"]=getParameter("sendani");
            $arrProp["adsi"]=getParameter("adsi");
            $arrProp["requirecalltoken"]=getParameter("requirecalltoken");
            $arrProp["encryption"]=getParameter("encryption");
            $arrProp["jitterbuffer"]=getParameter("jitterbuffer");
            $arrProp["forcejitterbuffer"]=getParameter("forcejitterbuffer");
            $arrProp["codecpriority"]=getParameter("codecpriority");
            $arrProp["qualifysmoothing"]=getParameter("qualifysmoothing");
            $arrProp["qualifyfreqok"]=getParameter("qualifyfreqok");
            $arrProp["qualifyfreqnotok"]=getParameter("qualifyfreqnotok");
        }
    }
    return $arrProp;
}

function deleteTrunk($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials){

    $pTrunk = new paloSantoTrunk($pDB);
    $error = "";
    //conexion elastix.db
    $continue=true;
    $successTrunk=false;
    
    $idTrunk=getParameter("id_trunk");

	if(!isset($idTrunk)){
        $error=_tr("Invalid Trunk");
    }else{
        $arrTrunks = $pTrunk->getTrunkById($idTrunk);
        if($arrTrunks===false){
            $error=_tr($pTrunk->errMsg);
        }else if(count($arrTrunks)==0){
            $error=_tr("Trunk doesn't exist");
        }else{
            if($error!=""){
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message",$error);
                return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);
            }
            $pDB->beginTransaction();
            $successTrunk = $pTrunk->deleteTrunk($idTrunk);
            if($successTrunk)
                $pDB->commit();
            else
                $pDB->rollBack();
            $error .=$pTrunk->errMsg;
        }
	}

    if($successTrunk){
        $smarty->assign("mb_title", _tr("MESSAGE"));
        //quitamos al peer de cache en caso que la truncal haya sido iax o sip
        if($arrTrunks["tech"]=="sip" || $arrTrunks["tech"]=="iax2"){
            $pTrunk->prunePeer($arrTrunks["name"],$arrTrunks["tech"]);
        }
        if(writeAsteriskFile($error,$arrTrunks["tech"])==true)
            $smarty->assign("mb_message",_tr("Trunk was deleted successfully"));
        else
            $smarty->assign("mb_message",_tr("Error: Trunk was deleted. ").$error);
    }else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message",_tr($error));
    }

	return reportTrunks($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $credentials);;
}

function writeAsteriskFile(&$error,$tech){
    if($tech=="sip" || $tech=="iax2"){
        $sComando = "/usr/bin/elastix-helper asteriskconfig writeTechRegister $tech 2>&1";
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $error = _tr("Error writing $tech.conf file").implode('', $output);
            return FALSE;
        }
    }
    
    $sComando = '/usr/bin/elastix-helper asteriskconfig createExtGeneral 2>&1';
    $output = $ret = NULL;
    exec($sComando, $output, $ret);
    if ($ret != 0) {
        $error = _tr("Error writing extensions_additionals file").implode('', $output);
        return FALSE;
    }
    
    $sComando = '/usr/bin/elastix-helper asteriskconfig dialplan-reload 2>&1';
    $output = $ret = NULL;
    exec($sComando, $output, $ret);
    if ($ret != 0){
        $error = implode('', $output);
        return FALSE;
    }
    
    return true;
}



function createFieldForm($tech,$arrOrgz)
{
    $arrCid=array("off"=>_tr("Allow Any CID"), "on"=>_tr("Block Foreign CIDs"), "cnum"=>_tr("Remove CNAM"), "all"=>_tr("Force Trunk CID"));
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrAmaflag=array("noset"=>"noset","default"=>"default","omit"=>"omit","billing"=>"billing","documentation"=>"documentation");
    $auth=array("md5"=>"md5","plaintext"=>"plaintext","rsa"=>"rsa");
    $arrNat=array("noset"=>"","no"=>"no","force_rport"=>"force_rport","yes"=>"yes","comedia"=>"comedia");
    $arrType=array("friend"=>"friend","peer"=>"peer");
    $arrDtmf=array('rfc2833'=>'rfc2833','info'=>"info",'shortinfo'=>'shortinfo','inband'=>'inband','auto'=>'auto');
    $arrPeriod=array(5=>"5 min",10=>"10 min",15=>"15 min",30=>"30 min",45=>"45",60=>"1 hora",120=>"2 horas",180=>"3 horas",240=>"4 horas",300=>"5 horas",360=>"6 horas",600=>"10 horas",720=>"12 horas",900=>"15 horas",1200=>"20 horas",1440=>"1 dia");
    
    $arrStatus=array('off'=>_tr('Enabled'),'on'=>_tr('Disabled'));

    $arrFormElements = array("trunk_name"	=> array("LABEL"                  => _tr('Descriptive Name'),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "keepcid" 	=> array("LABEL"                => _tr("CID Options"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCid,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),//accion en javascript
                             "outcid"   => array("LABEL"                  => _tr("Outbound Caller ID"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "maxchans" => array("LABEL"                   => _tr("Max # Current Calls"),
                                                     "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "dialoutprefix" => array("LABEL"         => _tr("Outbound Dial Prefix"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:100px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "disabled" => array("LABEL"         => _tr("Status"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrStatus,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "prepend_digit__" => array("LABEL"               => _tr("prepend digit"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:60px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pattern_prefix__" => array("LABEL"               => _tr("pattern prefix"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:40px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "pattern_pass__" => array("LABEL"               => _tr("pattern pass"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:150px;text-align:center;"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "org"            => array("LABEL"                => _tr("Organization"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrOrgz,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "period_time"    => array("LABEL"                => _tr("Period of Time"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrPeriod,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "maxcalls_time"  => array("LABEL"                => _tr("Max # Calls"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sec_call_time"  => array("LABEL"                => _tr("Set Max # Calls By time"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            );
    if($tech=="dahdi" || $tech=="custom"){
        $ttt=($tech=="dahdi")?_tr("DAHDI Identifier"):_tr("Dial String");
        $arrFormElements["channelid"] = array("LABEL"                  => $ttt,
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
    }else{
        $arrFormElements["name"] =  array("LABEL"                 => _tr("Name Peer"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["register"] =  array("LABEL"                 => _tr("Register String"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:400px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["type"] =  array("LABEL"                       => _tr("type"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrType,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(friend|peer){1}$");
        $arrFormElements["secret"] =  array("LABEL"                  => _tr("secret"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["username"] =  array("LABEL"                  => _tr("username"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["host"] =  array("LABEL"                  => _tr("host"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["qualify"] =  array("LABEL"                  => _tr("qualify"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["context"] =  array("LABEL"                  => _tr("context"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");     
        $arrFormElements["allow"] =  array("LABEL"                  => _tr("allow"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["disallow"] =  array("LABEL"                  => _tr("disallow"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["deny"] =  array("LABEL"                  => _tr("deny"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["permit"] =  array("LABEL"                  => _tr("permit"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["amaflags"] =  array("LABEL"                  => _tr("amaflags"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrAmaflag,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        if($tech=="sip"){
        $arrFormElements["insecure"] =  array("LABEL"                  => _tr("insecure"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("invite"=>"invite","port"=>"port","port,invite"=>"port,invite"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(invite|port|port,invite){1}$");
        $arrFormElements["nat"] =  array("LABEL"                  => _tr("nat"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrNat,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements["dtmfmode"] =  array("LABEL"                  => _tr("dtmfmode"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDtmf,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "");
        $arrFormElements=array_merge($arrFormElements,createSipFrom());
        }elseif($tech=="iax2"){
        $arrFormElements["auth"] =  array("LABEL"                  => _tr("auth"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $auth,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(md5|plaintext|rsa){1}$");
        $arrFormElements["trunk"] =  array("LABEL"                  => _tr("trunk"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrYesNo,
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$");
        $arrFormElements=array_merge($arrFormElements,createIaxFrom());
        }
    }
	
	return $arrFormElements;
}

function createSipFrom(){
    $arrYesNod=array("noset"=>"noset","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrYesNo=array("yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrMedia=array("noset"=>"",'yes'=>'yes','no'=>'no','nonat'=>'nonat','update'=>'update',"update,nonat"=>"update,nonat","outgoing"=>"outgoing");
    $arrFormElements = array("fromuser" => array("LABEL"             => _tr("fromuser"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "fromdomain" => array("LABEL"             => _tr("fromdomain"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sendrpid" => array("LABEL"             => _tr("sendrpid"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "trustrpid" => array("LABEL"             => _tr("trustrpid"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "directmedia"   => array( "LABEL"              => _tr("directmedia"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrMedia,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "useragent" => array("LABEL"             => _tr("useragent"),
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
    );
    return $arrFormElements;
}

function createIaxFrom(){
    $arrYesNod=array("noset"=>"noset","yes"=>_tr("Yes"),"no"=>_tr("No"));
    $arrCallTok=array("yes"=>"yes","no"=>"no","auto"=>"auto");
    $arrCodecPrio=array("noset"=>"noset","host"=>"host","caller"=>"caller","disabled"=>"disabled","reqonly"=>"reqonly");
    $encryption=array("noset"=>"noset","aes128"=>"aes128","yes"=>"yes","no"=>"no");
    
    $arrFormElements = array("requirecalltoken" => array("LABEL"             => _tr("requirecalltoken"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrCallTok,
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
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "sendani" => array("LABEL"             => _tr("sendani"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"Yes","no"=>"No"),
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "adsi" => array("LABEL"             => _tr("adsi"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"Yes","no"=>"No"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "^(yes|no){1}$"),
                            "trunkfreq" => array("LABEL"             => _tr("trunkfreq"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                            "trunktimestamps" => array("LABEL"             => _tr("trunktimestamps"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => array("yes"=>"Yes","no"=>"No"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
    );
    return $arrFormElements;
}

function createFieldFilter($arrOrgz,$arrTech,$arrStatus)
{
    $arrFields = array(
        "organization"  => array("LABEL"         => _tr("Organization Allow"),
                        "REQUIRED"               => "no",
                        "INPUT_TYPE"             => "SELECT",
                        "INPUT_EXTRA_PARAM"      => $arrOrgz,
                        "VALIDATION_TYPE"        => "domain",
                        "VALIDATION_EXTRA_PARAM" => ""),
        "technology"  => array("LABEL"         => _tr("Type"),
                        "REQUIRED"               => "no",
                        "INPUT_TYPE"             => "SELECT",
                        "INPUT_EXTRA_PARAM"      => $arrTech,
                        "VALIDATION_TYPE"        => "text",
                        "VALIDATION_EXTRA_PARAM" => ""),
        "status"  => array("LABEL"         => _tr("Status"),
                        "REQUIRED"               => "no",
                        "INPUT_TYPE"             => "SELECT",
                        "INPUT_EXTRA_PARAM"      => $arrStatus,
                        "VALIDATION_TYPE"        => "text",
                        "VALIDATION_EXTRA_PARAM" => ""),
        );
    return $arrFields;
}

function getAction(){
    global $arrPermission;
    if(getParameter("create_trunk"))
        return (in_array('create',$arrPermission))?'new_trunk':'report';
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
    else if(getParameter("action")=="get_num_calls")
        return "get_num_calls";
    else if(getParameter("action")=="actDesactTrunk")
        return (in_array('edit',$arrPermission))?'actDesactTrunk':'report';
    else
        return "report"; //cancel
}
?>
