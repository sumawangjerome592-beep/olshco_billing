<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Fetch all assessment records ---
$records = $conn->query("
    SELECT a.*, u.full_name, u.student_id,
           (a.tuition_fee + a.miscellaneous_fee + a.laboratory_fee + a.other_fees) AS total_amount
    FROM assessments a
    JOIN users u ON a.student_id = u.user_id
    ORDER BY a.due_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Records - Admin</title>
<style>
/* Reset & global */
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:#f4f4f4;
    color:#333;
    min-height:100vh;
}

/* Navbar */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding:1rem 2rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
    color:white;
    border-radius:0 0 10px 10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
}
.nav-brand { font-size:1.5rem; font-weight:bold; }
.nav-menu { display:flex; gap:1rem; flex-wrap:wrap; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }

/* Container & Section */
.container { max-width:1200px; margin:3rem auto; padding:1rem; }
.section {
    background:white;
    padding:2rem;
    border-radius:10px;
    box-shadow:0 2px 15px rgba(0,0,0,0.1);
}
.section h2 {
    color:#333;
    margin-bottom:1.5rem;
    border-bottom:2px solid #f0f0f0;
    padding-bottom:0.5rem;
}

/* Table Styles */
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:1rem; border-bottom:1px solid #f0f0f0; text-align:left; }
th { background:#f8f9fa; color:#333; }
tr:hover { background: #f1f5ff; }
td.amount { font-weight:bold; }

/* Status badges */
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
        <a href="view_records.php">Records</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="section">
        <h2>All Assessment Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>School Year</th>
                    <th>Semester</th>
                    <th>Tuition Fee</th>
                    <th>Misc. Fee</th>
                    <th>Laboratory Fee</th>
                    <th>Other Fees</th>
                    <th>Total Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($r['school_year']); ?></td>
                    <td><?php echo ucfirst($r['semester']); ?></td>
                    <td>₱<?php echo number_format($r['tuition_fee'],2); ?></td>
                    <td>₱<?php echo number_format($r['miscellaneous_fee'],2); ?></td>
                    <td>₱<?php echo number_format($r['laboratory_fee'],2); ?></td>
                    <td>₱<?php echo number_format($r['other_fees'],2); ?></td>
                    <td class="amount">₱<?php echo number_format($r['total_amount'],2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($r['due_date'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $r['status']??'pending'; ?>">
                            <?php echo strtoupper($r['status']??'PENDING'); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>