<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
*/

define('ASTERISK_VOICEMAIL_CONF', '/etc/asterisk/voicemail.conf');

class paloSantoVoiceMail
{
    var $errMsg;

    function writeFileVoiceMail($new_vmext, $new_vmparam = NULL)
    {
        if (!file_exists(ASTERISK_VOICEMAIL_CONF)) {
            $this->errMsg = "File ".ASTERISK_VOICEMAIL_CONF." does not exist";
            return FALSE;
        }

        $k_vm_parameters = array('voicemail_password', 'user_name',
            'user_email_address', 'pager_email_address');
        $k_user_options = array('attach', 'saycid', 'envelope', 'delete',
            'attachfmt', 'serveremail', 'tz', 'imapuser', 'imappasswd',
            'sendvoicemail', 'review', 'tempgreetwarn', 'sayduration',
            'saydurationm', 'forcename', 'forcegreetings', 'maxmsg', 'volgain',
            'operator', 'callback', 'dialout', 'exitcontext',
        );

        $new_content = array();
        foreach (file(ASTERISK_VOICEMAIL_CONF) as $l) {
            list($old_vmext, $old_vmparam) = $this->_parseVoicemailContext(trim($l));
            if (!is_null($old_vmext) && $new_vmext == $old_vmext) {
                if (!is_null($new_vmparam)) {
                    foreach ($k_vm_parameters as $k)
                        if (isset($new_vmparam[$k])) $old_vmparam[$k] = $new_vmparam[$k];
                    if (isset($new_vmparam['user_options']) && is_array($new_vmparam['user_options'])) {
                        foreach ($new_vmparam['user_options'] as $k => $v) {
                            if (in_array($k, $k_user_options)) $old_vmparam['user_options'][$k] = $v;
                        }
                    }
                    $new_content[] = $this->_formatVoicemailContext($old_vmext, $old_vmparam);
                }
                $new_vmparam = NULL;
            } else {
                $new_content[] = $l;
            }
        }
        if (!is_null($new_vmparam))
            $new_content[] = $this->_formatVoicemailContext($new_vmext, $new_vmparam);
        return (file_put_contents(ASTERISK_VOICEMAIL_CONF, $new_content) !== FALSE);
    }

    function loadConfiguration($extension)
    {
        foreach (file(ASTERISK_VOICEMAIL_CONF) as $l) {
            list($vmext, $vmparam) = $this->_parseVoicemailContext(trim($l));
            if (!is_null($vmext) && $extension == $vmext)
                return $vmparam;
        }
        return NULL;
    }

    private function _parseVoicemailContext($l)
    {
        $regs = NULL;
        if (!preg_match('/^(\d+)\s*=>\s*(.+)/', $l, $regs)) return array(NULL, NULL);

        $vmext = $regs[1];
        $vmparam = $regs[2];
        $regs = NULL;
        $param = explode(',', $vmparam, 5);
        if (count($param) < 5) return array(NULL, NULL);

        $vmparam = array(
            'voicemail_password'    =>  $param[0],
            'user_name'             =>  $param[1],
            'user_email_address'    =>  $param[2],
            'pager_email_address'   =>  $param[3],
            'user_options'          =>  array(),
        );
        $param = array_map('trim', explode('|', $param[4]));
        foreach ($param as $keyval) {
            $regs = NULL;
            if (preg_match('/^(.+?)=(.*)/', $keyval, $regs)) {
                $vmparam['user_options'][trim($regs[1])] = trim($regs[2]);
            }
        }

        return array($vmext, $vmparam);
    }

    private function _formatVoicemailContext($vmext, $vmparam)
    {
        $param = array(
            isset($vmparam['voicemail_password']) ? $vmparam['voicemail_password'] : '',
            isset($vmparam['user_name']) ? $vmparam['user_name'] : '',
            isset($vmparam['user_email_address']) ? $vmparam['user_email_address'] : '',
            isset($vmparam['pager_email_address']) ? $vmparam['pager_email_address'] : '',
        );
        $o = array();
        foreach ($vmparam['user_options'] as $k => $v) $o[] = "$k=$v";
        $param[] = implode('|', $o);
        return "$vmext => ".implode(',', $param)."\n";
    }
}
