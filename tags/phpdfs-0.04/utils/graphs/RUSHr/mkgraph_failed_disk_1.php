<?php
$graph = require('barGraph.php');

$datay = array(
                            0,
                            41,
                            35,
                            32,
                            36,
                            93,
                            104,
                            97,
                            99,
                            103,
                            222,
                            239,
                            208,
                            225,
                            220,
                                );

// Create a bar pot
$bplot = new BarPlot($datay);
// Adjust fill color
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);

// Setup the titles
$graph->title->Set("Replica distribution for failed disk 1");
// Display the graph
$graph->Stroke();
