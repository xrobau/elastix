<?php
/*	Clase que encapsula la manipulacion de los archivos y comandos necesarios para
	configurar la computadora de StickBox
*/

if (is_null(LOCK_SH)) define (LOCK_SH, 1);
if (is_null(LOCK_EX)) define (LOCK_EX, 2);
if (is_null(LOCK_UN)) define (LOCK_UN, 3);

define("SYSMANIP_MSG_1","Changing permissions of ");
define("SYSMANIP_MSG_2","Changing system directory permissions : ");
define("SYSMANIP_MSG_3","Could not do flock(LOCK_SH) on lock file ");
define("SYSMANIP_MSG_4","Reading system files:");
define("SYSMANIP_MSG_5","When writing file:");
define("SYSMANIP_MSG_6","Could not rename file");
define("SYSMANIP_MSG_7","When restarting network service:");
define("SYSMANIP_MSG_8","When modifying wall rules");
define("SYSMANIP_MSG_9","Could not create temporary network rules file");
define("SYSMANIP_MSG_10","When restarting mail service");
define("SYSMANIP_MSG_11","Could not open file");
define("SYSMANIP_MSG_12","for reading");
define("SYSMANIP_MSG_13","for writing");
define("SYSMANIP_MSG_14","The Gateway IP address must be in the same network as one of the network interfases");

class stick_sysmanip
{
	var $error_msg = "";	// Mensaje explicativo del error ocurrido
	var $directorio_resolv  = "/etc";
	var $directorio_sysconf = "/etc/sysconfig";
	var $directorio_qmail   = "/var/qmail/control";
        var $directorio_amavis  = "/etc";
        //var $directorio_postfix = "/etc/postfix";
	var $usuario_proceso = "nobody";
	var $usuario_sistema = "root";

//	var $candado_config_red = "/tmp/candado_config_red.lck";
	var $candado_config_red = "/etc/sysconfig/network";


