<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Dashboard";

// Get user data
$user = get_user_by_id($conn, $_SESSION['user_id']);

// Get user's recent reports
$recent_reports = get_user_reports($conn, $_SESSION['user_id'], 5);

// Get nearby incidents
$nearby_incidents = [];

// If we have user's location, get nearby incidents
if ($user) {
    $stmt = $conn->prepare("SELECT lat, lng FROM user_details WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $location = $result->fetch_assoc();
        $nearby_incidents = get_nearby_reports($conn, $location['lat'], $location['lng'], 5, 5);
    }
}

include_once 'includes/header.php';
?>

<div class="dashboard-container">
  <div class="dashboard-header">
    <div class="container">
      <div class="dashboard-header-content">
        <div>
          <h1>Dashboard</h1>
          <p>Welcome back, <?php echo htmlspecialchars($user['name'] ?? $_SESSION['user_email']); ?></p>
        </div>
        
        <div class="dashboard-actions">
          <a href="report.php" class="btn btn-primary">
            <i class="fas fa-file-alt"></i> Report Crime
          </a>
          <a href="emergency.php" class="btn btn-outline">
            <i class="fas fa-phone"></i> Emergency
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <div class="container">
    <div class="dashboard-cards">
      <div class="dashboard-card">
        <div class="card-icon">
          <i class="fas fa-map"></i>
        </div>
        <div class="card-content">
          <h3>Crime Map</h3>
          <p>View crime incidents in your area</p>
          <a href="map.php" class="btn btn-sm">Open Map</a>
        </div>
      </div>
      
      <div class="dashboard-card">
        <div class="card-icon">
          <i class="fas fa-bell"></i>
        </div>
        <div class="card-content">
          <h3>Safety Alerts</h3>
          <p>Get notified about incidents near you</p>
          <a href="alerts.php" class="btn btn-sm">View Alerts</a>
        </div>
      </div>
      
      <div class="dashboard-card">
        <div class="card-icon">
          <i class="fas fa-phone"></i>
        </div>
        <div class="card-content">
          <h3>Emergency SOS</h3>
          <p>Quick access to emergency services</p>
          <a href="emergency.php" class="btn btn-sm btn-outline">Emergency</a>
        </div>
      </div>
    </div>
    
    <div class="dashboard-tabs">
      <ul class="tabs-nav">
        <li class="tab-item active" data-tab="reports">Your Reports</li>
        <li class="tab-item" data-tab="nearby">Nearby Incidents</li>
      </ul>
      
      <div class="tabs-content">
        <!-- Your Reports Tab -->
        <div class="tab-pane active" id="reports">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-file-alt"></i> Your Recent Reports</h2>
              <p>Track the status of your submitted reports</p>
            </div>
            
            <div class="card-body">
              <?php if (empty($recent_reports)): ?>
                <div class="empty-state">
                  <p>You haven't submitted any reports yet</p>
                  <a href="report.php" class="btn btn-primary">Report a Crime</a>
                </div>
              <?php else: ?>
                <div class="reports-list">
                  <?php foreach ($recent_reports as $report): ?>
                    <?php $type_info = get_crime_type_label($report['type']); ?>
                    <?php $status_info = get_status_label($report['status']); ?>
                    
                    <div class="report-card">
                      <div class="report-status-indicator <?php echo $status_info['color']; ?>"></div>
                      <div class="report-content">
                        <div class="report-header">
                          <div>
                            <h3 class="report-title">
                              <i class="fas <?php echo $type_info['icon']; ?>"></i>
                              <?php echo htmlspecialchars($type_info['label']); ?>
                            </h3>
                            <p class="report-date">
                              Reported on <?php echo format_date($report['created_at']); ?>
                            </p>
                          </div>
                          <div class="report-status <?php echo $status_info['color']; ?>">
                            <?php echo htmlspecialchars($status_info['label']); ?>
                          </div>
                        </div>
                        
                        <p class="report-description">
                          <?php echo htmlspecialchars(substr($report['description'], 0, 150)); ?>
                          <?php if (strlen($report['description']) > 150): ?>...<?php endif; ?>
                        </p>
                        
                        <div class="report-actions">
                          <a href="report_details.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-link">
                            View Details
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  
                  <div class="view-all-link">
                    <a href="my_reports.php" class="btn btn-outline">View All Reports</a>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Nearby Incidents Tab -->
        <div class="tab-pane" id="nearby">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-exclamation-triangle"></i> Nearby Incidents</h2>
              <p>Recent incidents reported in your area</p>
            </div>
            
            <div class="card-body">
              <?php if (empty($nearby_incidents)): ?>
                <div class="empty-state">
                  <p>No recent incidents reported in your area</p>
                </div>
              <?php else: ?>
                <div class="incidents-list">
                  <?php foreach ($nearby_incidents as $incident): ?>
                    <?php $type_info = get_crime_type_label($incident['type']); ?>
                    
                    <div class="incident-card">
                      <div class="incident-content">
                        <div class="incident-header">
                          <h3 class="incident-title">
                            <i class="fas <?php echo $type_info['icon']; ?>"></i>
                            <?php echo htmlspecialchars($type_info['label']); ?>
                          </h3>
                          <div class="incident-location">
                            <?php 
                              $address_parts = explode(',', $incident['address']);
                              echo htmlspecialchars($address_parts[0] ?? 'Unknown location'); 
                            ?>
                          </div>
                        </div>
                        
                        <p class="incident-date">
                          <?php echo format_date($incident['created_at']); ?>
                        </p>
                        
                        <p class="incident-description">
                          <?php echo htmlspecialchars(substr($incident['description'], 0, 150)); ?>
                          <?php if (strlen($incident['description']) > 150): ?>...<?php endif; ?>
                        </p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  
                  <div class="view-all-link">
                    <a href="map.php" class="btn btn-outline">View on Map</a>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php 
$page_scripts = ['assets/js/dashboard.js'];
include_once 'includes/footer.php'; 
?>

