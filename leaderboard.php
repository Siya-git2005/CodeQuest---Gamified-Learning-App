<?php
require 'config.php';
requireLogin();

$db = getDB();

// Get top users by XP
$leaderboard = $db->query("
    SELECT u.id, u.full_name, u.username, u.total_xp,
           COUNT(p.id) as levels_done
    FROM users u
    LEFT JOIN progress p ON p.user_id = u.id AND p.completed = 1
    GROUP BY u.id
    ORDER BY u.total_xp DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

$db->close();

$currentUserId = $_SESSION['user_id'];
$pageTitle = "Leaderboard";
$activePage = "leaderboard";
include 'includes/header.php';
?>

<div class="topbar">
    <h1 class="page-title">🏆 Leaderboard</h1>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Student</th>
                <th>Levels Done</th>
                <th>Total XP</th>
                <th>Badge</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard as $i => $row):
                $rank = $i + 1;
                $rankIcon = match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => "#$rank" };
                $isMe = ($row['id'] == $currentUserId);
                $xp = $row['total_xp'];
                $badge = match(true) {
                    $xp >= 3000 => 'Master 👑',
                    $xp >= 2000 => 'Expert 🔥',
                    $xp >= 1000 => 'Advanced ⚡',
                    $xp >= 400  => 'Intermediate 💡',
                    default     => 'Beginner 🌱'
                };
            ?>
            <tr style="<?= $isMe ? 'background:rgba(108,99,255,0.1);' : '' ?>">
                <td style="font-size:20px;text-align:center;"><?= $rankIcon ?></td>
                <td>
                    <div style="font-weight:600;<?= $isMe ? 'color:var(--primary)' : '' ?>">
                        <?= htmlspecialchars($row['full_name']) ?>
                        <?php if ($isMe): ?><span style="font-size:11px;background:var(--primary);color:white;padding:2px 8px;border-radius:10px;margin-left:6px;">You</span><?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);">@<?= htmlspecialchars($row['username']) ?></div>
                </td>
                <td style="color:var(--accent);"><?= $row['levels_done'] ?></td>
                <td>
                    <span class="xp-badge">⚡ <?= number_format($row['total_xp']) ?> XP</span>
                </td>
                <td><?= $badge ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($leaderboard)): ?>
            <tr>
                <td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">
                    No data yet. Start learning to appear here!
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
