# 9. ການນຳເຂົ້າ CSV

ໃຊ້ປຸ່ມ **ນຳເຂົ້າ CSV** ຢູ່ດ້ານເທິງຂອງໜ້າລາຍການໜ່ວຍງານ, ຫຼັກສູດ, ອາຄານ, ຫ້ອງຮຽນ ແລະ ຂໍ້ມູນລະຫັດ (ສິດ `ນຳເຂົ້າຂໍ້ມູນອ້າງອີງຈາກ CSV`). ລາຍການບັນທຶກນັກສຶກສາ ແລະ ອາຈານກໍມີປຸ່ມດຽວກັນ (ສິດ `ສ້າງ ແລະ ແກ້ໄຂບັນທຶກນັກສຶກສາ` ແລະ `ຈັດການອາຈານ` ຕາມລຳດັບ). ໄຟລ໌ຂະໜາດໃຫຍ່ຫຼາຍ ຜູ້ດຳເນີນງານ IT ສາມາດນຳເຂົ້າດ້ວຍວິທີດຽວກັນໂດຍໃຊ້ຄຳສັ່ງ `reference:import`.

## ກົດລະບຽບທົ່ວໄປ

- ແຖວທຳອິດແມ່ນຊື່ຖັນ (ຕາຕະລາງຂ້າງລຸ່ມ). ການເຂົ້າລະຫັດແມ່ນ UTF-8. ໃນ Excel ໃຫ້ບັນທຶກເປັນ "CSV UTF-8".
- ຖ້າມີແຖວທີ່ມີລະຫັດດຽວກັນຢູ່ແລ້ວ ຈະ **ອັບເດດ**. ການນຳເຂົ້າໄຟລ໌ດຽວກັນຊ້ຳຈະບໍ່ເກີດຂໍ້ມູນຊ້ຳ.
- ຖັນຕາມພາສາມີຮູບແບບ `ຊື່ຖັນ_ພາສາ`: `name_en`, `name_ko`, `name_zh_CN`. ແຖວໃໝ່ຕ້ອງມີຊື່ພາສາອັງກິດ (`name_en`). ຊ່ອງວ່າງຈະບໍ່ລຶບຄຳແປທີ່ມີຢູ່ແລ້ວ.
- ວັນທີຂຽນເປັນ `2026-03-01`, ແມ່ນ/ບໍ່ແມ່ນ ຂຽນເປັນ `1`/`0` ຫຼື `true`/`false`.
- ການນຳເຂົ້າຈະຖືກປະມວນຜົນໃນເບື້ອງຫຼັງ. ເມື່ອສຳເລັດ ຜົນຈະມາທີ່ການແຈ້ງເຕືອນຢູ່ມຸມຂວາເທິງຂອງໜ້າບໍລິຫານ ແລະ ຖ້າມີແຖວທີ່ລົ້ມເຫຼວ ໃຫ້ກົດ **ດາວໂຫຼດແຖວທີ່ລົ້ມເຫຼວ** ເພື່ອຮັບໄຟລ໌ CSV ທີ່ມີເຫດຜົນຂຽນໄວ້. ແກ້ໄຂແລ້ວນຳເຂົ້າສະເພາະໄຟລ໌ນັ້ນຄືນໃໝ່ກໍພໍ.
- ຖ້າຊື່ຖັນແຕກຕ່າງຈາກໃນໜ້າຈໍ ສາມາດຈັບຄູ່ຖັນດ້ວຍຕົນເອງໄດ້ໃນໜ້າຕ່າງນຳເຂົ້າ.

## ຮູບແບບຖັນ

