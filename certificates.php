<?php
// admin/certificates.php - Admin Certificate Management
require_once '../config.php';
requireAdminLogin();

$db = getDB();

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM certificates WHERE id = ?");
    $stmt->bind_param("i", $delId);
    $stmt->execute();
    $stmt->close();
    header("Location: certificates.php?msg=deleted");
    exit();
}

$successMsg = '';
$errorMsg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $successMsg = 'Certificate deleted successfully.';
}

// Search / filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$langFilter = isset($_GET['language']) ? sanitize($_GET['language']) : '';

// Build query
$whereClause = '1=1';
$params = [];
$types = '';

if ($search !== '') {
    $whereClause .= ' AND (c.username LIKE ? OR c.certificate_code LIKE ? OR u.full_name LIKE ?)';
    $searchVal = '%' . $search . '%';
    $params[] = $searchVal;
    $params[] = $searchVal;
    $params[] = $searchVal;
    $types .= 'sss';
}
if ($langFilter !== '') {
    $whereClause .= ' AND c.language = ?';
    $params[] = $langFilter;
    $types .= 's';
}

// Get total count
$countSql = "SELECT COUNT(*) as cnt FROM certificates c JOIN users u ON u.id = c.user_id WHERE $whereClause";
$totalCerts = 0;
if ($params) {
    $stmt = $db->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalCerts = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
} else {
    $totalCerts = $db->query($countSql)->fetch_assoc()['cnt'];
}

// Pagination
$perPage = 15;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $perPage;
$totalPages = ceil($totalCerts / $perPage);

// Get certificates
$sql = "SELECT c.*, u.full_name FROM certificates c 
        JOIN users u ON u.id = c.user_id 
        WHERE $whereClause 
        ORDER BY c.issue_date DESC 
        LIMIT $perPage OFFSET $offset";

$certificates = [];
if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $certificates[] = $row;
    $stmt->close();
} else {
    $result = $db->query($sql);
    while ($row = $result->fetch_assoc()) $certificates[] = $row;
}

// Get distinct languages for filter
$languages = $db->query("SELECT DISTINCT language FROM certificates ORDER BY language")->fetch_all(MYSQLI_ASSOC);

