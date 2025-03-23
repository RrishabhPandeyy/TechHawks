-- Create database
CREATE DATABASE IF NOT EXISTS crime_reporting_system;
USE crime_reporting_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    avatar_url VARCHAR(255),
    otp VARCHAR(10),
    otp_expiry DATETIME,
    is_police BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User details table
CREATE TABLE IF NOT EXISTS user_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aadhar_number VARCHAR(20) NOT NULL,
    relative_phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Crime reports table
CREATE TABLE IF NOT EXISTS crime_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    address TEXT NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    evidence_urls TEXT,
    status ENUM('reported', 'investigating', 'resolved', 'closed') DEFAULT 'reported',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- SOS alerts table
CREATE TABLE IF NOT EXISTS sos_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    type ENUM('standard', 'emergency') DEFAULT 'standard',
    status ENUM('active', 'resolved', 'false_alarm') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Comments table for crime reports
CREATE TABLE IF NOT EXISTS report_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Report status history
CREATE TABLE IF NOT EXISTS report_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    old_status ENUM('reported', 'investigating', 'resolved', 'closed'),
    new_status ENUM('reported', 'investigating', 'resolved', 'closed') NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Police stations table
CREATE TABLE IF NOT EXISTS police_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Police officers table
CREATE TABLE IF NOT EXISTS police_officers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    station_id INT NOT NULL,
    badge_number VARCHAR(20) NOT NULL UNIQUE,
    rank VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES police_stations(id) ON DELETE CASCADE
);

-- Safety alerts table
CREATE TABLE IF NOT EXISTS safety_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    area_name VARCHAR(100),
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    radius_km DECIMAL(5, 2),
    alert_level ENUM('info', 'warning', 'danger') DEFAULT 'info',
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- User alert preferences
CREATE TABLE IF NOT EXISTS user_alert_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    alert_radius_km DECIMAL(5, 2) DEFAULT 5.0,
    alert_types VARCHAR(255) DEFAULT 'theft,assault,vandalism,fraud,harassment',
    email_alerts BOOLEAN DEFAULT TRUE,
    sms_alerts BOOLEAN DEFAULT TRUE,
    push_alerts BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_crime_reports_location ON crime_reports(lat, lng);
CREATE INDEX idx_crime_reports_type ON crime_reports(type);
CREATE INDEX idx_crime_reports_status ON crime_reports(status);
CREATE INDEX idx_sos_alerts_location ON sos_alerts(lat, lng);
CREATE INDEX idx_sos_alerts_status ON sos_alerts(status);
CREATE INDEX idx_police_stations_location ON police_stations(lat, lng);
CREATE INDEX idx_safety_alerts_location ON safety_alerts(lat, lng);
CREATE INDEX idx_safety_alerts_dates ON safety_alerts(start_date, end_date);

-- Insert sample data

-- Sample users (password is 'password123' hashed with password_hash)
INSERT INTO users (name, username, email, password, phone, is_police) VALUES
('John Doe', 'johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567890', FALSE),
('Jane Smith', 'janesmith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1987654321', FALSE),
('Officer Johnson', 'ojohnson', 'officer@police.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1122334455', TRUE),
('Captain Williams', 'cwilliams', 'captain@police.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1555666777', TRUE);

-- Sample user details
INSERT INTO user_details (user_id, aadhar_number, relative_phone, address, lat, lng) VALUES
(1, '123456789012', '+1234567899', '123 Main St, New York, NY', 40.7128, -74.0060),
(2, '987654321098', '+1987654329', '456 Park Ave, Los Angeles, CA', 34.0522, -118.2437);

-- Sample police stations
INSERT INTO police_stations (name, address, phone, lat, lng) VALUES
('Central Police Station', '100 Police Plaza, New York, NY', '+1555123456', 40.7138, -74.0070),
('North District Police', '200 North St, New York, NY', '+1555234567', 40.7500, -73.9800),
('East Police Headquarters', '300 East Blvd, New York, NY', '+1555345678', 40.7200, -73.9500);

-- Sample police officers
INSERT INTO police_officers (user_id, station_id, badge_number, rank) VALUES
(3, 1, 'B12345', 'Officer'),
(4, 1, 'B67890', 'Captain');

-- Sample crime reports
INSERT INTO crime_reports (user_id, type, description, address, lat, lng, status) VALUES
(1, 'theft', 'My bike was stolen from outside the grocery store.', '123 Market St, New York, NY', 40.7140, -74.0062, 'reported'),
(2, 'vandalism', 'Graffiti on the wall of the community center.', '456 Community Ave, New York, NY', 40.7145, -74.0065, 'investigating'),
(1, 'assault', 'I was attacked while walking home from work.', '789 Night St, New York, NY', 40.7150, -74.0070, 'resolved'),
(2, 'fraud', 'Someone used my credit card information to make unauthorized purchases.', '321 Bank St, New York, NY', 40.7155, -74.0075, 'closed');

-- Sample SOS alerts
INSERT INTO sos_alerts (user_id, lat, lng, type, status) VALUES
(1, 40.7128, -74.0060, 'standard', 'resolved'),
(2, 34.0522, -118.2437, 'emergency', 'active');

-- Sample report comments
INSERT INTO report_comments (report_id, user_id, comment) VALUES
(1, 3, 'We are looking into this case. Please provide any additional details if you remember them.'),
(2, 3, 'An officer has been dispatched to document the vandalism.'),
(3, 4, 'The suspect has been apprehended. Please come to the station to provide a formal statement.');

-- Sample report status history
INSERT INTO report_status_history (report_id, user_id, old_status, new_status, notes) VALUES
(2, 3, 'reported', 'investigating', 'Assigned to Officer Johnson'),
(3, 4, 'investigating', 'resolved', 'Suspect apprehended, case closed');

-- Sample safety alerts
INSERT INTO safety_alerts (title, description, area_name, lat, lng, radius_km, alert_level, start_date, end_date, created_by) VALUES
('Increased Theft Activity', 'There has been an increase in bicycle thefts in the downtown area. Please secure your bikes properly.', 'Downtown', 40.7128, -74.0060, 2.5, 'warning', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 3),
('Road Closure', 'Main Street will be closed for construction from 7 AM to 7 PM.', 'Main Street', 40.7140, -74.0062, 1.0, 'info', NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 4),
('Severe Weather Warning', 'Flash flood warning in effect. Avoid low-lying areas.', 'Citywide', 40.7128, -74.0060, 10.0, 'danger', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 3);

-- Sample user alert preferences
INSERT INTO user_alert_preferences (user_id, alert_radius_km, alert_types, email_alerts, sms_alerts, push_alerts) VALUES
(1, 5.0, 'theft,assault,vandalism', TRUE, TRUE, TRUE),
(2, 3.0, 'theft,fraud,harassment', TRUE, FALSE, TRUE);

