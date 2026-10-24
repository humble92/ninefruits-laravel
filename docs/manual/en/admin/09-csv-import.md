# 9. CSV import

Use the **Import CSV** button at the top of the org unit, program, building, room and code list screens (`Import reference data from CSV` permission). The student record and instructor lists have the same button (`Create and edit student records` and `Manage instructors` permissions respectively). Very large files can be imported the same way by the IT operator with the `reference:import` command.

## Common rules

- The first line holds the column names (tables below). Encoding is UTF-8; in Excel, save as "CSV UTF-8".
- A row whose code already exists **updates** that record. Importing the same file again does not create duplicates.
- Per-language columns use the form `column_language`: `name_en`, `name_ko`, `name_zh_CN`. New rows require the English name (`name_en`). Empty cells do not erase existing translations.
- Dates are written as `2026-03-01`; yes/no as `1`/`0` or `true`/`false`.
- Imports run in the background. When finished, the result arrives as a notification at the top right of the administration panel; if rows failed, **Download information about the failed rows** gives a CSV with the reason per row. Fix it and import just that file again.
- If the column names on screen differ, you can match the columns by hand in the import dialog.

## Column formats

| Kind | Required columns | Optional columns |
|---|---|---|
| Org units | `code`, `type` (org unit type code), `name_en`, `effective_start_date` | `parent` (parent code), `name_ko` etc., `short_name_*`, `effective_end_date`, `sort_order` |
| Programs | `code`, `org_unit` (org unit code), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Buildings | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Rooms | `building` (building code), `code` (room number) | `name_*`, `room_type` (room type code), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Code values | `type` (list code), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

`degree_level` values: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

For org units, parent units must come first (sort as university → college → department).

## Example: org units

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Imports for students and instructors (Phase 2) and courses (Phase 4) are added together with those features.

## Students and instructors

Student records are matched and updated by `student_number`, instructors by `employee_number`. Both kinds share the person columns.

| Kind | Required columns for new rows | Optional columns |
|---|---|---|
| Student records | `student_number`, `program` (program code), `admission_date`, `family_name` | `catalog_year` (admission year when empty), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Instructors | `employee_number`, `org_unit` (org unit code), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Person columns (shared) | — | `given_name`, per-language names such as `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- `status` values: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (`graduation_date` is required for graduated). A status changed by import is treated as a **data correction** and creates no status change history. Real changes are recorded on screen ([chapter 13](13-status-changes-and-advisors.md)).
- `employment_type` values: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. `gender` values: `female`, `male`, `other`, `undisclosed`.
- If the `national_id` of a new student record matches an existing person, the record is attached to that person (readmission). Otherwise a new person is created. On update, empty cells do not erase existing values.
- Imported students and instructors have no account. Send them an invitation with [account invitations](14-merge-and-invitations.md#account-invitations).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Courses

**Import CSV** at the top of the course list (`Manage courses, versions and requisites` permission) or `reference:import courses`. One row is one course version.

| Required columns | Optional columns |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (new version) | `org_unit` (required for a new course; the managing org unit code), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (course category code), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Courses are matched by `code`, versions by `code` + `effective_start_date`. Importing the same file again updates them.
- A revision history can be loaded from one file: list the rows of the same code per period (put an `effective_end_date` on the earlier version).
- A row whose period overlaps another version fails. If the row of a new course fails, the course is not created either.
- Requisites, equivalent courses and graduation requirements are entered on screen ([chapter 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Registration holds

**Import CSV** at the top of the **Registration > Holds** list (`Place and release holds, grant rule overrides` permission) or `reference:import registration_holds`. Used to load legacy `SRDENY` data or the finance office's list of unpaid students.

| Required columns | Optional columns |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (default: time of import), `ends_at`, `blocks_registration` (default 1), `blocks_grades`, `blocks_certificates` |

- Every row creates a new hold (source `import`). Importing the same file twice creates the holds twice, so check the list before importing again.
- Rows with an unknown student number fail. Holds are released on screen with a reason ([chapter 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
