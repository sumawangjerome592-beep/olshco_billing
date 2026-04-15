<?php
// Set Security Headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

// Database configuration
// Uses environment variables in production (Vercel + FreedDB)
// Falls back to localhost defaults for local XAMPP development
$db_host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
$db_user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'olshco_billing_system';
$db_port = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : 3306;

// Suppress mysqli exceptions so we can handle errors gracefully
mysqli_report(MYSQLI_REPORT_OFF);

// Use persistent connection prefix 'p:' to reuse existing connections
// This reduces total connections created per hour on shared hosts
$persistent_host = 'p:' . $db_host;

// Create connection
$conn = new mysqli($persistent_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection - show friendly error page instead of fatal crash
if ($conn->connect_error) {
    $error_code = $conn->connect_errno;
    $error_msg  = htmlspecialchars($conn->connect_error);
    http_response_code(503);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Service Unavailable - OLSHCO Billing</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',sans-serif; background:#0f0f1a; color:#e2e8f0;
           display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .card { background:#1e1e2e; border:1px solid #2d2d3d; border-radius:16px;
            padding:40px; max-width:480px; text-align:center; }
    .icon { font-size:56px; margin-bottom:16px; }
    h1 { font-size:22px; color:#f87171; margin-bottom:12px; }
    p  { color:#94a3b8; line-height:1.6; margin-bottom:8px; }
    .code { background:#0f0f1a; border-radius:8px; padding:10px 16px;
            font-family:monospace; font-size:13px; color:#fbbf24; margin-top:16px; }
    a.btn { display:inline-block; margin-top:24px; padding:12px 28px;
            background:#4f46e5; color:#fff; border-radius:8px;
            text-decoration:none; font-weight:600; }
    a.btn:hover { background:#4338ca; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">🔌</div>
    <h1>Database Connection Error</h1>
    <p>The system could not connect to the database.</p>
    <p>This may be due to a temporary connection limit on the free hosting tier. Please wait a few minutes and try again.</p>
    <div class="code">Error $error_code: $error_msg</div>
    <a class="btn" href="javascript:location.reload()">🔄 Try Again</a>
  </div>
</body>
</html>
HTML;
    exit();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
}

// Function to get user details
function getUserDetails($user_id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>