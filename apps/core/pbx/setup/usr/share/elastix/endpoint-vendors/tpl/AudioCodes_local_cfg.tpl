provisioning/configuration/url=tftp://{$SERVER_IP}/{$CONFIG_FILENAME}
voip/line/0/id={$ID_DEVICE}
voip/line/0/auth_name={$ID_DEVICE}
voip/line/0/auth_password={$SECRET}
voip/signalling/sip/proxy_address={$SERVER_IP}
voip/signalling/sip/sip_registrar/enabled=1
voip/signalling/sip/sip_registrar/addr={$SERVER_IP}
voip/signalling/sip/use_proxy=1