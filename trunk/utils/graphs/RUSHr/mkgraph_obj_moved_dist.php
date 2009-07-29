<?php
$graph = require 'barGraph.php';

$datay = array(
        457,
        457,
        457,
        457,
        458,

        914,
        914,
        914,
        914,
        915,

        1828,
        1828,
        1829,
        1829,
        1829,
);

$datay2 = array(
        466,
        445,
        429,
        442,
        458,

        933,
        887,
        908,
        926,
        850,

        1852,
        1837,
        1802,
        1853,
        1847,
                                       );

$bplot = new BarPlot($datay);
$bplot->SetFillColor('orange');
$bplot->SetLegend('Optimal');

$bplot2 = new BarPlot($datay2);
$bplot2->SetFillColor('blue');
$bplot2->SetLegend('PHPDFS');

$gbplot  = new GroupBarPlot (array($bplot ,$bplot2));

$graph->Add($gbplot);

// Setup the titles
$graph->title->Set("Objects moved when a sub cluster is added");
$graph->Stroke();
