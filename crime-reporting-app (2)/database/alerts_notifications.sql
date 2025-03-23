USE crime_reporting_system;

-- Table for user notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('alert', 'sos', 'emergency', 'crime', 'system') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,  -- ID of related item (crime report, SOS alert, etc.)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for alert subscriptions
CREATE TABLE IF NOT EXISTS alert_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    area_name VARCHAR(100),
    pin_code VARCHAR(20),
    district VARCHAR(100),
    state VARCHAR(100),
    crime_types VARCHAR(255), -- Comma-separated list of crime types
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for emergency broadcasts
CREATE TABLE IF NOT EXISTS emergency_broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sos_alert_id INT NOT NULL,
    broadcast_radius_km DECIMAL(5, 2) DEFAULT 5.0,
    message TEXT NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sos_alert_id) REFERENCES sos_alerts(id) ON DELETE CASCADE
);

-- Table for emergency live streams
CREATE TABLE IF NOT EXISTS emergency_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sos_alert_id INT NOT NULL,
    stream_url VARCHAR(255),
    stream_key VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME,
    FOREIGN KEY (sos_alert_id) REFERENCES sos_alerts(id) ON DELETE CASCADE
);

-- Table for AI chat conversations
CREATE TABLE IF NOT EXISTS ai_chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for AI chat messages
CREATE TABLE IF NOT EXISTS ai_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    message TEXT NOT NULL,
    is_user BOOLEAN DEFAULT TRUE, -- TRUE if message is from user, FALSE if from AI
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES ai_chat_conversations(id) ON DELETE CASCADE
);

-- Table for crime prediction data
CREATE TABLE IF NOT EXISTS crime_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100),
    pin_code VARCHAR(20),
    district VARCHAR(100),
    state VARCHAR(100),
    crime_type VARCHAR(50),
    prediction_value DECIMAL(10, 2), -- Predicted number of crimes
    confidence_score DECIMAL(5, 2),  -- Confidence level (0-1)
    prediction_period VARCHAR(50),   -- e.g., 'next_month', 'next_quarter'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for geographic areas
CREATE TABLE IF NOT EXISTS geographic_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('state', 'district', 'city', 'area', 'pin_code') NOT NULL,
    parent_id INT,  -- For hierarchical relationships
    pin_code VARCHAR(20),
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    boundary_points TEXT,  -- GeoJSON or similar format for area boundary
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES geographic_areas(id) ON DELETE SET NULL
);

-- Table for crime statistics by area
CREATE TABLE IF NOT EXISTS crime_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    crime_type VARCHAR(50) NOT NULL,
    count INT NOT NULL DEFAULT 0,
    period VARCHAR(20) NOT NULL,  -- e.g., '2023-01', '2023-Q1'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES geographic_areas(id) ON DELETE CASCADE,
    UNIQUE KEY (area_id, crime_type, period)
);

-- Add indexes for better performance
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_alert_subscriptions_user_id ON alert_subscriptions(user_id);
CREATE INDEX idx_alert_subscriptions_pin_code ON alert_subscriptions(pin_code);
CREATE INDEX idx_emergency_broadcasts_sos_alert_id ON emergency_broadcasts(sos_alert_id);
CREATE INDEX idx_emergency_streams_sos_alert_id ON emergency_streams(sos_alert_id);
CREATE INDEX idx_ai_chat_conversations_user_id ON ai_chat_conversations(user_id);
CREATE INDEX idx_ai_chat_messages_conversation_id ON ai_chat_messages(conversation_id);
CREATE INDEX idx_crime_predictions_area ON crime_predictions(state, district, pin_code);
CREATE INDEX idx_geographic_areas_parent_id ON geographic_areas(parent_id);
CREATE INDEX idx_geographic_areas_pin_code ON geographic_areas(pin_code);
CREATE INDEX idx_crime_statistics_area_id ON crime_statistics(area_id);
CREATE INDEX idx_crime_statistics_period ON crime_statistics(period);

