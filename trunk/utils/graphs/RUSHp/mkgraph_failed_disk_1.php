<?php
$graph = require('barGraph.php');

$datay = array(
                            0,
                            0,
                            22,
                            27,
                            0,
                            // 49
                            48,
                            66,
                            53,
                            61,
                            70,
                            // 298
                            232,
                            281,
                            299,
                            278,
                            253,
                            // 1343
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
