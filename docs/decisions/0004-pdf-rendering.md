# 0004. PDF 렌더링: Gotenberg (Chromium) 채택

- 상태: 채택 (2026-10-22, Phase 0)
- 관련: [이식 계획 5.1절](../porting-plan.md#51-기술-스택)

## 후보

| 후보 | 판단 |
|---|---|
| dompdf | CJK 폰트 처리와 복잡한 표 레이아웃 문제로 계획 단계에서 제외 |
| Browsershot | 앱 컨테이너에 Node와 Chromium 설치 필요. 앱 서버마다 브라우저 프로세스 관리 |
| Gotenberg | Chromium과 Noto CJK 폰트가 들어 있는 별도 컨테이너. HTTP로 HTML을 보내 PDF를 받음 |

## 결정

Gotenberg 8 컨테이너(`gotenberg/gotenberg:8`)를 쓴다.

- 앱 이미지에 브라우저를 넣지 않아 앱 서버가 가볍고, PDF 생성 부하를 따로 확장할 수 있다.
- 컨테이너에 Noto Sans/Serif CJK(KR, SC, TC, JP, HK)가 포함되어 있다.
- 코드: `App\Support\Pdf\PdfRenderer`(인터페이스), `GotenbergPdfRenderer`, `PdfOptions`(기본 A4). 레이아웃 컴포넌트 `x-pdf-layout`.

## 검증

- `php artisan pdf:sample`로 한국어, 중국어 간체, 영어가 섞인 표를 렌더링했다. PDF에 `NotoSansCJKkr`, `NotoSansCJKsc` 폰트가 부분 임베딩되고, 한글과 한자가 각각 올바른 글꼴로 표시된다.
- 언어별 글꼴 선택: 기본 글꼴 순서는 KR → SC → Latin. 중국어 구간은 `lang="zh-CN"`을 지정하면 SC 글꼴이 우선한다(한자 자형 차이 때문).
- 테스트: `tests/Feature/Pdf/PdfRenderingTest.php` (HTTP 가짜 응답 테스트 + 실제 Gotenberg 통합 테스트, 연결 불가 시 건너뜀).

## 남은 일

- 증명서 PDF 보관(medialibrary, Phase 8), 대량 생성은 큐 배치로 처리.
- 운영 환경에서는 Gotenberg를 내부망에만 노출하고 요청 크기와 시간 제한을 설정한다.
