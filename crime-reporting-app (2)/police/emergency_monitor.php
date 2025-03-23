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

$page_title = "Emergency Monitor";

// Get police station ID (in a real app, this would be associated with the officer's account)
$police_station_id = 1; // Default to first station for demo

// Get active emergency streams
$active_streams = [];
$stmt = $conn->prepare("CALL GetActiveEmergencyStreams(?)");
$stmt->bind_param("i", $police_station_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $active_streams[] = $row;
}
$stmt->close();

// Get active SOS alerts
$active_sos = [];
$sql = "SELECT sa.*, u.name as user_name, u.phone as user_phone, ud.relative_phone, ud.address as user_address
      FROM sos_alerts sa
      JOIN users u ON sa.user  ud.address as user_address
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
      )";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $police_station_id, $police_station_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $active_sos[] = $row;
}
$stmt->close();

include_once '../includes/header.php';
?>

<div class="emergency-monitor-container">
    <div class="container">
        <h1 class="page-title">Emergency Monitor</h1>
        
        <div class="emergency-alerts-section">
            <div class="card danger-card">
                <div class="card-header danger-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> Active Emergency Alerts</h2>
                    <p>Immediate attention required</p>
                </div>
                
                <div class="card-body">
                    <?php if (empty($active_streams) && empty($active_sos)): ?>
                        <div class="empty-state">
                            <p>No active emergency alerts at this time.</p>
                        </div>
                    <?php else: ?>
                        <!-- Emergency SOS with Live Stream -->
                        <?php if (!empty($active_streams)): ?>
                            <h3 class="section-title">Emergency SOS with Live Stream</h3>
                            
                            <div class="emergency-streams">
                                <?php foreach ($active_streams as $stream): ?>
                                    <div class="emergency-stream-card">
                                        <div class="stream-header">
                                            <div class="stream-info">
                                                <h4>
                                                    <span class="emergency-badge">EMERGENCY</span>
                                                    <?php echo htmlspecialchars($stream['user_name']); ?>
                                                </h4>
                                                <p class="stream-time">
                                                    Started: <?php echo format_date($stream['started_at'], 'M j, Y g:i A'); ?>
                                                </p>
                                            </div>
                                            
                                            <div class="stream-actions">
                                                <a href="tel:<?php echo htmlspecialchars($stream['user_phone']); ?>" class="btn btn-sm">
                                                    <i class="fas fa-phone"></i> Call User
                                                </a>
                                                <button class="btn btn-sm btn-outline dispatch-btn" data-lat="<?php echo $stream['lat']; ?>" data-lng="<?php echo $stream['lng']; ?>">
                                                    <i class="fas fa-car"></i> Dispatch
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="stream-content">
                                            <div class="stream-video">
                                                <!-- In a real app, this would be a live video stream -->
                                                <div class="video-placeholder">
                                                    <i class="fas fa-video"></i>
                                                    <p>Live Stream</p>
                                                </div>
                                            </div>
                                            
                                            <div class="stream-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Phone:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($stream['user_phone']); ?></span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Emergency Contact:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($stream['relative_phone']); ?></span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Address:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($stream['user_address']); ?></span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Location:</span>
                                                    <span class="detail-value">
                                                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $stream['lat']; ?>,<?php echo $stream['lng']; ?>" target="_blank">
                                                            <?php echo $stream['lat']; ?>, <?php echo $stream['lng']; ?>
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="stream-footer">
                                            <button class="btn btn-danger end-stream-btn" data-stream-id="<?php echo $stream['id']; ?>">
                                                End Emergency
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Standard SOS Alerts -->
                        <?php if (!empty($active_sos)): ?>
                            <h3 class="section-title">Standard SOS Alerts</h3>
                            
                            <div class="sos-alerts">
                                <?php foreach ($active_sos as $sos): ?>
                                    <?php if ($sos['type'] !== 'emergency' || !in_array($sos['id'], array_column($active_streams, 'sos_alert_id'))): ?>
                                        <div class="sos-alert-card">
                                            <div class="sos-header">
                                                <div class="sos-info">
                                                    <h4>
                                                        <span class="sos-badge">SOS</span>
                                                        <?php echo htmlspecialchars($sos['user_name']); ?>
                                                    </h4>
                                                    <p class="sos-time">
                                                        Received: <?php echo format_date($sos['created_at'], 'M j, Y g:i A'); ?>
                                                    </p>
                                                </div>
                                                
                                                <div class="sos-actions">
                                                    <a href="tel:<?php echo htmlspecialchars($sos['user_phone']); ?>" class="btn btn-sm">
                                                        <i class="fas fa-phone"></i> Call User
                                                    </a>
                                                    <button class="btn btn-sm btn-outline dispatch-btn" data-lat="<?php echo $sos['lat']; ?>" data-lng="<?php echo $sos['lng']; ?>">
                                                        <i class="fas fa-car"></i> Dispatch
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="sos-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Phone:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($sos['user_phone']); ?></span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Emergency Contact:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($sos['relative_phone']); ?></span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Address:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($sos['user_address']); ?></span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Location:</span>
                                                    <span class="detail-value">
                                                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $sos['lat']; ?>,<?php echo $sos['lng']; ?>" target="_blank">
                                                            <?php echo $sos['lat']; ?>, <?php echo $sos['lng']; ?>
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="sos-footer">
                                                <button class="btn btn-danger resolve-sos-btn" data-sos-id="<?php echo $sos['id']; ?>">
                                                    Resolve Alert
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="emergency-map-section">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-map-marked-alt"></i> Emergency Locations</h2>
                </div>
                
                <div class="card-body p-0">
                    <div id="emergencyMap" class="emergency-map"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize emergency map
    const mapElement = document.getElementById('emergencyMap');
    
    if (mapElement) {
        // Initialize map
        const map = new google.maps.Map(mapElement, {
            center: { lat: 20.5937, lng: 78.9629 }, // Center of India
            zoom: 5,
            mapTypeControl: false
        });
        
        // Add markers for emergency locations
        const markers = [];
        const infoWindow = new google.maps.InfoWindow();
        
        <?php foreach (array_merge($active_streams, $active_sos) as $alert): ?>
            // Create marker
            const marker = new google.maps.Marker({
                position: { lat: <?php echo $alert['lat']; ?>, lng: <?php echo $alert['lng']; ?> },
                map: map,
                title: '<?php echo $alert['type'] === 'emergency' ? 'EMERGENCY' : 'SOS'; ?>: <?php echo htmlspecialchars($alert['user_name']); ?>',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '<?php echo $alert['type'] === 'emergency' ? '#F44336' : '#FF9800'; ?>',
                    fillOpacity: 0.7,
                    strokeWeight: 1,
                    strokeColor: '#ffffff',
                    scale: 10
                }
            });
            
            // Add click event listener
            marker.addListener('click', () => {
                // Set info window content
                infoWindow.setContent(`
                    <div style="padding: 8px; max-width: 200px;">
                        <h3 style="margin: 0 0 8px; font-weight: bold;"><?php echo $alert['type'] === 'emergency' ? 'EMERGENCY' : 'SOS'; ?></h3>
                        <p style="margin: 0 0 4px;"><strong>Name:</strong> <?php echo htmlspecialchars($alert['user_name']); ?></p>
                        <p style="margin: 0 0 4px;"><strong>Phone:</strong> <?php echo htmlspecialchars($alert['user_phone']); ?></p>
                        <p style="margin: 0;"><strong>Time:</strong> <?php echo format_date($alert['created_at'], 'g:i A'); ?></p>
                    </div>
                `);
                
                // Open info window
                infoWindow.open(map, marker);
            });
            
            markers.push(marker);
        <?php endforeach; ?>
        
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
    }
    
    // End stream button
    const endStreamButtons = document.querySelectorAll('.end-stream-btn');
    
    endStreamButtons.forEach(button => {
        button.addEventListener('click', function() {
            const streamId = this.getAttribute('data-stream-id');
            
            if (confirm('Are you sure you want to end this emergency?')) {
                // Send AJAX request to end stream
                fetch('api/end_emergency_stream.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ stream_id: streamId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
    
    // Resolve SOS button
    const resolveSosButtons = document.querySelectorAll('.resolve-sos-btn');
    
    resolveSosButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sosId = this.getAttribute('data-sos-id');
            
            if (confirm('Are you sure you want to resolve this SOS alert?')) {
                // Send AJAX request to resolve SOS
                fetch('api/resolve_sos_alert.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ sos_id: sosId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
    
    // Dispatch button
    const dispatchButtons = document.querySelectorAll('.dispatch-btn');
    
    dispatchButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lat = this.getAttribute('data-lat');
            const lng = this.getAttribute('data-lng');
            
            // Open Google Maps directions in a new tab
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>

