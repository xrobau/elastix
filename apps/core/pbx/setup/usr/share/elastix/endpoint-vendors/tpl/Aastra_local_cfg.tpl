#aastra default config file
time server disabled: 0
time server1: {$SERVER_IP}

sip proxy ip: {$SERVER_IP}
sip proxy port: 5060
sip registrar ip: {$SERVER_IP}
sip registrar port: 5060

sip digit timeout: 6

xml application post list: {$SERVER_IP}

softkey1 type: speeddial
softkey1 label: "Voice Mail"
softkey1 value: *97

softkey2 type: speeddial
softkey2 label: "DND On"
softkey2 value: *78

softkey3 type: speeddial
softkey3 label: "DND Off"
softkey3 value: *79

sip line1 screen name: {$DISPLAY_NAME}
sip line1 screen name 2: {$ID_DEVICE}
sip line1 display name: {$ID_DEVICE}
sip line1 auth name: {$ID_DEVICE}
sip line1 user name: {$ID_DEVICE}
sip line1 password: {$SECRET}
sip line1 vmail: *97
sip line1 mode: 0 