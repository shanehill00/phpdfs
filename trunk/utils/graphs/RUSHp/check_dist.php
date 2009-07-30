<?php
// the number of objects to generate and test for data distribution
$totalObjs = isset($argv[1]) ? $argv[1] : 1000;
$failedDiskArg = isset($argv[2]) ? $argv[2] : 0;

$bucketConfs = array(
    array(
        'stats' => array( 'dist' => array(), 'failedDisk' => null, 'failed' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
    ),
    array(
        'stats' => array( 'dist' => array(), 'failedDisk' => null, 'failed' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
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
    $objs[ $objId ] = array( 'objKey' => $objKey );
}

// now we loop on each config
// and take stats on the distribution
// and data movement
$configs = require 'testConfigs.php';

require_once 'PHPDFS/DataLocator/HonickyMillerR.php';

foreach($bucketConfs as $currConfig => &$confStats){
    $hm = new PHPDFS_DataLocator_HonickyMillerR( $configs['data'][ $currConfig ] );
    echo( $hm->getTotalNodes()."\n". $failedDiskArg."\n" );
    $failedDisk = $failedDiskArg;
    if( $failedDiskArg > $hm->getTotalNodes() ){
        $failedDisk = $hm->getTotalNodes();
    } else if( $failedDiskArg < 0 ) {
        $failedDisk = 0;
    }
    
    $confStats['stats']['failedDisk'] = $failedDisk;
    if( $currConfig > 0 ){
        // make the locator for the previous config
        $hmPrev = new PHPDFS_DataLocator_HonickyMillerR( $configs['data'][ $currConfig - 1 ] );
    }
    foreach( $objs as $objId => $obj){
        $objKey = $obj['objKey'];
        $disks = $hm->findNodes( $objKey );
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
            // figure out if this object is being moved
            // something can only be moved if the curr config is greater than 0
            $prevDisk = null;

            if($currConfig > 0){

                $prevDisk = $hmPrev->findNode( $objKey, $replica );

                if($prevDisk == $disk){
                    $prevDisk = null;
                }
            }

            //  now set the stats for the buckets
            if(!isset($confStats['stats']['dist'][$disk]) ){
              $confStats['stats']['dist'][$disk] = 0;
            }
            $confStats['stats']['dist'][$disk]++;

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
}

print_r( $bucketConfs );
