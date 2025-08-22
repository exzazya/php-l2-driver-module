<?php
// db_connect.php

$host = "localhost";
$user = "logi_logs2jetl";
$pass = "hahaha25";
$db   = "logi_L2";

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>