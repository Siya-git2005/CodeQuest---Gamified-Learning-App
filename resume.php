<?php
require 'config.php';
requireLogin();

$db     = getDB();
$userId = $_SESSION['user_id'];

// ── Load user info ────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Load completed languages (for skills section) ─────────
$stmt = $db->prepare("
    SELECT l.name, l.icon, COUNT(p.id) as levels_done
    FROM languages l
    INNER JOIN progress p ON p.language_id = l.id AND p.user_id = ? AND p.completed = 1
    GROUP BY l.id
    HAVING levels_done > 0
    ORDER BY levels_done DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$completedLangs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

// Skill level badge based on levels done
function skillLevel($done) {
    if ($done >= 7) return ['Expert', '#43E97B'];
    if ($done >= 5) return ['Advanced', '#6C63FF'];
    if ($done >= 3) return ['Intermediate', '#F39C12'];
    return ['Beginner', '#aaa'];
}

$pageTitle  = 'Resume Builder';
$activePage = 'resume';
include 'includes/header.php';
?>

<!-- ══════════════════════════════════════════════════════ -->
<!--  RESUME PAGE STYLES                                    -->
<!-- ══════════════════════════════════════════════════════ -->
<style>
/* ── Layout ─────────────────────────────────────────────── */
.resume-layout {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 28px;
    align-items: start;
}

/* ── Form panel ─────────────────────────────────────────── */
.form-panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}
.form-panel::-webkit-scrollbar { width: 4px; }
.form-panel::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

.form-section {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}
.form-section:last-child { border-bottom: none; }
.section-head {
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; font-weight: 700;
    color: var(--primary); margin-bottom: 16px;
    text-transform: uppercase; letter-spacing: 0.5px;
}

/* Dynamic list items */
.dynamic-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 10px; }
.dynamic-item {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 8px; padding: 14px; position: relative;
}
.dynamic-item .remove-btn {
    position: absolute; top: 8px; right: 8px;
    background: rgba(255,101,132,0.15); border: none;
    color: var(--secondary); width: 22px; height: 22px;
    border-radius: 50%; cursor: pointer; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    line-height: 1; transition: all 0.2s;
}
.dynamic-item .remove-btn:hover { background: var(--secondary); color: white; }

.add-btn {
    display: flex; align-items: center; gap: 6px;
    background: rgba(108,99,255,0.1); border: 1px dashed rgba(108,99,255,0.4);
    color: var(--primary); padding: 9px 16px; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer; width: 100%;
    justify-content: center; transition: all 0.2s;
}
.add-btn:hover { background: rgba(108,99,255,0.2); }

/* ── Resume preview ─────────────────────────────────────── */
.resume-preview-wrap {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.preview-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    background: rgba(255,255,255,0.02);
}
.preview-toolbar h3 { font-size: 14px; font-weight: 700; }

/* ── Actual resume (white paper) ─────────────────────────── */
#resume-paper {
    background: #ffffff;
    color: #1a1a1a;
    font-family: 'Georgia', 'Times New Roman', serif;
    margin: 20px;
    border-radius: 4px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.4);
    min-height: 1000px;
    overflow: hidden;
}

/* Resume Header */
.r-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    padding: 36px 40px 28px;
}
.r-name {
    font-size: 32px; font-weight: 700; letter-spacing: 1px;
    margin-bottom: 6px; color: #ffffff;
    font-family: 'Arial', sans-serif;
}
.r-title {
    font-size: 15px; color: #a0aec0; font-family: sans-serif;
    margin-bottom: 18px; font-style: italic;
}
.r-contact {
    display: flex; flex-wrap: wrap; gap: 16px; margin-top: 4px;
}
.r-contact span {
    font-size: 12px; color: #cbd5e0;
    display: flex; align-items: center; gap: 5px;
    font-family: 'Arial', sans-serif;
}
.r-contact span::before { content: attr(data-icon); }

