#!/usr/bin/php
<?php
require_once ("/var/www/html/modules/agent_console/libs/ECCP.class.php");

if (count($argv) < 2) die("Use: {$argv[0]} [agentchannel]\n");

$x = new ECCP();
try {
	print "Connect...\n";
	$x->connect("localhost", "agentconsole", "agentconsole");
	print_r($x->getagentqueues(count($argv) > 1 ? $argv[1] : NULL));
	print "Disconnect...\n";
	$x->disconnect();
} catch (Exception $e) {
	print_r($e);
	print_r($x->getParseError());
}
?>
