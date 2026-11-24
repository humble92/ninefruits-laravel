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

## 7. Web 服务器（Nginx）

在 php-fpm 前面部署 Nginx。以下为示例，请根据服务器修改地址、路径、证书和 php-fpm 套接字。

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

- **错误页面：** `public/errors/unavailable.html` 是 PHP 无响应时（502、503、504）由 Nginx 显示的静态页面。它不依赖构建文件和数据库，文字随浏览器语言切换（不支持的语言显示英语）。由于 `fastcgi_intercept_errors` 保持默认值（off），应用自己返回的错误页面（500、`php artisan down` 期间的 503 等）会原样传递。
- **参考编号：** `ssi on` 会把 Nginx 的 `$request_id` 写入页面。同一个值以 `X-Request-Id` 传给应用，因此可以用一个编号同时检索 Nginx 访问日志（`rid=`）、应用日志和审计记录（[故障排查](troubleshooting.md)）。客户端发送的 `X-Request-Id` 会被这个值替换。
- **Nginx 代理到独立的应用服务器时：** 使用 `proxy_set_header X-Request-Id $request_id;`，并让 `proxy_intercept_errors` 保持默认值（off）。`error_page` 及其 `location` 保持不变。
- **修改文字时：** 修改 `lang/*/errors.php` 中的 `unavailable` 和 `reference` 时，也要同时修改此文件中的 JSON。两者不一致时测试（`ErrorPagesTest`）会失败。
- **Nginx 版本：** `http2 on;` 需要 Nginx 1.25.1 或更高版本。较低版本（例如 Ubuntu 22.04 的 1.18）请删除这一行，改写为 `listen 443 ssl http2;`。
