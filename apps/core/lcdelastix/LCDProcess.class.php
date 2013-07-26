<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
/* Codificación: UTF-8
   +----------------------------------------------------------------------+
   | Copyright (c) 1997-2003 PaloSanto Solutions S. A.                    |
   +----------------------------------------------------------------------+
   | Cdla. Nueva Kennedy Calle E #222 y 9na. Este                         |
   | Telfs. 2283-268, 2294-440, 2284-356                                  |
   | Guayaquil - Ecuador                                                  |
   +----------------------------------------------------------------------+
   | Este archivo fuente esta sujeto a las politicas de licenciamiento    |
   | de PaloSanto Solutions S. A. y no esta disponible publicamente.      |
   | El acceso a este documento esta restringido segun lo estipulado      |
   | en los acuerdos de confidencialidad los cuales son parte de las      |
   | politicas internas de PaloSanto Solutions S. A.                      |
   | Si Ud. esta viendo este archivo y no tiene autorizacion explicita    |
   | de hacerlo comuniquese con nosotros, podria estar infringiendo       |
   | la ley sin saberlo.                                                  |
   +----------------------------------------------------------------------+
   | Autores: Alex Villacís Lasso <a_villacis@palosanto.com>              |
   +----------------------------------------------------------------------+
  
   $Id: DialerProcess.class.php,v 1.4 2008/07/19 04:02:29 alex Exp $
*/
require_once('AbstractProcess.class.php');

class LCDProcess extends AbstractProcess
{
    private $oMainLog;      // Log abierto por framework de demonio
    private $_fp;
    
    private $sRutaApplets;
    private $arrMenu;

    private $_dimensionLCD = array(0, 0);   // Dimensiones ancho,alto de LCD
    private $_appletElegido = NULL;         // Applet elegido para mostrar
    private $_lineas = array();             // Salida del último applet mostrado
    private $_posicionSalida = array(0, 0); // Primera posición que debe mostrarse de salida
    private $_listaEventos = array();       // Lista de eventos pendientes por procesar

    private $_internalApplets = array();

    function inicioPostDemonio($infoConfig, &$oMainLog)
    {
        $bContinuar = TRUE;

        // Guardar referencias al log del programa
        $this->oMainLog =& $oMainLog;
        
        // Interpretar la configuración del demonio
        $this->interpretarParametrosConfiguracion($infoConfig);

        $errno = $errstr = NULL;
        $fp = @fsockopen("127.0.0.1", 13666, $errno, $errstr, 30);
        if (!$fp) {
            $this->oMainLog->output("ERR: no se puede conectar a LCDd - ($errno) $errstr");
            $bContinuar = FALSE;
        } else {
            $this->_fp = $fp;
        }

        $this->_internalApplets['cpuload'] = new CPULoadApplet();
        try {
            $this->_iniciarMenuLCD();
        } catch (Exception $ex) {
            $this->oMainLog->output("FATAL: Excepción no manejada - ".$ex->getMessage()."\n\n".$ex->getTraceAsString());
            fclose($this->_fp); $this->_fp = FALSE;
            $bContinuar = FALSE;
        }


        return $bContinuar;
    }

    /* Interpretar la configuración cuyo hash se indica en el parámetro. Los 
     * parámetros de la conexión a la base de datos se recogen, pero no se usan 
     * en este punto. Lo mismo con los parámetros de conexión al Asterisk Manager. 
     */
    private function interpretarParametrosConfiguracion(&$infoConfig)
    {
        $sRutaDB = NULL;
        
        // Recoger los parámetros para la conexión a la base de datos
        $this->sRutaApplets = '/opt/lcdapplets';
        if (isset($infoConfig['lcd']) && isset($infoConfig['lcd']['appdir'])) {
            $this->sRutaApplets = $infoConfig['lcd']['appdir'];
            $this->oMainLog->output('Usando ruta de applets: '.$this->sRutaApplets);
        } else {
            $this->oMainLog->output('Usando ruta de applets (por omisión): '.$this->sRutaApplets);
        }
    }

