<?php
require_once 'auth.php';
$page = 'reservations';

// Handle archive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_res'])) {
    $rid = (int)$_POST['res_id'];
    $r   = $conn->query("SELECT r.*, c.name as casket_name FROM reservations r JOIN caskets c ON r.casket_id = c.id WHERE r.id = $rid")->fetch_assoc();
    if ($r) {
        $conn->query("INSERT INTO archived_reservations
            (original_id, client_name, client_address, client_phone, reservation_date,
             deceased_name, deceased_age, deceased_dod, casket_name, casket_color, quantity,
             total_amount, remarks, admin_notes, status, rejection_reason, original_created_at)
            VALUES (
                '{$r['id']}','" . $conn->real_escape_string($r['client_name']) . "','" . $conn->real_escape_string($r['client_address']) . "',
                '" . $conn->real_escape_string($r['client_phone']) . "','" . $conn->real_escape_string($r['reservation_date']) . "',
                '" . $conn->real_escape_string($r['deceased_name'] ?? '') . "','{$r['deceased_age']}','" . $conn->real_escape_string($r['deceased_dod'] ?? '') . "',
                '" . $conn->real_escape_string($r['casket_name']) . "','" . $conn->real_escape_string($r['casket_color']) . "','{$r['quantity']}',
                '{$r['total_amount']}','" . $conn->real_escape_string($r['remarks'] ?? '') . "','" . $conn->real_escape_string($r['admin_notes'] ?? '') . "',
                '" . $conn->real_escape_string($r['status']) . "','" . $conn->real_escape_string($r['rejection_reason'] ?? '') . "',
                '" . $conn->real_escape_string($r['created_at']) . "')");
        $conn->query("DELETE FROM reservations WHERE id = $rid");
    }
    header('Location: reservations.php?archived=1');
    exit;
}

// Handle status + notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rid    = (int)$_POST['res_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $reason = $conn->real_escape_string(trim($_POST['rejection_reason'] ?? ''));
    $notes  = $conn->real_escape_string(trim($_POST['admin_notes'] ?? ''));
    if ($status === 'Rejected' && $reason) {
        $conn->query("UPDATE reservations SET status='$status', rejection_reason='$reason', admin_notes='$notes' WHERE id=$rid");
    } else {
        $conn->query("UPDATE reservations SET status='$status', rejection_reason=NULL, admin_notes='$notes' WHERE id=$rid");
    }
    header('Location: reservations.php?updated=1');
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? "WHERE r.status = '" . $conn->real_escape_string($statusFilter) . "'" : '';

