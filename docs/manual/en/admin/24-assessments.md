# 24. Examinations

Menu: **Examinations > Examination sessions**, **Examination results**, **Examination kinds**. Students register and see their results in the portal under **Examinations**.

| Permission | What it allows |
|---|---|
| `Register students, enter and publish results, record external results` | Register, cancel and mark students absent, enter results, import CSV, publish results, record and remove external results, view the results register (within the student record viewing scope) |
| `Manage examination kinds, areas and sessions` | Create and edit kinds, areas and sessions |

Role defaults: Registrar and Academic Affairs have both.

## Differences from the legacy system

- **No fixed columns per kind.** The legacy system (YUSTIC) had the four columns grammar, writing, listening and reading hard-wired in `CET`, one P/N letter per subject for the comprehensive examination (`SSCORE_ADD`), and yet another table for the computer examination (`COMEXAM`). The new system defines areas and a scoring method on the **examination kind** and handles every examination with the same session, registration and result structure.
- **Pass decision.** In the legacy, a total of the four areas at or above the cut-off meant a pass, with no minimum per area. The new system decides with the pass score of the kind (or session) and a **minimum per area**. Pass/fail kinds are decided by staff.
- **Registration window and capacity.** The legacy had none. A session has an opening and closing time for registration and a capacity.
- **Publishing results.** In the legacy, students saw results as soon as they were entered, and only the latest session. In the new system students see results after the session's **Publish results**, and the results of every session are kept.
- **External results.** The legacy had nowhere to record external tests such as TOEFL or TOPIK (the HSK box of the graduation audit sheet was an empty label). External results are recorded with an evidence file.
- **Link to graduation requirements.** In the legacy a person read the graduation audit sheet and decided. The requirement kind **Passed examination** puts it into the curriculum ([chapter 17](17-courses-and-curriculum.md)).

## Examination kinds and areas

**Examination kinds**: code, name (3 languages), category (Language, Comprehensive, Thesis, External test), scoring, maximum score, pass score, external results allowed, active.

| Scoring | Input | Decision |
|---|---|---|
| Score | Score per area (the total when there are no areas) | Total ≥ pass score and every area ≥ its minimum. Without a pass score, staff choose |
| Pass/fail | Passed or not | Staff choose |

**Areas** (at the bottom of the kind edit screen): code (CSV column name), name, maximum score, minimum to pass (optional). With areas, the total is the sum of the area scores.

Kinds seeded by the Korean university profile:

| Code | Kind | Decision | Note |
|---|---|---|---|
| `ENG_COMP` | English comprehensive examination | Score, pass at 60 | Listening, reading, writing, grammar, 25 points each |
| `COMP_EXAM` | Computer comprehensive examination | Pass/fail | |
| `THESIS` | Graduation thesis | Pass/fail | |
| `TOPIK` | Test of Proficiency in Korean | Pass/fail, external | Level and certificate number recorded |

## Sessions

**Examination sessions**: kind, date (unique per kind), venue, capacity, registration opens and closes, pass score (empty: the kind's value). The **Registrants** list is at the bottom of the session edit screen.

- **Register a student** (enter the student number): staff can register students regardless of the registration window, until the examination date. The capacity applies. Nobody can be registered for a session whose date has passed. Results are entered only for registered students; CSV rows of unregistered students are reported as errors and skipped.
- Students register and cancel in the portal during the registration window. Only enrolled and on-leave students can.
- **Enter result**: score per area (or total), passed or not where needed, note. Before publication, entering again overwrites it (audit event `assessment.result_recorded`). **After publication it is locked** (see correction requests below).
- **Absent**: registered but did not sit the examination. Entering a result puts the registration back to registered.
- **Cancel registration**: a registration with a result cannot be cancelled.
- **Publish results**: from now on students see the results of this session. After publication results cannot be edited or deleted directly; they change through a **correction request** (decision of 2026-10-23). Same as the command `assessment:publish-results`.
- The session's **pass score** (overriding the kind's): used when a meeting lowers the cut-off because the examination was hard. It can be changed **before publication**, and the moment it changes, every result already entered for that session is **judged again** from the stored scores (the total against the new threshold, the area minimums as set on the kind). With a reason, the audit event `assessment.pass_score_changed` keeps the old and new thresholds together with the student numbers whose decision changed. **After publication** it is locked (correction requests only). The minimum per area cannot be changed on the session (kind setting).
- **Import results (CSV)**: format below. Rows of unregistered students are skipped as errors. Same as the command `assessment:import-results`.

CSV format (header row, UTF-8):

| Column | Content |
|---|---|
| `student_number` | Student number (required) |
| Area code (`LISTENING`, …) | Area score. Case-insensitive |
| `total` | Total for kinds without areas |
| `passed` | `Y`/`N`. Required for pass/fail kinds or kinds without a pass score |
| `absent` | `Y` marks the student absent |
| `note` | Note |

Error rows are skipped and reported with their line number in the result notification (screen) or the output (command).

## Result corrections

The procedure to change the results of a published session. It works like grade changes.

1. **Request a correction** from the session's registrant list: the new scores (or passed or not) and the reason. One pending request per result. Audit event `assessment.correction_requested`.
2. Under **Examinations > Result corrections**, a holder of the `Manage examination kinds, areas and sessions` permission clicks **Approve** (note optional) or **Reject** (note required). **Nobody can decide their own request.**
3. On approval the result changes to the new values at once and the student sees it. Audit events `assessment.correction_approved`, `assessment.result_corrected`. The requester can cancel the request until it is decided.

The list shows pending requests by default, with the current values and the proposed values side by side.

## Results register

**Examination results**: every result (sessions and external). Filter by kind, source and passed. The viewing scope is the same as for student records.

- **Record an external result**: student number, kind (only kinds that allow external results), date taken (not in the future), total, level, passed or not, certificate number, evidence file (PDF, JPG, PNG, 5 MB), note. Audit event `assessment.external_recorded`.
- **Evidence**: opens the file. Students can open their own.
- **Remove**: reason required, audit event `assessment.result_removed`. For deleting and re-recording a wrongly entered external result or a result before publication. Results of a published session cannot be removed (correction request).

## Graduation requirements

In the **Graduation requirements** editor choose the requirement kind **Passed examination** and name the examination kind. A student satisfies it with at least one passed result (session or external) of that kind. The top of the portal **Examinations** page shows the kinds required by the student's curriculum and whether they are passed. The degree audit (Phase 11) reads this requirement.

## Student portal

**Examinations**: curriculum requirements and whether they are passed, sessions open for registration (register, cancel), my registrations, my results (published sessions and external results, area scores, evidence).

## Frequently asked questions

- **I need to register a student outside the registration window.** Staff register them under **Registrants > Register a student** on the session.
- **I want to change the pass score for this session only.** Enter the session's pass score. Results already entered are judged against the new threshold only when entered again.
- **I entered a score wrongly.** Before publication, **Enter result** again to overwrite it. After publication, file a **correction request** and have another staff member approve it.
- **Statistics on absentees.** Filter the registrant list by status.
