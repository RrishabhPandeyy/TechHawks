<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Report Crime";
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $crime_type = sanitize_input($_POST['crime_type']);
    $description = sanitize_input($_POST['description']);
    $address = sanitize_input($_POST['address']);
    $lat = sanitize_input($_POST['lat']);
    $lng = sanitize_input($_POST['lng']);
    
    // Validate form data
    if (empty($crime_type)) {
        $error = 'Please select the type of incident.';
    } elseif (empty($description)) {
        $error = 'Please provide a description of the incident.';
    } elseif (empty($address)) {
        $error = 'Please provide the address of the incident.';
    } elseif (empty($lat) || empty($lng)) {
        $error = 'Please select the location on the map.';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert crime report
            $stmt = $conn->prepare("INSERT INTO crime_reports (user_id, type, description, address, lat, lng, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'reported', NOW())");
            $stmt->bind_param("isssdd", $_SESSION['user_id'], $crime_type, $description, $address, $lat, $lng);
            $stmt->execute();
            
            $report_id = $conn->insert_id;
            
            // Upload evidence files if provided
            $evidence_urls = [];
            
            if (isset($_FILES['evidence']) && is_array($_FILES['evidence']['name'])) {
                for ($i = 0; $i < count($_FILES['evidence']['name']); $i++) {
                    if ($_FILES['evidence']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $file = [
                            'name' => $_FILES['evidence']['name'][$i],
                            'type' => $_FILES['evidence']['type'][$i],
                            'tmp_name' => $_FILES['evidence']['tmp_name'][$i],
                            'error' => $_FILES['evidence']['error'][$i],
                            'size' => $_FILES['evidence']['size'][$i]
                        ];
                        
                        $upload_result = upload_file($file, 'uploads/evidence', ['image/jpeg', 'image/png', 'image/gif', 'video/mp4']);
                        
                        if ($upload_result['success']) {
                            $evidence_urls[] = $upload_result['filepath'];
                        }
                    }
                }
            }
            
            // Update crime report with evidence URLs if any
            if (!empty($evidence_urls)) {
                $evidence_json = json_encode($evidence_urls);
                $stmt = $conn->prepare("UPDATE crime_reports SET evidence_urls = ? WHERE id = ?");
                $stmt->bind_param("si", $evidence_json, $report_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = 'Your crime report has been successfully submitted.';
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = 'Failed to submit report: ' . $e->getMessage();
        }
    }
}

include_once 'includes/header.php';
?>

<div class="report-container">
  <div class="container">
    <div class="report-header">
      <h1><i class="fas fa-exclamation-triangle"></i> Report an Incident</h1>
    </div>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <?php echo $success; ?>
        <p><a href="dashboard.php">Return to Dashboard</a> or <a href="map.php">View Crime Map</a></p>
      </div>
    <?php else: ?>
      <div class="card">
        <form method="POST" action="report.php" enctype="multipart/form-data">
          <div class="card-header">
            <h2>Incident Details</h2>
            <p>Provide information about the incident you want to report</p>
          </div>
          
          <div class="card-body">
            <div class="form-group">
              <label for="crime_type">Incident Type</label>
              <select id="crime_type" name="crime_type" required>
                <option value="">Select incident type</option>
                <option value="theft">Theft</option>
                <option value="assault">Assault</option>
                <option value="vandalism">Vandalism</option>
                <option value="fraud">Fraud</option>
                <option value="harassment">Harassment</option>
                <option value="other">Other</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="description">Description</label>
              <textarea id="description" name="description" rows="5" placeholder="Describe what happened in detail" required></textarea>
            </div>
            
            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" placeholder="Enter the address of the incident" required>
            </div>
            
            <div class="form-group">
              <label><i class="fas fa-map-marker-alt"></i> Location on Map</label>
              <div id="map" class="map-container"></div>
              <input type="hidden" id="lat" name="lat" required>
              <input type="hidden" id="lng" name="lng" required>
              <small class="form-text location-text">No location selected</small>
            </div>
            
            <div class="form-group">
              <label><i class="fas fa-file-alt"></i> Evidence (Photos/Videos)</label>
              <div class="evidence-container" id="evidenceContainer">
                <div class="evidence-preview-container" id="evidencePreview"></div>
                <label for="evidence" class="evidence-upload-btn">
                  <i class="fas fa-upload"></i>
                  <span>Add Files</span>
                </label>
                <input type="file" id="evidence" name="evidence[]" accept="image/*,video/*" multiple class="evidence-input">
              </div>
              <small class="form-text">Upload photos or videos as evidence (max 10MB per file)</small>
            </div>
          </div>
          
          <div class="card-footer">
            <button type="submit" class="btn btn-primary btn-block">Submit Report</button>
            <p class="disclaimer-text">
              By submitting this report, you confirm that the information provided is accurate to the best of your knowledge.
            </p>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php 
$page_scripts = ['https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places', 'assets/js/report.js'];
include_once 'includes/footer.php'; 
?>

