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
  $Id: plot.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

// NOTA: Este grafico debe abarcar 26 horas de data
require_once "../libs/jpgraph/jpgraph.php";
require_once "../libs/jpgraph/jpgraph_line.php";
include_once "../libs/paloSantoDB.class.php";
require_once "../libs/paloSantoSampler.class.php";
//cargar el idioma
include_once "../libs/misc.lib.php";
load_language("../");
if (!extension_loaded('sqlite3')) dl('sqlite3.so');
$msgError='';
$oSampler = new paloSampler();
$arrLines = $oSampler->getGraphLinesById($_GET['id_graph']);
//verificar si no hubo error
if (!empty($oSampler->errMsg)) $msgError=$oSampler->errMsg;
$arrGraph = $oSampler->getGraphById($_GET['id_graph']);
if (!empty($oSampler->errMsg)) $msgError=$oSampler->errMsg;
//- Aunque lo sgte. es redundante (porque ya se hace en sampler.php), quisiera estar seguro de que la base de datos
//- no tiene registros basura

$timestampLimiteBorrarData = time() - 26 * (60 * 60);
$oSampler->deleteDataBeforeThisTimestamp($timestampLimiteBorrarData);

$numLineas = count($arrLines);

$endtime = time();
$starttime = $endtime - 26*60*60;

if($numLineas<=2) {
    $ancho = "570";
    $margenDerecho = "140";
} else {
    $margenDerecho = "230";
    $ancho = "630";
}

$graph = new Graph($ancho,170);
$graph->SetMargin(50,$margenDerecho,30,50);
$graph->SetMarginColor('#fafafa');
$graph->SetFrame(true,'#999999');

$graph->legend->SetFillColor("#fafafa");
$graph->legend->Pos(0.012, 0.5, "right","center");
$graph->legend->SetColor("#444444", "#999999");
$graph->legend->SetShadow('gray@0.6',4);

$graph->title->SetColor("#444444");

// Especifico la escala
$graph->SetScale("intlin", 0, 0, $starttime, $endtime);
//$graph->SetScale("intlin");
$graph->title->Set(utf8_decode($arrLang[$arrGraph['name']]));

$graph->xaxis->SetLabelFormatCallback('TimeCallback');
$graph->xaxis->SetLabelAngle(90);
$numResults=0;//para verificar si hay datos y mostrar el gráfico
// OJO: Bug en JpGrah aquí
//      si uso ticks los ejes Y se posicionan mal
//$graph->xaxis->scale->ticks->Set(3600,1800);

$graph->xaxis->SetColor("#666666","#444444");

$i=0;
foreach($arrLines as $arrLine) {

    $idLine = $arrLine['id'];
    $arrSamples = $oSampler->getSamplesByLineId($idLine);
    $arrValue=array();$arrTimestamp=array();
    $max=NULL; $min=NULL;
    foreach($arrSamples as $k=>$arrMuestra) {
        $arrTimestamp[$k] = $arrMuestra['timestamp'];
        $arrValue[$k]     = (int) $arrMuestra['value'];
        if($max===NULL) $max=$arrMuestra['value'];
        if($min===NULL) $min=$arrMuestra['value'];
        
        if($arrMuestra['value']>=$max) {
            $max=$arrMuestra['value'];
        }

        if($arrMuestra['value']<=$max) {
            $min=$arrMuestra['value'];
        }
    }
    if (count($arrTimestamp)>0){
        $numResults++;
        $line[$idLine] = new LinePlot($arrValue, $arrTimestamp);
        $line[$idLine]->SetStepStyle();

        $line[$idLine]->SetColor($arrLine['color']);
        if($arrLine['line_type']!=0) {
            $line[$idLine]->setFillColor($arrLine['color']);
        }
        $line[$idLine]->SetLegend($arrLang[$arrLine['name']]);

        if($i==0) {
            $graph->Add($line[$idLine]);
            $graph->yaxis->SetColor($arrLine['color']);
        } else {
            $graph->SetYScale($i-1,'lin');
            $graph->AddY($i-1, $line[$idLine]);
            $graph->ynaxis[$i-1]->SetColor($arrLine['color']);
        }
    }
    $i++;
}
if ($numResults>0)
    $graph->Stroke();
else{
    $titulo=(isset($arrLang[$arrGraph['name']]))?utf8_decode($arrLang[$arrGraph['name']]):'';
    $im = imagecreate(400, 140);
    $background_color = imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 50, 0, "$titulo",$text_color);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 2, 130, 20, $arrLang["No records found"], $text_color);
    if (!empty($msgError)){
        $msgError=isset($arrLang[$msgError])?$arrLang[$msgError]:$arrLang[$msgError];
        imagestring($im, 2, 10, 40, $msgError, $text_color);
    }
    imagepng($im);
    imagedestroy($im);
}

function TimeCallback($aVal) {
    return Date('H:i', $aVal);
}
?>