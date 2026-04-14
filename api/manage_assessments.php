<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Handle delete assessment ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM assessments WHERE assessment_id = $delete_id");
    $_SESSION['success'] = "Assessment deleted successfully!";
    redirect('manage_assessments.php');
}

// --- Get all assessments ---
$assessments = $conn->query("
    SELECT a.*, u.full_name, u.student_id,
           (a.tuition_fee + a.miscellaneous_fee + a.laboratory_fee + a.other_fees) AS total_amount,
           COALESCE(a.status,'pending') AS status
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
<title>Manage Assessments - Admin</title>
<style>
/* Reset & Global */
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

/* Navbar */
.navbar {
    background: rgba(102,126,234,0.9); 
    padding:1rem 2rem;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-radius:0 0 10px 10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.3);
}
.nav-brand { font-size:1.5rem;font-weight:bold; }
.nav-menu { display:flex;align-items:center;gap:1rem; }
.nav-menu a { color:white;text-decoration:none;padding:0.5rem 1rem;border-radius:5px;transition:0.3s; }
.nav-menu a:hover { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }

/* Container */
.container { max-width:1000px;margin:3rem auto;padding:1rem; }

/* Section */
.section {
    background: rgba(255,255,255,0.95);
    padding:2rem;
    border-radius:10px;
    box-shadow:0 2px 15px rgba(0,0,0,0.2);
}
.section h2 {
    color:#333;
    margin-bottom:1.5rem;
    border-bottom:2px solid #f0f0f0;
    padding-bottom:0.5rem;
}

/* Table */
table { width:100%;border-collapse:collapse; }
th, td { padding:1rem;border-bottom:1px solid #e0e0e0;text-align:left; }
th { background:#f8f9fa;color:#333;border-bottom:2px solid #ccc; }
tr:hover { background: rgba(102,126,234,0.1); border-radius:5px; }

/* Status badges */
.status-badge { padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;font-weight:500; }
.status-pending { background:#fff3cd;color:#856404; }
.status-partial { background:#cce5ff;color:#004085; }
.status-paid { background:#d4edda;color:#155724; }

/* Buttons */
.action-buttons { display:flex;gap:0.5rem; }
.btn { padding:0.5rem 1rem;border:none;border-radius:5px;cursor:pointer;font-size:0.9rem;text-decoration:none;display:inline-block; }
.btn-edit { background:#ffc107;color:#333; }
.btn-delete { background:#dc3545;color:white; }
.btn-primary { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; }
.btn-add { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white;margin-bottom:1rem; display:inline-block; }

/* Alerts */
.alert { padding:10px;margin-bottom:1rem;border-radius:5px; }
.alert-success { background:#d4edda;color:#155724; }

/* Responsive */
@media (max-width:768px){
    .navbar{ flex-direction:column; gap:1rem; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    table, th, td { font-size:0.9rem; }
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
    <div class="section">
        <h2>Manage Assessments</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <a href="add_assessment.php" class="btn btn-add">➕ Add New Assessment</a>

        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>School Year</th>
                    <th>Semester</th>
                    <th>Total Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while($assessment=$assessments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($assessment['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($assessment['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($assessment['school_year']); ?></td>
                    <td><?php echo htmlspecialchars($assessment['semester']); ?></td>
                    <td>₱<?php echo number_format($assessment['total_amount'],2); ?></td>
                    <td><?php echo date('M d, Y',strtotime($assessment['due_date'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $assessment['status']; ?>">
                            <?php echo strtoupper($assessment['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_assessment.php?id=<?php echo $assessment['assessment_id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="record_payment.php?student=<?php echo $assessment['student_id']; ?>" class="btn btn-primary">Record Payment</a>
                            <a href="manage_assessments.php?delete_id=<?php echo $assessment['assessment_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this assessment?')">Delete</a>
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