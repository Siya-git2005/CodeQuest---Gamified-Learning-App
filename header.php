<?php
// includes/header.php
if (!isset($pageTitle)) $pageTitle = 'CodeQuest';
if (!isset($activePage)) $activePage = '';

// Get user info if logged in
$currentUser = null;
if (isLoggedIn()) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $currentUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - CodeQuest</title>
    <link rel="stylesheet" href="<?= isset($cssPath) ? $cssPath : '' ?>css/style.css">
</head>
<body>

<div class="layout">
    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99;opacity:0;transition:all 0.3s;"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-text">⚡ CodeQuest</div>
            <div class="logo-sub">Gamified Learning Platform</div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="<?= isset($cssPath) ? $cssPath : '' ?>dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                        <span class="icon">🏠</span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= isset($cssPath) ? $cssPath : '' ?>languages.php" class="<?= $activePage === 'languages' ? 'active' : '' ?>">
                        <span class="icon">🌐</span> Languages
                    </a>
                </li>
                <li>
                    <a href="<?= isset($cssPath) ? $cssPath : '' ?>profile.php" class="<?= $activePage === 'profile' ? 'active' : '' ?>">
                        <span class="icon">👤</span> My Profile
                    </a>
                </li>
                <li>
                    <a href="<?= isset($cssPath) ? $cssPath : '' ?>leaderboard.php" class="<?= $activePage === 'leaderboard' ? 'active' : '' ?>">
                        <span class="icon">🏆</span> Leaderboard
                    </a>
                </li>
                <?php
                // Show certificates link if user has any
                if (isLoggedIn()) {
                    $certDb = getDB();
                    $certExists = $certDb->query("SHOW TABLES LIKE 'certificates'")->num_rows > 0;
                    $hasCerts = false;
                    if ($certExists) {
                        $cStmt = $certDb->prepare("SELECT COUNT(*) as c FROM certificates WHERE user_id = ?");
                        $cStmt->bind_param("i", $_SESSION['user_id']);
                        $cStmt->execute();
                        $hasCerts = $cStmt->get_result()->fetch_assoc()['c'] > 0;
                        $cStmt->close();
                    }
                    $certDb->close();
                    if ($hasCerts):
                ?>
                <li>
                    <a href="<?= isset($cssPath) ? $cssPath : '' ?>my_certificates.php" class="<?= isset($activePage) && $activePage === 'certificates' ? 'active' : '' ?>">
                        <span class="icon">🎓</span> My Certificates
                    </a>
                </li>
                <?php endif; } ?>
                <li style="margin-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px;">
                    <a href="<?= isset($cssPath) ? $cssPath : '' ?>logout.php" style="color: #FF6584 !important;">
                        <span class="icon">🚪</span> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <?php if ($currentUser): ?>
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                <div class="user-xp">⚡ <?= number_format($currentUser['total_xp']) ?> XP</div>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Mobile hamburger -->
    <button id="hamburger" class="hamburger" style="position:fixed;top:16px;left:16px;z-index:200;padding:8px;">
        <span></span><span></span><span></span>
    </button>

    <main class="main-content">
