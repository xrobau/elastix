[ autop_mode ]
path = /yealink/config/Setting/autop.cfg
mode = 1

[ WAN ]
path=/yealink/config/Network/Network.cfg
#WANType:0:DHCP,1:PPPoE,2:StaticIP
{if $ENABLE_DHCP}
WANType = 0
{else}
WANType = 2

WANStaticIP       ={$STATIC_IP}
WANSubnetMask     ={$STATIC_MASK}
WANDefaultGateway ={$STATIC_GATEWAY}

[ DNS ]
path=/yealink/config/Network/Network.cfg
PrimaryDNS   = {$STATIC_DNS1}
SecondaryDNS = {$STATIC_DNS2}
{/if}

[ LAN ]
path=/yealink/config/Network/Network.cfg
#LANTYPE:0:Router, 1:Bridge
LANTYPE = {$ENABLE_BRIDGE}

[ account ]
path=/yealink/config/voip/sipAccount0.cfg
Enable = 1
Label = {$DISPLAY_NAME}
DisplayName = {$DISPLAY_NAME}
UserName = {$ID_DEVICE}
AuthName = {$ID_DEVICE}
password = {$SECRET}
SIPServerHost = {$SERVER_IP}
Expire = 60

[ autoprovision ]
path = /yealink/config/Setting/autop.cfg
server_type = tftp
server_address = {$SERVER_IP}
user =
password =

[ Time ] 
path = /yealink/config/Setting/Setting.cfg 
TimeZone = {$TIME_ZONE} 
TimeServer1 = {$SERVER_IP}
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
server_address = {$SERVER_IP}/contactData1.xml

[ RemotePhoneBook0 ]
path = /yealink/config/Setting/Setting.cfg
URL = tftp://{$SERVER_IP}/phonebook.xml
Name = Directory

[ memory11 ]
path = /yealink/config/vpPhone/vpPhone.ini
DKtype = 15
Line = 1
Value =  
type = 

[ memory12 ]
path = /yealink/config/vpPhone/vpPhone.ini
DKtype = 15 
Line = 1
Value =
type = 

[ memory13 ]
path = /yealink/config/vpPhone/vpPhone.ini
DKtype = 15
Line = 1
Value = 
type = 

[ memory14 ]
path = /yealink/config/vpPhone/vpPhone.ini
DKtype = 15
Line = 1
Value = 
type = 

[ memory15 ]
path = /yealink/config/vpPhone/vpPhone.ini
DKtype = 15
Line =
Value = 
type = 

[ memory16 ]
path = /yealink/config/vpPhone/vpPhone.ini
DKtype = 15
Line = 1
Value =
type =
