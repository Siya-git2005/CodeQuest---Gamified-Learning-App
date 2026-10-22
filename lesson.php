<?php
require 'config.php';
requireLogin();

$langId  = (int)($_GET['lang'] ?? 0);
$levelId = (int)($_GET['level'] ?? 0);

if (!$langId || !$levelId) {
    header("Location: languages.php");
    exit();
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Get language and level info
$stmt = $db->prepare("SELECT * FROM languages WHERE id = ?");
$stmt->bind_param("i", $langId);
$stmt->execute();
$lang = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare("SELECT * FROM levels WHERE id = ?");
$stmt->bind_param("i", $levelId);
$stmt->execute();
$level = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lang || !$level) {
    header("Location: languages.php");
    exit();
}

// Check if level is unlocked
$levelNum = $level['level_number'];
if ($levelNum > 1) {
    // Find previous level ID
    $stmt = $db->prepare("SELECT id FROM levels WHERE level_number = ?");
    $prevNum = $levelNum - 1;
    $stmt->bind_param("i", $prevNum);
    $stmt->execute();
    $prevLevel = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($prevLevel) {
        $stmt = $db->prepare("SELECT completed FROM progress WHERE user_id = ? AND language_id = ? AND level_id = ?");
        $stmt->bind_param("iii", $userId, $langId, $prevLevel['id']);
        $stmt->execute();
        $prevProgress = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prevProgress || !$prevProgress['completed']) {
            header("Location: levels.php?lang=$langId");
            exit();
        }
    }
}

// Get lesson for this language + level
$stmt = $db->prepare("SELECT * FROM lessons WHERE language_id = ? AND level_id = ?");
$stmt->bind_param("ii", $langId, $levelId);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's completion status
$stmt = $db->prepare("SELECT * FROM progress WHERE user_id = ? AND language_id = ? AND level_id = ?");
$stmt->bind_param("iii", $userId, $langId, $levelId);
$stmt->execute();
$userProgress = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isCompleted = $userProgress && $userProgress['completed'];
$levelComplete = false;

// Handle "Mark as Complete"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    if (!$isCompleted) {
        // Insert or update progress
        $stmt = $db->prepare("INSERT INTO progress (user_id, language_id, level_id, completed, completed_at, xp_earned) 
            VALUES (?, ?, ?, 1, NOW(), ?) 
            ON DUPLICATE KEY UPDATE completed=1, completed_at=NOW(), xp_earned=?");
        $xp = $level['xp_reward'];
        $stmt->bind_param("iiiii", $userId, $langId, $levelId, $xp, $xp);
        $stmt->execute();
        $stmt->close();

        // Update user total XP
        $stmt = $db->prepare("UPDATE users SET total_xp = total_xp + ? WHERE id = ?");
        $stmt->bind_param("ii", $xp, $userId);
        $stmt->execute();
        $stmt->close();

        $isCompleted = true;
        $levelComplete = true;
    }
}

// Get next level
$nextLevelNum = $levelNum + 1;
$stmt = $db->prepare("SELECT id FROM levels WHERE level_number = ?");
$stmt->bind_param("i", $nextLevelNum);
$stmt->execute();
$nextLevel = $stmt->get_result()->fetch_assoc();
$stmt->close();

$db->close();

$pageTitle = $lang['name'] . " - Level " . $levelNum;
$activePage = "languages";
include 'includes/header.php';
?>

<?php if ($levelComplete): ?>
<div id="level-complete-message"></div>
<div class="alert alert-success" style="display:flex;align-items:center;gap:12px;font-size:16px;">
    🎉 <strong>Level <?= $levelNum ?> Complete!</strong> You earned <strong><?= $level['xp_reward'] ?> XP</strong>!
    <?php if ($nextLevel): ?>
        <a href="lesson.php?lang=<?= $langId ?>&level=<?= $nextLevel['id'] ?>" class="btn btn-success btn-sm" style="margin-left:auto;">Next Level →</a>
    <?php else: ?>
        <span style="margin-left:auto;color:var(--gold);">🏆 Language Completed!</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);margin-bottom:20px;flex-wrap:wrap;">
    <a href="dashboard.php" style="color:var(--text-muted);">Home</a> /
    <a href="levels.php?lang=<?= $langId ?>" style="color:var(--text-muted);"><?= $lang['name'] ?></a> /
    <span style="color:var(--text-main);">Level <?= $levelNum ?>: <?= htmlspecialchars($level['title']) ?></span>

    <?php if ($isCompleted): ?>
        <span class="level-badge badge-completed" style="margin-left:auto;">✅ Completed</span>
    <?php endif; ?>
</div>

<?php if (!$lesson): ?>
    <div class="card text-center">
        <div style="font-size:48px;margin-bottom:16px;">📝</div>
        <h2>Lesson coming soon!</h2>
        <p style="color:var(--text-muted);margin-top:8px;">Content for this level is being prepared.</p>
        <a href="levels.php?lang=<?= $langId ?>" class="btn btn-outline mt-2">← Back to Levels</a>
    </div>
