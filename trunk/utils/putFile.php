<?php

if( !isset( $argv[1] ) ){
    throw new Exception("please include a url");
}
if( !isset( $argv[2] ) || !file_exists( $argv[2] ) ){
    throw new Exception("please include a file name");
}

$url = $argv[1];
$fileName = $argv[2];

$fh = fopen($fileName, 'r');
if(!$fh){
    throw new Exception("could not open file $fileName");
}
rewind($fh);
$ch = curl_init();
curl_setopt($ch, CURLOPT_INFILE, $fh);
curl_setopt($ch, CURLOPT_INFILESIZE, filesize( $fileName ) );
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_PUT, 4);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_HTTPHEADER, Array('PHPDFS_FILENAME: foo man joo'));
$response = curl_exec($ch);
fclose($fh);
print_r( $response );
