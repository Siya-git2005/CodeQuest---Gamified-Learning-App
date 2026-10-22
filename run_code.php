<?php
// ============================================================
// run_code.php - Online Code Compiler + Auto Evaluator
// GamifiedLearningApp - MCA Major Project
// ============================================================

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
if (!isset($_SESSION['run_count'])) { $_SESSION['run_count'] = 0; $_SESSION['run_window'] = time(); }
if (time() - $_SESSION['run_window'] > 60) { $_SESSION['run_count'] = 0; $_SESSION['run_window'] = time(); }
$_SESSION['run_count']++;
if ($_SESSION['run_count'] > 30) { jsonError('Rate limit exceeded. Please wait a minute.'); }

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { $input = $_POST; }

$language  = strtolower(trim($input['language']   ?? ''));
$code      = $input['code']       ?? '';
$userInput = $input['user_input'] ?? '';
$langId    = (int)($input['lang_id']  ?? 0);
$levelId   = (int)($input['level_id'] ?? 0);
$evalMode  = filter_var($input['evaluate'] ?? false, FILTER_VALIDATE_BOOLEAN);

$allowedLanguages = ['c','cpp','java','python','php','javascript','html'];
if (!in_array($language, $allowedLanguages, true)) { jsonError('Unsupported language: ' . htmlspecialchars($language)); }
if (strlen($code) > 50000) { jsonError('Code too large.'); }

if ($language === 'html') {
    echo json_encode(['success'=>true,'type'=>'html_preview','output'=>$code,'feedback'=>'']);
    exit;
}

