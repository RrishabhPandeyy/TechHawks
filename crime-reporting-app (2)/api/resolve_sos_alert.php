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

if (!isset($data['sos_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing SOS alert ID']);
    exit;
}

$sos_id = $data['sos_id'];

// Call stored procedure to resolve SOS alert
$stmt = $conn->prepare("CALL ResolveSosAlert(?)");
$stmt->bind_param("i", $sos_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'SOS alert resolved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to resolve SOS alert: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>

