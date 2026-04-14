<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Reports Data ---
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='student'")->fetch_assoc()['count'];
$total_assessments = $conn->query("SELECT COUNT(*) as count FROM assessments")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT SUM(amount_paid) as total FROM payments")->fetch_assoc()['total'] ?? 0;

// --- Recent assessments ---
$recent_assessments = $conn->query("
    SELECT a.*, u.full_name, u.student_id, u.course, u.year_level, u.semester, u.block,
           (a.tuition_fee+a.miscellaneous_fee+a.laboratory_fee+a.other_fees) as total_amount,
           COALESCE(a.status,'pending') AS status
    FROM assessments a
    JOIN users u ON a.student_id = u.user_id
    ORDER BY a.due_date DESC
    LIMIT 10
");

// --- Recent payments ---
$recent_payments = $conn->query("
    SELECT p.payment_id, p.or_number, p.amount_paid, p.payment_date, p.payment_method,
           u.full_name, u.student_id, u.course, u.year_level, u.semester, u.block
    FROM payments p
    JOIN users u ON p.student_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - Admin</title>
<style>
/* Reset & Global */
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height:100vh;
    background-image: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
    background-repeat:no-repeat;
    background-size:cover;
    background-position:center;
    background-attachment:fixed;
    color:#333;
}

/* Navbar */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding:1rem 2rem;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-radius:0 0 10px 10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
}
.nav-brand { font-size:1.5rem; font-weight:bold; }
.nav-menu { display:flex; gap:1rem; flex-wrap:wrap; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }

/* Container & Sections */
.container { max-width:1400px; margin:3rem auto; padding:1rem; }
.section {
    background:white;
    padding:2rem;
    border-radius:10px;
    box-shadow:0 2px 15px rgba(0,0,0,0.1);
    margin-bottom:2rem;
}
.section h2 {
    color:#333;
    margin-bottom:1.5rem;
    border-bottom:2px solid #f0f0f0;
    padding-bottom:0.5rem;
}

/* Stats Grid */
.stats-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:1.5rem;
    margin-bottom:2rem;
}
.stat-card {
    background:white;
    padding:1.5rem;
    border-radius:10px;
    box-shadow:0 2px 15px rgba(0,0,0,0.15);
    text-align:center;
}
.stat-card h3 {
    color:#666;
    font-size:0.9rem;
    text-transform:uppercase;
    margin-bottom:0.5rem;
}
.stat-card .value {
    font-size:2rem;
    font-weight:bold;
    color:#333;
}

/* Tables */
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:1rem; border-bottom:1px solid #f0f0f0; text-align:left; }
th { background:#f8f9fa; color:#333; }
.status-badge {
    padding:0.25rem 0.75rem;
    border-radius:20px;
    font-size:0.85rem;
    font-weight:500;
}
.status-pending { background:#fff3cd; color:#856404; }
.status-partial { background:#cce5ff; color:#004085; }
.status-paid { background:#d4edda; color:#155724; }

/* Responsive */
@media (max-width:768px){
    .navbar { flex-direction:column; gap:1rem; }
    .nav-menu { justify-content:center; }
    table, th, td { font-size:0.85rem; }
}
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

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Students</h3>
            <div class="value"><?php echo $total_students; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Assessments</h3>
            <div class="value"><?php echo $total_assessments; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Payments</h3>
            <div class="value">₱<?php echo number_format($total_payments,2); ?></div>
        </div>
    </div>

    <!-- Recent Assessments -->
    <div class="section">
        <h2>Recent Assessments</h2>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Semester</th>
                    <th>Block</th>
                    <th>Total Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($a=$recent_assessments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($a['course']); ?></td>
                    <td><?php echo htmlspecialchars($a['year_level']); ?></td>
                    <td><?php echo htmlspecialchars($a['semester']); ?></td>
                    <td><?php echo htmlspecialchars($a['block']); ?></td>
                    <td>₱<?php echo number_format($a['total_amount'],2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($a['due_date'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $a['status']; ?>">
                            <?php echo strtoupper($a['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Payments -->
    <div class="section">
        <h2>Recent Payments</h2>
        <table>
            <thead>
                <tr>
                    <th>OR Number</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Semester</th>
                    <th>Block</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
            <?php while($p=$recent_payments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['or_number']); ?></td>
                    <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($p['course']); ?></td>
                    <td><?php echo htmlspecialchars($p['year_level']); ?></td>
                    <td><?php echo htmlspecialchars($p['semester']); ?></td>
                    <td><?php echo htmlspecialchars($p['block']); ?></td>
                    <td>₱<?php echo number_format($p['amount_paid'],2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                    <td><?php echo ucfirst($p['payment_method']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>