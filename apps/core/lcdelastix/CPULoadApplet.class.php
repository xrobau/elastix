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
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/

class CPULoadApplet
{
    private $_cpu_sample;

    function CPULoadApplet()
    {
        $this->_cpu_sample = $this->obtener_muestra_actividad_cpu();
    }
    
    function update()
    {
    	/*
        $cpu_sample = $this->obtener_muestra_actividad_cpu();
        $fraction_cpu_used = $this->calcular_carga_cpu_intervalo($this->_cpu_sample, $cpu_sample);
        $this->_cpu_sample = $cpu_sample;
        $cpuusage = number_format($fraction_cpu_used * 100.0, 1);

        $loadavg = explode(' ', file_get_contents('/proc/loadavg'));
        while (count($loadavg) > 3) array_pop($loadavg);

        return array(
            "CPU Usage: {$cpuusage}%",
            "Load: ".implode(' ', $loadavg),
        );
        */
        $cpu = $this->updateCPU();
        $load = $this->updateLoad();
        return array(
            "CPU Usage: {$cpu[0]}",
            "Load: {$load[0]}",
        );
    }
    
    function updateCPU()
    {
        $cpu_sample = $this->obtener_muestra_actividad_cpu();
        $fraction_cpu_used = $this->calcular_carga_cpu_intervalo($this->_cpu_sample, $cpu_sample);
        $this->_cpu_sample = $cpu_sample;
        $cpuusage = number_format($fraction_cpu_used * 100.0, 1);

        return array("{$cpuusage}%");
    }
    
    function updateLoad()
    {
        $loadavg = explode(' ', file_get_contents('/proc/loadavg'));
        while (count($loadavg) > 3) array_pop($loadavg);

    	return array(implode(' ', $loadavg));
    }

    private function obtener_muestra_actividad_cpu()
    {
        if (!function_exists('_info_sistema_linea_cpu')) {
            function _info_sistema_linea_cpu($s) { return (strpos($s, 'cpu ') === 0); }
        }
        $muestra = preg_split('/\s+/',
            array_shift(array_filter(file('/proc/stat', FILE_IGNORE_NEW_LINES),
                '_info_sistema_linea_cpu')));
        array_shift($muestra);
        return $muestra;
    }

    private function calcular_carga_cpu_intervalo($m1, $m2)
    {
        $diffmuestra = array_map(array($this, '_info_sistema_diff_stat'), $m1, $m2);
        $cpuActivo = $diffmuestra[0] + $diffmuestra[1] + $diffmuestra[2] + $diffmuestra[4] + $diffmuestra[5] + $diffmuestra[6];
        $cpuTotal = $cpuActivo + $diffmuestra[3];
        return ($cpuTotal > 0) ? $cpuActivo / $cpuTotal : 0;
    }

    /* Método para poder realizar la resta de dos cantidades enteras que pueden
     * no caber en un entero de PHP, pero cuya diferencia es pequeña y puede 
     * caber en el mismo entero. */
    private function _info_sistema_diff_stat($a, $b)
    {
        $aa = str_split("$a");
        $bb = str_split("$b");
        while (count($aa) < count($bb)) array_unshift($aa, '0');
        while (count($aa) > count($bb)) array_unshift($bb, '0');
        while (count($aa) > 0 && $aa[0] == $bb[0]) {
            array_shift($aa);
            array_shift($bb);
        }
        if (count($aa) <= 0) return 0;
        $a = implode('', $aa); $b = implode('', $bb);
        return (int)$b - (int)$a;
    }
}
?>