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

class Endpoint(BaseEndpoint):
    _global_serverip = None
    
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Polycom', amipool, dbpool, sServerIP, sIP, mac)
        if Endpoint._global_serverip == None:
            Endpoint._global_serverip = sServerIP
        elif Endpoint._global_serverip != sServerIP:
            logging.warning('global server IP is %s but endpoint %s requires ' + 
                'server IP %s - this endpoint might not work correctly.' %
                (Endpoint._global_serverip, sIP, sServerIP))

    # FIXME: currently no known way to probe phone model remotely

    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Configuration for Polycom endpoints (global):
        
        Server definition goes in /tftpboot/server.cfg
        Sip global definition goes in /tftpboot/sip.cfg
        Requires directories /tftpboot/polycom/{logs,overrides,contacts}
        '''
        for sDir in ('/polycom/logs', '/polycom/overrides', '/polycom/contacts'):
            try:
                os.makedirs(elastix.BaseEndpoint.TFTP_DIR + sDir, 0777)
            except OSError, e:
                # swallow "already exists", re-raise anything else
                if e.errno != errno.EEXIST: 
                    logging.error('Failed to create directory for Polycom - %s' % (str(e),))
                    return False
        vars = {
            'server_ip' : Endpoint._global_serverip,
            'phonesrv'  : BaseEndpoint._buildPhoneProv(Endpoint._global_serverip, 'Polycom', 'GLOBAL'),
        }
        
        for sConfigFile, sTemplate in (
            ('server.cfg', 'Polycom_global_server.tpl'),
            ('sip_1.cfg', 'Polycom_global_sip_1.tpl'),
            ('sip_2.cfg', 'Polycom_global_sip_2.tpl'),
            ):
            try:
                sConfigPath = elastix.BaseEndpoint.TFTP_DIR + '/' + sConfigFile
                BaseEndpoint._writeTemplate(sTemplate, vars, sConfigPath)
            except IOError, e:
                logging.error('Failed to write %s for Polycom - %s' % (sConfigFile, str(e),))
                return False
        return True

    def updateLocalConfig(self):
        '''Configuration for Polycom endpoints (local):
        
        Two files should be created for each endpoint. The file XXXXXXXXXXXX.cfg
        references server.cfg, sip.cfg, and the detailed configuration, which is
        written to XXXXXXXXXXXXreg.cfg. Here XXXXXXXXXXXX is replaced by the
        lowercase MAC address of the phone.
        
        To reboot the phone, it is necessary to issue the AMI command:
        sip notify polycom-check-cfg {$IP}. Verified with IP 330.
        '''
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        # Need to calculate lowercase version of MAC address without colons
        mac = self._mac.replace(':', '').lower()
        
        # Write out configuration files
        sConfigFile = mac + '.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = {
            'config_filename'   :   mac + 'reg.cfg',
            'microbrowser_cfg'  :   mac + 'microbrowser.cfg',
        }
        if self._model in ('IP 301', 'IP 501', 'IP 601'):
            vars['sip_cfg'] = 'sip_1.cfg'
        else:
            vars['sip_cfg'] = 'sip_2.cfg'
        try:
            self._writeTemplate('Polycom_local_header.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        vars = self._prepareVarList()
        sConfigFile = mac + 'reg.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        try:
            self._writeTemplate('Polycom_local_reg.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        sConfigFile = mac + 'microbrowser.cfg'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        try:
            self._writeTemplate('Polycom_local_microbrowser.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        # Create useful logfiles in standard directories
        for logpath in (
            '/polycom/overrides/' + mac + '-phone.cfg',
            '/polycom/logs/' + mac + '-boot.log',
            '/polycom/logs/' + mac + '-app.log',):
            try:
                f = open(self._tftpdir + logpath, 'a')
                f.close()
                os.chmod(self._tftpdir + logpath, 0666)
            except IOError, e:
                logging.error('Endpoint %s@%s failed to create logfile - %s' %
                    (self._vendorname, self._ip, str(e)))
                return False

        self._amireboot('polycom-check-cfg')
        self._unregister()
        self._setConfigured()
        return True
        