ALTER TABLE provider ADD COLUMN orden integer;

UPDATE provider SET orden = 1 WHERE id=1;
UPDATE provider SET orden = 2 WHERE id=2;
UPDATE provider SET orden = 3 WHERE id=9;
UPDATE provider SET orden = 4 WHERE id=6;
UPDATE provider SET orden = 5 WHERE id=7;
UPDATE provider SET orden = 6 WHERE id=4; 
UPDATE provider SET orden = 7 WHERE id=3; 
UPDATE provider SET orden = 8 WHERE id=8;