<?php

// thi is the file that handles the uploads into the HM RUSH system

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
 *          /filename/num_times_to_replicate
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
require_once("/Users/shane/dev/DataLocator/library/DataLocator/HonickyMillerR.php");

$params = getParamsFromUrl();
$config = getConfig();
$hm = new DataLocator_HonickyMillerR( $config );
$nodes = $hm->findNodes( $params['name'] );

print_r( $nodes );

$destPath = null;
if( iAmATarget( $params, $nodes, $config ) ){
    $destPath = saveData( $params, $config );
}
// disconnect the client at this point
// and continue with the forward process
//
//disconnectClient();

forwardData( $params, $nodes, $config, $destPath );


/////////////////////////////////////////
// FUNCTIONS
/////////////////////////////////////////

function saveData( $params, $config ){

    $finalPath = join($config['pathSeparator'],array($config['storageRoot'],$params['name']));
    $tmpPath = tempnam($config['tmpRoot'],"");

    // write stdin to a temp file
    $tmpFH = fopen($tmpPath, "w");
    $putdata = fopen("php://input", "r");

    while ($data = fread($putdata, 1024))
      fwrite($tmpFH, $data);

    fclose($tmpFH);
    fclose($putdata);


    $copied = copy($tmpPath, $finalPath);

    if( !$copied ){
        // throw exception if the copy failed
        throw new Exception("final copy operation failed");
    }

    unlink($tmpPath);
    return $finalPath;
    
}


/**
 * we open the file at the passed destPath
 * and forward on to the next server if necessary
 * 
 * @param <type> $destPath
 * @param <type> $nodes
 */
function forwardData( $params, $nodes, $config, $dataPath = null ){
    if( !$nodes || empty($nodes) ){
        throw new Exception("an empty nodes param was passed to forward data");
    }

    if( !$dataPath ){
        // we are assuming that no destPath means we are simply forwarding stdin
        forwardStdin( $params, $nodes, $config );
    } else {
        forwardFile( $params, $nodes, $config, $dataPath );
    }
}

function forwardStdin( $params, $nodes, $config ){
    if( count( $nodes ) ){
        $from = "php://input";
        $url = "http://".$nodes[0]['host']."/".$params['name'];
        // sendData( $from, $url );
    }
}


/**
 *
 * @param <type> $urlParams
 * @param <type> $nodes
 * @param <type> $destPath
 */
function forwardFile( $params, $nodes, $config, $filePath ){
    if( count( $nodes ) ){
        // check whether or not we are done replicating.
        //
        // replicas are identified by the replica number.
        // replica numbers start at 0, so we check to see
        // if the replica number value is less than the max replicas minus 1
        // if yes, that means we need to continue to replicate
        // if not, that means we are done replicating and can quietly exit
        $replica = (int) $params['replica'];
        $replicationDegree = (int) $config['replicationDegree'];
        if( $replica < ( $replicationDegree - 1 ) ) {

            $position = $params['position'];
            if( !is_numeric( $position ) ){
                $position = getNodePosition( $params, $nodes, $config );
            }
            $replica++;
            $position++;
            $position %= count( $nodes );
            $url = "http://".join("/", array( $nodes[$position]['host'], $params['name'], $replica, $position ) );
            print_r( array( "forwarding", $filePath, $url ) );
            //sendData( $filePath, $url );
        }
    }
}

function sendData( $from, $url ){
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
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Expect: '));
    $response = curl_exec($ch);
    fclose($fh);
    print_r( array($from, $url, $response ) );
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

function getNodePosition( $params, $nodes, $config ){
    $nodePosition = null;
    $thisHost = '';
    if( isset( $config['thisHost'] ) &&  $config['thisHost'] ){
        $thisHost = $config['thisHost'];
    } else if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] ){
        $thisHost = $_SERVER['HTTP_HOST'];
    } else {
        throw new Exception(
            "No ip address available for checking.  Please add one to the 'thisHost' configuration value ".
            "or make sure that the _SERVER['HTTP_HOST'] var has a value"
        );
    }

    // now check to see if $myIp is in the set of nodes we were passed
    $n = 0;
    foreach( $nodes as $node ){
        if( $node['host'] == $thisHost ){
            $isTarget = true;
            $nodePosition = $n;
            break;
        }
        $n++;
    }
    return $nodePosition;
}

function iAmATarget( $params, $nodes, $config ){
    $isTarget = getNodePosition( $params, $nodes, $config );
    if( is_numeric( $isTarget ) ){
        $isTarget = true;
    } else {
        $isTarget = false;
    }
    return $isTarget;
}

function getConfig(){
    return require 'cluster_config.php';
}

function getParamsFromUrl(){
    $params = array( 'name' => '', 'replica' => 0, 'position' => null );
    if( isset( $_SERVER['PATH_INFO'] ) ){
        $data = trim($_SERVER['PATH_INFO'],'/');
        $data = split('\/', $data);
        
        if( isset( $data[0] ) ){
            $params['name'] = $data[0];
        }

        if( isset( $data[1] ) ){
            $params['replica'] = $data[1];
        }

        if( isset( $data[2] ) ){
            $params['position'] = $data[2];
        }
    }
    return $params;
}

function disconnectClient() {
    // use a 204 no response header
    header( "Response: 204" ) ;
    if( ob_get_length() ){
        ob_end_clean();
    }
    header("Connection: close");
    ob_start();
    header("Content-Length: 0");
    ob_end_flush();
    flush();
    session_write_close();
    flush();
} 

