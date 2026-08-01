<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$workTypes = [
    'Servicing (Full)', 'Servicing (Half)', 'Welding', 'Main Tank Work', 'Tyre Work',
    'Balancing Work', 'Battery Work', 'Oil Change', 'Clutch / Brake Work', 'Electrical Work',
    'Denting / Painting', 'Puncture Repair', 'Engine Work', 'Suspension Work', 'Other',
];
$paymentModes = ['Cash', 'UPI', 'Bank Transfer', 'Cheque', 'Credit'];
$statuses = ['Pending', 'In Progress', 'Completed'];

$errors = [];
$isEdit = false;
$record = [
    'id'                     => '',
    'service_date'           => '',
    'vehicle_no'             => '',
    'odometer_km'            => '',
    'work_type'              => 'Servicing (Full)',
    'work_type_other'        => '',
    'description'            => '',
    'parts_replaced'         => '',
    'garage_name'            => '',
    'location'               => '',
    'bill_number'            => '',
    'parts_cost'             => '',
    'labour_cost'            => '',
    'total_cost'             => '',
    'payment_mode'           => '',
    'status'                 => 'Pending',
    'next_service_due_date'  => '',
];

// ---------- Load existing record for edit ----------
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare('SELECT * FROM maintenance_records WHERE id = :id');
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
    $record['id']                    = $_POST['id'] ?? '';
    $record['service_date']          = trim($_POST['service_date'] ?? '');
    $record['vehicle_no']            = trim($_POST['vehicle_no'] ?? '');
    $record['odometer_km']           = trim($_POST['odometer_km'] ?? '');
    $record['work_type']             = trim($_POST['work_type'] ?? '');
    $record['work_type_other']       = trim($_POST['work_type_other'] ?? '');
    $record['description']           = trim($_POST['description'] ?? '');
    $record['parts_replaced']        = trim($_POST['parts_replaced'] ?? '');
    $record['garage_name']           = trim($_POST['garage_name'] ?? '');
    $record['location']              = trim($_POST['location'] ?? '');
    $record['bill_number']           = trim($_POST['bill_number'] ?? '');
    $record['parts_cost']            = trim($_POST['parts_cost'] ?? '');
    $record['labour_cost']           = trim($_POST['labour_cost'] ?? '');
    $record['total_cost']            = trim($_POST['total_cost'] ?? '');
    $record['payment_mode']          = trim($_POST['payment_mode'] ?? '');
    $record['status']                = trim($_POST['status'] ?? 'Pending');
    $record['next_service_due_date'] = trim($_POST['next_service_due_date'] ?? '');

    // ---- Validation ----
    if ($record['service_date'] === '') $errors[] = 'Service Date is required.';
    if ($record['vehicle_no'] === '')   $errors[] = 'Vehicle No. is required.';
    if (!in_array($record['work_type'], $workTypes, true)) {
        $errors[] = 'Please select a valid Work Type.';
    }
    if ($record['work_type'] === 'Other' && $record['work_type_other'] === '') {
        $errors[] = 'Please describe the work when Work Type is "Other".';
    }
    if ($record['total_cost'] === '') $errors[] = 'Total Cost is required.';
    if (!in_array($record['status'], $statuses, true)) {
        $errors[] = 'Invalid status.';
    }
    if ($record['payment_mode'] !== '' && !in_array($record['payment_mode'], $paymentModes, true)) {
        $errors[] = 'Invalid payment mode.';
    }

    $numericFields = [
        'odometer_km' => 'Odometer (KM)', 'parts_cost' => 'Parts Cost', 'labour_cost' => 'Labour Cost',
        'total_cost' => 'Total Cost',
    ];
    foreach ($numericFields as $field => $label) {
        if ($record[$field] !== '' && !is_numeric($record[$field])) {
            $errors[] = "$label must be a number.";
        }
    }

    // Auto-fill Total Cost from Parts + Labour if left blank
    if ($record['total_cost'] === '' && is_numeric($record['parts_cost']) && is_numeric($record['labour_cost'])) {
        $record['total_cost'] = (string)((float)$record['parts_cost'] + (float)$record['labour_cost']);
    }

    $toNull = function ($v) { return $v === '' ? null : $v; };
    $odometerKm         = $toNull($record['odometer_km']);
    $workTypeOther      = $record['work_type'] === 'Other' ? $toNull($record['work_type_other']) : null;
    $description        = $toNull($record['description']);
    $partsReplaced      = $toNull($record['parts_replaced']);
    $garageName         = $toNull($record['garage_name']);
    $location           = $toNull($record['location']);
    $billNumber         = $toNull($record['bill_number']);
    $partsCost          = $toNull($record['parts_cost']);
    $labourCost         = $toNull($record['labour_cost']);
    $paymentMode        = $toNull($record['payment_mode']);
    $nextServiceDueDate = $toNull($record['next_service_due_date']);

    if (empty($errors)) {
        if ($record['id'] !== '' && ctype_digit((string)$record['id'])) {
            // UPDATE
            $sql = 'UPDATE maintenance_records SET
                        service_date = :service_date,
                        vehicle_no = :vehicle_no,
                        odometer_km = :odometer_km,
                        work_type = :work_type,
                        work_type_other = :work_type_other,
                        description = :description,
                        parts_replaced = :parts_replaced,
                        garage_name = :garage_name,
                        location = :location,
                        bill_number = :bill_number,
                        parts_cost = :parts_cost,
                        labour_cost = :labour_cost,
                        total_cost = :total_cost,
                        payment_mode = :payment_mode,
                        status = :status,
                        next_service_due_date = :next_service_due_date
                    WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':service_date'           => $record['service_date'],
                ':vehicle_no'             => $record['vehicle_no'],
                ':odometer_km'            => $odometerKm,
                ':work_type'              => $record['work_type'],
                ':work_type_other'        => $workTypeOther,
                ':description'            => $description,
                ':parts_replaced'         => $partsReplaced,
                ':garage_name'            => $garageName,
                ':location'               => $location,
                ':bill_number'            => $billNumber,
                ':parts_cost'             => $partsCost,
                ':labour_cost'            => $labourCost,
                ':total_cost'             => $record['total_cost'],
                ':payment_mode'           => $paymentMode,
                ':status'                 => $record['status'],
                ':next_service_due_date'  => $nextServiceDueDate,
                ':id'                     => $record['id'],
            ]);
            header('Location: maintenance.php?msg=updated');
            exit;
        } else {
            // INSERT
            $sql = 'INSERT INTO maintenance_records
                        (service_date, vehicle_no, odometer_km, work_type, work_type_other,
                         description, parts_replaced, garage_name, location, bill_number, parts_cost,
                         labour_cost, total_cost, payment_mode, status, next_service_due_date)
                    VALUES
                        (:service_date, :vehicle_no, :odometer_km, :work_type, :work_type_other,
                         :description, :parts_replaced, :garage_name, :location, :bill_number, :parts_cost,
                         :labour_cost, :total_cost, :payment_mode, :status, :next_service_due_date)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':service_date'           => $record['service_date'],
                ':vehicle_no'             => $record['vehicle_no'],
                ':odometer_km'            => $odometerKm,
                ':work_type'              => $record['work_type'],
                ':work_type_other'        => $workTypeOther,
                ':description'            => $description,
                ':parts_replaced'         => $partsReplaced,
                ':garage_name'            => $garageName,
                ':location'               => $location,
                ':bill_number'            => $billNumber,
                ':parts_cost'             => $partsCost,
                ':labour_cost'            => $labourCost,
                ':total_cost'             => $record['total_cost'],
                ':payment_mode'           => $paymentMode,
                ':status'                 => $record['status'],
                ':next_service_due_date'  => $nextServiceDueDate,
            ]);
            header('Location: maintenance.php?msg=added');
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
<title><?= $isEdit ? 'Edit' : 'Add' ?> Maintenance Record</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php $activeSection = 'maintenance'; require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="tabs">
        <a href="maintenance.php">Dashboard</a>
        <a href="maintenance_form.php" class="active">+ Add Record</a>
    </div>

    <div class="card">
        <h2><?= $isEdit ? 'Edit Maintenance Record #' . (int)$record['id'] : 'Add New Maintenance Record' ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="maintenance_form.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$record['id']) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Service Date *</label>
                    <input type="date" name="service_date" value="<?= htmlspecialchars($record['service_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Vehicle No. *</label>
                    <input type="text" name="vehicle_no" placeholder="e.g. MH12AB1234"
                           value="<?= htmlspecialchars($record['vehicle_no']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Odometer (KM)</label>
                    <input type="number" step="0.01" name="odometer_km"
                           value="<?= htmlspecialchars((string)($record['odometer_km'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Work Type *</label>
                    <select name="work_type" id="work_type" required>
                        <?php foreach ($workTypes as $wt): ?>
                            <option value="<?= htmlspecialchars($wt) ?>" <?= $record['work_type'] === $wt ? 'selected' : '' ?>><?= htmlspecialchars($wt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="other_work_type_group" style="<?= $record['work_type'] === 'Other' ? '' : 'display:none;' ?>">
                    <label>Describe "Other" Work *</label>
                    <input type="text" name="work_type_other" id="work_type_other" placeholder="e.g. AC Compressor Repair"
                           value="<?= htmlspecialchars($record['work_type_other'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Garage / Service Provider</label>
                    <input type="text" name="garage_name" placeholder="e.g. Shree Auto Garage"
                           value="<?= htmlspecialchars($record['garage_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. Pune"
                           value="<?= htmlspecialchars($record['location'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Bill / Invoice Number</label>
                    <input type="text" name="bill_number" placeholder="e.g. BILL-4001"
                           value="<?= htmlspecialchars($record['bill_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Parts Cost (₹)</label>
                    <input type="number" step="0.01" name="parts_cost" id="parts_cost"
                           value="<?= htmlspecialchars((string)($record['parts_cost'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Labour Cost (₹)</label>
                    <input type="number" step="0.01" name="labour_cost" id="labour_cost"
                           value="<?= htmlspecialchars((string)($record['labour_cost'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Total Cost (₹) *</label>
                    <input type="number" step="0.01" name="total_cost" id="total_cost"
                           placeholder="Auto-filled from Parts + Labour if left blank"
                           value="<?= htmlspecialchars((string)($record['total_cost'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select name="payment_mode">
                        <option value="">— Select —</option>
                        <?php foreach ($paymentModes as $pm): ?>
                            <option value="<?= $pm ?>" <?= $record['payment_mode'] === $pm ? 'selected' : '' ?>><?= $pm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $record['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Next Service Due Date</label>
                    <input type="date" name="next_service_due_date" value="<?= htmlspecialchars($record['next_service_due_date'] ?? '') ?>">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Parts Replaced</label>
                    <input type="text" name="parts_replaced" placeholder="e.g. Engine oil, oil filter, air filter"
                           value="<?= htmlspecialchars($record['parts_replaced'] ?? '') ?>">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Description / Work Done</label>
                    <textarea name="description" rows="3" placeholder="Details of the work carried out..."><?= htmlspecialchars($record['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-accent"><?= $isEdit ? 'Update Record' : 'Save Record' ?></button>
                <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const workType = document.getElementById('work_type');
const otherGroup = document.getElementById('other_work_type_group');
const otherInput = document.getElementById('work_type_other');

function toggleOtherField() {
    if (workType.value === 'Other') {
        otherGroup.style.display = '';
        otherInput.setAttribute('required', 'required');
    } else {
        otherGroup.style.display = 'none';
        otherInput.removeAttribute('required');
    }
}
workType.addEventListener('change', toggleOtherField);
toggleOtherField();

const partsCost = document.getElementById('parts_cost');
const labourCost = document.getElementById('labour_cost');
const totalCost = document.getElementById('total_cost');

function recalcTotalCost() {
    const p = parseFloat(partsCost.value);
    const l = parseFloat(labourCost.value);
    if (!isNaN(p) || !isNaN(l)) {
        totalCost.value = ((isNaN(p) ? 0 : p) + (isNaN(l) ? 0 : l)).toFixed(2);
    }
}
partsCost.addEventListener('input', recalcTotalCost);
labourCost.addEventListener('input', recalcTotalCost);
</script>
</body>
</html>
