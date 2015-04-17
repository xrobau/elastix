/* Creación del modelo Grandstream GXP2130,GXP2140 */
INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(3, 1, 1, 1, "GXP2130", "GXP2130", (SELECT `id` FROM manufacturer WHERE `name` = "Grandstream")),
(4, 1, 1, 1, "GXP2140", "GXP2140", (SELECT `id` FROM manufacturer WHERE `name` = "Grandstream"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "3", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2130")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2130")),
("ssh_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2130")),
("ssh_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2130")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2130")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2130")),
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2140")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2140")),
("ssh_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2140")),
("ssh_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2140")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2140")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Grandstream" AND model.name = "GXP2140"));
