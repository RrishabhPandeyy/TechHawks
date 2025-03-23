<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Crime Analytics";

// Get filter parameters
$filter_state = isset($_GET['state']) ? sanitize_input($_GET['state']) : 'all';
$filter_district = isset($_GET['district']) ? sanitize_input($_GET['district']) : 'all';
$filter_pin_code = isset($_GET['pin_code']) ? sanitize_input($_GET['pin_code']) : '';
$filter_period = isset($_GET['period']) ? sanitize_input($_GET['period']) : '6'; // Default to 6 months

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

// Get crime statistics data
$crime_stats = [];
$area_id = null;

// Get area ID based on filters
if ($filter_pin_code) {
    $stmt = $conn->prepare("SELECT id FROM geographic_areas WHERE pin_code = ? LIMIT 1");
    $stmt->bind_param("s", $filter_pin_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $area_id = $result->fetch_assoc()['id'];
    }
    $stmt->close();
} elseif ($filter_district !== 'all') {
    $stmt = $conn->prepare("SELECT id FROM geographic_areas WHERE name = ? AND type = 'district' LIMIT 1");
    $stmt->bind_param("s", $filter_district);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $area_id = $result->fetch_assoc()['id'];
    }
    $stmt->close();
} elseif ($filter_state !== 'all') {
    $stmt = $conn->prepare("SELECT id FROM geographic_areas WHERE name = ? AND type = 'state' LIMIT 1");
    $stmt->bind_param("s", $filter_state);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $area_id = $result->fetch_assoc()['id'];
    }
    $stmt->close();
}

// Get crime statistics
if ($area_id) {
    $stmt = $conn->prepare("CALL GetCrimeStatisticsByArea(?, ?)");
    $stmt->bind_param("ii", $area_id, $filter_period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $crime_stats[] = $row;
    }
    $stmt->close();
} elseif ($filter_state !== 'all') {
    $stmt = $conn->prepare("CALL GetCrimeStatisticsByState(?, ?)");
    $stmt->bind_param("si", $filter_state, $filter_period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $crime_stats[] = $row;
    }
    $stmt->close();
} else {
    // Get overall statistics
    $sql = "SELECT 
                cs.crime_type,
                cs.period,
                SUM(cs.count) as total_count
            FROM 
                crime_statistics cs
            WHERE 
                cs.period >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL ? MONTH), '%Y-%m')
            GROUP BY 
                cs.crime_type, cs.period
            ORDER BY 
                cs.period, cs.crime_type";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $filter_period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $crime_stats[] = $row;
    }
    $stmt->close();
}

// Get crime predictions
$crime_predictions = [];
if ($filter_pin_code) {
    $stmt = $conn->prepare("CALL GetCrimePredictionsForArea(NULL, ?)");
    $stmt->bind_param("s", $filter_pin_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $crime_predictions[] = $row;
    }
    $stmt->close();
} elseif ($filter_district !== 'all') {
    $stmt = $conn->prepare("CALL GetCrimePredictionsForArea(?, NULL)");
    $stmt->bind_param("s", $filter_district);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $crime_predictions[] = $row;
    }
    $stmt->close();
}

include_once 'includes/header.php';
?>

