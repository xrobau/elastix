CREATE DATABASE IF NOT EXISTS elastix_address_book;

GRANT SELECT, INSERT, UPDATE, DELETE ON `elastix_address_book`.* TO 'asteriskuser'@'localhost';

USE elastix_address_book;

CREATE TABLE IF NOT EXISTS contact (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,   /* clave primaria */

    /* ID en acl.db del usuario ACL de Elastix que es responsable de este contacto */
    iduser  INT UNSIGNED NOT NULL,

    /* Bandera que indica si el contacto es público (1) o privado (0) - reemplaza a interno/externo */
    visibility  BOOLEAN NOT NULL DEFAULT 0,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Números telefónicos asociados al contacto */
CREATE TABLE IF NOT EXISTS contact_phone_number (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,   /* clave primaria */
    id_contact      INT UNSIGNED NOT NULL,                  /* relación al contacto */
    phone_number    VARCHAR(64) NOT NULL,                   /* el número en cuestión */
    extension       VARCHAR(32),                            /* extensión opcional a marcar luego de teléfono */

    /* rol asociado para el teléfono */
    phone_role      ENUM('work', 'home', 'mobile', 'fax') NOT NULL DEFAULT 'work',

    PRIMARY KEY (id),
    FOREIGN KEY (id_contact) REFERENCES contact(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Correos asociados al contacto */
CREATE TABLE IF NOT EXISTS contact_email (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,   /* clave primaria */
    id_contact      INT UNSIGNED NOT NULL,                  /* relación al contacto */

    email           VARCHAR(128) NOT NULL,

    /* rol asociado para el correo */
    email_role      ENUM('work', 'personal') NOT NULL DEFAULT 'personal',

    PRIMARY KEY (id),
    FOREIGN KEY (id_contact) REFERENCES contact(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Identidades de mensajería instantánea asociados al contacto */
CREATE TABLE IF NOT EXISTS contact_im_address (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,   /* clave primaria */
    id_contact      INT UNSIGNED NOT NULL,                  /* relación al contacto */

    im_address      VARCHAR(128) NOT NULL,

    /* rol asociado para el correo */
    im_role      ENUM('work', 'personal') NOT NULL DEFAULT 'personal',

    PRIMARY KEY (id),
    FOREIGN KEY (id_contact) REFERENCES contact(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Atributos adicionales asociados al contacto */
CREATE TABLE IF NOT EXISTS contact_attribute (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,   /* clave primaria */
    id_contact      INT UNSIGNED NOT NULL,                  /* relación al contacto */

    attribute_key   VARCHAR(32) NOT NULL,
    attribute_value TEXT NOT NULL,

    /* rol asociado para el correo */
    email_role      ENUM('work', 'personal') NOT NULL DEFAULT 'personal',

    PRIMARY KEY (id),
    FOREIGN KEY (id_contact) REFERENCES contact(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*
Atributos para compatibilidad con address_book.db:
name: nombre del contacto
last_name: apellidos del contacto

picture: nombre base del archivo de imagen en /var/www/address_book_images/

notes: notas variadas sobre el contacto

address: dirección del contacto
city: ciudad de la dirección
province: provincia/estado/departamento de dirección
(OJO: no hay país)

company: compañía del contacto
department: departamento dentro de compañía para contacto
company_contact: nombre del contacto responsable dentro de la compañía
contact_rol: cargo ejercido por contacto dentro de compañía
*/

