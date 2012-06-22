#!/usr/bin/php -q
<?php
include_once("/var/www/html/libs/misc.lib.php");
$arrSysInfo = obtener_info_de_sistema();
//print_r($arrSysInfo);
$hd_total = $arrSysInfo['particiones'][0]['num_bloques_total']/1024/1024; 
$hd_capacity = number_format($hd_total,2);
echo "HD Usage: ".$arrSysInfo['particiones'][0]['uso_porcentaje']."\n" .
     "HD Capac: $hd_capacity GB";
?>
