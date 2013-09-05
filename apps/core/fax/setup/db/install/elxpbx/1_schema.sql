use elxpbx;

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