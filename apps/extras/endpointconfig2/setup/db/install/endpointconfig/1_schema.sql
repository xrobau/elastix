CREATE DATABASE IF NOT EXISTS `endpointconfig`  DEFAULT CHARACTER SET 'UTF8';
USE `endpointconfig`;

CREATE TABLE IF NOT EXISTS `configlog` (
    `lastlog` TEXT
) ENGINE=InnoDB;

/* Lista de los fabricantes conocidos de teléfonos. El nombre del vendedor se
 * usa para elegir la clase que implementa el configurador del endpoint. */
CREATE TABLE IF NOT EXISTS `manufacturer`
(
    `id`            INT UNSIGNED    NOT NULL    auto_increment,
    `name`          VARCHAR(128)    NOT NULL,
    `description`   TEXT            NOT NULL    DEFAULT '',
    
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

/* Cada fabricante de teléfono tiene un conjunto de prefijos MACs de red 
 * asignados. Durante el escaneo de la red, estos prefijos permiten identificar
 * el vendedor que fabricó el teléfono. Los prefijos se almacenan en la forma
 * AA:BB:CC con letras hexadecimales en mayúsculas.
 */
CREATE TABLE IF NOT EXISTS `mac_prefix`
(
    `id`                INT UNSIGNED    NOT NULL    auto_increment,
    `id_manufacturer`   INT UNSIGNED    NOT NULL,
    `mac_prefix`        CHAR(8)         NOT NULL,   /* 00:08:5D Aastra */
    `description`       TEXT            NOT NULL    DEFAULT '',
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_manufacturer`) REFERENCES `manufacturer` (`id`)
) ENGINE=InnoDB;

/* Lista de los modelos de teléfono fabricados por cada fabricante. No existe
 * una correspondencia entre MACs y modelos individuales, así que no es posible
 * asociar una MAC a un modelo en particular. El nombre del modelo es parte de
 * la información necesaria para elegir un subalgoritmo de configuración a la
 * hora de programar las cuentas telefónicas en el endpoint.  
 */
CREATE TABLE IF NOT EXISTS `model`
(
    `id`                INT UNSIGNED    NOT NULL    auto_increment,
    `id_manufacturer`   INT UNSIGNED    NOT NULL,
    `name`              VARCHAR(128)    NOT NULL,
    `description`       TEXT            NOT NULL    DEFAULT '',
    
    /* Número máximo de cuentas telefónicas que se pueden asociar al endpoint */
    `max_accounts`      INT UNSIGNED    NOT NULL    DEFAULT 1,
    
    /* Banderas de soporte de red */
    `static_ip_supported`   BOOLEAN     NOT NULL    DEFAULT FALSE,
    `dynamic_ip_supported`  BOOLEAN     NOT NULL    DEFAULT TRUE,
    `static_prov_supported` BOOLEAN     NOT NULL    DEFAULT FALSE,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_manufacturer`) REFERENCES `manufacturer` (`id`)
) ENGINE=InnoDB;

/* Cada modelo de teléfono tiene atributos particulares que pueden influir en
 * la manera en que se configura el teléfono. Los atributos que se conocen para
 * los teléfonos actuales son:
 * 
 *  max_sip_accounts:   Si presente y > 0, máximo número de cuentas SIP
 *  max_iax2_accounts:  Si presente y > 0, máximo número de cuentas IAX2
 *  http_username:      Si presente, usuario de administración por omisión (HTTP)
 *  http_password:      Si presente, clave de administración por omisión (HTTP)
 *  telnet_username:    Si presente, usuario de administración por omisión (Telnet)
 *  telnet_password:    Si presente, clave de administración por omisión (Telnet)
 *  ssh_username:       Si presente, usuario de administración por omisión (SSH)
 *  ssh_password:       Si presente, clave de administración por omisión (SSH)
 */
