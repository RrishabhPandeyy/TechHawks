USE crime_reporting_system;

-- Stored procedure to get nearby crime reports
DELIMITER //
CREATE PROCEDURE GetNearbyCrimeReports(
    IN p_lat DECIMAL(10, 8),
    IN p_lng DECIMAL(11, 8),
    IN p_radius_km DECIMAL(5, 2),
    IN p_limit INT
)
BEGIN
    SELECT 
        cr.*,
        u.name as reporter_name,
        (
            6371 * acos(
                cos(radians(p_lat)) * 
                cos(radians(cr.lat)) * 
                cos(radians(cr.lng) - radians(p_lng)) + 
                sin(radians(p_lat)) * 
                sin(radians(cr.lat))
            )
        ) AS distance_km
    FROM 
        crime_reports cr
    JOIN 
        users u ON cr.user_id = u.id
    HAVING 
        distance_km <= p_radius_km
    ORDER BY 
        distance_km
    LIMIT p_limit;
END //
DELIMITER ;

-- Stored procedure to get nearby police stations
DELIMITER //
CREATE PROCEDURE GetNearbyPoliceStations(
    IN p_lat DECIMAL(10, 8),
    IN p_lng DECIMAL(11, 8),
    IN p_radius_km DECIMAL(5, 2),
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
    HAVING 
        distance_km <= p_radius_km
    ORDER BY 
        distance_km
    LIMIT p_limit;
END //
DELIMITER ;

-- Stored procedure to get crime statistics by type
DELIMITER //
CREATE PROCEDURE GetCrimeStatsByType(
    IN p_days INT
)
BEGIN
    SELECT 
        type,
        COUNT(*) as count
    FROM 
        crime_reports
    WHERE 
        created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    GROUP BY 
        type
    ORDER BY 
        count DESC;
END //
DELIMITER ;

-- Stored procedure to get crime statistics by status
DELIMITER //
CREATE PROCEDURE GetCrimeStatsByStatus(
    IN p_days INT
)
BEGIN
    SELECT 
        status,
        COUNT(*) as count
    FROM 
        crime_reports
    WHERE 
        created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    GROUP BY 
        status
    ORDER BY 
        count DESC;
END //
DELIMITER ;

-- Stored procedure to get crime statistics by hour of day
DELIMITER //
CREATE PROCEDURE GetCrimeStatsByHour(
    IN p_days INT
)
BEGIN
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as count
    FROM 
        crime_reports
    WHERE 
        created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    GROUP BY 
        HOUR(created_at)
    ORDER BY 
        hour;
END //
DELIMITER ;

-- Stored procedure to create a new SOS alert
DELIMITER //
CREATE PROCEDURE CreateSOSAlert(
    IN p_user_id INT,
    IN p_lat DECIMAL(10, 8),
    IN p_lng DECIMAL(11, 8),
    IN p_type ENUM('standard', 'emergency')
)
BEGIN
    INSERT INTO sos_alerts (user_id, lat, lng, type, status)
    VALUES (p_user_id, p_lat, p_lng, p_type, 'active');
    
    -- Return the created alert with user details
    SELECT 
        sa.*,
        u.name as user_name,
        u.phone as user_phone,
        ud.relative_phone
    FROM 
        sos_alerts sa
    JOIN 
        users u ON sa.user_id = u.id
    LEFT JOIN 
        user_details ud ON u.id = ud.user_id
    WHERE 
        sa.id = LAST_INSERT_ID();
END //
DELIMITER ;

-- Stored procedure to update crime report status
DELIMITER //
CREATE PROCEDURE UpdateCrimeReportStatus(
    IN p_report_id INT,
    IN p_user_id INT,
    IN p_new_status ENUM('reported', 'investigating', 'resolved', 'closed'),
    IN p_notes TEXT
)
BEGIN
    DECLARE old_status ENUM('reported', 'investigating', 'resolved', 'closed');
    
    -- Get current status
    SELECT status INTO old_status FROM crime_reports WHERE id = p_report_id;
    
    -- Update status
    UPDATE crime_reports
    SET status = p_new_status, updated_at = NOW()
    WHERE id = p_report_id;
    
    -- Add to status history
    INSERT INTO report_status_history (report_id, user_id, old_status, new_status, notes)
    VALUES (p_report_id, p_user_id, old_status, p_new_status, p_notes);
    
    -- Return updated report
    SELECT * FROM crime_reports WHERE id = p_report_id;
END //
DELIMITER ;

-- Stored procedure to get user reports with status history
DELIMITER //
CREATE PROCEDURE GetUserReportsWithHistory(
    IN p_user_id INT
)
BEGIN
    SELECT 
        cr.*,
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', rsh.id,
                    'old_status', rsh.old_status,
                    'new_status', rsh.new_status,
                    'notes', rsh.notes,
                    'created_at', rsh.created_at,
                    'user_name', u.name
                )
            )
            FROM report_status_history rsh
            JOIN users u ON rsh.user_id = u.id
            WHERE rsh.report_id = cr.id
            ORDER BY rsh.created_at DESC
        ) as status_history,
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', rc.id,
                    'comment', rc.comment,
                    'created_at', rc.created_at,
                    'user_name', u.name,
                    'is_police', u.is_police
                )
            )
            FROM report_comments rc
            JOIN users u ON rc.user_id = u.id
            WHERE rc.report_id = cr.id
            ORDER BY rc.created_at DESC
        ) as comments
    FROM 
        crime_reports cr
    WHERE 
        cr.user_id = p_user_id
    ORDER BY 
        cr.created_at DESC;
END //
DELIMITER ;

-- Stored procedure to get active safety alerts for a location
DELIMITER //
CREATE PROCEDURE GetActiveSafetyAlerts(
    IN p_lat DECIMAL(10, 8),
    IN p_lng DECIMAL(11, 8),
    IN p_radius_km DECIMAL(5, 2)
)
BEGIN
    SELECT 
        sa.*,
        u.name as created_by_name,
        (
            6371 * acos(
                cos(radians(p_lat)) * 
                cos(radians(sa.lat)) * 
                cos(radians(sa.lng) - radians(p_lng)) + 
                sin(radians(p_lat)) * 
                sin(radians(sa.lat))
            )
        ) AS distance_km
    FROM 
        safety_alerts sa
    JOIN 
        users u ON sa.created_by = u.id
    WHERE 
        NOW() BETWEEN sa.start_date AND IFNULL(sa.end_date, DATE_ADD(NOW(), INTERVAL 1 YEAR))
    HAVING 
        distance_km <= p_radius_km OR sa.radius_km >= distance_km
    ORDER BY 
        CASE sa.alert_level
            WHEN 'danger' THEN 1
            WHEN 'warning' THEN 2
            WHEN 'info' THEN 3
        END,
        distance_km;
END //
DELIMITER ;

