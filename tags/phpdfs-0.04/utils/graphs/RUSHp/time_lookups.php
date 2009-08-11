<?php
// the number of objects to generate and test for data distribution
$totalObjs = isset($argv[1]) ? $argv[1] : 1000;

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
$configs = makeConfigs(1,39);
$totalConfs = count($configs);
$timings = array();
require_once 'PHPDFS/DataLocator/HonickyMillerR.php';

for($n = 0; $n < $totalConfs; $n++){
    echo("processing conf $n with ".count( $configs[$n]['clusters'] )." clusters.\n");
    $totalTime = 0;
    $hm = new PHPDFS_DataLocator_HonickyMillerR( $configs[$n] );
    foreach( $objs as $objId => $obj){
        $objKey = $obj['objKey'];
        $time = microtime(1);
        $hm->findNode( $objKey );
        $time2 = microtime(1);
        $totalTime += $time2 - $time;
    }
    $timings[$n] = ( $totalTime / $totalObjs );
    echo("avgtime:". $timings[$n]."\n");
}
print_r( $timings );

function makeConfigs( $numConfigs = 1, $subClusters = 1 ){
    $configs = array();
    $replicationDegree = 3;
    for( $num = 0; $num < $numConfigs; $num++ ){
        $configs[$num] = array(
            'replicationDegree' => $replicationDegree,
            'clusters' => array(),
        );
        $weight = 1;
        $disk = 1;
        for( $cluster = 0; $cluster < $subClusters; $cluster++ ){
            $nodes = array();
            for( $n = 0; $n < 5; $n++ ){
                $nodes[] = $disk++;
            }
            $configs[ $num ]['clusters'][$cluster] = array(
                'weight' => $weight,
                'nodes' => $nodes,
            );
            
            if( $weight > 1 ){
                $weight = pow( $weight, 1.1 );
            } else {
                $weight = 2;
            }
        }
    }
    return $configs;
}
