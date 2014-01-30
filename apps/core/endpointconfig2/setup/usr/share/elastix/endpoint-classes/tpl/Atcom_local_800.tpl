<?xml version="1.0"?>
<PhoneConfig version="1.0">
	<System>
		<SystemConfiguration>
			<RestrictedAccessDomains></RestrictedAccessDomains>
			<HostName></HostName>
			<Domain></Domain>
		</SystemConfiguration>
		<WebServer Enable="Enable" EnableAdminAccess="Enable" Type="0" HttpPort="80" HttpsPort="443" AdminPasswd="" UserPassword=""/>
		<LcdPassword UserPassword="123"/>
		<Syslog Enable="Enable" Server="" Port="514" Level="3"/>
		<WANConfig ConnectionType="{{if enable_dhcp }}0{{else}}1{{endif}}" NegotiateType="0">
			<StaticIPSettings StaticIP="{{static_ip}}" NetMask="{{static_mask}}" Gateway="{{static_gateway}}" PrimaryDNS="{{static_dns1}}" SecondaryDNS="{{static_dns2}}" DNSServerOrder="0" DNSQueryMode="0"/>
			<PPPoESettings LoginName="" LoginPassword="" ServiceName=""/>
		</WANConfig>
		<LANConfig NegotiateType="0" IP="192.168.10.1" NetMask="255.255.255.0" NAT="Disable" BridgeMode="{{if enable_bridge }}Enable{{else}}Disable{{endif}}" DHCPService="Disable">
			<DHCPLease StartIP="192.168.10.20" EndIP="192.168.10.254" DNS1="192.168.10.1" DNS2="" DNSRelay="Disable" LeaseTime="3600"/>
		</LANConfig>
		<STUN>
			<UseStun>Disable</UseStun>
			<StunServerAddr/>
			<StunServerPort>0</StunServerPort>
			<StunEffectTime>0</StunEffectTime>
			<LocalSIPPort>0</LocalSIPPort>
		</STUN>
		<VLan>
			<Wan Enable="Disable" Vid="1" Priority="0"/>
			<Lan Enable="Disable" Vid="1" Priority="0"/>
		</VLan>
		<QoS Signal="0" Voice="0"/>
	</System>
	<SIP>
		<SIPParameters>
			<MaxForward>60</MaxForward>
			<MaxRedirection>5</MaxRedirection>
			<MaxAuth>2</MaxAuth>
			<SIPUserAgentName></SIPUserAgentName>
			<SIPServerName></SIPServerName>
			<SIPRegUserAgentName></SIPRegUserAgentName>
			<SIPAcceptLanguage>en</SIPAcceptLanguage>
			<DTMFRelayMIMEType>0</DTMFRelayMIMEType>
			<RemoveLastReg>Disable</RemoveLastReg>
			<UseCompactHeader>Disable</UseCompactHeader>
			<EscapeDisplayName>Disable</EscapeDisplayName>
			<AtcomKeySystem/>
			<SIP_BEnable>Disable</SIP_BEnable>
			<TalkPackage>Disable</TalkPackage>
			<HoldPackage>Disable</HoldPackage>
			<ConferencePackage>Disable</ConferencePackage>
			<NotifyConference>Disable</NotifyConference>
		</SIPParameters>
		<SIPTimerValues>
			<SIPT1>0.500</SIPT1>
			<SIPT2>4.000</SIPT2>
			<SIPT4>5.000</SIPT4>
			<SIPTimerB>16.000</SIPTimerB>
			<SIPTimerF>16.000</SIPTimerF>
			<SIPTimerH>16.000</SIPTimerH>
			<SIPTimerD>16.000</SIPTimerD>
			<SIPTimerJ>16.000</SIPTimerJ>
			<ReINVITEExpires>30</ReINVITEExpires>
			<RegRetryIntvl>8</RegRetryIntvl>
			<SubRetryIntvl>10</SubRetryIntvl>
		</SIPTimerValues>
		<ResponseStatusCodeHandling>
			<SIT1RSC>0</SIT1RSC>
			<SIT2RSC>0</SIT2RSC>
			<SIT3RSC>0</SIT3RSC>
			<SIT4RSC>0</SIT4RSC>
			<TryBackupRSC>0</TryBackupRSC>
			<RetryRegRSC>0</RetryRegRSC>
		</ResponseStatusCodeHandling>
		<RTPParameters>
			<RTPPortMin>16384</RTPPortMin>
			<RTPPortMax>16482</RTPPortMax>
			<RTPPacketSize>10</RTPPacketSize>
			<MaxRTPICMPErr>0</MaxRTPICMPErr>
			<RTCPTxInterval>0</RTCPTxInterval>
			<NoUDPChecksum>Disable</NoUDPChecksum>
			<SymmetricRTP>Disable</SymmetricRTP>
			<StatsInBYE>Disable</StatsInBYE>
		</RTPParameters>
		<SDPDynamicPayloadType AVT="101" INFOREQ="0" G726="108" G729b="18" iLBC="98" iLBC_Mode="30" G723_Mode="63" G726_Mode="32"/>
		<SDPCodecName AVT="telephone-event" G711u="PCMU" G711a="PCMA" G722="G722" G723="G723" G726="G726" G729="G729" iLBC="iLBC"/>
		<NATSupportParameters>
			<HandleVIAreceived>Disable</HandleVIAreceived>
			<HandleVIArport>Disable</HandleVIArport>
			<InsertVIAreceived>Disable</InsertVIAreceived>
			<InsertVIArport>Disable</InsertVIArport>
			<SubstituteVIAAddr>Disable</SubstituteVIAAddr>
			<SendRespToSrcPort>Disable</SendRespToSrcPort>
			<STUNEnable>Disable</STUNEnable>
			<STUNTestEnable>Disable</STUNTestEnable>
			<STUNServer></STUNServer>
			<EXTIP></EXTIP>
			<EXTRTPPortMin>0</EXTRTPPortMin>
			<NATKeepAliveIntvl>0</NATKeepAliveIntvl>
		</NATSupportParameters>
	</SIP>
	<Provisioning>
		<ConfigurationProfile Enable="Disable">
			<ResyncOnReset>Disable</ResyncOnReset>
			<ResyncRandomDelay>0</ResyncRandomDelay>
			<ResyncPeriodic>10</ResyncPeriodic>
			<ResyncErrorRetryDelay>0</ResyncErrorRetryDelay>
			<ForcedResyncDelay>0</ForcedResyncDelay>
			<ResyncFromSIP>Disable</ResyncFromSIP>
			<ResyncAfterUpgradeAttempt>Disable</ResyncAfterUpgradeAttempt>
			<ResyncTrigger1>Disable</ResyncTrigger1>
			<ResyncTrigger2>Disable</ResyncTrigger2>
			<ResyncFailsOnFNF>Disable</ResyncFailsOnFNF>
			<ProfileRule></ProfileRule>
			<ReportRule/>
		</ConfigurationProfile>
		<FirmwareUpgrade Enable="Disable">
			<UpgradeErrorRetryDelay>0</UpgradeErrorRetryDelay>
			<DowngradeRevLimit>0</DowngradeRevLimit>
			<UpgradePeriodic>10</UpgradePeriodic>
			<UpgradeRule></UpgradeRule>
			<LicenseKeys></LicenseKeys>
		</FirmwareUpgrade>
	</Provisioning>
	<Custom>
		<JinDouYun Enable="Disable">
			<CurCall></CurCall>
			<LastCall></LastCall>
		</JinDouYun>
	</Custom>

	<Regional>
		<CallProgressTones CountryStands="0">
			<DialTone>350@-19,440@-19;10(*/0/1+2)</DialTone>
			<OutsideDialTone>420@-16;10(*/0/1)</OutsideDialTone>
			<BusyTone>480@-19,620@-19;10(.5/.5/1+2)</BusyTone>
			<ReorderTone>480@-19,620@-19;10(.25/.25/1+2)</ReorderTone>
			<OffHookWarningTone>480@-10,620@0;10(.125/.125/1+2)</OffHookWarningTone>
			<RingBackTone>440@-19,480@-19;*(2/4/1+2)</RingBackTone>
			<CallWaitingTone>440@-10;30(.3/9.7/1)</CallWaitingTone>
			<SIT1Tone>985@-16,1428@-16,1777@-16;20(.380/0/1,.380/0/2,.380/0/3,0/4/0)</SIT1Tone>
			<SIT2Tone>914@-16,1371@-16,1777@-16;20(.274/0/1,.274/0/2,.380/0/3,0/4/0)</SIT2Tone>
			<SIT3Tone>914@-16,1371@-16,1777@-16;20(.380/0/1,.380/0/2,.380/0/3,0/4/0)</SIT3Tone>
			<SIT4Tone>985@-16,1371@-16,1777@-16;20(.380/0/1,.274/0/2,.380/0/3,0/4/0)</SIT4Tone>
			<MWIDialTone>350@-19,440@-19;2(.1/.1/1+2);10(*/0/1+2)</MWIDialTone>
			<CfwdDialTone>350@-19,440@-19;2(.2/.2/1+2);10(*/0/1+2)</CfwdDialTone>
			<HoldingTone>600@-19;25(.1/.1/1,.1/.1/1,.1/9.5/1)</HoldingTone>
			<ConferenceTone>350@-19;20(.1/.1/1,.1/9.7/1)</ConferenceTone>
		</CallProgressTones>
		<ControlTimerValues>
			<ReorderDelay>5</ReorderDelay>
			<ReorderTime>12</ReorderTime>
			<CallBackExpires>1800</CallBackExpires>
			<CallBackRetryIntvl>30</CallBackRetryIntvl>
			<CallBackDelay>0.500</CallBackDelay>
			<InterdigitLongTimer>20</InterdigitLongTimer>
			<InterdigitShortTimer>8</InterdigitShortTimer>
		</ControlTimerValues>
		<Miscellaneous>
			<DTMFPlaybackLevel>0</DTMFPlaybackLevel>
			<DTMFPlaybackLength>0.000</DTMFPlaybackLength>
			<InbandDTMFBoost>0</InbandDTMFBoost>
		</Miscellaneous>
		<DateTime DateFormat="1" TimeFormat="1">
			<NTP Enable="Enable" Server="{{server_ip}}" TimeZone="{{time_zone}}"/>
			<Manual Date="2011/09/21" Time="09:52:04"/>
			<DaylightSavingTime Enable="Disable" Rule=""/>
		</DateTime>
	</Regional>
	<Phone>
		<General StationName="" Language="0" TextLogo=""/>
		<LineKey1 Extension="0" ShortName=""/>
		<LineKeyLEDPattern>
			<IdleLED></IdleLED>
			<RemoteUndefinedLED></RemoteUndefinedLED>
			<LocalSeizedLED></LocalSeizedLED>
			<RemoteSeizedLED></RemoteSeizedLED>
			<LocalProgressingLED></LocalProgressingLED>
			<RemoteProgressingLED></RemoteProgressingLED>
			<LocalRingingLED></LocalRingingLED>
			<RemoteRingingLED></RemoteRingingLED>
			<LocalActiveLED></LocalActiveLED>
			<RemoteActiveLED></RemoteActiveLED>
			<LocalHeldLED></LocalHeldLED>
			<RemoteHeldLED></RemoteHeldLED>
			<RegisterFailedLED></RegisterFailedLED>
			<DisabledLED></DisabledLED>
			<RegisteringLED></RegisteringLED>
			<CallBackActiveLED></CallBackActiveLED>
		</LineKeyLEDPattern>
		<SupplementaryServices>
			<DNDServ>Enable</DNDServ>
			<BlockANCServ>Enable</BlockANCServ>
			<CallBackServ>Disable</CallBackServ>
			<BlockCIDServ>Enable</BlockCIDServ>
			<SecureCallServ>Enable</SecureCallServ>
			<PagingServ>Enable</PagingServ>
			<SendKey>#</SendKey>
			<CallForward>
				<All Number="" EnableCode="*72" DisableCode="*73"/>
				<Busy Number="" EnableCode="*90" DisableCode="*91"/>
				<NoAnswer Number="" Delay="5" EnableCode="*92" DisableCode="*93"/>
			</CallForward>
		</SupplementaryServices>
		<AudioInputGain Handset="12" Headset="12" Speakerphone="12"/>
		<Voice>
			<EchoCancellation VADEnable="Disable" CNGEnable="Enable"/>
			<JitterBuffer Type="1" MaxDelay="300" MinDelay="0" NormalDelay="120"/>
		</Voice>
	</Phone>
{{py:n = 1}}
{{for extension in sip}}
	<Ext{{n}} Enable="Enable">
		<ShareLineAppearance>
			<ShareExt>0</ShareExt>
			<SharedUserID>0</SharedUserID>
		</ShareLineAppearance>
		<NATSettings>
			<NATMappingEnable>Disable</NATMappingEnable>
			<NATKeepAliveEnable>Disable</NATKeepAliveEnable>
			<NATKeepAliveMsg></NATKeepAliveMsg>
			<NATKeepAliveDest></NATKeepAliveDest>
		</NATSettings>
		<NetworkSettings>
			<SIPTOS_DiffServValue>104</SIPTOS_DiffServValue>
			<SIPCoSValue>3</SIPCoSValue>
			<RTPTOS_DiffServValue>184</RTPTOS_DiffServValue>
			<RTPCoSValue>6</RTPCoSValue>
			<NetworkJitterLevel>2</NetworkJitterLevel>
			<JitterBufferAdjustment>0</JitterBufferAdjustment>
		</NetworkSettings>
		<SIPSettings>
			<SIPPort>5060</SIPPort>
			<LocalSIPPort>5060</LocalSIPPort>
			<TransportType>1</TransportType>
			<SIP100RELEnable>Disable</SIP100RELEnable>
			<EXTSIPPort>5060</EXTSIPPort>
			<AuthResync-Reboot>Disable</AuthResync-Reboot>
			<SIPProxy-Require></SIPProxy-Require>
			<SIPRemote-Party-ID>Disable</SIPRemote-Party-ID>
			<ReferorByeDelay>4</ReferorByeDelay>
			<Refer-ToTargetContact>Disable</Refer-ToTargetContact>
			<RefereeByeDelay>0</RefereeByeDelay>
			<SIPDebugOption>0</SIPDebugOption>
			<ReferTargetByeDelay>0</ReferTargetByeDelay>
			<Sticky183>Disable</Sticky183>
		</SIPSettings>
		<CallFeatureSettings>
			<BlindAttn-XferEnable>Disable</BlindAttn-XferEnable>
			<MOHServer></MOHServer>
			<MessageWaiting>Enable</MessageWaiting>
			<AuthPage>Disable</AuthPage>
			<DefaultRing>1</DefaultRing>
			<AuthPageRealm></AuthPageRealm>
			<ConferenceBridgeURL></ConferenceBridgeURL>
			<AuthPagePassword></AuthPagePassword>
			<VoiceMailNumber></VoiceMailNumber>
			<StateAgent></StateAgent>
			<UseSRTP>0</UseSRTP>
			<PickupServiceCode>*8</PickupServiceCode>
		</CallFeatureSettings>
		<ProxyandRegistration>
			<Proxy>{{server_ip}}</Proxy>
			<UseOutboundProxy>Disable</UseOutboundProxy>
			<OutboundProxy></OutboundProxy>
			<UseOBProxyInDialog>Disable</UseOBProxyInDialog>
			<Register>Enable</Register>
			<MakeCallWithoutReg>Disable</MakeCallWithoutReg>
			<RegisterExpires>300</RegisterExpires>
			<SubscribeExpires>3600</SubscribeExpires>
			<AnsCallWithoutReg>Disable</AnsCallWithoutReg>
			<ProxyFallbackIntvl>3600</ProxyFallbackIntvl>
			<ProxyRedundancyMethod>0</ProxyRedundancyMethod>
		</ProxyandRegistration>
		<SubscriberInformation>
			<DisplayName>{{extension.description}}</DisplayName>
			<UserID>{{extension.account}}</UserID>
			<Password>{{extension.secret}}</Password>
			<UseAuthID>Disable</UseAuthID>
			<AuthID></AuthID>
			<MiniCertificate></MiniCertificate>
			<SRTPPrivateKey></SRTPPrivateKey>
		</SubscriberInformation>
		<AudioConfiguration>
			<PreferredCodec>0</PreferredCodec>
			<UsePrefCodecOnly>Disable</UsePrefCodecOnly>
			<G729aEnable>Enable</G729aEnable>
			<G722Enable>Enable</G722Enable>
			<G723Enable>Enable</G723Enable>
			<G726Enable>Enable</G726Enable>
			<ReleaseUnusedCodec>Enable</ReleaseUnusedCodec>
			<DTMFProcessAVT>Enable</DTMFProcessAVT>
			<SilenceSuppEnable>Disable</SilenceSuppEnable>
			<DTMFTxMethod>2</DTMFTxMethod>
		</AudioConfiguration>
		<DialPlan Rule="(xxxxxxxxxxxx.)" EnableIPDialing="Enable"/>
		<UDPKeepAlive Enable="Disable" Interval="15"/>
	</Ext{{n}}>
{{py:n += 1}}
{{endfor}}
{{for m in range(n,max_sip_accounts+1)}}
    <Ext{{m}} Enable="Disable">
        <ShareLineAppearance>
            <ShareExt>0</ShareExt>
            <SharedUserID>0</SharedUserID>
        </ShareLineAppearance>
        <NATSettings>
            <NATMappingEnable>Disable</NATMappingEnable>
            <NATKeepAliveEnable>Disable</NATKeepAliveEnable>
            <NATKeepAliveMsg></NATKeepAliveMsg>
            <NATKeepAliveDest></NATKeepAliveDest>
        </NATSettings>
        <NetworkSettings>
            <SIPTOS_DiffServValue>104</SIPTOS_DiffServValue>
            <SIPCoSValue>3</SIPCoSValue>
            <RTPTOS_DiffServValue>184</RTPTOS_DiffServValue>
            <RTPCoSValue>6</RTPCoSValue>
            <NetworkJitterLevel>2</NetworkJitterLevel>
            <JitterBufferAdjustment>0</JitterBufferAdjustment>
        </NetworkSettings>
        <SIPSettings>
            <SIPPort>5060</SIPPort>
            <LocalSIPPort>5060</LocalSIPPort>
            <TransportType>1</TransportType>
            <SIP100RELEnable>Disable</SIP100RELEnable>
            <EXTSIPPort>5060</EXTSIPPort>
            <AuthResync-Reboot>Disable</AuthResync-Reboot>
            <SIPProxy-Require></SIPProxy-Require>
            <SIPRemote-Party-ID>Disable</SIPRemote-Party-ID>
            <ReferorByeDelay>4</ReferorByeDelay>
            <Refer-ToTargetContact>Disable</Refer-ToTargetContact>
            <RefereeByeDelay>0</RefereeByeDelay>
            <SIPDebugOption>0</SIPDebugOption>
            <ReferTargetByeDelay>0</ReferTargetByeDelay>
            <Sticky183>Disable</Sticky183>
        </SIPSettings>
        <CallFeatureSettings>
            <BlindAttn-XferEnable>Disable</BlindAttn-XferEnable>
            <MOHServer></MOHServer>
            <MessageWaiting>Enable</MessageWaiting>
            <AuthPage>Disable</AuthPage>
            <DefaultRing>1</DefaultRing>
            <AuthPageRealm></AuthPageRealm>
            <ConferenceBridgeURL></ConferenceBridgeURL>
            <AuthPagePassword></AuthPagePassword>
            <VoiceMailNumber></VoiceMailNumber>
            <StateAgent></StateAgent>
            <UseSRTP>0</UseSRTP>
            <PickupServiceCode>*8</PickupServiceCode>
        </CallFeatureSettings>
        <ProxyandRegistration>
            <Proxy>{{server_ip}}</Proxy>
            <UseOutboundProxy>Disable</UseOutboundProxy>
            <OutboundProxy></OutboundProxy>
            <UseOBProxyInDialog>Disable</UseOBProxyInDialog>
            <Register>Enable</Register>
            <MakeCallWithoutReg>Disable</MakeCallWithoutReg>
            <RegisterExpires>300</RegisterExpires>
            <SubscribeExpires>3600</SubscribeExpires>
            <AnsCallWithoutReg>Disable</AnsCallWithoutReg>
            <ProxyFallbackIntvl>3600</ProxyFallbackIntvl>
            <ProxyRedundancyMethod>0</ProxyRedundancyMethod>
        </ProxyandRegistration>
        <SubscriberInformation>
            <DisplayName></DisplayName>
            <UserID></UserID>
            <Password></Password>
            <UseAuthID>Disable</UseAuthID>
            <AuthID></AuthID>
            <MiniCertificate></MiniCertificate>
            <SRTPPrivateKey></SRTPPrivateKey>
        </SubscriberInformation>
        <AudioConfiguration>
            <PreferredCodec>0</PreferredCodec>
            <UsePrefCodecOnly>Disable</UsePrefCodecOnly>
            <G729aEnable>Enable</G729aEnable>
            <G722Enable>Enable</G722Enable>
            <G723Enable>Enable</G723Enable>
            <G726Enable>Enable</G726Enable>
            <ReleaseUnusedCodec>Enable</ReleaseUnusedCodec>
            <DTMFProcessAVT>Enable</DTMFProcessAVT>
            <SilenceSuppEnable>Disable</SilenceSuppEnable>
            <DTMFTxMethod>2</DTMFTxMethod>
        </AudioConfiguration>
        <DialPlan Rule="(xxxxxxxxxxxx.)" EnableIPDialing="Enable"/>
        <UDPKeepAlive Enable="Disable" Interval="15"/>
    </Ext{{m}}>
{{endfor}}
	<User>
		<SpeedDial>
			<SpeedDial2></SpeedDial2>
			<SpeedDial3></SpeedDial3>
			<SpeedDial4></SpeedDial4>
			<SpeedDial5></SpeedDial5>
			<SpeedDial6></SpeedDial6>
			<SpeedDial7></SpeedDial7>
			<SpeedDial8></SpeedDial8>
			<SpeedDial9></SpeedDial9>
		</SpeedDial>
		<SupplementaryServices>
			<DNDSetting>Disable</DNDSetting>
			<DialAssistance>Enable</DialAssistance>
			<AutoAnswerPage>Disable</AutoAnswerPage>
			<PreferredAudioDevice>1</PreferredAudioDevice>
			<SendAudioToSpeaker>Enable</SendAudioToSpeaker>
			<MissCallShortCut>Enable</MissCallShortCut>
			<BlockCidSeting>Disable</BlockCidSeting>
			<BlockCidCallSeting>Enable</BlockCidCallSeting>
			<BlockAncSeting>Disable</BlockAncSeting>
			<SecureAllCallSetting>Enable</SecureAllCallSetting>
			<PaginSetting>Enable</PaginSetting>
			<CallBackSetting>Enable</CallBackSetting>
			<CallReturnSetting>Enable</CallReturnSetting>
			<CallwaitSetting>Enable</CallwaitSetting>
			<CallwaitingToneSetting>Enable</CallwaitingToneSetting>
		</SupplementaryServices>
		<AudioVolume Ringer="6" Speaker="5" Handset="5" Headset="5"/>
		<LCD Contrast="38" Brightness="38" BacklightLevel="3" BacklightTime="10"/>
		<Buzzer Enable="1"/>
		<RingTone Enable="1"/>
		<RememberAccount Enable="1"/>
		<OpenSuppressionNoise Enable="0"/>
		<ProgramKey>
			<ProgramKey1 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey2 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey3 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey4 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey5 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey6 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey7 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey8 KeyMode="0" AccountNo="0" Number=""/>
			<ProgramKey9 KeyMode="0" AccountNo="0" Number=""/>
		</ProgramKey>
		<VoiceMailSetting From="" To="" SmtpServer="" Username="" Passwd=""/>
	</User>
	<AccessManage/>
</PhoneConfig>