    function procedimientoDemonio()
    {
        if ($this->_fp === FALSE) {
            $errno = $errstr = NULL;
            $this->_fp = @fsockopen("127.0.0.1", 13666, $errno, $errstr, 30);
            if (!$this->_fp) {
                $this->oMainLog->output("ERR: no se puede conectar a LCDd - ($errno) $errstr");
                sleep(5);
            } else {
                try {
                    $this->_iniciarMenuLCD();
                } catch (Exception $ex) {
                    $this->oMainLog->output("FATAL: Excepción no manejada - ".$ex->getMessage()."\n\n".$ex->getTraceAsString());
                    fclose($this->_fp); $this->_fp = FALSE;
                    sleep(5);
                }
            }
        }
        if ($this->_fp) {
            try {
                //$this->display->leerEvento();
                $this->_manejarEventoLCD();
            } catch (Exception $ex) {
                fclose($this->_fp); $this->_fp = FALSE;
                $this->display = NULL;                
                if ($ex->getMessage() == 'IO Error') {
                } else {
                    throw $ex;
                }
            }
        }
        return TRUE;
    }

    function limpiezaDemonio($signum)
    {
        if ($this->_fp !== FALSE) {
            fclose($this->_fp); $this->_fp = FALSE;
        }
    }

    /**************************************************************************/

    private function _iniciarMenuLCD()
    {
        stream_set_timeout($this->_fp, 3, 0);

        // Iniciar el GUI del LCD
        if (FALSE === fwrite($this->_fp, "hello\n"))
            throw new Exception('IO Error');
        $connStr = fgets($this->_fp, 1024);
        if ($connStr === FALSE) throw new Exception('IO Error');

        // connect LCDproc 0.5.2 protocol 0.3 lcd wid 20 hgt 4 cellwid 6 cellhgt 8
        $regs = NULL;
        if (!preg_match('/^connect LCDproc .* wid ([[:digit:]]+) hgt ([[:digit:]]+)/', $connStr, $regs)) {
            throw new Exception('ProtocolError: No se reconoce cadena de conexión: '.$connStr);
        }
        
        // Dimensiones disponibles, menos la línea del título
        $this->_dimensionLCD[0] = (int)$regs[1];
        $this->_dimensionLCD[1] = (int)$regs[2] - 1;
        $this->_lineas = array();
        $this->_posicionSalida = array(0, 0);
        
        // Los comandos estáticos
        $listaCmd = array(
            'client_set name Elastix',
            'screen_add ScreenOutput', 
            'screen_set ScreenOutput -name NONAME -priority foreground',
            'widget_add ScreenOutput ScreenTitle title',
            'widget_set ScreenOutput ScreenTitle "(no applet)"',
            'client_add_key Left',
            'client_add_key Right',
            'client_add_key Up',
            'client_add_key Down',
            'client_add_key Enter',
        );
        for ($i = 0; $i < $this->_dimensionLCD[1]; $i++) {
            $listaCmd[] = 'widget_add ScreenOutput SC'.($i+1).' string';
        }
        
        $this->_generarArbolMenu();
        
        foreach ($this->arrMenu['menu'] as $menuKey => $menuItem) {
        	$this->_agregarItemMenu('', $menuKey, $menuItem, $listaCmd);
        }
        $listaCmd[] = 'menu_set_main ""';
        $listaCmd[] = 'menu_goto ""';

        foreach ($listaCmd as $sComando) {
            $s = $this->enviar_comando($sComando);
            if ($s != "success\n")
                throw new Exception('ProtocolError: orden ['.$sComando.'] falla - '.$s);
        }
    }

