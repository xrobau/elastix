#!/usr/bin/php -q
<?php
if (file_exists("/usr/share/elastix/libs/paloSantoNetwork.class.php"))
    require_once "/usr/share/elastix/libs/paloSantoNetwork.class.php";
else require_once "/var/www/html/libs/paloSantoNetwork.class.php";

$pNet = new paloNetwork();
$arrEths = $pNet->obtener_interfases_red_fisicas();
$param = (count($argv) > 1) ? $argv[1] : NULL;
switch ($param) {
case 'type': echo @$arrEths['eth0']['Type']."\n"; break;
case 'addr': echo @$arrEths['eth0']['Inet Addr']."\n"; break;
case 'mask': echo @$arrEths['eth0']['Mask']."\n"; break;
default:
    echo "Type:".@$arrEths['eth0']['Type']."\n" .
         "Addr:".@$arrEths['eth0']['Inet Addr'] . "\n" .
         "Mask:".@$arrEths['eth0']['Mask'] . "\n";
    break;
}
?>
