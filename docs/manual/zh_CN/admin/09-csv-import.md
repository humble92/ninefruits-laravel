# 9. CSV 导入

使用组织单位、专业项目、楼宇、教室、代码数据列表界面上方的 **导入 CSV** 按钮（`通过 CSV 导入基础数据` 权限）。学籍和教师列表也有同样的按钮（分别需要 `登记和编辑学籍`、`管理教师` 权限）。特别大的文件可由 IT 运维人员用 `reference:import` 命令以同样方式导入。

## 通用规则

- 第一行是列名（见下表）。编码为 UTF-8。在 Excel 中以"CSV UTF-8"格式保存。
- 已存在相同代码的行时会 **更新**。同一文件重复导入也不会产生重复。
- 按语言区分的列采用 `列名_语言` 格式：`name_en`、`name_ko`、`name_zh_CN`。新行必须有英文名称（`name_en`）。空白单元格不会删除已有翻译。
- 日期写成 `2026-03-01`，是/否写成 `1`/`0` 或 `true`/`false`。
- 导入在后台处理。完成后，管理界面右上角的通知中会显示结果；如有失败的行，可通过 **下载导入失败的记录** 获取附带原因的 CSV。修正后只需重新导入该文件即可。
- 如果界面上的列名不同，可以在导入窗口中手动匹配列。

## 列格式

| 种类 | 必填列 | 可选列 |
|---|---|---|
| 组织单位 | `code`、`type`（组织单位类型代码）、`name_en`、`effective_start_date` | `parent`（上级代码）、`name_ko` 等、`short_name_*`、`effective_end_date`、`sort_order` |
| 专业项目 | `code`、`org_unit`（单位代码）、`degree_level`、`name_en`、`effective_start_date` | `degree_title_*`、`standard_duration_years`、`effective_end_date`、`allows_second_major`、`allows_minor` |
| 楼宇 | `code`、`name_en` | `name_*`、`address`、`is_active` |
| 教室 | `building`（楼宇代码）、`code`（房间号） | `name_*`、`room_type`（用途代码）、`org_unit`、`capacity`、`is_schedulable`、`is_active` |
| 代码值 | `type`（列表代码）、`code`、`name_en` | `name_*`、`sort_order`、`is_active` |

`degree_level` 的值：`associate`、`bachelor`、`master`、`doctorate`、`certificate`、`non_degree`

组织单位的上级单位必须排在前面（按大学 → 学院 → 系的顺序排列）。

## 示例：组织单位

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

学生、教师（Phase 2）和课程（Phase 4）的导入随相应功能一起添加。

## 学生与教师

学籍按 `student_number`（学号）、教师按 `employee_number`（工号）查找并更新已有行。两种类型都同时使用人员列。

| 种类 | 新行必填列 | 可选列 |
|---|---|---|
| 学籍 | `student_number`、`program`（专业项目代码）、`admission_date`、`family_name` | `catalog_year`（为空时取入学年份）、`year_level`、`status`、`graduation_date`、`institutional_email` |
| 教师 | `employee_number`、`org_unit`（单位代码）、`employment_type`、`family_name` | `academic_title_*`、`hire_date`、`end_date` |
| 人员列（通用） | — | `given_name`、`name_ko` 等各语言姓名、`birth_date`、`gender`、`email`、`phone`、`national_id` |

- `status` 的值：`enrolled`、`on_leave`、`withdrawn`、`dismissed`、`graduated`（为毕业时 `graduation_date` 必填）。通过导入更改的状态视为 **资料更正**，不会生成学籍异动历史。实际的异动在界面上办理（[第 13 章](13-status-changes-and-advisors.md)）。
- `employment_type` 的值：`full_time`、`part_time`、`adjunct`、`visiting`、`emeritus`。`gender` 的值：`female`、`male`、`other`、`undisclosed`。
- 新学籍的 `national_id` 与已有人员相同时，学籍会挂到该人员名下（重新入学）。否则创建新人员。更新时空白单元格不会删除已有值。
- 导入的学生、教师没有账户。通过 [账户邀请](14-merge-and-invitations.md#账户邀请) 发送注册邀请。

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## 课程

使用课程列表上方的 **导入 CSV**（`管理课程、版本和先修课程` 权限）或 `reference:import courses`。一行即一个课程版本。

| 必填列 | 可选列 |
|---|---|
| `code`、`effective_start_date`、`title_en`、`credits`（新版本） | `org_unit`（新课程必填。管理单位代码）、`effective_end_date`、`title_*`、`lecture_hours`、`lab_hours`、`category`（课程类别代码）、`grading_basis`（`graded`、`pass_fail`）、`counts_toward_credit_limit` |

- 课程按 `code` 查找，版本按 `code` + `effective_start_date` 查找。同一文件重新导入时会更新。
- 修订历史可以放在一个文件中录入：将同一代码的行按期间逐行列出（在前一版本填写 `effective_end_date`）。
- 期间与其他版本重叠的行会失败。新课程行失败时，课程也不会被创建。
- 先修课程、替代课程、毕业要求在界面上录入（[第 17 章](17-courses-and-curriculum.md)）。

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## 选课限制

使用 **选课 > 选课限制** 列表上方的 **导入 CSV**（`设置和解除选课限制，批准规则例外` 权限）或 `reference:import registration_holds`。用于导入旧系统（YUSTIC）的 `SRDENY` 数据或财务部门的欠费名单。

| 必填列 | 可选列 |
|---|---|
| `student_number`、`hold_type`（`unregistered`、`financial`、`academic`、`administrative`、`other`）、`reason` | `starts_at`（默认：导入时刻）、`ends_at`、`blocks_registration`（默认 1）、`blocks_grades`、`blocks_certificates` |

- 每行创建一条新限制（来源 `import`）。同一文件导入两次会生成两条限制，因此重新导入前请先确认列表。
- 学号不存在的行会失败。解除在界面上连同原因一起办理（[第 20 章](20-registration.md)）。

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
