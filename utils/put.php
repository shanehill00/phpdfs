<?php
$dataToPut = isset($argv[2]) ? $argv[2] : "this is a new file";
$fh = fopen('php://memory', 'rw');
fwrite($fh, $dataToPut);
rewind($fh);
$url = isset($argv[1]) ? $argv[1] : "http://192.168.0.2";
$ch = curl_init();
curl_setopt($ch, CURLOPT_INFILE, $fh);
curl_setopt($ch, CURLOPT_INFILESIZE, strlen( $dataToPut ) );
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_PUT, 4);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_HTTPHEADER, Array('PHPDFS_FILENAME: foo man joo'));
$response = curl_exec($ch);
fclose($fh);
print_r( $response );
