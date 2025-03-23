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

// Get police station ID (in a real app, this would be associated with the officer's account)
$police_station_id = 1; // Default to first station for demo

// Get active SOS alerts for this police station
$active_alerts = [];
$sql = "SELECT sa.*, u.name as user_name, u.phone as user_phone, ud.relative_phone 
      FROM sos_alerts sa 
      JOIN users u ON sa.user_id = u.id 
      LEFT JOIN user_details ud ON u.id = ud.user_id 
      WHERE sa.status = 'active' 
      AND (
          -- Find nearest police station
          ? = (
              SELECT ps.id
              FROM police_stations ps
              ORDER BY (
                  6371 * acos(
                      cos(radians(sa.lat)) * 
                      cos(radians(ps.lat)) * 
                      cos(radians(ps.lng) - radians(sa.lng)) + 
                      sin(radians(sa.lat)) * 
                      sin(radians(ps.lat))
                  )
              ) ASC
              LIMIT 1
          )
          OR ? IS NULL
      )
      ORDER BY sa.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $police_station_id, $police_station_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
  while ($row = $result->fetch_assoc()) {
      $active_alerts[] = $row;
  }
}
$stmt->close();

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
$total_reports = 0;
$resolved_reports = 0;
$investigating_reports = 0;
$reported_reports = 0;

$sql = "SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
          SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
          SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as reported
        FROM crime_reports";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
  $total_reports = $row['total'];
  $resolved_reports = $row['resolved'];
  $investigating_reports = $row['investigating'];
  $reported_reports = $row['reported'];
}

$resolution_rate = $total_reports > 0 ? round(($resolved_reports / $total_reports) * 100) : 0;

// Get crime data for charts
$crime_by_type = [];
$crime_by_status = [];
$crime_by_month = [];
$crime_by_district = [];
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

// Process crime by month (last 5 months)
$sql = "SELECT 
          DATE_FORMAT(created_at, '%Y-%m') as month,
          COUNT(*) as count
        FROM crime_reports
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month";
$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
      $crime_by_month[$row['month']] = $row['count'];
  }
}

// Process crime by district (extract district from address)
$sql = "SELECT 
          SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1) as district,
          COUNT(*) as count
        FROM crime_reports
        GROUP BY district
        ORDER BY count DESC
        LIMIT 10";
$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
      $district = trim($row['district']);
      if (!empty($district)) {
          $crime_by_district[$district] = $row['count'];
      }
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

// Get AI crime prediction data
// In a real app, this would use ML algorithms
// For demo, we'll simulate predictions based on historical data
$crime_prediction = [];

// Simple prediction: average of last 3 months + 10% growth
$last_3_months = array_slice($crime_by_month, -3, 3, true);
if (count($last_3_months) > 0) {
  $avg_crimes = array_sum($last_3_months) / count($last_3_months);
  $predicted_next_month = ceil($avg_crimes * 1.1); // 10% growth
  
  // Get next month
  $next_month = date('Y-m', strtotime('+1 month'));
  $crime_prediction[$next_month] = $predicted_next_month;
}

// Get hotspot predictions
$hotspot_predictions = [];

// Simple hotspot prediction: areas with highest crime rates
$top_districts = array_slice($crime_by_district, 0, 3, true);
foreach ($top_districts as $district => $count) {
  $hotspot_predictions[] = [
    'district' => $district,
    'risk_level' => 'high',
    'crime_types' => array_rand(array_flip(['theft', 'assault', 'vandalism']), 2)
  ];
}

include_once '../includes/header.php';
?>

