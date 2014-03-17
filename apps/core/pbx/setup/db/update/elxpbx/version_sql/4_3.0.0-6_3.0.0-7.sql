/* This column is checked by kamailio authentication */
ALTER TABLE sip ADD COLUMN `sippasswd` VARCHAR(80) DEFAULT NULL;
ALTER TABLE sip ADD COLUMN `kamailioname` VARCHAR(80) DEFAULT NULL;

/* Add parameters for Kamailio integration */
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('bindaddr','127.0.0.1','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('bindport','5080','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('outboundproxy','127.0.0.1','general');
INSERT INTO sip_general (property_name,property_val,cathegory) VALUES ('outboundproxyport','5060','general');

/* This table is for announcement module */
CREATE TABLE announcement (
    id                  INT(11)      NOT NULL AUTO_INCREMENT,
    description         VARCHAR(50)  DEFAULT NULL,
    recording_id        INT(11)      DEFAULT NULL,
    allow_skip          ENUM('yes','no')  DEFAULT NULL,
    goto                VARCHAR(50)  NOT NULL, 
    destination         VARCHAR(255) DEFAULT NULL,
    return_ivr          ENUM('yes','no') NOT NULL default 'no',
    noanswer            ENUM('yes','no') NOT NULL default 'no',
    repeat_msg          VARCHAR(2)   NOT NULL default '',
    organization_domain VARCHAR(100) NOT NULL,
    PRIMARY KEY  (id),
    FOREIGN KEY (organization_domain) REFERENCES organization(domain) ON DELETE CASCADE,
    INDEX organization_domain (organization_domain)
) ENGINE = INNODB;

/* The following view allows Kamailio to perform the mapping from mangled to demangled accounts */
CREATE VIEW uacreg AS
SELECT name AS l_uuid, kamailioname AS l_username, organization_domain AS l_domain,
    name AS r_username, '127.0.0.1:5080' AS r_domain, 'asterisk' AS realm,
    name AS auth_username, sippasswd AS auth_password, 'sip:127.0.0.1:5080' AS auth_proxy,
    90 AS expires
FROM sip;

/* The following table stores IPs and domains from which incoming calls from 
 * global SIP trunks should be accepted */
CREATE TABLE global_domains
(
    domain  VARCHAR(100) NOT NULL UNIQUE
);

/* The following view allows Kamailio to authenticate incoming REGISTERs */
CREATE VIEW subscriber AS
(SELECT kamailioname AS username, organization_domain AS domain, sippasswd AS ha1, NULL AS ha1b 
FROM sip
WHERE organization_domain <> '')
UNION
(SELECT kamailioname AS username, domain, sippasswd AS ha1, NULL AS ha1b
FROM sip, organization
WHERE sip.organization_domain = '' AND domain <> '')
UNION
(SELECT kamailioname AS username, domain, sippasswd AS ha1, NULL AS ha1b
FROM sip, global_domains
WHERE sip.organization_domain = '');
