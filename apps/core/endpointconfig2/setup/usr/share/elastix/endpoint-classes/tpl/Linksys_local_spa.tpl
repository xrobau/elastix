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
    <Time_Zone  ua="na">{{time_zone}}</Time_Zone>
    <Date_Format ua="na">day/month</Date_Format>
    <Daylight_Saving_Time_Rule ua="na">{{daylight_rule}}</Daylight_Saving_Time_Rule>
    <Daylight_Saving_Time_Enable ua="na">{{daylight_enable}}</Daylight_Saving_Time_Enable>
    <Voice_Mail_Number  ua="na">*97</Voice_Mail_Number>
    <Paging_Code ua="na">*80</Paging_Code>
    <Select_Logo ua="ua">Text Logo</Select_Logo>
    <Text_Logo ua="na">Elastix</Text_Logo>
    <Select_Background_Picture ua="ua">Text Logo</Select_Background_Picture>
<!-- <BMP_Picture_Download_URL ua="ua">tftp://{{server_ip}}/Linksys.bmp</BMP_Picture_Download_URL> -->

    <Primary_NTP_Server ua="na">{{server_ip}}</Primary_NTP_Server>
    <Profile_Rule ua="na">tftp://{{server_ip}}/{{config_filename}}</Profile_Rule>

<!-- <Text_Logo group="Phone/General">{{sip[0].description}}</Text_Logo> -->
    <Station_Name group="Phone/General">{{sip[0].description}}</Station_Name>

<!-- Vertical Service Activation Codes - factory reset of phone reloads these - if we don't clear them many of the feature codes fail-->

        <Call_Return_Code ua="na"></Call_Return_Code>
        <Blind_Transfer_Code ua="na"></Blind_Transfer_Code>
        <Call_Back_Act_Code ua="na"></Call_Back_Act_Code>
        <Call_Back_Deact_Code ua="na"></Call_Back_Deact_Code>
        <Cfwd_All_Act_Code ua="na"></Cfwd_All_Act_Code>
        <Cfwd_All_Deact_Code ua="na"></Cfwd_All_Deact_Code>
        <Cfwd_Busy_Act_Code ua="na"></Cfwd_Busy_Act_Code>
        <Cfwd_Busy_Deact_Code ua="na"></Cfwd_Busy_Deact_Code>
        <Cfwd_No_Ans_Act_Code ua="na"></Cfwd_No_Ans_Act_Code>
        <Cfwd_No_Ans_Deact_Code ua="na"></Cfwd_No_Ans_Deact_Code>
        <CW_Act_Code ua="na"></CW_Act_Code>
        <CW_Deact_Code ua="na"></CW_Deact_Code>
        <CW_Per_Call_Act_Code ua="na"></CW_Per_Call_Act_Code>
        <CW_Per_Call_Deact_Code ua="na"></CW_Per_Call_Deact_Code>
        <Block_CID_Act_Code ua="na"></Block_CID_Act_Code>
        <Block_CID_Deact_Code ua="na"></Block_CID_Deact_Code>
        <Block_CID_Per_Call_Act_Code ua="na"></Block_CID_Per_Call_Act_Code>
        <Block_CID_Per_Call_Deact_Code ua="na"></Block_CID_Per_Call_Deact_Code>
        <Block_ANC_Act_Code ua="na"></Block_ANC_Act_Code>
        <Block_ANC_Deact_Code ua="na"></Block_ANC_Deact_Code>
        <DND_Act_Code ua="na"></DND_Act_Code>
        <DND_Deact_Code ua="na"></DND_Deact_Code>
        <Secure_All_Call_Act_Code ua="na"></Secure_All_Call_Act_Code>
        <Secure_No_Call_Act_Code ua="na"></Secure_No_Call_Act_Code>
        <Secure_One_Call_Act_Code ua="na"></Secure_One_Call_Act_Code>
        <Secure_One_Call_Deact_Code ua="na"></Secure_One_Call_Deact_Code>
        <Paging_Code ua="na"></Paging_Code>
        <Call_Park_Code ua="na"></Call_Park_Code>
        <Call_Pickup_Code ua="na"></Call_Pickup_Code>
        <Call_UnPark_Code ua="na"></Call_UnPark_Code>
        <Group_Call_Pickup_Code ua="na"></Group_Call_Pickup_Code>
        <Media_Loopback_Code ua="na"></Media_Loopback_Code>
        <Referral_Services_Codes ua="na"></Referral_Services_Codes>
        <Feature_Dial_Services_Codes ua="na"></Feature_Dial_Services_Codes>


<!-- Outbound Call Codec Selection Codes - factory reset of phone reloads these - if we don't clear them many of the feature codes fail -->

        <Prefer_G711u_Code ua="na"></Prefer_G711u_Code>
        <Force_G711u_Code ua="na"></Force_G711u_Code>
        <Prefer_G711a_Code ua="na"></Prefer_G711a_Code>
        <Force_G711a_Code ua="na"></Force_G711a_Code>
        <Prefer_G723_Code ua="na"></Prefer_G723_Code>
        <Force_G723_Code ua="na"></Force_G723_Code>
        <Prefer_G726r16_Code ua="na"></Prefer_G726r16_Code>
        <Force_G726r16_Code ua="na"></Force_G726r16_Code>
        <Prefer_G726r24_Code ua="na"></Prefer_G726r24_Code>
        <Force_G726r24_Code ua="na"></Force_G726r24_Code>
        <Prefer_G726r32_Code ua="na"></Prefer_G726r32_Code>
        <Force_G726r32_Code ua="na"></Force_G726r32_Code>
        <Prefer_G726r40_Code ua="na"></Prefer_G726r40_Code>
        <Force_G726r40_Code ua="na"></Force_G726r40_Code>
        <Prefer_G729a_Code ua="na"></Prefer_G729a_Code>
        <Force_G729a_Code ua="na"></Force_G729a_Code>

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
    <Outbound_Proxy_{{n}}_ ua="na">{{server_ip}}</Outbound_Proxy_{{n}}_>
    <Use_Outbound_Proxy_{{n}}_ ua="na">Yes</Use_Outbound_Proxy_{{n}}_>
    <Proxy_{{n}}_ ua="na">{{server_ip}}</Proxy_{{n}}_>
    <Display_Name_{{n}}_ ua="na">{{extension.description}}</Display_Name_1_>
    <Short_Name_{{n}}_ ua="na">{{extension.extension}}</Short_Name_1_>
    <User_ID_{{n}}_ ua="na">{{extension.account}}</User_ID_1_>
    <Password_{{n}}_ ua="na">{{extension.secret}}</Password_1_>
	<Dial_Plan_{{n}}_ ua="na">(*xx|*xxx|**xx|**xxx|xx*|xxx*|xxx**|*xxxxx|[3469]11|0|00|[2-9]xxxxxx|1xxx[2-9]xxxxxx|xxxxxxxxxxxx.)</Dial_Plan_{{n}}_>
{{py:n += 1}}{{endfor}}
</flat-profile>
