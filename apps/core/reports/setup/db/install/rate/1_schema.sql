BEGIN TRANSACTION;
CREATE TABLE rate (
       id               INTEGER PRIMARY KEY, 
       name             varchar(200), 
       prefix           varchar(50), 
       rate             float, 
       rate_offset      float, 
       trunk            TEXT, 
       estado           VARCHAR(20) DEFAULT activo, 
       fecha_creacion   DATETIME DEFAULT '2005-01-01 10:00:00', 
       fecha_cierre     DATETIME, 
       hided_digits     INTEGER DEFAULT 0, 
       idParent         int DEFAULT 0
);
COMMIT;
