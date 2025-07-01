<?php
header('Content-Type: application/json');
session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            // Optionally add phone if stored in session
        ]
    ]);
} else {
    echo json_encode(['success' => false]);
} 