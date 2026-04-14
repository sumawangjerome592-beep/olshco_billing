<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserDetails($user_id);

// Fetch the latest assessment for defaults
$current_assessment_stmt = $conn->prepare("SELECT * FROM assessments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$current_assessment_stmt->bind_param("i", $user_id);
$current_assessment_stmt->execute();
$current_assessment = $current_assessment_stmt->get_result()->fetch_assoc();
$default_school_year = $current_assessment['school_year'] ?? '';
$default_semester = $current_assessment['semester'] ?? '';
$default_course = $user['course'] ?? '';

// --- Handle new payment ---
$payment_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount_paid'])) {
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $school_year = $_POST['school_year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $period = $_POST['period'] ?? 'Prelim';
    $course = $_POST['course'] ?? $default_course;

    // Auto-generate OR number and Reference number
    $or_number = 'OR' . time();
    $reference_number = 'REF' . time() . rand(100, 999);

    $insert_stmt = $conn->prepare("
        INSERT INTO payments 
        (student_id, or_number, payment_date, amount_paid, payment_method, reference_number, school_year, semester, period, course) 
        VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param(
        "isdssssss",
        $user_id,
        $or_number,
        $amount_paid,
        $payment_method,
        $reference_number,
        $school_year,
        $semester,
        $period,
        $course
    );

    if ($insert_stmt->execute()) {
        $payment_message = "Payment successfully recorded! Reference Number: $reference_number";
    } else {
        $payment_message = "Error recording payment: " . $conn->error;
    }
}

// --- Fetch all payments ---
$sql = "SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result();

// Totals
$total_paid_sql = "SELECT SUM(amount_paid) as total FROM payments WHERE student_id = ?";
$total_paid_stmt = $conn->prepare($total_paid_sql);
$total_paid_stmt->bind_param("i", $user_id);
$total_paid_stmt->execute();
$total_paid = $total_paid_stmt->get_result()->fetch_assoc()['total'] ?? 0;

$total_assessment = $current_assessment['total_amount'] ?? 0;
$balance = $total_assessment - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Payments - OLSHCO Billing System</title>
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    background-image: linear-gradient(to bottom, rgba(0,0,0,0.52), rgba(0,0,0,0.73)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
    background-repeat: no-repeat;
    background-size: cover;
    background-position: right;
    background-attachment: fixed;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.navbar {
    background: transparent; padding:1rem 2rem;
    box-shadow: 0px 3px 8px rgba(0,0,0,0.75);
    color:white; display:flex; justify-content:space-between; align-items:center;
}
.nav-brand { font-size:1.5rem; font-weight:bold; }
.nav-menu { display:flex; gap:2rem; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover, .nav-menu a.active { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }
.container { max-width:1200px; margin:2rem auto; padding:0 1rem; }
.page-header { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:2rem; border-radius:10px; margin-bottom:2rem; }
.page-header h1 { font-size:2rem; margin-bottom:0.5rem; }
.summary-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:1.5rem; margin-bottom:2rem; }
.summary-card { background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.summary-card h3 { color:#666; font-size:0.9rem; text-transform:uppercase; margin-bottom:0.5rem; }
.summary-card .amount { font-size:2rem; font-weight:bold; }
.summary-card .amount.paid { color:#28a745; }
.summary-card .amount.balance { color:#dc3545; }
.filter-section { background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:2rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
.filter-section input, .filter-section select { padding:0.75rem; border:2px solid #e0e0e0; border-radius:5px; font-size:1rem; flex:1; }
.filter-section button { padding:0.75rem 2rem; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border:none; border-radius:5px; cursor:pointer; }
.payments-table { background:white; padding:2rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { background:#f8f9fa; padding:1rem; text-align:left; font-weight:600; color:#333; }
td { padding:1rem; border-bottom:1px solid #f0f0f0; }
tr:hover { background:#f8f9fa; }
.badge { padding:0.25rem 0.75rem; border-radius:20px; font-size:0.85rem; font-weight:500; display:inline-block; }
.badge-cash { background:#d4edda; color:#155724; }
.badge-check { background:#cce5ff; color:#004085; }
.badge-bank_transfer { background:#fff3cd; color:#856404; }
.badge-online { background:#d1ecf1; color:#0c5460; }
.btn-view { padding:0.5rem 1rem; color:white; text-decoration:none; border-radius:5px; font-size:0.9rem; background:#667eea; }
.no-records { text-align:center; padding:3rem; color:#666; }
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSHCO Billing System</div>
    <div class="nav-menu">
        <a href="user_dashboard.php">Dashboard</a>
        <a href="view_payments.php" class="active">My Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- ====== Make a Payment Form ====== -->
    <div class="filter-section">
        <h2 style="width:100%; color:#333;">Make a Payment</h2>
        <?php if($payment_message) echo "<p style='color:green; width:100%;'>$payment_message</p>"; ?>
        <form method="POST" style="width:100%; display:flex; flex-wrap:wrap; gap:1rem;">
            <select name="school_year" required>
                <option value="">Select School Year</option>
                <option value="2025-2026" <?php if($default_school_year=='2025-2026') echo 'selected';?>>2025-2026</option>
                <option value="2026-2027" <?php if($default_school_year=='2026-2027') echo 'selected';?>>2026-2027</option>
                <option value="2027-2028" <?php if($default_school_year=='2027-2028') echo 'selected';?>>2027-2028</option>
            </select>

            <select name="semester" required>
                <option value="">Select Semester</option>
                <option value="1st" <?php if($default_semester=='1st') echo 'selected';?>>1st</option>
                <option value="2nd" <?php if($default_semester=='2nd') echo 'selected';?>>2nd</option>
                <option value="Summer" <?php if($default_semester=='Summer') echo 'selected';?>>Summer</option>
            </select>

            <select name="period" required>
                <option value="">Select Period</option>
                <option value="Prelim">Prelim</option>
                <option value="Midterm">Midterm</option>
                <option value="Finals">Finals</option>
            </select>

            <select name="course" required>
                <option value="">Select Course</option>
                <option value="BSIT" <?php if($default_course=='BSIT') echo 'selected';?>>BS Information Technology</option>
                <option value="BSCRIM" <?php if($default_course=='BSCRIM') echo 'selected';?>>BS Criminology</option>
                <option value="BSHM" <?php if($default_course=='BSHM') echo 'selected';?>>BS Hospitality Management</option>
                <option value="BSTE" <?php if($default_course=='BSTE') echo 'selected';?>>BS Teacher Education</option>
                <option value="BSOA" <?php if($default_course=='BSOA') echo 'selected';?>>BS Office Administration</option>
            </select>

            <input type="number" step="0.01" name="amount_paid" placeholder="Amount Paid (₱)" required>
            <select name="payment_method" required>
                <option value="cash">Cash</option>
                <option value="check">Check</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online">Online</option>
            </select>

            <button type="submit" class="btn" style="background:#667eea; color:white;">Submit Payment</button>
        </form>
    </div>

    <!-- ====== Page Header ====== -->
    <div class="page-header">
        <h1>My Payment History</h1>
        <p>Track all your payments and transactions</p>
    </div>

    <!-- ====== Summary Cards ====== -->
    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Assessment</h3>
            <div class="amount">₱<?php echo number_format($total_assessment, 2); ?></div>
        </div>
        <div class="summary-card">
            <h3>Total Paid</h3>
            <div class="amount paid">₱<?php echo number_format($total_paid, 2); ?></div>
        </div>
        <div class="summary-card">
            <h3>Current Balance</h3>
            <div class="amount balance">₱<?php echo number_format($balance, 2); ?></div>
        </div>
        <div class="summary-card">
            <h3>Total Transactions</h3>
            <div class="amount"><?php echo $payments->num_rows; ?></div>
        </div>
    </div>

    <!-- ====== Payments Table ====== -->
    <div class="payments-table">
        <table>
            <thead>
                <tr>
                    <th>OR Number</th>
                    <th>Payment Date</th>
                    <th>School Year</th>
                    <th>Semester</th>
                    <th>Period</th>
                    <th>Course</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments && $payments->num_rows > 0): ?>
                    <?php while($payment = $payments->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($payment['or_number']); ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($payment['school_year']); ?></td>
                        <td><?php echo htmlspecialchars($payment['semester']); ?></td>
                        <td><?php echo htmlspecialchars($payment['period']); ?></td>
                        <td><?php echo htmlspecialchars($payment['course']); ?></td>
                        <td><strong>₱<?php echo number_format($payment['amount_paid'], 2); ?></strong></td>
                        <td><span class="badge badge-<?php echo $payment['payment_method']; ?>"><?php echo ucfirst(str_replace('_',' ',$payment['payment_method'])); ?></span></td>
                        <td><?php echo $payment['reference_number']; ?></td>
                        <td>
                            <a href="view_receipt.php?id=<?php echo $payment['payment_id']; ?>" class="btn-view">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="no-records"><h3>No payment records found</h3><p>Your payment history will appear here once you make a payment.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>