<?php
// admin/view_progress.php
require_once '../config.php';
requireAdminLogin();

$db = getDB();

// Get all progress with details
$progress = $db->query("
    SELECT p.*, 
           u.full_name, u.username,
           l.name as lang_name, l.icon as lang_icon,
           lv.title as level_title, lv.level_number
    FROM progress p
    JOIN users u ON u.id = p.user_id
    JOIN languages l ON l.id = p.language_id
    JOIN levels lv ON lv.id = p.level_id
    WHERE p.completed = 1
    ORDER BY p.completed_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Summary by language
$langSummary = $db->query("
    SELECT l.name, l.icon, COUNT(p.id) as total_completions
    FROM languages l
    LEFT JOIN progress p ON p.language_id = l.id AND p.completed = 1
    GROUP BY l.id
    ORDER BY total_completions DESC
")->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Progress - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar admin-nav">
        <div class="sidebar-logo"><div class="logo-text">🛡️ Admin Panel</div></div>
        <nav class="sidebar-nav"><ul>
            <li><a href="admin_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="manage_lessons.php"><span class="icon">📚</span> Lessons</a></li>
            <li><a href="manage_users.php"><span class="icon">👥</span> Users</a></li>
            <li><a href="view_progress.php" class="active"><span class="icon">📈</span> Progress</a></li>
            <li style="margin-top:16px;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;">
                <a href="admin_logout.php" style="color:#FF6584 !important;"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul></nav>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">📈 Learning Progress</h1>
        </div>

        <!-- Language Summary -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px;">
            <?php foreach ($langSummary as $ls): ?>
            <div class="stat-card">
                <div style="font-size:28px;"><?= $ls['icon'] ?></div>
                <div class="stat-value text-primary" style="font-size:24px;"><?= $ls['total_completions'] ?></div>
                <div class="stat-label"><?= htmlspecialchars($ls['name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Completion Log -->
        <div class="card">
            <h3 style="font-weight:700;margin-bottom:16px;">📋 Recent Completions (Latest 100)</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Language</th><th>Level</th><th>XP Earned</th><th>Completed At</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($progress as $p): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($p['full_name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);">@<?= $p['username'] ?></div>
                        </td>
                        <td><?= $p['lang_icon'] ?> <?= htmlspecialchars($p['lang_name']) ?></td>
                        <td>
                            <span style="color:var(--text-muted);">Level <?= $p['level_number'] ?>:</span>
                            <?= htmlspecialchars($p['level_title']) ?>
                        </td>
                        <td><span class="xp-badge">⚡ +<?= $p['xp_earned'] ?></span></td>
                        <td style="font-size:12px;color:var(--text-muted);">
                            <?= $p['completed_at'] ? date('d M Y, g:i A', strtotime($p['completed_at'])) : 'N/A' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($progress)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px;">No completions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../js/main.js"></script>
</body>
</html>
