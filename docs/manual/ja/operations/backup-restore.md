# バックアップと復元

- 対象読者: IT 運用担当者

## バックアップ対象

| 対象 | 方法 | 頻度(推奨) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` または PITR(WAL アーカイブ) | 毎日のフルバックアップ + 継続的な WAL |
| ファイルストレージ(`storage/app/private` または S3) | スナップショットまたはバージョニング付きバケット | DB と同じ |
| 環境設定とキー | シークレットストア | 変更のたび |

DB とファイルストレージは同じ時点に復元できる必要があります(写真や証明書 PDF のメタデータは DB にあります)。

## DB バックアップの例

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` はすべてのスキーマ(`public`、`audit`、`reporting`、`util`)を所有しているため、このアカウントでバックアップします。ロール(`app_*`)はクラスターのオブジェクトであり、ダンプには含まれません。復元先のサーバーでは、[インストール](installation.md) の 2 節の説明に従って、先にロールを作成してください。

## 復元の例

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## 復元訓練

四半期ごとに別のサーバーへ復元し、次の点を確認します: ログイン、個人情報の復号(キーが一致すること)、監査記録の閲覧、ファイル(写真、PDF)を開けること。
