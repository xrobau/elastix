<?php
    $libsPath = "/var/www/html";
    require_once("$libsPath/libs/misc.lib.php");
    require_once("$libsPath/configs/default.conf.php");
    require_once("$libsPath/libs/paloSantoDB.class.php");
    require_once("$libsPath/libs/paloSantoConfig.class.php");


    $dsn_conn_database = generarDSNSistema('root', 'mya2billing',"$libsPath/");
    $pDBa2billing = new paloDB($dsn_conn_database);
    $QUERY ="SELECT pwd_encoded, userid, login FROM cc_ui_authen";
    $arr_result = $pDBa2billing->fetchTable($QUERY,true);

    // obteniendo la clave de administracion del usuario admin
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $listaParam = $pConfig->leer_configuracion(FALSE);
    $admin_pass = $listaParam['AMPDBPASS']['valor'];
    $admin_pass_enc = hash('whirlpool', $admin_pass);
    $old_pass_arr = array("changepassword", "myroot");

    if(is_array($arr_result) && count($arr_result) > 0){
        foreach($arr_result as $rowid) {
            $OldPassword = $rowid['pwd_encoded'];
            $Password = hash('whirlpool', $OldPassword);

            if(strlen($OldPassword) < 128){
                if($rowid['login'] == "root" || $rowid['login'] == "admin"){
		    if(in_array($OldPassword, $old_pass_arr)){
		        $Password = $admin_pass_enc;
		    }
		}
		if($pDBa2billing->genQuery("update cc_ui_authen set pwd_encoded='$Password' where userid='$rowid[userid]';")){
		    echo "Successfull, user PASSWORS were changed\n";
		}
                else 
                    echo "Error: user PASSWORS weren't changed\n";
            }else{
                // verificar la clave del usuario admin o root es una de las claves del arreglo de claves antiguas
                if($rowid['login'] == "root" || $rowid['login'] == "admin"){ 
		    for($i=0; $i < count($old_pass_arr); $i++){
		        $old_pass = $old_pass_arr[$i];
			$old_pass_enc = hash('whirlpool', $old_pass);
			if($OldPassword === $old_pass_enc){ // si es igual a una de las claves antiguas entonces se coloca la nueva clave
			    if($pDBa2billing->genQuery("update cc_ui_authen set pwd_encoded='$admin_pass_enc' where userid='$rowid[userid]';")){
			        echo "Successfull, user PASSWORS were changed\n";
			    }
                            else 
                                echo "Error: user PASSWORS weren't changed\n";
			}
		    }
		}
            }
        }
    }


    removeRootUser($pDBa2billing);
    changeConfigManager($pDBa2billing,"admin",$admin_pass);


    function removeRootUser($pDBa2billing)
    {
        // verificando si existe el usuario admin.
	$query = "SELECT userid FROM cc_ui_authen WHERE login='admin';";
	$result = $pDBa2billing->getFirstRowQuery($query);
	if(count($result) > 0 && isset($query)){
	    $query = "DELETE FROM cc_ui_authen WHERE login='root';";
	    if($pDBa2billing->genQuery($query))
	        echo "User root of a2billing was removed and user admin is the unique administrator\n";
	    else
	        echo "Error: User root of a2billing does not exist\n";
	}else{
	    $query = "UPDATE cc_ui_authen SET login='admin' WHERE login='root';";
	    if($pDBa2billing->genQuery($query))
	        echo "Login of user root was changed to admin\n";
	    else
	        echo "Error: User root of a2billing does not exist\n";
	}
    }

    function changeConfigManager($pDBa2billing, $user ,$passwd)
    {
        $sql1="update cc_config set config_value=? where config_group_title='global' and config_key='manager_username' and config_value='myasterisk';";
        $sql2="update cc_config set config_value=? where config_group_title='global' and config_key='manager_secret'   and config_value='mycode';";
        $sql3="update cc_server_manager set manager_username=?, manager_secret=? where id=1 and id_group=1 and manager_username='myasterisk' and manager_secret='mycode';";

        $ok1 = $pDBa2billing->genQuery($sql1,array($user));
        $ok2 = $pDBa2billing->genQuery($sql2,array($passwd));
        $ok3 = $pDBa2billing->genQuery($sql3,array($user,$passwd));
   
        if(!$ok1)
            echo "Error: Username in config global (For asterisk manager) can't be changed.\n";
        if(!$ok2)
            echo "Error: Secret in config global (For asterisk manager) can't be changed.\n";
        if(!$ok3)
            echo "Error: Username and Secret in config server manager can't be changed.\n";
        if($ok1 && $ok2 && $ok3)
            echo "Successfull, Username and Secret was changed for asterisk manager ans config server manager\n";
   }
?>
