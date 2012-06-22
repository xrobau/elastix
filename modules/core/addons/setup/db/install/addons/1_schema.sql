BEGIN TRANSACTION;
CREATE TABLE addons(
       id           integer     primary key,
       name         varchar(20),
       name_rpm     varchar(100),
       version      varchar(20),
       release      varchar(20),
       developed_by varchar(100),
       update_st    int(1) default 0
);
CREATE TABLE addons_cache(
       name_rpm         varchar(20),
       status           int,
       observation      varchar(100)
);
CREATE TABLE action_tmp (
       name_rpm          varchar(20),
       action_rpm        varchar(20),
       data_exp          varchar(100),
       user              varchar(20)
);
COMMIT;
