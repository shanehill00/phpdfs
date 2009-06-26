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
 * first we figure out if:
 *      we are going to save a copy locally
 *      whether we need to replicate or not
 *      or do we need to just pass on the file data to a different node altogether
 *
 *
 * get the cluster and node config
 * parse the url for the input params of:
 *      file name to use when the file is saved
 *      number of times to replicate the file
 *      the params will be located in the path info
 *
 *          /filename/replicaNumber/targetPosition
 *
 *      if num_times_to_replicate is not there, then we assume that we need to replicate
 *      the max_replica_count - 1
 *
 * get all of the urls for the image
 * check to see if this node is included in the urls
 * if not
 *      then we choose the first url in the list
 *      test to see if the url is available via an OPTIONS request
 *      if available send the file data there under the filename we parsed from the url.
 *      if not avaialble, try the next url in the list
 *      repeat until the list is exhausted,  if we did not manage
 *      to send the file anywhere, then we need to return a 500 error
 *
 *      we omit the num_times_to_replicate because we know that
 *      the node to which we are forwarding will take care of starting the replication
 *
 *      to facilitate the forward we read from stdin
 *      and use that with the curl lib to make a put request
 *      to the next node.
 *
 * if the url is in the list, that means we need to save the file to disk
 *      spool the file to disk to a temp spot
 *      rename the file to the name we parsed from the url
 *      this is where we need to send back a signal to the client
 *      that we are all good and we need to disconnect from the client
 *      but keep running to complete the replication checks below
 *
 *      now we check to see if we should replicate
 *          check the num_times_to_replicate
 *
 *          if num_times_to_replicate is 0
 *              do nothing
 *
 *          if num_times_to_replicate is null or non-existent or the empty string
 *              then we assume we are the first stop and need to
 *              start the full replication
 *
 *          if num_times_to_replicate is greater than 0
 *              we take the total number of urls for replicas
 *              subtract num_times_to_replicate,
 *                  this will yield the index of the url to which we should forward
 *
 *              we get the url from our url list
 *              subtract 1 from num_times_to_replicate
 *              forward on to the url with the total num_times_to_replicate
 *
 */

require_once 'PHPDFS/Helper.php';

class PHPDFS
{

    /**
     * holds the config data for this instantiation
     *
     * @var <array>
     */
    protected $config = null;

    /**
     * the data locator that is used for looking up the
     * location of an object.
     *
     * @var <array>
     */
    protected $locator = null;

    /**
     * caller inout for things like
     * file name, data directories
     * temp storage directories, etc
     *
     * @var <array>
     */
    protected $params = null;

    /**
     * path to the temp copy of the uploaded file
     * this file may or may not exist, so we need to do appropriate
     * existence checks
     *
     * @var <string>
     */
    protected $tmpPath = "";

    /**
     * path to the final copy of the uploaded file
     * if it gets saved here
     *
     * @var <string>
     */
    protected $finalPath = "";

    /**
     * an array that holds all the target nodes
     * for the file being saved
     *
     * @var <array>
     */
    protected $targetNodes = null;

    /**
     * int indicating which position
     * this node is in in he target node list
     *
     * @var <int>
     */
    protected $nodePosition = null;

    /**
     *
     * integer value that indicates that
     * we do not have a position in the list
     * of target nodes.  essentially meaning thatw e are
     * not a target node.
     *
     * @var <int>
     */
    const NO_TARGET_POSITION = -1;

    /**
     *
     * @param <type> $locator
     * @param <type> $config
     * @param <type> $params
     */

    public function __construct( $config, $params ){
        
        require_once( $config['locatorClassPath'] );
        $locatorClassName = $config['locatorClassName'];

        $this->locator = new $locatorClassName( $config );
        $this->config = $config;
        $this->params = $params;

        $this->finalPath = join($config['pathSeparator'],array($config['storageRoot'],$params['name']));
        $this->tmpPath = join($config['pathSeparator'],array($config['tmpRoot'],uuid_create()));
    }

