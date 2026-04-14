<?php
require_once 'config.php';

header('Content-Type: application/json');

/* Accept JSON OR POST */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

if (
    empty($data['student_id']) ||
    empty($data['assessment_id']) ||
    empty($data['amount_paid'])
){
    echo json_encode([
        "status"=>"error",
        "message"=>"Missing required fields"
    ]);
    exit;
}

$student_id = intval($data['student_id']);
$assessment_id = intval($data['assessment_id']);
$amount_paid = floatval($data['amount_paid']);
$payment_method = $data['payment_method'] ?? 'cash';
$payment_date = date('Y-m-d');

/* Generate OR */
$or_number = "OR-" . date('YmdHis');

/* INSERT PAYMENT */
$sql = "INSERT INTO payments
(student_id, assessment_id, amount_paid, payment_date, payment_method, or_number, created_at)
VALUES (?,?,?,?,?,?,NOW())";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iidsss",
    $student_id,
    $assessment_id,
    $amount_paid,
    $payment_date,
    $payment_method,
    $or_number
);

if(!$stmt->execute()){
    echo json_encode([
        "status"=>"error",
        "message"=>$stmt->error
    ]);
    exit;
}

/* UPDATE STATUS AUTOMATICALLY */

$total_paid = $conn->query("
SELECT SUM(amount_paid) total
FROM payments
WHERE assessment_id=$assessment_id
")->fetch_assoc()['total'] ?? 0;

$total_amount = $conn->query("
SELECT (tuition_fee+miscellaneous_fee+laboratory_fee+other_fees) total
FROM assessments
WHERE assessment_id=$assessment_id
")->fetch_assoc()['total'];

$status = "pending";

if ($total_paid >= $total_amount)
    $status = "paid";
elseif ($total_paid > 0)
    $status = "partial";

$conn->query("
UPDATE assessments
SET status='$status'
WHERE assessment_id=$assessment_id
");

echo json_encode([
    "status"=>"success",
    "or_number"=>$or_number,
    "assessment_status"=>$status
], JSON_PRETTY_PRINT);