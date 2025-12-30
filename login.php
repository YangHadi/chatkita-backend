<?php
header("Content-Type: application/json");
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username']);
$password = $data['password'];

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            "status" => "success", 
            "id" => $row['id'],
            "username" => $row['username']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
}
?>