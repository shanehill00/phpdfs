<?php
$graph = require 'barGraph.php';

$datay2 = array(
    303,
    299,
    335,
    298,
    284,
    0,
    0,
    0,
    0,
    0,
    758,
    763,
    764,
    782,
    782,
);

$bplot2 = new BarPlot($datay2);
$bplot2->SetFillColor('blue');
$bplot2->SetLegend('PHPDFS');

$graph->Add($bplot2);

$graph->title->Set("Objects Moved when the weight is changed on second cluster from 2 to 4");

$graph->Stroke();
