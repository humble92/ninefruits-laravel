# 28. LMS Integration

Menu: **LMS integration > LMS platforms**, **Course links**, **Sync logs** (`Manage LMS platforms, links and synchronization` permission; default: Academic Affairs, besides System Administrator and Registrar). Instructors use the LMS links under **My teaching** and in the section detail, and **Load scores from the LMS** on the grade entry screen. Loading follows the same conditions as grade entry (instructors within the grade entry window, staff within their `Grades` management scope).

Developer details are in the [LMS integration guide](../../../lms-integration-guide.md).

## What goes where

| SIS → LMS | When |
|---|---|
| Section (course creation) + instructors | When the section is **published**, and with `lms:sync` |
| Confirmed registration → enrollment | Immediately on registration, waitlist promotion or staff registration |
| Drop or withdrawal → unenrollment | Immediately |
| Full reconcile | Daily at 02:30 (`lms:reconcile`) |

| LMS → SIS | When |
|---|---|
| Final scores (draft) | When the instructor clicks **Load scores from the LMS** on the grade entry screen. Saving and submitting is done by the instructor |

Finalized grades are not sent to the LMS. Course materials, assignments and quizzes live in the LMS.

## Platforms

**LMS platforms**: code, name, driver, base URL, credentials (key/value, stored encrypted, kept when left empty on edit), active, synchronize rosters, pull final scores.

- Driver **None (records only)**: calls nothing and records what would be sent. Use it to check the flow or for demonstrations before integrating. The `DEMO` platform in the demo data uses it.
- `Moodle`, `Open edX`: no connector yet (marked not implemented yet). When chosen, synchronizations are recorded as failed.
- Turning **Active** on makes section publications and registration changes flow immediately from then on and includes the platform in the nightly reconcile.

## Course links and sync logs

- **Course links**: which section is linked to which platform course (id, URL), status (Pending/Linked/Failed), last synchronization and error. **Synchronize again** pushes the section, instructors and roster again.
- **Sync logs**: result and message per step (course, add and remove member, pull scores, reconcile). Look here for the cause of a failure.
- Commands: `lms:sync {platform} --term={term}` (full push, `--dry-run`), `lms:reconcile` (reconcile).

## Portal

- The platform course link is shown in the course detail for students and instructors and in the instructor's **My teaching** list (linked sections only).
- Grade entry: when **Pull final scores** is on for the platform and the section is linked, the **Load scores from the LMS** button appears. It only fills the score column and does not save.

## Frequently asked questions

- **A student is missing in the LMS.** The student needs an account (user) to be synchronized. Without one, invite them ([chapter 14](14-merge-and-invitations.md)); the next reconcile adds them.
- **A synchronization stays failed.** Read the message in the sync log. Expired credentials are common. Change the values in the platform's edit screen and **Synchronize again**.
- **We switched platforms.** Create the new platform and turn the old one's Active off. Links are created separately per platform.
