<?php

// the number of objects to generate and test for data distribution
$totalObjs = isset($argv[1]) ? $argv[1] : 1000;

// which bucket to make hot
$hotDisk = isset($argv[2]) ? $argv[2]  : null;

// the percentage of objects to make hot in the hot bucket
// this is campared to a random number  that is generated between 1 and 100
$heatRate = isset($argv[3]) ? $argv[3]  : 0;


function honickyMillerLocate( $objKey, &$clusters,  $totalClusters = null, $totalDisks = null){

    // totalClusters is an optional param that we can pass as an optimization
    // so that we do not call the count function over and over
    if(!$totalClusters){
        $totalClusters = count($clusters);
    }

    // $totalDisks is an optional param that we can pass as an optimization
    // so that we do not call the array_sum function over and over
    if(!$totalDisks){
        $totalDisks = array_sum($clusters);
    }

    $sumRemainingDisks = $totalDisks;

    // get the starting cluster
    $currentCluster = $totalClusters - 1;

    // node is the integer that represents
    // the disk in our whole array of disks across all clusters
    $node = null;

    /**
     * this loop is an implementation
     * of the honickey-miller algorithm for
     * fast placement and location of objects
     * in a distributed storage system
     */
    $mapped = false;
    // while we are not mapped
    // try and get a mapping
    while( ! $mapped ){

        // prevent an infinite loop, in case there is a bug
        if( $currentCluster < 0 ){
            echo "currentCluster < 0 - $currentCluster\n";
            exit();
        }

        // get the total disks in the cluster we are currently investigating
        $disksInCurrentCluster = $clusters[$currentCluster];

        // get the total disks on the rest of the clusters
        $sumRemainingDisks = $sumRemainingDisks - $disksInCurrentCluster;

        // set the seed to our set id
        srand( $objKey );

        // jump ahead the number of clusters we are investigating
        for($n = 0; $n < $currentCluster; $n++){
            rand();
        }

        // generate the random value that will tell us if we are meant to
        // place this object here
        $rand = rand( 0, ($sumRemainingDisks + $disksInCurrentCluster - 1) );

        if( $rand >= $disksInCurrentCluster ){
            // this means we missed the cluster
            // so we clock back to the next cluster and look there
            $currentCluster = $currentCluster - 1;
        } else {
            $mapped = true;
            // else we have a mapping
            // mapped tells us the absolute value of the disk node we need to place the object
            // so a value of 58 from three clusters could mean 25 in first two clusters and 8 disks in the third
            $node = $sumRemainingDisks + ( $rand % $disksInCurrentCluster );
        }

    }
    return $node;
}



$totalTime = 0;

$bucketConfs = array(

    array(
        'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
        'total_clusters' => 1,
    ),
    array(
        'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
        'total_clusters' => 2,
    ),
    array(
        'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
        'total_clusters' => 3,
    ),

);


// create the objs that we will be placing
$objs = array();
for($n = 0; $n < $totalObjs; $n++){
    $objId = uuid_create();
    // create an object key from the uuid of the object
    // we would just use the UUID but this
    // gets passed to srand which needs an int
    $objKey = ( crc32( $objId ) >> 16 ) & 0x7fff;
    $objs[ $objId ] = array( 'objKey' => $objKey, 'hot' => false);
}

// now we loop on each config
// and take stats on the distribution
// and data movement

foreach($bucketConfs as $currConfig => &$confStats){

    $disksPerCluster = 1;
    $totalClusters = $confStats['total_clusters'];
    $totalDisks = ($disksPerCluster * $totalClusters);

    // build the clusters
    $clusters = array();
    for($n = 0; $n < $totalClusters; $n++){
        $clusters[] = $disksPerCluster;
    }

    foreach( $objs as $objId => &$obj){
        $time = microtime(true);
        $disk = honickyMillerLocate($obj['objKey'], $clusters, $totalClusters, $totalDisks);
        $totalTime += (microtime(true) - $time);

        // create a hot node, we can only do this if we are:
        // in the first iteration on the configs
        // and the chosen bucket is the hot one as identified by the command line param hotBucket
        // and the chosen object is hot as identified by the heatRate command line param
        // the heatRate gives us a percentage of hot objects in the hot node
        $rand = rand(1,100);

        if($currConfig == 0 && ($rand <= $heatRate) && ( $disk == $hotDisk )){
            $obj['hot'] = true;
        }

        // figure out if this object is being moved
        // something can only be moved if the curr config is greater than 0
        $prevDisk = null;

        if($currConfig > 0){
            $prevTotalClusters = $bucketConfs[ ($currConfig - 1 )]['total_clusters'];
            $prevTotalDisks = ($disksPerCluster * $prevTotalClusters);

            // build the clusters
            $prevClusters = array();
            for($n = 0; $n < $prevTotalClusters; $n++){
                $prevClusters[] = $disksPerCluster;
            }

            $prevDisk = honickyMillerLocate($obj['objKey'], $prevClusters, $prevTotalClusters, $prevTotalDisks);

            if($prevDisk == $disk){
                $prevDisk = null;
            }
        }

        //  now set the stats for the buckets
        if(!isset($confStats['stats']['dist'][$disk]) ){
          $confStats['stats']['dist'][$disk] = 0;
        }
        $confStats['stats']['dist'][$disk]++;

        if($obj['hot']){
            if(!isset($confStats['stats']['hot'][$disk]) ){
                $confStats['stats']['hot'][$disk] = 0;
            }
            $confStats['stats']['hot'][$disk]++;
        }

        if($prevDisk !== null){

            if(!isset($confStats['stats']['moved']['from'][$prevDisk]) ){
                $confStats['stats']['moved']['from'][$prevDisk] = 0;
            }
            $confStats['stats']['moved']['from'][$prevDisk]++;

            if(!isset($confStats['stats']['moved']['to'][$disk]) ){
                $confStats['stats']['moved']['to'][$disk] = 0;
            }
            $confStats['stats']['moved']['to'][$disk]++;

        }
    }
}

echo( "avg lookup: ".($totalTime/$totalObjs)."\n");
print_r( $bucketConfs );