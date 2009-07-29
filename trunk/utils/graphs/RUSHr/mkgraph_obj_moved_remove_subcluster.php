<?php
$graph = require 'barGraph.php';

$datay = array(
        877,
        867,
        810,
        851,
        842,
        285,
        260,
        249,
        286,
        254,
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

$graph->title->Set("Objects Moved when the first sub-cluster is removed");
$graph->Stroke();
