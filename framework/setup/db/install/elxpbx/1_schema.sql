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

CREATE TABLE IF NOT EXISTS action
(
    name VARCHAR(50),
    description TEXT,
    PRIMARY KEY (name)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS group_resource_actions
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    id_group INTEGER NOT NULL,
    id_org_resource INTEGER NOT NULL,
    id_action VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY (id_group) REFERENCES acl_group(id) ON DELETE CASCADE,
    FOREIGN KEY (id_org_resource) REFERENCES organization_resource(id) ON DELETE CASCADE,
    FOREIGN KEY (id_action) REFERENCES action(name) ON DELETE CASCADE,
    UNIQUE INDEX permission_group (id_group,id_org_resource,id_action)
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

CREATE TABLE IF NOT EXISTS user_resource_actions
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    id_user INTEGER NOT NULL,
    id_org_resource INTEGER NOT NULL,
    id_action VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY (id_user) REFERENCES acl_user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_org_resource) REFERENCES organization_resource(id) ON DELETE CASCADE,
    FOREIGN KEY (id_action) REFERENCES action(name) ON DELETE CASCADE,
    UNIQUE INDEX permission_group (id_user,id_org_resource,id_action)
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
INSERT INTO acl_group VALUES( 5,'final_user','Final User',1);
INSERT INTO acl_user (id,username,name,md5_password,id_group) VALUES(1,'admin','admin','7a5210c173ea40c03205a5de7dcd4cb0',1);


