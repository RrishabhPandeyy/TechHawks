<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Crime Map View";

// Get filter parameters
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'all';
$filter_state = isset($_GET['state']) ? sanitize_input($_GET['state']) : 'all';
$filter_district = isset($_GET['district']) ? sanitize_input($_GET['district']) : 'all';
$filter_pin_code = isset($_GET['pin_code']) ? sanitize_input($_GET['pin_code']) : '';
$filter_period = isset($_GET['period']) ? sanitize_input($_GET['period']) : 'all';

// Get crime data
$crime_data = [];
$sql = "SELECT cr.*, u.name as reporter_name 
      FROM crime_reports cr 
      LEFT JOIN users u ON cr.user_id = u.id 
      WHERE 1=1";

if ($filter_type !== 'all') {
    $sql .= " AND cr.type = '$filter_type'";
}

if ($filter_period !== 'all') {
    if ($filter_period === 'today') {
        $sql .= " AND DATE(cr.created_at) = CURDATE()";
    } elseif ($filter_period === 'week') {
        $sql .= " AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter_period === 'month') {
        $sql .= " AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    } elseif ($filter_period === 'year') {
        $sql .= " AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    }
}

$sql .= " ORDER BY cr.created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crime_data[] = $row;
    }
}

// Get states for dropdown
$states = [];
$stmt = $conn->prepare("SELECT DISTINCT name FROM geographic_areas WHERE type = 'state' ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $states[] = $row['name'];
}
$stmt->close();

// Get districts for dropdown if state is selected
$districts = [];
if ($filter_state !== 'all') {
    $stmt = $conn->prepare("SELECT DISTINCT name FROM geographic_areas WHERE type = 'district' AND parent_id = (SELECT id FROM geographic_areas WHERE type = 'state' AND name = ?) ORDER BY name");
    $stmt->bind_param("s", $filter_state);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row['name'];
    }
    $stmt->close();
}

include_once 'includes/header.php';
?>

<div class="map-view-container">
    <div class="container">
        <h1 class="page-title">Crime Map View</h1>
        
        <div class="map-filters">
            <form method="GET" action="map_view.php" id="mapFiltersForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="type">Crime Type</label>
                        <select id="type" name="type" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="theft" <?php echo $filter_type === 'theft' ? 'selected' : ''; ?>>Theft</option>
                            <option value="assault" <?php echo $filter_type === 'assault' ? 'selected' : ''; ?>>Assault</option>
                            <option value="vandalism" <?php echo $filter_type === 'vandalism' ? 'selected' : ''; ?>>Vandalism</option>
                            <option value="fraud" <?php echo $filter_type === 'fraud' ? 'selected' : ''; ?>>Fraud</option>
                            <option value="harassment" <?php echo $filter_type === 'harassment' ? 'selected' : ''; ?>>Harassment</option>
                            <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="state">State</label>
                        <select id="state" name="state" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_state === 'all' ? 'selected' : ''; ?>>All States</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo htmlspecialchars($state); ?>" <?php echo $filter_state === $state ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($state); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="district">District</label>
                        <select id="district" name="district" onchange="this.form.submit()" <?php echo $filter_state === 'all' ? 'disabled' : ''; ?>>
                            <option value="all" <?php echo $filter_district === 'all' ? 'selected' : ''; ?>>All Districts</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo htmlspecialchars($district); ?>" <?php echo $filter_district === $district ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="pin_code">PIN Code</label>
                        <input type="text" id="pin_code" name="pin_code" value="<?php echo htmlspecialchars($filter_pin_code); ?>" placeholder="Enter PIN code">
                    </div>
                    
                    <div class="filter-group">
                        <label for="period">Time Period</label>
                        <select id="period" name="period" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $filter_period === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $filter_period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $filter_period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="year" <?php echo $filter_period === 'year' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="map_view.php" class="btn btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="map-container">
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
            
            <div class="map-legend">
                <h3>Crime Types</h3>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #FF5722;"></span>
                        <span class="legend-label">Theft</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #F44336;"></span>
                        <span class="legend-label">Assault</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #9C27B0;"></span>
                        <span class="legend-label">Vandalism</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #2196F3;"></span>
                        <span class="legend-label">Fraud</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #4CAF50;"></span>
                        <span class="legend-label">Harassment</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #FFC107;"></span>
                        <span class="legend-label">Other</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="map-actions">
            <a href="report.php" class="btn btn-primary">Report a Crime</a>
            <a href="map_analytics.php" class="btn btn-outline">View Analytics</a>
        </div>
    </div>
</div>

