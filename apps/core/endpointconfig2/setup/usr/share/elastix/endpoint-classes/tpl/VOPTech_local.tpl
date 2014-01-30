<<VOIP CONFIG FILE>>Version:{{version_cfg}}                            

<GLOBAL CONFIG MODULE>
Wan Mode           :{{if enable_dhcp}}DHCP{{else}}STATIC{{endif}}
{{if enable_dhcp }}
DHCP Mode          :1
DHCP Dns           :1
{{else}}
Static IP          :{{static_ip}}
Static NetMask     :{{static_mask}}
Static GateWay     :{{static_gateway}}
Primary DNS        :{{static_dns1}}
Alter DNS          :{{static_dns2}}
DHCP Mode          :0
DHCP Dns           :0
{{endif}}

Default Protocol   :{{default_protocol}}
Time Zone          :{{time_zone}}

<LAN CONFIG MODULE>
Bridge Mode        :{{enable_bridge}}

<DSP CONFIG MODULE>
Signal Standard    :11
Handdown Time      :200
G729 Payload Length:1
G723 Bit Rate      :1
G722 Timestamps    :0
VAD                :0
Ring Type          :1
Dtmf Payload Type  :101
RTP PROBE          :0

<SIP CONFIG MODULE>
SIP  Port          :5060
Stun Address       :
Stun Port          :3478
Stun Effect Time   :50
Extern Address     :
Reg Retry Time     :32
Strict BranchPrefix:0
{{py:n = 1}}
--SIP Line List--  :{{for extension in sip}}
SIP{{n}} Phone Number  :{{extension.extension}}
SIP{{n}} Display Name  :{{extension.description}}
SIP{{n}} Sip Name      :{{extension.account}}
SIP{{n}} Register Addr :{{server_ip}}
SIP{{n}} Register Port :5060
SIP{{n}} Register User :{{extension.account}}
SIP{{n}} Register Pwd  :{{extension.secret}}
SIP{{n}} Register TTL  :3600
SIP{{n}} Enable Reg    :1
SIP{{n}} Proxy Addr    :{{server_ip}}
SIP{{n}} Proxy Port    :5060
SIP{{n}} Signal Enc    :0
SIP{{n}} Signal Key    :
SIP{{n}} Media Enc     :0
SIP{{n}} Media Key     :
SIP{{n}} Local Domain  :
SIP{{n}} Fwd Service   :0
SIP{{n}} Fwd Number    :
SIP{{n}} Ring Type     :0
SIP{{n}} Hotline Number:
SIP{{n}} Server Type   :0
SIP{{n}} User Agent    :
SIP{{n}} PRACK         :0
SIP{{n}} KEEP AUTH     :0
SIP{{n}} Session Timer :0
SIP{{n}} Gruu          :0
SIP{{n}} DTMF Mode     :1
SIP{{n}} DTMF SIP-INFO :0
SIP{{n}} Subscribe     :0
SIP{{n}} Sub Expire    :3600
SIP{{n}} Single Codec  :0
SIP{{n}} CLIR          :0
SIP{{n}} Strict Proxy  :0
SIP{{n}} Direct Contact:0
SIP{{n}} History Info  :0
SIP{{n}} DNS SRV       :0
SIP{{n}} Transfer Expir:0
SIP{{n}} Ban Anonymous :0
SIP{{n}} Dial Without R:0
SIP{{n}} DisplayName Qu:0
SIP{{n}} Presence Mode :0
SIP{{n}} RFC Ver       :1
SIP{{n}} Signal Port   :0
SIP{{n}} Transport     :0
SIP{{n}} Use Mixer     :0
SIP{{n}} Mixer Uri     :
SIP{{n}} Long Contact  :0
SIP{{n}} Auto TCP      :0
SIP{{n}} Click to Talk :0
SIP{{n}} Mwi No.       :*97
SIP{{n}} Park No.      :
SIP{{n}} Help No.      :
SIP{{n}} Use user=phone:1{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}
SIP{{m}} Phone Number  :
SIP{{m}} Display Name  :
SIP{{m}} Sip Name      :
SIP{{m}} Register Addr :{{server_ip}}
SIP{{m}} Register Port :5060
SIP{{m}} Register User :
SIP{{m}} Register Pwd  :
SIP{{m}} Register TTL  :3600
SIP{{m}} Enable Reg    :0
SIP{{m}} Proxy Addr    :{{server_ip}}
SIP{{m}} Proxy Port    :5060
SIP{{m}} Signal Enc    :0
SIP{{m}} Signal Key    :
SIP{{m}} Media Enc     :0
SIP{{m}} Media Key     :
SIP{{m}} Local Domain  :
SIP{{m}} Fwd Service   :0
SIP{{m}} Fwd Number    :
SIP{{m}} Ring Type     :0
SIP{{m}} Hotline Number:
SIP{{m}} Server Type   :0
SIP{{m}} User Agent    :
SIP{{m}} PRACK         :0
SIP{{m}} KEEP AUTH     :0
SIP{{m}} Session Timer :0
SIP{{m}} Gruu          :0
SIP{{m}} DTMF Mode     :1
SIP{{m}} DTMF SIP-INFO :0
SIP{{m}} Subscribe     :0
SIP{{m}} Sub Expire    :3600
SIP{{m}} Single Codec  :0
SIP{{m}} CLIR          :0
SIP{{m}} Strict Proxy  :0
SIP{{m}} Direct Contact:0
SIP{{m}} History Info  :0
SIP{{m}} DNS SRV       :0
SIP{{m}} Transfer Expir:0
SIP{{m}} Ban Anonymous :0
SIP{{m}} Dial Without R:0
SIP{{m}} DisplayName Qu:0
SIP{{m}} Presence Mode :0
SIP{{m}} RFC Ver       :1
SIP{{m}} Signal Port   :0
SIP{{m}} Transport     :0
SIP{{m}} Use Mixer     :0
SIP{{m}} Mixer Uri     :
SIP{{m}} Long Contact  :0
SIP{{m}} Auto TCP      :0
SIP{{m}} Click to Talk :0
SIP{{m}} Mwi No.       :*97
SIP{{m}} Park No.      :
SIP{{m}} Help No.      :
SIP{{m}} Use user=phone:1{{endfor}}


<IAX2 CONFIG MODULE>
Server   Address   :{{server_ip}}
Server   Port      :4569
User     Name      :{{if iax2}}{{iax2.account}}{{endif}}
User     Password  :{{if iax2}}{{iax2.secret}}{{endif}}
User     Number    :{{if iax2}}{{iax2.extension}}{{endif}}
Voice    Number    :*97
Voice    Text      :*97
EchoTest Number    :1
EchoTest Text      :echo
Local    Port      :4569
Enable   Register  :{{if iax2}}1{{else}}0{{endif}}
Refresh  Time      :60
Enable   G.729     :0


<DEBUG CONFIG MODULE>
MGR Trace Level    :0
SIP Trace Level    :0
IAX Trace Level    :0
Trace File Info    :0
DHCP Option 66     :1


<AUTOUPDATE CONFIG MODULE>
Download Server IP :{{server_ip}}
Config File Name   :{{config_filename}}
Config File Key    :
Config File Key    :
Download Protocol  :2
Download Mode      :1
Download Interval  :1

<<END OF FILE>>
