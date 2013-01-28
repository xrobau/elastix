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

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoFax.class.php";
    include_once "libs/paloSantoDB.class.php";
	
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

	$contenidoModulo='';
    $arrFormElements = array("name"        => array("LABEL"                  => $arrLang["Virtual Fax Name"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "email"       => array("LABEL"                  => $arrLang["Destination Email"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "email",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "extension"   => array("LABEL"                  => "{$arrLang['Fax Extension']} (IAX)",
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "secret"      => array("LABEL"                  => "{$arrLang['Secret']} (IAX)",
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "clid_name"   => array("LABEL"                  => $arrLang["Caller ID Name"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "clid_number" => array("LABEL"                  => $arrLang["Caller ID Number"],
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "country_code" => array("LABEL"                  => $arrLang["Country Code"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "area_code" => array("LABEL"                  => $arrLang["Area Code"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "port" => array("LABEL"                  => "Port",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "HIDDEN",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "dev_id" => array("LABEL"                  => "DevId",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "HIDDEN",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                );

    $oForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("CONFIRM_CONTINUE", $arrLang["Are you sure you wish to continue?"]);
    $smarty->assign("icon","/modules/$module_name/images/fax_virtual_fax_list.png");

    if(isset($_POST['save'])) {
        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $oFax = new paloFax();
            // TODO: Debo revisar por errores aqui
            $oFax->createFaxExtension($_POST['name'], $_POST['extension'], $_POST['secret'], $_POST['email'], 
                                      $_POST['clid_name'], $_POST['clid_number'],$_POST['country_code'],$_POST['area_code']);
            header("Location: ?menu=faxlist");
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["New Virtual Fax"], $_POST);       
        }

    } else if(isset($_POST['apply_changes'])) {

        $oForm->setEditMode();
        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $oFax = new paloFax();
            $oFax->editFaxExtension($_POST['id_fax'],$_POST['name'], $_POST['extension'],
                              $_POST['secret'], $_POST['email'],$_POST['clid_name'],
                              $_POST['clid_number'],$_POST['dev_id'],
                              $_POST['port'],$_POST['country_code'],$_POST['area_code']);
            header("Location: ?menu=faxlist");
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["Edit Virtual Fax"], $_POST);       
            /////////////////////////////////
        }

    } else if(isset($_POST['delete'])) {
        //- TODO: Validar el id de fax
        $oFax = new paloFax(); 
        $oFax->deleteFaxExtensionById($_POST['id_fax']);
        header("Location: ?menu=faxlist");

    } else if(isset($_POST['edit'])) {

        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error
        // Aqui hago un query por el id de fax
        $oFax = new paloFax();
        $oForm->setEditMode(); // Esto es para activar el modo "edit"
        $arrFax=$oFax->getFaxById($_GET['id']);

        $smarty->assign("id_fax", $_GET['id']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["Edit Virtual Fax"], $arrFax); // hay que pasar el arreglo

    } else if(isset($_GET['action']) && $_GET['action']=="view") {
        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error
        // Aqui hago un query por el id de fax
        $oFax = new paloFax();
        $oForm->setViewMode(); // Esto es para activar el modo "preview"
        $arrFax=$oFax->getFaxById($_GET['id']);

        $smarty->assign("id_fax", $_GET['id']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["View Virtual Fax"], $arrFax); // hay que pasar el arreglo
    } else {
    	//incializar los valores
    	if (!isset($_POST['name'])) $_POST['name']='';
    	if (!isset($_POST['email'])) $_POST['email']='';
    	if (!isset($_POST['extension'])) $_POST['extension']='';
    	if (!isset($_POST['secret'])) $_POST['secret']='';
    	if (!isset($_POST['clid_name'])) $_POST['clid_name']='';
    	if (!isset($_POST['clid_number'])) $_POST['clid_number']='';
    	if (!isset($_POST['port'])) $_POST['port']='';
    	if (!isset($_POST['dev_id'])) $_POST['dev_id']='';
        if (!isset($_POST['country_code'])) $_POST['country_code']='';
    	if (!isset($_POST['area_code'])) $_POST['area_code']='';
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["New Virtual Fax"],$_POST);
    }
    return $contenidoModulo;
}
?>
