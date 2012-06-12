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
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoTrunk.class.php";
    require_once "libs/misc.lib.php";
    
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    

    $contenido='';
    $msgError='';
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
               $arrConfig['AMPDBHOST']['valor'] . "/asterisk";
    $pDB     = new paloDB($dsn);
    $pDBSetting = new paloDB("sqlite3:////var/www/db/settings.db");
    $pDBTrunk = new paloDB("sqlite3:////var/www/db/trunk.db");
    $arrForm  = array("default_rate"       => array("LABEL"                   => $arrLang["Default Rate"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "float",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                      "default_rate_offset"       => array("LABEL"                   => $arrLang["Default Rate Offset"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "float",
                                                    "VALIDATION_EXTRA_PARAM" => ""));


    $oForm = new paloForm($smarty, $arrForm);
    $oForm->setViewMode();
    //obtener el valor de la tarifa por defecto
    $arrDefaultRate['default_rate']=get_key_settings($pDBSetting,"default_rate");
    $arrDefaultRate['default_rate_offset']=get_key_settings($pDBSetting,"default_rate_offset");
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $strReturn = $oForm->fetchForm("$local_templates_dir/default_rate.tpl", $arrLang["Default Rate Configuration"], $arrDefaultRate);

    if(isset($_POST['edit_default'])) {
        $arrDefaultRate['default_rate']=get_key_settings($pDBSetting,"default_rate");
        $arrDefaultRate['default_rate_offset']=get_key_settings($pDBSetting,"default_rate_offset");
        $oForm = new paloForm($smarty, $arrForm);

        $smarty->assign("CANCEL", $arrLang["Cancel"]);
        $smarty->assign("SAVE", $arrLang["Save"]);
        $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
        $strReturn = $oForm->fetchForm("$local_templates_dir/default_rate.tpl", $arrLang["Default Rate Configuration"], $arrDefaultRate);

    }
    else if(isset($_POST['save_default'])) {
        $oForm = new paloForm($smarty, $arrForm);
        $arrDefaultRate['default_rate'] = $_POST['default_rate'];
        $arrDefaultRate['default_rate_offset'] = $_POST['default_rate_offset'];
        if($oForm->validateForm($_POST)) {
            $bValido=set_key_settings($pDBSetting,'default_rate',$arrDefaultRate['default_rate']);
            $bValido=set_key_settings($pDBSetting,'default_rate_offset',$arrDefaultRate['default_rate_offset']);
            if(!$bValido) {
                echo $arrLang["Error when saving default rate"];
            } else {
                header("Location: index.php?menu=billing_setup");
            }
        } else {
            // Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $smarty->assign("mb_message", $arrLang["Value for rate is not valid"]);
            $smarty->assign("CANCEL", $arrLang["Cancel"]);
            $smarty->assign("SAVE", $arrLang["Save"]);
            $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
            $strReturn = $oForm->fetchForm("$local_templates_dir/default_rate.tpl", $arrLang["Default Rate Configuration"], $arrDefaultRate);
        }

    }
    $arrTrunks=array();
    $arrData=array();
    $arrTrunksBill=array();
    //obtener todos los trunks
    $oTrunk     = new paloTrunk($pDBTrunk);
    //obtener todos los trunks que son para billing
    //$arrTrunksBill=array("ZAP/g0","ZAP/g1");
    $arrTrunksBill=$oTrunk->getTrunksBill();
    if(isset($_POST['submit_bill_trunks'])) {
        //obtengo las que estan guardadas y las que ahora no estan

        $selectedTrunks= isset($_POST['trunksBills'])?array_keys($_POST['trunksBills']):array();
        if (count($selectedTrunks)>0)
        {
            foreach ($selectedTrunks as $selectedTrunk)
                 $nuevaListaTrunks[]=base64_decode($selectedTrunk);
        }else $nuevaListaTrunks=array();

        $listaTrunksNuevos = array_diff($nuevaListaTrunks, $arrTrunksBill);
        $listaTrunksAusentes = array_diff($arrTrunksBill, $nuevaListaTrunks);
        //tengo que borrar los trunks ausentes
        //tengo que agregar los trunks nuevos
       // print_r($listaTrunksNuevos);
        //print_r($listaTrunksAusentes);
        if (count($listaTrunksAusentes)>0){
            $bExito=$oTrunk->deleteTrunksBill($listaTrunksAusentes);
            if (!$bExito)
               $msgError=$oTrunk->errMsg;
        }
        if (count($listaTrunksNuevos)>0){
            $bExito=$oTrunk->saveTrunksBill($listaTrunksNuevos);
            if (!$bExito)
               $msgError.=$oTrunk->errMsg;
        }
        if (!empty($msgError))
                $smarty->assign("mb_message", $msgError);
    } 


    $arrTrunks=getTrunks($pDB);
    $arrTrunksBill=$oTrunk->getTrunksBill();

    $end = count($arrTrunks);
    if (is_array($arrTrunks)){
    	foreach($arrTrunks as $trunk) {
        	$arrTmp    = array();

        	$checked=(in_array($trunk[1],$arrTrunksBill))?"checked":"";
        	$arrTmp[0] = "<input type='checkbox' name='trunksBills[".base64_encode($trunk[1])."]' $checked>";
        	$arrTmp[1] = $trunk[1];
        	$arrData[] = $arrTmp;
    	}
    }
    
    
    $arrGrid = array("title"    => $arrLang["Trunk Bill Configuration"],
                     "icon"     => "images/1x1.gif",
                     "width"    => "99%",
                     "start"    => ($end==0) ? 0 : 1,
                     "end"      => $end,
                     "total"    => $end,
                     "columns"  => array(0 => array("name"      => "",
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Trunk"], 
                                                    "property1" => ""),
                                        )
                    );
    
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->showFilter(
        "<input type='submit' name='submit_bill_trunks' value='{$arrLang['Billing Capable']}' class='button'>");
    $trunk_config=
        "<form style='margin-bottom:0;' method='POST' action='?menu=billing_setup'>" .
        $oGrid->fetchGrid($arrGrid, $arrData,$arrLang)."</form>";
   //mostrar los dos formularios
    $contenido.=$strReturn.$trunk_config;
    return $contenido;
}


?>