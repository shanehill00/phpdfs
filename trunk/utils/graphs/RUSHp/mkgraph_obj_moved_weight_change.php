<?php
$graph = require 'barGraph.php';

$datay2 = array(
        172,
        171,
        176,
        150,
        210,
        363,
        355,
        345,
        303,
        338,
        2838,
        2864,
        2660,
        2828,
        2860,
);

$bplot2 = new BarPlot($datay2);
$bplot2->SetFillColor('blue');
$bplot2->SetLegend('PHPDFS');

$graph->Add($bplot2);

$graph->title->Set("Objects Moved when the weight is changed on last cluster");

$graph->Stroke();
