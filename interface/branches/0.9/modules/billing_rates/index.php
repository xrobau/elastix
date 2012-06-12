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
    include_once "libs/paloSantoRate.class.php";
    
   //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    $pDB = new paloDB("sqlite3:////var/www/db/rate.db");
    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $arrData = array();
    $pRate = new paloRate($pDB);
    if(!empty($pRate->errMsg)) {
        echo "{$arrLang["ERROR"]}: {$arrLang[$pRate->errMsg]} <br>";
    }

    $arrFormElements = array(
                             "prefix"       => array("LABEL"                 => $arrLang["Prefix"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "EDITABLE"               => "no"),
                          /*   "num_digits"       => array("LABEL"                   => "Number of Digits",
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "EDITABLE"               => "no"),
*/
                             "name"       => array("LABEL"                   => $arrLang["Name"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rate"       => array("LABEL"                   => $arrLang["Rate"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "float",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "rate_offset"       => array("LABEL"                   => $arrLang["Rate Offset"],
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "float",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                         );

    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("CONFIRM_CONTINUE", $arrLang["Are you sure you wish to continue?"]);

    if(isset($_POST['submit_create_rate'])) {
         //AGREGAR NUEVA TARIFA
        include_once("libs/paloSantoForm.class.php");
        $arrFillUser['prefix']      = '';
        $arrFillUser['name']        = '';
        $arrFillUser['rate']        = '';
        $arrFillUser['rate_offset'] = '';
        $oForm = new paloForm($smarty, $arrFormElements);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", $arrLang["New Rate"],$arrFillUser);

    } else if(isset($_POST['edit'])) {
        //EDITAR TARIFA
        // Tengo que recuperar los datos del rate
        $arrRate = $pRate->getRates($_POST['id_rate']);
        $arrFillUser['prefix']      = $arrRate[0][1];
      //  $arrFillUser['num_digits']  = $arrRate[0][2];
        $arrFillUser['name']        = $arrRate[0][2];
        $arrFillUser['rate']        = $arrRate[0][3];
        $arrFillUser['rate_offset'] = $arrRate[0][4];

        // Implementar
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);
        $oForm->setEditMode();
        $smarty->assign("id_rate", $_POST['id_rate']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", "{$arrLang['Edit Rate']} \"" . $arrFillUser['name'] . "\"", $arrFillUser);

    } else if(isset($_POST['submit_save_rate'])) {
        //GUARDAR NUEVA TARIFA
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);
        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $pRate = new paloRate($pDB);
            //revisar por errores
            //el numero de digitos no debe ser mayor que la longitud del prefijos
            //no debe existir un rate con la combinacion prefix num_digits
           /* $arrRate = $pRate->getRates($_POST['prefix'],$_POST['num_digits']);
            if($_POST['num_digits'] > strlen($_POST['prefix'])) {
                // Error existe rate para combinacion prefix y num_digits
                $smarty->assign("mb_message", "ERROR: Number of Digits can't be greater than length of prefix");
                $contenidoModulo=$oForm->fetchForm("billing/new_rate.tpl", "New Rate", $_POST);
            } else {*/
                // Creo rate

                $pRate->createRate($_POST['prefix'],/*$_POST['num_digits'],*/$_POST['name'], $_POST['rate'], $_POST['rate_offset']);
                // Creo la membresia
                
                if(!empty($pRate->errMsg)) {
                    // Ocurrio algun error aqui
                    $smarty->assign("mb_message", $arrLang["ERROR"].':'. $arrLang[$pRate->errMsg]);
                    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", $arrLang["New Rate"], $_POST);
                } else {
                    header("Location: ?menu=billing_rates");
                }
           // }
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
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", $arrLang["New Rate"], $_POST);
        }

    } else if(isset($_POST['submit_apply_changes'])) {
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        $oForm->setEditMode();
        if($oForm->validateForm($_POST)) {
                //- La updateUser no es la adecuada porque pide el username. Deberia
                //- hacer una que no pida username en la proxima version
            $bExito=$pRate->updateRate($_POST['id_rate'],$_POST['rate_prefix'],/*$_POST['rate_num_digits'],*/$_POST['name'], $_POST['rate'], $_POST['rate_offset']);
            header("Location: ?menu=billing_rates");
        } else {
            // Manejo de Error
            $smarty->assign("mb_title", $arrLang["Validation Error"]);
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);


            $smarty->assign("id_rate", $_POST['id_rate']);
            $arrRate = $pRate->getRates($_POST['id_rate']);
            $_POST['prefix']      = $arrRate[0][1];
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", $arrLang["Edit Rate"], $_POST);
            /////////////////////////////////
        }

    } else if(isset($_GET['action']) && $_GET['action']=="view") {

        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error

        $oForm->setViewMode(); // Esto es para activar el modo "preview"
        $arrRate = $pRate->getRates($_GET['id']);
        // Conversion de formato
        $arrTmp['prefix']        = $arrRate[0][1];
       // $arrTmp['num_digits'] = $arrRate[0][2];
        $arrTmp['name']        = $arrRate[0][2];
        $arrTmp['rate'] = $arrRate[0][3];
        $arrTmp['rate_offset']        = $arrRate[0][4];

        $smarty->assign("id_rate", $_GET['id']);
        
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", $arrLang["View Rate"], $arrTmp); // hay que pasar el arreglo

    } else {
        //LISTADO DE RATES
        if (isset($_POST['delete'])) {
          $pRate->deleteRate($_POST['id_rate']);

        }

        $arrRates = $pRate->getRates();
        $end = count($arrRates);
        
        foreach($arrRates as $rate) {
            $arrTmp    = array();
            $arrTmp[0] = $rate[1];
            $arrTmp[1] = $rate[2];
            $arrTmp[2] = number_format($rate[3],3);
            $arrTmp[3] = number_format($rate[4],3);
            $arrTmp[4] = "&nbsp;<a href='?menu=billing_rates&action=view&id=".$rate[0]."'>{$arrLang['View']}</a>";
            $arrData[] = $arrTmp;
        }
        
        $arrGrid = array("title"    => $arrLang["Rates List"],
                         "icon"     => "images/list.png",
                         "width"    => "99%",
                         "start"    => ($end==0) ? 0 : 1,
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => $arrLang["Prefix"],
                                                        "property1" => ""),
                                             1 => array("name"      => $arrLang["Name"], 
                                                        "property1" => ""),
                                            // 2 => array("name"      => "Number of Digits", 
                                             //           "property1" => ""),
                                             2 => array("name"      => $arrLang["Rate"], 
                                                        "property1" => ""),
                                             3 => array("name"      => $arrLang["Rate Offset"], 
                                                        "property1" => ""),
                                             4 => array("name"      => " ", 
                                                        "property1" => "")
                                            )
                        );

        $oGrid = new paloSantoGrid($smarty);
        $oGrid->showFilter(
              "<form style='margin-bottom:0;' method='POST' action='?menu=billing_rates'>" .
              "<input type='submit' name='submit_create_rate' value='{$arrLang['Create New Rate']}' class='button'></form>");
        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }

    return $contenidoModulo;
}
?>
