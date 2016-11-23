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
from eventlet.green import socket, urllib2, urllib, os, httplib
import elastix.BaseEndpoint
from elastix.BaseEndpoint import BaseEndpoint

SNOM_HTTP_V1 = 1
SNOM_HTTP_V2 = 2

class Endpoint(BaseEndpoint):
    _global_serverip = None

    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Snom', amipool, dbpool, sServerIP, sIP, mac)
        if Endpoint._global_serverip == None:
            Endpoint._global_serverip = sServerIP
        elif Endpoint._global_serverip != sServerIP:
            logging.warning('global server IP is %s but endpoint %s requires ' +
                'server IP %s - this endpoint might not work correctly.' %
                (Endpoint._global_serverip, sIP, sServerIP))
        self._bridge = True
        self._timeZone = None
        self._cookie_v2 = None
        self._language = 'Español'

    def setExtraParameters(self, param):
        if not BaseEndpoint.setExtraParameters(self, param): return False
        if self._getHttpInterfaceClass() == SNOM_HTTP_V2:
            self._timeZone = '301'
        else:
            self._timeZone = 'USA-5'
        if 'bridge' in param: self._bridge = param['bridge']
        if 'timezone' in param: self._timeZone = param['timezone']
        if 'language' in param: self._language = param['language']
        return True

    def probeModel(self):
        '''Probe specific model of the Snom phone

        The Snom phone displays the phone model in the title screen, which is
        unsecured by default.
        '''
        self._loadCustomCredentials()

        sModel = None
        try:
            password_manager = urllib2.HTTPPasswordMgrWithDefaultRealm()
            if self._http_password != None:
                password_manager.add_password(None, 'http://' + self._ip + '/',
                    self._http_username, self._http_password)
            basic_auth_handler = urllib2.HTTPBasicAuthHandler(password_manager)
            opener = urllib2.build_opener(basic_auth_handler)
            response = opener.open('http://' + self._ip + '/')
            htmlbody = response.read()
            if response.code == 200:
                # <TITLE>snom 320</TITLE>
                m = re.search(r'<TITLE>snom (\w+)</TITLE>', htmlbody, re.IGNORECASE)
                if m != None:
                    sModel = m.group(1)
                else:
                    # M300, M700
                    m = re.search(r'<TITLE>(M\d+)</TITLE>', htmlbody, re.IGNORECASE)
                    if m != None:
                        sModel = m.group(1)
        #except urllib2.HTTPError, e:
        #    if e.code == 401 and 'WWW-Authenticate' in e.headers:
        #        m = re.search(r'realm="Aastra (.+)"', e.headers['WWW-Authenticate'])
        #        if m != None: sModel = m.group(1)
        except Exception, e:
            pass

        if sModel != None: self._saveModel(sModel)

    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Configuration for Snom endpoints (global):

        SIP global definition goes in /tftpboot/snom{300|320|360}.htm
        '''
        vars = {'server_ip' : Endpoint._global_serverip}

        #for sConfigFile in ('snom300.htm', 'snom320.htm', 'snom360.htm', 'snom821.htm'):
        for sModel in ('300', '320', '360', '710', '720', '760', '821', '870'):
            try:
                #sConfigPath = elastix.BaseEndpoint.TFTP_DIR + '/' + sConfigFile
                sConfigPath = '%s/snom%s.htm' % (elastix.BaseEndpoint.TFTP_DIR, sModel)
                BaseEndpoint._writeTemplate('Snom_global_3xx.tpl', vars, sConfigPath)
            except IOError, e:
                logging.error('Failed to write %s for Snom - %s' % (sConfigFile, str(e),))
                return False
        return True

    def _getHttpInterfaceClass(self):
        if self._model != None and self._model in ('m9', 'MeetingPoint', 'PA1',
            'D305', 'D315', 'D345', 'D375', 'D715', 'D725', 'D745', 'D765'):
            return SNOM_HTTP_V2
        return SNOM_HTTP_V1

    def updateLocalConfig(self):
        # Check that there is at least one account to configure
        if len(self._accounts) <= 0:
            logging.error('Endpoint %s@%s has no accounts to configure' %
                (self._vendorname, self._ip))
            return False

        if self._getHttpInterfaceClass() == SNOM_HTTP_V1:
            success = self._updateLocalConfig_V1()
        else:
            success = self._updateLocalConfig_V2()


        if success:
            self._unregister()
            self._setConfigured()
        return success

    def _updateLocalConfig_V1(self):
        '''Configuration for Snom endpoints (local):

        The file snomMMM-XXXXXXXXXXXX.htm contains the SIP configuration. Here
        XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone, and
        MMM is replaced by the specific model of the phone.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify reboot-snom {$EXTENSION}. Alternatively the phone can be
        rebooted by requesting the URL
        "http://{$this->_ip}/advanced_network.htm?reboot=Reboot".
        Verified with Snom 300.
        '''
        # Need to calculate UPPERCASE version of MAC address without colons
        sConfigFile = 'snom' + self._model + '-' + (self._mac.replace(':', '').upper()) + '.htm'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        vars.update({
            'time_zone'         :   self._timeZone,
            'enable_bridge'     :   int(self._bridge),
            'language'          :   self._language
        })
        try:
            self._writeTemplate('Snom_local_3xx.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        if not self._setProvisionServer_V1(): return False
        (success, rebooting) = self._setNetworkConfig_V1()
        if not success: return False

        if not rebooting:
            # Check if there is at least one registered extension. This is required
            # for sip notify to work
            if self._hasRegisteredExtension():
                self._amireboot('reboot-snom')
            elif not self._rebootbyhttp_V1():
                return False
        return True

    def _updateLocalConfig_V2(self):
        '''Configuration for Snom endpoints, new versions (local):

        The file snom-MMM-XXXXXXXXXXXX.xml contains the SIP configuration. Here
        XXXXXXXXXXXX is replaced by the UPPERCASE MAC address of the phone, and
        MMM is replaced by the specific model of the phone.

        To reboot the phone, it is necessary to issue the AMI command:
        sip notify cisco-check-cfg {$EXTENSION}. Alternatively the phone can be
        rebooted by POSTING to /update.htm with Reboot=reboot.Verified with
        Snom m9.
        '''
        # Need to calculate UPPERCASE version of MAC address without colons
        sConfigFile = 'snom-' + self._model + '-' + (self._mac.replace(':', '').upper()) + '.xml'
        sConfigPath = self._tftpdir + '/' + sConfigFile
        vars = self._prepareVarList()
        vars.update({
            'config_filename'   :   sConfigFile,
            'time_zone'         :   self._timeZone,
            'enable_bridge'     :   int(self._bridge),
            'current_ip'        :   self._ip,
            'language'          :   self._language
        })
        try:
            self._writeTemplate('Snom_local_m9.tpl', vars, sConfigPath)
        except IOError, e:
            logging.error('Endpoint %s@%s failed to write configuration file - %s' %
                (self._vendorname, self._ip, str(e)))
            return False

        if not self._loginHttp_V2(): return False
        if not self._setProvisionServer_V2(): return False
        #logging.error('Endpoint %s@%s unimplemented V2' %
        #    (self._vendorname, self._ip))
        (success, rebooting) = (True, False)
        if not success: return False

        #if not rebooting:
        #    # Check if there is at least one registered extension. This is required
        #    # for sip notify to work
        #    if self._hasRegisteredExtension():
        #        self._amireboot('cisco-check-cfg')
        #    elif not self._rebootbyhttp_V2():
        #        return False
        if not self._rebootbyhttp_V2():
            return False
        return True

    def _loginHttp_V2(self):
        try:
            # Do HTTP login and save session cookie
            postvars = {
                'username'  : self._http_username,
                'password'  : self._http_password,
                'link'      : 'index.htm',
                'submit'    : 'Login'
            }
            response = urllib2.urlopen(
                'http://' + self._ip + '/index.htm',
                urllib.urlencode(postvars))
            htmlbody = response.read()
            if not 'Set-Cookie' in response.headers:
                logging.error('Endpoint %s@%s invalid username or password' %
                        (self._vendorname, self._ip))
                return False
            self._cookie_v2 = response.headers['Set-Cookie']

            # If the phone is used for the first time, it will show an EULA that
            # must be accepted in order to continue.
            m = re.search(r'<input type="radio" name="eula" value="(.+?)">', htmlbody)
            if m != None:
                logging.warning('Endpoint %s@%s accepting EULA...' % (self._vendorname, self._ip))
                postvars = {'eula' : m.group(1), 'save' : 'Submit'}
                response = urllib2.urlopen(urllib2.Request(
                    'http://' + self._ip + '/index.htm',
                    urllib.urlencode(postvars),
                    {'Cookie' : self._cookie_v2}))
                htmlbody = response.read()
            return True
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
        return False

    def _setProvisionServer_V1(self):
        try:
            postvars = {'setting_server': 'tftp://' + self._serverip, 'Settings' : 'Save' }
            response = urllib2.urlopen('http://' + self._ip + '/advanced_update.htm', urllib.urlencode(postvars))
            htmlbody = response.read()
            return True
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to set provisioning server - %s' %
                (self._vendorname, self._ip, str(e)))
        return False

    def _setProvisionServer_V2(self):
        sConfigFile = 'snom-' + self._model + '-' + (self._mac.replace(':', '').upper()) + '.xml'
        try:
            '''
            base_name=snom-at-192.168.254.254-reg-192.168.254.1&
            asset_id=&
            dhcp=true&
            ip_adr=&
            netmask=&
            gateway=&
            dns_server1=&
            dns_server2=&
            dns_server3=&
            dns_server4=&
            dns_domain=&
            vlan_id=0&
            vlan_prio=0&
            setting_server=192.168.254.2&
            settings_refresh_timer=86400&
            sip_port=0&
            retry_t1=500&
            tos_rtp=160&
            tos_sip=160&
            allow_check_sync=false&
            stun_server=&
            stun_interval=5&
            ethernet_replug=reregister&
            network=true&
            save=Save
            '''
            postvars = {
                'setting_server'            :   'tftp://' + self._serverip,
                'base_name'                 :   'snom-at-' + self._ip + '-reg-' + self._serverip,
                'dhcp'                      :   'true',
                'ip_adr'                    :   '',
                'netmask'                   :   '',
                'gateway'                   :   '',
                'dns_server1'               :   '',
                'dns_server2'               :   '',
                'dns_server3'               :   '',
                'dns_server4'               :   '',
                'allow_check_sync'          :   'true', # Needed for sip notify to work
                'save'                      :   'Save',

                'network'                   :   'true',
                'asset_id'                  :   '',
                'vlan_id'                   :   0,
                'vlan_prio'                 :   0,
                'settings_refresh_timer'    :   86400,
                'sip_port'                  :   0,
                'retry_t1'                  :   500,
                'tos_rtp'                   :   160,
                'tos_sip'                   :   160,
                'stun_server'               :   '',
                'stun_interval'             :   5,
                'ethernet_replug'           :   'reregister',
            }
            if not self._dhcp:
                postvars.update({
                    'dhcp'          :   'false',
                    'ip_adr'        :   self._static_ip,
                    'netmask'       :   self._static_mask,
                    'gateway'       :   self._static_gw,
                    'dns_server1'   :   self._static_dns1,
                    'dns_server2'   :   self._static_dns2,
                })
            response = urllib2.urlopen(urllib2.Request(
                'http://' + self._ip + '/network.htm',
                urllib.urlencode(postvars),
                {'Cookie' : self._cookie_v2}))
            htmlbody = response.read()
            if not 'Please reboot the device' in htmlbody:
                logging.error('Endpoint %s@%s failed to save provisioning or network settings' %
                        (self._vendorname, self._ip))
                return False
            return True
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to reboot phone - %s' %
                (self._vendorname, self._ip, str(e)))
        return False

    def _setNetworkConfig_V1(self):
        try:
            if self._dhcp:
                postvars = {
                    'dhcp'      :   'on',
                    'Settings'  :   'Save',
                    'ignore_dhcp_findings' : '',
                }
            else:
                postvars = {
                    'dhcp'          :   'off',
                    'ip_adr'        :   self._static_ip,
                    'netmask'       :   self._static_mask,
                    'gateway'       :   self._static_gw,
                    'dns_server1'   :   self._static_dns1,
                    'dns_server2'   :   self._static_dns2,
                    'Settings'      :   'Save',
                    'ignore_dhcp_findings' : 'dns_server1 dns_server2 gateway ip_adr netmask',
                }
            response = urllib2.urlopen('http://' + self._ip + '/advanced_network.htm', urllib.urlencode(postvars))
            htmlbody = response.read()
            if 'CONFIRM_REBOOT' in htmlbody:
                response = urllib2.urlopen('http://' + self._ip + '/advanced_network.htm', 'CONFIRM_REBOOT=Reboot')
                htmlbody = response.read()
                response = urllib2.urlopen('http://' + self._ip + '/confirm.htm', 'REBOOT=Yes')
                htmlbody = response.read()
                logging.info('Endpoint %s@%s set network config - rebooting' %
                    (self._vendorname, self._ip))
                return (True, True)
            else:
                logging.info('Endpoint %s@%s set network config - not yet rebooting' %
                    (self._vendorname, self._ip))
                return (True, False)
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return (False, False)
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to reboot phone - %s' %
                (self._vendorname, self._ip, str(e)))
        except httplib.BadStatusLine, e:
            # Apparently a successful CONFIRM_REBOOT will start provisioning immediately
            return (True, True)
        return (False, False)

    def _rebootbyhttp_V1(self):
        try:
            response = urllib2.urlopen('http://' + self._ip + '/advanced_update.htm?reboot=Reboot')
            htmlbody = response.read()
            if response.code == 302:
                return True
            else:
                logging.error('Endpoint %s@%s failed to reboot phone - got error code %d' %
                    (self._vendorname, self._ip, response.code))
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except httplib.BadStatusLine, e:
            # Apparently a successful GET will start provisioning immediately
            return True
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to reboot phone - %s' %
                (self._vendorname, self._ip, str(e)))
        return False

    def _rebootbyhttp_V2(self):
        try:
            response = urllib2.urlopen(urllib2.Request(
                'http://' + self._ip + '/update.htm',
                urllib.urlencode({'reboot' : 'Reboot'}),
                {'Cookie' : self._cookie_v2}))
            htmlbody = response.read()
            if response.code == 200:
                return True
            else:
                logging.error('Endpoint %s@%s failed to reboot phone - got error code %d' %
                    (self._vendorname, self._ip, response.code))
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                    (self._vendorname, self._ip, str(e)))
            return False
        except httplib.BadStatusLine, e:
            # Apparently a successful GET will start provisioning immediately
            return True
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to reboot phone - %s' %
                (self._vendorname, self._ip, str(e)))
        return False