    private function _generarArbolMenu()
    {
        $sRutaApplets = $this->sRutaApplets;

        /* Este es el árbol de menú a mostrar si la pantalla LCD tiene al menos
         * 4 líneas de alto. */
        $this->arrMenu = array(
            'title' =>  'Main Menu',
            'menu'  =>  array(
                'NetworkParameters' => array(
                    'title' =>  'Network Parameters',
                    'menu'  =>  array(
                        'IP' => array(
                            'title'     =>  'IP Address',
                            'applet'    =>  "$sRutaApplets/ip.php",
                        ),
                        'Gateway' => array(
                            'title'     =>  'Gateway',
                            'applet'    =>  "$sRutaApplets/gw.php",
                        ),
                        'DNS' => array(
                            'title'     =>  'DNSs',
                            'applet'    =>  "$sRutaApplets/dnss.php",
                        ),
                    ),
                ),
                'HardwareResources' => array(
                    'title' =>  'Hardware Resources',
                    'menu'  =>  array(
                        'CPU' => array(
                            'title'     =>  'CPU Load',
                            'applet'    =>  array($this->_internalApplets['cpuload'], 'update'),
                            'autorefresh'=> TRUE,
                        ),
                        'Mem' => array(
                            'title'     =>  'Memory Usage',
                            'applet'    =>  "$sRutaApplets/mem.php",
                        ),
                        'HD' => array(
                            'title'     =>  'Hard Drive Usage',
                            'applet'    =>  "$sRutaApplets/hd.php",
                        ),
                    ),
                ),
                'PBXActivity' => array(
                    'title' =>  'PBX Activity',
                    'menu'  =>  array(
                        'Chan' => array(
                            'title'     =>  'Concurr. Channels',
                            'applet'    =>  "$sRutaApplets/ch.php",
                        ),
                        'VM' => array(
                            'title'     =>  'Voicemail Users',
                            'applet'    =>  "$sRutaApplets/vm.php",
                        ),
                    ),
                ),
            ),
        );

        /* Si la pantalla LCD tiene menos de 4 filas, se debe modificar el árbol
         * de menú para que muestre más elementos con menos contenido. */
        if ($this->_dimensionLCD[1] < 3) {
            $this->arrMenu['menu']['NetworkParameters']['title'] = 'Net Params.';
        	$this->arrMenu['menu']['NetworkParameters']['menu'] = array(
                'IPType' => array(
                    'title'     =>  'IP Type',
                    'applet'    =>  "$sRutaApplets/ip.php type",
                ),
                'IPAddr' => array(
                    'title'     =>  'IP Address',
                    'applet'    =>  "$sRutaApplets/ip.php addr",
                ),
                'IPMask' => array(
                    'title'     =>  'IP Mask',
                    'applet'    =>  "$sRutaApplets/ip.php mask",
                ),
                'Gateway' => array(
                    'title'     =>  'Gateway',
                    'applet'    =>  "$sRutaApplets/gw.php gw",
                ),
                'DNS1' => array(
                    'title'     =>  'DNS 1',
                    'applet'    =>  "$sRutaApplets/dnss.php 1",
                ),
                'DNS2' => array(
                    'title'     =>  'DNS 2',
                    'applet'    =>  "$sRutaApplets/dnss.php 2",
                ),
            );
            $this->arrMenu['menu']['HardwareResources']['title'] = 'HW Resources';
            $this->arrMenu['menu']['HardwareResources']['menu'] = array(
                'CPUUsage' => array(
                    'title'     =>  'CPU Usage',
                    'applet'    =>  array($this->_internalApplets['cpuload'], 'updateCPU'),
                    'autorefresh'=> TRUE,
                ),
                'CPULoad' => array(
                    'title'     =>  'CPU Load',
                    'applet'    =>  array($this->_internalApplets['cpuload'], 'updateLoad'),
                    'autorefresh'=> TRUE,
                ),
                'Mem' => array(
                    'title'     =>  'Memory Usage',
                    'applet'    =>  "$sRutaApplets/mem.php compact",
                ),
                'HD' => array(
                    'title'     =>  'Hard Drive Usage',
                    'applet'    =>  "$sRutaApplets/hd.php compact",
                ),
            );            
        }
    }

