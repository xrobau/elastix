#!/usr/bin/php
<?php
require_once ("/var/www/html/modules/agent_console/libs/ECCP.class.php");
$x = new ECCP();
try {
	print "Connect...\n";
	$x->connect("localhost", "agentconsole", "agentconsole");
    print "Listening for events...\n";
    while (TRUE) {
        $x->wait_response(1);
        while ($e = $x->getEvent()) {
            print_r($e);
        }
    }
	print "Disconnect...\n";
	$x->disconnect();
} catch (Exception $e) {
	print_r($e);
	print_r($x->getParseError());
}
?>
