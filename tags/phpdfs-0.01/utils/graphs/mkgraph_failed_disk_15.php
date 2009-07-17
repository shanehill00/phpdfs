<?php
$graph = require 'barGraph.php';

$datay = array(
    251,
    251,
    248,
    262,
    259,
    // 1271 / 4265 = 29.8% in load

    535,
    568,
    581,
    505,
    524,
    // 2713 / 8506  = 31.9% increase in load

    521,
    933,
    924,
    546,
    0,
    // 2924 / 17,229 = 17% increase in load
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
                             0,
                             // 17,229
);

$bplot = new BarPlot($datay);
$bplot->SetFillColor('blue');
$bplot->SetLegend('Replicas');

$bplot2 = new BarPlot($datay2);
$bplot2->SetFillColor('orange');
$bplot2->SetLegend('Objects');

$gbplot  = new GroupBarPlot (array($bplot ,$bplot2));

$graph->Add($gbplot);

$graph->title->Set("Replica distribution for failed disk 15");
// Display the graph
$graph->Stroke();
