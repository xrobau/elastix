#aastra default config file

dhcp: 1

time server disabled: 0
time server1: {{server_ip}}

sip proxy ip: {{server_ip}}
sip proxy port: 5060
sip registrar ip: {{server_ip}}
sip registrar port: 5060

sip digit timeout: 6

download protocol: TFTP
tftp server: {{server_ip}}

xml application post list: {{server_ip}}

softkey1 type: speeddial
softkey1 label: "Voice Mail"
softkey1 value: *97

softkey2 type: speeddial
softkey2 label: "DND On"
softkey2 value: *78

softkey3 type: speeddial
softkey3 label: "DND Off"
softkey3 value: *79