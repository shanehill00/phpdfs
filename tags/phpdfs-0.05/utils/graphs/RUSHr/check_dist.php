<?php
// the number of objects to generate and test for data distribution
$totalObjs = isset($argv[1]) ? $argv[1] : 1000;
$failedDiskArg = isset($argv[2]) ? $argv[2] : 0;
$totalTime = 0;
$lookups = 0;

$bucketConfs = array(
    array(
        'stats' => array( 'dist' => array(), 'failedDisk' => null, 'failed' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
    ),
    array(
        'stats' => array( 'dist' => array(), 'failedDisk' => null, 'failed' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
    ),
    array(
        'stats' => array( 'dist' => array(), 'failedDisk' => null, 'failed' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
    ),
    array(
        'stats' => array( 'dist' => array(), 'failedDisk' => null, 'failed' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
    ),
);


// create the objs that we will be placing
$objs = file("uuids", FILE_IGNORE_NEW_LINES );
/**
$objs = array();
for($n = 0; $n < $totalObjs; $n++){

    $objId = uuid_create();
    // create an object key from the uuid of the object
    // we would just use the UUID but this
    // gets passed to srand which needs an int
    $objs[] = $objId;
}
*/

// now we loop on each config
// and take stats on the distribution
// and data movement
$configs = require 'testConfigs.php';

require_once 'PHPDFS/DataLocator/RUSHr.php';

foreach($bucketConfs as $currConfig => &$confStats){
    $hm = new PHPDFS_DataLocator_RUSHr( $configs['data'][ $currConfig ] );
    echo( "total nodes: ".$hm->getTotalNodes()."\nfailed disk: ". $failedDiskArg."\n" );
    $failedDisk = $failedDiskArg;
    if( $failedDiskArg > $hm->getTotalNodes() ){
        $failedDisk = $hm->getTotalNodes();
    } else if( $failedDiskArg < 0 ) {
        $failedDisk = 0;
    }
    
    $confStats['stats']['failedDisk'] = $failedDisk;
    if( $currConfig > 0 ){
        // make the locator for the previous config
        $hmPrev = new PHPDFS_DataLocator_RUSHr( $configs['data'][ $currConfig - 1 ] );
    }
    foreach( $objs as $objId ){
        $time = microtime(1);
        $disks = $hm->findNodes( $objId );
        $totalTime += (microtime(1) - $time);
        $lookups++;
        $movedFrom = null;
        $movedTo = null;
        if($currConfig > 0){
            $prevDisks = $hmPrev->findNodes( $objId );

            $movedFrom = array_diff($prevDisks, $disks);
            $movedTo = array_diff($disks, $prevDisks);
            
            if( count( $movedFrom ) ){
                //print_r( array( $prevDisks, $disks, $movedFrom, $movedTo) );
                //exit();
                foreach( $movedFrom as $prevDisk ){
                    $toDisk = array_shift($movedTo);
                    if(!isset($confStats['stats']['moved']['from'][$prevDisk]) ){
                        $confStats['stats']['moved']['from'][$prevDisk] = 0;
                    }
                    $confStats['stats']['moved']['from'][$prevDisk]++;

                    if(!isset($confStats['stats']['moved']['to'][$toDisk]) ){
                        $confStats['stats']['moved']['to'][$toDisk] = 0;
                    }
                    $confStats['stats']['moved']['to'][$toDisk]++;
                }

                if( count($movedTo) ){
                    foreach( $movedTo as $toDisk ){
                        if(!isset($confStats['stats']['moved']['to'][$toDisk]) ){
                            $confStats['stats']['moved']['to'][$toDisk] = 0;
                        }
                        $confStats['stats']['moved']['to'][$toDisk]++;
                    }
                }
            }
        }
        $replicaCt = count( $disks );

        for( $replica = 0 ; $replica < $replicaCt; $replica++ ){
            $disk = $disks[$replica];
            if( $failedDisk == $disk ){
                // track replica distribution
                // for a failed disk
                foreach( $disks as $diskNo ){
                    if( $diskNo != $disk ){
                        if(!isset( $confStats['stats']['failed'][$diskNo] ) ){
                          $confStats['stats']['failed'][$diskNo] = 0;
                        }
                        $confStats['stats']['failed'][$diskNo]++;
                    }
                }
            }

            //  now set the stats for the buckets
            if(!isset($confStats['stats']['dist'][$disk]) ){
              $confStats['stats']['dist'][$disk] = 0;
            }
            $confStats['stats']['dist'][$disk]++;

        }
    }
}

print_r( array( $bucketConfs, ( $totalTime / $lookups ) ) );
