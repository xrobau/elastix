<?
require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoConfig.class.php";

function _moduleContent(&$smarty, $module_name)
{
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


    $formCampos= array();

    $oForm = new paloForm($smarty, $formCampos);
    $smarty->assign("asterisk", "Asterisk CLI");
    $smarty->assign("command", $arrLang["Command"]);
    $smarty->assign("txtCommand" , htmlspecialchars($txtCommand));
    $smarty->assign("execute", $arrLang["Execute"]);

    $txtCommand = isset($_POST['txtCommand'])?$_POST['txtCommand']:'';
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