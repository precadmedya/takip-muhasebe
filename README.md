# takip-muhasebe

Precad Medya Çokkiracılı, abonelik tabanlı, hizmet takip ve muhasebe sistemi.

Bu depo, sistemin ilk aşaması için rol tabanlı giriş/kayıt altyapısını içerir. Kayıt olan kullanıcılar "firma" rolünde oluşturulur ve varsayılan hizmet için abonelik başlatılır. Yönetici (admin) kullanıcıları veritabanından manuel ekleyebilirsiniz.

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
4. Tarayıcıda `http://localhost:8000` adresine giderek giriş/kayıt işlemlerini yapabilirsiniz. Giriş sonrası rolünüze göre ilgili panele yönlendirilirsiniz.

## Gereksinimler
- PHP 8+
- MySQL 5.7+

## Dizim Yapısı
```
app/
  config/        Veritabanı bağlantısı
  includes/      Ortak header, footer, menü
  sql/           Veritabanı şeması
admin/            Yönetici paneli
firma/            Firma paneli
public/
  css/           Stil dosyaları
  login.php      Giriş sayfası
  register.php   Kayıt sayfası
  dashboard.php  Rol bazlı yönlendirme
  logout.php     Çıkış
```

## Güvenlik Notları
- PDO ve hazırlıklı ifadeler kullanılarak SQL injection önlenir.
- Parolalar `password_hash` ve `password_verify` ile saklanır.
- Oturum açmada `session_regenerate_id` kullanılır.

Bu yapı ilerleyen aşamalarda yönetim paneli ve diğer modüllerle genişletilecektir.