<!-- Hidden div to store crime data for JavaScript -->
<div id="crimeData" style="display: none;" data-crime='<?php echo htmlspecialchars(json_encode($crime_data)); ?>'></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get crime data from hidden div
    const crimeDataElement = document.getElementById('crimeData');
    let crimeData = [];
    
    if (crimeDataElement) {
        try {
            crimeData = JSON.parse(crimeDataElement.getAttribute('data-crime'));
        } catch (e) {
            console.error('Error parsing crime data:', e);
        }
    }
    
    // Initialize map
    const mapElement = document.getElementById('crimeMap');
    
    if (mapElement) {
        // Initialize map
        const map = new google.maps.Map(mapElement, {
            center: { lat: 20.5937, lng: 78.9629 }, // Center of India
            zoom: 5,
            mapTypeControl: false
        });
        
        // Add markers for crime data
        const markers = [];
        const infoWindow = new google.maps.InfoWindow();
        
        crimeData.forEach(crime => {
            // Skip if no location data
            if (!crime.lat || !crime.lng) return;
            
            // Get crime type color
            const color = getCrimeTypeColor(crime.type);
            
            // Create marker
            const marker = new google.maps.Marker({
                position: { lat: Number.parseFloat(crime.lat), lng: Number.parseFloat(crime.lng) },
                map: map,
                title: crime.type,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: color,
                    fillOpacity: 0.7,
                    strokeWeight: 1,
                    strokeColor: '#ffffff',
                    scale: 10
                }
            });
            
            // Add click event listener
            marker.addListener('click', () => {
                // Format date
                const date = new Date(crime.created_at);
                const formattedDate = date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Set info window content
                infoWindow.setContent(`
                    <div style="padding: 8px; max-width: 200px;">
                        <h3 style="margin: 0 0 8px; font-weight: bold; text-transform: capitalize;">${crime.type}</h3>
                        <p style="margin: 0; font-size: 12px;">${formattedDate}</p>
                    </div>
                `);
                
                // Open info window
                infoWindow.open(map, marker);
                
                // Show crime details card
                showCrimeDetails(crime);
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
        
        // Try to get user's current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // If no markers, center on user's location
                    if (markers.length === 0) {
                        map.setCenter(userLocation);
                        map.setZoom(12);
                    }
                },
                () => {
                    console.log('Error: The Geolocation service failed.');
                }
            );
        }
        
        // Show crime details in card
        function showCrimeDetails(crime) {
            const selectedCrimeCard = document.getElementById('selectedCrime');
            const crimeTitle = document.getElementById('crimeTitle');
            const crimeStatus = document.getElementById('crimeStatus');
            const crimeDate = document.getElementById('crimeDate');
            const crimeDescription = document.getElementById('crimeDescription');
            const closeCardBtn = document.getElementById('closeCard');
            const navigateBtn = document.getElementById('navigateBtn');
            
            // Format date
            const date = new Date(crime.created_at);
            const formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Set card content
            crimeTitle.textContent = crime.type.charAt(0).toUpperCase() + crime.type.slice(1);
            crimeStatus.textContent = crime.status.charAt(0).toUpperCase() + crime.status.slice(1);
            crimeStatus.className = 'crime-status ' + crime.status;
            crimeDate.textContent = formattedDate;
            crimeDescription.textContent = crime.description;
            
            // Show card
            selectedCrimeCard.style.display = 'block';
            
            // Close card button
            closeCardBtn.addEventListener('click', () => {
                selectedCrimeCard.style.display = 'none';
            });
            
            // Navigate button
            navigateBtn.addEventListener('click', () => {
                // Open Google Maps directions in a new tab
                window.open(`https://www.google.com/maps/dir/?api=1&destination=${crime.lat},${crime.lng}`, '_blank');
            });
        }
        
        // Get color for crime type
        function getCrimeTypeColor(type) {
            switch (type.toLowerCase()) {
                case 'theft':
                    return '#FF5722'; // Deep Orange
                case 'assault':
                    return '#F44336'; // Red
                case 'vandalism':
                    return '#9C27B0'; // Purple
                case 'fraud':
                    return '#2196F3'; // Blue
                case 'harassment':
                    return '#4CAF50'; // Green
                default:
                    return '#FFC107'; // Amber
            }
        }
    }
    
    // State and district dropdowns
    const stateSelect = document.getElementById('state');
    const districtSelect = document.getElementById('district');
    
    if (stateSelect && districtSelect) {
        stateSelect.addEventListener('change', function() {
            const state = this.value;
            
            // Clear current options
            districtSelect.innerHTML = '<option value="all">All Districts</option>';
            districtSelect.disabled = state === 'all';
            
            if (state !== 'all') {
                // Fetch districts for the selected state
                fetch(`api/get_districts.php?state=${encodeURIComponent(state)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.districts.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district;
                                option.textContent = district;
                                districtSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error fetching districts:', error));
            }
        });
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>

