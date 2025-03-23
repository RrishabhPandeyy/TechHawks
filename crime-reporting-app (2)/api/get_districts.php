<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Get state parameter
$state = isset($_GET['state']) ? $_GET['state'] : '';

if (empty($state)) {
    echo json_encode(['success' => false, 'message' => 'State parameter is required']);
    exit;
}

// Get districts for the selected state
$districts = [];
$stmt = $conn->prepare("SELECT DISTINCT name FROM geographic_areas WHERE type = 'district' AND parent_id = (SELECT id FROM geographic_areas WHERE type = 'state' AND name = ?) ORDER BY name");
$stmt->bind_param("s", $state);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $districts[] = $row['name'];
}
$stmt->close();

echo json_encode(['success' => true, 'districts' => $districts]);

