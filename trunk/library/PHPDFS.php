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
require_once 'PHPDFS/PutException.php';
require_once 'PHPDFS/DeleteException.php';
require_once 'PHPDFS/GetException.php';
require_once 'PHPDFS/MoveException.php';

class PHPDFS
{

    /**
     * holds an array of configs
     * we need an array of config so that we can accommodate
     * move commands and automatic movement of data
     * when new resources are added
     * or old resources are dellocated or removed
     *
     * there are also some values in the array that are "global"
     * such as the path separator to use for file paths.
     *
     * @var <array>
     */
    protected $configArray = null;

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
     * final path to the directory where the file will be saved
     *
     * @var <string>
     */
    protected $finalDir = "";
    
    /**
     * an array that holds all the target nodes
     * for the file being saved
     *
     * @var <array>
     */
    protected $targetNodes = null;

    /**
     * an assoc array keyed of of proxy url where the value
     * of each element is the position of the proxyUrl
     * in the target node list
     * 
     * @var <array>
     */
    protected $positionByUrl = null;

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
    const POSITION_NONE = -1;

    const HEADER_REPLICA           = 'Phpdfs-Replica';
    const HEADER_POSITION          = 'Phpdfs-Position';
    const HEADER_MOVE_CONTEXT      = 'Phpdfs-Move-Context';
    const HEADER_MOVE_CONFIG_INDEX = 'Phpdfs-Move-Config-Index';
    const HEADER_CONFIG_INDEX      = 'Phpdfs-Config-Index';

    const MOVE_CONTEXT_START = 'start';
    const MOVE_CONTEXT_CREATE = 'create';
    const MOVE_CONTEXT_DELETE = 'delete';

    /**
     *
     * @param <type> $locator
     * @param <type> $config
     * @param <type> $params
     */

    public function __construct( $configArray, $params ){
        $this->configArray = $configArray;
        $this->params = $params;
        // the configIndex tells us which config to use for this request
        // it is initially passed to us via the header Phpdfs-Config-Index
        // we need this value because we need to hve a "history" of configs to
        // accommodate automatic movement of data.
        $configIndex = $this->params['configIndex'];
        $this->config = $this->configArray[ $configIndex ];

        require_once( $this->config['locatorClassPath'] );
        $locatorClassName = $this->config['locatorClassName'];

        $this->locator = new $locatorClassName( $this->config );

        $this->finalDir = join( $this->configArray['pathSeparator'], array($this->config['storageRoot'], $this->params['pathHash'] ) );
        $this->finalPath = join( $this->configArray['pathSeparator'], array( $this->finalDir, $this->params['fileName'] ) );
        $this->tmpPath = join( $this->configArray['pathSeparator'], array($this->config['tmpRoot'], uuid_create()));
    }


    public function handleRequest(){
        $action = $this->params['action'];
        if( $action == 'get' ){
            $this->getData();

        } else if( $action == 'put' ){
            $this->putData();

        } else if( $action == 'delete' ){
            $this->deleteData();

        }else if( $action == 'move' ){
            $this->moveData();

        }
    }

    /**
     * we move within 3 different contexts:
     *      start - this is the context when a move has not started copying data
     *              yet and we are still looking for the node that contains the data to be
     *              moved
     *
     *      create - this is the context after start and means that we are creating new data
     *               to facilitate the move function
     *
     *      delete - this is the context after the create phase is over nd we are deleting the old data
     *
     *  basically we try and ensure that data is always available and to that end we
     *  move data by first traveling to each node in the new config and copying the data to disk
     *  then we go to each node in the old config and remove the data.  by doing this we ensure that
     *  the data is always avaialable to all clients
     *
     *  so depending on our context, we make a decision to run a method that will contribute to completing the
     *  move action.
     *
     *
     */
    protected function moveData(){
        if( isset( $this->params['moveContext'] ) ){
            $context = $this->params['moveContext'];
            if( $context == 'start' ){
                $this->doStartForMove();
            } else if( $context == 'create' ){
                //error_log( "received a move action in create context ".print_r( $this->params, 1) );
                $this->doCreateForMove();
            } else if( $context == 'delete' ) {
                $this->doDeleteForMove();
            }
        }
    }

