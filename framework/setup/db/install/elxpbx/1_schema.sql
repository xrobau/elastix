
-- Create da-- Create database       
CREATE DATABASE IF NOT EXISTS elxpbx;   
USE elxpbx;     
-- Database: `elxpbx`
   
-- Create user db
GRANT SELECT, UPDATE, INSERT, DELETE ON `elxpbx`.* to asteriskuser@localhost;

CREATE TABLE IF NOT EXISTS organization
(
    id                INTEGER  NOT NULL AUTO_INCREMENT,
    name              VARCHAR(150) NOT NULL,
    domain            VARCHAR(100) NOT NULL,
    email_contact     VARCHAR(100),
    country           VARCHAR(100) NOT NULL,
    city              VARCHAR(150) NOT NULL,
    address           VARCHAR(255),
    -- codigo de la organizacion usado en asterisk como identificador
    code              VARCHAR(20) NOT NULL, 
    -- codigo unico de la orgnizacion usado para identificarla de manera      unica dentro del sistema
    idcode            VARCHAR(50) NOT NULL,   
    state             VARCHAR(20) DEFAULT "active",  
    PRIMARY KEY (id),
    UNIQUE INDEX domain (domain),
    UNIQUE INDEX code (code),
    UNIQUE INDEX idcode (idcode)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS organization_properties
(
    id_organization   INTEGER     NOT NULL AUTO_INCREMENT, 
    property             VARCHAR(50) NOT NULL,
    value             VARCHAR(50) NOT NULL,
    category          VARCHAR(50),
    PRIMARY KEY (id_organization,property),
    FOREIGN KEY (id_organization) REFERENCES organization(id)  ON DELETE CASCADE
) ENGINE = INNODB;

-- tabla org_email_template contiene
-- los parametros usados en el envio
-- de un mail a las organizaciones desde
-- el servidor elastix al momento de 
-- crear, eleminar o suspender 
-- una organizacion
CREATE TABLE IF NOT EXISTS org_email_template(
    from_email varchar(250) NOT NULL,
    from_name varchar(250) NOT NULL,
    subject varchar(250) NOT NULL,
    content TEXT NOT NULL,
    host_ip varchar(250) default "",
    host_domain varchar(250) default "",
    host_name varchar(250) default "",
    category varchar(250) NOT NULL,
    PRIMARY KEY (category)
) ENGINE = INNODB;

insert into org_email_template (from_email,from_name,subject,content,category) values("elastix@example.com","Elastix Admin","Create Company in Elastix Server",'Welcome to Elastix Server.\nYour company {COMPANY_NAME} with domain {DOMAIN} has been created.\nTo start to configurate you elastix server go to {HOST_IP} and login into elastix as:\nUsername: admin@{DOMAIN}\nPassword: {USER_PASSWORD}',"create");
insert into org_email_template (from_email,from_name,subject,content,category) values("elastix@example.com","Elastix Admin","Deleted Company in Elastix Server","","delete");
insert into org_email_template (from_email,from_name,subject,content,category) values("elastix@example.com","Elastix Admin","Suspended Company in Elastix Server","","suspend");

-- tabla creada con propositos de auditoria que guarda las acciones tomadas
-- con respecto a una organizacion dentro del sistema
-- entiendese por acciones el crear, suspender, reactivar o eliminar una organizacion del sistema
CREATE TABLE IF NOT EXISTS org_history_events(
    id INTEGER  NOT NULL AUTO_INCREMENT,
    -- create,suspend,unsuspend,delete,
    event varchar(100) NOT NULL,
    -- codigo unico generado  para la organizacion 
    -- este codigo no se puede repetir dentro del sistema
    org_idcode VARCHAR(50),
    -- fecha en que ocurrio el evento
    event_date DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE = INNODB;

-- esta tabla contiene informacion de todas las organizaciones creadas en algun
-- momento dentro del sistema
CREATE TABLE IF NOT EXISTS org_history_register(
    id INTEGER  NOT NULL AUTO_INCREMENT,
    org_domain VARCHAR(100) NOT NULL, 
    org_code VARCHAR(20) NOT NULL, 
    -- codigo unico generado  para la organizacion 
    -- este codigo no se puede repetir dentro del sistema
    org_idcode VARCHAR(50) NOT NULL,
    -- fecha en que ocurrio el evento
    create_date DATETIME NOT NULL,
    delete_date DATETIME default NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX orgIdcode (org_idcode)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS acl_resource
(
    id varchar(50) NOT NULL, -- menuid , es el unico identficador del recurso
    description varchar(100),
    IdParent varchar(50),
    Link varchar(250),
    Type varchar(20),
    order_no INTEGER,
    administrative enum('yes','no') default 'yes',
    organization_access enum('yes','no') default 'yes',
    PRIMARY KEY (id)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS organization_resource
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    id_organization INTEGER NOT NULL,
    id_resource varchar(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX permission_org (id_organization,id_resource),
    FOREIGN KEY (id_organization) REFERENCES organization(id) ON DELETE CASCADE,
    FOREIGN KEY (id_resource) REFERENCES acl_resource(id) ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS acl_group
(
    id INTEGER NOT NULL AUTO_INCREMENT ,
    name VARCHAR(200),
    description TEXT,
    id_organization INTEGER NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id) ON DELETE CASCADE,
    UNIQUE INDEX name_group (id_organization,name)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS resource_action
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    id_resource varchar(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX resource_action (id_resource,action)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS group_resource_action
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    id_group INTEGER NOT NULL,
    id_resource_action INTEGER NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_group) REFERENCES acl_group(id) ON DELETE CASCADE,
    FOREIGN KEY (id_resource_action) REFERENCES resource_action(id) ON DELETE CASCADE,
    UNIQUE INDEX permission_group (id_group,id_resource_action)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS acl_user
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    username VARCHAR(150) NOT NULL,
    name VARCHAR(150),
    md5_password VARCHAR(100) NOT NULL,
    id_group INTEGER NOT NULL,
    extension VARCHAR(20),
    fax_extension VARCHAR(20),
    picture varchar(50),
    PRIMARY KEY (id),
    FOREIGN KEY (id_group) REFERENCES acl_group(id) ON DELETE CASCADE,
    UNIQUE INDEX username (username)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS user_resource_action
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    id_user INTEGER NOT NULL,
    id_resource_action INTEGER NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_user) REFERENCES acl_user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_resource_action) REFERENCES resource_action(id) ON DELETE CASCADE,
    UNIQUE INDEX permission_user (id_user,id_resource_action)
) ENGINE = INNODB;


CREATE TABLE IF NOT EXISTS user_shortcut
(
    id           INTEGER     NOT NULL AUTO_INCREMENT ,
    id_user      INTEGER     NOT NULL,
    id_resource  varchar(50) NOT NULL,
    type         VARCHAR(50) NOT NULL,
    description  VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY (id_user) REFERENCES acl_user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_resource) REFERENCES acl_resource(id)  ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS sticky_note
(
    id           INTEGER     NOT NULL AUTO_INCREMENT ,
    id_user      INTEGER     NOT NULL,
    id_resource  varchar(50) NOT NULL,
    date_edit    DATETIME    NOT NULL,
    description  TEXT,
    auto_popup   INTEGER NOT NULL DEFAULT '0',
    PRIMARY KEY (id),
    FOREIGN KEY (id_user) REFERENCES acl_user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_resource) REFERENCES acl_resource(id)  ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS user_properties
(
    id_user   INTEGER     NOT NULL AUTO_INCREMENT,
    property     VARCHAR(100) NOT NULL,
    value        VARCHAR(150) NOT NULL,
    category     VARCHAR(50),
    PRIMARY KEY (id_user,property,category),
    FOREIGN KEY (id_user) REFERENCES acl_user(id) ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS email_list
(
    id               INTEGER AUTO_INCREMENT,
    id_organization  INTEGER,
    listname    VARCHAR(50),
    password    VARCHAR(15),
    mailadmin   VARCHAR(150),
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id) ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS member_list
(
    id              INTEGER NOT null AUTO_INCREMENT ,
    mailmember      VARCHAR(150),
    id_emaillist    INTEGER,
    namemember      VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY(id_emaillist) REFERENCES email_list(id) ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS messages_vacations
(
    id          INTEGER NOT NULL AUTO_INCREMENT,
    account     varchar(150) NOT NULL,
    subject     varchar(150) NOT NULL,
    body        text,
    vacation varchar(5) default 'no',
    ini_date date NOT NULL,
    end_date date NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (account) REFERENCES acl_user(username) ON DELETE CASCADE
) ENGINE = INNODB;


CREATE TABLE IF NOT EXISTS email_statistics(
    id                 integer not null AUTO_INCREMENT,
    date               datetime,
    unix_time          integer,
    total              integer,
    type               integer,
    id_organization    integer,
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id) ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS settings
(
    property               varchar(32) NOT NULL,
    value             varchar(32) NOT NULL,
    PRIMARY KEY (property)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS fax_docs
(
    id             integer          NOT NULL AUTO_INCREMENT,
    pdf_file       varchar(255)     NOT NULL DEFAULT '',
    modemdev       varchar(255)     NOT NULL DEFAULT '',
    status         varchar(255)     NOT NULL DEFAULT '',
    commID         varchar(255)     NOT NULL DEFAULT '',
    errormsg       varchar(255)     NOT NULL DEFAULT '',
    company_name   varchar(255)     NOT NULL DEFAULT '',
    company_fax    varchar(255)     NOT NULL DEFAULT '',
    date           timestamp        NOT NULL,
    type           varchar(3)       default 'in',
    faxpath        varchar(255)     default '',
    id_user        integer          not null,
    PRIMARY KEY (id),
    FOREIGN KEY    (id_user) REFERENCES acl_user(id) ON DELETE CASCADE
) ENGINE = INNODB;



INSERT INTO settings VALUES('elastix_version_release', '3.0.0-1');
INSERT INTO organization VALUES(1,'NONE','','','','','','','','active');
INSERT INTO organization_properties VALUES(1,'language','en','system');
INSERT INTO organization_properties VALUES(1,'default_rate',0.50,'system');
INSERT INTO organization_properties VALUES(1,'default_rate_offset',1,'system');
INSERT INTO organization_properties VALUES(1,'currency','$','system');
INSERT INTO organization_properties VALUES(1,'theme','elastixneo','system');
INSERT INTO acl_group VALUES( 1,'superadmin','super elastix admin',1);
INSERT INTO acl_group VALUES( 2,'administrator','Administrator',1);
INSERT INTO acl_group VALUES( 3,'supervisor','Supervisor',1);
INSERT INTO acl_group VALUES( 4,'end_user','End User',1);
INSERT INTO acl_user (id,username,name,md5_password,id_group) VALUES(1,'admin','admin','7a5210c173ea40c03205a5de7dcd4cb0',1);


INSERT INTO acl_resource VALUES('system', 'System', '', '', '', 0,'yes','yes');
INSERT INTO acl_resource VALUES('sysdash', 'Dashboard Manager', 'system', '', '', 1,'yes','yes'); 
INSERT INTO acl_resource VALUES('dashboard', 'Dashboard', 'sysdash', '', 'module', 11,'yes','yes'); 
INSERT INTO acl_resource VALUES('applet_admin', 'Dashboard Applet Admin', 'sysdash', '', 'module', 12,'yes','yes'); 
INSERT INTO acl_resource VALUES('orgmgr', 'Organization Manager', 'system', '', '', 2,'yes','yes');
INSERT INTO acl_resource VALUES('organization', 'Organization', 'orgmgr', '', 'module', 21,'yes','yes');
INSERT INTO acl_resource VALUES('organization_permission', 'Organization Resource', 'orgmgr', '', 'module', 22,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('usermgr', 'User/Group Manager', 'system', '', '', 3,'yes','yes');
INSERT INTO acl_resource VALUES('userlist', 'Users', 'usermgr', '', 'module', 1,'yes','yes'); 
INSERT INTO acl_resource VALUES('grouplist', 'Groups', 'usermgr', '', 'module', 2,'yes','yes'); 
INSERT INTO acl_resource VALUES('group_permission', 'Group Resource', 'usermgr', '', 'module', 3,'yes','yes'); 
INSERT INTO acl_resource VALUES('network', 'Network', 'system', '', '', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('network_parameters', 'Network Parameters', 'network', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dhcp_server', 'DHCP Server', 'network', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dhcp_clientlist', 'DHCP Client List', 'network', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dhcp_by_mac', 'Assign IP Address to Host', 'network', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('shutdown', 'Shutdown', 'system', '', 'module', 5,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('hardware_configuration', 'Hardware Configuration', 'system', '', '', 6,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('hardware_detector', 'Hardware Detector', 'hardware_configuration', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('updates', 'Updates', 'system', '', '', 7,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('repositories', 'Repositories', 'updates', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('packages', 'Packages', 'updates', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('preferences', 'Preferences', 'system', '', '', 8,'yes','yes'); 
INSERT INTO acl_resource VALUES('language', 'Language', 'preferences', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('themes_system', 'Themes', 'preferences', '', 'module', 2,'yes','yes');
INSERT INTO acl_resource VALUES('time_config', 'Date/Time', 'preferences', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('currency', 'Currency', 'preferences', '', 'module', 4,'yes','no'); 
INSERT INTO acl_resource VALUES('email_admin', 'Email', '', '', '', 2,'yes','yes');
INSERT INTO acl_resource VALUES('email_relay', 'Relay', 'email_admin', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('antispam', 'Antispam', 'email_admin', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('remote_smtp', 'Remote SMTP', 'email_admin', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('email_list', 'Email list', 'email_admin', '', 'module', 5,'yes','yes');
INSERT INTO acl_resource VALUES('email_stats', 'Email stats', 'email_admin', '', 'module', 6,'yes','yes');
INSERT INTO acl_resource VALUES('fax', 'Fax', '', '', '', 3,'yes','yes');
INSERT INTO acl_resource VALUES('faxviewer', 'Fax Viewer', 'fax', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('faxmaster', 'Fax Master', 'fax', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('faxclients', 'Fax Clients', 'fax', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('fax_email_template', 'Fax Email Template', 'fax', '', 'module', 4,'yes','yes'); 
INSERT INTO acl_resource VALUES('pbxconfig', 'PBX', '', '', '', 4,'yes','yes');
INSERT INTO acl_resource VALUES('pbxadmin', 'PBX Configuration', 'pbxconfig', '', '', 1,'yes','yes');
INSERT INTO acl_resource VALUES('trunks', 'Trunks', 'pbxadmin', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('did', 'DID', 'pbxadmin', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('general_settings_admin', 'General Settings Admin', 'pbxadmin', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('extensions', 'Extensions', 'pbxadmin', '', 'module', 4,'yes','yes');
INSERT INTO acl_resource VALUES('outbound_route', 'Outbound Routes', 'pbxadmin', '', 'module', 5,'yes','yes');
INSERT INTO acl_resource VALUES('inbound_route', 'Inbound Routes', 'pbxadmin', '', 'module', 6,'yes','yes');
INSERT INTO acl_resource VALUES('ivr', 'IVR', 'pbxadmin', '', 'module', 7,'yes','yes');
INSERT INTO acl_resource VALUES('queues', 'Queues', 'pbxadmin', '', 'module', 8,'yes','yes');
INSERT INTO acl_resource VALUES('features_code', 'Features Codes', 'pbxadmin', '', 'module', 9,'yes','yes');
INSERT INTO acl_resource VALUES('general_settings', 'General Settings', 'pbxadmin', '', 'module', 10,'yes','yes');
INSERT INTO acl_resource VALUES('ring_group', 'Ring Groups', 'pbxadmin', '', 'module', 11,'yes','yes');
INSERT INTO acl_resource VALUES('time_group', 'Time Group', 'pbxadmin', '', 'module', 12,'yes','yes');
INSERT INTO acl_resource VALUES('time_conditions', 'Time Conditions', 'pbxadmin', '', 'module', 13,'yes','yes');
INSERT INTO acl_resource VALUES('musiconhold', 'Music On Hold', 'pbxadmin', '', 'module', 14,'yes','yes');
INSERT INTO acl_resource VALUES('recordings', 'Recordings', 'pbxadmin', '', 'module', 15,'yes','yes');
INSERT INTO acl_resource VALUES('control_panel', 'Operator Panel', 'pbxconfig', '', 'module', 2,'yes','yes');
INSERT INTO acl_resource VALUES('voicemail', 'Voicemail', 'pbxconfig', '', 'module', 3,'yes','yes');
INSERT INTO acl_resource VALUES('monitoring', 'Monitoring', 'pbxconfig', '', 'module', 4,'yes','yes');
INSERT INTO acl_resource VALUES('batch_conf', 'Batch Configurations', 'pbxconfig', '', '', 5,'yes','yes'); 
INSERT INTO acl_resource VALUES('extensions_batch', 'Batch of Extensions', 'batch_conf', '', 'module', 3,'yes','yes'); 
INSERT INTO acl_resource VALUES('conference', 'Conference', 'pbxconfig', '', 'module', 6,'yes','yes'); 
INSERT INTO acl_resource VALUES('tools', 'Tools', 'pbxconfig', '', '', 7,'yes','yes');
INSERT INTO acl_resource VALUES('asterisk_cli', 'Asterisk-Cli', 'tools', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('file_editor', 'Asterisk File Editor', 'tools', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('text_to_wav', 'Text to Wav', 'tools', '', 'module', 3,'yes','yes');
INSERT INTO acl_resource VALUES('festival', 'Festival', 'tools', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('reports', 'Reports', '', '', '', 5,'yes','yes');
INSERT INTO acl_resource VALUES('cdrreport', 'CDR Report', 'reports', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('channelusage', 'Channels Usage', 'reports', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('asterisk_log', 'Asterisk Logs', 'reports', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('graphic_report', 'Graphic Report', 'reports', '', 'module', 5,'yes','yes');
INSERT INTO acl_resource VALUES('summary_by_extension', 'Summary', 'reports', '', 'module', 6,'yes','yes');
INSERT INTO acl_resource VALUES('missed_calls', 'Missed Calls', 'reports', '', 'module', 7,'yes','yes');
INSERT INTO acl_resource VALUES('security', 'Security', '', '', '', 7,'yes','yes'); 
INSERT INTO acl_resource VALUES('sec_firewall', 'Firewall', 'security', '', 'module', 1,'yes','no'); -- superadmin 
INSERT INTO acl_resource VALUES('sec_rules', 'Firewall Rules', 'sec_firewall', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('sec_ports', 'Define Ports', 'sec_firewall', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('sec_portknock_if', 'Port Knocking Interfaces', 'sec_firewall', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('sec_portknock_users', 'Port Knocking Users', 'sec_firewall', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('sec_accessaudit', 'Audit', 'security', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('sec_weak_keys', 'Weak Keys', 'security', '', 'module', 3,'yes','yes'); 
INSERT INTO acl_resource VALUES('sec_advanced_settings', 'Advanced Settings', 'security', '', 'module', 4,'yes','no'); -- superadmin

-- Modulos de final_user
INSERT INTO acl_resource VALUES('user_home', 'My Home', '', '', 'module', 1,'no','yes');
INSERT INTO acl_resource VALUES('user_setting', 'My settings', '', '', 'module', 2,'no','yes');
INSERT INTO acl_resource VALUES('vacations_msg', 'Vacations', 'user_setting', '', 'module', 3,'no','yes');
INSERT INTO acl_resource VALUES('agenda', 'Agenda', '', '', '', 3,'no','yes');
INSERT INTO acl_resource VALUES('calendar', 'Calendar', 'agenda', '', 'module', 1,'no','yes');
INSERT INTO acl_resource VALUES('address_book', 'Address Book', 'agenda', '', 'module', 2,'no','yes');


INSERT INTO organization_resource VALUES(2, 1, 'dashboard');
INSERT INTO organization_resource VALUES(3, 1, 'applet_admin');
INSERT INTO organization_resource VALUES(5, 1, 'organization');
INSERT INTO organization_resource VALUES(6, 1, 'organization_permission');
INSERT INTO organization_resource VALUES(8, 1, 'userlist');
INSERT INTO organization_resource VALUES(9, 1, 'grouplist');
INSERT INTO organization_resource VALUES(90, 1, 'group_permission');
INSERT INTO organization_resource VALUES(11, 1, 'network_parameters');
INSERT INTO organization_resource VALUES(12, 1, 'dhcp_server');
INSERT INTO organization_resource VALUES(13, 1, 'dhcp_clientlist');
INSERT INTO organization_resource VALUES(14, 1, 'dhcp_by_mac');
INSERT INTO organization_resource VALUES(15, 1, 'shutdown');
INSERT INTO organization_resource VALUES(17, 1, 'hardware_detector');
INSERT INTO organization_resource VALUES(19, 1, 'repositories');
INSERT INTO organization_resource VALUES(20, 1, 'packages');
INSERT INTO organization_resource VALUES(22, 1, 'language');
INSERT INTO organization_resource VALUES(23, 1, 'themes_system');
INSERT INTO organization_resource VALUES(24, 1, 'time_config');
INSERT INTO organization_resource VALUES(25, 1, 'currency');
INSERT INTO organization_resource VALUES(31, 1, 'email_relay');
INSERT INTO organization_resource VALUES(32, 1, 'antispam');
INSERT INTO organization_resource VALUES(33, 1, 'remote_smtp');
INSERT INTO organization_resource VALUES(34, 1, 'email_list');
INSERT INTO organization_resource VALUES(35, 1, 'email_stats');
INSERT INTO organization_resource VALUES(39, 1, 'fax_email_template');
INSERT INTO organization_resource VALUES(40, 1, 'faxmaster');
INSERT INTO organization_resource VALUES(41, 1, 'faxclients');
INSERT INTO organization_resource VALUES(42, 1, 'faxviewer');
INSERT INTO organization_resource VALUES(43, 1, 'pbxadmin');
INSERT INTO organization_resource VALUES(44, 1, 'trunks'); -- superadmin
INSERT INTO organization_resource VALUES(45, 1, 'did'); -- superadmin
INSERT INTO organization_resource VALUES(46, 1, 'general_settings_admin'); -- superadmin
INSERT INTO organization_resource VALUES(47, 1, 'extensions');
INSERT INTO organization_resource VALUES(48, 1, 'outbound_route');
INSERT INTO organization_resource VALUES(49, 1, 'inbound_route');
INSERT INTO organization_resource VALUES(50, 1, 'ivr');
INSERT INTO organization_resource VALUES(51, 1, 'queues');
INSERT INTO organization_resource VALUES(52, 1, 'features_code');
INSERT INTO organization_resource VALUES(53, 1, 'general_settings');
INSERT INTO organization_resource VALUES(54, 1, 'ring_group');
INSERT INTO organization_resource VALUES(55, 1, 'time_group');
INSERT INTO organization_resource VALUES(56, 1, 'time_conditions');
INSERT INTO organization_resource VALUES(57, 1, 'musiconhold');
INSERT INTO organization_resource VALUES(58, 1, 'recordings');
INSERT INTO organization_resource VALUES(59, 1, 'control_panel');
INSERT INTO organization_resource VALUES(60, 1, 'voicemail');
INSERT INTO organization_resource VALUES(61, 1, 'monitoring');
INSERT INTO organization_resource VALUES(62, 1, 'batch_conf'); -- superadmin
INSERT INTO organization_resource VALUES(64, 1, 'extensions_batch'); -- superadmin
INSERT INTO organization_resource VALUES(65, 1, 'conference');
INSERT INTO organization_resource VALUES(66, 1, 'tools');
INSERT INTO organization_resource VALUES(67, 1, 'asterisk_cli'); -- superadmin
INSERT INTO organization_resource VALUES(68, 1, 'file_editor'); -- superadmin
INSERT INTO organization_resource VALUES(69, 1, 'text_to_wav'); 
INSERT INTO organization_resource VALUES(70, 1, 'festival'); -- superadmin
INSERT INTO organization_resource VALUES(73, 1, 'cdrreport');
INSERT INTO organization_resource VALUES(74, 1, 'channelusage'); -- superadmin
INSERT INTO organization_resource VALUES(75, 1, 'asterisk_log'); -- superadmin
INSERT INTO organization_resource VALUES(76, 1, 'graphic_report');
INSERT INTO organization_resource VALUES(77, 1, 'summary_by_extension');
INSERT INTO organization_resource VALUES(78, 1, 'missed_calls');
INSERT INTO organization_resource VALUES(82, 1, 'sec_firewall'); -- superadmin
INSERT INTO organization_resource VALUES(83, 1, 'sec_rules'); -- superadmin
INSERT INTO organization_resource VALUES(84, 1, 'sec_ports'); -- superadmin
INSERT INTO organization_resource VALUES(85, 1, 'sec_portknock_if'); -- superadmin
INSERT INTO organization_resource VALUES(86, 1, 'sec_portknock_users'); -- superadmin
INSERT INTO organization_resource VALUES(87, 1, 'sec_accessaudit'); -- superadmin
INSERT INTO organization_resource VALUES(88, 1, 'sec_weak_keys');
INSERT INTO organization_resource VALUES(89, 1, 'sec_advanced_settings'); -- superadmin

-- final_user
INSERT INTO organization_resource VALUES(100, 1, 'user_home'); 
INSERT INTO organization_resource VALUES(102, 1, 'user_setting');
INSERT INTO organization_resource VALUES(104, 1, 'vacations_msg');
INSERT INTO organization_resource VALUES(105, 1, 'calendar'); 
INSERT INTO organization_resource VALUES(106, 1, 'address_book'); 

-- system
INSERT INTO resource_action (id,id_resource,action) VALUES(1,'dashboard','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(2,'dashboard','start_service');
INSERT INTO resource_action (id,id_resource,action) VALUES(3,'dashboard','restart_service');
INSERT INTO resource_action (id,id_resource,action) VALUES(4,'dashboard','stop_service');
INSERT INTO resource_action (id,id_resource,action) VALUES(5,'dashboard','enable');
INSERT INTO resource_action (id,id_resource,action) VALUES(6,'dashboard','disable');

INSERT INTO resource_action (id,id_resource,action) VALUES(7,'applet_admin','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(8,'applet_admin','edit');

INSERT INTO resource_action (id,id_resource,action) VALUES(9,'organization','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(10,'organization','create_org');
INSERT INTO resource_action (id,id_resource,action) VALUES(12,'organization','edit_org');
INSERT INTO resource_action (id,id_resource,action) VALUES(13,'organization','delete_org');
INSERT INTO resource_action (id,id_resource,action) VALUES(14,'organization','change_org_status');
INSERT INTO resource_action (id,id_resource,action) VALUES(15,'organization','access_did');
INSERT INTO resource_action (id,id_resource,action) VALUES(16,'organization','edit_did');

INSERT INTO resource_action (id,id_resource,action) VALUES(17,'organization_permission','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(18,'organization_permission','edit');

INSERT INTO resource_action (id,id_resource,action) VALUES(19,'userlist','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(11,'userlist','create_user');
INSERT INTO resource_action (id,id_resource,action) VALUES(20,'userlist','edit_user');
INSERT INTO resource_action (id,id_resource,action) VALUES(21,'userlist','delete_user');

INSERT INTO resource_action (id,id_resource,action) VALUES(22,'grouplist','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(23,'grouplist','create_group');
INSERT INTO resource_action (id,id_resource,action) VALUES(24,'grouplist','edit_group');
INSERT INTO resource_action (id,id_resource,action) VALUES(25,'grouplist','delete_group');

INSERT INTO resource_action (id,id_resource,action) VALUES(26,'group_permission','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(27,'group_permission','edit_permission');

INSERT INTO resource_action (id,id_resource,action) VALUES(28,'network_parameters','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(29,'network_parameters','edit_network');
INSERT INTO resource_action (id,id_resource,action) VALUES(30,'network_parameters','access_interface');
INSERT INTO resource_action (id,id_resource,action) VALUES(31,'network_parameters','edit_interface');

INSERT INTO resource_action (id,id_resource,action) VALUES(32,'dhcp_server','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(33,'dhcp_server','enable_dhcp');
INSERT INTO resource_action (id,id_resource,action) VALUES(34,'dhcp_server','edit_dhcp');

INSERT INTO resource_action (id,id_resource,action) VALUES(35,'dhcp_clientlist','access');

INSERT INTO resource_action (id,id_resource,action) VALUES(36,'dhcp_by_mac','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(37,'dhcp_by_mac','create_dhcp');
INSERT INTO resource_action (id,id_resource,action) VALUES(38,'dhcp_by_mac','edit_dhcp');
INSERT INTO resource_action (id,id_resource,action) VALUES(39,'dhcp_by_mac','delete_dhcp');

INSERT INTO resource_action (id,id_resource,action) VALUES(40,'hardware_detector','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(41,'hardware_detector','detect_hardware');
INSERT INTO resource_action (id,id_resource,action) VALUES(42,'hardware_detector','edit_spam');

INSERT INTO resource_action (id,id_resource,action) VALUES(43,'shutdown','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(44,'shutdown','shutdown');

INSERT INTO resource_action (id,id_resource,action) VALUES(45,'repositories','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(46,'repositories','save_update');

INSERT INTO resource_action (id,id_resource,action) VALUES(47,'packages','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(48,'packages','update');
INSERT INTO resource_action (id,id_resource,action) VALUES(49,'packages','edit_status');

INSERT INTO resource_action (id,id_resource,action) VALUES(50,'language','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(51,'language','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(52,'themes_system','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(53,'themes_system','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(54,'time_config','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(55,'time_config','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(56,'currency','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(57,'currency','edit');

-- email_admin
INSERT INTO resource_action (id,id_resource,action) VALUES(58,'email_relay','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(59,'email_relay','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(60,'antispam','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(61,'antispam','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(62,'remote_smtp','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(63,'remote_smtp','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(64,'email_list','access');
INSERT INTO resource_action (id,id_resource,action) VALUES(65,'email_list','create');
INSERT INTO resource_action (id,id_resource,action) VALUES(66,'email_list','edit');
INSERT INTO resource_action (id,id_resource,action) VALUES(67,'email_list','delete');
INSERT INTO resource_action (id,id_resource,action) VALUES(68,'email_stats','access');

-- system
-- dashboard
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,1);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,2);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,3);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,4);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,5);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,6);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,1);
-- applet_admin
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,7);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,8);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,7);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,8);
-- organization
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,9);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,10);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,12);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,13);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,14);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,15);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,16);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,9);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,12);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,15);
-- organization_permission
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,17);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,18);
-- userlist
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,19);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,11);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,20);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,21);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,19);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,11);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,20);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,21);
-- grouplist
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,22);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,23);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,24);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,25);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,22);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,23);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,24);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,25);
-- group_permission
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,26);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,27);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,26);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,27);
-- network_parameters
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,28);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,29);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,30);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,31);
-- dhcp_server
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,32);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,33);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,34);
-- dhcp_clientlist
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,35);
-- dhcp_by_mac
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,36);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,37);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,38);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,39);
-- hardware_detector
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,40);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,41);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,42);
-- shutdown
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,43);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,44);
-- repositories
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,45);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,46);
-- packages
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,47);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,48);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,49);
-- language
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,50);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,51);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,50);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,51);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(3,50);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(3,51);
-- themes_system
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,52);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,53);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,52);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,53);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(3,52);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(3,53);
-- time_config
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,54);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,55);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,54);
-- currency 
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,56);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,57);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,56);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,57);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(3,56);

-- email_admin
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,58);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,59);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,60);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,61);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,62);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,63);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,64);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,65);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,66);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,67);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,64);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,65);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,66);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,67);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(1,68);
INSERT INTO group_resource_action (id_group,id_resource_action) VALUES(2,68);
