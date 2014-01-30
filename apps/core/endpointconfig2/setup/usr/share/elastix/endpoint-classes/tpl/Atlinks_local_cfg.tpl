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
TimeServer1 = {{server_ip}}
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

{{py:n = 1}}{{for extension in sip}}
[ memory1{{n}} ]
path= /config/vpPhone/vpPhone.ini
DKtype = 15
Line = {{n}}
type = 
Value = 
KEY_MODE = Asterisk
HotNumber = 
HotLineId = 1
Callpickup = 
IntercomId = -1
IntercomNumber = 
PickupValue = 
Label = 

{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts + 1)}}
[ memory1{{m}} ]
path= /config/vpPhone/vpPhone.ini
DKtype = 15
Line = {{m}}
type = 
Value = 
KEY_MODE = Asterisk
HotNumber = 
HotLineId = 1
Callpickup = 
IntercomId = -1
IntercomNumber = 
PickupValue = 
Label = 

{{endfor}}

[ RemotePhoneBook0 ]
path = /config/Setting/Setting.cfg
URL = {{phonesrv}}/internal
Name = Elastix Phonebook - Internal

[ RemotePhoneBook1 ]
path = /config/Setting/Setting.cfg
URL = {{phonesrv}}/external
Name = Elastix Phonebook - External

[ RemotePhoneBook2 ]
path = /config/Setting/Setting.cfg
URL = {{phonesrv}}/internal?name=#SEARCH
Name = Elastix Search - Internal

[ RemotePhoneBook3 ]
path = /config/Setting/Setting.cfg
URL = {{phonesrv}}/external?name=#SEARCH
Name = Elastix Search - External

[ Lang ]
path = /config/Setting/Setting.cfg
ActiveWebLanguage = {{language}}
WebLanguage = {{language}}
