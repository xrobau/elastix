INSERT INTO `manufacturer` (`name`, `description`) VALUES ("Digium", "Digium DPMA Phones");

INSERT INTO `mac_prefix` (`mac_prefix`, `description`, `id_manufacturer`) VALUES
("00:19:15", "Digium Phones", (SELECT `id` FROM manufacturer WHERE `name` = "Digium")),
("00:0F:D3", "Digium Phones", (SELECT `id` FROM manufacturer WHERE `name` = "Digium"));

INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(2, 0, 1, 1, "D40", "D40", (SELECT `id` FROM manufacturer WHERE `name` = "Digium")),
(4, 0, 1, 1, "D50", "D50", (SELECT `id` FROM manufacturer WHERE `name` = "Digium")),
(6, 0, 1, 1, "D70", "D70", (SELECT `id` FROM manufacturer WHERE `name` = "Digium"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "2", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D40")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D40")),
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D50")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D50")),
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D70")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D70")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D40")),
("http_password", "789", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D40")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D50")),
("http_password", "789", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D50")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D70")),
("http_password", "789", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Digium" AND model.name = "D70"));
