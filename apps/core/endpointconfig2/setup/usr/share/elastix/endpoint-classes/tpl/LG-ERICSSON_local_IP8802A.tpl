[LAN]
;Lan configuration{{if enable_dhcp}}
network_mode dhcp
{{else}}
network_mode static
ipAddress {{static_ip}}
defaultGateway {{static_gateway}}
subnetMask {{static_mask}}
dns1_address {{static_dns1}}
dns2_address {{static_dns2}}
{{endif}}
tftp_server_address {{server_ip}}

[NETTIME]
;SNTP, Timezone and DST Configurations
timezone {{time_zone}}

[VOIP]
;VoIP configurations{{py:n = 1}}{{for extension in sip}}
line{{n}}_proxy_address {{server_ip}}
line{{n}}_proxy_port 5060
line{{n}}_displayname "{{extension.description}}"
line{{n}}_name {{extension.extension}}
line{{n}}_authname {{extension.account}}
line{{n}}_password {{extension.secret}}
line{{n}}_type private
line{{n}}_extension
line{{n}}_registration enable{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}
line{{m}}_proxy_address {{server_ip}}
line{{m}}_proxy_port 5060
line{{m}}_displayname 
line{{m}}_name 
line{{m}}_authname 
line{{m}}_password 
line{{m}}_type private
line{{m}}_extension 
line{{m}}_registration disable
{{endfor}}

