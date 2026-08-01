<?php
/**
 * Print-friendly view of maintenance_records, used for "Download PDF".
 * Honors the same ?search=, ?work_type=, ?status=, ?date_from=, ?date_to=
 * filters as maintenance.php.
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

$filterParts = [];
if ($search !== '')   $filterParts[] = 'Search: "' . htmlspecialchars($search) . '"';
if ($workType !== '') $filterParts[] = 'Work Type: ' . htmlspecialchars($workType);
if ($status !== '')   $filterParts[] = 'Status: ' . htmlspecialchars($status);
if ($dateFrom !== '') $filterParts[] = 'From: ' . htmlspecialchars($dateFrom);
if ($dateTo !== '')   $filterParts[] = 'To: ' . htmlspecialchars($dateTo);

function numOrDashMaintPdf($val, int $decimals = 2): string {
    return $val !== null && $val !== '' ? number_format((float)$val, $decimals) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maintenance Records - PDF</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 24px; }
    h1 { font-size: 18px; margin: 0 0 4px; }
    .meta { font-size: 11px; color: #555; margin-bottom: 4px; }
    .filters { font-size: 11px; color: #555; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; }
    th { background: #eef3f8; }
    .toolbar { margin-bottom: 16px; }
    .toolbar button {
        padding: 8px 16px; font-size: 14px; border: none; border-radius: 5px;
        background: #1e6091; color: #fff; cursor: pointer;
    }
    .toolbar a { margin-left: 10px; font-size: 13px; color: #1e6091; text-decoration: none; }
    @media print {
        .toolbar { display: none; }
        body { margin: 0.3in; }
        @page { size: landscape; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Print / Save as PDF</button>
    <a href="maintenance.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">&larr; Back to Dashboard</a>
</div>

<h1>Maintenance Records</h1>
<div class="meta">Generated <?= date('d M Y, H:i') ?> &middot; <?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?></div>
<?php if ($filterParts): ?>
    <div class="filters">Filters applied: <?= implode(' &middot; ', $filterParts) ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Id</th>
            <th>Service Date</th>
            <th>Vehicle No.</th>
            <th>Odometer (KM)</th>
            <th>Work Type</th>
            <th>Garage</th>
            <th>Location</th>
            <th>Bill No.</th>
            <th>Parts Cost</th>
            <th>Labour Cost</th>
            <th>Total Cost (₹)</th>
            <th>Payment Mode</th>
            <th>Status</th>
            <th>Next Due Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="14" style="text-align:center;">No maintenance records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($records as $r): ?>
        <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['service_date']) ?></td>
            <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
            <td><?= numOrDashMaintPdf($r['odometer_km']) ?></td>
            <td><?= htmlspecialchars($r['work_type'] === 'Other' && $r['work_type_other'] ? 'Other: ' . $r['work_type_other'] : $r['work_type']) ?></td>
            <td><?= htmlspecialchars($r['garage_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['location'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['bill_number'] ?? '—') ?></td>
            <td><?= numOrDashMaintPdf($r['parts_cost']) ?></td>
            <td><?= numOrDashMaintPdf($r['labour_cost']) ?></td>
            <td><?= numOrDashMaintPdf($r['total_cost']) ?></td>
            <td><?= htmlspecialchars($r['payment_mode'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['next_service_due_date'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
</body>
</html>
