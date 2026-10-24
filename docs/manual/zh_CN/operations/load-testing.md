# 负载测试（选课高峰）

用 k6 重现选课首日早晨大量学生同时涌向同一课程班的场景。脚本为 `tests/load/registration.js`。

## 准备

1. 生成大规模演示数据并为学生附加账户（账户列表 CSV 会生成在 `storage/app/private/load-accounts.csv`）。

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. 选课期间必须处于开放状态（演示数据会打开本学期时段的选课期间）。
3. 队列工作进程（Horizon）和 Redis 必须在运行。名额预留和候补递补通过队列处理。

## 执行

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- 把 `--vus` 按 100 → 200 → 500 逐步提高，观察 `http_req_duration` 的 p95 和失败率。基准：选课页面 p95 < 800ms，提交选课 p95 < 1.5s，失败率 < 1%。
- 服务器端同时观察 `pg_stat_activity` 中的等待（lock）数、Horizon 处理延迟、php-fpm 工作进程饱和情况。
- 在 Sail 上运行时，提高内置服务器的工作进程数（`.env` 中的 `PHP_CLI_SERVER_WORKERS`，默认 4）。工作进程数决定吞吐量上限。

## 设计上的保证

- 名额由数据库行锁（`SeatAllocator::lock`）保护，即使并发请求涌入也不会超额选课（`RegistrationConcurrencyTest`）。
- 名额预留在没有 Redis 和队列的情况下也能在数据库中保持正确，但候补递补和通知由队列处理。

## 关于 Octane 的判断

以 Sail 单个 php-fpm 为基准，如果 p95 超过基准，先调整 php-fpm 工作进程数以及 PostgreSQL 的 `max_connections` 和 PgBouncer。仍然不够时再考虑 Laravel Octane（FrankenPHP 或 Swoole）。切换到 Octane 之前需要确认：每个请求都必须初始化的状态（`DatabaseSessionContext` 的会话变量在请求结束时通过 `reset()` 复位，因此在 Octane 下也是安全的）、残留在单例中的用户数据、静态缓存。决定在看到实际负载数据后写入 Phase 13 报告。

## 2026-10-23 测试结果

开发用 Sail 容器（内置服务器，无 opcache），100 VU 持续 1 分钟：4 个工作进程 24.7 req/s，16 个工作进程 34.5 req/s，HTTP 失败 0，超额选课 0，PostgreSQL 连接 4 个。单个请求的服务器处理时间约为页面 120 ms、选课 190 ms；100 VU 下的数秒延迟来自工作进程排队。吞吐量随 PHP 工作进程数线性增长，数据库远未到瓶颈，因此**不引入 Octane**，以 php-fpm 工作进程数和应用服务器横向扩展应对。上线前在 php-fpm + opcache 的预发布环境以 200、500 VU 重新测试。详细数据见 [Phase 13 报告](../../../porting/phase-13-report.md) 第 7 节。
