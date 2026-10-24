# 故障处理

- 对象：IT 运维人员

| 现象 | 检查 |
|---|---|
| 登录后立即跳转到安全页面 | 双重身份验证策略为"必须"且宽限期已过。用户完成注册后即可解决。策略在 **设置 > 安全** |
| 管理界面 403 | 用户没有带 `admin_panel.access` 权限的有效角色分配。检查 **用户与权限 > 用户 > 角色分配** |
| 看不到菜单 | 角色中没有该页面的权限（例如 `roles.view`） |
| 收不到邮件验证码 | 邮件设置（`MAIL_*`）、安全事件中的 `已发送邮件验证码` 记录、发送限制（每分钟 1 次，每小时 5 次） |
| 页面上其他人的数据为 0 条 | 可能是正常现象（行级安全）。通过 **数据变更记录** 的用户列确认数据库会话执行者是否已设置 |
| `permission denied for schema public` | 应用以 `app_runtime` 尝试执行 DDL。迁移只能通过 `php artisan migrate`（自动使用 `app_migrator`）执行 |
| `Audit records are append-only` | 尝试修改或删除审计表。这是正常的拦截 |
| PDF 生成失败 | `php artisan pdf:sample`、Gotenberg 状态、`GOTENBERG_URL` |
| 队列任务停滞 | 在 `/horizon` 中检查失败任务，`php artisan horizon:status` |

按请求追踪：用响应头 `X-Request-Id` 的值可以同时检索日志、业务审计、数据变更记录和安全事件。
