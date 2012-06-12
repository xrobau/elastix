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

class ElastixDisplay
{
	private $_fp;		// Conexión a socket de LCDd
	private $_titulo;	// Cadena mostrada como título
	private $_lineas;	// Lista de 3 líneas mostradas en LCD
	private $_menu;		// Menú de aplicaciones a mostrar
	private $_posMenu;	// Posición dentro del menú
	private $oMainLog;

	/**
	 * Constructor de la clase, inicializa el LCD y construye título más 3 líneas
	 */
	function ElastixDisplay($fp, $menu, &$log)
	{
		$this->_fp = $fp;
		$this->_titulo = '';
		$this->_lineas = NULL;
		$this->_menu = $menu;
		$this->oMainLog =& $log;

		// Iniciar el GUI del LCD
		$connStr = $this->enviar_comando('hello');

		// connect LCDproc 0.5.2 protocol 0.3 lcd wid 20 hgt 4 cellwid 6 cellhgt 8
		$regs = NULL;
		if (!preg_match('/^connect LCDproc .* wid ([[:digit:]]+) hgt ([[:digit:]]+)/', $connStr, $regs)) {
			throw new Exception('ProtocolError: No se reconoce cadena de conexión: '.$connStr);
		}
		$scrWidth = $regs[1];
		$scrHeight = $regs[2] - 1;
		$this->_lineas = array();

		$listaCmd = array(
			"client_set name {Prueba}",
			"screen_add tail",
			"screen_set tail name {Tail archivo}",
			"client_add_key Left",
			"client_add_key Right",
			"client_add_key Up",
			"client_add_key Down",
			"client_add_key Enter",
			"widget_add tail title title",	// <--- devuelve 'listen tail' en vez de success
		);
		for ($i = 0; $i < $scrHeight; $i++) {
			$this->_lineas[$i] = '';
			$indice = $i + 2;
			$listaCmd[] = "widget_add tail $indice string";
		}
		foreach ($listaCmd as $sComando) {
			$s = $this->enviar_comando($sComando);
			if ($s != "success\n" && $s != "listen tail\n")
				throw new Exception('ProtocolError: orden ['.$sComando.'] falla - '.$s);
		}
		
		$this->_posMenu = array(
			array(0, 0),	// (primera fila mostrada, fila seleccionada)
		);
		$this->mostrarItem($this->_menu, $this->_posMenu[0]);
		
		stream_set_timeout($this->_fp, 3, 0);
	}
	
	function leerEvento()
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

		// Localizar item actualmente mostrado
		$pos = $this->_posMenu;
		$menu = $this->_menu;
		$prevMenu = NULL;
		$coord = array_shift($pos);
		while (count($pos) > 0 && isset($menu['menu']) && $coord[1] < count($menu['menu'])) {
			$prevMenu = $menu;
			$menu = $menu['menu'][$coord[1]];
			$coord = array_shift($pos);
		};
		unset($pos); unset($coord);

