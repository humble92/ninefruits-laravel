# 스케줄 작업과 큐

- 대상: IT 운영자

## 상시 프로세스

| 프로세스 | 명령 | 확인 |
|---|---|---|
| 큐 워커 | `php artisan horizon` | `/horizon` (권한 `queue_monitor.view`) |
| 스케줄러 | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## 스케줄 작업

Phase마다 이 표에 추가한다. 등록 내용은 `routes/console.php`. 시각은 서버 시간대(UTC) 기준이다.

| 작업 | 주기 | Phase | 실패 감지 |
|---|---|---|---|
| `admission:expire-drafts` | 매일 03:10 | 3 | 업무 감사(로그 `command`)에 실행마다 기록. 기록이 하루 넘게 없으면 스케줄러를 확인한다 |
| `registration:release-reserved-seats` | 5분마다 | 5 | 감사 기록 없음. 전환 시각이 지난 좌석 할당의 `release_processed_at`이 비어 있으면 스케줄러를 확인한다 |
| `waitlist:promote` | 매시간 | 5 | 업무 감사(로그 `command`)에 실행마다 기록 |

## 큐 작업

| 작업 | 발생 | 비고 |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | 배치. 실패한 작업은 Horizon에서 재시도 |
| `PromoteWaitlistJob` | 수강 취소·철회, 좌석 할당 전환 | 강좌별 하나만 실행(중복 방지). 대기자 승격은 모든 규칙을 다시 확인한다 |
| `ImportCsv` (Filament) | 관리 화면 CSV 가져오기, `reference:import` | 배치. 완료 알림은 관리 화면 알림(`notifications` 테이블). 실패 행은 `failed_import_rows` |

큐 작업은 실행 중 DB 수행자가 `system:job:{클래스}`로 기록된다.
