<<VOIP CONFIG FILE>>Version:{$VERSION_CFG}                         

<GLOBAL CONFIG MODULE>
SNTP Server        :{$SERVER_IP}
Enable SNTP        :1

{if $ENABLE_DHCP == 0}
Static IP          :{$STATIC_IP}
Static NetMask     :{$STATIC_MASK}
Static GateWay     :{$STATIC_GATEWAY}
Primary DNS        :{$STATIC_DNS1}
Secundary DNS      :{$STATIC_DNS2}

{/if}
DHCP Mode          :{$ENABLE_DHCP}
Time Zone          :{$TIME_ZONE}

<LAN CONFIG MODULE>
Bridge Mode        :{$ENABLE_BRIDGE}


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
--SIP Line List--  :
SIP1 Phone Number  :{$ID_DEVICE}
SIP1 Display Name  :{$DISPLAY_NAME}
SIP1 Register Addr :{$SERVER_IP}
SIP1 Register Port :5060
SIP1 Register User :{$ID_DEVICE}
SIP1 Register Pwd  :{$SECRET}
SIP1 Register TTL  :60
SIP1 Enable Reg    :1
SIP1 Proxy Addr    :{$SERVER_IP}
SIP1 Proxy Port    :5060
SIP1 Proxy User    :{$ID_DEVICE}
SIP1 Proxy Pwd     :{$SECRET}
SIP1 Signal Enc    :0
SIP1 Signal Key    :
SIP1 Media Enc     :0
SIP1 Media Key     :
SIP1 Local Domain  :
SIP1 Fwd Service   :0
SIP1 Fwd Number    :
SIP1 Enable Detect :0
SIP1 Detect TTL    :60
SIP1 Server Type   :0
SIP1 User Agent    :Voip Phone 1.0
SIP1 PRACK         :0
SIP1 KEEP AUTH     :0
SIP1 Session Timer :0
SIP1 DTMF Mode     :1
SIP1 Use Stun      :0
SIP1 Via Port      :1
SIP1 Subscribe     :0
SIP1 Sub Expire    :300
SIP1 Single Codec  :0
SIP1 CLIR          :0
SIP1 RFC Ver       :1
SIP1 Use Mixer     :0
SIP1 Mixer Uri     :

<AUTOUPDATE CONFIG MODULE>
Download Username  :user
Download password  :pass
Download Server IP :{$SERVER_IP}
Config File Name   :{$CONFIG_FILENAME}
Config File Key    :
Download Protocol  :2
Download Mode      :1
Download Interval  :1
<<END OF FILE>>