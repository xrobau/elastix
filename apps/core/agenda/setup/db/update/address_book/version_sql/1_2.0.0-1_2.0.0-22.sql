BEGIN TRANSACTION;
ALTER TABLE contact ADD COLUMN picture varchar(50);
ALTER TABLE contact ADD COLUMN address varchar(100);
ALTER TABLE contact ADD COLUMN company varchar(30);
ALTER TABLE contact ADD COLUMN notes varchar(200);
ALTER TABLE contact ADD COLUMN status varchar(30) DEFAULT 'isPrivate';
COMMIT;
