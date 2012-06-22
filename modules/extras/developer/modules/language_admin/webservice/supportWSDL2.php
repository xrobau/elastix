<?php
require_once("/var/www/html/libs/misc.lib.php");
require_once("/var/www/html/configs/default.conf.php");
require_once("$arrConf[basePath]/modules/language_admin/configs/default.conf.php");
require_once("$arrConf[basePath]/modules/language_admin/libs/paloSantoLanguageAdmin.class.php");

function sendTraducciones($module, $language, $arrayLangTrasl)
{
    global $arrConfModule;

    $pSeverCoverage = new paloSantoLanguageAdmin();
    
    if($pSeverCoverage->upload( $arrayLangTrasl, $module, $language))
        return true;
    else
        return false; 
}

// turn off the wsdl cache
ini_set("soap.wsdl_cache_enabled", "0");

$server = new SoapServer("supportWSDL2.wsdl");
$server->addFunction("sendTraducciones");
$server->handle();
?>
