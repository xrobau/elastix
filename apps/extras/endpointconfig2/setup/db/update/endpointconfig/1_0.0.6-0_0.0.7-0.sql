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
