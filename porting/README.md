# 이식(porting) 기록

레거시 YUSTIC(PHP + Oracle)을 Laravel로 새로 구현한 과정의 기록이다. 시스템을 사용·운영하는 데는 필요 없고, 왜 이렇게 만들었는지 찾아볼 때 본다.

- [porting-plan.md](porting-plan.md): 전체 계획, 범위, 데이터 모델 방향, Phase별 상태
- [decisions/](decisions/): 설계 결정(ADR) 0001–0017
- `phase-N-report.md`: Phase별 결과 보고(완료 기준, 구현 내용, 레거시와의 차이, 남은 일, CI 결과)

사용자 문서는 [../docs/](../docs/README.md)에 있다.
