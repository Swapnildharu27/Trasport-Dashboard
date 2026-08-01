<?php
/**
 * Login screen. Credentials are hardcoded (admin / admin) for now —
 * see the SECURITY NOTE below for how to change this.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Only allow redirecting back to a same-app relative path (e.g. "index.php"
 * or "trip_logs.php?status=Completed"), never to an external URL.
 */
function safeRedirectTarget(string $target): string
{
    if ($target === '' || str_contains($target, '://') || str_starts_with($target, '//')) {
        return 'index.php';
    }
    if (!preg_match('#^[A-Za-z0-9_./?=&%-]+$#', $target)) {
        return 'index.php';
    }
    return $target;
}

$redirect = safeRedirectTarget($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ---- SECURITY NOTE ----
    // Hardcoded credentials as requested. For real-world use, replace this
    // with a proper user store (hashed passwords in the database, etc).
    if ($username === 'admin' && $password === 'admin') {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['username']  = $username;
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Invalid username or password.';
}

if (!empty($_SESSION['logged_in'])) {
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Transport Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <h1 class="login-title">🚚 Transport Dashboard</h1>
        <p class="login-subtitle">Sign in to continue</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••" required>
            </div>
            <button type="submit" class="btn btn-accent login-submit">Sign In</button>
        </form>
    </div>
</div>

</body>
</html>