    private function _agregarItemMenu($itemPadre, $menuKey, &$menuItem, &$listaCmd)
    {
    	$type = isset($menuItem['menu']) ? 'menu' : 'action';
        if ($itemPadre != '') $menuKey = $itemPadre.'_'.$menuKey;
        $cmd = "menu_add_item \"$itemPadre\" $menuKey $type \"{$menuItem['title']}\"";
        if ($type == 'action') $cmd .= ' -menu_result quit';
        $listaCmd[] = $cmd;
        if (isset($menuItem['menu'])) {
        	foreach ($menuItem['menu'] as $submenuKey => $submenuItem)
                $this->_agregarItemMenu($menuKey, $submenuKey, $submenuItem, $listaCmd);
            $listaCmd[] = "menu_add_item \"$menuKey\" {$menuKey}_bak action \"Back\" -menu_result close";
        }
    }

    private function enviar_comando($comando)
    {
        $salida = '';
        //$this->oMainLog->output("DEBUG: --> {{{$comando}}}");
        if (FALSE === fwrite($this->_fp, "$comando\n"))
            throw new Exception('IO Error');
        while(!feof($this->_fp)) {
            $s = fgets($this->_fp, 1024);
            if ($s === FALSE) throw new Exception('IO Error');
            $salida .= $s;

            // Validar que se ha leído una línea completa
            if (strpos($salida, "\n") !== FALSE) {
                //$this->oMainLog->output("DEBUG: <-- {{{$salida}}}");
                
            	// Buscar éxito o fracaso, acumular resto de eventos
                if (strpos($salida, "success") === 0) return $salida;
                if (strpos($salida, "huh?") === 0) return $salida;
                array_push($this->_listaEventos, $salida);
                $salida = '';
            }
        }
        if (feof($this->_fp)) throw new Exception('IO Error');
        return $salida;
    }

    private function _manejarEventoLCD()
    {
        $bTimeout = FALSE;
        $line = "";
        while(!$bTimeout && !feof($this->_fp) and !preg_match("/\n/", $line)) {
            $s = fgets($this->_fp, 1024);
            if ($s === FALSE) {
                $metadata = stream_get_meta_data($this->_fp);
                if (!is_array($metadata)) {
                    throw new Exception('IO Error');
                } else if (!$metadata['timed_out']) {
                    throw new Exception('IO Error');
                } elseif ($metadata['timed_out']) {
                    $bTimeout = TRUE;
                }
            }
            $line .= $s;
        }

        if ($bTimeout && is_array($this->_appletElegido) && 
            isset($this->_appletElegido['autorefresh']) && 
            $this->_appletElegido['autorefresh']) {
        	$this->_ejecutarApplet($this->_appletElegido);
        } elseif (!$bTimeout) {
        	//$this->oMainLog->output("DEBUG: <<< {{{$line}}}");
            $regs = NULL;
            if (!preg_match('/^(\w+) (.+)/', $line, $regs)) return;
            $sEvento = $regs[1];
            $sParamEvento = $regs[2];
            switch ($sEvento) {
            case 'menuevent':
                //$this->oMainLog->output("DEBUG: reconocido evento $sEvento");
                if (preg_match('/select (\S+)/', $sParamEvento, $regs)) {
                    //$this->oMainLog->output("DEBUG: reconocido select {$regs[1]}");
                	$menupath = explode('_', $regs[1]);
                    $m =& $this->arrMenu;
                    while (!is_null($m) && count($menupath) > 0) {
                    	$k = array_shift($menupath);
                        //$this->oMainLog->output("DEBUG: seleccionado item que contiene ".print_r($m, 1));
                        //$this->oMainLog->output("DEBUG: intentando seleccionar rama $k restante es ".print_r($menupath, 1));
                        if ($k == 'bak' && count($menupath) <= 0) {
                        	// Elegido item para regresar a menú anterior
                            unset($m);
                            $m = NULL;
                            break;
                        } elseif (!isset($m['menu'][$k])) {
                        	$this->oMainLog->output("ERR: ruta invalida {$regs[1]}");
                            break;
                        }
                        $m =& $m['menu'][$k];
                    }
                     
                    if (!is_null($m) && isset($m['applet'])) {
                        $this->_posicionSalida = array(0, 0);
                    	$this->_ejecutarApplet($m);
                    }
                }
                break;
            case 'key':
                switch ($sParamEvento) {
                case 'Up':
                    $this->_posicionSalida[1]--;
                    $this->_actualizarSalida();
                    break;
                case 'Down':
                    $this->_posicionSalida[1]++;
                    $this->_actualizarSalida();
                    break;
                case 'Left':
                    $this->_posicionSalida[0]--;
                    $this->_actualizarSalida();
                    break;
                case 'Right':
                    $this->_posicionSalida[0]++;
                    $this->_actualizarSalida();
                    break;
                case 'Enter':
                    // Enter hace mostrar el menú
                    $this->_lineas = array();
                    $this->_posicionSalida = array(0, 0);
                    $this->_actualizarSalida();
                    $this->setTitulo('(no applet)');
                    $this->enviar_comando('menu_goto ""');
                    break;
                }
                break;
            }
        }
    }
    
