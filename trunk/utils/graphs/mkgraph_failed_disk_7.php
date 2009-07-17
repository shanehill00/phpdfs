<?php
$graph = require('barGraph.php');

$datay = array(
                             64,
                             65,
                             62,
                             69,
                             63,

                             129,
                             0,
                             127,
                             87,
                             91,

                             510,
                             525,
                             482,
                             516,
                             524,
);

$datay2 = array(
                             855,
                             873,
                             829,
                             842,
                             866,
                             // 4265

                             1737,
                             0,
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
$bplot->SetFillColor('blue');
$bplot->SetLegend('Replicas');

$bplot2 = new BarPlot($datay2);
$bplot2->SetFillColor('orange');
$bplot2->SetLegend('Objects');

$gbplot  = new GroupBarPlot (array($bplot ,$bplot2));

$graph->Add($gbplot);
$graph->title->Set("Replica distribution for failed disk 7");
$graph->Stroke();
