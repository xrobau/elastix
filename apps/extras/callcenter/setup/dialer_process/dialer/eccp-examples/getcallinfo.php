#!/usr/bin/php
<?php
if (count($argv) < 4) {
    fprintf(STDERR, $argv[0]." [incoming|outgoing] [campaign-id] [call-id]\n");
    exit(0);
}

require_once ("/var/www/html/modules/agent_console/libs/ECCP.class.php");
$x = new ECCP();
try {
    print "Connect...\n";
    $x->connect("localhost", "agentconsole", "agentconsole");
    print "Pidiendo información de llamada...\n";
    $r = $x->getcallinfo($argv[1], (($argv[2] == '') ? NULL : $argv[2]), $argv[3]);
    print_r($r);
    print "Disconnect...\n";
    $x->disconnect();
} catch (Exception $e) {
    print_r($e);
    print_r($x->getParseError());
}
?>