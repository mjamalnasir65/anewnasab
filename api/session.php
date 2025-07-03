<?php
header('Content-Type: application/json');
session_start();

// Custom logger
function log_message($msg) {
    $logfile = __DIR__ . '/../errorlog.txt'; // changed from debug.log
    file_put_contents($logfile, "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

if (isset($_SESSION['user_id'])) {
    log_message("Session check: user_id={$_SESSION['user_id']}, email={$_SESSION['email']}");
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            // Optionally add phone if stored in session
        ]
    ]);
} else {
    log_message("Session check: no active session");
    echo json_encode(['success' => false]);
}