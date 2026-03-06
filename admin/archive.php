<?php
require_once 'auth.php';
$page = 'archive';

// Handle restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    $type = $_POST['type'] ?? '';
    $id   = (int)$_POST['archive_id'];

    if ($type === 'reservation') {
        $r = $conn->query("SELECT * FROM archived_reservations WHERE id = $id")->fetch_assoc();
        if ($r) {
            // Get casket id by name
            $casket = $conn->query("SELECT id FROM caskets WHERE name = '" . $conn->real_escape_string($r['casket_name']) . "' LIMIT 1")->fetch_assoc();
            $cid = $casket ? $casket['id'] : 1;
            $conn->query("INSERT INTO reservations
    (id, client_name, client_address, client_phone, reservation_date, deceased_name, deceased_age, deceased_dod,
     casket_id, casket_color, quantity, payment_type, total_amount, remarks, admin_notes, status, rejection_reason)
    VALUES ('{$r['original_id']}','" . $conn->real_escape_string($r['client_name']) . "','" . $conn->real_escape_string($r['client_address']) . "',
            '" . $conn->real_escape_string($r['client_phone']) . "','" . $conn->real_escape_string($r['reservation_date']) . "',
            '" . $conn->real_escape_string($r['deceased_name']) . "','{$r['deceased_age']}','" . $conn->real_escape_string($r['deceased_dod']) . "',
            '$cid','" . $conn->real_escape_string($r['casket_color']) . "','{$r['quantity']}','Cash','{$r['total_amount']}',
            '" . $conn->real_escape_string($r['remarks']) . "','" . $conn->real_escape_string($r['admin_notes']) . "',
            '" . $conn->real_escape_string($r['status']) . "','" . $conn->real_escape_string($r['rejection_reason']) . "')");
            $conn->query("DELETE FROM archived_reservations WHERE id = $id");
        }
    } elseif ($type === 'bundle_reservation') {
        $r = $conn->query("SELECT * FROM archived_bundle_reservations WHERE id = $id")->fetch_assoc();
        if ($r) {
            $bundle = $conn->query("SELECT id FROM bundles WHERE name = '" . $conn->real_escape_string($r['bundle_name']) . "' LIMIT 1")->fetch_assoc();
            $bid = $bundle ? $bundle['id'] : 1;
            $conn->query("INSERT INTO bundle_reservations
    (id, client_name, client_address, client_phone, reservation_date, deceased_name, deceased_age, deceased_dod,
     bundle_id, payment_type, total_amount, remarks, admin_notes, status, rejection_reason)
    VALUES ('{$r['original_id']}','" . $conn->real_escape_string($r['client_name']) . "','" . $conn->real_escape_string($r['client_address']) . "',
            '" . $conn->real_escape_string($r['client_phone']) . "','" . $conn->real_escape_string($r['reservation_date']) . "',
            '" . $conn->real_escape_string($r['deceased_name']) . "','{$r['deceased_age']}','" . $conn->real_escape_string($r['deceased_dod']) . "',
            '$bid','Cash','{$r['total_amount']}','" . $conn->real_escape_string($r['remarks']) . "',
            '" . $conn->real_escape_string($r['admin_notes']) . "','" . $conn->real_escape_string($r['status']) . "',
            '" . $conn->real_escape_string($r['rejection_reason']) . "')");
            $conn->query("DELETE FROM archived_bundle_reservations WHERE id = $id");
        }
    } elseif ($type === 'casket') {
        $c = $conn->query("SELECT * FROM archived_caskets WHERE id = $id")->fetch_assoc();
        if ($c) {
            $conn->query("INSERT INTO caskets (id, name, material, price, description, image_url, stock)
    VALUES ('{$c['original_id']}','" . $conn->real_escape_string($c['name']) . "','" . $conn->real_escape_string($c['material']) . "',
            '{$c['price']}','" . $conn->real_escape_string($c['description']) . "',
            '" . $conn->real_escape_string($c['image_url']) . "','{$c['stock']}')");
            $conn->query("DELETE FROM archived_caskets WHERE id = $id");
        }
    } elseif ($type === 'bundle') {
        $b = $conn->query("SELECT * FROM archived_bundles WHERE id = $id")->fetch_assoc();
        if ($b) {
            $conn->query("INSERT INTO bundles (id, name, price, description, inclusions, image_url)
    VALUES ('{$b['original_id']}','" . $conn->real_escape_string($b['name']) . "','{$b['price']}',
            '" . $conn->real_escape_string($b['description']) . "','" . $conn->real_escape_string($b['inclusions']) . "',
            '" . $conn->real_escape_string($b['image_url']) . "')");
            $conn->query("DELETE FROM archived_bundles WHERE id = $id");
        }
    }
    header('Location: archive.php?restored=1');
    exit;
}

// Handle permanent delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perm_delete'])) {
    $type = $_POST['type'] ?? '';
    $id   = (int)$_POST['archive_id'];
    $tableMap = [
        'reservation'        => 'archived_reservations',
        'bundle_reservation' => 'archived_bundle_reservations',
        'casket'             => 'archived_caskets',
        'bundle'             => 'archived_bundles',
    ];
    if (isset($tableMap[$type])) {
        $conn->query("DELETE FROM {$tableMap[$type]} WHERE id = $id");
    }
    header('Location: archive.php?deleted=1');
    exit;
}

// Fetch all archived items
$archivedRes     = $conn->query("SELECT *, 'reservation' as type FROM archived_reservations ORDER BY archived_at DESC");
$archivedBunRes  = $conn->query("SELECT *, 'bundle_reservation' as type FROM archived_bundle_reservations ORDER BY archived_at DESC");
$archivedCaskets = $conn->query("SELECT *, 'casket' as type FROM archived_caskets ORDER BY archived_at DESC");
$archivedBundles = $conn->query("SELECT *, 'bundle' as type FROM archived_bundles ORDER BY archived_at DESC");

$totalArchived = $archivedRes->num_rows + $archivedBunRes->num_rows + $archivedCaskets->num_rows + $archivedBundles->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .archive-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .archive-tab { padding: 8px 20px; border-radius: 20px; border: 1px solid #ddd; background: white; cursor: pointer; font-size: 0.85rem; font-family: Jost, sans-serif; }
        .archive-tab.active { background: var(--navy); color: white; border-color: var(--navy); }
        .archive-section { display: none; }
        .archive-section.active { display: block; }
        .empty-msg { text-align: center; color: var(--muted); padding: 40px 0; font-size: 0.9rem; }
        .action-btns { display: flex; gap: 6px; }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Archive <span style="font-size:0.9rem;color:var(--muted);font-weight:400">(<?= $totalArchived ?> items)</span></h1>
    </div>

    <?php if (isset($_GET['restored'])): ?>
    <div style="background:#d1e7dd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#155724;font-size:0.9rem">✓ Item restored successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div style="background:#f8d7da;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#721c24;font-size:0.9rem">✓ Item permanently deleted.</div>
    <?php endif; ?>

    <div class="archive-tabs">
        <button class="archive-tab active" onclick="showTab('reservations')">Reservations (<?= $archivedRes->num_rows ?>)</button>
        <button class="archive-tab" onclick="showTab('bundle-res')">Bundle Reservations (<?= $archivedBunRes->num_rows ?>)</button>
        <button class="archive-tab" onclick="showTab('caskets')">Caskets (<?= $archivedCaskets->num_rows ?>)</button>
        <button class="archive-tab" onclick="showTab('bundles')">Bundles (<?= $archivedBundles->num_rows ?>)</button>
    </div>

    <!-- Reservations -->
    <div class="archive-section active" id="tab-reservations">
        <?php if ($archivedRes->num_rows === 0): ?>
        <p class="empty-msg">No archived reservations.</p>
        <?php else: ?>
        <div class="table-card">
            <table>
                <thead><tr>
                    <th>Ref #</th><th>Client</th><th>Phone</th><th>Deceased</th>
                    <th>Casket</th><th>Total</th><th>Status</th><th>Archived On</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php while ($r = $archivedRes->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:700;color:var(--muted)">RES-<?= str_pad($r['original_id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($r['client_name']) ?></td>
                    <td><?= htmlspecialchars($r['client_phone']) ?></td>
                    <td><?= htmlspecialchars($r['deceased_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['casket_name']) ?> / <?= htmlspecialchars($r['casket_color']) ?></td>
                    <td>₱<?= number_format($r['total_amount'], 0) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                    <td style="font-size:0.82rem;color:var(--muted)"><?= date('M j, Y', strtotime($r['archived_at'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="type" value="reservation">
                                <input type="hidden" name="archive_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="restore" class="btn btn-gold" style="padding:5px 12px;font-size:0.78rem">↩ Restore</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')">
                                <input type="hidden" name="type" value="reservation">
                                <input type="hidden" name="archive_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="perm_delete" class="btn btn-danger" style="padding:5px 12px;font-size:0.78rem">🗑 Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bundle Reservations -->
    <div class="archive-section" id="tab-bundle-res">
        <?php if ($archivedBunRes->num_rows === 0): ?>
        <p class="empty-msg">No archived bundle reservations.</p>
        <?php else: ?>
        <div class="table-card">
            <table>
                <thead><tr>
                    <th>Ref #</th><th>Client</th><th>Phone</th><th>Deceased</th>
                    <th>Package</th><th>Total</th><th>Status</th><th>Archived On</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php while ($r = $archivedBunRes->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:700;color:var(--muted)">BUN-<?= str_pad($r['original_id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($r['client_name']) ?></td>
                    <td><?= htmlspecialchars($r['client_phone']) ?></td>
                    <td><?= htmlspecialchars($r['deceased_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['bundle_name']) ?></td>
                    <td>₱<?= number_format($r['total_amount'], 0) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                    <td style="font-size:0.82rem;color:var(--muted)"><?= date('M j, Y', strtotime($r['archived_at'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="type" value="bundle_reservation">
                                <input type="hidden" name="archive_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="restore" class="btn btn-gold" style="padding:5px 12px;font-size:0.78rem">↩ Restore</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')">
                                <input type="hidden" name="type" value="bundle_reservation">
                                <input type="hidden" name="archive_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="perm_delete" class="btn btn-danger" style="padding:5px 12px;font-size:0.78rem">🗑 Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Caskets -->
    <div class="archive-section" id="tab-caskets">
        <?php if ($archivedCaskets->num_rows === 0): ?>
        <p class="empty-msg">No archived caskets.</p>
        <?php else: ?>
        <div class="table-card">
            <table>
                <thead><tr><th>Name</th><th>Material</th><th>Price</th><th>Stock</th><th>Archived On</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($c = $archivedCaskets->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['material']) ?></td>
                    <td>₱<?= number_format($c['price'], 0) ?></td>
                    <td><?= $c['stock'] ?></td>
                    <td style="font-size:0.82rem;color:var(--muted)"><?= date('M j, Y', strtotime($c['archived_at'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="type" value="casket">
                                <input type="hidden" name="archive_id" value="<?= $c['id'] ?>">
                                <button type="submit" name="restore" class="btn btn-gold" style="padding:5px 12px;font-size:0.78rem">↩ Restore</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')">
                                <input type="hidden" name="type" value="casket">
                                <input type="hidden" name="archive_id" value="<?= $c['id'] ?>">
                                <button type="submit" name="perm_delete" class="btn btn-danger" style="padding:5px 12px;font-size:0.78rem">🗑 Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bundles -->
    <div class="archive-section" id="tab-bundles">
        <?php if ($archivedBundles->num_rows === 0): ?>
        <p class="empty-msg">No archived bundles.</p>
        <?php else: ?>
        <div class="table-card">
            <table>
                <thead><tr><th>Name</th><th>Price</th><th>Archived On</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($b = $archivedBundles->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($b['name']) ?></td>
                    <td>₱<?= number_format($b['price'], 0) ?></td>
                    <td style="font-size:0.82rem;color:var(--muted)"><?= date('M j, Y', strtotime($b['archived_at'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="type" value="bundle">
                                <input type="hidden" name="archive_id" value="<?= $b['id'] ?>">
                                <button type="submit" name="restore" class="btn btn-gold" style="padding:5px 12px;font-size:0.78rem">↩ Restore</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')">
                                <input type="hidden" name="type" value="bundle">
                                <input type="hidden" name="archive_id" value="<?= $b['id'] ?>">
                                <button type="submit" name="perm_delete" class="btn btn-danger" style="padding:5px 12px;font-size:0.78rem">🗑 Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</main>
</div>
<script>
function showTab(name) {
    document.querySelectorAll('.archive-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.archive-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
