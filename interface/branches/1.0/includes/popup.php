<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 PaloSanto Solutions S. A.                    |
// +----------------------------------------------------------------------+
// | Cdla. Nueva Kennedy Calle E #222 y 9na. Este                         |
// | Telfs. 2283-268, 2294-440, 2284-356                                  |
// | Guayaquil - Ecuador                                                  |
// +----------------------------------------------------------------------+
// | Este archivo fuente esta sujeto a las politicas de licenciamiento    |
// | de PaloSanto Solutions S. A. y no esta disponible publicamente.      |
// | El acceso a este documento esta restringido segun lo estipulado      |
// | en los acuerdos de confidencialidad los cuales son parte de las      |
// | politicas internas de PaloSanto Solutions S. A.                      |
// | Si Ud. esta viendo este archivo y no tiene autorizacion explicita    |
// | de hacerlo comuniquese con nosotros, podria estar infringiendo       |
// | la ley sin saberlo.
// +----------------------------------------------------------------------+
// | Autores: Gladys Carrillo B.   <gcarrillo@palosanto.com>              |
// +----------------------------------------------------------------------+
// $Id: popup.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $



$gsRutaBase="..";
include_once "$gsRutaBase/libs/misc.lib.php";
include_once "$gsRutaBase/configs/default.conf.php";
// Load smarty 
require_once "$gsRutaBase/libs/smarty/libs/Smarty.class.php";
$smarty = new Smarty();

$smarty->template_dir = "$gsRutaBase/themes/" . $arrConf['mainTheme'];
$smarty->compile_dir  = "$gsRutaBase/var/templates_c/";
$smarty->config_dir   = "$gsRutaBase/configs/";
$smarty->cache_dir    = "$gsRutaBase/var/cache/";


$id=$_GET["action"];
$sContenido="";
switch($id){
case "display_record":
    $file_path=$_GET["record_file"]; 
    $sContenido=<<<contenido
    <embed src='audio.php?recording=$file_path' width=300, height=20 autoplay=true loop=false></embed><br>
contenido;
    break;
}

$smarty->assign("CONTENT", $sContenido);
//$smarty->assign("items", $items);
$smarty->display("_common/popup.tpl");   
?>