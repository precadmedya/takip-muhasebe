-- Patch existing services table to canonical form
SET @dbname := DATABASE();
-- Rename name -> service_name
SET @needs_rename := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='services' AND COLUMN_NAME='service_name'
) = 0 AND (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='services' AND COLUMN_NAME='name'
) = 1;
SET @sql := IF(@needs_rename, 'ALTER TABLE services CHANGE name service_name VARCHAR(150) NOT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Add price column if missing
SET @has_price := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='services' AND COLUMN_NAME='price'
);
SET @sql := IF(@has_price=0, 'ALTER TABLE services ADD COLUMN price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Update price from unit_price if exists
SET @has_unit := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='services' AND COLUMN_NAME='unit_price'
);
SET @sql := IF(@has_unit=1, 'UPDATE services SET price = unit_price WHERE (price = 0 OR price IS NULL)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Add period column if missing
SET @has_period := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='services' AND COLUMN_NAME='period'
);
SET @sql := IF(@has_period=0, "ALTER TABLE services ADD COLUMN period ENUM('ay','yil') NOT NULL DEFAULT 'ay'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Add unique index on service_name if not present
SET @has_idx := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='services' AND COLUMN_NAME='service_name' AND NON_UNIQUE=0
);
SET @sql := IF(@has_idx=0, 'ALTER TABLE services ADD UNIQUE KEY uq_services_service_name (service_name)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Ensure charset
ALTER TABLE services CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
