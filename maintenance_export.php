<?php
/**
 * Exports maintenance_records to a downloadable Excel (.xls) file.
 * Honors the same ?search=, ?work_type=, ?status=, ?date_from=, ?date_to=
 * filters used on maintenance.php.
 */
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$search   = trim($_GET['search'] ?? '');
$workType = trim($_GET['work_type'] ?? '');
$status   = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(vehicle_no LIKE :search OR garage_name LIKE :search
                 OR location LIKE :search OR bill_number LIKE :search OR description LIKE :search
                 OR work_type_other LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($workType !== '') {
    $where[] = 'work_type = :work_type';
    $params[':work_type'] = $workType;
}
if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

$filterError = ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo);

if ($dateFrom !== '' && !$filterError) {
    $where[] = 'service_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && !$filterError) {
    $where[] = 'service_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

$sql = 'SELECT * FROM maintenance_records';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY service_date DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$filename = 'maintenance_records_' . date('Y-m-d_His') . '.xls';

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
            <th>Service Date</th>
            <th>Vehicle No.</th>
            <th>Odometer (KM)</th>
            <th>Work Type</th>
            <th>Other Work Description</th>
            <th>Description</th>
            <th>Parts Replaced</th>
            <th>Garage</th>
            <th>Location</th>
            <th>Bill Number</th>
            <th>Parts Cost</th>
            <th>Labour Cost</th>
            <th>Total Cost</th>
            <th>Payment Mode</th>
            <th>Status</th>
            <th>Next Service Due Date</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['service_date']) ?></td>
            <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
            <td><?= htmlspecialchars((string)($r['odometer_km'] ?? '')) ?></td>
            <td><?= htmlspecialchars($r['work_type']) ?></td>
            <td><?= htmlspecialchars($r['work_type_other'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['parts_replaced'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['garage_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['location'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['bill_number'] ?? '') ?></td>
            <td><?= htmlspecialchars((string)($r['parts_cost'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['labour_cost'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)$r['total_cost']) ?></td>
            <td><?= htmlspecialchars($r['payment_mode'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['next_service_due_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
