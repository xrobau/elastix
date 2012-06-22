BEGIN TRANSACTION;
CREATE TABLE item_box 
(
    id                   INTEGER PRIMARY KEY,
    id_area              INTEGER,
    id_device            INTEGER,
    FOREIGN KEY(id_area) REFERENCES area(id)
);
CREATE TABLE area 
(
    id              INTEGER PRIMARY KEY,
    name            varchar (80),
    height          INTEGER,
    width           INTEGER,
    description     varchar (100),
    no_column       INTEGER, 
    color varchar(7)
);
INSERT INTO "area" VALUES(1, 'Extension', 232, 608, 'Extensions', 3, '#DEE4FA');
INSERT INTO "area" VALUES(2, 'Area1', 100, 380, 'Area 1', 2, '#FCF9D2');
INSERT INTO "area" VALUES(3, 'Area2', 100, 381, 'Area 2', 2, '#D4DCDC');
INSERT INTO "area" VALUES(4, 'Area3', 100, 380, 'Area 3', 2, '#E0FFEF');
INSERT INTO "area" VALUES(5, 'Queues', 100, 380, 'Queues', 2, '#FED0CF');
INSERT INTO "area" VALUES(6, 'Trunks', 271, 608, 'DAHDI Trunks', 3, '#D0FFD0');
INSERT INTO "area" VALUES(7, 'TrunksSIP', 271, 608, 'SIP/IAX Trunks', 3, '#D0FFFF');
INSERT INTO "area" VALUES(8, 'Conferences', 100, 380, 'Conferences', 2, '#DACCCA');
INSERT INTO "area" VALUES(9, 'Parkinglots', 229, 380, 'Parking lots', 2, '#F5F5DC');
COMMIT;
