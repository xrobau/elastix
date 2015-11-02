<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-28                                               |
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
  $Id: index.php,v 1.1 2011-07-27 05:07:46 Alberto Santos asantos@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoEmail.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoEmaillist.class.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $action = getAction();
    $content = "";

    switch($action){
	case "new_emaillist":
	    $content = viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "save_newList":
	    $content = saveNewList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "new_memberlist":
	case "remove_memberlist":
	    $content = viewFormMemberList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "save_newMember":
	case "save_removeMember":
	    $content = saveNewMember($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "delete":
	    $content = deleteEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "view_memberlist":
	    $content = viewMemberList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	case "export":
	    $content = exportMembers($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
        default:
            $content = reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportEmailList($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmailList = new paloSantoEmailList($pDB);
    $pEmail = new paloEmail($pDB);
    $arrDomains	    = $pEmail->getDomains();
    if(count($arrDomains) == 0){
	$smarty->assign("mb_message",_tr("There is no domain created. To use this module you need at least one domain. You can create a domain in the module Email->Domains"));
    }
    else
	$smarty->assign("VirtualDomains",1);
    $arrDominios    = array("all"=> _tr("All"));
    foreach($arrDomains as $domain) {
        $arrDominios[$domain[0]] = $domain[1];
    }
    $arrFormFilterEmailList = createFieldFilter($arrDominios);
    $oFilterForm = new paloForm($smarty, $arrFormFilterEmailList);
    //Verifico si en el archivo /etc/postfix/main.cf las variables alias_map y virtual_alias_map están apuntando a los archivos correctos, de no ser así se lo corrige
    $checkPostfixFile = $pEmailList->checkPostfixFile();

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);

    if(getParameter("domain"))
	$id_domain = getParameter("domain");
    else
	$id_domain = "all";

    $_POST["domain"]=$id_domain;

    $total = $pEmailList->getNumEmailList($id_domain);
    $limit  = 20;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->setTitle(_tr("Email List"));
    $oGrid->setIcon("/modules/$module_name/images/email.png");
    $oGrid->pagingShow(true); // show paging section.
    $offset = $oGrid->calculateOffset();
    $url = array('menu' => $module_name, 'domain' => $id_domain);
    $oGrid->setURL($url);
	$button_eliminar="";
    $arrResult = $pEmailList->getEmailList($id_domain,$limit,$offset);
    $arrColumns = array($button_eliminar,_tr("List name"),_tr("Membership"),_tr("Action"));
    $oGrid->setColumns($arrColumns);
    $arrData = null;

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){
	    $arrTmp[0] = "<input type='checkbox' name='".$value['id']."' id='".$value['id']."'>";
	    $domainName = $pEmailList->getDomainName($value['id_domain']);
	    $arrTmp[1] = "<a href='?menu=$module_name&action=view_memberlist&id=".$value['id']."'>$value[listname]@$domainName</a>";
	    $arrTmp[2] = $pEmailList->getTotalMembers($value['id']);
	    $arrTmp[3] = "<a href='?menu=$module_name&action=new_memberlist&id=".$value['id']."'>[ "._tr("Add Members")." ]</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='?menu=$module_name&action=remove_memberlist&id=".$value['id']."'>[ "._tr("Remove members")." ]</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='?menu=$module_name&action=view_memberlist&id=".$value['id']."'>[ "._tr("View members")." ]</a>";
            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);
    //begin section filter
    //ya no se usa esa variable smarty
    //$smarty->assign("NEW_EMAILLIST", _tr("New Email list"));
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Domain")." = ".$arrDominios[$id_domain], $_POST, array("domain" => "all"),true);
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);

    //end section filter
    $oGrid->addNew("new_emaillist",_tr("New Email list"));
	$oGrid->deleteList(_tr("Are you sure you wish to delete the Email List(s)."),"delete",_tr("Delete"));
    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    if (strpos($content, '<form') === FALSE)
        $content = "<form  method='POST' style='margin-bottom:0;' action=".construirURL($url).">$content</form>";

    //end grid parameters

    return $content;
}