	/*	Procedimiento para leer la configuracion de red del sistema, y almacenarla en un arreglo
		asociativo que se devuelve como respuesta. El arreglo contiene la siguiente informacion
		sobre la maquina:
			$arreglo["host"]		Nombre simbolico del sistema
			$arreglo["dominio"]		Nombre de dominio del sistema
			$arreglo["wan_ip"]		IP de la interfaz eth0, la interfaz externa
			$arreglo["wan_mascara"]	Mascara de red de la interfaz externa
			$arreglo["lan_ip"]		IP de la interfaz eth1, la interfaz interna
			$arreglo["lan_mascara"]	Mascara de red de la interfaz interna
			$arreglo["dns_ip_1"]	DNS primario de la maquina
			$arreglo["dns_ip_2"]	DNS secundario de la maquina
			$arreglo["gateway_ip"]	IP del gateway asociado a la interfaz externa
			$arreglo["soy_gateway"]	VERDADERO si la maquina funciona como gateway, o FALSO
		La funcion devuelve el arreglo con la informacion requerida, o null en caso de error.
		En caso de error, se asigna el valor de $this->error_msg para reflejar la causa del error.
	*/
	function leer_configuracion_red_sistema()
	{
		$continuar = true;
		$config_red = null;
		$this->error_msg = "";					// Anular cualquier error previo

		// Obtener candado de lectura sobre configuracion de red.
		$candado = fopen($this->candado_config_red, "r");
		if ($candado)
		{
			if (flock($candado, LOCK_SH))
			{
				$usuario_grupo = $this->usuario_proceso.".".$this->usuario_proceso;
				$usuario_viejo = $this->usuario_sistema.".".$this->usuario_sistema;

				// Contenidos de los archivos leidos
				$contenido_network = null;
				$contenido_resolv = null;
				$contenido_sysctl = null;
				$contenido_eth[0] = null;
				$contenido_eth[1] = null;

				// Se cambia el dueo de los directorios /etc/ , /etc/sysconfig/ y /etc/sysconfig/network-scripts/
				// temporalmente a nobody.nobody para poder modificarlos
				if (		$this->privado_chown($usuario_grupo, $this->directorio_resolv)
					&&	$this->privado_chown($usuario_grupo, $this->directorio_sysconf)
                                )
				{
					if ($continuar)
					{
						// Se cambia temporalmente el dueo del archivo /etc/sysconfig/network, y se lee
						if ($this->privado_chown($usuario_grupo, $this->directorio_sysconf."/network"))
						{
							$contenido_network = $this->privado_leer_claves_archivo($this->directorio_sysconf."/network");
							$this->privado_chown($usuario_viejo, $this->directorio_sysconf."/network");
							$continuar = is_array($contenido_network);
						}
						else
						{
							// Ocurrio un problema al cambiar el permiso de /etc/sysconfig/network
							$this->error_msg = SYSMANIP_MSG_1.$this->directorio_sysconf."/network : ".$this->error_msg;
							$continuar = false;
						}
					}

					if ($continuar)
					{
						// Se cambia temporalmente el dueo del archivo /etc/resolv.conf, y se lee
						if ($this->privado_chown($usuario_grupo, $this->directorio_resolv."/resolv.conf"))
						{
							$contenido_resolv = $this->privado_leer_claves_archivo(
								$this->directorio_resolv."/resolv.conf", "[[:blank:]]*");
							$this->privado_chown($usuario_viejo, $this->directorio_resolv."/resolv.conf");
							$continuar = is_array($contenido_resolv);
						}
						else
						{
							// Ocurrio un problema al cambiar el permiso de /etc/resolv.conf
							$this->error_msg = SYSMANIP_MSG_1.$this->directorio_resolv."/resolv.conf : ".$this->error_msg;
							$continuar = false;
						}
					}

					if ($continuar)
					{
						// Se cambia temporalmente el dueo del archivo /etc/sysctl.conf, y se lee
						if ($this->privado_chown($usuario_grupo, $this->directorio_resolv."/sysctl.conf"))
						{
							$contenido_sysctl = $this->privado_leer_claves_archivo(
								$this->directorio_resolv."/sysctl.conf");
							$this->privado_chown($usuario_viejo, $this->directorio_resolv."/sysctl.conf");
							$continuar = is_array($contenido_sysctl);
						}
						else
						{
							// Ocurrio un problema al cambiar el permiso de /etc/resolv.conf
							$this->error_msg = SYSMANIP_MSG_1.$this->directorio_resolv."/sysctl.conf : ".$this->error_msg;
							$continuar = false;
						}
					}

					

					// Restaurar los permisos de los directorios
					$this->privado_chown($usuario_viejo, $this->directorio_sysconf);
					$this->privado_chown($usuario_viejo, $this->directorio_resolv);
				}
				else
				{
					$this->error_msg = SYSMANIP_MSG_2.$this->error_msg;
					$continuar = false;
				}

				// Liberar el candado sobre la configuracion de red
				flock($candado, LOCK_UN);

				// Interpretar la configuracion indicada
				if ($continuar)
				{
					$config_red = array();
					$config_red["soy_gateway"] = false;

					// Configuraciones de /etc/resolv.conf
					$config_red["dns_ip_1"] = $this->privado_get_valor($contenido_resolv, "nameserver", 0);
					$config_red["dns_ip_2"] = $this->privado_get_valor($contenido_resolv, "nameserver", 1);

					// Configuraciones de /etc/sysconfig/network
					$config_red["gateway_ip"] = $this->privado_get_valor($contenido_network, "GATEWAY");
					$nombre_completo = $this->privado_get_valor($contenido_network, "HOSTNAME");
					$tokens_host = explode(".", $nombre_completo);
					$config_red["host"] = array_shift($tokens_host);
					$config_red["dominio"] = implode(".", $tokens_host);

					// Configuraciones de /etc/sysctl.conf
					$config_red["soy_gateway"] = ((int)($this->privado_get_valor($contenido_sysctl, "net.ipv4.ip_forward")) != 0);

                                
				}
			}
			else
			{
				$this->error_msg =SYSMANIP_MSG_3;
				$continuar = false;
			}
			fclose($candado);
		}
		else $this->error_msg = SYSMANIP_MSG_3;
		return $config_red;
	}

