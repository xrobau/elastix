[ autop_mode ]
path = /yealink/config/Setting/autop.cfg
mode = 1

[ WAN ]
path=/yealink/config/Network/Network.cfg
#WANType:0:DHCP,1:PPPoE,2:StaticIP
{{if enable_dhcp}}
WANType = 0
{{else}}
WANType = 2

WANStaticIP       ={{static_ip}}
WANSubnetMask     ={{static_mask}}
WANDefaultGateway ={{static_gateway}}

[ DNS ]
path=/yealink/config/Network/Network.cfg
PrimaryDNS   = {{static_dns1}}
SecondaryDNS = {{static_dns2}}
{{endif}}

[ LAN ]
path=/yealink/config/Network/Network.cfg
#LANTYPE:0:Router, 1:Bridge
LANTYPE = {{enable_bridge}}

{{py:n = 0}}{{for extension in sip}}
[ account ]
path=/yealink/config/voip/sipAccount{{n}}.cfg
Enable = 1
Label = {{extension.extension}}
DisplayName = {{extension.description}}
UserName = {{extension.account}}
AuthName = {{extension.account}}
password = {{extension.secret}}
SIPServerHost = {{server_ip}}
Expire = 60
{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts)}}
[ account ]
path=/yealink/config/voip/sipAccount{{m}}.cfg
Enable = 0
Label = 
DisplayName = 
UserName = 
AuthName = 
password = 
SIPServerHost = {{server_ip}}
Expire = 60
{{endfor}}

[ autoprovision ]
path = /yealink/config/Setting/autop.cfg
server_type = tftp
server_address = {{server_ip}}
user =
password =

[ Time ] 
path = /yealink/config/Setting/Setting.cfg 
TimeZone = {{time_zone}}
TimeServer1 = {{server_ip}}
TimeServer2 = 0.pool.ntp.org 
Interval = 300 
SummerTime = 0

[ PhoneSetting ]
path = /yealink/config/Setting/Setting.cfg
Manual_Time = 0


[ Lang ]
path = /yealink/config/Setting/Setting.cfg
ActiveWebLanguage = Spanish


[ ContactList ]
path = /tmp/download.cfg
server_address = {{server_ip}}/contactData1.xml

[ RemotePhoneBook0 ]
path = /yealink/config/Setting/Setting.cfg
URL = {{phonesrv}}/internal
Name = Elastix Phonebook - Internal

[ RemotePhoneBook1 ]
path = /yealink/config/Setting/Setting.cfg
URL = {{phonesrv}}/external
Name = Elastix Phonebook - External

[ RemotePhoneBook2 ]
path = /yealink/config/Setting/Setting.cfg
URL = {{phonesrv}}/internal?name=#SEARCH
Name = Elastix Search - Internal

[ RemotePhoneBook3 ]
path = /yealink/config/Setting/Setting.cfg
URL = {{phonesrv}}/external?name=#SEARCH
Name = Elastix Search - External

