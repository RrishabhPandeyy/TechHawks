<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Emergency";

// Get user data
$user = get_user_by_id($conn, $_SESSION['user_id']);

// Get user location
$user_location = null;
if ($user) {
    $stmt = $conn->prepare("SELECT lat, lng FROM user_details WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user_location = $result->fetch_assoc();
    }
}

// Process SOS alert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'sos') {
        // Regular SOS
        $lat = sanitize_input($_POST['lat']);
        $lng = sanitize_input($_POST['lng']);
        
        $stmt = $conn->prepare("INSERT INTO sos_alerts (user_id, lat, lng, type, status, created_at) VALUES (?, ?, ?, 'standard', 'active', NOW())");
        $stmt->bind_param("idd", $_SESSION['user_id'], $lat, $lng);
        $stmt->execute();
        
        set_flash_message("SOS Alert Sent! Your location has been shared with the nearest police station. Expect a call shortly.", "success");
        header("Location: emergency.php");
        exit;
    } elseif ($_POST['action'] === 'emergency_sos') {
        // Emergency SOS
        $lat = sanitize_input($_POST['lat']);
        $lng = sanitize_input($_POST['lng']);
        
        $stmt = $conn->prepare("INSERT INTO sos_alerts (user_id, lat, lng, type, status, created_at) VALUES (?, ?, ?, 'emergency', 'active', NOW())");
        $stmt->bind_param("idd", $_SESSION['user_id'], $lat, $lng);
        $stmt->execute();
        
        set_flash_message("EMERGENCY SOS ACTIVATED! Alert sent to police and emergency contacts. Help is on the way.", "error");
        header("Location: emergency.php");
        exit;
    }
}

// Mock data for nearby police stations
$nearby_stations = [
    [
        'id' => 'station1',
        'name' => 'Central Police Station',
        'lat' => $user_location ? $user_location['lat'] + 0.01 : 20.5937,
        'lng' => $user_location ? $user_location['lng'] + 0.01 : 78.9629,
        'phone' => '+1234567890',
        'distance' => '1.2 km'
    ],
    [
        'id' => 'station2',
        'name' => 'North District Police',
        'lat' => $user_location ? $user_location['lat'] - 0.015 : 20.5837,
        'lng' => $user_location ? $user_location['lng'] + 0.005 : 78.9679,
        'phone' => '+1234567891',
        'distance' => '1.8 km'
    ],
    [
        'id' => 'station3',
        'name' => 'East Police Headquarters',
        'lat' => $user_location ? $user_location['lat'] + 0.005 : 20.5987,
        'lng' => $user_location ? $user_location['lng'] + 0.02 : 78.9829,
        'phone' => '+1234567892',
        'distance' => '2.5 km'
    ]
];

include_once 'includes/header.php';
?>

<div class="emergency-container">
  <div class="container">
    <h1 class="page-title">Emergency Assistance</h1>
    
    <div class="emergency-layout">
      <div class="emergency-map-section">
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-map-marker-alt"></i> Nearby Police Stations</h2>
            <p>Find police stations near your current location</p>
          </div>
          
          <div class="card-body p-0">
            <div id="policeStationsMap" class="emergency-map"></div>
          </div>
        </div>
      </div>
      
      <div class="emergency-actions-section">
        <div class="card sos-card">
          <div class="card-header primary-header">
            <h2><i class="fas fa-phone"></i> SOS Alert</h2>
            <p>Send your location to the nearest police station</p>
          </div>
          
          <div class="card-body">
            <p>
              Use this option when you need police assistance but are not in immediate danger. 
              Your current location will be shared with the nearest police station, and they will call you.
            </p>
          </div>
          
          <div class="card-footer">
            <form method="POST" action="emergency.php" id="sosForm">
              <input type="hidden" name="action" value="sos">
              <input type="hidden" name="lat" id="sosLat" value="<?php echo $user_location ? $user_location['lat'] : ''; ?>">
              <input type="hidden" name="lng" id="sosLng" value="<?php echo $user_location ? $user_location['lng'] : ''; ?>">
              <button type="submit" class="btn btn-primary btn-block" id="sosBtn">Send SOS Alert</button>
            </form>
          </div>
        </div>
        
        <div class="card emergency-card">
          <div class="card-header danger-header">
            <h2><i class="fas fa-exclamation-triangle"></i> EMERGENCY SOS</h2>
            <p>For immediate life-threatening situations</p>
          </div>
          
          <div class="card-body">
            <p>
              Use this option ONLY in life-threatening emergencies. This will:
            </p>
            <ul class="emergency-list">
              <li>Alert police with your location</li>
              <li>Notify your emergency contacts</li>
              <li>Emit a loud siren sound</li>
              <li>Request immediate police dispatch</li>
            </ul>
          </div>
          
          <div class="card-footer">
            <button type="button" class="btn btn-danger btn-block" id="emergencySosBtn" data-toggle="modal" data-target="#emergencyModal">
              ACTIVATE EMERGENCY SOS
            </button>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-shield-alt"></i> Safety Tips</h2>
          </div>
          
          <div class="card-body">
            <ul class="safety-tips">
              <li>
                <span class="tip-icon"><i class="fas fa-check"></i></span>
                Stay in well-lit areas when walking at night
              </li>
              <li>
                <span class="tip-icon"><i class="fas fa-check"></i></span>
                Share your location with trusted contacts when traveling
              </li>
              <li>
                <span class="tip-icon"><i class="fas fa-check"></i></span>
                Keep emergency contacts easily accessible
              </li>
              <li>
                <span class="tip-icon"><i class="fas fa-check"></i></span>
                Be aware of your surroundings at all times
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Emergency Confirmation Modal -->
<div class="modal" id="emergencyModal">
  <div class="modal-overlay"></div>
  <div class="modal-container">
    <div class="modal-header danger-header">
      <h3><i class="fas fa-exclamation-triangle"></i> Confirm Emergency SOS</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    
    <div class="modal-body">
      <p>
        This will send an emergency alert to police and your emergency contacts.
        Only use this in genuine life-threatening emergencies.
      </p>
    </div>
    
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
      <form method="POST" action="emergency.php" id="emergencySosForm">
        <input type="hidden" name="action" value="emergency_sos">
        <input type="hidden" name="lat" id="emergencyLat" value="<?php echo $user_location ? $user_location['lat'] : ''; ?>">
        <input type="hidden" name="lng" id="emergencyLng" value="<?php echo $user_location ? $user_location['lng'] : ''; ?>">
        <button type="submit" class="btn btn-danger">ACTIVATE EMERGENCY SOS</button>
      </form>
    </div>
  </div>
</div>

<!-- Hidden div to store police stations data for JavaScript -->
<div id="policeStationsData" style="display: none;" data-stations='<?php echo htmlspecialchars(json_encode($nearby_stations)); ?>'></div>

<?php 
$page_scripts = [
  'https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places',
  'assets/js/emergency.js'
];
include_once 'includes/footer.php'; 
?>

