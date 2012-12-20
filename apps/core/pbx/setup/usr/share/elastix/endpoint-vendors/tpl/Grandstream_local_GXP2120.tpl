# Firmware Server Path
P192 = {$SERVER_IP}

# Config Server Path
P237 = {$SERVER_IP}

# Firmware Upgrade. 0 - TFTP Upgrade,  1 - HTTP Upgrade.
P212 = 0

# Account Name
P270 = {$DISPLAY_NAME}

# SIP Server
P47 = {$SERVER_IP}

# Outbound Proxy
P48 = {$SERVER_IP}

# SIP User ID
P35 = {$ID_DEVICE}

# Authenticate ID
P36 = {$ID_DEVICE}

# Authenticate password
P34 = {$SECRET}

# Display Name (John Doe)
P3 = {$DISPLAY_NAME}

# (GXV3175 specific) Dialplan string
P290 = {literal}{ x+ | *x+ }{/literal}

#Time Zone
P64={$TIME_ZONE}

# DHCP=0 o static=1
{if $ENABLE_DHCP == 1}
P8 = 0
{else}
P8 = 1

# IP Address
P9 = {$STATIC_IP[0]}
P10 = {$STATIC_IP[1]}
P11 = {$STATIC_IP[2]}
P12 = {$STATIC_IP[3]}

# Subnet Mask
P13 = {$STATIC_MASK[0]}
P14 = {$STATIC_MASK[1]}
P15 = {$STATIC_MASK[2]}
P16 = {$STATIC_MASK[3]}

# Gateway
P17 = {$STATIC_GATEWAY[0]}
P18 = {$STATIC_GATEWAY[1]}
P19 = {$STATIC_GATEWAY[2]}
P20 = {$STATIC_GATEWAY[3]}

# DNS Server 1
P21 = {$STATIC_DNS1[0]}
P22 = {$STATIC_DNS1[1]}
P23 = {$STATIC_DNS1[2]}
P24 = {$STATIC_DNS1[3]}

# DNS Server 2
P25 = {$STATIC_DNS2[0]}
P26 = {$STATIC_DNS2[1]}
P27 = {$STATIC_DNS2[2]}
P28 = {$STATIC_DNS2[3]}
{/if}

# TFTP Server
P41 = {$SERVER_IP_OCTETS[0]}
P42 = {$SERVER_IP_OCTETS[1]}
P43 = {$SERVER_IP_OCTETS[2]}
P44 = {$SERVER_IP_OCTETS[3]}

{if $FORCE_DTMF_RTP}
# Send DTMF. 8 - in audio, 1 - via RTP, 2 - via SIP INFO
P73=1
{/if}