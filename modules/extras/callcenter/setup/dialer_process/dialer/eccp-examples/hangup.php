#!/usr/bin/php
<?php
require_once ("/var/www/html/modules/agent_console/libs/ECCP.class.php");
$x = new ECCP();
try {
	print "Connect...\n";
	$x->connect("localhost", "agentconsole", "agentconsole");
        $x->setAgentNumber("Agent/9000");
        $x->setAgentPass("gatito");
	print_r($x->getAgentStatus());
	print "Hangup...\n";
	$r = $x->hangup();
	print_r($r);
	print "Disconnect...\n";
	$x->disconnect();
} catch (Exception $e) {
	print_r($e);
	print_r($x->getParseError());
}
?>
