<<VOIP CONFIG FILE>>Version:{{version_cfg}}                         

<GLOBAL CONFIG MODULE>
SNTP Server        :{{server_ip}}
Enable SNTP        :1

{{if not enable_dhcp }}
Static IP          :{{static_ip}}
Static NetMask     :{{static_mask}}
Static GateWay     :{{static_gateway}}
Primary DNS        :{{static_dns1}}
Secundary DNS      :{{static_dns2}}

{{endif}}
Default Protocol   :{{default_protocol}}
DHCP Mode          :{{enable_dhcp}}
Time Zone          :{{time_zone}}

<LAN CONFIG MODULE>
Bridge Mode        :{{enable_bridge}}

<TELE CONFIG MODULE>
Dial End With #    :1
Dial Fixed Length  :0
Fixed Length       :11
Dial With Timeout  :1
Dial Timeout value :5

<DSP CONFIG MODULE>
VAD                :0
Ring Type          :1
--Port Config--    :
P1 Codec           :1

<SIP CONFIG MODULE>
SIP  Port          :5060
Stun Address       :
Stun Port          :3478
Stun Effect Time   :50
SIP  Differv       :0
DTMF Mode          :1
Extern Address     :
Url Convert        :0
{{py:n = 1}}
--SIP Line List--  :{{for extension in sip}}
SIP{{n}} Phone Number  :{{extension.extension}}
SIP{{n}} Display Name  :{{extension.description}}
SIP{{n}} Register Addr :{{server_ip}}
SIP{{n}} Register Port :5060
SIP{{n}} Register User :{{extension.account}}
SIP{{n}} Register Pwd  :{{extension.secret}}
SIP{{n}} Register TTL  :60
SIP{{n}} Enable Reg    :1
SIP{{n}} Proxy Addr    :{{server_ip}}
SIP{{n}} Proxy Port    :5060
SIP{{n}} Proxy User    :{{extension.account}}
SIP{{n}} Proxy Pwd     :{{extension.secret}}
SIP{{n}} Signal Enc    :0
SIP{{n}} Signal Key    :
SIP{{n}} Media Enc     :0
SIP{{n}} Media Key     :
SIP{{n}} Local Domain  :
SIP{{n}} Fwd Service   :0
SIP{{n}} Fwd Number    :
SIP{{n}} Enable Detect :0
SIP{{n}} Detect TTL    :60
SIP{{n}} Server Type   :0
SIP{{n}} User Agent    :Voip Phone 1.0
SIP{{n}} PRACK         :0
SIP{{n}} KEEP AUTH     :0
SIP{{n}} Session Timer :0
SIP{{n}} DTMF Mode     :1
SIP{{n}} Use Stun      :0
SIP{{n}} Via Port      :1
SIP{{n}} Subscribe     :0
SIP{{n}} Sub Expire    :300
SIP{{n}} Single Codec  :0
SIP{{n}} CLIR          :0
SIP{{n}} RFC Ver       :1
SIP{{n}} Use Mixer     :0
SIP{{n}} Mixer Uri     :{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}
SIP{{m}} Phone Number  :
SIP{{m}} Display Name  :
SIP{{m}} Register Addr :{{server_ip}}
SIP{{m}} Register Port :5060
SIP{{m}} Register User :
SIP{{m}} Register Pwd  :
SIP{{m}} Register TTL  :60
SIP{{m}} Enable Reg    :0
SIP{{m}} Proxy Addr    :{{server_ip}}
SIP{{m}} Proxy Port    :5060
SIP{{m}} Proxy User    :
SIP{{m}} Proxy Pwd     :
SIP{{m}} Signal Enc    :0
SIP{{m}} Signal Key    :
SIP{{m}} Media Enc     :0
SIP{{m}} Media Key     :
SIP{{m}} Local Domain  :
SIP{{m}} Fwd Service   :0
SIP{{m}} Fwd Number    :
SIP{{m}} Enable Detect :0
SIP{{m}} Detect TTL    :60
SIP{{m}} Server Type   :0
SIP{{m}} User Agent    :Voip Phone 1.0
SIP{{m}} PRACK         :0
SIP{{m}} KEEP AUTH     :0
SIP{{m}} Session Timer :0
SIP{{m}} DTMF Mode     :1
SIP{{m}} Use Stun      :0
SIP{{m}} Via Port      :1
SIP{{m}} Subscribe     :0
SIP{{m}} Sub Expire    :300
SIP{{m}} Single Codec  :0
SIP{{m}} CLIR          :0
SIP{{m}} RFC Ver       :1
SIP{{m}} Use Mixer     :0
SIP{{m}} Mixer Uri     :{{endfor}}

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