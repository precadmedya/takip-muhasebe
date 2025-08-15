# takip-muhasebe

Precad Medya Çokkiracılı, abonelik tabanlı, hizmet takip ve muhasebe sistemi.

Bu depo, sistemin ilk aşaması için temel giriş/kayıt altyapısını içerir.

## Kurulum

1. **Veritabanı oluşturma**
   ```bash
   mysql -u root -p < app/sql/schema.sql
   ```
2. **Veritabanı ayarları**
   - `app/config/config.php` dosyasındaki veritabanı bilgilerini kendi ortamınıza göre güncelleyin.

3. **PHP yerel sunucu çalıştırma**
   ```bash
   php -S localhost:8000 -t public
   ```
4. Tarayıcıda `http://localhost:8000` adresine giderek giriş/kayıt işlemlerini yapabilirsiniz.

## Gereksinimler
- PHP 8+
- MySQL 5.7+

## Dizim Yapısı
```
app/
  config/        Veritabanı bağlantısı
  includes/      Ortak header, footer, menü
  sql/           Veritabanı şeması
public/
  css/           Stil dosyaları
  login.php      Giriş sayfası
  register.php   Kayıt sayfası
  dashboard.php  Giriş sonrası panel
  logout.php     Çıkış
```

## Güvenlik Notları
- PDO ve hazırlıklı ifadeler kullanılarak SQL injection önlenir.
- Parolalar `password_hash` ve `password_verify` ile saklanır.
- Oturum açmada `session_regenerate_id` kullanılır.

Bu yapı ilerleyen aşamalarda yönetim paneli ve diğer modüllerle genişletilecektir.
