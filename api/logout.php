<?php
header('Content-Type: application/json');
session_start();

// Destroy session
session_unset();
session_destroy();

// Return success response
echo json_encode(['success' => true, 'message' => 'Logged out successfully']); 