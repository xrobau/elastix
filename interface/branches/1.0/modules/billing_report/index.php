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
    include_once "libs/paloSantoCDR.class.php";
    require_once "libs/misc.lib.php";
    include_once "libs/paloSantoRate.class.php";
    include_once "libs/paloSantoTrunk.class.php";
    
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    global $arrConf;
    global $arrLang;
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
               $arrConfig['AMPDBHOST']['valor'] . "/asteriskcdrdb";
    $dsn2     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
               $arrConfig['AMPDBHOST']['valor'] . "/asterisk";
    $pDBTrunk = new paloDB("sqlite3:////var/www/db/trunk.db");
    $pDBSet = new paloDB("sqlite3:////var/www/db/settings.db");
    $pDB     = new paloDB($dsn);
    $arrData = array();
    $total = 0;
    $oCDR    = new paloSantoCDR($pDB);
    $smarty->assign("menu","billing_report");
    $pDB2     = new paloDB($dsn2);

    $pDBSQLite = new paloDB("sqlite3:////var/www/db/rate.db");
    if(!empty($pDBSQLite->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }


    $pRate = new paloRate($pDBSQLite);
    if(!empty($pRate->errMsg)) {
        echo "ERROR DE RATE: $pRate->errMsg <br>";
    }


    if(isset($_GET['exportcsv']) && $_GET['exportcsv']=='yes') {

        $limit = "";
        $offset = 0;
        if(empty($_GET['date_start'])) {
            $date_start = date("Y-m-d") . " 00:00:00"; 
        } else {
            $date_start = translateDate($_GET['date_start']) . " 00:00:00";
        }
        if(empty($_GET['date_end'])) { 
            $date_end = date("Y-m-d") . " 23:59:59"; 
        } else {
            $date_end   = translateDate($_GET['date_end']) . " 23:59:59";
        }
        $field_name = $_GET['field_name'];
        $field_pattern = $_GET['field_pattern'];
        $status = $_GET['status'];
        header("Cache-Control: private");
        header("Pragma: cache");
        header('Content-Type: application/octec-stream');
        header('Content-disposition: inline; filename="billing_report.csv"');
        header('Content-Type: application/force-download');


    } else {
    
        $arrFormElements = array("date_start"  => array("LABEL"                  => $arrLang["Start Date"],
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "DATE",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                                 "date_end"    => array("LABEL"                  => $arrLang["End Date"],
                                                        "REQUIRED"               => "yes",
                                                        "INPUT_TYPE"             => "DATE",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                                 "field_name"  => array("LABEL"                  => $arrLang["Field Name"],
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "SELECT",
                                                        "INPUT_EXTRA_PARAM"      => array( "dst"         => $arrLang["Destination"],
                                                                                           "src"         => $arrLang["Source"],
                                                                                           "dstchannel"  => $arrLang["Dst. Channel"]),
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^(dst|src|channel|dstchannel)$"),
                                 "field_pattern" => array("LABEL"                  => $arrLang["Field"],
                                                        "REQUIRED"               => "no",
                                                        "INPUT_TYPE"             => "TEXT",
                                                        "INPUT_EXTRA_PARAM"      => "",
                                                        "VALIDATION_TYPE"        => "ereg",
                                                        "VALIDATION_EXTRA_PARAM" => "^[[:alnum:]@_\.,/\-]+$"),
                                 );

        $smarty->assign("Filter",$arrLang['Filter']);
        $oFilterForm = new paloForm($smarty, $arrFormElements);
    
        // Por omision las fechas toman el sgte. valor (la fecha de hoy)
        $date_start = date("Y-m-d") . " 00:00:00"; 
        $date_end   = date("Y-m-d") . " 23:59:59";
        $field_name = "";
        $field_pattern = ""; 
        $status = "ALL"; 
    
        if(isset($_POST['filter'])) {
            if($oFilterForm->validateForm($_POST)) {
                // Exito, puedo procesar los datos ahora.
                $date_start = translateDate($_POST['date_start']) . " 00:00:00"; 
                $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
                $field_name = $_POST['field_name'];
                $field_pattern = $_POST['field_pattern'];
                $arrFilterExtraVars = array("date_start" => $_POST['date_start'], "date_end" => $_POST['date_end'], 
                                            "field_name" => $_POST['field_name'], "field_pattern" => $_POST['field_pattern'],);
            } else {
                // Error
                $smarty->assign("mb_title", $arrLang["Validation Error"]);
                $arrErrores=$oFilterForm->arrErroresValidacion;
                $strErrorMsg = "<b>{$arrLang['The following fields contain errors']}:</b><br>";
                foreach($arrErrores as $k=>$v) {
                    $strErrorMsg .= "$k, ";
                }
                $strErrorMsg .= "";
                $smarty->assign("mb_message", $strErrorMsg);
            }
            $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/billing_report.tpl", "", $_POST);
    
        } else if(isset($_GET['date_start']) AND isset($_GET['date_end'])) {
            $date_start = translateDate($_GET['date_start']) . " 00:00:00";
            $date_end   = translateDate($_GET['date_end']) . " 23:59:59";
            $field_name = $_GET['field_name'];
            $field_pattern = $_GET['field_pattern'];
            $status = $_GET['status'];
            $arrFilterExtraVars = array("date_start" => $_GET['date_start'], "date_end" => $_GET['date_end']);
            $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/billing_report.tpl", "", $_GET);
        } else {
            $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/billing_report.tpl", "", 
                          array('date_start' => date("d M Y"), 'date_end' => date("d M Y"),'field_name' => 'dst','field_pattern' => '' ));
        }
    
        // LISTADO
    
        $limit = 50;
        $offset = 0;
    
        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="end") {
            $arrCDRTmp  = $oCDR->obtenerCDRs($limit, $offset, $date_start, $date_end, $field_name, $field_pattern,"ANSWERED","outgoing");
            $totalCDRs  = $arrCDRTmp['NumRecords'][0];
            // Mejorar el sgte. bloque.
            if(($totalCDRs%$limit)==0) {
                $offset = $totalCDRs - $limit;
            } else {
                $offset = $totalCDRs - $totalCDRs%$limit;
            }
        }
    
        // Si se quiere avanzar a la sgte. pagina
        if(isset($_GET['nav']) && $_GET['nav']=="next") {
            $offset = $_GET['start'] + $limit - 1;
        }
    
        // Si se quiere retroceder
        if(isset($_GET['nav']) && $_GET['nav']=="previous") {
            $offset = $_GET['start'] - $limit - 1;
        }
    
        // Construyo el URL base
        if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) and count($arrFilterExtraVars)>0) {
            $url = construirURL($arrFilterExtraVars, array("nav", "start")); 
        } else {
            $url = construirURL(array(), array("nav", "start")); 
        }
        $smarty->assign("url", $url);
    
    }    


    // Bloque comun
    //consulto cuales son los trunks de salida
    $oTrunk    = new paloTrunk($pDBTrunk);
    $troncales = $oTrunk->getExtendedTrunksBill($grupos, $arrConfig['ASTETCDIR']['valor'].'/zapata.conf');//ej array("ZAP/1","ZAP/2");

    //echo "<pre>".print_r($troncales,1)."</pre>";
    //echo "<pre>".print_r($grupos,1)."</pre>";
    if (is_array($troncales) && count($troncales)>0){
        $arrCDR  = $oCDR->obtenerCDRs($limit, $offset, $date_start, $date_end, $field_name, $field_pattern,"ANSWERED","outgoing",$troncales);

        $total =$arrCDR['NumRecords'][0];

        foreach($arrCDR['Data'] as $cdr) {
        //tengo que buscar la tarifa para el numero de telefono
            if (ereg("^Zap/([[:digit:]]+)",$cdr[4],$regs3)) $trunk='ZAP/g'.$grupos[$regs3[1]];
            else $trunk=str_replace(strstr($cdr[4],'-'),'',$cdr[4]);

            $numero=$cdr[2];
            $arrTmp    = array();
            $arrTmp[0] = $cdr[0];
            if(isset($_GET['exportcsv']) && $_GET['exportcsv']=='yes'){
                $arrTmp[1] = ($cdr[1]?$cdr[1]:$arrLang["Unknown"]);
                $arrTmp[3] = $cdr[4];
            } else {
                $arrTmp[1] = "<div title=\"{$arrLang['Channel']}: $cdr[3]\" align=\"left\">".($cdr[1]?$cdr[1]:$arrLang["Unknown"])."</div>";
                $arrTmp[3] = "<div title=\"{$arrLang['Trunk']}: $trunk\" align=\"left\">$cdr[4]</div>";
            }
            $arrTmp[2] = $cdr[2];
            $arrTmp[4] = $cdr[8];
            $charge=0;
            $tarifa=array();
            $bExito=$pRate->buscarTarifa($numero,$tarifa,$trunk);
            if (!count($tarifa)>0 && ($bExito)) $bExito=$pRate->buscarTarifa($numero,$tarifa,'None');

            $rate_name="";
            if (!$bExito)
            {
                echo "ERROR DE RATE: $pRate->errMsg <br>";
            }else
            {

             //verificar si tiene tarifa
                if (count($tarifa)>0)
                {
                    $bTarifaOmision=FALSE;
                    foreach ($tarifa as $id_tarifa=>$datos_tarifa)
                    {
                        $charge=(($cdr[8]/60)*$datos_tarifa['rate'])+$datos_tarifa['offset'];
                        $rate_name=$datos_tarifa['name'];
                    }
                }else
                {
                    $bTarifaOmision=TRUE;
                    $rate_name=$arrLang["default"];
                //no tiene tarifa buscar tarifa por omision
                //por ahora para probar $1 el minuto
                    $rate=get_key_settings($pDBSet,"default_rate");
                    $rate_offset=get_key_settings($pDBSet,"default_rate_offset");
                    $charge=(($cdr[8]/60)*$rate)+$rate_offset;
                }
            }
            $arrTmp[5] = number_format($charge,3);
            $sum_cost  = $sum_cost+$arrTmp[5]; 
            $arrTmp[6] = $sum_cost;
            $arrTmp[7] = $rate_name;
            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array("title"    => $arrLang["Billing Report"],
                     "icon"     => "images/user.png",
                     "width"    => "99%",
                     "start"    => ($total==0) ? 0 : $offset + 1,
                     "end"      => ($offset+$limit)<=$total ? $offset+$limit : $total,
                     "total"    => $total,
                     "columns"  => array(0 => array("name"      => $arrLang["Date"],
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Source"],
                                                    "property1" => ""),
                                         2 => array("name"      => $arrLang["Destination"],
                                                    "property1" => ""),
                                         3 => array("name"	=> $arrLang["Dst. Channel"],
                                         			"property"	=> ""),
                                         4 => array("name"	=> $arrLang["Duration in seconds"],
                                         			"property"	=> ""),
                                         5 => array("name"	=> $arrLang["Cost"],
                                         			"property"	=> ""),
                                         6 => array("name"      => $arrLang["Summary Cost"],
                                                                "property"      => ""),
                                         7 => array("name"	=> $arrLang["Rate Applied"],
                                         			"property"	=> ""),
                                        )
                    );

    // Creo objeto de grid
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->enableExport();
    
    if(isset($_GET['exportcsv']) && $_GET['exportcsv']=='yes') {
        return $oGrid->fetchGridCSV($arrGrid, $arrData);
    } else {
        $oGrid->showFilter($htmlFilter);
        return $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    }
}
?>
