# Cisco SIP Configuration

# Proxy Server Address
proxy1_address: "{{server_ip}}"

# Proxy Server Port (default - 5060)
proxy1_port:"5060"

# Emergency Proxy info
proxy_emergency: "{{server_ip}}"
proxy_emergency_port: "5060"

# Backup Proxy info
proxy_backup: "{{server_ip}}"
proxy_backup_port: "5060"

# Outbound Proxy info
outbound_proxy: "{{server_ip}}"
outbound_proxy_port: "5060"

phone_label: "{{sip[0].description}}"

{{py:n = 1}}
{{for extension in sip}}
line{{n}}_name: "{{extension.extension}}"
line{{n}}_authname: "{{extension.account}}"
line{{n}}_shortname: "{{extension.description}}"
line{{n}}_displayname: "{{extension.description}}"
line{{n}}_password: "{{extension.secret}}"
{{py:n += 1}}
{{endfor}}
{{for m in range(n,max_sip_accounts+1)}}
line{{m}}_name: ""
line{{m}}_authname: "UNPROVISIONED"
line{{m}}_shortname: "L{{n}}"
line{{m}}_displayname: ""
line{{m}}_password: "UNPROVISIONED"
{{endfor}}

sntp_server: "{{server_ip}}"

# URL for external Phone Services
services_url: "{{phonesrv}}/services"

# URL for branding logo
logo_url: "{{phonesrv}}/logo.bmp"

