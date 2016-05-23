/* Creación del fabricante INCOM */
INSERT INTO `manufacturer` (`name`, `description`) VALUES ('INCOM', 'INCOM');

INSERT INTO `mac_prefix` (`id_manufacturer`, `mac_prefix`, `description`) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "INCOM"), '00:03:2A', 'INCOM ICW-1000');

/* Modelos asociados a INCOM */
INSERT INTO `model` (`id_manufacturer`, `name`, `description`, `max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "INCOM"), 'ICW-1000', 'ICW-1000', '1', '1', '1', '1');

/* Propiedades de los modelos INCOM */
INSERT INTO `model_properties` (`id_model`, `property_key`, `property_value`) VALUES
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "INCOM" AND model.name = "ICW-1000"), 'max_sip_accounts', '1'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "INCOM" AND model.name = "ICW-1000"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "INCOM" AND model.name = "ICW-1000"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "INCOM" AND model.name = "ICW-1000"), 'http_password', '000000');

