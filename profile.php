<?php
require 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get progress summary
$stmt = $db->prepare("
    SELECT l.name, l.icon, l.color, 
           COUNT(p.id) as total_done, 
           COALESCE(SUM(p.xp_earned), 0) as xp_earned
    FROM languages l
    LEFT JOIN progress p ON p.language_id = l.id AND p.user_id = ? AND p.completed = 1
    GROUP BY l.id
    ORDER BY total_done DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$langStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Overall stats
$stmt = $db->prepare("SELECT COUNT(*) as total_completed, COALESCE(SUM(xp_earned),0) as total_xp FROM progress WHERE user_id = ? AND completed = 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
$updateSuccess = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($fullName) || empty($email)) {
        $updateError = "Name and email are required.";
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fullName, $email, $userId);
        if ($stmt->execute()) {
            $updateSuccess = "Profile updated successfully!";
            $_SESSION['full_name'] = $fullName;
            $user['full_name'] = $fullName;
            $user['email'] = $email;
        } else {
            $updateError = "Update failed.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $updateError = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $updateError = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $updateError = "Passwords do not match.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $userId);
        if ($stmt->execute()) {
            $updateSuccess = "Password changed successfully!";
        }
        $stmt->close();
    }
}

$db->close();

// Determine rank
$xp = $user['total_xp'];
$rank = 'Beginner 🌱';
if ($xp >= 3000) $rank = 'Master 👑';
elseif ($xp >= 2000) $rank = 'Expert 🔥';
elseif ($xp >= 1000) $rank = 'Advanced ⚡';
elseif ($xp >= 400) $rank = 'Intermediate 💡';

$pageTitle = "My Profile";
$activePage = "profile";
include 'includes/header.php';
?>

<!-- Profile Header -->
<div class="profile-header">
    <div class="profile-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
    <div>
        <h1 style="font-size:26px;font-weight:800;"><?= htmlspecialchars($user['full_name']) ?></h1>
        <p style="opacity:0.8;font-size:14px;">@<?= htmlspecialchars($user['username']) ?></p>
        <div style="margin-top:8px;display:flex;gap:12px;flex-wrap:wrap;">
            <span style="background:rgba(255,255,255,0.2);padding:4px 14px;border-radius:20px;font-size:13px;font-weight:700;"><?= $rank ?></span>
            <span class="xp-badge">⚡ <?= number_format($user['total_xp']) ?> XP</span>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-3">
    <div class="stat-card">
        <div class="stat-value text-primary"><?= $stats['total_completed'] ?></div>
        <div class="stat-label">Levels Done</div>
    </div>
    <div class="stat-card">
        <div class="stat-value text-gold"><?= number_format($stats['total_xp']) ?></div>
        <div class="stat-label">Total XP</div>
    </div>
    <div class="stat-card">
        <div class="stat-value text-accent"><?= count(array_filter($langStats, fn($l) => $l['total_done'] > 0)) ?></div>
        <div class="stat-label">Languages Explored</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--secondary);"><?= count(array_filter($langStats, fn($l) => $l['total_done'] == 7)) ?></div>
        <div class="stat-label">Languages Mastered</div>
    </div>
</div>

<!-- Two column layout -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <!-- Language Progress -->
    <div class="card">
        <h3 style="font-weight:700;margin-bottom:20px;">📊 Language Progress</h3>
        <?php foreach ($langStats as $ls): ?>
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:14px;font-weight:600;"><?= $ls['icon'] ?> <?= htmlspecialchars($ls['name']) ?></span>
                <span style="font-size:12px;color:var(--text-muted);"><?= $ls['total_done'] ?>/7</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" data-width="<?= ($ls['total_done']/7)*100 ?>%" style="background:<?= $ls['color'] ?>;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Edit Profile -->
    <div>
        <div class="card mb-2">
            <h3 style="font-weight:700;margin-bottom:20px;">✏️ Edit Profile</h3>

            <?php if ($updateSuccess): ?>
                <div class="alert alert-success">✅ <?= $updateSuccess ?></div>
            <?php endif; ?>
            <?php if ($updateError): ?>
                <div class="alert alert-danger">⚠️ <?= $updateError ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-weight:700;margin-bottom:20px;">🔐 Change Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Current password">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="New password">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
                </div>
                <button type="submit" name="change_password" class="btn btn-warning">🔒 Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
