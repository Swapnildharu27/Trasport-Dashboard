<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

// ---------- Handle search / filter ----------
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

$filterError = '';
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $filterError = '"Date From" cannot be later than "Date To". Please adjust the range.';
}

if ($dateFrom !== '' && $filterError === '') {
    $where[] = 'departure_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && $filterError === '') {
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

// ---------- Stats ----------
$totalCount     = $pdo->query('SELECT COUNT(*) FROM transport_records')->fetchColumn();
$scheduledCount = $pdo->query("SELECT COUNT(*) FROM transport_records WHERE status='Scheduled'")->fetchColumn();
$transitCount   = $pdo->query("SELECT COUNT(*) FROM transport_records WHERE status='In Transit'")->fetchColumn();
$completedCount = $pdo->query("SELECT COUNT(*) FROM transport_records WHERE status='Completed'")->fetchColumn();
$cancelledCount = $pdo->query("SELECT COUNT(*) FROM transport_records WHERE status='Cancelled'")->fetchColumn();
$totalRevenue   = $pdo->query('SELECT COALESCE(SUM(rate),0) FROM transport_records')->fetchColumn();

function statusTabUrl(string $statusValue): string {
    $params = $_GET;
    if ($statusValue === '') {
        unset($params['status']);
    } else {
        $params['status'] = $statusValue;
    }
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

function badgeClass(string $status): string {
    return match ($status) {
        'Scheduled'  => 'badge-scheduled',
        'In Transit' => 'badge-transit',
        'Completed'  => 'badge-completed',
        'Cancelled'  => 'badge-cancelled',
        default      => 'badge-scheduled',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php $activeSection = 'transport'; require __DIR__ . '/partials/nav.php'; ?>

<div class="container">

    <div class="tabs">
        <a href="index.php" class="active">Dashboard</a>
        <a href="add_edit.php">+ Add Record</a>
        <a href="export_excel.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">Export to Excel</a>
        <a href="transport_pdf.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" target="_blank">Download PDF</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= $_GET['msg'] === 'added' ? 'Record added successfully.' :
                ($_GET['msg'] === 'updated' ? 'Record updated successfully.' :
                ($_GET['msg'] === 'deleted' ? 'Record deleted successfully.' : '')) ?>
        </div>
    <?php endif; ?>

    <?php if ($filterError !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($filterError) ?></div>
    <?php endif; ?>

    <?php $showStats = false; // stats panel hidden for now — set to true to bring it back ?>
    <?php if ($showStats): ?>
    <div class="stats-grid">
        <div class="stat-box"><div class="num"><?= (int)$totalCount ?></div><div class="label">Total Trips</div></div>
        <div class="stat-box"><div class="num"><?= (int)$scheduledCount ?></div><div class="label">Scheduled</div></div>
        <div class="stat-box"><div class="num"><?= (int)$transitCount ?></div><div class="label">In Transit</div></div>
        <div class="stat-box"><div class="num"><?= (int)$completedCount ?></div><div class="label">Completed</div></div>
        <div class="stat-box"><div class="num">₹<?= number_format((float)$totalRevenue, 2) ?></div><div class="label">Total Rate Value</div></div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" action="index.php">
                <div class="filter-group">
                    <label for="f_search">Search</label>
                    <input type="text" id="f_search" name="search" placeholder="Vehicle, driver, route, LR/invoice/GST..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label for="f_status">Status</label>
                    <select id="f_status" name="status">
                        <option value="">All Status</option>
                        <?php foreach (['Scheduled','In Transit','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="f_date_from">Date From</label>
                    <input type="date" id="f_date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label for="f_date_to">Date To</label>
                    <input type="date" id="f_date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="filter-group filter-actions">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn">Filter</button>
                        <a href="index.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
            <a href="add_edit.php" class="btn btn-accent">+ Add Record</a>
        </div>

        <div class="table-wrap">
        <table class="sortable-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle No.</th>
                    <th>Driver</th>
                    <th>Contact</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Departure Date</th>
                    <th>Departure Time</th>
                    <th>Arrival Time</th>
                    <th>Status</th>
                    <th>Supplier</th>
                    <th>LR No.</th>
                    <th>Invoice No.</th>
                    <th>GST No.</th>
                    <th>Qty</th>
                    <th>Rate (₹)</th>
                    <th>Remarks</th>
                    <th data-no-sort>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="18" style="text-align:center; color:#888;">No records found.</td></tr>
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
                    <td><span class="badge <?= badgeClass($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td><?= htmlspecialchars($r['supplier'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['lr_number'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['invoice_number'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['gst_number'] ?? '—') ?></td>
                    <td><?= $r['quantity'] !== null ? htmlspecialchars((string)$r['quantity']) : '—' ?></td>
                    <td><?= $r['rate'] !== null ? number_format((float)$r['rate'], 2) : '—' ?></td>
                    <td><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
                    <td class="actions">
                        <a class="edit" href="add_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <a class="delete" href="delete.php?id=<?= (int)$r['id'] ?>"
                           onclick="return confirm('Delete this record?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>
<script src="assets/sortable-table.js"></script>
</body>
</html>
