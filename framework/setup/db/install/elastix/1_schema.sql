CREATE TABLE organization
(
    id                INTEGER  NOT NULL,
    name              VARCHAR(150) NOT NULL,
    domain            VARCHAR(100) NOT NULL,
    email_contact     VARCHAR(100),
    country           VARCHAR(100) NOT NULL,
    city              VARCHAR(150) NOT NULL,
    address           VARCHAR(255),
    --codigo de la organizacion usado en asterisk como identificador
    code              VARCHAR(20) NOT NULL, 
    --codigo unico de la orgnizacion usado para identificarla de manera unica dentro del sistema
    idcode            VARCHAR(50) NOT NULL,   
    state             VARCHAR(20) default "active",  
    PRIMARY KEY (id)
);
create unique index domain on organization (domain);
create unique index code on organization (code);
create unique index idcode on organization (idcode);

CREATE TABLE organization_properties
(
    id_organization         INTEGER     NOT NULL,
    key               varchar(50) NOT NULL,
    value             varchar(50) NOT NULL,
    category         varchar(50),
    PRIMARY KEY (id_organization,key),
    FOREIGN KEY (id_organization) REFERENCES organization(id)
);

--tabla org_email_template contiene
--los parametros usados en el envio
--de un mail a las organizaciones desde
--el servidor elastix al momento de 
--crear, eleminar o suspender 
--una organizacion
CREATE TABLE org_email_template(
    from_email varchar(250) NOT NULL,
    from_name varchar(250) NOT NULL,
    subject varchar(250) NOT NULL,
    content TEXT NOT NULL,
    host_ip varchar(250) default "",
    host_domain varchar(250) default "",
    host_name varchar(250) default "",
    category varchar(250) NOT NULL,
    PRIMARY KEY (category)
);

insert into org_email_template (from_email,from_name,subject,content,category) values("elastix@example.com","Elastix Admin","Create Company in Elastix Server",'Welcome to Elastix Server.\nYour company {COMPANY_NAME} with domain {DOMAIN} has been created.\nTo start to configurate you elastix server go to {HOST_IP} and login into elastix as:\nUsername: admin@{DOMAIN}\nPassword: {USER_PASSWORD}',"create");

insert into org_email_template (from_email,from_name,subject,content,category) values("elastix@example.com","Elastix Admin","Deleted Company in Elastix Server","","delete");

insert into org_email_template (from_email,from_name,subject,content,category) values("elastix@example.com","Elastix Admin","Suspended Company in Elastix Server","","suspend");

--tabla creada con propositos de auditoria que guarda las acciones tomadas
--con respecto a una organizacion dentro del sistema
--entiendese por acciones el crear, suspender, reactivar o eliminar una organizacion del sistema
CREATE TABLE org_history_events(
    id INTEGER  NOT NULL,
    --create,suspend,unsuspend,delete,
    event varchar(100) NOT NULL,
    --codigo unico generado  para la organizacion 
    --este codigo no se puede repetir dentro del sistema
    org_idcode VARCHAR(50),
    --fecha en que ocurrio el evento
    event_date DATETIME NOT NULL,
    PRIMARY KEY (id)
);

--esta tabla contiene informacion de todas las organizaciones creadas en algun
--momento dentro del sistema
CREATE TABLE org_history_register(
    id INTEGER  NOT NULL,
    org_domain VARCHAR(100) NOT NULL, 
    org_code VARCHAR(20) NOT NULL, 
    --codigo unico generado  para la organizacion 
    --este codigo no se puede repetir dentro del sistema
    org_idcode VARCHAR(50) NOT NULL,
    --fecha en que ocurrio el evento
    create_date DATETIME NOT NULL,
    delete_date DATETIME default NULL,
    PRIMARY KEY (id)
);
create unique index orgIdcode on org_history_register (org_idcode);

CREATE TABLE acl_resource
(
    id varchar(50) NOT NULL, --menuid , es el unico identficador del recurso
    description varchar(100),
    IdParent varchar(50),
    Link varchar(250),
    Type varchar(20),
    order_no INTEGER,
    PRIMARY KEY (id)
);

CREATE TABLE organization_resource
(
    id INTEGER NOT NULL,
    id_organization INTEGER NOT NULL,
    id_resource varchar(50) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id),
    FOREIGN KEY (id_resource) REFERENCES acl_resource(id)
);
create unique index permission_org on organization_resource (id_organization,id_resource);

CREATE TABLE acl_group
(
    id INTEGER NOT NULL,
    name VARCHAR(200),
    description TEXT,
    id_organization INTEGER NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id)
);
create unique index name_group on acl_group (id_organization,name);

