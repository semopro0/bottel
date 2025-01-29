<?php
$local='sql108.infinityfree.com';
$username='if0_38175523';
$pass='Samaan100';
$dbname='if0_38175523_chse';
$conn = mysqli_connect($local,$username,$pass,$dbname);

$conn2 = mysqli_set_charset($conn, "utf8");
mysqli_select_db($conn, $dbname);
session_start();

?>
