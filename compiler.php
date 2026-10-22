<?php
// ============================================================
// compiler.php - Real Code Execution Backend
// ============================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'output' => 'Method not allowed.']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$code     = $data['code']    ?? '';
$language = $data['language'] ?? 'python';
$stdin    = $data['stdin']   ?? '';

if (empty(trim($code))) {
    echo json_encode(['success' => false, 'output' => 'No code provided.']);
    exit;
}

// ---- Sanitize / normalise language slug ----
$language = strtolower(trim($language));
$langMap  = [
    'python'     => 'python',
    'python3'    => 'python',
    'c'          => 'c',
    'c++'        => 'cpp',
    'cpp'        => 'cpp',
    'java'       => 'java',
    'javascript' => 'javascript',
    'js'         => 'javascript',
];
$lang = $langMap[$language] ?? 'python';

// ---- Detect Operating System ----
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// ---- Scratch directory ----
$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cq_compiler_' . uniqid('', true);
if (!mkdir($tmpDir, 0700, true)) {
    echo json_encode(['success' => false, 'output' => 'Server error: could not create temp dir.']);
    exit;
}

// ---- Write stdin file ----
$stdinFile = $tmpDir . DIRECTORY_SEPARATOR . 'stdin.txt';
file_put_contents($stdinFile, $stdin);

// ---- Helper: build run command (cross-platform) ----
// On Windows: no `timeout` command, use `cd /d` for drive-aware cd
// On Linux:   use `timeout N` prefix
function buildRunCmd($tmpDir, $executable, $args, $timeout, $isWindows) {
    $stdinRedir = '< ' . escapeshellarg($tmpDir . DIRECTORY_SEPARATOR . 'stdin.txt');
    if ($isWindows) {
        $cd = 'cd /d ' . escapeshellarg($tmpDir);
        return "{$cd} && {$executable} {$args} {$stdinRedir} 2>&1";
    } else {
        $cd = 'cd ' . escapeshellarg($tmpDir);
        return "{$cd} && timeout {$timeout} {$executable} {$args} {$stdinRedir} 2>&1";
    }
}

// ---- Language-specific setup ----
$timeout   = 10; // seconds (Linux only)
$outputStr = '';
$exitCode  = 0;

