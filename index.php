<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';
$activePage = 'home';
$caskets = $conn->query("SELECT * FROM caskets ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Y2J Funeral Service</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="casket-grid">
    <?php while ($c = $caskets->fetch_assoc()): ?>
    <div class="casket-card">
        <img src="<?= htmlspecialchars($c['image_url']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" onerror="this.src='images/placeholder.png'">
        <h2><?= htmlspecialchars($c['name']) ?></h2>
        <p class="material"><?= htmlspecialchars($c['material']) ?></p>
        <p class="price"><?= number_format($c['price'], 0) ?> Php</p>
        <a href="reserve.php?casket_id=<?= $c['id'] ?>" class="btn btn-gold">Reserve</a>
    </div>
    <?php endwhile; ?>
</div>

</body>
</html>
