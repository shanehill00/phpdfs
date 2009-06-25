<?php

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

/**
return array(
            'clusters' => array(
                // cluster 1
                array(
                    'weight' => 1,
                    'nodes' =>array(
                         // cluster 1 node 1 (1)
                         array(
                            'proxyUrl' => 'http://www.example.com/put/your/image/here'
                         ),
                         // cluster 1 node 2 (2)
                         array(
                            'proxyUrl' => 'http://www.example2.com/put/your/image/here'
                         ),
                         array(
                            'proxyUrl' => 'http://www.example3.com/put/your/image/here'
                         ),
                         array(
                            'proxyUrl' => 'http://www.example4.com/put/your/image/here'
                         ),
                         array(
                            'proxyUrl' => 'http://www.example5.com/put/your/image/here'
                         ),
                     ),
                 ),
              ),
        );
return array(
            'clusters' => array(
                // cluster 1
                array(
                    'weight' => 1,
                    'nodes' =>array(
                         // cluster 1 node 1 (1)
                         array(
                            'proxyUrl' => 'http://www.example.com/put/your/image/here'
                         ),
                         // cluster 1 node 2 (2)
                         array(
                            'proxyUrl' => 'http://www.example2.com/put/your/image/here'
                         ),
                     ),
                 ),

                // cluster 2
                array(
                    'weight' => 1,
                    'nodes' =>array(
                         // cluster 2 node 1 (3)
                         array(
                            'proxyUrl' => 'http://www.example3.com/put/your/image/here'
                         ),
                         // cluster 2 node 2 (4)
                         array(
                            'proxyUrl' => 'http://www.example4.com/put/your/image/here'
                         ),
                         // cluster 2 node 3 (5)
                         array(
                            'proxyUrl' => 'http://www.example5.com/put/your/image/here'
                         ),
                     ),
                 ),
                 
                // cluster 3
                array(
                    'weight' => 1,
                    'nodes' =>array(
                         // cluster 3 node 1 (6)
                         array(
                            'proxyUrl' => 'http://www.example6.com/put/your/image/here'
                         ),
                         // cluster 3 node 2 (7)
                         array(
                            'proxyUrl' => 'http://www.example7.com/put/your/image/here'
                         ),
                         // cluster 3 node 3 (8)
                         array(
                            'proxyUrl' => 'http://www.example8.com/put/your/image/here'
                         ),
                     ),
                 ),
             ),
        );
 *
 */

