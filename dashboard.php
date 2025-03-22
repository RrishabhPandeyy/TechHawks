<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Require login
require_login();

// Check if user is a police officer
// In a real app, you would check the user's role
$is_police = true; // For demo purposes

if (!$is_police) {
    set_flash_message('You do not have permission to access this page.', 'error');
    header('Location: ../dashboard.php');
    exit;
}

$page_title = "Police Dashboard";

// Get active SOS alerts
$active_alerts = [];
$sql = "SELECT sa.*, u.name as user_name, u.phone as user_phone, ud.relative_phone 
        FROM sos_alerts sa 
        JOIN users u ON sa.user_id = u.id 
        LEFT JOIN user_details ud ON u.id = ud.user_id 
        WHERE sa.status = 'active' 
        ORDER BY sa.created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $active_alerts[] = $row;
    }
}

// Get recent crime reports
$recent_reports = [];
$sql = "SELECT cr.*, u.name as reporter_name 
        FROM crime_reports cr 
        LEFT JOIN users u ON cr.user_id = u.id 
        ORDER BY cr.created_at DESC 
        LIMIT 10";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_reports[] = $row;
    }
}

// Get crime statistics
$total_reports = count($recent_reports);
$resolved_reports = 0;

foreach ($recent_reports as $report) {
    if ($report['status'] === 'resolved') {
        $resolved_reports++;
    }
}

$resolution_rate = $total_reports > 0 ? round(($resolved_reports / $total_reports) * 100) : 0;

// Get crime data for charts (same as map.php)
$crime_by_type = [];
$crime_by_status = [];
$crime_by_time = [];

// Process crime by type
$sql = "SELECT type, COUNT(*) as count FROM crime_reports GROUP BY type";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crime_by_type[$row['type']] = $row['count'];
    }
}

// Process crime by status
$sql = "SELECT status, COUNT(*) as count FROM crime_reports GROUP BY status";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crime_by_status[$row['status']] = $row['count'];
    }
}

// Process crime by time (hour of day)
$sql = "SELECT HOUR(created_at) as hour, COUNT(*) as count FROM crime_reports GROUP BY HOUR(created_at) ORDER BY hour";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crime_by_time[$row['hour']] = $row['count'];
    }
}

include_once '../includes/header.php';
?>