define('GCC_PATH',    'gcc');
define('GPP_PATH',    'g++');
define('JAVA_BIN',    'javac');
define('JAVA_RUN',    'java');
define('PYTHON_BIN',  'python');
define('PHP_CLI',     'php');
define('NODE_BIN',    'node');
define('EXEC_TIMEOUT', 10);
define('TEMP_BASE',   sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cq_compiler');

if (!is_dir(TEMP_BASE)) { mkdir(TEMP_BASE, 0700, true); }
cleanOldTempDirs();

$sandboxId  = preg_replace('/[^a-z0-9]/','',uniqid('cq',true));
$sandboxDir = TEMP_BASE . DIRECTORY_SEPARATOR . $sandboxId;
mkdir($sandboxDir, 0700, true);

if (!$evalMode) {
    $result = executeCode($language, $code, $userInput, $sandboxDir);
    deleteDir($sandboxDir);
    echo json_encode([
        'success'        => true,
        'type'           => $result['type'],
        'output'         => $result['output'] ?? '',
        'error'          => $result['error']  ?? null,
        'feedback'       => '',
        'execution_time' => $result['time']   ?? null,
    ]);
    exit;
}

// ── Evaluate mode: generate test inputs and verify ──
$testInputs  = generateTestInputs($levelId, $langId, 4);
$results     = [];
$passedCount = 0;
$totalCases  = count($testInputs);
$lastResult  = ['type'=>'output','output'=>'','error'=>null,'expected'=>'','actual'=>''];

foreach ($testInputs as $idx => $tc) {
    $caseDir = $sandboxDir . DIRECTORY_SEPARATOR . 'tc' . $idx;
    mkdir($caseDir, 0700, true);

    $result       = executeCode($language, $code, $tc['input'], $caseDir);
    $actualOutput = trim($result['output'] ?? '');
    $evalResult   = smartVerify($levelId, $langId, $tc['input'], $actualOutput, $result['type']);
    $passed       = $evalResult['passed'];

    if ($passed) $passedCount++;
    $lastResult = array_merge($result, ['expected' => $evalResult['expected'], 'actual' => $actualOutput]);
    $results[]  = [
        'case'     => $idx + 1,
        'passed'   => $passed,
        'expected' => $evalResult['expected'],
        'actual'   => $actualOutput,
        'input'    => $tc['input'],
        'error'    => ($result['type'] !== 'output') ? ($result['error'] ?? '') : null,
        'type'     => $result['type'],
    ];
}

deleteDir($sandboxDir);
$levelCompleted = ($passedCount === $totalCases && $totalCases > 0);
if ($levelCompleted && $langId && $levelId) { markLevelComplete($userId, $langId, $levelId); }

echo json_encode([
    'success'         => true,
    'type'            => 'evaluation',
    'test_results'    => $results,
    'passed'          => $passedCount,
    'total'           => $totalCases,
    'level_completed' => $levelCompleted,
    'feedback'        => '',
]);
exit;

// ============================================================
// EXECUTE CODE — routes to language handler
// ============================================================
function executeCode(string $lang, string $code, string $input, string $dir): array {
    $start = microtime(true);
    switch ($lang) {
        case 'c':          return compileAndRun_C($code, $input, $dir, $start);
        case 'cpp':        return compileAndRun_CPP($code, $input, $dir, $start);
        case 'java':       return compileAndRun_Java($code, $input, $dir, $start);
        case 'python':     return run_Python($code, $input, $dir, $start);
        case 'php':        return run_PHP($code, $input, $dir, $start);
        case 'javascript': return run_JavaScript($code, $input, $dir, $start);
        default:           return makeError('Unsupported language.');
    }
}

// ============================================================
// C
// ============================================================
function compileAndRun_C(string $code, string $input, string $dir, float $start): array {
    $src = $dir . DIRECTORY_SEPARATOR . 'main.c';
    $exe = $dir . DIRECTORY_SEPARATOR . 'main.exe';
    file_put_contents($src, $code);
    $compileOut = shell_exec(GCC_PATH . ' ' . escapeshellarg($src) . ' -o ' . escapeshellarg($exe) . ' 2>&1');
    if (!file_exists($exe)) {
        return ['type' => 'compile_error', 'output' => '', 'error' => trim($compileOut), 'time' => null];
    }
    return runWithSeparateStreams(escapeshellarg($exe), $input, $dir, $start);
}

// ============================================================
// C++
// ============================================================
function compileAndRun_CPP(string $code, string $input, string $dir, float $start): array {
    $src = $dir . DIRECTORY_SEPARATOR . 'main.cpp';
    $exe = $dir . DIRECTORY_SEPARATOR . 'main.exe';
    file_put_contents($src, $code);
    $compileOut = shell_exec(GPP_PATH . ' ' . escapeshellarg($src) . ' -o ' . escapeshellarg($exe) . ' 2>&1');
    if (!file_exists($exe)) {
        return ['type' => 'compile_error', 'output' => '', 'error' => trim($compileOut), 'time' => null];
    }
    return runWithSeparateStreams(escapeshellarg($exe), $input, $dir, $start);
}

// ============================================================
// Java
// ============================================================
function compileAndRun_Java(string $code, string $input, string $dir, float $start): array {
    $src   = $dir . DIRECTORY_SEPARATOR . 'Main.java';
    $class = $dir . DIRECTORY_SEPARATOR . 'Main.class';
    file_put_contents($src, $code);
    $compileOut = shell_exec(JAVA_BIN . ' ' . escapeshellarg($src) . ' 2>&1');
    if (!file_exists($class)) {
        return ['type' => 'compile_error', 'output' => '', 'error' => trim($compileOut), 'time' => null];
    }
    $cmd = JAVA_RUN . ' -cp ' . escapeshellarg($dir) . ' Main';
    return runWithSeparateStreams($cmd, $input, $dir, $start);
}

// ============================================================
// Python — silent input() prompts
// ============================================================
function run_Python(string $code, string $input, string $dir, float $start): array {
    $src = $dir . DIRECTORY_SEPARATOR . 'main.py';
    // Override input() to suppress prompt — matches HackerRank/LeetCode behaviour
    $silencer  = "import builtins\n";
    $silencer .= "_orig_input = builtins.input\n";
    $silencer .= "builtins.input = lambda prompt='': _orig_input('')\n";
    file_put_contents($src, $silencer . $code);
    $cmd = PYTHON_BIN . ' ' . escapeshellarg($src);
    return runWithSeparateStreams($cmd, $input, $dir, $start);
}

// ============================================================
// PHP
// ============================================================
function run_PHP(string $code, string $input, string $dir, float $start): array {
    $src = $dir . DIRECTORY_SEPARATOR . 'main.php';
    file_put_contents($src, $code);
    $cmd = PHP_CLI . ' ' . escapeshellarg($src);
    return runWithSeparateStreams($cmd, $input, $dir, $start);
}

// ============================================================
// JavaScript (Node.js)
// ============================================================
function run_JavaScript(string $code, string $input, string $dir, float $start): array {
    $src = $dir . DIRECTORY_SEPARATOR . 'main.js';
    file_put_contents($src, $code);
    $cmd = NODE_BIN . ' ' . escapeshellarg($src);
    return runWithSeparateStreams($cmd, $input, $dir, $start);
}

// ============================================================
// CORE RUNNER — stdout and stderr separated
// This is THE fix: prompt lines never mix with real output
// ============================================================
function runWithSeparateStreams(string $cmd, string $input, string $dir, float $start): array {
    $inpFile  = $dir . DIRECTORY_SEPARATOR . 'input.txt';
    $outFile  = $dir . DIRECTORY_SEPARATOR . 'stdout.txt';
    $errFile  = $dir . DIRECTORY_SEPARATOR . 'stderr.txt';

    file_put_contents($inpFile, $input);

    $fullCmd = $cmd
        . ' < '  . escapeshellarg($inpFile)
        . ' > '  . escapeshellarg($outFile)
        . ' 2> ' . escapeshellarg($errFile);

    shell_exec($fullCmd);
    $elapsed = round((microtime(true) - $start) * 1000, 2);

    $stdout = file_exists($outFile) ? file_get_contents($outFile) : '';
    $stderr = file_exists($errFile) ? file_get_contents($errFile) : '';

    // Runtime/compile errors
    if (trim($stdout) === '' && trim($stderr) !== '') {
        if (preg_match('/^(Exception in thread|Traceback|Fatal error|Segmentation|Error:|TypeError:|ValueError:|NameError:|IndexError:|SyntaxError:|IndentationError:|ZeroDivisionError:|RecursionError:)/m', $stderr)) {
            return ['type' => 'runtime_error', 'output' => '', 'error' => trim($stderr), 'time' => $elapsed];
        }
        return ['type' => 'runtime_error', 'output' => '', 'error' => trim($stderr), 'time' => $elapsed];
    }

    // Clean prompt lines from stdout
    $cleaned = stripPromptLines($stdout, $input);
    return ['type' => 'output', 'output' => trim($cleaned), 'error' => null, 'time' => $elapsed];
}

// ============================================================
// STRIP PROMPT LINES
// Removes lines like:
//   "Enter age: "          (pure prompt)
//   "Enter value: 12"      (prompt + echoed input)
//   "Input n: "
// Keeps lines that are actual program output
// ============================================================
function stripPromptLines(string $stdout, string $input): string {
    if (trim($stdout) === '') return $stdout;

    // All values the user provided as input
    $inputValues = array_filter(
        array_map('trim', preg_split('/\r?\n/', trim($input))),
        fn($v) => $v !== ''
    );

    $lines      = preg_split('/\r?\n/', $stdout);
    $cleanLines = [];

    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') {
            $cleanLines[] = $line; // keep blank lines
            continue;
        }

        $isPrompt = false;

        // Case 1: Line ends with one of the input values
        // e.g. stdout = "Enter age: 12"  input = "12"  → prompt
        foreach ($inputValues as $val) {
            if ($val !== '' && (
                $t === $val ||
                substr($t, -strlen($val)) === $val
            )) {
                $prefix = rtrim(substr($t, 0, strlen($t) - strlen($val)));
                // Prefix ends with prompt characters
                if (preg_match('/[:\?>\-=]\s*$/', $prefix) ||
                    preg_match('/(enter|input|give|type|read|provide|value|number|age|name)\s*[:\?>\s]*$/i', $prefix)) {
                    $isPrompt = true;
                    break;
                }
            }
        }

        // Case 2: Pure prompt line (no actual data after it)
        // e.g. "Enter your age: " or "Input n: "
        if (!$isPrompt) {
            if (preg_match('/^\s*(enter|please\s+enter|input|give|type|provide|read)\b.{0,60}[:\?>\s]\s*$/i', $t)) {
                $isPrompt = true;
            }
        }

        if (!$isPrompt) {
            $cleanLines[] = $line;
        }
    }

    return implode("\n", $cleanLines);
}

