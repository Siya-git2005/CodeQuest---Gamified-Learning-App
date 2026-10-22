/* ============================================================
   compiler.js — Online Code Compiler Frontend
   GamifiedLearningApp — Fixed Version
   ============================================================ */

(function () {
    'use strict';

    let isRunning = false;

    // Read language from hidden input (set by PHP from $lang['name'])
    function getCurrentLanguage() {
        const el = document.getElementById('compiler-lang');
        if (!el) return 'python';
        return (el.value || 'python').trim().toLowerCase();
    }

    // DOM refs
    const codeArea      = document.getElementById('compiler-code');
    const inputArea     = document.getElementById('compiler-input');
    const btnRun        = document.getElementById('btn-run');
    const btnEvaluate   = document.getElementById('btn-evaluate');
    const btnClear      = document.getElementById('btn-clear-code');
    const outputContent = document.getElementById('output-content');
    const outputHeader  = document.getElementById('output-header');
    const outputBadge   = document.getElementById('output-badge');
    const aiFeedback    = document.getElementById('ai-feedback');
    const evalResults   = document.getElementById('eval-results');
    const htmlFrame     = document.getElementById('html-preview-frame');
    const execTime      = document.getElementById('exec-time');
    const lineNumbers   = document.getElementById('line-numbers');

    if (!codeArea) return;

    // ---- Init: editor starts BLANK ----
    (function init() {
        codeArea.value = '';
        updateLineNumbers();
        updateInputVisibility();
    })();

    // ---- Line numbers ----
    codeArea.addEventListener('input', updateLineNumbers);
    codeArea.addEventListener('scroll', function () {
        if (lineNumbers) lineNumbers.scrollTop = codeArea.scrollTop;
    });
    codeArea.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const s = codeArea.selectionStart;
            codeArea.value = codeArea.value.substring(0, s) + '    ' + codeArea.value.substring(codeArea.selectionEnd);
            codeArea.selectionStart = codeArea.selectionEnd = s + 4;
            updateLineNumbers();
        }
    });

    function updateLineNumbers() {
        if (!lineNumbers) return;
        const count = (codeArea.value.match(/\n/g) || []).length + 1;
        let html = '';
        for (let i = 1; i <= count; i++) html += i + '\n';
        lineNumbers.textContent = html;
    }

    function updateInputVisibility() {
        const s = document.getElementById('stdin-section');
        if (s) s.style.display = (getCurrentLanguage() === 'html') ? 'none' : 'flex';
    }

    // ---- Reset button: clear editor ----
    if (btnClear) {
        btnClear.addEventListener('click', function () {
            codeArea.value = '';
            clearOutput();
            updateLineNumbers();
        });
    }

    // ---- Run Code ----
    if (btnRun) btnRun.addEventListener('click', function () { runCode(false); });

    // ---- Submit & Evaluate ----
    if (btnEvaluate) btnEvaluate.addEventListener('click', function () { runCode(true); });

    // ---- Core run function ----
    function runCode(evalMode) {
        if (isRunning) return;

        const lang = getCurrentLanguage();
        const code = codeArea.value.trim();

        if (!code) {
            showOutput('compile_error', '⚠️ Editor is empty. Please write your code first.', null, null);
            return;
        }

        // ── Warn user if code needs input but stdin is empty (Run Code only) ──
        if (!evalMode && inputArea) {
            const needsInput = (
                (lang === 'c'          && /scanf\s*\(/.test(code)) ||
                (lang === 'cpp'        && /cin\s*>>/.test(code)) ||
                (lang === 'java'       && /Scanner|nextInt|nextLine|next\(\)/.test(code)) ||
                (lang === 'python'     && /input\s*\(/.test(code)) ||
                (lang === 'php'        && /fgets\s*\(\s*STDIN/.test(code)) ||
                (lang === 'javascript' && /process\.stdin/.test(code))
            );
            if (needsInput && inputArea.value.trim() === '') {
                // Highlight stdin box to tell user to enter input
                inputArea.style.border = '2px solid #FF6584';
                inputArea.placeholder = '⚠️ Your code needs input! Enter a value here first...';
                inputArea.focus();
                setTimeout(function() {
                    inputArea.style.border = '';
                    inputArea.placeholder = 'Enter program input (if needed)...';
                }, 4000);
                showOutput('compile_error',
                    '⚠️ Your code needs input!\n\nPlease enter a value in the Stdin Input box below the editor, then click Run Code again.\n\nExample: If your code reads N, type a number like 5 in the input box.',
                    null, null);
                return;
            }
        }

        // HTML: client-side preview
        if (lang === 'html' && !evalMode) {
            renderHTML(codeArea.value);
            return;
        }

        isRunning = true;
        setLoading(true);
        clearOutput();

        const payload = {
            language:   lang,
            code:       codeArea.value,
            user_input: inputArea ? inputArea.value : '',
            lang_id:    parseInt(document.getElementById('cq-lang-id')?.value  || '0'),
            level_id:   parseInt(document.getElementById('cq-level-id')?.value || '0'),
            evaluate:   evalMode,
        };

        fetch('/GamifiedLearningApp/run_code.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        })
        .then(function (r) {
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return r.text().then(function (t) {
                    throw new Error('Server error: ' + t.substring(0, 200));
                });
            }
            return r.json();
        })
        .then(function (data) {
            if (!data.success) {
                showOutput('compile_error', data.error || 'Unknown error.', null, null);
                return;
            }
            if (data.type === 'evaluation') {
                showEvalResults(data);
            } else if (data.type === 'html_preview') {
                renderHTML(data.output);
            } else {
                showOutput(data.type, data.output || data.error, data.feedback, data.execution_time);
            }
        })
        .catch(function (err) {
            showOutput('compile_error', '⚠️ ' + err.message, null, null);
        })
        .finally(function () {
            isRunning = false;
            setLoading(false);
        });
    }

    // ---- Show single run output ----
    function showOutput(type, text, feedback, time) {
        if (!outputContent) return;

        const map = {
            'output':        { label: 'Output',        cls: 'type-output',      badge: 'badge-success', badgeText: 'SUCCESS' },
            'compile_error': { label: 'Compile Error', cls: 'type-compile-err', badge: 'badge-error',   badgeText: 'COMPILE ERROR' },
            'runtime_error': { label: 'Runtime Error', cls: 'type-runtime-err', badge: 'badge-warning', badgeText: 'RUNTIME ERROR' },
        };

        const info = map[type] || map['output'];
        outputContent.className  = 'output-content visible ' + info.cls;
        outputContent.textContent = text || '(no output)';

        if (outputHeader) outputHeader.textContent = '📤 ' + info.label;
        if (outputBadge)  { outputBadge.className = 'output-badge ' + info.badge; outputBadge.textContent = info.badgeText; }
        if (evalResults)  evalResults.classList.remove('visible');
        if (htmlFrame)    htmlFrame.style.display = 'none';

        if (execTime && time) { execTime.textContent = '⏱ ' + time + 'ms'; execTime.style.display = 'block'; }

        // AI Feedback hidden
        if (aiFeedback) aiFeedback.classList.remove('visible');
    }

    // ---- Show evaluation results ----
    function showEvalResults(data) {
        if (!evalResults) return;

        let html = '';
        data.test_results.forEach(function (tc) {
            const icon   = tc.passed ? '✅' : '❌';
            const cls    = tc.passed ? 'tc-pass' : 'tc-fail';
            const status = tc.passed ? 'Passed' : 'Failed';
            var inpLabel = tc.input ? esc(tc.input).replace(/\n/g, ' ↵ ') : '(none)';
            html += '<div class="test-case-row ' + cls + '">' + icon + ' Test Case ' + tc.case + ': <strong>' + status + '</strong>';
            if (tc.passed && tc.input) {
                html += '<div class="tc-input-passed">Input: <code>' + inpLabel + '</code></div>';
            }
            if (!tc.passed) {
                if (tc.error) {
                    html += '<div class="tc-detail tc-error">❗ Error: <code>' + esc(tc.error) + '</code></div>';
                } else {
                    var expDisplay = esc(tc.expected).replace(/\n/g, '<br>');
                    var gotDisplay = esc(tc.actual).replace(/\n/g, '<br>');
                    var inpDisplay = tc.input ? esc(tc.input).replace(/\n/g, ' ↵ ') : '(none)';
                    html += '<div class="tc-detail">';
                    html += '<span class="tc-label">Input:</span> <code>' + inpDisplay + '</code><br>';
                    html += '<span class="tc-label">Expected:</span><pre class="tc-pre">' + expDisplay + '</pre>';
                    html += '<span class="tc-label">Got:</span><pre class="tc-pre tc-wrong">' + gotDisplay + '</pre>';
                    html += '</div>';
                }
            }
            html += '</div>';
        });

        const sumCls  = data.level_completed ? 'eval-pass' : 'eval-fail';
        const sumText = data.level_completed
            ? '🏆 <strong>Level Completed!</strong> All ' + data.total + ' test case(s) passed.'
            : '❌ <strong>Wrong Answer</strong> — ' + data.passed + '/' + data.total + ' test cases passed.';

        html += '<div class="eval-summary ' + sumCls + '">' + sumText + '</div>';

        evalResults.innerHTML = html;
        evalResults.classList.add('visible');

        if (outputContent) outputContent.classList.remove('visible');
        if (htmlFrame)     htmlFrame.style.display = 'none';

        // AI Feedback hidden in eval mode too
        if (aiFeedback) aiFeedback.classList.remove('visible');

        if (data.level_completed) {
            // Auto submit mark_complete form if present
            setTimeout(function () {
                const form = document.getElementById('mark-complete-form');
                if (form) form.submit();
            }, 1500);
        }
    }

    // ---- HTML preview ----
    function renderHTML(code) {
        if (!htmlFrame) return;
        const blob = new Blob([code], { type: 'text/html' });
        htmlFrame.src = URL.createObjectURL(blob);
        htmlFrame.style.display = 'block';
        if (outputContent) outputContent.classList.remove('visible');
        if (evalResults)   evalResults.classList.remove('visible');
        if (outputBadge)   { outputBadge.className = 'output-badge badge-success'; outputBadge.textContent = 'HTML PREVIEW'; }
        if (outputHeader)  outputHeader.textContent = '📤 HTML Preview';
    }

    // ---- Clear output ----
    function clearOutput() {
        if (outputContent) { outputContent.textContent = ''; outputContent.className = 'output-content'; }
        if (evalResults)   evalResults.classList.remove('visible');
        if (aiFeedback)    { aiFeedback.innerHTML = ''; aiFeedback.classList.remove('visible'); }
        if (htmlFrame)     htmlFrame.style.display = 'none';
        if (execTime)      execTime.style.display = 'none';
        if (outputBadge)   { outputBadge.className = 'output-badge'; outputBadge.textContent = ''; }
        if (outputHeader)  outputHeader.textContent = '📤 Output';
    }

    // ---- Loading state ----
    function setLoading(on) {
        [btnRun, btnEvaluate].forEach(function (btn) {
            if (!btn) return;
            btn.disabled = on;
            if (on) { btn._orig = btn.innerHTML; btn.innerHTML = '<span class="compiler-spinner"></span> Running...'; }
            else if (btn._orig) btn.innerHTML = btn._orig;
        });
    }

    // ---- Escape HTML ----
    function esc(s) {
        if (!s) return '';
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

})();
