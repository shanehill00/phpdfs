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

class PHPDFS_DataLocator_RUSHr
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
            $this->clusters[] = array('count' => $nodeCount, 'list' => range(0, $nodeCount-1, 1 ) );
        }
        $this->dataConfig = $dataConfig;
    }
    /**
     * This function is an implementation of a RUSHr algorithm as described by R J Honicky and Ethan Miller
     *
    */
    public function findNode( $objKey ){

        $nodeData = array();
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
        while( 1 ){

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
            $t = ($replicationDegree - $sumRemainingNodes) > 0 ? ($replicationDegree - $sumRemainingNodes) : 0;

            $u = $t + $this->drawWHG(
                $replicationDegree - $t,
                $sumRemainingNodesW - $t,
                $disksInCurrentClusterW + $sumRemainingNodesW - $t,
                $weight
            );
            if( $u > 0 ){
                $servers = $this->choose( $u, $currentCluster, $sumRemainingNodes, $nodeData );
                $replicationDegree -= $u;
            }
            if( $replicationDegree == 0 ){
                break;
            }
            $currentCluster--;
        }

        return $nodeData;
    }

/**
def reset
    for i ∈ {0, . . . , k − 1}
        c ← an−k+i
        if c < n − k:
            ac ← c
        an−k+i ← n − k + i
    end for
 */

    public function reset( $nodesToRetrieve, $currentCluster ){
        $list = &$this->clusters[ $currentCluster ]['list'];
        $count = $this->clusters[ $currentCluster ]['count'];
        for( $nodeIdx = 0; $nodeIdx < $nodesToRetrieve; $nodeIdx++ ){
            $listIdx = $count - $nodesToRetrieve + $nodeIdx;
            $val = $list[$listIdx];
            if( $val < ($count - $nodesToRetrieve) ){
                $list[$val] = $val;
            }
            $list[ $listIdx ] = $listIdx;
        }
    }

/**
def choose ( k, n )
    for i = 0 to k − 1
        generate a random integer 0 ≤ z < (n − i)
        // swap az and an−i−1
        ri ← az
        az ← an−i−1
        an−i−1 ← ri
    end for
    return r
 */
    public function choose( $nodesToRetrieve, $currentCluster, $remainingNodes, &$nodeData ){
        $list = &$this->clusters[ $currentCluster ]['list'];
        $count = $this->clusters[ $currentCluster ]['count'];
        for( $nodeIdx = 0; $nodeIdx < $nodesToRetrieve; $nodeIdx++ ){
            $maxIdx = $count - $nodeIdx - 1;
            $randNode = rand( 0, $maxIdx );
            // swap
            $chosen = $list[ $randNode ];
            $list[ $randNode ] = $list[ $maxIdx ];
            $list[ $maxIdx ] = $chosen;
            // add the remaining nodes so we can find the node data when we are done
            $nodeData[] = $this->nodes[$remainingNodes + $nodeIdx];
        }
        $this->reset( $nodesToRetrieve, $currentCluster );
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

    public function drawWHG( $replicas, $remainingDisks, $totalDisks, $weight = 1 ){
        if( $replicas < 1 ) return 0;
        $prob = 0;
        $totalSuccesses = $replicas;
        $sampleSize = $totalDisks - $remainingDisks;
        $draw = (rand() / getrandmax());
        $totalSuccesses = $replicas;
        $ceiling = 0;
        while( $draw > 0 && $replicas >= 0 ){
            $prob = $this->hypergeometric( $replicas, $totalDisks, $sampleSize, $totalSuccesses );
            $ceiling += $prob;
            if( $draw <= $ceiling ){
                break;
            }
            $replicas--;
        }
        return $replicas;
    }

    /**
      * N: The number of items in the population.
      * k: The number of items in the population that are classified as successes.
      * n: The number of items in the sample.
      * x: The number of items in the sample that are classified as successes.
     *
     * @param <int> $replicas
     * @param <int> $totalDisks
     * @param <int> $sample
     * @param <int> $totalSuccesses
     * @return <int> $prob
     */
    public function hypergeometric( $replicas, $totalDisks, $sample, $totalSuccesses ) {
        $prob = null;
        $prob = $this->C($totalSuccesses, $replicas) * $this->C($totalDisks - $totalSuccesses, $sample - $replicas) / $this->C($totalDisks, $sample);
        return $prob;
    }

    /**
     * get the total possible unordered combinations
     *
     * @param  <int> $n
     * @param  <int> $k
     * @return <int> $total
     */

    public function C( $n, $k ){
        $total = 1;
        while( $k > 0 ){
            $total *= ( $n / $k );
            $n--;
            $k--;
        }
        return $total;
    }

}
