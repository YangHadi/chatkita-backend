<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

$type = isset($_GET['type']) ? $_GET['type'] : '';
$range = isset($_GET['range']) ? $_GET['range'] : 'daily';

// Helper function to process query results and fill gaps for date-based reports
function get_report_data($conn, $query, $num_points, $date_format, $interval_unit) {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // Return an empty array on failure to prevent frontend errors
        return array_fill(0, $num_points, 0);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $db_data = [];
    while ($row = $result->fetch_assoc()) {
        $db_data[$row['report_period']] = (int)$row['count'];
    }
    $stmt->close();

    $data = [];
    for ($i = 0; $i < $num_points; $i++) {
        $date_key = '';
        $offset = $num_points - 1 - $i;
        if ($interval_unit === 'day') {
            $date_key = date($date_format, strtotime("-$offset days"));
        } elseif ($interval_unit === 'week') {
            $date_key = date($date_format, strtotime("-$offset weeks"));
        } elseif ($interval_unit === 'month') {
            $date_key = date($date_format, strtotime("-$offset months"));
        }
        $data[$i] = isset($db_data[$date_key]) ? $db_data[$date_key] : 0;
    }
    return $data;
}

if ($type === 'messages') {
    $query = '';
    $num_points = 0;
    $date_format = '';
    $interval_unit = '';

    if ($range === 'daily') {
        $num_points = 7;
        $date_format = 'Y-m-d';
        $interval_unit = 'day';
        $query = "
            SELECT report_date as report_period, SUM(c) as count FROM (
                SELECT DATE(created_at) as report_date, COUNT(*) as c FROM messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY report_date
                UNION ALL
                SELECT DATE(created_at) as report_date, COUNT(*) as c FROM group_messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY report_date
            ) as combined_messages
            GROUP BY report_period
        ";
    } elseif ($range === 'weekly') {
        $num_points = 4;
        $date_format = 'oW'; // ISO-8601 year and week number
        $interval_unit = 'week';
        $query = "
            SELECT report_week as report_period, SUM(c) as count FROM (
                SELECT YEARWEEK(created_at, 1) as report_week, COUNT(*) as c FROM messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) GROUP BY report_week
                UNION ALL
                SELECT YEARWEEK(created_at, 1) as report_week, COUNT(*) as c FROM group_messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) GROUP BY report_week
            ) as combined_messages
            GROUP BY report_period
        ";
    } elseif ($range === 'monthly') {
        $num_points = 6;
        $date_format = 'Y-m';
        $interval_unit = 'month';
        $query = "
            SELECT report_month as report_period, SUM(c) as count FROM (
                SELECT DATE_FORMAT(created_at, '%Y-%m') as report_month, COUNT(*) as c FROM messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY report_month
                UNION ALL
                SELECT DATE_FORMAT(created_at, '%Y-%m') as report_month, COUNT(*) as c FROM group_messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY report_month
            ) as combined_messages
            GROUP BY report_period
        ";
    }

    $data = get_report_data($conn, $query, $num_points, $date_format, $interval_unit);
    echo json_encode(["success" => true, "data" => $data]);

} elseif ($type === 'users') {
    $query = '';
    $num_points = 0;
    $date_format = '';
    $interval_unit = '';

    if ($range === 'daily') {
        $num_points = 7;
        $date_format = 'Y-m-d';
        $interval_unit = 'day';
        $query = "SELECT DATE(created_at) as report_period, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY report_period";
    } elseif ($range === 'weekly') {
        $num_points = 4;
        $date_format = 'oW'; // ISO-8601 year and week number
        $interval_unit = 'week';
        $query = "SELECT YEARWEEK(created_at, 1) as report_period, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) GROUP BY report_period";
    } elseif ($range === 'monthly') {
        $num_points = 6;
        $date_format = 'Y-m';
        $interval_unit = 'month';
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as report_period, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY report_period";
    }
    
    $data = get_report_data($conn, $query, $num_points, $date_format, $interval_unit);
    echo json_encode(["success" => true, "data" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request type"]);
}

$conn->close();
?>