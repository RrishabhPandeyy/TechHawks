-- Create tables for emergency streaming functionality

-- Emergency streams table
CREATE TABLE IF NOT EXISTS emergency_streams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sos_alert_id INT NOT NULL,
  user_id INT NOT NULL,
  stream_key VARCHAR(50) NOT NULL,
  stream_url VARCHAR(255) NOT NULL,
  lat DECIMAL(10, 8) NOT NULL,
  lng DECIMAL(11, 8) NOT NULL,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  ended_at DATETIME NULL,
  status ENUM('active', 'ended') DEFAULT 'active',
  FOREIGN KEY (sos_alert_id) REFERENCES sos_alerts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX idx_emergency_streams_status ON emergency_streams(status);

-- Create stored procedure to get active emergency streams for a police station
DELIMITER //
CREATE PROCEDURE GetActiveEmergencyStreams(IN station_id INT)
BEGIN
    SELECT 
        es.*,
        u.name as user_name,
        u.phone as user_phone,
        ud.relative_phone,
        ud.address as user_address
    FROM 
        emergency_streams es
    JOIN 
        users u ON es.user_id = u.id
    LEFT JOIN 
        user_details ud ON u.id = ud.user_id
    WHERE 
        es.status = 'active'
    AND (
        -- Find nearest police station
        station_id = (
            SELECT ps.id
            FROM police_stations ps
            ORDER BY (
                6371 * acos(
                    cos(radians(es.lat)) * 
                    cos(radians(ps.lat)) * 
                    cos(radians(ps.lng) - radians(es.lng)) + 
                    sin(radians(es.lat)) * 
                    sin(radians(ps.lat))
                )
            ) ASC
            LIMIT 1
        )
        OR station_id IS NULL
    );
END //
DELIMITER ;

-- Create stored procedure to end an emergency stream
DELIMITER //
CREATE PROCEDURE EndEmergencyStream(IN stream_id INT)
BEGIN
    UPDATE emergency_streams
    SET 
        status = 'ended',
        ended_at = NOW()
    WHERE 
        id = stream_id;
END //
DELIMITER ;

-- Create stored procedure to resolve an SOS alert
DELIMITER //
CREATE PROCEDURE ResolveSosAlert(IN alert_id INT)
BEGIN
    UPDATE sos_alerts
    SET 
        status = 'resolved',
        updated_at = NOW()
    WHERE 
        id = alert_id;
        
    -- Also end any associated emergency streams
    UPDATE emergency_streams
    SET 
        status = 'ended',
        ended_at = NOW()
    WHERE 
        sos_alert_id = alert_id
    AND
        status = 'active';
END //
DELIMITER ;

-- Insert sample data
INSERT INTO emergency_streams (sos_alert_id, user_id, stream_key, stream_url, lat, lng)
VALUES
(2, 2, 'stream_key_123', 'https://example.com/stream/123', 34.0522, -118.2437);

