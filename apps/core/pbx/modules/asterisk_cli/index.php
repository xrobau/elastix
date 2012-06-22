<?php
require_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
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

    $formCampos= array();
    $txtCommand = isset($_POST['txtCommand'])?$_POST['txtCommand']:'';

    $oForm = new paloForm($smarty, $formCampos);
    $smarty->assign("asterisk", "Asterisk CLI");
    $smarty->assign("command", $arrLang["Command"]);
    $smarty->assign("txtCommand" , htmlspecialchars($txtCommand));
    $smarty->assign("execute", $arrLang["Execute"]);
    $smarty->assign("icon","modules/$module_name/images/pbx_tools_asterisk_cli.png");

    $result = "";
    if (!isBlank($txtCommand)) {
        $result=  "<pre>";
        putenv("TERM=vt100");
        putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");
        putenv("SCRIPT_FILENAME=" . strtok(stripslashes($txtCommand), " "));  /* PHP scripts */
        $badchars = array("'", "`", "\\", ";", "\""); // Strip off any nasty chars.
        $fixedcmd = str_replace($badchars, "", $txtCommand);
        $ph = popen(stripslashes("asterisk -nrx \"$fixedcmd\""), "r" );
        while ($line = fgets($ph))
            $result .= htmlspecialchars($line);
        pclose($ph);
        $result .= "</pre>";
    }
    if($result=="") $result="&nbsp;";
    $smarty->assign("RESPUESTA_SHELL", $result);
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/new.tpl", $arrLang["Asterisk-Cli"],$_POST);

    return $contenidoModulo;

}

////CODIGO AGREGADO DE PAGE.CLI.PHP
function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }


?>
