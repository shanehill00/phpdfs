<?php

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
     * @param <type> $locator
     * @param <type> $config
     * @param <type> $params
     */

    public function __construct( $locator, $config, $params ){
        $this->locator = $locator;
        $this->config = $config;
        $this->params = $params;

        $this->finalPath = join($config['pathSeparator'],array($config['storageRoot'],$params['name']));
        $this->tmpPath = join($config['pathSeparator'],array($config['tmpRoot'],uniqid(null, true)));
    }

    public function spoolFile( ){

        // write stdin to a temp file
        $tmpFH = fopen($this->tmpPath, "w");
        $putData = fopen("php://input", "r");

        while ($data = fread($putData, 1024))
          fwrite($tmpFH, $data);

        fclose($tmpFH);
        fclose($putData);
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

    /**
     *
     */
    public function forwardData( ){
        $targetNodes = $this->getTargetNodes();
        if( $this->iAmATarget() ){
            // check whether or not we are done replicating.
            //
            // replicas are identified by the replica number.
            // replica numbers start at 0, so we check to see
            // if the replica number value is less than the max replicas minus 1
            // if yes, that means we need to continue to replicate
            // if not, that means we are done replicating and can quietly exit
            $replica = (int) $this->params['replica'];
            $replicationDegree = (int) $this->config['replicationDegree'];
            if( $replica < ( $replicationDegree - 1 ) ) {

                $position = $this->params['position'];
                if( !is_numeric( $position ) ){
                    $position = $this->getNodePosition( $this->params, $targetNodes, $this->config );
                }
                $replica++;
                $position++;
                $position %= count( $targetNodes );

                $url = "http://".join("/", array( $targetNodes[$position]['host'], $this->params['name'], $replica, $position ) );
                $this->sendData( $this->finalPath, $url );
            }
        } else {
            $url = "http://".$targetNodes[0]['host']."/".$this->params['name'];
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
        $response = ""; // curl_exec($ch);
        print_r( array("curl_exec",$from, $url, $response, stream_get_meta_data($fh), curl_errno($ch) ) );
        fclose($fh);
    }

    /** LOOP ALERT
    * we can end up in a loop if for some reaon we find
    * an ip in the http_host that does not match what is in the configuration
    * and we end up forwarding to an ip address that resolves back to this machine.
    *
    * or if we do not have a http_host in the environment and we use the 'thisHost'
    * config value and that is wrong
    *
    * so the moral of the story is to make sure that either the $_SERVER['HTTP_HOST'] or the "myIp"
    * setting matches one of the hosts in the node config  or we make the assumption that this server
    * is just to act as a forwarding agent for the data on stdin.
    *
    */

    public function getNodePosition( ){
        if( is_null( $this->nodePosition ) ) {
            // negative one means this node has no position
            $this->nodePosition = -1;
            $thisHost = '';
            if( isset( $this->config['thisHost'] ) &&  $this->config['thisHost'] ){
                $thisHost = $this->config['thisHost'];
            } else if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] ){
                $thisHost = $_SERVER['HTTP_HOST'];
            } else {
                throw new Exception(
                    "No ip address available for checking.  Please add one to the 'thisHost' configuration value ".
                    "or make sure that the _SERVER['HTTP_HOST'] var has a value"
                );
            }

            $targetNodes = $this->getTargetNodes();
            $n = 0;
            foreach( $targetNodes as $node ){
                if( $node['host'] == $thisHost ){
                    $isTarget = true;
                    $this->nodePosition = $n;
                    break;
                }
                $n++;
            }
        }
        return $this->nodePosition;
    }

    public function iAmATarget( ){
        $isTarget = $this->getNodePosition( );
        if( is_numeric( $isTarget ) && $isTarget >= 0 ){
            $isTarget = true;
        } else {
            $isTarget = false;
        }
        return $isTarget;
    }

}
