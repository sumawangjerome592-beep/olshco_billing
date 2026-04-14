<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $student_id = sanitize($_POST['student_id']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, student_id, email, password, user_type) VALUES (?, ?, ?, ?, 'student')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $full_name, $student_id, $email, $password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "New student added successfully!";
        redirect('manage_students.php');
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student - Admin</title>
<style>
/* Reset & Global */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
.nav-brand { font-size:1.5rem; font-weight:bold; }
.nav-menu { display:flex; align-items:center; gap:1rem; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }

/* Container & Section */
.container { max-width:600px; margin:3rem auto; padding:1rem; }
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
    text-align:center;
}

/* Form */
.form-group { margin-bottom:1rem; }
label { display:block; margin-bottom:0.5rem; font-weight:500; }
input {
    width:100%;
    padding:0.5rem;
    border:1px solid #ccc;
    border-radius:5px;
    transition:0.3s;
}
input:focus { border-color:#667eea; outline:none; box-shadow:0 0 5px rgba(102,126,234,0.5); }

.btn-submit {
    width:100%;
    padding:0.75rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border:none;
    border-radius:5px;
    font-size:1rem;
    cursor:pointer;
    transition:0.3s;
    margin-top:1rem;
}
.btn-submit:hover { opacity:0.9; }

/* Alerts */
.alert { padding:10px; margin-bottom:1rem; border-radius:5px; text-align:center; font-weight:600; }
.alert-success { background:#d4edda; color:#155724; }
.alert-error { background:#f8d7da; color:#721c24; }

/* Responsive */
@media (max-width:768px){
    .navbar{ flex-direction:column; gap:1rem; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    input { font-size:0.9rem; }
}
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSCHO Billing System - Admin Panel</div>
    <div class="nav-menu">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="section">
        <h2>Add New Student</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php elseif (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Add Student</button>
        </form>
    </div>
</div>
</body>
</html>