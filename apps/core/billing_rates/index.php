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
    include_once "libs/paloSantoTrunk.class.php";
    
   //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    
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
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $pDBTrunk = new paloDB("sqlite3:///$arrConf[elastix_dbdir]/trunk.db");
    $oTrunk   = new paloTrunk($pDBTrunk);
    $arrTrunksBill['None']=$arrLang['None'];
    foreach ($oTrunk->getTrunksBill() as $trunk) $arrTrunksBill[$trunk]=$trunk;
    if(!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $arrData = array();
    $pRate = new paloRate($pDB);
    if(!empty($pRate->errMsg)) {
        echo "{$arrLang["ERROR"]}: {$arrLang[$pRate->errMsg]} <br>";
    }

    $arrFormElements = array(
                             "prefix"       => array("LABEL"                   => $arrLang["Prefix"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => "",
                                                     "EDITABLE"               => "no"),
                             "name"         => array("LABEL"                   => $arrLang["Name"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "rate"         => array("LABEL"                   => $arrLang["Rate"].$arrLang['(by min)'],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "float",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "rate_offset"  => array("LABEL"                   => $arrLang["Rate Offset"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "float",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "trunk"        => array("LABEL"                   => $arrLang["Trunk"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "SELECT",
                                                     "INPUT_EXTRA_PARAM"      => $arrTrunksBill,
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "importcsv"    => array("LABEL"                   => $arrLang["Import File"],
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "FILE",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "filename",
                                                     "VALIDATION_EXTRA_PARAM" => "")
                         );

    $smarty->assign("REQUIRED_FIELD", $arrLang["Required field"]);
    $smarty->assign("CANCEL", $arrLang["Cancel"]);
    $smarty->assign("APPLY_CHANGES", $arrLang["Apply changes"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("EDIT", $arrLang["Edit"]);
    $smarty->assign("DELETE", $arrLang["Delete"]);
    $smarty->assign("CONFIRM_CONTINUE", $arrLang["Are you sure you wish to continue?"]);

    if(isset($_POST['rate_offset']) && $_POST['rate_offset']!="" && $_POST['rate_offset']==0) $_POST['rate_offset']='0.0';
    if(isset($_POST['submit_create_rate'])) {
         //AGREGAR NUEVA TARIFA
        include_once("libs/paloSantoForm.class.php");
        $arrFillUser['prefix']      = '';
        $arrFillUser['name']        = '';
        $arrFillUser['rate']        = '';
        $arrFillUser['rate_offset'] = '';
        $arrFillUser['trunk']       = '';
        $oForm = new paloForm($smarty, $arrFormElements);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", $arrLang["New Rate"],$arrFillUser);

    } else if(isset($_POST['edit'])) {
        //EDITAR TARIFA
        // Tengo que recuperar los datos del rate
        $arrRate = $pRate->getRates($_POST['id_rate']);
        $arrFillUser['prefix']      = $arrRate[0][1];
        $arrFillUser['name']        = $arrRate[0][2];
        $arrFillUser['rate']        = $arrRate[0][3];
        $arrFillUser['rate_offset'] = $arrRate[0][4];
        $arrFillUser['trunk']       = $arrRate[0][5];

        // Implementar
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);
        $oForm->setEditMode();
        $smarty->assign("id_rate", $_POST['id_rate']);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/new_rate.tpl", "{$arrLang['Edit Rate']} \"" . $arrFillUser['name'] . "\"", $arrFillUser);

    } else if(isset($_POST['submit_import_rate'])) {
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/import_rate.tpl", $arrLang["Import File"], $_POST);

    } else if(isset($_POST['submit_import_changes'])) {
        include_once("libs/paloSantoForm.class.php");
        $oForm = new paloForm($smarty, $arrFormElements);
    $pRate = new paloRate($pDB);

    if (is_uploaded_file($_FILES['importcsv']['tmp_name'])) {
        $contenido_archivo=file($_FILES['importcsv']['tmp_name']);
        $count=0;
        foreach ($contenido_archivo as $linea)
                {
                                $count++;
                $rate_val=explode(';',$linea);
                $record=array('prefix'      => $rate_val[0], 
                                              'name'        => $rate_val[1],
                                              'rate'        => $rate_val[2], 
                                              'rate_offset' => ($rate_val[3]==0?'0.0':$rate_val[3]), 
                                              'trunk'       => trim($rate_val[4]));
                             //if no validation error             insert record         or        add error message
            if($oForm->validateForm($record)) 
                        {
                                   if(!$pRate->createRate($rate_val[0],$rate_val[1],$rate_val[2],$rate_val[3],trim($rate_val[4])))
                                   if(!empty($pRate->errMsg)) $arrErrorMsg['Insert Error'][$count]=$arrLang[$pRate->errMsg];
                        } else $arrErrorMsg[$arrLang["Validation Error"]][$count]=$oForm->arrErroresValidacion;
        }

                $strErrorMsg='';
                foreach ($arrErrorMsg as $Error_type => $on_line)
                {
                        $strErrorMsg.= "<B><font color=\"red\">".$Error_type.":</font></B><BR>";
                        foreach ($on_line as $line=>$error_msg)
                        {
                                if (is_array($error_msg)) foreach ($error_msg as $k=>$msg)
                                {
                                     if (!is_array($msg)) $error_msg=$msg;
                                     else foreach ($msg as $v) $error_msg= $k." has ".$v;
                                }
                                     $strErrorMsg.= "Error on line: ". $line."  ".$error_msg."<br>";
                        }
            $strErrorMsg.='<BR>';
                }       

    } else $strErrorMsg=$arrLang["File doesn't exist"];
 
    if (isset($strErrorMsg)&&$strErrorMsg!="") {
            $smarty->assign("mb_message", $strErrorMsg);
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/import_rate.tpl", $arrLang["New Rate"], $_POST);
    } else header("Location: ?menu=billing_rates");

    } else if(isset($_POST['submit_save_rate'])) {
        //GUARDAR NUEVA TARIFA
        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        if($oForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $pRate = new paloRate($pDB);

            //Creo rate

                $pRate->createRate($_POST['prefix'], $_POST['name'], $_POST['rate'], $_POST['rate_offset'], $_POST['trunk']);
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
            $bExito=$pRate->updateRate($_POST['id_rate'],$_POST['rate_prefix'], $_POST['name'], $_POST['rate'], $_POST['rate_offset'],$_POST['trunk']);
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
        $arrTmp['prefix']      = $arrRate[0][1];
        $arrTmp['name']        = $arrRate[0][2];
        $arrTmp['rate']        = $arrRate[0][3];
        $arrTmp['rate_offset'] = $arrRate[0][4];
        $arrTmp['trunk']       = $arrRate[0][5];

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
			if($rate[5]=="None")
           		$rate[5]=$arrLang["None"];
            $arrTmp[4] = $rate[5];
            $arrTmp[5] = "&nbsp;<a href='?menu=billing_rates&action=view&id=".$rate[0]."'>{$arrLang['View']}</a>";
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
                                             2 => array("name"      => $arrLang["Rate"], 
                                                        "property1" => ""),
                                             3 => array("name"      => $arrLang["Rate Offset"], 
                                                        "property1" => ""),
                                             4 => array("name"      => $arrLang["Trunk"],
                                                        "property1" => ""),
                                             5 => array("name"      => $arrLang["View"],
                                                        "property1" => "")
                                            )
                        );

        $oGrid = new paloSantoGrid($smarty);
        $oGrid->showFilter(
              "<form style='margin-bottom:0;' method='POST' action='?menu=billing_rates'>" .
              "<table><TR>".
              "<TD><input type='submit' name='submit_create_rate' value='{$arrLang['Create New Rate']}' class='button'></TD>".
              "<TD><input type='submit' name='submit_import_rate' value='{$arrLang['Import File']}' class='button'></TD>".
              "</TR></table>".
              "</form>");

        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }

    return $contenidoModulo;
}
?>
