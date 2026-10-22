<?php
// admin/admin_dashboard.php
require_once '../config.php';
requireAdminLogin();

$db = getDB();

// Stats
$totalUsers = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalLessons = $db->query("SELECT COUNT(*) as c FROM lessons")->fetch_assoc()['c'];
$totalCompleted = $db->query("SELECT COUNT(*) as c FROM progress WHERE completed = 1")->fetch_assoc()['c'];
$totalLanguages = $db->query("SELECT COUNT(*) as c FROM languages")->fetch_assoc()['c'];
$certTableExists = $db->query("SHOW TABLES LIKE 'certificates'")->num_rows > 0;
$totalCertificates = $certTableExists ? $db->query("SELECT COUNT(*) as c FROM certificates")->fetch_assoc()['c'] : 0;

// Recent users
$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Users with most XP
$topUsers = $db->query("SELECT full_name, username, total_xp FROM users ORDER BY total_xp DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CodeQuest</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
    <!-- Admin Sidebar -->
    <aside class="sidebar admin-nav" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-text">🛡️ Admin Panel</div>
            <div class="logo-sub">CodeQuest Administration</div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="admin_dashboard.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="manage_lessons.php"><span class="icon">📚</span> Manage Lessons</a></li>
                <li><a href="manage_users.php"><span class="icon">👥</span> Users</a></li>
                <li><a href="view_progress.php"><span class="icon">📈</span> Progress</a></li>
                <li><a href="certificates.php"><span class="icon">🎓</span> Certificates</a></li>
                <li style="margin-top:16px;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;">
                    <a href="admin_logout.php" style="color:#FF6584 !important;"><span class="icon">🚪</span> Logout</a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar" style="background:linear-gradient(135deg,#FF6584,#c0392b);">A</div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_username']) ?></div>
                <div style="font-size:11px;"><span class="admin-badge">ADMIN</span></div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">📊 Admin Dashboard</h1>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-3">
            <div class="stat-card">
                <div class="stat-value text-primary"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-accent"><?= $totalLessons ?></div>
                <div class="stat-label">Total Lessons</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-gold"><?= $totalCompleted ?></div>
                <div class="stat-label">Levels Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color:var(--secondary);"><?= $totalLanguages ?></div>
                <div class="stat-label">Languages</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color:#FFD700;"><?= $totalCertificates ?></div>
                <div class="stat-label">Certificates Issued</div>
            </div>
        </div>

        <!-- Two columns -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

            <!-- Recent Users -->
            <div class="card">
                <h3 style="font-weight:700;margin-bottom:16px;">👥 Recent Users</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Name</th><th>Username</th><th>XP</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td style="color:var(--text-muted);">@<?= htmlspecialchars($u['username']) ?></td>
                            <td><span class="xp-badge">⚡<?= $u['total_xp'] ?></span></td>
                            <td style="font-size:12px;color:var(--text-muted);"><?= date('d M', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentUsers)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">No users yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Learners -->
            <div class="card">
                <h3 style="font-weight:700;margin-bottom:16px;">🏆 Top Learners</h3>
                <?php foreach ($topUsers as $i => $u): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                    <div style="font-size:22px;"><?= ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?></div>
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);">@<?= $u['username'] ?></div>
                    </div>
                    <span class="xp-badge">⚡ <?= number_format($u['total_xp']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <h3 style="font-weight:700;margin-bottom:16px;">⚡ Quick Actions</h3>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="manage_lessons.php?action=add" class="btn btn-primary">➕ Add New Lesson</a>
                <a href="manage_users.php" class="btn btn-outline">👥 View All Users</a>
                <a href="view_progress.php" class="btn btn-outline">📈 View Progress</a>
                <a href="certificates.php" class="btn btn-outline">🎓 Certificates</a>
                <a href="../index.php" class="btn btn-outline">🌐 View Site</a>
            </div>
        </div>
    </main>
</div>
<script src="../js/main.js"></script>
</body>
</html>
