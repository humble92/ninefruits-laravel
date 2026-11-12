# 負荷テスト(履修登録のピーク)

履修登録初日の朝のように、多数の学生が同じ開講科目に同時に殺到する状況を k6 で再現します。スクリプトは `tests/load/registration.js` です。

## 準備

1. 大規模なデモデータを生成し、学生アカウントを付与します(アカウント一覧の CSV は `storage/app/private/load-accounts.csv` に書き出されます)。

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. 履修登録の業務期間が開いている必要があります(デモでは現在の学期の授業期間の履修登録期間が開きます)。
3. キューワーカー(Horizon)と Redis が動作している必要があります。席の確保とキャンセル待ちの繰り上げはキューを通じて実行されます。

## 実行

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- `--vus` を 100 から 200、500 へと上げながら、`http_req_duration` の p95 と失敗率を観察します。目標: 履修登録画面の p95 < 800ms、履修登録リクエストの p95 < 1.5s、失敗率 < 1%。
- サーバー側では、`pg_stat_activity` の待機中(ロック)のセッション数、Horizon の処理遅延、php-fpm ワーカーの飽和も観察します。
- Sail に対して実行する場合は、組み込みサーバーのワーカー数(`.env` の `PHP_CLI_SERVER_WORKERS`、既定は 4)を増やしてください。ワーカー数がスループットの上限になります。

## 設計上の保証

- 定員は DB の行ロック(`SeatAllocator::lock`)で保護されているため、同時リクエストが殺到しても開講科目が定員を超えて履修されることはありません(`ConcurrentRegistrationTest`)。
- 席の確保は Redis やキューがなくても DB だけで正しく行われますが、キャンセル待ちの繰り上げと通知はキューで処理されます。

## Octane の採否

Sail 上の単一の php-fpm で p95 が目標を超える場合は、まず php-fpm のワーカー数と PostgreSQL の `max_connections`、または PgBouncer を調整します。それでも不十分な場合は、Laravel Octane(FrankenPHP または Swoole)を検討します。Octane に切り替える前に確認すること: リクエストごとにリセットが必要な状態(`DatabaseSessionContext` のセッション変数はリクエストの終了時に `reset()` で元に戻るため、Octane でも安全です)、シングルトンに残るユーザーデータ、静的キャッシュ。判断は、実際の負荷の数値を確認したうえで Phase 13 報告書に記載します。

## 2026-10-23 の結果

開発用の Sail コンテナ(組み込みサーバー、opcache なし)、100 VU で 1 分間: ワーカー 4 で 24.7 req/s、ワーカー 16 で 34.5 req/s、HTTP の失敗 0、定員超過 0、PostgreSQL の接続数 4。リクエストあたりのサーバー処理時間はページが約 120 ms、履修登録が約 190 ms で、100 VU での数秒の遅延はワーカーの待ち行列によるものです。スループットは PHP のワーカー数に比例して伸び、データベースは限界には程遠いため、**Octane は採用しません**: php-fpm のワーカー数とアプリケーションサーバーの水平スケーリングで十分です。本稼働の前に、php-fpm + opcache のステージング環境で 200 VU と 500 VU での実行を繰り返してください。詳細な数値: [Phase 13 報告書](../../../../porting/phase-13-report.md) 7 節。
