<?php
include_once("../../../libs/misc.lib.php");
include_once("../../../libs/paloSantoDB.class.php");
include_once("../../../libs/paloSantoConfig.class.php");
include_once("paloSantoExtensionsBatch.class.php");

load_language('../../../');

download_extensions();

function download_extensions()
{
    global $arrLang;
    global $arrConf;

    $pDB = new paloDB($arrConf["cadena_dsn"]);
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP  = $pConfig->leer_configuracion(false);

    $dsnAsterisk = $arrAMP['AMPDBENGINE']['valor']."://".
                   $arrAMP['AMPDBUSER']['valor']. ":".
                   $arrAMP['AMPDBPASS']['valor']. "@".
                   $arrAMP['AMPDBHOST']['valor']. "/asterisk";
    $pDB = new paloDB($dsnAsterisk);
    if(!empty($pDB->errMsg)) {
        echo $arrLang["Error when connecting to database"]."\n".$pDB->errMsg;
    }

    header("Cache-Control: private");
    header("Pragma: cache");
    header('Content-Type: text/csv; charset=iso-8859-1; header=present');
    header("Content-disposition: attachment; filename=extensions.csv");
    echo backup_extensions($pDB);
}

function backup_extensions($pDB)
{
    global $arrLang;
    $csv = "";
    $pLoadExtension = new paloSantoLoadExtension($pDB);
    $arrResult = $pLoadExtension->queryExtensions();

    if(!$arrResult)
        return $arrLang["There aren't extensions"];
    else{
        //cabecera
        $csv .= "\"Display Name\",\"User Extension\",\"Direct DID\",\"Outbound CID\",\"Call Waiting\",".
                "\"Secret\",\"Voicemail Status\",\"Voicemail Password\",\"VM Email Address\",".
                "\"VM Pager Email Address\",\"VM Options\",\"VM Email Attachment\",".
                "\"VM Play CID\",\"VM Play Envelope\",\"VM Delete Vmail\",\"Context\"\n";
        foreach($arrResult as $key => $extension)
        {
            $csv .= "\"{$extension['name']}\",\"{$extension['extension']}\",\"{$extension['directdid']}\",\"{$extension['outboundcid']}\",".
                    "\"{$extension['callwaiting']}\",\"{$extension['secret']}\",\"{$extension['voicemail']}\",".
                    "\"{$extension['vm_secret']}\",\"{$extension['email_address']}\",\"{$extension['pager_email_address']}\",".
                    "\"{$extension['vm_options']}\",\"{$extension['email_attachment']}\",\"{$extension['play_cid']}\",".
                    "\"{$extension['play_envelope']}\",\"{$extension['delete_vmail']}\",\"{$extension['context']}\"".
                    "\n";
        }
    }
    return $csv;
}
?>