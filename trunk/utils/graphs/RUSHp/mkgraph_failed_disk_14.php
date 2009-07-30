<?php
$graph = require 'barGraph.php';

$datay = array(
                            287,
                            294,
                            276,
                            288,
                            279,
                            555,
                            522,
                            546,
                            541,
                            542,
                            937,
                            892,
                            522,
                            0,
                            521,
                        );


$bplot = new BarPlot($datay);
// Adjust fill color
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);

$graph->title->Set("Replica distribution for failed disk 14");

$graph->Stroke();