// ============================================================
// SMART EVALUATOR
// ============================================================
function generateTestInputs(int $levelId, int $langId, int $count = 4): array {
    $tests = [];
    for ($i = 0; $i < $count; $i++) {
        $tests[] = ['input' => randomInput($levelId, $langId, $i)];
    }
    return $tests;
}

function randomInput(int $levelId, int $langId, int $seed): string {
    switch ($levelId) {
        case 1:
            return '';
        case 2:
            return '';
        case 3:
            $pools = [
                1 => [95, 82, 65, 45],
                2 => [42, 30, 18, 8],
                3 => [10, -5, 0, 25],
                4 => [70, 25, 15, 8],
                5 => [1, 3, 5, 7],
                6 => [6, 4, 9, 7],
            ];
            $pool = $pools[$langId] ?? [10, -5, 0, 25];
            return (string)$pool[$seed % count($pool)];

        case 4:
            $pools = [
                1 => [2, 5, 7, 9],
                2 => [6, 10, 14, 12],
                3 => [123, 456, 999, 100],
                4 => [10, 15, 20, 5],
                5 => [12345, 9800, 100, 7],
                6 => [5, 7, 10, 15],
            ];
            $pool = $pools[$langId] ?? [5, 8, 10, 12];
            return (string)$pool[$seed % count($pool)];

        case 5:
            $pools = [
                1 => [7, 4, 11, 1],
                2 => ['2 8', '3 4', '5 3', '2 10'],
                3 => ['hello', 'java', 'world', 'code'],
                4 => ["70\n1.75", "90\n1.70", "50\n1.75", "110\n1.65"],
                5 => ['Hello World', 'Programming', 'aeiou', 'rhythm'],
                6 => [100, 0, 37, 25],
            ];
            $pool = $pools[$langId] ?? [5, 7, 9, 11];
            return (string)$pool[$seed % count($pool)];

        case 6:
            $arrays = [
                "5\n10 20 30 40 50",
                "4\n5 1 9 3",
                "6\n3 1 4 1 5 9",
                "3\n7 2 8",
            ];
            return $arrays[$seed % count($arrays)];

        case 7:
            $pools = [
                1 => ['madam', 'hello', 'racecar', 'world'],
                2 => [12, 7, 16, 20],
                3 => ["5\n10 20 30 40 50\n30", "5\n10 20 30 40 50\n99", "4\n2 4 6 8\n4", "3\n1 3 5\n7"],
                4 => ['48 18', '100 75', '7 5', '36 24'],
                5 => ['banana', 'hello', 'programming', 'aabb'],
                6 => ["5\n64 34 25 12 22", "4\n5 3 8 1", "3\n9 2 7", "6\n3 6 1 4 2 5"],
            ];
            $pool = $pools[$langId] ?? ['hello', 'world', 'madam', 'code'];
            return (string)$pool[$seed % count($pool)];

        default:
            return '';
    }
}

