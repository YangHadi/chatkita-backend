<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "chatkita";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}
?>