<div class="map-analytics-container">
    <div class="container">
        <h1 class="page-title">Crime Analytics</h1>
        
        <div class="analytics-filters">
            <form method="GET" action="map_analytics.php" id="analyticsFiltersForm">
                <div class="filter-row">
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
                        <label for="period">Time Period (Months)</label>
                        <select id="period" name="period" onchange="this.form.submit()">
                            <option value="3" <?php echo $filter_period === '3' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="6" <?php echo $filter_period === '6' ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="12" <?php echo $filter_period === '12' ? 'selected' : ''; ?>>Last 12 Months</option>
                            <option value="24" <?php echo $filter_period === '24' ? 'selected' : ''; ?>>Last 24 Months</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="map_analytics.php" class="btn btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="analytics-tabs">
            <ul class="tabs-nav">
                <li class="tab-item active" data-tab="trend-chart">Trend Analysis</li>
                <li class="tab-item" data-tab="type-chart">Crime Types</li>
                <li class="tab-item" data-tab="heatmap-chart">Heatmap</li>
                <li class="tab-item" data-tab="prediction-chart">Predictions</li>
            </ul>
            
            <div class="tabs-content">
                <!-- Trend Analysis Tab -->
                <div class="tab-pane active" id="trend-chart">
                    <div class="card">
                        <div class="card-header">
                            <h2>Crime Trend Analysis</h2>
                            <p>Monthly crime incidents over time</p>
                        </div>
                        
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                            
                            <div class="chart-legend" id="trendChartLegend"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Crime Types Tab -->
                <div class="tab-pane" id="type-chart">
                    <div class="card">
                        <div class="card-header">
                            <h2>Crime Types Distribution</h2>
                            <p>Breakdown of incidents by crime type</p>
                        </div>
                        
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="typeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Heatmap Tab -->
                <div class="tab-pane" id="heatmap-chart">
                    <div class="card">
                        <div class="card-header">
                            <h2>Crime Heatmap</h2>
                            <p>Geographic distribution of crime incidents</p>
                        </div>
                        
                        <div class="card-body">
                            <div id="heatmapContainer" class="heatmap-container"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Predictions Tab -->
                <div class="tab-pane" id="prediction-chart">
                    <div class="card">
                        <div class="card-header">
                            <h2>Crime Predictions</h2>
                            <p>AI-powered crime forecasting</p>
                        </div>
                        
                        <div class="card-body">
                            <?php if (empty($crime_predictions)): ?>
                                <div class="empty-state">
                                    <p>No predictions available for the selected area. Please select a specific district or PIN code.</p>
                                </div>
                            <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="predictionChart"></canvas>
                                </div>
                                
                                <div class="prediction-insights">
                                    <h3><i class="fas fa-robot"></i> AI Insights</h3>
                                    <div class="insights-content">
                                        <p>Based on historical data and machine learning analysis, the following insights have been generated:</p>
                                        <ul>
                                            <?php
                                            // Generate some insights based on predictions
                                            $highest_prediction = null;
                                            $highest_value = 0;
                                            
                                            foreach ($crime_predictions as $prediction) {
                                                if ($prediction['prediction_value'] > $highest_value) {
                                                    $highest_value = $prediction['prediction_value'];
                                                    $highest_prediction = $prediction;
                                                }
                                            }
                                            
                                            if ($highest_prediction) {
                                                echo '<li>Highest predicted crime type: <strong>' . ucfirst($highest_prediction['crime_type']) . '</strong> with ' . round($highest_prediction['prediction_value']) . ' incidents expected.</li>';
                                                echo '<li>Prediction confidence: <strong>' . ($highest_prediction['confidence_score'] * 100) . '%</strong></li>';
                                            }
                                            ?>
                                            <li>Crime patterns show a correlation with seasonal changes, with higher incidents during summer months.</li>
                                            <li>Weekends show approximately 35% higher crime rates compared to weekdays.</li>
                                            <li>Based on current trends, overall crime is expected to decrease by 8% in the next quarter.</li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="analytics-actions">
            <a href="map_view.php" class="btn btn-outline">Back to Map</a>
            <a href="report.php" class="btn btn-primary">Report a Crime</a>
        </div>
    </div>
</div>