/* Resume Body */
.r-body { padding: 30px 40px; }

/* Resume section */
.r-section { margin-bottom: 26px; }
.r-section-title {
    font-size: 13px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #1a1a2e;
    border-bottom: 2px solid #1a1a2e; padding-bottom: 5px;
    margin-bottom: 14px; font-family: 'Arial', sans-serif;
}
.r-section-empty { color: #aaa; font-size: 13px; font-style: italic; font-family: sans-serif; }

/* Summary */
.r-summary { font-size: 13.5px; line-height: 1.7; color: #444; font-family: sans-serif; }

/* Education / Experience entry */
.r-entry { margin-bottom: 14px; }
.r-entry-top {
    display: flex; justify-content: space-between; align-items: flex-start;
}
.r-entry-title { font-size: 14px; font-weight: 700; color: #1a1a2e; font-family: 'Arial', sans-serif; }
.r-entry-date { font-size: 12px; color: #718096; font-family: sans-serif; white-space: nowrap; }
.r-entry-sub { font-size: 13px; color: #4a5568; margin-top: 2px; font-family: sans-serif; }
.r-entry-desc { font-size: 13px; color: #555; margin-top: 5px; line-height: 1.6; font-family: sans-serif; }

/* Skills grid */
.r-skills { display: flex; flex-wrap: wrap; gap: 8px; }
.r-skill-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    font-family: 'Arial', sans-serif;
    border: 1px solid currentColor;
}

/* Projects */
.r-project { margin-bottom: 12px; }
.r-project-name { font-size: 14px; font-weight: 700; color: #1a1a2e; font-family: sans-serif; }
.r-project-tech { font-size: 12px; color: #6C63FF; font-family: sans-serif; margin: 2px 0; }
.r-project-desc { font-size: 13px; color: #555; line-height: 1.6; font-family: sans-serif; }

/* Certifications */
.r-cert { font-size: 13px; color: #444; margin-bottom: 6px; font-family: sans-serif; }
.r-cert::before { content: '✓ '; color: #2ecc71; font-weight: bold; }

/* Two column layout inside resume */
.r-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }

/* Print / download */
@media print {
    body * { visibility: hidden; }
    #resume-paper, #resume-paper * { visibility: visible; }
    #resume-paper {
        position: fixed; top: 0; left: 0;
        width: 100%; margin: 0; box-shadow: none;
        border-radius: 0;
    }
}

/* Template switcher */
.template-btn {
    padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
    cursor: pointer; border: 1px solid var(--border); background: none;
    color: var(--text-muted); transition: all 0.2s;
}
.template-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

/* Responsive */
@media (max-width: 1100px) {
    .resume-layout { grid-template-columns: 1fr; }
    .form-panel { position: static; max-height: none; }
}
</style>

<!-- ══════════════════════════════════════════════════════ -->

<div class="topbar">
    <div>
        <h1 class="page-title">📄 Resume Builder</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">
            Build your professional resume — your skills from CodeQuest are auto-filled!
        </p>
    </div>
    <button onclick="downloadResume()" class="btn btn-primary">
        ⬇️ Download PDF
    </button>
</div>

<div class="resume-layout">

    <!-- ════════════════════════════════════════════════ -->
    <!--  LEFT – Form Panel                              -->
    <!-- ════════════════════════════════════════════════ -->
    <div class="form-panel">

        <!-- Personal Info -->
        <div class="form-section">
            <div class="section-head">👤 Personal Information</div>
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" id="f-name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" oninput="updateResume()">
            </div>
            <div class="form-group">
                <label class="form-label">Job Title / Role</label>
                <input type="text" id="f-title" class="form-control" placeholder="e.g. Software Developer, CS Student" oninput="updateResume()">
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" id="f-email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" oninput="updateResume()">
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" id="f-phone" class="form-control" placeholder="+91 9876543210" oninput="updateResume()">
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" id="f-location" class="form-control" placeholder="City, State" oninput="updateResume()">
            </div>
            <div class="form-group">
                <label class="form-label">LinkedIn URL</label>
                <input type="text" id="f-linkedin" class="form-control" placeholder="linkedin.com/in/yourname" oninput="updateResume()">
            </div>
            <div class="form-group">
                <label class="form-label">GitHub URL</label>
                <input type="text" id="f-github" class="form-control" placeholder="github.com/yourname" oninput="updateResume()">
            </div>
        </div>

        <!-- Summary -->
        <div class="form-section">
            <div class="section-head">📝 Professional Summary</div>
            <div class="form-group">
                <textarea id="f-summary" class="form-control" rows="4"
                    placeholder="Write 2-3 sentences about yourself, your skills, and your goals..."
                    oninput="updateResume()"></textarea>
            </div>
            <button class="add-btn" onclick="autoFillSummary()" style="margin-top:-8px;">
                ✨ Auto-Generate Summary
            </button>
        </div>

        <!-- Education -->
        <div class="form-section">
            <div class="section-head">🎓 Education</div>
            <div id="edu-list" class="dynamic-list"></div>
            <button class="add-btn" onclick="addEdu()">+ Add Education</button>
        </div>

        <!-- Experience -->
        <div class="form-section">
            <div class="section-head">💼 Work / Internship Experience</div>
            <div id="exp-list" class="dynamic-list"></div>
            <button class="add-btn" onclick="addExp()">+ Add Experience</button>
        </div>

        <!-- Projects -->
        <div class="form-section">
            <div class="section-head">🚀 Projects</div>
            <div id="proj-list" class="dynamic-list"></div>
            <button class="add-btn" onclick="addProject()">+ Add Project</button>
        </div>

        <!-- Technical Skills (auto-filled from CodeQuest) -->
        <div class="form-section">
            <div class="section-head">💻 Technical Skills</div>
            <?php if (!empty($completedLangs)): ?>
            <div style="background:rgba(67,233,123,0.08);border:1px solid rgba(67,233,123,0.2);
                        border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:13px;">
                ✅ <strong style="color:var(--accent);">Auto-filled from your CodeQuest progress!</strong>
                <span style="color:var(--text-muted);">You can add more below.</span>
            </div>
            <?php endif; ?>
            <div id="skills-list" class="dynamic-list"></div>
            <button class="add-btn" onclick="addSkill()">+ Add Skill</button>
        </div>

        <!-- Certifications -->
        <div class="form-section">
            <div class="section-head">🏅 Certifications & Achievements</div>
            <div id="cert-list" class="dynamic-list"></div>
            <button class="add-btn" onclick="addCert()">+ Add Certification</button>
        </div>

        <!-- Hobbies -->
        <div class="form-section">
            <div class="section-head">🎯 Interests & Hobbies</div>
            <input type="text" id="f-hobbies" class="form-control"
                   placeholder="e.g. Competitive Programming, Open Source, Chess"
                   oninput="updateResume()">
        </div>

    </div><!-- /form-panel -->


    <!-- ════════════════════════════════════════════════ -->
    <!--  RIGHT – Live Preview                           -->
    <!-- ════════════════════════════════════════════════ -->
    <div class="resume-preview-wrap">
        <div class="preview-toolbar">
            <h3>👁️ Live Preview</h3>
            <div style="display:flex;gap:8px;align-items:center;">
                <button class="template-btn active" onclick="setTemplate('modern',this)">Modern</button>
                <button class="template-btn" onclick="setTemplate('classic',this)">Classic</button>
                <button onclick="downloadResume()" class="btn btn-success btn-sm">⬇️ Download PDF</button>
                <button onclick="printResume()" class="btn btn-outline btn-sm">🖨️ Print</button>
            </div>
        </div>

        <!-- THE RESUME PAPER -->
        <div id="resume-paper">

            <!-- Header -->
            <div class="r-header" id="r-header">
                <div class="r-name"  id="r-name">Your Name</div>
                <div class="r-title" id="r-title">Software Developer</div>
                <div class="r-contact" id="r-contact"></div>
            </div>

            <!-- Body -->
            <div class="r-body">

                <!-- Summary -->
                <div class="r-section" id="sec-summary">
                    <div class="r-section-title">Professional Summary</div>
                    <div class="r-summary" id="r-summary">
                        <span class="r-section-empty">Your summary will appear here...</span>
                    </div>
                </div>

                <!-- Two column: Skills + Education -->
                <div class="r-two-col">
                    <div>
                        <!-- Technical Skills -->
                        <div class="r-section" id="sec-skills">
                            <div class="r-section-title">Technical Skills</div>
                            <div class="r-skills" id="r-skills">
                                <span class="r-section-empty">Add skills...</span>
                            </div>
                        </div>

                        <!-- Certifications -->
                        <div class="r-section" id="sec-certs">
                            <div class="r-section-title">Certifications</div>
                            <div id="r-certs"><span class="r-section-empty">Add certifications...</span></div>
                        </div>

                        <!-- Interests -->
                        <div class="r-section" id="sec-hobbies" style="display:none;">
                            <div class="r-section-title">Interests</div>
                            <div id="r-hobbies" style="font-size:13px;color:#555;font-family:sans-serif;"></div>
                        </div>
                    </div>

                    <div>
                        <!-- Education -->
                        <div class="r-section" id="sec-edu">
                            <div class="r-section-title">Education</div>
                            <div id="r-edu"><span class="r-section-empty">Add education...</span></div>
                        </div>
                    </div>
                </div>

                <!-- Experience -->
                <div class="r-section" id="sec-exp">
                    <div class="r-section-title">Work Experience</div>
                    <div id="r-exp"><span class="r-section-empty">Add experience...</span></div>
                </div>

                <!-- Projects -->
                <div class="r-section" id="sec-proj">
                    <div class="r-section-title">Projects</div>
                    <div id="r-proj"><span class="r-section-empty">Add projects...</span></div>
                </div>

            </div><!-- /r-body -->
        </div><!-- /resume-paper -->
    </div>

</div><!-- /resume-layout -->


<!-- ══════════════════════════════════════════════════════ -->
<!--  JAVASCRIPT                                            -->
<!-- ══════════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// ── State ─────────────────────────────────────────────────
let eduItems   = [];
let expItems   = [];
let projItems  = [];
let skillItems = [];
let certItems  = [];
let template   = 'modern';

// ── Pre-fill skills from CodeQuest progress ───────────────
const cqSkills = <?= json_encode(array_map(fn($l) => [
    'name'  => $l['name'],
    'level' => skillLevel($l['levels_done'])[0],
    'color' => skillLevel($l['levels_done'])[1],
    'icon'  => $l['icon'],
], $completedLangs)) ?>;

// ── Init ──────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    // Add CodeQuest skills
    cqSkills.forEach(s => {
        skillItems.push({ name: s.icon + ' ' + s.name, level: s.level, color: s.color, fromCQ: true });
    });

    // Add one empty edu row to start
    addEdu();
    addProject();

    renderAll();
    updateResume();

    // Live update on all inputs
    document.querySelectorAll('input, textarea').forEach(el => {
        el.addEventListener('input', updateResume);
    });
});

// ── RENDER FORM LISTS ─────────────────────────────────────

function renderEduList() {
    const cont = document.getElementById('edu-list');
    cont.innerHTML = '';
    eduItems.forEach((item, i) => {
        cont.innerHTML += `
        <div class="dynamic-item">
            <button class="remove-btn" onclick="removeItem('edu',${i})">×</button>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div><label class="form-label" style="font-size:11px;">Degree / Course</label>
                    <input class="form-control" style="padding:8px;" placeholder="B.Tech Computer Science"
                        value="${esc(item.degree)}" oninput="eduItems[${i}].degree=this.value;updateResume()"></div>
                <div><label class="form-label" style="font-size:11px;">Year (e.g. 2021–2025)</label>
                    <input class="form-control" style="padding:8px;" placeholder="2021–2025"
                        value="${esc(item.year)}" oninput="eduItems[${i}].year=this.value;updateResume()"></div>
            </div>
            <div style="margin-top:8px;">
                <label class="form-label" style="font-size:11px;">College / University</label>
                <input class="form-control" style="padding:8px;" placeholder="ABC Engineering College"
                    value="${esc(item.college)}" oninput="eduItems[${i}].college=this.value;updateResume()">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                <div><label class="form-label" style="font-size:11px;">CGPA / Percentage</label>
                    <input class="form-control" style="padding:8px;" placeholder="8.5 CGPA / 85%"
                        value="${esc(item.grade)}" oninput="eduItems[${i}].grade=this.value;updateResume()"></div>
                <div><label class="form-label" style="font-size:11px;">Location</label>
                    <input class="form-control" style="padding:8px;" placeholder="Mumbai, MH"
                        value="${esc(item.location)}" oninput="eduItems[${i}].location=this.value;updateResume()"></div>
            </div>
        </div>`;
    });
}

function renderExpList() {
    const cont = document.getElementById('exp-list');
    cont.innerHTML = '';
    if (expItems.length === 0) {
        cont.innerHTML = '<p style="color:var(--text-muted);font-size:13px;">No experience added yet.</p>';
    }
    expItems.forEach((item, i) => {
        cont.innerHTML += `
        <div class="dynamic-item">
            <button class="remove-btn" onclick="removeItem('exp',${i})">×</button>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div><label class="form-label" style="font-size:11px;">Job Title</label>
                    <input class="form-control" style="padding:8px;" placeholder="Software Intern"
                        value="${esc(item.title)}" oninput="expItems[${i}].title=this.value;updateResume()"></div>
                <div><label class="form-label" style="font-size:11px;">Duration</label>
                    <input class="form-control" style="padding:8px;" placeholder="Jun 2024 – Aug 2024"
                        value="${esc(item.duration)}" oninput="expItems[${i}].duration=this.value;updateResume()"></div>
            </div>
            <div style="margin-top:8px;">
                <label class="form-label" style="font-size:11px;">Company / Organisation</label>
                <input class="form-control" style="padding:8px;" placeholder="XYZ Technologies"
                    value="${esc(item.company)}" oninput="expItems[${i}].company=this.value;updateResume()">
            </div>
            <div style="margin-top:8px;">
                <label class="form-label" style="font-size:11px;">Description (what you did)</label>
                <textarea class="form-control" style="padding:8px;min-height:60px;" placeholder="Developed REST APIs using PHP and MySQL..."
                    oninput="expItems[${i}].desc=this.value;updateResume()">${esc(item.desc)}</textarea>
            </div>
        </div>`;
    });
}

function renderProjList() {
    const cont = document.getElementById('proj-list');
    cont.innerHTML = '';
    projItems.forEach((item, i) => {
        cont.innerHTML += `
        <div class="dynamic-item">
            <button class="remove-btn" onclick="removeItem('proj',${i})">×</button>
            <div><label class="form-label" style="font-size:11px;">Project Name</label>
                <input class="form-control" style="padding:8px;" placeholder="Student Management System"
                    value="${esc(item.name)}" oninput="projItems[${i}].name=this.value;updateResume()"></div>
            <div style="margin-top:8px;">
                <label class="form-label" style="font-size:11px;">Technologies Used</label>
                <input class="form-control" style="padding:8px;" placeholder="PHP, MySQL, HTML, CSS"
                    value="${esc(item.tech)}" oninput="projItems[${i}].tech=this.value;updateResume()"></div>
            <div style="margin-top:8px;">
                <label class="form-label" style="font-size:11px;">Description</label>
                <textarea class="form-control" style="padding:8px;min-height:60px;" placeholder="Built a full-stack web app to manage student records..."
                    oninput="projItems[${i}].desc=this.value;updateResume()">${esc(item.desc)}</textarea>
            </div>
            <div style="margin-top:8px;">
                <label class="form-label" style="font-size:11px;">GitHub / Live Link (optional)</label>
                <input class="form-control" style="padding:8px;" placeholder="github.com/you/project"
                    value="${esc(item.link)}" oninput="projItems[${i}].link=this.value;updateResume()">
            </div>
        </div>`;
    });
}

function renderSkillsList() {
    const cont = document.getElementById('skills-list');
    cont.innerHTML = '';
    skillItems.forEach((item, i) => {
        const locked = item.fromCQ ? 'title="From CodeQuest" style="opacity:0.7;"' : '';
        cont.innerHTML += `
        <div class="dynamic-item" style="display:flex;align-items:center;gap:10px;padding:10px 14px;">
            <button class="remove-btn" style="position:static;flex-shrink:0;" onclick="removeItem('skill',${i})">×</button>
            <input class="form-control" style="padding:6px 10px;flex:1;" placeholder="Skill name" ${locked}
                value="${esc(item.name)}" oninput="skillItems[${i}].name=this.value;updateResume()">
            <select class="form-control" style="padding:6px;width:130px;flex-shrink:0;"
                onchange="skillItems[${i}].level=this.value;updateResume()">
                ${['Beginner','Intermediate','Advanced','Expert'].map(l =>
                    `<option value="${l}" ${item.level===l?'selected':''}>${l}</option>`
                ).join('')}
            </select>
        </div>`;
    });
}

function renderCertList() {
    const cont = document.getElementById('cert-list');
    cont.innerHTML = '';
    certItems.forEach((item, i) => {
        cont.innerHTML += `
        <div class="dynamic-item" style="display:flex;align-items:center;gap:10px;padding:10px 14px;">
            <button class="remove-btn" style="position:static;flex-shrink:0;" onclick="removeItem('cert',${i})">×</button>
            <input class="form-control" style="padding:6px 10px;flex:1;" placeholder="e.g. CodeQuest Python Expert Certificate"
                value="${esc(item.name)}" oninput="certItems[${i}].name=this.value;updateResume()">
            <input class="form-control" style="padding:6px;width:100px;flex-shrink:0;" placeholder="Year"
                value="${esc(item.year)}" oninput="certItems[${i}].year=this.value;updateResume()">
        </div>`;
    });
}

function renderAll() {
    renderEduList();
    renderExpList();
    renderProjList();
    renderSkillsList();
    renderCertList();
}

// ── ADD ITEMS ─────────────────────────────────────────────
function addEdu()     { eduItems.push({degree:'',year:'',college:'',grade:'',location:''}); renderEduList(); }
function addExp()     { expItems.push({title:'',company:'',duration:'',desc:''}); renderExpList(); }
function addProject() { projItems.push({name:'',tech:'',desc:'',link:''}); renderProjList(); }
function addSkill()   { skillItems.push({name:'',level:'Beginner',color:'#aaa',fromCQ:false}); renderSkillsList(); }
function addCert()    { certItems.push({name:'',year:''}); renderCertList(); }

function removeItem(type, i) {
    if (type === 'edu')   { eduItems.splice(i,1);   renderEduList(); }
    if (type === 'exp')   { expItems.splice(i,1);   renderExpList(); }
    if (type === 'proj')  { projItems.splice(i,1);  renderProjList(); }
    if (type === 'skill') { skillItems.splice(i,1); renderSkillsList(); }
    if (type === 'cert')  { certItems.splice(i,1);  renderCertList(); }
    updateResume();
}

// ── UPDATE LIVE PREVIEW ───────────────────────────────────
function updateResume() {
    const name     = v('f-name')     || 'Your Name';
    const title    = v('f-title')    || 'Software Developer';
    const email    = v('f-email');
    const phone    = v('f-phone');
    const location = v('f-location');
    const linkedin = v('f-linkedin');
    const github   = v('f-github');
    const summary  = v('f-summary');
    const hobbies  = v('f-hobbies');

    // Header
    document.getElementById('r-name').textContent  = name;
    document.getElementById('r-title').textContent = title;

    // Contact row
    let contact = '';
    if (email)    contact += `<span data-icon="✉ ">${email}</span>`;
    if (phone)    contact += `<span data-icon="📞 ">${phone}</span>`;
    if (location) contact += `<span data-icon="📍 ">${location}</span>`;
    if (linkedin) contact += `<span data-icon="🔗 ">${linkedin}</span>`;
    if (github)   contact += `<span data-icon="💻 ">${github}</span>`;
    document.getElementById('r-contact').innerHTML = contact || '<span style="color:#718096;font-size:12px;font-family:sans-serif;">Add your contact info</span>';

    // Summary
    document.getElementById('r-summary').innerHTML = summary
        ? `<span style="font-size:13.5px;line-height:1.7;color:#444;font-family:sans-serif;">${esc(summary)}</span>`
        : '<span class="r-section-empty">Your summary will appear here...</span>';

    // Education
    const eduHtml = eduItems.filter(e => e.degree || e.college).map(e => `
        <div class="r-entry">
            <div class="r-entry-top">
                <div class="r-entry-title">${esc(e.degree) || 'Degree'}</div>
                <div class="r-entry-date">${esc(e.year)}</div>
            </div>
            <div class="r-entry-sub">${esc(e.college)}${e.location ? ' · ' + esc(e.location) : ''}</div>
            ${e.grade ? `<div class="r-entry-desc">📊 ${esc(e.grade)}</div>` : ''}
        </div>`).join('');
    document.getElementById('r-edu').innerHTML = eduHtml || '<span class="r-section-empty">Add education...</span>';

    // Experience
    const expHtml = expItems.filter(e => e.title || e.company).map(e => `
        <div class="r-entry">
            <div class="r-entry-top">
                <div class="r-entry-title">${esc(e.title) || 'Job Title'}</div>
                <div class="r-entry-date">${esc(e.duration)}</div>
            </div>
            <div class="r-entry-sub">${esc(e.company)}</div>
            ${e.desc ? `<div class="r-entry-desc">${esc(e.desc)}</div>` : ''}
        </div>`).join('');
    document.getElementById('r-exp').innerHTML = expHtml || '<span class="r-section-empty">Add experience...</span>';

    // Projects
    const projHtml = projItems.filter(p => p.name).map(p => `
        <div class="r-project">
            <div class="r-project-name">${esc(p.name)}${p.link ? ` <span style="font-weight:400;font-size:12px;color:#6C63FF;">— ${esc(p.link)}</span>` : ''}</div>
            ${p.tech ? `<div class="r-project-tech">🛠 ${esc(p.tech)}</div>` : ''}
            ${p.desc ? `<div class="r-project-desc">${esc(p.desc)}</div>` : ''}
        </div>`).join('');
    document.getElementById('r-proj').innerHTML = projHtml || '<span class="r-section-empty">Add projects...</span>';

    // Skills
    const levelColors = { Expert:'#43E97B', Advanced:'#6C63FF', Intermediate:'#F39C12', Beginner:'#aaa' };
    const skillHtml = skillItems.filter(s => s.name).map(s => {
        const col = s.color || levelColors[s.level] || '#aaa';
        return `<span class="r-skill-tag" style="color:${col};border-color:${col};background:${col}18;">${esc(s.name)} <small style="opacity:0.8;">${s.level}</small></span>`;
    }).join('');
    document.getElementById('r-skills').innerHTML = skillHtml || '<span class="r-section-empty">Add skills...</span>';

    // Certifications
    const certHtml = certItems.filter(c => c.name).map(c =>
        `<div class="r-cert">${esc(c.name)}${c.year ? ' <span style="color:#718096;">('+esc(c.year)+')</span>' : ''}</div>`
    ).join('');
    document.getElementById('r-certs').innerHTML = certHtml || '<span class="r-section-empty">Add certifications...</span>';

    // Hobbies
    const hobSection = document.getElementById('sec-hobbies');
    if (hobbies) {
        hobSection.style.display = 'block';
        document.getElementById('r-hobbies').textContent = hobbies;
    } else {
        hobSection.style.display = 'none';
    }
}

// ── AUTO-GENERATE SUMMARY ─────────────────────────────────
function autoFillSummary() {
    const name  = v('f-name')  || 'I';
    const langs = cqSkills.map(s => s.name.replace(/^[^\w]+/, '')).slice(0, 4);
    const langStr = langs.length > 0 ? langs.join(', ') : 'multiple programming languages';
    const xp    = <?= (int)$user['total_xp'] ?>;
    const lvls  = <?= count($completedLangs) ?>;

    const summaries = [
        `Motivated Computer Science student with hands-on experience in ${langStr}. Completed ${lvls} language tracks on CodeQuest earning ${xp} XP. Passionate about building efficient software solutions and eager to apply my skills in a dynamic development environment.`,
        `Enthusiastic software developer skilled in ${langStr}. Self-driven learner who has completed ${lvls} programming courses through gamified learning. Looking for opportunities to contribute to innovative projects and grow as a developer.`,
        `Aspiring software engineer with a strong foundation in ${langStr}. Demonstrated dedication through structured learning and practical coding exercises. Seeking an internship to apply my technical skills and gain industry experience.`
    ];
    const chosen = summaries[Math.floor(Math.random() * summaries.length)];
    document.getElementById('f-summary').value = chosen;
    updateResume();
}

// ── TEMPLATE SWITCHER ─────────────────────────────────────
function setTemplate(t, btn) {
    template = t;
    document.querySelectorAll('.template-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const header = document.getElementById('r-header');
    if (t === 'classic') {
        header.style.background = '#f8f9fa';
        document.getElementById('r-name').style.color  = '#1a1a2e';
        document.getElementById('r-title').style.color = '#4a5568';
        document.querySelectorAll('.r-contact span').forEach(s => s.style.color = '#4a5568');
        header.style.borderBottom = '3px solid #1a1a2e';
    } else {
        header.style.background = 'linear-gradient(135deg,#1a1a2e 0%,#16213e 100%)';
        header.style.borderBottom = 'none';
        document.getElementById('r-name').style.color  = '#ffffff';
        document.getElementById('r-title').style.color = '#a0aec0';
    }
}

// ── DOWNLOAD PDF ──────────────────────────────────────────
function downloadResume() {
    const name = v('f-name') || 'resume';
    const element = document.getElementById('resume-paper');

    const opt = {
        margin:      0,
        filename:    name.replace(/\s+/g, '_') + '_Resume.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // Temporarily remove dark background for clean print
    element.style.boxShadow = 'none';
    element.style.borderRadius = '0';
    element.style.margin = '0';

    html2pdf().set(opt).from(element).save().then(() => {
        element.style.boxShadow = '';
        element.style.borderRadius = '4px';
        element.style.margin = '20px';
    });
}

// ── PRINT ─────────────────────────────────────────────────
function printResume() {
    window.print();
}

// ── HELPERS ───────────────────────────────────────────────
function v(id) {
    const el = document.getElementById(id);
    return el ? el.value.trim() : '';
}
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include 'includes/footer.php'; ?>
