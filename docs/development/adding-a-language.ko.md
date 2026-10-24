# 언어 추가하기

English: [adding-a-language.md](adding-a-language.md)

Ninefruits는 한국어, 영어, 중국어(간체), 라오스어로 제공된다. 코드 어디에도 언어 목록은 없다. `lang/{locale}/` 디렉터리가 있고 기관 설정에서 그 로케일을 켜면 언어가 생긴다. 이 문서는 언어 하나를 더 추가하는 전체 절차다. 프로젝트를 처음 보는 개발자나 번역가를 대상으로 쓴다. 소요: 기계 번역을 보조로 쓰면 UI 문자열과 매뉴얼에 2~4일, 그리고 원어민 검수.

## 0. 로케일 코드 정하기

- ISO 639-1 두 글자 코드(`lo`, `vi`, `th`)를 쓰고, 지역에 따라 문자나 표기가 다를 때만 `_지역`을 붙인다(`zh_CN`, `pt_BR`). 이 코드가 `lang/` 아래 디렉터리 이름이고 `users.locale`에 저장되는 값이다.
- ICU가 아는 코드인지 확인한다. `php -r 'echo Locale::getDisplayName("lo", "lo");'`가 그 언어로 쓴 언어 이름을 찍어야 한다(언어 메뉴에 그대로 보인다). Carbon(날짜)과 `Illuminate\Support\Number`(숫자)도 같은 코드를 쓴다.

## 1. UI 문자열 (`lang/{locale}/`)

`lang/en/`이 원본이고, 다른 언어는 같은 파일과 같은 키를 모두 가져야 한다. 두 가지 방법이 있다.

**번역가용 JSON으로 (PHP를 편집하지 않는 번역가에게 권장)**

```bash
mkdir -p lang/lo
./vendor/bin/sail artisan i18n:export lo storage/app/lo.json
# storage/app/lo.json을 번역가에게 전달: {"group.key": {"source": "영문", "translation": ""}}
./vendor/bin/sail artisan i18n:import lo storage/app/lo.json --dry-run   # 쓸 키 목록만 출력
./vendor/bin/sail artisan i18n:import lo storage/app/lo.json
```

`i18n:import`는 `lang/lo/*.php`를 쓰되 영문에 있는 키만 받고 빈 번역은 건너뛴다. 남은 키는 `--missing`으로 다시 내보낸다.

**PHP 파일을 직접**: `lang/en/*.php`를 `lang/lo/`에 복사해 값만 번역한다. 키, 자리표시자(`:name`, `:count`), 복수형(`one|many`, `{0} none|[1,*] some`), HTML은 그대로 둔다.

그다음:

- `lang/{locale}.json`: Laravel과 Filament 자체 문자열(검증 기본 메시지, "저장", 페이지 이동). `lang/ko.json`을 복사해 값을 번역한다. 키는 영문 문장이다.
- Filament 패널 자체 문구(Dashboard, Search, Sign out, 표 페이지 이동, 액션 버튼)는 Filament 패키지에서 오며 많은 언어를 제공하지만 전부는 아니다. 새 언어에서 관리 화면에 영어가 남으면 `lang/vendor/filament*/{locale}/`에 번역을 둔다(`php artisan vendor:publish --tag=filament-translations` 후 번역). 라오어는 아직 없다.
- 검사: `./vendor/bin/sail artisan i18n:missing lo --strict`가 `0 missing`이어야 한다. CI가 `lang/` 아래 모든 디렉터리에 이 검사를 돌리므로 반쯤 된 언어는 병합되지 않는다.
- 용어집: `docs/development/naming.md` 6절에 도메인 용어(학적, 학기, 개설강좌, 수강 …)가 있다. 번역을 시작하기 전에 용어별 번역을 한 번 정해 적어 두고 매뉴얼에도 같은 말을 쓴다.

## 2. 로케일 켜기

- 설치 한 곳에서: 관리 화면 **기관 설정 > 지원 언어**(또는 설정 테이블의 `institution.supported_locales`). 그러면 사용자 메뉴에 언어가 보이고 `/locale`이 받아들이며 브라우저 언어 협상에도 포함된다.
- 애플리케이션에 기본 탑재할 때는 설정 마이그레이션을 추가해 새 설치가 기본으로 갖게 한다. 예: `database/settings/2026_10_23_008010_add_lao_locale.php`는 목록이 아직 기본값일 때만 `lo`를 덧붙인다.

## 3. 시드 데이터의 이름 (선택, 권장)