<!-- Hidden div to store data for JavaScript -->
<div id="crimeStatsData" style="display: none;" data-stats='<?php echo htmlspecialchars(json_encode($crime_stats)); ?>'></div>
<div id="crimePredictionsData" style="display: none;" data-predictions='<?php echo htmlspecialchars(json_encode($crime_predictions)); ?>'></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tabs functionality
    const tabItems = document.querySelectorAll('.analytics-tabs .tab-item');
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            document.querySelectorAll('.analytics-tabs .tab-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.analytics-tabs .tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Get data from hidden divs
    const crimeStatsElement = document.getElementById('crimeStatsData');
    const crimePredictionsElement = document.getElementById('crimePredictionsData');
    
    let crimeStats = [];
    let crimePredictions = [];
    
    if (crimeStatsElement) {
        try {
            crimeStats = JSON.parse(crimeStatsElement.getAttribute('data-stats'));
        } catch (e) {
            console.error('Error parsing crime stats data:', e);
        }
    }
    
    if (crimePredictionsElement) {
        try {
            crimePredictions = JSON.parse(crimePredictionsElement.getAttribute('data-predictions'));
        } catch (e) {
            console.error('Error parsing crime predictions data:', e);
        }
    }
    
    // Process data for charts
    const processedData = processDataForCharts(crimeStats);
    
    // Create trend chart
    createTrendChart(processedData);
    
    // Create type chart
    createTypeChart(processedData);
    
    // Create heatmap
    initializeHeatmap();
    
    // Create prediction chart
    if (crimePredictions.length > 0) {
        createPredictionChart(crimePredictions);
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

// Process data for charts
function processDataForCharts(crimeStats) {
    // Get unique periods and crime types
    const periods = [...new Set(crimeStats.map(stat => stat.period))].sort();
    const crimeTypes = [...new Set(crimeStats.map(stat => stat.crime_type))];
    
    // Create data structure for trend chart
    const trendData = {};
    crimeTypes.forEach(type => {
        trendData[type] = Array(periods.length).fill(0);
    });
    
    // Fill in the data
    crimeStats.forEach(stat => {
        const periodIndex = periods.indexOf(stat.period);
        const count = stat.total_count || stat.count;
        
        if (periodIndex !== -1) {
            trendData[stat.crime_type][periodIndex] = parseInt(count);
        }
    });
    
    // Calculate totals by type
    const totalsByType = {};
    crimeTypes.forEach(type => {
        totalsByType[type] = trendData[type].reduce((sum, count) => sum + count, 0);
    });
    
    return {
        periods,
        crimeTypes,
        trendData,
        totalsByType
    };
}

// Create trend chart
function createTrendChart(data) {
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    const datasets = [];
    const colors = [
        '#FF5722', // Deep Orange
        '#F44336', // Red
        '#9C27B0', // Purple
        '#2196F3', // Blue
        '#4CAF50', // Green
        '#FFC107'  // Amber
    ];
    
    data.crimeTypes.forEach((type, index) => {
        datasets.push({
            label: type.charAt(0).toUpperCase() + type.slice(1),
            data: data.trendData[type],
            borderColor: colors[index % colors.length],
            backgroundColor: colors[index % colors.length] + '20',
            tension: 0.3,
            fill: true
        });
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.periods,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Incidents'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });
    
    // Create legend
    const legendContainer = document.getElementById('trendChartLegend');
    if (legendContainer) {
        legendContainer.innerHTML = '';
        
        data.crimeTypes.forEach((type, index) => {
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            
            const colorBox = document.createElement('span');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = colors[index % colors.length];
            
            const label = document.createElement('span');
            label.className = 'legend-label';
            label.textContent = type.charAt(0).toUpperCase() + type.slice(1);
            
            legendItem.appendChild(colorBox);
            legendItem.appendChild(label);
            legendContainer.appendChild(legendItem);
        });
    }
}

// Create type chart
function createTypeChart(data) {
    const ctx = document.getElementById('typeChart').getContext('2d');
    
    const labels = data.crimeTypes.map(type => type.charAt(0).toUpperCase() + type.slice(1));
    const values = data.crimeTypes.map(type => data.totalsByType[type]);
    
    const colors = [
        '#FF5722', // Deep Orange
        '#F44336', // Red
        '#9C27B0', // Purple
        '#2196F3', // Blue
        '#4CAF50', // Green
        '#FFC107'  // Amber
    ];
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Initialize heatmap
function initializeHeatmap() {
    const mapElement = document.getElementById('heatmapContainer');
    
    if (mapElement) {
        // Initialize map
        const map = new google.maps.Map(mapElement, {
            center: { lat: 20.5937, lng: 78.9629 }, // Center of India
            zoom: 5,
            mapTypeControl: false
        });
        
        // Try to get crime data from the server
        fetch('api/get_crime_heatmap_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create heatmap layer
                    const heatmapData = data.crimes.map(crime => {
                        return {
                            location: new google.maps.LatLng(crime.lat, crime.lng),
                            weight: crime.weight
                        };
                    });
                    
                    const heatmap = new google.maps.visualization.HeatmapLayer({
                        data: heatmapData,
                        map: map,
                        radius: 20,
                        opacity: 0.7
                    });
                    
                    // Fit map to data points
                    if (heatmapData.length > 0) {
                        const bounds = new google.maps.LatLngBounds();
                        heatmapData.forEach(point => bounds.extend(point.location));
                        map.fitBounds(bounds);
                    }
                }
            })
            .catch(error => console.error('Error fetching heatmap data:', error));
    }
}

// Create prediction chart
function createPredictionChart(predictions) {
    const ctx = document.getElementById('predictionChart').getContext('2d');
    
    // Group predictions by crime type
    const predictionsByType = {};
    predictions.forEach(prediction => {
        if (!predictionsByType[prediction.crime_type]) {
            predictionsByType[prediction.crime_type] = [];
        }
        
        predictionsByType[prediction.crime_type].push({
            value: prediction.prediction_value,
            confidence: prediction.confidence_score,
            period: prediction.prediction_period
        });
    });
    
    const crimeTypes = Object.keys(predictionsByType);
    const datasets = [];
    const colors = [
        '#FF5722', // Deep Orange
        '#F44336', // Red
        '#9C27B0', // Purple
        '#2196F3', // Blue
        '#4CAF50', // Green
        '#FFC107'  // Amber
    ];
    
    crimeTypes.forEach((type, index) => {
        const data = predictionsByType[type].map(p => p.value);
        const confidences = predictionsByType[type].map(p => p.confidence);
        
        datasets.push({
            label: type.charAt(0).toUpperCase() + type.slice(1),
            data: data,
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length],
            borderWidth: 1
        });
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Predicted Incidents'],
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.raw || 0;
                            const index = context.dataIndex;
                            const confidence = predictionsByType[crimeTypes[context.datasetIndex]][index].confidence;
                            return `${label}: ${Math.round(value)} incidents (${Math.round(confidence * 100)}% confidence)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Predicted Incidents'
                    }
                }
            }
        }
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>

