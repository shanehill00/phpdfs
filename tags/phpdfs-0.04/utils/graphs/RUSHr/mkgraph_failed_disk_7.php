<?php
$graph = require('barGraph.php');

$datay = array(
                        104,
                        92,
                        74,
                        91,
                        82,
                        174,
                        0,
                        185,
                        172,
                        193,
                        435,
                        422,
                        434,
                        442,
                        436,
                                );

// Create a bar pot
$bplot = new BarPlot($datay);
// Adjust fill color
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);

// Setup the titles
$graph->title->Set("Replica distribution for failed disk 7");
// Display the graph
$graph->Stroke();
