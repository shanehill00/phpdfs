<?php
$graph = require('barGraph.php');

$datay = array(
                            25,
                            0,
                            0,
                            0,
                            17,
                            69,
                            56,
                            62,
                            55,
                            68,
                            264,
                            281,
                            255,
                            260,
                            256,
                        );

$bplot = new BarPlot($datay);
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);
$graph->title->Set("Replica distribution for failed disk 3");
$graph->Stroke();
