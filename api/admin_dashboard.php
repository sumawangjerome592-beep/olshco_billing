<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Dashboard stats ---
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'];
$total_assessments = $conn->query("SELECT COUNT(*) as count FROM assessments")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT SUM(amount_paid) as total FROM payments")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM assessments WHERE COALESCE(status,'pending') != 'paid'")->fetch_assoc()['count'];

// --- Recent Payments ---
$recent_payments = $conn->query("
    SELECT p.*, u.full_name, u.student_id, COALESCE(p.amount_paid,0) AS amount_paid
    FROM payments p
    JOIN users u ON p.student_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT 10
");

// --- Pending Assessments ---
$pending_assessments = $conn->query("
    SELECT a.*, u.full_name, u.student_id,
           (a.tuition_fee + a.miscellaneous_fee + a.laboratory_fee + a.other_fees) AS total_amount,
           COALESCE(a.status,'pending') AS status
    FROM assessments a
    JOIN users u ON a.student_id = u.user_id
    WHERE COALESCE(a.status,'pending') != 'paid'
    ORDER BY a.due_date ASC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - OLSHCO Billing</title>
<style>
* {margin:0;padding:0;box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    padding:1rem 2rem;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.nav-brand { font-size:1.5rem;font-weight:bold;}
.nav-menu {display:flex;align-items:center;gap:2rem;}
.nav-menu a {color:white;text-decoration:none;padding:0.5rem 1rem;border-radius:5px;transition:0.3s;}
.nav-menu a:hover {background:rgba(255,255,255,0.1);}
.logout-btn {background:rgba(255,255,255,0.2);}
.container {max-width:1400px;margin:2rem auto;padding:0 1rem;}
.welcome-section {background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:2rem;border-radius:10px;margin-bottom:2rem;}
.stats-grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem;}
.stat-card {background:white;padding:1.5rem;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.stat-card h3 {color:#666;font-size:0.9rem;text-transform:uppercase;margin-bottom:0.5rem;}
.stat-card .value {font-size:2rem;font-weight:bold;color:#333;}
.admin-actions {display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;}
.admin-btn {background:white;padding:1.5rem;border-radius:10px;text-decoration:none;color:#333;text-align:center;font-weight:600;box-shadow:0 2px 10px rgba(0,0,0,0.1);transition:transform 0.3s;}
.admin-btn:hover {transform:translateY(-5px);}
.section {background:white;padding:2rem;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem;}
.section h2 {color:#333;margin-bottom:1.5rem;padding-bottom:0.5rem;border-bottom:2px solid #f0f0f0;}
table {width:100%;border-collapse:collapse;}
th {background:#f8f9fa;padding:1rem;text-align:left;font-weight:600;color:#333;}
td {padding:1rem;border-bottom:1px solid #f0f0f0;}
.status-badge {padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;font-weight:500;}
.status-pending {background:#fff3cd;color:#856404;}
.status-partial {background:#cce5ff;color:#004085;}
.status-paid {background:#d4edda;color:#155724;}
.action-buttons {display:flex;gap:0.5rem;}
.btn {padding:0.5rem 1rem;border:none;border-radius:5px;cursor:pointer;font-size:0.9rem;text-decoration:none;display:inline-block;}
.btn-primary {background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;}
.btn-edit {background:#ffc107;color:#333;}
.btn-delete {background:#dc3545;color:white;}
@media (max-width:768px){.navbar{flex-direction:column;gap:1rem;}.nav-menu{flex-wrap:wrap;justify-content:center;}}
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
    <div class="welcome-section">
        <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p>Manage student assessments, payments, and billing records</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h3>Total Students</h3><div class="value"><?php echo $total_students; ?></div></div>
        <div class="stat-card"><h3>Total Assessments</h3><div class="value"><?php echo $total_assessments; ?></div></div>
        <div class="stat-card"><h3>Total Payments</h3><div class="value">₱<?php echo number_format($total_payments,2); ?></div></div>
        <div class="stat-card"><h3>Pending Payments</h3><div class="value"><?php echo $pending_payments; ?></div></div>
    </div>

    <div class="admin-actions">
        <a href="add_assessment.php" class="admin-btn">➕ Add Assessment</a>
        <a href="record_payment.php" class="admin-btn">💰 Record Payment</a>
        <a href="manage_students.php" class="admin-btn">👥 Manage Students</a>
        <a href="reports.php" class="admin-btn">📊 Generate Reports</a>
    </div>

    <div class="section">
        <h2>Recent Payments</h2>
        <table>
            <thead>
                <tr><th>OR Number</th><th>Student</th><th>Student ID</th><th>Amount</th><th>Payment Date</th><th>Method</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($payment=$recent_payments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $payment['or_number']; ?></td>
                    <td><?php echo $payment['full_name']; ?></td>
                    <td><?php echo $payment['student_id']; ?></td>
                    <td>₱<?php echo number_format($payment['amount_paid'],2); ?></td>
                    <td><?php echo date('M d, Y',strtotime($payment['payment_date'])); ?></td>
                    <td><?php echo ucfirst($payment['payment_method']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="delete_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Pending Assessments</h2>
        <table>
            <thead>
                <tr><th>Student</th><th>Student ID</th><th>Total Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($assessment=$pending_assessments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $assessment['full_name']; ?></td>
                    <td><?php echo $assessment['student_id']; ?></td>
                    <td>₱<?php echo number_format($assessment['total_amount'],2); ?></td>
                    <td><?php echo date('M d, Y',strtotime($assessment['due_date'])); ?></td>
                    <td><span class="status-badge status-<?php echo $assessment['status']; ?>"><?php echo strtoupper($assessment['status']); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_assessment.php?id=<?php echo $assessment['assessment_id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="record_payment.php?student=<?php echo $assessment['student_id']; ?>" class="btn btn-primary">Record Payment</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>