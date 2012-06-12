<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-2                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-01 05:09:56 Bruno Macias <bmacias@palosanto.com> Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAntispam.class.php";

    $lang=get_language();
    $script_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";

    if (file_exists("$script_dir/$lang_file"))
        include_once($lang_file);
    else
        include_once("modules/$module_name/lang/en.lang");

    global $arrConf;
    global $arrConfigModule;
    global $arrLang;
    global $arrLangModule;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfigModule['templates_dir']))?$arrConfigModule['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $accion = getAction();

    $content = "";
    switch($accion)
    {

        case "update":
            $content = updateAntispam($smarty, $module_name, $local_templates_dir, $arrLang, $arrLangModule, $arrConf, $arrConfigModule);
            break;

        default:
            $content = formAntispam($smarty, $module_name, $local_templates_dir, $arrLang, $arrLangModule, $arrConf, $arrConfigModule);
            break;
    }

    return $content;
}

function updateAntispam($smarty, $module_name, $local_templates_dir, $arrLang, $arrLangModule, $arrConf, $arrConfigModule)
{
    $status = getParameter("status");
    $level  = getParameter("level");

    $objAntispam = new paloSantoAntispam($arrConfigModule['path_postfix'], $arrConfigModule['path_spamassassin'],$arrConfigModule['file_master_cf'], $arrConfigModule['file_local_cf']);
    $isOk = $objAntispam->changeThoroughnessLevel($level);
    if($isOk === false){
        $smarty->assign("mb_title", $arrLang["Error"]);
        $smarty->assign("mb_message", $objAntispam->errMsg);
    }

    if($status == "active"){
        $isOk = $objAntispam->activateSpamFilter();
        if($isOk === false){
            $smarty->assign("mb_title", $arrLang["Error"]);
            $smarty->assign("mb_message", $objAntispam->errMsg);
        }
    }
    else if($status == "disactive"){
        $isOk = $objAntispam->disactivateSpamFilter();
        if($isOk === false){
            $smarty->assign("mb_title", $arrLang["Error"]);
            $smarty->assign("mb_message", $objAntispam->errMsg);
        }
    }

    return formAntispam($smarty, $module_name, $local_templates_dir, $arrLang, $arrLangModule, $arrConf, $arrConfigModule);
}

function formAntispam($smarty, $module_name, $local_templates_dir, $arrLang, $arrLangModule, $arrConf, $arrConfigModule)
{
    $arrFormConference = createFieldForm($arrLang, $arrLangModule);
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("UPDATE", $arrLang["Update"]);
    $smarty->assign("TITLE", $arrLangModule["Antispam"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("IMG", "images/list.png");


    $objAntispam = new paloSantoAntispam($arrConfigModule['path_postfix'], $arrConfigModule['path_spamassassin'],$arrConfigModule['file_master_cf'], $arrConfigModule['file_local_cf']);
    $arrSpamFilter = $objAntispam->isActiveSpamFilter();
    if($arrSpamFilter["isOk"])
        $arrData['status'] = "active";
    else
        $arrData['status'] = "disactive";

    $valueRequiredHits = $objAntispam->getValueRequiredHits();
    $arrData['level'] = $valueRequiredHits;

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", "", $arrData);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldForm($arrLang, $arrLangModule)
{
    $arrLevel  = array();
    for($i=1;$i<=10;$i++)
        $arrLevel[$i]=$i;
    $arrStatus = array('active' => $arrLangModule['Active'], 'disactive' => $arrLangModule['Disactive']);

    $arrFields = array(
            "status"            => array(   "LABEL"                  => $arrLang["Status"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => $arrStatus,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                ),
            "level"             => array(   "LABEL"                  => $arrLangModule["Thoroughness Level"],
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrLevel,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                ),
            );
    return $arrFields;
}

function getParameter($parameter)
{
    if(isset($_POST[$parameter]))
        return $_POST[$parameter];
    else if(isset($_GET[$parameter]))
        return $_GET[$parameter];
    else
        return null;
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if(getParameter("update"))
        return "update";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}?>