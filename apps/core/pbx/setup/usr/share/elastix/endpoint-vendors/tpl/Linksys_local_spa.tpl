<flat-profile>
    <Resync_Periodic ua="na">86400</Resync_Periodic>
    <Proxy_1_ ua="na">{$SERVER_IP}</Proxy_1_>
    <Outbound_Proxy_1_ ua="na">{$SERVER_IP}</Outbound_Proxy_1_>
    <Primary_NTP_Server ua="na">{$SERVER_IP}</Primary_NTP_Server>
    <Profile_Rule ua="na">tftp://{$SERVER_IP}/spa{$MAC_ADDRESS}.cfg</Profile_Rule>
 <!-- Subscriber Information -->
    <Text_Logo group="Phone/General">{$DISPLAY_NAME}</Text_Logo>
    <Station_Name group="Phone/General">{$DISPLAY_NAME}</Station_Name>
    <Voice_Mail_Number group="Phone/General"></Voice_Mail_Number>
    <Display_Name_1_ ua="na">{$DISPLAY_NAME}</Display_Name_1_>
    <Short_Name_1_ ua="na">{$ID_DEVICE}</Short_Name_1_> 
    <User_ID_1_ ua="na">{$ID_DEVICE}</User_ID_1_>
    <Password_1_ ua="na">{$SECRET}</Password_1_>
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
 <!-- <BMP_Picture_Download_URL ua="ua">tftp://{$SERVER_IP}/Linksys.bmp</BMP_Picture_Download_URL> -->
</flat-profile>