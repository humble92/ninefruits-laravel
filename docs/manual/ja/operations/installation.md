# インストール

- 対象読者: IT 運用担当者
- 関連: [コマンドリファレンス](commands.md)、[キー管理](security-keys.md)

## 1. 要件

| コンポーネント | バージョン | 備考 |
|---|---|---|
| PHP | 8.5 | 拡張: `pdo_pgsql`、`pgsql`、`intl`、`redis`、`bcmath`、`zip`、`gd`、`sodium` |
| PostgreSQL | 18 | `btree_gist` と `pg_trgm` 拡張(マイグレーションでインストールされます)、ICU 照合順序 |
| Redis | 8 | キャッシュ、セッション、キュー |
| Gotenberg | 8 | PDF 生成。内部ネットワークにのみ公開します |
| メールサーバー | SMTP | パスワードのリセット、二要素認証のメールのコード |
| キューワーカー | Horizon | `php artisan horizon` をプロセスマネージャー(systemd、supervisor)で常時実行します |
| スケジューラー | cron | `* * * * * php artisan schedule:run` |

## 2. データベースの準備

DB のスーパーユーザーで 1 回だけ実行します。アプリケーションがスーパーユーザーを使用することはありません。

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| ロール | 環境変数 | 用途 |
|---|---|---|
| `app_runtime` | `DB_USERNAME`、`DB_PASSWORD` | アプリケーションの実行 |
| `app_migrator` | `DB_MIGRATOR_USERNAME`、`DB_MIGRATOR_PASSWORD` | マイグレーション専用 |
| `app_reporting` | (BI ツールで設定) | `reporting` スキーマの読み取り |

## 3. 環境設定

`.env.example` をコピーして値を入力します。変更が必要な値

- `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`、`PII_HASH_KEY`: `php artisan pii:generate-key`。**これらを失うと、暗号化された個人情報は復元できません。** [キー管理](security-keys.md) の説明に従って別途保管してください。
- `DB_*`、`REDIS_*`、`MAIL_*`、`GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: 初回インストール時の機関の既定のタイムゾーン

## 4. インストール

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` は質問に答えながら進みます。デプロイスクリプトでは `--force` とオプションを付けて実行します([コマンドリファレンス](commands.md#setupinstall))。

インストールプロファイル(`--profile=`): `korean-university` — 1 学期/2 学期/夏季・冬季学期、4.5 の成績評価基準、科目区分、09:00 開始の 50 分単位の時限、教室の用途。プロファイルを使わない場合は、これらを管理画面で入力します。後でプロファイルを再適用しても、管理者が変更した値は保持されます。

インストール後、業務管理者は [管理者マニュアル 第 5 章 初期設定の順序](../admin/05-institution-setup.md) に従って基準データを入力します。

## 5. インストールの確認

1. 初期管理者で `/login` からログインします。
2. 管理画面の **設定 > セキュリティ** で二要素認証のポリシーを設定します。
3. 管理者自身の二要素認証を登録します(ポータルの **セキュリティ**)。
4. `php artisan pdf:sample` で PDF サービスを確認します。
5. 管理画面の **監査 > データ変更履歴** で、インストールが `system:command:setup:install` として記録されていることを確認します。

## 6. 更新

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```

## 7. Web サーバー（Nginx）

php-fpm の前に Nginx を置きます。以下は例です。アドレス、パス、証明書、php-fpm ソケットはサーバーに合わせて変更してください。

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

- **エラーページ:** `public/errors/unavailable.html` は、PHP が応答しないとき（502、503、504）に Nginx が表示する静的ページです。ビルドファイルやデータベースなしで表示され、文言はブラウザの言語に合わせて切り替わります（未対応の言語は英語）。`fastcgi_intercept_errors` が既定値（off）のままなので、アプリ自身が返すエラー画面（500、`php artisan down` 中の 503 など）はそのまま届きます。
- **参照番号:** `ssi on` が Nginx の `$request_id` をページに埋め込みます。同じ値を `X-Request-Id` としてアプリに渡すため、Nginx のアクセスログ（`rid=`）、アプリのログ、監査記録を一つの番号で検索できます（[トラブルシューティング](troubleshooting.md)）。クライアントが送った `X-Request-Id` はこの値に置き換えられます。
- **Nginx が別のアプリサーバーへプロキシする場合:** `proxy_set_header X-Request-Id $request_id;` を使い、`proxy_intercept_errors` は既定値（off）のままにします。`error_page` とその `location` はそのまま使います。
- **文言を変えるとき:** `lang/*/errors.php` の `unavailable` と `reference` を直したら、このファイル内の JSON も一緒に直します。両者がずれるとテスト（`ErrorPagesTest`）が失敗します。