    public function handleRequest(){
        if( $_SERVER['REQUEST_METHOD'] == 'GET' ){
            $this->getData();
        } else if( $_SERVER['REQUEST_METHOD'] == 'PUT' ){
            $this->putData();
        } else if( $_SERVER['REQUEST_METHOD'] == 'DELETE' ){
            $this->deleteData();
        }
    }

    public function getData(){
        if( file_exists( $this->finalPath) ){
            $dataFH = fopen( $this->finalPath, "r");

            $finfo = finfo_open(FILEINFO_MIME, $this->config["magicDbPath"] );
            $contentType = finfo_file($finfo, $this->finalPath );
            finfo_close( $finfo );

            header("Content-Type: $contentType");

            while ($data = fread($dataFH, 1024))
              echo($data);

            fclose($dataFH);
        } else {
            $notFound = str_replace($this->config['storageRoot'], "", $this->finalPath);
            PHPDFS_Helper::send404( $notFound );
        }
    }

    public function putData(){
        // get the data from stdin and put it in a temp file
        // we will disconnect the client at this point if we are configured to do so
        // otherwise we hang on to the client, which in most cases is really bad
        // because you might stay connected until the replication chain is completed
        $this->spoolData( );

        // save the data to the appropriate directory and remove the spooled file
        // but only if we are a targetNode, otherwise DO NOTHING
        $this->saveData( );

        // forward the data on to the next node
        // the reasons for forwarding are:
        //
        // we are NOT a targetNode and are just the first node
        // to receive the upload, so we forward the data to the first targetNode
        // and remove the spooled file
        //
        // OR we are a targetNode and need to fulfill the replication requirements
        // so, we forward data to the next targetNode in our list.
        // however, if we are the last replication targetNode, we DO NOTHING.
        $this->forwardData( );
    }

    public function deleteData(){
        $this->_deleteData( );
        $this->forwardDelete();
    }

    public function spoolData( $disconnect = false ){

        // write stdin to a temp file
        $tmpFH = fopen($this->tmpPath, "w");
        $putData = fopen("php://input", "r");

        while ($data = fread($putData, 1024))
          fwrite($tmpFH, $data);

        fclose($tmpFH);
        fclose($putData);

        if( isset( $this->config['disconnectAfterSpooling'] ) && $this->config['disconnectAfterSpooling'] ){
            PHPDFS_Helper::disconnectClient();
        }
    }

    protected function _deleteData( ){
        if(  $this->iAmATarget( ) && file_exists( $this->finalPath ) ){
            $deleted = unlink( $this->finalPath );
            if( !$deleted ){
                // throw exception if the copy failed
                throw new Exception("could not unlink ".$this->finalPath);
            }
        }
    }

    public function saveData( ){
        if(  $this->iAmATarget( ) ){

            $copied = copy($this->tmpPath, $this->finalPath);

            if( !$copied ){
                // throw exception if the copy failed
                throw new Exception("final copy operation failed");
            }

            unlink( $this->tmpPath );
        }
    }

    protected function forwardDelete( ){
        $targetNodes = $this->getTargetNodes();
        if( $this->iAmATarget() ){
            // check whether or not we are done replicating.
            //
            // replicas are identified by the replica number.
            // replica numbers start at 0, so we check to see
            // if the replica number value is less than the max replicas minus 1
            // if yes, that means we need to continue to replicate
            // if not, that means we are done replicating and can quietly return
            $replica = (int) $this->params['replica'];
            $replicationDegree = (int) $this->config['replicationDegree'];
            if( $replica < ( $replicationDegree - 1 ) ) {

                $position = $this->params['position'];
                if( !is_numeric( $position ) ){
                    $position = $this->getNodePosition( );
                }
                $replica++;
                $position++;
                // resolve the array index for our position in the list of targetNodes
                $position %= count( $targetNodes );

                $url = join("/", array( $targetNodes[$position]['proxyUrl'], $this->params['name'], $replica, $position ) );
                $this->sendDelete( $url );
            }
        } else {
            $url = join('/', array($targetNodes[0]['proxyUrl'],$this->params['name'] ) );
            $this->sendDelete( $url );
        }
    }

