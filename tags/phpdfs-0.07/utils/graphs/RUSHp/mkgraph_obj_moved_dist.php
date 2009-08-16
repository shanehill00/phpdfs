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
        432,
        454,
        454,
        436,
        463,

        879,
        888,
        903,
        922,
        937,

        1859,
        1821,
        1809,
        1854,
        1869,
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
$graph->title->Set("Objects Moved when a sub cluster is added");
$graph->Stroke();
