#!/usr/bin/php -q
<?
    dl('sqlite3.so');
    $module_name='billing_sendmail';
    $full_path = realpath($_SERVER['PHP_SELF']);

    for ($i = 0; $i <= 2; $i++) $full_path = dirname($full_path);

    chdir($full_path);

    include_once "libs/paloSantoCron.class.php";
    require_once "libs/misc.lib.php";
    include_once "libs/paloSantoTrunk.class.php";
    include_once "libs/paloSantoCDR.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoRate.class.php";
    include_once "libs/MailAttach.php";

    load_language();
    # create object instance
    $pConfig   = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn       = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
                 $arrConfig['AMPDBHOST']['valor'] . "/asteriskcdrdb";

    $pDBSet  = new paloDB("sqlite3:////var/www/db/settings.db");
    $pDB     = new paloDB($dsn);
    $oCDR    = new paloSantoCDR($pDB);
    $mail    = new MailAttach;

    $pDBsendmail = new paloDB("sqlite3:////var/www/db/billing_sendmail.db");
    $pDBTrunk    = new paloDB("sqlite3:////var/www/db/trunk.db");

    $oCron = new paloCron($pDBsendmail);
    $oCron->module_name = $module_name;

    if (isset($_SERVER['argv'][1]) && !ereg("([^0-9]+)",$_SERVER['argv'][1])) {

    $arrScheduleList = $oCron->getCronSchedule($_SERVER['argv'][1]);

    if (isset($arrScheduleList[0][4])) $field_name = $arrScheduleList[0][4]; else die("No record!\n");

    $field_pattern = "";

    foreach (explode(';',$arrScheduleList[0][7]) as $v) {
		preg_match_all('/[[:digit:]]+|-[[:digit:]]+/', $v, $matches);

		if (isset($matches[0][1])){
		$clr_match[0] = $matches[0][0];
		$clr_match[1] = trim($matches[0][1],'-');
		$search = $clr_match[0].'-'.$clr_match[1];
			    $rest_str="";
			    for ($i = min($clr_match); $i <= max($clr_match); $i++) {
			        if (strpos($v,$search) !== false) {
			            $rest_str .= substr_replace($v,$i,strpos($v,$search),strlen($search)).",";
			        } else {
			            $rest_str .= $v.",";
			        }
			    }
			    $rest_str = trim ($rest_str,',');
		} else $rest_str = $v;
	$field_pattern .= $rest_str.",";
    }
        $field_pattern = trim($field_pattern,',');

    $pDBSQLite = new paloDB("sqlite3:////var/www/db/rate.db");

    if(!empty($pDBSQLite->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }
  
    $pRate = new paloRate($pDBSQLite);
    if(!empty($pRate->errMsg)) {
        echo "ERROR DE RATE: $pRate->errMsg <br>";
    }
  
    $oTrunk    = new paloTrunk($pDBTrunk);
    $troncales = $oTrunk->getExtendedTrunksBill($grupos);

    $sum_cost='';

    if (is_array($troncales) && count($troncales)>0){
	$date_start = date('Y-m-d H:i:s' , mktime ( date("H"),date("i"),date("s"), date("m"), date("d")-$arrScheduleList[0][6], date("Y")));
	$date_end   = date( 'Y-m-d H:i:s');

	$limit=null;
	$offset='0';
	$arrData=array();

        $arrCDR  = $oCDR->obtenerCDRs($limit, $offset, $date_start, $date_end, $field_name, $field_pattern,"ANSWERED","outgoing",$troncales);

	$headers  = '"'.$arrLang["Date"].'","'.$arrLang["Source"].'","'.$arrLang["Destination"].'","'.$arrLang["Dst. Channel"].'","';
        $headers .= $arrLang["Duration in seconds"].'","'.$arrLang["Cost"].'","'.$arrLang["Summary Cost"].'","'.$arrLang["Rate Applied"]."\",\n";

	$line="";
        foreach($arrCDR['Data'] as $cdr) {
        //tengo que buscar la tarifa para el numero de telefono
            if (ereg("^Zap/([[:digit:]]+)",$cdr[4],$regs3)) $trunk='ZAP/g'.$grupos[$regs3[1]];
            else $trunk=str_replace(strstr($cdr[4],'-'),'',$cdr[4]);

            $arrTmp = array();
            $tarifa = array();
            $charge = 0;
            $bExito = $pRate->buscarTarifa($cdr[2],$tarifa,$trunk);
            if (!count($tarifa)>0 && ($bExito)) $bExito=$pRate->buscarTarifa($cdr[2],$tarifa,'None');

            $rate_name="";
            if (!$bExito) {
                echo "ERROR DE RATE: $pRate->errMsg <br>";
            } else {
             //verificar si tiene tarifa
                if (count($tarifa)>0) {
                    $bTarifaOmision=FALSE;
                    foreach ($tarifa as $id_tarifa=>$datos_tarifa)
                    {
                        $charge=(($cdr[8]/60)*$datos_tarifa['rate'])+$datos_tarifa['offset'];
                        $rate_name=$datos_tarifa['name'];
                    }
                } else {
                    $bTarifaOmision=TRUE;
                    $rate_name="default";
                    $rate=get_key_settings($pDBSet,"default_rate");
                    $rate_offset=get_key_settings($pDBSet,"default_rate_offset");
                    $charge=(($cdr[8]/60)*$rate)+$rate_offset;
                }
            }
            $cost = number_format($charge,3);
            $sum_cost  = $sum_cost+$cost;
            $line .= '"' . $cdr[0] . '",';
	    $line .= '"' . ($cdr[1]?$cdr[1]:$cdr[3]) . '",';
            $line .= '"' . $cdr[2] . '",';
            $line .= '"' . $cdr[4] . '",';
            $line .= '"' . $cdr[8] . '",';
            $line .= '"' . $cost   . '",';
            $line .= '"' . $sum_cost  . '",';
            $line .= '"' . $rate_name . "\",\n";
        } // END foreach($arrCDR['Data'] as $cdr)

            $data = $headers.$line;
            $filename     = 'billing_report.csv';
            $content_type = 'text/plain';
             # set all data slots
            // $mail->from    = $module_name;
            $mail->from    = $arrScheduleList[0][5];
            $mail->to      = $arrScheduleList[0][5];
            $mail->subject = $arrLang["Billing Report"];
            $msg  = $arrLang["This is billing report from Elastix PBX system"].".\n\n\n";
            $msg .= $arrLang["Start date is"] . ": $date_start\n" . $arrLang["End date is"] . ": $date_end\n\n";
            $msg .= $arrLang["Please check your attachment for more details"].".";
            $mail->body    = $msg;

            if($filename <> "") {
            # append the attachment
            $mail->add_attachment($data, $filename, $content_type);
            }
            # send e-mail

            $enviado = $mail->send();
}

} else echo "No Data Found\n\n";

?>