function smartVerify(int $levelId, int $langId, string $input, string $actualOutput, string $resultType): array {
    if ($resultType !== 'output') {
        return ['passed' => false, 'expected' => '(program must run without errors)'];
    }
    $actual = trim($actualOutput);
    $inp    = trim($input);

    switch ($levelId) {
        case 1:
            $fixed = [
                1 => "*** Welcome to C ***\nStart Learning Today!",
                2 => "Welcome to C++!\nObject Oriented Programming is Fun.",
                3 => "Java is Platform Independent!\nWrite Once, Run Anywhere.",
                4 => "Python is Easy!\nCoding is Fun.",
                5 => "PHP Powers the Web!\nI am Learning PHP.",
                6 => "JavaScript Runs Everywhere!\nI am a Web Developer.",
            ];
            $expected = $fixed[$langId] ?? 'Hello, World!';
            return ['passed' => ($actual === $expected), 'expected' => $expected];

        case 2:
            $fixed = [
                1 => "Name: Arjun Sharma\nRoll No: 101\nMarks: 87\nPercentage: 87.00%",
                2 => "Product: Laptop\nBrand: Dell\nPrice: 55000\nIn Stock: Yes",
                3 => "Employee: Priya Mehta\nDepartment: Computer Science\nSalary: 45000\nIs Permanent: true",
                4 => "City: Pune\nPopulation: 3124458\nArea: 331.26 sq km\nFamous For: IT Hub",
                5 => "Username: dev_rahul\nRole: Full Stack Developer\nExperience: 3 years\nActive: true",
                6 => "App: CodeQuest\nVersion: 2.0\nLanguage: JavaScript\nOpen Source: true",
            ];
            $expected = $fixed[$langId] ?? 'Hello';
            return ['passed' => ($actual === $expected), 'expected' => $expected];

        case 3: return verifyLevel3($langId, $inp, $actual);
        case 4: return verifyLevel4($langId, $inp, $actual);
        case 5: return verifyLevel5($langId, $inp, $actual);
        case 6: return verifyLevel6($langId, $inp, $actual);
        case 7: return verifyLevel7($langId, $inp, $actual);
        default: return ['passed' => false, 'expected' => '?'];
    }
}

