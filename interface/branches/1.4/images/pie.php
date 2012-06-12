<?php
require_once("../libs/jpgraph/jpgraph.php");
require_once("../libs/jpgraph/jpgraph_pie.php");
require_once("../libs/jpgraph/jpgraph_pie3d.php");

//cargar el idioma
include_once("../libs/misc.lib.php");
load_language("../");

if(ereg("^([[:digit:]]{1,3})%", trim($_GET['du']), $arrReg)) {
    $usoDisco = $arrReg[1];
} else {
    $usoDisco = $_GET['du'];
}
$freeDisco = 100 - $usoDisco;

// Some data
$data = array($usoDisco, $freeDisco);

// Create the Pie Graph.
$graph = new PieGraph(630, 170,"auto");
//$graph->SetShadow();
$graph->SetMarginColor('#fafafa');
$graph->SetFrame(true,'#999999');

$graph->legend->SetFillColor("#fafafa");
//$graph->legend->Pos(0.012, 0.5, "right","center");
$graph->legend->SetColor("#444444", "#999999");
$graph->legend->SetShadow('gray@0.6',4);

//$graph->title->SetColor("#444444");

// Set A title for the plot
$graph->title->Set(utf8_decode($arrLang["Disk usage"]));
//$graph->title->SetFont(FF_VERDANA,FS_BOLD,18);
$graph->title->SetColor("#444444");
$graph->legend->Pos(0.1,0.2);

// Create 3D pie plot
$p1 = new PiePlot3d($data);
//$p1->SetTheme("water");
$p1->SetSliceColors(array("#3333cc", "#9999cc", "#CC3333", "#72394a", "#aa3424")); 
$p1->SetCenter(0.4);
$p1->SetSize(80);

// Adjust projection angle
$p1->SetAngle(45);

// Adjsut angle for first slice
$p1->SetStartAngle(45);

// Display the slice values
//$p1->value->SetFont(FF_ARIAL,FS_BOLD,11);
//$p1->value->SetColor("navy");
$p1->value->SetColor("black");

// Add colored edges to the 3D pie
// NOTE: You can't have exploded slices with edges!
$p1->SetEdge("black");

$p1->SetLegends(array(utf8_decode($arrLang["Used space"]),utf8_decode($arrLang["Free space"])));

$graph->Add($p1);
$graph->Stroke();

?>