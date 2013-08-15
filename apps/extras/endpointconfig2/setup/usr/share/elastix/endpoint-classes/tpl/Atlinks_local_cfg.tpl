[ WAN ]
path=/config/Network/Network.cfg
#WANType:0:DHCP,1:PPPoE,2:StaticIP
{{if enable_dhcp}}
WANType = 0
{{else}}
WANType = 2

WANStaticIP       ={{static_ip}}
WANSubnetMask     ={{static_mask}}
WANDefaultGateway ={{static_gateway}}

[ DNS ]
path=/config/Network/Network.cfg
PrimaryDNS   = {{static_dns1}}
SecondaryDNS = {{static_dns1}}
{{endif}}

[ LAN ]
path=/config/Network/Network.cfg
#LANTYPE:0:Router, 1:Bridge
LANTYPE = {{enable_bridge}}

[ Time ]
path = /config/Setting/Setting.cfg
TimeZone = {{time_zone}}
TimeServer1 = europe.pool.ntp.org
TimeServer2 = europe.pool.ntp.org
Interval = 1000
SummerTime = 2
DSTTimeType = 0
TimeZoneInstead = 8
StartTime = 1/1/0
EndTime = 12/31/23
TimeFormat = 1
DateFormat = 6
OffSetTime = 60
DHCPTime = 0

[ autoprovision ]
path = /config/Setting/autop.cfg
server_address = tftp://{{server_ip}}
user = 
password = 

{{py:n = 0}}{{for extension in sip}}
[ account ]
path= /config/voip/sipAccount{{n}}.cfg
Enable = 1
Label = {{extension.description}}
DisplayName = {{extension.description}}
UserName = {{extension.extension}}
AuthName = {{extension.account}}
password = {{extension.secret}}
SIPServerHost = {{server_ip}}

{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts)}}
[ account ]
path= /config/voip/sipAccount{{m}}.cfg
Enable = 0
Label = 
DisplayName = 
UserName = 
AuthName = 
password = 
SIPServerHost = {{server_ip}}

{{endfor}}
