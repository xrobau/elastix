[VOIP]
;VoIP configurations
line1_proxy_address {$SERVER_IP} ;Specifies the IP address or hostname of the Proxy Server for line
line1_proxy_port 5060 ;Specifies the port number of the proxy server for line: 1024 ~ 32000(default:5060)
line1_displayname {$DISPLAY_NAME} ;Specifies the display name for line : max. 50 length string
line1_name {$ID_DEVICE} ;Specifies the name for line : max. 50 length string
line1_authname {$ID_DEVICE} ;Specifies the authentication name for line : max. 50 length string
line1_password {$SECRET} ;Specifies the authentication password for line : max. 50 length string
line1_type private ;Specifies the type for line : private/shared/dss/service
line1_extension ;Specifies the extension number of the line for DSS: max. 50 length string
line1_registration enable ;Specifies the registration for line : enable/disable
