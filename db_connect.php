<?php
$host = "localhost";
$username = "root";
$password = "Punsup@123#";
$database = "lpg_punsup";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
