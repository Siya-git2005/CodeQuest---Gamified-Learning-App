<?php

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request');
}

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$postUserId = (int)($_POST['user_id'] ?? 0);

if ($sessionUserId <= 0 || $postUserId <= 0) {
    exit('Invalid user');
}

if ($sessionUserId !== $postUserId) {
    exit('Unauthorized');
}

$stmt = $conn->prepare(
    "UPDATE users SET tutorial_seen = 1 WHERE id = ?"
);

$stmt->bind_param("i", $sessionUserId);
$stmt->execute();
$stmt->close();

echo "success";
?>