-- Sample data for geographic areas
INSERT INTO geographic_areas (name, type, pin_code, lat, lng) VALUES
('Maharashtra', 'state', NULL, 19.7515, 75.7139, NULL),
('Karnataka', 'state', NULL, 15.3173, 75.7139, NULL),
('Tamil Nadu', 'state', NULL, 11.1271, 78.6569, NULL),
('Mumbai', 'district', NULL, 19.0760, 72.8777, NULL),
('Bangalore', 'district', NULL, 12.9716, 77.5946, NULL),
('Chennai', 'district', NULL, 13.0827, 80.2707, NULL),
('Andheri', 'area', '400053', 19.1136, 72.8697, NULL),
('Bandra', 'area', '400050', 19.0596, 72.8295, NULL),
('Koramangala', 'area', '560034', 12.9352, 77.6245, NULL),
('Indiranagar', 'area', '560038', 12.9784, 77.6408, NULL),
('T Nagar', 'area', '600017', 13.0418, 80.2341, NULL),
('Adyar', 'area', '600020', 13.0012, 80.2565, NULL);

-- Update parent relationships
UPDATE geographic_areas SET parent_id = 1 WHERE name IN ('Mumbai');
UPDATE geographic_areas SET parent_id = 2 WHERE name IN ('Bangalore');
UPDATE geographic_areas SET parent_id = 3 WHERE name IN ('Chennai');
UPDATE geographic_areas SET parent_id = 4 WHERE name IN ('Andheri', 'Bandra');
UPDATE geographic_areas SET parent_id = 5 WHERE name IN ('Koramangala', 'Indiranagar');
UPDATE geographic_areas SET parent_id = 6 WHERE name IN ('T Nagar', 'Adyar');

-- Sample data for crime statistics
INSERT INTO crime_statistics (area_id, crime_type, count, period) VALUES
(7, 'theft', 45, '2023-01'),
(7, 'theft', 38, '2023-02'),
(7, 'theft', 42, '2023-03'),
(7, 'theft', 40, '2023-04'),
(7, 'theft', 36, '2023-05'),
(7, 'assault', 12, '2023-01'),
(7, 'assault', 15, '2023-02'),
(7, 'assault', 10, '2023-03'),
(7, 'assault', 14, '2023-04'),
(7, 'assault', 11, '2023-05'),
(8, 'theft', 32, '2023-01'),
(8, 'theft', 35, '2023-02'),
(8, 'theft', 30, '2023-03'),
(8, 'theft', 28, '2023-04'),
(8, 'theft', 33, '2023-05'),
(9, 'theft', 28, '2023-01'),
(9, 'theft', 25, '2023-02'),
(9, 'theft', 30, '2023-03'),
(9, 'theft', 27, '2023-04'),
(9, 'theft', 24, '2023-05');

-- Sample data for crime predictions
INSERT INTO crime_predictions (area_name, pin_code, district, state, crime_type, prediction_value, confidence_score, prediction_period) VALUES
('Andheri', '400053', 'Mumbai', 'Maharashtra', 'theft', 39.5, 0.85, 'next_month'),
('Andheri', '400053', 'Mumbai', 'Maharashtra', 'assault', 13.2, 0.78, 'next_month'),
('Bandra', '400050', 'Mumbai', 'Maharashtra', 'theft', 31.8, 0.82, 'next_month'),
('Koramangala', '560034', 'Bangalore', 'Karnataka', 'theft', 26.3, 0.79, 'next_month');

