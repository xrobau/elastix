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
    $inicio= $fin = $total = 0;
    $extension = $pACL->getUserExtension($_SESSION['elastix_user']);
    $esAdministrador = $pACL->isUserAdministratorGroup($_SESSION['elastix_user']);
    if($esAdministrador)
        $extension = "[[:digit:]]+";

    $smarty->assign("menu","voicemail");
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
        borrarVoicemails($extension);
        if($oFilterForm->validateForm($_POST)) {
                // Exito, puedo procesar los datos ahora.
                $date_start = translateDate($_POST['date_start']) . " 00:00:00"; 
                $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
                $arrFilterExtraVars = array("date_start" => $_POST['date_start'], "date_end" => $_POST['date_end']
                                           );
        }
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
    }

    $end = 0;
    //si tiene extension consulto sino, muestro un mensaje de que no tiene asociada extension
    $archivos=array();
    if (!is_null($extension)){
        $path = "/var/spool/asterisk/voicemail/default";
        $folder = "INBOX";

        if($esAdministrador)
        {
            if ($handle = opendir($path)) {
                while (false !== ($dir = readdir($handle))) {
                    if ($dir != "." && $dir != ".." && ereg($extension, $dir, $regs) && is_dir($path."/".$dir)) {
                        $directorios[] = $dir;
                    }
                }
            }
        }else $directorios[] = $extension;

        $arrData = array();
        foreach($directorios as $directorio)
        {
            $voicemailPath = "$path/$directorio/$folder";
            if (file_exists($voicemailPath)) {
                if ($handle = opendir($voicemailPath)) {
                    $bExito=true;
                    while (false !== ($file = readdir($handle))) {
                        //no tomar en cuenta . y ..
                        //buscar los archivos de texto (txt) que son los que contienen los datos de las llamadas
                        if ($file!="." && $file!=".." && ereg("(.+)\.[txt|TXT]",$file,$regs))
                        {
                            //leer la info del archivo
                            $pConfig = new paloConfig($voicemailPath, $file, "=", "[[:space:]]*=[[:space:]]*");
                            $arrVoiceMailDes=array();
                            $arrVoiceMailDes = $pConfig->leer_configuracion(false);

                            //verifico que tenga datos
                            if (is_array($arrVoiceMailDes) && count($arrVoiceMailDes)>0 && isset($arrVoiceMailDes['origtime']['valor'])){
                                //uso las fechas del filtro
                                //si la fecha de llamada esta dentro del rango, la muestro
                                $fecha = date("Y-m-d",$arrVoiceMailDes['origtime']['valor']);
                                $hora = date("H:i:s",$arrVoiceMailDes['origtime']['valor']);

                                if (strtotime("$fecha $hora")<=strtotime($date_end) && strtotime("$fecha $hora")>=strtotime($date_start)){
                                    $arrTmp[0] = "<input type='checkbox' name='".utf8_encode("voc-".$file)."' />";
                                    $arrTmp[1] = $fecha;
                                    $arrTmp[2] = $hora;
                                    $arrTmp[3] = $arrVoiceMailDes['callerid']['valor'];
                                    $arrTmp[4] = $arrVoiceMailDes['origmailbox']['valor'];
                                    $arrTmp[5] = $arrVoiceMailDes['duration']['valor'].' sec.';
                                    $pathRecordFile="$voicemailPath/".$regs[1].'.wav';
                                    $recordingLink = "<a href='#' onClick=\"javascript:popUp('includes/popup.php?action=display_record&record_file=" . base64_encode($pathRecordFile) ."',350,100); return false;\">Listen</a>&nbsp;";
                                    $recordingLink .= "<a href='includes/audio.php?recording=".base64_encode($pathRecordFile)."'>Download</a>";
                                    $arrTmp[6] = $recordingLink;
                                    $arrData[] = $arrTmp;
                                }
                            }
                        }
                    }
                    closedir($handle);
                }
            } else {
                // No vale la ruta
            }
        }
        /*
        function sort_voicemails_hora_desc($a, $b) { return ($a[2] == $b[2]) ? 0 : (($a[2] < $b[2]) ? 1 : -1); }
        function sort_voicemails_fecha_desc($a, $b) { return ($a[1] == $b[1]) ? 0 : (($a[1] < $b[1]) ? 1 : -1); }
        usort($arrData, 'sort_voicemails_hora_desc');
        usort($arrData, 'sort_voicemails_fecha_desc');
        */
        $fechas = array();
        $horas  = array();
        foreach ($arrData as $llave => $fila) {
            $fechas[$llave]  = $fila[1];
            $horas[$llave]   = $fila[2];
        }
        array_multisort($fechas,SORT_DESC,$horas,SORT_DESC,$arrData);

        //Paginacion
        $limit  = 15;
        $total  = count($arrData);

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

        $arrVoiceData=array_slice($arrData, $offset, $limit);
    } //fin if (!is_null(extension))
    else {
        $smarty->assign("mb_message", "<b>".$arrLang["You don't have extension number associated with user"]."</b>");
    }
    $arrGrid = array("title"    => $arrLang["Voicemail List"],
                     "icon"     => "images/record.png",
                     "width"    => "99%",
                     "start"    => ($total==0) ? 0 : $offset + 1,
                     "end"      => $end,
                     "total"    => $total,
                     "columns"  => array(0 => array("name"      => "<input type='submit' onClick=\"return confirmSubmit('{$arrLang["Are you sure you wish to delete voicemails?"]}');\" name='submit_eliminar' value='{$arrLang["Delete"]}' class='button' />",
                                                    "property1" => ""),
                                         1 => array("name"      => $arrLang["Date"],
                                                    "property1" => ""),
                                         2 => array("name"      => $arrLang["Time"],
                                                    "property1" => ""),
                                         3 => array("name"      => $arrLang["CallerID"],
                                                    "property1" => ""),
                                         4 => array("name"      => $arrLang["Extension"],
                                                    "property1" => ""),
                                         5 => array("name"      => $arrLang["Duration"],
                                                    "property1" => ""),
                                         6 => array("name"      => $arrLang["Message"],
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

function borrarVoicemails($extension)
{
    $path = "/var/spool/asterisk/voicemail/default";
    $folder = "INBOX";
    $voicemailPath = "$path/$extension/$folder";

    if(is_array($_POST) && count($_POST) > 0){
        foreach($_POST as $name => $on){
            if(substr($name,0,4)=='voc-'){
                $file = substr($name,4);
                $pos = strrpos($file, '_');
                $file = substr($file, 0, strrpos($file, '_'));
                unlink("$voicemailPath/$file.txt");
                unlink("$voicemailPath/$file.wav");
                unlink("$voicemailPath/$file.WAV");
            }
        }
    }
}
?>