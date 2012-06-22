<?php

$base_dir="/var/www/html";
$base_db ="/var/www/db";
$module_name = "backup_restore";

include_once "$base_dir/libs/paloSantoConfig.class.php";
include_once "$base_dir/libs/misc.lib.php";
include_once "$base_dir/configs/default.conf.php";
include_once "$base_dir/modules/$module_name/configs/default.conf.php";
include_once "$base_dir/modules/$module_name/libs/paloSantoFTPBackup.class.php";
include_once "$base_dir/libs/paloSantoDB.class.php";
include_once "$base_dir/modules/$module_name/lang/en.lang";
include_once "$base_dir/lang/en.lang";
    //global variables  
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    
    $arrLang   = array_merge($arrLang,$arrLangModule);
    $arrConf   = array_merge($arrConf,$arrConfModule);  
    
    // empieza el backup
    $time = $_SERVER['argv'][1];

    process_backup($arrLang, $base_db);
    exec("sudo -u root chown -R asterisk.asterisk /var/www/backup");
    function process_backup($arrLang, $base_db)
    {
        $arrBackupOptions = Array_Options($arrLang);
        $arrSelectedOptions=array();
    
        $xml_backup = "<raiz>\n";
        foreach ($arrBackupOptions as $key_general=>$arrOptionGeneral)
        {
            $xml_backup .= "\t<options id=\"$key_general\">\n";
            foreach($arrOptionGeneral as $key=>$arrOption)
            {
                //le pongo checked
                $arrBackupOptions[$key]["check"]="checked";
                $xml_backup .= "\t\t<option>$key</option>\n";
                //lo agrego al arreglo
                $arrSelectedOptions[]=$key;
            }
            $xml_backup .= "\t</options>\n";
        }
        $xml_backup .= "</raiz>";
    
        //crear la carpeta donde se va a copiar el respaldo que se realice
        $dir_respaldo = "/var/www/backup";
        $valor_unico = "-".date("YmdHis")."-ab";
        $carpeta_respaldo = "backup";
        $timestamp= $carpeta_respaldo.$valor_unico;
        $ruta_respaldo="$dir_respaldo/$timestamp";
    
        $ruta_respaldo_sin_valor_unico = "$dir_respaldo/$carpeta_respaldo";
    
        //asegurarme que ya no exista la carpeta
        //si ya existe BORRO contenido
        if (file_exists($ruta_respaldo_sin_valor_unico)){
            exec("rm -rf $ruta_respaldo_sin_valor_unico",$output,$retval);
        }
        mkdir($ruta_respaldo_sin_valor_unico); // ??
    
        //Guardar xml para saber que opciones fueron respaldadas y obviar las que no en el momento de hacer el restore
        $gestor = fopen($ruta_respaldo_sin_valor_unico."/a_options.xml", "w");
        fwrite($gestor, $xml_backup);
        fclose($gestor);
    
        //Escribo el archo xml de versions para validaciones en el restore
        createVersionPrograms_XML($dir_respaldo);

        //hacer el respaldo de las opciones seleccionadas
        //tengo que mostrar cuales de las opciones seleccionadas, se hizo el respaldo correctamente por eso envio $arrBackupOptions
        process_each_backup($arrSelectedOptions,$ruta_respaldo_sin_valor_unico,$arrBackupOptions, $base_db);
        //en la carpeta backup ya deberia tener los respaldos
        //comprimo la carpeta
        //y la envio al navegador
        exec("tar -C $dir_respaldo -cvf $dir_respaldo/elastix$timestamp.tar $carpeta_respaldo ",$output,$retval);
        if ($retval<>0) //no se pudo generar el archivo comprimido
            $errMsg= $arrLang["Could not generate backup file"]." : $dir_respaldo/elastix$timestamp.tar\n";
    
        //borro la carpeta de backup
        exec("rm $ruta_respaldo_sin_valor_unico -rf");
    }

    function getVersionPrograms_SYSTEM(){
     //obteniendo version de asterisk, dahdi, Sangoma, freePBX, elastix
     // asterisk
     $comando1="rpm -q --queryformat '%{version}' asterisk";
     $comando2="rpm -q --queryformat '%{release}' asterisk";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['asterisk'] = array("version" => "$output1", "release" => "$output2");
    
     // dahdi
     $comando1="rpm -q --queryformat '%{version}' dahdi";
     $comando2="rpm -q --queryformat '%{release}' dahdi";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['dahdi'] = array("version" => "$output1", "release" => "$output2");

     // Sangoma
     $comando1="rpm -q --queryformat '%{version}' wanpipe-util";
     $comando2="rpm -q --queryformat '%{release}' wanpipe-util";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['wanpipe-util'] = array("version" => "$output1", "release" => "$output2");

     //freePBX
     $comando1="rpm -q --queryformat '%{version}' freePBX";
     $comando2="rpm -q --queryformat '%{release}' freePBX";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['freepbx']  = array("version" => "$output1", "release" => "$output2");

    //elastix
     $comando1="rpm -q --queryformat '%{version}' elastix";
     $comando2="rpm -q --queryformat '%{release}' elastix";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix']  = array("version" => "$output1", "release" => "$output2");
/***************************** added ******************************************************/
     // elastix-pbx
     $comando1="rpm -q --queryformat '%{version}' elastix-pbx";
     $comando2="rpm -q --queryformat '%{release}' elastix-pbx";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-pbx']  = array("version" => "$output1", "release" => "$output2");

     // elastix-email
     $comando1="rpm -q --queryformat '%{version}' elastix-email_admin";
     $comando2="rpm -q --queryformat '%{release}' elastix-email_admin";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-email_admin']  = array("version" => "$output1", "release" => "$output2");

     // elastix-agenda
     $comando1="rpm -q --queryformat '%{version}' elastix-agenda";
     $comando2="rpm -q --queryformat '%{release}' elastix-agenda";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-agenda']  = array("version" => "$output1", "release" => "$output2");

     // elastix-fax
     $comando1="rpm -q --queryformat '%{version}' elastix-fax";
     $comando2="rpm -q --queryformat '%{release}' elastix-fax";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-fax']  = array("version" => "$output1", "release" => "$output2");

     // elastix-vtigercrm
     $comando1="rpm -q --queryformat '%{version}' elastix-vtigercrm";
     $comando2="rpm -q --queryformat '%{release}' elastix-vtigercrm";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-vtigercrm']  = array("version" => "$output1", "release" => "$output2");

     // elastix-a2billing
     $comando1="rpm -q --queryformat '%{version}' elastix-a2billing";
     $comando2="rpm -q --queryformat '%{release}' elastix-a2billing";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     $arrPro['elastix-a2billing']  = array("version" => "$output1", "release" => "$output2");

     // elastix-sugarcrm-addon
     $comando1="rpm -q --queryformat '%{version}' elastix-sugarcrm-addon";
     $comando2="rpm -q --queryformat '%{release}' elastix-sugarcrm-addon";
     $output1 = `$comando1`;
     $output2 = `$comando2`;
     if(strlen($output2)>3){
	$output1 = _tr("Package not installed");
	$output2 = _tr("Package not installed");
     }
     $arrPro['elastix-sugarcrm-addon']  = array("version" => "$output1", "release" => "$output2");
/***************************** end added ***************************************************/
     return $arrPro;
}


    function createVersionPrograms_XML($dir_respaldo)
    {
        $arrSelectedOptions=array();
        $arrPrograms = getVersionPrograms_SYSTEM();
    
        $xml_versions = "<versions>\n";
        foreach ($arrPrograms as $id => $program)
            $xml_versions .= "\t<program id=\"$id\" ver=\"$program[version]\" rel=\"$program[release]\" />\n";
        $xml_versions .= "</versions>";
    
        //crear la carpeta donde se va a copiar el respaldo que se realice
        $dir_respaldo = "/var/www/backup";
        $carpeta_respaldo = "backup";
        $ruta_respaldo_sin_valor_unico = "$dir_respaldo/$carpeta_respaldo";
    
        //Guardar xml
        $gestor = fopen($ruta_respaldo_sin_valor_unico."/versions.xml", "w");
        fwrite($gestor, $xml_versions);
        fclose($gestor);
    }

    function Array_Options($arrLang, $disabled="")
    {
        $arrBackupOptions = array(
                "asterisk"      =>  array(
                                        "as_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "as_config_files"   =>  array("desc"=>$arrLang["Configuration Files"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "as_monitor"        =>  array("desc"=>$arrLang["Monitors"]."  ".$arrLang["(Heavy Content)"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "as_voicemail"      =>  array("desc"=>$arrLang["Voicemails"]."  ".$arrLang["(Heavy Content)"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "as_sounds"         =>  array("desc"=>$arrLang["Sounds"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "as_mohmp3"            =>
                                        array("desc"=>$arrLang["MOH"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "as_dahdi"         =>  array("desc"=>$arrLang["DAHDI Configuration"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    ),
                "fax"           =>  array(
                                        "fx_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "fx_pdf"            =>  array("desc"=>$arrLang["PDF"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    ),
                "email"         =>  array(
                                        "em_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "em_mailbox"        =>  array("desc"=>$arrLang["Mailbox"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    ),
                "endpoint"      =>  array(
                                        "ep_db"             =>  array("desc"=>$arrLang["Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "ep_config_files"   =>  array("desc"=>$arrLang["Configuration Files"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    ),
                "otros"         =>  array(
                                        "sugar_db"          =>  array("desc"=>$arrLang["SugarCRM Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "vtiger_db"         =>  array("desc"=>$arrLang["VtigerCRM Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "a2billing_db"      =>  array("desc"=>$arrLang["A2billing Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "mysql_db"          =>  array("desc"=>$arrLang["Mysql Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "menus_permissions" =>  array("desc"=>$arrLang["Menus and Permissions"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "fop_config"        =>  array("desc"=>$arrLang["Flash Operator Panel Config Files"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    ),
            "otros_new"      =>  array(
                                        "calendar_db"          =>  array("desc"=>$arrLang["Calendar  Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "address_db"          =>  array("desc"=>$arrLang["Address Book Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "conference_db"          =>  array("desc"=>$arrLang["Conference  Database"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                        "eop_db"          =>  array("desc"=>$arrLang["EOP"],"check"=>"","msg"=>"","disable"=>"$disabled"),
                                    ),
        );
        return $arrBackupOptions;
    }

    /* ------------------------------------------------------------------------------- */
    /* FUNCIONS PARA EL BACKUP*/
    /* ------------------------------------------------------------------------------- */
    
    function process_each_backup($arrSelectedOptions,$ruta_respaldo,&$arrBackupOptions, $base_db)
    {
	global $arrConf;
	    $arrResult = array();
	    $arrExito = array();
	    $arrExitoFolder = array();
	    foreach ($arrSelectedOptions as $option)
	    {
		$bExito=true;
		$error="";
		switch ($option){
		case "as_db":
		    $dir_resp_db="$ruta_respaldo/mysqldb_asterisk";
		    mkdir($dir_resp_db);

		    //Hacer mysqldump de cada base de asterisk
		    $arrMysqlAsterisk = respaldar_base_mysql($dir_resp_db, "asterisk");
		    if(!$arrMysqlAsterisk['status']){
			$arrMysqlAsterisk['db'] = "asterisk";
			$arrExito[] = $arrMysqlAsterisk;
			$bExito = false;
		    }
		    $arrMysqlAsteriskCdr = respaldar_base_mysql($dir_resp_db, "asteriskcdrdb");
		    if(!$arrMysqlAsteriskCdr['status']){
			$arrMysqlAsteriskCdr['db'] = "asteriskcdrdb";
			$arrExito[] = $arrMysqlAsteriskCdr;
			$bExito = false;
		    }
		    $arrMysqlAsteriskReal = respaldar_base_mysql($dir_resp_db, "asteriskrealtime");
		    if(!$arrMysqlAsteriskReal['status']){
			$arrMysqlAsteriskReal['db'] = "asteriskrealtime";
			$arrExito[] = $arrMysqlAsteriskReal;
			$bExito = false;
		    }

		    //Respaldar carpeta con las bases
		    $arrInfoRespaldo = array(   'folder_path'               =>  $ruta_respaldo,
						'folder_name'               =>  "mysqldb_asterisk",
						'nombre_archivo_respaldo'   =>  "mysqldb_asterisk.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "$ruta_respaldo/mysqldb_asterisk";
		    }

		    //Se respalda la base asterisk en /var/lib/asterisk/astdb
		    if(!copy("/var/lib/asterisk/astdb", $ruta_respaldo."/astdb")) $bExito = false;

		    //Se respalda la carpeta admin de FreePBX

		    $arrInfoRespaldo2 = array(  'folder_path'               =>  "/var/www/html",
						'folder_name'               =>  "admin",
						'nombre_archivo_respaldo'   =>  "var.www.html.admin.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo2,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/www/html/admin";
		    }

		    //borrar la carpeta de respaldo mysqldb
		    exec("rm $ruta_respaldo/mysqldb_asterisk -rf");
		    break;

		case "as_config_files":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/etc",
						'folder_name'               =>  "asterisk",
						'nombre_archivo_respaldo'   =>  "etc.asterisk.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/etc/asterisk";
		    }
		    break;

		case "as_monitor":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/var/spool/asterisk",
						'folder_name'               =>  "monitor",
						'nombre_archivo_respaldo'   =>  "var.spool.asterisk.monitor.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/spool/asterisk/monitor";
		    }
		    break;

		case "as_voicemail":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/var/spool/asterisk",
						'folder_name'               =>  "voicemail",
						'nombre_archivo_respaldo'   =>  "var.spool.asterisk.voicemail.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/spool/asterisk/voicemail";
		    }
		    break;

		case "as_sounds":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/var/lib/asterisk/sounds",
						'folder_name'               =>  "custom",
						'nombre_archivo_respaldo'   =>  "var.lib.asterisk.sounds.custom.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/lib/asterisk/sounds/custom";
		    }
		    break;

		case "as_mohmp3":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/var/lib/asterisk",
						'folder_name'               =>  "mohmp3",
						'nombre_archivo_respaldo'   =>  "var.lib.asterisk.mohmp3.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/lib/asterisk/mohmp3";
		    }

		    $arrInfoRespaldo2 = array( 'folder_path'               =>  "/var/lib/asterisk",
						'folder_name'               =>  "moh",
						'nombre_archivo_respaldo'   =>  "var.lib.asterisk.moh.tgz"
					);

		    if(!respaldar_carpeta($arrInfoRespaldo2,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/lib/asterisk/moh";
		    }
		    break;

		case "as_dahdi":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/etc",
						'folder_name'               =>  "dahdi",
						'nombre_archivo_respaldo'   =>  "etc.dahdi.tgz"
					    );
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/etc/dahdi";
		    }
		    break;

		case "fx_db":
		    if(!copy("$base_db/fax.db", $ruta_respaldo."/fax.db")) $bExito = false;
		    break;

		case "fx_pdf":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/var/www",
						'folder_name'               =>  "faxes",
						'nombre_archivo_respaldo'   =>  "var.www.faxes.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/www/faxes";
		    }
		    break;

		case "em_db":
		    $arrMysqlRoundcube = respaldar_base_mysql($ruta_respaldo, "roundcubedb");
		    if(!$arrMysqlRoundcube){
			$arrMysqlRoundcube['db'] = "roundcubedb";
			$arrExito[] = $arrMysqlRoundcube;
			$bExito = false;
		    }

		    if (file_exists("$ruta_respaldo/roundcubedb.sql")){
			$comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/roundcubedb_mysql.tgz roundcubedb.sql";
			exec($comando,$output,$retval);
			if ($retval!=0) $bExito = false;

			$comando="rm -f $ruta_respaldo/roundcubedb.sql";
			exec($comando,$output,$retval);
		    }else if (file_exists("$ruta_respaldo/roundcubedb2.sql")){
			//Si existe este archivo es porq la base esta vacia o no existe
			$comando="rm -f $ruta_respaldo/roundcubedb2.sql";
			exec($comando,$output,$retval);
		    }else $bExito = false;

		    if(!copy("$base_db/email.db", "$ruta_respaldo/email.db")) $bExito = false;

		    break;

		case "em_mailbox":
		    //respaldar los  mailboxes ruta /var/spool/imap
		    //primero cambiar los permisos a la carpeta
		    $comando="sudo -u root /bin/chown asterisk:asterisk /var/spool/imap -R";
		    exec($comando,$output,$retval);
		    if ($retval!=0) $bExito = false;
		    else{
			$arrInfoRespaldo = array(   'folder_path'               =>  "/var/spool",
						    'folder_name'               =>  "imap",
						    'nombre_archivo_respaldo'   =>  "var.spool.imap.tgz"
					);
			if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			    $bExito = false;
			    $arrExitoFolder[]['status'] = false;
			    $arrExitoFolder[]['folder'] = "/var/spool/imap";
			}
		    }
		    $comando="sudo -u root /bin/chown cyrus:mail /var/spool/imap -R";
		    exec($comando,$output,$retval);
		    break;

		case "ep_db":
		    if(!copy("$base_db/endpoint.db", "$ruta_respaldo/endpoint.db")) $bExito = false;
		    break;

		case "ep_config_files":
		    $arrInfoRespaldo = array(   'folder_path'               =>  "/",
						'folder_name'               =>  "tftpboot",
						'nombre_archivo_respaldo'   =>  "tftpboot.tgz"
					);
		    //Cambiar permisos a la carpeta (con sudo), sino no se puede hacer backup
		    $comando="sudo -u root /bin/chown asterisk:asterisk /tftpboot -R";
		    exec($comando,$output,$retval);
		    if ($retval!=0) $bExito = false;
		    else{
			if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			    $bExito = false;
			    $arrExitoFolder[]['status'] = false;
			    $arrExitoFolder[]['folder'] = "/tftpboot";
			}
		    }
		    //cambio de nuevo a root
		    $comando="sudo -u root /bin/chown root:root /tftpboot";
		    exec($comando,$output,$retval);
		    break;

		case "sugar_db":
		    $arrMysqlSugarCrm = respaldar_base_mysql($ruta_respaldo, "sugarcrm");
		    if(!$arrMysqlSugarCrm){
			$arrMysqlSugarCrm['db'] = "sugarcrm";
			$arrExito[] = $arrMysqlSugarCrm;
			$bExito = false;
		    }

		    if (file_exists("$ruta_respaldo/sugarcrm.sql"))
		    {
			$comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/sugarcrm_mysql.tgz sugarcrm.sql";
			exec($comando,$output,$retval);
			if ($retval!=0) $bExito = false;

			$comando="rm -f $ruta_respaldo/sugarcrm.sql";
			exec($comando,$output,$retval);
		    }else if (file_exists("$ruta_respaldo/sugarcrm2.sql"))
		    {
			//Si existe este archivo es porq la base esta vacia o no existe
			$comando="rm -f $ruta_respaldo/sugarcrm2.sql";
			exec($comando,$output,$retval);
		    }else $bExito = false;
		    break;

		case "vtiger_db":
		    $arrMysqlVtigerCrm = respaldar_base_mysql($ruta_respaldo, "vtigercrm510");
		    if(!$arrMysqlVtigerCrm){
			$arrMysqlVtigerCrm['db'] = "vtigercrm510";
			$arrExito[] = $arrMysqlVtigerCrm;
			$bExito = false;
		    }

		    if (file_exists("$ruta_respaldo/vtigercrm510.sql"))
		    {
			$comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/vtigercrm510_mysql.tgz vtigercrm510.sql";
			exec($comando,$output,$retval);
			if ($retval!=0) $bExito = false;

			$comando="rm -f $ruta_respaldo/vtigercrm510.sql";
			exec($comando,$output,$retval);
		    }else if (file_exists("$ruta_respaldo/vtigercrm5102.sql"))
		    {
			//Si existe este archivo es porq la base esta vacia o no existe
			$comando="rm -f $ruta_respaldo/vtigercrm5102.sql";
			exec($comando,$output,$retval);
		    }else $bExito = false;
		    break;

		case "a2billing_db":
		    $arrMysqlA2billing = respaldar_base_mysql($ruta_respaldo, "mya2billing");
		    if(!$arrMysqlA2billing){
			$arrMysqlA2billing['db'] = "mya2billing";
			$arrExito[] = $arrMysqlA2billing;
			$bExito = false;
		    }

		    if (file_exists("$ruta_respaldo/mya2billing.sql"))
		    {
			$comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/mya2billing_mysql.tgz mya2billing.sql";
			exec($comando,$output,$retval);
			if ($retval!=0) $bExito = false;

			$comando="rm -f $ruta_respaldo/mya2billing.sql";
			exec($comando,$output,$retval);
		    }else if (file_exists("$ruta_respaldo/mya2billing2.sql"))
		    {
			//Si existe este archivo es porq la base esta vacia o no existe
			$comando="rm -f $ruta_respaldo/mya2billing2.sql";
			exec($comando,$output,$retval);
		    }else $bExito = false;
		    break;

		case "mysql_db":
		    $arrMysqlDb = respaldar_base_mysql($ruta_respaldo, "mysql");
		    if(!$arrMysqlDb){
			$arrMysqlDb['db'] = "mysql";
			$arrExito[] = $arrMysqlDb;
			$bExito = false;
		    }

		    if (file_exists("$ruta_respaldo/mysql.sql"))
		    {
			$comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/mysql_mysql.tgz mysql.sql";
			exec($comando,$output,$retval);
			if ($retval!=0) $bExito = false;

			$comando="rm -f $ruta_respaldo/mysql.sql";
			exec($comando,$output,$retval);
		    }else $bExito = false;
		    break;

		case "menus_permissions":
		    if(!copy("$base_db/menu.db", "$ruta_respaldo/menu.db")) $bExito = false;
		    if(!copy("$base_db/acl.db", "$ruta_respaldo/acl.db")) $bExito = false;

		    break;

		case "fop_config":
		    //FLASH
		    $arrInfoRespaldo = array('folder_path'            =>"/var/www/html/",
					    'folder_name'            =>"panel/*.cfg panel/*.txt",
					    'nombre_archivo_respaldo'=>"var.www.html.panel.tgz"
					);
		    if(!respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,$error)){
			$bExito = false;
			$arrExitoFolder[]['status'] = false;
			$arrExitoFolder[]['folder'] = "/var/www/html/panel/*.cfg panel/*.txt";
		    }
		    $comando1="rpm -q --queryformat '%{version}' freePBX";
		    $output1 = `$comando1`;
		    if(isset($output1))
			$version = explode(".",$output1);
			if($version[0] >=2 && $version[1] <= 6)
			    if(!copy("/var/lib/asterisk/bin/retrieve_op_conf_from_mysql.pl", "$ruta_respaldo/retrieve_op_conf_from_mysql.pl")) $bExito = false;

		    break;

		case "calendar_db":
		    if(!copy("$base_db/calendar.db", "$ruta_respaldo/calendar.db")) $bExito = false;
		    break;

		case "address_db":
		    if(!copy("$base_db/address_book.db", "$ruta_respaldo/address_book.db")) $bExito = false;
		    break;

		case "conference_db":
		    $arrMysqlMeetme = respaldar_base_mysql($ruta_respaldo, "meetme");
		    if(!$arrMysqlMeetme){
			$arrMysqlMeetme['db'] = "meetme";
			$arrExito[] = $arrMysqlMeetme;
			$bExito = false;
		    }

		    if (file_exists("$ruta_respaldo/meetme.sql"))
		    {
			$comando="tar -C $ruta_respaldo -cvzf $ruta_respaldo/meetme_mysql.tgz meetme.sql";
			exec($comando,$output,$retval);
			if ($retval!=0) $bExito = false;

			$comando="rm -f $ruta_respaldo/meetme.sql";
			exec($comando,$output,$retval);
		    }else if (file_exists("$ruta_respaldo/meetme.sql"))
		    {
			//Si existe este archivo es porq la base esta vacia o no existe
			$comando="rm -f $ruta_respaldo/meetme.sql";
			exec($comando,$output,$retval);
		    }else $bExito = false;
		    break;

		case "eop_db":
		    if(!copy("$base_db/control_panel_design.db", "$ruta_respaldo/control_panel_design.db")) $bExito = false;
		    break;
		}

		if ($bExito){
		    $msge = "[ OK ]";

		}else{
		    $msge = "[ FAILED ]";
		}
		$arrBackupOptions[][$option]["msg"]=$msge;
	    }
    }
    
    function respaldar_carpeta($arrInfoRespaldo,$ruta_respaldo,&$error)
    {
        $bExito=true;
        $comando = "cd ".$arrInfoRespaldo['folder_path'] ."; ".
                   "tar -cvzf $ruta_respaldo/{$arrInfoRespaldo['nombre_archivo_respaldo']}  {$arrInfoRespaldo['folder_name']}";
        exec($comando,$output,$retval);
        if ($retval<>0) $bExito=false;
    
        return $bExito;
    }

    function databaseExist($base, $pass, $host, $user)
    {
        $pathDBs = "/var/lib/mysql/".$base;
        if(is_dir($pathDBs)){
            //Ejecutando el siguiente comando en la base schema de mysql.
            $pDB = new paloDB("mysql://$user:$pass@$host/information_schema");
            $pFTPBackup = new paloSantoFTPBackup($pDB);
            $result = $pFTPBackup->existSchemaDB($base, $pDB);
            return $result;
        }else{
            return false;
        }
    }
    
    function respaldar_base_mysql($dir_resp_db,$base)
    {
        $respaldo ="";
	$estructura="";
	$arrResult = array();
	$bContinuar = FALSE;
	$host="localhost";
	$user="root";
	$pass=obtenerClaveConocidaMySQL('root','/var/www/html/');
	$dsn     = "mysql://$user:$pass@$host/$base";
	$db = new paloDB($dsn);
	if($db->errMsg != ""){
	    $arrResult['status'] = false;
	    $arrResult['msg'] = $db->errMsg;
	}

	if(databaseExist($base, $pass, $host, $user))
	    //obtiene el dump de los inserts
	    system("mysqldump -h $host -u $user -p$pass  $base -t -c > $dir_resp_db/{$base}2.sql",$retorno);
	else
	    $retorno = 1;

	if ($retorno==0) $bContinuar = TRUE;

	if ($bContinuar){
	    // se obtiene la estructura de la base de datos es decir vacia.
	    system("mysqldump -h $host -u $user -p$pass  $base --no-data  > $dir_resp_db/{$base}.sql",$retorno);
	    if ($retorno==0){
		$estructura=file_get_contents("$dir_resp_db/{$base}.sql");
		$estructura=str_replace("CREATE TABLE","CREATE TABLE IF NOT EXISTS",$estructura);

		if (strlen(trim($estructura))>0){
		    $respaldo=$estructura.$respaldo;
		    $bContinuar=TRUE;
		}else{//si no hay estructura no se puede continuar con los datos
		    $bContinuar=FALSE;
		}
	    }else{
		$bContinuar = FALSE;
	    }
	}

	if ($bContinuar){
	    // file_put_contents("$dir_resp_db/{$base}.sql", $respaldo, FILE_APPEND);
	    $open = fopen ("$dir_resp_db/{$base}2.sql","a+");
	    $openSQL = fopen ("$dir_resp_db/{$base}.sql","w+");
	    rewind($open); //Establece el indicador de posición de archivo de handle al principio del flujo del archivo.
	    rewind($openSQL); //Establece el indicador de posición de archivo de handle al principio del flujo del archivo.
	    $tamanio_linea=4096;
	    $escribir = fwrite ($openSQL,$respaldo."\n");
	    while ($linea = fgets($open,$tamanio_linea))  // [0]
	    {
	      $escribir = fwrite ($openSQL,$linea);
	    }
	    fclose($open);
	    fclose($openSQL);
	    unlink("$dir_resp_db/{$base}2.sql");
	    $arrResult['status'] = true;
	    $arrResult['msg'] = "";
	}

	//return $bContinuar?0:($retorno>0?1:$retorno);
	return $arrResult;
    }

 ?>
