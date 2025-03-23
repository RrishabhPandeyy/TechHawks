<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Get crime data for heatmap
$crimes = [];
$sql = "SELECT lat, lng, type, COUNT(*) as count FROM crime_reports GROUP BY lat, lng, type";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Assign weight based on crime type
        $weight = 1;
        switch ($row['type']) {
            case 'assault':
                $weight = 3;
                break;
            case 'theft':
                $weight = 2;
                break;
            case 'vandalism':
                $weight = 1.5;
                break;
            case 'fraud':
                $weight = 1;
                break;
            case 'harassment':
                $weight = 2.5;
                break;
            default:
                $weight = 1;
        }
        
        // Multiply by count
        $weight *= $row['count'];
        
        $crimes[] = [
            'lat' => (float)$row['lat'],
            'lng' => (float)$row['lng'],
            'weight' => $weight
        ];
    }
}

echo json_encode(['success' => true, 'crimes' => $crimes]);

