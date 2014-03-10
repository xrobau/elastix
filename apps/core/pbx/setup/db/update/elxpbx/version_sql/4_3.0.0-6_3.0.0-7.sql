/* This column is checked by kamailio authentication */
ALTER TABLE sip ADD COLUMN `sippasswd` VARCHAR(80) DEFAULT NULL;
ALTER TABLE sip ADD COLUMN `kamailioname` VARCHAR(80) DEFAULT NULL;

/* Add parameters for Kamailio integration */
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('bindaddr','127.0.0.1','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('bindport','5080','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('outboundproxy','127.0.0.1','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('outboundproxyport','5060','general');
