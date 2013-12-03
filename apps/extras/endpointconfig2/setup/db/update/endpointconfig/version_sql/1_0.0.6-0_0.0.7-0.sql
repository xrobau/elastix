/* Creación del fabricante y modelo RCA IP150 */
INSERT INTO `manufacturer` (`name`, `description`) VALUES ("RCA", "RCA IP Phones");

INSERT INTO `mac_prefix` (`mac_prefix`, `description`, `id_manufacturer`) VALUES
("6C:A9:06", "RCA IP Phones", (SELECT `id` FROM manufacturer WHERE `name` = "RCA"));

INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(6, 1, 1, 1, "IP150", "IP150", (SELECT `id` FROM manufacturer WHERE `name` = "RCA"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP150")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP150")),
("ssh_username", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP150")),
("ssh_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP150")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP150")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP150"));

/* Ya se soporta provisionamiento estático para Aastra */
UPDATE model SET static_prov_supported = 1 WHERE id_manufacturer IN
    (SELECT `id` FROM manufacturer WHERE `name` = "Aastra");


/* Separar Polycom IP 320 y Polycom IP 330 entre sí */
INSERT INTO model
    SELECT NULL AS id, id_manufacturer, 'IP 330' AS name, 'IP 330' AS description, max_accounts, static_ip_supported, dynamic_ip_supported, static_prov_supported
    FROM model WHERE id_manufacturer IN
        (SELECT `id` FROM manufacturer WHERE `name` = "Polycom") AND name = 'IP 330/320';
UPDATE model SET name = "IP 320", description = "IP 320" WHERE id_manufacturer IN 
    (SELECT `id` FROM manufacturer WHERE `name` = "Polycom") AND name = 'IP 330/320';
INSERT INTO model_properties (id_model, property_key, property_value)
    SELECT 
        (   SELECT model.id FROM model, manufacturer 
            WHERE model.id_manufacturer = manufacturer.id 
            AND manufacturer.name = "Polycom" AND model.name = "IP 330")
        AS id_model,
        model_properties.property_key,
        model_properties.property_value
    FROM model_properties, model, manufacturer 
    WHERE model_properties.id_model = model.id AND model.id_manufacturer = manufacturer.id 
        AND manufacturer.name = "Polycom" AND model.name = "IP 320";
    
/* Renombrar modelos de Polycom para que coincidan con valores emitidos por Cisco Discovery Protocol */
UPDATE model SET name = CONCAT("SoundPoint ", name), description = CONCAT("SoundPoint ", description)
WHERE id_manufacturer IN (SELECT `id` FROM manufacturer WHERE `name` = "Polycom") AND name LIKE "IP %";


/* Nuevos modelos de Snom */
INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(4, 1, 1, 1, "710", "snom710-SIP", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "720", "snom720-SIP", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "760", "snom760-SIP", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "870", "snom870-SIP", (SELECT `id` FROM manufacturer WHERE `name` = "Snom"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "710")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "710")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "720")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "720")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "760")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "760")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "870")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "870"));
