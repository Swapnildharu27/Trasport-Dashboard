<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

// ---------- Handle search / filter ----------
$search   = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(lr_number LIKE :search OR vehicle_no LIKE :search OR location LIKE :search OR driver_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$filterError = '';
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $filterError = '"Date From" cannot be later than "Date To". Please adjust the range.';
}
if ($dateFrom !== '' && $filterError === '') {
    $where[] = 'trip_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && $filterError === '') {
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

// ---------- Stats ----------
$totalTrips     = $pdo->query('SELECT COUNT(*) FROM trip_logs')->fetchColumn();
$totalAmount    = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM trip_logs')->fetchColumn();
$totalAdvance   = $pdo->query('SELECT COALESCE(SUM(advance),0) FROM trip_logs')->fetchColumn();
$totalKm        = $pdo->query('SELECT COALESCE(SUM(total_km),0) FROM trip_logs')->fetchColumn();
$totalFastag    = $pdo->query('SELECT COALESCE(SUM(fasttag_exp),0) FROM trip_logs')->fetchColumn();

function numOrDash($val, int $decimals = 2): string {
    return $val !== null ? number_format((float)$val, $decimals) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trip Log</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php $activeSection = 'triplog'; require __DIR__ . '/partials/nav.php'; ?>

<div class="container">

    <div class="tabs">
        <a href="trip_logs.php" class="active">Dashboard</a>
        <a href="trip_log_form.php">+ Add Trip</a>
        <a href="trip_log_export.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">Export to Excel</a>
        <a href="trip_log_pdf.php?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" target="_blank">Download PDF</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= $_GET['msg'] === 'added' ? 'Trip log added successfully.' :
                ($_GET['msg'] === 'updated' ? 'Trip log updated successfully.' :
                ($_GET['msg'] === 'deleted' ? 'Trip log deleted successfully.' : '')) ?>
        </div>
    <?php endif; ?>

    <?php if ($filterError !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($filterError) ?></div>
    <?php endif; ?>

    <?php $showStats = false; // stats panel hidden for now — set to true to bring it back ?>
    <?php if ($showStats): ?>
    <div class="stats-grid">
        <div class="stat-box"><div class="num"><?= (int)$totalTrips ?></div><div class="label">Total Trips</div></div>
        <div class="stat-box"><div class="num">₹<?= number_format((float)$totalAmount, 2) ?></div><div class="label">Total Amount</div></div>
        <div class="stat-box"><div class="num">₹<?= number_format((float)$totalAdvance, 2) ?></div><div class="label">Total Advance</div></div>
        <div class="stat-box"><div class="num"><?= number_format((float)$totalKm, 0) ?> km</div><div class="label">Total KM Run</div></div>
        <div class="stat-box"><div class="num">₹<?= number_format((float)$totalFastag, 2) ?></div><div class="label">FASTag Expense</div></div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" action="trip_logs.php">
                <div class="filter-group">
                    <label for="f_search">Search</label>
                    <input type="text" id="f_search" name="search" placeholder="LR no., vehicle, location, driver..."
                           value="<?= htmlspecialchars($search) ?>">
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
                        <a href="trip_logs.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
            <a href="trip_log_form.php" class="btn btn-accent">+ Add Trip</a>
        </div>

        <div class="table-wrap">
        <table class="sortable-table">
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
                    <th data-no-sort>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="20" style="text-align:center; color:#888;">No trip log records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><?= htmlspecialchars($r['trip_date']) ?></td>
                    <td><?= htmlspecialchars($r['return_date'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['lr_number']) ?></td>
                    <td><?= htmlspecialchars($r['vehicle_no']) ?></td>
                    <td><?= htmlspecialchars($r['location']) ?></td>
                    <td><?= numOrDash($r['sakharwadi_diesel']) ?></td>
                    <td><?= numOrDash($r['rate']) ?></td>
                    <td><?= numOrDash($r['amount']) ?></td>
                    <td><?= numOrDash($r['advance']) ?></td>
                    <td><?= htmlspecialchars($r['driver_name']) ?></td>
                    <td><?= numOrDash($r['before_diesel']) ?></td>
                    <td><?= numOrDash($r['after_diesel']) ?></td>
                    <td><?= numOrDash($r['before_km']) ?></td>
                    <td><?= numOrDash($r['after_km']) ?></td>
                    <td><?= numOrDash($r['total_km']) ?></td>
                    <td><?= numOrDash($r['def_qty']) ?></td>
                    <td><?= numOrDash($r['kl_qty']) ?></td>
                    <td><?= numOrDash($r['fasttag_exp']) ?></td>
                    <td class="actions">
                        <a class="edit" href="trip_log_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <a class="delete" href="trip_log_delete.php?id=<?= (int)$r['id'] ?>"
                           onclick="return confirm('Delete this trip log?');">Delete</a>
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