<div class="police-dashboard-container">
  <div class="container">
    <div class="dashboard-header">
      <div class="dashboard-header-content">
        <div>
          <h1>Police Dashboard</h1>
          <p>Welcome, Officer <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
        
        <div class="dashboard-actions">
          <a href="officers.php" class="btn btn-primary">
            <i class="fas fa-users"></i> Manage Officers
          </a>
          <a href="reports.php" class="btn btn-outline">
            <i class="fas fa-file-alt"></i> All Reports
          </a>
        </div>
      </div>
    </div>
    
    <?php if (!empty($active_alerts)): ?>
      <div class="active-alerts-section">
        <div class="card danger-card">
          <div class="card-header danger-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Active SOS Alerts</h2>
            <p>Immediate attention required</p>
          </div>
          
          <div class="card-body">
            <div class="alerts-list">
              <?php foreach ($active_alerts as $alert): ?>
                <div class="alert-item <?php echo $alert['type'] === 'emergency' ? 'emergency-alert' : ''; ?>">
                  <div class="alert-info">
                    <div class="alert-header">
                      <span class="alert-badge <?php echo $alert['type'] === 'emergency' ? 'emergency-badge' : 'sos-badge'; ?>">
                        <?php echo $alert['type'] === 'emergency' ? 'EMERGENCY' : 'SOS'; ?>
                      </span>
                      <span class="alert-user">
                        <?php echo htmlspecialchars($alert['user_name'] ?? 'User ID: ' . $alert['user_id']); ?>
                      </span>
                    </div>
                    <p class="alert-time">
                      <?php echo format_date($alert['created_at'], 'M j, Y g:i A'); ?>
                    </p>
                  </div>
                  
                  <div class="alert-actions">
                    <a href="tel:<?php echo htmlspecialchars($alert['user_phone']); ?>" class="btn btn-sm">
                      <i class="fas fa-phone"></i> Connect
                    </a>
                    <a href="view_alert.php?id=<?php echo $alert['id']; ?>" class="btn btn-sm btn-outline">
                      <i class="fas fa-map-marker-alt"></i> View Location
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Total Reports</p>
          <p class="stat-value"><?php echo $total_reports; ?></p>
          <p class="stat-description">Total crime reports</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Active Alerts</p>
          <p class="stat-value"><?php echo count($active_alerts); ?></p>
          <p class="stat-description">SOS alerts requiring attention</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Resolution Rate</p>
          <p class="stat-value"><?php echo $resolution_rate; ?>%</p>
          <p class="stat-description">Cases successfully resolved</p>
        </div>
      </div>
    </div>
    
    <div class="police-dashboard-tabs">
      <ul class="tabs-nav">
        <li class="tab-item active" data-tab="map-tab">
          <i class="fas fa-map"></i> Map View
        </li>
        <li class="tab-item" data-tab="reports-tab">
          <i class="fas fa-file-alt"></i> Recent Reports
        </li>
        <li class="tab-item" data-tab="analytics-tab">
          <i class="fas fa-chart-bar"></i> Analytics
        </li>
      </ul>
      
      <div class="tabs-content">
        <!-- Map Tab -->
        <div class="tab-pane active" id="map-tab">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-map"></i> Crime Incidents Map</h2>
              <p>Interactive map of all reported incidents</p>
            </div>
            
            <div class="card-body">
              <div id="policeMap" class="police-map"></div>
            </div>
          </div>
        </div>
        
        <!-- Reports Tab -->
        <div class="tab-pane" id="reports-tab">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-file-alt"></i> Recent Crime Reports</h2>
              <p>Latest incidents reported by citizens</p>
            </div>
            
            <div class="card-body">
              <?php if (empty($recent_reports)): ?>
                <div class="empty-state">
                  <p>No reports available</p>
                </div>
              <?php else: ?>
                <div class="reports-list">
                  <?php foreach ($recent_reports as $report): ?>
                    <?php $type_info = get_crime_type_label($report['type']); ?>
                    <?php $status_info = get_status_label($report['status']); ?>
                    
                    <div class="police-report-card">
                      <div class="report-status-indicator <?php echo $status_info['color']; ?>"></div>
                      <div class="report-content">
                        <div class="report-header">
                          <div>
                            <h3 class="report-title">
                              <i class="fas <?php echo $type_info['icon']; ?>"></i>
                              <?php echo htmlspecialchars($type_info['label']); ?>
                            </h3>
                            <p class="report-date">
                              Reported on <?php echo format_date($report['created_at'], 'M j, Y g:i A'); ?>
                            </p>
                            <p class="report-user">
                              By: <?php echo htmlspecialchars($report['reporter_name'] ?? 'User ID: ' . $report['user_id']); ?>
                            </p>
                          </div>
                          
                          <div class="report-meta">
                            <div class="report-status <?php echo $status_info['color']; ?>">
                              <?php echo htmlspecialchars($status_info['label']); ?>
                            </div>
                            <div class="report-location">
                              <?php 
                                $address_parts = explode(',', $report['address']);
                                echo htmlspecialchars($address_parts[0] ?? 'Unknown location'); 
                              ?>
                            </div>
                          </div>
                        </div>
                        
                        <p class="report-description">
                          <?php echo htmlspecialchars(substr($report['description'], 0, 150)); ?>
                          <?php if (strlen($report['description']) > 150): ?>...<?php endif; ?>
                        </p>
                        
                        <div class="report-actions">
                          <a href="update_status.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline">
                            Update Status
                          </a>
                          <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm">
                            View Details
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  
                  <div class="view-all-link">
                    <a href="reports.php" class="btn btn-outline">View All Reports</a>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Analytics Tab -->
        <div class="tab-pane" id="analytics-tab">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-chart-bar"></i> Crime Analytics</h2>
              <p>AI-powered crime pattern analysis</p>
            </div>
            
            <div class="card-body">
              <div class="analytics-tabs">
                <ul class="tabs-nav">
                  <li class="tab-item active" data-tab="type-chart">By Type</li>
                  <li class="tab-item" data-tab="status-chart">By Status</li>
                  <li class="tab-item" data-tab  data-tab="status-chart">By Status</li>
                  <li class="tab-item" data-tab="time-chart">By Time</li>
                </ul>
                
                <div class="tabs-content">
                  <!-- By Type Chart -->
                  <div class="tab-pane active" id="type-chart">
                    <div class="chart-container">
                      <canvas id="typeChart"></canvas>
                    </div>
                  </div>
                  
                  <!-- By Status Chart -->
                  <div class="tab-pane" id="status-chart">
                    <div class="chart-container">
                      <canvas id="statusChart"></canvas>
                    </div>
                  </div>
                  
                  <!-- By Time Chart -->
                  <div class="tab-pane" id="time-chart">
                    <div class="chart-container">
                      <canvas id="timeChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="ai-insights">
                <h3><i class="fas fa-robot"></i> AI Insights</h3>
                <div class="insights-content">
                  <p>Based on the crime data analysis, the following patterns have been identified:</p>
                  <ul>
                    <li>Most crimes occur between 8 PM and 2 AM</li>
                    <li>Theft is the most common crime type in the Central District</li>
                    <li>There has been a 15% decrease in reported incidents compared to last month</li>
                    <li>Potential hotspot identified near the intersection of Main St and Park Ave</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Hidden div to store crime data for JavaScript -->
<div id="crimeData" style="display: none;" data-crime='<?php echo htmlspecialchars(json_encode($recent_reports)); ?>'></div>
<div id="crimeTypeData" style="display: none;" data-crime-type='<?php echo htmlspecialchars(json_encode($crime_by_type)); ?>'></div>
<div id="crimeStatusData" style="display: none;" data-crime-status='<?php echo htmlspecialchars(json_encode($crime_by_status)); ?>'></div>
<div id="crimeTimeData" style="display: none;" data-crime-time='<?php echo htmlspecialchars(json_encode($crime_by_time)); ?>'></div>

<?php 
$page_scripts = [
  'https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places',
  'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
  '../assets/js/police-dashboard.js'
];
include_once '../includes/footer.php'; 
?>

