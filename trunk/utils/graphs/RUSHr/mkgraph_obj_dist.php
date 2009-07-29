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
                                    877,
                                    867,
                                    810,
                                    851,
                                    842,
                                    1778,
                                    1668,
                                    1722,
                                    1748,
                                    1647,
                                    3446,
                                    3473,
                                    3417,
                                    3430,
                                    3424,
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
