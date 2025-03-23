<?php
// Database connection parameters
$db_host = 'localhost';
$db_user = 'root'; // Change to your MySQL username
$db_pass = ''; // Change to your MySQL password
$db_name = 'crime_reporting_system';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");

