<?php
require_once 'includes/db.php';
$activePage = 'bundles';
$bundles = $conn->query("SELECT * FROM bundles ORDER BY price ASC");
$bundles_data = [];
while ($b = $bundles->fetch_assoc()) $bundles_data[] = $b;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bundles — Y2J Funeral Service</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .bundle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 28px;
            padding: 60px 32px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .bundle-card {
            background: var(--grey-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
        }
        .bundle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px rgba(0,0,0,0.13);
        }
        .bundle-card.popular::after {
            content: 'MOST POPULAR';
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--gold);
            color: var(--white);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            padding: 5px 44px;
            transform: rotate(45deg);
            z-index: 2;
        }
        .bundle-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #e0e0e0;
            display: block;
        }
        .bundle-body {
            padding: 28px 28px 32px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .bundle-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.7rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .bundle-divider {
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
            margin: 10px 0 14px;
            width: 44px;
        }
        .bundle-desc {
            font-size: 0.88rem;
            color: var(--muted);
            margin-bottom: 18px;
            line-height: 1.5;
        }
        .bundle-price {
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 18px;
        }
        .bundle-price span {
            font-size: 0.95rem;
            font-weight: 400;
            color: var(--muted);
        }
        .bundle-inclusions {
            list-style: none;
            margin-bottom: 28px;
            flex: 1;
        }
        .bundle-inclusions li {
            padding: 8px 0;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bundle-inclusions li::before {
            content: '✓';
            color: var(--gold);
            font-weight: 700;
            flex-shrink: 0;
        }
        .bundle-inclusions li:last-child { border-bottom: none; }
    </style>
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="bundle-grid">
    <?php foreach ($bundles_data as $i => $b):
        $inclusions = explode('|', $b['inclusions']);
        $isPopular = ($i === 1);
    ?>
    <div class="bundle-card <?= $isPopular ? 'popular' : '' ?>">
        <img class="bundle-img"
             src="<?= htmlspecialchars($b['image_url'] ?? 'images/bundle_placeholder.jpg') ?>"
             alt="<?= htmlspecialchars($b['name']) ?>"
             onerror="this.src='images/bundle_placeholder.jpg'">
        <div class="bundle-body">
            <div class="bundle-name"><?= htmlspecialchars($b['name']) ?></div>
            <div class="bundle-divider"></div>
            <div class="bundle-desc"><?= htmlspecialchars($b['description']) ?></div>
            <div class="bundle-price">
                ₱<?= number_format($b['price'], 0) ?>
                <span>/ package</span>
            </div>
            <ul class="bundle-inclusions">
                <?php foreach ($inclusions as $item): ?>
                <li><?= htmlspecialchars(trim($item)) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="reserve_bundle.php?bundle_id=<?= $b['id'] ?>" class="btn btn-gold" style="width:100%;display:block;text-align:center">
                RESERVE THIS PACKAGE
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
