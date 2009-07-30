<?php
include ("jpgraph.php");
include ("jpgraph_bar.php");

// Create the graph. These two calls are always required
$graph = new Graph(500,500,"auto");
$graph->SetScale("linlin");

// Add a drop shadow
$graph->SetShadow();

// Adjust the margin a bit to make more room for titles
$graph->img->SetMargin(80,60,40,80);

$graph->legend->Pos( 0.20, 0.20, "left" ,"top");
// Setup the titles
$graph->xaxis->title->Set("Disk Id");
$graph->xaxis->title->SetMargin(30);
$graph->xaxis->setTickLabels( array(
    1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
));
$graph->xaxis->hideLastTickLabel(  );

$graph->yaxis->title->Set("Objects on Disk");
$graph->yaxis->title->SetMargin(30);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

return $graph;