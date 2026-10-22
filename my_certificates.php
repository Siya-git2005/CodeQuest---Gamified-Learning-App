<?php
// my_certificates.php - User's earned certificates
require 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if certificates table exists
$certTableExists = $db->query("SHOW TABLES LIKE 'certificates'")->num_rows > 0;
$certificates = [];

if ($certTableExists) {
    $stmt = $db->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY issue_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $certificates[] = $row;
    $stmt->close();
}

$db->close();

$pageTitle = "My Certificates";
$activePage = "certificates";
include 'includes/header.php';
?>

<div class="topbar">
    <div>
        <h1 class="page-title">🎓 My Certificates</h1>
        <p style="color:var(--text-muted);font-size:14px;margin-top:4px;">
            Your earned certificates — complete Level 7 of any language to earn one!
        </p>
    </div>
    <div class="xp-badge" style="font-size:16px;padding:8px 20px;">
        🎓 <?= count($certificates) ?> Earned
    </div>
</div>

<?php if (empty($certificates)): ?>
<div class="card" style="text-align:center;padding:60px 20px;">
    <div style="font-size:60px;margin-bottom:16px;">🎓</div>
    <h2 style="font-size:20px;margin-bottom:8px;">No Certificates Yet</h2>
    <p style="color:var(--text-muted);max-width:400px;margin:0 auto 24px;">
        Complete Level 7 of any programming language to automatically earn your certificate of completion!
    </p>
    <a href="languages.php" class="btn btn-primary">📚 Start Learning</a>
</div>
<?php else: ?>

<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
    <?php foreach ($certificates as $cert): ?>
    <div style="background:var(--bg-card);border:1px solid rgba(255,255,255,0.08);border-radius:12px;
                overflow:hidden;transition:all 0.3s;position:relative;"
         onmouseover="this.style.borderColor='rgba(108,99,255,0.4)';this.style.transform='translateY(-2px)'"
         onmouseout="this.style.borderColor='rgba(255,255,255,0.08)';this.style.transform=''">
        <!-- Top accent -->
        <div style="height:4px;background:linear-gradient(90deg,#6C63FF,#43E97B);"></div>

        <div style="padding:20px;">
            <!-- Language badge -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <span style="background:rgba(108,99,255,0.15);border:1px solid rgba(108,99,255,0.3);
                             color:#6C63FF;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;
                             text-transform:uppercase;letter-spacing:1px;">
                    <?= htmlspecialchars($cert['language']) ?>
                </span>
                <span style="font-size:20px;">🏆</span>
            </div>

            <!-- Certificate title -->
            <div style="font-size:11px;color:var(--text-muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px;">
                Certificate of Completion
            </div>
            <div style="font-weight:700;font-size:16px;margin-bottom:4px;">
                <?= htmlspecialchars($user['full_name']) ?>
            </div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">
                Gamified Learning App
            </div>

            <!-- Meta -->
            <div style="display:flex;justify-content:space-between;margin-bottom:16px;">
                <div>
                    <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;">Issue Date</div>
                    <div style="font-size:12px;font-weight:600;"><?= date('d M Y', strtotime($cert['issue_date'])) ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;">Certificate ID</div>
                    <div style="font-family:monospace;font-size:10px;color:var(--text-muted);">
                        <?= htmlspecialchars($cert['certificate_code']) ?>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div style="display:flex;gap:8px;">
                <a href="certificate.php?code=<?= urlencode($cert['certificate_code']) ?>"
                   class="btn btn-primary btn-sm" style="flex:1;justify-content:center;" target="_blank">
                    👁️ View
                </a>
                <a href="certificate.php?code=<?= urlencode($cert['certificate_code']) ?>"
                   class="btn btn-success btn-sm" style="flex:1;justify-content:center;" target="_blank"
                   onclick="setTimeout(()=>{},500)">
                    ⬇️ Download
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Summary card -->
<div class="card mt-3">
    <h3 style="font-weight:700;margin-bottom:16px;">📊 Your Progress Summary</h3>
    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <div style="text-align:center;flex:1;min-width:120px;">
            <div style="font-size:32px;font-weight:700;color:#6C63FF;"><?= count($certificates) ?></div>
            <div style="font-size:13px;color:var(--text-muted);">Certificates Earned</div>
        </div>
        <div style="text-align:center;flex:1;min-width:120px;">
            <div style="font-size:32px;font-weight:700;color:#43E97B;"><?= count($certificates) * 7 ?></div>
            <div style="font-size:13px;color:var(--text-muted);">Levels Completed</div>
        </div>
        <div style="text-align:center;flex:1;min-width:120px;">
            <div style="font-size:32px;font-weight:700;color:#FFD700;"><?= number_format($user['total_xp']) ?></div>
            <div style="font-size:13px;color:var(--text-muted);">Total XP Earned</div>
        </div>
    </div>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.08);">
        <a href="languages.php" class="btn btn-outline btn-sm">📚 Continue Learning</a>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
