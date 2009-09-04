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

return array(
            'autoMove' => true,
            'magicDbPath' => '/sw/share/file/magic.mgc',
            'disconnectAfterSpooling' => true,
            'thisProxyUrl' =>  'http://localhost/dfs.php',
            'spoolReadSize' => 2048,
            'inputStream' => 'php://input',
            'debug' => true,
    
            'data' => array(
                array(

                    'locatorClassName' => 'PHPDFS_DataLocator_RUSHr',
                    'locatorClassPath' => 'PHPDFS/DataLocator/RUSHr.php',

                    'storageRoot' => sys_get_temp_dir(),
                    'tmpRoot' => sys_get_temp_dir(),

                    'replicationDegree' => 1,
                    'clusters' => array(
                        // cluster 1
                        array(
                            'weight' => 1,
                            'nodes' =>array(
                                 array('proxyUrl' => 'http://localhost/dfs.php',), // cluster 1 node 1 (1)
                             ),
                         ),
                      ),
                  ),
              ),

              // below are the error and debug message templates
            'debugMsgs' => array(
                'moveDeleteAlike' => 'DEBUG: nodes were alike in delete for %s',
                'moveCreateAlike' => "DEBUG: nodes were alike in create for %s",
                'moveFinished'    => "DEBUG: finished move - checking if we are the last node in the replication chain",
                'autoMove'        => "DEBUG: facilitating auto move",
                'moveStartDelete' => "DEBUG: replica %s : forwarded a move cmd (start) in delete context to %s",
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
