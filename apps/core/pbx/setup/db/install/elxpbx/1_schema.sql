CREATE TABLE IF NOT EXISTS `globals` (
  `organization_domain` varchar(50) NOT NULL,
  `variable` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`organization_domain`,`variable`),
  INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `globals_settings` (
  `variable` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`variable`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `reload_dialplan` (
  `organization_domain` varchar(50) NOT NULL,
  `show_msg` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`organization_domain`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `sip_general` (
	  `organization_domain` varchar(50) NOT NULL, 
      `port` int(5) DEFAULT NULL,
      `defaultuser` varchar(10) DEFAULT NULL,
      `useragent` varchar(20) DEFAULT NULL,
      `host` varchar(40) NULL default 'dynamic',
      `type` enum('friend','user','peer') default 'friend',
      `context` varchar(40) DEFAULT NULL,
      `deny` varchar(40) DEFAULT NULL,
      `permit` varchar(40) DEFAULT NULL,
      `canreinvite` enum('yes','no') DEFAULT 'yes',
      `transport` enum('udp','tcp','udp,tcp','tcp,udp') DEFAULT NULL,
      `dtmfmode` enum('rfc2833','info','shortinfo','inband','auto') DEFAULT NULL,
      `directmedia` enum('yes','no','nonat','update') DEFAULT NULL,
      `nat` enum('yes','no','never','route') DEFAULT NULL,
      `language` varchar(40) DEFAULT NULL,
      `tonezone` varchar(3) DEFAULT NULL,
      `disallow` varchar(40) DEFAULT NULL,
      `allow` varchar(40) DEFAULT NULL,
      `trustrpid` enum('yes','no') DEFAULT NULL,
      `progressinband` enum('yes','no','never') DEFAULT NULL,
      `promiscredir` enum('yes','no') DEFAULT NULL,
      `useclientcode` enum('yes','no') DEFAULT NULL,
      `accountcode` varchar(40) DEFAULT NULL,
      `callcounter` enum('yes','no') DEFAULT NULL,
      `busylevel` int(11) DEFAULT NULL,
      `allowoverlap` enum('yes','no') DEFAULT NULL,
      `allowsubscribe` enum('yes','no') DEFAULT NULL,
      `videosupport` enum('yes','no') DEFAULT NULL,
      `maxcallbitrate` int(11) DEFAULT NULL,
      `rfc2833compensate` enum('yes','no') DEFAULT NULL,
      `session-timers` enum('accept','refuse','originate') DEFAULT NULL,
      `session-expires` int(11) DEFAULT NULL,
      `session-minse` int(11) DEFAULT NULL,
      `session-refresher` enum('uac','uas') DEFAULT NULL,
      `t38pt_usertpsource` varchar(40) DEFAULT NULL,
      `regexten` varchar(40) DEFAULT NULL,
      `qualify` varchar(40) DEFAULT NULL,
      `rtptimeout` int(11) DEFAULT NULL,
      `rtpholdtimeout` int(11) DEFAULT NULL,
      `sendrpid` enum('yes','no') DEFAULT NULL,
      `outboundproxy` varchar(40) DEFAULT NULL,
      `callbackextension` varchar(40) DEFAULT NULL,
      `timert1` int(11) DEFAULT NULL,
      `timerb` int(11) DEFAULT NULL,
      `qualifyfreq` int(11) DEFAULT NULL,
      `constantssrc` enum('yes','no') DEFAULT NULL,
      `usereqphone` enum('yes','no') DEFAULT NULL,
      `textsupport` enum('yes','no') DEFAULT NULL,
      `faxdetect` enum('yes','no') DEFAULT NULL,
      `buggymwi` enum('yes','no') DEFAULT NULL,
      `cid_number` varchar(40) DEFAULT NULL,
      `callingpres` enum('allowed_not_screened','allowed_passed_screen','allowed_failed_screen','allowed','prohib_not_screened','prohib_passed_screen','prohib_failed_screen','prohib') DEFAULT NULL,
      `mohinterpret` varchar(40) DEFAULT NULL,
      `mohsuggest` varchar(40) DEFAULT NULL,
      `parkinglot` varchar(40) DEFAULT NULL,
      `hasvoicemail` enum('yes','no') DEFAULT NULL,
      `subscribemwi` enum('yes','no') DEFAULT NULL,
      `vmexten` varchar(40) DEFAULT NULL,
      `autoframing` enum('yes','no') DEFAULT NULL,
      `rtpkeepalive` int(11) DEFAULT NULL,
      `call-limit` int(11) DEFAULT NULL,
      `g726nonstandard` enum('yes','no') DEFAULT NULL,
      `ignoresdpversion` enum('yes','no') DEFAULT NULL,
      `allowtransfer` enum('yes','no') DEFAULT NULL,
      PRIMARY KEY (`organization_domain`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `iax_general` (
  `organization_domain` varchar(50) NOT NULL, 
  `type` varchar(10) NOT NULL default 'friend', -- friend/user/peer
  `context` varchar(40) NOT NULL,
  `host` varchar(40) NOT NULL default 'dynamic',
  `ipaddr` varchar(40) NULL, -- Must be updateable by Asterisk user
  `port` int(5) NULL, -- Must be updateable by Asterisk user
  `sourceaddress` varchar(20) NULL,
  `mask` varchar(20) NULL,
  `regexten` varchar(40) NULL,
  `regseconds` int(11) NULL, -- Must be updateable by Asterisk user
  `accountcode` varchar(20) NULL, 
  `mohinterpret` varchar(20) NULL, 
  `mohsuggest` varchar(20) NULL, 
  `inkeys` varchar(40) NULL, 
  `outkey` varchar(40) NULL, 
  `language` varchar(10) NULL, 
  `sendani` varchar(10) NULL, -- yes/no
  `maxauthreq` varchar(5) NULL, -- Maximum outstanding AUTHREQ calls {1-32767}
  `requirecalltoken` varchar(4) NULL, -- yes/no/auto
  `encryption` varchar(20) NULL, -- aes128/yes/no
  `transfer` varchar(10) NULL, -- mediaonly/yes/no
  `jitterbuffer` varchar(3) NULL, -- yes/no
  `forcejitterbuffer` varchar(3) NULL, -- yes/no
  `disallow` varchar(40) NULL, -- all/{list-of-codecs}
  `allow` varchar(40) NULL, -- all/{list-of-codecs}
  `codecpriority` varchar(40) NULL, 
  `qualify` varchar(10) NULL, -- yes/no/{number of milliseconds}
  `qualifysmoothing` varchar(10) NULL, -- yes/no
  `qualifyfreqok` varchar(10) NULL, -- {number of milliseconds}|60000
  `qualifyfreqnotok` varchar(10) NULL, -- {number of milliseconds}|10000
  `timezone` varchar(20) NULL, 
  `adsi` varchar(10) NULL, -- yes/no
  `amaflags` varchar(20) NULL, 
  `setvar` varchar(200) NULL, 
  `permit` varchar(40) DEFAULT NULL,
  `deny` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`organization_domain`)
)ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `voicemail_general` (
	organization_domain varchar(50) NOT NULL, 
	-- Mailbox context.
	context CHAR(80) NOT NULL DEFAULT 'default',
	-- Attach sound file to email - YES/no
	attach CHAR(3),
	-- Send email from this address
	serveremail CHAR(80),
	-- Prompts in alternative language
	language CHAR(20),
	-- Alternative timezone, as defined in voicemail.conf
	tz CHAR(30),
	-- Delete voicemail from server after sending email notification - yes/NO
	deletevoicemail CHAR(3),
	-- Read back CallerID information during playback - yes/NO
	saycid CHAR(3),
	-- Allow user to send voicemail from within VoicemailMain - YES/no
	sendvoicemail CHAR(3),
	-- Listen to voicemail and approve before sending - yes/NO
	review CHAR(3),
	-- Warn user a temporary greeting exists - yes/NO
	tempgreetwarn CHAR(3),
	-- Allow '0' to jump out during greeting - yes/NO
	operator CHAR(3),
	-- Hear date/time of message within VoicemailMain - YES/no
	envelope CHAR(3),
	-- Hear length of message within VoicemailMain - yes/NO
	sayduration CHAR(3),
	-- Minimum duration in minutes to say
	saydurationm INT(3),
	-- Force new user to record name when entering voicemail - yes/NO
	forcename CHAR(3),
	-- Force new user to record greetings when entering voicemail - yes/NO
	forcegreetings CHAR(3),
	-- Context in which to dial extension for callback
	callback CHAR(80),
	-- Context in which to dial extension (from advanced menu)
	dialout CHAR(80),
	-- Context in which to execute 0 or * escape during greeting
	exitcontext CHAR(80),
	-- Maximum messages in a folder (100 if not specified)
	maxmsg INT(5),
	-- Increase DB gain on recorded message by this amount (0.0 means none)
	volgain DECIMAL(5,2),
	-- IMAP user for authentication (if using IMAP storage)
	imapuser VARCHAR(80),
	-- IMAP password for authentication (if using IMAP storage)
	imappassword VARCHAR(80),
	PRIMARY KEY (`organization_domain`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `sip` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(40) NOT NULL,
      `context` varchar(40) DEFAULT NULL,
      `callingpres` enum('allowed_not_screened','allowed_passed_screen','allowed_failed_screen','allowed','prohib_not_screened','prohib_passed_screen','prohib_failed_screen','prohib') DEFAULT NULL,
      `deny` varchar(40) DEFAULT NULL,
      `permit` varchar(40) DEFAULT NULL,
      `secret` varchar(40) DEFAULT NULL,
      `md5secret` varchar(40) DEFAULT NULL,
      `remotesecret` varchar(40) DEFAULT NULL,
      `transport` enum('udp','tcp','udp,tcp','tcp,udp') DEFAULT NULL,
      `host` varchar(40) NOT NULL DEFAULT 'dynamic',
      `nat` enum('yes','no','never','route') DEFAULT NULL,
      `type` enum('friend','user','peer') DEFAULT 'friend',
      `accountcode` varchar(40) DEFAULT NULL,
      `amaflags` varchar(40) DEFAULT NULL,
      `callgroup` varchar(40) DEFAULT NULL,
      `callerid` varchar(40) DEFAULT NULL,
      `canreinvite` enum('yes','no') DEFAULT 'yes',
      `defaultip` varchar(40) DEFAULT NULL,
      `dtmfmode` enum('rfc2833','info','shortinfo','inband','auto') DEFAULT NULL,
      `fromuser` varchar(40) DEFAULT NULL,
      `fromdomain` varchar(40) DEFAULT NULL,
      `insecure` varchar(40) DEFAULT NULL,
      `language` varchar(40) DEFAULT NULL,
      `tonezone` varchar(3) DEFAULT NULL,
      `mailbox` varchar(40) DEFAULT NULL,
      `pickupgroup` varchar(40) DEFAULT NULL,
      `qualify` char(3) DEFAULT 'yes',
      `regexten` varchar(40) DEFAULT NULL,
      `rtptimeout` int(11) DEFAULT NULL,
      `rtpholdtimeout` int(11) DEFAULT NULL,
      `setvar` varchar(40) DEFAULT NULL,
      `disallow` varchar(40) DEFAULT NULL,
      `allow` varchar(40) DEFAULT NULL,
      `fullcontact` varchar(35) NOT NULL DEFAULT '',
      `ipaddr` varchar(45) DEFAULT NULL,
      `port` int(5) DEFAULT NULL,
      `username` varchar(80) NOT NULL DEFAULT '',
      `defaultuser` varchar(10) NOT NULL DEFAULT '',
      `dial` varchar(50) DEFAULT NULL,
      `directmedia` enum('yes','no','nonat','update') DEFAULT NULL,
      `trustrpid` enum('yes','no') DEFAULT NULL,
      `sendrpid` enum('yes','no') DEFAULT NULL,
      `progressinband` enum('yes','no','never') DEFAULT NULL,
      `promiscredir` enum('yes','no') DEFAULT NULL,
      `useclientcode` enum('yes','no') DEFAULT NULL,
      `callcounter` enum('yes','no') DEFAULT NULL,
      `busylevel` int(11) DEFAULT NULL,
      `allowoverlap` enum('yes','no') DEFAULT NULL,
      `allowsubscribe` enum('yes','no') DEFAULT NULL,
      `allowtransfer` enum('yes','no') DEFAULT 'no',
      `lastms` int(11) NOT NULL DEFAULT '0',
      `useragent` varchar(20) NOT NULL DEFAULT '',
      `regseconds` int(11) NOT NULL DEFAULT '0',
      `regserver` varchar(100) NOT NULL DEFAULT '',
      `videosupport` enum('yes','no') DEFAULT NULL,
      `maxcallbitrate` int(11) DEFAULT NULL,
      `rfc2833compensate` enum('yes','no') DEFAULT NULL,
      `session-timers` enum('accept','refuse','originate') DEFAULT NULL,
      `session-expires` int(11) DEFAULT NULL,
      `session-minse` int(11) DEFAULT NULL,
      `session-refresher` enum('uac','uas') DEFAULT NULL,
      `t38pt_usertpsource` varchar(40) DEFAULT NULL,
      `outboundproxy` varchar(40) DEFAULT NULL,
      `callbackextension` varchar(40) DEFAULT NULL,
      `timert1` int(11) DEFAULT NULL,
      `timerb` int(11) DEFAULT NULL,
      `qualifyfreq` int(5) unsigned DEFAULT '120',
      `constantssrc` enum('yes','no') DEFAULT NULL,
      `contactpermit` varchar(40) DEFAULT NULL,
      `contactdeny` varchar(40) DEFAULT NULL,
      `usereqphone` enum('yes','no') DEFAULT NULL,
      `textsupport` enum('yes','no') DEFAULT NULL,
      `faxdetect` enum('yes','no') DEFAULT NULL,
      `buggymwi` enum('yes','no') DEFAULT NULL,
      `auth` varchar(40) DEFAULT NULL,
      `fullname` varchar(40) DEFAULT NULL,
      `trunkname` varchar(40) DEFAULT NULL,
      `cid_number` varchar(40) DEFAULT NULL,
      `mohinterpret` varchar(40) DEFAULT NULL,
      `mohsuggest` varchar(40) DEFAULT NULL,
      `parkinglot` varchar(40) DEFAULT NULL,
      `hasvoicemail` enum('yes','no') DEFAULT NULL,
      `subscribemwi` enum('yes','no') DEFAULT NULL,
      `vmexten` varchar(40) DEFAULT NULL,
      `autoframing` enum('yes','no') DEFAULT NULL,
      `rtpkeepalive` int(11) DEFAULT NULL,
      `call-limit` int(11) DEFAULT NULL,
      `g726nonstandard` enum('yes','no') DEFAULT NULL,
	  `ignoresdpversion` enum('yes','no') DEFAULT NULL,
	  `organization_domain` varchar(50) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `name` (`name`),
      KEY `ipaddr` (`ipaddr`,`port`),
      KEY `host` (`host`,`port`),
      INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `iax` (
  `organization_domain` varchar(50) NOT NULL,
  `name` varchar(40) NOT NULL default '',
  `type` varchar(10) NOT NULL default 'friend', -- friend/user/peer
  `username` varchar(40) NULL, -- username to send as peer
  `mailbox` varchar(40) NULL, -- mailbox@context
  `secret` varchar(40) NULL,
  `dial` varchar(50) DEFAULT NULL,
  `dbsecret` varchar(40) NULL, -- In AstDB, location to store/retrieve secret
  `context` varchar(40) NULL,
  `regcontext` varchar(40) NULL,
  `host` varchar(40) NULL default 'dynamic',
  `ipaddr` varchar(45) NULL, -- Must be updateable by Asterisk user
  `port` int(5) NULL, -- Must be updateable by Asterisk user
  `defaultip` varchar(20) NULL,
  `sourceaddress` varchar(20) NULL,
  `mask` varchar(20) NULL,
  `regexten` varchar(40) NULL,
  `regseconds`  int(11) NOT NULL DEFAULT '0', -- Must be updateable by Asterisk user
  `accountcode` varchar(20) NULL, 
  `mohinterpret` varchar(20) NULL, 
  `mohsuggest` varchar(20) NULL, 
  `inkeys` varchar(40) NULL, 
  `outkey` varchar(40) NULL, 
  `language` varchar(10) NULL, 
  `callerid` varchar(100) NULL, -- The whole callerid string, or broken down in the next 3 fields
  `cid_number` varchar(40) NULL, -- The number portion of the callerid
  `sendani` varchar(10) NULL, -- yes/no
  `fullname` varchar(40) NULL, -- The name portion of the callerid
  `trunk` varchar(3) NULL, -- Yes/no
  `auth` varchar(20) NULL, -- RSA/md5/plaintext
  `maxauthreq` varchar(5) NULL, -- Maximum outstanding AUTHREQ calls {1-32767}
  `requirecalltoken` varchar(4) NULL, -- yes/no/auto
  `encryption` varchar(20) NULL, -- aes128/yes/no
  `transfer` varchar(10) NULL, -- mediaonly/yes/no
  `jitterbuffer` varchar(3) NULL, -- yes/no
  `forcejitterbuffer` varchar(3) NULL, -- yes/no
  `disallow` varchar(40) NULL, -- all/{list-of-codecs}
  `allow` varchar(40) NULL, -- all/{list-of-codecs}
  `codecpriority` varchar(40) NULL, 
  `qualify` varchar(10) NULL, -- yes/no/{number of milliseconds}
  `qualifysmoothing` varchar(10) NULL, -- yes/no
  `qualifyfreqok` varchar(10) NULL, -- {number of milliseconds}|60000
  `qualifyfreqnotok` varchar(10) NULL, -- {number of milliseconds}|10000
  `timezone` varchar(20) NULL, 
  `adsi` varchar(10) NULL, -- yes/no
  `amaflags` varchar(20) NULL, 
  `setvar` varchar(200) NULL, 
  `permit` varchar(40) DEFAULT NULL,
  `deny` varchar(40) DEFAULT NULL,
  PRIMARY KEY  (`name`),
  INDEX name (name, host),
  INDEX name2 (name, ipaddr, port),
  INDEX ipaddr (ipaddr, port),
  INDEX host (host, port),
  INDEX organization_domain (organization_domain)
)ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS voicemail (
	-- All of these column names are very specific, including "uniqueid".  Do not change them if you wish voicemail to work.
	uniqueid INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	-- Mailbox context.
	context CHAR(80) NOT NULL DEFAULT 'default',
	-- Mailbox number.  Should be numeric.
	mailbox CHAR(80) NOT NULL,
	-- Must be numeric.  Negative if you don't want it to be changed from VoicemailMain
	password CHAR(80) NOT NULL,
	-- Used in email and for Directory app
	fullname CHAR(80),
	-- Email address (will get sound file if attach=yes)
	email CHAR(80),
	-- Email address (won't get sound file)
	pager CHAR(80),
	-- Attach sound file to email - YES/no
	attach CHAR(3),
	-- Which sound format to attach
	attachfmt CHAR(10),
	-- Send email from this address
	serveremail CHAR(80),
	-- Prompts in alternative language
	language CHAR(20),
	-- Alternative timezone, as defined in voicemail.conf
	tz CHAR(30),
	-- Delete voicemail from server after sending email notification - yes/NO
	deletevoicemail CHAR(3),
	-- Read back CallerID information during playback - yes/NO
	saycid CHAR(3),
	-- Allow user to send voicemail from within VoicemailMain - YES/no
	sendvoicemail CHAR(3),
	-- Listen to voicemail and approve before sending - yes/NO
	review CHAR(3),
	-- Warn user a temporary greeting exists - yes/NO
	tempgreetwarn CHAR(3),
	-- Allow '0' to jump out during greeting - yes/NO
	operator CHAR(3),
	-- Hear date/time of message within VoicemailMain - YES/no
	envelope CHAR(3),
	-- Hear length of message within VoicemailMain - yes/NO
	sayduration CHAR(3),
	-- Minimum duration in minutes to say
	saydurationm INT(3),
	-- Force new user to record name when entering voicemail - yes/NO
	forcename CHAR(3),
	-- Force new user to record greetings when entering voicemail - yes/NO
	forcegreetings CHAR(3),
	-- Context in which to dial extension for callback
	callback CHAR(80),
	-- Context in which to dial extension (from advanced menu)
	dialout CHAR(80),
	-- Context in which to execute 0 or * escape during greeting
	exitcontext CHAR(80),
	-- Maximum messages in a folder (100 if not specified)
	maxmsg INT(5),
	-- Increase DB gain on recorded message by this amount (0.0 means none)
	volgain DECIMAL(5,2),
	-- IMAP user for authentication (if using IMAP storage)
	imapuser VARCHAR(80),
	-- IMAP password for authentication (if using IMAP storage)
	imappassword VARCHAR(80),
	stamp timestamp,
	organization_domain varchar(50) NOT NULL,
	INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `extension` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `organization_domain` varchar(50) NOT NULL,
	  `context` varchar(40) NOT NULL,
      `exten` int(20) NOT NULL,
      `tech` varchar(20) NOT NULL,
	  `dial` varchar(40) DEFAULT NULL,
	  `device` varchar(40) DEFAULT NULL,
      `voicemail` varchar(40) DEFAULT 'no', 
      `rt` varchar(20) DEFAULT NULL,
      `record_in` enum('on_demand','always','never') DEFAULT 'on_demand',
      `record_out` enum('on_demand','always','never') DEFAULT 'on_demand',
      `outboundcid` varchar(50) default NULL,
      `alias` varchar(50) default NULL,
      `mohclass` varchar(80) default 'default',
	  `noanswer` varchar(100) default NULL,
      PRIMARY KEY (`id`),
      INDEX organization_domain (organization_domain)
)ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `fax` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `organization_domain` varchar(50) NOT NULL,
	  `context` varchar(40) NOT NULL,
      `exten` int(20) NOT NULL,
      `tech` varchar(20) NOT NULL,
	  `dial` varchar(40) DEFAULT NULL,
	  `device` varchar(40) DEFAULT NULL,
      `rt` varchar(20) DEFAULT NULL,
      `callerid_name` varchar(20) DEFAULT NULL,
      `callerid_number` varchar(40) DEFAULT NULL, 
      PRIMARY KEY (`id`),
      INDEX organization_domain (organization_domain)
)ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `trunks` (
  `organization_domain` varchar(50) NOT NULL,  
  `trunkid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL default '',
  `tech` varchar(20) NOT NULL,
  `outcid` varchar(40) NOT NULL default '',
  `keepcid` varchar(4) default 'off',
  `maxchans` varchar(6) default '',
  `failscript` varchar(255) NOT NULL default '',
  `dialoutprefix` varchar(255) NOT NULL default '',
  `channelid` varchar(255) NOT NULL default '',
  `usercontext` varchar(255) default NULL,
  `provider` varchar(40) default NULL,
  `disabled` varchar(4) default 'off',
  INDEX organization_domain (organization_domain),
  PRIMARY KEY  (`trunkid`,`tech`,`channelid`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `trunk_dialpatterns` (
  `trunkid` int(11) NOT NULL default '0',
  `match_pattern_prefix` varchar(50) NOT NULL default '',
  `match_pattern_pass` varchar(50) NOT NULL default '',
  `prepend_digits` varchar(50) NOT NULL default '',
  `seq` int(11) NOT NULL default '0',
  PRIMARY KEY  (`trunkid`,`match_pattern_prefix`,`match_pattern_pass`,`prepend_digits`,`seq`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `features_code_settings` (
  `name` varchar(50) NOT NULL,
  `description` varchar(50) default NULL,
  `default_code` varchar(40) NOT NULL, 
  `estado` enum('enabled','disabled') default 'enabled',
  PRIMARY KEY (`name`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `features_code` (
  `organization_domain` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(40) default NULL,
  `estado` enum('enabled','disabled') default 'enabled',
  PRIMARY KEY (`organization_domain`,`name`),
  INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

insert into `features_code_settings` (name,default_code,description,estado) values("blacklist_num","*30","Blacklist a number","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("blacklist_lcall","*32","Blacklist the last caller","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("blacklist_rm","*31","Blacklist remove a number","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_all_act","*72","Call Forward All Activate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_all_desact","*73","Call Forward All Desactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_all_promp","*74","Call Forward All Promting Deactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_busy_act","*90","Call Forward Busy Activate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_busy_desact","*91","Call Forward Busy Desactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_busy_promp","*92","Call Forward Busy Promting Deactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_nu_act","*52","Call Forward No Answer/Unavailable Activate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_nu_desact","*53","Call Forward No Answer/Unavailable Deactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cf_toggle","*740","Call Forward Toggle","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cw_act","*70","Call Waiting Activate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("cw_desact","*71","Call Waiting Desactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("dictation_email","*35","Email Completed Dictation","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("dictation_perform","*34","Perform Dictation","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("dnd_act","*78","DND Activate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("dnd_desact","*79","DND Desactivate","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("dnd_toggle","*76","DND Toggle","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("fm_toggle","*21","Findme Follow Toggle","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("call_trace","*69","Call Trace","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("directory","#","Directory","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("echo_test","*43","Echo Test","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("speak_u_exten","*65","Speak Your Exten Number","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("speak_clock","*60","Speaking Clock","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("pbdirectory","411","Phonebook dial-by-name directory","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("queue_toggle","*45","Queue Toggle","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("recording_check","*99","Check Recording","enabled");  
insert into `features_code_settings` (name,default_code,description,estado) values("recording_save","*77","Save Recording","enabled");  
insert into `features_code_settings` (name,default_code,description,estado) values("speeddial_set","*75","Set user speed dial","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("speeddial_prefix","*0","Speeddial Prefix","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("voicemail_dial","*98","Dial Voicemail","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("voicemail_mine","*97","My Voicemail","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("sim_in_call","7777","Simulate Incoming Call","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("direct_call_pickup","**","Directed Call Pickup","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("user_logoff","*12","Simulate Incoming Call","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("user_logon","*11","Simulate Incoming Call","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("pickup","*8","Asterisk General Call Pickup","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("blind_transfer","#1","In-Call Asterisk Blind Transfer","enabled");
insert into `features_code_settings` (name,default_code,description,estado) values("attended_transfer","*2","In-Call Asterisk Attended Transfer","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("one_touch_monitor","*1","In-Call Asterisk Toggle Call Recording","enabled"); 
insert into `features_code_settings` (name,default_code,description,estado) values("disconnect_call","**","In-Call Asterisk Disconnect Code","enabled"); 


CREATE TABLE IF NOT EXISTS `sip_settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `port` int(5) DEFAULT NULL,
      `defaultuser` varchar(10) DEFAULT NULL,
      `useragent` varchar(20) DEFAULT NULL,
      `host` varchar(40) NULL default 'dynamic',
      `type` enum('friend','user','peer') default 'friend',
      `context` varchar(40) DEFAULT NULL,
      `deny` varchar(40) DEFAULT NULL,
      `permit` varchar(40) DEFAULT NULL,
      `canreinvite` enum('yes','no') DEFAULT 'yes',
      `transport` enum('udp','tcp','udp,tcp','tcp,udp') DEFAULT NULL,
      `dtmfmode` enum('rfc2833','info','shortinfo','inband','auto') DEFAULT NULL,
      `directmedia` enum('yes','no','nonat','update') DEFAULT NULL,
      `nat` enum('yes','no','never','route') DEFAULT NULL,
      `language` varchar(40) DEFAULT NULL,
      `tonezone` varchar(3) DEFAULT NULL,
      `disallow` varchar(40) DEFAULT NULL,
      `allow` varchar(40) DEFAULT NULL,
      `trustrpid` enum('yes','no') DEFAULT NULL,
      `progressinband` enum('yes','no','never') DEFAULT NULL,
      `promiscredir` enum('yes','no') DEFAULT NULL,
      `useclientcode` enum('yes','no') DEFAULT NULL,
      `accountcode` varchar(40) DEFAULT NULL,
      `callcounter` enum('yes','no') DEFAULT NULL,
      `busylevel` int(11) DEFAULT NULL,
      `allowoverlap` enum('yes','no') DEFAULT NULL,
      `allowsubscribe` enum('yes','no') DEFAULT NULL,
      `videosupport` enum('yes','no') DEFAULT NULL,
      `maxcallbitrate` int(11) DEFAULT NULL,
      `rfc2833compensate` enum('yes','no') DEFAULT NULL,
      `session-timers` enum('accept','refuse','originate') DEFAULT NULL,
      `session-expires` int(11) DEFAULT NULL,
      `session-minse` int(11) DEFAULT NULL,
      `session-refresher` enum('uac','uas') DEFAULT NULL,
      `t38pt_usertpsource` varchar(40) DEFAULT NULL,
      `regexten` varchar(40) DEFAULT NULL,
      `qualify` varchar(40) DEFAULT NULL,
      `rtptimeout` int(11) DEFAULT NULL,
      `rtpholdtimeout` int(11) DEFAULT NULL,
      `sendrpid` enum('yes','no') DEFAULT NULL,
      `outboundproxy` varchar(40) DEFAULT NULL,
      `callbackextension` varchar(40) DEFAULT NULL,
      `timert1` int(11) DEFAULT NULL,
      `timerb` int(11) DEFAULT NULL,
      `qualifyfreq` int(11) DEFAULT NULL,
      `constantssrc` enum('yes','no') DEFAULT NULL,
      `usereqphone` enum('yes','no') DEFAULT NULL,
      `textsupport` enum('yes','no') DEFAULT NULL,
      `faxdetect` enum('yes','no') DEFAULT NULL,
      `buggymwi` enum('yes','no') DEFAULT NULL,
      `cid_number` varchar(40) DEFAULT NULL,
      `callingpres` enum('allowed_not_screened','allowed_passed_screen','allowed_failed_screen','allowed','prohib_not_screened','prohib_passed_screen','prohib_failed_screen','prohib') DEFAULT NULL,
      `mohinterpret` varchar(40) DEFAULT NULL,
      `mohsuggest` varchar(40) DEFAULT NULL,
      `parkinglot` varchar(40) DEFAULT NULL,
      `hasvoicemail` enum('yes','no') DEFAULT NULL,
      `subscribemwi` enum('yes','no') DEFAULT NULL,
      `vmexten` varchar(40) DEFAULT NULL,
      `autoframing` enum('yes','no') DEFAULT NULL,
      `rtpkeepalive` int(11) DEFAULT NULL,
      `call-limit` int(11) DEFAULT NULL,
      `g726nonstandard` enum('yes','no') DEFAULT NULL,
      `ignoresdpversion` enum('yes','no') DEFAULT NULL,
      `allowtransfer` enum('yes','no') DEFAULT NULL,
      PRIMARY KEY (`id`)
) ENGINE = INNODB;


CREATE TABLE IF NOT EXISTS `iax_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(10) NOT NULL default 'friend', -- friend/user/peer
  `context` varchar(40) NOT NULL,
  `host` varchar(40) NOT NULL default 'dynamic',
  `ipaddr` varchar(40) NULL, -- Must be updateable by Asterisk user
  `port` int(5) NULL, -- Must be updateable by Asterisk user
  `sourceaddress` varchar(20) NULL,
  `mask` varchar(20) NULL,
  `regexten` varchar(40) NULL,
  `regseconds` int(11) NULL, -- Must be updateable by Asterisk user
  `accountcode` varchar(20) NULL, 
  `mohinterpret` varchar(20) NULL, 
  `mohsuggest` varchar(20) NULL, 
  `inkeys` varchar(40) NULL, 
  `outkey` varchar(40) NULL, 
  `language` varchar(10) NULL, 
  `sendani` varchar(10) NULL, -- yes/no
  `maxauthreq` varchar(5) NULL, -- Maximum outstanding AUTHREQ calls {1-32767}
  `requirecalltoken` varchar(4) NULL, -- yes/no/auto
  `encryption` varchar(20) NULL, -- aes128/yes/no
  `transfer` varchar(10) NULL, -- mediaonly/yes/no
  `jitterbuffer` varchar(3) NULL, -- yes/no
  `forcejitterbuffer` varchar(3) NULL, -- yes/no
  `disallow` varchar(40) NULL, -- all/{list-of-codecs}
  `allow` varchar(40) NULL, -- all/{list-of-codecs}
  `codecpriority` varchar(40) NULL, 
  `qualify` varchar(10) NULL, -- yes/no/{number of milliseconds}
  `qualifysmoothing` varchar(10) NULL, -- yes/no
  `qualifyfreqok` varchar(10) NULL, -- {number of milliseconds}|60000
  `qualifyfreqnotok` varchar(10) NULL, -- {number of milliseconds}|10000
  `timezone` varchar(20) NULL, 
  `adsi` varchar(10) NULL, -- yes/no
  `amaflags` varchar(20) NULL, 
  `setvar` varchar(200) NULL, 
  `permit` varchar(40) DEFAULT NULL,
  `deny` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
)ENGINE = INNODB;


CREATE TABLE IF NOT EXISTS `voicemail_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
	-- Mailbox context.
	context CHAR(80) NOT NULL DEFAULT 'default',
	-- Attach sound file to email - YES/no
	attach CHAR(3),
	-- Send email from this address
	serveremail CHAR(80),
	-- Prompts in alternative language
	language CHAR(20),
	-- Alternative timezone, as defined in voicemail.conf
	tz CHAR(30),
	-- Delete voicemail from server after sending email notification - yes/NO
	deletevoicemail CHAR(3),
	-- Read back CallerID information during playback - yes/NO
	saycid CHAR(3),
	-- Allow user to send voicemail from within VoicemailMain - YES/no
	sendvoicemail CHAR(3),
	-- Listen to voicemail and approve before sending - yes/NO
	review CHAR(3),
	-- Warn user a temporary greeting exists - yes/NO
	tempgreetwarn CHAR(3),
	-- Allow '0' to jump out during greeting - yes/NO
	operator CHAR(3),
	-- Hear date/time of message within VoicemailMain - YES/no
	envelope CHAR(3),
	-- Hear length of message within VoicemailMain - yes/NO
	sayduration CHAR(3),
	-- Minimum duration in minutes to say
	saydurationm INT(3),
	-- Force new user to record name when entering voicemail - yes/NO
	forcename CHAR(3),
	-- Force new user to record greetings when entering voicemail - yes/NO
	forcegreetings CHAR(3),
	-- Context in which to dial extension for callback
	callback CHAR(80),
	-- Context in which to dial extension (from advanced menu)
	dialout CHAR(80),
	-- Context in which to execute 0 or * escape during greeting
	exitcontext CHAR(80),
	-- Maximum messages in a folder (100 if not specified)
	maxmsg INT(5),
	-- Increase DB gain on recorded message by this amount (0.0 means none)
	volgain DECIMAL(5,2),
	-- IMAP user for authentication (if using IMAP storage)
	imapuser VARCHAR(80),
	-- IMAP password for authentication (if using IMAP storage)
	imappassword VARCHAR(80),
	PRIMARY KEY (`id`)
) ENGINE = INNODB;

insert into sip_settings (faxdetect,vmexten,context,useragent,disallow,allow,port,host,qualify,type,deny,permit,dtmfmode,nat,
callcounter) values ("no","*97","from-internal","elastix_3.0","all","ulaw;alaw;gsm","5060",
"dynamic","no","friend","0.0.0.0/0.0.0.0","0.0.0.0/0.0.0.0","rfc2833","yes","yes");

insert into iax_settings (deny,permit,transfer,context,host,type,qualify,disallow,allow,
requirecalltoken) values (NULL,NULL,"no","from-internal","dynamic","friend","yes","all","ulaw,alaw,gsm","auto");

insert into voicemail_settings (attach,context,serveremail,review,operator,maxmsg,deletevoicemail,saycid,
envelope,forcename,forcegreetings) values ("yes","default","vm@asterisk","yes","yes","100","no","no","no","yes","no");


INSERT INTO globals_settings VALUES("DIAL_OPTIONS","tr");
INSERT INTO globals_settings VALUES("TRUNK_OPTIONS","");
INSERT INTO globals_settings VALUES("RECORDING_STATE","ENABLED");
INSERT INTO globals_settings VALUES("MIXMON_FORMAT","wav");
INSERT INTO globals_settings VALUES("MIXMON_DIR","/var/spool/asterisk/monitor/");
INSERT INTO globals_settings VALUES("MIXMON_POST","");
INSERT INTO globals_settings VALUES("RINGTIMER","15");
INSERT INTO globals_settings VALUES("VM_PREFIX","*");
INSERT INTO globals_settings VALUES("VM_DDTYPE","u");
INSERT INTO globals_settings VALUES("VM_GAIN","");
INSERT INTO globals_settings VALUES("VM_OPTS","");
INSERT INTO globals_settings VALUES("OPERATOR_XTN","");
INSERT INTO globals_settings VALUES("VMX_CONTEXT","from-internal");
INSERT INTO globals_settings VALUES("VMX_PRI","1");
INSERT INTO globals_settings VALUES("VMX_TIMEDEST_CONTEXT","");
INSERT INTO globals_settings VALUES("VMX_TIMEDEST_EXT","dovm");
INSERT INTO globals_settings VALUES("VMX_TIMEDEST_PRI","1");
INSERT INTO globals_settings VALUES("VMX_LOOPDEST_CONTEXT","");
INSERT INTO globals_settings VALUES("VMX_LOOPDEST_EXT","dovm");
INSERT INTO globals_settings VALUES("VMX_LOOPDEST_PRI","1");
INSERT INTO globals_settings VALUES("VMX_OPTS_TIMEOUT","");
INSERT INTO globals_settings VALUES("VMX_OPTS_LOOP","");
INSERT INTO globals_settings VALUES("VMX_OPTS_DOVM","");
INSERT INTO globals_settings VALUES("VMX_TIMEOUT","2");
INSERT INTO globals_settings VALUES("VMX_REPEAT","1");
INSERT INTO globals_settings VALUES("VMX_LOOPS","1");
INSERT INTO globals_settings VALUES("DIRECTORY","last");
INSERT INTO globals_settings VALUES("DIRECTORY_OPT_EXT","");
INSERT INTO globals_settings VALUES("DIRECTORY_OPT_LENGTH","3"); 
INSERT INTO globals_settings VALUES("TONEZONE","us");
INSERT INTO globals_settings VALUES("LANGUAGE","en");
INSERT INTO globals_settings VALUES("TIMEFORMAT","kM");
INSERT INTO globals_settings VALUES("ALLOW_SIP_ANON","no");
INSERT INTO globals_settings VALUES("TRANSFER_CONTEXT","from-internal-xfer"); 
INSERT INTO globals_settings VALUES("INTERCOMCODE","nointercom ");
INSERT INTO globals_settings VALUES("CALLFILENAME","");
INSERT INTO globals_settings VALUES("OPERATOR","");
INSERT INTO globals_settings VALUES("PARKNOTIFY","SIP/200");
INSERT INTO globals_settings VALUES("RECORDEXTEN","");
INSERT INTO globals_settings VALUES("CREATE_VM","yes");


