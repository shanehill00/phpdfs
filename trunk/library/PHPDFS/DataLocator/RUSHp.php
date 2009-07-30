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

class PHPDFS_DataLocator_HonickyMillerR
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
     * the total number of nodes in all of the clusters
     * this property is populated at construction time only
     *
     * @var integer
     */
    protected $totalNodesW = 0;

    /**
     * the prime value we use for the purpose of choosing replica locations
     *
     * @var integer
     */
    protected $replicationDegree = 0;

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
     * @return  DataLocator_HonickyMiller $this
     *
     * @throws DataLocator_HonickyMiller_Exception
     *
     */
    protected $nodes = array();
    public function __construct( $dataConfig ){

        if( !isset( $dataConfig['clusters'] ) || !count( $dataConfig['clusters'] ) ){
            throw new PHPDFS_DataLocator_Exception("data config to the Honicky-Miller locator does not contain a valid clusters property. bad joo joos mon!");
        }

        $this->replicationDegree = $dataConfig['replicationDegree'];
        $this->totalClusters = count($dataConfig['clusters']);

        $largestNodeCountW = 0;
        foreach( $dataConfig['clusters'] as $cluster ){
            $nodeCount = count( $cluster['nodes'] );
            for( $n = 0; $n < $nodeCount; $n++ ){
                $this->nodes[] = $cluster['nodes'][$n];
            }
            $this->totalNodes += $nodeCount;
            $nodeCountW = $nodeCount * $cluster[ 'weight' ];
            $this->totalNodesW += $nodeCountW;
            $this->clusters[] = array('count' => $nodeCount, 'prime' => $this->getNextPositivePrime( $nodeCountW ) );
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
    public function findNode( $objKey, $replica = 0 ){

        $nodeData = null;
        $clusters = $this->clusters;
        $totalClusters = $this->totalClusters;
        $totalNodes = $this->totalNodes;
        $clusterConfig = $this->dataConfig['clusters'];
        $replicationDegree = $this->replicationDegree;
        // throw an exception if the data is no good
        if( ( $totalNodes <= 0 )  || ( $totalClusters <= 0 ) ){
            throw new PHPDFS_DataLocator_Exception("the total nodes or total clusters is negative or 0.  bad joo joos!");
        }

        $sumRemainingNodes = $totalNodes;


        // get the starting cluster
        $currentCluster = --$totalClusters;


        $sumRemainingNodes = $this->totalNodes;
        // get the weighted total disks
        $sumRemainingNodesW = $this->totalNodesW;

        // turn a string identifier into an integer for the random seed
        if( is_string( $objKey ) ){
            $objKey  = ( crc32( $objKey ) >> 16 ) & 0x7fff;
        }

        /**
         * this loop is an implementation
         * of the honickey-miller algorithm for
         * fast placement and location of objects
         * in a distributed storage system
         *
         * j = current cluster
         * m = disks in current cluster
         * n = remaining nodes
         */
        while( ! $nodeData ){

            // prevent an infinite loop, in case there is a bug
            if( $currentCluster < 0 ){
                throw new PHPDFS_DataLocator_Exception("the cluster index became negative while we were looking for the following id: $objKey.  This should never happen with any key.  There is a bug or maybe your joo joos are BAD!");
            }

            $clusterData = $clusterConfig[$currentCluster];
            $weight = $clusterData['weight'];
            
            $disksInCurrentCluster = $clusters[$currentCluster]['count'];
            $sumRemainingNodes -= $disksInCurrentCluster;
            
            $disksInCurrentClusterW = $disksInCurrentCluster * $weight;
            $sumRemainingNodesW -= $disksInCurrentClusterW;

            // set the seed to our set id
            srand( $objKey );

            // jump ahead the number of clusters we are investigating
            for($n = 0; $n <= $currentCluster; $n++){
                rand();
            }

            // generate the random value that will tell us if we are meant to
            // place this object here
            $rand = rand( 0, ($sumRemainingNodesW + $disksInCurrentClusterW - 1) );

            // create a bijection here by using the prime we solved at construction time
            $prime = $clusters[$currentCluster]['prime'];
            $randB = ( $rand + $replica * $prime ) % ( $sumRemainingNodesW + $disksInCurrentClusterW );

            $v = $objKey + $rand + $replica * $prime;

            $absNode = null;
            if( $disksInCurrentCluster >= $replicationDegree && $randB < $disksInCurrentClusterW ){

                //  if m^j ≥ R and z′ < m′^j
                //
                // map to server n^j + (v mod m^j )
                // get the absolute position of the node relative to all nodes in all clusters
                $absNode = $sumRemainingNodes + ( $v % $disksInCurrentCluster );
                $nodeData = $this->nodes[$absNode];

            } else if($disksInCurrentCluster < $replicationDegree
                       && $randB < ($replicationDegree * $weight)
                         && ($v % $replicationDegree) < $disksInCurrentCluster)
            {

                // if m^j < R and z′ < R · w^j and v mod R < m^j
                //
                // map to  n^j + (v mod R)
                // get the absolute position of the node relative to all nodes in all clusters
                $absNode = $sumRemainingNodes + ( $v % $replicationDegree );
                $nodeData = $this->nodes[$absNode];
            } else {
                // this means we missed the cluster
                // so we decrement to the previous cluster and look there
                $currentCluster--;
            }

        }
        $num = 0;
        return $nodeData;
    }

    /**
     * this provides us with a reasonably fast way to get the next prime number
     * without having to compile with the gmp libs.
     *
     * also,  if we can somehow run this at apache start up time and then
     * write an apache var with the prime value in it, we would not need this at all
     *
     * given a number n,
     * one divides n by all numbers m less than or equal to the square root of that number.
     * If any of the divisions come out as an integer,
     * then the original number is not a prime.
     * Otherwise, it is a prime.
     */
    protected function getNextPositivePrime( $num ){
        if( $num < 0 ){
            $num = 0;
        }
        $prime = false;
        while( !$prime ){
            $num++;
            // greater than 3 and not divisible by 2 or 3
            if( $num > 3 && $num % 2 && $num % 3 ){
                $prime = true;
                $m = (int) sqrt( $num );
                for( $m; $m > 1; $m-- ){
                    if( !($num % $m) ){
                        $prime = false;
                        break;
                    }
                }
            } else if( $num <= 3 ){
                $prime = true;
            }
        }
        return $num;
    }

    public function findNodes( $objKey ){
        $nodes = array();
        for( $replicaNo = 0; $replicaNo < $this->replicationDegree; $replicaNo++ ){
            $nodes[] = $this->findNode($objKey, $replicaNo);
        }
        return $nodes;
    }

    public function isTargetNodeForObj( $nodeUrl, $objKey ){
        $isTarget = false;
        $nodes = $this->findNodes($objKey);
        foreach( $nodes as $node ){
            if( $nodeUrl == $node['proxyUrl'] ){
                $isTarget = true;
                break;
            }
        }
        return $isTarget;
    }

    public function getReplicationDegree(){
        return $this->replicationDegree;
    }

    public function getTotalNodes(){
        return $this->totalNodes;
    }

}
