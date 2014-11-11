<flat-profile>
    <Resync_Periodic ua="na">86400</Resync_Periodic>
    <Voice_Mail_Number group="Phone/General"></Voice_Mail_Number>

 <!-- Speed Dial -->
    <Speed_Dial_2 ua="rw"/>
    <Speed_Dial_3 ua="rw"/>
    <Speed_Dial_4 ua="rw"/>
    <Speed_Dial_5 ua="rw"/>
    <Speed_Dial_6 ua="rw"/>
    <Speed_Dial_7 ua="rw"/>
    <Speed_Dial_8 ua="rw"/>
    <Speed_Dial_9 ua="rw"/>

<!-- Additional -->
<!-- <Time_Zone  ua="na">GMT-06:00</Time_Zone> -->
    <Voice_Mail_Number  ua="na">*97</Voice_Mail_Number>
    <Paging_Code ua="na">*80</Paging_Code>
    <Select_Logo ua="ua">BMP Picture</Select_Logo>
    <Text_Logo ua="na">Linksys</Text_Logo>
    <Select_Background_Picture ua="ua">BMP Picture</Select_Background_Picture>
<!-- <BMP_Picture_Download_URL ua="ua">tftp://{{server_ip}}/Linksys.bmp</BMP_Picture_Download_URL> -->

    <Primary_NTP_Server ua="na">{{server_ip}}</Primary_NTP_Server>
    <Profile_Rule ua="na">tftp://{{server_ip}}/{{config_filename}}</Profile_Rule>

    <Text_Logo group="Phone/General">{{sip[0].description}}</Text_Logo>
    <Station_Name group="Phone/General">{{sip[0].description}}</Station_Name>

<!-- Internet Connection Type -->

{{if enable_dhcp }}
    <Connection_Type>DHCP</Connection_Type>
{{else}}
	<Connection_Type>Static IP</Connection_Type>
	<Static_IP>{{static_ip}}</Static_IP> 
	<NetMask>{{static_mask}}</NetMask> 
	<Gateway>{{static_gateway}}</Gateway>
	<Primary_DNS>{{static_dns1}}</Primary_DNS>
	<Secondary_DNS>{{static_dns2}}</Secondary_DNS>
{{endif}}

<!-- Subscriber Information -->
{{py:n = 1}}{{for extension in sip}}
    <Outbound_Proxy_{{n}}_ ua="na">{{server_ip}}</Outbound_Proxy_1_>
    <Display_Name_{{n}}_ ua="na">{{extension.description}}</Display_Name_1_>
    <Short_Name_{{n}}_ ua="na">{{extension.extension}}</Short_Name_1_> 
    <User_ID_{{n}}_ ua="na">{{extension.account}}</User_ID_1_>
    <Password_{{n}}_ ua="na">{{extension.secret}}</Password_1_>
{{py:n += 1}}{{endfor}}
</flat-profile>
