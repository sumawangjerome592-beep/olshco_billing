<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$sql = "SELECT p.*, u.full_name, u.student_id, u.course, u.year_level
        FROM payments p
        JOIN users u ON p.student_id = u.user_id
        WHERE p.payment_id = ? AND p.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payment_id, $user_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    redirect('view_payments.php');
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Official Receipt - OLSHCO</title>

<style>

body{
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#667eea,#764ba2);
    margin:0;
    padding:40px;
    display:flex;
    justify-content:center;
}

/* RECEIPT CARD */
.receipt{
    width:700px;
    background:white;
    border-radius:12px;
    box-shadow:0 15px 40px rgba(0,0,0,0.2);
    overflow:hidden;
}

/* HEADER */
.header{
    background:#800000;
    color:white;
    padding:25px;
    text-align:center;
}

.header h1{
    margin:0;
    font-size:26px;
    letter-spacing:1px;
}

.header p{
    margin:5px 0 0;
    opacity:.9;
}

/* LOGO */
.header img{
    height:100px;
    border-radius:50%;
    border:2px solid black;
    margin-bottom:10px;
}

/* OR NUMBER */
.or-box{
    text-align:center;
    padding:15px;
    font-size:20px;
    font-weight:bold;
    background:#f4f6f9;
    border-bottom:1px solid #eee;
}

/* CONTENT */
.content{
    padding:30px;
}

.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px 30px;
}

.label{
    font-weight:600;
    color:#555;
}

.value{
    text-align:right;
    font-weight:500;
}

/* AMOUNT */
.amount{
    margin-top:30px;
    text-align:center;
    background:#f8f9fa;
    padding:25px;
    border-radius:10px;
}

.amount span{
    font-size:32px;
    font-weight:bold;
    color:#28a745;
}

/* FOOTER */
.footer{
    text-align:center;
    padding:20px;
    font-size:14px;
    color:#777;
    border-top:1px dashed #ccc;
}

/* PAID MARK */
.paid-mark {
    text-align: center;
    font-size: 48px;
    font-weight: bold;
    color: #28a745; /* green for paid */
    margin: 20px 0;
    border: 3px dashed #28a745; /* optional dashed border */
    padding: 10px;
    display: inline-block;
    transform: rotate(-5deg); /* slight rotation for stamp effect */
}

/* BUTTONS */
.actions{
    padding:20px;
    display:flex;
    gap:15px;
}

.btn{
    flex:1;
    padding:14px;
    border:none;
    border-radius:8px;
    font-size:15px;
    cursor:pointer;
    text-decoration:none;
    text-align:center;
    color:white;
}

.print{
    background:#667eea;
}

.dashboard{
    background:#800000;
}

/* PRINT STYLE */
@media print{
    body{
        background:white;
        padding:0;
    }
    .actions{
        display:none;
    }
    .receipt{
        box-shadow:none;
        width:100%;
    }
}

</style>
</head>

<body>

<div class="receipt">

<div class="header">
    <img src="logo.jpg" alt="OLSCHO Logo">
    <h1>OLSHCO Billing Office</h1>
    <p>Official Payment Receipt</p>
</div>

<div class="or-box">
OR #: <?php echo htmlspecialchars($payment['or_number']); ?>
</div>

<div class="content">

<div class="grid">

<div class="label">Date</div>
<div class="value"><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></div>

<div class="label">Student Name</div>
<div class="value"><?php echo htmlspecialchars($payment['full_name']); ?></div>

<div class="label">Student ID</div>
<div class="value"><?php echo htmlspecialchars($payment['student_id']); ?></div>

<div class="label">Course</div>
<div class="value"><?php echo htmlspecialchars($payment['course']); ?></div>

<div class="label">Year Level</div>
<div class="value"><?php echo htmlspecialchars($payment['year_level']); ?></div>

<div class="label">School Year</div>
<div class="value"><?php echo htmlspecialchars($payment['school_year'] ?? 'N/A'); ?></div>

<div class="label">Semester</div>
<div class="value"><?php echo htmlspecialchars($payment['semester'] ?? 'N/A'); ?></div>

<div class="label">Period</div>
<div class="value"><?php echo htmlspecialchars($payment['period'] ?? 'N/A'); ?></div>

<div class="label">Payment Method</div>
<div class="value"><?php echo ucfirst($payment['payment_method']); ?></div>

<?php if(!empty($payment['reference_number'])): ?>
<div class="label">Reference No.</div>
<div class="value"><?php echo htmlspecialchars($payment['reference_number']); ?></div>
<?php endif; ?>

</div>

<div class="amount">
Amount Paid<br>
<span>₱<?php echo number_format($payment['amount_paid'],2); ?></span>
</div>

</div>

<!-- PAID MARK -->
<div class="paid-mark">PAID</div>

<div class="actions">
<button onclick="window.print()" class="btn print">🖨 Print Receipt</button>
<a href="user_dashboard.php" class="btn dashboard">← Back to Dashboard</a>
</div>

</div>

</body>
</html>