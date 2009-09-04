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
// start ouput buffering so we can
// control when data is sent to STDOUT
// we do this so we can prevent a failure
// when we test gets and webdfs sets headers
ob_start();
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Framework/IncompleteTestError.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/Runner/Version.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';

require_once 'PHPDFS.php';
/**
 * @package    DataLocator
 * @subpackage UnitTests
 */
class PHPDFS_DataLocator_RUSHrTest extends PHPUnit_Framework_TestCase
{
    
    private $data_config = null;
    
    private function getParamsForPut(){
        return array
        (
            'fileName' => test,
            'pathHash' => '25/98',
            'name' => test,
            'action' => put,
            'replica' => 0,
            'position' => null,
            'configIndex' => 0,
            'moveConfigIndex' => 0,
            'moveContext' => start,
            'getContext' => null,
        );
    }

    private function getParamsForGet(){
        return array
        (
            'fileName' => test,
            'pathHash' => '25/98',
            'name' => test,
            'action' => get,
            'replica' => 0,
            'position' => null,
            'configIndex' => 0,
            'moveConfigIndex' => 0,
            'moveContext' => start,
            'getContext' => null,
        );
    }

    private function getParamsForDelete(){
        return array
        (
            'fileName' => test,
            'pathHash' => '25/98',
            'name' => test,
            'action' => delete,
            'replica' => 0,
            'position' => null,
            'configIndex' => 0,
            'moveConfigIndex' => 0,
            'moveContext' => start,
            'getContext' => null,
        );
    }
    
    public function setUp(){
        $this->data_config = require 'cluster_config.php';
    }

    /**
     * test the out functionality of webdfs
     */
    public function testPut(){
        // create temp file to simulate input
        $tmpFile = tmpfile();
        fwrite($tmpFile,"foo");
        $data = stream_get_meta_data($tmpFile);
        // set the input stream
        $this->data_config['inputStream'] = $data['uri'];

        // put the file
        $params = $this->getParamsForPut();
        $dfs = new PHPDFS($this->data_config, $params );
        $dfs->handleRequest();
        $filePath = join('/', array( $this->data_config['data'][0]['storageRoot'], $params['pathHash'], $params['fileName'] ) );
        $this->assertFileExists( $filePath );
        $this->assertFileEquals( $data['uri'], $filePath );
        unlink( $filePath );
        unlink( $data['uri'] );
    }

    public function testGet(){

        // create temp file to simulate input
        $tmpFile = tmpfile();
        $fileData = "foooooooooooooooooooooobarrrrrrrrrrrrrrrrr";
        fwrite($tmpFile, $fileData);
        $data = stream_get_meta_data($tmpFile);

        // set the input stream
        $this->data_config['inputStream'] = $data['uri'];
        
        // first put the file
        $params = $this->getParamsForPut();
        $dfs = new PHPDFS($this->data_config, $params );
        $dfs->handleRequest();
        $filePath = join('/', array( $this->data_config['data'][0]['storageRoot'], $params['pathHash'], $params['fileName'] ) );
        $this->assertFileExists( $filePath );

        // we have to prevent a failure due to headers already being sent
        // by phpunit
        $out = '';
        if(ob_get_length()){
            $out = ob_get_contents();
            ob_end_clean();
            ob_start();
        }
        
        // now get the file we just put
        $params = $this->getParamsForGet();
        $dfs = new PHPDFS($this->data_config, $params );
        $dfs->handleRequest();

        $out2 = ob_get_contents();
        $this->assertEquals( strlen($out2), strlen($fileData) );
        ob_end_clean();
        echo( $out );
        unlink( $data['uri'] );
        unlink( $filePath );
    }

    public function testDelete(){
        // create temp file to simulate input
        $tmpFile = tmpfile();
        $fileData = "foooooooooooooooooooooobarrrrrrrrrrrrrrrrr";
        fwrite($tmpFile, $fileData);
        $data = stream_get_meta_data($tmpFile);

        // set the input stream
        $this->data_config['inputStream'] = $data['uri'];

        // first put the file
        $params = $this->getParamsForPut();
        $dfs = new PHPDFS($this->data_config, $params );
        $dfs->handleRequest();
        $filePath = join('/', array( $this->data_config['data'][0]['storageRoot'], $params['pathHash'], $params['fileName'] ) );
        $this->assertFileExists( $filePath );

        // now get the file we just put
        $params = $this->getParamsForDelete();
        $dfs = new PHPDFS($this->data_config, $params );
        $dfs->handleRequest();
        $this->assertFalse( file_exists( $filePath ) );
    }
    
}