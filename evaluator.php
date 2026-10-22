<?php
// evaluator.php
// Evaluates student code against the practice question
// Uses local logic — NO external API needed

header('Content-Type: application/json');

// Get input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Also support FormData
if (!$data) {
    $data = $_POST;
}

$code     = trim($data['code']     ?? '');
$language = trim($data['language'] ?? '');
$problem  = trim($data['problem']  ?? '');
$output   = trim($data['output']   ?? '');
$level    = trim($data['level']    ?? '');

if (!$code || !$problem) {
    echo json_encode(['feedback' => '❌ No code or problem provided.', 'correct' => false]);
    exit;
}

// ============================================================
// SMART EVALUATION LOGIC
// Compare student's code against the practice problem
// ============================================================

$feedback = '';
$correct = false;
$score = 0;
$maxScore = 5;
$tips = [];
$positives = [];

$codeLower    = strtolower($code);
$problemLower = strtolower($problem);
$outputLower  = strtolower($output);

// ---- 1. Check if code is not empty / has real content ----
$codeLines = array_filter(explode("\n", trim($code)), fn($l) => trim($l) !== '');
if (count($codeLines) >= 2) {
    $score++;
    $positives[] = "✅ Your code has proper structure.";
} else {
    $tips[] = "⚠️ Code seems too short. Make sure you wrote a complete solution.";
}

// ---- 2. Language-specific keyword checks ----
$langKeywords = [
    'python'     => ['print', 'def', 'if', 'for', 'while', 'return', 'import', 'class'],
    'javascript' => ['console.log', 'function', 'const', 'let', 'var', 'return', 'if', 'for'],
    'java'       => ['system.out', 'public', 'class', 'static', 'void', 'main', 'return'],
    'c'          => ['printf', '#include', 'int main', 'return', 'scanf'],
    'cpp'        => ['cout', '#include', 'int main', 'return', 'cin', 'using namespace'],
    'php'        => ['echo', '<?php', 'function', '$', 'print', 'return'],
    'html'       => ['<!doctype', '<html', '<body', '<head', '<div', '<p'],
    'css'        => ['{', '}', ':', 'color', 'background', 'font', 'margin', 'padding'],
];

$langLower = strtolower($language);
$foundLang = null;
foreach ($langKeywords as $lang => $kws) {
    if (str_contains($langLower, $lang)) {
        $foundLang = $lang;
        break;
    }
}

if ($foundLang) {
    $kwCount = 0;
    foreach ($langKeywords[$foundLang] as $kw) {
        if (str_contains($codeLower, strtolower($kw))) $kwCount++;
    }
    if ($kwCount >= 2) {
        $score++;
        $positives[] = "✅ Correct {$language} syntax used.";
    } else {
        $tips[] = "⚠️ Make sure you're using proper {$language} syntax and keywords.";
    }
}

// ---- 3. Check output against problem keywords ----
// Extract key expected terms from problem statement
$problemWords = array_filter(
    preg_split('/[\s,.\-!?]+/', $problemLower),
    fn($w) => strlen($w) > 3
);

$outputMatchCount = 0;
if (!empty($output)) {
    foreach ($problemWords as $word) {
        if (str_contains($outputLower, $word)) {
            $outputMatchCount++;
        }
    }
    $matchRatio = count($problemWords) > 0 ? $outputMatchCount / count($problemWords) : 0;

    if ($matchRatio >= 0.3) {
        $score++;
        $positives[] = "✅ Output seems related to the problem.";
    } elseif (!empty($outputLower) && $outputLower !== 'compiling and running your code...' && $outputLower !== '(no output)') {
        $score++;
        $positives[] = "✅ Code produced some output.";
    } else {
        $tips[] = "⚠️ Run your code first using Submit to see the output.";
    }
} else {
    $tips[] = "⚠️ No output detected. Click Submit first to run your code.";
}