<?php else: ?>

<div class="lesson-layout">
    <!-- Left: Lesson Content -->
    <div class="lesson-content-box">
        <div class="lesson-tabs">
            <button class="lesson-tab active" data-tab="theory">📖 Theory</button>
            <button class="lesson-tab" data-tab="syntax">✏️ Syntax</button>
            <button class="lesson-tab" data-tab="example">💻 Example</button>
            <button class="lesson-tab" data-tab="practice">🎯 Practice</button>
        </div>

        <!-- Theory Tab -->
        <div class="lesson-tab-content active" id="tab-theory">
            <h2 style="font-size:22px;font-weight:800;margin-bottom:16px;"><?= htmlspecialchars($lesson['title']) ?></h2>
            <div style="color:var(--text-muted);line-height:1.8;font-size:15px;">
                <?= nl2br(htmlspecialchars($lesson['theory'])) ?>
            </div>
        </div>

        <!-- Syntax Tab -->
        <div class="lesson-tab-content" id="tab-syntax">
            <h3 style="font-weight:700;margin-bottom:16px;">📝 Syntax</h3>
            <div class="code-block" style="position:relative;">
                <button class="copy-btn">Copy</button>
                <pre><?= htmlspecialchars($lesson['syntax_example']) ?></pre>
            </div>
        </div>

        <!-- Example Tab -->
        <div class="lesson-tab-content" id="tab-example">
            <h3 style="font-weight:700;margin-bottom:16px;">💡 Complete Code Example</h3>
            <div class="code-block" style="position:relative;">
                <button class="copy-btn">Copy</button>
                <pre><?= htmlspecialchars($lesson['code_example']) ?></pre>
            </div>
        </div>

        <!-- Practice Tab -->
        <div class="lesson-tab-content" id="tab-practice">
            <h3 style="font-weight:700;margin-bottom:12px;">🎯 Practice Problem</h3>

            <div style="background:rgba(108,99,255,0.1);border:1px solid rgba(108,99,255,0.25);border-radius:8px;padding:16px;margin-bottom:20px;">
                <p style="font-weight:600;margin-bottom:6px;">📋 Problem:</p>
                <p style="color:var(--text-muted);"><?= nl2br(htmlspecialchars($lesson['practice_problem'])) ?></p>
            </div>

            <label style="font-size:14px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:8px;">✍️ Your Code:</label>
            <textarea class="code-editor" id="code-editor" placeholder="Write your code here..."></textarea>

            <!-- ── Compiler Input Area (shown automatically when input() etc is detected) ── -->
            <div id="compiler-input-area" style="display:none;margin-top:14px;">
                <label class="compiler-input-label">
                    <span>⌨️ Program Input <span style="font-size:12px;font-weight:400;color:var(--text-muted);">(one value per line)</span></span>
                    <span id="input-detected-badge" class="input-badge">Input detected</span>
                </label>
                <textarea id="compiler-stdin"
                    class="compiler-stdin-area"
                    placeholder="Enter input values here, one per line&#10;Example:&#10;5&#10;hello world"></textarea>
            </div>

            <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;align-items:center;">
                <button class="btn btn-run" id="run-btn" onclick="runCode()">▶ Run Code</button>
                <button class="btn" id="ai-eval-btn" onclick="aiEvaluate()" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;font-weight:600;">🤖 AI Evaluate</button>
                <button class="btn btn-warning" id="hint-btn">💡 Show Hint</button>
                <button class="btn btn-outline" id="solution-btn">🔑 Show Solution</button>
            </div>

            <!-- ── Terminal Output Console ── -->
            <div id="compiler-output-wrapper" style="display:none;margin-top:18px;">
                <div class="terminal-header">
                    <div class="terminal-dots">
                        <span class="dot dot-red"></span>
                        <span class="dot dot-yellow"></span>
                        <span class="dot dot-green"></span>
                    </div>
                    <span class="terminal-title">Output Console</span>
                    <button class="terminal-clear-btn" onclick="clearTerminal()">✕ Clear</button>
                </div>
                <div class="terminal-body" id="compiler-output">
                    <span class="terminal-placeholder">Run your code to see output here…</span>
                </div>
            </div>

            <!-- AI Evaluate feedback box -->
            <div id="ai-feedback-box" style="display:none;margin-top:16px;padding:18px 20px;background:rgba(124,58,237,0.1);border:1px solid rgba(124,58,237,0.35);border-radius:10px;font-size:14px;line-height:1.75;color:var(--text-main);white-space:pre-wrap;font-family:inherit;">
                <div style="font-weight:700;font-size:13px;letter-spacing:0.5px;color:#a78bfa;margin-bottom:10px;">🤖 AI FEEDBACK</div>
                <div id="ai-feedback-content"></div>
            </div>

            <!-- Hint -->
            <div class="hint-box" id="hint-box">
                <div class="hint-label">💡 HINT:</div>
                <p><?= htmlspecialchars($lesson['hint']) ?></p>
            </div>

            <!-- Solution -->
            <div class="solution-box" id="solution-box">
                <div class="sol-label">✅ SOLUTION:</div>
                <div class="code-block" style="position:relative;margin-top:8px;">
                    <button class="copy-btn">Copy</button>
                    <pre><?= htmlspecialchars($lesson['solution']) ?></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Info Panel -->
    <div>
        <!-- Level info card -->
        <div class="card mb-2">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="font-size:36px;"><?= $lang['icon'] ?></div>
                <div>
                    <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($lang['name']) ?></div>
                    <div style="color:var(--text-muted);font-size:13px;">Level <?= $levelNum ?> of 7</div>
                </div>
            </div>

            <!-- Level progress -->
            <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                    <span style="color:var(--text-muted);">Level Progress</span>
                    <span style="color:<?= $lang['color'] ?>;"><?= $levelNum ?>/7</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" data-width="<?= ($levelNum/7)*100 ?>%"></div>
                </div>
            </div>

            <div class="xp-badge w-full" style="justify-content:center;font-size:14px;padding:10px;">
                🏆 Complete to Earn <?= $level['xp_reward'] ?> XP
            </div>
        </div>

        <!-- Navigation -->
        <div class="card mb-2">
            <h4 style="font-weight:700;margin-bottom:14px;">📑 Level Topics</h4>
            <?php
            $allLevels = [
                1 => 'Programming Basics',
                2 => 'Variables & Data Types',
                3 => 'Control Flow',
                4 => 'Loops',
                5 => 'Functions',
                6 => 'Data Structures',
                7 => 'Interview Prep',
            ];
            foreach ($allLevels as $num => $title):
            ?>
            <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;">
                <?php if ($num == $levelNum): ?>
                    <span style="color:var(--primary);">▶</span>
                    <span style="color:var(--primary);font-weight:600;">Level <?= $num ?>: <?= $title ?></span>
                <?php else: ?>
                    <span style="color:var(--text-muted);">◦</span>
                    <span style="color:var(--text-muted);">Level <?= $num ?>: <?= $title ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Complete button -->
        <?php if (!$isCompleted): ?>
        <form method="POST" id="complete-form">
            <button type="submit" name="mark_complete" id="mark-complete-btn"
                disabled
                style="width:100%;opacity:0.45;cursor:not-allowed;filter:grayscale(40%);transition:all 0.4s ease;"
                class="btn btn-success btn-block btn-lg">
                🔒 Mark Level as Complete
            </button>
        </form>

        <!-- Tab progress tracker -->
        <div id="tab-progress-wrap" style="margin-top:10px;">
            <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-bottom:6px;">
                Read all tabs to unlock:
            </p>
            <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap;">
                <span id="tp-theory"  class="tab-pill">📖 Theory</span>
                <span id="tp-syntax"  class="tab-pill">✏️ Syntax</span>
                <span id="tp-example" class="tab-pill">💻 Example</span>
                <span id="tp-practice"class="tab-pill">🎯 Practice</span>
            </div>
        </div>

        <style>
        .tab-pill {
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 20px;
            background: rgba(255,255,255,0.06);
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .tab-pill.done {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            border-color: rgba(34,197,94,0.4);
        }
        .tab-pill.done::before { content: "✔ "; }
        </style>
        <?php else: ?>
        <div class="btn btn-outline btn-block" style="cursor:default;justify-content:center;">✅ Level Completed!</div>
        <?php if ($nextLevel): ?>
        <a href="lesson.php?lang=<?= $langId ?>&level=<?= $nextLevel['id'] ?>" class="btn btn-primary btn-block mt-1">
            ⚡ Next Level →
        </a>
        <?php else: ?>
        <div style="text-align:center;margin-top:12px;color:var(--gold);font-weight:700;">🏆 All Levels Done!</div>
        <a href="levels.php?lang=<?= $langId ?>" class="btn btn-outline btn-block mt-1">← Back to Levels</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// ── Language slug for this lesson (derived from the URL / lang name) ──
const LESSON_LANG = <?= json_encode(strtolower($lang['name'])) ?>;

// ── Input-detection patterns per language ──
const INPUT_PATTERNS = {
    python:     /\binput\s*\(/,
    javascript: /\binput\s*\(/,
    c:          /\bscanf\s*\(|\bgets\s*\(|\bfgets\s*\(/,
    cpp:        /\bcin\s*>>|\bscanf\s*\(/,
    'c++':      /\bcin\s*>>|\bscanf\s*\(/,
    java:       /\bScanner\b|\bBufferedReader\b|\bSystem\.in\b/,
};

// Detect if code uses input functions and show/hide the stdin textarea
function detectInputUsage() {
    const code    = document.getElementById('code-editor').value;
    const pattern = INPUT_PATTERNS[LESSON_LANG] || INPUT_PATTERNS['python'];
    const area    = document.getElementById('compiler-input-area');
    if (area) {
        area.style.display = pattern.test(code) ? 'block' : 'none';
    }
}

// Run code via compiler.php — OR show live preview for HTML/CSS
async function runCode() {
    const code   = document.getElementById('code-editor').value.trim();
    const stdin  = (document.getElementById('compiler-stdin')?.value) ?? '';
    const runBtn = document.getElementById('run-btn');
    const wrapper = document.getElementById('compiler-output-wrapper');
    const output  = document.getElementById('compiler-output');

    if (!code) { alert('Please write some code first!'); return; }

    // ── HTML / CSS: show live browser preview instead of compiler ──
    if (['html', 'css'].includes(LESSON_LANG)) {
        wrapper.style.display = 'block';

        // Build a full HTML document for CSS-only code
        let previewHTML = code;
        if (LESSON_LANG === 'css') {
            previewHTML = `<!DOCTYPE html>
<html>
<head>
<style>${code}</style>
</head>
<body style="background:#1a1a2e;color:#fff;font-family:sans-serif;padding:20px;">
  <nav class="navbar">
    <div class="logo">🚀 Logo</div>
    <ul class="nav-links" style="list-style:none;display:flex;gap:20px;margin:0;padding:0;">
      <li><a href="#" style="color:#fff;text-decoration:none;">Home</a></li>
      <li><a href="#" style="color:#fff;text-decoration:none;">About</a></li>
      <li><a href="#" style="color:#fff;text-decoration:none;">Contact</a></li>
    </ul>
  </nav>
  <div class="container" style="margin-top:20px;">
    <div class="card"><p>Sample Card Content</p></div>
    <div class="box"><p>Sample Box</p></div>
    <button class="btn">Click Me</button>
  </div>
</body>
</html>`;
        }

        // Replace terminal with an iframe preview
        output.innerHTML = `
            <div style="background:#1e1b4b;padding:8px 12px;font-size:12px;color:#a78bfa;border-bottom:1px solid rgba(255,255,255,0.1);">
                🌐 Live Preview
                <span style="float:right;font-size:11px;color:#6b7280;">HTML/CSS renders in browser — no compiler needed</span>
            </div>
            <iframe id="preview-frame"
                style="width:100%;height:320px;border:none;background:#fff;"
                sandbox="allow-scripts allow-same-origin">
            </iframe>
            <div style="padding:6px 12px;background:rgba(34,197,94,0.1);border-top:1px solid rgba(34,197,94,0.2);">
                <span class="terminal-exit terminal-exit-ok">✔ Preview Rendered</span>
            </div>`;

        // Write HTML into iframe
        const iframe = document.getElementById('preview-frame');
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(previewHTML);
        doc.close();
        return;
    }

    // ── All other languages: send to compiler.php as before ──
    wrapper.style.display = 'block';
    output.innerHTML = '<span class="terminal-running"><span class="terminal-spinner"></span> Running…</span>';
    runBtn.disabled  = true;
    runBtn.innerHTML = '⏳ Running…';

    try {
        const res = await fetch('compiler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code, language: LESSON_LANG, stdin }),
        });
        const data = await res.json();
        renderTerminalOutput(data.output, data.success, data.exitCode);
    } catch (err) {
        renderTerminalOutput('Network error: could not reach the compiler.\n' + err.message, false, 1);
    } finally {
        runBtn.disabled  = false;
        runBtn.innerHTML = '▶ Run Code';
    }
}

// Render output in the terminal panel
function renderTerminalOutput(raw, success, exitCode) {
    const output = document.getElementById('compiler-output');
    if (!output) return;

    // Escape HTML
    const escaped = raw
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // Colour error lines
    const coloured = escaped.split('\n').map(line => {
        const lower = line.toLowerCase();
        if (/^(traceback|error|exception|compilation error|runtime error|time limit)/i.test(line) ||
            lower.includes('error:') || lower.includes('exception:') || lower.includes('fatal')) {
            return `<span class="terminal-error">${line}</span>`;
        }
        if (/^(warning:|note:)/i.test(line)) {
            return `<span class="terminal-warning">${line}</span>`;
        }
        return line;
    }).join('\n');

    const badge = success
        ? `<span class="terminal-exit terminal-exit-ok">✔ Exit 0</span>`
        : `<span class="terminal-exit terminal-exit-err">✘ Exit ${exitCode}</span>`;

    output.innerHTML = `<div class="terminal-output-text">${coloured}</div>${badge}`;
    output.scrollTop = output.scrollHeight;
}

function clearTerminal() {
    const output  = document.getElementById('compiler-output');
    const wrapper = document.getElementById('compiler-output-wrapper');
    if (output)  output.innerHTML = '<span class="terminal-placeholder">Run your code to see output here…</span>';
    if (wrapper) wrapper.style.display = 'none';
}

// ══════════════════════════════════════════════════════════
// 🤖 AI EVALUATE — Rule-based feedback (no API key needed)
// Ported from ai_feedback.py
// ══════════════════════════════════════════════════════════
function aiEvaluate() {
    const code    = document.getElementById('code-editor').value.trim();
    const output  = document.getElementById('compiler-output')?.innerText ?? '';
    const problem = <?= json_encode($lesson['practice_problem'] ?? '') ?>;
    const lang    = LESSON_LANG;

    if (!code) { alert('Please write some code first!'); return; }

    const btn     = document.getElementById('ai-eval-btn');
    const box     = document.getElementById('ai-feedback-box');
    const content = document.getElementById('ai-feedback-content');

    btn.disabled    = true;
    btn.textContent = '⏳ Analysing…';
    box.style.display = 'block';
    content.textContent = 'Thinking…';

    // Small delay so UI updates before heavy work
    setTimeout(() => {
        const feedback = generateAIFeedback(code, output, lang, problem);
        content.textContent = feedback;
        btn.disabled    = false;
        btn.textContent = '🤖 AI Evaluate';
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 300);
}

function generateAIFeedback(code, terminalOutput, language, problem) {
    const out  = terminalOutput || '';
    const codeL = code.toLowerCase();
    const probL = problem.toLowerCase();

    // ── Language tips ──
    const langTips = {
        python:     "💬 Python Tip: Remember Python uses indentation instead of brackets. Use 4 spaces consistently.",
        java:       "💬 Java Tip: Your class must be named 'Main'. Use System.out.println() for output.",
        c:          "💬 C Tip: Don't forget #include <stdio.h> and return 0 in main().",
        cpp:        "💬 C++ Tip: Use #include <iostream> and 'using namespace std;' for cout/cin.",
        'c++':      "💬 C++ Tip: Use #include <iostream> and 'using namespace std;' for cout/cin.",
        javascript: "💬 JS Tip: Use console.log() for output. Check for undefined variables.",
        php:        "💬 PHP Tip: Every statement must end with a semicolon (;). Start your file with <?php",
        html:       "💬 HTML Tip: Make sure all tags are properly opened and closed. Use valid attribute names.",
        css:        "💬 CSS Tip: Every property must end with a semicolon (;). Check that selectors match your HTML elements.",
    };

    // ── Compile error patterns (checked on terminal output) ──
    const compilePatterns = [
        [/expected\s*[';']/i,                        "🔴 Missing semicolon (;) at end of a statement. Check all your lines ending with a statement."],
        [/expected\s*'\)'|expected\s*'\('/i,          "🔴 Missing bracket. Check all your opening '(' and closing ')' brackets are balanced."],
        [/expected\s*'}'|expected\s*'{'/i,            "🔴 Missing curly brace. Check all your '{' and '}' braces are balanced."],
        [/undeclared\s+identifier|was not declared/i, "🔴 You used a variable or function that was never declared. Declare it before using it."],
        [/undefined reference to/i,                  "🔴 Undefined reference: A function you called doesn't exist or wasn't included."],
        [/implicit declaration of function/i,         "🔴 You called a function before declaring it. Add the correct #include at the top."],
        [/too few arguments|too many arguments/i,     "🔴 Wrong number of arguments in a function call. Check the function definition."],
        [/incompatible types|cannot convert/i,        "🔴 Type mismatch. You are assigning or using a value of the wrong data type."],
        [/redefinition of|already defined/i,          "🔴 You defined the same variable or function twice. Remove or rename the duplicate."],
        [/cannot find symbol/i,                       "🔴 Java cannot find a variable, method, or class. Check spelling and declaration."],
        [/reached end of file while parsing/i,        "🔴 Java: Missing closing '}'. Count your opening and closing curly braces."],
        [/illegal start of expression/i,              "🔴 Something is in the wrong place. Check for extra brackets, braces, or symbols."],
        [/unclosed string literal/i,                  "🔴 A string is not closed. Every opening quote \" needs a closing quote \"."],
        [/IndentationError|unexpected indent/i,       "🔴 Indentation Error: Python requires consistent spacing — use 4 spaces consistently."],
        [/SyntaxError: invalid syntax/i,              "🔴 Syntax Error: Something is written incorrectly. Check for missing colons (:) or brackets."],
        [/NameError: name '(.+)' is not defined/i,   "🔴 NameError: A variable or function is used before it is defined. Check spelling."],
        [/Parse error: syntax error/i,                "🔴 PHP Syntax Error: Check for missing semicolons (;), mismatched brackets, or unclosed strings."],
        [/compilation error|error:/i,                 "🔴 Compilation failed. Read the error message — it shows the exact line and problem."],
    ];

    // ── Runtime error patterns ──
    const runtimePatterns = [
        [/division by zero|ZeroDivisionError|Floating point exception/i, "⚠️ Division by Zero: You are dividing by 0. Add a check before dividing."],
        [/IndexError|ArrayIndexOutOfBoundsException|index out of range/i,"⚠️ Index Out of Range: You are accessing an array position that doesn't exist. Check your loop limits."],
        [/NullPointerException|null pointer|NoneType/i,                  "⚠️ Null/None Error: You are using a variable that is null/None. Make sure it is initialized."],
        [/Segmentation fault|SIGSEGV/i,                                  "⚠️ Segmentation Fault: Your program accessed memory it shouldn't. Check array bounds and pointers."],
        [/StackOverflowError|maximum recursion depth|RecursionError/i,   "⚠️ Stack Overflow: Infinite recursion detected. Make sure your recursive function has a base case."],
        [/ValueError/i,                                                   "⚠️ Value Error: You passed an incorrect value to a function. Check the type and range of your input."],
        [/TypeError/i,                                                    "⚠️ Type Error: Wrong type used. Check if you need to convert between int, string, or float."],
        [/RuntimeException|Exception in thread/i,                        "⚠️ Runtime Exception: An unexpected error occurred. Read the error details carefully."],
        [/time limit exceeded|TLE/i,                                      "⚠️ Time Limit Exceeded: Check for infinite loops — make sure your loop has a stopping condition."],
    ];

    const sep   = "═══════════════════════════════════";
    const tip   = langTips[language] || '';
    const lines = [];

    // ════════════════════════════════════════════════════
    // STEP 1 — Check terminal output for errors (if run)
    // ════════════════════════════════════════════════════
    const hasCompileError = /compilation error|error:|parse error|syntaxerror|indentationerror|nameerror/i.test(out)
                         && !/exit 0|✔\s*exit 0/i.test(out);
    const hasRuntimeError = /runtime error|segmentation fault|traceback \(most recent|exception in thread/i.test(out)
                         && !/exit 0|✔\s*exit 0/i.test(out);
    const ranSuccessfully = /✔\s*exit 0|exit 0/i.test(out);

    if (hasCompileError) {
        lines.push(sep);
        lines.push("🤖 AI FEEDBACK — COMPILE ERROR DETECTED");
        lines.push(sep);
        let matched = false;
        for (const [pattern, msg] of compilePatterns) {
            if (pattern.test(out)) { lines.push(msg); matched = true; break; }
        }
        if (!matched) {
            lines.push("🔴 Compilation failed. Read the error message above carefully.");
        }
        lines.push('');
        lines.push("📋 How to Fix:");
        lines.push("   1. Find the LINE NUMBER in the error message above.");
        lines.push("   2. Go to that line in your code.");
        lines.push("   3. Fix the issue and click Run Code again.");
        if (tip) { lines.push(''); lines.push(tip); }
        return lines.join('\n');
    }

    if (hasRuntimeError) {
        lines.push(sep);
        lines.push("🤖 AI FEEDBACK — RUNTIME ERROR DETECTED");
        lines.push(sep);
        lines.push("✅ Your code compiled successfully.");
        lines.push("❌ But it crashed while running.\n");
        let matched = false;
        for (const [pattern, msg] of runtimePatterns) {
            if (pattern.test(out)) { lines.push(msg); matched = true; break; }
        }
        if (!matched) {
            lines.push("⚠️ Your program crashed unexpectedly. Read the error message carefully.");
        }
        lines.push('');
        lines.push("📋 General Runtime Checks:");
        lines.push("   → Make sure all array/list accesses are within bounds.");
        lines.push("   → Check that all variables are initialized before use.");
        lines.push("   → Verify that user input is handled correctly.");
        return lines.join('\n');
    }

    // ════════════════════════════════════════════════════
    // STEP 2 — Static code analysis (works WITHOUT running)
    // ════════════════════════════════════════════════════
    const staticIssues  = [];
    const staticPraises = [];
    const codeLines     = code.split('\n').filter(l => l.trim()).length;

    // ── Check: code too short ──
    if (codeLines < 3) {
        staticIssues.push("   → Your code is very short. Make sure you have written a complete solution.");
    }

    // ── Check: output statement (not needed for CSS/HTML) ──
    const hasOutput = /cout\s*<<|printf\s*\(|puts\s*\(|print\s*\(|console\.log\s*\(|System\.out\.print/i.test(code);
    if (!['css','html'].includes(language) && !hasOutput) {
        staticIssues.push("   → No output statement found. Add cout / printf / print to display your result.");
    } else if (!['css','html'].includes(language) && hasOutput) {
        staticPraises.push("   ✔ Has an output statement (cout/print).");
    }

    // ── Check: if/else when problem needs conditions (not for CSS/HTML) ──
    const needsCondition = !['css','html'].includes(language) &&
        /if|condition|check|when|greater|less|equal|compare|between|temp|weather|hot|cold|warm|cool/i.test(probL);
    const hasIfElse      = /\bif\s*\(|\bif\s+/i.test(code);
    if (needsCondition) {
        if (!hasIfElse) {
            staticIssues.push("   → The problem requires conditions (if/else) but none found in your code.");
        } else {
            staticPraises.push("   ✔ Has if/else conditions.");
            const hasElseIf = /else\s+if\s*\(/i.test(code);
            const condCount = (probL.match(/→|->|>=|<=|>|</g) || []).length;
            if (condCount > 2 && !hasElseIf) {
                staticIssues.push("   → Problem has multiple conditions — make sure you used 'else if' for each case.");
            }
        }
    }

    // ── Check: loops when problem needs them (not for CSS/HTML) ──
    const needsLoop = !['css','html'].includes(language) &&
        /loop|repeat|each|every|all|sum|count|times|sequence|series|1 to|n times/i.test(probL);
    const hasLoop   = /\bfor\s*\(|\bwhile\s*\(|\bfor\s+/i.test(code);
    if (needsLoop) {
        if (!hasLoop) {
            staticIssues.push("   → The problem requires a loop but none was found. Add a for or while loop.");
        } else {
            staticPraises.push("   ✔ Has a loop (for/while).");
        }
    }

    // ── Check: function when problem asks for one ──
    const needsFunc = /function|method|define|create a function|write a function/i.test(probL);
    const hasFunc   = /\bvoid\b|\bint\b.*\(|\breturn\b/i.test(code);
    if (needsFunc && !hasFunc) {
        staticIssues.push("   → The problem asks you to write a function but none was found.");
    }

    // ── Language-specific static checks ──

    // ── CSS specific ──
    if (language === 'css') {
        // Check for missing semicolons (lines with : but no ;)
        const cssLines = code.split('\n');
        let missingSemicolon = false;
        cssLines.forEach(line => {
            const trimmed = line.trim();
            if (trimmed.includes(':') && !trimmed.endsWith(';') && !trimmed.endsWith('{') && !trimmed.endsWith('}') && trimmed !== '') {
                missingSemicolon = true;
            }
        });
        if (missingSemicolon) staticIssues.push("   → Some CSS properties may be missing a semicolon (;) at the end.");

        // Check for opening/closing braces balance
        const openBraces  = (code.match(/\{/g) || []).length;
        const closeBraces = (code.match(/\}/g) || []).length;
        if (openBraces !== closeBraces) {
            staticIssues.push(`   → Unbalanced curly braces: found ${openBraces} opening '{' and ${closeBraces} closing '}'. Make sure they match.`);
        } else {
            staticPraises.push("   ✔ Curly braces are balanced.");
        }

        // Check problem keywords vs code
        if (/flexbox|flex/i.test(probL) && !/display\s*:\s*flex/i.test(code))
            staticIssues.push("   → Problem asks for flexbox but 'display: flex' was not found in your code.");
        if (/grid/i.test(probL) && !/display\s*:\s*grid/i.test(code))
            staticIssues.push("   → Problem asks for CSS grid but 'display: grid' was not found.");
        if (/center/i.test(probL) && !/center/i.test(code))
            staticIssues.push("   → Problem asks for centering — try 'justify-content: center' or 'align-items: center'.");
        if (/color|colour/i.test(probL) && !/color\s*:/i.test(code))
            staticIssues.push("   → Problem mentions color but no 'color:' property was found.");
        if (/padding/i.test(probL) && !/padding\s*:/i.test(code))
            staticIssues.push("   → Problem mentions padding but no 'padding:' property was found.");
        if (/margin/i.test(probL) && !/margin\s*:/i.test(code))
            staticIssues.push("   → Problem mentions margin but no 'margin:' property was found.");

        if (staticIssues.length === 0) staticPraises.push("   ✔ CSS structure and properties look correct.");
    }

    // ── HTML specific ──
    else if (language === 'html') {
        // Check tag balance for common tags
        const tagsToCheck = ['div','p','ul','ol','li','table','tr','td','th','form','section','header','nav','footer'];
        tagsToCheck.forEach(tag => {
            const opens  = (code.match(new RegExp(`<${tag}[\\s>]`, 'gi')) || []).length;
            const closes = (code.match(new RegExp(`</${tag}>`, 'gi')) || []).length;
            if (opens !== closes) {
                staticIssues.push(`   → Unbalanced <${tag}> tags: ${opens} opening, ${closes} closing.`);
            }
        });
        if (!/<!DOCTYPE html>/i.test(code))
            staticIssues.push("   → Missing '<!DOCTYPE html>' at the top of your HTML file.");
        if (!/<html/i.test(code))
            staticIssues.push("   → Missing <html> tag.");
        if (!/<body/i.test(code))
            staticIssues.push("   → Missing <body> tag.");
        if (staticIssues.length === 0) staticPraises.push("   ✔ HTML structure looks well-formed.");
    }

    else if (['cpp','c++'].includes(language)) {
        if (!/\bint\s+main\s*\(/i.test(code))
            staticIssues.push("   → Missing 'int main()' — every C++ program needs a main function.");
        if (!/iostream|stdio\.h/i.test(code))
            staticIssues.push("   → Missing #include. Add '#include <iostream>' at the top.");
        if (!/using\s+namespace\s+std/i.test(code) && /cout|cin/i.test(code))
            staticIssues.push("   → Add 'using namespace std;' after your #include to use cout/cin.");
    }
    if (language === 'c') {
        if (!/stdio\.h/i.test(code))
            staticIssues.push("   → Missing '#include <stdio.h>' at the top of your C program.");
        if (!/\bint\s+main\s*\(/i.test(code))
            staticIssues.push("   → Missing 'int main()' — every C program needs a main function.");
    }
    if (language === 'python') {
        const indentOk = code.split('\n').every(l => !l.startsWith('\t'));
        if (!indentOk)
            staticIssues.push("   → Avoid tab indentation in Python. Use 4 spaces consistently.");
    }
    if (language === 'java') {
        if (!/class\s+Main/i.test(code))
            staticIssues.push("   → Java class must be named 'Main' exactly.");
        if (!/public\s+static\s+void\s+main/i.test(code))
            staticIssues.push("   → Missing 'public static void main(String[] args)' method.");
    }

    // ════════════════════════════════════════════════════
    // STEP 3 — Build final feedback message
    // ════════════════════════════════════════════════════
    if (staticIssues.length > 0) {
        lines.push(sep);
        lines.push("🤖 AI FEEDBACK — ISSUES FOUND");
        lines.push(sep);
        if (ranSuccessfully) {
            lines.push("✅ Your code ran, but the logic needs improvement:");
        } else {
            lines.push("📝 Code analysis (without running):");
        }
        lines.push('');

        if (staticPraises.length > 0) {
            lines.push("✅ What looks good:");
            lines.push(...staticPraises);
            lines.push('');
        }

        lines.push("💡 Things to fix:");
        lines.push(...staticIssues);
        lines.push('');
        lines.push("📋 Fix these issues, then click ▶ Run Code to test your solution.");
        if (tip) { lines.push(''); lines.push(tip); }

    } else {
        lines.push(sep);
        lines.push("🤖 AI FEEDBACK — LOOKS GREAT! 🎉");
        lines.push(sep);

        if (ranSuccessfully || ['css','html'].includes(language)) {
            lines.push("🎉 Your code ran successfully and looks correct!");
        } else {
            lines.push("📝 Your code structure looks correct!");
            lines.push("▶ Click Run Code to preview it in the browser.");
        }
        lines.push('');
        if (staticPraises.length > 0) {
            lines.push("✅ What's good:");
            lines.push(...staticPraises);
            lines.push('');
        }
        lines.push("🏆 Once it runs correctly, click ✅ Submit to earn your XP!");
        lines.push('');
        lines.push("👏 Great work — keep it up!");
    }

    return lines.join('\n');
}

// Wire up input detection on code editor changes
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.getElementById('code-editor');
    if (editor) {
        editor.addEventListener('input', detectInputUsage);
        editor.addEventListener('paste', () => setTimeout(detectInputUsage, 50));
    }

    // ══════════════════════════════════════════════
    // TAB VISIT TRACKER — unlocks Mark as Complete
    // ══════════════════════════════════════════════
    const REQUIRED_TABS = ['theory', 'syntax', 'example', 'practice'];
    const visitedTabs   = new Set(['theory']); // Theory is shown by default on load

    // Mark theory pill as done immediately (it's the default active tab)
    markPillDone('theory');

    function markPillDone(tabName) {
        const pill = document.getElementById('tp-' + tabName);
        if (pill) pill.classList.add('done');
    }

    function checkAllTabsVisited() {
        const allDone = REQUIRED_TABS.every(t => visitedTabs.has(t));
        if (allDone) unlockCompleteButton();
    }

    function unlockCompleteButton() {
        const btn  = document.getElementById('mark-complete-btn');
        const wrap = document.getElementById('tab-progress-wrap');
        if (!btn) return;

        btn.disabled = false;
        btn.textContent = '✅ Mark Level as Complete';
        btn.style.opacity  = '1';
        btn.style.cursor   = 'pointer';
        btn.style.filter   = 'none';

        // Subtle pulse animation to draw attention
        btn.animate([
            { transform: 'scale(1)',    boxShadow: '0 0 0px rgba(34,197,94,0)' },
            { transform: 'scale(1.03)', boxShadow: '0 0 16px rgba(34,197,94,0.5)' },
            { transform: 'scale(1)',    boxShadow: '0 0 0px rgba(34,197,94,0)' },
        ], { duration: 700, iterations: 2 });

        // Hide the pill tracker after unlock
        if (wrap) {
            wrap.innerHTML = '<p style="font-size:12px;color:#4ade80;text-align:center;margin-top:6px;">🎉 All tabs read! You can now complete this level.</p>';
        }
    }

    // Hook into existing tab switching — intercept all lesson-tab clicks
    document.querySelectorAll('.lesson-tab').forEach(tabBtn => {
        tabBtn.addEventListener('click', function () {
            const tabName = this.getAttribute('data-tab');
            if (tabName && !visitedTabs.has(tabName)) {
                visitedTabs.add(tabName);
                markPillDone(tabName);
                checkAllTabsVisited();
            }
        });
    });
}); // end DOMContentLoaded
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
