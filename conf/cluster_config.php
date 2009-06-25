<?php
/**
$clusters = array( 'replicationDegree' => 3, 'clusters' => array() );

$numNodes = 3;
$numClusters = 2;

$diskNo = 0;
for( $n = 0; $n < $numClusters; $n++){
    $nodes = array();
    for( $i = 0; $i < $numNodes; $i++ ){
        $nodes[] =
            array(
                'proxyUrl' => "http://www.example.com$diskNo/$n/$i/put/your/image/here"
            );
        $diskNo++;
    }
    $clusterData = array();
    $clusterData['weight'] =  (($n == 5) ? 1 : 1);
    $clusterData['nodes'] = $nodes;

    $clusters['clusters'][] = $clusterData;
}

//print_r($clusters);

return $clusters;
*/

return array(
            'pathSeparator' => '/',
            'storageRoot' => '/tmp/testData',
            'tmpRoot' => '/tmp/testData',
            'thisHost' =>  '192.168.0.2:80',
            'replicationDegree' => 2,
            'clusters' => array(
                // cluster 1
                array(
                    'weight' => 1,
                    'nodes' =>array(
                         // cluster 1 node 1 (1)
                         array(
                            'host' => '192.168.0.2:80',
                         ),
                         // cluster 1 node 2 (2)
                         array(
                            'host' => '192.168.0.6:80'
                         ),
                     ),
                 ),
              ),
        );
