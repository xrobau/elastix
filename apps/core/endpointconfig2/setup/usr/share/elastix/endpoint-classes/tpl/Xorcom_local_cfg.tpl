{{py:n = 0}}{{for extension in sip}}
[ cfg:/phone/config/voip/sipAccount{{n}}.cfg,account={{n}};reboot=1 ]
account.Enable = 1
account.Label = {{extension.description}}
account.DisplayName = {{extension.description}}
account.UserName = {{extension.extension}}
account.AuthName = {{extension.account}}
account.password = {{extension.secret}}
account.SIPServerHost = {{server_ip}}
{{py:n += 1}}{{endfor}}
{{for m in range(n,max_sip_accounts)}}
[ cfg:/phone/config/voip/sipAccount{{m}}.cfg,account={{m}};reboot=1 ]
account.Enable = 0
{{endfor}}

[ cfg:/phone/config/system.ini,reboot=1 ]
LocalTime.TimeServer1 = {{server_ip}}
LocalTime.TimeServer2 = 0.centos.pool.ntp.org
LocalTime.TimeZone = 0
LocalTime.DHCPTime = 1
LocalTime.bDSTEnable = 1
LocalTime.iDSTType = 2
LocalTime.TimeFormat = 1
LocalTime.DateFormat = 0
Network.eWANType = {{if enable_dhcp }}0{{else}}1{{endif}}

Network.strWANIP = {{static_ip}}
Network.strWANMask = {{static_mask}}
Network.strWanGateway = {{static_gateway}}
Network.strWanPrimaryDNS = {{static_dns1}}
Network.strWanSecondaryDNS = {{static_dns2}}
Network.strPPPoEUser = 
Network.strPPPoEPin = 
Network.bBridgeMode = 1
Network.strLanIP = 10.0.0.1
Network.strLanMask = 255.255.255.0
Network.strDHCPClientBegin = 10.0.0.10
Network.strDHCPClientEnd = 10.0.0.100

AutoProvision.bEnablePowerOn = 1
AutoProvision.strServerURL = {{server_ip}}
AutoProvision.strKeyAES16 = 
AutoProvision.strKeyAES16MAC = 
AutoProvision.strUser = 
AutoProvision.strPassword = 
