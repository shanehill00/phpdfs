<?php

return array(
            'autoMove' => true,
            'disconnectAfterSpooling' => true,
            'magicDbPath' => '/sw/share/file/magic',
            'thisProxyUrl' =>  'http://192.168.0.2:80/dfs.php',
            'spoolReadSize' => 2048,
            'debug' => true,
            'data' => array(
                array(
                    'storageRoot' => '/tmp/testData',
                    'tmpRoot' => '/tmp/tempData',
                    'locatorClassName' => 'PHPDFS_DataLocator_HonickyMillerR',
                    'locatorClassPath' => 'PHPDFS/DataLocator/HonickyMillerR.php',

                    //  node and cluster config below
                    'replicationDegree' => 3,
                    'clusters' => array(
                        // cluster 1
                        array(
                            'weight' => 1,
                            'nodes' =>array(
                                1,2,3,4,5
                             ),
                         ),
                      ),
                  ),
                array(
                    'storageRoot' => '/tmp/testData',
                    'tmpRoot' => '/tmp/tempData',
                    'locatorClassName' => 'PHPDFS_DataLocator_HonickyMillerR',
                    'locatorClassPath' => 'PHPDFS/DataLocator/HonickyMillerR.php',

                    //  node and cluster config below
                    'replicationDegree' => 3,
                    'clusters' => array(
                        // cluster 1
                        array(
                            'weight' => 1,
                            'nodes' =>array(
                                1,2,3,4,5
                             ),
                         ),
                        array(
                            'weight' => 2,
                            'nodes' =>array(
                                6,7,8,9,10
                             ),
                         ),
                      ),
                  ),
                array(
                    'storageRoot' => '/tmp/testData',
                    'tmpRoot' => '/tmp/tempData',
                    'locatorClassName' => 'PHPDFS_DataLocator_HonickyMillerR',
                    'locatorClassPath' => 'PHPDFS/DataLocator/HonickyMillerR.php',

                    //  node and cluster config below
                    'replicationDegree' => 3,
                    'clusters' => array(
                        // cluster 1
                        array(
                            'weight' => 1,
                            'nodes' =>array(
                                1,2,3,4,5
                             ),
                         ),
                        array(
                            'weight' => 2,
                            'nodes' =>array(
                                6,7,8,9,10
                             ),
                         ),
                        array(
                            'weight' => 4,
                            'nodes' =>array(
                                11,12,13,14,15
                             ),
                         ),
                      ),
                  ),
                array(
                    'storageRoot' => '/tmp/testData',
                    'tmpRoot' => '/tmp/tempData',
                    'locatorClassName' => 'PHPDFS_DataLocator_HonickyMillerR',
                    'locatorClassPath' => 'PHPDFS/DataLocator/HonickyMillerR.php',

                    //  node and cluster config below
                    'replicationDegree' => 3,
                    'clusters' => array(
                        // cluster 1
                        array(
                            'weight' => 1,
                            'nodes' =>array(
                                1,2,3,4,5
                             ),
                         ),
                        array(
                            'weight' => 2,
                            'nodes' =>array(
                                6,7,8,9,10
                             ),
                         ),
                        array(
                            'weight' => 4,
                            'nodes' =>array(
                                11,12,13,14,15
                             ),
                         ),
                        array(
                            'weight' => 8,
                            'nodes' =>array(
                                16,17,18,19,20
                             ),
                         ),
                      ),
                  ),
             ),

            'debugMsgs' => array(
                'moveDeleteAlike' => 'DEBUG: nodes were alike in delete for %s',
                'moveCreateAlike' => "DEBUG: nodes were alike in create for %s",
                'moveFinished'    => "DEBUG: finished move - checking if we are the last node in the replication chain",
                'moveStartDelete' => "DEBUG: replica %s : forwarded a move cmd (start) in delete context to %s",
                'autoMove'        => "DEBUG: facilitating auto move",
                'sendDeleteForMove' => "DEBUG: replica %s : forwarded a move cmd in delete context to %s",
                'sendStartMove'   => "DEBUG: replica %s : forwarded a move cmd in start context to %s moveConfigIndex: %s configIndex: %s",
                'sendDataToStartMove' => "DEBUG: forwarded %s to start a move cmd in create context to %s",
                'sendDataForMove' => "DEBUG: replica %s : copied data and forwarded a move cmd in create context to %s",
             ),

            'errMsgs' => array(
                'doDeleteForMove' => "ERROR: action: %s - context: %s - name: %s - %s - %s",
                'doCreateForMove' => "ERROR: action: %s - context: %s - name: %s - %s - %s",
                'doStartForMove'  => "ERROR: action: %s - context: %s - name: %s - %s - %s",
                'putData'         => "ERROR: action: %s - name: %s - %s - %s",
                'putForward'      => "ERROR: action: %s - name: %s - %s - %s",
                'deleteData'      => "ERROR: action: %s - name: %s - %s - %s",
                'deleteForward'   => "ERROR: action: %s - name: %s - %s - %s",
                'deleteSend'      => "ERROR: replica %s : forwarding a delete command to %s failed using curl.  curl error code: %s curl error message: %s |||| response: %s",
                'sendDataForPut'  => "ERROR: replica: %s - sending data to %s via curl failed.  curl error code: %s curl error message: %s |||| response: %s",
                'startDeleteForMove' => "ERROR: replica %s : forwarding a move cmd (start) in delete context to %s failed using curl.  curl error code: %s curl error message: %s |||| response: %s",
                'startDeleteForMoveEmptyForward' => "ERROR: could not start a delete for move.  it appears as if the forwardInfo is empty",
                'sendDeleteForMove' => "ERROR: replica %s : forwarding a move cmd in delete context to %s failed using curl.  curl error code: %s curl error message: %s |||| response: %s",
                'sendStartMove'   => "ERROR: replica %s : forwarding a move cmd in start context to %s failed using curl.  curl error code: %s curl error message: %s |||| response: %s",
                'sendDataToStartMove' => "ERROR: replica: %s - sending data for a move in create context to %s via curl failed.  curl error code: %s curl error message: %s |||| response: %s",
                'sendDataForMove' => "ERROR: replica: %s - sending data for a move in create context to %s via curl failed.  curl error code: %s curl error message: %s |||| response: %s",
                'noNodeFound'  => "function: %s - file: %s - line: %s - iterated all of the nodes and could not find a good one.  maybe your configuration is bad. be sure to check all of the proxy urls and make sure they are correct",
            ),

        );
