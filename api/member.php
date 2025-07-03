<?php
header('Content-Type: application/json');
session_start();

// Logger function
function log_message($msg) {
    $logfile = __DIR__ . '/../errorlog.txt';
    file_put_contents($logfile, "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

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
    $input = json_decode(file_get_contents('php://input'), true);
    $child_id = intval($input['child_id']);
    $full_name = trim($input['full_name'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $birth_date = trim($input['birth_date'] ?? '');

    log_message("add_parents called: child_id=$child_id, full_name=$full_name, gender=$gender, birth_date=$birth_date");

    if (!$child_id || !$full_name || !$gender || !$birth_date) {
        log_message("add_parents error: missing required fields");
        echo json_encode(['success' => false, 'error' => 'Full name, gender, and birth date are required']);
        exit;
    }

    // Get family_id of the child
    $stmt = $conn->prepare('SELECT family_id FROM members WHERE id = ?');
    if (!$stmt) {
        log_message("add_parents error: prepare failed for SELECT family_id: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('i', $child_id);
    $stmt->execute();
    $stmt->bind_result($family_id);
    if (!$stmt->fetch()) {
        log_message("add_parents error: child not found for id $child_id");
        echo json_encode(['success' => false, 'error' => 'Child not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert parent member
    $stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender, birth_date) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        log_message("add_parents error: prepare failed for INSERT member: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('isss', $family_id, $full_name, $gender, $birth_date);
    if (!$stmt->execute()) {
        log_message("add_parents error: execute failed for INSERT member: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to add parent']);
        $stmt->close();
        exit;
    }
    $parent_id = $stmt->insert_id;
    log_message("add_parents: parent inserted with id $parent_id");
    $stmt->close();

    // Log all IDs before insert
    log_message("add_parents: Insert relationship with family_id=$family_id, parent_id=$parent_id, child_id=$child_id");

    // Insert relationship: parent -> child
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, "parent")');
    if (!$stmt) {
        log_message("add_parents error: prepare failed for INSERT relationship: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('iii', $family_id, $parent_id, $child_id);
    if (!$stmt->execute()) {
        log_message("add_parents error: execute failed for INSERT relationship: " . $stmt->error . " | SQLSTATE: " . $stmt->sqlstate);
        echo json_encode(['success' => false, 'error' => 'Failed to add relationship']);
        $stmt->close();
        exit;
    }
    log_message("add_parents: relationship inserted parent_id=$parent_id, child_id=$child_id");
    $stmt->close();

    // Optionally, fetch the new parent member to return
    $stmt = $conn->prepare('SELECT id, full_name, gender, birth_date FROM members WHERE id = ?');
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $parent = $result->fetch_assoc();
    $stmt->close();

    log_message("add_parents: success for parent_id=$parent_id, child_id=$child_id");
    echo json_encode(['success' => true, 'member' => $parent]);
    exit;
}

// Get family tree with relationships
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'tree') {
    // Get all members with their relationships
    $stmt = $conn->prepare('
        SELECT 
            m.id, m.full_name, m.gender, m.birth_date, m.death_date, m.notes,
            r.relationship_type, r.related_member_id
        FROM members m
        LEFT JOIN relationships r ON m.id = r.member_id
        WHERE m.family_id = ?
        ORDER BY m.id ASC
    ');
    $stmt->bind_param('i', $family_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    $relationships = [];
    
    while ($row = $result->fetch_assoc()) {
        $member_id = $row['id'];
        
        // Add member if not already added
        if (!isset($members[$member_id])) {
            $members[$member_id] = [
                'id' => $member_id,
                'full_name' => $row['full_name'],
                'gender' => $row['gender'],
                'birth_date' => $row['birth_date'],
                'death_date' => $row['death_date'],
                'notes' => $row['notes'],
                'parents' => [],
                'children' => []
            ];
        }
        
        // Add relationship if exists
        if ($row['relationship_type'] && $row['related_member_id']) {
            $relationships[] = [
                'member_id' => $member_id,
                'related_member_id' => $row['related_member_id'],
                'relationship_type' => $row['relationship_type']
            ];
        }
    }
    $stmt->close();
    
    // Process relationships to build family structure
    foreach ($relationships as $rel) {
        if ($rel['relationship_type'] === 'parent') {
            $parent_id = $rel['member_id'];
            $child_id = $rel['related_member_id'];
            
            if (isset($members[$parent_id]) && isset($members[$child_id])) {
                $members[$child_id]['parents'][] = $parent_id;
                $members[$parent_id]['children'][] = $child_id;
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'members' => array_values($members),
        'relationships' => $relationships
    ]);
    $conn->close();
    exit;
}

// Add Partner
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_partner') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = intval($input['member_id']);
    $full_name = trim($input['full_name'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $birth_date = trim($input['birth_date'] ?? '');

    log_message("add_partner called: member_id=$member_id, full_name=$full_name, gender=$gender, birth_date=$birth_date");

    if (!$member_id || !$full_name || !$gender || !$birth_date) {
        log_message("add_partner error: missing required fields");
        echo json_encode(['success' => false, 'error' => 'Full name, gender, and birth date are required']);
        exit;
    }

    // Get family_id of the member
    $stmt = $conn->prepare('SELECT family_id FROM members WHERE id = ?');
    if (!$stmt) {
        log_message("add_partner error: prepare failed for SELECT family_id: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $stmt->bind_result($family_id);
    if (!$stmt->fetch()) {
        log_message("add_partner error: member not found for id $member_id");
        echo json_encode(['success' => false, 'error' => 'Member not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert partner member
    $stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender, birth_date) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        log_message("add_partner error: prepare failed for INSERT member: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('isss', $family_id, $full_name, $gender, $birth_date);
    if (!$stmt->execute()) {
        log_message("add_partner error: execute failed for INSERT member: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to add partner']);
        $stmt->close();
        exit;
    }
    $partner_id = $stmt->insert_id;
    log_message("add_partner: partner inserted with id $partner_id");
    $stmt->close();

    // Insert relationship: partner <-> member (bidirectional)
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, "partner"), (?, ?, ?, "partner")');
    if (!$stmt) {
        log_message("add_partner error: prepare failed for INSERT relationship: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('iiiiii', $family_id, $partner_id, $member_id, $family_id, $member_id, $partner_id);
    if (!$stmt->execute()) {
        log_message("add_partner error: execute failed for INSERT relationship: " . $stmt->error . " | SQLSTATE: " . $stmt->sqlstate);
        echo json_encode(['success' => false, 'error' => 'Failed to add relationship']);
        $stmt->close();
        exit;
    }
    log_message("add_partner: relationship inserted partner_id=$partner_id, member_id=$member_id");
    $stmt->close();

    // Optionally, fetch the new partner member to return
    $stmt = $conn->prepare('SELECT id, full_name, gender, birth_date FROM members WHERE id = ?');
    $stmt->bind_param('i', $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $partner = $result->fetch_assoc();
    $stmt->close();

    log_message("add_partner: success for partner_id=$partner_id, member_id=$member_id");
    echo json_encode(['success' => true, 'member' => $partner]);
    exit;
}

// Add Sibling
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_sibling') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = intval($input['member_id']);
    $full_name = trim($input['full_name'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $birth_date = trim($input['birth_date'] ?? '');

    log_message("add_sibling called: member_id=$member_id, full_name=$full_name, gender=$gender, birth_date=$birth_date");

    if (!$member_id || !$full_name || !$gender || !$birth_date) {
        log_message("add_sibling error: missing required fields");
        echo json_encode(['success' => false, 'error' => 'Full name, gender, and birth date are required']);
        exit;
    }

    // Get family_id of the member
    $stmt = $conn->prepare('SELECT family_id FROM members WHERE id = ?');
    if (!$stmt) {
        log_message("add_sibling error: prepare failed for SELECT family_id: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $stmt->bind_result($family_id);
    if (!$stmt->fetch()) {
        log_message("add_sibling error: member not found for id $member_id");
        echo json_encode(['success' => false, 'error' => 'Member not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert sibling member
    $stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender, birth_date) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        log_message("add_sibling error: prepare failed for INSERT member: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('isss', $family_id, $full_name, $gender, $birth_date);
    if (!$stmt->execute()) {
        log_message("add_sibling error: execute failed for INSERT member: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to add sibling']);
        $stmt->close();
        exit;
    }
    $sibling_id = $stmt->insert_id;
    log_message("add_sibling: sibling inserted with id $sibling_id");
    $stmt->close();

    // Insert relationship: sibling <-> member (bidirectional)
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, "sibling"), (?, ?, ?, "sibling")');
    if (!$stmt) {
        log_message("add_sibling error: prepare failed for INSERT relationship: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('iiiiii', $family_id, $sibling_id, $member_id, $family_id, $member_id, $sibling_id);
    if (!$stmt->execute()) {
        log_message("add_sibling error: execute failed for INSERT relationship: " . $stmt->error . " | SQLSTATE: " . $stmt->sqlstate);
        echo json_encode(['success' => false, 'error' => 'Failed to add relationship']);
        $stmt->close();
        exit;
    }
    log_message("add_sibling: relationship inserted sibling_id=$sibling_id, member_id=$member_id");
    $stmt->close();

    // Optionally, fetch the new sibling member to return
    $stmt = $conn->prepare('SELECT id, full_name, gender, birth_date FROM members WHERE id = ?');
    $stmt->bind_param('i', $sibling_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sibling = $result->fetch_assoc();
    $stmt->close();

    log_message("add_sibling: success for sibling_id=$sibling_id, member_id=$member_id");
    echo json_encode(['success' => true, 'member' => $sibling]);
    exit;
}

// Add Child
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_child') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = intval($input['member_id']);
    $full_name = trim($input['full_name'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $birth_date = trim($input['birth_date'] ?? '');

    log_message("add_child called: member_id=$member_id, full_name=$full_name, gender=$gender, birth_date=$birth_date");

    if (!$member_id || !$full_name || !$gender || !$birth_date) {
        log_message("add_child error: missing required fields");
        echo json_encode(['success' => false, 'error' => 'Full name, gender, and birth date are required']);
        exit;
    }

    // Get family_id of the member
    $stmt = $conn->prepare('SELECT family_id FROM members WHERE id = ?');
    if (!$stmt) {
        log_message("add_child error: prepare failed for SELECT family_id: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $stmt->bind_result($family_id);
    if (!$stmt->fetch()) {
        log_message("add_child error: member not found for id $member_id");
        echo json_encode(['success' => false, 'error' => 'Member not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert child member
    $stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender, birth_date) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        log_message("add_child error: prepare failed for INSERT member: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('isss', $family_id, $full_name, $gender, $birth_date);
    if (!$stmt->execute()) {
        log_message("add_child error: execute failed for INSERT member: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to add child']);
        $stmt->close();
        exit;
    }
    $child_id = $stmt->insert_id;
    log_message("add_child: child inserted with id $child_id");
    $stmt->close();

    // Insert relationship: parent -> child
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, "parent")');
    if (!$stmt) {
        log_message("add_child error: prepare failed for INSERT relationship: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt->bind_param('iii', $family_id, $member_id, $child_id);
    if (!$stmt->execute()) {
        log_message("add_child error: execute failed for INSERT relationship: " . $stmt->error . " | SQLSTATE: " . $stmt->sqlstate);
        echo json_encode(['success' => false, 'error' => 'Failed to add relationship']);
        $stmt->close();
        exit;
    }
    log_message("add_child: relationship inserted parent_id=$member_id, child_id=$child_id");
    $stmt->close();

    // Optionally, fetch the new child member to return
    $stmt = $conn->prepare('SELECT id, full_name, gender, birth_date FROM members WHERE id = ?');
    $stmt->bind_param('i', $child_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();
    $stmt->close();

    log_message("add_child: success for parent_id=$member_id, child_id=$child_id");
    echo json_encode(['success' => true, 'member' => $child]);
    exit;
}

// If not POST or GET
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
if ($conn && $conn->connect_errno === 0) {
    $conn->close();
}