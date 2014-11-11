<<VOIP CONFIG FILE>>Version:{{version_cfg}}                            

<GLOBAL CONFIG MODULE>
WAN Mode           :{{if enable_dhcp}}DHCP{{else}}STATIC{{endif}}
{{if not enable_dhcp }}
WAN IP             :{{static_ip}}
WAN Subnet Mask    :{{static_mask}}
WAN Gateway        :{{static_gateway}}
Primary DNS        :{{static_dns1}}
Secondary DNS      :{{static_dns2}}
{{endif}}
Enable DHCP        :{{enable_dhcp}}

Default Protocol   :{{default_protocol}}
Time Zone          :{{time_zone}}

<LAN CONFIG MODULE>
Enable Bridge Mode :{{enable_bridge}}

<DSP CONFIG MODULE>
Signal Standard    :11
Onhook Time        :200
G729 Payload Len   :1
G723 Bit Rate      :1
G722 Timestamps    :0
VAD                :0
Ring Type          :1
Dtmf Payload Type  :101
RTP Probe          :0
--Port Config--    :
P1 General Spk Vol :5
P1 General Mic Vol :3
P1 Headset Vol     :5
P1 Ring in Headset :0
P1 HandFree Vol    :5
P1 RingTone Vol    :5
P1 Voice Codec1    :0
P1 Voice Codec2    :1
P1 Voice Codec3    :15
P1 Voice Codec4    :9
P1 Voice Codec5    :23
P1 Voice Codec6    :17

