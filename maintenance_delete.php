<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM maintenance_records WHERE id = :id');
    $stmt->execute([':id' => $_GET['id']]);
}

header('Location: maintenance.php?msg=deleted');
exit;
