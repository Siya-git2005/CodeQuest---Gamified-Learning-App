<?php
require 'config.php';
requireLogin();

$langId = (int)($_GET['lang'] ?? 0);
if (!$langId) {
    header("Location: languages.php");
    exit();
}

$db = getDB();

// Get language info
$stmt = $db->prepare("SELECT * FROM languages WHERE id = ?");
$stmt->bind_param("i", $langId);
$stmt->execute();
$lang = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lang) {
    header("Location: languages.php");
    exit();
}

// Get all levels - cast all numeric fields to int
$levelsRaw = $db->query("SELECT * FROM levels ORDER BY level_number")->fetch_all(MYSQLI_ASSOC);
$levels = array_map(function($lv) {
    $lv['id'] = (int)$lv['id'];
    $lv['level_number'] = (int)$lv['level_number'];
    $lv['xp_reward'] = (int)$lv['xp_reward'];
    return $lv;
}, $levelsRaw);

// Get user progress for this language
$userId = $_SESSION['user_id'];
$progressMap = [];
$stmt = $db->prepare("SELECT level_id, completed FROM progress WHERE user_id = ? AND language_id = ?");
$stmt->bind_param("ii", $userId, $langId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $progressMap[$row['level_id']] = $row['completed'];
}
$stmt->close();
$db->close();

// Check if a level has a lesson for this language
function hasLesson($db, $langId, $levelId) {
    $stmt = $db->prepare("SELECT id FROM lessons WHERE language_id = ? AND level_id = ?");
    $stmt->bind_param("ii", $langId, $levelId);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

$pageTitle = $lang['name'] . " Levels";
$activePage = "languages";
include 'includes/header.php';
?>

<div class="topbar">
    <div>
        <a href="languages.php" style="color:var(--text-muted);font-size:14px;">← Back to Languages</a>
        <h1 class="page-title" style="margin-top:4px;"><?= $lang['icon'] ?> <?= htmlspecialchars($lang['name']) ?> Path</h1>
    </div>
</div>

<!-- Overall progress bar -->
<?php
$doneCount = array_sum($progressMap);
$totalLevels = count($levels);
$pct = $totalLevels > 0 ? round(($doneCount / $totalLevels) * 100) : 0;
?>
<div class="card mb-3">
    <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
        <span style="font-weight:600;">Overall Progress</span>
        <span style="color:<?= $lang['color'] ?>;font-weight:700;"><?= $doneCount ?>/<?= $totalLevels ?> Levels</span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" data-width="<?= $pct ?>%"></div>
    </div>
</div>

<!-- Levels Grid -->
<div class="levels-grid">
    <?php foreach ($levels as $level):
        $levelNum = (int)$level['level_number'];
        $levelId  = (int)$level['id'];
        $isCompleted = ($progressMap[$levelId] ?? 0) == 1;

        // Find previous level's ID by level_number
        $prevLevelId = null;
        foreach ($levels as $lv) {
            if ((int)$lv['level_number'] === ($levelNum - 1)) {
                $prevLevelId = (int)$lv['id'];
                break;
            }
        }

        // Level 1 is always unlocked; others unlock when previous is completed
        $isUnlocked = ($levelNum === 1) || ($prevLevelId !== null && ($progressMap[$prevLevelId] ?? 0) == 1);
        $cardClass = $isCompleted ? 'completed unlocked' : ($isUnlocked ? 'unlocked' : 'locked');
    ?>
    <div class="level-card <?= $cardClass ?>"
        <?php if ($isUnlocked): ?>
        onclick="window.location='lesson.php?lang=<?= $langId ?>&level=<?= $levelId ?>'"
        <?php endif; ?>>

        <div class="level-number">Level <?= $levelNum ?></div>
        <div class="level-title"><?= htmlspecialchars($level['title']) ?></div>
        <div class="level-desc"><?= htmlspecialchars($level['description']) ?></div>

        <div style="display:flex;align-items:center;justify-content:space-between;">
            <?php if ($isCompleted): ?>
                <span class="level-badge badge-completed">✅ Completed</span>
            <?php elseif ($isUnlocked): ?>
                <span class="level-badge badge-unlocked">🔓 Start Learning →</span>
            <?php else: ?>
                <span class="level-badge badge-locked">🔒 Complete Level <?= $levelNum - 1 ?> First</span>
            <?php endif; ?>
            <span class="xp-badge">+<?= $level['xp_reward'] ?> XP</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
