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
import elastix.BaseEndpoint
from elastix.BaseEndpoint import BaseEndpoint
from eventlet.green import urllib2
#from eventlet.green import httplib

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Damall', amipool, dbpool, sServerIP, sIP, mac)
        self._bridge = True
        self._timeZone = 12

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if 'bridge' in param: self._bridge = param['bridge']
        if 'timezone' in param: self._timeZone = param['timezone']
        return True

    def probeModel(self):
        '''Probe specific model of Damall phone
        
        The Damall web admin interface uses Basic authentication for access 
        control. The authentication realm exposes the phone model like this:
        
        HTTP/1.1 401 Unauthorized
        Server: Damall-WebServer
        Date: Mon Feb  4 12:08:59 2013
        WWW-Authenticate: Digest realm="Damall_D-3310", domain="localhost",qop="auth", nonce="a1a380054430dc40c69b69809ef6b140", opaque="5ccc069c403ebaf9f0171e9517f40e41",algorithm="MD5", stale="FALSE"
        Pragma: no-cache
        Cache-Control: no-cache
        Content-Type: text/html
        
        '''
        sModel = None
        try:
            # Do not expect this to succeed. Only interested in exception.
            urllib2.urlopen('http://' + self._ip + '/')
        except urllib2.HTTPError, e:
            if e.code == 401 and 'WWW-Authenticate' in e.headers:
                m = re.search(r'realm="Damall_([\w-]+)"', e.headers['WWW-Authenticate'])
                if m != None: sModel = m.group(1)
        except Exception, e:
            pass
        
        if sModel != None: self._saveModel(sModel)
    
    def updateLocalConfig(self):
        '''Configuration for Damall endpoints (local)
        
        The file XXXXXXXXXXXX.cfg contains the plaintext SIP configuration. Here
        XXXXXXXXXXXX is replaced by the lowercase MAC address of the phone.
        
        To reboot the phone, it is necessary to issue the AMI command:
        sip notify reboot-yealink {$IP}. Verified with Damall D-3310.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        sConfigFile = (self._mac.replace(':', '').lower()) + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        vars.update({
            'enable_bridge'     :   int(self._bridge),
            'time_zone'         :   self._timeZone,
            'config_filename'   :   sConfigFile,
        })
        try:
            self._writeTemplate('Damall_local_cfg.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Attempt to force provisioning to STATIC into this server. This allows
        # the system to point the phone at us even if we are not a master DHCP.
        # Otherwise the DHCP server might need to publish option 160.
        postvars = {
            'ServerType1'   :   1,
            'ServerAddress1':   self._serverip,
            'FileName1'     :   sConfigFile,
            'Import'        :   'Update',
        }
        #try:
        if not self._doAuthPost('/goform/set_import_config', postvars):
            return False
        #except httplib.BadStatusLine, e:
        #    # Apparently a successful POST will start provisioning immediately
        #    pass
        
        # Reboot the phone.
        self._amireboot('reboot-yealink')
        self._unregister()
        self._setConfigured()
        return True
    