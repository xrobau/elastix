INSERT INTO VENDOR (name,description) VALUES ('Xorcom','Xorcom');
INSERT INTO MAC (id_vendor, value, description)values ((SELECT id FROM vendor WHERE name='Xorcom'), '64:24:00','Xorcom');
INSERT INTO MODEL (name,description,id_vendor,iax_support) VALUES ('XP0100P','XP0100P',(SELECT id FROM vendor WHERE name='Xorcom'),'0');
INSERT INTO MODEL (name,description,id_vendor,iax_support) VALUES ('XP0120P','XP0120P',(SELECT id FROM vendor WHERE name='Xorcom'),'0');

