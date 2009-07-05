<?php
/**
pass the conf file on the command line

we require_once the conf file

print the value of the automove
print the value of disconnectAfterSpooling
check that the magicDbPath exists
check that the proxyUrl is of the correct form
make sure that there is at least one data config and that it contains at least one config array
check that we can require the locator class and instantiate a new instance of the locator
check that the storage and temp roots are not the same and that they are both writable
or at least report on the writability

iterate the data configs and check
    that the replication degree does not exceed the number of nodes in the first cluster
    check that the proxyUrl in each node is a valid url
    check that weight is filled in with an int
*/

$config = getConfig();

checkExts();
checkAutoMove( $config );
checkDisconnectAfterSpooling( $config );
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
        echo("
ERROR:
Your PHP version is $version.  You need at least $reqVersion installed and working.
==================
");
        exit();
    }

    // check for pecl_http
    if( !extension_loaded("http") ){
        echo("
ERROR:
It appears that the pecl_http extension is not installed or is not loaded!
You need pecl_http to be installed and working to be able to use PHPDFS
==================
");
        exit();
    }

    // check for pecl uuid
    if( !extension_loaded("uuid") ){
        echo("
ERROR:
It appears that the pecl uuid extension is not installed or is not loaded!
You need pecl uuid to be installed and working to be able to use PHPDFS
==================
");
        exit();
    }

    // check for libcurl
    if( !extension_loaded("curl") ){
        echo("
ERROR:
It appears that libcurl extension is not installed or is not loaded!
You need lib curl to be installed and working to be able to use PHPDFS
==================
");
        exit();
    }
    
    // check for fileinfo
    if( !extension_loaded("fileinfo") ){
        echo("
ERROR:
It appears that fileinfo extension is not installed or is not loaded!
You need fileinfo to be installed and working to be able to use PHPDFS
==================
");
        exit();
    }
    
    // check for filter
    if( !extension_loaded("filter") ){
        echo("
ERROR:
It appears that the filter extension is not installed or is not loaded!
You need fileinfo to be installed and working to be able to use PHPDFS
==================
");
        exit();
    }

    // check for filter
    if( !extension_loaded("pcre") ){
        echo("
ERROR:
It appears that the pcre extension is not installed or is not loaded!
You need pcre to be installed and working to be able to use PHPDFS
==================
");
        exit();
    }
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

function checkDataConfig( $config ){
    if( !isset($config['data']) ){
        echo("
ERROR:
Your config does not appear to have an 'data' key!
Please add an 'data' value to your config.
Refer to the documentation to read about the config values.
==================
");
        die();
    }

    $dataConfig = $config['data'];
    if( (count($dataConfig) <= 0 ) ){
        echo("
ERROR:
Your data config does not appear to have a 'clusters' key or is not configured with any data!
Please add a 'clusters' value to your data config or configure your clusters.
Refer to the documentation to read about the config values.
==================
");
        die();
    }

    // check the cluster configs
    $whichConfig = 0;
    foreach( $dataConfig as $clusterConf ){
        // first we check that we can load and instantiate the locator class
        require_once( $clusterConf['locatorClassPath'] );
        $locClass = $clusterConf['locatorClassName'];
        $locator = new $locClass( $clusterConf );

        if( !isset( $clusterConf['clusters'] ) ){
            echo("
ERROR:
Your data config does not appear to have a 'clusters' key!
Please add a 'clusters' config to your data config
Refer to the documentation to read about the config values.
==================
");
            exit();
        }
        
        if( !count( $clusterConf['clusters'] ) ){
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

        $whichCluster = 0;
        foreach( $clusterConf['clusters'] as $cluster ){
            
            if( !isset( $cluster['weight'] ) ){
                echo("
ERROR:
Your cluster config does not appear to have a 'weight' key!
Please add a 'weight' config to your cluster config.
Refer to the documentation to read about the config values.
==================
");
                exit();
            }

            if( $cluster['weight'] <= 0 ){
                echo("
ERROR:
Your cluster weight does not appear to be correct.
The cluster weight must be greater than 0 to be valid.
Refer to the documentation to read about the config values.
==================
");
                exit();
            }

            if( !isset( $cluster['nodes'] ) ){
                echo("
ERROR:
Your cluster config does not appear to have a 'nodes' key!
Please add a 'nodes' config to your cluster config.
Refer to the documentation to read about the config values.
==================
");
                exit();
            }

            if( !count(  $cluster['nodes'] ) ){
                echo("
ERROR:
Your data config 'clusters' property does not appear to be configured!
Please configure the 'clusters' property.
Refer to the documentation to read about the config values.
==================
");
                exit();
            }
            if( $whichCluster == 0 && ( count( $cluster['nodes'] ) < $clusterConf['replicationDegree'] ) ) {
                echo("
ERROR:
The number of nodes in the first cluster of configuration number $whichConfig
exceeds the replicationDegree value of ".$clusterConf['replicationDegree']." for that cluster configuration.
This will not work and will cause a bad distribution of data
Refer to the documentation to read about the config values.
==================
");
                exit();
            }

            // iterate the nodes and check the proxyUrl is valid
            $whichNode = 0;
            foreach( $cluster['nodes'] as $node ){
                if( !filter_var( $node['proxyUrl'], FILTER_VALIDATE_URL ) ){
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
                $whichNode++;
            }

            $whichCluster++;
        }

        if( !isset( $clusterConf['tmpRoot'] ) ){
            echo("
ERROR:
Your data config does not appear to have a 'tmpRoot' key!
Please add a 'tmpRoot' value to your data config
Refer to the documentation to read about the config values.
==================
");
            exit();
        }
    
        if( !file_exists( $clusterConf['tmpRoot'] ) ){
            $tmpRoot = $clusterConf['tmpRoot'] ;
            echo("
ERROR:
Cannot find $tmpRoot.
Please create $tmpRoot
and make sure it is writable and readable by your web server.
==================
");
            exit();
        }


        if( !isset( $clusterConf['storageRoot'] ) ){
            $tmpRoot = $clusterConf['storageRoot'] ;
            echo("
ERROR:
Your data config does not appear to have a 'tmpRoot' key!
Please add a 'tmpRoot' value to your data config
Refer to the documentation to read about the config values.
==================
");
            exit();
        }

        if( !file_exists( $clusterConf['storageRoot'] ) ){
            $storageRoot = $clusterConf['storageRoot'] ;
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

        if( $clusterConf['storageRoot'] == $clusterConf['tmpRoot'] ){
            echo("
ERROR:
The storage root (".$clusterConf['storageRoot'].") and the temp root (".$clusterConf['tmpRoot'].") are the same directory.
Please choose different directories for these values
Refer to the documentation to read about the config values.
==================
");
            exit();
        }

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

    if( !@finfo_open( FILEINFO_MIME, "/sw/share/file/magic" ) ){
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
Please pass the location of the PHPDFS configuration file to be checked!
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
