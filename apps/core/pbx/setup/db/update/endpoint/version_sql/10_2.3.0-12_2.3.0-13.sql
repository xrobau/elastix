INSERT INTO VENDOR (name,description) VALUES ('Xorcom','Xorcom');
INSERT INTO MAC (id_vendor, value, description)values ((SELECT id FROM vendor WHERE name='Xorcom'), '64:24:00','Xorcom');
INSERT INTO MODEL (name,description,id_vendor,iax_support) VALUES ('XP0100P','XP0100P',(SELECT id FROM vendor WHERE name='Xorcom'),'0');
INSERT INTO MODEL (name,description,id_vendor,iax_support) VALUES ('XP0120P','XP0120P',(SELECT id FROM vendor WHERE name='Xorcom'),'0');

INSERT INTO vendor (name, description) VALUES ('Zultys', 'Zultys Technologies');
INSERT INTO model (name, description, id_vendor, iax_support) VALUES ('ZIP2x1', 'Zultys ZIP 2x1', (SELECT id FROM vendor WHERE name = 'Zultys'),'0');
INSERT INTO model (name, description, id_vendor, iax_support) VALUES ('ZIP2x2', 'Zultys ZIP 2x2', (SELECT id FROM vendor WHERE name = 'Zultys'),'0');
INSERT INTO mac (value, description, id_vendor) VALUES ('00:0B:EA', 'Zultys Technologies', (SELECT id FROM vendor WHERE name = 'Zultys'));
