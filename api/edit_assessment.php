<?php
require_once 'config.php';

// --- Check login and admin ---
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// --- Get assessment ID ---
if (!isset($_GET['id'])) {
    redirect('manage_assessments.php');
}

$assessment_id = intval($_GET['id']);

// --- Fetch assessment ---
$assessment = $conn->query("
    SELECT a.*, u.full_name, u.user_id
    FROM assessments a
    JOIN users u ON a.student_id = u.user_id
    WHERE a.assessment_id = $assessment_id
")->fetch_assoc();

if (!$assessment) {
    redirect('manage_assessments.php');
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id']);
    $school_year = sanitize($_POST['school_year']);
    $semester = sanitize($_POST['semester']);
    $tuition_fee = floatval($_POST['tuition_fee']);
    $misc_fee = floatval($_POST['miscellaneous_fee']);
    $lab_fee = floatval($_POST['laboratory_fee']);
    $other_fee = floatval($_POST['other_fees']);
    $due_date = sanitize($_POST['due_date']);

    $sql = "UPDATE assessments 
            SET student_id=?, school_year=?, semester=?, tuition_fee=?, miscellaneous_fee=?, laboratory_fee=?, other_fees=?, due_date=? 
            WHERE assessment_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issddddsi", 
        $student_id, 
        $school_year, 
        $semester, 
        $tuition_fee, 
        $misc_fee, 
        $lab_fee, 
        $other_fee, 
        $due_date, 
        $assessment_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Assessment updated successfully!";
        redirect('manage_assessments.php');
    } else {
        $error = "Error: " . $conn->error;
    }
}

// --- Get all students for dropdown ---
$students = $conn->query("SELECT * FROM users WHERE user_type='student' ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Assessment - Admin</title>
<style>
/* Reset & Global */
* {margin:0; padding:0; box-sizing:border-box;}
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
}

/* Form */
.form-group { margin-bottom:1rem; }
label { display:block; margin-bottom:0.5rem; font-weight:500; }
input, select { width:100%; padding:0.5rem; border:1px solid #ccc; border-radius:5px; transition:0.3s; }
input:focus, select:focus { border-color:#667eea; outline:none; box-shadow:0 0 5px rgba(102,126,234,0.5); }

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:0.75rem 1.5rem;
    border:none;
    border-radius:5px;
    cursor:pointer;
    transition:0.3s;
}
.btn-submit:hover { opacity:0.9; }

/* Alerts */
.alert { padding:10px; margin-bottom:1rem; border-radius:5px; }
.alert-success { background:#d4edda; color:#155724; }
.alert-error { background:#f8d7da; color:#721c24; }

/* Responsive */
@media (max-width:768px){
    .navbar{ flex-direction:column; gap:1rem; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    input, select { font-size:0.9rem; }
}
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSCHO Billing System - Admin Panel</div>
    <div class="nav-menu">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_assessments.php">Assessments</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="section">
        <h2>Edit Assessment</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Student</label>
                <select name="student_id" required>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['user_id']; ?>" <?php if($student['user_id']==$assessment['student_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($student['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>School Year</label>
                <input type="text" name="school_year" value="<?php echo htmlspecialchars($assessment['school_year']); ?>" required>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <input type="text" name="semester" value="<?php echo htmlspecialchars($assessment['semester']); ?>" required>
            </div>
            <div class="form-group">
                <label>Tuition Fee</label>
                <input type="number" step="0.01" name="tuition_fee" value="<?php echo $assessment['tuition_fee']; ?>" required>
            </div>
            <div class="form-group">
                <label>Miscellaneous Fee</label>
                <input type="number" step="0.01" name="miscellaneous_fee" value="<?php echo $assessment['miscellaneous_fee']; ?>" required>
            </div>
            <div class="form-group">
                <label>Laboratory Fee</label>
                <input type="number" step="0.01" name="laboratory_fee" value="<?php echo $assessment['laboratory_fee']; ?>" required>
            </div>
            <div class="form-group">
                <label>Other Fees</label>
                <input type="number" step="0.01" name="other_fees" value="<?php echo $assessment['other_fees']; ?>" required>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?php echo $assessment['due_date']; ?>" required>
            </div>
            <button type="submit" class="btn-submit">Update Assessment</button>
        </form>
    </div>
</div>
</body>
</html>