INSERT INTO acl_resource VALUES('system', 'System', '', '', '', 0,'yes','yes');
INSERT INTO acl_resource VALUES('sysdash', 'Dashboard Manager', 'system', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dashboard', 'Dashboard', 'sysdash', '', 'module', 11,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('applet_admin', 'Dashboard Applet Admin', 'sysdash', '', 'module', 12,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('orgmgr', 'Organization Manager', 'system', '', 'module', 2,'yes','yes');
INSERT INTO acl_resource VALUES('organization', 'Organization', 'orgmgr', '', 'module', 21,'yes','yes');
INSERT INTO acl_resource VALUES('organization_permission', 'Organization Resource', 'orgmgr', '', 'module', 22,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('usermgr', 'User/Group Manager', 'system', '', 'module', 3,'yes','yes');
INSERT INTO acl_resource VALUES('userlist', 'Users', 'usermgr', '', 'module', 1,'yes','yes'); 
INSERT INTO acl_resource VALUES('grouplist', 'Groups', 'usermgr', '', 'module', 2,'yes','yes'); 
INSERT INTO acl_resource VALUES('group_permission', 'Group Resource', 'usermgr', '', 'module', 3,'yes','yes'); 
INSERT INTO acl_resource VALUES('network', 'Network', 'system', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('network_parameters', 'Network Parameters', 'network', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dhcp_server', 'DHCP Server', 'network', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dhcp_clientlist', 'DHCP Client List', 'network', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('dhcp_by_mac', 'Assign IP Address to Host', 'network', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('shutdown', 'Shutdown', 'system', '', 'module', 5,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('hardware_configuration', 'Hardware Configuration', 'system', '', 'module', 6,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('hardware_detector', 'Hardware Detector', 'hardware_configuration', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('updates', 'Updates', 'system', '', 'module', 7,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('repositories', 'Repositories', 'updates', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('packages', 'Packages', 'updates', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('preferences', 'Preferences', 'system', '', 'module', 8,'yes','yes'); 
INSERT INTO acl_resource VALUES('language', 'Language', 'preferences', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('themes_system', 'Themes', 'preferences', '', 'module', 2,'yes','yes');
INSERT INTO acl_resource VALUES('time_config', 'Date/Time', 'preferences', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('currency', 'Currency', 'preferences', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('email_admin', 'Email', '', '', '', 2,'yes','yes');
INSERT INTO acl_resource VALUES('email_accounts', 'Accounts', 'email_admin', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('email_relay', 'Relay', 'email_admin', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('antispam', 'Antispam', 'email_admin', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('remote_smtp', 'Remote SMTP', 'email_admin', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('email_list', 'Email list', 'email_admin', '', 'module', 5,'yes','yes');
INSERT INTO acl_resource VALUES('email_stats', 'Email stats', 'email_admin', '', 'module', 6,'yes','yes');
INSERT INTO acl_resource VALUES('fax', 'Fax', '', '', '', 3,'yes','yes');
INSERT INTO acl_resource VALUES('virtual_fax', 'Virtual Fax', 'fax', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('faxlist', 'Virtual Fax List', 'virtual_fax', '', 'module', 2,'yes','yes');

INSERT INTO acl_resource VALUES('faxmaster', 'Fax Master', 'fax', '', 'module', 4,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('faxclients', 'Fax Clients', 'fax', '', 'module', 5,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('faxviewer', 'Fax Viewer', 'fax', '', 'module', 6,'yes','yes'); 
INSERT INTO acl_resource VALUES('pbxconfig', 'PBX', '', '', '', 4,'yes','yes');
INSERT INTO acl_resource VALUES('pbxadmin', 'PBX Configuration', 'pbxconfig', '', 'module', 1,'yes','yes');
INSERT INTO acl_resource VALUES('trunks', 'Trunks', 'pbxadmin', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('did', 'DID Assign', 'pbxadmin', '', 'module', 2,'yes','no'); -- superadmin
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
INSERT INTO acl_resource VALUES('batch_conf', 'Batch Configurations', 'pbxconfig', '', 'module', 5,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('endpoint_configurator', 'Endpoint Configurator', 'batch_conf', '', 'module', 1,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('endpoints_batch', 'Batch of Endpoints', 'batch_conf', '', 'module', 2,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('extensions_batch', 'Batch of Extensions', 'batch_conf', '', 'module', 3,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('conference', 'Conference', 'pbxconfig', '', 'module', 6,'yes','yes'); 
INSERT INTO acl_resource VALUES('tools', 'Tools', 'pbxconfig', '', 'module', 7,'yes','yes');
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
INSERT INTO acl_resource VALUES('addons', 'Addons', '', '', '', 6,'yes','no'); -- superadmin
INSERT INTO acl_resource VALUES('addons_availables', 'Addons', 'addons', '', 'module', 1,'yes','no'); -- superadmin
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
INSERT INTO acl_resource VALUES('home', 'Home', '', '', '', 1,'no','yes');
INSERT INTO acl_resource VALUES('user_home', 'My Home', 'home', '', 'module', 1,'no','yes');
INSERT INTO acl_resource VALUES('sendfax', 'Send Fax', 'home', '', 'module', 2,'no','yes');
INSERT INTO acl_resource VALUES('user_setting', 'My settings', '', '', '', 2,'no','yes');
INSERT INTO acl_resource VALUES('user_language', 'Language', 'user_setting', '', 'module', 1,'no','yes');
INSERT INTO acl_resource VALUES('user_themes_system', 'Themes', 'user_setting', '', 'module', 2,'no','yes');
INSERT INTO acl_resource VALUES('vacations_msg', 'Vacations', 'user_setting', '', 'module', 3,'no','yes');
INSERT INTO acl_resource VALUES('agenda', 'Agenda', '', '', '', 3,'no','yes');
INSERT INTO acl_resource VALUES('calendar', 'Calendar', 'agenda', '', 'module', 1,'no','yes');
INSERT INTO acl_resource VALUES('address_book', 'Address Book', 'agenda', '', 'module', 2,'no','yes');

INSERT INTO organization_resource VALUES(1, 1, 'sysdash');
INSERT INTO organization_resource VALUES(2, 1, 'dashboard');
INSERT INTO organization_resource VALUES(3, 1, 'applet_admin');
INSERT INTO organization_resource VALUES(4, 1, 'orgmgr');
INSERT INTO organization_resource VALUES(5, 1, 'organization');
INSERT INTO organization_resource VALUES(6, 1, 'organization_permission');
INSERT INTO organization_resource VALUES(7, 1, 'usermgr');
INSERT INTO organization_resource VALUES(8, 1, 'userlist');
INSERT INTO organization_resource VALUES(9, 1, 'grouplist');
INSERT INTO organization_resource VALUES(90, 1, 'group_permission');
INSERT INTO organization_resource VALUES(10, 1, 'network');
INSERT INTO organization_resource VALUES(11, 1, 'network_parameters');
INSERT INTO organization_resource VALUES(12, 1, 'dhcp_server');
INSERT INTO organization_resource VALUES(13, 1, 'dhcp_clientlist');
INSERT INTO organization_resource VALUES(14, 1, 'dhcp_by_mac');
INSERT INTO organization_resource VALUES(15, 1, 'shutdown');
INSERT INTO organization_resource VALUES(16, 1, 'hardware_configuration');
INSERT INTO organization_resource VALUES(17, 1, 'hardware_detector');
INSERT INTO organization_resource VALUES(18, 1, 'updates');
INSERT INTO organization_resource VALUES(19, 1, 'repositories');
INSERT INTO organization_resource VALUES(20, 1, 'packages');
INSERT INTO organization_resource VALUES(21, 1, 'preferences');
INSERT INTO organization_resource VALUES(22, 1, 'language');
INSERT INTO organization_resource VALUES(23, 1, 'themes_system');
INSERT INTO organization_resource VALUES(24, 1, 'time_config');
INSERT INTO organization_resource VALUES(25, 1, 'currency');
INSERT INTO organization_resource VALUES(30, 1, 'email_accounts');
INSERT INTO organization_resource VALUES(31, 1, 'email_relay');
INSERT INTO organization_resource VALUES(32, 1, 'antispam');
INSERT INTO organization_resource VALUES(33, 1, 'remote_smtp');
INSERT INTO organization_resource VALUES(34, 1, 'email_list');
INSERT INTO organization_resource VALUES(35, 1, 'email_stats');
INSERT INTO organization_resource VALUES(37, 1, 'virtual_fax');
INSERT INTO organization_resource VALUES(38, 1, 'faxlist');
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
INSERT INTO organization_resource VALUES(63, 1, 'endpoint_configurator'); -- superadmin
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
INSERT INTO organization_resource VALUES(80, 1, 'addons_availables'); -- superadmin
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
INSERT INTO organization_resource VALUES(101, 1, 'sendfax'); 
INSERT INTO organization_resource VALUES(102, 1, 'user_language');
INSERT INTO organization_resource VALUES(103, 1, 'user_themes_system');
INSERT INTO organization_resource VALUES(104, 1, 'vacations_msg');
INSERT INTO organization_resource VALUES(105, 1, 'calendar'); 
INSERT INTO organization_resource VALUES(106, 1, 'address_book'); 


INSERT INTO action VALUES('access','Access');
INSERT INTO action VALUES('create','Create');
INSERT INTO action VALUES('edit','Edit');
INSERT INTO action VALUES('delete','Delete');
INSERT INTO action VALUES('download','Download');

-- superadmin
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 1, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 2, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 3, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 4, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 5, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 6, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 7, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 8, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 9, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 10, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 11, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 12, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 13, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 14, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 15, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 16, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 17, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 18, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 19, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 20, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 21, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 22, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 23, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 24, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 25, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 30, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 31, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 32, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 33, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 34, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 35, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 37, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 38, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 40, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 41, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 42, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 43, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 44, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 45, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 46, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 47, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 48, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 49, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 50, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 51, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 52, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 53, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 54, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 55, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 56, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 57, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 58, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 59, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 60, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 61, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 62, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 63, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 64, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 66, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 67, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 68, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 69, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 70, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 73, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 74, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 75, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 76, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 77, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 78, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 80, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 82, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 83, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 84, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 85, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 86, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 87, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 88, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 89, 'access');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 1, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 2, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 3, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 4, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 5, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 6, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 7, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 8, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 9, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 10, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 11, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 12, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 13, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 14, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 15, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 16, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 17, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 18, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 19, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 20, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 21, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 22, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 23, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 24, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 25, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 30, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 31, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 32, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 33, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 34, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 35, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 37, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 38, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 40, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 41, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 42, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 43, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 44, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 45, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 46, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 47, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 48, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 49, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 50, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 51, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 52, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 53, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 54, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 55, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 56, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 57, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 58, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 59, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 60, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 61, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 62, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 63, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 64, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 66, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 67, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 68, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 69, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 70, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 73, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 74, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 75, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 76, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 77, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 78, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 80, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 82, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 83, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 84, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 85, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 86, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 87, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 88, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 89, 'create');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 1, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 2, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 3, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 4, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 5, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 6, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 7, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 8, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 9, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 10, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 11, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 12, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 13, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 14, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 15, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 16, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 17, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 18, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 19, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 20, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 21, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 22, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 23, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 24, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 25, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 30, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 31, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 32, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 33, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 34, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 35, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 37, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 38, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 40, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 41, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 42, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 43, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 44, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 45, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 46, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 47, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 48, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 49, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 50, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 51, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 52, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 53, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 54, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 55, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 56, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 57, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 58, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 59, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 60, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 61, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 62, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 63, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 64, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 66, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 67, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 68, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 69, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 70, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 73, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 74, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 75, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 76, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 77, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 78, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 80, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 82, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 83, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 84, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 85, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 86, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 87, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 88, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 89, 'edit');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 1, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 2, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 3, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 4, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 5, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 6, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 7, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 8, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 9, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 10, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 11, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 12, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 13, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 14, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 15, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 16, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 17, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 18, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 19, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 20, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 21, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 22, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 23, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 24, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 25, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 30, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 31, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 32, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 33, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 34, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 35, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 37, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 38, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 40, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 41, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 42, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 43, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 44, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 45, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 46, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 47, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 48, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 49, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 50, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 51, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 52, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 53, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 54, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 55, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 56, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 57, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 58, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 59, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 60, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 61, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 62, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 63, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 64, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 66, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 67, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 68, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 69, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 70, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 73, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 74, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 75, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 76, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 77, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 78, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 80, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 82, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 83, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 84, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 85, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 86, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 87, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 88, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 89, 'delete');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 1, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 2, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 3, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 4, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 5, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 6, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 7, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 8, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 9, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 10, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 11, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 12, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 13, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 14, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 15, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 16, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 17, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 18, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 19, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 20, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 21, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 22, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 23, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 24, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 25, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 30, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 31, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 32, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 33, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 34, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 35, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 37, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 38, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 40, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 41, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 42, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 43, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 44, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 45, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 46, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 47, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 48, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 49, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 50, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 51, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 52, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 53, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 54, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 55, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 56, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 57, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 58, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 59, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 60, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 61, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 62, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 63, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 64, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 66, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 67, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 68, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 69, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 70, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 73, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 74, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 75, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 76, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 77, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 78, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 80, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 82, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 83, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 84, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 85, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 86, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 87, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 88, 'download');
INSERT INTO group_resource_actions (id_group,id_org_resource,id_action) VALUES(1, 89, 'download');
-- admin
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 4, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 5, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 7, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 8, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 9, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 90, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 21, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 22, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 23, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 30, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 34, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 35, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 37, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 38, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 42, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 43, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 47, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 48, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 49, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 50, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 51, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 52, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 53, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 54, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 55, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 56, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 57, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 58, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 59, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 60, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 61, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 65, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 66, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 69, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 73, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 76, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 77, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 78, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 88, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 4, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 5, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 7, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 8, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 9, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 90, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 21, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 22, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 23, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 30, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 34, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 35, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 37, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 38, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 42, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 43, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 47, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 48, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 49, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 50, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 51, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 52, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 53, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 54, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 55, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 56, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 57, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 58, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 59, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 60, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 61, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 65, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 66, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 69, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 73, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 76, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 77, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 78, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 88, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 4, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 5, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 7, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 8, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 9, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 90, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 21, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 22, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 23, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 30, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 34, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 35, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 37, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 38, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 42, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 43, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 47, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 48, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 49, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 50, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 51, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 52, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 53, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 54, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 55, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 56, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 57, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 58, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 59, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 60, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 61, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 65, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 66, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 69, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 73, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 76, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 77, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 78, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 88, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 4, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 5, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 7, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 8, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 9, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 90, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 21, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 22, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 23, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 30, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 34, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 35, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 37, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 38, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 42, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 43, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 47, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 48, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 49, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 50, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 51, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 52, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 53, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 54, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 55, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 56, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 57, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 58, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 59, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 60, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 61, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 65, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 66, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 69, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 73, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 76, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 77, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 78, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 88, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 4, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 5, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 7, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 8, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 9, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 90, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 21, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 22, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 23, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 30, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 34, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 35, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 37, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 38, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 42, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 43, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 47, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 48, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 49, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 50, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 51, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 52, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 53, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 54, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 55, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 56, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 57, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 58, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 59, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 60, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 61, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 65, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 66, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 69, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 73, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 76, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 77, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 78, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 88, 'download');

INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 100, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 101, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 102, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 103, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 104, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 105, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 106, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 100, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 101, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 102, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 103, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 104, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 105, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 106, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 100, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 101, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 102, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 103, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 104, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 105, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 106, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 100, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 101, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 102, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 103, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 104, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 105, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 106, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 100, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 101, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 102, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 103, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 104, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 105, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(2, 106, 'download');

-- supervisor
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 4, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 5, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 7, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 8, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 9, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 21, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 22, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 23, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 30, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 35, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 37, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 38, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 42, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 43, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 47, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 60, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 61, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 76, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 77, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 78, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 88, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 4, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 5, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 7, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 8, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 9, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 21, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 22, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 23, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 30, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 35, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 37, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 38, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 42, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 43, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 47, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 60, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 61, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 76, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 77, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 78, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 88, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 100, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 101, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 102, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 103, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 104, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 105, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 106, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 100, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 101, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 102, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 103, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 104, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 105, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 106, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 100, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 101, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 102, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 103, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 104, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 105, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 106, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 100, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 101, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 102, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 103, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 104, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 105, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 106, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 100, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 101, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 102, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 103, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 104, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 105, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(3, 106, 'download');

-- final_user
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 100, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 101, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 102, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 103, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 104, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 105, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 106, 'access');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 100, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 101, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 102, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 103, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 104, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 105, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 106, 'create');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 100, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 101, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 102, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 103, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 104, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 105, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 106, 'edit');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 100, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 101, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 102, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 103, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 104, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 105, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 106, 'delete');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 100, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 101, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 102, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 103, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 104, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 105, 'download');
INSERT INTO group_resource_actions  (id_group,id_org_resource,id_action) VALUES(5, 106, 'download');

