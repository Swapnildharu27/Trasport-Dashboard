<?php
/**
 * Exports transport_records to a downloadable Excel (.xls) file.
 * Honors the same ?search= and ?status= filters used on the dashboard,
 * so "Export to Excel" downloads exactly what's currently being viewed.
 *
 * No external libraries required: Excel opens HTML tables served with
 * an .xls filename and the ms-excel content type without issue.
 */
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$search    = trim($_GET['search'] ?? '');
$status    = trim($_GET['status'] ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(vehicle_no LIKE :search OR driver_name LIKE :search OR source LIKE :search OR destination LIKE :search
                 OR supplier LIKE :search OR lr_number LIKE :search OR invoice_number LIKE :search OR gst_number LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
$filterError = ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo);

if ($dateFrom !== '' && !$filterError) {
    $where[] = 'departure_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && !$filterError) {
    $where[] = 'departure_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

$sql = 'SELECT * FROM transport_records';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY departure_date DESC, departure_time DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$filename = 'transport_records_' . date('Y-m-d_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel renders non-ASCII text correctly
?>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Vehicle No.</th>
            <th>Driver Name</th>
            <th>Driver Contact</th>
            <th>Source</th>
            <th>Destination</th>
            <th>Departure Date</th>
            <th>Departure Time</th>
            <th>Arrival Time</th>
            <th>Status</th>
            <th>Supplier</th>
            <th>LR Number</th>
            <th>Invoice Number</th>
            <th>GST Number</th>
            <th>Quantity</th>
            <th>Rate</th>
            <th>Remarks</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
            <td><?= htmlspecialchars($r['driver_name']) ?></td>
            <td><?= htmlspecialchars($r['driver_contact']) ?></td>
            <td><?= htmlspecialchars($r['source']) ?></td>
            <td><?= htmlspecialchars($r['destination']) ?></td>
            <td><?= htmlspecialchars($r['departure_date']) ?></td>
            <td><?= htmlspecialchars(substr($r['departure_time'], 0, 5)) ?></td>
            <td><?= $r['arrival_time'] ? htmlspecialchars(substr($r['arrival_time'], 0, 5)) : '' ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['supplier'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['lr_number'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['invoice_number'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['gst_number'] ?? '') ?></td>
            <td><?= $r['quantity'] !== null ? htmlspecialchars((string)$r['quantity']) : '' ?></td>
            <td><?= $r['rate'] !== null ? htmlspecialchars((string)$r['rate']) : '' ?></td>
            <td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