// Stats
$totalCount = $db->query("SELECT COUNT(*) as c FROM certificates")->fetch_assoc()['c'];
$thisMonth = $db->query("SELECT COUNT(*) as c FROM certificates WHERE MONTH(issue_date) = MONTH(NOW()) AND YEAR(issue_date) = YEAR(NOW())")->fetch_assoc()['c'];
$uniqueUsers = $db->query("SELECT COUNT(DISTINCT user_id) as c FROM certificates")->fetch_assoc()['c'];
$uniqueLangs = $db->query("SELECT COUNT(DISTINCT language) as c FROM certificates")->fetch_assoc()['c'];

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .cert-preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(108,99,255,0.15);
            border: 1px solid rgba(108,99,255,0.3);
            color: #6C63FF;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .filter-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .filter-group label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .filter-group input,
        .filter-group select {
            background: var(--bg-dark);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            min-width: 180px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .cert-code {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: var(--text-muted);
            background: rgba(255,255,255,0.05);
            padding: 2px 8px;
            border-radius: 4px;
        }
        .action-links {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .pagination {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            transition: all 0.2s;
        }
        .pagination a:hover { border-color: var(--primary); color: var(--primary); }
        .pagination .active { background: var(--primary); color: white; border-color: var(--primary); }
        .alert-success {
            background: rgba(67,233,123,0.1);
            border: 1px solid rgba(67,233,123,0.3);
            color: #43E97B;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { color: var(--text-muted); }
    </style>
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
                <li><a href="admin_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="manage_lessons.php"><span class="icon">📚</span> Manage Lessons</a></li>
                <li><a href="manage_users.php"><span class="icon">👥</span> Users</a></li>
                <li><a href="view_progress.php"><span class="icon">📈</span> Progress</a></li>
                <li><a href="certificates.php" class="active"><span class="icon">🎓</span> Certificates</a></li>
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
            <div>
                <h1 class="page-title">🎓 Certificate Management</h1>
                <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">View, manage and track all issued certificates</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid mb-3">
            <div class="stat-card">
                <div class="stat-value text-primary"><?= $totalCount ?></div>
                <div class="stat-label">Total Certificates</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-accent"><?= $thisMonth ?></div>
                <div class="stat-label">This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-gold"><?= $uniqueUsers ?></div>
                <div class="stat-label">Certified Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color:var(--secondary);"><?= $uniqueLangs ?></div>
                <div class="stat-label">Languages Covered</div>
            </div>
        </div>

        <?php if ($successMsg): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <!-- Certificates Table Card -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                <h3 style="font-weight:700;font-size:16px;">All Issued Certificates</h3>
                <span style="font-size:13px;color:var(--text-muted);">
                    Showing <?= count($certificates) ?> of <?= $totalCerts ?> results
                </span>
            </div>

            <!-- Filters -->
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>🔍 Search</label>
                        <input type="text" name="search" placeholder="Name, username, cert ID..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-group">
                        <label>🌐 Language</label>
                        <select name="language">
                            <option value="">All Languages</option>
                            <?php foreach ($languages as $lang): ?>
                            <option value="<?= htmlspecialchars($lang['language']) ?>"
                                <?= $langFilter === $lang['language'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang['language']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="certificates.php" class="btn btn-outline btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
            </form>

            <?php if (empty($certificates)): ?>
            <div class="empty-state">
                <div class="icon">🎓</div>
                <p>No certificates found<?= $search || $langFilter ? ' matching your filters' : '' ?>.</p>
                <?php if ($search || $langFilter): ?>
                <br><a href="certificates.php" class="btn btn-outline btn-sm" style="display:inline-flex;">Clear Filters</a>
                <?php endif; ?>
            </div>
            <?php else: ?>

            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Certificate ID</th>
                            <th>Student Name</th>
                            <th>Username</th>
                            <th>Language</th>
                            <th>Issue Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $i => $cert): ?>
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px;"><?= ($offset + $i + 1) ?></td>
                            <td>
                                <span class="cert-code"><?= htmlspecialchars($cert['certificate_code']) ?></span>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($cert['full_name']) ?></div>
                            </td>
                            <td style="color:var(--text-muted);font-size:13px;">
                                @<?= htmlspecialchars($cert['username']) ?>
                            </td>
                            <td>
                                <span class="cert-preview-badge">
                                    <?= htmlspecialchars($cert['language']) ?>
                                </span>
                            </td>
                            <td style="font-size:13px;color:var(--text-muted);">
                                <?= date('d M Y', strtotime($cert['issue_date'])) ?>
                                <div style="font-size:11px;"><?= date('h:i A', strtotime($cert['issue_date'])) ?></div>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="../certificate.php?id=<?= $cert['id'] ?>"
                                       class="btn btn-sm btn-primary" target="_blank"
                                       title="View Certificate">👁️ View</a>
                                    <a href="../certificate.php?id=<?= $cert['id'] ?>"
                                       class="btn btn-sm btn-success" target="_blank"
                                       title="Download Certificate"
                                       onclick="setTimeout(()=>window.open('../certificate.php?id=<?= $cert['id'] ?>','_blank'),100)">
                                       ⬇️ Download</a>
                                    <a href="certificates.php?delete=<?= $cert['id'] ?>"
                                       class="btn btn-sm btn-danger"
                                       title="Delete Certificate"
                                       onclick="return confirm('Delete certificate for <?= htmlspecialchars(addslashes($cert['full_name'])) ?>? This cannot be undone.')">
                                       🗑️ Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&language=<?= urlencode($langFilter) ?>">← Prev</a>
                <?php endif; ?>

                <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                <?php if ($p == $page): ?>
                <span class="active"><?= $p ?></span>
                <?php else: ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&language=<?= urlencode($langFilter) ?>"><?= $p ?></a>
                <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&language=<?= urlencode($langFilter) ?>">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <h3 style="font-weight:700;margin-bottom:16px;">⚡ Quick Actions</h3>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
                <a href="manage_users.php" class="btn btn-outline">👥 View All Users</a>
                <a href="view_progress.php" class="btn btn-outline">📈 View Progress</a>
                <a href="../index.php" class="btn btn-outline">🌐 View Site</a>
            </div>
        </div>
    </main>
</div>
<script src="../js/main.js"></script>
</body>
</html>
