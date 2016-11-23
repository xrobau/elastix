INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("ssh_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP200")),
("ssh_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP200"));

INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(12, 1, 1, 1, "MeetingPoint", "snom MeetingPoint", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(4, 1, 1, 1, "PA1", "snom PA1", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(4, 1, 1, 1, "D305", "snom D305", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(4, 1, 1, 1, "D315", "snom D315", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "D345", "snom D345", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "D375", "snom D375", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(4, 1, 1, 1, "D715", "snom D715", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "D725", "snom D725", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "D745", "snom D745", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(12, 1, 1, 1, "D765", "snom D765", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(20, 1, 1, 1, "M300", "snom M300 base station", (SELECT `id` FROM manufacturer WHERE `name` = "Snom")),
(30, 1, 1, 1, "M700", "snom M700 base station", (SELECT `id` FROM manufacturer WHERE `name` = "Snom"))
;

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "MeetingPoint")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "MeetingPoint")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D765")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D765")),
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "PA1")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "PA1")),
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D715")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D715")),
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D315")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D315")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D725")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D725")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D745")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D745")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D375")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D375")),
("max_sip_accounts", "12", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D345")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D345")),
("max_sip_accounts", "4", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D305")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "D305")),
("max_sip_accounts", "20", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "M300")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "M300")),
("max_sip_accounts", "30", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "M700")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Snom" AND model.name = "M700"))
;
