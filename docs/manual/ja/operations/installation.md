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
