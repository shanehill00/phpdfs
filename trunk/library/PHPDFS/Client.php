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

class PHPDFS_Client{

    /**
     * the data locator that is used for looking up the
     * location of an object.
     *
     * @var <PHPDFS_DataLocator_HonickyMillerR>
     */
    
    protected $locator = null;

    public function __construct( $config ){
        require_once( $config['locatorClassPath'] );
        $locatorClassName = $config['locatorClassName'];
        $this->locator = new $locatorClassName( $config );
        $this->config = $config;
    }

    public function get( $fileId ){
        $paths = $this->getPaths($fileId);
        if( count( $paths ) ) {
            $url = join('/', array($paths[0]['url'],$fileId ) );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            return curl_exec($ch);
        }
    }

    public function getPaths( $fileId ){
        $paths = array();
        $nodes = $this->locator->findNodes( $fileId );
        foreach( $nodes as $node ){
            $paths[]['url'] = $node['proxyUrl'].'/'.$fileId;
        }
        return $paths;
    }

    public function delete( $fileId ){
        $paths = $this->getPaths($fileId);
        if( count( $paths ) ) {
            $url = join('/', array($paths[0]['url'],$fileId ) );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
        }
    }

    public function put( $fileId, $filePath ){
        $this->set( $fileId, $filePath );
    }

    public function set( $fileId, $filePath ){
        $paths = $this->getPaths($fileId);
        if( count( $paths ) ) {
            $url = join('/', array($paths[0]['url'],$fileId ) );
            $fh = fopen($filePath, "r");
            $size = filesize( $filePath );
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
    }

}