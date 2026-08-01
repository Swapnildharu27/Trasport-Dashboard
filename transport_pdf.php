<?php
/**
 * Print-friendly view of transport_records, used for "Download PDF".
 * Honors the same ?search=, ?status=, ?date_from=, ?date_to= filters as
 * the dashboard. No PDF library needed: this renders a clean printable
 * page and opens the browser's print dialog, where "Save as PDF" produces
 * the download.
 */
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

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

$filterParts = [];
if ($search !== '')   $filterParts[] = 'Search: "' . htmlspecialchars($search) . '"';
if ($status !== '')   $filterParts[] = 'Status: ' . htmlspecialchars($status);
if ($dateFrom !== '') $filterParts[] = 'From: ' . htmlspecialchars($dateFrom);
if ($dateTo !== '')   $filterParts[] = 'To: ' . htmlspecialchars($dateTo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transport Records - PDF</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 24px; }
    h1 { font-size: 18px; margin: 0 0 4px; }
    .meta { font-size: 11px; color: #555; margin-bottom: 4px; }
    .filters { font-size: 11px; color: #555; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th, td { border: 1px solid #999; padding: 5px 6px; text-align: left; }
    th { background: #eef3f8; }
    .toolbar { margin-bottom: 16px; }
    .toolbar button {
        padding: 8px 16px; font-size: 14px; border: none; border-radius: 5px;
        background: #1e6091; color: #fff; cursor: pointer;
    }
    .toolbar a { margin-left: 10px; font-size: 13px; color: #1e6091; text-decoration: none; }
    @media print {
        .toolbar { display: none; }
        body { margin: 0.4in; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Print / Save as PDF</button>
    <a href="index.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">&larr; Back to Dashboard</a>
</div>

<h1>Transport Records</h1>
<div class="meta">Generated <?= date('d M Y, H:i') ?> &middot; <?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?></div>
<?php if ($filterParts): ?>
    <div class="filters">Filters applied: <?= implode(' &middot; ', $filterParts) ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Vehicle No.</th>
            <th>Driver</th>
            <th>Contact</th>
            <th>Source</th>
            <th>Destination</th>
            <th>Dep. Date</th>
            <th>Dep. Time</th>
            <th>Arr. Time</th>
            <th>Status</th>
            <th>Supplier</th>
            <th>LR No.</th>
            <th>Invoice No.</th>
            <th>GST No.</th>
            <th>Qty</th>
            <th>Rate (₹)</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="17" style="text-align:center;">No records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($records as $r): ?>
        <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
            <td><?= htmlspecialchars($r['driver_name']) ?></td>
            <td><?= htmlspecialchars($r['driver_contact']) ?></td>
            <td><?= htmlspecialchars($r['source']) ?></td>
            <td><?= htmlspecialchars($r['destination']) ?></td>
            <td><?= htmlspecialchars($r['departure_date']) ?></td>
            <td><?= htmlspecialchars(substr($r['departure_time'], 0, 5)) ?></td>
            <td><?= $r['arrival_time'] ? htmlspecialchars(substr($r['arrival_time'], 0, 5)) : '—' ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['supplier'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['lr_number'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['invoice_number'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['gst_number'] ?? '—') ?></td>
            <td><?= $r['quantity'] !== null ? htmlspecialchars((string)$r['quantity']) : '—' ?></td>
            <td><?= $r['rate'] !== null ? number_format((float)$r['rate'], 2) : '—' ?></td>
            <td><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
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
