#!/usr/bin/php -q
<?php
    require_once "/var/www/html/libs/paloSantoNetwork.class.php";

    $pNet = new paloNetwork();
    $arrNetwork = $pNet->obtener_configuracion_red();

    //print_r($arrNetwork);
    echo "DNS 1: ".@$arrNetwork['dns'][0]."" .
         "DNS 2: ".@$arrNetwork['dns'][1];

?>
