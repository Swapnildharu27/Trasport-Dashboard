<?php
/**
 * Include this at the very top of every protected page (before any output,
 * including before db_config.php) to require a logged-in session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in'])) {
    $current = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?redirect=' . urlencode($current));
    exit;
}
