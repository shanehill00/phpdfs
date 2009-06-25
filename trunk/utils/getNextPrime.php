<?php


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
            //  m % j = m - ( ((int) m / j) * j )
            $bIdx2 = ($j + ($replicaNo * $prime) ) - ( ((int) (( $j + ($replicaNo * $prime ) ) / $m) * $m ));
            // print_r(array("bIdx2: $bIdx2", "bIdx: $bIdx"));
            /** 1 = ( $j + $replicaNo * $prime ) / $m
             *
             * 1 = (j + (m-1)) / m
             *
             * replicaNo * prime = m - 1
             *
             * prime = (m - j) / replicaNo
             *
             * k = m - j
             *
             *  $m = ( $j )+  ($replicaNo * $prime )
             *  $m - $j = $replicaNo * $prime;
             *  $prime = ( $m - $j ) / $replicaNo;
             *
             * 1 + (2 * 7) = 5
             *
             * 2 * 7 = 4
             *
             * 7 = 4 / 2
             *
             * 7 = 2
             *
             * $bIdx = ($j + $replicaNo * $prime) % $m
             * 0 = (1 + 2 * 7) % 5
             *
             * 4 % 30 = 4 - ( ((int)(4 / 30)) * 30 )
             *
             * mo = m % j;
             * mo = m - ( ((int) m / j) * j )
             * m - mo = ( ((int) m / j) * j )
             * (m - mo) / j = (int) m / j
             *
             * $bIdx2 = ($j + ($replicaNo * $prime) ) - ( ((int) (( $j + ($replicaNo * $prime ) ) / $m) * $m ));
             * ( ((int) (( $j + ($replicaNo * $prime ) ) / $m) * $m )) = ($j + ($replicaNo * $prime) ) - $bIdx2;
             */
            if( $bIdx == 0 ){
                print_r( "$bIdx = ($j + $replicaNo * $prime) % $m\n" );
            }
            $bijection[$bIdx] = $array[$j];
        }
        print_r( array($replicaNo, $prime, $bijection) );
    }

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
