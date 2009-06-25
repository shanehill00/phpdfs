<?php
;
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

require_once '../../library/DataLocator/HonickyMiller.php';
/**
 * @category   GXC
 * @package    DataLocator
 * @subpackage UnitTests
 */
class DataLocator_HonickyMillerTest extends PHPUnit_Framework_TestCase
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
        $hm = new DataLocator_HonickyMiller( $this->data_config );
        $this->assertType( 'DataLocator_HonickyMiller', $hm  );
    }

   /**
    * Tests that we throw a DataLocator_Exception when the cluster count is negative .
    * We accomplish this by passing in a negative cluster count 
    */
    public function testThrowLocatorException()
    {
        $badConf['clusters'] = array();
        try{
            $hm = new DataLocator_HonickyMiller( $badConf );
            $this->fail("successfully instantiated the locator when we should have failed");
        } catch( DataLocator_Exception $e){
            $this->assertType('DataLocator_Exception', $e);
        }
    }
    
    /**
    * Test that we consistently get the same data node for the same id.
    */
    public function testFindNode(){
        $hm = new DataLocator_HonickyMiller(  $this->data_config );
        $uuid = 'random_file_name';
        $node = $hm->findNode( $uuid );
        // now we repeat the operation 10 times and see that we get the same node back each time
        for($i = 0; $i < 10; $i++){
            $node2 = $hm->findNode( $uuid );
            $nodeHost = $node['proxyUrl'];
            $nodeHost2 = $node2['proxyUrl'];
            echo("$nodeHost == $nodeHost2\n");
            $this->assertNotEquals($nodeHost,'',"nodeHost is empty.  bad joos joos!");
            $this->assertNotEquals($nodeHost2,'',"nodeHost2 is empty.  bad joos joos!");
            $this->assertTrue( $nodeHost == $nodeHost2, "failed consistently fetching a node got $nodeHost == $nodeHost2" );
        }
    }


}