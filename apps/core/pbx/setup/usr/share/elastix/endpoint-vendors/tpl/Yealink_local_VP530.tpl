#!version:1.0.0.1

##File header "#!version:1.0.0.1" can not be edited or deleted.##

#######################################################################################
##                           Account1 Settings                                       ##                                                                          
#######################################################################################

#Enable or disable the account1, 0-Disabled (default), 1-Enabled;
account.1.enable = 1

#Configure the label displayed on the LCD screen for account1.
account.1.label = {$DISPLAY_NAME}

#Configure the display name of account1.
account.1.display_name = {$DISPLAY_NAME}

#Configure the username and password for register authentication.
account.1.auth_name = {$ID_DEVICE}
account.1.password = {$SECRET}

#Configure the register user name.
account.1.user_name = {$ID_DEVICE}

#Configure the SIP server address.
account.1.sip_server_host = {$SERVER_IP} 
#Specify the port for the SIP server. The default value is 5060.
account.1.sip_server_port = 5060
auto_provision.server.url = tftp://{$SERVER_IP}:69
