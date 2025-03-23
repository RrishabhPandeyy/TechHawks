<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Edit Profile";

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user data
$user = get_user_by_id($conn, $user_id);

// Get user details
$user_details = null;
$stmt = $conn->prepare("SELECT * FROM user_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user_details = $result->fetch_assoc();
}
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic info update
    if (isset($_POST['update_basic'])) {
        $name = sanitize_input($_POST['name']);
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        
        // Check if username is already taken by another user
        if ($username !== $user['username']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                set_flash_message('Username is already taken. Please choose a different one.', 'error');
                header('Location: profile_edit.php');
                exit;
            }
            $stmt->close();
        }
        
        // Check if email is already taken by another user
        if ($email !== $user['email']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                set_flash_message('Email is already taken. Please choose a different one.', 'error');
                header('Location: profile_edit.php');
                exit;
            }
            $stmt->close();
        }
        
        // Update user data
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $username, $email, $user_id);
        $stmt->execute();
        $stmt->close();
        
        set_flash_message('Basic information updated successfully.');
        header('Location: profile_edit.php');
        exit;
    }
    
    // Contact info update
    if (isset($_POST['update_contact'])) {
        $phone = sanitize_input($_POST['phone']);
        $relative_phone = sanitize_input($_POST['relative_phone']);
        
        // Update user phone
        $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->bind_param("si", $phone, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Update relative phone in user_details
        $stmt = $conn->prepare("UPDATE user_details SET relative_phone = ? WHERE user_id = ?");
        $stmt->bind_param("si", $relative_phone, $user_id);
        $stmt->execute();
        $stmt->close();
        
        set_flash_message('Contact information updated successfully.');
        header('Location: profile_edit.php');
        exit;
    }
    
    // Address update
    if (isset($_POST['update_address'])) {
        $address = sanitize_input($_POST['address']);
        $lat = sanitize_input($_POST['lat']);
        $lng = sanitize_input($_POST['lng']);
        
        // Update user_details
        $stmt = $conn->prepare("UPDATE user_details SET address = ?, lat = ?, lng = ? WHERE user_id = ?");
        $stmt->bind_param("sddi", $address, $lat, $lng, $user_id);
        $stmt->execute();
        $stmt->close();
        
        set_flash_message('Address updated successfully.');
        header('Location: profile_edit.php');
        exit;
    }
    
    // Password update
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_password = $result->fetch_assoc()['password'];
        $stmt->close();
        
        if (!password_verify($current_password, $user_password)) {
            set_flash_message('Current password is incorrect.', 'error');
            header('Location: profile_edit.php');
            exit;
        }
        
        // Check if new passwords match
        if ($new_password !== $confirm_password) {
            set_flash_message('New passwords do not match.', 'error');
            header('Location: profile_edit.php');
            exit;
        }
        
        // Validate password strength
        if (strlen($new_password) < 8) {
            set_flash_message('Password must be at least 8 characters long.', 'error');
            header('Location: profile_edit.php');
            exit;
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();
        
        set_flash_message('Password updated successfully.');
        header('Location: profile_edit.php');
        exit;
    }
    
    // Avatar update
    if (isset($_POST['update_avatar'])) {
        // Check if file was uploaded
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_file($_FILES['avatar'], 'uploads/avatars', ['image/jpeg', 'image/png', 'image/gif']);
            
            if ($upload_result['success']) {
                $avatar_path = $upload_result['filepath'];
                
                // Update user avatar
                $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                $stmt->bind_param("si", $avatar_path, $user_id);
                $stmt->execute();
                $stmt->close();
                
                set_flash_message('Profile photo updated successfully.');
            } else {
                set_flash_message($upload_result['error'], 'error');
            }
        } else {
            set_flash_message('No file selected.', 'error');
        }
        
        header('Location: profile_edit.php');
        exit;
    }
}

include_once 'includes/header.php';
?>

