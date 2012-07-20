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