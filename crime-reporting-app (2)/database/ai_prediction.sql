-- Create tables for AI prediction functionality

-- Crime prediction table
CREATE TABLE IF NOT EXISTS crime_predictions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prediction_date DATE NOT NULL,
  district VARCHAR(100) NOT NULL,
  crime_type VARCHAR(50) NOT NULL,
  predicted_count INT NOT NULL,
  confidence_level DECIMAL(5, 2) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hotspot prediction table
CREATE TABLE IF NOT EXISTS hotspot_predictions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  district VARCHAR(100) NOT NULL,
  lat DECIMAL(10, 8) NOT NULL,
  lng DECIMAL(11, 8) NOT NULL,
  radius_km DECIMAL(5, 2) NOT NULL,
  risk_level ENUM('low', 'medium', 'high') NOT NULL,
  predicted_crime_types TEXT NOT NULL,
  prediction_start_date DATE NOT NULL,
  prediction_end_date DATE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_crime_predictions_date ON crime_predictions(prediction_date);
CREATE INDEX idx_crime_predictions_district ON crime_predictions(district);
CREATE INDEX idx_hotspot_predictions_dates ON hotspot_predictions(prediction_start_date, prediction_end_date);
CREATE INDEX idx_hotspot_predictions_district ON hotspot_predictions(district);
CREATE INDEX idx_hotspot_predictions_risk ON hotspot_predictions(risk_level);

-- Create stored procedure to generate crime predictions
-- This is a simplified version for demo purposes
-- In a real app, this would use ML algorithms
DELIMITER //
CREATE PROCEDURE GenerateCrimePredictions()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE district_name VARCHAR(100);
    DECLARE crime_type_name VARCHAR(50);
    
    -- Get distinct districts
    DECLARE district_cursor CURSOR FOR 
        SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1) as district
        FROM crime_reports
        WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1) != '';
    
    -- Get distinct crime types
    DECLARE crime_type_cursor CURSOR FOR 
        SELECT DISTINCT type FROM crime_reports;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Clear existing predictions
    DELETE FROM crime_predictions 
    WHERE prediction_date >= CURDATE() 
    AND prediction_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY);
    
    -- Generate predictions for each district and crime type
    OPEN district_cursor;
    district_loop: LOOP
        FETCH district_cursor INTO district_name;
        IF done THEN
            LEAVE district_loop;
        END IF;
        
        SET done = FALSE;
        OPEN crime_type_cursor;
        crime_type_loop: LOOP
            FETCH crime_type_cursor INTO crime_type_name;
            IF done THEN
                CLOSE crime_type_cursor;
                SET done = FALSE;
                ITERATE district_loop;
            END IF;
            
            -- Calculate average daily crimes for this district and type
            -- over the last 90 days
            SET @avg_daily_count = (
                SELECT COUNT(*) / 90
                FROM crime_reports
                WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1) = district_name
                AND type = crime_type_name
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            );
            
            -- Generate predictions for next 30 days
            -- with some random variation
            SET @day_counter = 0;
            WHILE @day_counter < 30 DO
                SET @prediction_date = DATE_ADD(CURDATE(), INTERVAL @day_counter DAY);
                SET @random_factor = 0.8 + (RAND() * 0.4); -- Random factor between 0.8 and 1.2
                SET @predicted_count = CEIL(@avg_daily_count * @random_factor);
                SET @confidence = 70 + (RAND() * 20); -- Random confidence between 70% and 90%
                
                -- Insert prediction
                INSERT INTO crime_predictions (
                    prediction_date, 
                    district, 
                    crime_type, 
                    predicted_count, 
                    confidence_level
                ) VALUES (
                    @prediction_date,
                    district_name,
                    crime_type_name,
                    @predicted_count,
                    @confidence
                );
                
                SET @day_counter = @day_counter + 1;
            END WHILE;
        END LOOP crime_type_loop;
    END LOOP district_loop;
    CLOSE district_cursor;
    
    -- Generate hotspot predictions
    DELETE FROM hotspot_predictions 
    WHERE prediction_end_date >= CURDATE();
    
    -- Find districts with highest crime rates
    INSERT INTO hotspot_predictions (
        district,
        lat,
        lng,
        radius_km,
        risk_level,
        predicted_crime_types,
        prediction_start_date,
        prediction_end_date
    )
    SELECT 
        district,
        -- In a real app, you would have actual coordinates
        -- For demo, we'll use random coordinates
        20.5937 + (RAND() * 2 - 1) as lat,
        78.9629 + (RAND() * 2 - 1) as lng,
        1 + (RAND() * 4) as radius_km,
        CASE 
            WHEN crime_count > 20 THEN 'high'
            WHEN crime_count > 10 THEN 'medium'
            ELSE 'low'
        END as risk_level,
        GROUP_CONCAT(DISTINCT crime_type ORDER BY type_count DESC SEPARATOR ',') as predicted_crime_types,
        CURDATE() as prediction_start_date,
        DATE_ADD(CURDATE(), INTERVAL 7 DAY) as prediction_end_date
    FROM (
        -- Get crime counts by district
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1) as district,
            COUNT(*) as crime_count
        FROM crime_reports
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY district
        HAVING district != ''
        ORDER BY crime_count DESC
        LIMIT 10
    ) district_counts
    JOIN (
        -- Get top crime types for each district
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1) as district,
            type as crime_type,
            COUNT(*) as type_count
        FROM crime_reports
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY district, type
        HAVING district != ''
    ) district_types ON district_counts.district = district_types.district
    GROUP BY district_counts.district, crime_count;
END //
DELIMITER ;

-- Create event to run prediction generation daily
CREATE EVENT IF NOT EXISTS daily_crime_prediction
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL GenerateCrimePredictions();

-- Insert sample data
CALL GenerateCrimePredictions();

