<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.1-4                                               |
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
  $Id: default.conf.php,v 1.1 2008-06-12 09:06:35 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoRecordings.class.php";
    global $arrConf;
    global $arrLang;

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConfig['templates_dir']))?$arrConfig['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn_agi_manager['password'] = $arrConfig['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrConfig['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = 'admin';

    $pDB = new paloDB("sqlite3:////var/www/db/address_book.db");

    $accion = getAction();

    $content = "";
    switch($accion)
    {
        case "record":
            $content = new_recording($smarty, $module_name, $local_templates_dir, $arrLang, $pDB, $dsn_agi_manager, $arrConf);
            break;
        case "save":
            $content = save_recording($smarty, $module_name, $local_templates_dir, $arrLang, $arrConf);
            break;
        default:
            $content = form_Recordings($smarty, $module_name, $local_templates_dir, $arrLang);
            break;
    }

    return $content;
}

function save_recording($smarty, $module_name, $local_templates_dir, $arrLang, $arrConf)
{
    $bExito = true;
    $pRecording = new paloSantoRecordings($pDB);
    $extension = $pRecording->Obtain_Extension_Current_User($arrConf);
    if(!$extension)
    {
        $smarty->assign("mb_title", $arrLang['ERROR'].":");
        $smarty->assign("mb_message", $arrLang["You don't have extension number associated with user"]);
        return form_Recordings($smarty, $module_name, $local_templates_dir, $arrLang);
    }

    $destiny_path = "/var/lib/asterisk/sounds/custom/$extension/";

    if(isset($_POST['option_record']) && $_POST['option_record']=='by_record')
    {
        $filename   = isset($_POST['filename'])?$_POST['filename']:'';
        $smarty->assign("filename", $filename);

        if($filename != "")
        {
            $path = "/tmp";
            $archivo = "";
            $file_ext = "";
            if ($handle = opendir($path)) {
                while (false !== ($dir = readdir($handle))) {
                    if (ereg("({$extension}-.*)\.([gsm|wav]*)$", $dir, $regs)) {
                        $archivo = $regs[1];
                        $file_ext = $regs[2];
                        break;
                    }
                }
            }

            $tmp_file = "$archivo.$file_ext";
            $filename .= ".$file_ext";

            if($filename != "" && $tmp_file != "" && $extension != "")
            {
                if(!file_exists($destiny_path))
                {
                    $comando="mkdir $destiny_path";
                    exec($comando, $output, $retval);
                    if ($retval!=0) $bExito = false;
                }
                if($bExito)
                {
                    $comando="mv /tmp/$tmp_file $destiny_path/$filename";
                    exec($comando, $output, $retval);
                    if ($retval!=0) $bExito = false;
                }
            }else $bExito = false;
        }else $bExito = false;

        if(!$bExito)
        {
            $smarty->assign("mb_title", $arrLang['ERROR'].":");
            $smarty->assign("mb_message", $arrLang["The recording couldn't be realized"]);
        }
    }else{
        if (isset($_FILES['file_record'])) {
            if($_FILES['file_record']['name']!=""){
                $smarty->assign("file_record_name", $_FILES['file_record']['name']);
                if(!file_exists($destiny_path))
                {
                    $comando="mkdir $destiny_path";
                    exec($comando, $output, $retval);
                    if ($retval!=0) $bExito = false;
                }
                if($bExito)
                {
                    $filename = $_FILES['file_record']['name'];
                    $tmp_name = $_FILES['file_record']['tmp_name'];
                    if (!move_uploaded_file($tmp_name, "$destiny_path/$filename"))
                    {
                        $smarty->assign("mb_title", $arrLang['ERROR'].":");
                        $smarty->assign("mb_message", $arrLang["Possible file upload attack. Filename"]);
                        $bExito = false;
                    }
                }else
                {
                    $smarty->assign("mb_title", $arrLang['ERROR'].":");
                    $smarty->assign("mb_message", $arrLang["Destiny directory couldn't be created"]);
                    $bExito = false;
                }
            }
            else{
                $smarty->assign("mb_title", $arrLang['ERROR'].":");
                $smarty->assign("mb_message", $arrLang["Error copying the file"]);
                $bExito = false;
            }
        }else{
            $smarty->assign("mb_title", $arrLang['ERROR'].":");
            $smarty->assign("mb_message", $arrLang["Error copying the file"]);
            $bExito = false;
        }
    }

    if($bExito) $smarty->assign("mb_message", $arrLang["The recording was saved"]);

    return form_Recordings($smarty, $module_name, $local_templates_dir, $arrLang);
}

function new_recording($smarty, $module_name, $local_templates_dir, $arrLang, $pDB, $dsn_agi_manager, $arrConf)
{
    $recording_name = isset($_POST['recording_name'])?$_POST['recording_name']:'';

    if($recording_name != '')
    {
        $pRecording = new paloSantoRecordings($pDB);
        $result = $pRecording->Obtain_Protocol_Current_User($arrConf);

        $number2call = '*77';
        if($result != FALSE)
        {
            $result = $pRecording->Call2Phone($dsn_agi_manager, $result['id'], $number2call, $result['dial'], $result['description']);
            if($result)
            {
                $smarty->assign("filename", $recording_name);
                $smarty->assign("mb_message", $arrLang["To continue: record a message then click on save"]);
            }
            else{
                $smarty->assign("mb_title", $arrLang['ERROR'].":");
                $smarty->assign("mb_message", $arrLang["The call couldn't be realized"]);
            }
        }else{
            $smarty->assign("mb_title", $arrLang['ERROR'].":");
            $smarty->assign("mb_message", $arrLang["You don't have extension number associated with user"]);
        }
    }
    else{
        $smarty->assign("mb_title", $arrLang["Validation Error"]);
        $smarty->assign("mb_message", $arrLang['Insert the Recording Name']);
    }

    return form_Recordings($smarty, $module_name, $local_templates_dir, $arrLang);
}

function form_Recordings($smarty, $module_name, $local_templates_dir, $arrLang)
{
    if(isset($_POST['option_record']) && $_POST['option_record']=='by_file')
        $smarty->assign("check_file", "checked");
    else
        $smarty->assign("check_record", "checked");

    $oForm = new paloForm($smarty,array());

    $smarty->assign("recording_name_Label", $arrLang["Record Name"]);
    $smarty->assign("record_Label", $arrLang["File Upload"]);

    $smarty->assign("Record", $arrLang["Record"]);
    $smarty->assign("SAVE", $arrLang["Save"]);
    $smarty->assign("TITLE", $arrLang["Recordings"]);
    $smarty->assign("IMG", "images/recording.png");
    $smarty->assign("module_name", $module_name);
    $smarty->assign("file_upload", $arrLang["File Upload"]);
    $smarty->assign("record", $arrLang["Record"]);

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", "", $_POST);

    $contenidoModulo = "<form enctype='multipart/form-data' method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
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
    if(getParameter("record"))
        return "record";
    else if(getParameter("save"))
        return "save";
    else
        return "report";
}
?>
