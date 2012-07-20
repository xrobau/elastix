# Firmware Server Path
P192 = {$SERVER_IP}

# Config Server Path
P237 = {$SERVER_IP}

# Firmware Upgrade. 0 - TFTP Upgrade,  1 - HTTP Upgrade.
P212 = 0

# Account Name
P417 = {$DISPLAY_NAME}

# SIP Server
P402 = {$SERVER_IP}

# Outbound Proxy
P403 = {$SERVER_IP}

# SIP User ID
P404 = {$ID_DEVICE}

# Authenticate ID
P405 = {$ID_DEVICE}

# Authenticate password
P406 = {$SECRET}

# Display Name (John Doe)
P407 = {$DISPLAY_NAME}

# (GXV3140 specific) Dialplan string
P290 = {literal}{ x+ | *x+ }{/literal}