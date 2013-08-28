#!version:1.0.0.1

##File header "#!version:1.0.0.1" can not be edited or deleted.##

#######################################################################################
##                          Network                                                  ## 
#######################################################################################

#Configure the WAN port type; 0-DHCP (default), 1-PPPoE, 2-Static IP Address;
#Require reboot;
{{if enable_dhcp}}
network.internet_port.type =  0
{{else}}
network.internet_port.type =  2
#Configure the static IP address, subnet mask, gateway and DNS server;
#Require Reboot;
network.internet_port.ip = {{static_ip}}
network.internet_port.mask = {{static_mask}}
network.internet_port.gateway = {{static_gateway}}
network.primary_dns= {{static_dns1}}
network.secondary_dns = {{static_dns2}}
{{endif}}


{{py:n = 1}}{{for extension in sip}}
#######################################################################################
##                           Account{{n}} Settings                                       ##                                                                          
#######################################################################################

#Enable or disable the account1, 0-Disabled (default), 1-Enabled;
account.{{n}}.enable = 1

#Configure the label displayed on the LCD screen for account1.
account.{{n}}.label = {{extension.description}}

#Configure the display name of account1.
account.{{n}}.display_name = {{extension.description}}

#Configure the username and password for register authentication.
account.{{n}}.auth_name = {{extension.account}}
account.{{n}}.password = {{extension.secret}}

#Configure the register user name.
account.{{n}}.user_name = {{extension.extension}}

#Configure the SIP server address.
account.{{n}}.sip_server_host = {{server_ip}} 
#Specify the port for the SIP server. The default value is 5060.
account.{{n}}.sip_server_port = 5060
{{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}

#######################################################################################
##                           Account{{m}} Settings                                       ##                                                                          
#######################################################################################

#Enable or disable the account1, 0-Disabled (default), 1-Enabled;
account.{{m}}.enable = 0

#Configure the label displayed on the LCD screen for account1.
account.{{m}}.label = 

#Configure the display name of account1.
account.{{m}}.display_name = 

#Configure the username and password for register authentication.
account.{{m}}.auth_name = 
account.{{m}}.password = 

#Configure the register user name.
account.{{m}}.user_name = 

#Configure the SIP server address.
account.{{m}}.sip_server_host = {{server_ip}} 
#Specify the port for the SIP server. The default value is 5060.
account.{{m}}.sip_server_port = 5060
{{endfor}}
auto_provision.server.url = tftp://{{server_ip}}:69

#Configure the PC port type; 0-Router, 1-Bridge (default);
#Require reboot;
network.bridge_mode = {{enable_bridge}}

#######################################################################################
##                             Time Settings                                         ##
#######################################################################################
#Configure the time zone and time zone name. The time zone ranges from -11 to +12, the default value is +8. 
local_time.time_zone = {{time_zone}}


#######################################################################################
##            Contacts                                                               ##                                            
#######################################################################################
#Remote Phonebook 
remote_phonebook.data.1.url = {{phonesrv}}/internal
remote_phonebook.data.1.name = Elastix Phonebook - Internal
remote_phonebook.data.2.url = {{phonesrv}}/external
remote_phonebook.data.2.name = Elastix Phonebook - External
directory.update_time_interval = 20

