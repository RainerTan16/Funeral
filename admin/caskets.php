<?php
require_once 'auth.php';
$page = 'caskets';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_casket'])) {
    $name     = $conn->real_escape_string(trim($_POST['name']));
    $material = $conn->real_escape_string(trim($_POST['material']));
    $price    = (float)$_POST['price'];
    $desc     = $conn->real_escape_string(trim($_POST['description']));
    $stock    = (int)$_POST['stock'];
    $img      = $conn->real_escape_string(trim($_POST['image_url']));
    $conn->query("INSERT INTO caskets (name, material, price, description, image_url, stock) VALUES ('$name','$material','$price','$desc','$img','$stock')");
    header('Location: caskets.php?added=1');
    exit;
}

// Handle archive (replaces delete)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $c  = $conn->query("SELECT * FROM caskets WHERE id = $id")->fetch_assoc();
    if ($c) {
        $conn->query("INSERT INTO archived_caskets (original_id, name, material, price, description, image_url, stock, original_created_at)
            VALUES ('{$c['id']}','" . $conn->real_escape_string($c['name']) . "','" . $conn->real_escape_string($c['material']) . "',
                    '{$c['price']}','" . $conn->real_escape_string($c['description']) . "','" . $conn->real_escape_string($c['image_url']) . "',
                    '{$c['stock']}','" . $conn->real_escape_string($c['created_at']) . "')");
        $conn->query("DELETE FROM caskets WHERE id = $id");
    }
    header('Location: caskets.php?deleted=1');
    exit;
}

// Handle edit stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id    = (int)$_POST['casket_id'];
    $stock = (int)$_POST['stock'];
    $conn->query("UPDATE caskets SET stock=$stock WHERE id=$id");
    header('Location: caskets.php?updated=1');
    exit;
}

$caskets = $conn->query("SELECT * FROM caskets ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Caskets — Y2J Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-header">
        <h1>Manage Caskets</h1>
    </div>

    <?php if (isset($_GET['added']) || isset($_GET['updated']) || isset($_GET['deleted'])): ?>
    <div style="background:#d1e7dd;border-radius:8px;padding:12px 20px;margin-bottom:20px;color:#155724;font-size:0.9rem">✓ Changes saved.</div>
    <?php endif; ?>

    <!-- Add Casket -->
    <div class="table-card" style="margin-bottom:32px">
        <h2>Add New Casket</h2>
        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:16px 32px">
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Name</label>
                <input type="text" name="name" required style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Material</label>
                <input type="text" name="material" required style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Price (Php)</label>
                <input type="number" name="price" min="0" step="0.01" required style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Stock</label>
                <input type="number" name="stock" min="0" value="10" required style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Image URL</label>
                <input type="text" name="image_url" style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif" placeholder="images/casket.png">
            </div>
            <div>
                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--muted);margin-bottom:6px">Description</label>
                <input type="text" name="description" style="width:100%;padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
            </div>
            <div style="grid-column:1/-1">
                <button type="submit" name="add_casket" class="btn btn-gold">Add Casket</button>
            </div>
        </form>
    </div>

    <!-- Casket List -->
    <div class="table-card">
        <h2>Current Caskets</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Material</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Update Stock</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($c = $caskets->fetch_assoc()): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['material']) ?></td>
                <td>₱<?= number_format($c['price'], 0) ?></td>
                <td><?= $c['stock'] ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center">
                        <input type="hidden" name="casket_id" value="<?= $c['id'] ?>">
                        <input type="number" name="stock" value="<?= $c['stock'] ?>" min="0" style="width:70px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;font-family:Jost,sans-serif">
                        <button type="submit" name="update_stock" class="btn btn-gold" style="padding:6px 14px;font-size:0.82rem">Save</button>
                    </form>
                </td>
                <td>
                    <a href="caskets.php?delete=<?= $c['id'] ?>" class="btn btn-danger" style="padding:6px 14px;font-size:0.82rem" onclick="return confirm('Delete this casket?')">Delete</a>
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