function viewFormEmaillist($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmail = new paloEmail($pDB);
    $pEmaillist = new paloSantoEmaillist($pDB);
    $arrDomains = $pEmail->getDomains();
    $arrDominios    = array("0"=>'-- '._tr("Select a domain").' --');
    foreach($arrDomains as $domain) {
        $arrDominios[$domain[0]]    = $domain[1];
    }
    $arrFormEmaillist = createFieldForm($arrDominios);
    $oForm = new paloForm($smarty,$arrFormEmaillist);
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("List_Setting", _tr("New List Settings"));


    $MailmanListCreated = $pEmaillist->isMailmanListCreated();
    if(is_null($MailmanListCreated)){
	$smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message", $pEmaillist->getError());
    }
    elseif(!$MailmanListCreated){
	$smarty->assign("StatusNew", 1);
	$smarty->assign("Mailman_Setting", _tr("Mailman Admin Settings"));
    }

    $smarty->assign("icon", "/modules/$module_name/images/email.png");
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr("New Email List"), $_POST);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function saveNewList($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmaillist = new paloSantoEmaillist($pDB);
    $pEmail = new paloEmail($pDB);
    $arrDominios    = array("0"=>'-- '._tr("Select a domain").' --');
    $arrDomains = $pEmail->getDomains();
    foreach($arrDomains as $domain) {
        $arrDominios[$domain[0]]    = $domain[1];
    }

    $arrFormEmaillist = createFieldForm($arrDominios);
    $oForm = new paloForm($smarty,$arrFormEmaillist);

    $emailmailman = (getParameter("emailmailman"))?getParameter("emailmailman"):null;
    $passwdmailman = (getParameter("passwdmailman"))?getParameter("passwdmailman"):null;
    $id_domain = getParameter("domain");
    $namelist = getParameter("namelist");
    $password = getParameter("password");
    $passwordconfirm = getParameter("passwordconfirm");
    $emailadmin = getParameter("emailadmin");
    $namelist = strtolower($namelist);

    if(!$oForm->validateForm($_POST)) {
        // Falla la validación básica del formulario
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        $arrErrores = $oForm->arrErroresValidacion;
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
            }
        }
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", $strErrorMsg);
        return viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    elseif(!preg_match("/^[[:alpha:]]+([\-_\.]?[[:alnum:]]+)*$/",$namelist)){
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("Wrong List Name"));
	return viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    elseif($password != $passwordconfirm){
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("The Password List and Confirm Password List do not match"));
	return viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    elseif(!$pEmaillist->domainExists($id_domain)){
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("The domain selected does not exist"));
	return viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    elseif($pEmaillist->listExistsbyName($namelist)){
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("The List entered already exists"));
	return viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    if(isset($emailmailman) && isset($passwdmailman) && !$pEmaillist->isMailmanListCreated()){
	if(!$pEmaillist->mailmanCreateList("mailman",$emailmailman,$passwdmailman)){
	    $smarty->assign("mb_title", _tr("Error"));
	    $smarty->assign("mb_message", _tr("Could not create the list")." mailman");
	    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	}
    }

    $pDB->beginTransaction();
    if(!$pEmaillist->saveEmailList($id_domain,$namelist,$password,$emailadmin)){
	$pDB->rollBack();
	$smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", $pEmaillist->getError());
	return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    $domainName = $pEmaillist->getDomainName($id_domain);
    if(!isset($domainName)){
	$pDB->rollBack();
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("The domain selected does not exist"));
	return viewFormEmaillist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    if(!$pEmaillist->mailmanCreateList($namelist,$emailadmin,$password,$domainName)){
	$pDB->rollBack();
	$smarty->assign("mb_title", _tr("Error"));
	$smarty->assign("mb_message", _tr("Could not create the list")." $namelist");
	return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    if(!$pEmaillist->mailmanCreateVirtualAliases($namelist,$domainName)){
	$pDB->rollBack();
	$smarty->assign("mb_title", _tr("Error"));
	$smarty->assign("mb_message", _tr("Could not create the virtual alias"));
	return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $pDB->commit();
    $smarty->assign("mb_title", _tr("Message"));
    $smarty->assign("mb_message", _tr("The List was successfully created"));
    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function viewFormMemberList($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $action = getParameter("action");
    if($action == "new_memberlist"){
	$title = _tr("New List Member");
	$smarty->assign("MEMBER","save_newMember");
	$info = _tr("You must enter each email line by line, like the following").":<br /><br /><b>"._tr("userEmail1@domain1.com")."<br />"._tr("userEmail2@domain2.com")."<br />"._tr("userEmail3@domain3.com")."</b><br /><br />"._tr("You can also enter a name for each email, like the following").":<br /><br /><b>"._tr("Name1 Lastname1 <userEmail1@domain1.com>")."<br />"._tr("Name2 Lastname2 <userEmail2@domain2.com>")."<br />"._tr("Name3 Lastname3 <userEmail3@domain3.com>")."</b>";
	$smarty->assign("SAVE", _tr("Add"));
    }
    else{
	$title = _tr("Remove List Member");
	$smarty->assign("MEMBER","save_removeMember");
	$info = _tr("You must enter each email line by line, like the following").":<br /><br /><b>"._tr("userEmail1@domain1.com")."<br />"._tr("userEmail2@domain2.com")."<br />"._tr("userEmail3@domain3.com")."</b>";
	$smarty->assign("SAVE", _tr("Remove"));
    }
    $id_emailList = getParameter("id");
    $smarty->assign("IDEMAILLIST",$id_emailList);
    $arrFormMemberlist = createFieldFormMember();
    $oForm = new paloForm($smarty,$arrFormMemberlist);

    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("INFO", $info);
    $smarty->assign("icon", "/modules/$module_name/images/email.png");
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form_member.tpl", $title, $_POST);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;
}

function saveNewMember($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmaillist = new paloSantoEmaillist($pDB);
    $arrFormMemberlist = createFieldFormMember();
    $oForm = new paloForm($smarty,$arrFormMemberlist);

    $emailMembers = getParameter("emailmembers");
    $id_list	  = getParameter("id_emaillist");
    if(!$oForm->validateForm($_POST)) {
        // Falla la validación básica del formulario
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        $arrErrores = $oForm->arrErroresValidacion;
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
            }
        }
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", $strErrorMsg);
        return viewFormMemberList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }elseif(!$pEmaillist->listExistsbyId($id_list)){
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("The List entered does not exist"));
	return viewFormMemberList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    $emailMembers = explode("\n",$emailMembers);
    $arrMembers = array();
    $arrErrorMembers = array();
    $i = 0;
    foreach($emailMembers as $key => $value){
	$member = trim($value);
	if(preg_match("/^[[:alnum:]]+([\._\-]?[[:alnum:]]+)*@[[:alnum:]]+([\._\-]?[[:alnum:]]+)*(\.[[:alnum:]]{2,4})+$/",$member)){
	    if(getParameter("save_newMember")){
		if(!$pEmaillist->isAMemberOfList($member,$id_list)){
		    $arrMembers[$i]["member"] = $member;
		    $arrMembers[$i]["email_member"] = $member;
		    $i++;
		}
		else
		    $arrErrorMembers[] = _tr("Already a member").": ".htmlentities($member);
	    }
	    else{
		if($pEmaillist->isAMemberOfList($member,$id_list)){
		    $arrMembers[$i]["member"] = $member;
		    $arrMembers[$i]["email_member"] = $member;
		    $i++;
		}
		else
		    $arrErrorMembers[] = _tr("No such subscriber").": ".htmlentities($member);
	    }
	}
	elseif(preg_match("/^([[:alnum:]]+([[:space:]]*[[:alnum:]]+){0,3})[[:space:]]*\<([[:alnum:]]+([\._\-]?[[:alnum:]]+)*@[[:alnum:]]+([\._\-]?[[:alnum:]]+)*(\.[[:alnum:]]{2,4})+)\>$/",$member,$matches) && getParameter("save_newMember")){
	    if(!$pEmaillist->isAMemberOfList($matches[3],$id_list)){
		$arrMembers[$i]["member"] = preg_replace("/[[:space:]]+/"," ",$member);
		$arrMembers[$i]["namemember"] = $matches[1];
		$arrMembers[$i]["email_member"] = $matches[3];
		$i++;
	    }
	    else
		$arrErrorMembers[] = _tr("Already a member").": ".htmlentities($member);
	}
	elseif($member!="")
	    $arrErrorMembers[] = htmlentities($member);
    }
    $pDB->beginTransaction();
    if(count($arrMembers) > 0){
	foreach($arrMembers as $key => $value){
	    if(getParameter("save_newMember")){
		if(isset($value["namemember"]))
		    $namemember = $value["namemember"];
		else
		    $namemember = "";
		if(!$pEmaillist->saveMember($value["email_member"],$id_list,$namemember)){
		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", $pEmaillist->getError());
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}
	    }else{
		if(!$pEmaillist->removeMember($value["email_member"],$id_list)){
		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", $pEmaillist->getError());
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}
	    }
	}
	if(getParameter("save_newMember")){
	    if(!$pEmaillist->mailmanAddMembers($arrMembers,$id_list)){
		$pDB->rollBack();
		$smarty->assign("mb_title", _tr("Validation Error"));
		$smarty->assign("mb_message", _tr("Mailman could not add the members"));
		return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    }
	}
	else{
	    if(!$pEmaillist->mailmanRemoveMembers($arrMembers,$id_list)){
		$pDB->rollBack();
		$smarty->assign("mb_title", _tr("Validation Error"));
		$smarty->assign("mb_message", _tr("Mailman could not remove the members"));
		return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    }
	}
    }
    $pDB->commit();
    $message = "";
    if(count($arrMembers)>0){
	if(getParameter("save_newMember"))
	    $message .= "<b>"._tr("The following members were added to the list").":</b><br />";
	else
	    $message .= "<b>"._tr("The following members were removed from the list")."</b>:<br />";
	foreach($arrMembers as $member)
	    $message .= htmlentities($member["member"])."<br />";
    }
    if(count($arrErrorMembers)>0){
	if(getParameter("save_newMember"))
	    $message .= "<b>"._tr("The following members could not be added to the list").":</b><br />";
	else
	    $message .= "<b>"._tr("The following members could not be removed from the list")."</b>:<br />";
	foreach($arrErrorMembers as $noMember)
	    $message .= $noMember."<br />";
    }
    if($message != ""){
	$smarty->assign("mb_title", _tr("Message"));
	$smarty->assign("mb_message", $message);
    }
    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function deleteEmailList($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmaillist = new paloSantoEmaillist($pDB);
    $pDB->beginTransaction();
    foreach($_POST as $key => $value){
        if($value == "on")
        {
	    if($pEmaillist->listExistsbyId($key)){
		$listName = $pEmaillist->getListName($key);
		if(is_null($listName)){
		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", _tr("Mailman could not remove the list"));
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}

		$id_domain = $pEmaillist->getIdDomainofList($key);
		if(is_null($id_domain)){
		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", _tr("Mailman could not remove the list"));
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}

		$domainName = $pEmaillist->getDomainName($id_domain);
		if(is_null($domainName)){

		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", _tr("Mailman could not remove the list"));
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}

		if(!$pEmaillist->deleteEmailList($key)){
		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", $pEmaillist->getError());
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}

		if(!$pEmaillist->mailmanRemoveList($listName,$domainName)){
		    $pDB->rollBack();
		    $smarty->assign("mb_title", _tr("Error"));
		    $smarty->assign("mb_message", _tr("Mailman could not remove the list"));
		    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
		}
	    }
        }
    }
    $pDB->commit();
    $smarty->assign("mb_title", _tr("Message"));
    $smarty->assign("mb_message", _tr("The email list(s) were successfully deleted"));
    return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function viewMemberList($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pEmailList = new paloSantoEmailList($pDB);
    $id_list = getParameter("id");

    if(!$pEmailList->listExistsbyId($id_list)){
	$smarty->assign("mb_title", _tr("Validation Error"));
        $smarty->assign("mb_message", _tr("The List entered does not exist"));
	return reportEmailList($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $field_type = getParameter("filter_type");
    $field_pattern = getParameter("filter_txt");

    $smarty->assign("IDEMAILLIST",$id_list);
    $smarty->assign("SHOW",_tr("Show"));
    $smarty->assign("RETURN",_tr("Return"));
    $smarty->assign("LINK","?menu=$module_name&action=export&id=$id_list&rawmode=yes");
    $smarty->assign("EXPORT",_tr("Export Members"));

    $totalMembers = $pEmailList->getTotalMembers($id_list);

    $oGrid  = new paloSantoGrid($smarty);
    $limit  = 20;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($totalMembers);
    $oGrid->setTitle(_tr("List Members of")." ".$pEmailList->getListName($id_list));
    $oGrid->setIcon("/modules/$module_name/images/email.png");
    $oGrid->pagingShow(true);
    $offset = $oGrid->calculateOffset();
    $url = array(
        'menu'          => $module_name,
        'action'        =>  'view_memberlist',
        'id'            =>  $id_list,
        'filter_type'   =>  $field_type,
        'filter_txt'    =>  $field_pattern
    );
    $oGrid->setURL($url);

    $arrColumns = array(_tr("Member name"),_tr("Member email"));
    $oGrid->setColumns($arrColumns);

    $arrResult = $pEmailList->getMembers($limit,$offset,$id_list,$field_type,$field_pattern);
    $arrData = null;

    if(is_array($arrResult) && $totalMembers>0){
        foreach($arrResult as $key => $value){
	    $arrTmp[0] = $value["namemember"];
	    $arrTmp[1] = $value["mailmember"];
	    $arrData[] = $arrTmp;
	}
    }
    $oGrid->setData($arrData);

    $arrFormFilterMembers = createFieldFilterViewMembers();
    $oFilterForm = new paloForm($smarty, $arrFormFilterMembers);

	$arrType = array("name" => _tr("Name"), "email" => _tr("Email"));

	if(!is_null($field_type)){
		$nameField = $arrType[$field_type];
	}else{
		$nameField = "";
	}

	$oGrid->customAction("return", _tr("Return"));
	$oGrid->customAction("?menu=$module_name&action=export&id=$id_list&rawmode=yes",_tr("Export Members"),null,true);

	//$arrFiltro = array("filter_type"=>$field_type,"filter_txt"=>$field_pattern);

	$oGrid->addFilterControl(_tr("Filter applied: ").$nameField." = ".$field_pattern, $_POST, array("filter_type" => "name","filter_txt" => ""));
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/view_members.tpl","",$_POST);
    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    if (strpos($content, '<form') === FALSE)
        $content = "<form  method='POST' style='margin-bottom:0;' action=".construirURL($url).">$content</form>";
    return $content;
}

function exportMembers($smarty, $module_name, $local_templates_dir, $pDB, $arrConf)
{
    $pEmailList = new paloSantoEmailList($pDB);
    $id_list = getParameter("id");
    $listName = $pEmailList->getListName($id_list);
    $text = "";
    if(!is_null($listName)){
	$totalMembers = $pEmailList->getTotalMembers($id_list);
	$members      = $pEmailList->getMembers($totalMembers,0,$id_list,null,"");
	foreach($members as $key => $value){
	    if($text != "")
		$text .= "\n";
	    if(isset($value["namemember"]) && $value["namemember"] != "")
		$text .= $value["namemember"]." <$value[mailmember]>";
	    else
		$text .= $value["mailmember"];
	}
    }
    else
	$listName = "";

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: txt file");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=$listName"."_members.txt");
    header("Content-Transfer-Encoding: binary");
    header("Content-length: ".strlen($text));
    echo $text;
}

function createFieldFilter($arrDominios){
    $arrFormElements = array(
            "domain"   => array(    "LABEL"          	     => _tr("Domain"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrDominios,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "",
				    "ONCHANGE"		     => "javascript:submit();"),
                );
    return $arrFormElements;
}

function createFieldForm($arrDominios)
{
    $arrFields = array(
            "emailmailman"   => array(      "LABEL"                  => _tr("Email mailmam admin"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:200px","maxlength" =>"100"),
                                            "VALIDATION_TYPE"        => "email",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "passwdmailman"   => array(     "LABEL"                  => _tr("Password"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "PASSWORD",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:200px","maxlength" =>"200"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),

            "domain"  	       => array(    "LABEL"                  => _tr("Domain name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrDominios,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
                                            ),

            "namelist" 	       => array(    "LABEL"                  => _tr("List name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:200px","maxlength" =>"200"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "password"         => array(    "LABEL"                  => _tr("Password"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "PASSWORD",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:200px","maxlength" =>"200"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "passwordconfirm"   => array(   "LABEL"                  => _tr("Confirm password list"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "PASSWORD",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:200px","maxlength" =>"200"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "emailadmin"   	=> array(   "LABEL"                  => _tr("Email admin list"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:200px","maxlength" =>"200"),
                                            "VALIDATION_TYPE"        => "email",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            );
    return $arrFields;
}

function createFieldFormMember()
{
    $arrFields = array(
        "emailmembers"   => array(       "LABEL"                  => _tr("Members emails"),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "TEXTAREA",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:400px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => "",
					"ROWS"			 => "9"
                                        ),
        );
    return $arrFields;
}

function createFieldFilterViewMembers()
{
    $arrType = array("name" => _tr("Name"), "email" => _tr("Email"));

    $arrFormElements = array(
            "filter_type"  => array(   "LABEL"                  => _tr("Search"),
                                       "REQUIRED"               => "no",
                                       "INPUT_TYPE"             => "SELECT",
                                       "INPUT_EXTRA_PARAM"      => $arrType,
                                       "VALIDATION_TYPE"        => "text",
                                       "VALIDATION_EXTRA_PARAM" => ""),
            "filter_txt"   => array(   "LABEL"                  => "",
                                       "REQUIRED"               => "no",
                                       "INPUT_TYPE"             => "TEXT",
                                       "INPUT_EXTRA_PARAM"      => "",
                                       "VALIDATION_TYPE"        => "text",
                                       "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function getAction()
{
    if(getParameter("new_emaillist")) //Get parameter by POST (submit)
        return "new_emaillist";
	elseif(getParameter("return"))
		return "report";
    elseif(getParameter("save_newList"))
	return "save_newList";
    elseif(getParameter("action") == "new_memberlist")
	return "new_memberlist";
    elseif(getParameter("action") == "remove_memberlist")
	return "remove_memberlist";
    elseif(getParameter("action") == "view_memberlist" || getParameter("show"))
	return "view_memberlist";
    elseif(getParameter("save_newMember"))
	return "save_newMember";
    elseif(getParameter("save_removeMember"))
	return "save_removeMember";
    elseif(getParameter("delete"))
	return "delete";
    elseif(getParameter("action") == "export")
	return "export";
    else
        return "report"; //cancel
}
?>