CREATE TABLE group_resource
(
    id INTEGER NOT NULL,
    id_group INTEGER NOT NULL,
    id_org_resource INTEGER NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id_group) REFERENCES acl_group(id),
    FOREIGN KEY (id_org_resource) REFERENCES organization_resource(id)
);
create unique index permission_group on group_resource (id_group,id_org_resource);

CREATE TABLE acl_user
(
    id INTEGER NOT NULL,
    username VARCHAR(150) NOT NULL,
    name VARCHAR(150),
    md5_password VARCHAR(100) NOT NULL,
    id_group INTEGER NOT NULL,
    extension VARCHAR(20),
    fax_extension VARCHAR(20),
    picture varchar(50),
    PRIMARY KEY (id),
    FOREIGN KEY (id_group) REFERENCES acl_group(id)
);
create unique index username on acl_user (username);

CREATE TABLE user_shortcut
(
    id           INTEGER     NOT NULL,
    id_user      INTEGER     NOT NULL,
    id_resource  varchar(50) NOT NULL,
    type         VARCHAR(50) NOT NULL,
    description  VARCHAR(50),
    PRIMARY KEY (id)
    FOREIGN KEY (id_user) REFERENCES acl_user(id),
    FOREIGN KEY (id_resource) REFERENCES acl_resource(id)
);

CREATE TABLE sticky_note
(
    id           INTEGER     NOT NULL,
    id_user      INTEGER     NOT NULL,
    id_resource  varchar(50) NOT NULL,
    date_edit    DATETIME    NOT NULL,
    description  TEXT,
    auto_popup   INTEGER NOT NULL DEFAULT '0',
    PRIMARY KEY (id)
);

CREATE TABLE user_properties
(
    id_user   INTEGER     NOT NULL,
    property     VARCHAR(100) NOT NULL,
    value        VARCHAR(150) NOT NULL,
    category     VARCHAR(50),
    PRIMARY KEY (id_user,property,category),
    FOREIGN KEY (id_user) REFERENCES acl_user(id)
);

CREATE TABLE email_list
(
    id               INTEGER,
    id_organization  INTEGER,
    listname    VARCHAR(50),
    password    VARCHAR(15),
    mailadmin   VARCHAR(150),
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id)
);

CREATE TABLE member_list
(
    id              INTEGER NOT null,
    mailmember      VARCHAR(150),
    id_emaillist    INTEGER,
    namemember      VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY(id_emaillist) REFERENCES email_list(id)
);

CREATE TABLE messages_vacations
(
    id          INTEGER NOT NULL,
    account     varchar(150) NOT NULL,
    subject     varchar(150) NOT NULL,
    body        text,
    vacation varchar(5) default 'no',
    ini_date date NOT NULL,
    end_date date NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (account) REFERENCES acl_user(username)
);

CREATE TABLE email_statistics(
    id                 integer not null,
    date               datetime,
    unix_time          integer,
    total              integer,
    type               integer,
    id_organization    integer,
    PRIMARY KEY (id),
    FOREIGN KEY (id_organization) REFERENCES organization(id)
);

CREATE TABLE settings
(
    property               varchar(32) NOT NULL,
    value             varchar(32) NOT NULL,
    PRIMARY KEY (property)
);

CREATE TABLE fax_docs
(
    id             integer          PRIMARY KEY,
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
    FOREIGN KEY    (id_user) REFERENCES acl_user(id)
);

INSERT INTO "settings" VALUES('elastix_version_release', '2.3.0-6');

INSERT INTO "organization" VALUES(1,'NONE','','','','','','','','active');

INSERT INTO "organization_properties" VALUES(1,'language','en','system');
INSERT INTO "organization_properties" VALUES(1,'default_rate',0.50,'system');
INSERT INTO "organization_properties" VALUES(1,'default_rate_offset',1,'system');
INSERT INTO "organization_properties" VALUES(1,'currency','$','system');
INSERT INTO "organization_properties" VALUES(1,'theme','elastixneo','system');

INSERT INTO "acl_group" VALUES( 0,'superadmin','super elastix admin',1);
INSERT INTO "acl_group" VALUES( 1,'administrator','total access',1);
INSERT INTO "acl_group" VALUES( 2,'operator','Operator',1);
INSERT INTO "acl_group" VALUES( 3,'extension','extension user',1);

INSERT INTO "acl_user" VALUES(1,'admin','admin','7a5210c173ea40c03205a5de7dcd4cb0',0,'','','');

