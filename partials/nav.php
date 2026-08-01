<?php
/**
 * Shared navbar. Include after setting $activeSection to 'transport', 'triplog', or 'maintenance'.
 */
$activeSection = $activeSection ?? '';
?>
<div class="navbar">
    <h1>🚚 Transport Dashboard</h1>
    <div class="nav-links">
        <a href="index.php" class="<?= $activeSection === 'transport' ? 'active' : '' ?>">Transport Records</a>
        <a href="trip_logs.php" class="<?= $activeSection === 'triplog' ? 'active' : '' ?>">Trip Log</a>
        <a href="maintenance.php" class="<?= $activeSection === 'maintenance' ? 'active' : '' ?>">Maintenance</a>
        <span class="nav-separator">|</span>
        <?php if (!empty($_SESSION['logged_in'])): ?>
            <span class="nav-user"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <a href="logout.php" class="nav-logout">Logout</a>
        <?php endif; ?>
    </div>
</div>
