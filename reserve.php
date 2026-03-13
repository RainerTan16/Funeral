<?php
require_once 'includes/db.php';
$activePage = 'home';
session_start();

$casket_id = isset($_GET['casket_id']) ? (int)$_GET['casket_id'] : 1;
$casket = $conn->query("SELECT * FROM caskets WHERE id = $casket_id")->fetch_assoc();
if (!$casket) {
    $casket = $conn->query("SELECT * FROM caskets LIMIT 1")->fetch_assoc();
    $casket_id = $casket['id'];
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Clear session if starting fresh on step 1
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['res_name'], $_SESSION['res_address'], $_SESSION['res_date'],
          $_SESSION['res_phone'], $_SESSION['res_casket'], $_SESSION['res_color'],
          $_SESSION['res_quantity'], $_SESSION['res_total'], $_SESSION['res_remarks'],
          $_SESSION['res_dec_name'], $_SESSION['res_dec_age'], $_SESSION['res_dec_dod'],
          $_SESSION['res_color_img']);
}

$colorOptions = ['Brown', 'Black', 'White', 'Dark Walnut', 'Natural Oak'];
$error   = '';
$error2  = '';
$success = false;
$ref_id  = null;

// ── STEP 1: Client + Deceased Info ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    $name         = trim($_POST['client_name'] ?? '');
    $address      = trim($_POST['client_address'] ?? '');
    $date         = trim($_POST['reservation_date'] ?? '');
    $phone        = trim($_POST['client_phone'] ?? '');
    $remarks      = trim($_POST['remarks'] ?? '');
    $dec_name     = trim($_POST['deceased_name'] ?? '');
    $dec_age      = trim($_POST['deceased_age'] ?? '');
    $dec_dod      = trim($_POST['deceased_dod'] ?? '');

    if (!$name || !$address || !$date || !$phone || !$dec_name || !$dec_age || !$dec_dod) {
        $error = 'Please complete all required fields.';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $error = 'Phone number must be 11 digits and start with 09.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Reservation date cannot be in the past. Please select today or a future date.';
    } else {
        $_SESSION['res_name']     = $name;
        $_SESSION['res_address']  = $address;
        $_SESSION['res_date']     = $date;
        $_SESSION['res_phone']    = $phone;
        $_SESSION['res_casket']   = $casket_id;
        $_SESSION['res_remarks']  = $remarks;
        $_SESSION['res_dec_name'] = $dec_name;
        $_SESSION['res_dec_age']  = $dec_age;
        $_SESSION['res_dec_dod']  = $dec_dod;
        header("Location: reserve.php?casket_id=$casket_id&step=2");
        exit;
    }
}

// ── STEP 2: Color + Quantity ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2'])) {
    $color    = trim($_POST['color'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);

    if (!$color || $quantity < 1) {
        $error2 = 'Please select a color and enter a valid quantity.';
        $step = 2;
    } else {
        $_SESSION['res_color']    = $color;
        $_SESSION['res_quantity'] = $quantity;
        $_SESSION['res_total']    = $casket['price'] * $quantity;
        $_SESSION['res_color_img'] = $color !== '' ? 'images/' . strtolower(str_replace(' ', '_', $casket['name'])) . '_' . strtolower(str_replace(' ', '_', $color)) . '.png' : $casket['image_url'];
        header("Location: reserve.php?casket_id=$casket_id&step=3");
        exit;
    }
}

