<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin_dashboard.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserDetails($user_id);
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $course = sanitize($_POST['course']);
        $year_level = sanitize($_POST['year_level']);

        // Handle profile picture upload
        $profile_picture = $user['profile_picture']; // keep current by default
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $destination = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                    $profile_picture = $destination;
                } else {
                    $error = "Failed to upload profile picture.";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
            }
        }

        if (!$error) {
            $sql = "UPDATE users SET full_name=?, email=?, course=?, year_level=?, profile_picture=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssisi", $full_name, $email, $course, $year_level, $profile_picture, $user_id);

            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $success = "Profile updated successfully!";
                $user = getUserDetails($user_id); // refresh user data
            } else {
                $error = "Failed to update profile: " . $conn->error;
            }
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to change password: " . $conn->error;
                }
            } else {
                $error = "New passwords do not match!";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - OLSHCO Billing System</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.52), rgba(0, 0, 0, 0.73)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
    background-repeat: no-repeat;
    background-size: cover;
    background-position: right;
    background-attachment: fixed;
}
.navbar {
    background: transparent;
    padding: 1rem 2rem;
    -webkit-box-shadow: 0px 3px 8px 3px rgba(0,0,0,0.75); 
    box-shadow: 0px 3px 8px 3px rgba(50, 11, 5, 0.75);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-brand { font-size: 1.5rem; font-weight: bold; }
.nav-menu { display:flex; gap:2rem; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:background 0.3s; }
.nav-menu a:hover, .nav-menu a.active { background: rgba(255,255,255,0.2); }
.logout-btn { background: rgba(255,255,255,0.2); }
.container { max-width:1000px; margin:2rem auto; padding:0 1rem; }
.page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:2rem; border-radius:10px; margin-bottom:2rem; }
.profile-grid { display:grid; grid-template-columns:300px 1fr; gap:2rem; }
.profile-sidebar {
    background:white; padding:2rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);
    text-align:center;
}
.avatar {
    width:150px; height:150px; border-radius:50%; margin:0 auto 1.5rem;
    display:flex; align-items:center; justify-content:center; font-size:3rem; font-weight:bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow:hidden;
}
.avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.profile-name { font-size:1.5rem; font-weight:bold; margin-bottom:0.5rem; }
.profile-username { color:#666; margin-bottom:1rem; }
.profile-badge { background:#e0e0e0; padding:0.5rem; border-radius:5px; margin-bottom:1rem; }
.profile-stats { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid #f0f0f0; }
.stat { text-align:center; }
.stat-value { font-size:1.5rem; font-weight:bold; color:#667eea; }
.stat-label { font-size:0.85rem; color:#666; }
.profile-main { background:white; padding:2rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.tab-container { margin-bottom:2rem; }
.tabs { display:flex; gap:1rem; border-bottom:2px solid #f0f0f0; padding-bottom:1rem; }
.tab { padding:0.5rem 2rem; cursor:pointer; border-radius:5px; transition:all 0.3s; }
.tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; }
.tab-content { display:none; padding:2rem 0; }
.tab-content.active { display:block; }
.form-group { margin-bottom:1.5rem; }
label { display:block; margin-bottom:0.5rem; color:#333; font-weight:500; }
input, select { width:100%; padding:0.75rem; border:2px solid #e0e0e0; border-radius:5px; font-size:1rem; transition:border-color 0.3s; }
input:focus, select:focus { outline:none; border-color:#667eea; }
input[readonly] { background:#f5f5f5; cursor:not-allowed; }
.btn { padding:0.75rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; border:none; border-radius:5px; font-size:1rem; cursor:pointer; transition:transform 0.3s; }
.btn:hover { transform:translateY(-2px); }
.alert { padding:1rem; border-radius:5px; margin-bottom:1.5rem; }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.info-row { display:flex; margin-bottom:1rem; padding:0.5rem; background:#f8f9fa; border-radius:5px; }
.info-label { width:150px; font-weight:600; color:#666; }
.info-value { flex:1; color:#333; }
@media(max-width:768px) { .profile-grid { grid-template-columns:1fr; } }
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSHCO Billing System</div>
    <div class="nav-menu">
        <a href="user_dashboard.php">Dashboard</a>
        <a href="view_payments.php">My Payments</a>
        <a href="profile.php" class="active">Profile</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>My Profile</h1>
        <p>Manage your account information and settings</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="profile-sidebar">
            <div class="avatar">
                <?php if(!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                    <img src="<?php echo $user['profile_picture']; ?>" alt="Profile Picture">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['full_name'],0,1)); ?>
                <?php endif; ?>
            </div>
            <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></div>
            <div class="profile-badge"><?php echo strtoupper($user['user_type']); ?></div>
            <div class="profile-stats">
                <div class="stat">
                    <div class="stat-value"><?php echo $user['year_level']; ?></div>
                    <div class="stat-label">Year Level</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo htmlspecialchars($user['course']); ?></div>
                    <div class="stat-label">Course</div>
                </div>
            </div>
        </div>

        <div class="profile-main">
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="showTab('profile')">Profile Information</div>
                    <div class="tab" onclick="showTab('password')">Change Password</div>
                    <div class="tab" onclick="showTab('activity')">Account Activity</div>
                </div>

                <div id="profile" class="tab-content active">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['student_id']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course" required>
                                <option value="BSIT" <?php echo $user['course']=='BSIT'?'selected':''; ?>>BS Information Technology</option>
                                <option value="BSCS" <?php echo $user['course']=='BSCS'?'selected':''; ?>>BS Computer Science</option>
                                <option value="BSIS" <?php echo $user['course']=='BSIS'?'selected':''; ?>>BS Information Systems</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year Level</label>
                            <select name="year_level" required>
                                <option value="1" <?php echo $user['year_level']==1?'selected':''; ?>>1st Year</option>
                                <option value="2" <?php echo $user['year_level']==2?'selected':''; ?>>2nd Year</option>
                                <option value="3" <?php echo $user['year_level']==3?'selected':''; ?>>3rd Year</option>
                                <option value="4" <?php echo $user['year_level']==4?'selected':''; ?>>4th Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <input type="file" name="profile_picture" accept="image/*">
                        </div>
                        <button type="submit" name="update_profile" class="btn">Update Profile</button>
                    </form>
                </div>

                <div id="password" class="tab-content">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn">Change Password</button>
                    </form>
                </div>

                <div id="activity" class="tab-content">
                    <div class="info-row">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Login</div>
                        <div class="info-value">Today</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Account Status</div>
                        <div class="info-value"><span style="color: #28a745;">Active</span></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    var tabs = document.getElementsByClassName('tab');
    var contents = document.getElementsByClassName('tab-content');
    for(var i=0;i<tabs.length;i++){ tabs[i].classList.remove('active'); }
    for(var i=0;i<contents.length;i++){ contents[i].classList.remove('active'); }
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>