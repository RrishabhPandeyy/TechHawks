<?php
// Initialize any required variables
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title . ' - SafetyNet' : 'SafetyNet - Crime Reporting & Emergency Response'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <header class="main-header">
    <div class="container">
      <div class="logo">
        <a href="index.php">
          <i class="fas fa-shield-alt"></i>
          <span>SafetyNet</span>
        </a>
      </div>
      
      <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
      </button>
      
      <nav class="main-nav" id="mainNav">
        <ul>
          <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
          </li>
          
          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="<?php echo $current_page == 'map.php' ? 'active' : ''; ?>">
              <a href="map.php"><i class="fas fa-map"></i> Crime Map</a>
            </li>
            
            <li class="<?php echo $current_page == 'report.php' ? 'active' : ''; ?>">
              <a href="report.php"><i class="fas fa-file-alt"></i> Report Crime</a>
            </li>
            
            <li class="<?php echo $current_page == 'alerts.php' ? 'active' : ''; ?>">
              <a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a>
            </li>
            
            <li class="<?php echo $current_page == 'emergency.php' ? 'active' : ''; ?>">
              <a href="emergency.php"><i class="fas fa-phone"></i> Emergency</a>
            </li>
            
            <li class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
              <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            </li>
            
            <li>
              <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
          <?php else: ?>
            <li class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
              <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </li>
            
            <li class="<?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
              <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>
  
  <main class="main-content">
    <?php if (isset($_SESSION['flash_message'])): ?>
      <div class="flash-message <?php echo $_SESSION['flash_type']; ?>">
        <?php 
          echo $_SESSION['flash_message']; 
          unset($_SESSION['flash_message']);
          unset($_SESSION['flash_type']);
        ?>
      </div>
    <?php endif; ?>

