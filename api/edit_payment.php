<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Get payment ID from URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('manage_payments.php');
}
$payment_id = intval($_GET['id']);

// --- Fetch payment details ---
$stmt = $conn->prepare("
    SELECT p.*, u.full_name, u.student_id
    FROM payments p
    JOIN users u ON p.student_id = u.user_id
    WHERE p.payment_id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

if (!$payment) {
    $_SESSION['error'] = "Payment not found!";
    redirect('manage_payments.php');
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $or_number = sanitize($_POST['or_number']);
    $amount_paid = sanitize($_POST['amount_paid']);
    $payment_date = sanitize($_POST['payment_date']);
    $payment_method = sanitize($_POST['payment_method']);

    $update_stmt = $conn->prepare("
        UPDATE payments
        SET or_number = ?, amount_paid = ?, payment_date = ?, payment_method = ?
        WHERE payment_id = ?
    ");
    $update_stmt->bind_param("sdssi", $or_number, $amount_paid, $payment_date, $payment_method, $payment_id);

    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Payment updated successfully!";
        redirect('manage_payments.php');
    } else {
        $error = "Error updating payment: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Payment - Admin</title>
<style>
body {  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height:100vh;
    background-image:
        linear-gradient(to bottom, rgba(0,0,0,0.52), rgba(0,0,0,0.73)),
        url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
    background-repeat:no-repeat;
    background-size:cover;
    background-position:center;
    background-attachment:fixed;
}
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding:1rem 2rem; color:white; display:flex; justify-content:space-between; align-items:center;
    border-radius:0 0 10px 10px; box-shadow:0 4px 10px rgba(0,0,0,0.3);
}
.nav-brand { font-size:1.5rem;font-weight:bold; }
.nav-menu { display:flex; gap:1rem; flex-wrap:wrap; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }

.container { max-width:600px; margin:3rem auto; padding:1rem; }
.section { background:white; padding:2rem; border-radius:10px; box-shadow:0 2px 15px rgba(0,0,0,0.2); }
.section h2 { margin-bottom:1.5rem; border-bottom:2px solid #f0f0f0; padding-bottom:0.5rem; }
.form-group { margin-bottom:1rem; }
label { display:block; margin-bottom:0.5rem; font-weight:500; }
input, select { width:100%; padding:0.5rem; border:1px solid #ccc; border-radius:5px; font-size:1rem; }
.btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:0.75rem; border:none; border-radius:5px; cursor:pointer; width:100%; font-size:1rem; transition:0.3s; }
.btn-submit:hover { opacity:0.9; }
.alert { padding:10px; margin-bottom:1rem; border-radius:5px; font-weight:600; text-align:center; }
.alert-error { background:#f8d7da; color:#721c24; }
.alert-success { background:#d4edda; color:#155724; }
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSCHO Billing System - Admin Panel</div>
    <div class="nav-menu">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_assessments.php">Assessments</a>
        <a href="manage_payments.php">Payments</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="section">
        <h2>Edit Payment</h2>

        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>OR Number</label>
                <input type="text" name="or_number" value="<?php echo htmlspecialchars($payment['or_number']); ?>" required>
            </div>
            <div class="form-group">
                <label>Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" value="<?php echo htmlspecialchars($payment['amount_paid']); ?>" required>
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" name="payment_date" value="<?php echo htmlspecialchars($payment['payment_date']); ?>" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="cash" <?php echo $payment['payment_method']=='cash'?'selected':''; ?>>Cash</option>
                    <option value="gcash" <?php echo $payment['payment_method']=='gcash'?'selected':''; ?>>GCash</option>
                    <option value="bank" <?php echo $payment['payment_method']=='bank'?'selected':''; ?>>Bank Transfer</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Update Payment</button>
        </form>
    </div>
</div>
</body>
</html>