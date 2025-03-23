USE crime_reporting_system;

-- Trigger to automatically create a status history entry when a new crime report is created
DELIMITER //
CREATE TRIGGER after_crime_report_insert
AFTER INSERT ON crime_reports
FOR EACH ROW
BEGIN
    INSERT INTO report_status_history (report_id, user_id, old_status, new_status, notes)
    VALUES (NEW.id, NEW.user_id, NULL, NEW.status, 'Initial report created');
END //
DELIMITER ;

-- Trigger to automatically notify users when a new crime report is created in their area
-- This is a simplified version - in a real application, you would use an event queue
DELIMITER //
CREATE TRIGGER after_crime_report_insert_notify
AFTER INSERT ON crime_reports
FOR EACH ROW
BEGIN
    -- Insert into a notification queue table (would need to be created)
    -- This is just a placeholder for the concept
    /*
    INSERT INTO notification_queue (type, data, created_at)
    VALUES ('new_crime_report', JSON_OBJECT(
        'report_id', NEW.id,
        'type', NEW.type,
        'lat', NEW.lat,
        'lng', NEW.lng
    ), NOW());
    */
    
    -- In a real application, you would process this queue with a background job
    -- to send notifications to users based on their preferences and location
END //
DELIMITER ;

-- Trigger to automatically notify police when a new SOS alert is created
-- This is a simplified version - in a real application, you would use an event queue
DELIMITER //
CREATE TRIGGER after_sos_alert_insert
AFTER INSERT ON sos_alerts
FOR EACH ROW
BEGIN
    -- Insert into a notification queue table (would need to be created)
    -- This is just a placeholder for the concept
    /*
    INSERT INTO notification_queue (type, data, priority, created_at)
    VALUES ('new_sos_alert', JSON_OBJECT(
        'alert_id', NEW.id,
        'user_id', NEW.user_id,
        'type', NEW.type,
        'lat', NEW.lat,
        'lng', NEW.lng
    ), IF(NEW.type = 'emergency', 'high', 'medium'), NOW());
    */
    
    -- In a real application, you would process this queue with a background job
    -- to notify police officers based on their location and availability
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_user_update
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_user_details_update
BEFORE UPDATE ON user_details
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_crime_report_update
BEFORE UPDATE ON crime_reports
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_sos_alert_update
BEFORE UPDATE ON sos_alerts
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_police_station_update
BEFORE UPDATE ON police_stations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_police_officer_update
BEFORE UPDATE ON police_officers
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_safety_alert_update
BEFORE UPDATE ON safety_alerts
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Trigger to automatically update the updated_at timestamp
DELIMITER //
CREATE TRIGGER before_user_alert_preferences_update
BEFORE UPDATE ON user_alert_preferences
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

