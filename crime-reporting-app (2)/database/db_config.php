<?php
// Database configuration
$db_config = [
    'host' => 'localhost',
    'user' => 'root', // Change to your MySQL username
    'pass' => '', // Change to your MySQL password
    'name' => 'crime_reporting_system',
    'charset' => 'utf8mb4'
];

// Create a function to get database connection
function get_db_connection() {
    global $db_config;
    
    $conn = new mysqli(
        $db_config['host'],
        $db_config['user'],
        $db_config['pass'],
        $db_config['name']
    );
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset($db_config['charset']);
    
    return $conn;
}

// Create a function to execute a query and return the result
function db_query($sql, $params = []) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    
    // Bind parameters if any
    if (!empty($params)) {
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 's';
            }
            
            $values[] = $param;
        }
        
        // Create references array for bind_param
        $refs = [];
        $refs[] = &$types;
        
        foreach ($values as $key => $value) {
            $refs[] = &$values[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Close the statement
    $stmt->close();
    
    // Close the connection
    $conn->close();
    
    return $result;
}

// Create a function to fetch all rows from a query
function db_fetch_all($sql, $params = []) {
    $result = db_query($sql, $params);
    
    if ($result === false) {
        return false;
    }
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    return $rows;
}

// Create a function to fetch a single row from a query
function db_fetch_one($sql, $params = []) {
    $result = db_query($sql, $params);
    
    if ($result === false) {
        return false;
    }
    
    return $result->fetch_assoc();
}

// Create a function to execute a query and return the number of affected rows
function db_execute($sql, $params = []) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    
    // Bind parameters if any
    if (!empty($params)) {
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 's';
            }
            
            $values[] = $param;
        }
        
        // Create references array for bind_param
        $refs = [];
        $refs[] = &$types;
        
        foreach ($values as $key => $value) {
            $refs[] = &$values[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    // Execute the query
    $stmt->execute();
    
    // Get the number of affected rows
    $affected_rows = $stmt->affected_rows;
    
    // Get the last insert ID if any
    $insert_id = $conn->insert_id;
    
    // Close the statement
    $stmt->close();
    
    // Close the connection
    $conn->close();
    
    return [
        'affected_rows' => $affected_rows,
        'insert_id' => $insert_id
    ];
}

// Create a function to get the last insert ID
function db_last_insert_id() {
    $conn = get_db_connection();
    $insert_id = $conn->insert_id;
    $conn->close();
    
    return $insert_id;
}

// Create a function to escape a string
function db_escape($string) {
    $conn = get_db_connection();
    $escaped = $conn->real_escape_string($string);
    $conn->close();
    
    return $escaped;
}