	/*	Procedimiento para escribir la configuracin de red del sistema en los archivos de configuracin, a partir
		del arreglo indicado en el par�etro. El arreglo indicado en el par�etro debe de tener los siguientes
		elementos:
			$arreglo["host"]		Nombre simbolico del sistema
			$arreglo["dominio"]		Nombre de dominio del sistema
			$arreglo["dns_ip_1"]	DNS primario de la maquina
			$arreglo["dns_ip_2"]	DNS secundario de la maquina
			$arreglo["gateway_ip"]	IP del gateway asociado a la interfaz externa
			$arreglo["soy_gateway"]	VERDADERO si la maquina funciona como gateway, o FALSO
		La funcin devuelve VERDADERO en caso de �ito, FALSO en caso de error.
	*/
        function escribir_configuracion_red_sistema($config_red){
            
             $bValido=TRUE;
             $msg="";
             
             //Se debe validar el gateway, si no pertenece a la red de ninguna de las tarjetas existentes se debe devolver error
              /* if(!$this->validar_ip_gateway($config_red['gateway_ip'])){
                  $this->error_msg=SYSMANIP_MSG_14;
                  return FALSE;
               }
             */
             //para modificar el archivo /etc/sysconfig/network-----------------------------------------------------
             $hostname=$config_red['host'].".".$config_red['dominio'];             
             $arr_archivos[]=array("dir"=>"/etc/sysconfig","file"=>"network","separador"=>"=","regexp"=>"[[:blank:]]*=[[:blank:]]*","reemplazos"=>array("HOSTNAME"=>$hostname));
             
             //para setear los dns en /etc/resolv.conf--------------------------------------------------------------
             $dns_ip_1 =$config_red['dns_ip_1'];
             $dns_ip_2 =$config_red['dns_ip_2'];
             $arr_resolv[]="nameserver $dns_ip_1";
               if($dns_ip_2!="" && !is_null($dns_ip_2) && ip_validation($dns_ip_2,$msg)){
                  $arr_resolv[]="nameserver $dns_ip_2";
               }
               
             $arr_archivos[]=array("dir"=>"/etc","file"=>"resolv.conf","separador"=>" ","regexp"=>"[[:blank:]]*","reemplazos"=>$arr_resolv,"overwrite"=>TRUE);
            
             //para setear /etc/hosts------------------------------------------------------------+------------------
             //$arr_hosts=array("127.0.0.1"=>"localhost.localdomain localhost $hostname $config_red[host]");
             //$arr_archivos[]=array("dir"=>"/etc","file"=>"hosts"      ,"separador"=>"\t","regexp"=>"[[:blank:]]*","reemplazos"=>$arr_hosts);
             
             //para setear /etc/sysctl.conf-------------------------------------------------------------------------
             $arr_sysctl=array("net.ipv4.ip_forward"=>($config_red["soy_gateway"]) ? "1" : "1");
             $arr_archivos[]=array("dir"=>"/etc","file"=>"sysctl.conf","separador"=>"=","regexp"=>"[[:blank:]]*=[[:blank:]]*","reemplazos"=>$arr_sysctl);
                 
                 foreach($arr_archivos as $archivo){
                     $overwrite=FALSE;  //Si esta true escribe las lineas de arr_reemplazos directamente en el archivo, sin buscar las claves
                     $conf_file=new paloConfig($archivo['dir'],$archivo['file'],$archivo['separador'],$archivo['regexp']);
                       
                        if(array_key_exists("overwrite",$archivo) && $archivo['overwrite']==TRUE)
                           $overwrite=TRUE;
                     $bool=$conf_file->escribir_configuracion($archivo['reemplazos'],$overwrite);
                     $bValido*=$bool;
                     
                        if(!$bool){
                          $this->error_msg=$conf_file->error_msg;
                          break;
                        }
                 }
                 
                 
                 if ($bValido){
                    $comando = "/sg/bin/sudo -u root /sg/scripts/network_restart $hostname";
                    $mensaje = `$comando 2>&1`;
                 }
                 
            return $bValido;
        }
        
        
        function validar_ip_gateway($ip_gateway){
         /* $bValido=FALSE;
          
          
          
             $arr_interfases=obtener_interfases_red_fisicas();
               foreach($arr_interfases as $dev=>$datos){
                  $arr_ip=explode(".",$datos["Inet Addr"]);
                  $arr_mask=explode(".",$datos["Mask"]);
                  $ip=sprintf("%x", ($arr_ip[0] << 24) | ($arr_ip[1] << 16) | ($arr_ip[2] << 8) | ($arr_ip[3]));
                  
          
                  $mask=sprintf("%x",($arr_mask[0] << 24) | ($arr_mask[1] << 16) | ($arr_mask[2] << 8) | ($arr_mask[3]));
                  
                  $dif=$ip^$mask;
           
               }
          return $bValido;*/
          return TRUE;
        }
        
       
	/*	Funcion que encapsula la llamada a chown en un procedimiento de PHP,
		devuelve VERDADERO si el comando se ejecuta correctamente, FALSO si no.
	 */
	function privado_chown($usuario, $ruta)
	{   
		$cmd_chown = escapeshellcmd("/sg/bin/sudo -u root chown $usuario $ruta");
        	$mensaje = `$cmd_chown 2>&1`;
		if ($mensaje != "")
		{
			$this->error_msg = $mensaje;
			return false;
		}
		else return true;
	}

