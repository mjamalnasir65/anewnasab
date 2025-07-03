<?php
header('Content-Type: application/json');
session_start();

// Custom logger
function log_message($msg) {
    $logfile = __DIR__ . '/../errorlog.txt'; // changed from debug.log
    file_put_contents($logfile, "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'anewnasab');
if ($conn->connect_error) {
    log_message("DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Validate input
if (!$email || !$password) {
    log_message("Login failed: missing email or password");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

// Check user by email
$stmt = $conn->prepare('SELECT id, email, phone, password_hash FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    log_message("Login failed: user not found for email $email");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->bind_result($id, $db_email, $phone, $password_hash);
$stmt->fetch();

// Verify password
if (!password_verify($password, $password_hash)) {
    log_message("Login failed: wrong password for email $email");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    $stmt->close();
    $conn->close();
    exit;
}

// Set session
$_SESSION['user_id'] = $id;
$_SESSION['email'] = $db_email;
log_message("Login success: user $id ($db_email)");

// Success response
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $id,
        'email' => $db_email,
        'phone' => $phone
    ]
]);

$stmt->close();
$conn->close();