CREATE TABLE IF NOT EXISTS `model_properties`
(
    `id`                INT UNSIGNED    NOT NULL    auto_increment,
    `id_model`          INT UNSIGNED    NOT NULL,
    `property_key`      VARCHAR(128)    NOT NULL,
    `property_value`    TEXT            NOT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_model`) REFERENCES `model` (`id`)
) ENGINE=InnoDB;

/* Lista de endpoints detectados o ingresados a mano. */
CREATE TABLE IF NOT EXISTS `endpoint`
(
    `id`                INT UNSIGNED    NOT NULL    auto_increment,

    /* Cuando se escanea con nmap, el fabricante del teléfono puede 
     * identificarse automáticamente a partir de la MAC, pero el modelo del 
     * teléfono requiere selección manual. */
    `id_manufacturer`   INT UNSIGNED    NOT NULL,    
    `id_model`          INT UNSIGNED,   /* Requiere selección manual */
    
    `mac_address`       CHAR(17),
    `last_known_ipv4`   VARCHAR(15),   /* Dirección IPv4 de endpoint */
    
    `last_scanned`      DATETIME,   /* Fecha y hora de la última detección */
    `last_modified`     DATETIME,   /* Fecha y hora de la última modificación */
    `last_configured`   DATETIME,   /* Fecha y hora de la última reprogramación */
    
    `selected`          INT(1) UNSIGNED NOT NULL DEFAULT 0,   /* Bandera de selección para programa externo */
    `authtoken_sha1`    CHAR(40),
    
    PRIMARY KEY (`id`),
    KEY (`mac_address`),
    FOREIGN KEY (`id_manufacturer`) REFERENCES `manufacturer` (`id`),
    FOREIGN KEY (`id_model`) REFERENCES `model` (`id`)
) ENGINE=InnoDB;

/* Lista de las cuentas telefónicas asociadas a cada endpoint. Si el endpoint
 * tiene el concepto de cuenta por omisión, entonces se usará la cuenta con
 * la prioridad más baja como la cuenta por omisión. Si el endpoint debe de
 * elegir una bandera de tecnología por omisión, se usará la tecnología de la
 * cuenta por omisión. */
CREATE TABLE IF NOT EXISTS `endpoint_account`
(
    `id`                INT UNSIGNED    NOT NULL    auto_increment,
    `id_endpoint`       INT UNSIGNED    NOT NULL,
    `tech`              VARCHAR(10)     NOT NULL,   /* sip,iax2 */
    `account`           VARCHAR(50)     NOT NULL,   /* 1064 */
    `priority`          INT UNSIGNED    NOT NULL,
    
    /* El secret para conectarse se debe buscar en asterisk.{$tech} keyword='secret' */

    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_endpoint`) REFERENCES `endpoint` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB;

/* Lista de las propiedades asociadas a cada enpoint. Cada propiedad, si está
 * definida, reemplaza a la propiedad de igual nombre en model_properties. */
CREATE TABLE IF NOT EXISTS `endpoint_properties`
(
    `id`                INT UNSIGNED    NOT NULL    auto_increment,
    `id_endpoint`       INT UNSIGNED    NOT NULL,
    `property_key`      VARCHAR(128)    NOT NULL,
    `property_value`    TEXT            NOT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_endpoint`) REFERENCES `endpoint` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

/* Lista de las propiedades adicionales asociadas a cada cuenta. Esta tabla no 
 * está destinada a almacenar propiedades (como secret) que pueden leerse de la 
 * configuración telefónica. Aquí deben almacenarse propiedades que ayudan a
 * asociar cada cuenta al endpoint. El primer caso de uso es el valor de IPUI
 * que identifica al handset a asociar a cada cuenta para el teléfono Snom m9 */
CREATE TABLE IF NOT EXISTS `endpoint_account_properties`
(
    `id`                    INT UNSIGNED    NOT NULL    auto_increment,
    `id_endpoint_account`   INT UNSIGNED    NOT NULL,
    `property_key`      VARCHAR(128)    NOT NULL,
    `property_value`    TEXT            NOT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_endpoint_account`) REFERENCES `endpoint_account` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

GRANT SELECT, UPDATE, INSERT, DELETE ON `endpointconfig`.* to asteriskuser@localhost;
