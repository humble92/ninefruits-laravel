# 安装

- 对象：IT 运维人员
- 相关：[命令参考](commands.md)、[密钥管理](security-keys.md)

## 1. 系统要求

| 组件 | 版本 | 备注 |
|---|---|---|
| PHP | 8.5 | 扩展：`pdo_pgsql`、`pgsql`、`intl`、`redis`、`bcmath`、`zip`、`gd`、`sodium` |
| PostgreSQL | 18 | `btree_gist`、`pg_trgm` 扩展（由迁移安装），ICU 排序规则 |
| Redis | 8 | 缓存、会话、队列 |
| Gotenberg | 8 | 生成 PDF。只在内网暴露 |
| 邮件服务器 | SMTP | 密码重置、双重身份验证邮件验证码 |
| 队列工作进程 | Horizon | 用进程管理器（systemd、supervisor）常驻运行 `php artisan horizon` |
| 调度器 | cron | `* * * * * php artisan schedule:run` |

## 2. 准备数据库

由数据库超级用户执行一次。应用本身不使用超级用户。

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| 角色 | 环境变量 | 用途 |
|---|---|---|
| `app_runtime` | `DB_USERNAME`、`DB_PASSWORD` | 应用运行 |
| `app_migrator` | `DB_MIGRATOR_USERNAME`、`DB_MIGRATOR_PASSWORD` | 仅用于迁移 |
| `app_reporting` | （在 BI 工具中配置） | 查询 `reporting` 模式 |

## 3. 环境配置

复制 `.env.example` 并填写各项值。必须修改的值

- `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL`
- `APP_KEY`：`php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`、`PII_HASH_KEY`：`php artisan pii:generate-key`。**丢失后无法恢复已加密的个人信息。**按[密钥管理](security-keys.md)另行保管。
- `DB_*`、`REDIS_*`、`MAIL_*`、`GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`：首次安装时机构时区的默认值

## 4. 安装

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` 以问答方式进行。在部署脚本中用 `--force` 和各选项执行（[命令参考](commands.md#setupinstall)）。

安装配置文件（`--profile=`）：`korean-university` —— 第一学期/第二学期/短学期、4.5 绩点等级制、课程类别、从 09:00 开始的 50 分钟课时、教室用途。不应用时需在管理界面手动录入。之后再次应用也会保留管理员修改过的值。

安装完成后，业务管理员按[管理员手册第 5 章 初始设置顺序](../admin/05-institution-setup.md)录入基础数据。

## 5. 安装确认

1. 在 `/login` 用初始管理员登录。
2. 在管理界面 **设置 > 安全** 中确定双重身份验证策略。
3. 管理员为自己注册双重身份验证（门户 **安全**）。
4. 用 `php artisan pdf:sample` 检查 PDF 服务。
5. 在管理界面 **审计 > 数据变更记录** 中确认安装操作已以 `system:command:setup:install` 记录。

## 6. 更新

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # 迁移、必要数据、权限同步
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # 工作进程以新代码重新启动
php artisan up
```