<div class="profile-edit-container">
    <div class="container">
        <h1 class="page-title">Edit Profile</h1>
        
        <div class="profile-edit-tabs">
            <ul class="tabs-nav">
                <li class="tab-item active" data-tab="basic-info">Basic Info</li>
                <li class="tab-item" data-tab="contact-info">Contact Info</li>
                <li class="tab-item" data-tab="address">Address</li>
                <li class="tab-item" data-tab="password">Password</li>
                <li class="tab-item" data-tab="avatar">Profile Photo</li>
            </ul>
            
            <div class="tabs-content">
                <!-- Basic Info Tab -->
                <div class="tab-pane active" id="basic-info">
                    <div class="card">
                        <div class="card-header">
                            <h2>Basic Information</h2>
                        </div>
                        
                        <form method="POST" action="profile_edit.php">
                            <input type="hidden" name="update_basic" value="1">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Contact Info Tab -->
                <div class="tab-pane" id="contact-info">
                    <div class="card">
                        <div class="card-header">
                            <h2>Contact Information</h2>
                        </div>
                        
                        <form method="POST" action="profile_edit.php">
                            <input type="hidden" name="update_contact" value="1">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    <small class="form-text">Include country code (e.g., +91 for India)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="relative_phone">Emergency Contact Number</label>
                                    <input type="tel" id="relative_phone" name="relative_phone" value="<?php echo htmlspecialchars($user_details['relative_phone']); ?>" required>
                                    <small class="form-text">This number will be contacted in case of emergency</small>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Address Tab -->
                <div class="tab-pane" id="address">
                    <div class="card">
                        <div class="card-header">
                            <h2>Address Information</h2>
                        </div>
                        
                        <form method="POST" action="profile_edit.php">
                            <input type="hidden" name="update_address" value="1">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_details['address']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Select Location on Map</label>
                                    <div id="map" class="map-container"></div>
                                    <input type="hidden" id="lat" name="lat" value="<?php echo htmlspecialchars($user_details['lat']); ?>" required>
                                    <input type="hidden" id="lng" name="lng" value="<?php echo htmlspecialchars($user_details['lng']); ?>" required>
                                    <small class="form-text location-text">
                                        Selected: <?php echo htmlspecialchars($user_details['lat']); ?>, <?php echo htmlspecialchars($user_details['lng']); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Password Tab -->
                <div class="tab-pane" id="password">
                    <div class="card">
                        <div class="card-header">
                            <h2>Change Password</h2>
                        </div>
                        
                        <form method="POST" action="profile_edit.php">
                            <input type="hidden" name="update_password" value="1">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <small class="form-text">Password must be at least 8 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Avatar Tab -->
                <div class="tab-pane" id="avatar">
                    <div class="card">
                        <div class="card-header">
                            <h2>Profile Photo</h2>
                        </div>
                        
                        <form method="POST" action="profile_edit.php" enctype="multipart/form-data">
                            <input type="hidden" name="update_avatar" value="1">
                            
                            <div class="card-body">
                                <div class="current-avatar">
                                    <h3>Current Photo</h3>
                                    <div class="avatar-preview">
                                        <?php if (!empty($user['avatar_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile Photo">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="avatar">Upload New Photo</label>
                                    <div class="file-upload">
                                        <div class="file-preview" id="avatarPreview">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <label for="avatar" class="file-label">
                                            <i class="fas fa-upload"></i>
                                            <span>Choose File</span>
                                        </label>
                                        <input type="file" id="avatar" name="avatar" accept="image/*" class="file-input">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update Photo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-actions">
            <a href="profile.php" class="btn btn-outline">Back to Profile</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tabs functionality
    const tabItems = document.querySelectorAll('.profile-edit-tabs .tab-item');
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            document.querySelectorAll('.profile-edit-tabs .tab-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.profile-edit-tabs .tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Map functionality
    const mapElement = document.getElementById('map');
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const locationText = document.querySelector('.location-text');
    const addressInput = document.getElementById('address');
    
    if (mapElement && latInput && lngInput) {
        // Initialize map
        const map = new google.maps.Map(mapElement, {
            center: { lat: parseFloat(latInput.value) || 20.5937, lng: parseFloat(lngInput.value) || 78.9629 },
            zoom: 15,
            mapTypeControl: false,
        });
        
        // Initialize geocoder
        const geocoder = new google.maps.Geocoder();
        
        // Add initial marker
        let marker = new google.maps.Marker({
            position: { lat: parseFloat(latInput.value) || 20.5937, lng: parseFloat(lngInput.value) || 78.9629 },
            map: map,
            draggable: true,
        });
        
        // Add click event listener to map
        map.addListener('click', (event) => {
            const location = event.latLng;
            
            // Update marker position
            marker.setPosition(location);
            
            // Update inputs
            latInput.value = location.lat();
            lngInput.value = location.lng();
            locationText.textContent = `Selected: ${location.lat().toFixed(6)}, ${location.lng().toFixed(6)}`;
            
            // Get address from coordinates
            geocoder.geocode({ location: location }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    addressInput.value = results[0].formatted_address;
                }
            });
        });
        
        // Add drag event listener to marker
        marker.addListener('dragend', () => {
            const position = marker.getPosition();
            
            // Update inputs
            latInput.value = position.lat();
            lngInput.value = position.lng();
            locationText.textContent = `Selected: ${position.lat().toFixed(6)}, ${position.lng().toFixed(6)}`;
            
            // Get address from coordinates
            geocoder.geocode({ location: position }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    addressInput.value = results[0].formatted_address;
                }
            });
        });
        
        // Address search
        if (addressInput) {
            addressInput.addEventListener('blur', function() {
                const address = this.value;
                
                if (address) {
                    geocoder.geocode({ address: address }, (results, status) => {
                        if (status === 'OK' && results[0]) {
                            const location = results[0].geometry.location;
                            
                            // Update map
                            map.setCenter(location);
                            
                            // Update marker
                            marker.setPosition(location);
                            
                            // Update inputs
                            latInput.value = location.lat();
                            lngInput.value = location.lng();
                            locationText.textContent = `Selected: ${location.lat().toFixed(6)}, ${location.lng().toFixed(6)}`;
                        }
                    });
                }
            });
        }
    }
    
    // Avatar preview
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Avatar preview">`;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>

