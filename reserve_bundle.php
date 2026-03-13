<?php
require_once 'includes/db.php';
$activePage = 'bundles';
session_start();

$bundle_id = isset($_GET['bundle_id']) ? (int)$_GET['bundle_id'] : 1;
$bundle = $conn->query("SELECT * FROM bundles WHERE id = $bundle_id")->fetch_assoc();
if (!$bundle) { header('Location: bundles.php'); exit; }
$inclusions = explode('|', $bundle['inclusions']);

$step    = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Clear session if starting fresh on step 1
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['bres_name'], $_SESSION['bres_address'], $_SESSION['bres_date'],
          $_SESSION['bres_phone'], $_SESSION['bres_bundle'], $_SESSION['bres_remarks'],
          $_SESSION['bres_dec_name'], $_SESSION['bres_dec_age'], $_SESSION['bres_dec_dod']);
}

$error   = '';
$success = false;
$ref_id  = null;

// ── STEP 1 ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    $name     = trim($_POST['client_name'] ?? '');
    $address  = trim($_POST['client_address'] ?? '');
    $date     = trim($_POST['reservation_date'] ?? '');
    $phone    = trim($_POST['client_phone'] ?? '');
    $remarks  = trim($_POST['remarks'] ?? '');
    $dec_name = trim($_POST['deceased_name'] ?? '');
    $dec_age  = trim($_POST['deceased_age'] ?? '');
    $dec_dod  = trim($_POST['deceased_dod'] ?? '');

    if (!$name || !$address || !$date || !$phone || !$dec_name || !$dec_age || !$dec_dod) {
        $error = 'Please complete all required fields.';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $error = 'Phone number must be 11 digits and start with 09.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Reservation date cannot be in the past. Please select today or a future date.';
    } else {
        $_SESSION['bres_name']     = $name;
        $_SESSION['bres_address']  = $address;
        $_SESSION['bres_date']     = $date;
        $_SESSION['bres_phone']    = $phone;
        $_SESSION['bres_bundle']   = $bundle_id;
        $_SESSION['bres_remarks']  = $remarks;
        $_SESSION['bres_dec_name'] = $dec_name;
        $_SESSION['bres_dec_age']  = $dec_age;
        $_SESSION['bres_dec_dod']  = $dec_dod;
        header("Location: reserve_bundle.php?bundle_id=$bundle_id&step=2");
        exit;
    }
}

