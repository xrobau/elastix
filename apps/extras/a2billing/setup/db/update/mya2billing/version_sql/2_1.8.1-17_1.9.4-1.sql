
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 *
 * @copyright   Copyright (C) 2004-2009 - Star2billing S.L.
 * @author      Belaid Arezqui <areski@gmail.com>
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @package     A2Billing
 *
 * Software License Agreement (GNU Affero General Public License)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
**/

USE mya2billing;


/* actualizando desde 1.8.1 - 1.8.2 */

ALTER TABLE cc_did_destination ADD COLUMN validated integer DEFAULT 0;
UPDATE cc_did_destination SET validated=1;
ALTER TABLE cc_did ADD aleg_carrier_connect_charge DECIMAL( 15, 5 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD aleg_carrier_cost_min DECIMAL( 15, 5 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD aleg_retail_connect_charge DECIMAL( 15, 5 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD aleg_retail_cost_min DECIMAL( 15, 5 ) NOT NULL DEFAULT '0';
UPDATE cc_version SET version = '1.8.2';




/* actualizando desde 1.8.2 - 1.8.3 */

UPDATE cc_version SET version = '1.8.3';




/* actualizando desde 1.8.3 - 1.8.4 */

INSERT INTO cc_config ( config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
VALUES( 'Callback CID Prompt Confirm PhoneNumber ', 'cid_prompt_callback_confirm_phonenumber', '0', 'Set to yes, a menu will be play to let the user confirm his phone number', 1, 'yes,no', 'agi-conf1');
UPDATE cc_version SET version = '1.8.4';



/* actualizando desde 1.8.4 - 1.8.5 */

UPDATE cc_version SET version = '1.8.5';


/* actualizando desde 1.8.5 - 1.8.6 */

ALTER TABLE cc_did ADD aleg_carrier_initblock int(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD aleg_carrier_increment int(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD aleg_retail_initblock int(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD aleg_retail_increment int(11) NOT NULL DEFAULT '0';
UPDATE cc_version SET version = '1.8.6';


/* actualizando desde 1.8.6 - 1.9.0 */

ALTER TABLE  cc_call CHANGE  calledstation  calledstation VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
UPDATE cc_version SET version = '1.9.0';


/* actualizando desde 1.9.0 - 1.9.1 */

UPDATE cc_version SET version = '1.9.1';


/* actualizando desde 1.9.1 - 1.9.2 */

UPDATE cc_version SET version = '1.9.2';


/* actualizando desde 1.9.2 - 1.9.3 */

UPDATE cc_version SET version = '1.9.3';


/* actualizando desde 1.9.3 - 1.9.4 */

UPDATE cc_version SET version = '1.9.4';