    private function _ejecutarApplet(&$menu)
    {
        // Item es un applet que hay que ejecutar
        $arrSalida = NULL;
        if (is_array($menu['applet'])) {
            $arrSalida = call_user_func($menu['applet']);
        	$this->_appletElegido =& $menu;
        } else {
            exec($menu['applet'], $arrSalida);
            $this->_appletElegido =& $menu;
        }

        // Verificar estado de salida del programa
        if (is_array($arrSalida)) {
            $this->_lineas = array_map('trim', $arrSalida);
            $this->setTitulo($menu['title']);
        } else {            
            $this->_lineas = array('Applet not found!');
            $this->setTitulo('Error');
        }
        $this->_actualizarSalida();
    }
    
    private function _actualizarSalida()
    {
    	// Validar línea inicial de salida
        if ($this->_posicionSalida[1] + $this->_dimensionLCD[1] > count($this->_lineas))
            $this->_posicionSalida[1] = count($this->_lineas) - $this->_dimensionLCD[1];
        if ($this->_posicionSalida[1] < 0)
            $this->_posicionSalida[1] = 0;
        
        // Validar posición horizontal
        $maxstrlen = array_reduce(array_map('strlen', $this->_lineas), 'max', 0);
        if ($this->_posicionSalida[0] + $this->_dimensionLCD[0] > $maxstrlen)
            $this->_posicionSalida[0] = $maxstrlen - $this->_dimensionLCD[0];
        if ($this->_posicionSalida[0] < 0)
            $this->_posicionSalida[0] = 0;

        // La N-ésima línea está contenida en el widget SC{N} en ScreenOutput
        for ($i = 1; $i <= $this->_dimensionLCD[1]; $i++) {
        	$s = '';
            
            $yindex = ($i - 1) + $this->_posicionSalida[1];
            if ($yindex < count($this->_lineas)) {
            	$s = substr($this->_lineas[$yindex], $this->_posicionSalida[0], $this->_dimensionLCD[0]);
            }
            
            // Mandar a actualizar la salida
            $spos = $i + 1;
            $sComando = "widget_set ScreenOutput SC{$i} 1 $spos \"{$s}\"";
            $r = $this->enviar_comando($sComando);
            if ($r != "success\n") throw new Exception('ProtocolError: orden ['.$sComando.'] falla - '.$r);
        }
    }

    private function setTitulo($s)
    {
        $sComando = "widget_set ScreenOutput ScreenTitle \"{$s}\"";
        $s = $this->enviar_comando($sComando);
        if ($s != "success\n") throw new Exception('ProtocolError: orden ['.$sComando.'] falla - '.$s);
    }

}
?>