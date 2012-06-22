BEGIN TRANSACTION;

CREATE TABLE statistics(
        id          integer not null primary key,
        date        datetime,
        unix_time   integer,
        total       integer,
        type        integer
);

CREATE TABLE messages_vacations(
        id          INTEGER PRIMARY KEY,
        account     varchar(50),
        subject     varchar(50),
        body        text,
        vacation varchar(5) default 'no'
);

COMMIT;