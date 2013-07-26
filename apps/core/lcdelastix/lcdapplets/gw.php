#!/usr/bin/php -q
<?php
if (file_exists("/usr/share/elastix/libs/paloSantoNetwork.class.php"))
    require_once "/usr/share/elastix/libs/paloSantoNetwork.class.php";
else require_once "/var/www/html/libs/paloSantoNetwork.class.php";

$pNet = new paloNetwork();
$arrNetwork = $pNet->obtener_configuracion_red();

if (count($argv) <= 1) echo "GW: ";
echo @$arrNetwork['gateway']."\n";
?>
