BEGIN TRANSACTION;
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
       days_repeat           VARCHAR(30)
);
COMMIT;