<SIP CONFIG MODULE>
SIP  Port          :5060
STUN Server        :
STUN Port          :3478
STUN Refresh Time  :50
SIP Wait Stun Time :800
Extern NAT Addrs   :
Reg Fail Interval  :32
Strict BranchPrefix:0
Video Mute Attr    :0
Enable Group Backup:0
{{py:n = 1}}
--SIP Line List--  :{{for extension in sip}}
SIP{{n}} Phone Number  :{{extension.extension}}
SIP{{n}} Display Name  :{{extension.description}}
SIP{{n}} Sip Name      :{{extension.account}}
SIP{{n}} Register Addr :{{server_ip}}
SIP{{n}} Register Port :5060
SIP{{n}} Register User :{{extension.account}}
SIP{{n}} Register Pswd :{{extension.secret}}
SIP{{n}} Register TTL  :3600
SIP{{n}} Enable Reg    :1
SIP{{n}} Proxy Addr    :{{server_ip}}
SIP{{n}} Proxy Port    :5060
SIP{{n}} BakProxy Addr :
SIP{{n}} BakProxy Port :5060
SIP{{n}} Signal Crypto :0
SIP{{n}} SigCrypto Key :
SIP{{n}} Media Crypto  :0
SIP{{n}} MedCrypto Key :
SIP{{n}} SRTP Auth-Tag :0
SIP{{n}} Local Domain  :
SIP{{n}} FWD Type      :0
SIP{{n}} FWD Number    :
SIP{{n}} FWD Timer     :60
SIP{{n}} Ring Type     :0
SIP{{n}} Hotline Num   :
SIP{{n}} Enable Hotline:0
SIP{{n}} WarmLine Time :0
SIP{{n}} NAT UDPUpdate :1
SIP{{n}} UDPUpdate TTL :60
SIP{{n}} Server Type   :0
SIP{{n}} User Agent    :
SIP{{n}} PRACK         :0
SIP{{n}} Keep AUTH     :0
SIP{{n}} Session Timer :0
SIP{{n}} S.Timer Expire:0
SIP{{n}} Enable GRUU   :0
SIP{{n}} DTMF Mode     :1
SIP{{n}} DTMF Info Mode:0
SIP{{n}} NAT Type      :0
SIP{{n}} Enable Rport  :0
SIP{{n}} Subscribe     :0
SIP{{n}} Sub Expire    :3600
SIP{{n}} Single Codec  :0
SIP{{n}} CLIR          :0
SIP{{n}} Strict Proxy  :0
SIP{{n}} Direct Contact:0
SIP{{n}} History Info  :0
SIP{{n}} DNS SRV       :0
SIP{{n}} XFER Expire   :0
SIP{{n}} Ban Anonymous :0
SIP{{n}} Dial Off Line :0
SIP{{n}} Quota Name    :0
SIP{{n}} Presence Mode :0
SIP{{n}} RFC Ver       :1
SIP{{n}} Signal Port   :0
SIP{{n}} Transport     :0
SIP{{n}} Use SRV Mixer :0
SIP{{n}} SRV Mixer Uri :
SIP{{n}} Long Contact  :0
SIP{{n}} Auto TCP      :0
SIP{{n}} Uri Escaped   :1
SIP{{n}} Click to Talk :0
SIP{{n}} MWI Num       :*97
SIP{{n}} CallPark Num  :
SIP{{n}} MSRPHelp Num  :
SIP{{n}} User Is Phone :1
SIP{{n}} Auto Answer   :0
SIP{{n}} NoAnswerTime  :60
SIP{{n}} MissedCallLog :1
SIP{{n}} SvcCode Mode  :0
SIP{{n}} DNDOn SvcCode :
SIP{{n}} DNDOff SvcCode:
SIP{{n}} CFUOn SvcCode :
SIP{{n}} CFUOff SvcCode:
SIP{{n}} CFBOn SvcCode :
SIP{{n}} CFBOff SvcCode:
SIP{{n}} CFNOn SvcCode :
SIP{{n}} CFNOff SvcCode:
SIP{{n}} ANCOn SvcCode :
SIP{{n}} ANCOff SvcCode:
SIP{{n}} VoiceCodecMap :G711A,G711U,G722,G723,G726-32,G729
SIP{{n}} BLFList Uri   :
SIP{{n}} Enable BLFList:0
SIP{{n}} Caller Id Type:1{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}
SIP{{m}} Phone Number  :
SIP{{m}} Display Name  :
SIP{{m}} Sip Name      :
SIP{{m}} Register Addr :{{server_ip}}
SIP{{m}} Register Port :5060
SIP{{m}} Register User :
SIP{{m}} Register Pswd :
SIP{{m}} Register TTL  :3600
SIP{{m}} Enable Reg    :0
SIP{{m}} Proxy Addr    :{{server_ip}}
SIP{{m}} Proxy Port    :5060
SIP{{m}} BakProxy Addr :
SIP{{m}} BakProxy Port :5060
SIP{{m}} Signal Crypto :0
SIP{{m}} SigCrypto Key :
SIP{{m}} Media Crypto  :0
SIP{{m}} MedCrypto Key :
SIP{{m}} SRTP Auth-Tag :0
SIP{{m}} Local Domain  :
SIP{{m}} FWD Type      :0
SIP{{m}} FWD Number    :
SIP{{m}} FWD Timer     :60
SIP{{m}} Ring Type     :0
SIP{{m}} Hotline Num   :
SIP{{m}} Enable Hotline:0
SIP{{m}} WarmLine Time :0
SIP{{m}} NAT UDPUpdate :1
SIP{{m}} UDPUpdate TTL :60
SIP{{m}} Server Type   :0
SIP{{m}} User Agent    :
SIP{{m}} PRACK         :0
SIP{{m}} Keep AUTH     :0
SIP{{m}} Session Timer :0
SIP{{m}} S.Timer Expire:0
SIP{{m}} Enable GRUU   :0
SIP{{m}} DTMF Mode     :1
SIP{{m}} DTMF Info Mode:0
SIP{{m}} NAT Type      :0
SIP{{m}} Enable Rport  :0
SIP{{m}} Subscribe     :0
SIP{{m}} Sub Expire    :3600
SIP{{m}} Single Codec  :0
SIP{{m}} CLIR          :0
SIP{{m}} Strict Proxy  :0
SIP{{m}} Direct Contact:0
SIP{{m}} History Info  :0
SIP{{m}} DNS SRV       :0
SIP{{m}} XFER Expire   :0
SIP{{m}} Ban Anonymous :0
SIP{{m}} Dial Off Line :0
SIP{{m}} Quota Name    :0
SIP{{m}} Presence Mode :0
SIP{{m}} RFC Ver       :1
SIP{{m}} Signal Port   :0
SIP{{m}} Transport     :0
SIP{{m}} Use SRV Mixer :0
SIP{{m}} SRV Mixer Uri :
SIP{{m}} Long Contact  :0
SIP{{m}} Auto TCP      :0
SIP{{m}} Uri Escaped   :1
SIP{{m}} Click to Talk :0
SIP{{m}} MWI Num       :*97
SIP{{m}} CallPark Num  :
SIP{{m}} MSRPHelp Num  :
SIP{{m}} User Is Phone :1
SIP{{m}} Auto Answer   :0
SIP{{m}} NoAnswerTime  :60
SIP{{m}} MissedCallLog :1
SIP{{m}} SvcCode Mode  :0
SIP{{m}} DNDOn SvcCode :
SIP{{m}} DNDOff SvcCode:
SIP{{m}} CFUOn SvcCode :
SIP{{m}} CFUOff SvcCode:
SIP{{m}} CFBOn SvcCode :
SIP{{m}} CFBOff SvcCode:
SIP{{m}} CFNOn SvcCode :
SIP{{m}} CFNOff SvcCode:
SIP{{m}} ANCOn SvcCode :
SIP{{m}} ANCOff SvcCode:
SIP{{m}} VoiceCodecMap :G711A,G711U,G722,G723,G726-32,G729
SIP{{m}} BLFList Uri   :
SIP{{m}} Enable BLFList:0
SIP{{m}} Caller Id Type:1{{endfor}}


<IAX2 CONFIG MODULE>
Server Address     :{{server_ip}}
Server Port        :4569
User Name          :{{if iax2}}{{iax2.account}}{{endif}}
User Password      :{{if iax2}}{{iax2.secret}}{{endif}}
User Number        :{{if iax2}}{{iax2.extension}}{{endif}}
Voice Number       :*97
Voice Text         :*97
EchoTest Number    :1
EchoTest Text      :echo
Local Port         :4569
Enable Register    :{{if iax2}}1{{else}}0{{endif}}
Refresh Time       :60
Enable G.729       :0


<DEBUG CONFIG MODULE>
MGR Trace Level    :0
SIP Trace Level    :0
IAX Trace Level    :0
Trace File Info    :0


<AUTOUPDATE CONFIG MODULE>
Download Server IP :{{server_ip}}
Config File Name   :{{config_filename}}
Config File Key    :
Common Cfg File Key:
Download Protocol  :2
Download Mode      :1
Download Interval  :1
PNP Enable         :0

<<END OF FILE>>
