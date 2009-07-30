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

require_once 'PHPDFS/DataLocator/HonickyMillerW.php';
/**
 * @package    DataLocator
 * @subpackage UnitTests
 */
class PHPDFS_DataLocator_HonickyMillerTestW extends PHPUnit_Framework_TestCase
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
        $hm = new PHPDFS_DataLocator_HonickyMillerW( $this->data_config );
        $this->assertType( 'PHPDFS_DataLocator_HonickyMillerW', $hm  );
    }

   /**
    * Tests that we throw a DataLocator_Exception when the cluster count is negative .
    * We accomplish this by passing in a negative cluster count 
    */
    public function testThrowLocatorException()
    {
        $badConf['clusters'] = array();
        try{
            $hm = new PHPDFS_DataLocator_HonickyMillerW( $badConf );
            $this->fail("successfully instantiated the locator when we should have failed");
        } catch( PHPDFS_DataLocator_Exception $e){
            $this->assertType('PHPDFS_DataLocator_Exception', $e);
        }
    }
    
    /**
    * Test that we consistently get the same data node for the same id.
    */
    public function testFindNode(){
        //$uuid = uniqid();
        $uuid = 'random_file_name';
        $replicaNo = 1;
        $replicaMax = 3;

        $hm = new PHPDFS_DataLocator_HonickyMillerW( $this->data_config );

        $node = $hm->findNode( $uuid );
        // now we repeat the operation 10 times and see that we get the same node back each time
        $totalTime = 0;
        $N = 10;
        $J = 1000;
        for($j = 0; $j < $J; $j++){
            for($i = 0; $i < $N; $i++){
                $time = microtime(1);
                $node2 = $hm->findNode( $uuid );
                $totalTime += (microtime(1) - $time);

                $nodeHost = $node['proxyUrl'];
                $nodeHost2 = $node2['proxyUrl'];
                $this->assertNotEquals($nodeHost,'',"nodeHost is empty.  bad joos joos!");
                $this->assertNotEquals($nodeHost2,'',"nodeHost2 is empty.  bad joos joos!");
                $this->assertTrue( $nodeHost == $nodeHost2, "failed consistently fetching a node got $nodeHost == $nodeHost2" );
            }
        }
        echo("totalTime: $totalTime\navg time per lookup:".($totalTime/($N * $J) ) );
    }

    protected function getStatsStructure(){
        return array(

            array(
                'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
                'total_clusters' => 1,
            ),
            array(
                'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
                'total_clusters' => 2,
            ),
            array(
                'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
                'total_clusters' => 3,
            ),
            array(
                'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
                'total_clusters' => 4,
            ),
            array(
                'stats' => array( 'dist' => array(), 'hot' => array(), 'moved' => array( 'from' => array(), 'to' => array() ) ),
                'total_clusters' => 5,
            ),

        );
    }

    protected function makeObjects( &$objs ){
        $totalObjs = isset($argv[1]) ? $argv[1] : 1000;
        for($n = 0; $n < $totalObjs; $n++){
            $objId = uniqid();
            // create an object key from the uuid of the object
            // we would just use the UUID but this
            // gets passed to srand which needs an int
            $objKey = ( crc32( $objId ) >> 16 ) & 0x7fff;
            $objs[ $objId ] = array( 'objKey' => $objKey, 'hot' => false);
        }
    }

    /**
     * in this test we demonstrate that objects
     * are both optimally distributed and moved
     * as resources are added
    */

    /**
    public function testDistribution(){
        
        $totalTime = 0;

        $stats = $this->getStatsStructure();


        // create the objs that we will be placing
        $objs = array();
        $this->makeObject( $objs );

        // now we loop on each config
        // and take stats on the distribution
        // and data movement

        $hm = new DataLocator_HonickyMillerW( $this->data_config );
        foreach($stats as $currConfig => &$confStats){

            $disksPerCluster = 1;
            $totalClusters = $confStats['total_clusters'];
            $totalDisks = ($disksPerCluster * $totalClusters);

            // build the clusters
            $clusters = array();
            for($n = 0; $n < $totalClusters; $n++){
                $clusters[] = $disksPerCluster;
            }

            foreach( $objs as $objId => &$obj){
                $objKey = $obj['objKey'];
                $time = microtime(true);
                $disk = $hm->findNode( $objKey );
                $totalTime += (microtime(true) - $time);

                // create a hot node, we can only do this if we are:
                // in the first iteration on the configs
                // and the chosen bucket is the hot one as identified by the command line param hotBucket
                // and the chosen object is hot as identified by the heatRate command line param
                // the heatRate gives us a percentage of hot objects in the hot node
                $rand = rand(1,100);

                if($currConfig == 0 && ($rand <= $heatRate) && ( $disk == $hotDisk )){
                    $obj['hot'] = true;
                }

                // figure out if this object is being moved
                // something can only be moved if the curr config is greater than 0
                $prevDisk = null;

                if($currConfig > 0){
                    $prevTotalClusters = $stats[ ($currConfig - 1 )]['total_clusters'];
                    $prevTotalDisks = ($disksPerCluster * $prevTotalClusters);

                    // build the clusters
                    $prevClusters = array();
                    for($n = 0; $n < $prevTotalClusters; $n++){
                        $prevClusters[] = $disksPerCluster;
                    }

                    $prevDisk = honickyMillerLocate($obj['objKey'], $prevClusters, $prevTotalClusters, $prevTotalDisks);

                    if($prevDisk == $disk){
                        $prevDisk = null;
                    }
                }

                //  now set the stats for the buckets
                if(!isset($confStats['stats']['dist'][$disk]) ){
                  $confStats['stats']['dist'][$disk] = 0;
                }
                $confStats['stats']['dist'][$disk]++;

                if($obj['hot']){
                    if(!isset($confStats['stats']['hot'][$disk]) ){
                        $confStats['stats']['hot'][$disk] = 0;
                    }
                    $confStats['stats']['hot'][$disk]++;
                }

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

        echo( "avg lookup: ".($totalTime/$totalObjs)."\n");
        print_r( $stats );
    }

*/

}