<?php
/**
 * Exports trip_logs to a downloadable Excel (.xls) file.
 * Honors the same ?search=, ?date_from=, ?date_to= filters used on trip_logs.php.
 */
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$search   = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(lr_number LIKE :search OR vehicle_no LIKE :search OR location LIKE :search OR driver_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$filterError = ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo);

if ($dateFrom !== '' && !$filterError) {
    $where[] = 'trip_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && !$filterError) {
    $where[] = 'trip_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

$sql = 'SELECT * FROM trip_logs';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY trip_date DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$filename = 'trip_log_' . date('Y-m-d_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel renders non-ASCII text correctly
?>
<table border="1">
    <thead>
        <tr>
            <th>Id</th>
            <th>Date</th>
            <th>Return Date</th>
            <th>LR Number</th>
            <th>Vehicle No.</th>
            <th>Location</th>
            <th>Sakharwadi Diesel</th>
            <th>Rate</th>
            <th>Amount</th>
            <th>Advance (Rs)</th>
            <th>Driver Name</th>
            <th>Before Diesel (Ltr)</th>
            <th>After Diesel (Ltr)</th>
            <th>Before KM</th>
            <th>After KM</th>
            <th>Total KM</th>
            <th>DEF</th>
            <th>KL</th>
            <th>Fast Tag Exp.</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['trip_date']) ?></td>
            <td><?= htmlspecialchars($r['return_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['lr_number']) ?></td>
            <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
            <td><?= htmlspecialchars($r['location']) ?></td>
            <td><?= htmlspecialchars((string)$r['sakharwadi_diesel']) ?></td>
            <td><?= htmlspecialchars((string)$r['rate']) ?></td>
            <td><?= htmlspecialchars((string)$r['amount']) ?></td>
            <td><?= htmlspecialchars((string)($r['advance'] ?? '')) ?></td>
            <td><?= htmlspecialchars($r['driver_name']) ?></td>
            <td><?= htmlspecialchars((string)($r['before_diesel'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['after_diesel'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['before_km'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['after_km'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['total_km'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)$r['def_qty']) ?></td>
            <td><?= htmlspecialchars((string)$r['kl_qty']) ?></td>
            <td><?= htmlspecialchars((string)($r['fasttag_exp'] ?? '')) ?></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
