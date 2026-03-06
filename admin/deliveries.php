<?php
require_once 'auth.php';
$page = 'deliveries';

// Handle mark delivered — casket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'])) {
    $rid  = (int)$_POST['res_id'];
    $type = $_POST['type'] ?? 'casket';
    $table = $type === 'bundle' ? 'bundle_reservations' : 'reservations';
    $conn->query("UPDATE $table SET status='Delivered' WHERE id=$rid");
    header('Location: deliveries.php?updated=1');
    exit;
}

// Casket deliveries
$casketDeliveries = $conn->query("
    SELECT r.*, c.name as item_name, 'casket' as type
    FROM reservations r
    JOIN caskets c ON r.casket_id = c.id
    WHERE r.status IN ('Approved','Delivered')
    ORDER BY r.reservation_date ASC
");

// Bundle deliveries
$bundleDeliveries = $conn->query("
    SELECT br.*, b.name as item_name, 'bundle' as type, 1 as quantity, NULL as casket_color
    FROM bundle_reservations br
    JOIN bundles b ON br.bundle_id = b.id
    WHERE br.status IN ('Approved','Delivered')
    ORDER BY br.reservation_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliveries — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Deliveries</h1>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div style="background:#d1e7dd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#155724;font-size:0.9rem">
        ✓ Marked as delivered.
    </div>
    <?php endif; ?>

    <!-- Casket Deliveries -->
    <div class="table-card" style="margin-bottom:28px">
        <h2>🪦 Casket Reservations</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Client</th><th>Phone</th><th>Address</th>
                    <th>Casket</th><th>Color</th><th>Qty</th>
                    <th>Date</th><th>Payment</th><th>Total</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $casketDeliveries->fetch_assoc()): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['client_name']) ?></td>
                <td><?= htmlspecialchars($r['client_phone']) ?></td>
                <td style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['client_address']) ?></td>
                <td><?= htmlspecialchars($r['item_name']) ?></td>
                <td><?= htmlspecialchars($r['casket_color']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                <td><?= htmlspecialchars($r['payment_type']) ?></td>
                <td>₱<?= number_format($r['total_amount'], 0) ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td>
                    <?php if ($r['status'] === 'Approved'): ?>
                    <form method="POST">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="type" value="casket">
                        <button type="submit" name="mark_delivered" class="btn btn-success" style="padding:6px 14px;font-size:0.82rem">Mark Delivered</button>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--muted);font-size:0.85rem">— Done</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bundle Deliveries -->
    <div class="table-card">
        <h2>📦 Bundle Reservations</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Client</th><th>Phone</th><th>Address</th>
                    <th>Package</th><th>Date</th><th>Payment</th><th>Total</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $bundleDeliveries->fetch_assoc()): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['client_name']) ?></td>
                <td><?= htmlspecialchars($r['client_phone']) ?></td>
                <td style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['client_address']) ?></td>
                <td>
                    <span style="background:var(--grey-bg);padding:3px 10px;border-radius:50px;font-size:0.82rem">
                        📦 <?= htmlspecialchars($r['item_name']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                <td><?= htmlspecialchars($r['payment_type']) ?></td>
                <td>₱<?= number_format($r['total_amount'], 0) ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td>
                    <?php if ($r['status'] === 'Approved'): ?>
                    <form method="POST">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="type" value="bundle">
                        <button type="submit" name="mark_delivered" class="btn btn-success" style="padding:6px 14px;font-size:0.82rem">Mark Delivered</button>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--muted);font-size:0.85rem">— Done</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>
</div>
</body>
</html>
