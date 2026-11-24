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

## 7. वेब सर्भर (Nginx)

php-fpm अगाडि Nginx राख्नुहोस्। तलको उदाहरण मात्र हो: ठेगाना, पथ, प्रमाणपत्र र php-fpm सकेट आफ्नो सर्भर अनुसार बदल्नुहोस्।

```nginx
log_format sis '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent '
               '"$http_referer" "$http_user_agent" rt=$request_time rid=$request_id';

server {
    listen 443 ssl;
    http2 on;
    server_name sis.example.edu;
    root /var/www/sis/public;
    index index.php;

    ssl_certificate     /etc/ssl/sis/fullchain.pem;
    ssl_certificate_key /etc/ssl/sis/privkey.pem;

    access_log /var/log/nginx/sis.access.log sis;
    client_max_body_size 32m;     # same as post_max_size in php.ini
    server_tokens off;

    # Only when PHP does not answer. The application's own error pages pass through.
    error_page 502 503 504 /errors/unavailable.html;
    location = /errors/unavailable.html {
        internal;
        ssi on;                    # puts $request_id on the page
        add_header Cache-Control "no-store" always;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_X_REQUEST_ID $request_id;   # replaces a client-sent X-Request-Id
        internal;
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

- **त्रुटि पृष्ठ:** `public/errors/unavailable.html` PHP ले जवाफ नदिँदा (502, 503, 504) Nginx ले देखाउने स्थिर पृष्ठ हो। यसलाई build फाइल वा डाटाबेस चाहिँदैन, र यसको पाठ ब्राउजरको भाषाअनुसार बदलिन्छ (समर्थन नभएका भाषामा अंग्रेजी)। `fastcgi_intercept_errors` पूर्वनिर्धारित (off) मै रहने भएकाले एप आफैंले पठाएका त्रुटि पृष्ठहरू (500, `php artisan down` बेलाको 503 आदि) जस्ताको तस्तै पुग्छन्।
- **सन्दर्भ नम्बर:** `ssi on` ले Nginx को `$request_id` पृष्ठमा राख्छ। उही मान `X-Request-Id` का रूपमा एपमा पठाइन्छ, त्यसैले Nginx access log (`rid=`), एपका लग र अडिट रेकर्ड एउटै नम्बरले खोज्न सकिन्छ ([समस्या समाधान](troubleshooting.md))। क्लाइन्टले पठाएको `X-Request-Id` यही मानले प्रतिस्थापित हुन्छ।
- **Nginx ले छुट्टै एप सर्भरमा proxy गर्दा:** `proxy_set_header X-Request-Id $request_id;` प्रयोग गर्नुहोस् र `proxy_intercept_errors` पूर्वनिर्धारित (off) मै छोड्नुहोस्। `error_page` र यसको `location` जस्ताको तस्तै राख्नुहोस्।
- **पाठ बदल्दा:** `lang/*/errors.php` मा `unavailable` र `reference` सम्पादन गरे यस फाइलभित्रको JSON पनि सँगै सम्पादन गर्नुहोस्। दुई मेल नखाएमा परीक्षण (`ErrorPagesTest`) असफल हुन्छ।
- **Nginx संस्करण:** `http2 on;` का लागि Nginx 1.25.1 वा पछिको संस्करण चाहिन्छ। पुराना संस्करणमा (जस्तै Ubuntu 22.04 को 1.18) यो लाइन हटाएर `listen 443 ssl http2;` लेख्नुहोस्।