// ── STEP 3: Confirm & Submit ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $name     = $conn->real_escape_string($_SESSION['res_name'] ?? '');
    $address  = $conn->real_escape_string($_SESSION['res_address'] ?? '');
    $date     = $conn->real_escape_string($_SESSION['res_date'] ?? '');
    $phone    = $conn->real_escape_string($_SESSION['res_phone'] ?? '');
    $cid      = (int)($_SESSION['res_casket'] ?? $casket_id);
    $color    = $conn->real_escape_string($_SESSION['res_color'] ?? '');
    $quantity = (int)($_SESSION['res_quantity'] ?? 1);
    $total    = (float)($_SESSION['res_total'] ?? 0);
    $remarks  = $conn->real_escape_string($_SESSION['res_remarks'] ?? '');
    $dec_name = $conn->real_escape_string($_SESSION['res_dec_name'] ?? '');
    $dec_age  = (int)($_SESSION['res_dec_age'] ?? 0);
    $dec_dod  = $conn->real_escape_string($_SESSION['res_dec_dod'] ?? '');

    $conn->query("INSERT INTO reservations
        (client_name, client_address, client_phone, reservation_date, deceased_name, deceased_age, deceased_dod,
         casket_id, casket_color, quantity, payment_type, total_amount, remarks, status)
        VALUES ('$name','$address','$phone','$date','$dec_name','$dec_age','$dec_dod',
                '$cid','$color','$quantity','Cash','$total','$remarks','Pending')");

    $ref_id = $conn->insert_id;

    // Decrease stock
    $conn->query("UPDATE caskets SET stock = stock - $quantity WHERE id = $cid AND stock >= $quantity");

    unset($_SESSION['res_name'], $_SESSION['res_address'], $_SESSION['res_date'], $_SESSION['res_color_img'],
          $_SESSION['res_phone'], $_SESSION['res_casket'], $_SESSION['res_color'],
          $_SESSION['res_quantity'], $_SESSION['res_total'], $_SESSION['res_remarks'],
          $_SESSION['res_dec_name'], $_SESSION['res_dec_age'], $_SESSION['res_dec_dod']);

    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve — Y2J Funeral Service</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reserve-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
        .reserve-image-panel { position: sticky; top: 110px; text-align: center; }
        .reserve-image-panel img { width: 100%; max-height: 300px; object-fit: contain; border-radius: 12px; background: var(--white); padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .reserve-image-panel h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; margin-top: 16px; margin-bottom: 4px; }
        .price-display { font-size: 1.4rem; font-weight: 700; color: var(--gold); margin-bottom: 4px; }
        .material { font-size: 0.88rem; color: var(--muted); }
        .total-display { background: var(--navy); color: var(--white); border-radius: 10px; padding: 14px 20px; margin-top: 16px; font-size: 1rem; font-weight: 500; }
        .total-display strong { font-size: 1.3rem; color: var(--gold-lt); }
        .section-divider { font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em; color: var(--muted); text-transform: uppercase; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #eee; }
        .confirm-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .confirm-table td { padding: 11px 16px; border-bottom: 1px solid #eee; font-size: 0.92rem; }
        .confirm-table td:first-child { font-weight: 600; color: var(--muted); width: 180px; }
        .confirm-table .section-header td { background: var(--grey-bg); font-size: 0.78rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); padding: 8px 16px; }
        .confirm-table tr:last-child td { border-bottom: none; }
        .confirm-total { font-size: 1.3rem; font-weight: 700; color: var(--gold); }
        .ref-box { background: var(--navy); color: var(--white); border-radius: 12px; padding: 20px 28px; text-align: center; margin-bottom: 20px; }
        .ref-box .ref-label { font-size: 0.85rem; letter-spacing: 0.1em; opacity: 0.7; margin-bottom: 6px; }
        .ref-box .ref-number { font-size: 2rem; font-weight: 700; color: var(--gold-lt); letter-spacing: 0.05em; }
        .ref-box .ref-note { font-size: 0.82rem; opacity: 0.7; margin-top: 8px; }
        .steps { display: flex; justify-content: center; gap: 8px; margin-bottom: 36px; align-items: center; }
        .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; font-weight: 700; background: #ddd; color: var(--muted); }
        .step-dot.active { background: var(--gold); color: var(--white); }
        .step-dot.done { background: var(--navy); color: var(--white); }
        .step-line { flex: 1; max-width: 60px; height: 2px; background: #ddd; }
        .step-line.done { background: var(--navy); }
        @media print { nav, .btn, .steps, .form-center { display: none !important; } body { background: white; } .card-section { box-shadow: none; } }
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
    <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2</div>
    <div class="step-line <?= $step > 2 ? 'done' : '' ?>"></div>
    <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
</div>
<?php endif; ?>

<?php if ($step == 1): ?>
<div class="reserve-layout">
    <div class="reserve-image-panel">
        <img src="<?= htmlspecialchars($casket['image_url']) ?>" alt="<?= htmlspecialchars($casket['name']) ?>" onerror="this.src='images/placeholder.png'">
        <h2><?= htmlspecialchars($casket['name']) ?></h2>
        <div class="price-display">₱<?= number_format($casket['price'], 0) ?> per unit</div>
        <div class="material"><?= htmlspecialchars($casket['material']) ?></div>
    </div>
    <form method="POST">
        <input type="hidden" name="step1" value="1">

        <div class="section-divider">Client Information</div>
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="client_name" value="<?= htmlspecialchars($_SESSION['res_name'] ?? $_POST['client_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz">
        </div>
        <div class="form-group">
            <label>Full Address:</label>
            <input type="text" name="client_address" value="<?= htmlspecialchars($_SESSION['res_address'] ?? $_POST['client_address'] ?? '') ?>" placeholder="Street, Barangay, City, Province">
        </div>
        <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="client_phone" value="<?= htmlspecialchars($_SESSION['res_phone'] ?? $_POST['client_phone'] ?? '') ?>" placeholder="09XXXXXXXXX" maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
        <div class="form-group">
            <label>Reservation Date:</label>
            <input type="date" name="reservation_date" value="<?= htmlspecialchars($_SESSION['res_date'] ?? $_POST['reservation_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
        </div>

        <div class="section-divider">Deceased Information</div>
        <div class="form-group">
            <label>Name of Deceased:</label>
            <input type="text" name="deceased_name" value="<?= htmlspecialchars($_SESSION['res_dec_name'] ?? $_POST['deceased_name'] ?? '') ?>" placeholder="Full name of the deceased">
        </div>
        <!-- <div class="form-group"> -->
            <label>Age:</label>
            <input type="number" name="deceased_age" value="<?= htmlspecialchars($_SESSION['res_dec_age'] ?? $_POST['deceased_age'] ?? '') ?>" min="0" max="130" placeholder="e.g. 72">
        </div>
        <div class="form-group">
            <label>Date of Death:</label>
            <input type="date" name="deceased_dod" value="<?= htmlspecialchars($_SESSION['res_dec_dod'] ?? $_POST['deceased_dod'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="section-divider">Additional</div>
        <div class="form-group">
            <label>Special Remarks <span style="font-weight:400;color:var(--muted)">(Optional)</span></label>
            <textarea name="remarks" rows="3" placeholder="e.g. Preferred pickup time, special requests..." style="resize:vertical"><?= htmlspecialchars($_SESSION['res_remarks'] ?? $_POST['remarks'] ?? '') ?></textarea>
        </div>
        <div class="form-center">
            <button type="submit" class="btn btn-gold">NEXT →</button>
        </div>
    </form>
</div>

<?php elseif ($step == 2): ?>
<?php $casketSlug = strtolower(str_replace(' ', '_', $casket['name'])); ?>
<div class="reserve-layout">
    <div class="reserve-image-panel">
        <img src="<?= htmlspecialchars($casket['image_url']) ?>" alt="<?= htmlspecialchars($casket['name']) ?>" onerror="this.src='images/placeholder.png'" id="colorPreviewImg">
        <h2><?= htmlspecialchars($casket['name']) ?></h2>
        <div class="price-display">₱<?= number_format($casket['price'], 0) ?> per unit</div>
        <div class="material"><?= htmlspecialchars($casket['material']) ?></div>
        <div class="total-display">Total: <strong id="totalDisplay">₱<?= number_format($casket['price'], 0) ?></strong></div>
    </div>
    <form method="POST">
        <input type="hidden" name="step2" value="1">
        <div class="form-group">
            <label>Choose the color:</label>
            <select name="color" id="colorSelect" onchange="updateColorPreview(this.value)">
                <option value="">-- Select Color --</option>
                <?php foreach ($colorOptions as $c): ?>
                <option value="<?= $c ?>"><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Enter Quantity:</label>
            <input type="number" name="quantity" id="quantityInput" min="1" max="<?= $casket['stock'] ?>" value="1" oninput="updateTotal(this.value)">
        </div>
        <?php if ($casket['stock'] <= 2): ?>
        <p style="color:var(--danger);font-size:0.85rem;margin-top:-8px">⚠ Only <?= $casket['stock'] ?> unit(s) left in stock.</p>
        <?php endif; ?>
        <div class="form-center" style="margin-top:40px">
            <a href="reserve.php?casket_id=<?= $casket_id ?>" class="btn btn-outline" style="margin-right:12px">← Back</a>
            <button type="submit" class="btn btn-gold">NEXT →</button>
        </div>
    </form>
</div>
<script>
    const unitPrice  = <?= $casket['price'] ?>;
    const casketSlug = '<?= $casketSlug ?>';
    const defaultImg = '<?= htmlspecialchars($casket['image_url']) ?>';
    function updateColorPreview(color) {
        const img = document.getElementById('colorPreviewImg');
        if (!color) { img.src = defaultImg; return; }
        const slug = color.toLowerCase().replace(/ /g, '_');
        const tryLoad = (exts, i) => {
            if (i >= exts.length) { img.src = defaultImg; return; }
            const t = new Image();
            t.onload  = () => img.src = `images/${casketSlug}_${slug}.${exts[i]}`;
            t.onerror = () => tryLoad(exts, i + 1);
            t.src = `images/${casketSlug}_${slug}.${exts[i]}`;
        };
        tryLoad(['png', 'jpg', 'jpeg'], 0);
    }
    function updateTotal(qty) {
        qty = parseInt(qty) || 1;
        document.getElementById('totalDisplay').textContent = '₱' + (unitPrice * qty).toLocaleString('en-PH');
    }
</script>

<?php elseif ($step == 3): ?>
<h2 style="font-family:'Cormorant Garamond',serif;font-size:1.6rem;margin-bottom:8px;text-align:center">Review Your Reservation</h2>
<p style="text-align:center;color:var(--muted);font-size:0.9rem;margin-bottom:28px">Please check all details before confirming.</p>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
    <div style="text-align:center">
        <img src="<?= htmlspecialchars($_SESSION['res_color_img'] ?? $casket['image_url']) ?>" alt="<?= htmlspecialchars($casket['name']) ?>" onerror="this.src='images/placeholder.png'" style="width:100%;max-height:220px;object-fit:contain;background:var(--white);border-radius:12px;padding:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08)">
        <div style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;margin-top:12px"><?= htmlspecialchars($casket['name']) ?></div>
    </div>
    <div>
        <table class="confirm-table">
            <tr class="section-header"><td colspan="2">Client Information</td></tr>
            <tr><td>Full Name</td><td><?= htmlspecialchars($_SESSION['res_name'] ?? '') ?></td></tr>
            <tr><td>Address</td><td><?= htmlspecialchars($_SESSION['res_address'] ?? '') ?></td></tr>
            <tr><td>Phone</td><td><?= htmlspecialchars($_SESSION['res_phone'] ?? '') ?></td></tr>
            <tr><td>Reservation Date</td><td><?= htmlspecialchars($_SESSION['res_date'] ?? '') ?></td></tr>
            <tr class="section-header"><td colspan="2">Deceased Information</td></tr>
            <tr><td>Name</td><td><?= htmlspecialchars($_SESSION['res_dec_name'] ?? '') ?></td></tr>
            <tr><td>Age</td><td><?= htmlspecialchars($_SESSION['res_dec_age'] ?? '') ?></td></tr>
            <tr><td>Date of Death</td><td><?= htmlspecialchars($_SESSION['res_dec_dod'] ?? '') ?></td></tr>
            <tr class="section-header"><td colspan="2">Casket & Payment</td></tr>
            <tr><td>Casket</td><td><?= htmlspecialchars($casket['name']) ?></td></tr>
            <tr><td>Color</td><td><?= htmlspecialchars($_SESSION['res_color'] ?? '') ?></td></tr>
            <tr><td>Quantity</td><td><?= (int)($_SESSION['res_quantity'] ?? 1) ?></td></tr>
            <tr><td>Payment</td><td>Cash</td></tr>
            <tr><td>Total Amount</td><td class="confirm-total">₱<?= number_format($_SESSION['res_total'] ?? 0, 0) ?></td></tr>
            <?php if (!empty($_SESSION['res_remarks'])): ?>
            <tr class="section-header"><td colspan="2">Remarks</td></tr>
            <tr><td colspan="2"><?= htmlspecialchars($_SESSION['res_remarks']) ?></td></tr>
            <?php endif; ?>
        </table>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="reserve.php?casket_id=<?= $casket_id ?>&step=2" class="btn btn-outline">← Edit</a>
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

<?php if (!empty($error)): ?>
<div class="modal-overlay show" id="errModal">
    <div class="modal">
        <h3 style="color:var(--danger);font-size:1.1rem"><?= htmlspecialchars($error) ?></h3>
        <button class="btn btn-gold" onclick="document.getElementById('errModal').classList.remove('show')">OK</button>
    </div>
</div>
<?php endif; ?>
<?php if (!empty($error2)): ?>
<div class="modal-overlay show" id="errModal2">
    <div class="modal">
        <h3 style="color:var(--danger);font-size:1.1rem"><?= htmlspecialchars($error2) ?></h3>
        <button class="btn btn-gold" onclick="document.getElementById('errModal2').classList.remove('show')">OK</button>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="modal-overlay show">
    <div class="modal">
        <h3 class="success">RESERVATION SUBMITTED!</h3>
        <div style="background:var(--grey-bg);border-radius:10px;padding:16px 24px;margin:16px 0;text-align:center">
            <div style="font-size:0.8rem;color:var(--muted);letter-spacing:0.08em;margin-bottom:4px">YOUR REFERENCE NUMBER</div>
            <div style="font-size:2rem;font-weight:700;color:var(--gold)">RES-<?= str_pad($ref_id, 5, '0', STR_PAD_LEFT) ?></div>
            <div style="font-size:0.8rem;color:var(--muted);margin-top:6px">Use this number to check your reservation status.</div>
        </div>
        <p style="font-size:0.88rem;color:var(--muted)">Waiting for admin approval. Thank you for trusting us.</p>
        <a href="index.php" class="btn btn-gold">OK</a>
    </div>
</div>
<?php endif; ?>

</body>
</html>
