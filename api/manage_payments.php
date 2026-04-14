<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Handle delete payment ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM payments WHERE payment_id = $delete_id");
    $_SESSION['success'] = "Payment deleted successfully!";
    redirect('manage_payments.php');
}

// --- Get all payments with user info ---
$payments = $conn->query("
    SELECT p.*, u.full_name, u.student_id, u.course, u.year_level, u.semester, u.block
    FROM payments p
    JOIN users u ON p.student_id = u.user_id
    ORDER BY u.course, u.year_level, u.semester, u.block, p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Payments - Admin</title>
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height:100vh;
    background-image: linear-gradient(to bottom, rgba(0,0,0,0.52), rgba(0,0,0,0.73)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
    background-size:cover;
    background-position:center;
    background-attachment:fixed;
    color:#333;
}

/* Navbar */
.navbar {
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    padding:1rem 2rem;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-radius:0 0 10px 10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.3);
}
.nav-brand { font-size:1.5rem; font-weight:bold; }
.nav-menu { display:flex; align-items:center; gap:1rem; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }

/* Container & Section */
.container { max-width:1200px; margin:3rem auto; padding:1rem; }
.section {
    background: rgba(255,255,255,0.95);
    padding:2rem;
    border-radius:10px;
    box-shadow:0 2px 15px rgba(0,0,0,0.2);
}
.section h2 {
    color:#333;
    margin-bottom:1rem;
    border-bottom:2px solid #f0f0f0;
    padding-bottom:0.5rem;
}
.group-title {
    background:#f0f0f0;
    padding:0.5rem 1rem;
    font-weight:600;
    margin-top:1.5rem;
    border-radius:5px;
}

/* Scrollable Table */
.scroll-table {
    max-height:400px; /* Adjust height as needed */
    overflow-y:auto;
    margin-bottom:2rem;
}
table { width:100%; border-collapse:collapse; }
th, td { padding:1rem; border-bottom:1px solid #f0f0f0; text-align:left; }
th {
    background:#f8f9fa;
    color:#333;
    position:sticky;
    top:0;
    z-index:2;
    box-shadow: 0 2px 2px -1px rgba(0,0,0,0.2);
}
tr:nth-child(even) { background:#f9f9f9; }
tr:hover { background: rgba(102,126,234,0.1); }

.action-buttons { display:flex; gap:0.5rem; }

/* Buttons */
.btn {
    padding:0.5rem 1rem;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-size:0.9rem;
    text-decoration:none;
    display:inline-block;
    transition:0.3s;
}
.btn-edit { background:#ffc107; color:#333; }
.btn-delete { background:#dc3545; color:white; }
.btn-add, .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; margin-bottom:1rem; }
.btn:hover { opacity:0.9; }

/* Alerts */
.alert { padding:10px; margin-bottom:1rem; border-radius:5px; font-weight:600; text-align:center; }
.alert-success { background:#d4edda; color:#155724; }

/* Responsive */
@media (max-width:768px){
    .navbar{ flex-direction:column; gap:1rem; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    table, th, td { font-size:0.85rem; }
    .btn { font-size:0.8rem; padding:0.4rem 0.8rem; }
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
        <h2>Manage Payments</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <a href="record_payment.php" class="btn btn-add">💰 Record New Payment</a>

        <?php
        // --- Group payments by course/year/semester/block ---
        $currentGroup = '';
        while ($p = $payments->fetch_assoc()):
            $group = "{$p['course']} - {$p['year_level']} - {$p['semester']} - Block {$p['block']}";
            if ($group !== $currentGroup):
                if ($currentGroup !== '') echo "</tbody></table></div>";
                echo "<div class='group-title'>$group</div>";
                echo "<div class='scroll-table'><table>
                        <thead>
                            <tr>
                                <th>OR Number</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Amount Paid</th>
                                <th>Payment Date</th>
                                <th>Payment Method</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>";
                $currentGroup = $group;
            endif;
        ?>
            <tr>
                <td><?php echo htmlspecialchars($p['or_number']); ?></td>
                <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                <td><?php echo htmlspecialchars($p['student_id']); ?></td>
                <td>₱<?php echo number_format($p['amount_paid'],2); ?></td>
                <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                <td><?php echo ucfirst($p['payment_method']); ?></td>
                <td class="action-buttons">
                    <a href="edit_payment.php?id=<?php echo $p['payment_id']; ?>" class="btn btn-edit">Edit</a>
                    <a href="manage_payments.php?delete_id=<?php echo $p['payment_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this payment?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        </table></div>
    </div>
</div>

</body>
</html>