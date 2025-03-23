<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['stream_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing stream ID']);
    exit;
}

$stream_id = $data['stream_id'];

// Call stored procedure to end emergency stream
$stmt = $conn->prepare("CALL EndEmergencyStream(?)");
$stmt->bind_param("i", $stream_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Emergency stream ended successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to end emergency stream: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>