	/*	Funcion que extrae claves de un archivo que contiene claves de la forma CLAVE=VALOR,
		y devuelve las claves indicadas en el arreglo indicado como parametro. Si la linea
		leida inicia con numeral, o no se ajusta a CLAVE=VALOR, entonces se asigna al elemento
		del arreglo la cadena sin parsear. De otro modo, se asigna al element del arreglo, una
		tupla cuyo primer elemento es la CLAVE y el segundo es el VALOR.
		Se devuelve un arreglo con el siguiente contenido del archivo en caso de exito
			array(
				array("clave1", "valor1"),
				array("clave2", "valor2"),
				"#un comentario",
				"texto arbitrario",
				...
				array("clave3", "valor3")
				)
		Se devuelve null en caso de fracaso, y se asigna un valor al texto $this->error_msg.
	*/
	function privado_leer_claves_archivo($ruta, $separador = "[[:blank:]]*=[[:blank:]]*")
	{
		$lista_claves = null;

		$archivo = fopen($ruta, "r");
		if ($archivo)
		{
			// Cargar todo el archivo en memoria
			$lista_claves = array();
			while (!feof($archivo))
			{
				$linea_leida = fgets($archivo, 8192);
				if ($linea_leida)
				{
					$linea_leida = chop($linea_leida);
					// Si la linea leida del archivo coincide con la expresion regular, se asigna
					// el arreglo de las expresiones encontradas.
					if (ereg("^([[:alnum:]._]+)".$separador."(.*)$", $linea_leida, $tupla))
					{
						$linea_leida = array();
						$linea_leida["clave"] = $tupla[1];
						$linea_leida["valor"] = $tupla[2];
					}
					$lista_claves[] = $linea_leida;
				}
			}
			fclose($archivo);
		}
		else $this->error_msg = SYSMANIP_MSG_11.'$ruta'.SYSMANIP_MSG_12;
		return $lista_claves;
	}

	/*	Funcion que escribe el contenido de la lista de claves devuelta por la funcion
		privado_leer_claves_archivo(),
	*/
	function privado_escribir_claves_archivo($ruta, $lista_claves, $separador = "=")
	{
		$exito = false;
		$archivo = fopen($ruta, "w");
		if ($archivo)
		{
			// Para cada linea, verificar si es arreglo o cadena
			foreach ($lista_claves as $linea)
			{
				if (is_array($linea))
					fputs($archivo, $linea["clave"].$separador.$linea["valor"]."\n");
				else fputs($archivo, $linea."\n");
			}

			fclose($archivo);
			$exito = true;
		}
		else $this->error_msg = SYSMANIP_MSG_11.'$ruta'.SYSMANIP_MSG_13;
		return $exito;
	}

