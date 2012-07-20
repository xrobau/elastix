<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<localcfg>
    <server voIpProt.server.1.address="{$SERVER_IP}"/>
    <SIP>
        <outboundProxy voIpProt.SIP.outboundProxy.address="{$SERVER_IP}"/>
    </SIP>
    <SNTP tcpIpApp.sntp.daylightSavings.enable="1" tcpIpApp.sntp.address="{$SERVER_IP}"  tcpIpApp.sntp.gmtOffset="-18000" />
<localcfg>