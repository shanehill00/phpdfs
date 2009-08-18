<?php

$config = getConfig();

checkExts();
checkAutoMove( $config );
checkDisconnectAfterSpooling( $config );
checkSpoolReadSize( $config );
checkDebug($config);
checkMagicDbPath( $config );
checkProxyUrl( $config );
checkDataConfig( $config );
configOK( $config );


/*
 * check to see that we have all of the libs we need and that we are running
 * a correct version of php
 *
 * we recommend at least 5.2.0
 *
 * the list of required extensions / modules / libraries are as follows:
 *
 * pecl_http
 * uuid (pecl extension)
 * curl
 * fileinfo
 * filter
 * pcre
 *
 */

function checkExts(){
    // checkl the php version
    $reqVersion = '5.2.0';
    $version = phpversion();
    if( version_compare( $version, $reqVersion, "<" ) ){
        badVersion();
    }

    // check for pecl_http
    if( !extension_loaded("http") ) noExtHttp();

    // check for pecl uuid
    if( !extension_loaded("uuid") ) noExtUuid();

    // check for libcurl
    if( !extension_loaded("curl") ) noExtCurl();
    
    // check for fileinfo
    if( !extension_loaded("fileinfo") ) noExtFileInfo();
    
    // check for filter
    if( !extension_loaded("filter") ) noExtFilter();

    // check for filter
    if( !extension_loaded("pcre") ) noExtPcre();
}


function checkDataConfig( $config ){
    if( !isset($config['data']) ) noDataConfig();

    $dataConfig = $config['data'];
    if( (count($dataConfig) <= 0 ) ) badDataConfig();

    // check the cluster configs
    $whichConfig = 0;
    foreach( $dataConfig as $clusterConf ){
        // first we check that we can load and instantiate the locator class
        require_once( $clusterConf['locatorClassPath'] );
        $locClass = $clusterConf['locatorClassName'];
        $locator = new $locClass( $clusterConf );

        if( !isset( $clusterConf['clusters'] ) ) noClusterConf();
        
        if( !count( $clusterConf['clusters'] ) ) badClusterConf();

        $whichCluster = 0;
        $totalNodes = 0;
        foreach( $clusterConf['clusters'] as $cluster ){
            
            if( !isset( $cluster['weight'] ) ) noWeightKey();

            if( $cluster['weight'] <= 0 ) badWeight();

            if( !isset( $cluster['nodes'] ) ) noNodeConfig();

            if( !count(  $cluster['nodes'] ) ) badNodeConfig();

            // iterate the nodes and check the proxyUrl is valid
            $whichNode = 0;
            foreach( $cluster['nodes'] as $node ){
                if( !filter_var( $node['proxyUrl'], FILTER_VALIDATE_URL ) )
                    badProxyUrl( $whichNode, $whichCluster, $whichConfig );
                $whichNode++;
            }
            $totalNodes += count( $cluster['nodes'] );
            $whichCluster++;
        }

        if( !isset( $clusterConf['replicationDegree'] ) )
            noReplicationDegree( $whichConfig );

        $replicationDegree = $clusterConf['replicationDegree'];
        if( $replicationDegree < 1 )
            insufficientReplicationDegree( $replicationDegree, $whichConfig );
            

        if( $replicationDegree > $totalNodes )
            badReplicationDegree( $replicationDegree,  $totalNodes, $whichConfig );

        if( !isset( $clusterConf['tmpRoot'] ) ) noTmpRoot();
    
        if( !file_exists( $clusterConf['tmpRoot'] ) ){
            $tmpRoot = $clusterConf['tmpRoot'] ;
            badTmpRoot( $tmpRoot );
        }


        if( !isset( $clusterConf['storageRoot'] ) ) noStorageRoot();

        if( !file_exists( $clusterConf['storageRoot'] ) ){
            $storageRoot = $clusterConf['storageRoot'] ;
            badStorageRoot( $storageRoot );
        }

        if( $clusterConf['storageRoot'] == $clusterConf['tmpRoot'] ) sameRootError( $storageRoot, $tmpRoot );

        $whichConfig++;
    }
    
}

