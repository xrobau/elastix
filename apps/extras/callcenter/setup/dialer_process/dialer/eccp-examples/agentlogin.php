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
	print "Login agent\n";
	$r = $x->loginagent("1064");
	print_r($r);
	$bFalloLogin = FALSE;
	if (!isset($r->failure) && !isset($r->loginagent_response->failure)) while (!$bFalloLogin) {
		$x->wait_response(1);
		while ($e = $x->getEvent()) {
			print_r($e);
			foreach ($e->children() as $ee) $evt = $ee;
			if ($evt->getName() == 'agentfailedlogin') {
				$bFalloLogin = TRUE;
				break;
			}
		}
	}
	print "Disconnect...\n";
	$x->disconnect();
} catch (Exception $e) {
	print_r($e);
	print_r($x->getParseError());
}
?>