`setup:install`이 만드는 기준 데이터의 이름은 로케일별 JSON으로 저장된다. 예: `['en' => 'University', 'ko' => '대학교', 'zh_CN' => '大学', 'lo' => 'ມະຫາວິທະຍາໄລ']`. 없는 로케일은 영어로 대체되므로 필수는 아니지만, 그 언어를 쓰는 대학은 자기 말로 보고 싶어 한다.

- `database/seeders/Essential/`: 조직 단위 유형, 역할 템플릿, 학적 변동 유형, 코드 데이터 유형.
- `database/seeders/Profiles/`: 규칙 묶음(교과 구분, 성적 척도, 요건 이름).

국가와 언어(`CountrySeeder`, `LanguageSeeder`)는 설치된 모든 언어의 이름을 ICU에서 자동으로 받는다. 기존 설치는 다음 배포에서 필수 시더가 다시 돌면서 비어 있는 로케일만 채운다.

## 4. 문서

| 무엇 | 어디 | 메모 |
|---|---|---|
| 관리자 매뉴얼 | `docs/manual/{locale}/admin/` | 28장 + `README.md`(장 목차). `docs/manual/ko/admin/`과 파일 이름을 같게 해 언어 간 링크가 맞게 한다 |
| 운영 매뉴얼 | `docs/manual/{locale}/operations/` | 9개 파일. `commands.md`는 `### \`명령\`` 제목과 옵션 이름을 그대로 두고 설명만 번역한다 |
| 매뉴얼 색인 | `docs/manual/{locale}/README.md` | 제목, 대상, 두 부분 링크 |
| 프로젝트 README | `README.{locale}.md` | `README.md`와 같은 구성. 데모 계정과 명령은 그대로 |
| 언어 표 | 모든 `README*.md` 상단 | 언어마다 한 행: README 링크와 매뉴얼 디렉터리. 모든 README에 새 행을 넣는다 |
| 스크린샷 | `docs/screenshots/{locale}/{admin,instructor,student}.png` | 아래 참고 |

매뉴얼의 원본은 한국어(`docs/manual/ko/`)다. 굵게 쓴 화면 이름은 그 언어의 UI 라벨과 같아야 하므로 1단계를 끝낸 뒤 매뉴얼을 번역한다.

**스크린샷** 세 장은 데모 데이터로만 만든다(실제 데이터 금지):

```bash
./vendor/bin/sail artisan migrate:fresh --force
./vendor/bin/sail artisan setup:install --force --profile=korean-university --grade-scale=4.5
./vendor/bin/sail artisan setup:demo --size=small --seed=42
python3 tools/screenshots.py lo docs/screenshots/lo
```

스크립트가 데모 시스템 관리자·교원·학생으로 로그인해 계정 언어를 바꾸고 Gotenberg 컨테이너(`/forms/chromium/screenshot/url`)로 찍는다.

## 5. PDF와 글꼴

증명서, 성적표, 출석부는 Gotenberg(Chromium)가 렌더링한다. 그 이미지에는 Noto 글꼴이 들어 있어 라오어, 태국어, 크메르어, 아랍어, 데바나가리, CJK를 덮는다. 덮지 않는 문자는 Gotenberg 이미지(`compose.yaml`)와 `resources/views/pdf/`의 PDF 스타일시트에 글꼴을 추가한다. `./vendor/bin/sail artisan pdf:sample`로 여러 문자가 섞인 표를 렌더링해 확인한다.

## 6. 데모 데이터 (선택)

`database/factories/Person/PersonFactory.php`는 이름에 Faker 로케일(`en_US`, `ko_KR`, `zh_CN`)을 섞고 문자별 이름 변형을 저장한다. 그 언어의 Faker 로케일이 있고 시연에 그 문자 이름이 중요하면 추가한다.

## 7. 풀 리퀘스트 전 체크리스트

- [ ] `lang/{locale}/` 완성: `i18n:missing {locale} --strict`가 0 missing
- [ ] `lang/{locale}.json` 있음
- [ ] 설정 마이그레이션(탑재 시) 또는 기관 설정 갱신
- [ ] 시드 이름 번역(또는 영어 대체를 의도적으로 선택)
- [ ] `docs/manual/{locale}/`(관리자 28장 + 색인, 운영 9개, 색인)과 `README.{locale}.md`
- [ ] 모든 `README*.md`의 언어 표 갱신
- [ ] `docs/screenshots/{locale}/` 스크린샷
- [ ] `./vendor/bin/sail composer check` 통과(Pint, PHPStan, 명명, 번역, 테스트)
- [ ] 원어민이 UI 문자열과 최소한 매뉴얼 앞부분을 검수했고 풀 리퀘스트에 검수자를 적음

라오스어는 2026-10-23에 처음 세 언어 다음으로 추가됐다. 그 풀 리퀘스트가 실제 예시다.
