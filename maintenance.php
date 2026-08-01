<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

// ---------- Handle search / filter ----------
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

$filterError = '';
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $filterError = '"Date From" cannot be later than "Date To". Please adjust the range.';
}
if ($dateFrom !== '' && $filterError === '') {
    $where[] = 'service_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && $filterError === '') {
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

// ---------- Stats ----------
$totalJobs       = $pdo->query('SELECT COUNT(*) FROM maintenance_records')->fetchColumn();
$pendingCount    = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status='Pending'")->fetchColumn();
$inProgressCount = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status='In Progress'")->fetchColumn();
$totalCostSum    = $pdo->query('SELECT COALESCE(SUM(total_cost),0) FROM maintenance_records')->fetchColumn();

$workTypes = [
    'Servicing (Full)', 'Servicing (Half)', 'Welding', 'Main Tank Work', 'Tyre Work',
    'Balancing Work', 'Battery Work', 'Oil Change', 'Clutch / Brake Work', 'Electrical Work',
    'Denting / Painting', 'Puncture Repair', 'Engine Work', 'Suspension Work', 'Other',
];

function maintBadgeClass(string $status): string {
    return match ($status) {
        'Pending'     => 'badge-scheduled',
        'In Progress' => 'badge-transit',
        'Completed'   => 'badge-completed',
        default       => 'badge-scheduled',
    };
}

function numOrDashMaint($val, int $decimals = 2): string {
    return $val !== null && $val !== '' ? number_format((float)$val, $decimals) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Maintenance</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php $activeSection = 'maintenance'; require __DIR__ . '/partials/nav.php'; ?>

<div class="container">

    <div class="tabs">
        <a href="maintenance.php" class="active">Dashboard</a>
        <a href="maintenance_form.php">+ Add Record</a>
        <a href="maintenance_export.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">Export to Excel</a>
        <a href="maintenance_pdf.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" target="_blank">Download PDF</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= $_GET['msg'] === 'added' ? 'Maintenance record added successfully.' :
                ($_GET['msg'] === 'updated' ? 'Maintenance record updated successfully.' :
                ($_GET['msg'] === 'deleted' ? 'Maintenance record deleted successfully.' : '')) ?>
        </div>
    <?php endif; ?>

    <?php if ($filterError !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($filterError) ?></div>
    <?php endif; ?>

    <?php $showStats = false; // stats panel hidden for now — set to true to bring it back ?>
    <?php if ($showStats): ?>
    <div class="stats-grid">
        <div class="stat-box"><div class="num"><?= (int)$totalJobs ?></div><div class="label">Total Jobs</div></div>
        <div class="stat-box"><div class="num"><?= (int)$pendingCount ?></div><div class="label">Pending</div></div>
        <div class="stat-box"><div class="num"><?= (int)$inProgressCount ?></div><div class="label">In Progress</div></div>
        <div class="stat-box"><div class="num">₹<?= number_format((float)$totalCostSum, 2) ?></div><div class="label">Total Cost</div></div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" action="maintenance.php">
                <div class="filter-group">
                    <label for="f_search">Search</label>
                    <input type="text" id="f_search" name="search" placeholder="Vehicle, driver, garage, bill no..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label for="f_work_type">Work Type</label>
                    <select id="f_work_type" name="work_type">
                        <option value="">All Types</option>
                        <?php foreach ($workTypes as $wt): ?>
                            <option value="<?= htmlspecialchars($wt) ?>" <?= $workType === $wt ? 'selected' : '' ?>><?= htmlspecialchars($wt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="f_status">Status</label>
                    <select id="f_status" name="status">
                        <option value="">All Status</option>
                        <?php foreach (['Pending','In Progress','Completed'] as $s): ?>
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
                        <a href="maintenance.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
            <a href="maintenance_form.php" class="btn btn-accent">+ Add Record</a>
        </div>

        <div class="table-wrap">
        <table class="sortable-table">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Service Date</th>
                    <th>Vehicle No.</th>
                    <th>Odometer (KM)</th>
                    <th>Work Type</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Parts Replaced</th>
                    <th>Garage</th>
                    <th>Location</th>
                    <th>Bill No.</th>
                    <th>Parts Cost</th>
                    <th>Labour Cost</th>
                    <th>Total Cost (₹)</th>
                    <th>Payment Mode</th>
                    <th>Next Due Date</th>
                    <th data-no-sort>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="17" style="text-align:center; color:#888;">No maintenance records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><?= htmlspecialchars($r['service_date']) ?></td>
                    <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
                    <td><?= numOrDashMaint($r['odometer_km']) ?></td>
                    <td><?= htmlspecialchars($r['work_type'] === 'Other' && $r['work_type_other'] ? 'Other: ' . $r['work_type_other'] : $r['work_type']) ?></td>
                    <td><?= htmlspecialchars($r['description'] ?? '—') ?></td>
                    <td><span class="badge <?= maintBadgeClass($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td><?= htmlspecialchars($r['parts_replaced'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['garage_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['location'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['bill_number'] ?? '—') ?></td>
                    <td><?= numOrDashMaint($r['parts_cost']) ?></td>
                    <td><?= numOrDashMaint($r['labour_cost']) ?></td>
                    <td><?= numOrDashMaint($r['total_cost']) ?></td>
                    <td><?= htmlspecialchars($r['payment_mode'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['next_service_due_date'] ?? '—') ?></td>
                    <td class="actions">
                        <a class="edit" href="maintenance_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <a class="delete" href="maintenance_delete.php?id=<?= (int)$r['id'] ?>"
                           onclick="return confirm('Delete this maintenance record?');">Delete</a>
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
