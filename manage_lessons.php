<?php
// admin/manage_lessons.php
// GamifiedLearningApp - Admin Panel
// Manage Lessons + Test Cases in one place
require_once '../config.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error   = '';

$languages = $db->query("SELECT * FROM languages ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$levels    = $db->query("SELECT * FROM levels ORDER BY level_number")->fetch_all(MYSQLI_ASSOC);

// ---- DELETE LESSON ----
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM lessons WHERE id=?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute() ? $message = "Lesson deleted." : $error = "Delete failed.";
    $stmt->close();
}

// ---- SAVE LESSON (Add or Edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lesson'])) {
    $langId   = (int)$_POST['language_id'];
    $levelId  = (int)$_POST['level_id'];
    $title    = trim($_POST['title']            ?? '');
    $theory   = trim($_POST['theory']           ?? '');
    $syntax   = trim($_POST['syntax_example']   ?? '');
    $code     = trim($_POST['code_example']     ?? '');
    $practice = trim($_POST['practice_problem'] ?? '');
    $hint     = trim($_POST['hint']             ?? '');
    $solution = trim($_POST['solution']         ?? '');
    $editId   = (int)($_POST['edit_id']         ?? 0);

    if (empty($title) || empty($theory)) {
        $error = "Title and Theory are required fields.";
    } else {
        if ($editId > 0) {
            $stmt = $db->prepare("UPDATE lessons SET language_id=?,level_id=?,title=?,theory=?,syntax_example=?,code_example=?,practice_problem=?,hint=?,solution=? WHERE id=?");
            $stmt->bind_param("iisssssssi", $langId,$levelId,$title,$theory,$syntax,$code,$practice,$hint,$solution,$editId);
        } else {
            $stmt = $db->prepare("INSERT INTO lessons (language_id,level_id,title,theory,syntax_example,code_example,practice_problem,hint,solution) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iisssssss", $langId,$levelId,$title,$theory,$syntax,$code,$practice,$hint,$solution);
        }
        if ($stmt->execute()) {
            $lessonId = $editId > 0 ? $editId : $db->insert_id;
            $message  = $editId ? "✅ Lesson updated successfully!" : "✅ Lesson added successfully!";

            // ---- Save Test Cases ----
            if (!empty($_POST['tc_expected']) && is_array($_POST['tc_expected'])) {
                // Delete old test cases for this lesson's lang+level
                $stmt2 = $db->prepare("DELETE FROM test_cases WHERE language_id=? AND level_id=?");
                $stmt2->bind_param("ii", $langId, $levelId);
                $stmt2->execute();
                $stmt2->close();

                // Insert new test cases
                $tcExpected = $_POST['tc_expected'];
                $tcInput    = $_POST['tc_input'] ?? [];
                $order      = 1;
                foreach ($tcExpected as $i => $exp) {
                    $exp = trim($exp);
                    if ($exp === '') continue;
                    $inp = trim($tcInput[$i] ?? '');
                    $stmt3 = $db->prepare("INSERT INTO test_cases (language_id,level_id,input_data,expected_output,sort_order) VALUES (?,?,?,?,?)");
                    $stmt3->bind_param("iissi", $langId,$levelId,$inp,$exp,$order);
                    $stmt3->execute();
                    $stmt3->close();
                    $order++;
                }
                $message .= " Test cases saved.";
            }
        } else {
            $error = "Save failed: " . $db->error;
        }
        $stmt->close();
    }
}