function verifyLevel3(int $langId, string $inp, string $actual): array {
    switch ($langId) {
        case 1: // C — Grade (A/B/C/D/F)
            $m = (int)$inp;
            if ($m >= 90)     $exp = 'Grade: A';
            elseif ($m >= 75) $exp = 'Grade: B';
            elseif ($m >= 60) $exp = 'Grade: C';
            elseif ($m >= 40) $exp = 'Grade: D';
            else              $exp = 'Grade: F';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 2: // C++ — Temperature
            $t = (int)$inp;
            if ($t >= 40)     $exp = 'Hot';
            elseif ($t >= 25) $exp = 'Warm';
            elseif ($t >= 15) $exp = 'Cool';
            else              $exp = 'Cold';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 3: // Java — Positive/Negative/Zero
            $n = (int)$inp;
            if ($n > 0)      $exp = 'Positive';
            elseif ($n < 0)  $exp = 'Negative';
            else             $exp = 'Zero';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 4: // Python — Age stage
            $age = (int)$inp;
            if ($age >= 60)     $exp = 'Senior';
            elseif ($age >= 18) $exp = 'Adult';
            elseif ($age >= 13) $exp = 'Teenager';
            else                $exp = 'Child';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 5: // PHP — Day of week
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $n = (int)$inp;
            $exp = ($n >= 1 && $n <= 7) ? $days[$n-1] : '?';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 6: // JS — FizzBuzz
            $n = (int)$inp;
            if ($n % 2 === 0 && $n % 3 === 0)    $exp = 'FizzBuzz';
            elseif ($n % 2 === 0)                  $exp = 'Fizz';
            elseif ($n % 3 === 0)                  $exp = 'Buzz';
            else                                   $exp = 'None';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
    }
    return ['passed' => false, 'expected' => '?'];
}