INSERT INTO "acl_resource" VALUES('system', 'System', '', '', '', 0);
INSERT INTO "acl_resource" VALUES('usermgr', 'Users', 'system', '', 'module', 4);
INSERT INTO "acl_resource" VALUES('organization', 'Organization', 'usermgr', '', 'module', 41);
INSERT INTO "acl_resource" VALUES('userlist', 'Users', 'usermgr', '', 'module', 42);
INSERT INTO "acl_resource" VALUES('grouplist', 'Groups', 'usermgr', '', 'module', 43);
INSERT INTO "acl_resource" VALUES('organization_permission', 'Organization Resource', 'usermgr', '', 'module', 44);
INSERT INTO "acl_resource" VALUES('group_permission', 'Group Resource', 'usermgr', '', 'module', 45);
INSERT INTO "acl_resource" VALUES('preferences', 'Preferences', 'system', '', 'module', 10);
INSERT INTO "acl_resource" VALUES('language', 'Language', 'preferences', '', 'module', 101);
INSERT INTO "acl_resource" VALUES('themes_system', 'Themes', 'preferences', '', 'module', 103);
INSERT INTO "acl_resource" VALUES('webmail', 'Webmail', 'email_admin', 'mail', 'framed', 4);
INSERT INTO "acl_resource" VALUES('addons', 'Addons', '', '', '', 10);
INSERT INTO "acl_resource" VALUES('addons_availables', 'Addons', 'addons', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('reports', 'Reports', '', '', '', 8);
INSERT INTO "acl_resource" VALUES('cdrreport', 'CDR Report', 'reports', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('channelusage', 'Channels Usage', 'reports', '', 'module', 2);
INSERT INTO "acl_resource" VALUES('billing', 'Billing', 'reports', '', 'module', 3);
INSERT INTO "acl_resource" VALUES('billing_rates', 'Rates', 'billing', '', 'module', 31);
INSERT INTO "acl_resource" VALUES('billing_report', 'Billing Report', 'billing', '', 'module', 32);
INSERT INTO "acl_resource" VALUES('dest_distribution', 'Destination Distribution', 'billing', '', 'module', 33);
INSERT INTO "acl_resource" VALUES('billing_setup', 'Billing Setup', 'billing', '', 'module', 34);
INSERT INTO "acl_resource" VALUES('asterisk_log', 'Asterisk Logs', 'reports', '', 'module', 4);
INSERT INTO "acl_resource" VALUES('graphic_report', 'Graphic Report', 'reports', '', 'module', 5);
INSERT INTO "acl_resource" VALUES('summary_by_extension', 'Summary', 'reports', '', 'module', 6);
INSERT INTO "acl_resource" VALUES('missed_calls', 'Missed Calls', 'reports', '', 'module', 7);
INSERT INTO "acl_resource" VALUES('im', 'IM', '', '', '', 6);
INSERT INTO "acl_resource" VALUES('openfire', 'OpenFire', 'im', 'openfireWrapper.php?IP={NAME_SERVER}&PORT=9090', 'framed', 1);
INSERT INTO "acl_resource" VALUES('agenda', 'Agenda', '', '', '', 2);
INSERT INTO "acl_resource" VALUES('calendar', 'Calendar', 'agenda', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('address_book', 'Address Book', 'agenda', '', 'module', 2);
INSERT INTO "acl_resource" VALUES('security', 'Security', '', '', '', 12);
INSERT INTO "acl_resource" VALUES('sec_firewall', 'Firewall', 'security', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('sec_rules', 'Firewall Rules', 'sec_firewall', '', 'module', 11);
INSERT INTO "acl_resource" VALUES('sec_ports', 'Define Ports', 'sec_firewall', '', 'module', 12);
INSERT INTO "acl_resource" VALUES('sec_portknock_if', 'Port Knocking Interfaces', 'sec_firewall', '', 'module', 13);
INSERT INTO "acl_resource" VALUES('sec_portknock_users', 'Port Knocking Users', 'sec_firewall', '', 'module', 14);
INSERT INTO "acl_resource" VALUES('sec_accessaudit', 'Audit', 'security', '', 'module', 2);
INSERT INTO "acl_resource" VALUES('sec_weak_keys', 'Weak Keys', 'security', '', 'module', 3);
INSERT INTO "acl_resource" VALUES('sec_advanced_settings', 'Advanced Settings', 'security', '', 'module', 4);
INSERT INTO "acl_resource" VALUES('email_admin', 'Email', '', '', '', 3);
INSERT INTO "acl_resource" VALUES('email_accounts', 'Accounts', 'email_admin', '', 'module', 2);
INSERT INTO "acl_resource" VALUES('email_relay', 'Relay', 'email_admin', '', 'module', 3);
INSERT INTO "acl_resource" VALUES('antispam', 'Antispam', 'email_admin', '', 'module', 5);
INSERT INTO "acl_resource" VALUES('remote_smtp', 'Remote SMTP', 'email_admin', '', 'module', 6);
INSERT INTO "acl_resource" VALUES('email_list', 'Email list', 'email_admin', '', 'module', 7);
INSERT INTO "acl_resource" VALUES('email_stats', 'Email stats', 'email_admin', '', 'module', 8);
INSERT INTO "acl_resource" VALUES('vacations', 'Vacations', 'email_admin', '', 'module', 9);
INSERT INTO "acl_resource" VALUES('fax', 'Fax', '', '', '', 4);
INSERT INTO "acl_resource" VALUES('virtual_fax', 'Virtual Fax', 'fax', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('faxlist', 'Virtual Fax List', 'virtual_fax', '', 'module', 11);
INSERT INTO "acl_resource" VALUES('sendfax', 'Send Fax', 'virtual_fax', '', 'module', 13);
INSERT INTO "acl_resource" VALUES('faxmaster', 'Fax Master', 'fax', '', 'module', 2);
INSERT INTO "acl_resource" VALUES('faxclients', 'Fax Clients', 'fax', '', 'module', 3);
INSERT INTO "acl_resource" VALUES('faxviewer', 'Fax Viewer', 'fax', '', 'module', 4);
INSERT INTO "acl_resource" VALUES('sysdash', 'Dashboard', 'system', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('dashboard', 'Dashboard', 'sysdash', '', 'module', 11);
INSERT INTO "acl_resource" VALUES('applet_admin', 'Dashboard Applet Admin', 'sysdash', '', 'module', 12);
INSERT INTO "acl_resource" VALUES('network', 'Network', 'system', '', 'module', 3);
INSERT INTO "acl_resource" VALUES('network_parameters', 'Network Parameters', 'network', '', 'module', 31);
INSERT INTO "acl_resource" VALUES('dhcp_server', 'DHCP Server', 'network', '', 'module', 32);
INSERT INTO "acl_resource" VALUES('dhcp_clientlist', 'DHCP Client List', 'network', '', 'module', 33);
INSERT INTO "acl_resource" VALUES('dhcp_by_mac', 'Assign IP Address to Host', 'network', '', 'module', 34);
INSERT INTO "acl_resource" VALUES('shutdown', 'Shutdown', 'system', '', 'module', 6);
INSERT INTO "acl_resource" VALUES('hardware_configuration', 'Hardware Configuration', 'system', '', 'module', 7);
INSERT INTO "acl_resource" VALUES('hardware_detector', 'Hardware Detector', 'hardware_configuration', '', 'module', 71);
INSERT INTO "acl_resource" VALUES('updates', 'Updates', 'system', '', 'module', 8);
INSERT INTO "acl_resource" VALUES('packages', 'Packages', 'updates', '', 'module', 82);
INSERT INTO "acl_resource" VALUES('repositories', 'Repositories', 'updates', '', 'module', 81);
INSERT INTO "acl_resource" VALUES('time_config', 'Date/Time', 'preferences', '', 'module', 102);
INSERT INTO "acl_resource" VALUES('currency', 'Currency', 'preferences', '', 'module', 104);
INSERT INTO "acl_resource" VALUES('pbxconfig', 'PBX', '', '', '', 5);
INSERT INTO "acl_resource" VALUES('pbxadmin', 'PBX Configuration', 'pbxconfig', '', 'module', 1);
INSERT INTO "acl_resource" VALUES('control_panel', 'Operator Panel', 'pbxconfig', '', 'module', 2);
INSERT INTO "acl_resource" VALUES('voicemail', 'Voicemail', 'pbxconfig', '', 'module', 3);
INSERT INTO "acl_resource" VALUES('monitoring', 'Monitoring', 'pbxconfig', '', 'module', 4);
INSERT INTO "acl_resource" VALUES('endpoint_configurator', 'Endpoint Configurator', 'pbxconfig', '', 'module', 5);
INSERT INTO "acl_resource" VALUES('conference', 'Conference', 'pbxconfig', '', 'module', 6);
INSERT INTO "acl_resource" VALUES('extensions_batch', 'Batch of Extensions', 'pbxconfig', '', 'module', 7);
INSERT INTO "acl_resource" VALUES('tools', 'Tools', 'pbxconfig', '', 'module', 8);
INSERT INTO "acl_resource" VALUES('asterisk_cli', 'Asterisk-Cli', 'tools', '', 'module', 81);
INSERT INTO "acl_resource" VALUES('file_editor', 'Asterisk File Editor', 'tools', '', 'module', 82);
INSERT INTO "acl_resource" VALUES('text_to_wav', 'Text to Wav', 'tools', '', 'module', 83);
INSERT INTO "acl_resource" VALUES('festival', 'Festival', 'tools', '', 'module', 84);
INSERT INTO "acl_resource" VALUES('fop', 'Flash Operator Panel', 'pbxconfig', 'panel', 'framed', 9);
INSERT INTO "acl_resource" VALUES('trunks', 'Trunks', 'pbxadmin', '', 'module', 11);
INSERT INTO "acl_resource" VALUES('did', 'DID Assign', 'pbxadmin', '', 'module', 12);
INSERT INTO "acl_resource" VALUES('general_settings_admin', 'General Settings', 'pbxadmin', '', 'module', 13);
INSERT INTO "acl_resource" VALUES('extensions', 'Extensions', 'pbxadmin', '', 'module', 14);
INSERT INTO "acl_resource" VALUES('queues', 'Queues', 'pbxadmin', '', 'module', 18);
INSERT INTO "acl_resource" VALUES('ivr', 'IVR', 'pbxadmin', '', 'module', 17);
INSERT INTO "acl_resource" VALUES('features_code', 'Features Codes', 'pbxadmin', '', 'module', 19);
INSERT INTO "acl_resource" VALUES('general_settings', 'General Settings', 'pbxadmin', '', 'module', 20);
INSERT INTO "acl_resource" VALUES('inbound_route', 'Inbound Routes', 'pbxadmin', '', 'module', 16);
INSERT INTO "acl_resource" VALUES('outbound_route', 'Outbound Routes', 'pbxadmin', '', 'module', 15);
INSERT INTO "acl_resource" VALUES('ring_group', 'Ring Groups', 'pbxadmin', '', 'module', 21);
INSERT INTO "acl_resource" VALUES('time_group', 'Time Group', 'pbxadmin', '', 'module', 22);
INSERT INTO "acl_resource" VALUES('time_conditions', 'Time Conditions', 'pbxadmin', '', 'module', 23);
INSERT INTO "acl_resource" VALUES('musiconhold', 'Music On Hold', 'pbxadmin', '', 'module', 24);
INSERT INTO "acl_resource" VALUES('recordings', 'Recordings', 'pbxadmin', '', 'module', 25);

INSERT INTO "organization_resource" VALUES(1, 1, 'usermgr');
INSERT INTO "organization_resource" VALUES(2, 1, 'organization');
INSERT INTO "organization_resource" VALUES(3, 1, 'userlist');
INSERT INTO "organization_resource" VALUES(4, 1, 'grouplist');
INSERT INTO "organization_resource" VALUES(5, 1, 'organization_permission');
INSERT INTO "organization_resource" VALUES(6, 1, 'group_permission');
INSERT INTO "organization_resource" VALUES(7, 1, 'preferences');
INSERT INTO "organization_resource" VALUES(8, 1, 'language');
INSERT INTO "organization_resource" VALUES(9, 1, 'themes_system');
INSERT INTO "organization_resource" VALUES(11, 1, 'webmail');
INSERT INTO "organization_resource" VALUES(12, 1, 'addons_availables');
INSERT INTO "organization_resource" VALUES(13, 1, 'cdrreport');
INSERT INTO "organization_resource" VALUES(14, 1, 'channelusage');
INSERT INTO "organization_resource" VALUES(15, 1, 'billing');
INSERT INTO "organization_resource" VALUES(16, 1, 'billing_rates');
INSERT INTO "organization_resource" VALUES(17, 1, 'billing_report');
INSERT INTO "organization_resource" VALUES(18, 1, 'dest_distribution');
INSERT INTO "organization_resource" VALUES(19, 1, 'billing_setup');
INSERT INTO "organization_resource" VALUES(20, 1, 'asterisk_log');
INSERT INTO "organization_resource" VALUES(21, 1, 'graphic_report');
INSERT INTO "organization_resource" VALUES(22, 1, 'summary_by_extension');
INSERT INTO "organization_resource" VALUES(23, 1, 'missed_calls');
INSERT INTO "organization_resource" VALUES(24, 1, 'openfire');
INSERT INTO "organization_resource" VALUES(30, 1, 'calendar');
INSERT INTO "organization_resource" VALUES(31, 1, 'address_book');
INSERT INTO "organization_resource" VALUES(33, 1, 'sec_firewall');
INSERT INTO "organization_resource" VALUES(34, 1, 'sec_rules');
INSERT INTO "organization_resource" VALUES(35, 1, 'sec_ports');
INSERT INTO "organization_resource" VALUES(96, 1, 'sec_portknock_if');
INSERT INTO "organization_resource" VALUES(97, 1, 'sec_portknock_users');
INSERT INTO "organization_resource" VALUES(36, 1, 'sec_accessaudit');
INSERT INTO "organization_resource" VALUES(37, 1, 'sec_weak_keys');
INSERT INTO "organization_resource" VALUES(38, 1, 'sec_advanced_settings');
INSERT INTO "organization_resource" VALUES(40, 1, 'email_accounts');
INSERT INTO "organization_resource" VALUES(41, 1, 'email_relay');
INSERT INTO "organization_resource" VALUES(42, 1, 'antispam');
INSERT INTO "organization_resource" VALUES(43, 1, 'remote_smtp');
INSERT INTO "organization_resource" VALUES(44, 1, 'email_list');
INSERT INTO "organization_resource" VALUES(45, 1, 'email_stats');
INSERT INTO "organization_resource" VALUES(46, 1, 'vacations');
INSERT INTO "organization_resource" VALUES(51, 1, 'virtual_fax');
INSERT INTO "organization_resource" VALUES(52, 1, 'faxlist');
INSERT INTO "organization_resource" VALUES(53, 1, 'sendfax');
INSERT INTO "organization_resource" VALUES(54, 1, 'faxmaster');
INSERT INTO "organization_resource" VALUES(55, 1, 'faxclients');
INSERT INTO "organization_resource" VALUES(56, 1, 'faxviewer');
INSERT INTO "organization_resource" VALUES(58, 1, 'sysdash');
INSERT INTO "organization_resource" VALUES(59, 1, 'dashboard');
INSERT INTO "organization_resource" VALUES(60, 1, 'applet_admin');
INSERT INTO "organization_resource" VALUES(61, 1, 'network');
INSERT INTO "organization_resource" VALUES(62, 1, 'network_parameters');
INSERT INTO "organization_resource" VALUES(63, 1, 'dhcp_server');
INSERT INTO "organization_resource" VALUES(64, 1, 'dhcp_clientlist');
INSERT INTO "organization_resource" VALUES(65, 1, 'dhcp_by_mac');
INSERT INTO "organization_resource" VALUES(66, 1, 'shutdown');
INSERT INTO "organization_resource" VALUES(67, 1, 'hardware_detector');
INSERT INTO "organization_resource" VALUES(68, 1, 'updates');
INSERT INTO "organization_resource" VALUES(69, 1, 'packages');
INSERT INTO "organization_resource" VALUES(70, 1, 'repositories');
INSERT INTO "organization_resource" VALUES(72, 1, 'time_config');
INSERT INTO "organization_resource" VALUES(73, 1, 'currency');
INSERT INTO "organization_resource" VALUES(74, 1, 'pbxadmin');
INSERT INTO "organization_resource" VALUES(75, 1, 'control_panel');
INSERT INTO "organization_resource" VALUES(76, 1, 'voicemail');
INSERT INTO "organization_resource" VALUES(77, 1, 'monitoring');
INSERT INTO "organization_resource" VALUES(78, 1, 'endpoint_configurator');
INSERT INTO "organization_resource" VALUES(79, 1, 'conference');
INSERT INTO "organization_resource" VALUES(80, 1, 'extensions_batch');
INSERT INTO "organization_resource" VALUES(81, 1, 'tools');
INSERT INTO "organization_resource" VALUES(82, 1, 'asterisk_cli');
INSERT INTO "organization_resource" VALUES(83, 1, 'file_editor');
INSERT INTO "organization_resource" VALUES(84, 1, 'text_to_wav');
INSERT INTO "organization_resource" VALUES(85, 1, 'festival');
INSERT INTO "organization_resource" VALUES(86, 1, 'extensions');
INSERT INTO "organization_resource" VALUES(87, 1, 'trunks');
INSERT INTO "organization_resource" VALUES(88, 1, 'outbound_route');
INSERT INTO "organization_resource" VALUES(89, 1, 'inbound_route');
INSERT INTO "organization_resource" VALUES(90, 1, 'ivr');
INSERT INTO "organization_resource" VALUES(91, 1, 'queues');
INSERT INTO "organization_resource" VALUES(92, 1, 'features_code');
INSERT INTO "organization_resource" VALUES(93, 1, 'general_settings');
INSERT INTO "organization_resource" VALUES(94, 1, 'did');
INSERT INTO "organization_resource" VALUES(95, 1, 'hardware_configuration');
INSERT INTO "organization_resource" VALUES(98, 1, 'ring_group');
INSERT INTO "organization_resource" VALUES(99, 1, 'musiconhold');
INSERT INTO "organization_resource" VALUES(100, 1,'time_group');
INSERT INTO "organization_resource" VALUES(101, 1,'time_conditions');
INSERT INTO "organization_resource" VALUES(102, 1,'recordings');
INSERT INTO "organization_resource" VALUES(103, 1,'general_settings_admin');

INSERT INTO "group_resource" VALUES(1, 0, 1);
INSERT INTO "group_resource" VALUES(2, 0, 2);
INSERT INTO "group_resource" VALUES(3, 0, 3);
INSERT INTO "group_resource" VALUES(5, 0, 5);
INSERT INTO "group_resource" VALUES(7, 0, 7);
INSERT INTO "group_resource" VALUES(8, 0, 8);
INSERT INTO "group_resource" VALUES(9, 0, 9);
INSERT INTO "group_resource" VALUES(12, 0, 12);
INSERT INTO "group_resource" VALUES(13, 0, 13);
INSERT INTO "group_resource" VALUES(14, 0, 14);
INSERT INTO "group_resource" VALUES(15, 0, 15);
INSERT INTO "group_resource" VALUES(16, 0, 16);
INSERT INTO "group_resource" VALUES(17, 0, 17);
INSERT INTO "group_resource" VALUES(18, 0, 18);
INSERT INTO "group_resource" VALUES(19, 0, 19);
INSERT INTO "group_resource" VALUES(20, 0, 20);
INSERT INTO "group_resource" VALUES(21, 0, 21);
INSERT INTO "group_resource" VALUES(22, 0, 22);
INSERT INTO "group_resource" VALUES(23, 0, 23);
INSERT INTO "group_resource" VALUES(33, 0, 33);
INSERT INTO "group_resource" VALUES(34, 0, 34);
INSERT INTO "group_resource" VALUES(35, 0, 35);
INSERT INTO "group_resource" VALUES(36, 0, 36);
INSERT INTO "group_resource" VALUES(37, 0, 37);
INSERT INTO "group_resource" VALUES(38, 0, 38);
INSERT INTO "group_resource" VALUES(40, 0, 40);
INSERT INTO "group_resource" VALUES(41, 0, 41);
INSERT INTO "group_resource" VALUES(42, 0, 42);
INSERT INTO "group_resource" VALUES(43, 0, 43);
INSERT INTO "group_resource" VALUES(51, 0, 51);
INSERT INTO "group_resource" VALUES(52, 0, 52);
INSERT INTO "group_resource" VALUES(54, 0, 54);
INSERT INTO "group_resource" VALUES(55, 0, 55);
INSERT INTO "group_resource" VALUES(56, 0, 56);
INSERT INTO "group_resource" VALUES(58, 0, 58);
INSERT INTO "group_resource" VALUES(59, 0, 59);
INSERT INTO "group_resource" VALUES(60, 0, 60);
INSERT INTO "group_resource" VALUES(61, 0, 61);
INSERT INTO "group_resource" VALUES(62, 0, 62);
INSERT INTO "group_resource" VALUES(63, 0, 63);
INSERT INTO "group_resource" VALUES(64, 0, 64);
INSERT INTO "group_resource" VALUES(65, 0, 65);
INSERT INTO "group_resource" VALUES(66, 0, 66);
INSERT INTO "group_resource" VALUES(67, 0, 67);
INSERT INTO "group_resource" VALUES(68, 0, 68);
INSERT INTO "group_resource" VALUES(69, 0, 69);
INSERT INTO "group_resource" VALUES(70, 0, 70);
INSERT INTO "group_resource" VALUES(72, 0, 72);
INSERT INTO "group_resource" VALUES(73, 0, 73);
INSERT INTO "group_resource" VALUES(74, 0, 74);
INSERT INTO "group_resource" VALUES(81, 0, 81);
INSERT INTO "group_resource" VALUES(82, 0, 82);
INSERT INTO "group_resource" VALUES(83, 0, 83);
INSERT INTO "group_resource" VALUES(84, 0, 84);
INSERT INTO "group_resource" VALUES(85, 0, 85);
INSERT INTO "group_resource" VALUES(86, 0, 86);
INSERT INTO "group_resource" VALUES(87, 0, 87);
INSERT INTO "group_resource" VALUES(88, 0, 88);
INSERT INTO "group_resource" VALUES(89, 0, 89);
INSERT INTO "group_resource" VALUES(90, 0, 90);
INSERT INTO "group_resource" VALUES(91, 0, 91);
INSERT INTO "group_resource" VALUES(94, 0, 94);
INSERT INTO "group_resource" VALUES(95, 0, 95);
INSERT INTO "group_resource" VALUES(96, 0, 96);
INSERT INTO "group_resource" VALUES(97, 0, 97);
INSERT INTO "group_resource" VALUES(98, 0, 98);
INSERT INTO "group_resource" VALUES(99, 0, 99);
INSERT INTO "group_resource" VALUES(100, 0, 100);
INSERT INTO "group_resource" VALUES(238, 0, 101);
INSERT INTO "group_resource" VALUES(239, 0, 102);
INSERT INTO "group_resource" VALUES(240, 0, 103);

INSERT INTO "group_resource" VALUES(101, 1, 1);
INSERT INTO "group_resource" VALUES(102, 1, 2);
INSERT INTO "group_resource" VALUES(103, 1, 3);
INSERT INTO "group_resource" VALUES(104, 1, 4);
INSERT INTO "group_resource" VALUES(106, 1, 6);
INSERT INTO "group_resource" VALUES(107, 1, 7);
INSERT INTO "group_resource" VALUES(108, 1, 8);
INSERT INTO "group_resource" VALUES(109, 1, 9);
INSERT INTO "group_resource" VALUES(111, 1, 11);
INSERT INTO "group_resource" VALUES(113, 1, 13);
INSERT INTO "group_resource" VALUES(115, 1, 15);
INSERT INTO "group_resource" VALUES(116, 1, 16);
INSERT INTO "group_resource" VALUES(117, 1, 17);
INSERT INTO "group_resource" VALUES(118, 1, 18);
INSERT INTO "group_resource" VALUES(119, 1, 19);
INSERT INTO "group_resource" VALUES(121, 1, 21);
INSERT INTO "group_resource" VALUES(122, 1, 22);
INSERT INTO "group_resource" VALUES(123, 1, 23);
INSERT INTO "group_resource" VALUES(124, 1, 24);
INSERT INTO "group_resource" VALUES(130, 1, 30);
INSERT INTO "group_resource" VALUES(131, 1, 31);
INSERT INTO "group_resource" VALUES(136, 1, 36);
INSERT INTO "group_resource" VALUES(137, 1, 37);
INSERT INTO "group_resource" VALUES(140, 1, 40);
INSERT INTO "group_resource" VALUES(144, 1, 44);
INSERT INTO "group_resource" VALUES(145, 1, 45);
INSERT INTO "group_resource" VALUES(146, 1, 46);
INSERT INTO "group_resource" VALUES(151, 1, 51);
INSERT INTO "group_resource" VALUES(152, 1, 52);
INSERT INTO "group_resource" VALUES(153, 1, 53);
INSERT INTO "group_resource" VALUES(156, 1, 56);
INSERT INTO "group_resource" VALUES(173, 1, 73);
INSERT INTO "group_resource" VALUES(174, 1, 74);
INSERT INTO "group_resource" VALUES(175, 1, 75);
INSERT INTO "group_resource" VALUES(176, 1, 76);
INSERT INTO "group_resource" VALUES(177, 1, 77);
INSERT INTO "group_resource" VALUES(178, 1, 78);
INSERT INTO "group_resource" VALUES(179, 1, 79);
INSERT INTO "group_resource" VALUES(180, 1, 80);
INSERT INTO "group_resource" VALUES(181, 1, 81);
INSERT INTO "group_resource" VALUES(184, 1, 84);
INSERT INTO "group_resource" VALUES(186, 1, 86);
INSERT INTO "group_resource" VALUES(188, 1, 88);
INSERT INTO "group_resource" VALUES(189, 1, 89);
INSERT INTO "group_resource" VALUES(190, 1, 90);
INSERT INTO "group_resource" VALUES(191, 1, 91);
INSERT INTO "group_resource" VALUES(192, 1, 92);
INSERT INTO "group_resource" VALUES(193, 1, 93);
INSERT INTO "group_resource" VALUES(194, 1, 98);
INSERT INTO "group_resource" VALUES(195, 1, 99);
INSERT INTO "group_resource" VALUES(196, 1, 100);
INSERT INTO "group_resource" VALUES(197, 1, 101);
INSERT INTO "group_resource" VALUES(198, 1, 102);

INSERT INTO "group_resource" VALUES(201, 2, 1);
INSERT INTO "group_resource" VALUES(202, 2, 3);
INSERT INTO "group_resource" VALUES(204, 2, 11);
INSERT INTO "group_resource" VALUES(205, 2, 13);
INSERT INTO "group_resource" VALUES(206, 2, 18);
INSERT INTO "group_resource" VALUES(207, 2, 19);
INSERT INTO "group_resource" VALUES(208, 2, 30);
INSERT INTO "group_resource" VALUES(209, 2, 31);
INSERT INTO "group_resource" VALUES(210, 2, 46);
INSERT INTO "group_resource" VALUES(211, 2, 51);
INSERT INTO "group_resource" VALUES(212, 2, 52);
INSERT INTO "group_resource" VALUES(213, 2, 53);
INSERT INTO "group_resource" VALUES(214, 2, 56);
INSERT INTO "group_resource" VALUES(218, 2, 75);
INSERT INTO "group_resource" VALUES(219, 2, 76);
INSERT INTO "group_resource" VALUES(220, 2, 77);

INSERT INTO "group_resource" VALUES(222, 3, 1);
INSERT INTO "group_resource" VALUES(223, 3, 3);
INSERT INTO "group_resource" VALUES(225, 3, 11);
INSERT INTO "group_resource" VALUES(226, 3, 30);
INSERT INTO "group_resource" VALUES(227, 3, 31);
INSERT INTO "group_resource" VALUES(228, 3, 46);
INSERT INTO "group_resource" VALUES(229, 3, 51);
INSERT INTO "group_resource" VALUES(230, 3, 52);
INSERT INTO "group_resource" VALUES(231, 3, 53);
INSERT INTO "group_resource" VALUES(232, 3, 56);
INSERT INTO "group_resource" VALUES(236, 3, 76);
INSERT INTO "group_resource" VALUES(237, 3, 77);