// ── STEP 2: Confirm & Submit ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $nameE    = $conn->real_escape_string($_SESSION['bres_name'] ?? '');
    $addressE = $conn->real_escape_string($_SESSION['bres_address'] ?? '');
    $dateE    = $conn->real_escape_string($_SESSION['bres_date'] ?? '');
    $phoneE   = $conn->real_escape_string($_SESSION['bres_phone'] ?? '');
    $bid      = (int)($_SESSION['bres_bundle'] ?? $bundle_id);
    $remarksE = $conn->real_escape_string($_SESSION['bres_remarks'] ?? '');
    $decNameE = $conn->real_escape_string($_SESSION['bres_dec_name'] ?? '');
    $decAge   = (int)($_SESSION['bres_dec_age'] ?? 0);
    $decDodE  = $conn->real_escape_string($_SESSION['bres_dec_dod'] ?? '');
    $total    = $bundle['price'];

    $conn->query("INSERT INTO bundle_reservations
        (client_name, client_address, client_phone, reservation_date, deceased_name, deceased_age, deceased_dod,
         bundle_id, payment_type, total_amount, remarks, status)
        VALUES ('$nameE','$addressE','$phoneE','$dateE','$decNameE','$decAge','$decDodE',
                '$bid','Cash','$total','$remarksE','Pending')");

    $ref_id = $conn->insert_id;

    unset($_SESSION['bres_name'], $_SESSION['bres_address'], $_SESSION['bres_date'],
          $_SESSION['bres_phone'], $_SESSION['bres_bundle'], $_SESSION['bres_remarks'],
          $_SESSION['bres_dec_name'], $_SESSION['bres_dec_age'], $_SESSION['bres_dec_dod']);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Package — Y2J Funeral Service</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reserve-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
        .reserve-image-panel { position: sticky; top: 110px; text-align: center; }
        .reserve-image-panel img { width: 100%; max-height: 300px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .reserve-image-panel h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; margin-top: 16px; margin-bottom: 12px; }
        .price-display { font-size: 1.8rem; font-weight: 700; color: var(--gold); margin-bottom: 16px; }
        .pkg-inclusions { display: flex; flex-wrap: wrap; gap: 7px; justify-content: center; }
        .pkg-tag { background: var(--white); border-radius: 50px; padding: 4px 13px; font-size: 0.8rem; }
        .section-divider { font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em; color: var(--muted); text-transform: uppercase; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #eee; }
        .confirm-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .confirm-table td { padding: 11px 16px; border-bottom: 1px solid #eee; font-size: 0.92rem; }
        .confirm-table td:first-child { font-weight: 600; color: var(--muted); width: 180px; }
        .confirm-table .section-header td { background: var(--grey-bg); font-size: 0.78rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); padding: 8px 16px; }
        .confirm-table tr:last-child td { border-bottom: none; }
        .confirm-total { font-size: 1.3rem; font-weight: 700; color: var(--gold); }
        .steps { display: flex; justify-content: center; gap: 8px; margin-bottom: 36px; align-items: center; }
        .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; font-weight: 700; background: #ddd; color: var(--muted); }
        .step-dot.active { background: var(--gold); color: var(--white); }
        .step-dot.done { background: var(--navy); color: var(--white); }
        .step-line { flex: 1; max-width: 60px; height: 2px; background: #ddd; }
        .step-line.done { background: var(--navy); }
        @media print { nav, .btn, .steps { display: none !important; } body { background: white; } .card-section { box-shadow: none; } }
        @media (max-width: 768px) { .reserve-layout { grid-template-columns: 1fr; } .reserve-image-panel { position: static; } }
    </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="page-content">
<div class="card-section">

<?php if (!$success): ?>
<div class="steps">
    <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
    <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
    <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>">2</div>
</div>
<?php endif; ?>

<?php if ($step == 1): ?>
<div class="reserve-layout">
    <div class="reserve-image-panel">
        <img src="<?= htmlspecialchars($bundle['image_url'] ?? 'images/bundle_placeholder.jpg') ?>" alt="<?= htmlspecialchars($bundle['name']) ?>" onerror="this.src='images/bundle_placeholder.jpg'">
        <h2><?= htmlspecialchars($bundle['name']) ?></h2>
        <div class="price-display">₱<?= number_format($bundle['price'], 0) ?></div>
        <div class="pkg-inclusions">
            <?php foreach ($inclusions as $item): ?>
            <span class="pkg-tag">✓ <?= htmlspecialchars(trim($item)) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <form method="POST">
        <input type="hidden" name="step1" value="1">

        <div class="section-divider">Client Information</div>
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="client_name" value="<?= htmlspecialchars($_SESSION['bres_name'] ?? $_POST['client_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz">
        </div>
        <div class="form-group">
            <label>Full Address:</label>
            <input type="text" name="client_address" value="<?= htmlspecialchars($_SESSION['bres_address'] ?? $_POST['client_address'] ?? '') ?>" placeholder="Street, Barangay, City, Province">
        </div>
        <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="client_phone" value="<?= htmlspecialchars($_SESSION['bres_phone'] ?? $_POST['client_phone'] ?? '') ?>" placeholder="09XXXXXXXXX" maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
        <div class="form-group">
            <label>Reservation Date:</label>
            <input type="date" name="reservation_date" value="<?= htmlspecialchars($_SESSION['bres_date'] ?? $_POST['reservation_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
        </div>

        <div class="section-divider">Deceased Information</div>
        <div class="form-group">
            <label>Name of Deceased:</label>
            <input type="text" name="deceased_name" value="<?= htmlspecialchars($_SESSION['bres_dec_name'] ?? $_POST['deceased_name'] ?? '') ?>" placeholder="Full name of the deceased">
        </div>
        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="deceased_age" value="<?= htmlspecialchars($_SESSION['bres_dec_age'] ?? $_POST['deceased_age'] ?? '') ?>" min="0" max="130" placeholder="e.g. 72">
        </div>
        <div class="form-group">
            <label>Date of Death:</label>
            <input type="date" name="deceased_dod" value="<?= htmlspecialchars($_SESSION['bres_dec_dod'] ?? $_POST['deceased_dod'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="section-divider">Additional</div>
        <div class="form-group">
            <label>Special Remarks <span style="font-weight:400;color:var(--muted)">(Optional)</span></label>
            <textarea name="remarks" rows="3" placeholder="e.g. Preferred pickup time, special requests..." style="resize:vertical"><?= htmlspecialchars($_SESSION['bres_remarks'] ?? $_POST['remarks'] ?? '') ?></textarea>
        </div>
        <div class="form-center" style="margin-top:32px">
            <a href="bundles.php" class="btn btn-outline" style="margin-right:12px">← Back</a>
            <button type="submit" class="btn btn-gold">NEXT →</button>
        </div>
    </form>
</div>

<?php elseif ($step == 2): ?>
<h2 style="font-family:'Cormorant Garamond',serif;font-size:1.6rem;margin-bottom:8px;text-align:center">Review Your Reservation</h2>
<p style="text-align:center;color:var(--muted);font-size:0.9rem;margin-bottom:28px">Please check all details before confirming.</p>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
    <div style="text-align:center">
        <img src="<?= htmlspecialchars($bundle['image_url'] ?? 'images/bundle_placeholder.jpg') ?>" alt="<?= htmlspecialchars($bundle['name']) ?>" onerror="this.src='images/bundle_placeholder.jpg'" style="width:100%;max-height:220px;object-fit:cover;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1)">
        <div style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;margin-top:12px"><?= htmlspecialchars($bundle['name']) ?></div>
        <div style="margin-top:10px">
            <?php foreach ($inclusions as $item): ?>
            <div style="font-size:0.82rem;color:var(--muted);padding:3px 0">✓ <?= htmlspecialchars(trim($item)) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div>
        <table class="confirm-table">
            <tr class="section-header"><td colspan="2">Client Information</td></tr>
            <tr><td>Full Name</td><td><?= htmlspecialchars($_SESSION['bres_name'] ?? '') ?></td></tr>
            <tr><td>Address</td><td><?= htmlspecialchars($_SESSION['bres_address'] ?? '') ?></td></tr>
            <tr><td>Phone</td><td><?= htmlspecialchars($_SESSION['bres_phone'] ?? '') ?></td></tr>
            <tr><td>Reservation Date</td><td><?= htmlspecialchars($_SESSION['bres_date'] ?? '') ?></td></tr>
            <tr class="section-header"><td colspan="2">Deceased Information</td></tr>
            <tr><td>Name</td><td><?= htmlspecialchars($_SESSION['bres_dec_name'] ?? '') ?></td></tr>
            <tr><td>Age</td><td><?= htmlspecialchars($_SESSION['bres_dec_age'] ?? '') ?></td></tr>
            <tr><td>Date of Death</td><td><?= htmlspecialchars($_SESSION['bres_dec_dod'] ?? '') ?></td></tr>
            <tr class="section-header"><td colspan="2">Package & Payment</td></tr>
            <tr><td>Package</td><td><?= htmlspecialchars($bundle['name']) ?></td></tr>
            <tr><td>Payment</td><td>Cash</td></tr>
            <tr><td>Total Amount</td><td class="confirm-total">₱<?= number_format($bundle['price'], 0) ?></td></tr>
            <?php if (!empty($_SESSION['bres_remarks'])): ?>
            <tr class="section-header"><td colspan="2">Remarks</td></tr>
            <tr><td colspan="2"><?= htmlspecialchars($_SESSION['bres_remarks']) ?></td></tr>
            <?php endif; ?>
        </table>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="reserve_bundle.php?bundle_id=<?= $bundle_id ?>" class="btn btn-outline">← Edit</a>
            <button onclick="window.print()" class="btn btn-outline">🖨 Print</button>
            <form method="POST" style="display:inline">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-gold">CONFIRM RESERVATION</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
</div>

<?php if ($error): ?>
<div class="modal-overlay show" id="errModal">
    <div class="modal">
        <h3 style="color:var(--danger);font-size:1.1rem"><?= htmlspecialchars($error) ?></h3>
        <button class="btn btn-gold" onclick="document.getElementById('errModal').classList.remove('show')">OK</button>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="modal-overlay show">
    <div class="modal">
        <h3 class="success">RESERVATION SUBMITTED!</h3>
        <div style="background:var(--grey-bg);border-radius:10px;padding:16px 24px;margin:16px 0;text-align:center">
            <div style="font-size:0.8rem;color:var(--muted);letter-spacing:0.08em;margin-bottom:4px">YOUR REFERENCE NUMBER</div>
            <div style="font-size:2rem;font-weight:700;color:var(--gold)">BUN-<?= str_pad($ref_id, 5, '0', STR_PAD_LEFT) ?></div>
            <div style="font-size:0.8rem;color:var(--muted);margin-top:6px">Use this number to check your reservation status.</div>
        </div>
        <p style="font-size:0.88rem;color:var(--muted)">Waiting for admin approval. Thank you for trusting us.</p>
        <a href="index.php" class="btn btn-gold">OK</a>
    </div>
</div>
<?php endif; ?>

</body>
</html>
