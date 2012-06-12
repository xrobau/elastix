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
  $Id: index.php,v 1.3 2007/09/05 00:26:21 gcarrillo Exp $
  $Id: index.php,v 1.3 2008/04/14 09:22:21 afigueroa Exp $  */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoACL.class.php";
    include_once "libs/paloSantoForm.class.php";
    require_once "libs/misc.lib.php";

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

    $pDBCDR     = new paloDB($dsn);
    //segun el usuario que esta logoneado consulto si tiene asignada extension para buscar los voicemails
    $pDB = new paloDB("sqlite3:////var/www/db/acl.db");

    if (!empty($pDB->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $arrData = array();
    $pACL = new paloACL($pDB);
    if (!empty($pACL->errMsg)) {
        echo "ERROR DE ACL: $pACL->errMsg <br>";
    }
    $arrVoiceData = array();
    $llamadas = array();
    $inicio= $fin = $total = 0;
    $extension = $pACL->getUserExtension($_SESSION['elastix_user']);
    $esAdministrador = $pACL->isUserAdministratorGroup($_SESSION['elastix_user']);
    $tmpExtension=$extension;
    if($esAdministrador)
        $extension="[[:digit:]]+";

    //filtro de fechas
    $smarty->assign("menu","monitoring");
    $smarty->assign("Filter",$arrLang['Filter']);
   //formulario para el filtro
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
                                 );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    // Por omision las fechas toman el sgte. valor (la fecha de hoy)
    $date_start = date("Y-m-d") . " 00:00:00";
    $date_end   = date("Y-m-d") . " 23:59:59";

    if(isset($_POST['filter'])) {
            if($oFilterForm->validateForm($_POST)) {
                // Exito, puedo procesar los datos ahora.
                $date_start = translateDate($_POST['date_start']) . " 00:00:00"; 
                $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
                $arrFilterExtraVars = array("date_start" => $_POST['date_start'], "date_end" => $_POST['date_end']
                                            );
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
            $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
    } else if (isset($_GET['date_start']) AND isset($_GET['date_end'])) {
        $date_start = translateDate($_GET['date_start']) . " 00:00:00";
        $date_end   = translateDate($_GET['date_end']) . " 23:59:59";

        $arrFilterExtraVars = array("date_start" => $_GET['date_start'], "date_end" => $_GET['date_end']);
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_GET);
    } else {
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", 
        array('date_start' => date("d M Y"), 'date_end' => date("d M Y")));
    }

    if(isset($_POST['submit_eliminar'])) {
        borrarRecordings();
        if($oFilterForm->validateForm($_POST)) {
                // Exito, puedo procesar los datos ahora.
                $date_start = translateDate($_POST['date_start']) . " 00:00:00"; 
                $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
                $arrFilterExtraVars = array("date_start" => $_POST['date_start'], "date_end" => $_POST['date_end']
                                            );
        }
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
    }

    //si tiene extension consulto sino, muestro un mensaje de que no tiene asociada extension
    if (!is_null($extension) && $extension!=""){
        $path = "/var/spool/asterisk/monitor";
        $archivos = array();

        if (file_exists($path)) {
            if ($handle = opendir($path)) {
                $bExito=true;
                while (false !== ($file = readdir($handle))) {
                //no tomar en cuenta . y ..
                    if ($file!="." && $file!="..")
                    {
                        $date = Files_Between_Dates($file, $extension, $date_start, $date_end, $esAdministrador);
                        if($date!=false)
                            $archivos[] = array(0 => $file, 1 => $date);
                    }
                }
                closedir($handle);
            }
        } else {
            // No vale la ruta
        }

        //Ordenamiento por fechas en orden descendente (nuevos primero)
        $fechas = array();
        //$horas  = array();
        foreach ($archivos as $llave => $valor)
            $fechas[$llave]  = $valor[1];
        array_multisort($fechas,SORT_DESC,$archivos);


        //Paginacion
        $limit  = 15;
        $total  = count($archivos);

        $oGrid  = new paloSantoGrid($smarty);
        $offset = $oGrid->getOffSet($limit,$total,(isset($_GET['nav']))?$_GET['nav']:NULL,(isset($_GET['start']))?$_GET['start']:NULL);

        $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;

        // Construyo el URL base
        if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) and count($arrFilterExtraVars)>0) {
            $url = construirURL($arrFilterExtraVars, array("nav", "start"));
        } else {
            $url = construirURL(array(), array("nav", "start")); 
        }
        $smarty->assign("url", $url);
        //Fin Paginacion

        for($i=$offset; $i<$end; $i++)
        {
            $archivo = $archivos[$i][0];
            //tengo que obtener los archivos que pertenezcan a la extension
           //obtener los archivos con formato auto-timestamp-extension... grabacion ONDEMAND
            //"auto\-[[:digit:]]+\-$extension(.+)\.[wav|WAV]$"

            $llamada_incoming=false;
            $llamada_outgoing = false;
            if (ereg("^auto\-([[:digit:]]+)\-$extension(.+)\.[wav|WAV|gsm]",$archivo,$regs)){
                //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                $llamada=obtenerCDROnDemand($pDBCDR,$extension,$regs[1], $esAdministrador);
                if(count($llamada)>0){
                    $llamada['archivo']=$archivo;
                    $llamada['type'] = "on demand";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
             }
            //buscar llamadas incoming IN-extension-uniqueid
            else if (ereg("^IN\-$extension\-([[:digit:]]+(\.[[:digit:]]+)*)\.[wav|WAV|gsm]",$archivo,$regs)){
                $llamada_incoming = true;
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "auto - incoming";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }
            //buscar llamadas incoming IN-extension-fecha-hora
            else if (!$llamada_incoming && ereg("^IN\-$extension\-([[:digit:]]+)\-([[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs)){
                //formar la fecha y la hora
                $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                $calldate="$fecha $hora";
                //busco por fecha y extension destino
                //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                $llamada=obtenerCDRIncoming($pDBCDR,$extension, $calldate, $esAdministrador);
                if(count($llamada)>0){
                    $llamada['archivo']=$archivo;
                    $llamada['type'] = "auto - incoming";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }/*
            else if (!$llamada_incoming && ereg("g$extension\-([[:digit:]]+)\-([[:digit:]]+)(.+)\.[wav|WAV|gsm]",$archivo,$regs)){
                 //formar la fecha y la hora
                 $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                 $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                 $calldate="$fecha $hora";
                 //busco por fecha y extension destino
                 //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                 $llamada=obtenerCDRIncoming($pDBCDR,$extension, $calldate);
                 $llamada['archivo']=$archivo;
                 $llamada['type'] = "incoming";
                 $llamadas[strtotime($llamada['calldate'])]=$llamada;
            }*/

            //g1-1207292249.1473.wav
            else if (!$llamada_incoming && ereg("^g$extension-([[:digit:]]+\.[[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs))
            {
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "always";
                    if($extension==$llamada['src'] || $extension==$llamada['dst'] || $extension=="[[:digit:]]+") //se se cumple esto es porque es el usuario solo puede ver sus llamadas y la otra es porque es administrador
                        $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }
            //g121-20070828-162421-1188336241.1610.wav
            else if (!$llamada_incoming && ereg("^g$extension-[[:digit:]]+-[[:digit:]]+-([[:digit:]]+\.[[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs))
            {
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "always";
                    if($extension==$llamada['src'] || $extension==$llamada['dst'] || $extension=="[[:digit:]]+") //se se cumple esto es porque es el usuario solo puede ver sus llamadas y la otra es porque es administrador
                        $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }

             //buscar llamadas OUTGOING
             //OUT-ext-uniqueid.wav
            //OUT-104-1208782232.2382.wav 
            else if (ereg("^OUT\-$extension\-([[:digit:]]+(\.[[:digit:]]+)*)\.[wav|WAV|gsm]",$archivo,$regs)){
                $llamada_outgoing = true;
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "auto - outgoing";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }
            //OUT404--20070426-090918.wav
            else if (!$llamada_outgoing && ereg("^OUT$extension\-([[:digit:]]+)\-([[:digit:]]+)(.+)\.[wav|WAV|gsm]",$archivo,$regs)){
                //formar la fecha y la hora
                $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                $calldate="$fecha $hora";
                //busco por fecha y extension destino
                //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                $llamada=obtenerCDROutgoing($pDBCDR,$extension, $calldate, $esAdministrador);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "auto - outgoing";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
             }
            else if (!$llamada_outgoing && ereg("^OUT$extension\-[(.+)|\-]*([[:digit:]]+)\-([[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs)){
                //formar la fecha y la hora
                $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                $calldate="$fecha $hora";
                //busco por fecha y extension destino
                //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                $llamada=obtenerCDROutgoing($pDBCDR,$extension, $calldate, $esAdministrador);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "auto - outgoing";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }

            /****PARA LAS COLAS****/
            //q7000-20080411-180242-1207954962.473.wav
            else if($esAdministrador && ereg("^q[[:digit:]]+-[[:digit:]]+-[[:digit:]]+-([[:digit:]]+\.[[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs))
            {
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "queue - total";
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }
            //q7000-20080411-162833-1207949313.9-in.wav
            else if($esAdministrador && ereg("^q[[:digit:]]+-[[:digit:]]+-[[:digit:]]+-([[:digit:]]+\.[[:digit:]]+)-in\.[wav|WAV|gsm]",$archivo,$regs))
            {
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "queue - in";
                    $llamadas[strtotime($llamada['calldate'])."-in"]=$llamada;
                }
            }
            //q7000-20080411-162833-1207949313.9-out.wav
            else if($esAdministrador && ereg("^q[[:digit:]]+-[[:digit:]]+-[[:digit:]]+-([[:digit:]]+\.[[:digit:]]+)-out\.[wav|WAV|gsm]",$archivo,$regs))
            {
                $unique_id=$regs[1];
                $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "queue - out";
                    $llamadas[strtotime($llamada['calldate'])."-out"]=$llamada;
                }
            }


             // El caso para cuando a la extension se le configuró sus records incoming or outgoing a always 
            else if (ereg("[[:digit:]]+\-[[:digit:]]+\-([[:digit:]]+.[[:digit:]]+).[wav|WAV|gsm]",$archivo,$regs)){
                $unique_id = $regs[1];
                $llamada = obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
                if(count($llamada)>0){
                    $llamada['archivo'] = $archivo;
                    $llamada['type'] = "always";
                    if($extension==$llamada['src'] || $extension==$llamada['dst'] || $extension=="[[:digit:]]+") //se se cumple esto es porque es el usuario solo puede ver sus llamadas y la otra es porque es administrador
                        $llamadas[strtotime($llamada['calldate'])]=$llamada;
                }
            }
        }

        if($tmpExtension=="" || is_null($tmpExtension))//validacion solo para usuarios del grupo administrator
            $smarty->assign("mb_message", "<b>".$arrLang["You don't have extension number associated with user"]."</b>");
        //rsort($llamadas);

        foreach ($llamadas as $llamada){ 
            $fecha = date("Y-m-d",strtotime($llamada['calldate']));
            $hora = date("H:i:s",strtotime($llamada['calldate']));

            $pathRecordFile="$path/".$llamada['archivo'];
            $arrTmp[0] = "<input type='checkbox' name='".utf8_encode("rcd-".$llamada['archivo'])."' />";
            $arrTmp[1] = $fecha;
            $arrTmp[2] = $hora;
            $arrTmp[3] = empty($llamada['src'])?'-':$llamada['src'];
            $arrTmp[4] = $llamada['dst'];
            $arrTmp[5] = $llamada['duration'].' sec.';
            $arrTmp[6] = $llamada['type'];
            $recordingLink = "<a href='#' onClick=\"javascript:popUp('includes/popup.php?action=display_record&record_file=" . base64_encode($pathRecordFile) ."',350,100); return false;\">{$arrLang['Listen']}</a>&nbsp;";
            $recordingLink .= "<a href='includes/audio.php?recording=".base64_encode($pathRecordFile)."'>{$arrLang['Download']}</a>";
            $arrTmp[7] = $recordingLink;
            $arrData[] = $arrTmp;
        }
    } //fin if (!is_null(extension))
    else {
        $smarty->assign("mb_message", "<b>".$arrLang["You don't have extension number associated with user"]."</b>");
    }
    $arrGrid = array("title"    => $arrLang["Monitorig List"],
                     "icon"     => "images/record.png",
                     "width"    => "99%",
                     "start"    => ($total==0) ? 0 : $offset + 1,
                     "end"      => $end,
                     "total"    => $total,
                     "columns"  => array(0 => array("name"      => "<input type='submit' onClick=\"return confirmSubmit('{$arrLang["Are you sure you wish to delete recordings?"]}');\" name='submit_eliminar' value='{$arrLang["Delete"]}' class='button' />",
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Date"],
                                                    "property1" => ""),
                                         2 => array("name"      => $arrLang["Time"],
                                                    "property1" => ""),
                                         3 => array("name"      => $arrLang["Source"],
                                                    "property1" => ""),
                                         4 => array("name"      => $arrLang["Destination"],
                                                    "property1" => ""),
                                         5 => array("name"      => $arrLang["Duration"],
                                                    "property1" => ""),
                                         6 => array("name"      => $arrLang["Type"],
                                                    "property1" => ""),
                                         7 => array("name"      => $arrLang["Message"],
                                                    "property1" => ""),
                                        )
                    );


    $contenidoModulo  = "<form style='margin-bottom:0;' method='POST' action='?menu=$module_name'>";
    $oGrid = new paloSantoGrid($smarty);
    $oGrid->showFilter($htmlFilter);
    $contenidoModulo  .= $oGrid->fetchGrid($arrGrid, $arrData,$arrLang);
    $contenidoModulo .= "</form>";
    return $contenidoModulo;
}

function Files_Between_Dates($file, $extension, $date_start, $date_end, $esAdministrador)
{
    //Se obtiene la fecha por timestamp
    //este valor es siempre unico generalmente lleva adjunto un id
    $fecha = 0;
    if (ereg("^auto\-([[:digit:]]+)\-$extension(.+)\.[wav|WAV|gsm]",$file,$regs))
        $fecha = $regs[1];


    /****llamadas incoming IN-extension-uniqueid****/
    //IN-100-1207645055.197.wav
    else if (ereg("^IN\-$extension\-([[:digit:]]+)[\.[[:digit:]]+]*\.[wav|WAV|gsm]",$file,$regs))
        $fecha = $regs[1];
    else if (ereg("^IN\-$extension\-([[:digit:]]+)\-([[:digit:]]+)\.[wav|WAV|gsm]",$file,$regs)){
        //formar la fecha y la hora
        $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
        $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
        $calldate="$fecha $hora";
        $fecha = strtotime($calldate);
    }

    //if (ereg("g$extension\-([[:digit:]]+)\-([[:digit:]]+)(.+)\.[wav|WAV|gsm]",$file,$regs))
    //g1-1207292249.1473.wav
    else if (ereg("^g$extension-([[:digit:]]+)\.[[:digit:]]+\.[wav|WAV|gsm]",$file,$regs))
        $fecha = $regs[1];
    //g121-20070828-162421-1188336241.1610.wav
    else if (ereg("^g$extension-[[:digit:]]+-[[:digit:]]+-([[:digit:]]+)\.[[:digit:]]+\.[wav|WAV|gsm]",$file,$regs))
        $fecha = $regs[1];

    /****llamadas incoming IN-extension-uniqueid****/



    /****llamadas OUTGOING OUT-extension-uniqueid****/
    //OUT-504-1207151691.420.wav
    else if (ereg("^OUT\-$extension\-([[:digit:]]+)[\.[[:digit:]]+]*\.[wav|WAV|gsm]",$file,$regs))
        $fecha = $regs[1];
    //OUT404-20070426-090918.wav
    //OUT504-20080402-133229-1207161149.873.wav
    else if (ereg("^OUT$extension\-([[:digit:]]+)\-([[:digit:]]+)(.+)\.[wav|WAV|gsm]",$file,$regs))
    {
        $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
        $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
        $calldate="$fecha $hora";
        $fecha = strtotime($calldate);
    }
    else if (ereg("^OUT$extension\-[(.+)|\-]*([[:digit:]]+)\-([[:digit:]]+)\.[wav|WAV|gsm]",$file,$regs))
    {
        //formar la fecha y la hora
        $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
        $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
        $calldate="$fecha $hora";
        $fecha = strtotime($calldate);
    }
    /****llamadas OUTGOING OUT-extension-uniqueid****/



    /****Colas****/
    //q7000-20080411-180242-1207954962.473.wav
    //q7000-20080411-162833-1207949313.9-in.wav
    //q7000-20080411-162833-1207949313.9-out.wav
    else if($esAdministrador && ereg("^q[[:digit:]]+-[[:digit:]]+-[[:digit:]]+-([[:digit:]]+)\.[[[:digit:]]+|[[:digit:]]+-in|[[:digit:]]+-out]\.[wav|WAV|gsm]",$file,$regs))
            $fecha = $regs[1];
    /****Colas****/



    //El caso para cuando a la extension se le configuró sus records incoming or outgoing a always
    else if (ereg("^[[:digit:]]+\-[[:digit:]]+\-([[:digit:]]+).[[:digit:]]+.[wav|WAV|gsm]",$file,$regs))
        $fecha = $regs[1];


    //COMPARAR LAS FECHAS
    if ($fecha<=strtotime($date_end) && $fecha>=strtotime($date_start))
        return $fecha;

    return false;
}

function obtenerCDROnDemand($db, $extension, $start_time, $esAdministrador)
{
    $arr_result=array();
    $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE $start_time BETWEEN UNIX_TIMESTAMP(calldate) AND (UNIX_TIMESTAMP(calldate)+duration)";
    if(!$esAdministrador)
        $query .= " AND (src='$extension' OR dst='$extension')";

    $arr_result=$db->getFirstRowQuery($query,TRUE);
    if (is_array($arr_result) && count($arr_result)>0) {
    }
    return $arr_result;
}

function obtenerCDRIncoming($db,$extension, $calldate, $esAdministrador)
{
    $arr_result=array();
    $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE calldate='$calldate'";
    if(!$esAdministrador)
        $query .= " AND dst='$extension'";

    $arr_result=$db->getFirstRowQuery($query,TRUE);
    if (is_array($arr_result) && count($arr_result)>0) {
    }
    return $arr_result;
}

function obtenerCDROutgoing($db,$extension, $calldate, $esAdministrador)
{
    $arr_result=array();
    $query  = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE calldate='$calldate'";
    if(!$esAdministrador)
        $query .= " AND src='$extension'";

    $arr_result=$db->getFirstRowQuery($query,TRUE);
    if (is_array($arr_result) && count($arr_result)>0) {
    }
    return $arr_result;
}

function obtenerCDR_with_uniqueid($db,$uniqueid)
{
    $arr_result=array();
    $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE uniqueid='$uniqueid'";

    $arr_result=$db->getFirstRowQuery($query,TRUE);
    if (is_array($arr_result) && count($arr_result)>0) {
    }
    return $arr_result;
}

function borrarRecordings()
{
    $path = "/var/spool/asterisk/monitor";

    if(is_array($_POST) && count($_POST) > 0){
        foreach($_POST as $name => $on){
            if(substr($name,0,4)=='rcd-'){
                $file = substr($name,4);
                $file = str_replace("_",".",$file);
                unlink("$path/$file");
            }
        }
    }
}
?>