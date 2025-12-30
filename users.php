<?php
header("Content-Type: application/json");
include "db.php";

// GET request: fetch users
if ($_SERVER['REQUEST_METHOD'] === "GET") {

    if (!isset($_GET['id'])) {
        echo json_encode(["success" => false, "message" => "Missing id"]);
        exit;
    }

    $id = intval($_GET['id']);
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

    try {
        if ($group_id) {
            // Case 1: Add members to existing group → exclude current user + existing group members
            $stmt = $conn->prepare("
                SELECT u.id, u.username
                FROM users u
                WHERE u.id != ?
                AND u.id NOT IN (
                    SELECT gm.user_id FROM group_members gm WHERE gm.group_id = ?
                )
            ");
            $stmt->bind_param("ii", $id, $group_id);
        } else {
            // Case 2: Create group or fetch contacts → exclude only current user
            $stmt = $conn->prepare("
                SELECT u.id, u.username
                FROM users u
                WHERE u.id != ?
            ");
            $stmt->bind_param("i", $id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode([
            "success" => true,
            "users" => $users
        ]);

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