try {
    switch ($lang) {

        // ── Python ──────────────────────────────────────────────
        case 'python': {
            $srcFile = $tmpDir . DIRECTORY_SEPARATOR . 'solution.py';
            file_put_contents($srcFile, $code);

            // Try python3 first, fall back to python (Windows often uses 'python')
            $pyBin = $isWindows ? 'python' : 'python3';
            $cmd   = buildRunCmd($tmpDir, $pyBin, '-u solution.py', $timeout, $isWindows);
            exec($cmd, $outLines, $exitCode);

            // If python3 not found on Windows, retry with 'py' launcher
            if ($isWindows && $exitCode === 1 && count($outLines) === 0) {
                $outLines = [];
                $cmd = buildRunCmd($tmpDir, 'py', '-u solution.py', $timeout, $isWindows);
                exec($cmd, $outLines, $exitCode);
            }

            $outputStr = implode("\n", $outLines);
            break;
        }

        // ── C ───────────────────────────────────────────────────
        case 'c': {
            $srcFile = $tmpDir . DIRECTORY_SEPARATOR . 'solution.c';
            $binExt  = $isWindows ? 'solution_bin.exe' : 'solution_bin';
            $binFile = $tmpDir . DIRECTORY_SEPARATOR . $binExt;
            file_put_contents($srcFile, $code);

            $compileCmd = sprintf('gcc -o %s %s -lm 2>&1',
                escapeshellarg($binFile), escapeshellarg($srcFile));
            exec($compileCmd, $compileLines, $compileExit);

            if ($compileExit !== 0) {
                $outputStr = "Compilation Error:\n" . implode("\n", $compileLines);
                $exitCode  = $compileExit;
            } else {
                $cmd = buildRunCmd($tmpDir, escapeshellarg($binFile), '', $timeout, $isWindows);
                exec($cmd, $outLines, $exitCode);
                $outputStr = implode("\n", $outLines);
            }
            break;
        }

        // ── C++ ─────────────────────────────────────────────────
        case 'cpp': {
            $srcFile = $tmpDir . DIRECTORY_SEPARATOR . 'solution.cpp';
            $binExt  = $isWindows ? 'solution_bin.exe' : 'solution_bin';
            $binFile = $tmpDir . DIRECTORY_SEPARATOR . $binExt;
            file_put_contents($srcFile, $code);

            $compileCmd = sprintf('g++ -o %s %s -lm 2>&1',
                escapeshellarg($binFile), escapeshellarg($srcFile));
            exec($compileCmd, $compileLines, $compileExit);

            if ($compileExit !== 0) {
                $outputStr = "Compilation Error:\n" . implode("\n", $compileLines);
                $exitCode  = $compileExit;
            } else {
                $cmd = buildRunCmd($tmpDir, escapeshellarg($binFile), '', $timeout, $isWindows);
                exec($cmd, $outLines, $exitCode);
                $outputStr = implode("\n", $outLines);
            }
            break;
        }

        // ── Java ────────────────────────────────────────────────
        case 'java': {
            preg_match('/public\s+class\s+(\w+)/', $code, $classMatch);
            $className = $classMatch[1] ?? 'Solution';
            $srcFile   = $tmpDir . DIRECTORY_SEPARATOR . "{$className}.java";
            file_put_contents($srcFile, $code);

            $compileCmd = sprintf('javac %s 2>&1', escapeshellarg($srcFile));
            exec($compileCmd, $compileLines, $compileExit);

            if ($compileExit !== 0) {
                $outputStr = "Compilation Error:\n" . implode("\n", $compileLines);
                $exitCode  = $compileExit;
            } else {
                $cmd = buildRunCmd($tmpDir, 'java', escapeshellarg($className), $timeout, $isWindows);
                exec($cmd, $outLines, $exitCode);
                $outputStr = implode("\n", $outLines);
            }
            break;
        }

        // ── JavaScript (Node.js) ────────────────────────────────
        case 'javascript': {
            $wrapper = <<<'JS'
const readline = require('readline');
const rl = readline.createInterface({ input: process.stdin, terminal: false });
const _inputLines = [];
let _inputIdx = 0;
rl.on('line', l => _inputLines.push(l));
rl.on('close', () => {
    function input(prompt) {
        if (prompt) process.stdout.write(prompt);
        return _inputLines[_inputIdx++] ?? '';
    }
    (function() {
JS;
            $wrapper .= "\n" . $code . "\n";
            $wrapper .= "    })();\n});\n";

            $srcFile = $tmpDir . DIRECTORY_SEPARATOR . 'solution.js';
            file_put_contents($srcFile, $wrapper);

            $cmd = buildRunCmd($tmpDir, 'node', 'solution.js', $timeout, $isWindows);
            exec($cmd, $outLines, $exitCode);
            $outputStr = implode("\n", $outLines);
            break;
        }

        default:
            $outputStr = "Language '{$language}' is not supported yet.";
            $exitCode  = 1;
    }

} catch (Exception $e) {
    $outputStr = 'Server error: ' . $e->getMessage();
    $exitCode  = 1;
} finally {
    // ---- Cleanup temp directory ----
    array_map('unlink', glob("$tmpDir" . DIRECTORY_SEPARATOR . "*"));
    @rmdir($tmpDir);
}

// ---- Timeout detection (Linux only - exit 124) ----
if (!$isWindows && $exitCode === 124) {
    $outputStr = "Time Limit Exceeded: Your program ran for more than {$timeout} seconds.";
}

// ---- Empty output ----
if (trim($outputStr) === '') {
    $outputStr = '(no output)';
}

echo json_encode([
    'success'  => ($exitCode === 0),
    'output'   => $outputStr,
    'exitCode' => $exitCode,
]);
