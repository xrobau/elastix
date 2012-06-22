<?php
    $libsPath = "/var/www/html";
    require_once("$libsPath/libs/misc.lib.php");
    require_once("$libsPath/configs/default.conf.php");
    require_once("$libsPath/libs/paloSantoDB.class.php");

    $dsn_conn_database = generarDSNSistema('root', 'mya2billing',"$libsPath/");
    $pDBa2billing = new paloDB($dsn_conn_database);
    $QUERY ="SELECT pwd_encoded,userid FROM cc_ui_authen";
    $arr_result = $pDBa2billing->fetchTable($QUERY,true);

    if(is_array($arr_result) && count($arr_result) > 0){
        foreach($arr_result as $rowid) {
            $OldPassword=$rowid['pwd_encoded'];
                if(strlen($OldPassword)<128)
                {
                    $Password= hash('whirlpool', $OldPassword);
                    if($pDBa2billing->genQuery("update cc_ui_authen set pwd_encoded='$Password' where userid='$rowid[userid]';"))
                    {
                        echo "Successfull, user PASSWORS were changed\n";
                    }
                    else echo "Error: user PASSWORS weren't changed\n";    
                }
        }
    }
?>
