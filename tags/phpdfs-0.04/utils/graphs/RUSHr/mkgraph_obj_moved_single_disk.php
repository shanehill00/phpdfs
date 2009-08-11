<?php
$graph = require 'barGraph.php';

$datay = array(
    877,
    662,
    483,
    290,
    136,
    64,
    57,
    55,
    74,
    64,
    0,
    0,
    0,
    0,
    0,
);


$bplot = new BarPlot($datay);
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);

$graph->title->Set("Objects moved when disk removed from first sub cluster.");

$graph->Stroke();
