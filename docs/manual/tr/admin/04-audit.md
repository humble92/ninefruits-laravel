# 4. Denetim

**Denetim** menüsü altındaki üç ekran salt okunurdur; hiç kimse bu kayıtları düzenleyemez veya silemez (`Denetim kayıtlarını görüntüleme` ve `Güvenlik olaylarını görüntüleme` yetkileri gerekir).

| Ekran | Gösterdiği bilgi | Örnekler |
|---|---|---|
| İşlem günlüğü | Kimin ne yaptığı ve **neden** yaptığı. Gerekçe ve ayrıntılar | Rol ataması (gerekçe: dönem boyunca vekâlet), yetki değişikliği (eklenen/kaldırılan listesi), ayar değişikliği (öncesi/sonrası), komut çalıştırma |
| Veri değişiklikleri | Tablo satırlarının önceki ve sonraki değerleri. Uygulamayı atlayan doğrudan değişiklikler de kaydedilir | `users` UPDATE, değişen sütun `locale` |
| Güvenlik olayları | Oturum açmalar, başarısız oturum açmalar, iki adımlı doğrulama, yetkiyle ilgili olaylar, parola ve e-posta değişiklikleri, denetim kayıtlarının budanması (`audit:prune`) | Oturum açma başarısız (oturum açma adı, IP) |

## Nasıl okunur

- **Kullanıcı** sütunu: ekranda yapılan işlemlerde kullanıcı adı veya `user:number`, otomatik işlerde `system:job:...`, komutlarda `system:command:...` gösterilir.
- Parolalar, iki adımlı doğrulama gizli anahtarları ve benzeri değerler `[redacted]` olarak maskelenir.
- Aynı **İstek kimliği** değerine sahip kayıtlar tek bir ekran isteğiyle oluşturulmuştur. Aynı istek kimliğini ekranlarda aramak, tek bir işlemin tüm izini gösterir.

## Sık yapılan sorgulamalar

| Öğrenmek istediğiniz | Yöntem |
|---|---|
| Bir kullanıcının yetkilerinin ne zaman değiştiği | İşlem günlüğünde günlük `security`, olaylar `role.assigned`, `role.assignment_revoked` |
| Çok sayıda başarısız oturum açma denemesi olan bir hesap | Güvenlik olaylarında `Oturum açma başarısız` olayına göre filtreleyin, oturum açma adı sütununa bakın |
| Güvenlik politikasını kimin değiştirdiği | İşlem günlüğünde `settings.updated` olayı |
| Belirli bir satırın nasıl değiştiği | Veri değişikliklerinde tabloya göre filtreleyin ve kayıt kimliğiyle arayın |