-- Stored procedure to get notifications for a user
DELIMITER //
CREATE PROCEDURE GetUserNotifications(
    IN p_user_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT * FROM notifications
    WHERE user_id = p_user_id
    ORDER BY created_at DESC
    LIMIT p_limit OFFSET p_offset;
END //
DELIMITER ;

-- Stored procedure to mark notification as read
DELIMITER //
CREATE PROCEDURE MarkNotificationAsRead(
    IN p_notification_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE notifications
    SET is_read = TRUE
    WHERE id = p_notification_id AND user_id = p_user_id;
END //
DELIMITER ;

-- Stored procedure to create a new notification
DELIMITER //
CREATE PROCEDURE CreateNotification(
    IN p_user_id INT,
    IN p_title VARCHAR(100),
    IN p_message TEXT,
    IN p_type ENUM('alert', 'sos', 'emergency', 'crime', 'system'),
    IN p_related_id INT
)
BEGIN
    INSERT INTO notifications (user_id, title, message, type, related_id)
    VALUES (p_user_id, p_title, p_message, p_type, p_related_id);
    
    SELECT LAST_INSERT_ID() AS notification_id;
END //
DELIMITER ;

-- Stored procedure to get crime statistics for an area
DELIMITER //
CREATE PROCEDURE GetCrimeStatisticsByArea(
    IN p_area_id INT,
    IN p_months INT
)
BEGIN
    SELECT 
        cs.crime_type,
        cs.period,
        cs.count,
        ga.name as area_name,
        ga.type as area_type,
        ga.pin_code
    FROM 
        crime_statistics cs
    JOIN 
        geographic_areas ga ON cs.area_id = ga.id
    WHERE 
        cs.area_id = p_area_id
        AND cs.period >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL p_months MONTH), '%Y-%m')
    ORDER BY 
        cs.period, cs.crime_type;
END //
DELIMITER ;

-- Stored procedure to get crime statistics by state
DELIMITER //
CREATE PROCEDURE GetCrimeStatisticsByState(
    IN p_state_name VARCHAR(100),
    IN p_months INT
)
BEGIN
    SELECT 
        cs.crime_type,
        cs.period,
        SUM(cs.count) as total_count,
        ga_district.name as district_name
    FROM 
        crime_statistics cs
    JOIN 
        geographic_areas ga ON cs.area_id = ga.id
    JOIN 
        geographic_areas ga_district ON ga.parent_id = ga_district.id
    JOIN 
        geographic_areas ga_state ON ga_district.parent_id = ga_state.id
    WHERE 
        ga_state.name = p_state_name
        AND cs.period >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL p_months MONTH), '%Y-%m')
    GROUP BY 
        cs.crime_type, cs.period, ga_district.name
    ORDER BY 
        ga_district.name, cs.period, cs.crime_type;
END //
DELIMITER ;

-- Stored procedure to get crime predictions for an area
DELIMITER //
CREATE PROCEDURE GetCrimePredictionsForArea(
    IN p_area_name VARCHAR(100),
    IN p_pin_code VARCHAR(20)
)
BEGIN
    SELECT * FROM crime_predictions
    WHERE area_name = p_area_name
    AND (p_pin_code IS NULL OR pin_code = p_pin_code)
    ORDER BY prediction_period, crime_type;
END //
DELIMITER ;

-- Stored procedure to create emergency broadcast
DELIMITER //
CREATE PROCEDURE CreateEmergencyBroadcast(
    IN p_sos_alert_id INT,
    IN p_broadcast_radius_km DECIMAL(5, 2),
    IN p_message TEXT
)
BEGIN
    INSERT INTO emergency_broadcasts (sos_alert_id, broadcast_radius_km, message)
    VALUES (p_sos_alert_id, p_broadcast_radius_km, p_message);
    
    SELECT LAST_INSERT_ID() AS broadcast_id;
END //
DELIMITER ;

-- Stored procedure to start emergency stream
DELIMITER //
CREATE PROCEDURE StartEmergencyStream(
    IN p_sos_alert_id INT,
    IN p_stream_url VARCHAR(255),
    IN p_stream_key VARCHAR(100)
)
BEGIN
    INSERT INTO emergency_streams (sos_alert_id, stream_url, stream_key)
    VALUES (p_sos_alert_id, p_stream_url, p_stream_key);
    
    SELECT LAST_INSERT_ID() AS stream_id;
END //
DELIMITER ;

-- Stored procedure to end emergency stream
DELIMITER //
CREATE PROCEDURE EndEmergencyStream(
    IN p_stream_id INT
)
BEGIN
    UPDATE emergency_streams
    SET is_active = FALSE, ended_at = NOW()
    WHERE id = p_stream_id;
