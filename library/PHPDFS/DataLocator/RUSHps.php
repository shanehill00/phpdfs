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
 * This class is an implementation of a RUSH algorithm as described by R.J. Honicky and Ethan Miller
 * 
 * @author Shane Hill <shanehill00@gmail.com>
 *
 */

/**
 *  Exception class
 */
require_once('PHPDFS/DataLocator/Exception.php');

class PHPDFS_DataLocator_RUSHps
{
    
    /**
     * an ordinal array where each element represents a cluster
     * and the value is an int that is the total number of nodes in the cluster
     *
     * this property is populated at construction time only
     * @var unknown_type
     */
    protected $clusters = array();
    
    /**
     *  total number of clusters in our data configuration
     *  this property is populated at construction time only
     * @var integer
     */
    protected $totalClusters = 0;
    
    /**
     * the total number of nodes in all of the clusters
     * this property is populated at construction time only
     *
     * @var integer
     */
    protected $totalNodes = 0;

    /**
     * the config that was passed to the constructor
     * it is from the dataConfig that we retrieve the data node
     *
     * @var object
     */
    public $dataConfig = null;

    /**
     * The constructor analyzes the passed config to obtain the fundamental values
     * and data structures for locating a node.
     * each of those values is described in detail above with each property.  briefly:
     * 
     *      $this->clusters
     *      $this->totalClusters
     *      $this->totalNodes
     * 
     *  The values above are derived from the $dataConfig passed to the locator.
     * 
     * @param object $dataConfig
     * @return  PHPDFS_DataLocator_RUSHps $this
     * 
     * @throws PHPDFS_DataLocator_Exception
     * 
     */
    public function __construct( $dataConfig ){

        if( !isset( $dataConfig['clusters'] ) || !count( $dataConfig['clusters'] ) ){
            throw new PHPDFS_DataLocator_Exception("data config to the Honicky-Miller locator does not contain a valid clusters property");
        }
        
        $this->totalClusters = count($dataConfig['clusters']);
        foreach( $dataConfig['clusters'] as $cluster ){
            $nodeCount = count( $cluster['nodes'] );
            $this->clusters[] = $nodeCount;
            $this->totalNodes += $nodeCount;
        }
        $this->dataConfig = $dataConfig;
    }
    /**
     * This function is an implementation of a RUSH algorithm as described by R J Honicky and Ethan Miller
     *
     * @param string $objKey - an int used as the prng seed.  this int is usually derived from a string hash
     * 
     * @return $nodeInfo - holds three values:
     *                                 abs_node - an int which is the absolute position of the located node in relation to all nodes on all clusters
     *                                 rel_node - an int which is the relative postion located node within the located cluster
     *                                 cluster - an int which is the located cluster 
     * 
     * @throws DataLocator_Exception
     */
    public function findNode( $objKey ){

        $nodeInfo = null;
        $clusters = $this->clusters;
        $totalClusters = $this->totalClusters;
        $totalNodes = $this->totalNodes;
    
        // throw an exception if the data is no good
        if( ( $totalNodes <= 0 )  || ( $totalClusters <= 0 ) ){
            throw new PHPDFS_DataLocator_Exception("the total nodes or total clusters is negative");
        }

        $sumRemainingNodes = $totalNodes;
    
        // get the starting cluster
        $currentCluster = $totalClusters - 1;
    
        // turn a string identifier into an integer for the random seed
        if( is_string( $objKey ) ){
            $objKey  = ( crc32( $objKey ) >> 16 ) & 0x7fff;
        }

        /**
         * this loop is an implementation
         * of the honickey-miller algorithm for
         * fast placement and location of objects
         * in a distributed storage system
         */
        $mapped = false;
        $clusterConfig = $this->dataConfig['clusters'];
        // while we are not mapped
        // try and get a mapping
        while( ! $mapped ){
        
            // prevent an infinite loop, in case there is a bug
            if( $currentCluster < 0 ){
                throw PHPDFS_DataLocator_Exception("the cluster index became negative while we were looking for the following id: $objKey.  This should never happen with any key.  Try checking the toothpaste you are using for some clues.");
            }
            
            // get the total disks in the cluster we are currently investigating
            $disksInCurrentCluster = $clusters[$currentCluster];
            
            // get the total disks on the rest of the clusters
            $sumRemainingNodes -= $disksInCurrentCluster;
            
            // set the seed to our set id
            srand( $objKey );
            
            // jump ahead the number of clusters we are investigating
            for($n = 0; $n <= $currentCluster; $n++){
                rand();
            }
            
            // generate the random value that will tell us if we are meant to
            // place this object here
            $rand = rand( 0, ($sumRemainingNodes + $disksInCurrentCluster - 1) );
            
            if( $rand >= $disksInCurrentCluster ){
                // this means we missed the cluster
                // so we decrement to the previous cluster and look there
                $currentCluster--;
            } else {
                $mapped = true;
                // get the absolute position of the node relative to all nodes in all clusters
                $absNode = $sumRemainingNodes + ( $rand % $disksInCurrentCluster );
                // we need the relative position of the node within the cluster
                // so we take the modulus of the absnode %
                // this way we can reach into our data config and get the object and return it
                $relNode = $currentCluster ? ( $absNode % $currentCluster + 1 ) : $absNode;
                $nodeInfo = $clusterConfig[ $currentCluster ]['nodes'][$relNode];
            }
        
        }
        return $nodeInfo;
    }
}