<?php
require_once __DIR__ . '/db_config.php';
$pdo = getDbConnection();

$errors = [];
$isEdit = false;
$record = [
    'id'             => '',
    'vehicle_no'     => '',
    'driver_name'    => '',
    'driver_contact' => '',
    'source'         => '',
    'destination'    => '',
    'departure_date' => '',
    'departure_time' => '',
    'arrival_time'   => '',
    'status'         => 'Scheduled',
    'remarks'        => '',
    'supplier'       => '',
    'lr_number'      => '',
    'invoice_number' => '',
    'gst_number'     => '',
    'quantity'       => '',
    'rate'           => '',
];

// ---------- Load existing record for edit ----------
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare('SELECT * FROM transport_records WHERE id = :id');
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
    $record['id']             = $_POST['id'] ?? '';
    $record['vehicle_no']     = trim($_POST['vehicle_no'] ?? '');
    $record['driver_name']    = trim($_POST['driver_name'] ?? '');
    $record['driver_contact'] = trim($_POST['driver_contact'] ?? '');
    $record['source']         = trim($_POST['source'] ?? '');
    $record['destination']    = trim($_POST['destination'] ?? '');
    $record['departure_date'] = trim($_POST['departure_date'] ?? '');
    $record['departure_time'] = trim($_POST['departure_time'] ?? '');
    $record['arrival_time']   = trim($_POST['arrival_time'] ?? '');
    $record['status']         = trim($_POST['status'] ?? 'Scheduled');
    $record['remarks']        = trim($_POST['remarks'] ?? '');
    $record['supplier']       = trim($_POST['supplier'] ?? '');
    $record['lr_number']      = trim($_POST['lr_number'] ?? '');
    $record['invoice_number'] = trim($_POST['invoice_number'] ?? '');
    $record['gst_number']     = trim(strtoupper($_POST['gst_number'] ?? ''));
    $record['quantity']       = trim($_POST['quantity'] ?? '');
    $record['rate']           = trim($_POST['rate'] ?? '');

    // ---- Validation ----
    if ($record['vehicle_no'] === '')     $errors[] = 'Vehicle number is required.';
    if ($record['driver_name'] === '')    $errors[] = 'Driver name is required.';
    if ($record['driver_contact'] === '') $errors[] = 'Driver contact is required.';
    elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $record['driver_contact'])) {
        $errors[] = 'Driver contact must be a valid phone number.';
    }
    if ($record['source'] === '')         $errors[] = 'Source is required.';
    if ($record['destination'] === '')    $errors[] = 'Destination is required.';
    if ($record['departure_date'] === '') $errors[] = 'Departure date is required.';
    if ($record['departure_time'] === '') $errors[] = 'Departure time is required.';
    if (!in_array($record['status'], ['Scheduled','In Transit','Completed','Cancelled'], true)) {
        $errors[] = 'Invalid status.';
    }
    if ($record['gst_number'] !== '' && !preg_match('/^[0-9A-Z]{15}$/', $record['gst_number'])) {
        $errors[] = 'GST number must be 15 alphanumeric characters (e.g. 27ABCDE1234F1Z5).';
    }
    if ($record['quantity'] !== '' && !is_numeric($record['quantity'])) {
        $errors[] = 'Quantity must be a number.';
    }
    if ($record['rate'] !== '' && !is_numeric($record['rate'])) {
        $errors[] = 'Rate must be a number.';
    }

    $arrivalTime    = $record['arrival_time'] !== '' ? $record['arrival_time'] : null;
    $remarks        = $record['remarks'] !== '' ? $record['remarks'] : null;
    $supplier       = $record['supplier'] !== '' ? $record['supplier'] : null;
    $lrNumber       = $record['lr_number'] !== '' ? $record['lr_number'] : null;
    $invoiceNumber  = $record['invoice_number'] !== '' ? $record['invoice_number'] : null;
    $gstNumber      = $record['gst_number'] !== '' ? $record['gst_number'] : null;
    $quantity       = $record['quantity'] !== '' ? $record['quantity'] : null;
    $rate           = $record['rate'] !== '' ? $record['rate'] : null;

    if (empty($errors)) {
        if ($record['id'] !== '' && ctype_digit((string)$record['id'])) {
            // UPDATE
            $sql = 'UPDATE transport_records SET
                        vehicle_no = :vehicle_no,
                        driver_name = :driver_name,
                        driver_contact = :driver_contact,
                        source = :source,
                        destination = :destination,
                        departure_date = :departure_date,
                        departure_time = :departure_time,
                        arrival_time = :arrival_time,
                        status = :status,
                        remarks = :remarks,
                        supplier = :supplier,
                        lr_number = :lr_number,
                        invoice_number = :invoice_number,
                        gst_number = :gst_number,
                        quantity = :quantity,
                        rate = :rate
                    WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':vehicle_no'     => $record['vehicle_no'],
                ':driver_name'    => $record['driver_name'],
                ':driver_contact' => $record['driver_contact'],
                ':source'         => $record['source'],
                ':destination'    => $record['destination'],
                ':departure_date' => $record['departure_date'],
                ':departure_time' => $record['departure_time'],
                ':arrival_time'   => $arrivalTime,
                ':status'         => $record['status'],
                ':remarks'        => $remarks,
                ':supplier'       => $supplier,
                ':lr_number'      => $lrNumber,
                ':invoice_number' => $invoiceNumber,
                ':gst_number'     => $gstNumber,
                ':quantity'       => $quantity,
                ':rate'           => $rate,
                ':id'             => $record['id'],
            ]);
            header('Location: index.php?msg=updated');
            exit;
        } else {
            // INSERT
            $sql = 'INSERT INTO transport_records
                        (vehicle_no, driver_name, driver_contact, source, destination,
                         departure_date, departure_time, arrival_time, status, remarks,
                         supplier, lr_number, invoice_number, gst_number, quantity, rate)
                    VALUES
                        (:vehicle_no, :driver_name, :driver_contact, :source, :destination,
                         :departure_date, :departure_time, :arrival_time, :status, :remarks,
                         :supplier, :lr_number, :invoice_number, :gst_number, :quantity, :rate)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':vehicle_no'     => $record['vehicle_no'],
                ':driver_name'    => $record['driver_name'],
                ':driver_contact' => $record['driver_contact'],
                ':source'         => $record['source'],
                ':destination'    => $record['destination'],
                ':departure_date' => $record['departure_date'],
                ':departure_time' => $record['departure_time'],
                ':arrival_time'   => $arrivalTime,
                ':status'         => $record['status'],
                ':remarks'        => $remarks,
                ':supplier'       => $supplier,
                ':lr_number'      => $lrNumber,
                ':invoice_number' => $invoiceNumber,
                ':gst_number'     => $gstNumber,
                ':quantity'       => $quantity,
                ':rate'           => $rate,
            ]);
            header('Location: index.php?msg=added');
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
<title><?= $isEdit ? 'Edit' : 'Add' ?> Transport Record</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php $activeSection = 'transport'; require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="tabs">
        <a href="index.php">Dashboard</a>
        <a href="add_edit.php" class="active">+ Add Record</a>
    </div>

    <div class="card">
        <h2><?= $isEdit ? 'Edit Transport Record #' . (int)$record['id'] : 'Add New Transport Record' ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_edit.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$record['id']) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Vehicle Number *</label>
                    <input type="text" name="vehicle_no" placeholder="e.g. MH12AB1234"
                           value="<?= htmlspecialchars($record['vehicle_no']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Driver Name *</label>
                    <input type="text" name="driver_name" placeholder="e.g. Ramesh Patil"
                           value="<?= htmlspecialchars($record['driver_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Driver Contact *</label>
                    <input type="tel" name="driver_contact" placeholder="e.g. 9876543210"
                           value="<?= htmlspecialchars($record['driver_contact']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Source *</label>
                    <input type="text" name="source" placeholder="e.g. Pune"
                           value="<?= htmlspecialchars($record['source']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Destination *</label>
                    <input type="text" name="destination" placeholder="e.g. Mumbai"
                           value="<?= htmlspecialchars($record['destination']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Departure Date *</label>
                    <input type="date" name="departure_date"
                           value="<?= htmlspecialchars($record['departure_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Departure Time *</label>
                    <input type="time" name="departure_time"
                           value="<?= htmlspecialchars(substr($record['departure_time'], 0, 5)) ?>" required>
                </div>
                <div class="form-group">
                    <label>Arrival Time</label>
                    <input type="time" name="arrival_time"
                           value="<?= $record['arrival_time'] ? htmlspecialchars(substr($record['arrival_time'], 0, 5)) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <?php foreach (['Scheduled','In Transit','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $record['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" name="supplier" placeholder="e.g. ABC Traders"
                           value="<?= htmlspecialchars($record['supplier'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>LR Number</label>
                    <input type="text" name="lr_number" placeholder="e.g. LR-1001"
                           value="<?= htmlspecialchars($record['lr_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Invoice Number</label>
                    <input type="text" name="invoice_number" placeholder="e.g. INV-2001"
                           value="<?= htmlspecialchars($record['invoice_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>GST Number</label>
                    <input type="text" name="gst_number" placeholder="e.g. 27ABCDE1234F1Z5" maxlength="15"
                           style="text-transform:uppercase"
                           value="<?= htmlspecialchars($record['gst_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" step="0.01" name="quantity" placeholder="e.g. 12.50"
                           value="<?= htmlspecialchars((string)($record['quantity'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Rate (₹)</label>
                    <input type="number" step="0.01" name="rate" placeholder="e.g. 4500.00"
                           value="<?= htmlspecialchars((string)($record['rate'] ?? '')) ?>">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" placeholder="Optional notes..."><?= htmlspecialchars($record['remarks'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-accent"><?= $isEdit ? 'Update Record' : 'Save Record' ?></button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
