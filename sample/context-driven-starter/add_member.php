
<?php
include 'db.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$fullName = $input['full_name'];
$gender = $input['gender'];
$birthDate = $input['birth_date'];
$context = $input['context'] ?? [];

if (!$context || !$context['relationship_type'] || !$context['target_member_id']) {
    echo json_encode(['success' => false, 'error' => 'Missing context']);
    exit;
}

$stmt = $conn->prepare('SELECT family_id FROM members WHERE id = ?');
$stmt->bind_param('i', $context['target_member_id']);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$familyId = $row ? $row['family_id'] : null;

if (!$familyId) {
    echo json_encode(['success' => false, 'error' => 'Invalid family context']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO members (family_id, full_name, gender, birth_date) VALUES (?, ?, ?, ?)');
$stmt->bind_param('isss', $familyId, $fullName, $gender, $birthDate);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to insert member']);
    exit;
}
$newMemberId = $stmt->insert_id;

switch ($context['relationship_type']) {
  case 'parent':
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, "parent")');
    $stmt->bind_param('iii', $familyId, $newMemberId, $context['target_member_id']);
    break;
  case 'child':
    $stmt = $conn->prepare('INSERT INTO relationships (family_id, member_id, related_member_id, relationship_type) VALUES (?, ?, ?, "parent")');
    $stmt->bind_param('iii', $familyId, $context['target_member_id'], $newMemberId);
    break;
  default:
    echo json_encode(['success' => false, 'error' => 'Unknown relationship type']);
    exit;
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to insert relationship']);
    exit;
}

echo json_encode(['success' => true]);
?>
