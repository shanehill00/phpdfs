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
require_once 'PHPDFS.php';
class PHPDFS_Helper {


    /**
     * indicates whether or not the client is still connected to us
     * @var <boolean>
     */
    protected static $clientGone = false;

    /**
     * hold a copy of the last loaded config by getConfig()
     *
     * @var <array>
     */
    protected static $config = null;

    /**
     * returns the last loaded config from the previous call to
     * getConfig, null otherwise.  if a name is passed, an attemot to load
     * that config is made
     *
     * @param <string> $name
     * @return <array>
     */
    public static function getConfig( $name = "cluster_config.php" ){
        if( !self::$config || $name ){
            if( extension_loaded("apc") ){
                self::$config = apc_fetch("phpdfsconfig");
                if( self::$config === false ){
                    self::$config = require $name;
                    apc_store("phpdfsconfig", self::$config);
                }
            } else {
                self::$config = require $name;
            }
        }
        return self::$config;
    }

    public static function getParams(){
        $params = array(
            'fileName' => '',
            'pathHash' => '',
            'name' => '',
            'action' => '',
            'replica' => 0,
            'position' => null,
            'configIndex' => 0,
            'moveConfigIndex' => 0,
            'moveContext' => PHPDFS::MOVE_CONTEXT_START,
            'getContext' => '',
        );
        if( isset( $_SERVER['PATH_INFO'] ) ){
            $params['name'] = trim($_SERVER['PATH_INFO'],'/');
            $params['name'] = str_replace( array('/','\\',':','*','?','|','<','>','"','%'),"", $params['name'] );

            $params['action'] = strtolower( $_SERVER['REQUEST_METHOD'] );

            // get the last element of the path info
            $params['fileName'] = split( '/', $params['name'] );
            $params['fileName'] = array_pop( $params['fileName'] );

            // hash the path info
            $params['pathHash'] = self::getPathHash( $params['name'] );

            $headers = http_get_request_headers();

            if( isset( $headers[ PHPDFS::HEADER_REPLICA ] ) ){
                $params['replica'] = (int) $headers[ PHPDFS::HEADER_REPLICA ];
            }

            if( isset( $headers[ PHPDFS::HEADER_POSITION ] ) ){
                $params['position'] = (int) $headers[ PHPDFS::HEADER_POSITION ];
            }

            if( isset( $headers[ PHPDFS::HEADER_CONFIG_INDEX ] ) ){
                $params['configIndex'] = (int) $headers[ PHPDFS::HEADER_CONFIG_INDEX ];
            }

            if( isset( $headers[ PHPDFS::HEADER_MOVE_CONTEXT ] ) ){
                $params['moveContext'] = strtolower( $headers[ PHPDFS::HEADER_MOVE_CONTEXT ] );
            }

            if( isset( $headers[ PHPDFS::HEADER_MOVE_CONFIG_INDEX ] ) ){
                $params['moveConfigIndex'] = (int) $headers[ PHPDFS::HEADER_MOVE_CONFIG_INDEX ];
            }

            if( isset( $headers[ PHPDFS::HEADER_GET_CONTEXT ] ) ){
                $params['getContext'] = $headers[ PHPDFS::HEADER_GET_CONTEXT ];
            }
        }
        return $params;
    }

    public static function getPathHash( $name ){
        $c = self::getConfig();
        $path = "00/00";
        $pathHash = ( crc32( $name ) >> 16 ) & 0x7fff;
        mt_srand( $pathHash );
        $pathHash = sprintf("%05s", mt_rand(0,9999));
        $path[0] = $pathHash[1];
        $path[1] = $pathHash[2];
        $path[2] = "/";
        $path[3] = $pathHash[3];
        $path[4] = $pathHash[4];
        return $path;
    }

    public static function disconnectClient() {
        if(self::$clientGone) return;
        if( isset( self::$config['disconnectAfterSpooling'] ) && self::$config['disconnectAfterSpooling'] ){
            // use a 204 no content header
            header( $_SERVER['SERVER_PROTOCOL']." 204 No Content" ) ;
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
            self::$clientGone = true;
        }
    }
    
    public static function send500( $msg = "Internal Server Error" ) {
        if(self::$clientGone) return;
        header(  $_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error' );
        header("Content-Length: ".strlen($msg));
        echo($msg);
        session_write_close();
        self::$clientGone = true;
    }

    public static function send301( $url ) {
        if(self::$clientGone) return;
        header(  $_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently' );
        header('Location: '.$url);
        header('Content-Length: 0');
        session_write_close();
        self::$clientGone = true;
    }
    
    public static function send404( $path = "" ) {
        if(self::$clientGone) return;
        $msg = "cannot find $path";
        header(  $_SERVER['SERVER_PROTOCOL']." 404 Not Found" ) ;
        header("Content-Length: ".strlen($msg));
        echo($msg);
        session_write_close();
        self::$clientGone = true;
    }
}
