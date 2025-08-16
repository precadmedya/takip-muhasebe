-- Schema without CREATE DATABASE/USE
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE firms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  status ENUM('active','suspended','cancelled') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NULL,
  role ENUM('yonetici','firma') DEFAULT 'firma',
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  failed_attempts TINYINT UNSIGNED DEFAULT 0,
  last_login_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL UNIQUE,
  brand_name VARCHAR(255) DEFAULT NULL,
  logo_path VARCHAR(255) DEFAULT NULL,
  theme_primary_color VARCHAR(7) DEFAULT '#1d4ed8',
  currency_method ENUM('transaction_date','latest') DEFAULT 'latest',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_settings_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(12,2) DEFAULT 0.00,
  period ENUM('ay','yil') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO services (service_name, description, price, period, created_at)
VALUES ('Temel Hizmet','Varsayılan servis',0.00,'ay',NOW())
ON DUPLICATE KEY UPDATE description=VALUES(description), price=VALUES(price), period=VALUES(period);

CREATE TABLE firm_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  plan ENUM('monthly','yearly') NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('active','expired','suspended') DEFAULT 'active',
  auto_renew TINYINT(1) DEFAULT 0,
  grace_days INT UNSIGNED DEFAULT 7,
  last_payment_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_firm_end (firm_id, end_date),
  CONSTRAINT fk_subscription_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscription_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  entity_type VARCHAR(64) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  action ENUM('create','update','delete','login','logout') NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_firm (firm_id),
  CONSTRAINT fk_audit_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE SET NULL,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
