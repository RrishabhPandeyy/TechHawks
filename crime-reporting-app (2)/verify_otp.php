<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if not coming from login page
if (!isset($_SESSION['temp_email']) && !isset($_SESSION['temp_phone'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Verify OTP";
$error = '';

// Process OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitize_input($_POST['otp']);
    
    if (empty($otp)) {
        $error = 'Please enter the OTP.';
    } else {
        if ($_SESSION['otp_type'] === 'email') {
            $email = $_SESSION['temp_email'];
            
            $stmt = $conn->prepare("SELECT id, name, email, otp, otp_expiry FROM users WHERE email = ? AND otp = ?");
            $stmt->bind_param("ss", $email, $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if OTP is expired
                if (strtotime($user['otp_expiry']) < time()) {
                    $error = 'OTP has expired. Please request a new one.';
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Clear OTP
                    $stmt = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    // Clear temporary session variables
                    unset($_SESSION['temp_email']);
                    unset($_SESSION['otp_type']);
                    
                    set_flash_message('Login successful. Welcome back!');
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid OTP.';
            }
        } elseif ($_SESSION['otp_type'] === 'phone') {
            $phone = $_SESSION['temp_phone'];
            
            $stmt = $conn->prepare("SELECT id, name, email, otp, otp_expiry FROM users WHERE phone = ? AND otp = ?");
            $stmt->bind_param("ss", $phone, $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if OTP is expired
                if (strtotime($user['otp_expiry']) < time()) {
                    $error = 'OTP has expired. Please request a new one.';
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Clear OTP
                    $stmt = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    // Clear temporary session variables
                    unset($_SESSION['temp_phone']);
                    unset($_SESSION['otp_type']);
                    
                    set_flash_message('Login successful. Welcome back!');
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid OTP.';
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
      <h1>Verify OTP</h1>
      <p>
        <?php if ($_SESSION['otp_type'] === 'email'): ?>
          We've sent a verification code to your email: <?php echo htmlspecialchars($_SESSION['temp_email']); ?>
        <?php else: ?>
          We've sent a verification code to your phone: <?php echo htmlspecialchars($_SESSION['temp_phone']); ?>
        <?php endif; ?>
      </p>
    </div>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <form method="POST" action="verify_otp.php">
      <div class="form-group">
        <label for="otp">Enter OTP</label>
        <input type="text" id="otp" name="otp" placeholder="123456" required>
      </div>
      
      <div class="form-group">
        <button type="submit" class="btn btn-primary btn-block">Verify OTP</button>
      </div>
      
      <div class="form-group text-center">
        <a href="login.php" class="btn btn-link">Back to Login</a>
      </div>
    </form>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>

