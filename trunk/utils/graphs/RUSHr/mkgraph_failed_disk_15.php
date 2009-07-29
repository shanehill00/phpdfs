<?php
$graph = require 'barGraph.php';

$datay = array(
                    220,
                    250,
                    217,
                    212,
                    186,
                    473,
                    436,
                    424,
                    442,
                    432,
                    881,
                    899,
                    859,
                    917,
                    0,
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
