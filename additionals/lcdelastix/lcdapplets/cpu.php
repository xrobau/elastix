#!/usr/bin/php -q
<?php
include_once("/var/www/html/libs/misc.lib.php");
$arrSysInfo = obtener_info_de_sistema();
//print_r($arrSysInfo);
$cpuusage = number_format($arrSysInfo['CpuUsage']*100, 2);
exec("/usr/bin/uptime", $arrSalida);
preg_match("/load average:(.*)/", $arrSalida[0], $arrReg);
$load = $arrReg[1];
//$load = str_replace(" ", "", $load);
echo "CPU Usage: $cpuusage%\n" .
     "Load:\n" . 
     $load;
?>
