<?php
// Set flash message to be displayed on the next page
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        set_flash_message('Please log in to access this page.', 'error');
        header('Location: login.php');
        exit;
    }
}

// Generate a random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Validate email format
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number format
function is_valid_phone($phone) {
    // Basic validation for international phone numbers
    return preg_match('/^\+[0-9]{1,3}[0-9]{6,14}$/', $phone);
}

// Get user data by ID
function get_user_by_id($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get crime reports by user ID
function get_user_reports($conn, $user_id, $limit = null) {
    $sql = "SELECT * FROM crime_reports WHERE user_id = ? ORDER BY created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    return $reports;
}

// Get nearby crime reports based on location
function get_nearby_reports($conn, $lat, $lng, $radius_km = 5, $limit = 10) {
    // Haversine formula to calculate distance between two points on Earth
    $sql = "SELECT *, 
            (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance 
            FROM crime_reports 
            HAVING distance < ? 
            ORDER BY distance 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddii", $lat, $lng, $lat, $radius_km, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    return $reports;
}

// Format date for display
function format_date($date_string, $format = 'M j, Y') {
    $date = new DateTime($date_string);
    return $date->format($format);
}

// Get crime type label with icon
function get_crime_type_label($type) {
    $types = [
        'theft' => ['icon' => 'fa-shopping-bag', 'label' => 'Theft'],
        'assault' => ['icon' => 'fa-fist-raised', 'label' => 'Assault'],
        'vandalism' => ['icon' => 'fa-spray-can', 'label' => 'Vandalism'],
        'fraud' => ['icon' => 'fa-credit-card', 'label' => 'Fraud'],
        'harassment' => ['icon' => 'fa-bullhorn', 'label' => 'Harassment'],
        'other' => ['icon' => 'fa-question-circle', 'label' => 'Other']
    ];
    
    if (isset($types[strtolower($type)])) {
        return $types[strtolower($type)];
    }
    
    return $types['other'];
}

// Get status label with color
function get_status_label($status) {
    $statuses = [
        'reported' => ['color' => 'yellow', 'label' => 'Reported'],
        'investigating' => ['color' => 'blue', 'label' => 'Investigating'],
        'resolved' => ['color' => 'green', 'label' => 'Resolved'],
        'closed' => ['color' => 'gray', 'label' => 'Closed'],
    ];
    
    if (isset($statuses[strtolower($status)])) {
        return $statuses[strtolower($status)];
    }
    
    return ['color' => 'gray', 'label' => ucfirst($status)];
}

// Upload file and return the file path
function upload_file($file, $upload_dir = 'uploads', $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4']) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed with error code: ' . $file['error']];
    }
    
    // Check file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types)];
    }
    
    // Check file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File is too large. Maximum size is 10MB.'];
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }
}

