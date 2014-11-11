INSERT INTO `mac_prefix` (`mac_prefix`, `description`, `id_manufacturer`) VALUES
("74:65:D1", "RCA IP160s", (SELECT `id` FROM manufacturer WHERE `name` = "RCA"));

INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(2, 1, 1, 1, "IP115", "IP115", (SELECT `id` FROM manufacturer WHERE `name` = "RCA")),
(3, 1, 1, 1, "IP125", "IP125", (SELECT `id` FROM manufacturer WHERE `name` = "RCA")),
(8, 1, 1, 1, "IP160s", "IP160s", (SELECT `id` FROM manufacturer WHERE `name` = "RCA"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "2", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP115")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP115")),
("telnet_username", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP115")),
("telnet_password", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP115")),
("http_username", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP115")),
("http_password", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP115")),
("max_sip_accounts", "3", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP125")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP125")),
("telnet_username", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP125")),
("telnet_password", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP125")),
("http_username", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP125")),
("http_password", "root", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP125")),
("max_sip_accounts", "8", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP160s")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP160s")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP160s")),
("http_password", "7227", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "RCA" AND model.name = "IP160s"));

