BEGIN TRANSACTION;

CREATE TABLE contact(
       id          integer primary key autoincrement,
       name        varchar(35),
       last_name   varchar(35),
       telefono    varchar(12),
       extension   varchar(7),
       email       varchar(30),
       iduser      int,
       picture 	   varchar(50),
       address 	   varchar(100),
       company 	   varchar(30),
       notes 	   varchar(200),
       status 	   varchar(30) DEFAULT 'isPrivate',
       last_update varchar(20));

CREATE TABLE events(
       id                    INTEGER PRIMARY KEY,
       uid                   INTEGER,
       startdate             DATE,
       enddate               DATE,
       starttime             DATETIME,
       eventtype             INTEGER,
       subject               VARCHAR(255),
       description           LONGTEXT,
       asterisk_call         VARCHAR(10),
       recording             VARCHAR(100),
       call_to               VARCHAR(20),
       notification          VARCHAR(50),
       emails_notification   LONGTEXT,
       endtime               DATETIME,
       each_repeat           INTEGER,
       days_repeat           VARCHAR(30),
       reminderTimer 	     VARCHAR(5),
       color 		     VARCHAR(10) DEFAULT '#3366CC',
       last_update           VARCHAR(20));

CREATE TABLE history(
   id             INTEGER PRIMARY KEY,
   id_register    INTEGER,
   id_user        INTEGER,
   status         VARCHAR(30),
   type           VARCHAR(40),
   timestamp      VARCHAR(20),
   action         VARCHAR(20),
   description    LONGTEXT);

CREATE TABLE queues(
  id          	 LONGTEXT PRIMARY KEY,
  type        	 VARCHAR(40),
  data        	 LONGTEXT,
  status      	 VARCHAR(20),
  user 	      	 varchar(20),
  response_data  LONGTEXT);

COMMIT;
