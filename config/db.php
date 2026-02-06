<?php
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$name = "bank_soal_smks";
$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");
?>