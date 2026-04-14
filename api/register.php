<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $student_id = sanitize($_POST['student_id']);
    $course = sanitize($_POST['course']);
    $year_level = sanitize($_POST['year_level']);
    $block = sanitize($_POST['block']); // ✅ NEW

    $sql = "INSERT INTO users (full_name, username, email, password, student_id, course, year_level, block, user_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssis", $full_name, $username, $email, $password, $student_id, $course, $year_level, $block);
    
    if ($stmt->execute()) {
        $success = "Registration successful! You can now login.";
    } else {
        $error = "Registration failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - OLSHCO Billing System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.52), rgba(0, 0, 0, 0.73)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: right;
            background-attachment: fixed;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #800000 0%, #800000 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .form-container {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .header img {
            height: 100px;
            border-radius: 50%;
            border: 2px solid black;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.jpg" alt="logo">
            <h1>OLSCHO Billing System</h1>
            <p>Student Registration</p>
        </div>
        
        <div class="form-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" required>
                </div>

                <div class="form-group">
                    <label for="course">Course</label>
                    <select id="course" name="course" required>
                        <option value="">Select Course</option>
                        <option value="BSIT">BS Information Technology</option>
                        <option value="BSCRIM">BS Criminology</option>
                        <option value="BSHM">BS Hospitality Management</option>
                        <option value="BSTE">BS Teacher Education</option>
                        <option value="BSOA">BS Office Administration</option>
                    </select>
                </div>

                <!-- ✅ YEAR LEVEL (RESTORED) -->
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>

                <!-- ✅ NEW BLOCK -->
                <div class="form-group">
                    <label for="block">Block</label>
                    <select id="block" name="block" required>
                        <option value="">Select Block</option>
                        <option value="Block-A">Block A</option>
                        <option value="Block-B">Block B</option>
                    </select>
                </div>

                <button type="submit" class="btn-register">Register</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>