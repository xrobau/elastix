INSERT INTO `mac_prefix` (id_manufacturer, mac_prefix, description) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "Fanvil"), "0C:38:3E", "Fanvil");

/* Creación del modelo Fanvil X3/X3P */
INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(2, 1, 1, 1, "X3", "X3/X3P", (SELECT `id` FROM manufacturer WHERE `name` = "Fanvil")),
(6, 1, 1, 1, "X5", "X5/X5P", (SELECT `id` FROM manufacturer WHERE `name` = "Fanvil")),
(6, 1, 1, 1, "C400", "C400/C400P", (SELECT `id` FROM manufacturer WHERE `name` = "Fanvil")),
(6, 1, 1, 1, "C600", "C600/C600P", (SELECT `id` FROM manufacturer WHERE `name` = "Fanvil")),
(6, 1, 1, 1, "D900", "D900", (SELECT `id` FROM manufacturer WHERE `name` = "Fanvil"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "2", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X3")),
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X5")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X5")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X5")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "X5")),
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C400")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C400")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C400")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C400")),
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C600")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C600")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C600")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "C600")),
("max_sip_accounts", "6", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "D900")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "D900")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "D900")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Fanvil" AND model.name = "D900"));

/* Creación del fabricante Cisco SPA */
INSERT INTO `manufacturer` (`name`, `description`) VALUES ('CiscoSPA', 'CiscoSPA');

INSERT INTO `mac_prefix` (`id_manufacturer`, `mac_prefix`, `description`) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '1C:DF:0F', 'Cisco SPA502G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '58:BF:EA', 'Cisco SPA502G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '00:02:FD', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'C8:9C:1D', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '00:26:99', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '64:9E:F3', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '70:81:05', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '58:0A:20', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'CC:EF:48', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'E0:2F:6D', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '30:F7:0D', 'Cisco SPA525G2'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'B0:FA:EB', 'Cisco SPA525G2'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '1C:1D:86', 'Cisco SPA525G2'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '4C:00:82', 'Cisco SPA525G2'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'A4:93:4C', 'Cisco SPA514G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '3C:CE:73', 'Cisco SPA301G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '3C:CE:73', 'Cisco SPA303G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'E8:B7:48', 'Cisco SPA504G'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), '54:75:D0', 'Cisco SPA301G');

/* Modelos asociados a CiscoSPA */
INSERT INTO `model` (`id_manufacturer`, `name`, `description`, `max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA501G', 'SPA501G', '4', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA502G', 'SPA502G', '1', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA504G', 'SPA504G', '4', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA508G', 'SPA508G', '8', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA509G', 'SPA509G', '12', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA512G', 'SPA512G', '1', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA514G', 'SPA514G', '4', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA525G2', 'SPA525G2', '5', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA301G', 'SPA301G', '1', '1', '1', '0'),
((SELECT `id` FROM manufacturer WHERE `name` = "CiscoSPA"), 'SPA303G', 'SPA303G', '3', '1', '1', '0');

/* Propiedades de los modelos Cisco SPA */
INSERT INTO `model_properties` (`id_model`, `property_key`, `property_value`) VALUES
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA501G"), 'max_sip_accounts', '4'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA501G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA501G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA501G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA502G"), 'max_sip_accounts', '1'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA502G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA502G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA502G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA504G"), 'max_sip_accounts', '4'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA504G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA504G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA504G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA508G"), 'max_sip_accounts', '8'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA508G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA508G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA508G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA509G"), 'max_sip_accounts', '12'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA509G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA509G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA509G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA512G"), 'max_sip_accounts', '1'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA512G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA512G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA512G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA514G"), 'max_sip_accounts', '4'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA514G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA514G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA514G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA525G2"), 'max_sip_accounts', '5'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA525G2"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA525G2"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA525G2"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA301G"), 'max_sip_accounts', '1'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA301G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA301G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA301G"), 'http_password', '22222'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA303G"), 'max_sip_accounts', '3'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA303G"), 'max_iax2_accounts', '0'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA303G"), 'http_username', 'admin'),
((SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "CiscoSPA" AND model.name = "SPA303G"), 'http_password', '22222');
