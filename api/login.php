<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('user_dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Temporary fix - accept both password_verify and md5
        if (password_verify($password, $user['password']) || md5($password) == $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            if ($user['user_type'] == 'admin') {
                redirect('admin_dashboard.php');
            } else {
                redirect('user_dashboard.php');
            }
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Username not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - OLSHCO Billing System</title>
<style>
* {margin: 0; padding: 0; box-sizing: border-box;}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background-image: linear-gradient(to bottom, rgba(0,0,0,0.52), rgba(0,0,0,0.73)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
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
    max-width: 450px;
    overflow: hidden;
}
.header {
    background: linear-gradient(135deg, #800000 0%, #3c0202 100%);
    color: white;
    padding: 40px;
    text-align: center;
}
.header h1 { font-size: 28px; margin-bottom: 10px; }
.header p { opacity: 0.9; }
.header img { height: 100px; border-radius: 50%; border: 2px solid black; }
.form-container { padding: 40px; }
.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
input:focus { outline: none; border-color: #667eea; }
.btn-login {
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
.btn-login:hover { transform: translateY(-2px); }
.alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.register-link { text-align: center; margin-top: 20px; }
.register-link a { color: #667eea; text-decoration: none; font-weight: 500; }
.register-link a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="logo.jpg" alt="logo">
        <h1>OLSHCO Billing System</h1>
        <p>Login to your account</p>
    </div>
    <div class="form-container">
        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>
</body>
</html>