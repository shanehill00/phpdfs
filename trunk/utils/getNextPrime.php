<?php

class testError{
    public function handlePutError( $errno, $errmsg, $errfile = "filename not given", $errline = "line number not given", $errcontext = "not given" ){
        echo("in handlePutError\n");
        throw new Exception( " $errno : $errmsg : $errfile : $errline " );
    }

    public function putData(){
        set_error_handler( array( $this, "handlePutError") );
        try{
            echo("before spool data\n");
            $jooby = $this->spoolData( );
            echo("after spool data\n");

            $this->saveData( );
        } catch( Exception $e ){
            echo("caught exception\n");
            error_log($e->getMessage().' : '.$e->getTraceAsString() );
        }
        restore_error_handler();
    }

    public function spoolData(){
        echo("entering spool data\n");
        fopen("/shaneo","r");
        echo("exiting spool data\n");
    }
}

$te = new testError();
$te->putData();

/**
 *
 *
    $num = isset($argv[1]) ? $argv[1] : 0;
    echo( getNextPositivePrime( $num )."\n" );

    $array = array(0,1,2,3,4);
    $m = count( $array );
    $prime = getNextPositivePrime( $m );
    // print_r( array( $prime, $array ) );
    // the point here is to create bijections of the
    // set in the given array above
    $replicaCount = 3;
    for( $replicaNo = 0; $replicaNo < $replicaCount; $replicaNo++ ){
        $bijection = array( null, null, null, null, null );
        for( $j = 0; $j < $m; $j++ ){
            $bIdx = ($j + $replicaNo * $prime) % $m;
            $bijection[$bIdx] = $array[$j];
        }
        print_r( array($replicaNo, $prime, $bijection) );
    }
**/

    /**
     * given a number n
     * one divides n by all numbers m less than or equal to the square root of that number.
     * If any of the divisions come out as an integer,
     * then the original number is not a prime.
     * Otherwise, it is a prime.
     */
    function getNextPositivePrime( $num ){
        if( $num < 0 ){
            $num = 0;
        }
        $prime = false;
        while( !$prime ){
            $num++;
            // greater than 3 and not divisible by 2 or 3
            if( $num > 3 && $num % 2 && $num % 3 ){
                $prime = true;
                $m = (int) sqrt( $num );
                for( $m; $m > 1; $m-- ){
                    if( !($num % $m) ){
                        $prime = false;
                        break;
                    }
                }
            } else if( $num <= 3 ){
                $prime = true;
            }
        }
        return $num;
    }
