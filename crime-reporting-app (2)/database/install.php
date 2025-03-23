<?php
// Database installation script
// This script will create the database and tables

// Database configuration
$db_config = [
    'host' => 'localhost',
    'user' => 'root', // Change to your MySQL username
    'pass' => '', // Change to your MySQL password
    'name' => 'crime_reporting_system',
    'charset' => 'utf8mb4'
];

// Connect to MySQL server
$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to MySQL server successfully.<br>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . $db_config['name'];
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($db_config['name']);

// Set charset
$conn->set_charset($db_config['charset']);

// Read SQL files
$sql_files = [
    'crime_reporting_system.sql',
    'stored_procedures.sql',
    'triggers.sql',
    'views.sql'
];

foreach ($sql_files as $file) {
    echo "Executing $file...<br>";
    
    // Read file
    $sql = file_get_contents($file);
    
    // Split SQL file into individual statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (!empty($statement)) {
            if ($conn->query($statement) === FALSE) {
                echo "Error executing statement: " . $conn->error . "<br>";
                echo "Statement: " . $statement . "<br>";
            }
        }
    }
    
    echo "Finished executing $file.<br>";
}

echo "Database installation completed successfully.<br>";

// Close connection
$conn->close();

