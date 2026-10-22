<?php
require 'config.php';
requireLogin();

$db = getDB();
$langs = $db->query("SELECT * FROM languages ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$userId = $_SESSION['user_id'];
$progressByLang = [];
$stmt = $db->prepare("SELECT language_id, SUM(completed) as done FROM progress WHERE user_id = ? GROUP BY language_id");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $progressByLang[$row['language_id']] = $row['done'];
}
$stmt->close();
$db->close();

$pageTitle = "Languages";
$activePage = "languages";
include 'includes/header.php';
?>

<div class="topbar">
    <h1 class="page-title">🌐 All Languages</h1>
</div>

<div class="card-grid">
    <?php foreach ($langs as $lang):
        $done = $progressByLang[$lang['id']] ?? 0;
        $pct = round(($done / 7) * 100);
    ?>
    <a href="levels.php?lang=<?= $lang['id'] ?>" style="text-decoration:none;">
        <div class="lang-card" style="--lang-color:<?= $lang['color'] ?>;">
            <div class="lang-icon"><?= $lang['icon'] ?></div>
            <div class="lang-name"><?= htmlspecialchars($lang['name']) ?></div>
            <div class="lang-desc"><?= htmlspecialchars($lang['description']) ?></div>
            <div class="lang-progress w-full">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                    <span style="color:var(--text-muted);"><?= $done ?>/7 levels done</span>
                    <span style="color:<?= $lang['color'] ?>;"><?= $pct ?>%</span>
                </div>
                <div class="lang-progress-bar">
                    <div class="lang-progress-fill" style="width:<?= $pct ?>%;background:<?= $lang['color'] ?>;"></div>
                </div>
            </div>
            <?php if ($done === 7): ?>
                <span class="badge bg-primary" style="background:rgba(67,233,123,0.2);color:#43E97B;">✅ Completed!</span>
            <?php elseif ($done > 0): ?>
                <span class="badge" style="background:rgba(108,99,255,0.2);color:var(--primary);">🔥 In Progress</span>
            <?php else: ?>
                <span class="badge" style="background:rgba(255,255,255,0.05);color:var(--text-muted);">Not Started</span>
            <?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
