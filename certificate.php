<?php
// certificate.php - View & Download Certificate
require 'config.php';

$db = getDB();

$cert = null;
$certCode = isset($_GET['code']) ? sanitize($_GET['code']) : '';
$certId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($certCode) {
    $stmt = $db->prepare("SELECT c.*, u.full_name, u.email FROM certificates c JOIN users u ON u.id = c.user_id WHERE c.certificate_code = ?");
    $stmt->bind_param("s", $certCode);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($certId) {
    $stmt = $db->prepare("SELECT c.*, u.full_name, u.email FROM certificates c JOIN users u ON u.id = c.user_id WHERE c.id = ?");
    $stmt->bind_param("i", $certId);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$isAdmin = isAdminLoggedIn();
$isUser  = isLoggedIn();

if (!$isAdmin && !$isUser) {
    header("Location: login.php");
    exit();
}

if ($isUser && !$isAdmin && $cert && $cert['user_id'] != $_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit();
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion - Gamified Learning App</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #0a0a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 16px;
            color: #fff;
        }
        .action-bar {
            width: 100%;
            max-width: 950px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .back-link { color: #C9A84C; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #f0c060; }
        .btn-group { display: flex; gap: 10px; }
        .btn {
            padding: 10px 22px; border-radius: 6px; font-size: 14px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
        }
        .btn-gold { background: linear-gradient(135deg, #C9A84C, #f0c060, #C9A84C); color: #1a1a2e; }
        .btn-gold:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(201,168,76,0.4); }
        .btn-outline { background: transparent; color: #C9A84C; border: 1px solid #C9A84C; }
        .btn-outline:hover { background: rgba(201,168,76,0.1); }

        .certificate-wrapper { width: 100%; max-width: 950px; }

        #certificate {
            width: 100%;
            aspect-ratio: 1.414 / 1;
            position: relative;
            overflow: hidden;
            border-radius: 6px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.8), 0 0 0 2px rgba(201,168,76,0.3);
            background: #f0f4f8;
        }

        .cert-bg-base {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, #e8edf5 0%, #f5f7fa 40%, #eef1f7 100%);
        }
        .cert-wave-top {
            position: absolute; top: 0; right: 0; width: 42%; height: 100%;
            background: linear-gradient(160deg, #0d1b4b 0%, #1a2f6b 40%, #0d1b4b 100%);
            clip-path: polygon(25% 0%, 100% 0%, 100% 100%, 0% 100%);
        }
        .cert-wave-gold {
            position: absolute; top: 0; right: 0; width: 44%; height: 100%;
            background: linear-gradient(160deg, #C9A84C 0%, #f0c060 30%, #C9A84C 60%, #8B6914 100%);
            clip-path: polygon(27% 0%, 29% 0%, 4% 100%, 2% 100%);
        }
        .cert-wave-gold2 {
            position: absolute; top: 0; right: 0; width: 44%; height: 100%;
            background: linear-gradient(160deg, #f0c060 0%, #C9A84C 50%, #f0c060 100%);
            clip-path: polygon(31% 0%, 33% 0%, 8% 100%, 6% 100%);
            opacity: 0.5;
        }
        .cert-lines {
            position: absolute; inset: 0;
            background-image: repeating-linear-gradient(-55deg, transparent, transparent 30px, rgba(201,168,76,0.04) 30px, rgba(201,168,76,0.04) 31px);
        }
        .cert-border {
            position: absolute; inset: 10px;
            border: 1.5px solid rgba(201,168,76,0.35);
            pointer-events: none; z-index: 10;
        }

        .cert-left {
            position: absolute; top: 0; left: 0; width: 62%; height: 100%;
            display: flex; flex-direction: column; justify-content: center;
            padding: clamp(16px,3.5%,45px) clamp(18px,4.5%,50px);
            padding-bottom: clamp(30px,6%,60px);
            z-index: 5;
        }
        .cert-platform-row { display: flex; align-items: center; gap: 7px; margin-bottom: clamp(6px,1.2%,14px); }
        .cert-platform-icon { font-size: clamp(14px,2.2vw,20px); }
        .cert-platform-name {
            font-size: clamp(7px,1vw,11px); font-weight: 700;
            letter-spacing: 3px; text-transform: uppercase; color: #1a2f6b;
        }
        .cert-gold-line {
            width: clamp(28px,4.5vw,55px); height: 2px;
            background: linear-gradient(90deg, #C9A84C, #f0c060);
            margin-bottom: clamp(6px,1.2%,14px);
        }
        .cert-recognizes {
            font-family: Georgia, serif; font-size: clamp(9px,1.4vw,15px);
            color: #444; line-height: 1.7; margin-bottom: clamp(3px,0.6%,7px);
        }
        .cert-student-name {
            font-family: Georgia, serif; font-size: clamp(18px,3.2vw,38px);
            font-weight: 700; color: #0d1b4b; line-height: 1.1;
            margin-bottom: clamp(5px,0.9%,10px); letter-spacing: 0.5px;
        }
        .cert-name-line {
            width: clamp(90px,16vw,190px); height: 1px;
            background: linear-gradient(90deg, #C9A84C, transparent);
            margin-bottom: clamp(6px,1.2%,14px);
        }
        .cert-for-completing {
            font-family: Georgia, serif; font-size: clamp(9px,1.3vw,14px);
            color: #444; margin-bottom: clamp(3px,0.5%,6px);
        }
        .cert-course-name {
            font-family: Georgia, serif; font-size: clamp(12px,1.8vw,21px);
            font-weight: 600; color: #0d1b4b; line-height: 1.3;
            margin-bottom: clamp(4px,0.7%,9px);
        }
        .cert-congrats {
            font-family: Georgia, serif; font-size: clamp(8px,1.2vw,13px);
            color: #555; line-height: 1.6; margin-bottom: clamp(8px,1.8%,18px);
        }
        .cert-sig-text {
            font-family: Georgia, serif; font-style: italic;
            font-size: clamp(11px,1.7vw,19px); color: #0d1b4b; letter-spacing: 1px;
        }
        .cert-sig-underline {
            width: clamp(75px,11vw,125px); height: 1px;
            background: #333; margin-bottom: 3px;
        }
        .cert-sig-label {
            font-size: clamp(6px,0.85vw,9px); font-weight: 700;
            letter-spacing: 3px; text-transform: uppercase; color: #888;
        }
        .cert-bottom-meta {
            position: absolute;
            bottom: clamp(14px,2.5%,28px); left: clamp(18px,4.5%,50px);
            display: flex; gap: clamp(20px,3.5%,40px); z-index: 5;
        }
        .cert-bottom-item .label {
            font-size: clamp(8px,1vw,11px); color: #777;
            text-transform: uppercase; letter-spacing: 1.5px;
            margin-bottom: 2px;
        }
        .cert-bottom-item .value {
            font-family: monospace; font-size: clamp(9px,1.1vw,13px);
            color: #333; font-weight: 700;
        }

        .cert-right {
            position: absolute; top: 0; right: 0; width: 38%; height: 100%;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            z-index: 5; padding: 16px 12px; gap: clamp(8px,1.5%,16px);
        }
        .cert-title-block { text-align: right; width: 100%; padding-right: clamp(8px,1.8%,18px); }
        .cert-title-main {
            font-family: 'Georgia', serif; font-size: clamp(14px,2.5vw,30px);
            font-weight: 900; color: #C9A84C; letter-spacing: 2px;
            text-transform: uppercase; line-height: 1;
            text-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }
        .cert-title-of {
            font-family: Georgia, serif; font-size: clamp(8px,1.2vw,14px);
            font-weight: 400; color: #e8d5a3; letter-spacing: 5px;
            text-transform: uppercase; margin-top: 3px;
        }

        .cert-medal {
            width: clamp(65px,10.5vw,115px); height: clamp(65px,10.5vw,115px);
            position: relative; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .cert-medal::before {
            content: ''; position: absolute; bottom: -6px; left: 50%;
            transform: translateX(-50%); width: 30%; height: 28%;
            background: linear-gradient(180deg, #C9A84C, #8B6914);
            clip-path: polygon(20% 0%, 80% 0%, 100% 100%, 50% 75%, 0% 100%);
        }
        .cert-medal-stars {
            position: absolute; inset: -6px; border-radius: 50%;
            border: 2px dashed rgba(201,168,76,0.5);
        }
        .cert-medal-outer {
            width: 100%; height: 100%; border-radius: 50%;
            background: linear-gradient(135deg, #f0c060 0%, #C9A84C 30%, #8B6914 60%, #C9A84C 80%, #f0c060 100%);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 3px rgba(240,192,96,0.3), 0 8px 25px rgba(0,0,0,0.5), inset 0 2px 4px rgba(255,255,255,0.3);
            position: relative; z-index: 2;
        }
        .cert-medal-inner {
            width: 72%; height: 72%; border-radius: 50%;
            background: linear-gradient(135deg, #1a2f6b, #0d1b4b);
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 2px;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
        }
        .cert-medal-icon { font-size: clamp(16px,2.8vw,30px); line-height: 1; }
        .cert-medal-stars-row { display: flex; gap: 1px; font-size: clamp(5px,0.8vw,9px); color: #C9A84C; }

        .cert-meta-block { text-align: center; margin-top: 6px; }
        .cert-meta-label {
            font-size: clamp(5px,0.75vw,8px); color: rgba(232,213,163,0.7);
            letter-spacing: 2px; text-transform: uppercase; margin-bottom: 2px;
        }
        .cert-meta-value { font-family: monospace; font-size: clamp(6px,0.85vw,10px); color: #e8d5a3; font-weight: 600; }

        .not-found { text-align: center; padding: 80px 20px; }
        .not-found h2 { color: #C9A84C; font-size: 24px; margin-bottom: 12px; }
        .not-found p { color: #888; }

        @media print {
            body { background: #fff; padding: 0; display: block; }
            .action-bar { display: none !important; }
            .certificate-wrapper { max-width: 100%; }
            #certificate { box-shadow: none; width: 100vw; }
        }
    </style>
</head>
<body>

<?php if ($cert): ?>

<div class="action-bar">
    <a href="<?= $isAdmin ? 'admin/certificates.php' : 'dashboard.php' ?>" class="back-link">
        ← Back to <?= $isAdmin ? 'Certificate Management' : 'Dashboard' ?>
    </a>
    <div class="btn-group">
        <button class="btn btn-gold" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <button class="btn btn-outline" onclick="downloadCert()">⬇️ Download</button>
    </div>
</div>

<div class="certificate-wrapper">
    <div id="certificate">
        <div class="cert-bg-base"></div>
        <div class="cert-wave-top"></div>
        <div class="cert-wave-gold"></div>
        <div class="cert-wave-gold2"></div>
        <div class="cert-lines"></div>
        <div class="cert-border"></div>

        <!-- LEFT SIDE -->
        <div class="cert-left">
            <div class="cert-platform-row">
                <span class="cert-platform-icon">⚡</span>
                <span class="cert-platform-name">Gamified Learning App</span>
            </div>
            <div class="cert-gold-line"></div>
            <div class="cert-recognizes">
                Gamified Learning App recognizes the<br>hard-work and dedication of
            </div>
            <div class="cert-student-name"><?= htmlspecialchars($cert['full_name']) ?></div>
            <div class="cert-name-line"></div>
            <div class="cert-for-completing">for successfully completing the</div>
            <div class="cert-course-name">"<?= htmlspecialchars($cert['language']) ?> Programming Language"</div>
            <div class="cert-congrats">
                on <?= date('F d, Y', strtotime($cert['issue_date'])) ?>. Gamified Learning App congratulates<br>you for your accomplishment.
            </div>
            <div>
                <div class="cert-sig-text">Sakshi Shrivas</div>
                <div class="cert-sig-underline"></div>
                <div class="cert-sig-label">Signature &nbsp;|&nbsp; Head of Platform</div>
            </div>
        </div>

        <!-- Bottom meta -->
        <div class="cert-bottom-meta">
            <div class="cert-bottom-item">
                <div class="label">Certificate ID</div>
                <div class="value"><?= htmlspecialchars($cert['certificate_code']) ?></div>
            </div>
            <div class="cert-bottom-item">
                <div class="label">Issue Date</div>
                <div class="value"><?= date('d M Y', strtotime($cert['issue_date'])) ?></div>
            </div>
        </div>

        <!-- RIGHT SIDE (navy) -->
        <div class="cert-right">
            <div class="cert-title-block">
                <div class="cert-title-main">Certificate</div>
                <div class="cert-title-of">of Completion</div>
            </div>
            <div class="cert-medal">
                <div class="cert-medal-stars"></div>
                <div class="cert-medal-outer">
                    <div class="cert-medal-inner">
                        <div class="cert-medal-icon">⚡</div>
                        <div class="cert-medal-stars-row">★ ★ ★ ★ ★</div>
                    </div>
                </div>
            </div>
            <div class="cert-meta-block">
                <div class="cert-meta-label">Language</div>
                <div class="cert-meta-value"><?= htmlspecialchars($cert['language']) ?></div>
            </div>
            <div class="cert-meta-block">
                <div class="cert-meta-label">Verify Certificate</div>
                <div class="cert-meta-value" style="font-size:clamp(5px,0.7vw,8px);opacity:0.8;">
                    localhost/GamifiedLearningApp/<br>certificate.php?code=<?= htmlspecialchars($cert['certificate_code']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadCert() {
    const orig = document.title;
    document.title = 'Certificate_<?= htmlspecialchars($cert['certificate_code']) ?>';
    window.print();
    document.title = orig;
}
</script>

<?php else: ?>
<div class="not-found">
    <div style="font-size:64px;margin-bottom:20px;">🎓</div>
    <h2>Certificate Not Found</h2>
    <p>This certificate does not exist or has been removed.</p>
    <br>
    <a href="<?= $isAdmin ? 'admin/certificates.php' : 'dashboard.php' ?>" style="color:#C9A84C;text-decoration:none;">← Go Back</a>
</div>
<?php endif; ?>

</body>
</html>
