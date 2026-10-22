<?php
require 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

if (isAdminLoggedIn()) {
    header("Location: admin/admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = sanitize($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $db = getDB();

        // First check normal user
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];

            $db->close();

            header("Location: dashboard.php");
            exit();

        } else {

            // If not a normal user, check admin
            $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $db->close();

            /*
             * Admin Login
             * Username: Admin@123
             * Password: Sss@2005
             *
             * Also supports the password hash stored in the database.
             */
            if (
                $admin &&
                $admin['username'] === 'Admin@123' &&
                (
                    $password === 'Sss@2005' ||
                    password_verify($password, $admin['password'])
                )
            ) {

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                header("Location: admin/admin_dashboard.php");
                exit();

            } else {
                $error = "Invalid username/email or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CodeQuest</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <div class="logo">⚡</div>
            <h1>Welcome Back!</h1>
            <p>Login to continue your learning journey</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">
                ✅ Account created! Login to get started.
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label class="form-label">Username or Email</label>
                <input
                    type="text"
                    name="login"
                    class="form-control"
                    placeholder="Enter username or email"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Your password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                🔐 Login
            </button>

        </form>

        <div class="text-center mt-2"
             style="color:var(--text-muted);font-size:14px;">
            New here?
            <a href="register.php">Create an account →</a>
        </div>

    </div>
</div>

<script src="js/main.js"></script>

</body>
</html>