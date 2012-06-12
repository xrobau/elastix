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
  $Id: plot.php,v 1.1.1.1 2008-09-23 11:44:00 jvega Exp $ */

// NOTA: Este grafico debe abarcar 26 horas de data
require_once "../../../libs/jpgraph/jpgraph.php";
require_once "../../../libs/jpgraph/jpgraph_line.php";
require_once "../../../libs/paloSantoDB.class.php";
require_once "../../../libs/paloSantoSampler.class.php";

//cargar el idioma
include_once "../../../libs/misc.lib.php";
load_language("../../../");

//language
$lang = get_language("../../../");
$lang_file = "../lang/$lang.lang";

if (file_exists( $lang_file ))
    include_once($lang_file);
else
    include_once("../lang/en.lang");
global $arrLangModule;

//***************************************
require_once "paloSantoExtention.class.php";

$ancho = "700";
$margenDerecho = "100";

//============================================================================

$dsnAsteriskCdr = "mysql://asteriskuser:eLaStIx.asteriskuser.2oo7@localhost/asteriskcdrdb";
$pDB_ast_cdr = new paloDB($dsnAsteriskCdr);//asteriskcdrdb -> CDR
$objPalo_AST_CDR = new paloSantoExtention($pDB_ast_cdr);


$dsnAsteriskDev = "mysql://asteriskuser:eLaStIx.asteriskuser.2oo7@localhost/asterisk";
$pDB_ast = new paloDB($dsnAsteriskDev);//asterisk -> QUEUE
$objPalo_AST     = new paloSantoExtention($pDB_ast);

/*
*   VALORES POR GET
*/

$queue = "";//isset($_GET['queue'])?$_GET['queue']:"";//queue
$dti   = isset($_GET['dti'])?$_GET['dti']:"";//fecha inicio
$dtf   = isset($_GET['dtf'])?$_GET['dtf']:"";//fecha fin

$arrData=array();
$numResults=0;
$arrValue=array();
$arrTimestamp=array();

//============================================================================

include_once "../../../libs/paloSantoQueue.class.php";
$paloQueue = new paloQueue($pDB_ast);
$arrResult = ( strlen($queue) != 0 )?$paloQueue->getQueue($queue):$paloQueue->getQueue();

//$arrResult
//Array ( [0] => Array ( [0] => 2000 [1] => 2000 Recepcion )
//        [1] => Array ( [0] => 5000 [1] => 5000 Soporte )
//        [2] => Array ( [0] => 7000 [1] => 7000 Ventas )  )

/*
*   SE CREA UN 2 ARREGLOS DE TAMAÑO 3*X+1
*   $arrData PARA LOS DATOS DEL EJE Y
*   $arrayX PARA EL ARREGLO DE DATOS PARA EL EJE X
*/

$arrayX = array();
$num = sizeof($arrResult) ;
$i = 0;
for($i = 1; $i <= $num ; $i++){

    $s = $arrResult[$i-1];
    $s_0 = array( 0 => "", 1 => "" );

    if( $i == 1 ){ $arrData[0] = $s_0; $arrayX[0] = ""; }

    $arrData[3*($i-1)+1] = $s;    $arrayX[3*($i-1)+1] = "";
    $arrData[3*($i-1)+2] = $s;    $arrayX[3*($i-1)+2] = $s[0];
    $arrData[3*($i-1)+3] = $s_0;  $arrayX[3*($i-1)+3] = "";

    if($i == $num){ $arrData[3*($i-1)+4] = $s_0; $arrayX[3*($i-1)+4] = ""; }
}

//======================================================

$graph = new Graph($ancho,250);
$graph->SetMargin(50,$margenDerecho,30,40);
$graph->SetMarginColor('#fafafa');
$graph->SetFrame(true,'#999999');

$graph->legend->SetFillColor("#fafafa");
$graph->legend->Pos(0.012, 0.5, "right","center");
$graph->legend->SetColor("#444444", "#999999");
$graph->legend->SetShadow('gray@0.6',4);
$graph->title->SetColor("#444444");

// Especifico la escala
$graph->SetScale("intlin");
$graph->title->Set(utf8_decode($arrLangModule["Number Calls vs Queues"]));
$graph->xaxis->SetLabelFormatCallback('NameQueue');
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetColor("#666666","#444444");

if(is_array($arrData) && count($arrData) > 0 ){
    foreach($arrData as $k => $arrMuestra){
        $arrTimestamp[$k] = $k; /* X */
        
        //$arr = $objPalo_AST_CDR->countQueue( $arrMuestra['id'], $dti, $dtf);
        $arr = $objPalo_AST_CDR->countQueue( $arrMuestra[0], $dti, $dtf);
        $arrValue[$k] = $arr[0]; /* Y */
    }
    
    if( count($arrTimestamp) > 0 ){
        $numResults++;
        $line = new LinePlot($arrValue, $arrTimestamp);
        $line->SetStepStyle();
        $line->SetColor("#00cc00");
        $line->setFillColor("#00cc00");
        $line->SetLegend("# ".$arrLangModule["Calls"]);
        $graph->Add($line);
        $graph->yaxis->SetColor("#00cc00");
    }
}

//======================================================================================

if ($numResults>0)
    $graph->Stroke();
else{
    $titulo=utf8_decode("Gráfico");
    $im = imagecreate(400, 140);
    $background_color = imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 50, 0, "$titulo",$text_color);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 2, 130, 20, $arrLang["No records found"], $text_color);
    if( !empty($msgError) ){
        $msgError="Error data base...";
        imagestring($im, 2, 10, 40, $msgError, $text_color);
    }
    imagepng($im);
    imagedestroy($im);
}

function NameQueue($aVal){
    global $arrayX;
    return $arrayX[$aVal];
}
?>