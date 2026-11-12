# スケジュールジョブとキュー

- 対象読者: IT 運用担当者

## 常駐プロセス

| プロセス | コマンド | 確認 |
|---|---|---|
| キューワーカー | `php artisan horizon` | `/horizon`(権限 `queue_monitor.view`) |
| スケジューラー | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## スケジュールジョブ

各フェーズでこの表に追加していきます。登録は `routes/console.php` にあります。時刻はサーバーのタイムゾーン(UTC)です。

| ジョブ | 頻度 | フェーズ | 失敗の検知 |
|---|---|---|---|
| `admission:expire-drafts` | 毎日 03:10 | 3 | 実行のたびに業務ログ(ログ `command`)に記録されます。1 日以上記録がない場合はスケジューラーを確認してください |
| `registration:release-reserved-seats` | 5 分ごと | 5 | 監査記録なし。開放日時を過ぎた確保枠の `release_processed_at` が空のままの場合は、スケジューラーを確認してください |
| `waitlist:promote` | 毎時 | 5 | 実行のたびに業務ログ(ログ `command`)に記録されます |

## キュージョブ

| ジョブ | 契機 | 備考 |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | バッチ。失敗したジョブは Horizon から再試行します |
| `PromoteWaitlistJob` | 履修の削除または履修中止、確保枠の開放 | 開講科目ごとに 1 つだけ実行されます(重複は防止)。キャンセル待ちの繰り上げではすべてのルールを再チェックします |
| `CloseTermRecordsJob`、`FinishTermCloseJob` | `term:close`、管理画面での成績締め | 学生 100 名単位のバッチで、その後に仕上げのジョブ(成績優秀者の順位付け、学期の状態変更)が続きます。進行状況は Horizon のバッチ `term-close:{学期コード}` で確認します。失敗したジョブは Horizon から再試行します(学生ごとの計算は再実行しても同じ結果になります) |
| `ImportCsv`(Filament) | 管理画面での CSV 取り込み、`reference:import` | バッチ。完了は管理画面の通知(`notifications` テーブル)で知らされます。失敗した行は `failed_import_rows` に入ります |
| `audit:seal` | 毎日 03:30 | 監査ハッシュチェーンを封印します([監査の完全性](audit-and-maintenance.md)) |
| スケジューラーのハートビート | 毎分 | 最後の実行時刻をキャッシュに書き込みます。`system:health-check` は 5 分を超えて書き込みがない場合を失敗とみなします |

キュージョブの実行中、DB の実行者は `system:job:{class}` として記録されます。
