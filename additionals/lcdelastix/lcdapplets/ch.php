#!/usr/bin/php -q
<?php
$comando = "/usr/sbin/asterisk -r -x \"show channels\"";
exec($comando, $arrSalida, $varSalida);

$counter_channels_zap = 0;
$counter_channels_sip = 0; 
$counter_channels_iax = 0;
$counter_channels_h323 = 0;
$counter_channels_local = 0;

foreach($arrSalida as $linea) {
    if(eregi("^Zap/", $linea)) {
        $counter_channels_zap++;
    } else if(eregi("SIP", $linea)) {
        $counter_channels_sip++;
    } else if(eregi("IAX2", $linea)) {
        $counter_channels_iax++;
    } else if(eregi("h323", $linea)) {
        $counter_channels_h323++;
    } else if(eregi("Local", $linea)) {
        $counter_channels_local++;
    } else if(preg_match("/^([[:digit:]]+)[[:space:]]+active calls?/", $linea, $arrReg)) {
        $simCalls = $arrReg[1];
    }
}

$counter_channels_total = $counter_channels_zap + $counter_channels_sip + $counter_channels_iax + $counter_channels_h323 + $counter_channels_local;

echo "Sim. Channels: $simCalls\n"; 
?>
