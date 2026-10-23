# 감사 무결성과 정기 정리

## 감사 해시 체인

감사 테이블(`audit.record_changes` 데이터 변경 기록, `audit.activity_log` 업무 감사, `audit.security_events` 보안 이벤트)은 추가만 가능하다(트리거). 여기에 더해 변조를 **드러내는** 장치가 있다.

- 행마다 삽입 시 내용의 SHA-256(`row_hash`)이 붙는다.
- `audit:seal`(매일 03:30 스케줄)이 새 행을 ID 순서로 체인에 잇는다: `chain_hash = SHA-256(이전 chain_hash || row_hash)`.
- `audit:verify-chain`이 모든 행의 내용 해시와 체인을 다시 계산한다. 내용이 바뀐 행은 "row mismatch", 빠지거나 순서가 바뀐 행은 "chain mismatch"로 나오고 종료 코드 2를 돌려준다.

유의점

- 봉인 전(그날 안)에 바뀐 행은 체인으로 잡지 못한다. 그래서 봉인은 매일, 검증은 백업 전과 보안 점검 때 한다.
- 정리(`audit:prune`)로 앞부분을 지우면 남은 첫 행의 연결은 검사하지 않는다(지운 범위는 정리 명령의 감사 기록에 남는다).
- DB 관리자(스키마 소유자)만 `app.audit_maintenance = on`으로 바꿀 수 있다. 애플리케이션 역할은 UPDATE·DELETE 권한이 없다.

## 보존과 정리

1. 보존 기간을 정한다(예: 데이터 변경 기록 3년, 업무 감사 5년, 보안 이벤트 1년).
2. 정리 전 백업([백업·복구](backup-restore.md))을 받고 `audit:verify-chain`으로 체인이 온전한지 확인한다.
3. `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run`으로 건수를 보고, `--dry-run` 없이 실행한다. 봉인되지 않은 행은 지우지 않으므로 먼저 `audit:seal`을 돌린다.
4. 정리 뒤 `audit:verify-chain`을 다시 돌린다.

## 파일 정리

`media:prune-orphans --dry-run`은 소유자가 사라진 미디어 행과 행이 없는 디렉터리를 보여 주고, `--dry-run` 없이 지운다. 인물 사진, 지원 서류, 증명서 PDF, 시험 증빙이 대상이다. 월 1회 또는 대량 삭제 뒤에 실행한다.

## 상태 점검

`system:health-check`는 설정과 키, DB 역할과 RLS, 마이그레이션, 권한 카탈로그, Redis·큐(Horizon), Gotenberg, 저장소, 스케줄러 심장박동(1분마다 캐시에 기록)을 점검하고 문제가 있으면 종료 코드 2를 돌려준다. `--json`으로 모니터링에 연결한다.
