<?php
// admin/sidebar.php
$page = $page ?? '';
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">Y2J FUNERAL<br>SERVICE<br><small style="font-family:Jost,sans-serif;font-size:0.7rem;opacity:0.6;font-weight:400">Admin Panel</small></div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= $page==='dashboard'?'active':'' ?>">📊 Dashboard</a></li>
        <li><a href="reservations.php" class="<?= $page==='reservations'?'active':'' ?>">📋 Reservations</a></li>
        <li><a href="bundle_reservations.php" class="<?= $page==='bundle_reservations'?'active':'' ?>">📦 Bundle Reservations</a></li>
        <li><a href="deliveries.php" class="<?= $page==='deliveries'?'active':'' ?>">🚚 Deliveries</a></li>
        <li><a href="sales.php" class="<?= $page==='sales'?'active':'' ?>">💰 Sales Report</a></li>
        <li><a href="caskets.php" class="<?= $page==='caskets'?'active':'' ?>">🪦 Manage Caskets</a></li>
        <li><a href="bundles_manage.php" class="<?= $page==='bundles_manage'?'active':'' ?>">📦 Manage Bundles</a></li>
        <li><a href="archive.php" class="<?= ($page ?? '') === 'archive' ? 'active' : '' ?>">🗄 Archive</a></li>
        <li><a href="logout.php" style="margin-top:auto">🚪 Logout</a></li>
    </ul>
</aside>
