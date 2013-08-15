<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<localcfg>
    <server voIpProt.server.1.address="{{server_ip}}"/>
    <SIP>
        <outboundProxy voIpProt.SIP.outboundProxy.address="{{server_ip}}"/>
    </SIP>
    <SNTP tcpIpApp.sntp.daylightSavings.enable="1" tcpIpApp.sntp.address="{{server_ip}}"  tcpIpApp.sntp.gmtOffset="-18000" />
<localcfg>