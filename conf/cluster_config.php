<?php

return array(

            'pathSeparator' => '/',
            'storageRoot' => '/tmp/testData',
            'tmpRoot' => '/tmp/testData',
            'thisHost' =>  '192.168.0.2:80',
            'disconnectAfterSpooling' => true,
            'locatorClassName' => 'PHPDFS_DataLocator_HonickyMillerR',
            'locatorClassPath' => 'PHPDFS/DataLocator/HonickyMillerR.php',

            //  data config below
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
