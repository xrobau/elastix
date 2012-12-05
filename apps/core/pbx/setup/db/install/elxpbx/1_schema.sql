-- Create database	 
 CREATE DATABASE IF NOT EXISTS elxpbx;	 
 USE elxpbx;	 
 -- Database: `elxpbx`

-- Create user db
GRANT SELECT, UPDATE, INSERT, DELETE ON `elxpbx`.* to asteriskuser@localhost;

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
    organization_domain varchar(100) NOT NULL, 
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
  `trunkfreq` int(5) default 20,
  `trunktimestamps` varchar(3) NULL, -- Yes/no
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
    organization_domain varchar(100) NOT NULL,
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

CREATE TABLE extensions_table (
    id int(11) NOT NULL auto_increment,
    context varchar(20) NOT NULL default '',
    exten varchar(20) NOT NULL default '',
    priority tinyint(4) NOT NULL default '0',
    app varchar(20) NOT NULL default '',
    appdata varchar(128) NOT NULL default '',
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY (`context`,`exten`,`priority`),
    KEY `id` (`id`),
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

CREATE TABLE IF NOT EXISTS `trunk` (
    trunkid int(11) NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL default '',
    tech varchar(20) NOT NULL,
    outcid varchar(40) NOT NULL default '',
    keepcid varchar(4) default 'off',
    maxchans varchar(6) default '',
    failscript varchar(255) NOT NULL default '',
    dialoutprefix varchar(255) NOT NULL default '',
    -- el canal dentro de asterisk usado para marcar la truncal
    channelid varchar(255) NOT NULL default '',
    provider varchar(40) default NULL,
    -- si es 'on' la truncal no estar activa y no se podran realizar llamadas por ella
    disabled enum('on','off') default 'off',
    sec_call_time enum('yes','no') default 'no',
    -- si la truncal va a ser usada para conectarse con otro server de mi red o para obtener coneccion con otras redes (PSTN) 
    string_register varchar(100),
    PRIMARY KEY  (`trunkid`),
    UNIQUE KEY (`tech`,`channelid`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `trunk_dialpatterns` (
  `trunkid` int(11) NOT NULL,
  `match_pattern_prefix` varchar(50) NOT NULL default '',
  `match_pattern_pass` varchar(50) NOT NULL default '',
  `prepend_digits` varchar(50) NOT NULL default '',
  `seq` int(11) NOT NULL default '0',
  PRIMARY KEY  (`trunkid`,`match_pattern_prefix`,`match_pattern_pass`,`prepend_digits`,`seq`),
  FOREIGN KEY (`trunkid`) REFERENCES trunk(`trunkid`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `trunk_organization` (
  `trunkid` int(11) NOT NULL,
  `organization_domain` varchar(50) NOT NULL,
  PRIMARY KEY  (`trunkid`,`organization_domain`),
  FOREIGN KEY (`trunkid`) REFERENCES trunk(`trunkid`)
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
INSERT INTO globals_settings VALUES("DIRECTORY","first");
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

DROP TABLE IF EXISTS queue;
CREATE TABLE queue (
    name VARCHAR(128) PRIMARY KEY,
    description VARCHAR(128) DEFAULT NULL,
    autofill enum ('yes','no') DEFAULT 'yes',
    monitor_type enum ('MixMonitor','Monitor') default 'MixMonitor',
    -- si no se configura un valor, no se graba la llamada
    monitor_format enum ('wav','gsm','wav49') default NULL,
    musicclass VARCHAR(128),
    announce VARCHAR(128),
    strategy enum ('ringall','leastrecent','fewestcalls','random','rrmemory','rrordered','linear','leastrecent') DEFAULT 'ringall',
    servicelevel INT(11) default 60,
    context VARCHAR(128),
    penaltymemberslimit INT(11),
    -- This timeout specifies the amount of time to try ringing a member's phone before considering the member to be unavailable
    timeout INT(11) not NULL DEFAULT 15,
    retry INT(11) DEFAULT 5,
    timeoutpriority enum ('app','conf') default 'app',
    weight INT(11) default 0,
    wrapuptime INT(11) default 0,
    autopause enum ('yes','no','all'),
    autopausedelay INT(11),
    -- maximo numero de llamadas esperando en la cola, 0 para ilimitado
    maxlen INT(11) DEFAULT 0,
    announce_frequency INT(11) DEFAULT 0,
    min_announce_frequency INT(11),
    periodic_announce_frequency INT(11),
    announce_holdtime ENUM ('yes','no','once') DEFAULT 'no',
    announce_position ENUM ('yes','no','limit','more') DEFAULT 'no',
    announce_position_limit INT(11),
    announce_round_seconds INT(11),
    queue_youarenext VARCHAR(128),
    queue_thereare VARCHAR(128),
    queue_callswaiting VARCHAR(128),
    queue_holdtime VARCHAR(128),
    queue_minute VARCHAR(128),
    queue_minutes VARCHAR(128),
    queue_seconds VARCHAR(128),
    queue_lessthan VARCHAR(128),
    queue_thankyou VARCHAR(128),
    queue_reporthold VARCHAR(128),
    periodic_announce VARCHAR(50),
    joinempty enum ('yes','no','strict','loose') default 'yes',
    leavewhenempty enum ('yes','no','strict','loose') default 'no',
    eventmemberstatus enum ('yes','no') default 'yes',
    eventwhencalled enum ('yes','no'),
    reportholdtime enum ('yes','no'),
    ringinuse enum ('yes','no') default 'yes',
    memberdelay INT(11),
    timeoutrestart enum ('yes','no'),
    defaultrule VARCHAR(128),
    setinterfacevar enum ('yes','no') default 'yes',
    setqueueentryvar enum ('yes','no') default 'yes',
    setqueuevar enum ('yes','no') default 'yes',
    organization_domain varchar(100) NOT NULL,
    timeout_detail INT(11),
    password_detail varchar(50),
    cid_prefix_detail varchar(50),
    cid_holdtime_detail enum ('yes','no') default 'no',
    alert_info_detail varchar(50),
    announce_caller_detail INT(11),
    announce_detail INT(11),
    ringing_detail enum ('yes','no') default 'no',
    retry_detail enum ('yes','no') default 'yes',
    destination_detail varchar(128),
    restriction_agent enum ('yes','no') default 'no',
    calling_restriction INT(11) default 0,
    skip_busy_detail INT(11),
    queue_number INT(11) not null,
    UNIQUE KEY queue_number (queue_number,organization_domain),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;


DROP TABLE IF EXISTS queue_member;
CREATE TABLE queue_member(
uniqueid INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
membername varchar(40),
queue_name varchar(128) not null,
interface varchar(128) not null,
penalty INT(11),
paused INT(11),
state_interface varchar(128), 
exten INT(11) NOT NULL,
UNIQUE KEY queue_interface (queue_name, interface),
FOREIGN KEY (queue_name) REFERENCES queue(name)
) ENGINE = INNODB;

DROP TABLE IF EXISTS musiconhold;
CREATE TABLE musiconhold (
    -- Name of the MOH class
    name char(80) not null primary key,
    -- Descriptive name of the MOH class
    description char(80) default "", 
    -- One of 'custom', 'files', 'mp3nb', 'quietmp3nb', or 'quietmp3'
    mode enum('custom', 'files', 'mp3nb', 'quietmp3nb', 'quietmp3') default 'files',
    -- If 'custom', directory is ignored.  Otherwise, specifies a directory with files to play or a stream URL
    directory char(255) null,
    -- If 'custom', application will be invoked to provide MOH.  Ignored otherwise.
    application char(255) null,
    -- Digit associated with this MOH class, when MOH is selectable by the caller.
    digit char(1) null,
    -- One of 'random' or 'alpha', to determine how files are played.  If NULL, files are played in directory order
    sort enum('random', 'alpha') default 'alpha',
    -- In custom mode, the format of the audio delivered.  Ignored otherwise.  Defaults to SLIN.
    format char(10) null,
    -- When this record was last modified
    stamp timestamp,
    -- organization's Domain that is owner of the class
    organization_domain varchar(100) NOT NULL,
    INDEX organization_domain (organization_domain),
    UNIQUE KEY description_moh (description,organization_domain)
) ENGINE = INNODB;

insert into musiconhold (name,description,mode,directory,sort) values('default','default','files','/var/lib/asterisk/mohmp3/','random');

insert into musiconhold (name,description,mode,directory,sort) values('none','none','files','/var/lib/asterisk/mohmp3/none/','random');

DROP TABLE IF EXISTS recordings;
CREATE TABLE recordings (
    uniqueid INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    -- path completo de donde se encuentra la grabacion incluyendo el nombre de la grabacion
    filename varchar(128) NOT NULL,
    -- dominio al que pertenece la grabacion
    organization_domain varchar(100) NOT NULL,
    source varchar(20) NOT NULL,
    name varchar(50) default null,
    UNIQUE KEY filename (filename),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

DROP TABLE IF EXISTS ivr;
CREATE TABLE ivr (
    id INT(10) PRIMARY KEY AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    announcement INT(11) default null,
    timeout INT(11),
    directdial enum ('yes','no') default 'no',
    retvm enum ('yes','no') default 'no',
    loops INT(11),
    mesg_timeout INT(11) default null,
    mesg_invalid INT(11) default null,
    organization_domain varchar(100) NOT NULL,
    UNIQUE KEY ivr_name (name,organization_domain),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

DROP TABLE IF EXISTS ivr_destination;
CREATE TABLE ivr_destination (
    id int(11) NOT NULL AUTO_INCREMENT,
    key_option varchar(50) NOT NULL,
    type varchar(50) NOT NULL,
    destine varchar(50) NOT NULL,
    ivr_return enum ('yes','no') default 'no',
    ivr_id INT(10) not NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (ivr_id) REFERENCES ivr(id)
) ENGINE = INNODB;

DROP TABLE IF EXISTS did;
CREATE TABLE did (
    id int(11) NOT NULL AUTO_INCREMENT,
    did varchar(100) NOT NULL,
    organization_domain varchar(100),
    country varchar(100) NOT NULL,
    city varchar(100) NOT NULL,
    country_code varchar(100) NOT NULL,
    area_code varchar(100) NOT NULL,
    type enum ('digital','analog','voip') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY did (did)
) ENGINE = INNODB;

DROP TABLE IF EXISTS did_details;
CREATE TABLE did_details (
    did varchar(100) NOT NULL,
    keyword varchar(50) NOT NULL,
    data varchar(50) NOT NULL,
    PRIMARY KEY (did,keyword,data),
    FOREIGN KEY (did) REFERENCES did(did)
) ENGINE = INNODB;

DROP TABLE IF EXISTS inbound_route;
CREATE TABLE inbound_route (
    id int(11) NOT NULL AUTO_INCREMENT,
    description varchar(50) NOT NULL,
    did_number varchar(50) default "",
    cid_number varchar(50) default "",
    cid_prefix varchar(50) default null,
    moh varchar(50) default null,
    delay_answer int(11),
    alertinfo varchar(50) default null,
    language varchar(3) default 'en',
    ringing enum('on','off') default 'off',
    primanager enum('yes','no') default 'no',
    max_attempt int(2) default 3,
    min_length int(2) default 5,
    goto varchar(50) NOT NULL,
    destination varchar(50) NOT NULL,
    fax_detect enum('yes','no') default 'no',
    fax_type enum('fax','nvfax') default 'fax',
    fax_time  int(2) default 10,
    fax_destiny varchar(50),
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY (id),
    INDEX organization_domain (organization_domain),
    UNIQUE KEY route_in (did_number,cid_number)
) ENGINE = INNODB;

DROP TABLE IF EXISTS outbound_route;
CREATE TABLE outbound_route (
    id int(11) NOT NULL AUTO_INCREMENT,
    routename varchar(50) NOT NULL,
    outcid varchar(50) default null,
    outcid_mode varchar(50) default null,
    routepass varchar(50) default null,
    mohsilence varchar(50) default null,
    time_group_id int(11),
    seq int(11) NOT NULL,
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (mohsilence) REFERENCES musiconhold(name),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

DROP TABLE IF EXISTS outbound_route_dialpattern;
CREATE TABLE outbound_route_dialpattern (
    outbound_route_id int(11) NOT NULL,
    prepend varchar(50) NOT NULL default '',
    prefix varchar(50) NOT NULL default '',
    match_pattern varchar(50) NOT NULL default '',
    match_cid varchar(50) NOT NULL default '',
    seq int(11) NOT NULL,
    PRIMARY KEY (`outbound_route_id`,`prepend`,`prefix`,`match_pattern`,`seq`),
    FOREIGN KEY (`outbound_route_id`) REFERENCES outbound_route(`id`)
) ENGINE = INNODB;

DROP TABLE IF EXISTS outbound_route_trunkpriority;
CREATE TABLE outbound_route_trunkpriority (
    outbound_route_id int(11) NOT NULL,
    trunk_id int(11) NOT NULL,
    seq int(11) NOT NULL,
    PRIMARY KEY  (`outbound_route_id`,`trunk_id`),
    FOREIGN KEY (`outbound_route_id`) REFERENCES outbound_route(`id`),
    FOREIGN KEY (`trunk_id`) REFERENCES trunk(`trunkid`)
) ENGINE = INNODB;

DROP TABLE IF EXISTS ring_group;
CREATE TABLE ring_group (
    id int(11) NOT NULL AUTO_INCREMENT,
    rg_number varchar(50) NOT NULL,
    rg_name varchar(50) NOT NULL,
    rg_strategy enum ('ringall','leastrecent','fewestcalls','random','rrmemory','rrordered','linear','leastrecent') DEFAULT 'ringall',
    rg_time TINYINT default 10,
    rg_recording varchar(128),
    rg_alertinfo varchar(50),
    rg_play_moh enum ('ring','yes') default 'ring',
    rg_moh varchar(80),
    rg_cid_prefix varchar(100),
    rg_cf_ignore enum ('yes','no') default 'no',
    rg_skipbusy enum ('yes','no') default 'no',
    rg_confirm_call enum ('yes','no') default 'no',
    rg_record_remote varchar(128),
    rg_record_toolate varchar(128),
    goto varchar(50) NOT NULL,
    destination varchar(128) NOT NULL,
    rg_extensions varchar(128),
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY  (id),
    FOREIGN KEY (rg_moh) REFERENCES musiconhold(name),
    UNIQUE INDEX rg_num_org (rg_number,organization_domain),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

DROP TABLE IF EXISTS time_group;
CREATE TABLE time_group (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY  (id),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

DROP TABLE IF EXISTS tg_parameters;
CREATE TABLE tg_parameters (
    id_tg int(11) NOT NULL,
    tg_hour varchar(50),
    tg_day_w varchar(50), 
    tg_day_m varchar(50),
    tg_month varchar(50),
    FOREIGN KEY (id_tg) REFERENCES time_group(id),
    PRIMARY KEY (id_tg,tg_hour,tg_day_w,tg_day_m,tg_month)
) ENGINE = INNODB;

DROP TABLE IF EXISTS time_conditions;
CREATE TABLE time_conditions (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    id_tg int(11) NOT NULL,
    goto_m varchar(50) NOT NULL,
    destination_m varchar(50) NOT NULL,
    goto_f varchar(50) NOT NULL,
    destination_f varchar(50) NOT NULL,
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY  (id),
    FOREIGN KEY (id_tg) REFERENCES time_group(id),
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;