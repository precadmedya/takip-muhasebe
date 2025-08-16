# Çokkiracılı Takip/Muhasebe

cPanel/Shared hosting üzerinde çalışacak basit çokkiracılı takip ve muhasebe uygulaması.

## Kurulum
1. PHP 8.1+ ve pdo_mysql, mbstring, intl, curl, json, zip, gd, fileinfo eklentilerinin aktif olduğundan emin olun.
2. MySQL'de bir veritabanı ve kullanıcı oluşturup yetkilerini verin.
3. phpMyAdmin'de hedef veritabanını seçin ve `app/sql/schema_base.sql` ardından `app/sql/seed.sql` dosyalarını içe aktarın. Mevcut tabloları güncellemek için `tools/repair.php?token=TOKEN` çalıştırılabilir.
4. `app/config/.env` dosyasını oluşturup `.env.example` içeriğine göre düzenleyin.
5. (Opsiyonel) Cron görevleri için `app/cron/*.php` dosyalarını zamanlayıcıya ekleyin.

## İlk Giriş Bilgileri
- Yönetici: `admin@example.com` / `Admin123!`
- Firma: `info@precadmedya.com.tr` / `123456`

Güvenlik sebebiyle ilk girişten sonra parolaları değiştiriniz.

## 500 Hatasını Teşhis Etme
1. `.env` içinde `APP_DEBUG=1` ayarlayın.
2. Tarayıcıdan `/_debug/diagnostics.php?token=TOKEN` adresine gidin.
3. `pdo_mysql`, DSN ve tablo listesini kontrol edin.
4. Gerekirse `tools/repair.php?token=TOKEN` ile şemayı düzeltin.
5. İşiniz bitince `APP_DEBUG=0` yapmayı unutmayın.
