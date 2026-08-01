<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$errors = [];
$isEdit = false;
$record = [
    'id'                => '',
    'trip_date'         => '',
    'return_date'       => '',
    'lr_number'         => '',
    'vehicle_no'        => '',
    'location'          => '',
    'sakharwadi_diesel' => '',
    'rate'              => '',
    'amount'            => '',
    'advance'           => '',
    'driver_name'       => '',
    'before_diesel'     => '',
    'after_diesel'      => '',
    'before_km'         => '',
    'after_km'          => '',
    'total_km'          => '',
    'def_qty'           => '',
    'kl_qty'            => '',
    'fasttag_exp'       => '',
];

// ---------- Load existing record for edit ----------
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare('SELECT * FROM trip_logs WHERE id = :id');
    $stmt->execute([':id' => $_GET['id']]);
    $found = $stmt->fetch();
    if ($found) {
        $record = $found;
    } else {
        $isEdit = false;
    }
}

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record['id']                = $_POST['id'] ?? '';
    $record['trip_date']         = trim($_POST['trip_date'] ?? '');
    $record['return_date']       = trim($_POST['return_date'] ?? '');
    $record['lr_number']         = trim($_POST['lr_number'] ?? '');
    $record['vehicle_no']        = trim($_POST['vehicle_no'] ?? '');
    $record['location']          = trim($_POST['location'] ?? '');
    $record['sakharwadi_diesel'] = trim($_POST['sakharwadi_diesel'] ?? '');
    $record['rate']              = trim($_POST['rate'] ?? '');
    $record['amount']            = trim($_POST['amount'] ?? '');
    $record['advance']           = trim($_POST['advance'] ?? '');
    $record['driver_name']       = trim($_POST['driver_name'] ?? '');
    $record['before_diesel']     = trim($_POST['before_diesel'] ?? '');
    $record['after_diesel']      = trim($_POST['after_diesel'] ?? '');
    $record['before_km']         = trim($_POST['before_km'] ?? '');
    $record['after_km']          = trim($_POST['after_km'] ?? '');
    $record['total_km']          = trim($_POST['total_km'] ?? '');
    $record['def_qty']           = trim($_POST['def_qty'] ?? '');
    $record['kl_qty']            = trim($_POST['kl_qty'] ?? '');
    $record['fasttag_exp']       = trim($_POST['fasttag_exp'] ?? '');

    // ---- Validation: required fields (marked * in the spec) ----
    if ($record['trip_date'] === '')         $errors[] = 'Date is required.';
    if ($record['lr_number'] === '')         $errors[] = 'LR number is required.';
    if ($record['vehicle_no'] === '')        $errors[] = 'Vehicle No. is required.';
    if ($record['location'] === '')          $errors[] = 'Location is required.';
    if ($record['sakharwadi_diesel'] === '') $errors[] = 'Sakharwadi Diesel is required.';
    if ($record['rate'] === '')              $errors[] = 'Rate is required.';
    if ($record['amount'] === '')            $errors[] = 'Amount is required.';
    if ($record['driver_name'] === '')       $errors[] = 'Driver Name is required.';
    if ($record['def_qty'] === '')           $errors[] = 'DEF is required.';
    if ($record['kl_qty'] === '')            $errors[] = 'KL is required.';

    // ---- Numeric validation ----
    $numericFields = [
        'sakharwadi_diesel' => 'Sakharwadi Diesel',
        'rate'              => 'Rate',
        'amount'            => 'Amount',
        'advance'           => 'Advance',
        'before_diesel'     => 'Before Diesel',
        'after_diesel'      => 'After Diesel',
        'before_km'         => 'Before KM',
        'after_km'          => 'After KM',
        'total_km'          => 'Total KM',
        'def_qty'           => 'DEF',
        'kl_qty'            => 'KL',
        'fasttag_exp'       => 'Fast Tag Exp.',
    ];
    foreach ($numericFields as $field => $label) {
        if ($record[$field] !== '' && !is_numeric($record[$field])) {
            $errors[] = "$label must be a number.";
        }
    }
    if ($record['return_date'] !== '' && $record['trip_date'] !== '' && $record['return_date'] < $record['trip_date']) {
        $errors[] = 'Return Date cannot be earlier than Date.';
    }
    if ($record['before_km'] !== '' && $record['after_km'] !== '' && is_numeric($record['before_km']) && is_numeric($record['after_km'])
        && (float)$record['after_km'] < (float)$record['before_km']) {
        $errors[] = 'After KM cannot be less than Before KM.';
    }

    // Auto-calculate Total KM if left blank and both readings are present
    if ($record['total_km'] === '' && is_numeric($record['before_km']) && is_numeric($record['after_km'])) {
        $record['total_km'] = (string)((float)$record['after_km'] - (float)$record['before_km']);
    }

    // Normalize optional numeric/blank fields to null
    $toNull = function ($v) { return $v === '' ? null : $v; };
    $returnDate  = $toNull($record['return_date']);
    $advance     = $toNull($record['advance']);
    $beforeDiesel = $toNull($record['before_diesel']);
    $afterDiesel  = $toNull($record['after_diesel']);
    $beforeKm     = $toNull($record['before_km']);
    $afterKm      = $toNull($record['after_km']);
    $totalKm      = $toNull($record['total_km']);
    $fasttagExp   = $toNull($record['fasttag_exp']);

    if (empty($errors)) {
        if ($record['id'] !== '' && ctype_digit((string)$record['id'])) {
            // UPDATE
            $sql = 'UPDATE trip_logs SET
                        trip_date = :trip_date,
                        return_date = :return_date,
                        lr_number = :lr_number,
                        vehicle_no = :vehicle_no,
                        location = :location,
                        sakharwadi_diesel = :sakharwadi_diesel,
                        rate = :rate,
                        amount = :amount,
                        advance = :advance,
                        driver_name = :driver_name,
                        before_diesel = :before_diesel,
                        after_diesel = :after_diesel,
                        before_km = :before_km,
                        after_km = :after_km,
                        total_km = :total_km,
                        def_qty = :def_qty,
                        kl_qty = :kl_qty,
                        fasttag_exp = :fasttag_exp
                    WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':trip_date'         => $record['trip_date'],
                ':return_date'       => $returnDate,
                ':lr_number'         => $record['lr_number'],
                ':vehicle_no'        => $record['vehicle_no'],
                ':location'          => $record['location'],
                ':sakharwadi_diesel' => $record['sakharwadi_diesel'],
                ':rate'              => $record['rate'],
                ':amount'            => $record['amount'],
                ':advance'           => $advance,
                ':driver_name'       => $record['driver_name'],
                ':before_diesel'     => $beforeDiesel,
                ':after_diesel'      => $afterDiesel,
                ':before_km'         => $beforeKm,
                ':after_km'          => $afterKm,
                ':total_km'          => $totalKm,
                ':def_qty'           => $record['def_qty'],
                ':kl_qty'            => $record['kl_qty'],
                ':fasttag_exp'       => $fasttagExp,
                ':id'                => $record['id'],
            ]);
            header('Location: trip_logs.php?msg=updated');
            exit;
        } else {
            // INSERT
            $sql = 'INSERT INTO trip_logs
                        (trip_date, return_date, lr_number, vehicle_no, location, sakharwadi_diesel,
                         rate, amount, advance, driver_name, before_diesel, after_diesel,
                         before_km, after_km, total_km, def_qty, kl_qty, fasttag_exp)
                    VALUES
                        (:trip_date, :return_date, :lr_number, :vehicle_no, :location, :sakharwadi_diesel,
                         :rate, :amount, :advance, :driver_name, :before_diesel, :after_diesel,
                         :before_km, :after_km, :total_km, :def_qty, :kl_qty, :fasttag_exp)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':trip_date'         => $record['trip_date'],
                ':return_date'       => $returnDate,
                ':lr_number'         => $record['lr_number'],
                ':vehicle_no'        => $record['vehicle_no'],
                ':location'          => $record['location'],
                ':sakharwadi_diesel' => $record['sakharwadi_diesel'],
                ':rate'              => $record['rate'],
                ':amount'            => $record['amount'],
                ':advance'           => $advance,
                ':driver_name'       => $record['driver_name'],
                ':before_diesel'     => $beforeDiesel,
                ':after_diesel'      => $afterDiesel,
                ':before_km'         => $beforeKm,
                ':after_km'          => $afterKm,
                ':total_km'          => $totalKm,
                ':def_qty'           => $record['def_qty'],
                ':kl_qty'            => $record['kl_qty'],
                ':fasttag_exp'       => $fasttagExp,
            ]);
            header('Location: trip_logs.php?msg=added');
            exit;
        }
    }
    $isEdit = $record['id'] !== '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isEdit ? 'Edit' : 'Add' ?> Trip Log</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php $activeSection = 'triplog'; require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="tabs">
        <a href="trip_logs.php">Dashboard</a>
        <a href="trip_log_form.php" class="active">+ Add Trip</a>
    </div>

    <div class="card">
        <h2><?= $isEdit ? 'Edit Trip Log #' . (int)$record['id'] : 'Add New Trip Log' ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="trip_log_form.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$record['id']) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="trip_date" value="<?= htmlspecialchars($record['trip_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Return Date</label>
                    <input type="date" name="return_date" value="<?= htmlspecialchars($record['return_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>LR Number *</label>
                    <input type="text" name="lr_number" placeholder="e.g. LR-3001"
                           value="<?= htmlspecialchars($record['lr_number']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Vehicle No. *</label>
                    <input type="text" name="vehicle_no" placeholder="e.g. MH12AB1234"
                           value="<?= htmlspecialchars($record['vehicle_no']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" placeholder="e.g. Sakharwadi"
                           value="<?= htmlspecialchars($record['location']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Sakharwadi Diesel *</label>
                    <input type="number" step="0.01" name="sakharwadi_diesel" placeholder="Ltr"
                           value="<?= htmlspecialchars((string)($record['sakharwadi_diesel'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Rate *</label>
                    <input type="number" step="0.01" name="rate" placeholder="e.g. 92.50"
                           value="<?= htmlspecialchars((string)($record['rate'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" step="0.01" name="amount" placeholder="e.g. 11100.00"
                           value="<?= htmlspecialchars((string)($record['amount'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Advance (Rs)</label>
                    <input type="number" step="0.01" name="advance"
                           value="<?= htmlspecialchars((string)($record['advance'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Driver Name *</label>
                    <input type="text" name="driver_name" placeholder="e.g. Ramesh Patil"
                           value="<?= htmlspecialchars($record['driver_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Before Diesel (Ltr)</label>
                    <input type="number" step="0.01" name="before_diesel"
                           value="<?= htmlspecialchars((string)($record['before_diesel'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>After Diesel (Ltr)</label>
                    <input type="number" step="0.01" name="after_diesel"
                           value="<?= htmlspecialchars((string)($record['after_diesel'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Before KM</label>
                    <input type="number" step="0.01" name="before_km" id="before_km"
                           value="<?= htmlspecialchars((string)($record['before_km'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>After KM</label>
                    <input type="number" step="0.01" name="after_km" id="after_km"
                           value="<?= htmlspecialchars((string)($record['after_km'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Total KM</label>
                    <input type="number" step="0.01" name="total_km" id="total_km"
                           placeholder="Auto-filled from Before/After KM if left blank"
                           value="<?= htmlspecialchars((string)($record['total_km'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>DEF *</label>
                    <input type="number" step="0.01" name="def_qty" placeholder="Ltr"
                           value="<?= htmlspecialchars((string)($record['def_qty'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>KL *</label>
                    <input type="number" step="0.01" name="kl_qty"
                           value="<?= htmlspecialchars((string)($record['kl_qty'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Fast Tag Exp.</label>
                    <input type="number" step="0.01" name="fasttag_exp"
                           value="<?= htmlspecialchars((string)($record['fasttag_exp'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-accent"><?= $isEdit ? 'Update Trip Log' : 'Save Trip Log' ?></button>
                <a href="trip_logs.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const beforeKm = document.getElementById('before_km');
const afterKm  = document.getElementById('after_km');
const totalKm  = document.getElementById('total_km');

function recalcTotalKm() {
    const b = parseFloat(beforeKm.value);
    const a = parseFloat(afterKm.value);
    if (!isNaN(b) && !isNaN(a) && a >= b) {
        totalKm.value = (a - b).toFixed(2);
    }
}
beforeKm.addEventListener('input', recalcTotalKm);
afterKm.addEventListener('input', recalcTotalKm);
</script>
</body>
</html>
