<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM trip_logs WHERE id = :id');
    $stmt->execute([':id' => $_GET['id']]);
}

header('Location: trip_logs.php?msg=deleted');
exit;
