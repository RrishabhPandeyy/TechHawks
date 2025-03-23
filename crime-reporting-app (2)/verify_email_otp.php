<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = "Verify Email";

// Check if email is set in session
if (!isset($_SESSION['temp_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['temp_email'];
$error = '';
$success = '';

// Process OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitize_input($_POST['otp']);
    
    if (empty($otp)) {
        $error = 'Please enter the OTP.';
    } else {
        // Check if OTP is valid
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND otp = ? AND otp_expiry > NOW()");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_id = $result->fetch_assoc()['id'];
            
            // Clear OTP
            $stmt = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Set email as verified
            $stmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Set success message
            $success = 'Email verified successfully! You can now login.';
            
            // Clear session variables
            unset($_SESSION['temp_email']);
        } else {
            $error = 'Invalid or expired OTP. Please try again.';
        }
        
        $stmt->close();
    }
}

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    // Generate new OTP
    $otp = rand(100000, 999999);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Update OTP in database
    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $otp_expiry, $email);
    $stmt->execute();
    $stmt->close();
    
    // In a real application, send OTP via email
    // For demo purposes, we'll just show it
    $success = "Your new OTP is: $otp (In a real app, this would be sent via email)";
}

include_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-envelope"></i>
            </div>
            <h1>Verify Your Email</h1>
            <p>We've sent a verification code to: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <?php if (strpos($success, 'verified successfully') !== false): ?>
                    <p><a href="login.php">Click here to login</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form method="POST" action="verify_email_otp.php">
                <div class="otp-input-container">
                    <label for="otp">Enter Verification Code</label>
                    <input type="text" id="otp" name="otp" placeholder="123456" maxlength="6" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Verify Email</button>
                </div>
                
                <div class="resend-otp">
                    <p>Didn't receive the code? <a href="verify_email_otp.php?resend=1">Resend Code</a></p>
                    <p class="otp-timer" id="otpTimer">Code expires in: <span id="timer">15:00</span></p>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            <a href="register.php" class="btn btn-link">Back to Registration</a>
        </div>
    </div>
</div>

<script>
// OTP timer
document.addEventListener('DOMContentLoaded', function() {
    const timerElement = document.getElementById('timer');
    
    if (timerElement) {
        let timeLeft = 15 * 60; // 15 minutes in seconds
        
        const countdown = setInterval(function() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerElement.textContent = '00:00';
                document.querySelector('.resend-otp a').classList.add('active');
            }
            
            timeLeft--;
        }, 1000);
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>

