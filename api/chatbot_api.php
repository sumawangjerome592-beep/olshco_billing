<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["reply"=>"⚠️ Please login first."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$message = strtolower(trim($data['message'] ?? ''));
$message_clean = preg_replace("/[^a-z0-9\s]/","",$message);

// Fetch assessment & payments
$assessment = $conn->query("
    SELECT tuition_fee, miscellaneous_fee, laboratory_fee, other_fees,
           (tuition_fee + miscellaneous_fee + laboratory_fee + other_fees) AS total_assessment,
           due_date, status
    FROM assessments
    WHERE student_id = $user_id
    ORDER BY assessment_id DESC LIMIT 1
")->fetch_assoc();

$payment = $conn->query("SELECT SUM(amount_paid) AS total_paid FROM payments WHERE student_id = $user_id")->fetch_assoc();

$total_assessment = $assessment['total_assessment'] ?? 0;
$total_paid = $payment['total_paid'] ?? 0;
$balance = $total_assessment - $total_paid;

$pay_status = $balance<=0?'PAID':($total_paid>0?'PARTIAL':'PENDING');
$today = new DateTime();
$due_date = isset($assessment['due_date'])?new DateTime($assessment['due_date']):null;
$overdue = $due_date && $today>$due_date && $balance>0;

// Pinned questions
$pinned_questions = [
    "balance"=>["what is my balance","current balance","show balance","how much do i owe"],
    "status"=>["what is my payment status","my status","payment status","check status"],
    "pay"=>["how can i pay","make payment","record payment","pay my fees"],
    "receipt"=>["how do i print my receipt","print receipt","view receipt"],
    "profile"=>["how do i update my profile","update profile","edit profile"]
];

$response="";
$topic_found=false;

foreach($pinned_questions as $topic=>$aliases){
    foreach($aliases as $alias){
        if(strpos($message_clean,$alias)!==false){
            $topic_found=true;
            switch($topic){
                case "balance":
                    $response = $balance<=0?"🎉 Fully paid!":"💰 Remaining balance: ₱".number_format($balance,2).($overdue?" ⚠️ Overdue since ".$due_date->format('F d, Y'):" Due: ".$due_date->format('F d, Y'));
                    break;
                case "status":
                    switch($pay_status){
                        case "PAID": $response="✅ Payment status: PAID"; break;
                        case "PARTIAL": $response="📌 Payment status: PARTIAL. Paid ₱".number_format($total_paid,2)." / ₱".number_format($total_assessment,2).($overdue?" ⚠️ Overdue":""); break;
                        case "PENDING": $response="❌ Payment status: PENDING. No payments yet.".($overdue?" ⚠️ Due date ".$due_date->format('F d, Y'):""); break;
                    }
                    break;
                case "pay":
                    $response=$pay_status==="PAID"?"✅ Already paid!":"💳 Go to 'My Payments' → 'Record Payment' to settle your fees.";
                    break;
                case "receipt":
                    $response="🧾 Print or view receipt from 'My Payments' after selecting a payment record.";
                    break;
                case "profile":
                    $response="👤 Update profile via 'Profile' menu. Edit info or change picture.";
                    break;
            }
            break 2;
        }
    }
}

if(!$topic_found){
    $response="🤔 I only understand pinned questions. Try clicking a button or ask about:\n- Balance\n- Payment Status\n- How to Pay\n- Receipt\n- Profile";
}

echo json_encode(["reply"=>$response]);