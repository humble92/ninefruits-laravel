# 密钥管理

- 对象：IT 运维人员

| 密钥 | 用途 | 丢失后果 | 更换 |
|---|---|---|---|
| `APP_KEY` | 会话、Cookie、双重身份验证密钥、记住设备 Cookie 的签名 | 所有用户被登出，已注册的双重身份验证（身份验证器应用）无法使用 | 把旧密钥放入 `APP_PREVIOUS_KEYS` 后更换 |
| `PII_ENCRYPTION_KEY` | 身份证号等个人信息的加密 | **已加密的个人信息无法恢复** | 按下面的步骤 |
| `PII_HASH_KEY` | 个人信息精确匹配搜索的哈希 | 无法搜索（数据仍在） | 原则上不更换 |

保管原则

- 三个密钥在服务器环境配置之外，另在机密存储（Vault、云端机密管理器等）或离线封存处保管两份以上。
- 不要与备份文件放在同一位置。即使数据库备份泄露，没有密钥也不能读取个人信息。

## 个人信息密钥更换步骤

1. 用 `php artisan pii:generate-key` 生成新密钥（只使用输出中的 `PII_ENCRYPTION_KEY`）。
2. 把当前密钥放入 `PII_PREVIOUS_KEYS`（多个用逗号分隔），把新密钥放入 `PII_ENCRYPTION_KEY`。
3. 让配置重新加载：`php artisan config:cache`、`php artisan horizon:terminate`。
4. 对每个个人信息模型，先用 `php artisan pii:reencrypt {模型} --dry-run` 确认对象数量，然后不带 `--force` 执行。
5. 在 Horizon 中确认批次完成，待 `--dry-run` 的结果为 0 后，从 `PII_PREVIOUS_KEYS` 中删除旧密钥。
6. 旧密钥在备份保留期内封存保管（恢复旧备份时需要）。
