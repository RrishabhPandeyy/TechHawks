
<?php
require 'db.php'; // Database connection

// Set user ID (replace with your session user ID)
$user_id = 1;

// Get today's date
$today = date('Y-m-d');

// Fetch alerts for today
try {
    $stmt = $pdo->prepare("SELECT * FROM alerts WHERE user_id = ? AND DATE(alert_date) = ? ORDER BY alert_date DESC");
    $stmt->execute([$user_id, $today]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $alert_count = count($alerts);
} catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - Notification Center</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
            color: #333;
            box-sizing: border-box;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 28px;
            margin: 0;
        }
        .alert-icon {
            position: relative;
            font-size: 28px;
            cursor: pointer;
        }
        .alert-count {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        tr:hover {
            background: #f1f1f1;
        }
        .no-alerts {
            text-align: center;
            padding: 20px;
            color: #888;
        }
        @media (max-width: 600px) {
            .header h1 {
                font-size: 20px;
            }
            th, td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔔 Alerts - <?php echo date('F j, Y'); ?></h1>
        <div class="alert-icon">
            <i class="fas fa-bell"></i>
            <?php if ($alert_count > 0): ?>
                <span class="alert-count"><?php echo $alert_count; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($alert_count > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Message</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $index => $alert): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($alert['message']); ?></td>
                        <td><?php echo date('F j, Y - H:i', strtotime($alert['alert_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-alerts">🎉 No alerts for today!</p>
    <?php endif; ?>
</div>

</body>
</html>
