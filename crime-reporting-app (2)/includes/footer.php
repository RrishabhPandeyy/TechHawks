</main>
  
  <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-logo">
          <i class="fas fa-shield-alt"></i>
          <span>SafetyNet</span>
        </div>
        
        <div class="footer-links">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
          </ul>
        </div>
        
        <div class="footer-contact">
          <h3>Contact Us</h3>
          <p><i class="fas fa-envelope"></i> info@safetynet.com</p>
          <p><i class="fas fa-phone"></i> Emergency: 911</p>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> SafetyNet. All rights reserved.</p>
      </div>
    </div>
  </footer>
  
  <script src="assets/js/main.js"></script>
  <?php if (isset($page_scripts)): ?>
    <?php foreach ($page_scripts as $script): ?>
      <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>

