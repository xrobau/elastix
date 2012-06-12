<?php
include_once("../../../libs/misc.lib.php");
include_once("../../../libs/paloSantoDB.class.php");
require_once("../../../libs/smarty/libs/Smarty.class.php");
include_once("../../../libs/paloSantoConfig.class.php");
include_once "paloSantoExtensionsBatch.class.php";

load_language('../../../');

download_extensions();

function download_extensions()
{
    global $arrLang;
    global $arrConf;

    $smarty = getSmarty();
    $pDB = new paloDB($arrConf["cadena_dsn"]);
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP  = $pConfig->leer_configuracion(false);

    $dsnAsterisk = $arrAMP['AMPDBENGINE']['valor']."://".
                   $arrAMP['AMPDBUSER']['valor']. ":".
                   $arrAMP['AMPDBPASS']['valor']. "@".
                   $arrAMP['AMPDBHOST']['valor']. "/asterisk";
    $pDB = new paloDB($dsnAsterisk);
    if(!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", $arrLang["Error when connecting to database"]."<br/>".$pDB->errMsg);
    }

    header("Cache-Control: private");
    header("Pragma: cache");
    header('Content-Type: text/csv; charset=iso-8859-1; header=present');
    header("Content-disposition: attachment; filename=extensions.csv");
    echo backup_extensions($pDB, $smarty);
}

function backup_extensions($pDB, $smarty)
{
    $Messages = "";
    $csv = "";
    $pLoadExtension = new paloSantoLoadExtension($pDB);
    $arrResult = $pLoadExtension->queryExtensions();

    if(!$arrResult)
    {
        $Messages .= $arrLang["There aren't extensions"].". ".$pLoadExtension->errMsg."<br />";
    }else{
        //cabecera
        $csv .= "\"Display Name\",\"User Extension\",\"Direct DID\",\"Call Waiting\",".
                "\"Secret\",\"Voicemail Status\",\"Voicemail Password\",\"VM Email Address\",".
                "\"VM Pager Email Address\",\"VM Options\",\"VM Email Attachment\",".
                "\"VM Play CID\",\"VM Play Envelope\",\"VM Delete Vmail\"\n";
        foreach($arrResult as $key => $extension)
        {
            $csv .= "\"{$extension['name']}\",\"{$extension['extension']}\",\"{$extension['directdid']}\",".
                    "\"{$extension['callwaiting']}\",\"{$extension['secret']}\",\"{$extension['voicemail']}\",".
                    "\"{$extension['vm_secret']}\",\"{$extension['email_address']}\",\"{$extension['pager_email_address']}\",".
                    "\"{$extension['vm_options']}\",\"{$extension['email_attachment']}\",\"{$extension['play_cid']}\",".
                    "\"{$extension['play_envelope']}\",\"{$extension['delete_vmail']}\"".
                    "\n";
        }
    }
    $smarty->assign("mb_message", $Messages);
    return $csv;
}

function getSmarty() {
    global $arrConf;
    $smarty = new Smarty();
    $smarty->template_dir = "themes/".$arrConf['mainTheme']."/";
    $smarty->compile_dir =  "var/templates_c/";
    $smarty->config_dir =   "configs/";
    $smarty->cache_dir =    "var/cache/";
    return $smarty;
}

?>