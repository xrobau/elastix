#!/usr/bin/php -q
<?php
// Recolectar la información de particiones
$output = $retval = NULL;
exec('/bin/df -P /etc/fstab', $output, $retval);
$bloques_total = $bloques_usados = 0;
$regexp = "!^([/-_\.[:alnum:]|-]+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d{1,3}%)\s+([/-_\.[:alnum:]]+)$!";
foreach ($output as $linea) {
    $regs = NULL;
    if (preg_match($regexp, $linea, $regs)) {
        $bloques_total += (int)$regs[2];
        $bloques_usados += (int)$regs[3];
    }
}
$hd_capacity = number_format($bloques_total/1024/1024, 0);
$hd_usage = number_format(100.0 * $bloques_usados / $bloques_total, 0);
if (count($argv) > 1) {
	echo "{$hd_usage}% of {$hd_capacity}GB\n";
} else {
    echo "HD Usage:{$hd_usage}%\n" .
         "HD Capac:{$hd_capacity}GB";
}
?>
