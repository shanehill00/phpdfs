<?php

$cats = '"epoch","time","load avg",,,"paging",,"cpu0 usage",,,,,,"cpu1 usage",,,,,,"cpu2 usage",,,,,,"cpu3 usage",,,,,,"cpu4 usage",,,,,,"cpu5 usage",,,,,,"cpu6 usage",,,,,,"cpu7 usage",,,,,,"net/eth0",,"dsk/sda1",,"dsk/sdb",,"dsk/sdc",,"dsk/sdd",,"dsk/sde",,"system",,"memory usage",,,,,"procs",,,"interrupts",,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';
$titles = '"epoch","date/time","1m","5m","15m","in","out","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","usr","sys","idl","wai","hiq","siq","recv","send","read","writ","read","writ","read","writ","read","writ","read","writ","int","csw","used","buff","cach","free",,"run","blk","new","256","257","258","259","260","261","262","263","264","265","266","267","268","269","270","271","272","273","274","275","276","277","278","279","280","281","282","283","284","285","286","287"';


$cats = preg_replace('/"/','',$cats);
$titles = preg_replace('/"/','',$titles);

$cats = split(',', $cats);
$titles = split(',', $titles);

$statFiles = array();
while( count( $cats ) ){
    $cat = array_shift($cats);
    $cat = preg_replace('/\\s+|\//','_',$cat);
    if( $cat ){
        $currentCat = $cat;
    }
    $title = array_shift($titles);
    $title = preg_replace('/\\s+|\//','_',$title);
    $statFiles[] = "stats/$currentCat/$title/stats.csv";
    @mkdir("stats/$currentCat/$title",0777,1);
    file_put_contents("stats/$currentCat/$title/stats.csv", '');
}

$data = file("dstats0.stats");

foreach($data as $line){
    $statData = split(',',$line);
    //print_r($statData);
    foreach( $statFiles as $statFile ){
        $stat = array_shift($statData);
        file_put_contents($statFile, "$stat\n",FILE_APPEND);
    }
}
