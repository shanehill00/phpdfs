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

class PHPDFS_Helper {

    public static function getConfig( $name = "cluster_config.php" ){
        return require $name;
    }

    public static function getParamsFromUrl(){
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

    public static function disconnectClient() {
        // use a 204 no content header
        header( "HTTP/1.1 204 No Content" ) ;
        if( ob_get_length() ){
            ob_end_clean();
        }
        header("Content-Length: 0");
        session_write_close();
    }
    
    public static function send404( $path = "" ) {
        // use a 204 no response header
        $msg = "cannot find $path";
        header( "HTTP/1.1 404 Not Found" ) ;
        header("Content-Length: ".strlen($msg));
        echo($msg);
        session_write_close();
    }
}
