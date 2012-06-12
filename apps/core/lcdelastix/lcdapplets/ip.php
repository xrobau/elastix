#!/usr/bin/php -q
<?php
    require_once "/var/www/html/libs/paloSantoNetwork.class.php";

    $pNet = new paloNetwork();
//    $arrNetwork = $pNet->obtener_configuracion_red();

    $arrEths = $pNet->obtener_interfases_red_fisicas();

    //print_r($arrEths);
    echo "Type: ".@$arrEths['eth0']['Type']."\n" .
         "Addr: ".@$arrEths['eth0']['Inet Addr'] . "\n" .
         "Mask: ".@$arrEths['eth0']['Mask'] . "\n";

?>
