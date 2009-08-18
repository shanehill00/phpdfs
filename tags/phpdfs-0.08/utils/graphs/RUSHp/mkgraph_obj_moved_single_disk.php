<?php
$graph = require 'barGraph.php';

$datay = array(
                 137,
                 136,
                 132,
                 126,
                 142,

                 267,
                 267,
                 253,
                 310,
                 303,

                 2791,
                 2802,
                 2727,
                 2771,
                 3403,
        );


$bplot = new BarPlot($datay);
$bplot->SetFillColor('blue');
$bplot->SetLegend('PHPDFS');

$graph->Add($bplot);

$graph->Stroke();
