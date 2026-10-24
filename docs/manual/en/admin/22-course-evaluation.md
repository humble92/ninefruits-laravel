# 22. Course Evaluation

Menu: **Course evaluation > Term evaluations**, **Evaluation forms**, **Responses by section**, **Settings > Course evaluation**. Students use the portal **Course evaluation**; instructors use the portal **My teaching > Course evaluation results**.

| Permission | What it allows |
|---|---|
| `View course evaluation response rates and results` | Response counts and response rates per section, published results. Assigned with an org unit scope, only the sections offered by that unit |
| `Manage evaluation forms, term evaluations and publication` | Forms and questions, term evaluation settings, publishing results, course evaluation settings |

Role defaults: Academic Affairs has both, Department Assistant has viewing (department scope).

## Differences from the legacy system

- **Anonymity.** The legacy system (YUSTIC) stored the student number with every response, and instructor accounts could read the raw table. The new system stores **who evaluated (completion records)** and **what was answered (responses)** separately. A response carries no student, no sequence number and no time. Responses are not written to the data change record (audit) either, because the audit record would keep the actor.
- **Threshold for showing results.** The legacy had no minimum response threshold on the instructor screen, and instructors could see other sections' results. The new system shows results and comments, after the term's results are **published**, **only for sections that reached the minimum number of responses and the minimum response rate**. Instructors see only their own sections.
- **Grade viewing block.** The legacy only blocked the menu link, and the lock was not lifted when the evaluation period ended. The new system blocks on the server and lifts the block when the window closes.
- **Question management.** The legacy inserted questions by SQL every term. The new system manages forms and questions on screen.
- The rule that refused responses with all 1s or all 5s became a setting (off by default).

## Preparing a term

1. **Form**: choose a form under **Evaluation forms**. The Korean university profile seeds the `STANDARD` form (3 questions each on preparation, teaching method, content, assessment, and facilities and equipment, on a 5-point scale, plus 1 comment question).
2. **Set up a term**: term, form, minimum responses, minimum response rate (%). The defaults come from **Settings > Course evaluation** (5 responses, 25%). Both thresholds must be met for results to show. The number of responses protects anonymity (with only a few responses the students could be guessed); the response rate ensures the results are representative.
3. **Evaluation window**: add a **Course evaluation** window per session among the **term windows** on the term edit screen ([chapter 10](10-terms.md)). Target criteria allow windows per year level.
4. During the window, `php artisan evaluation:remind {term}` sends reminder emails to students who have not finished. See response rates under **Responses by section**.
5. When grade processing is done, **Publish results** (or `evaluation:publish-results {term}`).

## Forms and questions

| Field | Description |
|---|---|
| Code, Name, Description | The description is shown above the student's evaluation screen |
| Scale points | 2 to 10, usually 5 |
| Label of 1, Label of the highest point | e.g. Strongly disagree, Strongly agree |

**Questions** tab: area (questions of the same area are grouped), question, type (Scale, Comment), required, **Reversed** (a negatively worded question; the answer is reversed in averages, e.g. a 5 on "It was hard to keep up with the pace of the class" counts as 1), order.

A form with at least one response can no longer have its questions or scale changed (so that results never become answers to a question that was not asked). To change it, make a new form with **Copy form** and use it next term.

## What is evaluated

- Courses registered or completed in a term that has a term evaluation set up. Withdrawn and dropped courses are left out.
- Courses whose **Course evaluation** setting is off are left out (practicums, internships and the like; [chapter 17](17-courses-and-curriculum.md)).
- A course can be evaluated only while the course evaluation window of its session is open for the student.

## Student screen

Portal **Course evaluation**: in the list of courses to evaluate, click **Evaluate**, answer the questions and submit. Every required question must be answered, and the submission cannot be changed. Each course is evaluated once.

**Grade viewing block** (a setting, on by default): during the window, a student with an unevaluated course in the term has that term's grades hidden on the portal grades screen and the grade report, with a notice. The grades show once the evaluation is finished or the window ends. Grades of other terms stay visible.

## Results

- Instructors: **Course evaluation results** under the portal **My teaching**. Opens only after the term's results are published.
- Staff: **Course evaluation > Responses by section** shows the response count and rate per section; **Results** opens the results screen. Detailed results are shown to staff only after publication as well (during the window, matching changes in the results with completion times could identify students).
- Results screen: responses/expected (response rate), mean per question and number of answers per point (reversed for reversed questions), overall mean, comments (in random order).
- A section with fewer responses than the minimum or a response rate below the minimum shows only the response count, not the results and comments.

## Settings

**Settings > Course evaluation**: hide a term's grades until its evaluation is done (default on), refuse the same answer to every question (default off), minimum responses (default 5) and minimum response rate (default 25%, the threshold of the legacy academic office screen) for a new term's evaluation.
