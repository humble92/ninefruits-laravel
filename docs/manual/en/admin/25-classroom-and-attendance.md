# 25. Instructor Classroom and Attendance

Menu: instructors use the portal **My teaching > Roster and attendance**, students the portal **Attendance**. Staff use **Class meetings** at the bottom of the **Curriculum and sections > Sections > Edit** screen.

| Who | What they can do |
|---|---|
| Assigned instructors | Roster (with photos) and CSV, attendance sheet PDF, generating, adding and cancelling class meetings, entering attendance |
| Staff with `Plan sections` (within scope) | Same as instructors + class meetings in the administration panel |
| Staff with `View sections` (within scope) | Roster, attendance sheet, viewing attendance |
| Students | Their own attendance |

Classroom features such as announcements, assignments and materials are left to the LMS (porting plan section 12).

## Differences from the legacy system

- **The legacy system (YUSTIC) did not record attendance.** Its "attendance sheet" was a PDF that printed empty boxes (27 to 34 of them, varying by copy) next to the roster, with no dates and no totals. The new system creates **class meetings** from the meeting times, records present, late, absent or excused per meeting, and prints the records and totals on the attendance sheet PDF.
- **Roster.** The legacy instructor screen showed only student numbers and names (and exposed the resident registration number and address in a popup) and had no Excel download. The new system shows only student number, name, photo, program, year level and contact email, and downloads as CSV. Downloads are recorded in the audit log.
- **Instructors' access to their students.** An RLS policy was added so instructors can see the student records and people of the students in their sections (before, they could see only their advisees, which left names empty even on the grade entry screen).

## Class meetings

- **Generate**: one meeting per occurrence of the weekly meeting times (day, time, period). Instructors use the **Generate from meeting times** button; staff use **Class meetings > Generate meetings** in the administration panel or the command `attendance:generate-sessions {term}` (whole term). Existing meetings and attendance are left alone, so it can be run again after the meeting times change.
- **Add a meeting**: a class outside the meeting times, such as a make-up class. Only dates within the session.
- **Cancel**: cancel the meeting with a reason. The meeting stays but is left out of the totals. **Restore** brings it back.
- There is no holiday calendar. Cancel the meetings on public holidays (see section 13.2; this becomes automatic once a term calendar is added).

## Entering attendance

Under the meeting's **Attendance**, choose present, late, absent or excused for each student and add a note (200 characters). The quickest way is **All present** and then changing the exceptions.

- Attendance can be entered from the day of the meeting (not for future meetings).
- Saving again records only the students who changed, and the audit event (`attendance.recorded`) keeps the old and new values.
- Clearing the status puts the student back to "not recorded".

## Totals

| Item | Definition |
|---|---|
| Meetings held | Meetings that are not cancelled and lie on or before today |
| Attendance rate | (present + late + excused) ÷ meetings held × 100 |
| Not recorded | Meetings held without a record |

There is no rule converting a number of late marks into an absence or giving an F above one third of absences. It differs between universities, so instructors reflect attendance in the grade themselves (review item 13.5).

## Attendance sheet PDF and roster CSV

Both downloads are recorded in the audit log (`attendance.sheet_exported`; the roster CSV as before). CSV values starting with `=`, `+`, `-` or `@` are exported with a leading single quote so they are not run as formulas.

- **Attendance sheet (PDF)**: landscape A4. Students down, meetings across (cancelled ones left out), O/△/X/E marks and totals per student. Meetings still to come are blank, so it also serves for recording by hand.
- **Roster (CSV)**: student number, name, program, year level, contact email, registration status. UTF-8 with BOM, so it opens directly in Excel. Every download is recorded in the audit event `attendance.roster_exported`.

## Student portal

**Attendance**: per term and course, the numbers present, late, absent and excused, the attendance rate, and the record per meeting (only what the instructor entered).

## Operating procedure

1. Before the term starts, finalize the meeting times of the sections ([chapter 18](18-sections-and-timetables.md)).
2. Create the meetings with `php artisan attendance:generate-sessions {term} --actor=...` (instructors can also create them on screen).
3. Run it again when meeting times change. Meetings of removed times are not deleted automatically; cancel them.
