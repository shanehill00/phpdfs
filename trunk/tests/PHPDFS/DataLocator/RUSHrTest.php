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

/**
 * @package    DataLocator
 * @subpackage UnitTests
 *   
 * @author     Shane Hill <shanehill00@gmail.com>
 *
 */

/**
 * Test helper
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Framework/IncompleteTestError.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/Runner/Version.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';

require_once 'PHPDFS/DataLocator/RUSHr.php';
/**
 * @package    DataLocator
 * @subpackage UnitTests
 */
class PHPDFS_DataLocator_RUSHrTest extends PHPUnit_Framework_TestCase
{
    /**
     * holds the GXC_VO that is used for the tests
     *
     * @var GXC_VO
     */
    private $data_config = null;
    
    public function setUp(){
        $this->data_config = require 'cluster_config.php';
    }

    /**
     * Tests that we get a DataLocator_HonickyMiller object and that it functions as expected
     */
    public function testInstance()
    {
        $hm = new PHPDFS_DataLocator_RUSHr( $this->data_config );
        $this->assertType( 'PHPDFS_DataLocator_RUSHr', $hm  );
    }

   /**
    * Tests that we throw a DataLocator_Exception when the cluster count is negative .
    * We accomplish this by passing in a negative cluster count 
    */
    public function testThrowLocatorException()
    {
        $badConf['clusters'] = array();
        try{
            $hm = new PHPDFS_DataLocator_RUSHr( $badConf );
            $this->fail("successfully instantiated the locator when we should have failed");
        } catch( PHPDFS_DataLocator_Exception $e){
            $this->assertType('PHPDFS_DataLocator_Exception', $e);
        }
    }
    
    /**
    * Test that we consistently get the same data node for the same id.
    */
    public function testFindNode(){
        $uuid = 'random_file_name';
        $replicaNo = 2;

        $hm = new PHPDFS_DataLocator_RUSHr( $this->data_config );

        $node = $hm->findNode( $uuid, $replicaNo );
        $nodeHost = $node['proxyUrl'];
        // now we repeat the operation 10 times and see that we get the same node back each time
        $N = 10;
        for($i = 0; $i < $N; $i++){
            $node2 = $hm->findNode( $uuid, $replicaNo );
            $nodeHost2 = $node2['proxyUrl'];
            $this->assertNotEquals($nodeHost,'',"nodeHost is empty.  bad joos joos!");
            $this->assertNotEquals($nodeHost2,'',"nodeHost2 is empty.  bad joos joos!");
            $this->assertTrue( $nodeHost == $nodeHost2, "failed consistently fetching a node got $nodeHost == $nodeHost2" );
        }
    }

    /**
     * this test checks that we never place replicas or original on the same server
     *
     * we loop n times and generate a uniqid and for each id we get three urls for replicas
     */
    public function testReplication(){
        $numClusters = 1;
        $nodesPerCluster = 3;
        $replicaCount = 3;
        $clusterToWeight = 0;
        $weight = 1;

        $hm = new PHPDFS_DataLocator_RUSHr(
            $this->makeConfig($numClusters, $nodesPerCluster, $replicaCount, $clusterToWeight, $weight)
        );

        $totalTime = 0;
        $iterations = 10000;
        for( $n = 0; $n < $iterations; $n++ ){
            $uuid = uniqid();
            $replicaData = array();
            $replicaNodes = array();

            for( $replicaNo = 0; $replicaNo < $replicaCount; $replicaNo++ ){
                $time = microtime(1);
                $node = $hm->findNode( $uuid, $replicaNo );
                $node['replicaNo'] = $replicaNo;
                $totalTime += (microtime(1) - $time);
                $replicaData[ $node['proxyUrl'] ] = $node;
                $replicaNodes[] = $node;
            }

            // now check that all urls are unique
            // by checking thelength of the replica data
            // if for some reason the length of the replicaData array
            // IS NOT equivalent to the replicaCount, then we have a problem
            $this->assertEquals( count($replicaData), $replicaCount, print_r(array("replica urls are not all unique!", $uuid, $replicaData, $replicaNodes ),1));

        }
        echo("totalTime: $totalTime\navg time per lookup:".($totalTime/($replicaCount * $iterations)));
    }

    public function makeConfig($numClusters = 1, $numNodes = 1, $replicationDegree = 1, $clusterToWeight = 0, $weight = 1){
        $clusters = array( 'replicationDegree' => $replicationDegree, 'clusters' => array() );

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
            $clusterData['weight'] =  (($n == $clusterToWeight) ? $weight : 1);
            $clusterData['nodes'] = $nodes;

            $clusters['clusters'][] = $clusterData;
        }

        //print_r($clusters);

        return $clusters;
    }


}