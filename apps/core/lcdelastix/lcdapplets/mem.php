#!/usr/bin/php -q
<?php
$arrInfo = array(
    'MemTotal'      =>  0,
    'MemFree'       =>  0,
    'MemBuffers'    =>  0,
    'SwapTotal'     =>  0,
    'SwapFree'      =>  0,
    'Cached'        =>  0,
);
foreach (file('/proc/meminfo') as $linea) {
    $regs = NULL;
    if (preg_match('/^(\w+):\s+(\d+) kB/', $linea, $regs)) {
        if (isset($arrInfo[$regs[1]])) $arrInfo[$regs[1]] = $regs[2];
    }
}
$mem_usage = ($arrInfo['MemTotal'] - $arrInfo['MemFree'] - $arrInfo['MemBuffers'] - $arrInfo['Cached'])/$arrInfo['MemTotal'];
$mem_usage = number_format($mem_usage * 100, 0);
$mem_total = number_format($arrInfo['MemTotal']/1024, 0);
if (count($argv) > 1) {
    echo "{$mem_usage}% of {$mem_total}MB\n";
} else {
    echo "MemUsage: $mem_usage%\n" .
         "MemTotal:{$mem_total}MB\n";
}
?>