		$regs = NULL;
		if ($bTimeout && isset($menu['autorefresh']) && $menu['autorefresh']) {
			$this->mostrarItem($menu, $this->_posMenu[count($this->_posMenu) - 1]);
		} elseif (!$bTimeout) {
			if (preg_match('/^key ([[:alnum:]]+)/', $line, $regs)) {

				switch ($regs[1]) {
				case 'Up':
					$coord = array_pop($this->_posMenu);
					if ($coord[1] > 0) $coord[1]--;
					if ($coord[0] > $coord[1]) $coord[0] = $coord[1];				
					array_push($this->_posMenu, $coord);
					$this->mostrarItem($menu, $coord);
					break;
				case 'Down':
					$coord = array_pop($this->_posMenu);
					if (isset($menu['menu']) && $coord[1] < count($menu['menu']) - 1) $coord[1]++;
					if ($coord[1] - $coord[0] >= count($this->_lineas)) $coord[0] = $coord[1] - count($this->_lineas) + 1;
					array_push($this->_posMenu, $coord);
					$this->mostrarItem($menu, $coord);
					break;
				case 'Enter':
				case 'Right':
					$coord = $this->_posMenu[count($this->_posMenu) - 1];
					if (isset($menu['menu'])) {
						$menu = $menu['menu'][$coord[1]];
						$coord = array(0, 0);
						array_push($this->_posMenu, $coord);
						$this->mostrarItem($menu, $coord);
					}
					break;
				case 'Left':
					if (count($this->_posMenu) > 1) {
						array_pop($this->_posMenu);
						$this->mostrarItem($prevMenu, $this->_posMenu[count($this->_posMenu) - 1]);
					}
					break;
/*
				case 'Enter':
					// Evento que repite applet, o nop en menú
					$this->mostrarItem($menu, $this->_posMenu[count($this->_posMenu) - 1]);
					break;*/
				default:
					fwrite(STDERR, "WARN: tecla no manejada {$regs[1]}\n");
					break;
				}
			} elseif ($line == "success\n") {
				// Para que no salga advertencia
			} else {
				$this->oMainLog->output("WARN: evento no manejado $line\n");
			}
		}
	}
	
	private function mostrarItem(&$menu, $coord)
	{
		$this->setTitulo($menu['title']);

		$lineas = array();
		if (isset($menu['menu'])) {
			// Item es un menú a mostrar
			for ($i = $coord[0]; $i < count($menu['menu']) && $i < $coord[0] + count($this->_lineas); $i++) {
				$sFlecha = ($i == $coord[1]) ? chr(16) : ' ';
				$lineas[] = $sFlecha . $menu['menu'][$i]['title'];
			}
		} else {
			// Item es un applet que hay que ejecutar
			$arrSalida = NULL;
			$programa = $menu['applet'];
			if(file_exists($programa)) {
				exec($programa, $arrSalida);
			}

			// Verificar estado de salida del programa
			if (is_array($arrSalida)) {
				for ($i = 0; $i < count($this->_lineas) && $i < count($arrSalida); $i++)
					$lineas[$i] = $arrSalida[$i];	// TODO: implementar scroll
			} else {
				$this->setTitulo('Error');
				$lineas[] = 'Applet not found!';
			}
		}
		while (count($lineas) < count($this->_lineas)) $lineas[] = '';
		foreach ($lineas as $i => $s) $this->setLinea($i, $s);
	}

	private function setTitulo($s)
	{
		if ($s != $this->_titulo) {
			$sComando = "widget_set tail title \"{$s}\"";
			$s = $this->enviar_comando($sComando);
			if ($s != "success\n") throw new Exception('ProtocolError: orden ['.$sComando.'] falla - '.$s);
			$this->_titulo = $s;
		}
	}
	
	private function setLinea($i, $s)
	{
		if ($i < 0 || $i > count($this->_lineas) - 1)
			throw new Exception('RangeError: indice '.$i.' fuera de rango');
		if ($s != $this->_lineas[$i]) {
			$indice = $i + 2;	// Comienza desde segunda línea
			$sComando = "widget_set tail $indice 1 $indice \"{$s}\"";
			$r = $this->enviar_comando($sComando);
			if ($r != "success\n") throw new Exception('ProtocolError: orden ['.$sComando.'] falla - '.$r);
			$this->_lineas[$i] = $s;
		}
	}
	 
	private function enviar_comando($comando)
	{
		$salida = "";
		if (FALSE === fwrite($this->_fp, "$comando\n"))
			throw new Exception('IO Error');
		while(!feof($this->_fp) and !preg_match("/\n/", $salida)) {
			$s = fgets($this->_fp, 1024);
			if ($s === FALSE) throw new Exception('IO Error');
		    $salida .= $s;

			// Lo siguiente es necesario porque es posible que se reporte
			// un evento ANTES de recibir la línea que indica éxito o no
		    $regs = NULL;
		    if (preg_match("/^(key [[:alnum:]]+\n)(.*)$/", $salida, $regs)) {
		    	$salida = $regs[2];
		    }
		}
		if (feof($this->_fp)) throw new Exception('IO Error');
		return $salida;
	}
}

