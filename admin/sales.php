<?php
require_once 'auth.php';
$page = 'sales';

// Monthly sales summary — caskets
$monthly = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_orders,
        SUM(quantity) as total_qty,
        SUM(total_amount) as revenue
    FROM reservations
    WHERE status IN ('Approved','Delivered')
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");

// Monthly — bundles
$monthlyBundle = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_orders,
        SUM(total_amount) as revenue
    FROM bundle_reservations
    WHERE status IN ('Approved','Delivered')
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");

// Per casket
$byCasket = $conn->query("
    SELECT c.name, 
        COUNT(r.id) as orders,
        SUM(r.quantity) as qty,
        SUM(r.total_amount) as revenue
    FROM reservations r
    JOIN caskets c ON r.casket_id = c.id
    WHERE r.status IN ('Approved','Delivered')
    GROUP BY c.id
");

// Per bundle
$byBundle = $conn->query("
    SELECT b.name,
        COUNT(br.id) as orders,
        SUM(br.total_amount) as revenue
    FROM bundle_reservations br
    JOIN bundles b ON br.bundle_id = b.id
    WHERE br.status IN ('Approved','Delivered')
    GROUP BY b.id
");

$casketTotal  = $conn->query("SELECT SUM(total_amount) as t FROM reservations WHERE status IN ('Approved','Delivered')")->fetch_assoc()['t'] ?? 0;
$bundleTotal  = $conn->query("SELECT SUM(total_amount) as t FROM bundle_reservations WHERE status IN ('Approved','Delivered')")->fetch_assoc()['t'] ?? 0;
$grandTotal   = $casketTotal + $bundleTotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Sales Report</h1>
        <div style="font-size:0.9rem;color:var(--muted)">Approved & Delivered orders only</div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Grand Total Revenue</div>
            <div class="stat-value">₱<?= number_format($grandTotal, 0) ?></div>
        </div>
        <div class="stat-card" style="border-color:#084298">
            <div class="stat-label">Casket Sales</div>
            <div class="stat-value">₱<?= number_format($casketTotal, 0) ?></div>
        </div>
        <div class="stat-card" style="border-color:var(--gold)">
            <div class="stat-label">Bundle Sales</div>
            <div class="stat-value">₱<?= number_format($bundleTotal, 0) ?></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">

    <div class="table-card">
        <h2>Casket Sales — Monthly</h2>
        <table>
            <thead>
                <tr><th>Month</th><th>Orders</th><th>Units</th><th>Revenue</th></tr>
            </thead>
            <tbody>
            <?php while ($m = $monthly->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($m['month']) ?></td>
                <td><?= $m['total_orders'] ?></td>
                <td><?= $m['total_qty'] ?></td>
                <td>₱<?= number_format($m['revenue'], 0) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <h2>Bundle Sales — Monthly</h2>
        <table>
            <thead>
                <tr><th>Month</th><th>Orders</th><th>Revenue</th></tr>
            </thead>
            <tbody>
            <?php while ($m = $monthlyBundle->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($m['month']) ?></td>
                <td><?= $m['total_orders'] ?></td>
                <td>₱<?= number_format($m['revenue'], 0) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

    <div class="table-card">
        <h2>Sales by Casket</h2>
        <table>
            <thead>
                <tr><th>Casket</th><th>Orders</th><th>Qty</th><th>Revenue</th></tr>
            </thead>
            <tbody>
            <?php while ($b = $byCasket->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($b['name']) ?></td>
                <td><?= $b['orders'] ?></td>
                <td><?= $b['qty'] ?></td>
                <td>₱<?= number_format($b['revenue'], 0) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <h2>Sales by Bundle</h2>
        <table>
            <thead>
                <tr><th>Package</th><th>Orders</th><th>Revenue</th></tr>
            </thead>
            <tbody>
            <?php while ($b = $byBundle->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($b['name']) ?></td>
                <td><?= $b['orders'] ?></td>
                <td>₱<?= number_format($b['revenue'], 0) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    </div>
</main>
</div>
</body>
</html>
