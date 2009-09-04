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

require_once 'PHPDFS/DataLocator/RUSHps.php';
/**
 * @package    DataLocator
 * @subpackage UnitTests
 */
class PHPDFS_DataLocator_RUSHpsTest extends PHPUnit_Framework_TestCase
{
    private $data_config = null;
    
    public function setUp(){
        $this->data_config = require 'cluster_config.php';
    }

    /**
     * Tests that we get a PHPDFS_DataLocator_RUSHps object and that it functions as expected
     */
    public function testInstance()
    {
        $hm = new PHPDFS_DataLocator_RUSHps( $this->data_config );
        $this->assertType( 'PHPDFS_DataLocator_RUSHps', $hm  );
    }

   /**
    * Tests that we throw a DataLocator_Exception when the cluster count is negative .
    * We accomplish this by passing in a negative cluster count 
    */
    public function testThrowLocatorException()
    {
        $badConf['clusters'] = array();
        try{
            $hm = new PHPDFS_DataLocator_RUSHps( $badConf );
            $this->fail("successfully instantiated the locator when we should have failed");
        } catch( PHPDFS_DataLocator_Exception $e){
            $this->assertType('PHPDFS_DataLocator_Exception', $e);
        }
    }
    
    /**
    * Test that we consistently get the same data node for the same id.
    */
    public function testFindNode(){
        $totalTime = 0;
        $hm = new PHPDFS_DataLocator_RUSHps(  $this->data_config );
        $uuid = 'random_file_name';
        $node = $hm->findNode( $uuid );
        // now we repeat the operation 10 times and see that we get the same node back each time
        $J = 1000;
        $N = 10;
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
}