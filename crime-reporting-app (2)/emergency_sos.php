<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

// Get user ID
$user_id = $_SESSION['user_id'];

// Process SOS request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $lat = sanitize_input($_POST['lat']);
    $lng = sanitize_input($_POST['lng']);
    
    if ($_POST['action'] === 'sos') {
        // Regular SOS
        $stmt = $conn->prepare("CALL CreateSOSAlert(?, ?, ?, 'standard')");
        $stmt->bind_param("idd", $user_id, $lat, $lng);
        $stmt->execute();
        $result = $stmt->get_result();
        $alert = $result->fetch_assoc();
        $stmt->close();
        
        // Get nearest police station
        $stmt = $conn->prepare("CALL GetNearestPoliceStations(?, ?, 1)");
        $stmt->bind_param("ddi", $lat, $lng, 1);
        $stmt->execute();
        $result = $stmt->get_result();
        $police_station = $result->fetch_assoc();
        $stmt->close();
        
        // Create notification for user
        $stmt = $conn->prepare("CALL CreateNotification(?, 'SOS Alert Sent', 'Your SOS alert has been sent to the nearest police station. Help is on the way.', 'sos', ?)");
        $stmt->bind_param("ii", $user_id, $alert['id']);
        $stmt->execute();
        $stmt->close();  ?)");
        $stmt->bind_param("ii", $user_id, $alert['id']);
        $stmt->execute();
        $stmt->close();
        
        // Send response
        echo json_encode([
            'success' => true,
            'message' => 'SOS Alert Sent! Your location has been shared with the nearest police station. Expect a call shortly.',
            'police_station' => $police_station ? $police_station['name'] : 'Nearest Police Station'
        ]);
        exit;
    } elseif ($_POST['action'] === 'emergency_sos') {
        // Emergency SOS
        $stmt = $conn->prepare("CALL CreateSOSAlert(?, ?, ?, 'emergency')");
        $stmt->bind_param("idd", $user_id, $lat, $lng);
        $stmt->execute();
        $result = $stmt->get_result();
        $alert = $result->fetch_assoc();
        $stmt->close();
        
        // Get nearest police station
        $stmt = $conn->prepare("CALL GetNearestPoliceStations(?, ?, 1)");
        $stmt->bind_param("ddi", $lat, $lng, 1);
        $stmt->execute();
        $result = $stmt->get_result();
        $police_station = $result->fetch_assoc();
        $stmt->close();
        
        // Create emergency broadcast
        $message = "EMERGENCY: A user has triggered an Emergency SOS alert in your area. Please be vigilant and report any suspicious activity to the police.";
        $stmt = $conn->prepare("CALL CreateEmergencyBroadcast(?, 2.0, ?)");
        $stmt->bind_param("is", $alert['id'], $message);
        $stmt->execute();
        $stmt->close();
        
        // Start emergency stream
        $stream_key = bin2hex(random_bytes(16));
        $stream_url = "https://stream.safetynet.com/emergency/" . $stream_key;
        $stmt = $conn->prepare("CALL StartEmergencyStream(?, ?, ?)");
        $stmt->bind_param("iss", $alert['id'], $stream_url, $stream_key);
        $stmt->execute();
        $stmt->close();
        
        // Create notification for user
        $stmt = $conn->prepare("CALL CreateNotification(?, 'EMERGENCY SOS ACTIVATED', 'Emergency services have been notified. Help is on the way. Your camera and microphone are now streaming to the police.', 'emergency', ?)");
        $stmt->bind_param("ii", $user_id, $alert['id']);
        $stmt->execute();
        $stmt->close();
        
        // Send response
        echo json_encode([
            'success' => true,
            'message' => 'EMERGENCY SOS ACTIVATED! Alert sent to police and emergency contacts. Help is on the way.',
            'police_station' => $police_station ? $police_station['name'] : 'Nearest Police Station',
            'stream_key' => $stream_key
        ]);
        exit;
    }
}

// Return error if not a POST request or invalid action
echo json_encode(['success' => false, 'message' => 'Invalid request']);

