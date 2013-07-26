#!/usr/bin/php -q
<?php
if (file_exists("/usr/share/elastix/libs/paloSantoNetwork.class.php"))
    require_once "/usr/share/elastix/libs/paloSantoNetwork.class.php";
else require_once "/var/www/html/libs/paloSantoNetwork.class.php";

$pNet = new paloNetwork();
$arrNetwork = $pNet->obtener_configuracion_red();

$which = (count($argv) > 1) ? $argv[1] : NULL;

switch ($which) {
case '1':
    echo (isset($arrNetwork['dns'][0]) ? $arrNetwork['dns'][0] : '(none)')."\n";
    break;
case '2':
    echo (isset($arrNetwork['dns'][1]) ? $arrNetwork['dns'][1] : '(none)')."\n";
    break;
default:
    echo "DNS1: ".(isset($arrNetwork['dns'][0]) ? $arrNetwork['dns'][0] : '(none)')."\n";
    echo "DNS2: ".(isset($arrNetwork['dns'][1]) ? $arrNetwork['dns'][1] : '(none)')."\n";
}
?>
