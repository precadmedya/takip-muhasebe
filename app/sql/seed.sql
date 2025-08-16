INSERT INTO firms (name, slug, email, status, created_at, updated_at)
VALUES ('Precad Medya','precad-medya','info@precadmedya.com.tr','active',NOW(),NOW());

INSERT INTO settings (firm_id, brand_name, logo_path, theme_primary_color, currency_method, created_at, updated_at)
VALUES (1,'Precad Medya',NULL,'#1d4ed8','latest',NOW(),NOW());

INSERT INTO users (firm_id, role, full_name, email, password_hash, created_at, updated_at)
VALUES
(NULL,'yonetici','Sistem Yöneticisi','admin@example.com','$2y$12$e.91VviNoIn0DiWXboSNPuAQkkT.17tiNM9AhHWCVX8ENtxosREEC',NOW(),NOW()),
(1,'firma','Precad Medya','info@precadmedya.com.tr','$2y$12$EF5FbbpY3cuTUkgLTwOT0.XzWl8OSQAKUZTu1qkay2ZLJgzVXXhyS',NOW(),NOW());

INSERT INTO service_catalog (service_name, description, price, period, created_at)
VALUES ('Temel Hizmet','Varsayılan servis',0.00,'ay',NOW())
ON DUPLICATE KEY UPDATE description=VALUES(description), price=VALUES(price), period=VALUES(period);

INSERT INTO services (firm_id, service_name, description, price, period, created_at)
VALUES (1,'Temel Hizmet','Varsayılan servis',0.00,'ay',NOW())
ON DUPLICATE KEY UPDATE description=VALUES(description), price=VALUES(price), period=VALUES(period);

INSERT INTO firm_subscriptions (firm_id, service_id, plan, start_date, end_date, status, auto_renew, grace_days, created_at)
VALUES (1,(SELECT id FROM services WHERE service_name='Temel Hizmet' LIMIT 1),'monthly',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 30 DAY),'active',1,7,NOW());
