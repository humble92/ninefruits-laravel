# 备份与恢复

- 对象：IT 运维人员

## 备份对象

| 对象 | 方法 | 频率（建议） |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` 或 PITR（WAL 归档） | 每天全量 + WAL 连续归档 |
| 文件存储（`storage/app/private` 或 S3） | 快照或开启版本控制的存储桶 | 与数据库相同 |
| 环境配置与密钥 | 机密存储 | 变更时 |

数据库和文件存储必须能恢复到同一时间点（照片、证明 PDF 的元数据保存在数据库中）。

## 数据库备份示例

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` 是所有模式（`public`、`audit`、`reporting`、`util`）的所有者，因此用这个账户备份。角色（`app_*`）是集群级对象，不会进入转储文件。在恢复服务器上，先按[安装](installation.md)第 2 节创建角色。

## 恢复示例

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## 恢复演练

每季度在独立服务器上恢复一次，并确认以下各项：登录、个人信息解密（密钥一致）、审计记录查询、文件（照片、PDF）查看。
