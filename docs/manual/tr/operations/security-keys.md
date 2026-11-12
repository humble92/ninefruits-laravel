# Anahtar yönetimi

- Hedef kitle: BT operatörleri

| Anahtar | Amaç | Kaybolursa | Yenileme |
|---|---|---|---|
| `APP_KEY` | Oturumlar, çerezler, iki adımlı doğrulama sırları, hatırlanan cihaz çerezlerinin imzalanması | Tüm kullanıcıların oturumu kapanır; kaydedilmiş iki adımlı doğrulama (kimlik doğrulayıcı uygulamalar) çalışmaz hale gelir | Önceki anahtarı `APP_PREVIOUS_KEYS` içine koyun, ardından yenileyin |
| `PII_ENCRYPTION_KEY` | Kimlik numarası gibi kişisel verilerin şifrelenmesi | **Şifrelenmiş kişisel veriler kurtarılamaz** | Aşağıdaki prosedür |
| `PII_HASH_KEY` | Kişisel verilerde tam eşleşmeli arama için karmalar | Arama çalışmaz hale gelir (veriler kalır) | Kural olarak yenilenmez |

Saklama ilkeleri

- Üç anahtarın en az iki kopyasını sunucunun ortam yapılandırması dışında, bir gizli bilgi deposunda (Vault, bir bulut gizli bilgi yöneticisi vb.) veya mühürlü çevrim dışı depolamada tutun.
- Anahtarları hiçbir zaman yedek dosyalarıyla birlikte saklamayın. Bir DB yedeği sızsa bile kişisel veriler anahtarlar olmadan okunamaz kalmalıdır.

## Kişisel veri anahtarının yenilenmesi prosedürü

1. `php artisan pii:generate-key` ile yeni bir anahtar oluşturun (çıktıdan yalnızca `PII_ENCRYPTION_KEY` değerini kullanın).
2. Geçerli anahtarı `PII_PREVIOUS_KEYS` içine (birden fazlaysa virgülle ayırarak), yeni anahtarı `PII_ENCRYPTION_KEY` içine koyun.
3. Yapılandırmayı yeniden yükleyin: `php artisan config:cache`, `php artisan horizon:terminate`.
4. Her kişisel veri modeli için etkilenen kayıt sayısını `php artisan pii:reencrypt {model} --dry-run` ile kontrol edin, ardından `--force` olmadan çalıştırın.
5. Horizon toplu işi tamamlanmış olarak gösterdiğinde ve `--dry-run` 0 raporladığında önceki anahtarı `PII_PREVIOUS_KEYS` içinden kaldırın.
6. Önceki anahtarı yedek saklama süresi boyunca mühürlü olarak saklayın (eski yedekleri geri yüklemek için gereklidir).
