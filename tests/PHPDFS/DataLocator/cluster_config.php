<?php
/**
Copyright (c) 2009, Shane Hill <shanehill00@gmail.com>
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain
      the above copyright notice, this list of conditions
      and the following disclaimer.

    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

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

