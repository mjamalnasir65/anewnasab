<?php
header('Content-Type: application/json');
session_start();

// DB connection
$conn = new mysqli('localhost', 'root', '', 'anewnasab');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the user's family (for now, assume one family per user)
$family_id = null;
$stmt = $conn->prepare('SELECT id FROM families WHERE created_by = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($family_id);
$stmt->fetch();
$stmt->close();

if (!$family_id) {
    // Create a family for the user if not exists
    $stmt = $conn->prepare('INSERT INTO families (name, created_by) VALUES (?, ?)');
    $default_family_name = 'My Family';
    $stmt->bind_param('si', $default_family_name, $user_id);
    $stmt->execute();
    $family_id = $stmt->insert_id;
    $stmt->close();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Add new member
    $data = json_decode(file_get_contents('php://input'), true);
    $full_name = isset($data['full_name']) ? trim($data['full_name']) : '';
    $gender = isset($data['gender']) ? $data['gender'] : 'other';
    $birth_date = isset($data['birth_date']) ? $data['birth_date'] : null;
    $death_date = isset($data['death_date']) ? $data['death_date'] : null;
    $notes = isset($data['notes']) ? $data['notes'] : null;
    $photo = isset($data['photo']) ? $data['photo'] : null;

    if (!$full_name || !$gender || !$birth_date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Full name, gender, and birth date are required']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender, birth_date, death_date, notes, photo) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssss', $family_id, $full_name, $gender, $birth_date, $death_date, $notes, $photo);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'member' => [
                'id' => $stmt->insert_id,
                'full_name' => $full_name,
                'gender' => $gender,
                'birth_date' => $birth_date,
                'death_date' => $death_date,
                'notes' => $notes,
                'photo' => $photo
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add member']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Get a single member by ID
        $member_id = intval($_GET['id']);
        $stmt = $conn->prepare('SELECT id, full_name, gender, birth_date, death_date, notes, photo FROM members WHERE id = ? AND family_id = ? LIMIT 1');
        $stmt->bind_param('ii', $member_id, $family_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        if ($member) {
            echo json_encode(['success' => true, 'member' => $member]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Member not found.']);
        }
        $stmt->close();
        $conn->close();
        exit;
    } else {
        // Get all members for this family
        $stmt = $conn->prepare('SELECT id, full_name, gender, birth_date, death_date, notes, photo FROM members WHERE family_id = ? ORDER BY id ASC');
        $stmt->bind_param('i', $family_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        echo json_encode(['success' => true, 'members' => $members]);
        $stmt->close();
        $conn->close();
        exit;
    }
}

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_parents') {
    // Debug: log the raw input and parsed data
    $raw_input = file_get_contents('php://input');
    file_put_contents(__DIR__ . '/debug_add_parents.log', "RAW INPUT:\n" . $raw_input . "\n", FILE_APPEND);
    $data = json_decode($raw_input, true);
    file_put_contents(__DIR__ . '/debug_add_parents.log', "PARSED DATA:\n" . print_r($data, true) . "\n", FILE_APPEND);
    $child_id = isset($data['child_id']) ? intval($data['child_id']) : 0;
    if (!$child_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing child_id']);
        exit;
    }
    // Get child's name for default parent names
    $stmt = $conn->prepare('SELECT full_name FROM members WHERE id = ? AND family_id = ?');
    $stmt->bind_param('ii', $child_id, $family_id);
    $stmt->execute();
    $stmt->bind_result($child_name);
    $stmt->fetch();
    $stmt->close();
    if (!$child_name) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Child not found']);
        exit;
    }
    // Create mother
    $mother_name = 'Mother of ' . $child_name;
    $stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender) VALUES (?, ?, ?)');
    $gender_female = 'female';
    $stmt->bind_param('iss', $family_id, $mother_name, $gender_female);
    $stmt->execute();
    $mother_id = $stmt->insert_id;
    // Create father
    $father_name = 'Father of ' . $child_name;
    $gender_male = 'male';
    $stmt->bind_param('iss', $family_id, $father_name, $gender_male);
    $stmt->execute();
    $father_id = $stmt->insert_id;
    $stmt->close();
    // Add relationships (parent -> child)
    $rel_type = 'parent';
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, ?), (?, ?, ?, ?)');
    $stmt->bind_param('iiisiiis', $family_id, $mother_id, $child_id, $rel_type, $family_id, $father_id, $child_id, $rel_type);
    $stmt->execute();
    $stmt->close();
    // Return new parent data
    $mother = [ 'id' => $mother_id, 'full_name' => $mother_name, 'gender' => 'female' ];
    $father = [ 'id' => $father_id, 'full_name' => $father_name, 'gender' => 'male' ];
    echo json_encode(['success' => true, 'mother' => $mother, 'father' => $father]);
    $conn->close();
    exit;
}

// If not POST or GET
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
if ($conn && $conn->connect_errno === 0) {
    $conn->close();
} 