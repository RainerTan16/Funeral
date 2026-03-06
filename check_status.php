<?php
require_once 'includes/db.php';
$activePage = 'status';

$results  = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input    = trim($_POST['search'] ?? '');
    $searched = true;

    if (empty($input)) {
        $searchError = 'Please enter a phone number or reference number.';
    } else {
        // Detect if it's a reference number (RES-XXXXX or BUN-XXXXX)
        if (preg_match('/^(RES|BUN)-?(\d+)$/i', strtoupper($input), $m)) {
            $type = strtoupper($m[1]);
            $id   = (int)$m[2];

            if ($type === 'RES') {
                $q = $conn->query("SELECT r.*, c.name as item_name, 'Casket Reservation' as type
                    FROM reservations r JOIN caskets c ON r.casket_id = c.id WHERE r.id = $id");
            } else {
                $q = $conn->query("SELECT br.*, b.name as item_name, 'Bundle Reservation' as type
                    FROM bundle_reservations br JOIN bundles b ON br.bundle_id = b.id WHERE br.id = $id");
            }
            while ($row = $q->fetch_assoc()) {
                $row['ref_prefix'] = $type;
                $results[] = $row;
            }
        } else {
    $searchError = 'Please enter a valid reference number (e.g. RES-00001 or BUN-00001).';
}
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Reservation Status — Y2J Funeral Service</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .status-card { background: var(--white); border-radius: var(--radius); padding: 24px 28px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 5px solid var(--gold); }
        .status-card.approved  { border-color: var(--success); }
        .status-card.rejected  { border-color: var(--danger); }
        .status-card.delivered { border-color: #084298; }
        .status-card.pending   { border-color: #856404; }
        .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; font-size: 0.9rem; }
        .status-grid .lbl { color: var(--muted); font-weight: 600; }
        .section-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin: 16px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #eee; }
        .rejection-box { background: #fff3f3; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px 16px; margin-top: 12px; font-size: 0.9rem; color: var(--danger); }
        .ref-badge { display: inline-block; background: var(--navy); color: var(--gold-lt); padding: 3px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 10px; }
    </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="page-content">
<div class="card-section">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;margin-bottom:8px;text-align:center">Check Reservation Status</h2>
    <p style="text-align:center;color:var(--muted);font-size:0.9rem;margin-bottom:32px">
        Enter your <strong>reference number</strong> (e.g. RES-00001 or BUN-00001).
    </p>

    <form method="POST" style="max-width:480px;margin:0 auto 40px">
        <div class="form-group" style="grid-template-columns:1fr">
            <input type="text" name="search"
                   placeholder="e.g. RES-00001 or BUN-00001"
                   value="<?= htmlspecialchars($_POST['search'] ?? '') ?>"
                   style="text-align:center;font-size:1.05rem;letter-spacing:0.05em">
        </div>
        <?php if (!empty($searchError)): ?>
        <p style="color:var(--danger);font-size:0.875rem;text-align:center;margin-bottom:10px"><?= htmlspecialchars($searchError) ?></p>
        <?php endif; ?>
        <div class="form-center">
            <button type="submit" class="btn btn-gold">CHECK STATUS</button>
        </div>
    </form>

    <?php if ($searched && empty($searchError)): ?>
        <?php if (empty($results)): ?>
        <p style="text-align:center;color:var(--muted);padding:24px 0">No reservations found. Please check your phone number or reference number.</p>
        <?php else: ?>
        <div style="max-width:680px;margin:0 auto">
            <?php foreach ($results as $r): ?>
            <div class="status-card <?= strtolower($r['status']) ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
                    <div>
                        <div class="ref-badge"><?= $r['ref_prefix'] ?>-<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:1.2rem;font-weight:600"><?= htmlspecialchars($r['item_name']) ?></div>
                        <div style="font-size:0.82rem;color:var(--muted)"><?= htmlspecialchars($r['type']) ?></div>
                    </div>
                    <span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span>
                </div>

                <div class="section-label">Client</div>
                <div class="status-grid">
                    <span class="lbl">Name</span><span><?= htmlspecialchars($r['client_name']) ?></span>
                    <span class="lbl">Phone</span><span><?= htmlspecialchars($r['client_phone']) ?></span>
                    <span class="lbl">Reservation Date</span><span><?= htmlspecialchars($r['reservation_date']) ?></span>
                </div>

                <?php if (!empty($r['deceased_name'])): ?>
                <div class="section-label">Deceased</div>
                <div class="status-grid">
                    <span class="lbl">Name</span><span><?= htmlspecialchars($r['deceased_name']) ?></span>
                    <span class="lbl">Age</span><span><?= htmlspecialchars($r['deceased_age']) ?></span>
                    <span class="lbl">Date of Death</span><span><?= htmlspecialchars($r['deceased_dod']) ?></span>
                </div>
                <?php endif; ?>

                <div class="section-label">Payment</div>
                <div class="status-grid">
                    <span class="lbl">Total Amount</span><span style="font-weight:700;color:var(--gold)">₱<?= number_format($r['total_amount'], 0) ?></span>
                    <span class="lbl">Method</span><span>Cash</span>
                </div>

                <?php if (!empty($r['remarks'])): ?>
                <div class="section-label">Your Remarks</div>
                <p style="font-size:0.88rem;margin:0"><?= htmlspecialchars($r['remarks']) ?></p>
                <?php endif; ?>

                <p style="font-size:0.78rem;color:var(--muted);margin-top:14px;margin-bottom:0">Submitted: <?= date('F j, Y', strtotime($r['created_at'])) ?></p>

                <?php if ($r['status'] === 'Rejected' && $r['rejection_reason']): ?>
                <div class="rejection-box">
                    <strong>Reason for rejection:</strong> <?= htmlspecialchars($r['rejection_reason']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>
</body>
</html>
