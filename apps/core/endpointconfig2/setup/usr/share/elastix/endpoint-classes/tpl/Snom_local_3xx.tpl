<html>
    <pre>
        timezone: {{time_zone}}
        wifi_ether_bridge: {{if enable_bridge}}on{{else}}off{{endif}}
        {{py:n = 1}}{{for extension in sip}}
        user_active{{n}}: on
        user_realname{{n}}: {{extension.description}}
        user_name{{n}}: {{extension.account}}
        user_pass{{n}}: {{extension.secret}}
        user_pname{{n}}: {{extension.account}}
        user_mailbox{{n}}: *97
        user_host{{n}}: {{server_ip}}
        user_srtp{{n}}: off{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}
        user_active{{m}}: off
        user_realname{{m}}:
        user_name{{m}}:
        user_pass{{m}}:
        user_pname{{m}}:
        user_mailbox{{m}}:
        user_host{{m}}: {{server_ip}}
        user_srtp{{m}}: off{{endfor}}
        language: {{language}}
    </pre>
</html>