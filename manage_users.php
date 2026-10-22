<?php
// admin/manage_users.php
require_once '../config.php';
requireAdminLogin();

$db = getDB();

$message = '';

// Handle delete user
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    if ($stmt->execute()) $message = "User deleted.";
    $stmt->close();
}

// Get all users with stats
$users = $db->query("
    SELECT u.*, 
           COUNT(p.id) as levels_done,
           COUNT(DISTINCT p.language_id) as langs_started
    FROM users u
    LEFT JOIN progress p ON p.user_id = u.id AND p.completed = 1
    GROUP BY u.id
    ORDER BY u.total_xp DESC
")->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar admin-nav">
        <div class="sidebar-logo"><div class="logo-text">🛡️ Admin Panel</div></div>
        <nav class="sidebar-nav"><ul>
            <li><a href="admin_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="manage_lessons.php"><span class="icon">📚</span> Lessons</a></li>
            <li><a href="manage_users.php" class="active"><span class="icon">👥</span> Users</a></li>
            <li><a href="view_progress.php"><span class="icon">📈</span> Progress</a></li>
            <li style="margin-top:16px;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;">
                <a href="admin_logout.php" style="color:#FF6584 !important;"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul></nav>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">👥 All Users (<?= count($users) ?>)</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>XP</th>
                        <th>Levels Done</th>
                        <th>Languages</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="flex items-center" style="gap:10px;">
                                <div class="user-avatar" style="width:32px;height:32px;font-size:13px;flex-shrink:0;">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <span style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></span>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);">@<?= htmlspecialchars($u['username']) ?></td>
                        <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="xp-badge">⚡ <?= number_format($u['total_xp']) ?></span></td>
                        <td style="color:var(--accent);"><?= $u['levels_done'] ?></td>
                        <td><?= $u['langs_started'] ?></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>? All their progress will be lost.')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">No users registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../js/main.js"></script>
</body>
</html>
