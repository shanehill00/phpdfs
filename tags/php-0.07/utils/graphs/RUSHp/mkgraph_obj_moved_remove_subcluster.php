<?php
$graph = require 'barGraph.php';

$datay = array(
        842,
        864,
        851,
        882,
        865,

        1733,
        1713,
        1662,
        1674,
        1653,
        
        2995,
        2888,
        2935,
        2920,
        3016,
);

$bplot = new BarPlot($datay);
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);

$graph->title->Set("Objects Moved when the first sub-cluster is removed");
$graph->Stroke();
