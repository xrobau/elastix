[WAN_Config]
ITEM=19 
CurrentIP={{static_ip}}
CurrentGateway={{static_gateway}}
CurrentNetMask={{static_mask}}
#0:DHCP, 1:Static, 2:PPPoE 
NetworkMode={{if enable_dhcp}}0{{else}}1{{endif}} 
DeviceName=Damall D-3310 
DomainName= 
DNSdomain= 
PrimaryDNS={{static_dns1}}
SecondrayDNS={{static_dns2}}
AlterDNS= 
StaticIP={{static_ip}}
SubnetMask={{static_mask}}
DefaultGateway={{static_gateway}}
UserAccount=
Password= 
LcpEchoInterval= 
ISPName= 
#0:PAP, 1:CHAP
AuthType=0 


[LAN_Config] 
ITEM=7 
BridgeMode={{enable_bridge}} 
DHCPServer=1 
ForwardDNS=1 
StartIP=2 
EndIP=19 

[Primary_Register]
ITEM=26
#0:Unregistered; 1:registered
Registered={{if len(sip) > 0 }}1{{else}}0{{endif}}
Enable={{if len(sip) > 0 }}1{{else}}0{{endif}}
DisplayName={{if len(sip) > 0 }}{{sip[0].description}}{{endif}}
ServerAddress={{server_ip}}
ServerPort=5060
UserName1={{if len(sip) > 0 }}{{sip[0].extension}}{{endif}}
Password1={{if len(sip) > 0 }}{{sip[0].secret}}{{endif}}
AuthUserName={{if len(sip) > 0 }}{{sip[0].account}}{{endif}}
DomainRealm=
SameServer=
EnableProxy=1
ProxyAddress=
ProxyPort=5060
UserName2=
Password2=
Version=RFC 3261
#0:RFC 2833; 1:Inband; 2:SIP Info
DTMFMode=0
UserAgent=Damall D-3310
DetectInterval=60
RegisterExpire=60
LocalSIPPort=5060
LocalRTPPort=12345
RegisterTime=60
RTPPort=10000
RTPQuantity=200
NatKeepAlive=0
PRACKEnable=0
Register_time=2

[Secondary_Register]
ITEM=21
#0:Unregistered; 1:registered
Registered={{if len(sip) > 1 }}1{{else}}0{{endif}}
Enable={{if len(sip) > 1 }}1{{else}}0{{endif}}
DisplayName={{if len(sip) > 1 }}{{sip[1].description}}{{endif}}
ServerAddress={{server_ip}}
ServerPort=5060
UserName1={{if len(sip) > 1 }}{{sip[1].account}}{{endif}}
Password1={{if len(sip) > 1 }}{{sip[1].secret}}{{endif}}
AuthUserName={{if len(sip) > 1 }}{{sip[1].account}}{{endif}}
DomainRealm=
SameServer=
EnableProxy=1
ProxyAddress=
ProxyPort=5060
UserName2=
Password2=
Version=RFC 3261
#0:RFC 2833; 1:Inband; 2:SIP Info
DTMFMode=0
UserAgent=
DetectInterval=120
RegisterExpire=120
LocalSIPPort=5060
LocalRTPPort=12345

[Audio_Config]
ITEM=27
MEMBER=2
OTHERS=27
RingerVolume=2
RingerType=5
VoiceVolume=4
MicVolume=4
HandsetIn=5
HandsetOut=5
Speaker=2
RingTone=2
#0:default
Ringer=6
AudioFrame=4
#0:G.729; 1:G.711a; 2:G.711u; 3:G.723.1
Codec#1=0
Codec#2=1
Codec#3=2
Codec#4=3
Codec#5=0
HighRate=1
VAD=0
AGC=1
AEC=1
#0:Italy,1:Belgium;2:China;3:Israel;4:Japan;5:Netherlands;6:Norway;
#7:South Korea;8:Sweden;9:Switzerland;10:Taiwan;11:United States
SignalStandard=0
#0:10ms,1:20ms,2:30ms,3:40ms,4:50ms,5:60ms
G729Payload=2
InputVolume=4
OutputVolume=4
HandfreeVolume=4
RingVolume=3
HanddwonTime=200
SRTP=0

[Time_Config]
ITEM=13
Timezone={{time_zone}}
ServerAddress=209.81.9.7
ServerPort=21
PollingInterval=300
#0: 0:00; 1: -0:30; 2: -1:00; 3: +0:30; 4: +1:00
DaylightSaving=0
timeout=60
DaylightEnable=0
SelectSNTP=1
TIM_ManualYear=
TIM_ManualMonths=
TIM_ManualDay=
TIM_ManualHour=
TIM_ManualMinute=  

[My_Config]
ITEM=8
#0:ftp; 1:tftp
ServerType=1
ServerAddress={{server_ip}}
UserName=
Password=
Contrast=5
Brightness=1
LCDlogo=
FileName={{config_filename}}

[Auto_Provisioning]
ITEM=17
#0: disable; 1: enable
EnableAutoConfig=1
ProfileRule1=
#0: disable; 1: enable
EnableAutoUpdate=1
ProfileRule2=
#0: power on; 1: scheduling
CheckFirmwareMode=
#1~30days
CheckFirmwareDate=
#0: AM 00:00- 05:59; 1: AM 06:00- 11:59; 2: PM 12:00- 17:59; 3: PM 18:00- 23:59
CheckFirmwareTime=
#0: notify only; 1: automatic
UpdateFirmwareMode=
SoftVersion=
UpdateMode=1
#0:http 1:tftp
ServerType=1
ServerAddress={{server_ip}}
UserName=
Password=
FileName={{config_filename}}
IntervalTime=
AutoProvisionServer=tftp://{{server_ip}}
