# 23. Certificates

Menu: **Certificates > Issued certificates**, **Certificate kinds**, **Settings > Certificate settings**. Certificates are issued with the **Issue certificate** button on the student record screen (**Students and staff > Student records > View**). Students download their own certificate PDFs in the portal under **Certificates**. Anyone can verify a certificate on the `/verify/{code}` page.

| Permission | What it allows |
|---|---|
| `Issue, re-render and revoke certificates` | Issue, render again, revoke, view the issued certificates (within the student record viewing scope) |
| `Manage certificate kinds, templates and settings` | Kinds and their templates per language, serial number pattern, signers |

Role defaults: Certificate Officer issues, Registrar has both, Academic Affairs manages kinds and templates.

## Differences from the legacy system

- **Register of issued certificates.** The legacy system (YUSTIC) printed straight from the screen (`aca3/cti_*`), and its register (`SRCERT`) existed only to generate numbers, so it did not show who issued a certificate or whether it was revoked. The new system records the serial number, verification code, staff member, purpose, number of copies, PDF file and checksum for every issue, and a revoked certificate keeps its row.
- **Verification.** The legacy had none. Every certificate carries a verification code and QR code, and a public page shows that it was issued, its kind, issue date and the masked holder (first and last letter of the family and given names, first 4 digits of the student number).
- **Templates.** The legacy had a separate PDF script per kind and language. The new system edits a template per language (title, body, footer, signer) for each kind on screen.
- **Blocks.** The legacy issued certificates regardless of the registration denial list. The new system does not issue to a student with a certificate block ([chapter 20](20-registration.md)).
- Fees are not recorded (the legacy only recorded the amount).
- There is no online request by students (porting plan 17.5). Staff issue certificates.

## Kinds and templates

**Certificate kinds**: code (used in the serial number), name (3 languages), audience (any student record / students / graduates), includes grades, includes photo, active, order. The Korean university profile seeds three kinds.

| Code | Kind | Audience | Note |
|---|---|---|---|
| `ENR` | Certificate of enrollment | Students (not graduated) | |
| `GRD` | Certificate of graduation | Graduates | Graduation date, degree number |
| `TRN` | Transcript | Any student record | Table of finalized grades by term, cumulative GPA and earned credits |

**Templates** (at the bottom of the kind edit screen): one per language. Title, body (paragraphs separated by a blank line), footer, signer. The body uses placeholders in curly braces.

| Placeholder | Value |
|---|---|
| `{institution}` | Institution name (in that language) |
| `{student_name}`, `{student_number}` | Name (in that language's form, otherwise the Latin spelling), student number |
| `{program}`, `{org_unit}` | Program, org unit |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Admission date, graduation date, degree number |
| `{status}`, `{year_level}` | Student status, year level |
| `{birth_date}`, `{gender}`, `{nationality}` | Date of birth, gender, nationality |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Issue date, serial number, purpose, number of copies |

Dates are printed in that language's format. A missing value (e.g. the graduation date) prints as blank. A table of personal facts (name, student number, date of birth, gender, nationality, program, admission date, then the graduation date for graduates or else status and year level) is added automatically above the body.

## Settings

**Settings > Certificate settings**

- **Serial number pattern**: default `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. `{SEQ:n}` is required. The sequence counts separately for every distinct prefix. `{TYPE}-{SEQ:6}` counts per kind across years, `{YYYY}{SEQ:5}` per year across kinds. Concurrent issues never collide (the counter is incremented atomically in the database).
- **Verification valid for (days)**: 0 means always. After the period the verification page answers "expired". The issue itself stays in the register.
- **Signer by language**: the name printed above the seal ("President, Ninefruits University"). A signer in the template takes precedence.

## Issuing

**Issue certificate** on the student record screen: kind, language, purpose, copies (1 to 20). On save the PDF opens in a new tab.

Issuing is refused when:

- the kind's audience does not match (a certificate of enrollment for a graduate, a certificate of graduation for a current student)
- the kind is inactive
- there is no template for that language
- a **certificate block** is in force for the student (a hold, [chapter 20](20-registration.md)); release it first
- the student record is outside the viewing scope

Issuing is recorded in the activity log (`certificate.issued`: student number, kind, language, serial number, copies, purpose).

## Issued certificates

**Certificates > Issued certificates**: issue date, serial number, kind and language, student number, name, purpose, copies, status, staff member. Filter by kind and status. The viewing scope is the same as for student records.

- **PDF**: opens the stored file. It is not rendered again, so it is exactly as issued.
- **Verification page**: opens the public page.
- **Render again**: renders a new PDF from the current data, e.g. after fixing a template, and replaces the file. The serial number and verification code stay; the checksum changes. Audit event `certificate.rerendered`. Same as the command `certificate:render`.
- **Revoke**: reason required. The row and file stay, the status becomes revoked, and the verification page answers "revoked". Audit event `certificate.revoked`. A revoked certificate is not restored; a new one is issued.

Register rows cannot be edited or deleted on screen.

## Verification

- The verification URL (`/verify/{code}`) and a QR code are printed at the bottom of the certificate. The code is a random 20-character string.
- The page opens without signing in and is limited to 30 requests per minute. It shows whether the certificate was issued, its kind, serial number, issue date, and the masked name and student number. Revoked, expired and unknown codes each get a different answer.
- To check a submitted PDF file itself, compare the checksum (SHA-256) in the register with the file's hash. The command `certificate:verify {code}` does this too.

## Student portal

**Certificates**: the certificates issued on the student's own record, with PDFs. Revoked ones are listed with their status, but the student cannot download a revoked certificate's PDF (the issuing staff can open it to check the record). Other students' files cannot be opened (enforced in the application and the database).

## Frequently asked questions

- **The name is spelled wrong.** Correct the person's name variant (in that language) and **Render again**. If the certificate was already handed in, revoke it and issue a new one.
- **I changed the template; what about certificates already issued?** They do not change. **Render again** if needed.
- **A serial number was skipped.** When an error occurs during issuing (e.g. the PDF service is down), the number is consumed and no register row may be left. Numbers are guaranteed to be unique, not consecutive.
