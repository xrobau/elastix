<<VOIP CONFIG FILE>>Version:{$VERSION_CFG}                         

<GLOBAL CONFIG MODULE>
SNTP Server        :{$SERVER_IP}
Enable SNTP        :1
Static IP          :{$STATIC_IP}
Static NetMask     :{$STATIC_MASK}
Static GateWay     :{$STATIC_GATEWAY}
Primary DNS        :{$STATIC_DNS1}
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

<IAX2 CONFIG MODULE>
Server   Address   :{$SERVER_IP}
Server   Port      :4569
User     Name      :{$ID_DEVICE}
User     Password  :{$SECRET}
User     Number    :{$ID_DEVICE}
Voice    Number    :0
Voice    Text      :mail
EchoTest Number    :1
EchoTest Text      :echo
Local    Port      :4569
Enable   Register  :1
Refresh  Time      :60
Enable   G.729     :0

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