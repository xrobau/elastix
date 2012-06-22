#!/usr/bin/php -q
<?php
include_once("/var/www/html/libs/misc.lib.php");
$arrSysInfo = obtener_info_de_sistema();
//print_r($arrSysInfo);
$mem_usage  = ($arrSysInfo['MemTotal'] - $arrSysInfo['MemFree'] - $arrSysInfo['MemBuffers'] - $arrSysInfo['Cached'])/$arrSysInfo['MemTotal'];
$mem_usage = number_format($mem_usage*100);
$mem_total = number_format($arrSysInfo['MemTotal']/1024);
echo "Mem Usage: $mem_usage%\n" .
     "Mem Total: $mem_total MB";
?>
