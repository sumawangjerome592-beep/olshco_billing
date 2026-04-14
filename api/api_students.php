<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$response = [];

$sql = "SELECT user_id, full_name, student_id, email 
        FROM users 
        WHERE user_type='student'
        ORDER BY full_name";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "count" => count($data),
    "students" => $data
], JSON_PRETTY_PRINT);