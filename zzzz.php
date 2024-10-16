<?php
$row['number_plates'] = '73ع112';
$row['plate_area']=10;
$nums = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
$chr = str_replace($nums, null, $row['number_plates']);
$one = substr($row['number_plates'], 0, 2);
$second = substr(preg_replace('/\D/', '', $row['number_plates']), 2, 3);
$plate = [
    "char" => $chr,
    "number1" => $one,
    "number2" => $second,
    "number3" => $row['plate_area']
];

print_r($plate);
