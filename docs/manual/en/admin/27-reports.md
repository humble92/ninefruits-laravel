# 27. Reports and reporting Views

Menu: **Reports > Reports** (`View standard reports and download them` permission). Role defaults: Registrar, Academic Affairs, Department Assistant (within scope).

## Standard reports

Choose a report and its filters: up to 500 rows are shown on screen, and **Download CSV** gets them all (UTF-8 with BOM, opens directly in Excel). Downloads are recorded in the audit event `reports.exported`. Values starting with `=`, `+`, `-` or `@` are exported with a leading single quote (`'`) so Excel does not run them as formulas.

| Report | Filters | Content |
|---|---|---|
| Student list | Org unit (including units below it), status, year level | Student number, name, program, org unit, year level, status, admission date, graduation date, degree number |
| Headcount by unit, program, year and status | Org unit, status | Number of students |
| Term grade summary | Term, academic standing | Term and cumulative credits and GPA, number of academic warnings, honors rank ([chapter 21](21-grades.md)) |
| Graduation candidates | Satisfied or not | Latest degree audit per student and program ([chapter 26](26-graduation.md)) |
| Status changes | Term, period | Kind of change, statuses before and after, effective date, request status |
| Enrollment per section | Term, org unit | Section, instructors, capacity, enrolled, waiting |
| Students not registered in a term | Term, org unit | Enrolled students without a registration in that term |

What is visible follows the viewer's permission scope (student record viewing scope and the like). The legacy system's (YUSTIC) fee and tuition reports (unpaid tuition and the like) are out of scope and do not exist.

## reporting views

Reports read the views of the `reporting` schema in the database. BI tools and SQL users query the same views with the read-only role `app_reporting`.

| View | Content |
|---|---|
| `reporting.students` | Student record + person name + program and org unit names |
| `reporting.headcount` | Headcount by org unit, program, year level and status |
| `reporting.term_records` | Grade summary per term |
| `reporting.course_grades` | Grades per course (finalized or not, grade, passed, retake) |
| `reporting.section_enrollments` | Enrolled and waiting per section, instructors |
| `reporting.section_meetings` | Meeting times and rooms |
| `reporting.status_changes` | Status changes |
| `reporting.degree_audits` | Latest degree audit per student and program role |

- Translated columns (`program_name` and the like) follow the session locale: `SET app.locale = 'ko';`. The raw jsonb columns (`program_name_i18n` and the like) are available too.
- The views are `security_invoker`, so the viewer's row level security applies unchanged. Read with a student account, they return only that student's rows.
- The view definitions are in `database/sql/views/reporting.sql`; after a change they are applied again from a migration.

The legacy `AV_*` views (about 90 of them) were not in use and were not ported.
