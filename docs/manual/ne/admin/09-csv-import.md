# 9. CSV आयात

संगठन एकाइ, कार्यक्रम, भवन, कोठा र कोड सूचीका स्क्रिनको माथिपट्टिको **CSV आयात गर्नुहोस्** बटन प्रयोग गर्नुहोस् (`CSV बाट सन्दर्भ डेटा आयात गर्ने` अनुमति)। विद्यार्थी अभिलेख र प्राध्यापकका सूचीमा पनि यही बटन हुन्छ (क्रमशः `विद्यार्थी अभिलेख बनाउने र सम्पादन गर्ने` र `प्राध्यापक व्यवस्थापन गर्ने` अनुमति)। धेरै ठूला फाइलहरू IT सञ्चालकले `reference:import` कमान्डले यसै गरी आयात गर्न सक्छन्।

## साझा नियमहरू

- पहिलो पङ्क्तिमा स्तम्भका नामहरू हुन्छन् (तलका तालिका)। इन्कोडिङ UTF-8 हो; Excel मा "CSV UTF-8" का रूपमा सुरक्षित गर्नुहोस्।
- कोड पहिल्यै भएको पङ्क्तिले त्यो अभिलेख **अद्यावधिक** गर्छ। एउटै फाइल फेरि आयात गर्दा दोहोरो बन्दैन।
- भाषाअनुसारका स्तम्भ `स्तम्भ_भाषा` ढाँचामा हुन्छन्: `name_en`, `name_ko`, `name_zh_CN`। नयाँ पङ्क्तिमा अङ्ग्रेजी नाम (`name_en`) अनिवार्य छ। खाली सेलले विद्यमान अनुवाद मेटाउँदैन।
- मिति `2026-03-01` ढाँचामा; हो/होइन `1`/`0` वा `true`/`false` का रूपमा लेखिन्छ।
- आयात पृष्ठभूमिमा चल्छ। सकिएपछि नतिजा प्रशासन प्यानलको माथि दायाँपट्टि सूचनाका रूपमा आउँछ; कुनै पङ्क्ति असफल भएमा, **असफल पङ्क्तिहरूको जानकारी डाउनलोड गर्नुहोस्** ले प्रत्येक पङ्क्तिको कारणसहित CSV दिन्छ। त्यसलाई सच्याएर त्यो फाइल मात्र फेरि आयात गर्नुहोस्।
- स्क्रिनका स्तम्भ नाम फरक भएमा, आयात संवादमा स्तम्भहरू हातैले मिलाउन सकिन्छ।

## स्तम्भको ढाँचा

| प्रकार | अनिवार्य स्तम्भ | वैकल्पिक स्तम्भ |
|---|---|---|
| संगठन एकाइ | `code`, `type` (संगठन एकाइको प्रकारको कोड), `name_en`, `effective_start_date` | `parent` (माथिल्लो एकाइको कोड), `name_ko` आदि, `short_name_*`, `effective_end_date`, `sort_order` |
| कार्यक्रम | `code`, `org_unit` (संगठन एकाइको कोड), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| भवन | `code`, `name_en` | `name_*`, `address`, `is_active` |
| कोठा | `building` (भवनको कोड), `code` (कोठा नम्बर) | `name_*`, `room_type` (कोठाको प्रकारको कोड), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| कोड मान | `type` (सूची कोड), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

`degree_level` का मानहरू: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

संगठन एकाइमा माथिल्ला एकाइहरू पहिले आउनुपर्छ (विश्वविद्यालय → सङ्काय → विभागको क्रममा मिलाउनुहोस्)।

## उदाहरण: संगठन एकाइ

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

विद्यार्थी र प्राध्यापक (चरण 2) तथा विषय (चरण 4) का आयात ती सुविधासँगै थपिन्छन्।

## विद्यार्थी र प्राध्यापक

विद्यार्थी अभिलेखहरू `student_number` द्वारा र प्राध्यापकहरू `employee_number` द्वारा मिलाइन्छन् र अद्यावधिक गरिन्छन्। दुवैले व्यक्तिका स्तम्भहरू साझा गर्छन्।

