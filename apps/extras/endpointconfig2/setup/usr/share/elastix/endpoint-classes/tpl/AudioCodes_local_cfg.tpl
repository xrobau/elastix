system/ntp/enabled=1
system/ntp/primary_server_address={{server_ip}}
system/ntp/gmt_offset={{timezone}}
provisioning/configuration/url=tftp://{{server_ip}}/{{config_filename}}
network/lan_type={{if enable_dhcp}}DHCP{{else}}STATIC{{endif}}
network/lan/fixed_ip/ip_address={{static_ip}}
network/lan/fixed_ip/netmask={{static_mask}}
network/lan/fixed_ip/gateway={{static_gateway}}
network/lan/fixed_ip/primary_dns={{static_dns1}}
network/lan/fixed_ip/secondary_dns={{static_dns2}}
{{py:n = 0}}{{for extension in sip}}voip/line/{{n}}/enabled=1
voip/line/{{n}}/id={{extension.account}}
voip/line/{{n}}/description={{extension.description}}
voip/line/{{n}}/auth_name={{extension.account}}
voip/line/{{n}}/auth_password={{extension.secret}}
{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts)}}voip/line/{{m}}/enabled=0
voip/line/{{m}}/id={{m+1}}
voip/line/{{m}}/description=320HD
voip/line/{{m}}/auth_name=0
voip/line/{{m}}/auth_password=0
{{endfor}}voip/signalling/sip/proxy_address={{server_ip}}
voip/signalling/sip/sip_registrar/enabled=1
voip/signalling/sip/sip_registrar/addr={{server_ip}}
voip/signalling/sip/use_proxy=1
personal_settings/language={{language}}