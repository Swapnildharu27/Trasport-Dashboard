<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM transport_records WHERE id = :id');
    $stmt->execute([':id' => $_GET['id']]);
}

header('Location: index.php?msg=deleted');
exit;
