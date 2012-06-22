#!/usr/bin/php
<?php
require_once ("/var/www/html/modules/agent_console/libs/ECCP.class.php");
$x = new ECCP();
try {
    print "Connect...\n";
    $x->connect("localhost", "agentconsole", "agentconsole");
    $x->setAgentNumber("Agent/9000");
    $x->setAgentPass("gatito");
    print "Agendando llamada...\n";
    $r = $x->schedulecall(
/*
        array(
            'date_init' =>  date('Y-m-d'),
            'date_end'  =>  '2011-08-31',
            'time_init' =>  '00:00:00',
            'time_end'  =>  '23:00:01'
        ),   // schedule
*/
        NULL,        
        0,   // sameagent
        7337,   // newphone
        "Sugar & Spice"    // newcontactname
    );
    print_r($r);
    print "Disconnect...\n";
    $x->disconnect();
} catch (Exception $e) {
    print_r($e);
    print_r($x->getParseError());
}
?>
