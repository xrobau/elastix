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
# | Other modifications made :                                           |
# | * Added code to convert GMT Offsite into format for Cisco SPA Phones |
# | * added timezone as a parameter that can be set in the Endpoint GUI  |
# | * made modifications to utilse the CiscoSPA replacing Linksys name   |
# | * added published variables daylightrule and daylightenable,         |
# |   customdialplan.
# | * updated Linksys_local_spa.tpl file from Linksys template file
# +----------------------------------------------------------------------+
# $Id: dialerd,v 1.2 2008/09/08 18:29:36 alex Exp $
import logging
from elastix.BaseEndpoint import BaseEndpoint
from eventlet.green import urllib2, socket

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Linksys', amipool, dbpool, sServerIP, sIP, mac)

       # Convert Signed Decimal TimeZone Offset to Signed HH:MM offset formated for Cisco SPA Phones
        tzoffset = BaseEndpoint.getTimezoneOffset() / 60
        self._timeZone = ''
        if tzoffset < 0:
            self._timeZone = 'GMT-'
        else:
            self._timeZone = 'GMT+'
        self._timeZone = self._timeZone + ('%02d:%02d' % (abs(tzoffset) / 60, abs(tzoffset) % 60))
        self._daylightrule = ''
        self._daylightenable = ''



    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'timezone' in param: self._timeZone = param['timezone']
        if 'daylightrule' in param: self._daylightrule = param['daylightrule']
        if 'daylightenable' in param: self._daylightenable = param['daylightenable']
        return True


    def updateLocalConfig(self):
        '''Configuration for Linksys endpoints (local):

        A file called spaXXXXXXXXXXXX.cfg should be created with the XML
        configuration for the endpoint. The XXXXXXXXXXXX should be replaced with
        the lowercase version of the MAC address of the endpoint. To reboot and
        refresh the configuration, an HTTP GET request is performed to the URL:
        http://{$IP}/admin/resync?{$SERVER_IP}/spaXXXXXXXXXXXX.cfg, again
        replacing XXXXXXXXXXXX with the MAC address.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = 'spa' + (self._mac.replace(':', '').lower()) + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        vars['config_filename'] = sConfigFile
        vars.update({
            'time_zone'         :   self._timeZone,
            'daylight_rule'     :   self._daylightrule,
            'daylight_enable'   :   self._daylightenable,
        })

        try:
            self._writeTemplate('Linksys_local_spa.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Reboot the phone.
        try:
            # Open connection to execute resync.
            # TODO: what, no authentication?
            r = urllib2.urlopen('http://' + self._ip + '/admin/resync?' + self._serverip + '/' + sConfigFile)
            r.read()
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s got HTTP Error - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False

        self._unregister()
        self._setConfigured()
        return True

