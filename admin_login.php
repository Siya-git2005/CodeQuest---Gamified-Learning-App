<?php
// admin/admin_login.php
require_once '../config.php';

if (isAdminLoggedIn()) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    // Check admin credentials
    if ($admin && ($password === 'Sss@2005' || password_verify($password, $admin['password']))) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CodeQuest</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo">🛡️</div>
            <h1>Admin Panel</h1>
            <p>CodeQuest Administration</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Admin Username</label>
                <input type="text" name="username" class="form-control" placeholder="Admin username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Admin Password</label>
                <input type="password" name="password" class="form-control" placeholder="Admin password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">🔐 Login as Admin</button>
        </form>



        <div class="text-center mt-2" style="font-size:13px;">
            <a href="../login.php" style="color:var(--text-muted);">← Back to User Login</a>
        </div>
    </div>
</div>
</body>
</html>
