<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('manage_students.php');
}

$student_id = intval($_GET['id']);
$student = $conn->query("SELECT * FROM users WHERE user_id = $student_id AND user_type='student'")->fetch_assoc();

if (!$student) {
    redirect('manage_students.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $student_code = sanitize($_POST['student_id']);
    $email = sanitize($_POST['email']);

    $sql = "UPDATE users SET full_name=?, student_id=?, email=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $full_name, $student_code, $email, $student_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Student updated successfully!";
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
<title>Edit Student - Admin</title>
<style>
/* Reset & Global */
* {margin:0;padding:0;box-sizing:border-box;}
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
}

/* Form */
.form-group { margin-bottom:1rem; }
label { display:block; margin-bottom:0.5rem; color:#333; }
input { width:100%; padding:0.5rem; border:1px solid #ccc; border-radius:5px; font-size:1rem; }
.btn-submit { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:0.75rem 1.5rem; border:none; border-radius:5px; cursor:pointer; transition:0.3s; }
.btn-submit:hover { opacity:0.9; }

/* Alerts */
.alert { padding:10px; margin-bottom:1rem; border-radius:5px; }
.alert-success { background:#d4edda; color:#155724; }
.alert-error { background:#f8d7da; color:#721c24; }

/* Responsive */
@media (max-width:768px){
    .navbar{ flex-direction:column; gap:1rem; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    input { font-size:0.95rem; }
}
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSCHO Billing System - Admin Panel</div>
    <div class="nav-menu">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="section">
        <h2>Edit Student</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
            </div>
            <button type="submit" class="btn-submit">Update Student</button>
        </form>
    </div>
</div>
</body>
</html>