function verifyLevel4(int $langId, string $inp, string $actual): array {
    $n = (int)$inp;
    switch ($langId) {
        case 1: // C — Multiplication table
            $lines = [];
            for ($i = 1; $i <= 10; $i++) $lines[] = "$n x $i = " . ($n * $i);
            $exp = implode("\n", $lines);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 2: // C++ — Even numbers
            $evens = [];
            for ($i = 2; $i <= $n; $i += 2) $evens[] = $i;
            $exp = implode("\n", $evens);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 3: // Java — Sum of digits
            $sum = array_sum(str_split((string)abs($n)));
            return ['passed' => ($actual === (string)$sum), 'expected' => (string)$sum];
        case 4: // Python — Primes
            $primes = [];
            for ($num = 2; $num <= $n; $num++) {
                $ok = true;
                for ($i = 2; $i < $num; $i++) { if ($num%$i===0){$ok=false;break;} }
                if ($ok) $primes[] = $num;
            }
            $exp = implode("\n", $primes);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 5: // PHP — Reverse number
            $rev = (int)strrev((string)abs($n));
            return ['passed' => ($actual === (string)$rev), 'expected' => (string)$rev];
        case 6: // JS — Sum of odd numbers
            $sum = 0;
            for ($i = 1; $i <= $n; $i += 2) $sum += $i;
            return ['passed' => ($actual === (string)$sum), 'expected' => (string)$sum];
    }
    return ['passed' => false, 'expected' => '?'];
}

function verifyLevel5(int $langId, string $inp, string $actual): array {
    switch ($langId) {
        case 1: // C — isPrime
            $n = (int)$inp;
            $ok = $n >= 2;
            if ($ok) for ($i=2; $i*$i<=$n; $i++) if ($n%$i===0){$ok=false;break;}
            $exp = $ok ? 'Prime' : 'Not Prime';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 2: // C++ — Power
            $parts = explode(' ', $inp);
            $base = (int)($parts[0]??0); $e = (int)($parts[1]??0);
            $exp = (string)(int)pow($base, $e);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 3: // Java — Reverse string
            $exp = strrev($inp);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 4: // Python — BMI
            $parts = explode("\n", $inp);
            $w = (float)($parts[0]??0); $h = (float)($parts[1]??1);
            $bmi = $w / ($h * $h);
            if ($bmi >= 30)       $exp = 'Obese';
            elseif ($bmi >= 25)   $exp = 'Overweight';
            elseif ($bmi >= 18.5) $exp = 'Normal';
            else                  $exp = 'Underweight';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 5: // PHP — Count vowels
            $count = preg_match_all('/[aeiou]/i', $inp);
            $exp = (string)$count;
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 6: // JS — Celsius to Fahrenheit
            $c = (float)$inp;
            $exp = number_format(($c * 9/5) + 32, 2);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
    }
    return ['passed' => false, 'expected' => '?'];
}

function verifyLevel6(int $langId, string $inp, string $actual): array {
    $lines = explode("\n", $inp);
    $nums  = array_map('intval', explode(' ', trim($lines[1] ?? '')));
    switch ($langId) {
        case 1: // C — Second largest
            $u = array_unique($nums); rsort($u);
            $exp = isset($u[1]) ? (string)$u[1] : (string)$u[0];
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 2: // C++ — Reverse array
            $exp = implode(' ', array_reverse($nums));
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 3: // Java — Average
            $exp = number_format(array_sum($nums)/count($nums), 2);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 4: // Python — Even/Odd count
            $even = count(array_filter($nums, fn($x) => $x%2===0));
            $odd  = count($nums) - $even;
            $exp  = "Even: $even\nOdd: $odd";
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 5: // PHP — Sum and Product
            $exp = "Sum: " . array_sum($nums) . "\nProduct: " . array_product($nums);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 6: // JS — Min and Max
            $exp = "Min: " . min($nums) . "\nMax: " . max($nums);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
    }
    return ['passed' => false, 'expected' => '?'];
}

