<?php
// $id = uniqid().'-'.'aookd'.'-'.ip2long($_SERVER['REMOTE_ADDR']).'-'.time();
// echo $id;exit;
// header("content-security-policy: script-src 'self';report-uri http://localhost:5000; report-to  http://localhost:5000;");
// /
// upton           |cruickshank.bella@example.net       | 15a002d84e3faa  | 5c9926d16e4ddcc90603c11f075b0e5ef7c |
// 
require_once('../vendor/autoload.php');


//koko();

echo 'hi';
echo ' <> bro';


$link = mysqli_connect("localhost", "homestead", "secret", "homestead");

if (mysqli_query($link, "CREATE TEMPORARY TABLE myCity LIKE ".$_GET['r']) === TRUE) {
    printf("Table myCity successfully created.\n");
}


// function seez()
// {
// 	print_r( unserialize($_GET['xxx'], []) );
// }


// seez();
// fastcgi_finish_request();

//file_get_contents('http://localhost:7000/x');
//file_put_contents(__DIR__.'/111.txt', str_repeat('ABCD EFG HIJ KLM NOPQ RST UVW XYZ', 15000000));
// echo 'this is offline';