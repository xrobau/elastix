INSERT INTO `model_properties` (`property_key`, `property_value`, `id_model`) VALUES
("ssh_username", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP200")),
("ssh_password", "admin", (SELECT model.id FROM manufacturer, model WHERE manufacturer.id = model.id_manufacturer AND manufacturer.name = "Elastix" AND model.name = "LXP200"));

