<?php

// this is the file that handles the uploads into the HM RUSH system

require_once("PHPDFS.php");
require_once("PHPDFS/Helper.php");

$config = PHPDFS_Helper::getConfig();
$params = PHPDFS_Helper::getParamsFromUrl();

$dfs = new PHPDFS( $config, $params );

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

