INSERT INTO `mac_prefix` (id_manufacturer, mac_prefix, description) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "Fanvil"), "0C:38:3E", "Fanvil");

/* Creación del modelo Fanvil X3/X3P */
INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(2, 1, 1, 1, "X3", "X3/X3P", (SELECT `id` FROM manufacturer WHERE `name` = "Fanvil"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "2", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3"));

