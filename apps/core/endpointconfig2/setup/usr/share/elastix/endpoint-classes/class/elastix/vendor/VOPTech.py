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
import re
from elastix.BaseEndpoint import BaseEndpoint
import elastix.vendor.Atcom

class Endpoint(elastix.vendor.Atcom.Endpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'VOPTech', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = True
        self._timeZone = 12

    def probeModel(self):
        '''Probe specific model of the VOPTech phone

        Attempt to fetch /currentstat.htm . Although this page does not contain
        the phone model, it shows the number of accounts and can give hints on
        which model is being probed.
        '''
        self._loadCustomCredentials()
        if self._http_username == None: self._http_username = 'admin'
        if self._http_password == None: self._http_password = 'admin'
        htmlres = self._fetchAtcomAuthenticatedPage(('/currentstat.htm',))
        if htmlres != None:
            resource, htmlbody = htmlres

            sipline3 = ('SIP LINE 3' in htmlbody)
            iax = ('IAX' in htmlbody)
            if iax and sipline3:
                self._saveModel('VI2008')
            elif iax and not sipline3:
                self._saveModel('VI2007')
            else:
                self._saveModel('VI2006')

    def updateLocalConfig(self):
        '''Configuration for VOPTech endpoints

        This phone is essentially a rebranded Atcom AT530+ with slightly
        different configuration directives. Apart from the template, the
        network-level interaction is identical to the AT530.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        configVersion = self._fetchOldConfigVersion()
        if configVersion == None: return False
        t = str(int(''.join(configVersion.split('.'))) + 1)
        configVersion = t[:1] + '.' + t[1:]
        logging.info('Endpoint %s@%s new config version is %s' %
            (self._vendorname, self._ip, configVersion))

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').lower()) + '.cfg'
        self._writeAtcom530Template(configVersion, sConfigFile, 'VOPTech_local.tpl')

        # Force download of new configuration followed by reboot
        if self._transferConfig2Phone(sConfigFile):
            self._unregister()
            self._setConfigured()
            return True
        else:
            return False
