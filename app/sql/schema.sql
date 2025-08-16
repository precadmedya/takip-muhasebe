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

CREATE TABLE service_catalog (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  period ENUM('ay','yil') NOT NULL DEFAULT 'ay',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO service_catalog (service_name, description, price, period, created_at)
VALUES ('Temel Hizmet','Varsayılan servis',0.00,'ay',NOW())
ON DUPLICATE KEY UPDATE description=VALUES(description), price=VALUES(price), period=VALUES(period);

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL,
  service_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  period ENUM('ay','yil') NOT NULL DEFAULT 'ay',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY service_name (service_name),
  CONSTRAINT fk_service_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  company VARCHAR(150) NULL,
  tax_no VARCHAR(50) NULL,
  address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cust_firm (firm_id),
  CONSTRAINT fk_cust_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  sku VARCHAR(80) NOT NULL,
  default_currency ENUM('TRY','USD','EUR','GBP') NOT NULL DEFAULT 'TRY',
  base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  vat_rate DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_product_sku_per_firm (firm_id, sku),
  INDEX idx_prod_firm (firm_id),
  CONSTRAINT fk_prod_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE extra_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_extra_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  product_id INT UNSIGNED NULL,
  service_id INT UNSIGNED NULL,
  extra_item_id INT UNSIGNED NULL,
  currency ENUM('TRY','USD','EUR','GBP') NOT NULL DEFAULT 'TRY',
  amount DECIMAL(12,2) NOT NULL,
  amount_try DECIMAL(12,2) NOT NULL,
  payment_date DATE NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  CONSTRAINT fk_pay_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  CONSTRAINT fk_pay_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
  CONSTRAINT fk_pay_extra FOREIGN KEY (extra_item_id) REFERENCES extra_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exchange_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_currency ENUM('TRY') DEFAULT 'TRY',
  usd DECIMAL(12,4) NULL,
  eur DECIMAL(12,4) NULL,
  gbp DECIMAL(12,4) NULL,
  fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL UNIQUE,
  from_name VARCHAR(150) NOT NULL,
  from_email VARCHAR(150) NOT NULL,
  smtp_host VARCHAR(150) NOT NULL,
  smtp_port INT NOT NULL,
  smtp_username VARCHAR(150) NOT NULL,
  smtp_password VARCHAR(150) NOT NULL,
  smtp_secure ENUM('tls','ssl','none') DEFAULT 'tls',
  email_logo_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_email_settings_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NULL,
  to_email VARCHAR(150) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  context ENUM('service_reminder','subscription_reminder','test') NOT NULL,
  status ENUM('success','error') NOT NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reminder_policies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firm_id INT UNSIGNED NOT NULL UNIQUE,
  service_days_before JSON DEFAULT '[30,15,7,1]',
  subscription_days_before JSON DEFAULT '[14,7,3,1]',
  CONSTRAINT fk_reminder_policy_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes & Performance
ALTER TABLE users ADD INDEX idx_users_email (email);
ALTER TABLE users ADD INDEX idx_users_firm (firm_id);
ALTER TABLE customers ADD INDEX idx_customers_firm_full (firm_id,full_name);
ALTER TABLE customers ADD INDEX idx_customers_email (email);
ALTER TABLE products ADD INDEX idx_products_active (is_active);
ALTER TABLE services ADD INDEX idx_services_firm_name (firm_id,service_name);
ALTER TABLE services ADD INDEX idx_services_period (period);
ALTER TABLE payments ADD INDEX idx_payments_firm_date (firm_id,payment_date);
ALTER TABLE payments ADD INDEX idx_payments_currency (currency);
ALTER TABLE firm_subscriptions ADD INDEX idx_fs_firm_end (firm_id,end_date);

-- Patch: Services canonicalization & payment indexes
ALTER TABLE services ADD COLUMN IF NOT EXISTS service_name VARCHAR(150) NULL AFTER firm_id;
UPDATE services SET service_name=name WHERE service_name IS NULL AND name IS NOT NULL;
ALTER TABLE services ADD COLUMN IF NOT EXISTS price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER description;
UPDATE services SET price=unit_price WHERE (price IS NULL OR price=0) AND unit_price IS NOT NULL;
ALTER TABLE services ADD COLUMN IF NOT EXISTS period ENUM('ay','yil') NOT NULL DEFAULT 'ay';
ALTER TABLE services ADD COLUMN IF NOT EXISTS firm_id INT UNSIGNED NULL;
UPDATE services SET firm_id=(SELECT MIN(id) FROM firms) WHERE firm_id IS NULL;
ALTER TABLE services MODIFY COLUMN service_name VARCHAR(150) NOT NULL;
ALTER TABLE services MODIFY COLUMN firm_id INT UNSIGNED NOT NULL;
ALTER TABLE services ADD CONSTRAINT IF NOT EXISTS fk_service_firm FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE;
ALTER TABLE services ADD UNIQUE INDEX IF NOT EXISTS service_name_unique (service_name);
ALTER TABLE services CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_pay_firm_date (firm_id,payment_date);
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_pay_currency (currency);
CREATE TABLE IF NOT EXISTS exchange_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_currency ENUM('TRY') DEFAULT 'TRY',
  usd DECIMAL(12,6) NULL,
  eur DECIMAL(12,6) NULL,
  gbp DECIMAL(12,6) NULL,
  fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
