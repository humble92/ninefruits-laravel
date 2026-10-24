# 2. Users and roles

## Concepts

| Term | Meaning |
|---|---|
| Permission | The right to use one feature. Examples: `View users`, `Reset two-factor authentication` |
| Role | A bundle of permissions. Examples: registrar, department assistant |
| Role assignment | Giving a user a role with a scope and a period |
| Scope | Where the role applies: **All**, **Org unit** (college, department, etc.) or **Program** |
| Persona role | Applicant, student, alumni, instructor, advisor. Granted automatically from academic data; cannot be assigned by hand |

When responsibilities change, only the role configuration or the assignments change; the program itself does not. Every change is recorded in the audit log.

## Creating a user account

1. Go to **Users and access > Users** and press **New user**.
2. Enter the name, email and language, then save.
3. The user receives a link by email to set a password. Administrators never see the password.

Accounts are not deleted. To stop an account, for example when someone leaves, open the user's **Edit** page and turn off **Active**. An inactive account cannot sign in and loses every permission. Screens that were already signed in are signed out on their next request.

Safeguards when editing accounts

- You cannot deactivate your own account.
- You cannot edit an account that holds permissions you do not have (an administrator with higher privileges).
- Changing the email resets the email verification, ends every sign-in session of that user and records the security event `Email changed`.
- When a user changes their password, sessions on other devices and automatic sign-in (remember me) end, and the security event `Password changed` is recorded.

## Creating and editing roles

1. Go to **Users and access > Roles** and press **New role**, or **Edit** on a role.
2. The **Internal name** uses lowercase letters and underscores (for example `exam_office`). It is best not to change it after creation.
3. Enter the **Display name** per language. The English name is required. Clearing a language field removes the name in that language.
4. Under **Permissions**, tick the permissions per feature. On save, the added and removed permissions are written to the audit log.

| Role kind | Editing | Deleting |
|---|---|---|
| System role (system administrator) | Name only. Always has every permission | Not possible |
| Persona role | Display name and permissions. Not the internal name | Not possible |
| Regular role | Everything | Only when it has no active assignments |

## Assigning roles

1. Go to **Users and access > Users**, open the user and scroll to the **Role assignments** list below.
2. Press **Assign role** and enter the role, scope, start/end (optional) and **Reason**.
   - If the scope is an org unit or a program, choose it under **Scope target**. Choosing an org unit includes every unit below it.
   - Leave the start empty to apply immediately; leave the end empty for an open-ended assignment.
3. Assignments are never edited. To change one, use **End assignment** on the existing assignment (a reason is required) and create a new one. The history stays intact.

You cannot end your own assignments (separation of duties). Ask another administrator.

Rules that prevent privilege escalation

- You cannot assign a role to yourself.
- You cannot assign a role that contains permissions you do not have.
- The scope can only be chosen within the scope of your own `Assign and end roles` permission. For example, an administrator who holds it for the College of Engineering cannot assign the All scope or another college's scope.
- The `security:create-administrator` command that creates the first administrator is run by the server operator, so these rules do not apply to it.