| ປະເພດ | ຖັນບັງຄັບ | ຖັນທາງເລືອກ |
|---|---|---|
| ໜ່ວຍງານ | `code`, `type` (ລະຫັດປະເພດໜ່ວຍງານ), `name_en`, `effective_start_date` | `parent` (ລະຫັດໜ່ວຍງານຂັ້ນເທິງ), `name_ko` ແລະ ອື່ນໆ, `short_name_*`, `effective_end_date`, `sort_order` |
| ຫຼັກສູດ | `code`, `org_unit` (ລະຫັດໜ່ວຍງານ), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| ອາຄານ | `code`, `name_en` | `name_*`, `address`, `is_active` |
| ຫ້ອງຮຽນ | `building` (ລະຫັດອາຄານ), `code` (ເລກຫ້ອງ) | `name_*`, `room_type` (ລະຫັດປະເພດຫ້ອງ), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| ຄ່າລະຫັດ | `type` (ລະຫັດລາຍການ), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

ຄ່າ `degree_level`: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

ໜ່ວຍງານຂັ້ນເທິງຕ້ອງປາກົດກ່ອນໜ່ວຍງານລູກ (ຈັດລຽງຕາມລຳດັບ ມະຫາວິທະຍາໄລ → ຄະນະ → ພາກວິຊາ).

## ຕົວຢ່າງ: ໜ່ວຍງານ

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

ການນຳເຂົ້ານັກສຶກສາ, ອາຈານ (Phase 2) ແລະ ວິຊາ (Phase 4) ຈະຖືກເພີ່ມພ້ອມກັບໜ້າທີ່ທີ່ກ່ຽວຂ້ອງ.

## ນັກສຶກສາ ແລະ ອາຈານ

ບັນທຶກນັກສຶກສາຄົ້ນຫາແຖວເດີມດ້ວຍ `student_number` (ລະຫັດນັກສຶກສາ) ແລະ ອາຈານດ້ວຍ `employee_number` (ລະຫັດພະນັກງານ) ເພື່ອອັບເດດ. ທັງສອງປະເພດໃຊ້ຖັນບຸກຄົນຮ່ວມກັນ.