function checkProxyUrl( $config ){
    if( !isset($config['thisProxyUrl']) ){
        echo("
ERROR:
Your config does not appear to have an 'thisProxyUrl' key!
Please add an 'thisProxyUrl' value to your config.
Refer to the documentation to read about the config values.
==================
");
        die();
    }
    $url = $config['thisProxyUrl'];
    // check the validity of the url
    if(!filter_var($url, FILTER_VALIDATE_URL)){
        echo("
ERROR:
The proxy url for the 'thisProxyUrl' key does not appear to be a valid URL!
Please add a valid thisProxyUrl value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }

}

function checkMagicDbPath( $config ){
    if( !isset($config['magicDbPath']) ){
        echo("
ERROR:
Your config does not appear to have an 'magicDbPath' key!
Please add an 'magicDbPath' value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }

    $mdb = $config['magicDbPath'];

    if( !@finfo_open( FILEINFO_MIME, $mdb ) ){
        echo("
ERROR:
Your magicDbPath $mdb does not appear to exist.
Are you sure that the fileinfo stuff is installed?
If so, Please add the correct magicDbPath value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }
}

function checkDisconnectAfterSpooling( $config ){
    if( !isset($config['disconnectAfterSpooling']) ){
        echo("
ERROR:
Your config does not appear to have a 'disconnectAfterSpooling' key!
Please add an 'disconnectAfterSpooling' value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }

}

function checkSpoolReadSize( $config ){
    if( !isset($config['spoolReadSize']) ){
        echo("
ERROR:
Your config does not appear to have a 'spoolReadSize' key!
Please add a 'spoolReadSize' value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }

    if( $config['spoolReadSize'] < 1){
        echo("
ERROR:
The spoolReadSize is set to a value less than 1
please set the spoolReadSize to a positive value
spoolReadSize: ".$config['spoolReadSize']."
==================
");
        exit();
    }

}

function checkDebug( $config ){
    if( !isset($config['debug']) ){
        echo("
ERROR:
Your config does not appear to have an 'debug' key!
Please add a 'debug' value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }
}

function checkAutoMove( $config ){
    if( !isset($config['autoMove']) ){
        echo("
ERROR:
Your config does not appear to have an 'autoMove' key!
Please add an 'autoMove' value to your config.
Refer to the documentation to read about the config values.
==================
");
        exit();
    }
}

function getConfig(){
    global $argv;
    if( !isset( $argv[1] ) ){
        echo("
ERROR:
Please pass the location of the PHPDFS configuration file to be checked as the first parameter on the command line!
");
        exit();
    }
    $confFile = $argv[1];
    return require $confFile;
}
function getTestConfig(){
    return array(
        // architecture specific
        'autoMove' => false,
        'magicDbPath' => '/sw/share/file/magic',
        'disconnectAfterSpooling' => true,
        'thisProxyUrl' =>  'http://192.168.0.2:80/dfs.php',
        'data' => array(
            array(
                'locatorClassName' => 'PHPDFS_DataLocator_HonickyMillerR',
                'locatorClassPath' => 'PHPDFS/DataLocator/HonickyMillerR.php',

                'storageRoot' => '/tmp/testData',
                'tmpRoot' => '/tmp/testData',

                //  node and cluster config below
                'replicationDegree' => 2,
                'clusters' => array(
                    // cluster 1
                    array(
                        'weight' => 1,
                        'nodes' =>array(
                             array('proxyUrl' => 'http://192.168.0.2:80/dfs.php',), // cluster 1 node 1 (1)
                             array('proxyUrl' => 'http://192.168.0.6:80/dfs.php',), // cluster 1 node 2 (2)
                         ),
                     ),
                  ),
              ),
          ),
    );
}

function noExtCurl(){
        echo("
ERROR:
It appears that libcurl extension is not installed or is not loaded!
You need lib curl to be installed and working to be able to use PHPDFS
==================
");
        exit();
}

function noExtUuid(){
        echo("
ERROR:
It appears that the pecl uuid extension is not installed or is not loaded!
You need pecl uuid to be installed and working to be able to use PHPDFS
==================
");
        exit();
}

function noExtHttp(){
        echo("
ERROR:
It appears that the pecl_http extension is not installed or is not loaded!
You need pecl_http to be installed and working to be able to use PHPDFS
==================
");
    exit();
}

function badVersion(){
        echo("
ERROR:
Your PHP version is $version.  You need at least $reqVersion installed and working.
==================
"
);
    exit();
}

function noExtFileInfo(){
        echo("
ERROR:
It appears that fileinfo extension is not installed or is not loaded!
You need fileinfo to be installed and working to be able to use PHPDFS
==================
");
        exit();
}

function noExtFilter(){
        echo("
ERROR:
It appears that the filter extension is not installed or is not loaded!
You need fileinfo to be installed and working to be able to use PHPDFS
==================
");
        exit();
}

function noExtPcre(){
        echo("
ERROR:
It appears that the pcre extension is not installed or is not loaded!
You need pcre to be installed and working to be able to use PHPDFS
==================
");
        exit();
}

function configOK( $config ){
    echo("
==============================================
|| CONFIG OK!
|| Your PHPDFS config looks ok.  sweet!
|| If you find bugs or problems please report
|| them to shanehill00 <<at>> gmail-com
==============================================
");
}

function noDataConfig(){
        echo("
ERROR:
Your config does not appear to have an 'data' key!
Please add an 'data' value to your config.
Refer to the documentation to read about the config values.
==================
");
        die();
}

function badDataConfig(){
        echo("
ERROR:
Your data config does not appear to have a 'data' key or is not configured with any data!
Please add a 'clusters' value to your data config or configure your clusters.
Refer to the documentation to read about the config values.
==================
");
        die();
}

function noClusterConf(){
            echo("
ERROR:
Your data config does not appear to have a 'clusters' key!
Please add a 'clusters' config to your data config
Refer to the documentation to read about the config values.
==================
");
            exit();
}

function badClusterConf(){
            echo("
ERROR:
Your data config 'clusters' property does not
appear to be configured!
Please configure the 'clusters' property.
Refer to the documentation to read about the config values.
==================
");
            exit();
}

function noWeightKey(){
                echo("
ERROR:
Your cluster config does not appear to have a 'weight' key!
Please add a 'weight' config to your cluster config.
Refer to the documentation to read about the config values.
==================
");
                exit();
}

function badWeight(){
                echo("
ERROR:
Your cluster weight does not appear to be correct.
The cluster weight must be greater than or equal to 0 to be valid.
Refer to the documentation to read about the config values.
==================
");
                exit();
}

function noNodeConfig(){
                echo("
ERROR:
Your cluster config does not appear to have a 'nodes' key!
Please add a 'nodes' config to your cluster config.
Refer to the documentation to read about the config values.
==================
");
                exit();
}

function badNodeConfig(){
                echo("
ERROR:
Your data config 'clusters' property does not appear to be configured!
Please configure the 'clusters' property.
Refer to the documentation to read about the config values.
==================
");
                exit();
}

function badProxyUrl( $whichNode, $whichCluster, $whichConfig ){
                    echo("
ERROR:
The proxyUrl value for node $whichNode in cluster $whichCluster in config $whichConfig
does not appear to be a valid URL!
Please assign a valid URL to proxyUrl in your config.
Refer to the documentation to read about the config values.
==================
");
                    exit();
}

function noTmpRoot(){
            echo("
ERROR:
Your data config does not appear to have a 'tmpRoot' key!
Please add a 'tmpRoot' value to your data config
Refer to the documentation to read about the config values.
==================
");
            exit();
}

function badTmpRoot(){
            echo("
ERROR:
Cannot find $tmpRoot.
Please create $tmpRoot
and make sure it is writable and readable by your web server.
==================
");
            exit();
}

function noStorageRoot(){
            echo("
ERROR:
Your data config does not appear to have a 'storageRoot' key!
Please add a 'storageRoot' value to your data config
Refer to the documentation to read about the config values.
==================
");
            exit();
}

function badStorageRoot( $storageRoot ){
            echo("
ERROR:
Cannot find $storageRoot.
Please create $storageRoot
and make sure it is writable and readable by your web server.
Refer to the documentation to read about the config values.
==================
");
            exit();
}

function sameRootError( $storageRoot, $tmpRoot ){
            echo("
ERROR:
The storage root ($storageRoot) and the temp root ($tmpRoot) are the same directory.
Please choose different directories for these values
Refer to the documentation to read about the config values.
==================
");
            exit();
}

function badReplicationDegree( $replicationDegree,  $totalNodes, $whichConfig ){
            echo("
ERROR:
The replication degree exceeds the number of nodes in the config number $whichConfig
replication degree $replicationDegree
total nodes: $totalNodes
==================
");
            exit();
}

function noReplicationDegree( $whichConfig ){
            echo("
ERROR:
No replication degree has been declared for config number $whichConfig
==================
");
            exit();
}

function insufficientReplicationDegree( $replicationDegree, $whichConfig ){
            echo("
ERROR:
The replication degree for config number $whichConfig is less than 1
replication degree: $replicationDegree
==================
");
            exit();
}
