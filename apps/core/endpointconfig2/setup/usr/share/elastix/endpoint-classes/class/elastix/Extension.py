# vim: set fileencoding=utf-8 :
# vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
# Codificación: UTF-8
# +----------------------------------------------------------------------+
# | Elastix version 2.0.4                                                |
# | http://www.elastix.com                                               |
# +----------------------------------------------------------------------+
# | Copyright (c) 2006 Palosanto Solutions S. A.                         |
# +----------------------------------------------------------------------+
# | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
# | Telfs. 2283-268, 2294-440, 2284-356                                  |
# | Guayaquil - Ecuador                                                  |
# | http://www.palosanto.com                                             |
# +----------------------------------------------------------------------+
# | The contents of this file are subject to the General Public License  |
# | (GPL) Version 2 (the "License"); you may not use this file except in |
# | compliance with the License. You may obtain a copy of the License at |
# | http://www.opensource.org/licenses/gpl-license.php                   |
# |                                                                      |
# | Software distributed under the License is distributed on an "AS IS"  |
# | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
# | the License for the specific language governing rights and           |
# | limitations under the License.                                       |
# +----------------------------------------------------------------------+
# | The Original Code is: Elastix Open Source.                           |
# | The Initial Developer of the Original Code is PaloSanto Solutions    |
# +----------------------------------------------------------------------+
# $Id: dialerd,v 1.2 2008/09/08 18:29:36 alex Exp $
import logging

class Extension:
    def __init__(self, dbconn, tech, exten):
        '''Initialize the extension information from the database.
        
        This implementation reads the configuration data from the FreePBX
        database 'asterisk' and assumes that account == extension.
        '''        
        self.registered = False
        self.tech = tech
        self.extension = exten
        self.secret = None
        self.description = None
        
        # This will be overwritten with value from database
        self.account = exten

        sth = dbconn.cursor()
        sth.execute('SELECT tech, description FROM asterisk.devices WHERE id = %s',
                    (exten,))
        row = sth.fetchone()
        if row == None:
            logging.error('Extension %s not found in freepbx database' % (exten,))
            self.tech = None
            self.account = None
            self.extension = None
        elif self.tech != row[0]:
            logging.warning('Extension %s requested as %s but shows up in freepbx as %s' % (exten, tech, row[0]))
        else:
            techmap = {'sip' : 'sip', 'iax2' : 'iax'}
            self.description = row[1]
            sth.execute(('SELECT keyword, data FROM asterisk.' + techmap[tech] + 
                         " WHERE id = %s AND (keyword = 'secret' OR keyword = 'account')"),
                        (exten,))
            for row in sth.fetchall():
                setattr(self, row[0], row[1])
        
        sth.execute(
            'SELECT property_key, property_value ' +
            'FROM endpoint_account_properties, endpoint_account ' +
            'WHERE endpoint_account_properties.id_endpoint_account = endpoint_account.id ' +
                'AND endpoint_account.account = %s',
            (exten,))
        for row in sth.fetchall():
            setattr(self, row[0], row[1])
