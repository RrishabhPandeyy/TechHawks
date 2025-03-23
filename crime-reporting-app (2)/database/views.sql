USE crime_reporting_system;

-- View for crime reports with user information
CREATE OR REPLACE VIEW v_crime_reports AS
SELECT 
    cr.*,
    u.name as reporter_name,
    u.phone as reporter_phone,
    u.email as reporter_email
FROM 
    crime_reports cr
JOIN 
    users u ON cr.user_id = u.id;

-- View for active SOS alerts with user information
CREATE OR REPLACE VIEW v_active_sos_alerts AS
SELECT 
    sa.*,
    u.name as user_name,
    u.phone as user_phone,
    u.email as user_email,
    ud.relative_phone,
    ud.address as user_address
FROM 
    sos_alerts sa
JOIN 
    users u ON sa.user_id = u.id
LEFT JOIN 
    user_details ud ON u.id = ud.user_id
WHERE 
    sa.status = 'active';

-- View for police officers with user and station information
CREATE OR REPLACE VIEW v_police_officers AS
SELECT 
    po.*,
    u.name as officer_name,
    u.phone as officer_phone,
    u.email as officer_email,
    ps.name as station_name,
    ps.address as station_address,
    ps.phone as station_phone,
    ps.lat as station_lat,
    ps.lng as station_lng
FROM 
    police_officers po
JOIN 
    users u ON po.user_id = u.id
JOIN 
    police_stations ps ON po.station_id = ps.id;

-- View for crime statistics by type
CREATE OR REPLACE VIEW v_crime_stats_by_type AS
SELECT 
    type,
    COUNT(*) as count,
    COUNT(*) * 100.0 / (SELECT COUNT(*) FROM crime_reports) as percentage
FROM 
    crime_reports
GROUP BY 
    type
ORDER BY 
    count DESC;

-- View for crime statistics by status
CREATE OR REPLACE VIEW v_crime_stats_by_status AS
SELECT 
    status,
    COUNT(*) as count,
    COUNT(*) * 100.0 / (SELECT COUNT(*) FROM crime_reports) as percentage
FROM 
    crime_reports
GROUP BY 
    status
ORDER BY 
    count DESC;

-- View for crime statistics by hour of day
CREATE OR REPLACE VIEW v_crime_stats_by_hour AS
SELECT 
    HOUR(created_at) as hour,
    COUNT(*) as count
FROM 
    crime_reports
GROUP BY 
    HOUR(created_at)
ORDER BY 
    hour;

-- View for crime statistics by day of week
CREATE OR REPLACE VIEW v_crime_stats_by_day AS
SELECT 
    DAYNAME(created_at) as day_name,
    DAYOFWEEK(created_at) as day_number,
    COUNT(*) as count
FROM 
    crime_reports
GROUP BY 
    day_name, day_number
ORDER BY 
    day_number;

-- View for crime statistics by month
CREATE OR REPLACE VIEW v_crime_stats_by_month AS
SELECT 
    MONTHNAME(created_at) as month_name,
    MONTH(created_at) as month_number,
    COUNT(*) as count
FROM 
    crime_reports
GROUP BY 
    month_name, month_number
ORDER BY 
    month_number;

-- View for active safety alerts
CREATE OR REPLACE VIEW v_active_safety_alerts AS
SELECT 
    sa.*,
    u.name as created_by_name
FROM 
    safety_alerts sa
JOIN 
    users u ON sa.created_by = u.id
WHERE 
    NOW() BETWEEN sa.start_date AND IFNULL(sa.end_date, DATE_ADD(NOW(), INTERVAL 1 YEAR));

-- View for user report statistics
CREATE OR REPLACE VIEW v_user_report_stats AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    COUNT(cr.id) as total_reports,
    SUM(CASE WHEN cr.status = 'reported' THEN 1 ELSE 0 END) as reported_count,
    SUM(CASE WHEN cr.status = 'investigating' THEN 1 ELSE 0 END) as investigating_count,
    SUM(CASE WHEN cr.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN cr.status = 'closed' THEN 1 ELSE 0 END) as closed_count,
    MAX(cr.created_at) as last_report_date
FROM 
    users u
LEFT JOIN 
    crime_reports cr ON u.id = cr.user_id
GROUP BY 
    u.id, u.name;

