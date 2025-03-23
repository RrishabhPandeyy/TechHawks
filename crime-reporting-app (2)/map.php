<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Crime Map";

// Get filter parameters
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'all';
$filter_region = isset($_GET['region']) ? sanitize_input($_GET['region']) : 'all';

// Fetch crime data
$crime_data = [];
$sql = "SELECT cr.*, u.name as reporter_name 
        FROM crime_reports cr 
        LEFT JOIN users u ON cr.user_id = u.id 
        WHERE 1=1";

if ($filter_type !== 'all') {
    $sql .= " AND cr.type = '$filter_type'";
}

if ($filter_region !== 'all') {
    // This is a simplified example - in a real app, you'd need to filter by geographic region
    $sql .= " AND cr.address LIKE '%$filter_region%'";
}

$sql .= " ORDER BY cr.created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crime_data[] = $row;
    }
}

// Get crime statistics for charts
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
$result  as hour, COUNT(*) as count FROM crime_reports GROUP BY HOUR(created_at) ORDER BY hour";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crime_by_time[$row['hour']] = $row['count'];
    }
}

include_once 'includes/header.php';
?>

<div class="map-container">
  <div class="container">
    <div class="map-header">
      <div class="map-title">
        <h1>Crime Map</h1>
        <p>View and analyze crime incidents in your area</p>
      </div>
      
      <div class="map-filters">
        <div class="filter-group">
          <label for="crime-type">Crime Type</label>
          <select id="crime-type" name="type" onchange="this.form.submit()">
            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
            <option value="theft" <?php echo $filter_type === 'theft' ? 'selected' : ''; ?>>Theft</option>
            <option value="assault" <?php echo $filter_type === 'assault' ? 'selected' : ''; ?>>Assault</option>
            <option value="vandalism" <?php echo $filter_type === 'vandalism' ? 'selected' : ''; ?>>Vandalism</option>
            <option value="fraud" <?php echo $filter_type === 'fraud' ? 'selected' : ''; ?>>Fraud</option>
            <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="region">Region</label>
          <select id="region" name="region" onchange="this.form.submit()">
            <option value="all" <?php echo $filter_region === 'all' ? 'selected' : ''; ?>>All Regions</option>
            <option value="north" <?php echo $filter_region === 'north' ? 'selected' : ''; ?>>North District</option>
            <option value="south" <?php echo $filter_region === 'south' ? 'selected' : ''; ?>>South District</option>
            <option value="east" <?php echo $filter_region === 'east' ? 'selected' : ''; ?>>East District</option>
            <option value="west" <?php echo $filter_region === 'west' ? 'selected' : ''; ?>>West District</option>
            <option value="central" <?php echo $filter_region === 'central' ? 'selected' : ''; ?>>Central District</option>
          </select>
        </div>
      </div>
    </div>
    
    <div class="map-tabs">
      <ul class="tabs-nav">
        <li class="tab-item active" data-tab="map-view">
          <i class="fas fa-map"></i> Map View
        </li>
        <li class="tab-item" data-tab="stats-view">
          <i class="fas fa-chart-bar"></i> Statistics
        </li>
      </ul>
      
      <div class="tabs-content">
        <!-- Map View Tab -->
        <div class="tab-pane active" id="map-view">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-exclamation-triangle"></i> Crime Incidents Map</h2>
              <p>Click on markers to view details about reported incidents</p>
            </div>
            
            <div class="card-body">
              <div id="crimeMap" class="crime-map"></div>
              
              <div id="selectedCrime" class="selected-crime-card" style="display: none;">
                <div class="crime-card-header">
                  <h3 id="crimeTitle"></h3>
                  <span id="crimeStatus" class="crime-status"></span>
                </div>
                <p id="crimeDate" class="crime-date"></p>
                <p id="crimeDescription" class="crime-description"></p>
                <div class="crime-card-actions">
                  <button id="closeCard" class="btn btn-sm btn-outline">Close</button>
                  <button id="navigateBtn" class="btn btn-sm">
                    <i class="fas fa-location-arrow"></i> Navigate
                  </button>
                </div>
              </div>
              
              <div class="map-actions">
                <a href="report.php" class="btn btn-primary">Report a Crime</a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Statistics Tab -->
        <div class="tab-pane" id="stats-view">
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-shield-alt"></i> Crime Statistics</h2>
              <p>Analyze crime patterns and trends in your selected region</p>
            </div>
            
            <div class="card-body">
              <div class="stats-tabs">
                <ul class="tabs-nav">
                  <li class="tab-item active" data-tab="type-stats">By Type</li>
                  <li class="tab-item" data-tab="status-stats">By Status</li>
                  <li class="tab-item" data-tab="time-stats">By Time</li>
                </ul>
                
                <div class="tabs-content">
                  <!-- By Type Tab -->
                  <div class="tab-pane active" id="type-stats">
                    <div class="chart-container">
                      <canvas id="typeChart"></canvas>
                    </div>
                  </div>
                  
                  <!-- By Status Tab -->
                  <div class="tab-pane" id="status-stats">
                    <div class="chart-container">
                      <canvas id="statusChart"></canvas>
                    </div>
                  </div>
                  
                  <!-- By Time Tab -->
                  <div class="tab-pane" id="time-stats">
                    <div class="chart-container">
                      <canvas id="timeChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="stats-cards">
                <div class="stat-card">
                  <div class="stat-title">Total Incidents</div>
                  <div class="stat-value"><?php echo count($crime_data); ?></div>
                </div>
                
                <div class="stat-card">
                  <div class="stat-title">Most Common Type</div>
                  <div class="stat-value">
                    <?php
                      if (!empty($crime_by_type)) {
                        $max_type = array_keys($crime_by_type, max($crime_by_type))[0];
                        $type_info = get_crime_type_label($max_type);
                        echo htmlspecialchars($type_info['label']);
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </div>
                </div>
                
                <div class="stat-card">
                  <div class="stat-title">Resolution Rate</div>
                  <div class="stat-value">
                    <?php
                      $resolved_count = $crime_by_status['resolved'] ?? 0;
                      $total_count = count($crime_data);
                      
                      if ($total_count > 0) {
                        echo round(($resolved_count / $total_count) * 100) . '%';
                      } else {
                        echo '0%';
                      }
                    ?>
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
<div id="crimeData" style="display: none;" data-crime='<?php echo htmlspecialchars(json_encode($crime_data)); ?>'></div>
<div id="crimeTypeData" style="display: none;" data-crime-type='<?php echo htmlspecialchars(json_encode($crime_by_type)); ?>'></div>
<div id="crimeStatusData" style="display: none;" data-crime-status='<?php echo htmlspecialchars(json_encode($crime_by_status)); ?>'></div>
<div id="crimeTimeData" style="display: none;" data-crime-time='<?php echo htmlspecialchars(json_encode($crime_by_time)); ?>'></div>

<?php 
$page_scripts = [
  'https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places',
  'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
  'assets/js/map.js'
];
include_once 'includes/footer.php'; 
?>

