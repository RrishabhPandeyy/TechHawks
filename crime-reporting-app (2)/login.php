<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Login";
$error = '';
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_type']) && $_POST['login_type'] === 'password') {
        // Password login
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    set_flash_message('Login successful. Welcome back!');
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'No account found with that email.';
            }
        }
    } elseif (isset($_POST['login_type']) && $_POST['login_type'] === 'email_otp') {
        // Email OTP login
        $email = sanitize_input($_POST['email']);
        
        if (empty($email) || !is_valid_email($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Generate OTP
                $otp = rand(100000, 999999);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store OTP in database
                $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
                $stmt->bind_param("sss", $otp, $otp_expiry, $email);
                $stmt->execute();
                
                // In a real application, send OTP via email
                // For demo purposes, we'll just show it
                set_flash_message("Your OTP is: $otp (In a real app, this would be sent via email)", 'info');
                
                // Redirect to OTP verification page
                $_SESSION['temp_email'] = $email;
                $_SESSION['otp_type'] = 'email';
                header('Location: verify_otp.php');
                exit;
            } else {
                $error = 'No account found with that email.';
            }
        }
    } elseif (isset($_POST['login_type']) && $_POST['login_type'] === 'phone_otp') {
        // Phone OTP login
        $phone = sanitize_input($_POST['phone']);
        
        if (empty($phone) || !is_valid_phone($phone)) {
            $error = 'Please enter a valid phone number with country code (e.g., +1234567890).';
        } else {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Generate OTP
                $otp = rand(100000, 999999);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store OTP in database
                $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE phone = ?");
                $stmt->bind_param("sss", $otp, $otp_expiry, $phone);
                $stmt->execute();
                
                // In a real application, send OTP via SMS
                // For demo purposes, we'll just show it
                set_flash_message("Your OTP is: $otp (In a real app, this would be sent via SMS)", 'info');
                
                // Redirect to OTP verification page
                $_SESSION['temp_phone'] = $phone;
                $_SESSION['otp_type'] = 'phone';
                header('Location: verify_otp.php');
                exit;
            } else {
                $error = 'No account found with that phone number.';
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">
        <i class="fas fa-shield-alt"></i>
      </div>
      <h1>Welcome to SafetyNet</h1>
    </div>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <div class="auth-tabs">
      <ul class="tabs-nav">
        <li class="tab-item active" data-tab="password">Password</li>
        <li class="tab-item" data-tab="email-otp">Email OTP</li>
        <li class="tab-item" data-tab="phone-otp">Phone OTP</li>
      </ul>
      
      <div class="tabs-content">
        <!-- Password Login Tab -->
        <div class="tab-pane active" id="password">
          <form method="POST" action="login.php">
            <input type="hidden" name="login_type" value="password">
            
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
              <div class="form-row">
                <label for="password">Password</label>
                <a href="forgot_password.php" class="form-link">Forgot password?</a>
              </div>
              <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
          </form>
        </div>
        
        <!-- Email OTP Tab -->
        <div class="tab-pane" id="email-otp">
          <form method="POST" action="login.php">
            <input type="hidden" name="login_type" value="email_otp">
            
            <div class="form-group">
              <label for="email-otp">Email</label>
              <input type="email" id="email-otp" name="email" required>
            </div>
            
            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-block">Send OTP</button>
            </div>
          </form>
        </div>
        
        <!-- Phone OTP Tab -->
        <div class="tab-pane" id="phone-otp">
          <form method="POST" action="login.php">
            <input type="hidden" name="login_type" value="phone_otp">
            
            <div class="form-group">
              <label for="phone-otp">Phone Number</label>
              <input type="tel" id="phone-otp" name="phone" placeholder="+1234567890" required>
              <small class="form-text">Include country code (e.g., +91 for India)</small>
            </div>
            
            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-block">Send OTP</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <div class="auth-footer">
      <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
  </div>
</div>

<?php 
$page_scripts = ['assets/js/auth.js'];
include_once 'includes/footer.php'; 
?>