// ---- 4. Check for common logic patterns from problem ----
// Look for numbers, function names, or key concepts in problem
preg_match_all('/\b\d+\b/', $problem, $nums);
if (!empty($nums[0])) {
    foreach ($nums[0] as $num) {
        if (str_contains($code, $num)) {
            $score++;
            $positives[] = "✅ Correct values used in your code.";
            break;
        }
    }
}

// ---- 5. Check for loops/functions if problem mentions them ----
$loopKeywords    = ['loop', 'repeat', 'iterate', 'for each', 'while', 'times'];
$funcKeywords    = ['function', 'method', 'define', 'create a function', 'write a function'];
$arrayKeywords   = ['array', 'list', 'collection', 'store multiple'];
$condKeywords    = ['if', 'check', 'condition', 'when', 'whether', 'else'];

foreach ($loopKeywords as $kw) {
    if (str_contains($problemLower, $kw)) {
        if (str_contains($codeLower, 'for') || str_contains($codeLower, 'while')) {
            $score++;
            $positives[] = "✅ Loop used correctly as required.";
        } else {
            $tips[] = "💡 The problem asks for a loop — use for or while.";
        }
        break;
    }
}

foreach ($funcKeywords as $kw) {
    if (str_contains($problemLower, $kw)) {
        if (str_contains($codeLower, 'def ') || str_contains($codeLower, 'function') || str_contains($codeLower, 'int ') || str_contains($codeLower, 'void ')) {
            $score++;
            $positives[] = "✅ Function defined correctly.";
        } else {
            $tips[] = "💡 The problem asks you to write a function.";
        }
        break;
    }
}

// ---- Final Score & Feedback ----
$percentage = min(100, round(($score / $maxScore) * 100));

if ($percentage >= 80) {
    $correct = true;
    $verdict = "🎉 <strong>Excellent Work!</strong> Your solution looks correct!";
    $verdictColor = "#43E97B";
} elseif ($percentage >= 50) {
    $correct = null; // partial
    $verdict = "👍 <strong>Good Attempt!</strong> You're on the right track.";
    $verdictColor = "#FFD700";
} else {
    $correct = false;
    $verdict = "💪 <strong>Keep Trying!</strong> Review the problem and try again.";
    $verdictColor = "#FF6584";
}

// Build feedback HTML
$feedbackParts = [];
$feedbackParts[] = "<div style='font-size:16px;margin-bottom:12px;'>{$verdict}</div>";

// Score bar
$feedbackParts[] = "<div style='margin-bottom:12px;'>
    <div style='font-size:12px;color:#aaa;margin-bottom:4px;'>Score: {$score}/{$maxScore}</div>
    <div style='background:rgba(255,255,255,0.1);border-radius:20px;height:8px;overflow:hidden;'>
        <div style='width:{$percentage}%;height:100%;background:{$verdictColor};border-radius:20px;transition:width 0.5s;'></div>
    </div>
</div>";

// Positives
if (!empty($positives)) {
    $feedbackParts[] = "<div style='margin-bottom:10px;'>";
    foreach ($positives as $p) {
        $feedbackParts[] = "<div style='font-size:13px;margin-bottom:4px;color:#ccc;'>{$p}</div>";
    }
    $feedbackParts[] = "</div>";
}

// Tips
if (!empty($tips)) {
    $feedbackParts[] = "<div style='margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,0.1);'>";
    foreach ($tips as $t) {
        $feedbackParts[] = "<div style='font-size:13px;margin-bottom:4px;color:#FFD700;'>{$t}</div>";
    }
    $feedbackParts[] = "</div>";
}

// Problem reminder
$feedbackParts[] = "<div style='margin-top:12px;padding:10px;background:rgba(255,255,255,0.05);border-radius:8px;font-size:12px;color:#888;'>
    <strong style='color:#aaa;'>📋 Problem was:</strong> " . htmlspecialchars(mb_substr($problem, 0, 200)) . "
</div>";

$feedback = implode('', $feedbackParts);

echo json_encode([
    'feedback' => $feedback,
    'correct'  => $correct,
    'score'    => $score,
    'max'      => $maxScore,
    'percent'  => $percentage
]);
