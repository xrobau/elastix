/* Creación del modelo Grandstream GXP2160 */
INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(6, 1, 1, 1, "GXP2160", "GXP2160", (SELECT `id` FROM manufacturer WHERE `name` = "Grandstream"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2160")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2160")),
("ssh_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2160")),
("ssh_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2160")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2160")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2160"));

/* Creación del fabricante Hanlong */
INSERT INTO `manufacturer` (name, description) VALUES ("Hanlong", "Hanlong Technology");

INSERT INTO `mac_prefix` (id_manufacturer, mac_prefix, description) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "Hanlong"), "00:1F:C1", "Hanlong");

INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(4, 1, 1, 1, "UC862", "UC862", (SELECT `id` FROM manufacturer WHERE `name` = "Hanlong"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Hanlong" AND model.name = "UC862")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Hanlong" AND model.name = "UC862")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Hanlong" AND model.name = "UC862")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Hanlong" AND model.name = "UC862"));

/* Nuevos MAC para Fanvil */
INSERT INTO `mac_prefix` (id_manufacturer, mac_prefix, description) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "Fanvil"), "00:38:3E", "Fanvil");