<div class="police-dashboard-container">
<div class="container">
  <div class="dashboard-header">
    <div class="dashboard-header-content">
      <div>
        <h1>Police Dashboard</h1>
        <p>Welcome, Officer <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></p>
      </div>
      
      <div class="dashboard-actions">
        <a href="officers.php" class="btn btn-primary">
          <i class="fas fa-users"></i> Manage Officers
        </a>
        <a href="reports.php" class="btn btn-outline">
          <i class="fas fa-file-alt"></i> All Reports
        </a>
        <a href="emergency_monitor.php" class="btn btn-danger">
          <i class="fas fa-exclamation-triangle"></i> 
          Emergency Monitor
          <?php if (count($active_alerts) > 0): ?>
            <span class="badge"><?php echo count($active_alerts); ?></span>
          <?php endif; ?>
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
    
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-search"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label">Investigating</p>
        <p class="stat-value"><?php echo $investigating_reports; ?></p>
        <p class="stat-description">Cases under investigation</p>
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
      <li class="tab-item" data-tab="ai-tab">
        <i class="fas fa-robot"></i> AI Predictions
      </li>
      <li class="tab-item" data-tab="chat-tab">
        <i class="fas fa-comments"></i> AI Assistant
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
            <div class="map-controls">
              <div class="map-view-selector">
                <button class="btn btn-sm active" data-map-view="markers">
                  <i class="fas fa-map-marker-alt"></i> Markers
                </button>
                <button class="btn btn-sm" data-map-view="heatmap">
                  <i class="fas fa-fire"></i> Heat Map
                </button>
              </div>
              
              <div class="map-filter">
                <label for="crimeTypeFilter">Filter by Type:</label>
                <select id="crimeTypeFilter" class="form-control">
                  <option value="all">All Types</option>
                  <?php foreach (array_keys($crime_by_type) as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>">
                      <?php echo ucfirst(htmlspecialchars($type)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="map-filter">
                <label for="timeRangeFilter">Time Range:</label>
                <select id="timeRangeFilter" class="form-control">
                  <option value="all">All Time</option>
                  <option value="24h">Last 24 Hours</option>
                  <option value="7d">Last 7 Days</option>
                  <option value="30d">Last 30 Days</option>
                </select>
              </div>
            </div>
            
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
            <p>Detailed crime statistics and trends</p>
          </div>
          
          <div class="card-body">
            <div class="analytics-tabs">
              <ul class="tabs-nav">
                <li class="tab-item active" data-tab="monthly-chart">Monthly Trends</li>
                <li class="tab-item" data-tab="type-chart">By Type</li>
                <li class="tab-item" data-tab="district-chart">By District</li>
                <li class="tab-item" data-tab="time-chart">By Time</li>
                <li class="tab-item" data-tab="status-chart">By Status</li>
              </ul>
              
              <div class="tabs-content">
                <!-- Monthly Trends Chart -->
                <div class="tab-pane active" id="monthly-chart">
                  <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                  </div>
                </div>
                
                <!-- By Type Chart -->
                <div class="tab-pane" id="type-chart">
                  <div class="chart-container">
                    <canvas id="typeChart"></canvas>
                  </div>
                </div>
                
                <!-- By District Chart -->
                <div class="tab-pane" id="district-chart">
                  <div class="chart-container">
                    <canvas id="districtChart"></canvas>
                  </div>
                </div>
                
                <!-- By Time Chart -->
                <div class="tab-pane" id="time-chart">
                  <div class="chart-container">
                    <canvas id="timeChart"></canvas>
                  </div>
                </div>
                
                <!-- By Status Chart -->
                <div class="tab-pane" id="status-chart">
                  <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="analytics-filters">
              <h3>Advanced Filters</h3>
              <div class="filters-grid">
                <div class="filter-item">
                  <label for="dateRangeFilter">Date Range:</label>
                  <div class="date-range-inputs">
                    <input type="date" id="startDate" class="form-control">
                    <span>to</span>
                    <input type="date" id="endDate" class="form-control">
                  </div>
                </div>
                
                <div class="filter-item">
                  <label for="crimeTypeFilterAnalytics">Crime Type:</label>
                  <select id="crimeTypeFilterAnalytics" class="form-control">
                    <option value="all">All Types</option>
                    <?php foreach (array_keys($crime_by_type) as $type): ?>
                      <option value="<?php echo htmlspecialchars($type); ?>">
                        <?php echo ucfirst(htmlspecialchars($type)); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="filter-item">
                  <label for="districtFilter">District:</label>
                  <select id="districtFilter" class="form-control">
                    <option value="all">All Districts</option>
                    <?php foreach (array_keys($crime_by_district) as $district): ?>
                      <option value="<?php echo htmlspecialchars($district); ?>">
                        <?php echo htmlspecialchars($district); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="filter-item">
                  <label for="statusFilter">Status:</label>
                  <select id="statusFilter" class="form-control">
                    <option value="all">All Statuses</option>
                    <option value="reported">Reported</option>
                    <option value="investigating">Investigating</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                  </select>
                </div>
                
                <div class="filter-actions">
                  <button id="applyFilters" class="btn">Apply Filters</button>
                  <button id="resetFilters" class="btn btn-outline">Reset</button>
                </div>
              </div>
            </div>
            
            <div class="export-options">
              <h3>Export Data</h3>
              <div class="export-buttons">
                <button id="exportCSV" class="btn btn-outline">
                  <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button id="exportPDF" class="btn btn-outline">
                  <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button id="printReport" class="btn btn-outline">
                  <i class="fas fa-print"></i> Print Report
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- AI Predictions Tab -->
      <div class="tab-pane" id="ai-tab">
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-robot"></i> AI Crime Predictions</h2>
            <p>Machine learning-based crime forecasting</p>
          </div>
          
          <div class="card-body">
            <div class="prediction-section">
              <h3>Crime Trend Forecast</h3>
              <div class="chart-container">
                <canvas id="predictionChart"></canvas>
              </div>
              <p class="prediction-note">
                <i class="fas fa-info-circle"></i>
                Predictions are based on historical data patterns and may not account for all variables.
              </p>
            </div>
            
            <div class="hotspot-section">
              <h3>Predicted Crime Hotspots</h3>
              <div class="hotspot-map-container">
                <div id="hotspotMap" class="hotspot-map"></div>
              </div>
              
              <div class="hotspot-list">
                <h4>High-Risk Areas</h4>
                <div class="hotspot-cards">
                  <?php foreach ($hotspot_predictions as $hotspot): ?>
                    <div class="hotspot-card">
                      <div class="hotspot-header">
                        <h5><?php echo htmlspecialchars($hotspot['district']); ?></h5>
                        <span class="risk-badge high-risk">High Risk</span>
                      </div>
                      <div class="hotspot-details">
                        <p><strong>Likely Crime Types:</strong></p>
                        <ul class="crime-type-list">
                          <?php foreach ($hotspot['crime_types'] as $crime_type): ?>
                            <li><?php echo ucfirst(htmlspecialchars($crime_type)); ?></li>
                          <?php endforeach; ?>
                        </ul>
                        <p><strong>Recommended Action:</strong></p>
                        <p>Increase patrol frequency in this area, especially during evening hours.</p>
                      </div>
                      <div class="hotspot-actions">
                        <button class="btn btn-sm view-on-map-btn" data-district="<?php echo htmlspecialchars($hotspot['district']); ?>">
                          View on Map
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              
              <div class="ai-insights">
                <h3>AI Insights</h3>
                <div class="insights-content">
                  <p>Based on the crime data analysis, the following patterns have been identified:</p>
                  <ul>
                    <li>Most crimes occur between 8 PM and 2 AM</li>
                    <li>Theft is the most common crime type in the Central District</li>
                    <li>There has been a 15% decrease in reported incidents compared to last month</li>
                    <li>Potential hotspot identified near the intersection of Main St and Park Ave</li>
                  </ul>
                  
                  <div class="resource-allocation">
                    <h4>Recommended Resource Allocation</h4>
                    <div class="allocation-chart-container">
                      <canvas id="resourceAllocationChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- AI Chatbot Tab -->
      <div class="tab-pane" id="chat-tab">
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-comments"></i> AI Police Assistant</h2>
            <p>Get insights and assistance from our AI</p>
          </div>
          
          <div class="card-body">
            <div class="chatbot-container">
              <div class="chat-messages" id="chatMessages">
                <div class="message system-message">
                  <div class="message-content">
                    <p>Hello, I'm your AI Police Assistant. I can help you analyze crime data, generate reports, and provide insights. How can I assist you today?</p>
                  </div>
                </div>
                <!-- Chat messages will be added here dynamically -->
              </div>
              
              <div class="chat-input">
                <form id="chatForm">
                  <input type="text" id="userMessage" class="form-control" placeholder="Ask about crime trends, statistics, or request a report...">
                  <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                </form>
              </div>
              
              <div class="chat-suggestions">
                <p>Try asking:</p>
                <div class="suggestion-chips">
                  <button class="suggestion-chip" data-query="Show me crime trends for the last 3 months">
                    Crime trends for last 3 months
                  </button>
                  <button class="suggestion-chip" data-query="What are the most common crime types in Central District?">
                    Common crimes in Central District
                  </button>
                  <button class="suggestion-chip" data-query="Generate a weekly crime report">
                    Generate weekly report
                  </button>
                  <button class="suggestion-chip" data-query="Predict crime hotspots for next week">
                    Predict next week's hotspots
                  </button>
                </div>
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
<div id="crimeMonthData" style="display: none;" data-crime-month='<?php echo htmlspecialchars(json_encode($crime_by_month)); ?>'></div>
<div id="crimeDistrictData" style="display: none;" data-crime-district='<?php echo htmlspecialchars(json_encode($crime_by_district)); ?>'></div>
<div id="crimePredictionData" style="display: none;" data-crime-prediction='<?php echo htmlspecialchars(json_encode($crime_prediction)); ?>'></div>  data-crime-prediction='<?php echo htmlspecialchars(json_encode($crime_prediction)); ?>'></div>
<div id="hotspotPredictionData" style="display: none;" data-hotspot-prediction='<?php echo htmlspecialchars(json_encode($hotspot_predictions)); ?>'></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation
    const tabItems = document.querySelectorAll('.tabs-nav .tab-item');
    const tabPanes = document.querySelectorAll('.tabs-content .tab-pane');
    
    tabItems.forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            tabItems.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to current tab and pane
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Initialize police map
    const mapElement = document.getElementById('policeMap');
    
    if (mapElement) {
        // Get crime data from hidden div
        const crimeDataElement = document.getElementById('crimeData');
        const crimeData = JSON.parse(crimeDataElement.getAttribute('data-crime'));
        
        // Initialize map
        const map = new google.maps.Map(mapElement, {
            center: { lat: 20.5937, lng: 78.9629 }, // Center of India
            zoom: 5,
            mapTypeControl: false
        });
        
        // Add markers for crime locations
        const markers = [];
        const infoWindow = new google.maps.InfoWindow();
        
        crimeData.forEach(crime => {
            // Create marker
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(crime.lat), lng: parseFloat(crime.lng) },
                map: map,
                title: crime.type,
                icon: getMarkerIcon(crime.type)
            });
            
            // Add click event listener
            marker.addListener('click', () => {
                // Set info window content
                infoWindow.setContent(`
                    <div style="padding: 8px; max-width: 200px;">
                        <h3 style="margin: 0 0 8px; font-weight: bold;">${crime.type.toUpperCase()}</h3>
                        <p style="margin: 0 0 4px;"><strong>Status:</strong> ${crime.status}</p>
                        <p style="margin: 0 0 4px;"><strong>Date:</strong> ${formatDate(crime.created_at)}</p>
                        <p style="margin: 0;">${crime.description.substring(0, 100)}${crime.description.length > 100 ? '...' : ''}</p>
                        <a href="view_report.php?id=${crime.id}" style="display: block; margin-top: 8px; text-align: right;">View Details</a>
                    </div>
                `);
                
                // Open info window
                infoWindow.open(map, marker);
            });
            
            markers.push(marker);
        });
        
        // Fit map to markers
        if (markers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            markers.forEach(marker => bounds.extend(marker.getPosition()));
            map.fitBounds(bounds);
            
            // Don't zoom in too far
            google.maps.event.addListenerOnce(map, 'idle', () => {
                if (map.getZoom() > 15) {
                    map.setZoom(15);
                }
            });
        }
        
        // Map view selector
        const mapViewButtons = document.querySelectorAll('.map-view-selector button');
        let heatmap = null;
        
        mapViewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const view = this.getAttribute('data-map-view');
                
                // Remove active class from all buttons
                mapViewButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to current button
                this.classList.add('active');
                
                // Toggle map view
                if (view === 'markers') {
                    // Show markers
                    markers.forEach(marker => marker.setMap(map));
                    
                    // Hide heatmap
                    if (heatmap) {
                        heatmap.setMap(null);
                    }
                } else if (view === 'heatmap') {
                    // Hide markers
                    markers.forEach(marker => marker.setMap(null));
                    
                    // Show heatmap
                    if (!heatmap) {
                        const heatmapData = markers.map(marker => ({
                            location: marker.getPosition(),
                            weight: 1
                        }));
                        
                        heatmap = new google.maps.visualization.HeatmapLayer({
                            data: heatmapData,
                            map: map,
                            radius: 20
                        });
                    } else {
                        heatmap.setMap(map);
                    }
                }
            });
        });
        
        // Crime type filter
        const crimeTypeFilter = document.getElementById('crimeTypeFilter');
        
        if (crimeTypeFilter) {
            crimeTypeFilter.addEventListener('change', function() {
                const type = this.value;
                
                markers.forEach((marker, index) => {
                    const crime = crimeData[index];
                    
                    if (type === 'all' || crime.type === type) {
                        marker.setMap(map);
                    } else {
                        marker.setMap(null);
                    }
                });
            });
        }
        
        // Time range filter
        const timeRangeFilter = document.getElementById('timeRangeFilter');
        
        if (timeRangeFilter) {
            timeRangeFilter.addEventListener('change', function() {
                const range = this.value;
                const now = new Date();
                
                markers.forEach((marker, index) => {
                    const crime = crimeData[index];
                    const crimeDate = new Date(crime.created_at);
                    let show = true;
                    
                    if (range === '24h') {
                        show = (now - crimeDate) <= (24 * 60 * 60 * 1000);
                    } else if (range === '7d') {
                        show = (now - crimeDate) <= (7 * 24 * 60 * 60 * 1000);
                    } else if (range === '30d') {
                        show = (now - crimeDate) <= (30 * 24 * 60 * 60 * 1000);
                    }
                    
                    if (show) {
                        marker.setMap(map);
                    } else {
                        marker.setMap(null);
                    }
                });
            });
        }
    }
    
    // Initialize hotspot map
    const hotspotMapElement = document.getElementById('hotspotMap');
    
    if (hotspotMapElement) {
        // Get hotspot prediction data
        const hotspotDataElement = document.getElementById('hotspotPredictionData');
        const hotspotData = JSON.parse(hotspotDataElement.getAttribute('data-hotspot-prediction'));
        
        // Initialize map
        const map = new google.maps.Map(hotspotMapElement, {
            center: { lat: 20.5937, lng: 78.9629 }, // Center of India
            zoom: 5,
            mapTypeControl: false
        });
        
        // Add circles for hotspot areas
        const circles = [];
        
        hotspotData.forEach(hotspot => {
            // Geocode district to get coordinates
            // In a real app, you would store coordinates in the database
            // For demo, we'll use random coordinates near the center
            const lat = 20.5937 + (Math.random() * 2 - 1);
            const lng = 78.9629 + (Math.random() * 2 - 1);
            
            // Create circle
            const circle = new google.maps.Circle({
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35,
                map: map,
                center: { lat, lng },
                radius: 50000, // 50km
                title: hotspot.district
            });
            
            circles.push(circle);
        });
        
        // Fit map to circles
        if (circles.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            circles.forEach(circle => bounds.extend(circle.getCenter()));
            map.fitBounds(bounds);
        }
        
        // View on map buttons
        const viewOnMapButtons = document.querySelectorAll('.view-on-map-btn');
        
        viewOnMapButtons.forEach(button => {
            button.addEventListener('click', function() {
                const district = this.getAttribute('data-district');
                
                // Find circle for district
                const circle = circles.find(c => c.title === district);
                
                if (circle) {
                    // Center map on circle
                    map.setCenter(circle.getCenter());
                    map.setZoom(10);
                    
                    // Highlight circle
                    circles.forEach(c => {
                        c.setOptions({
                            strokeColor: c === circle ? '#FF0000' : '#FF9800',
                            fillColor: c === circle ? '#FF0000' : '#FF9800',
                            strokeWeight: c === circle ? 3 : 2,
                            fillOpacity: c === circle ? 0.5 : 0.35
                        });
                    });
                }
            });
        });
    }
    
    // Initialize charts
    initializeCharts();
    
    // AI Chatbot
    const chatForm = document.getElementById('chatForm');
    const userMessageInput = document.getElementById('userMessage');
    const chatMessages = document.getElementById('chatMessages');
    const suggestionChips = document.querySelectorAll('.suggestion-chip');
    
    if (chatForm && userMessageInput && chatMessages) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = userMessageInput.value.trim();
            
            if (message) {
                // Add user message to chat
                addChatMessage(message, 'user');
                
                // Clear input
                userMessageInput.value = '';
                
                // Process message and get response
                processUserMessage(message);
            }
        });
        
        // Suggestion chips
        suggestionChips.forEach(chip => {
            chip.addEventListener('click', function() {
                const query = this.getAttribute('data-query');
                
                // Set input value
                userMessageInput.value = query;
                
                // Submit form
                chatForm.dispatchEvent(new Event('submit'));
            });
        });
    }
    
    // Helper functions
    function getMarkerIcon(crimeType) {
        // Return different icons based on crime type
        const icons = {
            'theft': 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
            'assault': 'http://maps.google.com/mapfiles/ms/icons/purple-dot.png',
            'vandalism': 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            'fraud': 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
            'harassment': 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
        };
        
        return icons[crimeType] || 'http://maps.google.com/mapfiles/ms/icons/red-dot.png';
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function initializeCharts() {
        // Get chart data from hidden divs
        const crimeTypeData = JSON.parse(document.getElementById('crimeTypeData').getAttribute('data-crime-type'));
        const crimeStatusData = JSON.parse(document.getElementById('crimeStatusData').getAttribute('data-crime-status'));
        const crimeTimeData = JSON.parse(document.getElementById('crimeTimeData').getAttribute('data-crime-time'));
        const crimeMonthData = JSON.parse(document.getElementById('crimeMonthData').getAttribute('data-crime-month'));
        const crimeDistrictData = JSON.parse(document.getElementById('crimeDistrictData').getAttribute('data-crime-district'));
        const crimePredictionData = JSON.parse(document.getElementById('crimePredictionData').getAttribute('data-crime-prediction'));
        
        // Monthly Chart
        const monthlyChartElement = document.getElementById('monthlyChart');
        
        if (monthlyChartElement) {
            const labels = Object.keys(crimeMonthData).map(month => {
                const [year, monthNum] = month.split('-');
                return new Date(year, monthNum - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            
            const data = Object.values(crimeMonthData);
            
            new Chart(monthlyChartElement, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Crime Reports',
                        data: data,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Crime Trends'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Reports'
                            }
                        }
                    }
                }
            });
        }
        
        // Type Chart
        const typeChartElement = document.getElementById('typeChart');
        
        if (typeChartElement) {
            const labels = Object.keys(crimeTypeData).map(type => type.charAt(0).toUpperCase() + type.slice(1));
            const data = Object.values(crimeTypeData);
            
            new Chart(typeChartElement, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 206, 86)',
                            'rgb(75, 192, 192)',
                            'rgb(153, 102, 255)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Crime Reports by Type'
                        }
                    }
                }
            });
        }
        
        // District Chart
        const districtChartElement = document.getElementById('districtChart');
        
        if (districtChartElement) {
            const labels = Object.keys(crimeDistrictData);
            const data = Object.values(crimeDistrictData);
            
            new Chart(districtChartElement, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Crime Reports',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Crime Reports by District'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Reports'
                            }
                        }
                    }
                }
            });
        }
        
        // Time Chart
        const timeChartElement = document.getElementById('timeChart');
        
        if (timeChartElement) {
            const labels = Array.from({ length: 24 }, (_, i) => {
                const hour = i % 12 || 12;
                const ampm = i < 12 ? 'AM' : 'PM';
                return `${hour} ${ampm}`;
            });
            
            const data = Array.from({ length: 24 }, (_, i) => crimeTimeData[i] || 0);
            
            new Chart(timeChartElement, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Crime Reports',
                        data: data,
                        backgroundColor: 'rgba(153, 102, 255, 0.5)',
                        borderColor: 'rgb(153, 102, 255)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Crime Reports by Time of Day'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Reports'
                            }
                        }
                    }
                }
            });
        }
        
        // Status Chart
        const statusChartElement = document.getElementById('statusChart');
        
        if (statusChartElement) {
            const labels = Object.keys(crimeStatusData).map(status => status.charAt(0).toUpperCase() + status.slice(1));
            const data = Object.values(crimeStatusData);
            
            new Chart(statusChartElement, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgb(255, 159, 64)',
                            'rgb(54, 162, 235)',
                            'rgb(75, 192, 192)',
                            'rgb(201, 203, 207)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Crime Reports by Status'
                        }
                    }
                }
            });
        }
        
        // Prediction Chart
        const predictionChartElement = document.getElementById('predictionChart');
        
        if (predictionChartElement) {
            // Combine historical and prediction data
            const combinedData = { ...crimeMonthData, ...crimePredictionData };
            
            const labels = Object.keys(combinedData).map(month => {
                const [year, monthNum] = month.split('-');
                return new Date(year, monthNum - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            
            const historicalData = Object.keys(combinedData).map(month => {
                return month in crimeMonthData ? crimeMonthData[month] : null;
            });
            
            const predictionData = Object.keys(combinedData).map(month => {
                return month in crimePredictionData ? crimePredictionData[month] : null;
            });
            
            new Chart(predictionChartElement, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Historical Data',
                            data: historicalData,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            tension: 0.1,
                            fill: false
                        },
                        {
                            label: 'Predicted Data',
                            data: predictionData,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderDash: [5, 5],
                            tension: 0.1,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Crime Trend Forecast'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Reports'
                            }
                        }
                    }
                }
            });
        }
        
        // Resource Allocation Chart
        const resourceAllocationChartElement = document.getElementById('resourceAllocationChart');
        
        if (resourceAllocationChartElement) {
            new Chart(resourceAllocationChartElement, {
                type: 'radar',
                data: {
                    labels: ['Central District', 'North District', 'South District', 'East District', 'West District'],
                    datasets: [
                        {
                            label: 'Current Allocation',
                            data: [65, 59, 90, 81, 56],
                            fill: true,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgb(54, 162, 235)',
                            pointBackgroundColor: 'rgb(54, 162, 235)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(54, 162, 235)'
                        },
                        {
                            label: 'Recommended Allocation',
                            data: [80, 50, 70, 90, 60],
                            fill: true,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgb(255, 99, 132)',
                            pointBackgroundColor: 'rgb(255, 99, 132)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(255, 99, 132)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Resource Allocation by District'
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            });
        }
    }
    
    function addChatMessage(message, sender) {
        const messageElement = document.createElement('div');
        messageElement.className = `message ${sender}-message`;
        
        const contentElement = document.createElement('div');
        contentElement.className = 'message-content';
        
        const textElement = document.createElement('p');
        textElement.textContent = message;
        
        contentElement.appendChild(textElement);
        messageElement.appendChild(contentElement);
        
        chatMessages.appendChild(messageElement);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function processUserMessage(message) {
        // Simulate AI response
        setTimeout(() => {
            let response;
            
            // Simple pattern matching for demo
            if (message.toLowerCase().includes('crime trends')) {
                response = "Based on our data, crime rates have decreased by 15% over the last 3 months. Theft remains the most common crime type, followed by vandalism and assault.";
            } else if (message.toLowerCase().includes('common crimes')) {
                response = "In the Central District, the most common crime types are theft (45%), vandalism (25%), and assault (15%). Most incidents occur between 8 PM and 2 AM.";
            } else if (message.toLowerCase().includes('generate') && message.toLowerCase().includes('report')) {
                response = "I've generated a weekly crime report for you. It shows 23 new incidents, with a 20% decrease compared to last week. Would you like me to email this report to you?";
            } else if (message.toLowerCase().includes('predict') && message.toLowerCase().includes('hotspot')) {
                response = "Based on historical patterns and recent trends, I predict high crime activity in the following areas next week: Central District (near Main St), North District (around Park Ave), and East District (near the shopping mall).";
            } else {
                response = "I'll analyze that request and get back to you. Is there anything specific about crime data or statistics you'd like to know?";
            }
            
            // Add AI response to chat
            addChatMessage(response, 'ai');
        }, 1000);
    }
});
</script>

<?php 
$page_scripts = [
'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&libraries=visualization,places',
'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
'../assets/js/police-dashboard.js'
];
include_once '../includes/footer.php'; 
?>

