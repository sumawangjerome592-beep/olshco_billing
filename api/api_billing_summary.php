<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode([
        "status"=>"error",
        "message"=>"student_id missing"
    ]);
    exit;
}

$student_id = intval($_GET['student_id']);

/* TOTAL ASSESSMENT */
$assessment_sql = "
SELECT SUM(tuition_fee + miscellaneous_fee + laboratory_fee + other_fees) AS total_assessment
FROM assessments
WHERE student_id = ?
";

$stmt = $conn->prepare($assessment_sql);
$stmt->bind_param("i",$student_id);
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc()['total_assessment'] ?? 0;


/* TOTAL PAYMENTS */
$payment_sql = "
SELECT SUM(amount_paid) AS total_paid
FROM payments
WHERE student_id = ?
";

$stmt2 = $conn->prepare($payment_sql);
$stmt2->bind_param("i",$student_id);
$stmt2->execute();
$paid = $stmt2->get_result()->fetch_assoc()['total_paid'] ?? 0;

$balance = $assessment - $paid;

echo json_encode([
    "status"=>"success",
    "student_id"=>$student_id,
    "total_assessment"=>floatval($assessment),
    "total_paid"=>floatval($paid),
    "balance"=>floatval($balance)
], JSON_PRETTY_PRINT);