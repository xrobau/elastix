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
import tempita
import re
from eventlet.green import os, socket, urllib2, urllib, time
import errno
import sha
import random
from os.path import isfile
from datetime import datetime
import MySQLdb

ENDPOINT_DIR = '/usr/share/elastix/endpoint-classes'
ENDPOINT_CUSTOM_DIR = '/usr/local/share/elastix/endpoint-classes'
TFTP_DIR = '/tftpboot'

class BaseEndpoint(object):

    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Set up global environment for this manufacturer

        Typically this entails creating a directory structure under /tftpboot
        and/or creating global configuration files under same directory.
        '''
        return True

    @staticmethod
    def chooseServerIP(iplist, endpointIp):
        '''Choose a server IP whose network matches the one of the endpoint'''
        defaultip = None
        ip = endpointIp.split('.')
        ipval = (int(ip[0]) << 24) | (int(ip[1]) << 16) | (int(ip[2]) << 8) | int(ip[3])
        for serverip in iplist:
            netinfo = iplist[serverip]
            if defaultip == None: defaultip = serverip
            if (ipval & netinfo['netmask']) == netinfo['network']:
                return serverip
        return defaultip

    def __init__(self, sVendorName, amipool, dbpool, sServerIP, sIP, mac):
        '''Base class constructor for an endpoint. '''
        self._vendorname = str(sVendorName)
        self._amipool = amipool
        self._dbpool = dbpool
        self._serverip = str(sServerIP)
        self._ip = str(sIP)
        self._mac = mac
        self.max_accounts = 1
        self.static_ip_supported = False
        self.dynamic_ip_supported = True
        self._model = None
        self._max_sip_accounts = 1
        self._max_iax2_accounts = 0
        self._http_username = None
        self._http_password = None
        self._telnet_username = None
        self._telnet_password = None
        self._ssh_username = None
        self._ssh_password = None
        self._dhcp = True
        self._static_ip = None
        self._static_mask = None
        self._static_gw = None
        self._static_dns1 = None
        self._static_dns2 = None
        self._accounts = []
        self._endpointdir = ENDPOINT_DIR
        self._tftpdir = TFTP_DIR
        self._authtoken_sha1 = None

    def getIP(self):
        return self._ip

    def setModel(self, sModel):
        '''Assign a model for an endpoint vendor

        Should override - default implementation assigns any model without any check.
        '''
        self._model = str(sModel)
        return True

    def probeModel(self):
        '''Attempt to probe the phone model

        Should override - default implementation does not probe anything.
        '''
        pass

    def setExtraParameters(self, param):
        '''Assign extra parameters for the endpoint

        The default implementation knows about the following keywords:
        max_sip_accounts
        max_iax2_accounts
        http_username
        http_password
        telnet_username
        telnet_password
        ssh_username
        ssh_password
        dhcp
        static_ip
        statig_gw
        static_mask
        static_dns1
        static_dns2

        This method should be extended to store extra parameters beyond these
        '''
        baseparam = ('max_sip_accounts', 'max_iax2_accounts', 'http_username',
            'http_password', 'telnet_username', 'telnet_password', 'dhcp',
            'static_ip', 'static_gw', 'static_mask', 'static_dns1', 'static_dns2',
            'ssh_username', 'ssh_password')
        for k in param:
            if k in baseparam: setattr(self, '_' + k, param[k])
        self._max_sip_accounts = int(self._max_sip_accounts)
        self._max_iax2_accounts = int(self._max_iax2_accounts)
        self._dhcp = bool(int(self._dhcp))

        if (not self.dynamic_ip_supported) and self._dhcp:
            logging.error('Endpoint %s@%s does not support dynamic IP configuration' % (self._vendorname, self._ip))
            return False
        if (not self.static_ip_supported) and (not self._dhcp):
            logging.error('Endpoint %s@%s does not support static IP configuration' % (self._vendorname, self._ip))
            return False

        if self._dhcp:
            self._static_ip = None
            self._static_mask = None
            self._static_gw = None
            self._static_dns1 = None
            self._static_dns2 = None
        else:
            if self._static_ip == None or self._static_mask == None:
                logging.error('Endpoint %s@%s requires static_ip and static_mask' %
                    (self._vendorname, self._ip))
                return False
            for k in ('_static_ip', '_static_mask', '_static_gw', '_static_dns1', '_static_dns2'):
                v = getattr(self, k)
                if v != None:
                    if v == '0.0.0.0' or not re.search(r'^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$', v):
                        logging.error('Endpoint %s@%s found invalid IP address: %s' %
                            (self._vendorname, self._ip, v))
                        return False
        return True

    def setAccountList(self, accountlist):
        '''Assign the list of accounts to configure on this endpoint

        This implementation will warn if the list contains more accounts than
        supported by the particular model
        '''
        totaltech = {'sip' : 0, 'iax2': 0}
        total= 0
        for account in accountlist:
            totaltech[account.tech] += 1
            total += 1
            max_tech_accounts = getattr(self, '_max_' + account.tech + '_accounts', 0)
            if total <= self.max_accounts and totaltech[account.tech] <= max_tech_accounts:
                self._accounts.append(account)
        for tech in totaltech:
            max_tech_accounts = getattr(self, '_max_' + tech + '_accounts', 0)
            if totaltech[tech] > max_tech_accounts:
                if max_tech_accounts > 0:
                    logging.warning(
                        'Endpoint %s@%s only supports %d %s accounts, got %d - only %d accounts will be configured' %
                        (self._vendorname, self._ip, max_tech_accounts, tech, totaltech[tech], max_tech_accounts))
                else:
                    logging.warning(
                        'Endpoint %s@%s does not support %s accounts' %
                        (self._vendorname, self._ip, tech))

    def updateLocalConfig(self):
        logging.error('Endpoint %s@%s unimplemented updateLocalConfig' % (self._vendorname, self._ip))
        return False

    def _amireboot(self, sNotify):
        '''Send AMI SIP notification for phone reboot

        Execute a SIP notification that should cause a reboot of a phone. The
        specific notification-string is phone-specific and should be defined in
        /etc/asterisk/sip_notify.conf or (FreePBX)
        /etc/asterisk/sip_notify_additional.conf .
         '''
        target = self._ip
        for account in self._accounts:
            if account.tech == 'sip' and account.registered:
                target = account.account
                break
        ami = self._amipool.get()
        ami.Command('sip notify ' + sNotify + ' ' + target)
        self._amipool.put(ami)

    def _hasRegisteredExtension(self):
        '''Returns True if at least one configured SIP account is registered'''
        for endpoint in self._accounts:
            if endpoint.tech == 'sip' and endpoint.registered:
                return True
        return False

    def _unregister(self):
        '''Force unregistration

        Method to force unregistration of the registered endpoint as the final
        step while rebooting the phone.
        '''
        ami = self._amipool.get()
        for account in self._accounts:
            if account.registered:
                ami.Command(account.tech + ' unregister ' + account.account)
        self._amipool.put(ami)

    def _saveVendor(self, sVendor):
        '''Forces a vendor different from the class default.

        This should be done during model probe only. Otherwise behavior is undefined.
        '''
        if self._vendorname == sVendor: return

        dbconn = None
        try:
            dbconn = self._dbpool.get()
            sth = dbconn.cursor()

            # Query the model ID for the identified model
            sth.execute('SELECT id FROM manufacturer WHERE name = %s', (sVendor,))
            row = sth.fetchone()
            if row != None:
                sth.execute('UPDATE endpoint SET id_manufacturer = %s, id_model = NULL WHERE last_known_ipv4 = %s',
                            (row[0], self._ip))
                dbconn.commit()
                self._vendorname = sVendor
            else:
                logging.warning('Endpoint %s@%s failed to save unknown vendor "%s"' %
                        (self._vendorname, self._ip, sModel))
            self._dbpool.put(dbconn)
        except MySQLdb.Error, e:
            logging.error('Endpoint %s@%s failed to query database - %s' %
                (self._vendorname, self._ip, str(e)))
            if dbconn != None: dbpool.put(dbconn)

    def _loadCustomCredentials(self):
        ''' Load custom credentials in case model probe requires them '''
        dbconn = None
        try:
            dbconn = self._dbpool.get()
            sth = dbconn.cursor()

            # Load any custom credentials
            sth.execute(
                'SELECT property_key, property_value '\
                'FROM endpoint_properties, endpoint '\
                'WHERE endpoint_properties.id_endpoint = endpoint.id '\
                    'AND endpoint.last_known_ipv4 = %s '\
                    'AND property_key IN ("http_username", "http_password", '\
                        '"telnet_username", "telnet_password", "ssh_username", '\
                        '"ssh_password")',
                self._ip)
            for row in sth.fetchall():
                setattr(self, '_' + row[0], row[1])

            self._dbpool.put(dbconn)
        except MySQLdb.Error, e:
            logging.error('Endpoint %s@%s failed to query database - %s' %
                (self._vendorname, self._ip, str(e)))
            if dbconn != None:
                dbconn.rollback()
                dbpool.put(dbconn)

    def _saveModel(self, sModel):
        '''Save a probed model identification to database '''
        dbconn = None
        try:
            dbconn = self._dbpool.get()
            sth = dbconn.cursor()

            # Query the model ID for the identified model
            sth.execute(
                'SELECT model.id FROM model, manufacturer '\
                'WHERE model.id_manufacturer = manufacturer.id '\
                    'AND manufacturer.name = %s AND model.name = %s',
                (self._vendorname, sModel))
            row = sth.fetchone()
            if row != None:
                sth.execute('UPDATE endpoint SET id_model = %s WHERE last_known_ipv4 = %s',
                            (row[0], self._ip))
                dbconn.commit()
            else:
                logging.warning('Endpoint %s@%s failed to save unknown model "%s"' %
                        (self._vendorname, self._ip, sModel))
            self._dbpool.put(dbconn)
        except MySQLdb.Error, e:
            logging.error('Endpoint %s@%s failed to query database - %s' %
                (self._vendorname, self._ip, str(e)))
            if dbconn != None:
                dbconn.rollback()
                dbpool.put(dbconn)

    def _setConfigured(self):
        try:
            dbconn = self._dbpool.get()
            sth = dbconn.cursor()
            sth.execute('SELECT id FROM endpoint WHERE last_known_ipv4 = %s', (self._ip));
            row = sth.fetchone()
            if row != None:
                id = row[0]

                sth.execute(\
                    'UPDATE endpoint SET last_configured = NOW(), selected = 0, authtoken_sha1 = %s WHERE id = %s',\
                    (self._authtoken_sha1, id,))
                if not self._dhcp and self._static_ip != None and self._static_ip != self._ip:
                    sth.execute('UPDATE endpoint SET last_known_ipv4 = %s WHERE id = %s', (self._static_ip, id,))
                    self._ip = self._static_ip
            sth.close()
            dbconn.commit()
            self._dbpool.put(dbconn)
        except MySQLdb.Error, e:
            logging.error('Endpoint %s@%s failed to save configured status - %s' %
                (self._vendorname, self._ip, str(e)))
            if dbconn != None:
                dbconn.rollback()
                dbpool.put(dbconn)

    def _prepareVarList(self):
        '''Prepare list of common variables to substitute in template '''

        hash = sha.new()
        hash.update(self._vendorname + self._model + self._serverip + self._ip + str(random.randint(0, 1024 * 1024 * 1024)))
        self._authtoken_sha1 = hash.hexdigest()

        vars = {
            'server_ip'         :   self._serverip,
            'static_ip'         :   self._static_ip,
            'static_mask'       :   self._static_mask,
            'static_gateway'    :   self._static_gw,
            'static_dns1'       :   self._static_dns1,
            'static_dns2'       :   self._static_dns2,
            'sip'               :   [],
            'max_sip_accounts'  :   self._max_sip_accounts,
            'enable_dhcp'       :   int(self._dhcp),
            'phonesrv'          :   self._buildPhoneProv(self._serverip, self._vendorname, self._authtoken_sha1),
        }
        for account in self._accounts:
            if account.tech == 'sip':
                vars['sip'].append(account)

        # Save the auth hash NOW in order to have it available for HTTP requests
        try:
            dbconn = self._dbpool.get()
            sth = dbconn.cursor()
            sth.execute('SELECT id FROM endpoint WHERE last_known_ipv4 = %s', (self._ip));
            row = sth.fetchone()
            if row != None:
                id = row[0]

                sth.execute(\
                    'UPDATE endpoint SET authtoken_sha1 = %s WHERE id = %s',\
                    (self._authtoken_sha1, id,))
            sth.close()
            dbconn.commit()
            self._dbpool.put(dbconn)
        except MySQLdb.Error, e:
            logging.error('Endpoint %s@%s failed to save authtoken - %s' %
                (self._vendorname, self._ip, str(e)))
            if dbconn != None:
                dbconn.rollback()
                dbpool.put(dbconn)

        return vars

    def _doAuthGet(self, urlpath):
        '''Perform an HTTP GET on a particular URL using the HTTP credentials

        Implemented using _doAuthPost
        '''
        return self._doAuthPost(urlpath, None)

    def _doAuthPost(self, urlpath, postvars):
        '''Perform an HTTP POST on a particular URL using the HTTP credentials

        This method is frequently used to make the phone use the Elastix server
        as the TFTP source for autoprovisioning.
        '''
        password_manager = urllib2.HTTPPasswordMgrWithDefaultRealm()
        password_manager.add_password(None, 'http://' + self._ip + '/',
            self._http_username, self._http_password)
        basic_auth_handler = urllib2.HTTPBasicAuthHandler(password_manager)
        digest_auth_handler = urllib2.HTTPDigestAuthHandler(password_manager)
        opener = urllib2.build_opener(basic_auth_handler, digest_auth_handler)
        if postvars != None:
            opener.addheaders = [('Content-Type', 'application/x-www-form-urlencoded')]
            if not isinstance(postvars, str):
                postvars = urllib.urlencode(postvars)
        try:
            opener.open('http://' + self._ip + urlpath, postvars)
        except urllib2.HTTPError, e:
            logging.error('Endpoint %s@%s failed to authenticate - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except urllib2.URLError, e:
            logging.error('Endpoint %s@%s failed to authenticate - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        except socket.error, e:
            logging.error('Endpoint %s@%s failed to connect - %s' %
                (self._vendorname, self._ip, str(e)))
            return False
        return True

    @staticmethod
    def _buildPhoneProv(serverip, vendor, authtoken):
        return 'http://' + serverip + '/modules/endpoint_configurator/phonesrv/phonesrv.php/' + vendor + '/' + authtoken

    @staticmethod
    def _fetchTemplate(sTemplate, vars):
        tmpl = tempita.Template.from_filename(ENDPOINT_DIR + '/tpl/' + sTemplate)
        # Check for custom DIR
        if isfile (ENDPOINT_CUSTOM_DIR + '/tpl/' + sTemplate):
            tmpl = tempita.Template.from_filename(ENDPOINT_CUSTOM_DIR + '/tpl/' + sTemplate)
        return tmpl.substitute(vars)

    @staticmethod
    def _writeTemplate(sTemplate, vars, sOutput):
        BaseEndpoint._writeContent(sOutput, BaseEndpoint._fetchTemplate(sTemplate, vars))

    @staticmethod
    def _writeContent(filepath, content):
        # Unlink before overwrite to cope with Backup/Restore restoring as root
        try:
            os.unlink(filepath)
        except OSError, e:
            # swallow "no such file", re-raise anything else
            if e.errno != errno.ENOENT: raise e
        f = open(filepath, 'w')
        f.write(content)
        f.close()

    @staticmethod
    def deleteGlobalContent(serveriplist, amipool, endpoints):
        # The default implementation does nothing
        return True

    def deleteContent(self):
        # The default implementation deletes all files under the TFTP directory
        # with a name containing a lowercase or uppercase MAC address that
        # matches the one assigned for the endpoint
        lcasemac = self._mac.replace(':', '').lower()
        ucasemac = self._mac.replace(':', '').upper()
        for filename in os.listdir(TFTP_DIR):
            if (lcasemac in filename) or (ucasemac in filename):
                os.unlink(TFTP_DIR + '/' + filename)

        # Unregister accounts
        self._unregister()

    @staticmethod
    def getTimezoneOffset():
        return -1 * time.timezone