// ---- LOAD LESSON FOR EDITING ----
$editLesson   = null;
$editTestCases = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM lessons WHERE id=?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editLesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editLesson) {
        // Load existing test cases for this lang+level
        $stmt = $db->prepare("SELECT * FROM test_cases WHERE language_id=? AND level_id=? ORDER BY sort_order");
        $stmt->bind_param("ii", $editLesson['language_id'], $editLesson['level_id']);
        $stmt->execute();
        $editTestCases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ---- FETCH ALL LESSONS ----
$filterLang = (int)($_GET['lang'] ?? 0);
if ($filterLang) {
    $stmt = $db->prepare("SELECT ls.*, l.name as lang_name, lv.title as level_title, lv.level_number FROM lessons ls JOIN languages l ON l.id=ls.language_id JOIN levels lv ON lv.id=ls.level_id WHERE ls.language_id=? ORDER BY lv.level_number");
    $stmt->bind_param("i", $filterLang);
    $stmt->execute();
    $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $lessons = $db->query("SELECT ls.*, l.name as lang_name, lv.title as level_title, lv.level_number FROM lessons ls JOIN languages l ON l.id=ls.language_id JOIN levels lv ON lv.id=ls.level_id ORDER BY l.name, lv.level_number")->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lessons - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .tc-row { display:grid; grid-template-columns:1fr 1.5fr auto; gap:10px; align-items:start; margin-bottom:10px; }
        .tc-row input, .tc-row textarea { width:100%; font-family:monospace; font-size:13px; padding:8px 10px; background:rgba(0,0,0,0.25); border:1px solid var(--border); border-radius:6px; color:var(--text-primary); resize:vertical; }
        .tc-row textarea { min-height:60px; }
        .btn-remove-tc { background:rgba(255,80,80,0.15); border:1px solid rgba(255,80,80,0.3); color:#ff6b6b; border-radius:6px; padding:6px 12px; cursor:pointer; font-size:13px; white-space:nowrap; }
        .btn-remove-tc:hover { background:rgba(255,80,80,0.3); }
        .btn-add-tc { background:rgba(108,99,255,0.15); border:1px solid rgba(108,99,255,0.3); color:var(--primary); border-radius:8px; padding:8px 18px; cursor:pointer; font-size:13px; margin-top:4px; }
        .btn-add-tc:hover { background:rgba(108,99,255,0.3); }
        .tc-section { background:rgba(0,0,0,0.2); border:1px solid var(--border); border-radius:10px; padding:20px; margin-top:8px; }
        .tc-header-row { display:grid; grid-template-columns:1fr 1.5fr auto; gap:10px; margin-bottom:8px; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; }
        .section-label { font-size:13px; font-weight:700; color:var(--primary); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .tab-btns { display:flex; gap:8px; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:12px; }
        .tab-btn { background:transparent; border:1px solid var(--border); color:var(--text-muted); padding:6px 16px; border-radius:6px; cursor:pointer; font-size:13px; transition:all 0.2s; }
        .tab-btn.active { background:var(--primary); border-color:var(--primary); color:#fff; }
        .form-section { display:none; }
        .form-section.active { display:block; }
        .code-textarea { font-family: 'Courier New', monospace; font-size:13px; background:rgba(0,0,0,0.3); color:#a8ff78; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar admin-nav" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-text">🛡️ Admin Panel</div>
        </div>
        <nav class="sidebar-nav"><ul>
            <li><a href="admin_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="manage_lessons.php" class="active"><span class="icon">📚</span> Manage Lessons</a></li>
            <li><a href="manage_users.php"><span class="icon">👥</span> Users</a></li>
            <li><a href="view_progress.php"><span class="icon">📈</span> Progress</a></li>
            <li style="margin-top:16px;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;">
                <a href="admin_logout.php" style="color:#FF6584 !important;"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul></nav>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">📚 Manage Lessons</h1>
            <a href="?action=add" class="btn btn-primary">➕ Add New Lesson</a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ============================
             ADD / EDIT FORM
             ============================ -->
        <?php if (isset($_GET['action']) || $editLesson): ?>
        <div class="card mb-3">
            <h3 style="font-weight:700;margin-bottom:20px;">
                <?= $editLesson ? '✏️ Edit Lesson: ' . htmlspecialchars($editLesson['title']) : '➕ Add New Lesson' ?>
            </h3>

            <!-- Tab Navigation -->
            <div class="tab-btns">
                <button class="tab-btn active" onclick="showTab('basic')">📝 Basic Info</button>
                <button class="tab-btn" onclick="showTab('content')">📖 Theory & Code</button>
                <button class="tab-btn" onclick="showTab('practice')">🎯 Practice & Test Cases</button>
            </div>

            <form method="POST">
                <?php if ($editLesson): ?>
                <input type="hidden" name="edit_id" value="<?= $editLesson['id'] ?>">
                <?php endif; ?>
                <input type="hidden" name="save_lesson" value="1">

                <!-- TAB 1: Basic Info -->
                <div class="form-section active" id="tab-basic">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">🌐 Language *</label>
                            <select name="language_id" class="form-control" required>
                                <option value="">Select Language</option>
                                <?php foreach ($languages as $l): ?>
                                <option value="<?= $l['id'] ?>" <?= ($editLesson && $editLesson['language_id'] == $l['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">📊 Level *</label>
                            <select name="level_id" class="form-control" required>
                                <option value="">Select Level</option>
                                <?php foreach ($levels as $lv): ?>
                                <option value="<?= $lv['id'] ?>" <?= ($editLesson && $editLesson['level_id'] == $lv['id']) ? 'selected' : '' ?>>
                                    Level <?= $lv['level_number'] ?>: <?= htmlspecialchars($lv['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">📌 Lesson Title *</label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="e.g. Python Basics, Java Loops..."
                               value="<?= htmlspecialchars($editLesson['title'] ?? '') ?>">
                    </div>
                    <div style="display:flex;gap:12px;margin-top:8px;">
                        <button type="button" class="btn btn-primary" onclick="showTab('content')">Next: Theory & Code →</button>
                        <a href="manage_lessons.php" class="btn btn-outline">Cancel</a>
                    </div>
                </div>

                <!-- TAB 2: Theory & Code -->
                <div class="form-section" id="tab-content">
                    <div class="form-group">
                        <label class="form-label">📖 Theory / Explanation *</label>
                        <small style="color:var(--text-muted);display:block;margin-bottom:6px;">Explain the concept clearly. Students will read this before practicing.</small>
                        <textarea name="theory" class="form-control" rows="6" required
                                  placeholder="Explain the concept here..."><?= htmlspecialchars($editLesson['theory'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">💡 Syntax Example</label>
                        <small style="color:var(--text-muted);display:block;margin-bottom:6px;">Show the basic syntax format.</small>
                        <textarea name="syntax_example" class="form-control code-textarea" rows="4"
                                  placeholder="e.g. for(int i=0; i<n; i++) { ... }"><?= htmlspecialchars($editLesson['syntax_example'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">💻 Code Example</label>
                        <small style="color:var(--text-muted);display:block;margin-bottom:6px;">A working code example students can study.</small>
                        <textarea name="code_example" class="form-control code-textarea" rows="7"
                                  placeholder="Write a complete working code example..."><?= htmlspecialchars($editLesson['code_example'] ?? '') ?></textarea>
                    </div>
                    <div style="display:flex;gap:12px;margin-top:8px;">
                        <button type="button" class="btn btn-primary" onclick="showTab('practice')">Next: Practice & Test Cases →</button>
                        <button type="button" class="btn btn-outline" onclick="showTab('basic')">← Back</button>
                    </div>
                </div>

                <!-- TAB 3: Practice & Test Cases -->
                <div class="form-section" id="tab-practice">

                    <!-- Practice Problem -->
                    <div class="form-group">
                        <label class="form-label">🎯 Practice Problem Statement</label>
                        <small style="color:var(--text-muted);display:block;margin-bottom:6px;">
                            Write a clear problem. Include Sample Input/Output so students know exactly what to print.
                        </small>
                        <textarea name="practice_problem" class="form-control" rows="5"
                                  placeholder="e.g. Write a Python program to print exactly:\nHello, World!\n\nSample Input: (none)\nSample Output: Hello, World!"><?= htmlspecialchars($editLesson['practice_problem'] ?? '') ?></textarea>
                    </div>

                    <!-- Hint -->
                    <div class="form-group">
                        <label class="form-label">💡 Hint</label>
                        <input type="text" name="hint" class="form-control"
                               placeholder="Give a small hint without revealing full solution..."
                               value="<?= htmlspecialchars($editLesson['hint'] ?? '') ?>">
                    </div>

                    <!-- Solution -->
                    <div class="form-group">
                        <label class="form-label">✅ Solution Code</label>
                        <small style="color:var(--text-muted);display:block;margin-bottom:6px;">The complete correct solution. Shown when student clicks "Show Solution".</small>
                        <textarea name="solution" class="form-control code-textarea" rows="7"
                                  placeholder="Write the complete working solution here..."><?= htmlspecialchars($editLesson['solution'] ?? '') ?></textarea>
                    </div>

                    <!-- ===== TEST CASES ===== -->
                    <div class="tc-section">
                        <div class="section-label">🧪 Test Cases
                            <span style="font-size:11px;color:var(--text-muted);font-weight:400;">
                                — Student's output must match Expected Output exactly to pass
                            </span>
                        </div>
                        <div style="background:rgba(255,193,7,0.08);border:1px solid rgba(255,193,7,0.2);border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#ffc107;">
                            ⚠️ <strong>Important:</strong> Test cases apply to ALL lessons of the same Language + Level.
                            If you change test cases here, it affects all students on this Language + Level.
                        </div>

                        <div class="tc-header-row">
                            <span>📥 Input (leave blank if no input needed)</span>
                            <span>✅ Expected Output (must match exactly)</span>
                            <span></span>
                        </div>

                        <div id="tc-container">
                            <?php if (!empty($editTestCases)): ?>
                                <?php foreach ($editTestCases as $i => $tc): ?>
                                <div class="tc-row">
                                    <input type="text" name="tc_input[]"
                                           placeholder="e.g. 5  or  10 50 30  or leave blank"
                                           value="<?= htmlspecialchars($tc['input_data']) ?>">
                                    <textarea name="tc_expected[]"
                                              placeholder="e.g. Hello, World!  or  Even  or  120"><?= htmlspecialchars($tc['expected_output']) ?></textarea>
                                    <button type="button" class="btn-remove-tc" onclick="removeTc(this)">✕ Remove</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div id="tc-empty-msg" style="text-align:center;padding:24px;color:var(--text-muted);font-size:13px;background:rgba(108,99,255,0.05);border-radius:8px;border:1px dashed var(--border);">
                                    ✅ <strong>Test cases not needed!</strong><br>
                                    System automatically generates and verifies test cases based on level logic.<br>
                                    <span style="font-size:11px;margin-top:6px;display:block;opacity:0.7;">You can still add custom test cases using the button below if needed.</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="btn-add-tc" onclick="addTc()">➕ Add Test Case</button>

                        <div style="margin-top:16px;padding:12px;background:rgba(0,0,0,0.2);border-radius:6px;font-size:12px;color:var(--text-muted);">
                            <strong>📋 Examples:</strong><br>
                            Level 1 (Hello World): Input = <em>blank</em> | Expected = <code>Hello, World!</code><br>
                            Level 3 (Even/Odd): Input = <code>4</code> | Expected = <code>Even</code><br>
                            Level 4 (Sum): Input = <code>5</code> | Expected = <code>15</code><br>
                            Level 5 (Factorial): Input = <code>5</code> | Expected = <code>120</code>
                        </div>
                    </div>

                    <div style="display:flex;gap:12px;margin-top:20px;">
                        <button type="submit" class="btn btn-primary" style="padding:12px 32px;font-size:15px;">
                            💾 <?= $editLesson ? 'Update Lesson' : 'Save Lesson' ?>
                        </button>
                        <button type="button" class="btn btn-outline" onclick="showTab('content')">← Back</button>
                        <a href="manage_lessons.php" class="btn btn-outline">Cancel</a>
                    </div>
                </div>

            </form>
        </div>
        <?php endif; ?>

        <!-- ============================
             FILTER BAR
             ============================ -->
        <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:14px;color:var(--text-muted);font-weight:600;">Filter:</span>
            <a href="manage_lessons.php" class="btn btn-sm <?= !$filterLang ? 'btn-primary' : 'btn-outline' ?>">All Languages</a>
            <?php foreach ($languages as $l): ?>
            <a href="?lang=<?= $l['id'] ?>" class="btn btn-sm <?= $filterLang == $l['id'] ? 'btn-primary' : 'btn-outline' ?>">
                <?= htmlspecialchars($l['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- ============================
             LESSONS TABLE
             ============================ -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Language</th>
                        <th>Level</th>
                        <th>Title</th>
                        <th>Practice Problem</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessons as $les): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($les['lang_name']) ?></strong></td>
                        <td style="color:var(--text-muted);white-space:nowrap;">
                            Lv.<?= $les['level_number'] ?> — <?= htmlspecialchars($les['level_title']) ?>
                        </td>
                        <td><?= htmlspecialchars($les['title']) ?></td>
                        <td style="font-size:12px;color:var(--text-muted);max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= htmlspecialchars(substr($les['practice_problem'] ?? 'No problem set', 0, 80)) ?>
                            <?= strlen($les['practice_problem'] ?? '') > 80 ? '...' : '' ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="?edit=<?= $les['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                            <a href="?delete=<?= $les['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete lesson: <?= htmlspecialchars(addslashes($les['title'])) ?>?')">
                                🗑️ Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lessons)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px;">
                            No lessons found. <a href="?action=add">Add one?</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;font-size:13px;color:var(--text-muted);">
            Total: <strong><?= count($lessons) ?></strong> lessons
        </div>
    </main>
</div>

<script src="../js/main.js"></script>
<script>
// ---- Tab switching ----
function showTab(tab) {
    document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    const btns = document.querySelectorAll('.tab-btn');
    const map = { basic: 0, content: 1, practice: 2 };
    if (btns[map[tab]]) btns[map[tab]].classList.add('active');
}

// ---- Add test case row ----
function addTc() {
    const container = document.getElementById('tc-container');
    const row = document.createElement('div');
    row.className = 'tc-row';
    row.innerHTML = `
        <input type="text" name="tc_input[]" placeholder="e.g. 5  or leave blank">
        <textarea name="tc_expected[]" placeholder="Expected output here..."></textarea>
        <button type="button" class="btn-remove-tc" onclick="removeTc(this)">✕ Remove</button>
    `;
    container.appendChild(row);
    row.querySelector('input').focus();
}

// ---- Remove test case row ----
function removeTc(btn) {
    const row = btn.closest('.tc-row');
    row.remove();
}
</script>
</body>
</html>
