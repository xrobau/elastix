<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-14                                               |
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
  $Id: paloSantoSetup.class.php,v 1.1 2012-01-17 11:01:49 Manuel Olvera molvera@palosanto.com Exp $ */

include_once "parseFile.class.php";

class paloSantoSetup{
    var $_DB;
    var $errMsg;
    protected
        $service = 'collectd',
        $elastix_helper = '/usr/bin/elastix-helper',
        $conf_file_path;
    private
        $scommand = 'c3VkbyBjaG1vZA==';

    function paloSantoSetup(&$pDB, $conf_file)
    {
        $this->conf_file_path = $conf_file;
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    public function isRunningService(){
        $output = $retval = NULL;
        if (file_exists('/var/run/collectdmon.pid')) {
            return true;
        }else
            return false;
    }

    function startService(){
        if($this->isRunningService())   $action = 'reload';
        else                            $action = 'start';
        if(!file_exists($this->elastix_helper)){ // for elastix < 2.0.3
            $basicCommand = "sudo /sbin/service $this->service";

            $txt2 = exec("$basicCommand $action 2>&1");
            if(preg_match('@^.+\.\.(done)$| OK @i',$txt2)){
                return TRUE;
            }else
                $this->errMsg = sprintf(_tr('Fail to \'%s\' service'),_tr($action));
            return FALSE;
        }
        exec($this->elastix_helper.' '.$this->service.' --'.$action.' 2>&1', $output, $retval);
        if ($retval != 0) {
            $this->errMsg = implode(' ', $output);
            return FALSE;
        }
        return TRUE;
    }
    /**
     * Stop collectd service
     */
    public function stopService(){
        if(!file_exists($this->elastix_helper)){ // for elastix < 2.0.3
            $basicCommand = "sudo /sbin/service $this->service";

            $txt2 = exec("$basicCommand stop 2>&1");
            if(preg_match('@^.+\.\.(done)$| OK @i',$txt2)){
                return TRUE;
            }else
                $this->errMsg = sprintf(_tr('Fail to \'%s\' service'),_tr('stop'));
            return FALSE;
        }
        exec($this->elastix_helper.' '.$this->service.' --stop 2>&1', $output, $retval);
        if ($retval != 0) {
            $this->errMsg = implode(' ', $output);
            return FALSE;
        }
        return TRUE;

    }

    public function cmpServerKeyFile($server_key){
        $key = $this->getServerKeyFromFile();
        if($key !== FALSE){
            return strcmp($server_key, $key) == 0;
        }
        return FALSE;
    }

    /**
     * Obtiene el valor del server key que recide en el archivo
     * de configuración del collectd
     *
     * @return mixed Una cadena si encuentra la cadena o cadena vacía si no la encuentra en el archivo. FALSE si ocurre algún error al tratar de acceder al archivo
     */
    public function getServerKeyFromFile(){
        if(!file_exists($this->conf_file_path)){
            $this->errMsg = sprintf(_tr('File could not found \'%s\''),$this->conf_path_file);
            return FALSE;
        }
        if(!file_exists($this->elastix_helper)){ // for elastix < 2.0.3
            $k = '';
            $scommand = base64_decode($this->scommand);
            exec("{$scommand} 644 {$this->conf_file_path}");//Asignación temporal
            try{
                $opReadFile = parseFile::NO_DEFAULT | parseFile::SECTIONS | parseFile::COLLECT_REPEATED;
                $oFile = new parseFile($this->conf_file_path, $opReadFile, array(
                    'headers' => array(
                        'start' =>'^\s*<(.+\s.+)>\s*$',
                        'end'   =>'^#?\s*</.+\s*>\s*$',
                    ),
                ));
                //Si encuentra que la clave del servidor elastix no coincide forza la bandera para hacer el cambio en el archivo.
                $k_write_http = '';
                if($oFile->offsetExists('Plugin write_http'))       $k_write_http = 'Plugin write_http';
                elseif($oFile->offsetExists('Plugin "write_http"')) $k_write_http = 'Plugin "write_http"';

                $arr_data = $oFile[$k_write_http];

                if('' != $k_write_http && preg_match('@https?:\/\/(cloud\.elastix\.org|107\.21\.106\.155)/mon\.php\?sk=(.+)"@',key($arr_data),$m))
                    $k = trim($m[2]);
            }catch(InvalidArgumentException $e){//File not found
                $this->errMsg = sprintf($e->getMessage(),$this->conf_path_file);
                $k = FALSE;
            }
            exec("{$scommand} 640 {$this->conf_file_path}");//Deshaciendo la asignación temporal
            return $k;
        }else{
            $k = exec($this->elastix_helper.' '.$this->service.' --getsk 2>&1', $output, $retval);
            if ($retval != 0) {
                $this->errMsg = implode(' ', $output);
                return FALSE;
            }
            return $k;
        }

    }

    /**
     * Almacena una cadena en el archivo de configuración del collectd
     * necesaria para para identificar a el servidor que remite la información
     * al servidor en la nube de elastix
     *
     * @param string $server_key cadena destina a ser almacenada
     * @return boolean TRUE si la cadena es guardada exitosamente; FALSE en caso contrario
     */
    public function saveServerKeyOnFile($server_key){
        if(!file_exists($this->conf_file_path)){
            $this->errMsg = sprintf(_tr('File could not found \'%s\''),$this->conf_file_path);
            return FALSE;
        }
        $server_key = trim($server_key);
        if(!file_exists($this->elastix_helper)){ // for elastix < 2.0.3
            $scommand = base64_decode($this->scommand);
            $bExito = TRUE;
            $bwritable = is_writable($this->conf_file_path) && is_readable($this->conf_file_path);
            if(!$bwritable) exec("{$scommand} 646 {$this->conf_file_path}");//Asignación temporal

            $file = file_get_contents($this->conf_file_path);
            $newData = preg_replace('@(<URL "http)s?(:\/\/)(cloud\.elastix\.org|107\.21\.106\.155)(/mon\.php\?sk=).*(">)@','${1}s${2}cloud.elastix.org${4}'.$server_key.'$5',$file);

            if(preg_match('@^x86_64$@',php_uname('m')))
                $newData = preg_replace('@(Exec ".+" "/usr/)lib(/collectd/elastixactivechannels.php")@','${1}lib64${2}',$newData);

            $fp = fopen($this->conf_file_path, 'w+'); //Overwrite config file
            if ($fp) {
                fwrite($fp,$newData);
                fclose($fp);
            }else{
                $bExito = FALSE;
                $this->errMsg = sprintf(_tr("Could not write on file '%s'"),$this->conf_file_path);
            }

            if(!$bwritable) exec("{$scommand} 640 {$this->conf_file_path}");//Deshaciendo la asignación temporal
            return $bExito;
        }else{
            exec($this->elastix_helper.' '.$this->service.' --savesk '.escapeshellcmd($server_key).' 2>&1', $output, $retval);
            if ($retval != 0) {
                $this->errMsg = implode(' ', $output);
                return FALSE;
            }
            return TRUE;
        }
    }
}
