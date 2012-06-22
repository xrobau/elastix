BEGIN TRANSACTION;
CREATE TABLE SysLog
(
  syslogid  INTEGER      PRIMARY KEY ,
  logdate   timestamp    NOT NULL ,
  logtext   varchar(255) NOT NULL
);

CREATE TABLE info_fax_recvq
(
    id             INTEGER          PRIMARY KEY,
    pdf_file       varchar(255)     NOT NULL DEFAULT '',
    modemdev       varchar(255)     NOT NULL DEFAULT '',
    status         varchar(255)     NOT NULL DEFAULT '',
    commID         varchar(255)     NOT NULL DEFAULT '',
    errormsg       varchar(255)     NOT NULL DEFAULT '',
    company_name   varchar(255)     NOT NULL DEFAULT '',
    company_fax    varchar(255)     NOT NULL DEFAULT '',
    fax_destiny_id INTEGER          NOT NULL DEFAULT 0,
    date           timestamp        NOT NULL,
    type           varchar(3)       default 'in',
    faxpath        varchar(255)     default '',
    FOREIGN KEY    (fax_destiny_id) REFERENCES fax(id)
);

CREATE TABLE fax 
(
    clid_name      varchar(60), 
    clid_number    varchar(60), 
    date_creation  varchar(20), 
    dev_id         varchar(20), 
    email          varchar(120), 
    extension      varchar(20), 
    id             INTEGER        PRIMARY KEY, 
    name           varchar(60), 
    port           varchar(60), 
    secret         varchar(20), 
    country_code   varchar(20), 
    area_code      varchar(20)
);

CREATE TABLE configuration_fax_mail
(
  id         integer       primary key,
  remite     varchar(255)  NOT NULL,
  remitente  varchar(255)  NOT NULL,
  subject    varchar(255)  NOT NULL,
  content    varchar(255)
);

INSERT INTO "configuration_fax_mail" VALUES(1, 'elastix@example.com', 'Fax Elastix', 'Fax attached (ID: {NAME_PDF})', 'Fax sent from "{COMPANY_NAME_FROM}". The phone number is {COMPANY_NUMBER_FROM}.
This email has a fax attached with ID {NAME_PDF}.');

COMMIT;
