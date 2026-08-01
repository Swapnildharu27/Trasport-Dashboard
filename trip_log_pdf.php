<?php
/**
 * Print-friendly view of trip_logs, used for "Download PDF".
 * Honors the same ?search=, ?date_from=, ?date_to= filters as trip_logs.php.
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

$filterParts = [];
if ($search !== '')   $filterParts[] = 'Search: "' . htmlspecialchars($search) . '"';
if ($dateFrom !== '') $filterParts[] = 'From: ' . htmlspecialchars($dateFrom);
if ($dateTo !== '')   $filterParts[] = 'To: ' . htmlspecialchars($dateTo);

function numOrDashPdf($val, int $decimals = 2): string {
    return $val !== null ? number_format((float)$val, $decimals) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Trip Log - PDF</title>
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
    <a href="trip_logs.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">&larr; Back to Dashboard</a>
</div>

<h1>Trip Log</h1>
<div class="meta">Generated <?= date('d M Y, H:i') ?> &middot; <?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?></div>
<?php if ($filterParts): ?>
    <div class="filters">Filters applied: <?= implode(' &middot; ', $filterParts) ?></div>
<?php endif; ?>

<table>
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
            <th>Advance</th>
            <th>Driver Name</th>
            <th>Before Diesel</th>
            <th>After Diesel</th>
            <th>Before KM</th>
            <th>After KM</th>
            <th>Total KM</th>
            <th>DEF</th>
            <th>KL</th>
            <th>Fast Tag Exp.</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="19" style="text-align:center;">No trip log records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($records as $r): ?>
        <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['trip_date']) ?></td>
            <td><?= htmlspecialchars($r['return_date'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['lr_number']) ?></td>
            <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
            <td><?= htmlspecialchars($r['location']) ?></td>
            <td><?= numOrDashPdf($r['sakharwadi_diesel']) ?></td>
            <td><?= numOrDashPdf($r['rate']) ?></td>
            <td><?= numOrDashPdf($r['amount']) ?></td>
            <td><?= numOrDashPdf($r['advance']) ?></td>
            <td><?= htmlspecialchars($r['driver_name']) ?></td>
            <td><?= numOrDashPdf($r['before_diesel']) ?></td>
            <td><?= numOrDashPdf($r['after_diesel']) ?></td>
            <td><?= numOrDashPdf($r['before_km']) ?></td>
            <td><?= numOrDashPdf($r['after_km']) ?></td>
            <td><?= numOrDashPdf($r['total_km']) ?></td>
            <td><?= numOrDashPdf($r['def_qty']) ?></td>
            <td><?= numOrDashPdf($r['kl_qty']) ?></td>
            <td><?= numOrDashPdf($r['fasttag_exp']) ?></td>
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
