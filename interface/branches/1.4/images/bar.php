<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.org                                               |
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
  $Id: bar.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

require_once("../libs/paloSantoGraph.class.php");

$ancho = 90;
$alto  = 20;

$img = new paloGraph($ancho, $alto);

// Progress
if($_GET['prog']>=0 and $_GET['prog']<=1) {
    $prog = $_GET['prog'];
} else {
    $prog = 0;
}

$color_borde="333333";
$color = "638ecf";
$color_relleno = "638ecf";

if($prog>1) {
    $prog=$prog/100;
}
  
$img->crearBarra($prog, 0, 0, $alto-1, $ancho-1, $color_relleno, $color_borde);

$img->genSalida();
?>
