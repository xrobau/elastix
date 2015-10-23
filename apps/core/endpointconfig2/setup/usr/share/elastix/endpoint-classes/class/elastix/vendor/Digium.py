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
import errno

class Endpoint(BaseEndpoint):
    def __init__(self, amipool, dbpool, sServerIP, sIP, mac):
        BaseEndpoint.__init__(self, 'Digium', amipool, dbpool, sServerIP, sIP, mac)

    def probeModel(self):
        '''Probe specific model of Digium phone
        
        The Digium web admin interface exposes the phone model at the URI
        /cgi-bin/prefetch.cgi without authentication:
        <?xml version="1.0" ?>
        <response>
            <firmware version="1_0_0_44308" />
            <config>
                <setting id="phone_model" value="D70" conflict="freeze" />
                <setting id="web_ui_enabled" value="1" />
            </config>
        </response>
        '''
        sModel = None
        try:
            response = urllib2.urlopen('http://' + self._ip + '/cgi-bin/prefetch.cgi')
            htmlbody = response.read()
            if response.code == 200:
                m = re.search(r'setting id="phone_model" value="(\w+)"', htmlbody)
                if m != None: sModel = m.group(1)
        except Exception, e:
            pass
        
        if sModel != None: self._saveModel(sModel)

    @staticmethod
    def updateGlobalConfig(serveriplist, amipool, endpoints):
        '''Set up global environment for this manufacturer
        '''
        # First of all, check if DPMA is available and properly licensed.
        if not Endpoint._isDPMAAvailable(amipool):
            return False
        
        # Check if SIP configuration is appropriate for DPMA
        Endpoint.checkSipConfiguration(amipool)
        
        '''
        We cannot just use a template for this, since a single file contains
        the configuration for all phones, and the endpoint list might be partial
        and risk removing the configuration of phones not involved in the update.
        
        The res_digium_phone.conf file will be parsed into an array. This array
        will contain, not lines, but sections of text. Each entry will be either
        a context, or a span of blank lines and comments between two contexts.
        For each context, its name will be extracted to a separate dictionary, 
        with the corresponding array index as a value.
        '''
        configsections, contexts = Endpoint._readDPMAConfig()
        if configsections == None: return False

        ''' First, we parse the general context. If any modifications are made,
        its raw text will be replaced. It is assumed that there are no
        duplicate keys to preserve. '''        
        if 'general' in contexts:
            generalcontext = contexts['general']
        else:
            generalcontext = {
                'type'      : 'context',
                'name'      : 'general',
                'rawtext'   : '[general]\n',
                'properties': {}
            }
            contexts['general'] = generalcontext
            configsections[0:0] = [generalcontext]
        modified = False
        targetproperties = {
            'service_discovery_enabled' : 'yes',
            'service_name' : 'Elastix',
            'config_auth' : 'mac'
        }
        for k in targetproperties:
            if (not k in generalcontext['properties']) or generalcontext['properties'][k] != targetproperties[k]:
                generalcontext['properties'][k] = targetproperties[k]
                modified = True
        if modified:
            generalcontext['rawtext'] = '[general]\n;DO NOT MODIFY - written by Endpoint Configurator\n'
            for k in generalcontext['properties']:
                generalcontext['rawtext'] += k + '=' + generalcontext['properties'][k] + '\n'

        '''
        Next, we define a bunch of configuration contexts that need to be 
        defined for the affected phones. If the corresponding context name 
        exists, it will replace the one on the config file, and new raw text 
        will be generated. Otherwise it will be appended to the end of the 
        array. After this, the file is written to disk.
        '''
        networkcontexts = {}
        phonecontexts = {}
        accountcontexts = {}
        for endpoint in endpoints:
            serverip = BaseEndpoint.chooseServerIP(serveriplist, endpoint.getIP())
            netval = serveriplist[serverip]['network']
            network_key = 'Elastix_Network_' + \
                str((netval >> 24) & 0xFF) + '-' + \
                str((netval >> 16) & 0xFF) + '-' + \
                str((netval >>  8) & 0xFF) + '-' + \
                str((netval      ) & 0xFF) + '-' + \
                str(serveriplist[serverip]['netbits'])
            
            # Check that network context is ready
            if not network_key in networkcontexts:
                networkspec = \
                    str((netval >> 24) & 0xFF) + '.' + \
                    str((netval >> 16) & 0xFF) + '.' + \
                    str((netval >>  8) & 0xFF) + '.' + \
                    str((netval      ) & 0xFF) + '/' + \
                    str(serveriplist[serverip]['netbits'])
                networkcontexts[network_key] = {
                    'type': 'context',
                    'name': network_key,
                    'rawtext' : '[' + network_key + ']\n;DO NOT MODIFY - written by Endpoint Configurator\n',
                    'properties' : {
                        'type': 'network',
                        'cidr': networkspec,
                        'registration_address' : serverip,
                        'registration_port': '5060',
                        'file_url_prefix' : BaseEndpoint._buildPhoneProv(serverip, 'Digium', 'GLOBAL')
                    }
                }                
                for k in networkcontexts[network_key]['properties']:
                    networkcontexts[network_key]['rawtext'] += k + '=' + networkcontexts[network_key]['properties'][k] + '\n'
            
            # Generate phone context, references network context
            endpoint_key = 'Phone_' + (endpoint._mac.replace(':', '').upper())
            phonecontexts[endpoint_key] = {
                'type': 'context',
                'name': endpoint_key,
                'rawtext': '[' + endpoint_key + ']\n;DO NOT MODIFY - written by Endpoint Configurator\n',
                'properties' : {
                    'type': 'phone',
                    'network': network_key,
                    'mac' : (endpoint._mac.replace(':', '').upper()),
                    'web_ui_enabled': 'yes'
                }
            }
            
            # Generate account contexts, references phone context
            rawaccounts = ''
            vars = endpoint._prepareVarList()
            for extension in vars['sip']:
                account_key = extension.account
                if not 'full_name' in phonecontexts[endpoint_key]['properties']:
                    phonecontexts[endpoint_key]['properties']['full_name'] = extension.description
                accountcontexts[account_key] = {
                    'type': 'context',
                    'name': account_key,
                    'rawtext': '[' + account_key + ']\n;DO NOT MODIFY - written by Endpoint Configurator\n',
                    'properties': {
                        'type': 'line',
                        'exten': extension.extension,
                        'line_label': extension.description
                    }
                }
                rawaccounts += 'line=' + account_key + '\n'
                for k in accountcontexts[account_key]['properties']:
                    accountcontexts[account_key]['rawtext'] += k + '=' + accountcontexts[account_key]['properties'][k] + '\n'
                
            # Build rawtext for phone
            for k in phonecontexts[endpoint_key]['properties']:
                phonecontexts[endpoint_key]['rawtext'] += k + '=' + phonecontexts[endpoint_key]['properties'][k] + '\n'
            phonecontexts[endpoint_key]['rawtext'] += rawaccounts
        
        newcontexts = {}
        newcontexts.update(networkcontexts);
        newcontexts.update(phonecontexts);
        newcontexts.update(accountcontexts);
        
        # Update existing contexts
        for k in newcontexts:
            if k in contexts:
                contexts[k].update(newcontexts[k])
            else:
                newcontexts[k]['rawtext'] += '\n'
                configsections.append(newcontexts[k])
        
        # Write out final configuration
        if not Endpoint._writeDPMAConfig(configsections):
            return False

        # Reconfigure all the phones
        ami = amipool.get()
        ami.Command('module reload res_digium_phone.so');
        ami.Command('digium_phones reconfigure all');
        amipool.put(ami)
        return True

    @staticmethod
    def deleteGlobalContent(serveriplist, amipool, endpoints):
        configsections, contexts = Endpoint._readDPMAConfig()
        if configsections == None: return False

        # Remove referenced endpoint
        for endpoint in endpoints:
            endpoint_key = 'Phone_' + (endpoint._mac.replace(':', '').upper())
            if endpoint_key in contexts:
                contexts[endpoint_key]['rawtext'] = ''

        # Write out final configuration
        if not Endpoint._writeDPMAConfig(configsections):
            return False

        # Reconfigure all the phones, if DPMA is available
        if Endpoint._isDPMAAvailable(amipool):
            ami = amipool.get()
            ami.Command('module reload res_digium_phone.so');
            ami.Command('digium_phones reconfigure all');
            amipool.put(ami)
        else:
            logging.warning('DPMA not available or loaded, cannot broadcast reconfiguration')
        return True

    @staticmethod
    def _readDPMAConfig():
        configsections = []
        contexts = {}
        try:
            configfile = open('/etc/asterisk/res_digium_phone.conf', 'r')
            currentcontext = None
            commentspan = None
            for configline in configfile:
                # Detect the start of a context
                m = re.search(r'^\s*\[(.+?)\]', configline)
                if m != None:
                    # If there was a previous context, it should be added to the list
                    if currentcontext != None:
                        configsections.append(currentcontext)
                    
                    # If there was a comment span, add as its own section
                    if commentspan != None:
                        configsections.append({
                            'type'      : 'commentspan',
                            'name'      : None,
                            'rawtext'   : commentspan,
                            'properties': None
                        })
                        commentspan = None
                    
                    # Create new named context
                    currentcontext = {
                        'type'      : 'context',
                        'name'      : m.group(1),
                        'rawtext'   : configline,
                        'properties': {}
                    }
                    
                    contexts[m.group(1)] = currentcontext
                    continue
                
                # Detect a property setting
                m = re.search(r'^\s*(\S+)\s*=\s*(.*?\S)?\s*(;.*)?$', configline)
                if m != None:
                    if currentcontext == None:
                        logging.warning('Discarding property set prior to any context!')
                        continue
                    
                    # The current comment span was part of the context
                    if commentspan != None:
                        currentcontext['rawtext'] += commentspan
                        commentspan = None
                    
                    currentcontext['rawtext'] += configline
                    # FIXME: this discards duplicate keys
                    currentcontext['properties'][m.group(1)] = m.group(2)
                    continue
                
                # Anything else is a comment
                if commentspan == None:
                    commentspan = ''
                commentspan += configline
            
            # Add any pending context, then any pending comments
            if currentcontext != None:
                configsections.append(currentcontext)
                currentcontext = None
            
            # If there was a comment span, add as its own section
            if commentspan != None:
                configsections.append({
                    'type'      : 'commentspan',
                    'name'      : None,
                    'rawtext'   : commentspan,
                    'properties': None
                })
                commentspan = None
                
        except IOError, e:
            if e.errno != errno.ENOENT:
                logging.error('Failed to read current res_digium_phone.conf - %s' % str(e))
                return [None, None]
        return [configsections, contexts]

    @staticmethod
    def _writeDPMAConfig(configsections):
        try:
            f = open('/etc/asterisk/res_digium_phone.conf', 'w')
            for currentsection in configsections:
                f.write(currentsection['rawtext'])
            f.close()
            return True
        except IOError, e:
            logging.error('Failed to write new res_digium_phone.conf - %s' % str(e))
            return False
    
    @staticmethod
    def _isDPMAAvailable(amipool):
        ami = amipool.get()
        
        module_loaded = False
        r = ami.Command('module show like res_digium_phone')
        for s in r:
            if 'res_digium_phone' in s:
                module_loaded = True
        if not module_loaded:
            logging.error('Required asterisk module res_digium_phone not loaded!')
            amipool.put(ami)
            return False
        
        module_licensed = False
        r = ami.Command('digium_phones license status')
        for s in r:
            if 'OK, Valid' in s:
                module_licensed = True
        if not module_licensed:
            logging.error('No valid license found for res_digium_phone!')
            amipool.put(ami)
            return False

        amipool.put(ami)
        return True
    
    @staticmethod
    def checkSipConfiguration(amipool):
        content = ''
        try:
            properties = {
                'auth_message_requests': 'no',
                'accept_outofcall_messages': 'yes',
                'outofcall_message_context': 'dpma_message_context',
                'callcounter': 'yes'
            }
            modified = False
            
            # FIXME: archivo es parte de FreePBX
            f = open('/etc/asterisk/sip_general_custom.conf', 'r')
            for configline in f:
                # Detect a property setting
                m = re.search(r'^\s*(\S+)\s*=\s*(.*?\S)?\s*(;.*)?$', configline)
                if (m == None) or not (m.group(1) in properties):
                    # Not a property or not interested - pass through
                    content += configline
                else:
                    if properties[m.group(1)] == m.group(2):
                        content += configline
                    else:
                        content += m.group(1) + '=' + properties[m.group(1)] + '\n'
                        modified = True
                    del properties[m.group(1)]
            f.close()
            for k in properties:
                content += k +'=' + properties[k] + '\n'
                modified = True
            
            # Write updated file and reload chan_sip
            if modified:
                f = open('/etc/asterisk/sip_general_custom.conf', 'w')
                f.write(content)
                f.close()
                
                ami = amipool.get()
                ami.Command('module reload chan_sip.so');
                amipool.put(ami)
        except IOError, e:
            if e.errno != errno.ENOENT:
                logging.warning('Failed to check SIP configuration - %s' % str(e))
            return
    
    def updateLocalConfig(self):
        # All configuration was already done in global configuration
        self._amireboot('cisco-check-cfg')  # NOP for already-configured
        self._unregister()
        self._setConfigured()
        return True
