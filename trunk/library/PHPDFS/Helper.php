<?php

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
        // use a 204 no response header
        header( "Response: 204" ) ;
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
    }
    
}
