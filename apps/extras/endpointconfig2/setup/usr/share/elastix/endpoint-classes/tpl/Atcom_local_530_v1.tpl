<<VOIP CONFIG FILE>>Version:{{version_cfg}}                            

<GLOBAL CONFIG MODULE>
SNTP Server        :{{server_ip}}
Enable SNTP        :1

{{if not enable_dhcp }}
Static Ip          :{{static_ip}}
Static NetMask     :{{static_mask}}
Static GateWay     :{{static_gateway}}
Primary DNS        :{{static_dns1}}
Alter DNS          :{{static_dns2}}

{{endif}}
Default Protocol   :{{default_protocol}}
DHCP Mode          :{{enable_dhcp}}
SNTP Time Zone     :{{time_zone}}

<LAN CONFIG MODULE>
Bridge Mode        :{{enable_bridge}}

<TELE CONFIG MODULE>
Dial End With #    :1
Dial Fixed Length  :0
Fixed Length       :11
Dial With Timeout  :1
Dial Timeout value :5
H323 Ras Location  :0
Poll Sequence      :0
Accept Any Call    :1
Phone Prefix       :
Local Area Code    :
{{if len(sip) > 0 }}
{{py:n = 1}}
--Phone Number--   :
{{for extension in sip}}
Item{{n}} Number       :{{extension.extension}}
Item{{n}} Count        :1
Item{{n}} Protocol     :{{n + 1}}
Item{{n}} Type         :{{2 * n - 1}}
Item{{n}} Alias        :
{{py:n += 1}}
{{endfor}}
--Number Index--   :
{{py:n = 1}}
{{for extension in sip}}
Port1 Num{{n}} Index   :{{n - 1}}
{{py:n += 1}}
{{endfor}}
{{endif}}
--Port Config--    :
P1 No Disturb      :0
P1 No Dial Out     :0
P1 No Empty Calling:0
P1 Enable CallerId :1
P1 Forward Service :0
P1 H323 TransNum   :
P1 H323 TransAddr  :
P1 H323 TransPort  :1720
P1 SIP TransNum    :
P1 SIP TransAddr   :
P1 SIP TransPort   :5060
P1 CallWaiting     :1
P1 CallTransfer    :1
P1 Call3Way        :1
P1 AutoAnswer      :0
P1 No Answer Time  :20
P1 Hotline Num     :

<DSP CONFIG MODULE>
Signal Standard    :1
Handdown Time      :200
G729 Payload Length:1
VAD                :0
Ring Type          :0
--Port Config--    :
P1 Output Vol      :9
P1 Input Vol       :3
P1 HandFree Vol    :9
P1 RingTone Vol    :1
P1 Codec           :17
P1 Voice Record    :0
P1 Record Playing  :1
P1 UserDef Voice   :0

<SIP CONFIG MODULE>
Register Address   :{{server_ip}}
Register Port      :5060
Register User      :{{if len(sip) > 0 }}{{sip[0].account}}{{endif}}
Register Password  :{{if len(sip) > 0 }}{{sip[0].secret}}{{endif}}
Register Expire    :60
Proxy Address      :{{server_ip}}
Proxy Port         :5060
Proxy User         :{{if len(sip) > 0 }}{{sip[0].account}}{{endif}}
Proxy Pass         :{{if len(sip) > 0 }}{{sip[0].secret}}{{endif}}
AlterReg Address   :
AlterReg Port      :5060
AlterReg User      :
AlterReg Pass      :
AlterReg Expire    :0
AlterProxy Address :
AlterProxy Port    :5060
AlterProxy User    :
AlterProxy Pass    :
PrivateReg Address :{{server_ip}}
PrivateReg Port    :5060
PrivateReg User    :{{if len(sip) > 1 }}{{sip[1].account}}{{endif}}
PrivateReg Pass    :{{if len(sip) > 1 }}{{sip[1].secret}}{{endif}}
PrivateReg Expire  :60
PrivateProxy Addr  :{{server_ip}}
PrivateProxy Port  :5060
PrivateProxy User  :{{if len(sip) > 1 }}{{sip[1].account}}{{endif}}
PrivateProxy Pass  :{{if len(sip) > 1 }}{{sip[1].secret}}{{endif}}
Local Sip Port     :5060
Local Domain       :{{server_ip}}
Private Domain     :{{server_ip}}
Enable Regisger    :{{if len(sip) > 0 }}1{{else}}0{{endif}}
Dtmf Mode          :0
Enable Stun        :0
Enable Private     :{{if len(sip) > 1 }}1{{else}}0{{endif}}
Stun Address       :
Stun Port          :3478
Stun Effect Time   :50
Rfc Version        :1
Enable PublicProxy :0
Enable PrivateProxy:0
Public User-Agent  :Voip Phone 1.0
Private User-Agent :Voip Phone 1.0
Public Server Type :0
Private Server Type:0
Enable ServerDetect:0
Server Detecet Time:60
Server Auto Swap   :0
Busy if gw no reg  :0
PRACK  100Rel      :1
Session Timer      :0
Auth   Header      :1
Support Via rport  :1
Single Audio Code  :0

<IAX2 CONFIG MODULE>
Server   Address   :{{server_ip}}
Server   Port      :4569
User     Name      :{{if iax2}}{{iax2.account}}{{endif}}
User     Password  :{{if iax2}}{{iax2.secret}}{{endif}}
User     Number    :{{if iax2}}{{iax2.extension}}{{endif}}
Voice    Number    :0
Voice    Text      :mail
EchoTest Number    :1
EchoTest Text      :echo
Local    Port      :4569
Enable   Register  :{{if iax2}}1{{else}}0{{endif}}
Refresh  Time      :60
Enable   G.729     :0

<AUTOUPDATE CONFIG MODULE>
Download Username  :user
Download password  :pass
Download Server IP :{{server_ip}}
Config File Name   :{{config_filename}}
Config File Key    :
Download Protocol  :2
Download Mode      :1
Download Interval  :1
<<END OF FILE>>