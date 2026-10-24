# 审计完整性与定期清理

## 审计哈希链

审计表（`audit.record_changes` 数据变更记录、`audit.activity_log` 业务审计、`audit.security_events` 安全事件）只允许追加（由触发器保证）。在此之上，还有一套用于**暴露**篡改的机制。

- 每行在插入时都会附带其内容的 SHA-256（`row_hash`）。
- `audit:seal`（每天 03:30 定时执行）按 ID 顺序把新行接入哈希链：`chain_hash = SHA-256(前一行 chain_hash || row_hash)`。
- `audit:verify-chain` 重新计算所有行的内容哈希和链。内容被改动的行报告为 "row mismatch"，缺失或顺序被改动的行报告为 "chain mismatch"，并返回退出码 2。

注意事项

- 封存之前（当天内）被改动的行无法通过链发现。因此封存每天执行，验证在备份前和安全检查时执行。
- 用清理（`audit:prune`）删除前段数据后，剩余第一行的链接不再检查（被删除的范围会记录在清理命令的审计记录中）。
- 只有数据库管理员（模式所有者）才能把 `app.audit_maintenance` 设为 `on`。应用角色没有 UPDATE、DELETE 权限。

## 保留与清理

1. 确定保留期限（例如：数据变更记录 3 年，业务审计 5 年，安全事件 1 年）。
2. 清理前先做备份（[备份与恢复](backup-restore.md)），并用 `audit:verify-chain` 确认链完整。
3. 用 `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run` 查看行数，再去掉 `--dry-run` 执行。未封存的行不会被删除，所以先运行 `audit:seal`。
4. 清理后再次运行 `audit:verify-chain`。

清理本身的记录：每次执行都会在业务审计（日志 `command`）中留下基准日期、选项、执行者、OS 用户，以及每张表的**删除行数、删除的 ID 范围、边界处的 chain_hash**；实际删除还会作为安全事件 `audit.pruned` 记录（**审计 > 安全事件**）。这些记录行也会在下一次封存时进入链中。`audit:verify-chain` 不检查剩余第一行链接的原因正是这个边界；把边界哈希与清理记录对照，就能确认被删除的范围与记录的清理一致。

## 文件清理

`media:prune-orphans --dry-run` 显示所有者已不存在的媒体行以及没有对应行的目录，去掉 `--dry-run` 后执行删除。对象包括人员照片、申请材料、证明 PDF、考试凭证。每月执行一次，或在大批量删除之后执行。

## 状态检查

`system:health-check` 检查配置与密钥、数据库角色与 RLS、迁移、权限目录、Redis 与队列（Horizon）、Gotenberg、存储，以及调度器心跳（每分钟写入缓存），发现问题时返回退出码 2。用 `--json` 接入监控系统。