| ປະເພດ | ຖັນບັງຄັບສຳລັບແຖວໃໝ່ | ຖັນທາງເລືອກ |
|---|---|---|
| ບັນທຶກນັກສຶກສາ | `student_number`, `program` (ລະຫັດຫຼັກສູດ), `admission_date`, `family_name` | `catalog_year` (ຖ້າວ່າງ ໃຊ້ປີຮັບເຂົ້າ), `year_level`, `status`, `graduation_date`, `institutional_email` |
| ອາຈານ | `employee_number`, `org_unit` (ລະຫັດໜ່ວຍງານ), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| ຖັນບຸກຄົນ (ຮ່ວມກັນ) | — | `given_name`, ຊື່ຕາມພາສາ ເຊັ່ນ `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- ຄ່າ `status`: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (ຖ້າຈົບການສຶກສາ ຕ້ອງມີ `graduation_date`). ສະຖານະທີ່ປ່ຽນດ້ວຍການນຳເຂົ້າຖືກຖືວ່າເປັນ **ການແກ້ໄຂຂໍ້ມູນ** ແລະ ຈະບໍ່ສ້າງປະຫວັດການປ່ຽນສະຖານະ. ການປ່ຽນສະຖານະຕົວຈິງໃຫ້ດຳເນີນການຈາກໜ້າຈໍ ([ບົດ 13](13-status-changes-and-advisors.md)).
- ຄ່າ `employment_type`: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. ຄ່າ `gender`: `female`, `male`, `other`, `undisclosed`.
- ຖ້າ `national_id` ຂອງບັນທຶກນັກສຶກສາໃໝ່ກົງກັບບຸກຄົນທີ່ມີຢູ່ແລ້ວ ບັນທຶກນັກສຶກສາຈະຖືກຕິດກັບບຸກຄົນນັ້ນ (ການເຂົ້າຮຽນຄືນ). ນອກນັ້ນຈະສ້າງບຸກຄົນໃໝ່. ເມື່ອອັບເດດ ຊ່ອງວ່າງຈະບໍ່ລຶບຄ່າເດີມ.
- ນັກສຶກສາ ແລະ ອາຈານທີ່ນຳເຂົ້າມາຈະບໍ່ມີບັນຊີ. ສົ່ງຄຳເຊີນສ້າງບັນຊີດ້ວຍ [ການເຊີນສ້າງບັນຊີ](14-merge-and-invitations.md#ການເຊີນສ້າງບັນຊີ).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## ວິຊາ

**ນຳເຂົ້າ CSV** ຢູ່ດ້ານເທິງຂອງລາຍການວິຊາ (ສິດ `ຈັດການວິຊາ, ສະບັບ ແລະ ວິຊາບັງຄັບກ່ອນ`) ຫຼື `reference:import courses`. ໜຶ່ງແຖວແມ່ນໜຶ່ງສະບັບວິຊາ.

| ຖັນບັງຄັບ | ຖັນທາງເລືອກ |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (ສະບັບໃໝ່) | `org_unit` (ບັງຄັບສຳລັບວິຊາໃໝ່. ລະຫັດໜ່ວຍງານທີ່ຮັບຜິດຊອບ), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (ລະຫັດປະເພດວິຊາ), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- ວິຊາຄົ້ນຫາດ້ວຍ `code`, ສະບັບຄົ້ນຫາດ້ວຍ `code` + `effective_start_date`. ຖ້ານຳເຂົ້າໄຟລ໌ດຽວກັນຊ້ຳ ຈະຖືກອັບເດດ.
- ສາມາດນຳເຂົ້າປະຫວັດການປັບປຸງໃນໄຟລ໌ດຽວໄດ້: ລຽງແຖວທີ່ມີລະຫັດດຽວກັນຕາມແຕ່ລະໄລຍະ (ໃສ່ `effective_end_date` ໃຫ້ສະບັບກ່ອນໜ້າ).
- ແຖວທີ່ໄລຍະເວລາຊ້ອນທັບກັບສະບັບອື່ນຈະລົ້ມເຫຼວ. ຖ້າແຖວວິຊາໃໝ່ລົ້ມເຫຼວ ວິຊາກໍຈະບໍ່ຖືກສ້າງ.
- ວິຊາບັງຄັບກ່ອນ, ວິຊາທົດແທນ ແລະ ເງື່ອນໄຂຈົບການສຶກສາ ປ້ອນຈາກໜ້າຈໍ ([ບົດ 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## ການລະງັບການລົງທະບຽນຮຽນ

**ນຳເຂົ້າ CSV** ຢູ່ດ້ານເທິງຂອງລາຍການ **ການລົງທະບຽນຮຽນ > ການລະງັບການລົງທະບຽນ ແລະ ຂໍ້ຍົກເວັ້ນ** (ສິດ `ຕັ້ງ ແລະ ປົດການລະງັບ, ອະນຸຍາດຂໍ້ຍົກເວັ້ນກົດລະບຽບ`) ຫຼື `reference:import registration_holds`. ໃຊ້ເມື່ອນຳເຂົ້າຂໍ້ມູນ `SRDENY` ຂອງລະບົບເກົ່າ (YUSTIC) ຫຼື ລາຍຊື່ຜູ້ຄ້າງຊຳລະຈາກພະແນກການເງິນ.

| ຖັນບັງຄັບ | ຖັນທາງເລືອກ |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (ຄ່າເລີ່ມຕົ້ນ: ເວລາທີ່ນຳເຂົ້າ), `ends_at`, `blocks_registration` (ຄ່າເລີ່ມຕົ້ນ 1), `blocks_grades`, `blocks_certificates` |

- ແຕ່ລະແຖວຈະສ້າງການລະງັບໃໝ່ (ແຫຼ່ງທີ່ມາ `import`). ຖ້ານຳເຂົ້າໄຟລ໌ດຽວກັນສອງຄັ້ງ ການລະງັບຈະຖືກສ້າງສອງຄັ້ງ ດັ່ງນັ້ນກ່ອນນຳເຂົ້າຄືນ ໃຫ້ກວດລາຍການກ່ອນ.
- ແຖວທີ່ມີລະຫັດນັກສຶກສາທີ່ບໍ່ມີຢູ່ຈະລົ້ມເຫຼວ. ການປົດການລະງັບດຳເນີນການຈາກໜ້າຈໍພ້ອມເຫດຜົນ ([ບົດ 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