    /**
     *
     */
    protected function forwardData( ){
        $targetNodes = $this->getTargetNodes();
        if( $this->iAmATarget() ){
            // check whether or not we are done replicating.
            //
            // replicas are identified by the replica number.
            // replica numbers start at 0, so we check to see
            // if the replica number value is less than the max replicas minus 1
            // if yes, that means we need to continue to replicate
            // if not, that means we are done replicating and can quietly return
            $replica = (int) $this->params['replica'];
            $replicationDegree = (int) $this->config['replicationDegree'];
            if( $replica < ( $replicationDegree - 1 ) ) {

                $position = $this->params['position'];
                if( !is_numeric( $position ) ){
                    $position = $this->getNodePosition( );
                }
                $replica++;
                $position++;
                // resolve the array index for our position
                $position %= count( $targetNodes );

                $url = join("/", array( $targetNodes[$position]['proxyUrl'], $this->params['name'], $replica, $position ) );
                $this->sendData( $this->finalPath, $url );
            }
        } else {
            $url = join('/', array($targetNodes[0]['proxyUrl'],$this->params['name'] ) );
            $this->sendData( $this->tmpPath, $url );
            unlink( $this->tmpPath );
        }
    }

    public function getTargetNodes(){
        if( !$this->targetNodes ){
            $this->targetNodes = $this->locator->findNodes( $this->params['name'] );
        }
        return $this->targetNodes;
    }

    protected function sendDelete( $url ){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
    }

    protected function sendData( $from, $url ){
        $fh = fopen($from, "r");
        $size = filesize( $from );
        rewind($fh);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $size );
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_PUT, 4);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        fclose($fh);
    }

    /** LOOP ALERT
    * we can end up in a loop if for some reaon we find
    * an ip in the http_host that does not match what is in the configuration
    * and we end up forwarding to an ip address that resolves back to this machine.
    *
    * or if we do not have a http_host in the environment and we use the 'thisProxyUrl'
    * config value and that is wrong
    *
    * so the moral of the story is to make sure that either the $_SERVER['HTTP_HOST'] or the "myIp"
    * setting matches one of the hosts in the node config  or we make the assumption that this server
    * is just to act as a forwarding agent for the data on stdin.
    *
    */

    public function getNodePosition( ){
        if( is_null( $this->nodePosition ) ) {
            // set this node as if it has no position
            $this->nodePosition = self::NO_TARGET_POSITION;
            $thisHost = '';
            if( isset( $this->config['thisProxyUrl'] ) &&  $this->config['thisProxyUrl'] ){
                $thisHost = $this->config['thisProxyUrl'];
            } else if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] ){
                $thisHost = $_SERVER['HTTP_HOST'];
            } else {
                throw new Exception(
                    "No ip address available for checking.  Please add one to the 'thisProxyUrl' configuration value ".
                    "or make sure that the _SERVER['HTTP_HOST'] var has a value"
                );
            }

            $targetNodes = $this->getTargetNodes();
            $n = 0;
            foreach( $targetNodes as $node ){
                if( $node['proxyUrl'] == $thisHost ){
                    $isTarget = true;
                    // we are a target node so set the node position
                    $this->nodePosition = $n;
                    break;
                }
                $n++;
            }
        }
        return $this->nodePosition;
    }

    public function iAmATarget( ){
        $isTarget = false;
        $targetPos = $this->getNodePosition( );
        if( is_numeric( $targetPos ) && $targetPos > self::NO_TARGET_POSITION ){
            $isTarget = true;
        } else {
            $isTarget = false;
        }
        return $isTarget;
    }

}
