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
import elastix.BaseEndpoint
from elastix.BaseEndpoint import BaseEndpoint
from eventlet.green import os
import errno

# WARNING: this manufacturer has not been tested due to lack of samples!
class Endpoint(BaseEndpoint):
    _global_serverip = None
    
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Zultys', amipool, dbpool, sServerIP, sIP, mac)
        if Endpoint._global_serverip == None:
            Endpoint._global_serverip = sServerIP
        elif Endpoint._global_serverip != sServerIP:
            logging.warning('global server IP is %s but endpoint %s requires ' + 
                'server IP %s - this endpoint might not work correctly.' %
                (Endpoint._global_serverip, sIP, sServerIP))

    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Configuration for Zultys endpoints (global):
        
        Apparently, each supported model fetches a common file named 
        {$MODEL}_common.cfg in the base TFTP directory. Additionally, each
        supported phone model gets its own configuration directory.
        '''
        for sModel in ('ZIP2x1', 'ZIP2x2'):
            vars = {
                'server_ip' :   Endpoint._global_serverip,
                'model'     :   sModel,
            }
            sConfigFile = sModel + '_common.cfg'
            sConfigPath = elastix.BaseEndpoint.TFTP_DIR + '/' + sConfigFile
            try:
                BaseEndpoint._writeTemplate('Zultys_global_cfg.tpl', vars, sConfigPath)
            except IOError, e:
                logging.error('Failed to write %s for Zultys - %s' % (sConfigFile, str(e),))
                return False
            
            try:
                os.makedirs(elastix.BaseEndpoint.TFTP_DIR + '/' + sModel, 0777)
            except OSError, e:
                # swallow "already exists", re-raise anything else
                if e.errno != errno.EEXIST: 
                    logging.error('Failed to create directory for Zultys - %s' % (str(e),))
                    return False
        return True

    def updateLocalConfig(self):
        '''Configuration for Zultys endpoints (local):
        
        A file XXXXXXXXXXXX.cfg must be created under the appropriate directory
        according to the phone model. Here XXXXXXXXXXXX is replaced by the
        UPPERCASE MAC address of the phone.
        
        Phone auto-reboot is not supported for this vendor.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        mac = self._mac.replace(':', '').upper()
        
        vars = self._prepareVarList()
        sConfigFile = mac + '.cfg'
        sConfigPath = self._tftpdir + '/' + self._model + '/' + sConfigFile
        try:
            self._writeTemplate('Zultys_local_cfg.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        self._unregister()
        self._setConfigured()
        return True
        