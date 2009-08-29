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


// get the address off of the command line amnd make the appropriate directories

$nodeData = file($argv[1]);

makeClusterConf( $nodeData );
makeBaseUrls( $nodeData );
makeCommandFiles($nodeData);

function makeClusterConf( $nodeData ){
    $nodeStr = makeNodeStr( $nodeData );

    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];

        $root = "./configs/$type";
        @mkdir("./configs/clusterConf/$privateDns", 0777, true);
        @mkdir("$root/$privateDns/$privateIp/", 0777, true);
        @mkdir("$root/$privateDns/$publicDns/", 0777, true);

        $confTemplate = file_get_contents('confTemplate');

        $thisProxy = "http://$privateIp/dfs.php";
        $confTemplate = preg_replace('/THISPROXY/', $thisProxy, $confTemplate);
        $confTemplate = preg_replace('/NODES/', $nodeStr, $confTemplate);
        $confTemplate = preg_replace('/REPLICATION/', $replication, $confTemplate);

        file_put_contents("./configs/clusterConf/$privateDns/cluster_config.php", $confTemplate );

    }
}

function makeNodeStr( $nodeData ){
    $configStr = array();
    $nodeStr = array();
    $n = 1;
    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];
        
        if( $type == 'server' ){
            $nodeStr[] = "
                         array(
                            'proxyUrl' => 'http://$privateIp/dfs.php',
                            'staticUrl' => 'http://$privateIp/data'
                         ),
                        ";
            if( ($n % 3) == 0 ){
                $nodeStr = join("\n",$nodeStr);
                $nodeStr = "
                    array(
                        'weight' => 1,
                        'nodes' => array($nodeStr),
                     ),
                    ";
                $configStr[] = $nodeStr;
                $nodeStr = array();
            }
        }
        $n++;
    }

    $configStr = join( "\n", $configStr );
    return $configStr;
}

function makeBaseUrls( $nodeData ){
    $baseUrls = array();
    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];

        if( $type == 'server' ){
            $baseUrls[] = 'http://'.$privateIp.'/dfs.php';
        }
    }
    $baseUrls = join("\n",$baseUrls);
    file_put_contents("./configs/baseUrls", $baseUrls );
}

function makeCommandFiles( $nodeData ){
    $cmdStr = array();

    // make the copy commands for the cluster configs
    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];
        
        if( $type == 'server' ){
            $cmdStr[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "./clusterConf/$privateDns/cluster_config.php ".
            "root@$publicDns".
            ':/home/phpdfs/phpdfs/conf/;';
        }
    }
    $cmdStr = join("\n",$cmdStr);
    file_put_contents("./configs/copyConfs.sh", $cmdStr );


    $cmdStr = array();
    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];
        
        if( $type == 'client' ){
            $cmdStr[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "baseUrls ".
            "root@$publicDns".
            ':/home/phpdfs/phpdfs/conf/;';
        }
    }
    $cmdStr = join("\n",$cmdStr);
    file_put_contents("./configs/copybaseUrls.sh", $cmdStr );

    // make the server and client host files for use with csshX
    $clients = array();
    $servers = array();
    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];

        $host = 'root@'.$publicDns;
        if( $type == 'client' ){
            $clients[] = $host;
        } else if( $type == 'server'){
            $servers[] = $host;
        }
    }
    $servers = join("\n",$servers);
    file_put_contents("./configs/serverHosts", $servers );

    $clients = join("\n",$clients);
    file_put_contents("./configs/clientHosts", $clients );

    $server = array();
    $client = array();
    foreach( $nodeData as $nodeInfo ){
        $nodeInfo = trim($nodeInfo);
        if(!$nodeInfo){
            continue;
        }
        $info = split(',',$nodeInfo);
        $publicDns = $info[0];
        $privateDns = $info[1];
        $privateIp = $info[2];
        $type = $info[3];
        $replication = $info[4];

        if( $type == 'client' ){
            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/phpdfs.stats.txt ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/client_stats.$publicDns.txt";
            
            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/uploadedFiles ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/uploadedFiles.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/downloadedFiles ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/downloadedFiles.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/uploadIOExceptions ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/uploadIOExceptions.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/uploadHttpExceptions ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/uploadHttpExceptions.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/uploadResponseErrors ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/uploadResponseErrors.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/downloadIOExceptions ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/downloadIOExceptions.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/downloadHttpExceptions ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/downloadHttpExceptions.$publicDns.txt";

            $client[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/downloadErrors ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/downloadErrors.$publicDns.txt";
        }

        if( $type == 'server'){
            $server[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/tmp/dataTotals.txt ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/dataTotals.$publicDns.txt";

            $server[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/mnt/fileIndex ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/fileIndex.$publicDns.txt";
            
            $server[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/mnt/dstat.stats ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/dstats.$publicDns.csv";

            $server[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/usr/local/apache2/logs/access_log ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/$publicDns.access_log";

            $server[] = 'scp -i /Users/shane/downloads/phpdfs-0.pem  '.
            "root@$publicDns:/usr/local/apache2/logs/error_log ".
            "/Users/shane/dev/phpdfs/installs/test/statData/c5n3-2/$publicDns.error_log";
        }
    }
    $client = join("\n",$client);
    $server = join("\n",$server);
    file_put_contents("./configs/downloadClientTestResults.sh", $client );
    file_put_contents("./configs/downloadServerTestResults.sh", $server );

}
