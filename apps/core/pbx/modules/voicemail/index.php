<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.com                                               |
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

    include_once "lib/paloSantoVoiceMail.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //segun el usuario que esta logoneado consulto si tiene asignada extension para buscar los voicemails
    $pDB = new paloDB($arrConf['elastix_dsn']['acl']);


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
    $extension = $pACL->getUserExtension($_SESSION['elastix_user']); $ext = $extension;
    $esAdministrador = $pACL->isUserAdministratorGroup($_SESSION['elastix_user']);
    $bandCustom = true;
    if (is_null($ext) || $ext=="") {
        $bandCustom = false;
        if (!$esAdministrador) {
            $smarty->assign("mb_message", "<b>"._tr("contact_admin")."</b>");
            return "";
        }
    }
    if($esAdministrador)
        $extension = "[[:digit:]]+";

    $smarty->assign("menu","voicemail");
    $smarty->assign("Filter",_tr('Show'));
    //formulario para el filtro
    $arrFormElements = createFieldFormVoiceList();
    $oFilterForm = new paloForm($smarty, $arrFormElements);
        // Por omision las fechas toman el sgte. valor (la fecha de hoy)
    $date_start = date("Y-m-d")." 00:00:00";
    $date_end   = date("Y-m-d")." 23:59:59";
    $dateStartFilter = getParameter('date_start');
    $dateEndFilter = getParameter('date_end');
    $report = false;


    if( getParameter('filter') ){
        if($oFilterForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $date_start = translateDate($dateStartFilter)." 00:00:00";
            $date_end   = translateDate($dateEndFilter)." 23:59:59";
            $arrFilterExtraVars = array("date_start" => $dateStartFilter, "date_end" => $dateEndFilter);
        } else {
            // Error
            $smarty->assign("mb_title", _tr("Validation Error"));
            $arrErrores=$oFilterForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
        }
        if($dateStartFilter==""){
            $dateStartFilter = " ";
        }
        if($dateEndFilter==""){
            $dateEndFilter= " ";
        }
        //se añade control a los filtros
        $report = true;
        $arrDate = array('date_start'=>$dateStartFilter,'date_end'=>$dateEndFilter);
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
    } else if (isset($dateStartFilter) AND isset($dateEndFilter)) {
        $report = true;
        $date_start = translateDate($dateStartFilter) . " 00:00:00";
        $date_end   = translateDate($dateEndFilter) . " 23:59:59";

        $arrDate = array('date_start'=>$dateStartFilter,'date_end'=>$dateEndFilter);
        $arrFilterExtraVars = array("date_start" => $dateStartFilter, "date_end" => $dateEndFilter);
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_GET);
    } else {
        $report = true;
        //se añade control a los filtros
        $arrDate = array('date_start'=>date("d M Y"),'date_end'=>date("d M Y"));
        $htmlFilter = $contenidoModulo=$oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "",
        array('date_start' => date("d M Y"), 'date_end' => date("d M Y")));
    }

    $oGrid  = new paloSantoGrid($smarty);
    if ($report){
        $oGrid->addFilterControl(_tr("Filter applied ")._tr("Start Date")." = ".$arrDate['date_start'].", "._tr("End Date")." = ".$arrDate['date_end'], $arrDate, array('date_start' => date("d M Y"),'date_end' => date("d M Y")),true);
    }

    if( getParameter('submit_eliminar') ) {
        borrarVoicemails($pACL);
        if($oFilterForm->validateForm($_POST)) {
            // Exito, puedo procesar los datos ahora.
            $date_start = translateDate($_POST['date_start']) . " 00:00:00";
            $date_end   = translateDate($_POST['date_end']) . " 23:59:59";
            $arrFilterExtraVars = array("date_start" => $_POST['date_start'], "date_end" => $_POST['date_end']);
        }
        $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $_POST);
    }

    if( getParameter('config') ){
        if(!(is_null($ext) || $ext==""))
            return form_config($smarty, $module_name, $local_templates_dir, $ext);
    }

    if( getParameter('save') ){
        if( !save_config($smarty, $module_name, $local_templates_dir, $ext) )
            return form_config($smarty, $module_name, $local_templates_dir, $ext);
    }

    if( getParameter('action') == "display_record"){

        $file = getParameter("name");
        $ext  = getParameter("ext");
        $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
        $extension = $pACL->getUserExtension($user);
        $esAdministrador = $pACL->isUserAdministratorGroup($user);
        $path = "/var/spool/asterisk/voicemail/default";
        $voicemailPath = "$path/$ext/INBOX/".base64_decode($file);
        $tmpfile = basename($voicemailPath);
        $filetmp = "$path/$ext/INBOX/$tmpfile";
        if(!is_file($filetmp)){
            die("<b>404 "._tr("no_file")."</b>");
        }
        if(!$esAdministrador){
            if($extension != $ext){
                 die("<b>404 "._tr("no_file")."</b>");
            }
            $voicemailPath = "$path/$extension/INBOX/".base64_decode($file);
        }

        if (isset($file) && preg_match("/^[[:alpha:]]+[[:digit:]]+\.(wav|WAV|Wav|mp3|gsm)$/",base64_decode($file))) {
            if (!is_file($voicemailPath)) {
                die("<b>404 "._tr("no_file")."</b>");
            }
            $sContenido="";

            $name = basename($voicemailPath);
            $format=substr(strtolower($name), -3);
            // This will set the Content-Type to the appropriate setting for the file
            $ctype ='';
            switch( $format ) {

                case "mp3": $ctype="audio/mpeg"; break;
                case "wav": $ctype='audio/wav'; break;
                case "gsm": $ctype="audio/x-gsm"; break;
                // not downloadable
                default: die("<b>404 "._tr("no_file")."</b>"); break ;
            }

            if($sContenido == "")
                $session_id = session_id();

            $audiourl = construirURL(array(
                'menu'           =>  $module_name,
                'action'         =>  'download',
                'ext'            =>  $ext,
                'name'           =>  $file,
                'rawmode'        =>  'yes',
                'elastixSession' =>  $session_id,
            ));
            $sContenido=<<<contenido
<!DOCTYPE html>
<html>
<head><title>Elastix</title></head>
<body>
    <audio src="$audiourl" controls autoplay>
        <embed src="$audiourl" type="$ctype" width="300" height="20" autoplay="true" loop="false" />
    </audio>
    <br/>
</body>
</html>
contenido;
            echo $sContenido;
        }else{
            die("<b>404 "._tr("no_file")."</b>");
        }
        return;
    }

    if( getParameter('action') == "download"){
        $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
        $extension = $pACL->getUserExtension($user);
        $esAdministrador = $pACL->isUserAdministratorGroup($user);
        $record = getParameter("name");
        $ext  = getParameter("ext");
        if (!preg_match("/^[[:digit:]]+$/", $ext)) {
            Header("HTTP/1.1 404 Not Found");
            die("<b>404 "._tr("no_file")."</b>");
        }
        $record = base64_decode($record);
        $path = "/var/spool/asterisk/voicemail/default";
        $voicemailPath = "$path/$ext/INBOX/".$record;//"$path/$record";
        $tmpfile = basename($voicemailPath);
        $filetmp = "$path/$ext/INBOX/$tmpfile";
        if(!is_file($filetmp)){
            die("<b>404 "._tr("no_file")."</b>");
        }
        if(!$esAdministrador){
            if($extension != $ext){
                Header("HTTP/1.1 404 Not Found");
                 die("<b>404 "._tr("no_extension")."</b>");
            }
            $voicemailPath = "$path/$extension/INBOX/".$record;
        }
        if (isset($record) && preg_match("/^[[:alpha:]]+[[:digit:]]+\.(wav|WAV|Wav|mp3|gsm)$/",$record)) {
        // See if the file exists

            if (!is_file($voicemailPath)) {
                Header("HTTP/1.1 404 Not Found");
                die("<b>404 "._tr("no_file")."</b>");
            }
        // Gather relevent info about file
            $size = filesize($voicemailPath);
            $name = basename($voicemailPath);

        //$extension = strtolower(substr(strrchr($name,"."),1));
            $extension=substr(strtolower($name), -3);

        // This will set the Content-Type to the appropriate setting for the file
            $ctype ='';
            switch( $extension ) {

                case "mp3": $ctype="audio/mpeg"; break;
                case "wav": $ctype='audio/wav'; break;
                case "gsm": $ctype="audio/x-gsm"; break;
                // not downloadable
                default: die("<b>404 "._tr("no_file")."</b>"); break ;
            }

        // need to check if file is mislabeled or a liar.
            $fp=fopen($voicemailPath, "rb");
            if ($size && $ctype && $fp) {
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: public");
                header("Content-Description: wav file");
                header("Content-Type: " . $ctype);
                header("Content-Disposition: attachment; filename=" . $name);
                header("Content-Transfer-Encoding: binary");
                header("Content-length: " . $size);
                fpassthru($fp);
            }
        }else{
            Header("HTTP/1.1 404 Not Found");
            die("<b>404 "._tr("no_file")."</b>");
        }
        return;
    }

    $end = 0;

    $url = array('menu' => $module_name);

    //si tiene extension consulto sino, muestro un mensaje de que no tiene asociada extension
    $archivos=array();
    if (!(is_null($ext) || $ext=="") || $esAdministrador) {
        if(is_null($ext) || $ext=="")
            $smarty->assign("mb_message", "<b>"._tr("no_extension_assigned")."</b>");

        $param = array(
            'date_start'    => $date_start,
            'date_end'      => $date_end,
        );
        if (!$esAdministrador) $param['extension'] = $extension;

        $paloVoice = new paloSantoVoiceMail();
        $rs = $paloVoice->listVoicemail($param);

        $limit = 15;
        $oGrid->setLimit($limit);
        $oGrid->setTotal(count($rs));
        $offset = $oGrid->calculateOffset();
        $arrData = array_slice($rs, $offset, $limit);

        $arrVoiceData = array();
        foreach ($arrData as $t) {
            $pathRecordFile = base64_encode($t['recordingfile']);
            $arrVoiceData[] = array(
                '<input type="checkbox" name="voicemails[]" value="'.htmlentities($t['mailbox'].','.basename($t['file'], '.txt'), ENT_COMPAT, 'UTF-8').'" />',
                date('Y-m-d', $t['origtime']),
                date('H:i:s', $t['origtime']),
                htmlentities($t['callerid'], ENT_COMPAT, 'UTF-8'),
                $t['extension'],
                $t['duration'].' sec.',
                "<a href='#' onClick=\"javascript:popUp('index.php?menu=$module_name&action=display_record&ext={$t['mailbox']}&name=$pathRecordFile&rawmode=yes',350,100); return false;\">"._tr('Listen')."</a>&nbsp;".
                    "<a href='?menu=$module_name&action=download&ext={$t['mailbox']}&name=$pathRecordFile&rawmode=yes'>"._tr('Download')."</a>",
            );
        }
        $oGrid->setData($arrVoiceData);

        // Construyo el URL base
        if(isset($arrFilterExtraVars) && is_array($arrFilterExtraVars) and count($arrFilterExtraVars)>0) {
            $url = array_merge($url, $arrFilterExtraVars);
        }

        //fin if (!is_null(extension))
    } else {
        $smarty->assign("mb_message", "<b>"._tr("contact_admin")."</b>");
    }

    $oGrid->setTitle(_tr("Voicemail List"));
    $oGrid->setURL($url);
    $oGrid->setIcon("/modules/$module_name/images/pbx_voicemail.png");
    $oGrid->setColumns(array('', _tr('Date'), _tr('Time'), _tr('CallerID'),
        _tr('Extension'), _tr('Duration'), _tr('Message')));

    if($bandCustom == true)
        $oGrid->customAction("config",_tr("Configuration"));
    $oGrid->deleteList(_tr("Are you sure you wish to delete voicemails?"),"submit_eliminar",_tr("Delete"));
    $oGrid->showFilter($htmlFilter);
    $contenidoModulo  = $oGrid->fetchGrid();
    if (strpos($contenidoModulo, '<form') === FALSE)
    $contenidoModulo  = "<form style='margin-bottom:0;' method='POST' action='?menu=$module_name'>$contenidoModulo</form>";
    return $contenidoModulo;
}

