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

require_once 'PHPDFS/Client.php';
require_once 'PHPDFS/Helper.php';

if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
    handleUpload();
} else if( isset($_GET['delete']) ) {
    deleteFile( $_GET['delete'] );
} else if( isset( $_GET['get'] ) ) {
    getFile( $_GET['get'] );
} else {
    showForm();
}

function showForm(){
    echo('
        <html>
        <body>
        <form action="/upload.php" enctype="multipart/form-data" method="post">
        <p>
        filename<br>
        <input type="text" name="name" size="30">
        </p>
        <p>
        Please specify a file, or a set of files:<br>
        <input type="file" name="datafile" size="40">
        </p>
        <div>
        <input type="submit" value="Send">
        </div>
        </form>
        </body>
        </html>
    ');

}

function handleUpload(){

    if( isset($_FILES['datafile']['tmp_name'])){
        // first create the PHPDFS client.
        $config = PHPDFS_Helper::getConfig();
        $client = new PHPDFS_Client( $config );

        // set the uploaded file in PHPDFS
        $filepath = $_FILES['datafile']['tmp_name'];
        $name = $_POST['name'];
        
        $client->put($name, $filepath);

        // now get the urls where the file
        // can be found
        $paths = $client->getPaths( $name );
        $urls = array();
        foreach( $paths as $path ){
            $url = $path['url'];
            $urls[] = "<a href='$url'>$url</a> --- <a href='?delete=$name'>delete $name (deletes all replicas)</a> -- fetch <a href='?get=$name'>$name</a> using the PHPDFS_Client<br>";
        }
        showMessage($urls);
    }

}

function getFile( $name ){
    $config = PHPDFS_Helper::getConfig();
    $client = new PHPDFS_Client( $config );
    $data = "";
    try{
        $data = $client->get($name);
        $finfo = finfo_open( FILEINFO_MIME, $config["magicDbPath"] );
        $contentType = finfo_buffer( $finfo, $data );
        finfo_close( $finfo );
        header( "Content-Type: $contentType");
        header( "Content-Length: ".strlen( $data ) );
    } catch( PHPDFS_Exception_GetException $e ) {
        $errData = $e->getData();
        if( $errData['httpCode'] > 400 ){
            header(  $_SERVER['SERVER_PROTOCOL']." ".$errData['httpCode'] ) ;
            $data = $errData['body'];
        }
    }

    echo( $data );
}

function deleteFile( $name ){
    $config = PHPDFS_Helper::getConfig();
    $client = new PHPDFS_Client( $config );
    $client->delete($name);

    echo("$name was deleted. woo hoo!<br>try accessing the URLs below.<br><br>\n");

    $paths = $client->getPaths( $name );
    $urls = array();
    foreach( $paths as $path ){
        $url = $path['url'];
        echo "<a href='$url'>$url</a><br>\n";
    }
}

function showMessage($urls){
    $urls = join("<br>", $urls );
    echo("
        <html>
        <body>
        below are the URLs where you can find the image you just uploaded.  woo hoo!
        <p>
        $urls
        </p>
        </body>
        </html>
    ");

}

