<?php
require 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $gender    = $_POST['gender'] ?? '';
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($username) || strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (!in_array($gender, ['male', 'female'])) $errors[] = "Please select your gender.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $db = getDB();

        // Check if username/email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $errors[] = "Username or email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password, gender) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $username, $email, $hashed, $gender);

            if ($stmt->execute()) {
                $success = "Account created! You can now login.";
            } else {
                $errors[] = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CodeQuest</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <div class="logo">⚡</div>
            <h1>Join CodeQuest</h1>
            <p>Create your account and start learning</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= $success ?>
                <a href="login.php" style="color:inherit;font-weight:700;">
                    Login now →
                </a>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div>⚠️ <?= $e ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    class="form-control"
                    placeholder="Your full name"
                    value="<?= sanitize($_POST['full_name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Choose a username"
                    value="<?= sanitize($_POST['username'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="your@email.com"
                    value="<?= sanitize($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <!-- Gender -->
            <div class="form-group">
                <label class="form-label">Gender</label>

                <div style="display:flex;gap:20px;align-items:center;margin-top:8px;">

                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input
                            type="radio"
                            name="gender"
                            value="male"
                            <?= (($_POST['gender'] ?? '') === 'male') ? 'checked' : '' ?>
                            required
                        >
                        👦 Male
                    </label>

                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input
                            type="radio"
                            name="gender"
                            value="female"
                            <?= (($_POST['gender'] ?? '') === 'female') ? 'checked' : '' ?>
                        >
                        👧 Female
                    </label>

                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Min. 6 characters"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    class="form-control"
                    placeholder="Repeat password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                🚀 Create Account
            </button>

        </form>

        <div class="text-center mt-2" style="color:var(--text-muted);font-size:14px;">
            Already have an account?
            <a href="login.php">Login →</a>
        </div>

    </div>
</div>

<script src="js/main.js"></script>

</body>
</html>