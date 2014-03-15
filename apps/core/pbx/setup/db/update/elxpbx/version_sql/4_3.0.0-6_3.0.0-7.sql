/* This column is checked by kamailio authentication */
ALTER TABLE sip ADD COLUMN `sippasswd` VARCHAR(80) DEFAULT NULL;
ALTER TABLE sip ADD COLUMN `kamailioname` VARCHAR(80) DEFAULT NULL;

/* Add parameters for Kamailio integration */
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('bindaddr','127.0.0.1','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('bindport','5080','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('outboundproxy','127.0.0.1','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('outboundproxyport','5060','general');

/* The following view allows Kamailio to perform the mapping from mangled to demangled accounts */
CREATE VIEW uacreg AS
SELECT name AS l_uuid, kamailioname AS l_username, organization_domain AS l_domain,
    name AS r_username, '127.0.0.1:5080' AS r_domain, 'asterisk' AS realm,
    name AS auth_username, sippasswd AS auth_password, 'sip:127.0.0.1:5080' AS auth_proxy,
    90 AS expires
FROM sip;

/* The following view allows Kamailio to authenticate incoming REGISTERs */
CREATE VIEW subscriber AS
SELECT kamailioname AS username, organization_domain AS domain, sippasswd AS ha1, NULL AS ha1b
FROM sip;
