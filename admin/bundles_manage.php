<?php
require_once 'auth.php';
$page = 'bundles_manage';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bundle'])) {
    $name        = $conn->real_escape_string(trim($_POST['name']));
    $price       = (float)$_POST['price'];
    $desc        = $conn->real_escape_string(trim($_POST['description']));
    $img = $conn->real_escape_string(trim($_POST['image_url'] ?? 'images/bundle_placeholder.jpg'));
    $inclusions  = $conn->real_escape_string(trim($_POST['inclusions']));
    $conn->query("INSERT INTO bundles (name, price, description, inclusions, image_url) VALUES ('$name','$price','$desc','$inclusions','$img')");
    header('Location: bundles_manage.php?added=1');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $b  = $conn->query("SELECT * FROM bundles WHERE id = $id")->fetch_assoc();
    if ($b) {
        $conn->query("INSERT INTO archived_bundles (original_id, name, price, description, inclusions, image_url, original_created_at)
            VALUES ('{$b['id']}','" . $conn->real_escape_string($b['name']) . "','{$b['price']}',
                    '" . $conn->real_escape_string($b['description']) . "','" . $conn->real_escape_string($b['inclusions']) . "',
                    '" . $conn->real_escape_string($b['image_url']) . "','" . $conn->real_escape_string($b['created_at']) . "')");
        $conn->query("DELETE FROM bundles WHERE id = $id");
    }
    header('Location: bundles_manage.php?deleted=1');
    exit;
}

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bundle'])) {
    $id    = (int)$_POST['bundle_id'];
    $price = (float)$_POST['price'];
    $conn->query("UPDATE bundles SET price=$price WHERE id=$id");
    header('Location: bundles_manage.php?updated=1');
    exit;
}

$bundles = $conn->query("SELECT * FROM bundles ORDER BY price ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bundles — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Manage Bundles</h1>
    </div>

    <?php if (isset($_GET['added']) || isset($_GET['updated']) || isset($_GET['deleted'])): ?>
    <div style="background:#d1e7dd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#155724;font-size:0.9rem">✓ Changes saved.</div>
    <?php endif; ?>

    <!-- Add Bundle -->
    <div class="table-card" style="margin-bottom:32px">
        <h2>Add New Bundle</h2>
        <form method="POST" style="display:grid;gap:16px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Package Name</label>
                    <input type="text" name="name" required placeholder="e.g. Deluxe Package" style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Price (₱)</label>
                    <input type="number" name="price" min="0" step="0.01" required style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Description</label>
                <input type="text" name="description" style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif" placeholder="Short description of the package">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">
                    Inclusions <small style="font-weight:400;color:var(--muted)">(separate each item with a pipe | symbol)</small>
                </label>
                <input type="text" name="inclusions" style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif" placeholder="e.g. Casket|Embalming|3-day wake|Flower arrangement">
            </div>
            <div>
                <button type="submit" name="add_bundle" class="btn btn-gold">Add Bundle</button>
            </div>
        </form>
    </div>

    <!-- Bundle List -->
    <div class="table-card">
        <h2>Current Bundles</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Inclusions</th>
                    <th>Price</th>
                    <th>Update Price</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($b = $bundles->fetch_assoc()):
                $items = explode('|', $b['inclusions']);
            ?>
            <tr>
                <td><?= $b['id'] ?></td>
                <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                <td style="max-width:160px;font-size:0.85rem;color:var(--muted)"><?= htmlspecialchars($b['description']) ?></td>
                <td style="max-width:200px">
                    <?php foreach ($items as $item): ?>
                    <span style="display:inline-block;background:var(--grey-bg);border-radius:50px;padding:2px 10px;font-size:0.78rem;margin:2px">✓ <?= htmlspecialchars(trim($item)) ?></span>
                    <?php endforeach; ?>
                </td>
                <td>₱<?= number_format($b['price'], 0) ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center">
                        <input type="hidden" name="bundle_id" value="<?= $b['id'] ?>">
                        <input type="number" name="price" value="<?= $b['price'] ?>" min="0" step="0.01" style="width:100px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
                        <button type="submit" name="update_bundle" class="btn btn-gold" style="padding:6px 14px;font-size:0.82rem">Save</button>
                    </form>
                </td>
                <td>
                    <a href="bundles_manage.php?delete=<?= $b['id'] ?>" class="btn btn-danger" style="padding:6px 14px;font-size:0.82rem" onclick="return confirm('Delete this bundle?')">Delete</a>
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
