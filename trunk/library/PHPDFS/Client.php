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

    const PHPDFS_PUT_ERR = 1;
    const PHPDFS_DELETE_ERR = 2;
    const PHPDFS_GET_ERR = 3;

    protected $errs;

    public function __construct( $config ){
        require_once( $config['locatorClassPath'] );
        $locatorClassName = $config['locatorClassName'];
        $this->locator = new $locatorClassName( $config );
        $this->config = $config;

        $this->errs = array(
            self::PHPDFS_PUT_ERR => array(
                'class' => 'PHPDFS_Client_PutException',
                'msg'   => 'PUT Exception',
                'require_once' => 'PHPDFS/Client/PutException.php'
            ),
            self::PHPDFS_DELETE_ERR => array(
                'class' => 'PHPDFS_Client_DeleteException',
                'msg'   => 'DELETE Exception',
                'require_once' => 'PHPDFS/Client/DeleteException.php'
            ),
            self::PHPDFS_GET_ERR => array(
                'class' => 'PHPDFS_Client_GetException',
                'msg'   => 'GET Exception',
                'require_once' => 'PHPDFS/Client/GetException.php'
            ),
        );
    }

    /**
     * @param <string> $fileId
     *
     * @throws PHPDFS_Client_GetException
     */
    public function get( $fileId ){
        $paths = $this->getPaths($fileId);
        if( count( $paths ) ) {
            $url = $paths[0]['url'];
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_exec($curl);
            $this->checkError( $curl, self::PHPDFS_GET_ERR );
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

    /**
     *
     * @param <string> $fileId
     * @throws PHPDFS_Client_GetException
     */
    public function delete( $fileId ){
        $paths = $this->getPaths($fileId);
        $url = $paths[0]['url'];
        if( count( $paths ) ) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            $this->checkError( $curl, self::PHPDFS_DELETE_ERR, $response );
        }
    }

    /**
     * @param <string> $fileId
     * @param <string> $filePath
     *
     * @throws PHPDFS_Client_PutException
     */
    public function put( $fileId, $filePath ){
        $this->set( $fileId, $filePath );
    }

    /**
     * @param <string> $fileId
     * @param <string> $filePath
     *
     * @throws PHPDFS_Client_PutException
     */
    public function set( $fileId, $filePath ){
        $paths = $this->getPaths($fileId);
        if( count( $paths ) ) {
            $url = $paths[0]['url'];
            $fh = fopen($filePath, "rb");
            $size = filesize( $filePath );
            rewind($fh);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_INFILE, $fh);
            curl_setopt($curl, CURLOPT_INFILESIZE, $size );
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_PUT, 4);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            $this->checkError( $curl, self::PHPDFS_PUT_ERR, $response );
            fclose($fh);
        }
    }

    protected function checkError( $curl, $phpDfsErrCode, $additionalInfo ){
        if( curl_errno($curl) ){
            $errInfo = $this->errs[ $phpDfsErrCode ];
            require_once( $errInfo['require_once'] );
            $exceptionClass = $errInfo['class'];
            $msg = $phpDfsErrCode." - ".$errInfo['msg']. " : ".curl_errno($curl)." - " .curl_error($curl)." : $additionalInfo";
            throw new $exceptionClass( $msg );
        }
    }
}