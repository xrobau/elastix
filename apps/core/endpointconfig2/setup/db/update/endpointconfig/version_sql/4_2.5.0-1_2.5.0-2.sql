/* Creación del modelo Elastix LXP180 */
INSERT INTO `model` (`max_accounts`, `static_ip_supported`, `dynamic_ip_supported`, `static_prov_supported`, `name`, `description`, `id_manufacturer`) VALUES
(2, 1, 1, 1, "LXP180", "Elastix LXP180", (SELECT `id` FROM manufacturer WHERE `name` = "Elastix"));

INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("max_sip_accounts", "2", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP180")),
("max_iax2_accounts", "0", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP180")),
("http_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP180")),
("http_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP180"));

INSERT INTO `mac_prefix` (id_manufacturer, mac_prefix, description) VALUES
((SELECT `id` FROM manufacturer WHERE `name` = "Elastix"), "30:6C:BE", "Elastix");