| प्रकार | नयाँ पङ्क्तिका अनिवार्य स्तम्भ | वैकल्पिक स्तम्भ |
|---|---|---|
| विद्यार्थी अभिलेख | `student_number`, `program` (कार्यक्रम कोड), `admission_date`, `family_name` | `catalog_year` (खाली भएमा भर्ना वर्ष), `year_level`, `status`, `graduation_date`, `institutional_email` |
| प्राध्यापक | `employee_number`, `org_unit` (संगठन एकाइको कोड), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| व्यक्तिका स्तम्भ (साझा) | — | `given_name`, `name_ko` जस्ता भाषाअनुसारका नाम, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- `status` का मानहरू: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (graduated का लागि `graduation_date` अनिवार्य)। आयातद्वारा परिवर्तन गरिएको स्थितिलाई **डेटा सुधार** मानिन्छ र अभिलेख स्थिति परिवर्तनको इतिहास बन्दैन। वास्तविक परिवर्तनहरू स्क्रिनमा अभिलेख गरिन्छन् ([अध्याय 13](13-status-changes-and-advisors.md))।
- `employment_type` का मानहरू: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`। `gender` का मानहरू: `female`, `male`, `other`, `undisclosed`।
- नयाँ विद्यार्थी अभिलेखको `national_id` विद्यमान व्यक्तिसँग मिलेमा, अभिलेख त्यही व्यक्तिमा जोडिन्छ (पुनर्भर्ना)। नमिलेमा नयाँ व्यक्ति बनाइन्छ। अद्यावधिक गर्दा खाली सेलले विद्यमान मान मेटाउँदैन।
- आयात गरिएका विद्यार्थी र प्राध्यापकको खाता हुँदैन। [खाता निमन्त्रणा](14-merge-and-invitations.md#खाता-निमन्त्रणा) बाट उनीहरूलाई निमन्त्रणा पठाउनुहोस्।

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## विषयहरू

विषय सूचीको माथिपट्टिको **CSV आयात गर्नुहोस्** (`विषय, संस्करण र पूर्वशर्त व्यवस्थापन गर्ने` अनुमति) वा `reference:import courses`। एउटा पङ्क्ति भनेको एउटा विषय संस्करण हो।

| अनिवार्य स्तम्भ | वैकल्पिक स्तम्भ |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (नयाँ संस्करण) | `org_unit` (नयाँ विषयका लागि अनिवार्य; व्यवस्थापन गर्ने संगठन एकाइको कोड), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (विषयको वर्गको कोड), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- विषयहरू `code` द्वारा, संस्करणहरू `code` + `effective_start_date` द्वारा मिलाइन्छन्। एउटै फाइल फेरि आयात गर्दा ती अद्यावधिक हुन्छन्।
- संशोधनको इतिहास एउटै फाइलबाट लोड गर्न सकिन्छ: एउटै कोडका पङ्क्तिहरू अवधिअनुसार सूचीबद्ध गर्नुहोस् (अघिल्लो संस्करणमा `effective_end_date` राख्नुहोस्)।
- अर्को संस्करणसँग अवधि खप्टिने पङ्क्ति असफल हुन्छ। नयाँ विषयको पङ्क्ति असफल भएमा विषय पनि बन्दैन।
- पूर्वशर्त, समकक्ष विषय र स्नातक आवश्यकता स्क्रिनमा प्रविष्ट गरिन्छन् ([अध्याय 17](17-courses-and-curriculum.md))।

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## दर्ता रोक

**विषय दर्ता > रोकहरू** सूचीको माथिपट्टिको **CSV आयात गर्नुहोस्** (`रोक लगाउने र हटाउने, नियम छुट दिने` अनुमति) वा `reference:import registration_holds`। पुरानो `SRDENY` डेटा वा आर्थिक प्रशासन कार्यालयको शुल्क नतिरेका विद्यार्थीको सूची लोड गर्न प्रयोग गरिन्छ।

| अनिवार्य स्तम्भ | वैकल्पिक स्तम्भ |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (पूर्वनिर्धारित: आयात गरिएको समय), `ends_at`, `blocks_registration` (पूर्वनिर्धारित 1), `blocks_grades`, `blocks_certificates` |

- हरेक पङ्क्तिले नयाँ रोक बनाउँछ (स्रोत `import`)। एउटै फाइल दुई पटक आयात गर्दा रोकहरू दुई पटक बन्छन्, त्यसैले फेरि आयात गर्नुअघि सूची जाँच्नुहोस्।
- अज्ञात विद्यार्थी नम्बर भएका पङ्क्ति असफल हुन्छन्। रोकहरू स्क्रिनमा कारणसहित हटाइन्छन् ([अध्याय 20](20-registration.md))।

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
