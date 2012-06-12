<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-3                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-01 10:09:57 jjvega Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoExtention.class.php";
    global $arrConf;
    global $arrLang;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $lang = get_language();
    $script_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";

    if (file_exists("$script_dir/$lang_file"))
        include_once($lang_file);
    else
        include_once("modules/$module_name/lang/en.lang");
    global $arrLangModule;

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    //solo para obtener los devices (extensiones) creadas.
    $dsnAsteriskCdr = $arrConfig['AMPDBENGINE']['valor']."://".
                      $arrConfig['AMPDBUSER']['valor']. ":".
                      $arrConfig['AMPDBPASS']['valor']. "@".
                      $arrConfig['AMPDBHOST']['valor']."/asteriskcdrdb";

    $pDB_cdr = new paloDB($dsnAsteriskCdr);//asteriskcdrdb -> CDR

    $dsnAsteriskDev = $arrConfig['AMPDBENGINE']['valor']."://".
                      $arrConfig['AMPDBUSER']['valor']. ":".
                      $arrConfig['AMPDBPASS']['valor']. "@".
                      $arrConfig['AMPDBHOST']['valor']."/asterisk";

    $pDB_ext = new paloDB($dsnAsteriskDev);//asterisk -> devices
/*
    include_once "libs/paloSantoTrunk.class.php";
    print_r( getTrunks($pDB_ext) );
*/
/*
    $p = new paloSantoExtention($pDB_cdr);
    print_r( $p->loadTrunks("ZAP/2","dfh") );
*/
    $accion = getAction();

    $content = "";
    switch($accion)
    {
        case "show":
            $_POST['nav'] = null; $_POST['start'] = null;
            $content = report_Extention($smarty, $module_name, $local_templates_dir, $arrLang, $pDB_cdr, $pDB_ext, $arrLangModule);
            break;
        default:
            $content = report_Extention($smarty, $module_name, $local_templates_dir, $arrLang, $pDB_cdr, $pDB_ext, $arrLangModule);
            break;
    }

    return $content;
}

