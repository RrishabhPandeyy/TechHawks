<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Register";
$error = '';
$success = '';

// Initialize form data
$form_data = [
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'relative_phone' => '',
    'aadhar_number' => '',
    'address' => '',
    'lat' => '',
    'lng' => ''
];

// Process  => '',
    'aadhar_number' => '',
    'address' => '',
    'lat' => '',
    'lng' => ''
];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $form_data = [
        'name' => sanitize_input($_POST['name']),
        'username' => sanitize_input($_POST['username']),
        'email' => sanitize_input($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'phone' => sanitize_input($_POST['phone']),
        'relative_phone' => sanitize_input($_POST['relative_phone']),
        'aadhar_number' => sanitize_input($_POST['aadhar_number']),
        'address' => sanitize_input($_POST['address']),
        'lat' => sanitize_input($_POST['lat']),
        'lng' => sanitize_input($_POST['lng'])
    ];
    
    // Validate form data
    if (empty($form_data['name'])) {
        $error = 'Name is required.';
    } elseif (empty($form_data['username'])) {
        $error = 'Username is required.';
    } elseif (empty($form_data['email']) || !is_valid_email($form_data['email'])) {
        $error = 'Valid email is required.';
    } elseif (empty($form_data['password'])) {
        $error = 'Password is required.';
    } elseif ($form_data['password'] !== $form_data['confirm_password']) {
        $error = 'Passwords do not match.';
    } elseif (strlen($form_data['password']) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (empty($form_data['phone']) || !is_valid_phone($form_data['phone'])) {
        $error = 'Valid phone number with country code is required.';
    } elseif (empty($form_data['relative_phone']) || !is_valid_phone($form_data['relative_phone'])) {
        $error = 'Valid emergency contact phone number is required.';
    } elseif (empty($form_data['aadhar_number'])) {
        $error = 'Aadhar number is required.';
    } elseif (empty($form_data['address'])) {
        $error = 'Address is required.';
    } elseif (empty($form_data['lat']) || empty($form_data['lng'])) {
        $error = 'Please select your location on the map.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $form_data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists. Please use a different email or login.';
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $form_data['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Hash password
                $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
                
                // Upload profile photo if provided
                $avatar_path = '';
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = upload_file($_FILES['avatar'], 'uploads/avatars', ['image/jpeg', 'image/png', 'image/gif']);
                    
                    if ($upload_result['success']) {
                        $avatar_path = $upload_result['filepath'];
                    } else {
                        $error = $upload_result['error'];
                    }
                }
                
                if (empty($error)) {
                    // Begin transaction
                    $conn->begin_transaction();
                    
                    try {
                        // Insert user data
                        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, phone, avatar_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("ssssss", $form_data['name'], $form_data['username'], $form_data['email'], $hashed_password, $form_data['phone'], $avatar_path);
                        $stmt->execute();
                        
                        $user_id = $conn->insert_id;
                        
                        // Insert user details
                        $stmt = $conn->prepare("INSERT INTO user_details (user_id, aadhar_number, relative_phone, address, lat, lng) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssdd", $user_id, $form_data['aadhar_number'], $form_data['relative_phone'], $form_data['address'], $form_data['lat'], $form_data['lng']);
                        $stmt->execute();
                        
                        // Commit transaction
                        $conn->commit();
                        
                        $success = 'Registration successful! You can now login.';
                        
                        // Clear form data
                        $form_data = [
                            'name' => '',
                            'username' => '',
                            'email' => '',
                            'phone' => '',
                            'relative_phone' => '',
                            'aadhar_number' => '',
                            'address' => '',
                            'lat' => '',
                            'lng' => ''
                        ];
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $conn->rollback();
                        $error = 'Registration failed: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="register-container">
  <div class="register-card">
    <div class="register-header">
      <div class="register-logo">
        <i class="fas fa-shield-alt"></i>
      </div>
      <h1>Create an Account</h1>
    </div>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <?php echo $success; ?>
        <p><a href="login.php">Click here to login</a></p>
      </div>
    <?php else: ?>
      <form method="POST" action="register.php" enctype="multipart/form-data" id="registerForm">
        <div class="form-steps">
          <div class="step-indicators">
            <div class="step-indicator active" data-step="1">1</div>
            <div class="step-indicator" data-step="2">2</div>
            <div class="step-indicator" data-step="3">3</div>
          </div>
          <div class="step-labels">
            <div class="step-label active" data-step="1">Basic Info</div>
            <div class="step-label" data-step="2">Contact Details</div>
            <div class="step-label" data-step="3">Location & Documents</div>
          </div>
        </div>
        
        <div class="step-content">
          <!-- Step 1: Basic Information -->
          <div class="step-pane active" id="step1">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required>
              <small class="form-text">Password must be at least 8 characters long</small>
            </div>
            
            <div class="form-group">
              <label for="confirm_password">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
              <button type="button" class="btn btn-primary btn-block next-step" data-next="2">Next</button>
            </div>
          </div>
          
          <!-- Step 2: Contact Details -->
          <div class="step-pane" id="step2">
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" placeholder="+1234567890" value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
              <small class="form-text">Include country code (e.g., +91 for India)</small>
            </div>
            
            <div class="form-group">
              <label for="relative_phone">Emergency Contact Number</label>
              <input type="tel" id="relative_phone" name="relative_phone" placeholder="+1234567890" value="<?php echo htmlspecialchars($form_data['relative_phone']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="aadhar_number">Aadhar Number</label>
              <input type="text" id="aadhar_number" name="aadhar_number" value="<?php echo htmlspecialchars($form_data['aadhar_number']); ?>" required>
            </div>
            
            <div class="form-group">
              <div class="button-group">
                <button type="button" class="btn btn-outline prev-step" data-prev="1">Back</button>
                <button type="button" class="btn btn-primary next-step" data-next="3">Next</button>
              </div>
            </div>
          </div>
          
          <!-- Step 3: Location & Documents -->
          <div class="step-pane" id="step3">
            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($form_data['address']); ?>" required>
            </div>
            
            <div class="form-group">
              <label>Select Location on Map</label>
              <div id="map" class="map-container"></div>
              <input type="hidden" id="lat" name="lat" value="<?php echo htmlspecialchars($form_data['lat']); ?>" required>
              <input type="hidden" id="lng" name="lng" value="<?php echo htmlspecialchars($form_data['lng']); ?>" required>
              <small class="form-text location-text">No location selected</small>
            </div>
            
            <div class="form-group">
              <label for="avatar">Profile Photo</label>
              <div class="file-upload">
                <div class="file-preview" id="avatarPreview">
                  <i class="fas fa-user"></i>
                </div>
                <label for="avatar" class="file-label">
                  <i class="fas fa-upload"></i>
                  <span>Upload Photo</span>
                </label>
                <input type="file" id="avatar" name="avatar" accept="image/*" class="file-input">
              </div>
            </div>
            
            <div class="form-group">
              <div class="button-group">
                <button type="button" class="btn btn-outline prev-step" data-prev="2">Back</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
              </div>
            </div>
          </div>
        </div>
      </form>
      
      <div class="register-footer">
        <p>Already have an account? <a href="login.php">Sign in</a></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php 
$page_scripts = ['https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places', 'assets/js/register.js'];
include_once 'includes/footer.php'; 
?>

