# デプロイチェックリストと PHP のセキュリティ設定

## PHP のバージョン

- **PHP ≥ 8.5.12**(または 8.4.27、8.3.36)を使用してください。2026-10-21 のセキュリティリリースでは、認証不要のリモートコード実行の脆弱性 2 件(CVE-2026-109618 OOM による状態破壊、CVE-2026-109619 リクエスト間の状態漏えい)が修正され、php.net は「完全な緩和策はなく、更新が唯一の安全な選択肢である」と述べています。`composer.json` の `"php": "^8.5.12"` という制約により、これより低い PHP にはインストールできません。
- 開発環境: `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` を実行してから、`./vendor/bin/sail php -v` で確認します。
- コンテナやサーバーのイメージをビルドするときは、PHP パッケージのバージョンを固定せず、最新のパッチリリースを使用してください。`system:health-check` でも PHP のバージョンが表示されます。

## 本番の php.ini(`.user.ini` や `ini_set()` ではなく、php-fpm のプールまたはサーバーの設定)

| 設定 | 値 | 理由 |
|---|---|---|
| `session.upload_progress.enabled` | `0` | CVE-2026-109619 と CVE-2026-115560 の経路を遮断します(php.net の 2026-10-21 および 23 日のアドバイザリ)。このシステムはアップロード進捗を使用しません |
| `fatal_error_backtraces` | `0`(PHP 8.5) | 同じアドバイザリにある致命的エラーのバックトレースの経路を遮断します |
| `memory_limit` | `256M` 以上、`php_admin_value` で固定 | アプリケーションのコードから変更できないようにするため |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | 出願書類、試験の証拠資料、写真のアップロードの上限(アプリケーションの 5MB と 2MB の検証に対して余裕を持たせた値) |
| `expose_php` | `Off` | バージョンを公開しない |
| `display_errors` | `Off`、`log_errors=On` | 本番環境 |
| `session.cookie_secure`、`session.cookie_httponly`、`session.cookie_samesite` | `1`、`1`、`Lax` | Laravel のセッション設定(`SESSION_SECURE_COOKIE=true`)と併せて |
| `opcache.enable`、`opcache.validate_timestamps` | `1`、`0`(デプロイ時に再起動) | 性能 |

## アプリケーションの環境(.env)

- `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL` は実際のアドレスに設定します。
- `APP_KEY`、`PII_ENCRYPTION_KEY`、`PII_HASH_KEY` はシークレットストアから注入し、バックアップしておきます([キー管理](security-keys.md))。キーを失うと、個人情報を復号できなくなります。
- DB: `DB_USERNAME=app_runtime`、`DB_MIGRATOR_USERNAME=app_migrator`(マイグレーション専用のパスワード)、`app_reporting` は BI ツール専用です。
- `QUEUE_CONNECTION=redis`、`CACHE_STORE=redis`、`SESSION_DRIVER=redis`。Horizon(`php artisan horizon`)とスケジューラー(`* * * * * php artisan schedule:run`)をサービスとして登録します。
- `GOTENBERG_URL`: 内部ネットワークのアドレス。Gotenberg は決して外部に公開しません。
- メール(`MAIL_*`): 招待、二要素認証のメール、履修登録と成績の通知に使用します。

## デプロイ手順

1. バックアップを取ります([バックアップと復元](backup-restore.md))。
2. コードをデプロイし、`composer install --no-dev --optimize-autoloader`、`npm ci && npm run build` を実行します。
3. `php artisan migrate --force`(自動的に migrator の接続に切り替わります)、`php artisan security:sync-permissions`、`php artisan setup:install --force`(必須データを更新し、管理者による変更は保持します)。
4. `php artisan optimize`、Horizon の再起動(`horizon:terminate`)、php-fpm の再起動(新しい php.ini を適用)。
5. `php artisan system:health-check` で確認します。`audit:verify-chain` で監査チェーンを確認します。

## 定期チェック

| 頻度 | 作業 |
|---|---|
| 毎日(自動) | `audit:seal`、`lms:reconcile`、履修登録の確保枠の開放、キャンセル待ちの繰り上げ、出願の下書きの失効 |
| 毎週 | `system:health-check`、PHP と OS のセキュリティ更新の確認、Horizon の失敗ジョブの確認 |
| 毎月 | `media:prune-orphans --dry-run`、バックアップの復元訓練、`audit:verify-chain` |
| 学期ごと | ロール割り当ての見直し(退職者や異動者)、保存期間を過ぎた監査記録の削除(`audit:prune`) |
