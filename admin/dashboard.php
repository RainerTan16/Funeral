<?php
require_once 'auth.php';
$page = 'dashboard';

// Stats — casket reservations
$totalRes   = $conn->query("SELECT COUNT(*) as c FROM reservations")->fetch_assoc()['c'];
$pending    = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE status='Pending'")->fetch_assoc()['c'];
$approved   = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE status='Approved'")->fetch_assoc()['c']
            + $conn->query("SELECT COUNT(*) as c FROM bundle_reservations WHERE status='Approved'")->fetch_assoc()['c'];
$delivered  = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE status='Delivered'")->fetch_assoc()['c']
            + $conn->query("SELECT COUNT(*) as c FROM bundle_reservations WHERE status='Delivered'")->fetch_assoc()['c'];
$salesCasket= $conn->query("SELECT SUM(total_amount) as s FROM reservations WHERE status IN ('Approved','Delivered')")->fetch_assoc()['s'] ?? 0;

// Bundle reservations
$totalBundle  = $conn->query("SELECT COUNT(*) as c FROM bundle_reservations")->fetch_assoc()['c'];
$pendBundle   = $conn->query("SELECT COUNT(*) as c FROM bundle_reservations WHERE status='Pending'")->fetch_assoc()['c'];
$salesBundle  = $conn->query("SELECT SUM(total_amount) as s FROM bundle_reservations WHERE status IN ('Approved','Delivered')")->fetch_assoc()['s'] ?? 0;

$totalSales = $salesCasket + $salesBundle;

// Recent 5 — combined casket + bundle
$recent = $conn->query("
    SELECT id, client_name, 'casket' as type, 
        (SELECT name FROM caskets WHERE id = r.casket_id) as item_name,
        reservation_date, total_amount, status, created_at
    FROM reservations r
    UNION ALL
    SELECT id, client_name, 'bundle' as type,
        (SELECT name FROM bundles WHERE id = br.bundle_id) as item_name,
        reservation_date, total_amount, status, created_at
    FROM bundle_reservations br
    ORDER BY created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Dashboard</h1>
        <span style="font-size:0.9rem;color:var(--muted)">Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Casket Reservations</div>
            <div class="stat-value"><?= $totalRes ?></div>
        </div>
        <div class="stat-card" style="border-color:#084298">
            <div class="stat-label">Bundle Reservations</div>
            <div class="stat-value"><?= $totalBundle ?></div>
        </div>
        <div class="stat-card" style="border-color:#856404">
            <div class="stat-label">Pending (All)</div>
            <div class="stat-value"><?= $pending + $pendBundle ?></div>
        </div>
        <div class="stat-card" style="border-color:var(--success)">
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?= $approved ?></div>
        </div>
        <div class="stat-card" style="border-color:#198754">
            <div class="stat-label">Delivered</div>
            <div class="stat-value"><?= $delivered ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">₱<?= number_format($totalSales, 0) ?></div>
        </div>
    </div>

    <div class="table-card">
        <h2>Recent Reservations</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Casket</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $recent->fetch_assoc()): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['client_name']) ?></td>
                <td>
                    <?php if ($r['type'] === 'bundle'): ?>
                    <span style="background:var(--grey-bg);padding:2px 10px;border-radius:50px;font-size:0.82rem">📦 <?= htmlspecialchars($r['item_name']) ?></span>
                    <?php else: ?>
                    <?= htmlspecialchars($r['item_name']) ?>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                <td>₱<?= number_format($r['total_amount'], 0) ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td>
                    <?php $link = $r['type'] === 'bundle' ? 'bundle_reservations.php' : 'reservations.php'; ?>
                    <a href="<?= $link ?>?id=<?= $r['id'] ?>" class="btn btn-outline" style="padding:6px 16px;font-size:0.8rem">View</a>
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
