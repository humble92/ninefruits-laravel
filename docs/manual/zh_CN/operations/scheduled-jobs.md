# 定时任务与队列

- 对象：IT 运维人员

## 常驻进程

| 进程 | 命令 | 确认方式 |
|---|---|---|
| 队列工作进程 | `php artisan horizon` | `/horizon`（权限 `queue_monitor.view`） |
| 调度器 | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## 定时任务

每个 Phase 都会向此表追加。注册内容在 `routes/console.php`。时间以服务器时区（UTC）为准。

| 任务 | 频率 | Phase | 失败检测 |
|---|---|---|---|
| `admission:expire-drafts` | 每天 03:10 | 3 | 每次执行都记录到业务审计（日志 `command`）。超过一天没有记录时检查调度器 |
| `registration:release-reserved-seats` | 每 5 分钟 | 5 | 无审计记录。如果已过转换时刻的预留名额的 `release_processed_at` 为空，检查调度器 |
| `waitlist:promote` | 每小时 | 5 | 每次执行都记录到业务审计（日志 `command`） |

## 队列任务

| 任务 | 触发 | 备注 |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | 批处理。失败的任务在 Horizon 中重试 |
| `PromoteWaitlistJob` | 退选、撤销选课、预留名额转换 | 每个课程班只运行一个（防止重复）。候补递补时重新检查所有规则 |
| `CloseTermRecordsJob`、`FinishTermCloseJob` | `term:close`、管理界面的学期结算 | 以 100 名学生为一批处理后，接着执行收尾任务（成绩优秀排名、学期状态变更）。在 Horizon 的批次 `term-close:{学期代码}` 中查看进度。失败的任务在 Horizon 中重试（按学生的计算重复执行结果相同） |
| `ImportCsv`（Filament） | 管理界面 CSV 导入、`reference:import` | 批处理。完成通知显示在管理界面通知中（`notifications` 表）。失败行在 `failed_import_rows` |
| `audit:seal` | 每天 03:30 | 封存审计哈希链（[审计完整性](audit-and-maintenance.md)） |
| 调度器心跳 | 每分钟 | 把最近一次执行时间写入缓存。`system:health-check` 在超过 5 分钟没有心跳时判定为失败 |

队列任务执行期间，数据库执行者记录为 `system:job:{类名}`。
