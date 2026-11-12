# डिप्लोय जाँचसूची र PHP सुरक्षा सेटिङ

## PHP संस्करण

- **PHP ≥ 8.5.12** (वा 8.4.27, 8.3.36) प्रयोग गर्नुहोस्। 2026-10-21 को सुरक्षा रिलिजले प्रमाणीकरणबिनै रिमोट कोड चलाउन सकिने दुई कमजोरीहरू (CVE-2026-109618 OOM अवस्था भ्रष्टता, CVE-2026-109619 अनुरोधहरूबीच अवस्था चुहावट) सच्यायो, र php.net ले "पूर्ण न्यूनीकरण छैन; अद्यावधिक गर्नु नै एक मात्र सुरक्षित विकल्प हो" भनी उल्लेख गर्‍यो। `composer.json` मा रहेको `"php": "^8.5.12"` सर्तले यसभन्दा तल्लो PHP मा स्थापना हुन दिँदैन।
- विकास वातावरण: `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` चलाउनुहोस्, त्यसपछि `./vendor/bin/sail php -v` ले पुष्टि गर्नुहोस्।
- कन्टेनर वा सर्भर इमेज बनाउँदा PHP प्याकेजलाई निश्चित संस्करणमा नबाँध्नुहोस्; सबैभन्दा नयाँ प्याच रिलिज लिनुहोस्। `system:health-check` ले पनि PHP संस्करण देखाउँछ।

## उत्पादन php.ini (php-fpm पुल वा सर्भर कन्फिगरेसन, `.user.ini` वा `ini_set()` होइन)

| सेटिङ | मान | कारण |
|---|---|---|
| `session.upload_progress.enabled` | `0` | CVE-2026-109619 र CVE-2026-115560 (2026-10-21 र 23 का php.net सूचनाहरू) का मार्गहरू रोक्छ। यो प्रणालीले अपलोड प्रगति प्रयोग गर्दैन |
| `fatal_error_backtraces` | `0` (PHP 8.5) | सोही सूचनामा उल्लेखित घातक-त्रुटि ब्याकट्रेस मार्ग रोक्छ |
| `memory_limit` | `256M` वा बढी, `php_admin_value` ले निश्चित गरिएको | एप्लिकेसन कोडले परिवर्तन गर्न नसकोस् भनेर |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | भर्ना कागजात, परीक्षाका प्रमाण र फोटो अपलोडको माथिल्लो सीमा (एप्लिकेसनको 5MB र 2MB प्रमाणीकरणभन्दा माथि केही गुन्जायससहित) |
| `expose_php` | `Off` | संस्करण नदेखाउने |
| `display_errors` | `Off`, `log_errors=On` | उत्पादन |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Laravel सत्र सेटिङ (`SESSION_SECURE_COOKIE=true`) सँगै |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (डिप्लोयमा पुनः सुरु) | कार्यसम्पादन |

## एप्लिकेसन वातावरण (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` लाई वास्तविक ठेगानामा सेट गर्नुहोस्।
- `APP_KEY`, `PII_ENCRYPTION_KEY` र `PII_HASH_KEY` गोप्य भण्डारबाट इन्जेक्ट गर्नुहोस् र तिनको ब्याकअप राख्नुहोस् ([कुञ्जी व्यवस्थापन](security-keys.md))। कुञ्जीहरू हराएमा व्यक्तिगत विवरण डिक्रिप्ट गर्न सकिँदैन।
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (माइग्रेसनका लागि मात्र प्रयोग हुने पासवर्ड), `app_reporting` BI उपकरणका लागि मात्र।
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`। Horizon (`php artisan horizon`) र शेड्युलर (`* * * * * php artisan schedule:run`) लाई सेवाको रूपमा दर्ता गर्नुहोस्।
- `GOTENBERG_URL`: आन्तरिक नेटवर्क ठेगाना। Gotenberg कहिल्यै बाहिर खुला गरिँदैन।
- मेल (`MAIL_*`): निमन्त्रणा, दुई-चरण प्रमाणीकरण इमेल, र विषय दर्ता तथा ग्रेड सूचनाका लागि प्रयोग हुन्छ।

## डिप्लोय प्रक्रिया

1. ब्याकअप लिनुहोस् ([ब्याकअप र पुनर्स्थापना](backup-restore.md))।
2. कोड डिप्लोय गर्नुहोस्, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`।
3. `php artisan migrate --force` (स्वतः माइग्रेटर जडानमा सर्छ), `php artisan security:sync-permissions`, `php artisan setup:install --force` (आवश्यक डेटा ताजा गर्छ, प्रशासकले गरेका सम्पादन राख्छ)।
4. `php artisan optimize`, Horizon पुनः सुरु (`horizon:terminate`), php-fpm पुनः सुरु (नयाँ php.ini लागू गर्छ)।
5. `php artisan system:health-check` ले प्रमाणित गर्नुहोस्। `audit:verify-chain` ले अडिट चेन प्रमाणित गर्नुहोस्।

## नियमित जाँच

| आवृत्ति | कार्य |
|---|---|
| दैनिक (स्वचालित) | `audit:seal`, `lms:reconcile`, आरक्षित दर्ता सिटहरूको खुलाइ, प्रतीक्षा सूचीबाट बढुवा, आवेदन मस्यौदाको म्याद समाप्ति |
| साप्ताहिक | `system:health-check`, PHP र OS सुरक्षा अद्यावधिकको जाँच, Horizon मा विफल कार्यहरूको जाँच |
| मासिक | `media:prune-orphans --dry-run`, ब्याकअप पुनर्स्थापना अभ्यास, `audit:verify-chain` |
| प्रत्येक सेमेस्टर | भूमिका तोकाइको समीक्षा (छोडेका वा सरुवा भएका व्यक्तिहरू), अवधारण अवधि नाघेका अडिट अभिलेखको छँटाइ (`audit:prune`) |