function form_config($smarty, $module_name, $local_templates_dir, $ext)
{
    $arrForm = createFieldFormConfig();
    $oForm = new paloForm($smarty, $arrForm);

    $smarty->assign("REQUIRED_FIELD", _tr("Required Field"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("icon","images/list.png");

    $paloVoice = new paloSantoVoiceMail();
    $arrDat = $paloVoice->loadConfiguration($ext);

    if( !isset($_POST['save']) ){
        if (is_null($arrDat)) {
            $_POST['status'] = "Disable";
            $_POST['email_attach']  = "No";
            $_POST['play_cid']      = "No";
            $_POST['play_envelope'] = "No";
            $_POST['delete_vmail']  = "No";
        } else {
            $_POST['status'] = "Enable";
            $_POST['password']        = $arrDat['voicemail_password'];
            $_POST['password_confir'] = $arrDat['voicemail_password'];
            $_POST['email']           = $arrDat['user_email_address'];
            $_POST['pager_email']     = $arrDat['pager_email_address'];
            $_POST['email_attach']    = (isset($arrDat['user_options']['attach']) && $arrDat['user_options']['attach'] == 'yes')?"Yes":"No";
            $_POST['play_cid']        = (isset($arrDat['user_options']['saycid']) && $arrDat['user_options']['saycid'] == 'yes')?"Yes":"No";
            $_POST['play_envelope']   = (isset($arrDat['user_options']['envelope']) && $arrDat['user_options']['envelope'] == 'yes')?"Yes":"No";
            $_POST['delete_vmail']    = (isset($arrDat['user_options']['delete']) && $arrDat['user_options']['delete'] == 'yes')?"Yes":"No";
        }
    }

    $htmlForm = $oForm->fetchForm("$local_templates_dir/configuration.tpl",_tr("Configuration"), $_POST);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function save_config($smarty, $module_name, $local_templates_dir, $ext)
{
    $paloVoice = new paloSantoVoiceMail();
    $arrDat = $paloVoice->loadConfiguration($ext);

    $arrForm = createFieldFormConfig();
    $oForm = new paloForm($smarty, $arrForm);

    if(!$oForm->validateForm($_POST) || $_POST['password'] != $_POST['password_confir']){
        $smarty->assign("mb_title", "Validation Error");
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>'The following fields contain errors':</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";
        }

        if($_POST['password'] != $_POST['password_confir']) $strErrorMsg .= "Confirm Password";

        $smarty->assign("mb_message", $strErrorMsg);
        return false;
    }

    $bandera = $paloVoice->writeFileVoiceMail($ext, ($_POST['status'] == "Enable")
        ? array(
            'voicemail_password'    =>  $_POST['password'],
            // user_name se conserva
            'user_email_address'    =>  $_POST['email'],
            'pager_email_address'   =>  $_POST['pager_email'],
            'user_options'          =>  array(
                // toda bandera distinta de las indicadas abajo se conserva
                'attach'    =>  ($_POST['email_attach'] == 'Yes') ? 'yes' : 'no',
                'saycid'    =>  ($_POST['play_cid'] == 'Yes')     ? 'yes' : 'no',
                'envelope'  =>  ($_POST['play_envelope'] == 'Yes')? 'yes' : 'no',
                'delete'    =>  ($_POST['delete_vmail'] == 'Yes') ? 'yes' : 'no',
            ),
        )
        : NULL);

    if( $bandera == true )
        return true;
    else{
        $smarty->assign("mb_title", "Error");
        $smarty->assign("mb_message", $paloVoice->errMsg);
        return false;
    }
}

function borrarVoicemails($pACL)
{
    $user = isset($_SESSION['elastix_user']) ? $_SESSION['elastix_user'] : "";
    $extension = $pACL->getUserExtension($user);
    $esAdministrador = $pACL->isUserAdministratorGroup($user);

    $listaArchivos = array();
    if (is_array($_POST)) foreach ($_POST['voicemails'] as $name) {
        // El formato esperado de clave es 1064,msg0001
        $regs = NULL;
        if (preg_match('/^(\d+),(\w+)$/', $name, $regs)) {
            if ($esAdministrador || $extension == $regs[1]) {
                $voicemailPath = "/var/spool/asterisk/voicemail/default/{$regs[1]}/INBOX";
                $listaArchivos[] = "$voicemailPath/{$regs[2]}.txt";
                $listaArchivos[] = "$voicemailPath/{$regs[2]}.wav";
                $listaArchivos[] = "$voicemailPath/{$regs[2]}.WAV";
            } else {
                // Intento de borrar el voicemail de otro usuario
                return;
            }
        }
    }
    array_map('unlink', $listaArchivos);
}

function createFieldFormVoiceList()
{
    $arrayFields = array(
        "date_start" => array("LABEL"                  => _tr("Start Date"),
                              "REQUIRED"               => "yes",
                              "INPUT_TYPE"             => "DATE",
                              "INPUT_EXTRA_PARAM"      => "",
                              "VALIDATION_TYPE"        => "ereg",
                              "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
        "date_end"   => array("LABEL"                  => _tr("End Date"),
                              "REQUIRED"               => "yes",
                              "INPUT_TYPE"             => "DATE",
                              "INPUT_EXTRA_PARAM"      => "",
                              "VALIDATION_TYPE"        => "ereg",
                              "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
       );
    return $arrayFields;
}

function createFieldFormConfig()
{
    $arrFields = array(
        "email"             => array("LABEL"                  => _tr('Email'),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "TEXT",
                                     "INPUT_EXTRA_PARAM"      => "",
                                     "VALIDATION_TYPE"        => "email",
                                     "VALIDATION_EXTRA_PARAM" => ""),
        "pager_email"       => array("LABEL"                  => _tr('Pager Email Address'),
                                     "REQUIRED"               => "no",
                                     "INPUT_TYPE"             => "TEXT",
                                     "INPUT_EXTRA_PARAM"      => "",
                                     "VALIDATION_TYPE"        => "email",
                                     "VALIDATION_EXTRA_PARAM" => ""),
        "status"            => array("LABEL"                  => _tr('Status'),
                                     "REQUIRED"               => "no",
                                     "INPUT_TYPE"             => "SELECT",
                                     "INPUT_EXTRA_PARAM"      => array("Enable"=>_tr("Enable"),"Disable"=>_tr("Disable")),
                                     "VALIDATION_TYPE"        => "text",
                                     "VALIDATION_EXTRA_PARAM" => ""),
        "password"          => array("LABEL"                  => _tr('Password'),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "PASSWORD",
                                     "INPUT_EXTRA_PARAM"      => "",
                                     "VALIDATION_TYPE"        => "ereg",
                                     "VALIDATION_EXTRA_PARAM" => "[[:digit:]]+"),
        "password_confir"   => array("LABEL"                  => _tr('Confirm Password'),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "PASSWORD",
                                     "INPUT_EXTRA_PARAM"      => "",
                                     "VALIDATION_TYPE"        => "ereg",
                                     "VALIDATION_EXTRA_PARAM" => "[[:digit:]]+"),
        "email_attach"      => array("LABEL"                  => _tr("Email Attachment"),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "RADIO",
                                     "INPUT_EXTRA_PARAM"      => array("Yes"=>_tr("Yes"),"No"=>_tr("No")),
                                     "VALIDATION_TYPE"        => "text",
                                     "VALIDATION_EXTRA_PARAM" => ""),
        "play_cid"          => array("LABEL"                  => _tr("Play CID"),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "RADIO",
                                     "INPUT_EXTRA_PARAM"      => array("Yes"=>_tr("Yes"),"No"=>_tr("No")),
                                     "VALIDATION_TYPE"        => "text",
                                     "VALIDATION_EXTRA_PARAM" => ""),
        "play_envelope"     => array("LABEL"                  => _tr("Play Envelope"),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "RADIO",
                                     "INPUT_EXTRA_PARAM"      => array("Yes"=>_tr("Yes"),"No"=>_tr("No")),
                                     "VALIDATION_TYPE"        => "text",
                                     "VALIDATION_EXTRA_PARAM" => ""),
        "delete_vmail"     => array("LABEL"                  => _tr("Delete Vmail"),
                                     "REQUIRED"               => "yes",
                                     "INPUT_TYPE"             => "RADIO",
                                     "INPUT_EXTRA_PARAM"      => array("Yes"=>_tr("Yes"),"No"=>_tr("No")),
                                     "VALIDATION_TYPE"        => "text",
                                     "VALIDATION_EXTRA_PARAM" => ""),
    );

    return $arrFields;
}
?>
