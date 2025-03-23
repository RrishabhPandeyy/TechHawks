<?php
session_start();
$page_title = "Home";
include_once 'includes/header.php';
?>

<div class="hero-section">
  <div class="container">
    <h1>SafetyNet</h1>
    <p>A comprehensive crime reporting and emergency response platform that connects citizens with law enforcement</p>
    <div class="button-group">
      <a href="register.php" class="btn btn-primary">Sign Up</a>
      <a href="login.php" class="btn btn-outline">Login</a>
    </div>
  </div>
</div>

<section class="features-section">
  <div class="container">
    <h2 class="section-title">Key Features</h2>
    
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3>Report Crimes</h3>
        <p>Report incidents with location data and multimedia evidence</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <h3>Crime Mapping</h3>
        <p>View crime hotspots and statistics on interactive maps</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-bell"></i>
        </div>
        <h3>Safety Alerts</h3>
        <p>Receive notifications about incidents in your area</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-phone"></i>
        </div>
        <h3>Emergency SOS</h3>
        <p>One-tap emergency assistance with location sharing</p>
      </div>
    </div>
    
    <div class="cta-container">
      <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
    </div>
  </div>
</section>

<?php include_once 'includes/footer.php'; ?>

