<?php
require 'config.php';
requireLogin();

$db = getDB();

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all languages
$langs = $db->query("SELECT * FROM languages ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Get user's overall stats
$userId = $_SESSION['user_id'];

// Total completed levels
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM progress WHERE user_id = ? AND completed = 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$completedLevels = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Total languages started
$stmt = $db->prepare("SELECT COUNT(DISTINCT language_id) as cnt FROM progress WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$langStarted = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Progress per language
$progressByLang = [];
$stmt = $db->prepare("SELECT language_id, COUNT(*) as total, SUM(completed) as done FROM progress WHERE user_id = ? GROUP BY language_id");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $progressByLang[$row['language_id']] = $row;
}

$stmt->close();

// Check which languages have Level 7 completed and get/generate certificates
$certsByLang = [];
$certTableExists = $db->query("SHOW TABLES LIKE 'certificates'")->num_rows > 0;

if ($certTableExists) {

    // Get level 7 ID
    $level7 = $db->query("SELECT id FROM levels WHERE level_number = 7 LIMIT 1")->fetch_assoc();
    $level7Id = $level7 ? $level7['id'] : null;

    if ($level7Id) {

        // Find languages where user completed level 7
        $stmt = $db->prepare("SELECT p.language_id, p.completed_at FROM progress p WHERE p.user_id = ? AND p.level_id = ? AND p.completed = 1");
        $stmt->bind_param("ii", $userId, $level7Id);
        $stmt->execute();
        $level7Completions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($level7Completions as $comp) {

            $langId = $comp['language_id'];

            // Check if certificate already exists
            $stmt = $db->prepare("SELECT * FROM certificates WHERE user_id = ? AND language = (SELECT name FROM languages WHERE id = ?)");
            $stmt->bind_param("ii", $userId, $langId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existing) {

                // Auto-generate certificate
                $langName = '';

                $stmt2 = $db->prepare("SELECT name, slug FROM languages WHERE id = ?");
                $stmt2->bind_param("i", $langId);
                $stmt2->execute();
                $langRow = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();

                if ($langRow) {

                    $certCode = 'CERT-' . strtoupper(substr($langRow['slug'], 0, 3)) . '-' 
                                . $userId . '-' . str_pad($langId, 3, '0', STR_PAD_LEFT) 
                                . '-' . date('Ymd', strtotime($comp['completed_at']));

                    $stmt3 = $db->prepare("INSERT IGNORE INTO certificates (user_id, username, language, certificate_code, issue_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt3->bind_param("issss", $userId, $user['username'], $langRow['name'], $certCode, $comp['completed_at']);
                    $stmt3->execute();
                    $stmt3->close();

                    // Fetch the newly created cert
                    $stmt4 = $db->prepare("SELECT * FROM certificates WHERE certificate_code = ?");
                    $stmt4->bind_param("s", $certCode);
                    $stmt4->execute();
                    $certsByLang[$langId] = $stmt4->get_result()->fetch_assoc();
                    $stmt4->close();
                }

            } else {
                $certsByLang[$langId] = $existing;
            }
        }
    }
}

$db->close();

$pageTitle = "Dashboard";
$activePage = "dashboard";

include 'includes/header.php';
?>

<!-- CodeQuest New Player Assistant -->
<div id="codequest-assistant"
     data-gender="<?= htmlspecialchars($user['gender'] ?? 'male') ?>"
     data-user-id="<?= (int)$user['id'] ?>"
     data-tutorial-seen="<?= (int)($user['tutorial_seen'] ?? 0) ?>"
     style="display:none;">
</div>

<div class="topbar">

    <div>
        <h1 class="page-title">
            👋 Hi, <?= htmlspecialchars($user['full_name']) ?>!
        </h1>

        <p style="color:var(--text-muted);font-size:14px;margin-top:4px;">
            Ready to level up today?
        </p>
    </div>

    <div class="xp-badge" style="font-size:16px;padding:8px 20px;">
        ⚡ <?= number_format($user['total_xp']) ?> XP
    </div>

</div>


<!-- Stats -->
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-value text-primary">
            <?= $completedLevels ?>
        </div>

        <div class="stat-label">
            Levels Completed
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-value text-accent">
            <?= $langStarted ?>
        </div>

        <div class="stat-label">
            Languages Started
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-value text-gold">
            <?= number_format($user['total_xp']) ?>
        </div>

        <div class="stat-label">
            Total XP Earned
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-value" style="color:var(--secondary);">
            <?= 7 * count($langs) ?>
        </div>

        <div class="stat-label">
            Total Levels Available
        </div>
    </div>

</div>


<!-- Language Cards -->
<div class="mb-2">

    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;">
        📚 Choose a Language to Learn
    </h2>

    <p style="color:var(--text-muted);font-size:14px;">
        Select any language to view your levels and progress
    </p>

</div>


<div class="card-grid">

    <?php foreach ($langs as $lang):

        $done = $progressByLang[$lang['id']]['done'] ?? 0;

        $total = 7;

        $pct = $total > 0
            ? round(($done / $total) * 100)
            : 0;

    ?>

    <div style="position:relative;">

        <a href="levels.php?lang=<?= $lang['id'] ?>"
           style="text-decoration:none;">

            <div class="lang-card"
                 style="--lang-color:<?= $lang['color'] ?>;">

                <div class="lang-icon">
                    <?= $lang['icon'] ?>
                </div>

                <div class="lang-name">
                    <?= htmlspecialchars($lang['name']) ?>
                </div>

                <div class="lang-desc">
                    <?= htmlspecialchars($lang['description']) ?>
                </div>


                <div class="lang-progress w-full">

                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">

                        <span style="color:var(--text-muted);">
                            <?= $done ?>/<?= $total ?> levels
                        </span>

                        <span style="color:<?= $lang['color'] ?>;">
                            <?= $pct ?>%
                        </span>

                    </div>


                    <div class="lang-progress-bar">

                        <div
                            style="width:<?= $pct ?>%;background:<?= $lang['color'] ?>;"
                            class="lang-progress-fill">
                        </div>

                    </div>

                </div>


                <?php if ($pct >= 100): ?>

                <div style="margin-top:6px;text-align:center;font-size:11px;color:#FFD700;font-weight:700;">
                    🏆 COMPLETED
                </div>

                <?php endif; ?>

            </div>

        </a>


        <?php if ($pct >= 100 && isset($certsByLang[$lang['id']])): ?>

        <div style="padding:8px 0 4px;text-align:center;">

            <a
                href="certificate.php?code=<?= urlencode($certsByLang[$lang['id']]['certificate_code']) ?>"
                style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#6C63FF,#43E97B);
                color:white;padding:8px 18px;border-radius:20px;font-size:12px;font-weight:700;
                text-decoration:none;transition:all 0.2s;box-shadow:0 4px 12px rgba(108,99,255,0.3);"
                target="_blank"
                onmouseover="this.style.transform='translateY(-1px)'"
                onmouseout="this.style.transform=''">

                🎓 Download Certificate

            </a>

        </div>

        <?php endif; ?>

    </div>

    <?php endforeach; ?>

</div>


<?php if (!empty($certsByLang)): ?>

<!-- My Certificates Section -->

<div class="mb-2 mt-3">

    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;">
        🎓 My Certificates
    </h2>

    <p style="color:var(--text-muted);font-size:14px;">
        Your earned certificates for completed languages
    </p>

</div>


<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">

    <?php foreach ($certsByLang as $langId => $cert):

        // Find lang name
        $certLang = null;

        foreach ($langs as $l) {
            if ($l['id'] == $langId) {
                $certLang = $l;
                break;
            }
        }

    ?>

    <div style="background:linear-gradient(135deg,rgba(108,99,255,0.1),rgba(67,233,123,0.05));
                border:1px solid rgba(108,99,255,0.3);
                border-radius:12px;
                padding:16px 20px;
                display:flex;
                align-items:center;
                gap:12px;
                min-width:220px;">

        <span style="font-size:28px;">
            <?= $certLang ? $certLang['icon'] : '🎓' ?>
        </span>

        <div style="flex:1;">

            <div style="font-weight:700;font-size:13px;">
                <?= htmlspecialchars($cert['language']) ?>
            </div>

            <div style="font-size:11px;color:var(--text-muted);">
                Completed <?= date('d M Y', strtotime($cert['issue_date'])) ?>
            </div>

        </div>


        <a
            href="certificate.php?code=<?= urlencode($cert['certificate_code']) ?>"
            style="background:#6C63FF;
            color:white;
            padding:6px 14px;
            border-radius:8px;
            font-size:11px;
            font-weight:700;
            text-decoration:none;"
            target="_blank">

            View

        </a>

    </div>

    <?php endforeach; ?>


    <a
        href="my_certificates.php"
        style="background:rgba(255,255,255,0.05);
        border:1px dashed rgba(255,255,255,0.2);
        border-radius:12px;
        padding:16px 20px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        color:var(--text-muted);
        font-size:13px;
        text-decoration:none;
        min-width:160px;">

        View All Certificates →

    </a>

</div>

<?php endif; ?>


<?php include 'includes/footer.php'; ?>