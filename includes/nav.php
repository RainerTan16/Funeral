<?php
// includes/nav.php
$activePage = $activePage ?? '';
?>
<nav>
    <a href="index.php" class="nav-brand">
        <img src="images/logo.png" alt="Y2J Logo" onerror="this.style.display='none'" style="height:52px;width:52px;object-fit:contain;border-radius:50%;background:#fff;padding:3px;flex-shrink:0;">
        <span>Y2J FUNERAL<br>SERVICE</span>
    </a>
    <ul class="nav-links">
        <li><a href="index.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">HOME</a></li>
        <li><a href="bundles.php" class="<?= $activePage === 'bundles' ? 'active' : '' ?>">BUNDLES</a></li>
        <li><a href="check_status.php" class="<?= $activePage === 'status' ? 'active' : '' ?>">CHECK STATUS</a></li>
        <li><a href="contact.php" class="<?= $activePage === 'contact' ? 'active' : '' ?>">CONTACT</a></li>
    </ul>
</nav>
