<?php
session_start();
require_once '../includes/db.php';

if ($_SESSION['admin_logged_in'] ?? false) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$info  = '';
$step  = $_SESSION['otp_step'] ?? 1;

// ── STEP 1: Username + Password ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_admin_id'], $_SESSION['otp_step']);

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = time() + 600; // 10 minutes

        $_SESSION['otp_code']     = $otp;
        $_SESSION['otp_expires']  = $expires;
        $_SESSION['otp_admin_id'] = $admin['id'];
        $_SESSION['otp_step']     = 2;

        // Force session save BEFORE sending email or doing anything else
        session_write_close();

        $sent = sendOTP($admin['username'], $otp);

        // Reopen session after email
        session_start();

        if (!$sent) {
            $error = 'Failed to send OTP email. Check your SMTP config in includes/mail_config.php.';
            unset($_SESSION['otp_step'], $_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_admin_id']);
            $step = 1;
        } else {
            $step = 2;
        }
    } else {
        $error = 'Invalid username or password.';
    }
}

// ── STEP 2: OTP Verification ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered = trim($_POST['otp'] ?? '');
    $stored  = $_SESSION['otp_code']    ?? '';
    $expires = $_SESSION['otp_expires'] ?? 0;

    if (empty($stored) || empty($expires)) {
        $error = 'Session lost. Please log in again.';
        unset($_SESSION['otp_step'], $_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_admin_id']);
        $step = 1;
    } elseif (time() > $expires) {
        $error = 'OTP has expired. Please log in again.';
        unset($_SESSION['otp_step'], $_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_admin_id']);
        $step = 1;
    } elseif ($entered === $stored) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $_SESSION['otp_admin_id'];
        $stmt2 = $conn->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt2->bind_param('i', $_SESSION['admin_id']);
        $stmt2->execute();
        $a = $stmt2->get_result()->fetch_assoc();
        $_SESSION['admin_username'] = $a['username'];
        unset($_SESSION['otp_step'], $_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_admin_id']);
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Incorrect OTP. Please try again.';
        $step  = 2;
    }
}

// ── Resend OTP ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = time() + 600;
    $_SESSION['otp_code']    = $otp;
    $_SESSION['otp_expires'] = $expires;
    session_write_close();

    $stmt3 = $conn->prepare("SELECT username FROM admins WHERE id = ?");
    $stmt3->bind_param('i', $_SESSION['otp_admin_id']);
    $stmt3->execute();
    $a2 = $stmt3->get_result()->fetch_assoc();
    sendOTP($a2['username'], $otp);

    session_start();
    $step = 2;
    $info = 'A new OTP has been sent to your email.';
}

// ── Mail Functions ────────────────────────────────────────────────────────
function sendOTP($username, $otp) {
    require_once '../includes/mail_config.php';
    $subject = 'Y2J Admin Login — Your OTP Code';
    $message = "Hello $username,\n\nYour OTP code is: $otp\n\nThis code expires in 10 minutes.\n\nIf you did not request this, please ignore this email.\n\n— Y2J Funeral Service";
    return sendSmtpMail(MAIL_TO, $subject, $message);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Y2J Funeral Service</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-wrap">
    <div class="login-card">
        <h1>Y2J Funeral Service</h1>

        <?php if ($step == 1): ?>
        <p>Admin Portal</p>
        <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST">
            <div class="login-field">
                <label>Username</label>
                <input type="number" name="username" required autofocus>
            </div>
            <div class="login-field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <br>
            <button type="submit" name="login" class="btn btn-gold" style="width:100%">LOG IN</button>
        </form>

        <?php elseif ($step == 2): ?>
        <p style="color:var(--muted);font-size:0.9rem;margin-bottom:28px">
            An OTP has been sent to your email.<br>Enter it below to continue. It expires in 10 minutes.
        </p>
        <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($info): ?><p style="color:var(--success);font-size:0.875rem;margin-bottom:12px"><?= htmlspecialchars($info) ?></p><?php endif; ?>
        <form method="POST">
            <div class="login-field">
                <label>OTP Code</label>
                <input type="number" name="otp" maxlength="6" placeholder="000000" required autofocus
                       style="text-align:center;font-size:1.5rem;letter-spacing:0.3em">
            </div>
            <br>
            <button type="submit" name="verify_otp" class="btn btn-gold" style="width:100%">VERIFY OTP</button>
        </form>
        <form method="POST" style="margin-top:14px">
            <button type="submit" name="resend_otp" class="btn btn-outline" style="width:100%;font-size:0.85rem">Resend OTP</button>
        </form>
        <form method="POST" style="margin-top:10px">
            <button type="submit" name="login" class="btn" style="width:100%;background:none;color:var(--muted);font-size:0.82rem;text-decoration:underline">← Back to Login</button>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
