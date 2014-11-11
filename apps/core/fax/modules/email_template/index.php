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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent($smarty, $module_name)
{
    include_once "libs/paloSantoFax.class.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
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

     // Definición del formulario
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("EDIT_PARAMETERS", $arrLang["Edit Parameters"]);
    $smarty->assign("icon","/modules/$module_name/images/fax_email_template.png");

    $arrFaxConfig    = array("remite"        => array("LABEL"                 => $arrLang['Fax From'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:240px"),
                                                     "VALIDATION_TYPE"        => "email",
                                                     "EDITABLE"               => "si",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "remitente"      => array("LABEL"                => $arrLang["Fax From Name"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:240px"),
                                                     "VALIDATION_TYPE"        => "name",
                                                     "EDITABLE"               => "si",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "subject"        => array("LABEL"                => $arrLang["Fax Suject"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:240px"),
                                                     "VALIDATION_TYPE"        => "text",
                                                     "EDITABLE"               => "si",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "content"       => array("LABEL"                 => $arrLang["Fax Content"],
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXTAREA",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "text",
                                                     "EDITABLE"               => "si",
                                                     "COLS"                   => "50",
                                                     "ROWS"                   => "4",
                                                     "VALIDATION_EXTRA_PARAM" => ""));

    $oForm = new paloForm($smarty, $arrFaxConfig);
    $contenidoModulo = "";
    $action="";
    if (isset($_POST["submit_edit"]))         $action = "submit_edit";
    if (isset($_POST["submit_apply_change"])) $action = "submit_apply_change";

    switch($action){
        case 'submit_edit': 
            $contenidoModulo = editParameterFaxMail($smarty, $module_name, $local_templates_dir, $oForm);
            break;
        case 'submit_apply_change': 
            $contenidoModulo = applyChnageParameterFaxMail($smarty, $module_name, $local_templates_dir, $oForm);
            break;
        default:
            $contenidoModulo = listParameterFaxMail($smarty, $module_name, $local_templates_dir, $oForm);
            break;
    }
    return $contenidoModulo;
}

function editParameterFaxMail($smarty, $module_name, $local_templates_dir, $oForm)
{
    global $arrLang;
    $oFax    = new paloFax();
    $arrParameterFaxMail = $oFax->getConfigurationSendingFaxMail();
    $oForm->setEditMode();
    return $oForm->fetchForm("$local_templates_dir/parameterFaxMail.tpl", $arrLang["Configuration Sending Fax Mail"], $arrParameterFaxMail);
}

function applyChnageParameterFaxMail($smarty, $module_name, $local_templates_dir, $oForm)
{
    global $arrLang;
    $contenidoModulo="";

    if(!$oForm->validateForm($_POST)) {
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $arrErrores=$oForm->arrErroresValidacion;
        $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
        }
        $strErrorMsg .= "";
        $smarty->assign("mb_message", $strErrorMsg);
        $oForm->setEditMode();
        $contenidoModulo = $oForm->fetchForm("$local_templates_dir/parameterFaxMail.tpl", $arrLang["Configuration Sending Fax Mail"], $_POST);
    } 
    else {
        $oFax    = new paloFax();
        if($oFax->setConfigurationSendingFaxMail($_POST['remite'],$_POST['remitente'],$_POST['subject'],$_POST['content'])){
            header("Location: ?menu=$module_name");
        }
        else{
            $smarty->assign("mb_message", $oFax->errMsg);
            $oForm->setEditMode();
            $contenidoModulo = $oForm->fetchForm("$local_templates_dir/parameterFaxMail.tpl", $arrLang["Configuration Sending Fax Mail"], $_POST);
        }
    }
    return $contenidoModulo;
}

function listParameterFaxMail($smarty, $module_name, $local_templates_dir, $oForm)
{
    global $arrLang;
    $arrData = array();
    $oFax    = new paloFax();
    $arrParameterFaxMail = $oFax->getConfigurationSendingFaxMail(); 
    $oForm->setViewMode();
    return $oForm->fetchForm("$local_templates_dir/parameterFaxMail.tpl", $arrLang["Configuration Sending Fax Mail"], $arrParameterFaxMail);
}
?>
