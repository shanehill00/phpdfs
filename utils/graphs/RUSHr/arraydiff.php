<?php
$array1 = array(4, 5, 6);
$array2 = array(4, 7, 8);

$result = array_diff($array1, $array2);
$result2 = array_diff($array2, $array1);
print_r(array($result,$result2));