function verifyLevel7(int $langId, string $inp, string $actual): array {
    switch ($langId) {
        case 1: // C — Palindrome
            $s = strtolower($inp);
            $exp = ($s === strrev($s)) ? 'Palindrome' : 'Not Palindrome';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 2: // C++ — Factors
            $n = (int)$inp;
            $f = [];
            for ($i=1; $i<=$n; $i++) if ($n%$i===0) $f[]=$i;
            $exp = implode(' ', $f);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 3: // Java — Binary search
            $lines  = explode("\n", $inp);
            $nums   = array_map('intval', explode(' ', trim($lines[1]??'')));
            $target = (int)($lines[2]??0);
            $idx    = array_search($target, $nums);
            $exp    = ($idx !== false) ? (string)$idx : '-1';
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 4: // Python — GCD
            $parts = explode(' ', $inp);
            $a=(int)($parts[0]??0); $b=(int)($parts[1]??0);
            while ($b) { $t=$b; $b=$a%$b; $a=$t; }
            return ['passed' => ($actual === (string)$a), 'expected' => (string)$a];
        case 5: // PHP — Char frequency
            $freq = array_count_values(str_split($inp));
            ksort($freq);
            $lines = [];
            foreach ($freq as $ch=>$cnt) $lines[] = "$ch:$cnt";
            $exp = implode("\n", $lines);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
        case 6: // JS — Bubble sort
            $lines = explode("\n", $inp);
            $nums  = array_map('intval', explode(' ', trim($lines[1]??'')));
            sort($nums);
            $exp = implode(' ', $nums);
            return ['passed' => ($actual === $exp), 'expected' => $exp];
    }
    return ['passed' => false, 'expected' => '?'];
}

// ============================================================
// DB HELPERS
// ============================================================
function markLevelComplete(int $userId, int $langId, int $levelId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT xp_reward FROM levels WHERE id=?");
        $stmt->bind_param("i",$levelId); $stmt->execute();
        $level = $stmt->get_result()->fetch_assoc(); $stmt->close();
        $xp = $level['xp_reward'] ?? 100;

        $stmt = $db->prepare("SELECT id FROM progress WHERE user_id=? AND language_id=? AND level_id=? AND completed=1");
        $stmt->bind_param("iii",$userId,$langId,$levelId); $stmt->execute();
        $alreadyDone = $stmt->get_result()->num_rows > 0; $stmt->close();

        $stmt = $db->prepare("INSERT INTO progress (user_id,language_id,level_id,completed,completed_at,xp_earned) VALUES (?,?,?,1,NOW(),?) ON DUPLICATE KEY UPDATE completed=1,completed_at=NOW(),xp_earned=?");
        $stmt->bind_param("iiiii",$userId,$langId,$levelId,$xp,$xp); $stmt->execute(); $stmt->close();

        if (!$alreadyDone) {
            $stmt = $db->prepare("UPDATE users SET total_xp=total_xp+? WHERE id=?");
            $stmt->bind_param("ii",$xp,$userId); $stmt->execute(); $stmt->close();
        }
        $db->close();
    } catch (Throwable $e) {}
}

function deleteDir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item==='.'||$item==='..') continue;
        $path = $dir.DIRECTORY_SEPARATOR.$item;
        is_dir($path) ? deleteDir($path) : @unlink($path);
    }
    @rmdir($dir);
}

function cleanOldTempDirs(): void {
    if (!is_dir(TEMP_BASE)) return;
    foreach (scandir(TEMP_BASE) as $item) {
        if ($item==='.'||$item==='..') continue;
        $path = TEMP_BASE.DIRECTORY_SEPARATOR.$item;
        if (is_dir($path) && filemtime($path) < time()-300) { deleteDir($path); }
    }
}

function makeError(string $msg): array {
    return ['type'=>'compile_error','output'=>'','error'=>$msg,'time'=>null];
}

function jsonError(string $msg): void {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$msg]);
    exit;
}
