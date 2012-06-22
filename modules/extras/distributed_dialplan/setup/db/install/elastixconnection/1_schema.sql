BEGIN TRANSACTION;

CREATE TABLE general(
       id           INTEGER PRIMARY KEY,
       organization varchar(100),
       locality     varchar(100),
       stateprov    varchar(150),
       country      varchar(150),
       email        varchar(150),
       phone        varchar(20),
       department   varchar(255),
       certificate  varchar(255)
);

CREATE TABLE parameter(
       id           INTEGER PRIMARY KEY,
       name         varchar(255),
       value        varchar(255),
       id_peer      integer,
       foreign key(id_peer) references peer(id)
);

CREATE TABLE peer(
       id           INTEGER PRIMARY KEY,
       mac          varchar(255),
       model        varchar(255),
       host         varchar(255),
       inkey        varchar(255),
       outkey       varchar(255),
       status       varchar(25)  default 'connect',
       key          text,
       comment      text,
       company      varchar(250),
       his_status   varchar(255) default 'disconnected'
);

COMMIT;
