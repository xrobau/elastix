#!/usr/bin/php
<?php
require_once ("/var/www/html/modules/agent_console/libs/ECCP.class.php");

if (count($argv) < 2) die("Use: {$argv[0]} agentchannel\n");
$agentname = $argv[1];

$x = new ECCP();
try {
	print "Connect...\n";
	$x->connect("localhost", "agentconsole", "agentconsole");
	$x->setAgentNumber($agentname);
	print_r($x->getAgentStatus());
	print "Disconnect...\n";
	$x->disconnect();
} catch (Exception $e) {
	print_r($e);
	print_r($x->getParseError());
}
?>
