<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "Alerts & Notifications";

// Get user ID
$user_id = $_SESSION['user_id'];

// Get notifications
$notifications = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("CALL GetUserNotifications(?, ?, ?)");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Get total notifications count for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_notifications = $result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $limit);
$stmt->close();

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    
    $stmt = $conn->prepare("CALL MarkNotificationAsRead(?, ?)");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to remove the query parameter
    header("Location: alerts.php");
    exit;
}

// Get alert subscriptions
$alert_subscriptions = [];
$stmt = $conn->prepare("SELECT * FROM alert_subscriptions WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $alert_subscriptions[] = $row;
}
$stmt->close();

// Process subscription form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'subscribe') {
        $area_name = sanitize_input($_POST['area_name']);
        $pin_code = sanitize_input($_POST['pin_code']);
        $district = sanitize_input($_POST['district']);
        $state = sanitize_input($_POST['state']);
        $crime_types = isset($_POST['crime_types']) ? implode(',', $_POST['crime_types']) : '';
        
        $stmt = $conn->prepare("INSERT INTO alert_subscriptions (user_id, area_name, pin_code, district, state, crime_types) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $area_name, $pin_code, $district, $state, $crime_types);
        $stmt->execute();
        $stmt->close();
        
        set_flash_message("Alert subscription added successfully.");
        header("Location: alerts.php");
        exit;
    } elseif ($_POST['action'] === 'unsubscribe' && isset($_POST['subscription_id'])) {
        $subscription_id = (int)$_POST['subscription_id'];
        
        $stmt = $conn->prepare("DELETE FROM alert_subscriptions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $subscription_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        set_flash_message("Alert subscription removed successfully.");
        header("Location: alerts.php");
        exit;
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

include_once 'includes/header.php';
?>

<div class="alerts-container">
    <div class="container">
        <h1 class="page-title">Alerts & Notifications</h1>
        
        <div class="alerts-layout">
            <!-- Notifications Section -->
            <div class="notifications-section">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-bell"></i> Your Notifications</h2>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state">
                                <p>You don't have any notifications yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                        <div class="notification-icon">
                                            <?php if ($notification['type'] === 'alert'): ?>
                                                <i class="fas fa-exclamation-circle"></i>
                                            <?php elseif ($notification['type'] === 'sos'): ?>
                                                <i class="fas fa-phone"></i>
                                            <?php elseif ($notification['type'] === 'emergency'): ?>
                                                <i class="fas fa-exclamation-triangle"></i>
                                            <?php elseif ($notification['type'] === 'crime'): ?>
                                                <i class="fas fa-file-alt"></i>
                                            <?php else: ?>
                                                <i class="fas fa-info-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="notification-content">
                                            <div class="notification-header">
                                                <h3><?php echo htmlspecialchars($notification['title']); ?></h3>
                                                <span class="notification-time">
                                                    <?php echo format_date($notification['created_at'], 'M j, Y g:i A'); ?>
                                                </span>
                                            </div>
                                            
                                            <p class="notification-message">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            
                                            <?php if (!$notification['is_read']): ?>
                                                <div class="notification-actions">
                                                    <a href="alerts.php?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm">
                                                        Mark as Read
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="alerts.php?page=<?php echo $page - 1; ?>" class="pagination-prev">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="pagination-pages">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="alerts.php?page=<?php echo $i; ?>" class="pagination-page <?php echo $i === $page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="alerts.php?page=<?php echo $page + 1; ?>" class="pagination-next">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Alert Subscriptions Section -->
            <div class="subscriptions-section">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-rss"></i> Alert Subscriptions</h2>
                        <p>Get notified about incidents in specific areas</p>
                    </div>
                    
                    <div class="card-body">
                        <div class="subscription-form">
                            <h3>Add New Subscription</h3>
                            
                            <form method="POST" action="alerts.php">
                                <input type="hidden" name="action" value="subscribe">
                                
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <select id="state" name="state" required>
                                        <option value="">Select State</option>
                                        <?php foreach ($states as $state): ?>
                                            <option value="<?php echo htmlspecialchars($state); ?>">
                                                <?php echo htmlspecialchars($state); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="district">District</label>
                                    <select id="district" name="district" required>
                                        <option value="">Select District</option>
                                        <!-- Districts will be populated via JavaScript -->
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="area_name">Area Name (Optional)</label>
                                    <input type="text" id="area_name" name="area_name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="pin_code">PIN Code (Optional)</label>
                                    <input type="text" id="pin_code" name="pin_code" pattern="[0-9]{6}" title="Enter a valid 6-digit PIN code">
                                </div>
                                
                                <div class="form-group">
                                    <label>Crime Types</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="crime_types[]" value="theft" checked>
                                            Theft  name="crime_types[]" value="theft" checked>
                                            Theft
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="crime_types[]" value="assault" checked>
                                            Assault
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="crime_types[]" value="vandalism" checked>
                                            Vandalism
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="crime_types[]" value="fraud" checked>
                                            Fraud
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="crime_types[]" value="harassment" checked>
                                            Harassment
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="crime_types[]" value="other" checked>
                                            Other
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Subscribe</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="subscription-list">
                            <h3>Your Current Subscriptions</h3>
                            
                            <?php if (empty($alert_subscriptions)): ?>
                                <div class="empty-state">
                                    <p>You don't have any alert subscriptions yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($alert_subscriptions as $subscription): ?>
                                    <div class="subscription-item">
                                        <div class="subscription-details">
                                            <h4>
                                                <?php 
                                                    $location = [];
                                                    if (!empty($subscription['area_name'])) $location[] = $subscription['area_name'];
                                                    if (!empty($subscription['pin_code'])) $location[] = 'PIN: ' . $subscription['pin_code'];
                                                    if (!empty($subscription['district'])) $location[] = $subscription['district'];
                                                    if (!empty($subscription['state'])) $location[] = $subscription['state'];
                                                    echo htmlspecialchars(implode(', ', $location));
                                                ?>
                                            </h4>
                                            
                                            <p class="subscription-types">
                                                <?php 
                                                    $types = explode(',', $subscription['crime_types']);
                                                    echo htmlspecialchars(implode(', ', $types));
                                                ?>
                                            </p>
                                        </div>
                                        
                                        <div class="subscription-actions">
                                            <form method="POST" action="alerts.php">
                                                <input type="hidden" name="action" value="unsubscribe">
                                                <input type="hidden" name="subscription_id" value="<?php echo $subscription['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline">Unsubscribe</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript to populate districts based on selected state
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('state');
    const districtSelect = document.getElementById('district');
    
    if (stateSelect && districtSelect) {
        stateSelect.addEventListener('change', function() {
            const state = this.value;
            
            // Clear current options
            districtSelect.innerHTML = '<option value="">Select District</option>';
            
            if (state) {
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

