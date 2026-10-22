<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeQuest - Gamified College Learning</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'config.php'; ?>

<section class="hero">
    <div style="max-width:900px;margin:0 auto;">
        <div style="font-size:60px;margin-bottom:16px;">⚡</div>
        <h1 class="hero-title">Level Up Your<br>Coding Skills</h1>
        <p class="hero-sub">
            Learn programming through gamified lessons. Unlock levels, earn XP, and become placement-ready with structured courses in 8 languages.
        </p>
        <div class="hero-btns">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php" class="btn btn-primary btn-lg">🚀 Go to Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-lg">🎮 Start Learning Free</a>
                <a href="login.php" class="btn btn-outline btn-lg">Login</a>
            <?php endif; ?>
        </div>

        <!-- Features -->
        <div class="features-grid fade-in">
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <div class="feature-title">Level Unlock System</div>
                <div class="feature-desc">Complete each level to unlock the next. Structured learning path.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">XP & Progress</div>
                <div class="feature-desc">Earn XP points, track progress, and see your growth.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💡</div>
                <div class="feature-title">Hints & Solutions</div>
                <div class="feature-desc">Stuck? Get hints. Need the answer? View the solution.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <div class="feature-title">8 Languages</div>
                <div class="feature-desc">C, C++, Java, Python, PHP, JavaScript, HTML & CSS.</div>
            </div>
        </div>

        <!-- Languages strip -->
        <div style="margin-top:48px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">
            <?php
            $langs = [
                ['C', '#A8B9CC'], ['C++', '#F34B7D'], ['Java', '#B07219'], ['Python', '#3572A5'],
                ['PHP', '#4F5D95'], ['JavaScript', '#F1E05A'], ['HTML', '#E34C26'], ['CSS', '#563D7C']
            ];
            foreach ($langs as $l):
            ?>
            <span style="background:<?= $l[1] ?>22;border:1px solid <?= $l[1] ?>44;color:<?= $l[1] ?>;padding:6px 18px;border-radius:20px;font-size:13px;font-weight:700;">
                <?= $l[0] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script src="js/main.js"></script>
</body>
</html>
