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

// this is the file that handles the uploads into the HM RUSH system

require_once("PHPDFS.php");
require_once("PHPDFS/Helper.php");

$config = PHPDFS_Helper::getConfig();
$params = PHPDFS_Helper::getParamsFromUrl();

$dfs = new PHPDFS( $config, $params );

if( $_SERVER['REQUEST_METHOD'] == 'GET' ){
    $dfs->getData();

} else if( $_SERVER['REQUEST_METHOD'] == 'PUT' ){

    // get the data from stdin and put it in a temp file
    // we will disconnect the client at this point if we are configured to do so
    // otherwise we hang on to the client, which in most cases is really bad
    // because you might stay connected until the replication chain is completed
    $dfs->spoolData( );

    // save the data to the appropriate directory and remove the spooled file
    // but only if we are a targetNode, otherwise DO NOTHING
    $dfs->saveData( );

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
    $dfs->forwardData( );
}

