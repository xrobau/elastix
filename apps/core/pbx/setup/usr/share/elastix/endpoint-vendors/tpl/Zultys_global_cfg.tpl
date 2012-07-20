[NET_CONFIG]
use_dhcp=yes
ntp_server_addr={$SERVER_IP}
;sntp_server_addr={$SERVER_IP}
tftp_server_addr={$SERVER_IP}
tftp_cfg_dir=./{$MODEL}

[SIP_CONFIG]
phone_sip_port=5060
rtp_start_port=33000
register_w_proxy=yes
proxy_addr={$SERVER_IP}
proxy_port=5060
voice_mail_uri=*98
registration_expires=3600
session_expires=3600
[GENERAL_INFO]
language=es_ES.iso88591

[VLAN_CONFIG]
mode=0
;vlan_id_a=10
;circuits_a=UET
;vlan_id_b=30
;circuits_b=EUT
;cos_setting=5