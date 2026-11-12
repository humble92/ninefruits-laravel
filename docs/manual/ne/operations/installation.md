# स्थापना

- पाठक: IT सञ्चालक
- सम्बन्धित: [कमान्ड सन्दर्भ](commands.md), [कुञ्जी व्यवस्थापन](security-keys.md)

## 1. आवश्यकताहरू

| घटक | संस्करण | टिप्पणी |
|---|---|---|
| PHP | 8.5 | एक्सटेन्सनहरू: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist` र `pg_trgm` एक्सटेन्सनहरू (माइग्रेसनले स्थापना गर्छ), ICU collation |
| Redis | 8 | क्यास, सत्र, क्यु |
| Gotenberg | 8 | PDF निर्माण। आन्तरिक नेटवर्कमा मात्र खुला |
| मेल सर्भर | SMTP | पासवर्ड रिसेट, दुई-चरण प्रमाणीकरणका इमेल कोड |
| क्यु वर्कर | Horizon | `php artisan horizon` लाई प्रक्रिया व्यवस्थापक (systemd, supervisor) अन्तर्गत चलिरहन दिनुहोस् |
| शेड्युलर | cron | `* * * * * php artisan schedule:run` |

## 2. डेटाबेस तयारी

DB सुपरयुजरको रूपमा एक पटक चलाउनुहोस्। एप्लिकेसनले कहिल्यै सुपरयुजर प्रयोग गर्दैन।

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| भूमिका | वातावरण चरहरू | उद्देश्य |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | एप्लिकेसन चलाउने |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | माइग्रेसनका लागि मात्र |
| `app_reporting` | (BI उपकरणमा कन्फिगर गरिन्छ) | `reporting` स्किमा पढ्ने |

## 3. वातावरण कन्फिगरेसन

`.env.example` प्रतिलिपि गरी मानहरू भर्नुहोस्। अनिवार्य रूपमा परिवर्तन गर्नुपर्ने मानहरू

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`। **यी हराएमा इन्क्रिप्ट गरिएको व्यक्तिगत विवरण पुनः प्राप्त गर्न सकिँदैन।** [कुञ्जी व्यवस्थापन](security-keys.md) मा वर्णन गरेअनुसार छुट्टै भण्डारण गर्नुहोस्।
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: पहिलो स्थापनाका बेला संस्थाको पूर्वनिर्धारित समय क्षेत्र

## 4. स्थापना

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` प्रश्नहरू सोध्दै अघि बढ्छ। डिप्लोय स्क्रिप्टहरूमा यसलाई `--force` र विकल्पहरूसहित चलाउनुहोस् ([कमान्ड सन्दर्भ](commands.md#setupinstall))।

स्थापना प्रोफाइल (`--profile=`): `korean-university` — पहिलो/दोस्रो/ग्रीष्म-शीत सेमेस्टरहरू, 4.5 ग्रेड स्केल, विषयका वर्गहरू, 09:00 बाट सुरु हुने 50 मिनेटका पिरियडहरू, कोठाका प्रयोजनहरू। प्रोफाइल बिना, यी कुराहरू प्रशासन प्यानलमा प्रविष्ट गर्नुहोस्। पछि प्रोफाइल फेरि लागू गर्दा प्रशासकहरूले परिवर्तन गरेका मानहरू कायम रहन्छन्।

स्थापनापछि, व्यावसायिक प्रशासकले [प्रशासक पुस्तिका अध्याय 5, प्रारम्भिक सेटअप क्रम](../admin/05-institution-setup.md) अनुसार सन्दर्भ डेटा प्रविष्ट गर्छन्।

## 5. स्थापना प्रमाणीकरण

1. प्रारम्भिक प्रशासकको रूपमा `/login` मा साइन इन गर्नुहोस्।
2. प्रशासन प्यानलको **सेटिङ > सुरक्षा** मा दुई-चरण प्रमाणीकरण नीति सेट गर्नुहोस्।
3. प्रशासकको आफ्नै दुई-चरण प्रमाणीकरण दर्ता गर्नुहोस् (पोर्टल **सुरक्षा**)।
4. `php artisan pdf:sample` ले PDF सेवा जाँच गर्नुहोस्।
5. प्रशासन प्यानलको **अडिट > डेटा परिवर्तनहरू** मा स्थापना `system:command:setup:install` को रूपमा अभिलेख भएको पुष्टि गर्नुहोस्।

## 6. अद्यावधिक

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