$reservations = $conn->query("
    SELECT r.*, c.name as casket_name, c.price as unit_price,
           c.stock as casket_stock, c.low_stock_threshold
    FROM reservations r
    JOIN caskets c ON r.casket_id = c.id
    $where
    ORDER BY r.created_at DESC
");

// Low stock alert
$lowStock = $conn->query("SELECT name, stock FROM caskets WHERE stock <= low_stock_threshold ORDER BY stock ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .detail-panel { display:none; position:fixed; top:0; right:0; width:420px; height:100vh; background:white; box-shadow:-4px 0 24px rgba(0,0,0,0.12); z-index:1000; overflow-y:auto; padding:28px 28px 40px; }
        .detail-panel.open { display:block; }
        .detail-panel h3 { font-family:'Cormorant Garamond',serif; font-size:1.4rem; margin-bottom:20px; }
        .detail-row { display:grid; grid-template-columns:140px 1fr; gap:6px 12px; font-size:0.88rem; margin-bottom:4px; }
        .detail-row .lbl { color:var(--muted); font-weight:600; }
        .detail-section { font-size:0.75rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted); margin:16px 0 8px; padding-bottom:5px; border-bottom:1px solid #eee; }
        .overlay-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.3); z-index:999; }
        .overlay-bg.open { display:block; }
        @media print {
            .admin-layout > :not(.print-area), nav, .sidebar, .admin-header, .table-card { display:none !important; }
            .print-area { display:block !important; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Reservations</h1>
        <div>
            <a href="reservations.php" class="btn btn-outline" style="font-size:0.85rem;padding:8px 20px">All</a>
            <a href="reservations.php?status=Pending" class="btn btn-outline" style="font-size:0.85rem;padding:8px 20px">Pending</a>
            <a href="reservations.php?status=Approved" class="btn btn-outline" style="font-size:0.85rem;padding:8px 20px">Approved</a>
            <a href="reservations.php?status=Delivered" class="btn btn-outline" style="font-size:0.85rem;padding:8px 20px">Delivered</a>
        </div>
    </div>

    <?php if (isset($_GET['archived'])): ?>
    <div style="background:#d1e7dd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#155724;font-size:0.9rem">
        ✓ Reservation archived successfully.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
    <div style="background:#d1e7dd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#155724;font-size:0.9rem">
        ✓ Reservation updated successfully.
    </div>
    <?php endif; ?>

    <?php if ($lowStock && $lowStock->num_rows > 0): ?>
    <div style="background:#fff3cd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#856404;font-size:0.88rem">
        ⚠ <strong>Low Stock Alert:</strong>
        <?php while ($ls = $lowStock->fetch_assoc()): ?>
            <strong><?= htmlspecialchars($ls['name']) ?></strong> (<?= $ls['stock'] ?> left)&nbsp;&nbsp;
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Client</th>
                    <th>Phone</th>
                    <th>Deceased</th>
                    <th>Casket</th>
                    <th>Color / Qty</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $reservations->fetch_assoc()): ?>
            <tr>
                <td>
                    <a href="#" onclick="openDetail(<?= htmlspecialchars(json_encode($r)) ?>); return false;"
                       style="font-weight:700;color:var(--gold);text-decoration:underline;cursor:pointer"
                       title="Click to view full details">
                        RES-<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?> 🔍
                    </a>
                </td>
                <td><?= htmlspecialchars($r['client_name']) ?></td>
                <td><?= htmlspecialchars($r['client_phone']) ?></td>
                <td><?= htmlspecialchars($r['deceased_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['casket_name']) ?></td>
                <td><?= htmlspecialchars($r['casket_color']) ?> / <?= $r['quantity'] ?></td>
                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                <td>₱<?= number_format($r['total_amount'], 0) ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td>
                    <form method="POST" style="display:flex;flex-direction:column;gap:6px;min-width:220px">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <div style="display:flex;gap:6px;align-items:center">
                            <select name="status" id="status_<?= $r['id'] ?>" onchange="toggleReason(<?= $r['id'] ?>, this.value)" style="padding:6px 12px;border-radius:20px;border:1px solid #ddd;font-size:0.82rem">
                                <option value="Pending"   <?= $r['status']==='Pending'   ?'selected':'' ?>>Pending</option>
                                <option value="Approved"  <?= $r['status']==='Approved'  ?'selected':'' ?>>Approved</option>
                                <option value="Rejected"  <?= $r['status']==='Rejected'  ?'selected':'' ?>>Rejected</option>
                                <option value="Delivered" <?= $r['status']==='Delivered' ?'selected':'' ?>>Delivered</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-gold" style="padding:6px 14px;font-size:0.8rem">Save</button>
                        </div>                        <div id="reason_<?= $r['id'] ?>" style="display:<?= $r['status']==='Rejected' ? 'block' : 'none' ?>">
                            <input type="text" name="rejection_reason"
                                   value="<?= htmlspecialchars($r['rejection_reason'] ?? '') ?>"
                                   placeholder="Reason for rejection..."
                                   style="width:100%;padding:6px 10px;border-radius:8px;border:1px solid #ddd;font-size:0.82rem;font-family:Jost,sans-serif">
                        </div>
                        <textarea name="admin_notes" rows="2" placeholder="Internal notes (admin only)..."
                                  style="width:100%;padding:6px 10px;border-radius:8px;border:1px solid #ddd;font-size:0.82rem;font-family:Jost,sans-serif;resize:none"><?= htmlspecialchars($r['admin_notes'] ?? '') ?></textarea>
                    </form>
                    <form method="POST" onsubmit="return confirm('Archive this reservation? It will be moved to the Archive.')">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <button type="submit" name="archive_res" class="btn btn-outline" style="padding:5px 12px;font-size:0.78rem;margin-top:4px;width:100%">🗄 Archive</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</div>

<!-- Detail Slide Panel -->
<div class="overlay-bg" id="overlayBg" onclick="closeDetail()"></div>
<div class="detail-panel" id="detailPanel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h3 id="dp-ref">RES-00000</h3>
        <div style="display:flex;gap:8px">
            <button onclick="printDetail()" class="btn btn-outline" style="padding:6px 14px;font-size:0.82rem">🖨 Print</button>
            <button onclick="closeDetail()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--muted)">×</button>
        </div>
    </div>
    <div id="dp-content"></div>
</div>

<script>
function toggleReason(id, status) {
    document.getElementById('reason_' + id).style.display = status === 'Rejected' ? 'block' : 'none';
}

function openDetail(r) {
    document.getElementById('dp-ref').textContent = 'RES-' + String(r.id).padStart(5, '0');
    document.getElementById('dp-content').innerHTML = `
        <div class="detail-section">Client Information</div>
        <div class="detail-row"><span class="lbl">Name</span><span>${esc(r.client_name)}</span></div>
        <div class="detail-row"><span class="lbl">Address</span><span>${esc(r.client_address)}</span></div>
        <div class="detail-row"><span class="lbl">Phone</span><span>${esc(r.client_phone)}</span></div>
        <div class="detail-row"><span class="lbl">Reservation Date</span><span>${esc(r.reservation_date)}</span></div>
        <div class="detail-section">Deceased Information</div>
        <div class="detail-row"><span class="lbl">Name</span><span>${esc(r.deceased_name || '—')}</span></div>
        <div class="detail-row"><span class="lbl">Age</span><span>${esc(r.deceased_age || '—')}</span></div>
        <div class="detail-row"><span class="lbl">Date of Death</span><span>${esc(r.deceased_dod || '—')}</span></div>
        <div class="detail-section">Casket & Payment</div>
        <div class="detail-row"><span class="lbl">Casket</span><span>${esc(r.casket_name)}</span></div>
        <div class="detail-row"><span class="lbl">Color</span><span>${esc(r.casket_color)}</span></div>
        <div class="detail-row"><span class="lbl">Quantity</span><span>${esc(r.quantity)}</span></div>
        <div class="detail-row"><span class="lbl">Total</span><span style="font-weight:700;color:var(--gold)">₱${Number(r.total_amount).toLocaleString('en-PH')}</span></div>
        <div class="detail-row"><span class="lbl">Payment</span><span>Cash</span></div>
        ${r.remarks ? `<div class="detail-section">Client Remarks</div><p style="font-size:0.88rem">${esc(r.remarks)}</p>` : ''}
        ${r.admin_notes ? `<div class="detail-section">Admin Notes</div><p style="font-size:0.88rem">${esc(r.admin_notes)}</p>` : ''}
        ${r.rejection_reason ? `<div class="detail-section">Rejection Reason</div><p style="font-size:0.88rem;color:var(--danger)">${esc(r.rejection_reason)}</p>` : ''}
        <div class="detail-section">Status</div>
        <span class="badge badge-${r.status.toLowerCase()}">${r.status}</span>
        <p style="font-size:0.8rem;color:var(--muted);margin-top:16px">Submitted: ${esc(r.created_at)}</p>
    `;
    document.getElementById('detailPanel').classList.add('open');
    document.getElementById('overlayBg').classList.add('open');
}

function closeDetail() {
    document.getElementById('detailPanel').classList.remove('open');
    document.getElementById('overlayBg').classList.remove('open');
}

function printDetail() {
    const content = document.getElementById('dp-content').innerHTML;
    const ref     = document.getElementById('dp-ref').textContent;
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>${ref}</title>
        <style>body{font-family:Georgia,serif;padding:32px;max-width:480px}
        .detail-section{font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#666;margin:16px 0 6px;padding-bottom:4px;border-bottom:1px solid #eee}
        .detail-row{display:grid;grid-template-columns:140px 1fr;gap:4px 12px;font-size:0.88rem;margin-bottom:3px}
        .lbl{color:#666;font-weight:600}.badge{padding:3px 10px;border-radius:20px;font-size:0.8rem}
        h2{margin-bottom:4px}p{font-size:0.85rem;color:#666}</style>
        </head><body><h2>Y2J Funeral Service</h2><h3>${ref}</h3>${content}</body></html>`);
    w.document.close();
    w.print();
}

function esc(str) {
    if (str === null || str === undefined) return '—';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