END //
DELIMITER ;

-- Stored procedure to get active emergency streams
DELIMITER //
CREATE PROCEDURE GetActiveEmergencyStreams(
    IN p_police_station_id INT
)
BEGIN
    SELECT 
        es.*,
        sa.user_id,
        sa.lat,
        sa.lng,
        sa.type as alert_type,
        u.name as user_name,
        u.phone as user_phone,
        ud.relative_phone,
        ud.address as user_address
    FROM 
        emergency_streams es
    JOIN 
        sos_alerts sa ON es.sos_alert_id = sa.id
    JOIN 
        users u ON sa.user_id = u.id
    LEFT JOIN 
        user_details ud ON u.id = ud.user_id
    WHERE 
        es.is_active = TRUE
        AND sa.status = 'active'
        AND (
            -- Find nearest police station
            p_police_station_id = (
                SELECT ps.id
                FROM police_stations ps
                ORDER BY (
                    6371 * acos(
                        cos(radians(sa.lat)) * 
                        cos(radians(ps.lat)) * 
                        cos(radians(ps.lng) - radians(sa.lng)) + 
                        sin(radians(sa.lat)) * 
                        sin(radians(ps.lat))
                    )
                ) ASC
                LIMIT 1
            )
            OR p_police_station_id IS NULL
        );
END //
DELIMITER ;

-- Stored procedure to add AI chat message
DELIMITER //
CREATE PROCEDURE AddAIChatMessage(
    IN p_user_id INT,
    IN p_session_id VARCHAR(100),
    IN p_message TEXT,
    IN p_is_user BOOLEAN
)
BEGIN
    DECLARE v_conversation_id INT;
    
    -- Get or create conversation
    SELECT id INTO v_conversation_id
    FROM ai_chat_conversations
    WHERE user_id = p_user_id AND session_id = p_session_id;
    
    IF v_conversation_id IS NULL THEN
        INSERT INTO ai_chat_conversations (user_id, session_id)
        VALUES (p_user_id, p_session_id);
        
        SET v_conversation_id = LAST_INSERT_ID();
    ELSE
        -- Update conversation timestamp
        UPDATE ai_chat_conversations
        SET updated_at = NOW()
        WHERE id = v_conversation_id;
    END IF;
    
    -- Add message
    INSERT INTO ai_chat_messages (conversation_id, message, is_user)
    VALUES (v_conversation_id, p_message, p_is_user);
    
    -- Return message ID
    SELECT LAST_INSERT_ID() AS message_id;
END //
DELIMITER ;

-- Stored procedure to get AI chat conversation
DELIMITER //
CREATE PROCEDURE GetAIChatConversation(
    IN p_user_id INT,
    IN p_session_id VARCHAR(100),
    IN p_limit INT
)
BEGIN
    DECLARE v_conversation_id INT;
    
    -- Get conversation ID
    SELECT id INTO v_conversation_id
    FROM ai_chat_conversations
    WHERE user_id = p_user_id AND session_id = p_session_id;
    
    IF v_conversation_id IS NOT NULL THEN
        -- Get messages
        SELECT * FROM ai_chat_messages
        WHERE conversation_id = v_conversation_id
        ORDER BY created_at DESC
        LIMIT p_limit;
    END IF;
END //
DELIMITER ;

-- Stored procedure to get nearest police stations
DELIMITER //
CREATE PROCEDURE GetNearestPoliceStations(
    IN p_lat DECIMAL(10, 8),
    IN p_lng DECIMAL(11, 8),
    IN p_limit INT
)
BEGIN
    SELECT 
        ps.*,
        (
            6371 * acos(
                cos(radians(p_lat)) * 
                cos(radians(ps.lat)) * 
                cos(radians(ps.lng) - radians(p_lng)) + 
                sin(radians(p_lat)) * 
                sin(radians(ps.lat))
            )
        ) AS distance_km
    FROM 
        police_stations ps
    ORDER BY 
        distance_km
    LIMIT p_limit;
END //
DELIMITER ;