    protected function handleMoveDeleteError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_PutException( " $errno : $errmsg : $errfile : $errline " );
    }
    
    protected function doDeleteForMove(){
        set_error_handler( array( $this, "handleMoveDeleteError") );
        try{
            // check to see if we are a target node in both the
            // old config and the current config at configIndex = 0
            // if we are a target in both configs then we take no action
            // we just send the delte on to the next node
            $currentConfig = $this->configArray[ 0 ];
            $locClass = $currentConfig['locatorClassName'];
            $locator = new $locClass( $currentConfig );
            $currentNodes = $locator->findNodes( $this->params['name'] );
            if( !$this->iAmATarget( $currentNodes ) ){
                $this->_deleteData();
            } else {
                error_log("nodes were alike in delete for ".$this->config['thisProxyUrl'] );
            }
            $this->sendDeleteForMove();
        } catch( Exception $e ){
            error_log("error while deleting a file for a move operation for file ".$this->params['name']." data".$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500( "error when deleting a file in a move operation." );
        }
        restore_error_handler();
    }

    protected function handleMoveCreateError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_PutException( " $errno : $errmsg : $errfile : $errline " );
    }
    
    /**
     * called when we are in create context for a move operation
     *
     * the algorithm at this point is very similar to the
     * putData algorithm.
     *
     * spoolData
     * saveData
     * sendDataForMove
     *
     */

    protected function doCreateForMove(){
        set_error_handler( array( $this, "handleMoveCreateError") );
        try{
            // check to see if we are a target node in both the
            // old config and the current config at configIndex = 0
            // if we are a target in both configs then we take no action
            // we just send the delte on to the next node
            $oldConfig = $this->configArray[ $this->params['moveConfigIndex'] ];
            $locClass = $oldConfig['locatorClassName'];
            $locator = new $locClass( $oldConfig );
            $oldNodes = $locator->findNodes( $this->params['name'] );
            if( !$this->iAmATarget( $oldNodes ) ){
                $this->spoolData();
                $this->saveData();
            }else {
                error_log("nodes were alike in create for ".$this->config['thisProxyUrl'] );
            }
            $this->sendDataForMove();
            // now we need to check if this is the last node to receive the data for a move
            // by calling for the forwardData.  if the forwardata is empty then we assume we are the last node
            // and start the deleteForMove process
            // which means that we need to set the config index to the same value
            // as the moveConfigIndex so that we start the propagation of the delete
            // down the correct chain
            error_log("finished move checking if we are the last node in the replication chain");
            if( !$this->getForwardInfo() ){
                error_log("starting delete for move");
                $this->startDeleteForMove();
            }
        } catch( Exception $e ){
            error_log("error while creating a file for a move operation for file ".$this->params['name']." data".$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500( "error when copying during a move operation." );
        }
        restore_error_handler();
    }


    protected function handleMoveStartError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_PutException( " $errno : $errmsg : $errfile : $errline " );
    }
    /**
     *  called when we are in start context for a move operation
     * 
     *  we check to see if we were a target node
     *  that needs to be moved.  it checks using the configIndex value in the params name 'moveConfigIndex'.
     *  if we are an owning node, we set the context to 'create' and we send the data to be moved
     *  to the first node of the current config that should have the data.
     *
     *  if we are not the owning node, we get the list of possible nodes that are owners
     *  and send the move command to the owner node with a context of 'start'
     */
    protected function doStartForMove(){
        // here we make a new locator instance and use it to locate the old data
        require_once( $this->config['locatorClassPath'] );
        set_error_handler( array( $this, "handleMoveStartError") );
        try{
            $locClass = $this->config['locatorClassName'];
            $locator = new $locClass( $this->configArray[ $this->params['moveConfigIndex'] ] );
            $thisProxyUrl = $this->config['thisProxyUrl'];
            $objKey = $this->params['name'];
            if( $this->iAmATarget( $locator->findNodes( $objKey ) ) ){
                $this->sendDataToStartMove( );
            } else {
                $this->sendStartMoveCmd( $locator );
            }
        } catch( Exception $e ){
            error_log("error while starting a move operation for file ".$this->params['name']." data".$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500( "error when starting a move operation." );
        }
        restore_error_handler();
    }
    
    /**
     * need to add a param to indicate that we want to continue looking for the data
     * if we are not actually a target node.  logically, any client directly
     * asking for data should be able to locate the exact nodes from which
     * to ask.  So this really should not happen very often (unles someone os making lots of bad requests)
     * and might even be indicative a problem.  we do not really have a way to tell if it denotes a prob or not
     *
     * what we do need is a check to be sure that we are not a target anyway so we can throw an error
     * if the data is supposed to be here. ah but wait we might not have the data
     */
    public function getData(){
        $finalPath = $this->finalPath;
        $config = $this->config;
        $iAmATarget = $this->iAmATarget();
        if( file_exists( $finalPath) && $iAmATarget ){
            $dataFH = fopen( $finalPath, "rb" );

            $finfo = finfo_open( FILEINFO_MIME, $config["magicDbPath"] );
            $contentType = finfo_file( $finfo, $finalPath );
            finfo_close( $finfo );

            header( "Content-Type: $contentType");
            header( "Content-Length: ".filesize( $finalPath ) );
            fpassthru( $dataFH );

            fclose( $dataFH );
        } else if( !$iAmATarget ){
            // get the paths, chhose one, and print a 302 redirect
            $nodes = $this->getTargetNodes();
            if( $nodes ){
                PHPDFS_Helper::send301( $nodes[0]['proxyUrl'].'/'.$this->params['name'] );
            }
        } else{
            PHPDFS_Helper::send404( $this->params['name'] );
        }
    }

    protected function handleSpoolError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_PutException( " $errno : $errmsg : $errfile : $errline " );
    }

    protected function handleForwardDataError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_PutException( " $errno : $errmsg : $errfile : $errline " );
    }

    public function putData(){
        $error500Msg = "error when processing upload";
        set_error_handler( array( $this, "handleSpoolError") );
        try{
            // get the data from stdin and put it in a temp file
            // we will disconnect the client at this point if we are configured to do so
            // otherwise we hang on to the client, which in most cases is really bad
            // because you might stay connected until the replication chain is completed
            $this->spoolData( );

            // save the data to the appropriate directory and remove the spooled file
            // but only if we are a targetNode, otherwise DO NOTHING
            $this->saveData( );
        } catch( PHPDFS_PutException $e ){
            error_log("error while spooling data".$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500($error500Msg);
            // we want to be sure to exit here because we have errored
            // and the state of the file upload is unknown
            exit();
        }
        restore_error_handler();

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
        set_error_handler( array( $this, "handleForwardDataError") );
        try{
            $this->forwardDataForPut( );
        } catch( PHPDFS_PutException $e ){
            error_log(" error while forwarding data" .$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500($error500Msg);
        }
        restore_error_handler();
    }

    protected function handleDeleteDataError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_DeleteException( " $errno : $errmsg : $errfile : $errline " );
    }
    
    protected function handleForwardDeleteError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        throw new PHPDFS_DeleteException( " $errno : $errmsg : $errfile : $errline " );
    }

    public function deleteData(){
        $error500Msg = "error when processing delete command";
        set_error_handler( array( $this, "handleDeleteDataError") );
        try{
            $this->_deleteData( );
        } catch( PHPDFS_DeleteException $e ){
            error_log(" error while deleting data" .$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500($error500Msg);
        }
        restore_error_handler();


        set_error_handler( array( $this, "handleForwardDeleteError") );
        try{
            $this->sendDelete();
        } catch( PHPDFS_DeleteException $e ){
            error_log(" error while forwarding the delete action " .$e->getMessage().' : '.$e->getTraceAsString() );
            PHPDFS_Helper::send500($error500Msg);
        }
        restore_error_handler();
    }

    /*
     * get the data from stdin and put it in a temp file
     * we will disconnect the client at this point if we are configured to do so
     * otherwise we hang on to the client, which in most cases is really bad
     * because you might stay connected until the replication chain is completed.
     * (depending on how the other nodes are configured of course)
     *
     * @param <boolean> $disconnect
     *
     * @throws PHPDFS_PutException
     */
    protected function spoolData( ){

        // write stdin to a temp file
        $tmpFH = fopen($this->tmpPath, "wb");
        $putData = fopen("php://input", "rb");

        while ($data = fread($putData, 1024))
          fwrite($tmpFH, $data);

        fclose($tmpFH);
        fclose($putData);

        if( isset( $this->config['disconnectAfterSpooling'] ) && $this->config['disconnectAfterSpooling'] ){
            PHPDFS_Helper::disconnectClient();
        }
    }

    /**
     * this wher we unlink the file from the fs
     * we want to prevent the errors from
     * killing the process here as we might
     * receive erroneous delete requests and it
     * does not seem like we should die because of those
     *
     */
    protected function _deleteData( ){
        if(  $this->iAmATarget( ) && file_exists( $this->finalPath ) ){
            $deleted = unlink( $this->finalPath );
            if( !$deleted ){
                // throw exception if the delete failed
                throw new PHPDFS_PutException("could not unlink ".$this->finalPath);
            }
        }
    }

    protected function saveData( ){
        if(  $this->iAmATarget( ) ){

            if( !file_exists( $this->finalDir ) ){
                // suppress any probs in case someone
                // else is making this directory
                @mkdir( $this->finalDir, 0755, true );
            }

            if(  !copy( $this->tmpPath, $this->finalPath ) ){
                // throw exception if the copy failed
                throw new Exception("final copy operation failed when copying ".$this->tmpPath." to ".$this->finalPath );
            }

            $deleted = unlink( $this->tmpPath );
            if( !$deleted ){
                // throw exception if the delete failed
                throw new PHPDFS_PutException("could not unlink ".$this->tmpPath);
            }
        }
    }

    protected function getForwardInfo( $replica = null, $position = null, $replicationDegree = null, $targetNodes = null ){

        // solve replica value
        if( is_null( $replica ) ){
            $replica = (int) $this->params['replica'];
        }

        // solve replicationDegree value
        if( is_null( $replicationDegree ) ){
            $replicationDegree = $this->config['replicationDegree'];
        }

        // obtain targetNodes for the
        if( is_null( $targetNodes ) ){
            $targetNodes = $this->getTargetNodes();
        }

        // solve position value
        if( is_null( $position ) ) {
            $position = $this->params['position'];
            if( !is_numeric( $position ) ){
                $position = $this->getTargetNodePosition( $targetNodes );
            }
        }
        if( $position == self::POSITION_NONE ){
            srand();
            $position = rand(0, (count($targetNodes)-1) );
        } else {
            $position++;
            $position %= count( $targetNodes );
        }

        $forwardInfo = null;
        $filename = $this->params['name'];
        if( $this->iAmATarget( $targetNodes ) ){
            // check whether or not we are done replicating.
            //
            // replicas are identified by the replica number.
            // replica numbers start at 0, so we check to see
            // if the replica number value is less than the max replicas minus 1
            // if yes, that means we need to continue to replicate
            // if not, that means we are done replicating and can quietly return
            if( $replica < ( $replicationDegree - 1 ) ) {
                $replica++;
                $forwardInfo = array(
                    'forwardUrl' => join("/", array( $targetNodes[$position]['proxyUrl'], $filename ) ),
                    'position' => $position,
                    'replica' => $replica,
                );
            }
        } else {
            $forwardInfo = array(
                'forwardUrl' => join('/', array( $targetNodes[$position]['proxyUrl'], $filename ) ),
                'position' => $position,
                'replica' => $replica,
            );
        }
        return $forwardInfo;
    }

    protected function forwardDataForPut( ){

        if( $this->iAmATarget() ){
            $this->sendDataForPut( $this->finalPath );
        } else {
            $this->sendDataForPut( $this->tmpPath );
            unlink( $this->tmpPath );
        }

    }

    public function getTargetNodes(){
        if( !$this->targetNodes ){
            $this->targetNodes = $this->locator->findNodes( $this->params['name'] );
        }
        return $this->targetNodes;
    }

    protected function getTargetNodePosition( $nodes = null, $proxyUrl = null ){
        if( !$proxyUrl ){
            $proxyUrl = $this->config['thisProxyUrl'];
        }

        if( !$nodes ){
            $nodes = $this->getTargetNodes();
        }
        $position = self::POSITION_NONE;
        $n = 0;
        foreach( $nodes as $node ){
            if($node['proxyUrl'] == $proxyUrl ){
                $position = $n;
                break;
            }
            $n++;
        }
        return $position;
    }

    /**
     * we will only loop until we see that the position number we are currently
     * looping with is the same as the position number with which we started.
     * if the two position values are equal then that means we have cycled on the
     * whole node list and we should not continue.  with each iteration we log an error
     * before moving on to the next node in the list
     */
    protected function sendDelete( ){
        $forwardInfo = $this->getForwardInfo( );
        if( $forwardInfo ){
            $errNo = 0;
            $origPosition = $forwardInfo['position'];
            $curl = curl_init();
            $headers = array();
            do{
                $headers[0] = self::HEADER_REPLICA.': '.$forwardInfo['replica'];
                $headers[1] = self::HEADER_POSITION.': '.$forwardInfo['position'];
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl'] );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                $errNo = curl_errno($curl);
                if( $errNo ){
                    error_log("replica ".$forwardInfo['replica']." : forwarding a delete command to ".$forwardInfo['forwardUrl']." failed using curl.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                    $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'] );
                }
            } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
            curl_close($curl);
        }
    }

    /**
     *
     * we will try to send data to the node identified by the url passed in to us
     * if we experience an error communicating with the url, we then go to the next
     * node in the list and repeat until we get a good node. if we cannot find a good node
     * then we just exit.
     *
     * the position parameter is important here as it needs to correlate to the url to which we are forwarding
     * so we check for the NO_POSITION value and if it is the no
     *
     * @param <string> $filepath - the file to send to the next node
     */
    protected function sendDataForPut( $filePath ){

        $forwardInfo = $this->getForwardInfo( );
        if( $forwardInfo ){
            $fh = fopen($filePath, "rb");
            $size = filesize( $filePath );
            rewind($fh);

            $errNo = 0;
            $origPosition = $forwardInfo['position'];
            $curl = curl_init();
            $headers = array();
            do{
                $headers[0] = self::HEADER_REPLICA.': '.$forwardInfo['replica'];
                $headers[1] = self::HEADER_POSITION.': '.$forwardInfo['position'];
                
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
                curl_setopt($curl, CURLOPT_INFILE, $fh);
                curl_setopt($curl, CURLOPT_INFILESIZE, $size );
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_PUT, 4);
                curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl']);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                $errNo = curl_errno($curl);
                if( $errNo ){
                    error_log("replica: ".$forwardInfo['replica']." - sending data to ".$forwardInfo['forwardUrl']." via curl failed.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                    $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'] );
                }
            } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
            curl_close($curl);
            fclose($fh);
        }
    }

    /**
     * here is where we start the delete process during a move operation.
     * this will only be called after the file in question has been moved to
     * all of its new locations (nodes, servers, etc.)
     *
     * @param <type>
     */
    protected function startDeleteForMove( ){
        $deleteForMoveConfig = $this->configArray[ $this->params['moveConfigIndex'] ];
        $locClass = $deleteForMoveConfig['locatorClassName'];
        $locator = new $locClass( $deleteForMoveConfig );
        $nodes = $locator->findNodes( $this->params['name'] );
        $replicationDegree = $locator->getReplicationDegree();
        $position = $this->getTargetNodePosition( $nodes );
        $forwardInfo = $this->getForwardInfo( null, null, $replicationDegree, $nodes );
        
        if( $forwardInfo ){
            $errNo = 0;
            $origPosition = $forwardInfo['position'];
            $curl = curl_init();
            $headers = array();
            do{
                $headers[0] = self::HEADER_MOVE_CONTEXT.': delete';
                // here we have to switch the config index headers so that
                // we start the correct path for deletion
                $headers[1] = self::HEADER_MOVE_CONFIG_INDEX.': '.$this->params['configIndex'];
                $headers[2] = self::HEADER_CONFIG_INDEX.': '.$this->params['moveConfigIndex'];
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );

                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "MOVE");
                curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl'] );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                $errNo = curl_errno($curl);
                if( $errNo ){
                    error_log("replica ".$forwardInfo['replica']." : forwarding a move cmd (start) in delete context to ".$forwardInfo['forwardUrl']." failed using curl.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                    $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'], $replicationDegree, $nodes );
                } else {
                    error_log("replica ".$forwardInfo['replica']." : forwarded a move cmd (start) in delete context to ".$forwardInfo['forwardUrl'] );
                }
            } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
            curl_close($curl);
        }
    }

    protected function sendDeleteForMove( ){
        $forwardInfo = $this->getForwardInfo();
        if( $forwardInfo ){
            $errNo = 0;
            $origPosition = $forwardInfo['position'];
            $curl = curl_init();
            $headers = array();
            do{
                $headers[0] = self::HEADER_REPLICA.': '.$forwardInfo['replica'];
                $headers[1] = self::HEADER_POSITION.': '.$forwardInfo['position'];
                $headers[2] = self::HEADER_MOVE_CONTEXT.': delete';
                $headers[3] = self::HEADER_MOVE_CONFIG_INDEX.': '.$this->params['moveConfigIndex'];
                $headers[4] = self::HEADER_CONFIG_INDEX.': '.$this->params['configIndex'];
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );

                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "MOVE");
                curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl'] );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                $errNo = curl_errno($curl);
                if( $errNo ){
                    error_log("replica ".$forwardInfo['replica']." : forwarding a move cmd in delete context to ".$forwardInfo['forwardUrl']." failed using curl.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                    $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'] );
                } else {
                    error_log("replica ".$forwardInfo['replica']." : forwarded a move cmd in delete context to ".$forwardInfo['forwardUrl'] );
                }
            } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
            curl_close($curl);
        }
    }
    
    protected function sendStartMoveCmd( $locator ){
        $nodes = $locator->findNodes( $this->params['name'] );
        $replicationDegree = $locator->getReplicationDegree();
        $forwardInfo = $this->getForwardInfo( null, null, $replicationDegree, $nodes );
        if( $forwardInfo ){
            $errNo = 0;
            $origPosition = $forwardInfo['position'];
            $curl = curl_init();
            $headers = array();
            do{
                $headers[0] = self::HEADER_MOVE_CONTEXT.': start';
                $headers[1] = self::HEADER_MOVE_CONFIG_INDEX.': '.$this->params['moveConfigIndex'];
                $headers[2] = self::HEADER_CONFIG_INDEX.': '.$this->params['configIndex'];
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );

                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "MOVE");
                curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl'] );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                $errNo = curl_errno($curl);
                if( $errNo ){
                    error_log("replica ".$forwardInfo['replica']." : forwarding a move cmd in start context to ".$forwardInfo['forwardUrl']." failed using curl.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                    $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'], $replicationDegree, $nodes );
                } else {
                    error_log("replica ".$forwardInfo['replica']." : forwarded a move cmd in start context to ".$forwardInfo['forwardUrl'] );
                }
            } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
            curl_close($curl);
        }
    }

    protected function sendDataToStartMove( ){
        $filePath = $this->finalPath;
        $forwardInfo = $this->getForwardInfo( );
        if( $forwardInfo ){
            $fh = fopen($filePath, "rb");
            if( $fh ){
                $size = filesize( $filePath );
                rewind($fh);

                $errNo = 0;
                $origPosition = $forwardInfo['position'];
                $curl = curl_init();
                $headers = array();
                do{
                    $headers[0] = self::HEADER_MOVE_CONTEXT.': create';
                    $headers[1] = self::HEADER_MOVE_CONFIG_INDEX.': '.$this->params['moveConfigIndex'];
                    $headers[2] = self::HEADER_CONFIG_INDEX.': '.$this->params['configIndex'];
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
                    
                    curl_setopt($curl, CURLOPT_UPLOAD, true);
                    curl_setopt($curl, CURLOPT_INFILE, $fh);
                    curl_setopt($curl, CURLOPT_INFILESIZE, $size );
                    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "MOVE");
                    curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl']);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($curl);
                    $errNo = curl_errno($curl);
                    if( $errNo ){
                        error_log("replica: ".$forwardInfo['replica']." - sending data for a move in create context to ".$forwardInfo['forwardUrl']." via curl failed.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                        $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'] );
                    }else {
                        error_log("forwarded $filePath to start a move cmd in create context to ".$forwardInfo['forwardUrl'] );
                    }
                } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
                curl_close($curl);
                fclose($fh);
            } else {
                $msg = "received command in start context to move ".$this->params['name']." but cannot find file!";
                error_log($msg);
                throw new MoveException($msg);
            }
        }
    }

    protected function sendDataForMove( ){
        $filePath = $this->finalPath;
        $forwardInfo = $this->getForwardInfo( );
        if( $forwardInfo ){
            $fh = fopen($filePath, "rb");
            $size = filesize( $filePath );
            rewind($fh);

            $errNo = 0;
            $origPosition = $forwardInfo['position'];
            $curl = curl_init();
            $headers = array();
            do{
                $headers[0] = self::HEADER_REPLICA.': '.$forwardInfo['replica'];
                $headers[1] = self::HEADER_POSITION.': '.$forwardInfo['position'];
                $headers[2] = self::HEADER_MOVE_CONTEXT.': create';
                $headers[3] = self::HEADER_MOVE_CONFIG_INDEX.': '.$this->params['moveConfigIndex'];
                $headers[4] = self::HEADER_CONFIG_INDEX.': '.$this->params['configIndex'];
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );

                curl_setopt($curl, CURLOPT_UPLOAD, true);
                curl_setopt($curl, CURLOPT_INFILE, $fh);
                curl_setopt($curl, CURLOPT_INFILESIZE, $size );
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "MOVE");
                curl_setopt($curl, CURLOPT_URL, $forwardInfo['forwardUrl']);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                $errNo = curl_errno($curl);
                if( $errNo ){
                    error_log("replica: ".$forwardInfo['replica']." - sending data for a move in create context to ".$forwardInfo['forwardUrl']." via curl failed.  curl error code: ".curl_errno($curl)." curl error message: ".curl_error($curl)." |||| response: $response" );
                    $forwardInfo = $this->getForwardInfo( $forwardInfo['replica'], $forwardInfo['position'] );
                }else {
                    error_log("replica ".$forwardInfo['replica']." : copied data and forwarded a move cmd in create context to ".$forwardInfo['forwardUrl'] );
                }
            } while( $errNo && $origPosition != $forwardInfo['position'] && $forwardInfo );
            curl_close($curl);
            fclose($fh);
        }
    }

    public function iAmATarget( $targetNodes = null ){
        $isTarget = false;
        $targetPos = $this->getTargetNodePosition( $targetNodes );
        if( is_numeric( $targetPos ) && $targetPos > self::POSITION_NONE ){
            $isTarget = true;
        } else {
            $isTarget = false;
        }
        return $isTarget;
    }

}
