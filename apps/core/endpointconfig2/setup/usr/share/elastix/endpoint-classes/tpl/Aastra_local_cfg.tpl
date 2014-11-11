#aastra default config file

{{if enable_dhcp}}
dhcp: 1
{{else}}
dhcp: 0
ip: {{static_ip}}
subnet mask: {{static_mask}}
default gateway: {{static_gateway}}
dns1: {{static_dns1}}
dns2: {{static_dns2}}
{{endif}}

time server disabled: 0
time server1: {{server_ip}}

sip proxy ip: {{server_ip}}
sip proxy port: 5060
sip registrar ip: {{server_ip}}
sip registrar port: 5060

sip digit timeout: 6

download protocol: TFTP
tftp server: {{server_ip}}
alternate tftp server: {{server_ip}}
use alternate tftp: 1

xml application post list: {{server_ip}}
xml application uri: {{phonesrv}}/
xml application title: "Elastix Services for Aastra"

softkey1 type: speeddial
softkey1 label: "Voice Mail"
softkey1 value: *97

softkey2 type: speeddial
softkey2 label: "DND On"
softkey2 value: *78

softkey3 type: speeddial
softkey3 label: "DND Off"
softkey3 value: *79

{{py:n = 1}}
{{for extension in sip}}
{{if n > 3 }}
softkey{{n}} type: line
softkey{{n}} label: "{{extension.description}}"
softkey{{n}} line: {{n}}
{{endif}}
{{py:n += 1}}
{{endfor}}
{{py:n = 1}}
{{for extension in sip}}
sip line{{n}} screen name: {{extension.description}}
sip line{{n}} screen name 2: {{extension.account}}
sip line{{n}} display name: {{extension.account}}
sip line{{n}} auth name: {{extension.account}}
sip line{{n}} user name: {{extension.account}}
sip line{{n}} password: {{extension.secret}}
sip line{{n}} vmail: *97
sip line{{n}} mode: 0 
{{py:n += 1}}
{{endfor}}
{{for m in range(n,max_sip_accounts+1)}}
sip line{{m}} screen name: 
sip line{{m}} screen name 2: 
sip line{{m}} display name: 
sip line{{m}} auth name: 
sip line{{m}} user name: 
sip line{{m}} password: 
sip line{{m}} vmail: *97
sip line{{m}} mode: 0

{{endfor}}