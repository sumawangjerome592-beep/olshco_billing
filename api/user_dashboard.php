<?php
require_once 'config.php';

// --- Check login and redirect ---
if (!isLoggedIn()) {
    redirect('login.php');
}
if (isAdmin()) {
    redirect('admin_dashboard.php');
}

$user_id = $_SESSION['user_id'];

// --- Fetch user details including block ---
$user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// --- Fetch current assessment (fees set by admin) ---
$assessment_stmt = $conn->prepare("SELECT * FROM assessments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$assessment_stmt->bind_param("i", $user_id);
$assessment_stmt->execute();
$assessment = $assessment_stmt->get_result()->fetch_assoc();

// --- Fetch payment history ---
$payments_stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$payments_stmt->bind_param("i", $user_id);
$payments_stmt->execute();
$payments = $payments_stmt->get_result();

// --- Calculate total paid and balance ---
$total_paid_stmt = $conn->prepare("SELECT SUM(amount_paid) AS total FROM payments WHERE student_id = ?");
$total_paid_stmt->bind_param("i", $user_id);
$total_paid_stmt->execute();
$total_paid = $total_paid_stmt->get_result()->fetch_assoc()['total'] ?? 0;

$balance = ($assessment['total_amount'] ?? 0) - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - OLSHCO Billing</title>

<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
       background-image: linear-gradient(to bottom, rgba(0,0,0,0.52), rgba(0,0,0,0.73)), url('e19583f8-dcab-43a3-9825-a79a9ec984ff.jpg');
       background-size: cover; background-repeat: no-repeat; background-attachment: fixed; color:#333; }

.navbar { background: transparent; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; color:white; box-shadow:0px 3px 8px rgba(0,0,0,0.5);}
.nav-brand { font-size:1.5rem; font-weight:bold; }
.nav-menu { display:flex; gap:1rem; }
.nav-menu a { color:white; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition:0.3s; }
.nav-menu a:hover { background:rgba(255,255,255,0.1); }
.nav-menu a.active { background:rgba(255,255,255,0.2); }
.logout-btn { background:#800000; }
.container { max-width:1200px; margin:2rem auto; padding:0 1rem; }
.welcome-section { background:rgba(0,0,0,0.6); color:white; padding:2rem; border-radius:10px; margin-bottom:2rem; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:1.5rem; margin-bottom:2rem; }
.stat-card { background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.stat-card h3 { color:#666; font-size:0.9rem; text-transform:uppercase; margin-bottom:0.5rem; }
.stat-card .value { font-size:2rem; font-weight:bold; color:#333; }
.stat-card .value.positive { color:#28a745; }
.stat-card .value.negative { color:#dc3545; }
.section { background:white; padding:2rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:2rem; }
.section h2 { margin-bottom:1rem; border-bottom:2px solid #f0f0f0; padding-bottom:0.5rem; }
table { width:100%; border-collapse:collapse; }
th { background:#f8f9fa; padding:1rem; text-align:left; font-weight:600; }
td { padding:1rem; border-bottom:1px solid #f0f0f0; }
.status-badge { padding:0.25rem 0.75rem; border-radius:20px; font-size:0.85rem; font-weight:500; }
.status-pending { background:#fff3cd; color:#856404; }
.status-partial { background:#cce5ff; color:#004085; }
.status-paid { background:#d4edda; color:#155724; }
@media (max-width:768px){ .navbar { flex-direction:column; gap:1rem; } .nav-menu { justify-content:center; flex-wrap:wrap; } }

/* ===============================
   AI ASSISTANT UI - Maroon Theme
================================ */
#chatToggle{
    position:fixed; bottom:20px; right:20px; background:#800000; color:white;
    width:65px; height:65px; border-radius:50%; display:flex; justify-content:center; align-items:center;
    font-size:28px; cursor:pointer; box-shadow:0 5px 15px rgba(0,0,0,0.3); z-index:9999;
}
#chatBox{
    position:fixed; bottom:95px; right:20px; width:350px; height:450px; background:white;
    border-radius:12px; display:none; flex-direction:column; overflow:hidden; font-family:'Segoe UI',sans-serif;
    z-index:9999; box-shadow:0 10px 25px rgba(0,0,0,0.35);
}
.chat-header{ background:#800000; color:white; padding:12px; font-weight:bold; text-align:center; }
#chatMessages{ flex:1; overflow-y:auto; padding:10px; background:#f7f8fc; }
.message{ margin:8px 0; padding:8px 10px; border-radius:10px; max-width:85%; font-size:14px; }
.message.user{ background:#800000; color:white; margin-left:auto; }
.message.bot{ background:#f2dede; color:#800000; }
#chatInput{ border:none; border-top:1px solid #ddd; padding:12px; outline:none; font-size:14px; width:100%; box-sizing:border-box; }
#chatControls{ display:flex; flex-wrap:wrap; gap:5px; padding:5px; justify-content:center; }
.chat-btn{ background:#800000; color:white; border:none; padding:6px 10px; border-radius:5px; cursor:pointer; font-size:13px; transition:0.3s; }
.chat-btn:hover{ background:#a00000; }
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">OLSHCO Billing System</div>
    <div class="nav-menu">
        <a href="user_dashboard.php" class="active">Dashboard</a>
        <a href="view_payments.php">My Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <p>
            Student ID: <?php echo htmlspecialchars($user['student_id']); ?> |
            Course: <?php echo htmlspecialchars($user['course']); ?> |
            Year Level: <?php echo $user['year_level']; ?> |
            Block: <?php echo htmlspecialchars($user['block']); ?>
        </p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Assessment</h3>
            <div class="value">₱<?php echo number_format($assessment['total_amount'] ?? 0,2); ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Paid</h3>
            <div class="value positive">₱<?php echo number_format($total_paid,2); ?></div>
        </div>
        <div class="stat-card">
            <h3>Balance</h3>
            <div class="value <?php echo $balance>0?'negative':'positive'; ?>">₱<?php echo number_format($balance,2); ?></div>
        </div>
        <div class="stat-card">
            <h3>Payment Status</h3>
            <div class="value"><?php echo $balance<=0?'PAID':($total_paid>0?'PARTIAL':'PENDING'); ?></div>
        </div>
    </div>

    <?php if($assessment): ?>
    <div class="section">
        <h2>Your Fees</h2>
        <table>
            <tr><th>School Year</th><td><?php echo $assessment['school_year']; ?></td><th>Semester</th><td><?php echo $assessment['semester']; ?></td></tr>
            <tr><th>Tuition Fee</th><td>₱<?php echo number_format($assessment['tuition_fee'],2); ?></td><th>Miscellaneous Fee</th><td>₱<?php echo number_format($assessment['miscellaneous_fee'],2); ?></td></tr>
            <tr><th>Laboratory Fee</th><td>₱<?php echo number_format($assessment['laboratory_fee'],2); ?></td><th>Other Fees</th><td>₱<?php echo number_format($assessment['other_fees'],2); ?></td></tr>
            <tr><th>Due Date</th><td><?php echo date('F d, Y', strtotime($assessment['due_date'])); ?></td>
                <th>Status</th><td><span class="status-badge status-<?php echo $assessment['status']; ?>"><?php echo strtoupper($assessment['status']); ?></span></td></tr>
        </table>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>Payment History</h2>
        <table>
            <thead><tr><th>OR Number</th><th>Payment Date</th><th>Amount</th><th>Payment Method</th><th>Reference No.</th></tr></thead>
            <tbody>
                <?php if($payments->num_rows>0): while($p=$payments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $p['or_number']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                    <td>₱<?php echo number_format($p['amount_paid'],2); ?></td>
                    <td><?php echo ucfirst($p['payment_method']); ?></td>
                    <td><?php echo $p['reference_number'] ?? 'N/A'; ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" style="text-align:center;">No payment records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===============================
     AI Assistant
================================ -->
<div id="chatToggle">🤖</div>
<div id="chatBox">
    <div class="chat-header">OLSHCO Billing Assistant</div>
    <div id="chatMessages">
        <div class="message bot">Hello 👋 Ask me about your balance, payments, receipts, or profile.</div>
    </div>
    <div id="chatControls">
        <button class="chat-btn" data-msg="What is my balance">💰 Balance</button>
        <button class="chat-btn" data-msg="What is my payment status">📌 Payment Status</button>
        <button class="chat-btn" data-msg="How can I pay">💳 How to Pay</button>
        <button class="chat-btn" data-msg="How do I print my receipt">🧾 Receipt</button>
        <button class="chat-btn" data-msg="How do I update my profile">👤 Profile</button>
    </div>
    <input type="text" id="chatInput" placeholder="Type a question..." />
</div>

<script>
const toggle = document.getElementById("chatToggle");
const chatBox = document.getElementById("chatBox");
const input = document.getElementById("chatInput");
const messages = document.getElementById("chatMessages");
const buttons = document.querySelectorAll(".chat-btn");

toggle.onclick = () => {
    chatBox.style.display = chatBox.style.display==="flex"?"none":"flex";
};

function addMessage(text,sender){
    const div=document.createElement("div");
    div.classList.add("message",sender);
    div.textContent=text;
    messages.appendChild(div);
    messages.scrollTop=messages.scrollHeight;
}

function sendMessage(msg){
    if(msg.trim()==="") return;
    addMessage(msg,"user");
    fetch("chatbot_api.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({message:msg})})
    .then(res=>res.json()).then(data=>{addMessage(data.reply,"bot");})
    .catch(()=> addMessage("Server error.","bot"));
}

input.addEventListener("keypress", function(e){ if(e.key==="Enter"){ sendMessage(this.value); this.value=""; } });
buttons.forEach(btn=>{ btn.addEventListener("click",()=>{ sendMessage(btn.getAttribute("data-msg")); }); });
</script>
</body>
</html>