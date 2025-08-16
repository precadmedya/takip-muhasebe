# Çokkiracılı Takip/Muhasebe

cPanel/Shared hosting üzerinde çalışacak basit çokkiracılı takip ve muhasebe uygulaması.

## Kurulum
1. PHP 8.1+ ve pdo_mysql, mbstring, intl, curl, json, zip, gd, fileinfo eklentilerinin aktif olduğundan emin olun.
2. MySQL'de bir veritabanı ve kullanıcı oluşturup yetkilerini verin.
3. phpMyAdmin'de hedef veritabanını seçin ve `app/sql/schema.sql` ardından `app/sql/seed.sql` dosyalarını içe aktarın.
4. `app/config/.env` dosyasını oluşturup `.env.example` içeriğine göre düzenleyin.
5. (Opsiyonel) Cron görevleri için `app/cron/*.php` dosyalarını zamanlayıcıya ekleyin.

## İlk Giriş Bilgileri
- Yönetici: `admin@example.com` / `Admin123!`
- Firma: `info@precadmedya.com.tr` / `123456`

Güvenlik sebebiyle ilk girişten sonra parolaları değiştiriniz.
