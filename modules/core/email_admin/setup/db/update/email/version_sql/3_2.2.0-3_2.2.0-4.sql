BEGIN TRANSACTION;
ALTER TABLE messages_vacations ADD COLUMN ini_date date;
ALTER TABLE messages_vacations ADD COLUMN end_date date;
COMMIT;