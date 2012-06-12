BEGIN TRANSACTION;
CREATE TABLE contact (
       id          integer primary key autoincrement,
       name        varchar(35),
       last_name   varchar(35),
       telefono    varchar(12),
       extension   varchar(7),
       email       varchar(30),
       iduser      int
);
COMMIT;