	function privado_indice_clave(&$lista, $clave, $saltar = 0)
	{
		$posicion = null;
		$i = 0;
		foreach ($lista as $indice => $contenido)
		{
			if (is_array($contenido) && $contenido["clave"] == $clave)
			{
				if ($i == $saltar) $posicion = $indice;
				$i++;
			}
		}
		return $posicion;
	}
	function privado_get_valor(&$lista, $clave, $saltar = 0)
	{
		$posicion = $this->privado_indice_clave($lista, $clave, $saltar);
		if (!is_null($posicion))
			return $lista[$posicion]["valor"];
		else return null;
	}
	function privado_set_valor(&$lista, $clave, $valor, $saltar = 0)
	{
		$posicion = $this->privado_indice_clave($lista, $clave, $saltar);
		if (is_null($posicion))
		{
			$tupla["clave"] = $clave;
			$tupla["valor"] = $valor;
			$lista[] = $tupla;
		}
		else $lista[$posicion]["valor"] = $valor;
	}

	// Procedimiento para descomponer la salida de iptables-save en una estructura examinable
	function privado_parsear_iptables_save($texto)
	{
		$lista_contenido = explode("\n", $texto);	// Partir el texto en los saltos de l�ea

		// Remover las lineas de comentarios del archivo
		function remover_comentario($linea)
		{
			return (trim($linea) != "" && substr($linea, 0, 1) != "#");
		}
		$lista_contenido = array_filter($lista_contenido, "remover_comentario");

		// Agrupar las lineas de texto debajo de las entradas que inician con asterisco
		$clasificacion = array(); $grupo = array(); $entrada_asterisco = "";
		foreach ($lista_contenido as $linea)
		{
			if (substr($linea, 0, 1) == "*")	// Se encontro entrada de asterisco
			{
				if ($entrada_asterisco != "")
				{
					$clasificacion[$entrada_asterisco] = $grupo;
					$grupo = array();
				}
				$entrada_asterisco = $linea;
			}
			else $grupo[] = $linea;
		}
		$clasificacion[$entrada_asterisco] = $grupo;
		$lista_contenido = null;

		// Para cada entrada de asterisco, agrupar las lineas de texto debajo de las entradas que inician
		// con dos puntos.
		foreach (array_keys($clasificacion) as $filtro)
		{
			$subclasificacion = array(); $grupo = array(); $entrada_tabla = "";
			foreach ($clasificacion[$filtro] as $linea)
			{
				if (substr($linea, 0, 1) == ":")
				{
					if ($entrada_tabla != "")
					{
						list($tabla, $politica, $numero1, $numero2) = sscanf($entrada_tabla, "%s %s [%d:%d]");
						$subclasificacion[$tabla]["POLICY"] = $politica;
						$subclasificacion[$tabla]["COUNTERS"] = array($numero1, $numero2);
					}
					$entrada_tabla = $linea;
				}
				// Acumular las entradas que comienzan con '-' para el grupo de reglas
				else if (substr($linea, 0, 1) == "-") $grupo[] = $linea;
			}
			list($tabla, $politica, $numero1, $numero2) = sscanf($entrada_tabla, "%s %s [%d:%d]");
			$subclasificacion[$tabla]["POLICY"] = $politica;
			$subclasificacion[$tabla]["COUNTERS"] = array($numero1, $numero2);
			$subclasificacion["RULES"] = $grupo;

			$clasificacion[$filtro] = $subclasificacion;
		}

		return $clasificacion;
	}

	// Procedimiento para recomponer una estructura en un comando reconocible por iptables-restore
	function privado_construir_iptables_restore($contenido)
	{
		$texto = "";

		foreach ($contenido as $filtro => $lista_tablas)
		{
			if(!empty($filtro)) {

					$texto .= "$filtro\n";
				foreach ($lista_tablas as $tabla => $info_tabla)
				{
					if ($tabla != "RULES")
						$texto .= $tabla." ".$info_tabla["POLICY"]." [".$info_tabla["COUNTERS"][0].":".$info_tabla["COUNTERS"][1]."]\n";
					else	foreach ($info_tabla as $linea)
					{
						if ($linea != "") $texto .= "$linea\n";
					}
				}
				$texto .= "COMMIT\n";
			}
		}
		return $texto;
	}
}

