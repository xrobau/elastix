#!/usr/bin/php
<?php
// script para crear carpetas spam y suscribirlas
    $module_name = "antispam";
    include_once "/var/www/html/libs/misc.lib.php";
    include_once "/var/www/html/libs/paloSantoDB.class.php";
    include_once "/var/www/html/configs/email.conf.php";
    include_once "/var/www/html/libs/cyradm.php";
    include_once "/var/www/html/modules/$module_name/libs/paloSantoAntispam.class.php";

    $days = trim($_SERVER['argv'][1]);

    if(isset($days) & $days!=""){
        $today     = date("d-M-Y");
        $sinceDate = date("d-M-Y",strtotime($today." -$days day"));
        $pDB = new paloDB("sqlite3:////var/www/db/email.db");
        $objAntispam = new paloSantoAntispam("", "", "", "");
        // primero se verifica si alguna cuenta no tiene la carpeta Spam creada
        $accounts = $objAntispam->getEmailList($pDB);

        exec("/etc/init.d/spamassassin status", $flag, $status);

        if($status == 0){
            // si el arreglo es vacio no hace nada
            if(isset($accounts) & $accounts!=""){// existe alguna cuenta sin esa carpeta
                foreach($accounts as $key => $value){
                    $status .= $objAntispam->deleteSpamMessages($value['username'], $sinceDate);
                }
            }
        }else{
            echo "ERROR: ".$flag[0]."\n";
        }
    }

?>