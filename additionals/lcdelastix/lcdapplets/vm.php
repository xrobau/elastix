#!/usr/bin/php -q
<?php
$comando = "/usr/sbin/asterisk -r -x \"voicemail show users\"";
exec($comando, $arrSalida, $varSalida);

$lineas = count($arrSalida);
$usuarios_voicemail = $lineas - 2;
if($usuarios_voicemail<=0) $usuarios_voicemail=0;

echo "VM Users: $usuarios_voicemail\n"; 
?>
