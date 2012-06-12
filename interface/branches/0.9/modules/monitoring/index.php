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
  $Id: index.php,v 1.3 2007/09/05 00:26:21 gcarrillo Exp $ */

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
                    if ($file!="." && $file!=".." )
                        $archivos[]=$file;
                }
                closedir($handle);
            }
        } else {
            // No vale la ruta
        }
        rsort($archivos);
        
        foreach($archivos as $archivo) {
            //tengo que obtener los archivos que pertenezcan a la extension
           //obtener los archivos con formato auto-timestamp-extension... grabacion ONDEMAND
            //"auto\-[[:digit:]]+\-$extension(.+)\.[wav|WAV]$"
            if (ereg("auto\-([[:digit:]]+)\-$extension(.+)\.[wav|WAV|gsm]",$archivo,$regs)){
                 //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                 $llamada=obtenerCDROnDemand($pDBCDR,$extension,$regs[1]);
                 $llamada['archivo']=$archivo;
                 $llamada['type'] = "on demand";
                 $llamadas[strtotime($llamada['calldate'])]=$llamada;
             }
			 $llamada_incoming=false;
            //buscar llamadas incoming IN-extension-uniqueid
            if (ereg("IN\-$extension\-([[:digit:]]+(\.[[:digit:]]+)*)\.[wav|WAV|gsm]",$archivo,$regs)){
            	$llamada_incoming = true;
            	$unique_id=$regs[1];
            	$llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
            	$llamada['archivo'] = $archivo;
            	$llamada['type'] = "auto - incoming";
                $llamadas[strtotime($llamada['calldate'])]=$llamada;
              }
            //buscar llamadas incoming IN-extension-fecha-hora
            if (!$llamada_incoming && ereg("IN\-$extension\-([[:digit:]]+)\-([[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs)){
                 //formar la fecha y la hora
                 $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                 $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                 $calldate="$fecha $hora";
                 //busco por fecha y extension destino
                 //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                 $llamada=obtenerCDRIncoming($pDBCDR,$extension, $calldate);
                 $llamada['archivo']=$archivo;
                 $llamada['type'] = "auto - incoming";
                 $llamadas[strtotime($llamada['calldate'])]=$llamada;
             }
//g121-20070828-162421-1188336241.1610.wav
            if (!$llamada_incoming && ereg("g$extension\-([[:digit:]]+)\-([[:digit:]]+)(.+)\.[wav|WAV|gsm]",$archivo,$regs)){
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
             }
             //buscar llamadas OUTGOING
             
             //OUT-ext-uniqueid.wav
          	$llamada_outgoing = false;
              if (ereg("OUT\-$extension\-([[:digit:]]+(\.[[:digit:]]+)*)\.[wav|WAV|gsm]",$archivo,$regs)){
            	$llamada_outgoing = true;
            	$unique_id=$regs[1];
            	$llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
            	$llamada['archivo'] = $archivo;
            	$llamada['type'] = "auto - outgoing";
                $llamadas[strtotime($llamada['calldate'])]=$llamada;
              }
             //OUT404--20070426-090918.wav

            if (!$llamada_outgoing && ereg("OUT$extension\-([[:digit:]]+)\-([[:digit:]]+)(.+)\.[wav|WAV|gsm]",$archivo,$regs)){
                 //formar la fecha y la hora
                 $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                 $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                 $calldate="$fecha $hora";
                 //busco por fecha y extension destino
                 //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                 $llamada=obtenerCDROutgoing($pDBCDR,$extension, $calldate);
                 $llamada['archivo'] = $archivo;
                 $llamada['type'] = "auto - outgoing";
                 $llamadas[strtotime($llamada['calldate'])]=$llamada;
             }
            if (!$llamada_outgoing && ereg("OUT$extension\-[(.+)|\-]*([[:digit:]]+)\-([[:digit:]]+)\.[wav|WAV|gsm]",$archivo,$regs)){
                 //formar la fecha y la hora
                 $fecha=substr($regs[1], 0, 4).'-'.substr($regs[1], 4, 2).'-'.substr($regs[1], 6, 2);
                 $hora=substr($regs[2], 0, 2).':'.substr($regs[2], 2, 2).':'.substr($regs[2], 4, 2);
                 $calldate="$fecha $hora";
                 //busco por fecha y extension destino
                 //ya tengo el archivo, busco el correspondiente en el registro de llamadas - con el timestamp y la extension
                 $llamada=obtenerCDROutgoing($pDBCDR,$extension, $calldate);
                 $llamada['archivo'] = $archivo;
                 $llamada['type'] = "auto - outgoing";
                 $llamadas[strtotime($llamada['calldate'])]=$llamada;
             }
             // El caso para cuando a la extension se le configuró sus records incoming or outgoing a always 
             if (ereg("[[:digit:]]+\-[[:digit:]]+\-([[:digit:]]+.[[:digit:]]+).[wav|WAV|gsm]",$archivo,$regs)){
            	 $unique_id=$regs[1]; 
            	 $llamada=obtenerCDR_with_uniqueid($pDBCDR,$unique_id);
            	 $llamada['archivo'] = $archivo;
            	 $llamada['type'] = "always";
                 if($extension==$llamada['src'] || $extension==$llamada['dst'] || $extension=="[[:digit:]]+") //se se cumple esto es porque es el usuario solo puede ver sus llamadas y la otra es porque es administrador
                    $llamadas[strtotime($llamada['calldate'])]=$llamada;
             }
        }

        if($tmpExtension=="" || is_null($tmpExtension))//validacion solo para usuarios del grupo administrator
            $smarty->assign("mb_message", "<b>".$arrLang["You don't have extension number associated with user"]."</b>");
        rsort($llamadas);
        foreach ($llamadas as $llamada){ 
            $fecha = date("Y-m-d",strtotime($llamada['calldate']));
            $hora = date("H:i:s",strtotime($llamada['calldate']));

            if (strtotime("$fecha $hora")<=strtotime($date_end) && strtotime("$fecha $hora")>=strtotime($date_start)){
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
        }
    
        $total=count($arrData);
    // LISTADO
        
        $limit = 15;
        $offset = 0;
    
        // Si se quiere avanzar a la sgte. pagina
        if($_GET['nav']=="end") {
    
            // Mejorar el sgte. bloque.
            if(($total%$limit)==0) {
                $offset = $total - $limit;
            } else {
                $offset = $total - $total%$limit;
            }
        }
    
        // Si se quiere avanzar a la sgte. pagina
        if($_GET['nav']=="next") {
            $offset = $_GET['start'] + $limit - 1;
        }
    
        // Si se quiere retroceder
        if($_GET['nav']=="previous") {
            $offset = $_GET['start'] - $limit - 1;
        }
    
        // Construyo el URL base
        if(is_array($arrFilterExtraVars) and count($arrFilterExtraVars)>0) {
            $url = construirURL($arrFilterExtraVars, array("nav", "start")); 
        } else {
            $url = construirURL(array(), array("nav", "start")); 
        }
        $smarty->assign("url", $url);
    
        $inicio = ($total==0) ? 0 : $offset + 1;
        $fin = ($offset+$limit)<=$total ? $offset+$limit : $total;
        $leng=$fin-$inicio;
        //muestro los registros correspondientes al offset
        $arrVoiceData=array_slice($arrData,$inicio-1,$leng+1);
    } //fin if (!is_null(extension))
    else {
        $smarty->assign("mb_message", "<b>".$arrLang["You don't have extension number associated with user"]."</b>");
    }
    $arrGrid = array("title"    => $arrLang["Monitorig List"],
                     "icon"     => "images/record.png",
                     "width"    => "99%",
                     "start"    => $inicio,
                     "end"      => $fin,
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
    $contenidoModulo  .= $oGrid->fetchGrid($arrGrid, $arrVoiceData,$arrLang);
    $contenidoModulo .= "</form>";
    return $contenidoModulo;
}

function obtenerCDROnDemand($db,$extension, $start_time)
{
    $arr_result=array();
    $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE (src='$extension' OR dst='$extension') AND $start_time BETWEEN UNIX_TIMESTAMP(calldate) AND (UNIX_TIMESTAMP(calldate)+duration)";

    $arr_result=$db->getFirstRowQuery($query,TRUE);          
    if (is_array($arr_result) && count($arr_result)>0) {
    }
    return $arr_result;
}

function obtenerCDRIncoming($db,$extension, $calldate)
{
    $arr_result=array();
    $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE dst='$extension' AND calldate='$calldate'";


    $arr_result=$db->getFirstRowQuery($query,TRUE);          
    if (is_array($arr_result) && count($arr_result)>0) {
    }
    return $arr_result;
}

function obtenerCDROutgoing($db,$extension, $calldate)
{
    $arr_result=array();
    $query   = "SELECT calldate, src, dst, channel, dstchannel, disposition, uniqueid, duration, billsec, accountcode FROM cdr ";
    $query .= "WHERE src='$extension' AND calldate='$calldate'";


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