//Funciones fuera de la clase

/* Las siguientes funciones se usan para localizar y reemplazar los nombres de hosty dominio
                                           en los archivos de configuracion donde aparecen como parte de listas */
function reemplazar_nombres_lista($lista_hosts, $arreglo_reemplazo)
{
   // Reemplazar la cadena de hosts con su representacion arreglo
   $lista_hosts = split("[[:blank:]]+", $lista_hosts);
   // Para cada uno de los hosts, se prueba cada uno de los reemplazos en $arreglo_reemplazo
       foreach ($lista_hosts as $indice => $nombre_host)
       {
           $reemplazo_encontrado = false;
              foreach ($arreglo_reemplazo as $tupla_reemplazo)
              {
                  if (!$reemplazo_encontrado && ($nombre_host == $tupla_reemplazo[0]))
                  {
                        $reemplazo_encontrado = true;
                        $lista_hosts[$indice] = $tupla_reemplazo[1];
                  }
              }
      }
   return implode(" ", $lista_hosts);
}

function asegurar_existe_nombre_host($lista_hosts, $nombre)
{
  // Reemplazar la cadena de hosts con su representacion arreglo
  $lista_hosts = split("[[:blank:]]+", $lista_hosts);
    if (!in_array($nombre, $lista_hosts)) $lista_hosts[] = $nombre;
  return implode(" ", $lista_hosts);
}

/* A continuacion se reemplazan todas las ocurrencias del dominio anterior de la maq uina n el nuevo dominio a usar, para poder usar el correo QMail */

function reemplazar_nombre_host(&$contenido_archivo, $arreglo_reemplazo)
{
    foreach ($contenido_archivo as $indice => $linea)
    {
        $reemplazo = false;
           foreach ($arreglo_reemplazo as $tupla_reemplazo)
           {
               if (!$reemplazo && trim($linea) == $tupla_reemplazo[0])
               {
                    $contenido_archivo[$indice] = $tupla_reemplazo[1];
                    $reemplazo = true;
               }
           }
    }
}


function sobreescribir_variable(&$contenido_archivo,$prefijo,$valor,$quote="")
{      
   $fin=($quote!="'" && $quote!="\"" )?"":";";
     foreach ($contenido_archivo as $indice => $linea)
     {
         $reemplazo = false;
           if(!$reemplazo){
              if(ereg($prefijo,$linea)){
                 $prefijo=str_replace("\\","",$prefijo);
                 $linea_new=$prefijo.$quote.$valor.$quote.$fin;
                    if($linea_new!=$linea){
                       $contenido_archivo[$indice] = $linea_new;
                       $reemplazo = true;
                       break;
                    }
              }
           }
     }
}



function modificar_variable(&$contenido_archivo,$clave,$valor)
{
   foreach ($contenido_archivo as $indice => $linea)
   {
       if(is_array($linea)){
           if(isset($linea['clave']) && isset($linea['valor'])){
                 if($linea['clave']==$clave){
                     $linea['valor']=$valor;
                     $contenido_archivo[$indice]=$linea;
                     return;
                 }
           }
       }
   }
}


function construir_ip_broadcast($ip, $mascara)
{
   $ip = explode(".", $ip);
   $mascara = explode(".", $mascara);
      for ($i = 0; $i < 4; $i++) $ip[$i] = ((int)$ip[$i]) | (~((int)$mascara[$i])& 0xFF);
         return implode(".", $ip);
}

function construir_ip_red($ip, $mascara)
{
   $ip = explode(".", $ip);
   $mascara = explode(".", $mascara);
      for ($i = 0; $i < 4; $i++) $ip[$i] = (int)$ip[$i] & (int)$mascara[$i];
   return implode(".", $ip);
}

?>