# Sorun giderme

- Hedef kitle: BT operatörleri

| Belirti | Kontrol edilecekler |
|---|---|
| Oturum açtıktan hemen sonra güvenlik sayfasına yönlendiriliyor | İki adımlı doğrulama politikası "zorunlu" ve geçiş süresi dolmuş. Kullanıcı kayıt yaptığında çözülür. Politika **Ayarlar > Güvenlik** altındadır |
| Yönetim panelinde 403 | Kullanıcının `admin_panel.access` yetkisini içeren geçerli bir rol ataması yok. **Kullanıcılar ve erişim > Kullanıcılar > Rol atamaları** bölümünü kontrol edin |
| Bir menü öğesi eksik | Rolde o ekranın yetkisi yok (örneğin `roles.view`) |
| E-posta kodları ulaşmıyor | E-posta ayarları (`MAIL_*`), güvenlik olayları arasındaki `E-posta kodu gönderildi` kayıtları, gönderim sınırları (dakikada bir, saatte beş) |
| Bir ekranda başkalarının verilerinden 0 satır görünüyor | Normal olabilir (satır düzeyi güvenlik). **Veri değişiklikleri** içindeki kullanıcı sütununa bakarak DB oturumundaki işlemi yapan kişinin ayarlandığını doğrulayın |
| `permission denied for schema public` | Uygulama `app_runtime` olarak DDL denedi. Migration'ları yalnızca `php artisan migrate` ile çalıştırın (otomatik olarak `app_migrator` kullanır) |
| `Audit records are append-only` | Bir denetim tablosunu güncelleme veya silme girişimi. Bu engelleme bilinçlidir |
| PDF üretimi başarısız | `php artisan pdf:sample`, Gotenberg durumu, `GOTENBERG_URL` |
| Kuyruk işleri takılı kaldı | `/horizon` içinde başarısız işleri kontrol edin, `php artisan horizon:status` |

İstek bazında izleme: `X-Request-Id` yanıt başlığının değeriyle günlüklerde, işlem günlüğünde, veri değişikliklerinde ve güvenlik olaylarında birlikte arama yapabilirsiniz.
