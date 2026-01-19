<?php
$host = 'localhost';
$db   = 'scpmm';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Cannot connect to DB: " . $conn->connect_error);
}
?>