function report_Extention($smarty, $module_name, $local_templates_dir, $arrLang, $pDB_cdr, $pDB_ext, $arrLangModule)
{
    $arrCalls = array("All"=>$arrLang["All"],"Incoming_Calls" => $arrLang["Incoming Calls"],"Outcoming_Calls" => "Outcoming Calls");

    $arrFormElements = array(
        "date_from"         => array(   "LABEL"                  => $arrLangModule["Start date"],
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "DATE",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        "date_to"           => array(   "LABEL"                  => $arrLangModule["End date"],
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "DATE",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        "extensions"        => array(   "LABEL"                  => $arrLangModule["Number"],
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => loadExtentions($pDB_ext),
                                        "VALIDATION_TYPE"        => "text",
                                        "EDITABLE"               => "yes",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        "extensions_option" => array(   "LABEL"                  => "",
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => array("Number"=>"Number","Queue"=>"Queue","Trunk"=>"Trunk"),
                                        "VALIDATION_TYPE"        => "text",
                                        "EDITABLE"               => "yes",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        "call_to"           => array(   "LABEL"                  => $arrLangModule["Number"],
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("id" => 'call_to'),
                                        "VALIDATION_TYPE"        => "text",
                                        "EDITABLE"               => "yes",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        "trunks"            => array(   "LABEL"                  => "Trunk",
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => loadTrunks($pDB_ext),
                                        "VALIDATION_TYPE"        => "text",
                                        "EDITABLE"               => "yes",
                                        "VALIDATION_EXTRA_PARAM" => ""),
                            );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SHOW", $arrLang["Show"]);
    $smarty->assign("REQUIRED_FIELD", $arrLangModule["Required Field"]);
    $smarty->assign("HERE", $arrLangModule["Here"]);

    $date_ini = getParameter("date_from");
    $date_fin = getParameter("date_to");
    $ext = getParameter("call_to");
    $calls_io = getParameter("calls");

    $date_ini2 = ConverterDate($date_ini);
    $date_fin2 = ConverterDate($date_fin);
    $ext2 = $ext;

    $option = "";
    if( isset($_POST["menu"]) ){
        $option = $_POST["menu"];
        $smarty->assign("menu",$option);
    }

    if( getAction() == "show" ){
        $smarty->assign("date_from", $date_ini);
        $smarty->assign("date_1", $date_ini);

        $smarty->assign("date_to", $date_fin);
        $smarty->assign("date_2", $date_fin);

        $date_ini2 = ConverterDate($date_ini);
        $date_fin2 = ConverterDate($date_fin);
    }
    else{
        $_POST["date_from"] = date("d M Y");
        $_POST["date_to"] = date("d M Y");
        $date_ini = date("d M Y");
        $date_fin = date("d M Y");
        $date_ini2 = ConverterDate($date_ini);
        $date_fin2 = ConverterDate($date_fin);
    }

    $_POST["extensions"] = $ext;
    $_POST["calls"] = $calls_io;

    $smarty->assign("value_2", $date_ini);
    $smarty->assign("module_name", $module_name);

    $pExtention = new paloSantoExtention($pDB_cdr);

    if( $option == "Number" )
    {
        $smarty->assign("SELECTED_1","selected");
        $smarty->assign("SELECTED_2","");
        $smarty->assign("SELECTED_3","");

        //Paginacion
        //$limit  = 15;
        $total_datos = $pExtention->ObtainNumExtention($date_ini2, $date_fin2, $ext2, $calls_io);
        $total  = $total_datos[0];
    
        $numIn = 0; $numOut = 0; $numTot = 0;
        if($calls_io=="Incoming_Calls"){//CUANDO ES INCOMING
            $numTot_T = $pExtention->ObtainNumExtention($date_ini2, $date_fin2, $ext2, "");//total TODOS
            $numTot = $numTot_T[0];
            //$total -> incoming
            $numIn = $total;
            $numOut = $numTot - $numIn;  
        }
        else if($calls_io=="Outcoming_Calls"){
            $numTot_T = $pExtention->ObtainNumExtention($date_ini2, $date_fin2, $ext2, "");//total todos
            $numTot = $numTot_T[0];
            //$total -> outcoming
            $numIn = $numTot - $total;
            $numOut = $total;
        }
        else{
            $numIn_T = $pExtention->ObtainNumExtentionByIOrO($date_ini2, $date_fin2, $ext, 'in');
            //$total -> todos
            $numTot = $total;
            $numIn = $numIn_T[0];
            $numOut = $total - $numIn;
        }

        if($numIn != 0) $VALUE = (int)( 100*( $numIn/$numTot ) );
        else $VALUE = 0;

        $ruta_img = "<tr class='letra12'><td align='center'><img src='modules/{$module_name}/libs/grafic.php?du={$VALUE}%&in={$numIn}&out={$numOut}&ext={$ext2}&tot={$numTot}' border='0'></td></tr>";
        $smarty->assign("ruta_img", $ruta_img);
    }
    else if($option == "Queue"){
        $smarty->assign("SELECTED_1","");
        $smarty->assign("SELECTED_2","selected");
        $smarty->assign("SELECTED_3","");

        $ruta_img = "<tr class='letra12'><td align='center'><img src='modules/{$module_name}/libs/grafic_queue.php?queue={$ext2}&dti={$date_ini2}&dtf={$date_fin2}' border='0'></td></tr>";
        $smarty->assign("ruta_img", $ruta_img);
    }
    else if($option == "Trunk"){
        $smarty->assign("SELECTED_1","");
        $smarty->assign("SELECTED_2","");
        $smarty->assign("SELECTED_3","selected");

        $trunkT = getParameter("trunks");
        $smarty->assign("trunks", $trunkT);

        //$ruta_img  = "<tr class='letra12'><td align='center'><img src='modules/{$module_name}/libs/grafic_trunk.php?trunk={$trunkT}&dti={$date_ini2}&dtf={$date_fin2}' border='0'></td></tr>";
        //$ruta_img .= "<tr class='letra12'><td align='center'><img src='modules/{$module_name}/libs/grafic_trunk2.php?trunk={$trunkT}&dti={$date_ini2}&dtf={$date_fin2}' border='0'></td></tr>";
        $ruta_img  = "<tr class='letra12'><td align='center'><img src='modules/{$module_name}/libs/grafic_trunk.php?trunk={$trunkT}&dti={$date_ini2}&dtf={$date_fin2}' border='0'>&nbsp;&nbsp;
                                                             <img src='modules/{$module_name}/libs/grafic_trunk2.php?trunk={$trunkT}&dti={$date_ini2}&dtf={$date_fin2}' border='0'></td></tr>";

        $smarty->assign("ruta_img", $ruta_img);
    }
    else{//default
        $ruta_img = "<tr class='letra12'><td align='center'><img src='modules/{$module_name}/libs/grafic.php?du=0%&in=0&out=0&ext=0&tot=0' border='0'><td></tr>";
        $smarty->assign("ruta_img", $ruta_img);
    }

    $htmlForm = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", $arrLangModule["Graphic Report"], $_POST);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function loadExtentions($pDB_ext)
{
    $pExtention = new paloSantoExtention($pDB_ext);
    $arrayExt = $pExtention->loadExtentions();

    $arrayR = array();
    foreach($arrayExt as $key => $value){
        $arrayR[$value['id']] = $value['id'];
    }

    return $arrayR; 
}

function ConverterDate($date)
{
    //$date DD MM AAAA
    $date_fin = "";
    $dia = "";
    $mes = "";
    $anio = "";

    $cont = 0;
    $posTemp = 0;
    $leng = strlen($date);
    if( $leng != 0 ){
        for($i = 0; $i < $leng; $i++ ){
            if( strcmp($date[$i]," ") == 0 ){
    
                if( $cont == 0 ) $dia = substr($date, $posTemp, $i);
                if( $cont == 1 ){ 
                    $mes = substr($date, $posTemp+1, $i - strlen($dia) - 1 );
                    $anio = substr($date, $i+1);
                    break;
                }

                $cont++;
                $posTemp = $i;
            }
        }
    }

    return "$anio-".MesStr2Int($mes)."-$dia"; 
}

function MesStr2Int($str_mes)
{
    switch($str_mes){
        case "Jan": return "01";
        case "Feb": return "02";
        case "Mar": return "03";
        case "Apr": return "04";
        case "May": return "05";
        case "Jun": return "06";
        case "Jul": return "07";
        case "Aug": return "08";
        case "Sep": return "09";
        case "Oct": return "10";
        case "Nov": return "11";
        case "Dec": return "12";
        default: return "00";
    }
}

function redondear_dos_decimal($valor)
{
    $float_redondeado = round($valor * 100) / 100;

    return $float_redondeado;
} 

function loadTrunks($pDB_ext)
{
    //Array ( [0] => Array ( [0] => OUT_1 [1] => ZAP/g0 )
    //        [1] => Array ( [0] => OUT_2 [1] => ZAP/g1 )
    //        [2] => Array ( [0] => OUT_3 [1] => ZAP/g2 ) 

    include_once "libs/paloSantoTrunk.class.php";

    $arrTrunksTemp = getTrunks($pDB_ext);
    $arrTrunk = array();
    foreach($arrTrunksTemp as $key => $arr){
        $arrTrunk[ $arr[1] ] = $arr[1]; 
    }

    return $arrTrunk;
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
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}
?>
