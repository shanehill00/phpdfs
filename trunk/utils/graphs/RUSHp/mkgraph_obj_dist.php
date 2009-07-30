<?php
$graph = require 'barGraph.php';
$datay = array(
                             857,
                             857,
                             857,
                             857,
                             857,

                             1714,
                             1714,
                             1714,
                             1715,
                             1715,

                             3428,
                             3428,
                             3429,
                             3429,
                             3429,
);

$datay2 = array(
                             855,
                             873,
                             829,
                             842,
                             866,
                             // 4265

                             1737,
                             1657,
                             1683,
                             1694,
                             1735,
                             // 8506

                             3397,
                             3504,
                             3442,
                             3405,
                             3481,
                             // 17,229
);

$bplot = new BarPlot($datay);
$bplot->SetFillColor('orange');
$bplot->SetLegend('Optimal');

$bplot2 = new BarPlot($datay2);
$bplot2->SetFillColor('blue');
$bplot2->SetLegend('PHPDFS');

$gbplot  = new GroupBarPlot (array($bplot ,$bplot2));

$graph->title->Set("Object Distribution");
$graph->Add($gbplot);
$graph->Stroke();
