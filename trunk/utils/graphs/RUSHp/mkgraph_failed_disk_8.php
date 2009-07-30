<?php
$graph = require('barGraph.php');

$datay = array(
            57,
            54,
            54,
            68,
            70,
            69,
            135,
            0,
            139,
            74,
            523,
            516,
            517,
            560,
            522,
                        );

$bplot = new BarPlot($datay);
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);
$graph->title->Set("Replica distribution for failed disk 8");
$graph->Stroke();
