# Image Version
image_version: "{{firmware_version}}"

# NAT/Firewall Traversal
nat_enable: "1"
nat_address: ""
voip_control_port: "5060"
start_media_port: "16384"
end_media_port:  "32766"
nat_received_processing: "0"

# Proxy Registration (0-disable (default), 1-enable)
proxy_register: "1"

# Phone Registration Expiration [1-3932100 sec] (Default - 3600)
timer_register_expires: "3600"

# Codec for media stream (g711ulaw (default), g711alaw, g729)
preferred_codec: "g711ulaw"

# TOS bits in media stream [0-5] (Default - 5)
# tos_media: "5"
dscpForAudio: 184

# Enable VAD (0-disable (default), 1-enable)
enable_vad: "0"

# Allow for the bridge on a 3way call to join remaining parties upon hangup
cnf_join_enable: "1"     ; 0-Disabled, 1-Enabled (default)

# Allow Transfer to be completed while target phone is still ringing
semi_attended_transfer: "0"   ; 0-Disabled, 1-Enabled (default)

# Telnet Level (enable or disable the ability to telnet into this phone
telnet_level: "2"      ; 0-Disabled (default), 1-Enabled, 2-Privileged

# Inband DTMF Settings (0-disable, 1-enable (default))
dtmf_inband: "1"

# Out of band DTMF Settings (none-disable, avt-avt enable (default), avt_always - always avt )
dtmf_outofband: "avt"

# DTMF dB Level Settings (1-6dB down, 2-3db down, 3-nominal (default), 4-3db up, 5-6dB up)
dtmf_db_level: "3"

# SIP Timers
timer_t1: "500"                   ; Default 500 msec
timer_t2: "4000"                  ; Default 4 sec
sip_retx: "10"                     ; Default 11
sip_invite_retx: "6"               ; Default 7
timer_invite_expires: "180"        ; Default 180 sec

# Setting for Message speeddial to UOne box
messages_uri: "*97"

#Subdirectory config file location
#tftp_cfg_dir: /tftpboot/configs/sipphone
# TFTP Phone Specific Configuration File Directory
tftp_cfg_dir: "./"

# Time Server
sntp_mode: "unicast"
#time_zone: "EST"
#dst_offset: "1"
#dst_start_month: "Mar"
#dst_start_day: ""
#dst_start_day_of_week: "Sun"
#dst_start_week_of_month: "2"
#dst_start_time: "02"
#dst_stop_month: "Nov"
#dst_stop_day: ""
#dst_stop_day_of_week: "Sunday"
#dst_stop_week_of_month: "1"
#dst_stop_time: "2"
#dst_auto_adjust: "1"

# Do Not Disturb Control (0-off, 1-on, 2-off with no user control, 3-on with no user control)
dnd_control: "0"                  ; Default 0 (Do Not Disturb feature is off)

# Caller ID Blocking (0-disabled, 1-enabled, 2-disabled no user control, 3-enabled no user control)
callerid_blocking: "0"            ; Default 0 (Disable sending all calls as anonymous)

# Anonymous Call Blocking (0-disbaled, 1-enabled, 2-disabled no user control, 3-enabled no user control)
anonymous_call_block: "0"         ; Default 0 (Disable blocking of anonymous calls)

# Call Waiting (0-disabled, 1-enabled, 2-disabled with no user control, 3-enabled with no user control)
call_waiting: "1"                 ; Default 1 (Call Waiting enabled)

# DTMF AVT Payload (Dynamic payload range for AVT tones - 96-127)
dtmf_avt_payload: "101"           ; Default 100

# XML file that specifies the dialplan desired
dial_template: "dialplan"

# Network Media Type (auto, full100, full10, half100, half10)
network_media_type: "auto"

#Autocompletion During Dial (0-off, 1-on [default])
autocomplete: "1"

#Time Format (0-12hr, 1-24hr [default])
time_format_24hr: "0"

# Remote Party ID
remote_party_id: 1              ; 0-Disabled (default), 1-Enabled

# URL for external Directory location
directory_url: "{{phonesrv}}/directory"

# This must be a different value from the one in syncinfo.xml to force reboot.
sync: "0"
