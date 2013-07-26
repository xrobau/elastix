#!/usr/bin/php -q
<?php
require_once "/var/www/html/libs/paloSantoNetwork.class.php";

$pNet = new paloNetwork();
$arrNetwork = $pNet->obtener_configuracion_red();

if (count($argv) <= 1) echo "GW: ";
echo @$arrNetwork['gateway']."\n";
?>