//$sRutaApplets = '/opt/lcdapplets';


class LCDProcess extends AbstractProcess
{
    private $oMainLog;      // Log abierto por framework de demonio
    private $display;
    private $fp;
    
    private $sRutaApplets;
    private $arrMenu;

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
//            echo "$errstr ($errno)<br />\n";
            $this->oMainLog->output("ERR: no se puede conectar a LCDd - ($errno) $errstr");
            $bContinuar = FALSE;
        } else {
            $this->fp = $fp;
        }
        $sRutaApplets = $this->sRutaApplets;
        // TODO: cargar menú de archivo de texto, usar realmente capacidad de menú de LCDd
        $this->arrMenu = array(
	        'title'	=>	'Main Menu',
	        'menu'	=>	array(
		        array(
			        'title'	=>	'Network Parameters',
			        'menu'	=>	array(
				        array(
					        'title'		=>	'IP Address',
					        'applet'	=>	"$sRutaApplets/ip.php",
				        ),
				        array(
					        'title'		=>	'Gateway',
					        'applet'	=>	"$sRutaApplets/gw.php",
				        ),
				        array(
					        'title'		=>	'DNSs',
					        'applet'	=>	"$sRutaApplets/dnss.php",
				        ),
			        ),
		        ),
		        array(
			        'title'	=>	'Hardware Resources',
			        'menu'	=>	array(
				        array(
					        'title'		=>	'CPU Load',
					        'applet'	=>	"$sRutaApplets/cpu.php",
					        'autorefresh'=> TRUE,
				        ),
				        array(
					        'title'		=>	'Memory Usage',
					        'applet'	=>	"$sRutaApplets/mem.php",
				        ),
				        array(
					        'title'		=>	'Hard Drive Usage',
					        'applet'	=>	"$sRutaApplets/hd.php",
				        ),
			        ),
		        ),
		        array(
			        'title'	=>	'PBX Activity',
			        'menu'	=>	array(
				        array(
					        'title'		=>	'Concurr. Channels',
					        'applet'	=>	"$sRutaApplets/ch.php",
				        ),
				        array(
					        'title'		=>	'Voicemail Users',
					        'applet'	=>	"$sRutaApplets/vm.php",
				        ),
			        ),
		        ),
	        ),
        );
        try {
            $this->display = new ElastixDisplay($this->fp, $this->arrMenu, $this->oMainLog);
        } catch (Exception $ex) {
            $this->oMainLog->output("FATAL: Excepción no manejada - ".$ex->getMessage()."\n\n".$ex->getTraceAsString());
            fclose($this->fp);
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

    // Ejecutar la revisión periódica de las llamadas pendientes por timbrar
    function procedimientoDemonio()
    {
        if ($this->fp === FALSE) {
            $errno = $errstr = NULL;
		    $this->fp = @fsockopen("127.0.0.1", 13666, $errno, $errstr, 30);
		    if (!$this->fp) {
			    $this->oMainLog->output("ERR: no se puede conectar a LCDd - ($errno) $errstr");
			    sleep(5);
		    } else {
                try {
                    $this->display = new ElastixDisplay($this->fp, $this->arrMenu, $this->oMainLog);
                } catch (Exception $ex) {
                    $this->oMainLog->output("FATAL: Excepción no manejada - ".$ex->getMessage()."\n\n".$ex->getTraceAsString());
                    fclose($this->fp);
                    $this->fp = FALSE;
                    sleep(5);
                }
		    }
        }
        if ($this->fp) {
            try {
                $this->display->leerEvento();
            } catch (Exception $ex) {
			    fclose($this->fp);
			    $this->display = NULL;
			    $this->fp = FALSE;
			    if ($ex->getMessage() == 'IO Error') {
			    } else {
				    throw $ex;
			    }
            }
        }
        return TRUE;
    }

    // Al terminar el demonio, se desconecta Asterisk y base de datos
    function limpiezaDemonio($signum)
    {
        // Marcar como inválidas las llamadas que sigan en curso
        if ($this->fp !== FALSE) fclose($this->fp);
    }
}
?>
