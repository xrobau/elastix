USE elxpbx; 

CREATE TABLE IF NOT EXISTS email_list
(
    id                       INTEGER AUTO_INCREMENT,
    organization_domain      VARCHAR(100) NOT NULL,
    listname                 VARCHAR(50),
    password                 VARCHAR(15),
    mailadmin                VARCHAR(150),
    PRIMARY KEY (id),
    UNIQUE INDEX listname (listname,organization_domain),
    INDEX organization_domain (organization_domain),
    FOREIGN KEY (organization_domain) REFERENCES organization(domain) ON DELETE CASCADE
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
    id                       integer not null AUTO_INCREMENT,
    date                     datetime,
    unix_time                integer,
    total                    integer,
    type                     integer,
    organization_domain varchar(100) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (organization_domain) REFERENCES organization(domain) ON DELETE CASCADE
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS email_relay(
    name varchar(150) NOT NULL,
    value varchar(150),
    PRIMARY KEY (name)
) ENGINE = INNODB;