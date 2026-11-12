<?php

namespace Database\Seeders\Profiles;

use App\Models\Admission\Application;
use App\Models\Assessment\AssessmentComponent;
use App\Models\Assessment\AssessmentType;
use App\Models\Certificate\CertificateTemplate;
use App\Models\Certificate\CertificateType;
use App\Models\Curriculum\CourseCategory;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\Facility\Room;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Institution\LookupType;
use App\Models\Institution\LookupValue;
use App\Models\Term\TermType;
use App\Models\Term\TimeSlot;
use App\Support\Setup\SetupProfile;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Database\Seeders\Essential\LookupTypeSeeder;
use Illuminate\Database\Seeder;

/**
 * Typical Korean four-year university: spring/summer/fall/winter terms, general education
 * and major categories, a 4.5 grade scale, 50-minute periods from 09:00, common room
 * types, admission types and application documents. Idempotent; values administrators changed are kept.
 */
class KoreanUniversityProfileSeeder extends Seeder implements SetupProfile
{
    use SeedsTranslatedRows;

    public static function key(): string
    {
        return 'korean-university';
    }

    public static function label(): string
    {
        return 'Korean university';
    }

    public static function description(): string
    {
        return 'Spring/summer/fall/winter terms, 4.5 grade scale, course categories, periods from 09:00, room types, admission types and application documents';
    }

    public function run(): void
    {
        $this->call(LookupTypeSeeder::class);

        $this->termTypes();
        $this->courseCategories();
        $this->gradeScale();
        $this->evaluationForm();
        $this->certificateTypes();
        $this->assessmentTypes();
        $this->timeSlots();
        $this->roomTypes();
        $this->lookupValues(Application::ADMISSION_TYPE_LOOKUP, [
            'freshman' => ['en' => 'Freshman admission', 'ko' => '신입학', 'zh_CN' => '新生入学', 'lo' => 'ການຮັບເຂົ້າຮຽນໃໝ່', 'de' => 'Zulassung zum ersten Fachsemester', 'fil' => 'Admisyon ng freshman', 'fr' => 'Admission en première année', 'ja' => '一般入学', 'mn' => 'Шинэ элсэлт', 'ms' => 'Kemasukan pelajar baharu', 'ne' => 'नयाँ भर्ना', 'ru' => 'Поступление на первый курс', 'th' => 'รับเข้าศึกษาใหม่', 'tr' => 'Yeni öğrenci girişi', 'uz' => 'Birinchi kursga qabul', 'vi' => 'Tuyển sinh năm thứ nhất'],
            'transfer' => ['en' => 'Transfer admission', 'ko' => '편입학', 'zh_CN' => '插班入学', 'lo' => 'ການຮັບຍ້າຍເຂົ້າຮຽນ', 'de' => 'Zulassung zum höheren Fachsemester', 'fil' => 'Admisyon ng transferee', 'fr' => 'Admission par transfert', 'ja' => '編入学', 'mn' => 'Шилжин элсэлт', 'ms' => 'Kemasukan pindahan', 'ne' => 'स्थानान्तरण भर्ना', 'ru' => 'Поступление в порядке перевода', 'th' => 'รับโอนเข้าศึกษา', 'tr' => 'Yatay geçiş', 'uz' => 'Oʻqishni koʻchirish orqali qabul', 'vi' => 'Tuyển sinh chuyển tiếp'],
            'readmission' => ['en' => 'Readmission', 'ko' => '재입학', 'zh_CN' => '重新入学', 'lo' => 'ການຮັບເຂົ້າຮຽນຄືນ', 'de' => 'Wiedereinschreibung', 'fil' => 'Muling pagtanggap', 'fr' => 'Réadmission', 'ja' => '再入学', 'mn' => 'Дахин элсэх', 'ms' => 'Kemasukan semula', 'ne' => 'पुनर्भर्ना', 'ru' => 'Восстановление', 'th' => 'คืนสภาพนักศึกษา', 'tr' => 'Yeniden kayıt', 'uz' => 'Oʻqishga qayta tiklash', 'vi' => 'Tiếp nhận học lại'],
            'international' => ['en' => 'International student admission', 'ko' => '외국인 특별전형', 'zh_CN' => '外国留学生入学', 'lo' => 'ການຮັບນັກສຶກສາຕ່າງປະເທດ', 'de' => 'Zulassung internationaler Studierender', 'fil' => 'Admisyon ng dayuhang mag-aaral', 'fr' => 'Admission des étudiants internationaux', 'ja' => '外国人留学生入試', 'mn' => 'Гадаад оюутны элсэлт', 'ms' => 'Kemasukan pelajar antarabangsa', 'ne' => 'अन्तर्राष्ट्रिय विद्यार्थी भर्ना', 'ru' => 'Приём иностранных граждан', 'th' => 'รับนักศึกษาต่างชาติ', 'tr' => 'Uluslararası öğrenci kabulü', 'uz' => 'Xorijiy talabalar qabuli', 'vi' => 'Tuyển sinh sinh viên quốc tế'],
        ]);
        $this->lookupValues(Application::DOCUMENT_TYPE_LOOKUP, [
            'diploma' => ['en' => 'Diploma or certificate of expected graduation', 'ko' => '졸업(예정)증명서', 'zh_CN' => '毕业（预计毕业）证明', 'lo' => 'ໃບຢັ້ງຢືນການຈົບການສຶກສາ (ຫຼື ຄາດວ່າຈະຈົບ)', 'de' => 'Abschlusszeugnis oder Bescheinigung über den voraussichtlichen Abschluss', 'fil' => 'Diploma o sertipiko ng inaasahang pagtatapos', 'fr' => 'Diplôme ou attestation de réussite prévisionnelle', 'ja' => '卒業証明書または卒業見込証明書', 'mn' => 'Диплом эсвэл төгсөх тухай тодорхойлолт', 'ms' => 'Sijil tamat pengajian atau surat pengesahan jangkaan tamat pengajian', 'ne' => 'उत्तीर्ण प्रमाणपत्र वा उत्तीर्ण हुने सम्भावित प्रमाणपत्र', 'ru' => 'Документ об образовании или справка о предстоящем окончании', 'th' => 'สำเนาปริญญาบัตรหรือหนังสือรับรองว่าจะสำเร็จการศึกษา', 'tr' => 'Diploma veya mezuniyet durum belgesi', 'uz' => 'Diplom yoki bitirish arafasidagi maʼlumotnoma', 'vi' => 'Bằng tốt nghiệp hoặc giấy chứng nhận tốt nghiệp tạm thời'],
            'transcript' => ['en' => 'Transcript', 'ko' => '성적증명서', 'zh_CN' => '成绩证明', 'lo' => 'ໃບຄະແນນ', 'de' => 'Notenübersicht', 'fil' => 'Transcript of Records', 'fr' => 'Relevé de notes', 'ja' => '成績証明書', 'mn' => 'Дүнгийн хуудас', 'ms' => 'Transkrip', 'ne' => 'ट्रान्सक्रिप्ट', 'ru' => 'Академическая справка', 'th' => 'ใบแสดงผลการเรียน', 'tr' => 'Transkript', 'uz' => 'Akademik maʼlumotnoma', 'vi' => 'Bảng điểm'],
            'identity' => ['en' => 'Copy of ID or passport', 'ko' => '신분증 또는 여권 사본', 'zh_CN' => '身份证或护照复印件', 'lo' => 'ສຳເນົາບັດປະຈຳຕົວ ຫຼື ໜັງສືຜ່ານແດນ', 'de' => 'Kopie des Personalausweises oder Reisepasses', 'fil' => 'Kopya ng ID o pasaporte', 'fr' => 'Copie de la pièce d’identité ou du passeport', 'ja' => '身分証明書または旅券の写し', 'mn' => 'Иргэний үнэмлэх эсвэл паспортын хуулбар', 'ms' => 'Salinan kad pengenalan atau pasport', 'ne' => 'परिचयपत्र वा राहदानीको प्रतिलिपि', 'ru' => 'Копия удостоверения личности или паспорта', 'th' => 'สำเนาบัตรประจำตัวประชาชนหรือหนังสือเดินทาง', 'tr' => 'Kimlik kartı veya pasaport fotokopisi', 'uz' => 'Shaxsiy guvohnoma yoki pasport nusxasi', 'vi' => 'Bản sao căn cước hoặc hộ chiếu'],
            'language_test' => ['en' => 'Language test score', 'ko' => '어학 성적', 'zh_CN' => '语言成绩', 'lo' => 'ຄະແນນສອບພາສາ', 'de' => 'Ergebnis eines Sprachtests', 'fil' => 'Iskor sa pagsusulit sa wika', 'fr' => 'Résultat de test de langue', 'ja' => '語学試験の成績', 'mn' => 'Хэлний шалгалтын дүн', 'ms' => 'Keputusan ujian bahasa', 'ne' => 'भाषा परीक्षाको अङ्क', 'ru' => 'Результат языкового теста', 'th' => 'ผลสอบวัดระดับภาษา', 'tr' => 'Dil sınavı puanı', 'uz' => 'Til sinovi natijasi', 'vi' => 'Chứng chỉ ngoại ngữ'],
        ]);
    }

    private function termTypes(): void
    {
        $types = [
            'spring' => [10, true, ['en' => 'Spring term', 'ko' => '1학기', 'zh_CN' => '春季学期', 'lo' => 'ພາກຮຽນທີ 1', 'de' => 'Sommersemester', 'fil' => 'Unang semestre', 'fr' => 'Semestre de printemps', 'ja' => '春学期', 'mn' => 'Хаврын улирал', 'ms' => 'Semester 1', 'ne' => 'पहिलो सेमेस्टर', 'ru' => 'Весенний семестр', 'th' => 'ภาคการศึกษาที่ 1', 'tr' => 'Bahar dönemi', 'uz' => 'Bahorgi semestr', 'vi' => 'Học kỳ 1']],
            'summer' => [20, false, ['en' => 'Summer session', 'ko' => '여름 계절학기', 'zh_CN' => '夏季学期', 'lo' => 'ພາກຮຽນລະດູຮ້ອນ', 'de' => 'Sommerkurse', 'fil' => 'Summer term', 'fr' => 'Session d’été', 'ja' => '夏期集中授業期間', 'mn' => 'Зуны улирал', 'ms' => 'Semester pendek (musim panas)', 'ne' => 'ग्रीष्मकालीन सत्र', 'ru' => 'Летний семестр', 'th' => 'ภาคฤดูร้อน', 'tr' => 'Yaz okulu', 'uz' => 'Yozgi semestr', 'vi' => 'Học kỳ hè']],
            'fall' => [30, true, ['en' => 'Fall term', 'ko' => '2학기', 'zh_CN' => '秋季学期', 'lo' => 'ພາກຮຽນທີ 2', 'de' => 'Wintersemester', 'fil' => 'Ikalawang semestre', 'fr' => 'Semestre d’automne', 'ja' => '秋学期', 'mn' => 'Намрын улирал', 'ms' => 'Semester 2', 'ne' => 'दोस्रो सेमेस्टर', 'ru' => 'Осенний семестр', 'th' => 'ภาคการศึกษาที่ 2', 'tr' => 'Güz dönemi', 'uz' => 'Kuzgi semestr', 'vi' => 'Học kỳ 2']],
            'winter' => [40, false, ['en' => 'Winter session', 'ko' => '겨울 계절학기', 'zh_CN' => '冬季学期', 'lo' => 'ພາກຮຽນລະດູໜາວ', 'de' => 'Winterkurse', 'fil' => 'Winter term', 'fr' => 'Session d’hiver', 'ja' => '冬期集中授業期間', 'mn' => 'Өвлийн улирал', 'ms' => 'Semester pendek (musim sejuk)', 'ne' => 'शीतकालीन सत्र', 'ru' => 'Зимний семестр', 'th' => 'ภาคฤดูหนาว', 'tr' => 'Kış okulu', 'uz' => 'Qishki semestr', 'vi' => 'Học kỳ đông']],
        ];

        foreach ($types as $code => [$sortOrder, $isRegular, $name]) {
            $this->seedRow(TermType::class, ['code' => $code], ['name' => $name], ['sort_order' => $sortOrder, 'is_regular' => $isRegular]);
        }
    }

    private function courseCategories(): void
    {
        $groups = [
            'GE' => [10, ['en' => 'General education', 'ko' => '교양', 'zh_CN' => '通识教育', 'lo' => 'ວິຊາສຶກສາທົ່ວໄປ', 'de' => 'Studium generale', 'fil' => 'General education', 'fr' => 'Culture générale', 'ja' => '教養科目', 'mn' => 'Ерөнхий суурь', 'ms' => 'Pendidikan umum', 'ne' => 'सामान्य शिक्षा', 'ru' => 'Общеобразовательные дисциплины', 'th' => 'หมวดวิชาศึกษาทั่วไป', 'tr' => 'Genel kültür', 'uz' => 'Umumtaʼlim fanlari', 'vi' => 'Giáo dục đại cương'], [
                'GE_REQ' => ['en' => 'General education required', 'ko' => '교양필수', 'zh_CN' => '通识必修', 'lo' => 'ວິຊາສຶກສາທົ່ວໄປບັງຄັບ', 'de' => 'Studium generale (Pflicht)', 'fil' => 'General education na kinakailangan', 'fr' => 'Culture générale obligatoire', 'ja' => '教養必修', 'mn' => 'Ерөнхий суурь (заавал)', 'ms' => 'Pendidikan umum wajib', 'ne' => 'सामान्य शिक्षा अनिवार्य', 'ru' => 'Обязательные общеобразовательные дисциплины', 'th' => 'วิชาศึกษาทั่วไปบังคับ', 'tr' => 'Zorunlu genel kültür', 'uz' => 'Umumtaʼlim majburiy fanlari', 'vi' => 'Đại cương bắt buộc'],
                'GE_ELEC' => ['en' => 'General education elective', 'ko' => '교양선택', 'zh_CN' => '通识选修', 'lo' => 'ວິຊາສຶກສາທົ່ວໄປເລືອກ', 'de' => 'Studium generale (Wahl)', 'fil' => 'General education na elective', 'fr' => 'Culture générale optionnelle', 'ja' => '教養選択', 'mn' => 'Ерөнхий суурь (сонгон)', 'ms' => 'Pendidikan umum elektif', 'ne' => 'सामान्य शिक्षा ऐच्छिक', 'ru' => 'Общеобразовательные дисциплины по выбору', 'th' => 'วิชาศึกษาทั่วไปเลือก', 'tr' => 'Seçmeli genel kültür', 'uz' => 'Umumtaʼlim tanlov fanlari', 'vi' => 'Đại cương tự chọn'],
            ]],
            'MAJ' => [20, ['en' => 'Major', 'ko' => '전공', 'zh_CN' => '专业', 'lo' => 'ວິຊາສາຂາ', 'de' => 'Studienfach', 'fil' => 'Major', 'fr' => 'Spécialité', 'ja' => '専攻', 'mn' => 'Мэргэжил', 'ms' => 'Major', 'ne' => 'मुख्य विषय', 'ru' => 'Специальность', 'th' => 'สาขาวิชา', 'tr' => 'Ana dal', 'uz' => 'Mutaxassislik', 'vi' => 'Ngành'], [
                'MAJ_FND' => ['en' => 'Major foundation', 'ko' => '전공기초', 'zh_CN' => '专业基础', 'lo' => 'ວິຊາສາຂາພື້ນຖານ', 'de' => 'Grundlagen des Fachs', 'fil' => 'Batayang asignatura sa major', 'fr' => 'Fondamentaux de la spécialité', 'ja' => '専攻基礎', 'mn' => 'Мэргэжлийн суурь', 'ms' => 'Asas program', 'ne' => 'मुख्य विषय आधारभूत', 'ru' => 'Базовые дисциплины специальности', 'th' => 'วิชาแกน', 'tr' => 'Alan temel', 'uz' => 'Mutaxassislik asos fanlari', 'vi' => 'Cơ sở ngành'],
                'MAJ_REQ' => ['en' => 'Major required', 'ko' => '전공필수', 'zh_CN' => '专业必修', 'lo' => 'ວິຊາສາຂາບັງຄັບ', 'de' => 'Pflichtbereich des Fachs', 'fil' => 'Kinakailangang asignatura sa major', 'fr' => 'Spécialité obligatoire', 'ja' => '専攻必修', 'mn' => 'Мэргэжлийн (заавал)', 'ms' => 'Teras program', 'ne' => 'मुख्य विषय अनिवार्य', 'ru' => 'Обязательные дисциплины специальности', 'th' => 'วิชาเอกบังคับ', 'tr' => 'Alan zorunlu', 'uz' => 'Mutaxassislik majburiy fanlari', 'vi' => 'Chuyên ngành bắt buộc'],
                'MAJ_ELEC' => ['en' => 'Major elective', 'ko' => '전공선택', 'zh_CN' => '专业选修', 'lo' => 'ວິຊາສາຂາເລືອກ', 'de' => 'Wahlpflichtbereich des Fachs', 'fil' => 'Elective sa major', 'fr' => 'Spécialité optionnelle', 'ja' => '専攻選択', 'mn' => 'Мэргэжлийн (сонгон)', 'ms' => 'Elektif program', 'ne' => 'मुख्य विषय ऐच्छिक', 'ru' => 'Дисциплины специальности по выбору', 'th' => 'วิชาเอกเลือก', 'tr' => 'Alan seçmeli', 'uz' => 'Mutaxassislik tanlov fanlari', 'vi' => 'Chuyên ngành tự chọn'],
            ]],
            'FREE' => [30, ['en' => 'Free elective', 'ko' => '일반선택', 'zh_CN' => '任意选修', 'lo' => 'ວິຊາເລືອກເສລີ', 'de' => 'Freier Wahlbereich', 'fil' => 'Free elective', 'fr' => 'Option libre', 'ja' => '自由選択', 'mn' => 'Чөлөөт сонгон', 'ms' => 'Elektif bebas', 'ne' => 'स्वतन्त्र ऐच्छिक', 'ru' => 'Факультативные дисциплины', 'th' => 'วิชาเลือกเสรี', 'tr' => 'Serbest seçmeli', 'uz' => 'Erkin tanlov fanlari', 'vi' => 'Tự chọn tự do'], []],
        ];

        foreach ($groups as $code => [$sortOrder, $name, $children]) {
            $parent = $this->seedRow(CourseCategory::class, ['code' => $code], ['name' => $name], ['sort_order' => $sortOrder]);
            $childOrder = $sortOrder;

            foreach ($children as $childCode => $childName) {
                $this->seedRow(CourseCategory::class, ['code' => $childCode], ['name' => $childName], ['parent_id' => $parent->id, 'sort_order' => ++$childOrder]);
            }
        }
    }

    /**
     * A standard course evaluation: five areas of three questions on a five-point scale
     * (the legacy areas), and one comment. Created once; admin edits are kept.
     */
    private function evaluationForm(): void
    {
        if (EvaluationForm::query()->where('code', 'STANDARD')->exists()) {
            return;
        }

        $form = EvaluationForm::query()->create([
            'code' => 'STANDARD',
            'name' => ['en' => 'Standard course evaluation', 'ko' => '표준 강의평가', 'zh_CN' => '标准课程评价', 'lo' => 'ການປະເມີນລາຍວິຊາມາດຕະຖານ', 'de' => 'Standard-Lehrevaluation', 'fil' => 'Karaniwang ebalwasyon ng asignatura', 'fr' => 'Évaluation standard des enseignements', 'ja' => '標準授業評価', 'mn' => 'Хичээлийн үнэлгээний стандарт маягт', 'ms' => 'Penilaian kursus standard', 'ne' => 'मानक विषय मूल्याङ्कन', 'ru' => 'Стандартная анкета по дисциплине', 'th' => 'แบบประเมินรายวิชามาตรฐาน', 'tr' => 'Standart ders değerlendirme', 'uz' => 'Standart fan soʻrovnomasi', 'vi' => 'Phiếu đánh giá học phần chuẩn'],
            'description' => [
                'en' => 'Your answers are anonymous: instructors see only totals, after the results are published, and only when enough students answered.',
                'ko' => '응답은 익명입니다. 교원은 결과 공개 후, 충분한 학생이 응답한 강좌의 집계만 봅니다.',
                'zh_CN' => '回答为匿名。教师只能在结果公布后、回答人数足够时看到汇总结果。',
                'lo' => 'ຄຳຕອບຂອງທ່ານເປັນຄວາມລັບ: ອາຈານຈະເຫັນພຽງຜົນລວມຫຼັງຈາກປະກາດຜົນແລ້ວ ແລະ ສະເພາະເມື່ອມີນັກສຶກສາຕອບພຽງພໍເທົ່ານັ້ນ.',
                'de' => 'Ihre Antworten sind anonym: Lehrpersonen sehen nur Gesamtergebnisse, erst nach der Veröffentlichung und nur, wenn genügend Studierende geantwortet haben.',
                'fil' => 'Anonymous ang iyong mga sagot: mga kabuuan lamang ang makikita ng mga instruktor, pagkatapos mailathala ang mga resulta, at kapag sapat lamang ang bilang ng mag-aaral na sumagot.',
                'fr' => 'Vos réponses sont anonymes : les enseignants ne voient que les totaux, après la publication des résultats, et seulement si suffisamment d’étudiants ont répondu.',
                'ja' => '回答は匿名です。教員が確認できるのは集計結果のみで、結果公開後、かつ十分な数の学生が回答した場合に限られます。',
                'mn' => 'Таны хариулт нэргүй: багш нар үр дүн нийтлэгдсэний дараа, хангалттай оюутан хариулсан үед л зөвхөн нэгтгэлийг харна.',
                'ms' => 'Jawapan anda tanpa nama: pensyarah hanya melihat jumlah keseluruhan, selepas keputusan diterbitkan, dan hanya apabila cukup ramai pelajar menjawab.',
                'ne' => 'तपाईंका उत्तरहरू गोप्य रहन्छन्: प्राध्यापकले नतिजा प्रकाशित भएपछि, र पर्याप्त विद्यार्थीले उत्तर दिएमा मात्र, जम्मा मात्र देख्छन्।',
                'ru' => 'Ваши ответы анонимны: преподаватели видят только сводные данные, только после публикации результатов и только при достаточном числе ответивших студентов.',
                'th' => 'คำตอบของท่านไม่ระบุตัวตน อาจารย์ผู้สอนจะเห็นเฉพาะผลรวมหลังประกาศผลแล้ว และเฉพาะเมื่อมีนักศึกษาตอบเพียงพอเท่านั้น',
                'tr' => 'Yanıtlarınız anonimdir: öğretim elemanları yalnızca toplamları, sonuçlar yayımlandıktan sonra ve yeterli sayıda öğrenci yanıtladıysa görür.',
                'uz' => 'Javoblaringiz anonim: oʻqituvchilar natijalar eʼlon qilingandan keyin va faqat yetarlicha talaba javob berganda umumiy koʻrsatkichlarni koʻradi.',
                'vi' => 'Câu trả lời của bạn được ẩn danh: giảng viên chỉ xem được số liệu tổng hợp, sau khi kết quả được công bố và chỉ khi có đủ sinh viên trả lời.',
            ],
            'scale_points' => 5,
            'scale_low_label' => ['en' => 'Strongly disagree', 'ko' => '전혀 그렇지 않다', 'zh_CN' => '完全不同意', 'lo' => 'ບໍ່ເຫັນດີຢ່າງຍິ່ງ', 'de' => 'Trifft überhaupt nicht zu', 'fil' => 'Lubos na hindi sang-ayon', 'fr' => 'Pas du tout d’accord', 'ja' => 'まったくそう思わない', 'mn' => 'Огт санал нийлэхгүй', 'ms' => 'Sangat tidak setuju', 'ne' => 'पूर्ण असहमत', 'ru' => 'Совершенно не согласен', 'th' => 'ไม่เห็นด้วยอย่างยิ่ง', 'tr' => 'Kesinlikle katılmıyorum', 'uz' => 'Mutlaqo qoʻshilmayman', 'vi' => 'Hoàn toàn không đồng ý'],
            'scale_high_label' => ['en' => 'Strongly agree', 'ko' => '매우 그렇다', 'zh_CN' => '非常同意', 'lo' => 'ເຫັນດີຢ່າງຍິ່ງ', 'de' => 'Trifft voll zu', 'fil' => 'Lubos na sang-ayon', 'fr' => 'Tout à fait d’accord', 'ja' => 'とてもそう思う', 'mn' => 'Бүрэн санал нийлнэ', 'ms' => 'Sangat setuju', 'ne' => 'पूर्ण सहमत', 'ru' => 'Полностью согласен', 'th' => 'เห็นด้วยอย่างยิ่ง', 'tr' => 'Kesinlikle katılıyorum', 'uz' => 'Toʻliq qoʻshilaman', 'vi' => 'Hoàn toàn đồng ý'],
        ]);

        $areas = [
            [['en' => 'Preparation', 'ko' => '강의 준비', 'zh_CN' => '课程准备', 'lo' => 'ການກະກຽມການສອນ', 'de' => 'Vorbereitung', 'fil' => 'Paghahanda', 'fr' => 'Préparation', 'ja' => '授業の準備', 'mn' => 'Бэлтгэл', 'ms' => 'Persediaan', 'ne' => 'तयारी', 'ru' => 'Подготовка к занятиям', 'th' => 'การเตรียมการสอน', 'tr' => 'Ders hazırlığı', 'uz' => 'Darsga tayyorgarlik', 'vi' => 'Chuẩn bị giảng dạy'], [
                ['en' => 'The syllabus explained the goals, content and grading of the course.', 'ko' => '강의계획서에 수업 목표, 내용, 평가 방법이 잘 안내되었다.', 'zh_CN' => '教学大纲清楚说明了课程目标、内容和评分方法。', 'lo' => 'ຫຼັກສູດການສອນໄດ້ອະທິບາຍເປົ້າໝາຍ, ເນື້ອໃນ ແລະ ວິທີການໃຫ້ຄະແນນຂອງລາຍວິຊາຢ່າງຈະແຈ້ງ.', 'de' => 'Der Kursplan erläuterte Ziele, Inhalte und Bewertung der Lehrveranstaltung.', 'fil' => 'Ipinaliwanag ng syllabus ang mga layunin, nilalaman at pagmamarka ng asignatura.', 'fr' => 'Le syllabus présentait les objectifs, le contenu et les modalités d’évaluation du cours.', 'ja' => 'シラバスで授業の目標、内容、成績評価の方法が説明されていた。', 'mn' => 'Силлабуст хичээлийн зорилго, агуулга, үнэлгээг тодорхой тайлбарласан.', 'ms' => 'Rangka kursus menerangkan matlamat, kandungan dan penggredan kursus.', 'ne' => 'पाठ्यविवरणले विषयको उद्देश्य, विषयवस्तु र ग्रेडिङ स्पष्ट गरेको थियो।', 'ru' => 'В рабочей программе были разъяснены цели, содержание и порядок оценивания по дисциплине.', 'th' => 'ประมวลรายวิชาอธิบายวัตถุประสงค์ เนื้อหา และการวัดผลของรายวิชาไว้อย่างชัดเจน', 'tr' => 'Ders izlencesi dersin amaçlarını, içeriğini ve notlandırmasını açıkladı.', 'uz' => 'Sillabusda fanning maqsadlari, mazmuni va baholash tartibi tushuntirilgan.', 'vi' => 'Đề cương học phần đã nêu rõ mục tiêu, nội dung và cách đánh giá của học phần.'],
                ['en' => 'Classes were well prepared.', 'ko' => '수업이 충실히 준비되었다.', 'zh_CN' => '课程准备充分。', 'lo' => 'ການສອນໄດ້ກະກຽມມາເປັນຢ່າງດີ.', 'de' => 'Die Sitzungen waren gut vorbereitet.', 'fil' => 'Mahusay na inihanda ang mga klase.', 'fr' => 'Les cours étaient bien préparés.', 'ja' => '授業は十分に準備されていた。', 'mn' => 'Хичээл сайн бэлтгэлтэй байсан.', 'ms' => 'Kelas disediakan dengan baik.', 'ne' => 'कक्षाहरू राम्ररी तयार गरिएका थिए।', 'ru' => 'Занятия были хорошо подготовлены.', 'th' => 'การสอนมีการเตรียมการมาเป็นอย่างดี', 'tr' => 'Dersler iyi hazırlanmıştı.', 'uz' => 'Darslar yaxshi tayyorlangan edi.', 'vi' => 'Các buổi học được chuẩn bị chu đáo.'],
                ['en' => 'Classes started and ended on time.', 'ko' => '수업이 정해진 시간에 시작하고 끝났다.', 'zh_CN' => '课程按时开始和结束。', 'lo' => 'ການສອນເລີ່ມ ແລະ ສິ້ນສຸດຕາມເວລາທີ່ກຳນົດ.', 'de' => 'Die Sitzungen begannen und endeten pünktlich.', 'fil' => 'Nagsimula at natapos sa oras ang mga klase.', 'fr' => 'Les cours commençaient et finissaient à l’heure.', 'ja' => '授業は定刻に始まり、定刻に終わった。', 'mn' => 'Хичээл цагтаа эхэлж, цагтаа дууссан.', 'ms' => 'Kelas bermula dan tamat tepat pada masanya.', 'ne' => 'कक्षाहरू समयमै सुरु र अन्त्य भए।', 'ru' => 'Занятия начинались и заканчивались вовремя.', 'th' => 'การสอนเริ่มและเลิกตรงตามเวลา', 'tr' => 'Dersler zamanında başladı ve bitti.', 'uz' => 'Darslar oʻz vaqtida boshlanib, oʻz vaqtida tugadi.', 'vi' => 'Các buổi học bắt đầu và kết thúc đúng giờ.'],
            ]],
            [['en' => 'Teaching', 'ko' => '강의 방법', 'zh_CN' => '教学方法', 'lo' => 'ວິທີການສອນ', 'de' => 'Lehre', 'fil' => 'Pagtuturo', 'fr' => 'Pédagogie', 'ja' => '授業の方法', 'mn' => 'Заах арга', 'ms' => 'Pengajaran', 'ne' => 'अध्यापन', 'ru' => 'Методика преподавания', 'th' => 'วิธีการสอน', 'tr' => 'Öğretim yöntemi', 'uz' => 'Oʻqitish usuli', 'vi' => 'Phương pháp giảng dạy'], [
                ['en' => 'Explanations were clear and easy to follow.', 'ko' => '설명이 명확하고 이해하기 쉬웠다.', 'zh_CN' => '讲解清楚易懂。', 'lo' => 'ການອະທິບາຍຈະແຈ້ງ ແລະ ເຂົ້າໃຈງ່າຍ.', 'de' => 'Die Erklärungen waren klar und gut nachvollziehbar.', 'fil' => 'Malinaw at madaling sundan ang mga paliwanag.', 'fr' => 'Les explications étaient claires et faciles à suivre.', 'ja' => '説明は明確でわかりやすかった。', 'mn' => 'Тайлбар тодорхой, ойлгомжтой байсан.', 'ms' => 'Penerangan jelas dan mudah difahami.', 'ne' => 'व्याख्याहरू स्पष्ट र बुझ्न सजिला थिए।', 'ru' => 'Объяснения были ясными и понятными.', 'th' => 'การอธิบายชัดเจนและเข้าใจง่าย', 'tr' => 'Açıklamalar net ve kolay anlaşılırdı.', 'uz' => 'Tushuntirishlar aniq va oson tushunarli edi.', 'vi' => 'Giảng viên giải thích rõ ràng, dễ hiểu.'],
                ['en' => 'Students could ask questions and take part.', 'ko' => '질문과 참여의 기회가 충분했다.', 'zh_CN' => '学生有充分的提问和参与机会。', 'lo' => 'ນັກສຶກສາມີໂອກາດຖາມ ແລະ ມີສ່ວນຮ່ວມຢ່າງພຽງພໍ.', 'de' => 'Die Studierenden konnten Fragen stellen und sich beteiligen.', 'fil' => 'Nakapagtanong at nakalahok ang mga mag-aaral.', 'fr' => 'Les étudiants pouvaient poser des questions et participer.', 'ja' => '質問や参加の機会が十分にあった。', 'mn' => 'Оюутнууд асуулт асууж, оролцох боломжтой байсан.', 'ms' => 'Pelajar boleh bertanya soalan dan mengambil bahagian.', 'ne' => 'विद्यार्थीले प्रश्न सोध्न र सहभागी हुन पाए।', 'ru' => 'У студентов была возможность задавать вопросы и участвовать в работе.', 'th' => 'นักศึกษามีโอกาสซักถามและมีส่วนร่วมอย่างเพียงพอ', 'tr' => 'Öğrenciler soru sorabildi ve derse katılabildi.', 'uz' => 'Talabalar savol berishi va faol qatnashishi mumkin edi.', 'vi' => 'Sinh viên có cơ hội đặt câu hỏi và tham gia.'],
                ['en' => 'The pace of the course was hard to follow.', 'ko' => '수업 진도를 따라가기 어려웠다.', 'zh_CN' => '课程进度很难跟上。', 'lo' => 'ຈັງຫວະການສອນຕິດຕາມໄດ້ຍາກ.', 'de' => 'Dem Tempo der Lehrveranstaltung war schwer zu folgen.', 'fil' => 'Mahirap sundan ang bilis ng asignatura.', 'fr' => 'Le rythme du cours était difficile à suivre.', 'ja' => '授業の進度についていくのが難しかった。', 'mn' => 'Хичээлийн явцыг дагахад хэцүү байсан.', 'ms' => 'Rentak kursus sukar diikuti.', 'ne' => 'कक्षाको गति पछ्याउन गाह्रो थियो।', 'ru' => 'За темпом изложения материала было трудно успевать.', 'th' => 'จังหวะการสอนเร็วจนติดตามได้ยาก', 'tr' => 'Dersin hızını takip etmek zordu.', 'uz' => 'Dars surʼatiga ergashish qiyin edi.', 'vi' => 'Tiến độ của học phần khó theo kịp.', 'reversed' => true],
            ]],
            [['en' => 'Content', 'ko' => '강의 내용', 'zh_CN' => '教学内容', 'lo' => 'ເນື້ອໃນການສອນ', 'de' => 'Inhalt', 'fil' => 'Nilalaman', 'fr' => 'Contenu', 'ja' => '授業の内容', 'mn' => 'Агуулга', 'ms' => 'Kandungan', 'ne' => 'विषयवस्तु', 'ru' => 'Содержание', 'th' => 'เนื้อหาการสอน', 'tr' => 'Ders içeriği', 'uz' => 'Fan mazmuni', 'vi' => 'Nội dung giảng dạy'], [
                ['en' => 'The content followed the syllabus.', 'ko' => '수업 내용이 강의계획서에 맞게 진행되었다.', 'zh_CN' => '教学内容与教学大纲一致。', 'lo' => 'ເນື້ອໃນການສອນສອດຄ່ອງກັບຫຼັກສູດການສອນ.', 'de' => 'Der Inhalt folgte dem Kursplan.', 'fil' => 'Sumunod sa syllabus ang nilalaman.', 'fr' => 'Le contenu correspondait au syllabus.', 'ja' => '授業内容はシラバスに沿って進められた。', 'mn' => 'Хичээлийн агуулга силлабусын дагуу явагдсан.', 'ms' => 'Kandungan kursus mengikut rangka kursus.', 'ne' => 'विषयवस्तु पाठ्यविवरणअनुसार थियो।', 'ru' => 'Содержание занятий соответствовало рабочей программе.', 'th' => 'เนื้อหาการสอนเป็นไปตามประมวลรายวิชา', 'tr' => 'İçerik ders izlencesine uygundu.', 'uz' => 'Dars mazmuni sillabusga mos keldi.', 'vi' => 'Nội dung giảng dạy bám sát đề cương học phần.'],
                ['en' => 'The course increased my knowledge of the subject.', 'ko' => '이 과목에 대한 지식이 늘었다.', 'zh_CN' => '这门课增加了我对该学科的了解。', 'lo' => 'ລາຍວິຊານີ້ເຮັດໃຫ້ຂ້ອຍມີຄວາມຮູ້ໃນວິຊານີ້ເພີ່ມຂຶ້ນ.', 'de' => 'Die Lehrveranstaltung hat mein Wissen über das Thema erweitert.', 'fil' => 'Nadagdagan ng asignatura ang aking kaalaman sa paksa.', 'fr' => 'Le cours a enrichi mes connaissances dans la discipline.', 'ja' => 'この科目に関する知識が深まった。', 'mn' => 'Энэ хичээл тухайн чиглэлээрх миний мэдлэгийг нэмэгдүүлсэн.', 'ms' => 'Kursus ini menambah pengetahuan saya tentang subjek ini.', 'ne' => 'यस विषयले मेरो ज्ञान बढायो।', 'ru' => 'Дисциплина расширила мои знания в предметной области.', 'th' => 'รายวิชานี้ช่วยเพิ่มพูนความรู้ในเนื้อหาวิชา', 'tr' => 'Ders, konuyla ilgili bilgimi artırdı.', 'uz' => 'Bu fan mavzu boʻyicha bilimlarimni oshirdi.', 'vi' => 'Học phần giúp tôi nâng cao kiến thức về môn học.'],
                ['en' => 'Materials and assignments helped me learn.', 'ko' => '교재와 과제가 학습에 도움이 되었다.', 'zh_CN' => '教材和作业对学习有帮助。', 'lo' => 'ເອກະສານການສອນ ແລະ ວຽກບ້ານຊ່ວຍໃນການຮຽນຮູ້.', 'de' => 'Materialien und Aufgaben haben mir beim Lernen geholfen.', 'fil' => 'Nakatulong sa aking pagkatuto ang mga materyales at takdang-aralin.', 'fr' => 'Les supports et les devoirs m’ont aidé à apprendre.', 'ja' => '教材と課題は学習に役立った。', 'mn' => 'Материал, даалгавар суралцахад тус болсон.', 'ms' => 'Bahan dan tugasan membantu saya belajar.', 'ne' => 'सामग्री र गृहकार्यले मलाई सिक्न मद्दत गरे।', 'ru' => 'Учебные материалы и задания помогали в обучении.', 'th' => 'เอกสารประกอบการสอนและงานที่มอบหมายช่วยให้เกิดการเรียนรู้', 'tr' => 'Materyaller ve ödevler öğrenmeme yardımcı oldu.', 'uz' => 'Materiallar va topshiriqlar oʻrganishimga yordam berdi.', 'vi' => 'Tài liệu và bài tập giúp ích cho việc học của tôi.'],
            ]],
            [['en' => 'Assessment', 'ko' => '평가 방법', 'zh_CN' => '考核方法', 'lo' => 'ວິທີການປະເມີນຜົນ', 'de' => 'Leistungsbewertung', 'fil' => 'Pagtatasa', 'fr' => 'Évaluation', 'ja' => '成績評価', 'mn' => 'Үнэлгээ', 'ms' => 'Penilaian', 'ne' => 'मूल्याङ्कन', 'ru' => 'Оценивание', 'th' => 'การวัดและประเมินผล', 'tr' => 'Ölçme ve değerlendirme', 'uz' => 'Baholash', 'vi' => 'Kiểm tra, đánh giá'], [
                ['en' => 'Assessment followed the announced criteria.', 'ko' => '평가가 안내된 기준에 따라 이루어졌다.', 'zh_CN' => '考核按照公布的标准进行。', 'lo' => 'ການປະເມີນຜົນເປັນໄປຕາມເກນທີ່ປະກາດໄວ້.', 'de' => 'Die Bewertung folgte den angekündigten Kriterien.', 'fil' => 'Sumunod ang pagtatasa sa inanunsiyong pamantayan.', 'fr' => 'L’évaluation a respecté les critères annoncés.', 'ja' => '成績評価は示された基準に従って行われた。', 'mn' => 'Үнэлгээ зарласан шалгуурын дагуу явагдсан.', 'ms' => 'Penilaian dibuat mengikut kriteria yang dimaklumkan.', 'ne' => 'मूल्याङ्कन घोषित मापदण्डअनुसार भयो।', 'ru' => 'Оценивание проводилось по объявленным критериям.', 'th' => 'การวัดและประเมินผลเป็นไปตามเกณฑ์ที่แจ้งไว้', 'tr' => 'Değerlendirme duyurulan ölçütlere göre yapıldı.', 'uz' => 'Baholash eʼlon qilingan mezonlar asosida oʻtkazildi.', 'vi' => 'Việc đánh giá tuân theo tiêu chí đã công bố.'],
                ['en' => 'Exams and assignments matched what was taught.', 'ko' => '시험과 과제가 수업 내용과 맞았다.', 'zh_CN' => '考试和作业与教学内容相符。', 'lo' => 'ການສອບເສັງ ແລະ ວຽກບ້ານສອດຄ່ອງກັບເນື້ອໃນທີ່ສອນ.', 'de' => 'Prüfungen und Aufgaben entsprachen dem Gelehrten.', 'fil' => 'Tumugma ang mga pagsusulit at takdang-aralin sa itinuro.', 'fr' => 'Les examens et les devoirs correspondaient à ce qui a été enseigné.', 'ja' => '試験と課題は授業内容に合っていた。', 'mn' => 'Шалгалт, даалгавар заасан агуулгатай нийцсэн.', 'ms' => 'Peperiksaan dan tugasan sepadan dengan apa yang diajar.', 'ne' => 'परीक्षा र गृहकार्य पढाइएको कुरासँग मेल खान्थे।', 'ru' => 'Экзамены и задания соответствовали пройденному материалу.', 'th' => 'การสอบและงานที่มอบหมายสอดคล้องกับเนื้อหาที่สอน', 'tr' => 'Sınavlar ve ödevler işlenen konularla uyumluydu.', 'uz' => 'Imtihonlar va topshiriqlar oʻtilgan mavzularga mos keldi.', 'vi' => 'Bài thi và bài tập phù hợp với nội dung đã giảng dạy.'],
                ['en' => 'I received useful feedback on my work.', 'ko' => '과제와 시험에 대해 도움이 되는 피드백을 받았다.', 'zh_CN' => '我得到了对学习有帮助的反馈。', 'lo' => 'ຂ້ອຍໄດ້ຮັບຄຳຕິຊົມທີ່ເປັນປະໂຫຍດຕໍ່ວຽກຂອງຂ້ອຍ.', 'de' => 'Ich habe hilfreiche Rückmeldungen zu meinen Leistungen erhalten.', 'fil' => 'Nakatanggap ako ng kapaki-pakinabang na feedback sa aking mga gawa.', 'fr' => 'J’ai reçu des retours utiles sur mon travail.', 'ja' => '課題や試験について役立つフィードバックを受けた。', 'mn' => 'Ажлынхаа талаар хэрэгтэй санал зөвлөгөө авсан.', 'ms' => 'Saya menerima maklum balas yang berguna tentang hasil kerja saya.', 'ne' => 'मैले आफ्नो कामबारे उपयोगी प्रतिक्रिया पाएँ।', 'ru' => 'Я получал(а) полезную обратную связь по своим работам.', 'th' => 'ข้าพเจ้าได้รับข้อเสนอแนะที่เป็นประโยชน์ต่องานของข้าพเจ้า', 'tr' => 'Çalışmalarım hakkında yararlı geri bildirim aldım.', 'uz' => 'Ishlarim boʻyicha foydali fikr-mulohazalar oldim.', 'vi' => 'Tôi nhận được nhận xét hữu ích về bài làm của mình.'],
            ]],
            [['en' => 'Facilities', 'ko' => '교학 시설 및 장비', 'zh_CN' => '教学设施和设备', 'lo' => 'ສິ່ງອຳນວຍຄວາມສະດວກ ແລະ ອຸປະກອນການສອນ', 'de' => 'Räume und Ausstattung', 'fil' => 'Mga pasilidad', 'fr' => 'Locaux et équipements', 'ja' => '施設・設備', 'mn' => 'Танхим, тоног төхөөрөмж', 'ms' => 'Kemudahan', 'ne' => 'सुविधा र उपकरण', 'ru' => 'Аудитории и оборудование', 'th' => 'สิ่งอำนวยความสะดวกและอุปกรณ์การสอน', 'tr' => 'Derslik ve donanım', 'uz' => 'Oʻquv sharoitlari va jihozlar', 'vi' => 'Cơ sở vật chất và trang thiết bị'], [
                ['en' => 'The classroom was suitable for the course.', 'ko' => '강의실이 수업에 적합했다.', 'zh_CN' => '教室适合本课程。', 'lo' => 'ຫ້ອງຮຽນເໝາະສົມກັບລາຍວິຊາ.', 'de' => 'Der Raum war für die Lehrveranstaltung geeignet.', 'fil' => 'Angkop ang silid-aralan para sa asignatura.', 'fr' => 'La salle était adaptée au cours.', 'ja' => '教室は授業に適していた。', 'mn' => 'Танхим хичээлд тохиромжтой байсан.', 'ms' => 'Bilik kuliah sesuai untuk kursus ini.', 'ne' => 'कक्षाकोठा यस विषयका लागि उपयुक्त थियो।', 'ru' => 'Аудитория подходила для проведения занятий.', 'th' => 'ห้องเรียนเหมาะสมกับรายวิชา', 'tr' => 'Derslik ders için uygundu.', 'uz' => 'Auditoriya fan uchun qulay edi.', 'vi' => 'Phòng học phù hợp với học phần.'],
                ['en' => 'Equipment needed for the course worked well.', 'ko' => '수업에 필요한 장비가 잘 작동했다.', 'zh_CN' => '课程所需设备运行良好。', 'lo' => 'ອຸປະກອນທີ່ຈຳເປັນສຳລັບລາຍວິຊາໃຊ້ງານໄດ້ດີ.', 'de' => 'Die benötigte Ausstattung funktionierte gut.', 'fil' => 'Maayos na gumana ang mga kagamitang kailangan sa asignatura.', 'fr' => 'Les équipements nécessaires au cours fonctionnaient bien.', 'ja' => '授業に必要な機器は正常に動作した。', 'mn' => 'Хичээлд шаардлагатай тоног төхөөрөмж хэвийн ажилласан.', 'ms' => 'Peralatan yang diperlukan untuk kursus berfungsi dengan baik.', 'ne' => 'विषयका लागि आवश्यक उपकरणहरूले राम्ररी काम गरे।', 'ru' => 'Необходимое для занятий оборудование работало исправно.', 'th' => 'อุปกรณ์ที่จำเป็นสำหรับรายวิชาใช้งานได้ดี', 'tr' => 'Ders için gereken donanım düzgün çalıştı.', 'uz' => 'Fan uchun zarur jihozlar yaxshi ishladi.', 'vi' => 'Trang thiết bị cần cho học phần hoạt động tốt.'],
                ['en' => 'Overall, I am satisfied with this course.', 'ko' => '전반적으로 이 수업에 만족한다.', 'zh_CN' => '总体上我对这门课感到满意。', 'lo' => 'ໂດຍລວມແລ້ວ ຂ້ອຍພໍໃຈກັບລາຍວິຊານີ້.', 'de' => 'Insgesamt bin ich mit dieser Lehrveranstaltung zufrieden.', 'fil' => 'Sa kabuuan, nasisiyahan ako sa asignaturang ito.', 'fr' => 'Dans l’ensemble, je suis satisfait de ce cours.', 'ja' => '全体として、この授業に満足している。', 'mn' => 'Ерөнхийдөө би энэ хичээлд сэтгэл хангалуун байна.', 'ms' => 'Secara keseluruhan, saya berpuas hati dengan kursus ini.', 'ne' => 'समग्रमा, म यस विषयसँग सन्तुष्ट छु।', 'ru' => 'В целом я доволен (довольна) этой дисциплиной.', 'th' => 'โดยภาพรวม ข้าพเจ้าพึงพอใจต่อรายวิชานี้', 'tr' => 'Genel olarak bu dersten memnunum.', 'uz' => 'Umuman olganda, bu fandan mamnunman.', 'vi' => 'Nhìn chung, tôi hài lòng với học phần này.'],
            ]],
        ];
        $order = 0;

        foreach ($areas as [$category, $questions]) {
            foreach ($questions as $question) {
                EvaluationQuestion::query()->create([
                    'evaluation_form_id' => $form->id,
                    'sort_order' => $order += 10,
                    'category' => $category,
                    'text' => array_diff_key($question, ['reversed' => true]),
                    'is_reversed' => $question['reversed'] ?? false,
                ]);
            }
        }

        EvaluationQuestion::query()->create([
            'evaluation_form_id' => $form->id,
            'sort_order' => $order + 10,
            'category' => ['en' => 'Comments', 'ko' => '의견', 'zh_CN' => '意见', 'lo' => 'ຄຳຄິດເຫັນ', 'de' => 'Anmerkungen', 'fil' => 'Mga komento', 'fr' => 'Commentaires', 'ja' => '意見', 'mn' => 'Сэтгэгдэл', 'ms' => 'Ulasan', 'ne' => 'टिप्पणीहरू', 'ru' => 'Комментарии', 'th' => 'ความคิดเห็น', 'tr' => 'Görüşler', 'uz' => 'Fikrlar', 'vi' => 'Ý kiến'],
            'text' => ['en' => 'What should the instructor keep or change?', 'ko' => '이 수업에서 좋았던 점이나 바뀌었으면 하는 점을 적어 주세요.', 'zh_CN' => '请写下这门课的优点或需要改进的地方。', 'lo' => 'ກະລຸນາຂຽນສິ່ງທີ່ດີຂອງລາຍວິຊານີ້ ຫຼື ສິ່ງທີ່ຢາກໃຫ້ປ່ຽນແປງ.', 'de' => 'Was sollte die Lehrperson beibehalten oder ändern?', 'fil' => 'Ano ang dapat panatilihin o baguhin ng instruktor?', 'fr' => 'Que devrait conserver ou changer l’enseignant ?', 'ja' => 'この授業の良かった点や改善してほしい点を書いてください。', 'mn' => 'Багш юуг хэвээр үлдээх, юуг өөрчлөх хэрэгтэй вэ?', 'ms' => 'Apakah yang patut dikekalkan atau diubah oleh pensyarah?', 'ne' => 'प्राध्यापकले के कायम राख्नुपर्छ वा के परिवर्तन गर्नुपर्छ?', 'ru' => 'Что преподавателю стоит сохранить или изменить?', 'th' => 'สิ่งใดที่อาจารย์ผู้สอนควรคงไว้หรือปรับปรุง', 'tr' => 'Öğretim elemanı neleri sürdürmeli veya değiştirmeli?', 'uz' => 'Oʻqituvchi nimani saqlab qolishi yoki oʻzgartirishi kerak?', 'vi' => 'Giảng viên nên giữ lại hoặc thay đổi điều gì?'],
            'question_type' => 'text',
            'is_required' => false,
        ]);
    }

    /**
     * The certificate kinds the legacy issued (재학, 졸업, 성적증명서) with their wording in
     * Korean, English and Chinese. Created once; the institution edits the templates.
     */
    private function certificateTypes(): void
    {
        if (CertificateType::query()->exists()) {
            return;
        }

        $kinds = [
            ['ENR', ['en' => 'Certificate of Enrollment', 'ko' => '재학증명서', 'zh_CN' => '在学证明书', 'lo' => 'ໃບຢັ້ງຢືນການເປັນນັກສຶກສາ', 'de' => 'Immatrikulationsbescheinigung', 'fil' => 'Certificate of Enrollment', 'fr' => 'Certificat de scolarité', 'ja' => '在学証明書', 'mn' => 'Суралцаж буйг гэрчлэх тодорхойлолт', 'ms' => 'Surat Pengesahan Pelajar', 'ne' => 'अध्ययनरत प्रमाणपत्र', 'ru' => 'Справка об обучении', 'th' => 'หนังสือรับรองการเป็นนักศึกษา', 'tr' => 'Öğrenci Belgesi', 'uz' => 'Talabalik haqida maʼlumotnoma', 'vi' => 'Giấy xác nhận sinh viên'], 'enrolled', false, [
                'ko' => ['재학증명서', "위의 사람이 본교에 재학 중임을 증명합니다.\n\n용도: {purpose}", '교무처장'],
                'en' => ['Certificate of Enrollment', "This is to certify that the above mentioned is presently enrolled at {institution}.\n\nPurpose: {purpose}", 'Dean of Academic Affairs'],
                'zh_CN' => ['在学证明书', "兹证明上述学生现在本校在读。\n\n用途：{purpose}", '教务处长'],
                'lo' => ['ໃບຢັ້ງຢືນການເປັນນັກສຶກສາ', "ຂໍຢັ້ງຢືນວ່າ ບຸກຄົນທີ່ມີຊື່ຂ້າງເທິງນີ້ ກຳລັງສຶກສາຢູ່ {institution}.\n\nຈຸດປະສົງ: {purpose}", 'ຫົວໜ້າຫ້ອງການວິຊາການ'],
                'de' => ['Immatrikulationsbescheinigung', "Hiermit wird bescheinigt, dass die oben genannte Person derzeit an der {institution} immatrikuliert ist.\n\nVerwendungszweck: {purpose}", 'Vizepräsident/in für Studium und Lehre'],
                'fil' => ['Certificate of Enrollment', "Ito ay nagpapatunay na ang nabanggit sa itaas ay kasalukuyang naka-enroll sa {institution}.\n\nLayunin: {purpose}", 'Dekano ng Academic Affairs'],
                'fr' => ['Certificat de scolarité', "Nous certifions que la personne susmentionnée est actuellement inscrite à {institution}.\n\nObjet : {purpose}", 'Le Vice-président chargé des formations'],
                'ja' => ['在学証明書', "上記の者は、現在 {institution} に在学していることを証明します。\n\n用途: {purpose}", '教務部長'],
                'mn' => ['Суралцаж буйг гэрчлэх тодорхойлолт', "Дээр дурдсан хүн {institution}-д одоо суралцаж байгааг тодорхойлов.\n\nЗориулалт: {purpose}", 'Сургалт хариуцсан дэд захирал'],
                'ms' => ['Surat Pengesahan Pelajar', "Adalah disahkan bahawa individu yang tersebut di atas kini sedang mengikuti pengajian di {institution}.\n\nTujuan: {purpose}", 'Dekan Hal Ehwal Akademik'],
                'ne' => ['अध्ययनरत प्रमाणपत्र', "माथि उल्लिखित व्यक्ति हाल {institution} मा अध्ययनरत हुनुहुन्छ भनी प्रमाणित गरिन्छ।\n\nप्रयोजन: {purpose}", 'शैक्षिक प्रशासन प्रमुख'],
                'ru' => ['Справка об обучении', "Настоящая справка подтверждает, что указанное выше лицо в настоящее время обучается в {institution}.\n\nСправка выдана для представления: {purpose}", 'Проректор по учебной работе'],
                'th' => ['หนังสือรับรองการเป็นนักศึกษา', "หนังสือฉบับนี้ให้ไว้เพื่อรับรองว่าผู้มีรายนามข้างต้นเป็นนักศึกษาของ {institution} ในปัจจุบัน\n\nวัตถุประสงค์: {purpose}", 'รองอธิการบดีฝ่ายวิชาการ'],
                'tr' => ['Öğrenci Belgesi', "Yukarıda bilgileri yer alan kişinin {institution} öğrencisi olduğu onaylanır.\n\nKullanım amacı: {purpose}", 'Öğrenci İşleri Daire Başkanı'],
                'uz' => ['Talabalik haqida maʼlumotnoma', "Ushbu maʼlumotnoma yuqorida koʻrsatilgan shaxs hozirda {institution}da tahsil olayotganini tasdiqlaydi.\n\nMaqsad: {purpose}", 'Oʻquv ishlari boʻyicha prorektor'],
                'vi' => ['Giấy xác nhận sinh viên', "Xác nhận người có tên trên hiện đang là sinh viên của {institution}.\n\nMục đích: {purpose}", 'Trưởng phòng Đào tạo'],
            ]],
            ['GRD', ['en' => 'Certificate of Graduation', 'ko' => '졸업증명서', 'zh_CN' => '毕业证明书', 'lo' => 'ໃບຢັ້ງຢືນການຈົບການສຶກສາ', 'de' => 'Abschlussbescheinigung', 'fil' => 'Certificate of Graduation', 'fr' => 'Attestation de réussite au diplôme', 'ja' => '卒業証明書', 'mn' => 'Төгссөн тухай тодорхойлолт', 'ms' => 'Surat Pengesahan Tamat Pengajian', 'ne' => 'स्नातक प्रमाणपत्र', 'ru' => 'Справка об окончании', 'th' => 'หนังสือรับรองการสำเร็จการศึกษา', 'tr' => 'Mezuniyet Belgesi', 'uz' => 'Bitirganlik haqida maʼlumotnoma', 'vi' => 'Giấy chứng nhận tốt nghiệp'], 'graduated', false, [
                'ko' => ['졸업증명서', "위의 사람이 {graduation_date} 본교 {program}을(를) 졸업하였음을 증명합니다.\n\n학위번호: {degree_number}", '교무처장'],
                'en' => ['Certificate of Graduation', "This is to certify that the above mentioned graduated from {institution}, {program}, on {graduation_date}.\n\nDiploma no.: {degree_number}", 'Dean of Academic Affairs'],
                'zh_CN' => ['毕业证明书', "兹证明上述学生于{graduation_date}在本校{program}毕业。\n\n学位证书编号：{degree_number}", '教务处长'],
                'lo' => ['ໃບຢັ້ງຢືນການຈົບການສຶກສາ', "ຂໍຢັ້ງຢືນວ່າ ບຸກຄົນທີ່ມີຊື່ຂ້າງເທິງນີ້ ໄດ້ຈົບການສຶກສາຈາກ {institution} ຫຼັກສູດ {program} ໃນວັນທີ {graduation_date}.\n\nເລກທີໃບປະກາດ: {degree_number}", 'ຫົວໜ້າຫ້ອງການວິຊາການ'],
                'de' => ['Abschlussbescheinigung', "Hiermit wird bescheinigt, dass die oben genannte Person am {graduation_date} den Studiengang {program} an der {institution} abgeschlossen hat.\n\nUrkundennummer: {degree_number}", 'Vizepräsident/in für Studium und Lehre'],
                'fil' => ['Certificate of Graduation', "Ito ay nagpapatunay na ang nabanggit sa itaas ay nagtapos sa {institution}, {program}, noong {graduation_date}.\n\nBlg. ng diploma: {degree_number}", 'Dekano ng Academic Affairs'],
                'fr' => ['Attestation de réussite au diplôme', "Nous certifions que la personne susmentionnée a obtenu son diplôme de {institution}, {program}, le {graduation_date}.\n\nN° de diplôme : {degree_number}", 'Le Vice-président chargé des formations'],
                'ja' => ['卒業証明書', "上記の者は、{graduation_date} に {institution} {program} を卒業したことを証明します。\n\n学位記番号: {degree_number}", '教務部長'],
                'mn' => ['Төгссөн тухай тодорхойлолт', "Дээр дурдсан хүн {graduation_date}-нд {institution}-ийн {program} хөтөлбөрийг төгссөнийг тодорхойлов.\n\nДипломын дугаар: {degree_number}", 'Сургалт хариуцсан дэд захирал'],
                'ms' => ['Surat Pengesahan Tamat Pengajian', "Adalah disahkan bahawa individu yang tersebut di atas telah bergraduat daripada {institution}, {program}, pada {graduation_date}.\n\nNo. ijazah: {degree_number}", 'Dekan Hal Ehwal Akademik'],
                'ne' => ['स्नातक प्रमाणपत्र', "माथि उल्लिखित व्यक्तिले {graduation_date} मा {institution}, {program} बाट स्नातक गर्नुभएको हो भनी प्रमाणित गरिन्छ।\n\nउपाधि नं.: {degree_number}", 'शैक्षिक प्रशासन प्रमुख'],
                'ru' => ['Справка об окончании', "Настоящая справка подтверждает, что указанное выше лицо окончило {institution}, программа {program}, {graduation_date}.\n\nНомер диплома: {degree_number}", 'Проректор по учебной работе'],
                'th' => ['หนังสือรับรองการสำเร็จการศึกษา', "หนังสือฉบับนี้ให้ไว้เพื่อรับรองว่าผู้มีรายนามข้างต้นสำเร็จการศึกษาจาก {institution} หลักสูตร {program} เมื่อวันที่ {graduation_date}\n\nเลขที่ปริญญาบัตร: {degree_number}", 'รองอธิการบดีฝ่ายวิชาการ'],
                'tr' => ['Mezuniyet Belgesi', "Yukarıda bilgileri yer alan kişinin {graduation_date} tarihinde {institution}, {program} programından mezun olduğu onaylanır.\n\nDiploma no.: {degree_number}", 'Öğrenci İşleri Daire Başkanı'],
                'uz' => ['Bitirganlik haqida maʼlumotnoma', "Ushbu maʼlumotnoma yuqorida koʻrsatilgan shaxs {graduation_date} da {institution}, {program} dasturini bitirganini tasdiqlaydi.\n\nDiplom raqami: {degree_number}", 'Oʻquv ishlari boʻyicha prorektor'],
                'vi' => ['Giấy chứng nhận tốt nghiệp', "Chứng nhận người có tên trên đã tốt nghiệp {institution}, {program}, ngày {graduation_date}.\n\nSố hiệu văn bằng: {degree_number}", 'Trưởng phòng Đào tạo'],
            ]],
            ['TRN', ['en' => 'Academic Transcript', 'ko' => '성적증명서', 'zh_CN' => '成绩证明书', 'lo' => 'ໃບຢັ້ງຢືນຜົນການຮຽນ', 'de' => 'Notenspiegel', 'fil' => 'Transcript of Records', 'fr' => 'Relevé de notes', 'ja' => '成績証明書', 'mn' => 'Дүнгийн хуудас', 'ms' => 'Transkrip Akademik', 'ne' => 'शैक्षिक ट्रान्सक्रिप्ट', 'ru' => 'Академическая справка', 'th' => 'ใบแสดงผลการเรียน', 'tr' => 'Transkript', 'uz' => 'Akademik maʼlumotnoma', 'vi' => 'Bảng điểm học tập'], 'any', true, [
                'ko' => ['성적증명서', '위의 사람의 성적이 아래와 같음을 증명합니다.', '교무처장'],
                'en' => ['Academic Transcript', 'I certify that the transcript below is a complete and authentic copy of the academic record of the above mentioned.', 'Dean of Academic Affairs'],
                'zh_CN' => ['成绩证明书', '兹证明上述学生的成绩如下。', '教务处长'],
                'lo' => ['ໃບຢັ້ງຢືນຜົນການຮຽນ', 'ຂໍຢັ້ງຢືນວ່າ ຜົນການຮຽນຂ້າງລຸ່ມນີ້ ແມ່ນສຳເນົາທີ່ຄົບຖ້ວນ ແລະ ຖືກຕ້ອງຂອງບັນທຶກການສຶກສາຂອງບຸກຄົນທີ່ມີຊື່ຂ້າງເທິງ.', 'ຫົວໜ້າຫ້ອງການວິຊາການ'],
                'de' => ['Notenspiegel', 'Hiermit wird bescheinigt, dass der folgende Notenspiegel eine vollständige und echte Wiedergabe der Studienleistungen der oben genannten Person ist.', 'Vizepräsident/in für Studium und Lehre'],
                'fil' => ['Transcript of Records', 'Pinatutunayan ko na ang transcript sa ibaba ay kumpleto at tunay na kopya ng akademikong rekord ng nabanggit sa itaas.', 'Dekano ng Academic Affairs'],
                'fr' => ['Relevé de notes', 'Je certifie que le relevé ci-dessous est une copie complète et authentique du dossier académique de la personne susmentionnée.', 'Le Vice-président chargé des formations'],
                'ja' => ['成績証明書', '以下の成績は、上記の者の学業記録の完全かつ正確な写しであることを証明します。', '教務部長'],
                'mn' => ['Дүнгийн хуудас', 'Доорх дүнгийн хуудас нь дээр дурдсан хүний сурлагын бүртгэлийн бүрэн, үнэн зөв хуулбар болохыг баталж байна.', 'Сургалт хариуцсан дэд захирал'],
                'ms' => ['Transkrip Akademik', 'Saya mengesahkan bahawa transkrip di bawah ialah salinan lengkap dan sahih rekod akademik individu yang tersebut di atas.', 'Dekan Hal Ehwal Akademik'],
                'ne' => ['शैक्षिक ट्रान्सक्रिप्ट', 'तलको ट्रान्सक्रिप्ट माथि उल्लिखित व्यक्तिको शैक्षिक अभिलेखको पूर्ण र प्रामाणिक प्रतिलिपि हो भनी म प्रमाणित गर्छु।', 'शैक्षिक प्रशासन प्रमुख'],
                'ru' => ['Академическая справка', 'Удостоверяю, что приведённая ниже академическая справка является полной и достоверной копией сведений об успеваемости указанного выше лица.', 'Проректор по учебной работе'],
                'th' => ['ใบแสดงผลการเรียน', 'ขอรับรองว่าใบแสดงผลการเรียนด้านล่างนี้เป็นสำเนาที่ครบถ้วนและถูกต้องตรงตามระเบียนผลการเรียนของผู้มีรายนามข้างต้น', 'รองอธิการบดีฝ่ายวิชาการ'],
                'tr' => ['Transkript', 'Aşağıdaki transkriptin, yukarıda bilgileri yer alan kişinin akademik kaydının eksiksiz ve aslına uygun bir örneği olduğunu onaylarım.', 'Öğrenci İşleri Daire Başkanı'],
                'uz' => ['Akademik maʼlumotnoma', 'Quyidagi akademik maʼlumotnoma yuqorida koʻrsatilgan shaxsning oʻquv natijalari toʻgʻrisidagi toʻliq va haqiqiy nusxa ekanini tasdiqlayman.', 'Oʻquv ishlari boʻyicha prorektor'],
                'vi' => ['Bảng điểm học tập', 'Xác nhận bảng điểm dưới đây là bản đầy đủ và xác thực của kết quả học tập của người có tên trên.', 'Trưởng phòng Đào tạo'],
            ]],
        ];
        $order = 0;

        foreach ($kinds as [$code, $name, $audience, $grades, $templates]) {
            $type = CertificateType::query()->create(['code' => $code, 'name' => $name, 'audience' => $audience, 'has_grades' => $grades, 'sort_order' => $order += 10]);

            foreach ($templates as $locale => [$title, $body, $signer]) {
                CertificateTemplate::query()->create([
                    'certificate_type_id' => $type->id, 'locale' => $locale, 'title' => $title, 'body' => $body, 'signer' => $signer,
                    'footer' => ['ko' => '이 증명서의 진위는 아래 QR 코드 또는 확인 주소에서 확인할 수 있습니다.', 'en' => 'Verify this certificate with the QR code or at the address below.', 'zh_CN' => '可通过下方二维码或网址核验本证明。', 'lo' => 'ສາມາດກວດສອບຄວາມຖືກຕ້ອງຂອງໃບຢັ້ງຢືນນີ້ ດ້ວຍລະຫັດ QR ຫຼື ທີ່ຢູ່ຂ້າງລຸ່ມນີ້.', 'de' => 'Prüfen Sie die Echtheit dieser Bescheinigung über den QR-Code oder unter der folgenden Adresse.', 'fil' => 'I-verify ang sertipikong ito gamit ang QR code o sa address sa ibaba.', 'fr' => 'Vérifiez cette attestation avec le code QR ou à l’adresse ci-dessous.', 'ja' => 'この証明書の真正性は、QR コードまたは下記のアドレスで確認できます。', 'mn' => 'Энэ тодорхойлолтыг QR кодоор эсвэл доорх хаягаар баталгаажуулна уу.', 'ms' => 'Sahkan sijil ini melalui kod QR atau di alamat di bawah.', 'ne' => 'यो प्रमाणपत्र QR कोडबाट वा तलको ठेगानामा प्रमाणित गर्नुहोस्।', 'ru' => 'Подлинность справки можно проверить по QR-коду или по адресу ниже.', 'th' => 'ตรวจสอบความถูกต้องของหนังสือรับรองนี้ได้ด้วย QR code หรือที่อยู่ด้านล่าง', 'tr' => 'Bu belgeyi QR kodu ile veya aşağıdaki adresten doğrulayabilirsiniz.', 'uz' => 'Ushbu maʼlumotnomaning haqiqiyligini QR kod orqali yoki quyidagi manzilda tekshiring.', 'vi' => 'Xác minh giấy chứng nhận này bằng mã QR hoặc tại địa chỉ dưới đây.'][$locale],
                ]);
            }
        }
    }

    /**
     * The 4.5 scale, the institution's scale unless one is fixed already (the scale is fixed
     * at installation: setup:install --grade-scale, or the first profile's scale).
     */
    private function gradeScale(): void
    {
        self::seedScale45(true);
    }

    /**
     * The 4.5 scale (default only while the installation has no scale yet).
     */
    public static function seedScale45(bool $isDefault): GradeScale
    {
        // code => [points, min %, max %, counts in GPA, passing]
        return self::seedScale('KR45', ['en' => '4.5 scale', 'ko' => '4.5 만점', 'zh_CN' => '4.5 分制', 'lo' => 'ລະບົບ 4.5 ຄະແນນ', 'de' => '4,5-Skala', 'fil' => 'Iskalang 4.5', 'fr' => 'Barème sur 4,5', 'ja' => '4.5 点満点', 'mn' => '4.5 шкал', 'ms' => 'Skala 4.5', 'ne' => '4.5 स्केल', 'ru' => 'Шкала 4,5', 'th' => 'ระบบ 4.5 คะแนน', 'tr' => '4,5 ölçeği', 'uz' => '4.5 ballik shkala', 'vi' => 'Thang điểm 4.5'], 4.5, $isDefault, [
            'A+' => [4.5, 95, 100, true, true],
            'A0' => [4.0, 90, 94.99, true, true],
            'B+' => [3.5, 85, 89.99, true, true],
            'B0' => [3.0, 80, 84.99, true, true],
            'C+' => [2.5, 75, 79.99, true, true],
            'C0' => [2.0, 70, 74.99, true, true],
            'D+' => [1.5, 65, 69.99, true, true],
            'D0' => [1.0, 60, 64.99, true, true],
            'F' => [0.0, 0, 59.99, true, false],
            'P' => [null, null, null, false, true],
            'NP' => [null, null, null, false, false],
        ]);
    }

    /**
     * The 4.3 scale with minus grades, for installations that choose it.
     */
    public static function seedScale43(bool $isDefault): GradeScale
    {
        return self::seedScale('KR43', ['en' => '4.3 scale', 'ko' => '4.3 만점', 'zh_CN' => '4.3 分制', 'lo' => 'ລະບົບ 4.3 ຄະແນນ', 'de' => '4,3-Skala', 'fil' => 'Iskalang 4.3', 'fr' => 'Barème sur 4,3', 'ja' => '4.3 点満点', 'mn' => '4.3 шкал', 'ms' => 'Skala 4.3', 'ne' => '4.3 स्केल', 'ru' => 'Шкала 4,3', 'th' => 'ระบบ 4.3 คะแนน', 'tr' => '4,3 ölçeği', 'uz' => '4.3 ballik shkala', 'vi' => 'Thang điểm 4.3'], 4.3, $isDefault, self::scale43(['P' => [true], 'NP' => [false]]));
    }

    /**
     * 4.3 grades with three-point score ranges, plus the given non-GPA grades.
     *
     * @param  array<string, array{0: bool, 1?: string}>  $nonGpa  code => [passing, applies to]
     * @return array<string, array{0: float|null, 1: float|null, 2: float|null, 3: bool, 4: bool, 5?: string}>
     */
    public static function scale43(array $nonGpa): array
    {
        $items = [
            'A+' => [4.3, 97, 100], 'A0' => [4.0, 93, 96.99], 'A-' => [3.7, 90, 92.99],
            'B+' => [3.3, 87, 89.99], 'B0' => [3.0, 83, 86.99], 'B-' => [2.7, 80, 82.99],
            'C+' => [2.3, 77, 79.99], 'C0' => [2.0, 73, 76.99], 'C-' => [1.7, 70, 72.99],
            'D+' => [1.3, 67, 69.99], 'D0' => [1.0, 63, 66.99], 'D-' => [0.7, 60, 62.99],
            'F' => [0.0, 0, 59.99],
        ];
        $rows = [];

        foreach ($items as $code => [$points, $min, $max]) {
            $rows[$code] = [$points, $min, $max, true, $code !== 'F'];
        }

        foreach ($nonGpa as $code => $options) {
            $rows[$code] = [null, null, null, false, $options[0], $options[1] ?? 'pass_fail'];
        }

        return $rows;
    }

    /**
     * Creates the scale and its missing grades; existing grades and scales are not changed.
     * A new default is only set when no scale is the default yet (the scale is fixed).
     *
     * @param  array<string, string>  $name
     * @param  array<string, array{0: float|null, 1: float|null, 2: float|null, 3: bool, 4: bool, 5?: string}>  $items
     */
    public static function seedScale(string $code, array $name, float $maxPoints, bool $isDefault, array $items): GradeScale
    {
        $scale = GradeScale::query()->firstOrCreate(['code' => $code], [
            'name' => $name, 'max_grade_points' => $maxPoints, 'is_default' => $isDefault && ! GradeScale::query()->where('is_default', true)->exists(),
        ]);
        $order = 0;

        foreach ($items as $itemCode => $item) {
            [$points, $min, $max, $countsInGpa, $isPassing] = $item;

            GradeScaleItem::query()->firstOrCreate(['grade_scale_id' => $scale->id, 'code' => $itemCode], [
                'grade_points' => $points,
                'min_percentage' => $min,
                'max_percentage' => $max,
                'counts_in_gpa' => $countsInGpa,
                'is_passing' => $isPassing,
                'applies_to' => $item[5] ?? ($countsInGpa ? 'graded' : 'pass_fail'),
                'sort_order' => $order += 10,
            ]);
        }

        return $scale;
    }

    private function timeSlots(): void
    {
        foreach (range(1, 10) as $period) {
            $start = sprintf('%02d:00', 8 + $period);

            $this->seedRow(TimeSlot::class, ['code' => (string) $period], [
                'name' => ['en' => "Period {$period}", 'ko' => "{$period}교시", 'zh_CN' => "第{$period}节", 'lo' => "ຊົ່ວໂມງທີ {$period}", 'de' => "{$period}. Block", 'fil' => "Period {$period}", 'fr' => "Créneau {$period}", 'ja' => "{$period} 限", 'mn' => "{$period}-р пар", 'ms' => "Slot {$period}", 'ne' => "पिरियड {$period}", 'ru' => "{$period}-я пара", 'th' => "คาบที่ {$period}", 'tr' => "{$period}. ders saati", 'uz' => "{$period}-para", 'vi' => "Tiết {$period}"],
            ], [
                'start_time' => $start,
                'end_time' => sprintf('%02d:50', 8 + $period),
                'weekdays' => [1, 2, 3, 4, 5],
                'sort_order' => $period,
            ]);
        }
    }

    /**
     * @param  array<string, array<string, string>>  $values
     */
    private function lookupValues(string $typeCode, array $values): void
    {
        $type = LookupType::query()->where('code', $typeCode)->sole();
        $order = 0;

        foreach ($values as $code => $name) {
            $this->seedRow(LookupValue::class, ['lookup_type_id' => $type->id, 'code' => $code], ['name' => $name], ['sort_order' => $order += 10]);
        }
    }

    private function roomTypes(): void
    {
        $type = LookupType::query()->where('code', Room::ROOM_TYPE_LOOKUP)->sole();
        $values = [
            'lecture' => ['en' => 'Lecture room', 'ko' => '강의실', 'zh_CN' => '教室', 'lo' => 'ຫ້ອງບັນຍາຍ', 'de' => 'Hörsaal', 'fil' => 'Silid-lektyuran', 'fr' => 'Salle de cours', 'ja' => '講義室', 'mn' => 'Лекцийн танхим', 'ms' => 'Bilik kuliah', 'ne' => 'व्याख्यान कक्ष', 'ru' => 'Лекционная аудитория', 'th' => 'ห้องบรรยาย', 'tr' => 'Derslik', 'uz' => 'Maʼruza xonasi', 'vi' => 'Phòng lý thuyết'],
            'laboratory' => ['en' => 'Laboratory', 'ko' => '실험실', 'zh_CN' => '实验室', 'lo' => 'ຫ້ອງທົດລອງ', 'de' => 'Labor', 'fil' => 'Laboratoryo', 'fr' => 'Laboratoire', 'ja' => '実験室', 'mn' => 'Лаборатори', 'ms' => 'Makmal', 'ne' => 'प्रयोगशाला', 'ru' => 'Лаборатория', 'th' => 'ห้องปฏิบัติการ', 'tr' => 'Laboratuvar', 'uz' => 'Laboratoriya', 'vi' => 'Phòng thí nghiệm'],
            'practice' => ['en' => 'Practice room', 'ko' => '실습실', 'zh_CN' => '实训室', 'lo' => 'ຫ້ອງຝຶກປະຕິບັດ', 'de' => 'Übungsraum', 'fil' => 'Silid-praktikum', 'fr' => 'Salle de travaux pratiques', 'ja' => '実習室', 'mn' => 'Дадлагын танхим', 'ms' => 'Bilik amali', 'ne' => 'अभ्यास कक्ष', 'ru' => 'Аудитория для практических занятий', 'th' => 'ห้องฝึกปฏิบัติ', 'tr' => 'Uygulama salonu', 'uz' => 'Amaliyot xonasi', 'vi' => 'Phòng thực hành'],
            'seminar' => ['en' => 'Seminar room', 'ko' => '세미나실', 'zh_CN' => '研讨室', 'lo' => 'ຫ້ອງສຳມະນາ', 'de' => 'Seminarraum', 'fil' => 'Silid-seminar', 'fr' => 'Salle de séminaire', 'ja' => '演習室', 'mn' => 'Семинарын танхим', 'ms' => 'Bilik seminar', 'ne' => 'सेमिनार कक्ष', 'ru' => 'Семинарская аудитория', 'th' => 'ห้องสัมมนา', 'tr' => 'Seminer salonu', 'uz' => 'Seminar xonasi', 'vi' => 'Phòng hội thảo'],
            'auditorium' => ['en' => 'Auditorium', 'ko' => '강당', 'zh_CN' => '礼堂', 'lo' => 'ຫໍປະຊຸມ', 'de' => 'Aula', 'fil' => 'Awditoryum', 'fr' => 'Amphithéâtre', 'ja' => '講堂', 'mn' => 'Их танхим', 'ms' => 'Auditorium', 'ne' => 'सभाहल', 'ru' => 'Актовый зал', 'th' => 'หอประชุม', 'tr' => 'Konferans salonu', 'uz' => 'Majlislar zali', 'vi' => 'Hội trường'],
        ];
        $order = 0;

        foreach ($values as $code => $name) {
            $this->seedRow(LookupValue::class, ['lookup_type_id' => $type->id, 'code' => $code], ['name' => $name], ['sort_order' => $order += 10]);
        }
    }

    /**
     * Examination kinds (legacy CET types, SSCORE_ADD exams, COMEXAM): an in-house English
     * comprehensive exam with four areas, a computer exam and the thesis as pass/fail, and
     * TOPIK as an external test with a level.
     */
    private function assessmentTypes(): void
    {
        if (AssessmentType::query()->exists()) {
            return;
        }

        $english = AssessmentType::query()->create([
            'code' => 'ENG_COMP', 'name' => ['en' => 'English comprehensive examination', 'ko' => '영어종합시험', 'zh_CN' => '英语综合考试', 'lo' => 'ການສອບເສັງພາສາອັງກິດລວມ', 'de' => 'Englisch-Gesamtprüfung', 'fil' => 'Komprehensibong pagsusulit sa Ingles', 'fr' => 'Examen général d’anglais', 'ja' => '英語総合試験', 'mn' => 'Англи хэлний иж бүрэн шалгалт', 'ms' => 'Peperiksaan komprehensif bahasa Inggeris', 'ne' => 'अङ्ग्रेजी व्यापक परीक्षा', 'ru' => 'Комплексный экзамен по английскому языку', 'th' => 'การสอบประมวลความรู้ภาษาอังกฤษ', 'tr' => 'İngilizce yeterlik sınavı', 'uz' => 'Ingliz tili boʻyicha kompleks imtihon', 'vi' => 'Kỳ thi tổng hợp tiếng Anh'],
            'category' => 'language', 'scoring' => 'score', 'pass_score' => 60, 'sort_order' => 10,
        ]);

        foreach ([
            ['LISTENING', ['en' => 'Listening', 'ko' => '듣기', 'zh_CN' => '听力', 'lo' => 'ການຟັງ', 'de' => 'Hörverstehen', 'fil' => 'Pakikinig', 'fr' => 'Compréhension orale', 'ja' => 'リスニング', 'mn' => 'Сонсох', 'ms' => 'Mendengar', 'ne' => 'सुनाइ', 'ru' => 'Аудирование', 'th' => 'การฟัง', 'tr' => 'Dinleme', 'uz' => 'Tinglab tushunish', 'vi' => 'Nghe']],
            ['READING', ['en' => 'Reading', 'ko' => '읽기', 'zh_CN' => '阅读', 'lo' => 'ການອ່ານ', 'de' => 'Leseverstehen', 'fil' => 'Pagbasa', 'fr' => 'Compréhension écrite', 'ja' => 'リーディング', 'mn' => 'Унших', 'ms' => 'Membaca', 'ne' => 'पढाइ', 'ru' => 'Чтение', 'th' => 'การอ่าน', 'tr' => 'Okuma', 'uz' => 'Oʻqish', 'vi' => 'Đọc']],
            ['WRITING', ['en' => 'Writing', 'ko' => '쓰기', 'zh_CN' => '写作', 'lo' => 'ການຂຽນ', 'de' => 'Schreiben', 'fil' => 'Pagsulat', 'fr' => 'Expression écrite', 'ja' => 'ライティング', 'mn' => 'Бичих', 'ms' => 'Menulis', 'ne' => 'लेखाइ', 'ru' => 'Письмо', 'th' => 'การเขียน', 'tr' => 'Yazma', 'uz' => 'Yozish', 'vi' => 'Viết']],
            ['GRAMMAR', ['en' => 'Grammar', 'ko' => '문법', 'zh_CN' => '语法', 'lo' => 'ໄວຍາກອນ', 'de' => 'Grammatik', 'fil' => 'Gramatika', 'fr' => 'Grammaire', 'ja' => '文法', 'mn' => 'Дүрэм', 'ms' => 'Tatabahasa', 'ne' => 'व्याकरण', 'ru' => 'Грамматика', 'th' => 'ไวยากรณ์', 'tr' => 'Dil bilgisi', 'uz' => 'Grammatika', 'vi' => 'Ngữ pháp']],
        ] as $index => [$code, $name]) {
            AssessmentComponent::query()->create(['assessment_type_id' => $english->id, 'code' => $code, 'name' => $name, 'max_score' => 25, 'sort_order' => ($index + 1) * 10]);
        }

        AssessmentType::query()->create([
            'code' => 'COMP_EXAM', 'name' => ['en' => 'Computer comprehensive examination', 'ko' => '전산종합시험', 'zh_CN' => '计算机综合考试', 'lo' => 'ການສອບເສັງຄອມພິວເຕີລວມ', 'de' => 'Informatik-Gesamtprüfung', 'fil' => 'Komprehensibong pagsusulit sa kompyuter', 'fr' => 'Examen général d’informatique', 'ja' => '情報処理総合試験', 'mn' => 'Компьютерийн иж бүрэн шалгалт', 'ms' => 'Peperiksaan komprehensif komputer', 'ne' => 'कम्प्युटर व्यापक परीक्षा', 'ru' => 'Комплексный экзамен по информатике', 'th' => 'การสอบประมวลความรู้คอมพิวเตอร์', 'tr' => 'Bilgisayar yeterlik sınavı', 'uz' => 'Kompyuter savodxonligi boʻyicha kompleks imtihon', 'vi' => 'Kỳ thi tổng hợp tin học'],
            'category' => 'comprehensive', 'scoring' => 'pass_fail', 'sort_order' => 20,
        ]);
        AssessmentType::query()->create([
            'code' => 'THESIS', 'name' => ['en' => 'Graduation thesis', 'ko' => '졸업논문', 'zh_CN' => '毕业论文', 'lo' => 'ບົດວິທະຍານິພົນຈົບການສຶກສາ', 'de' => 'Abschlussarbeit', 'fil' => 'Tesis sa pagtatapos', 'fr' => 'Mémoire de fin d’études', 'ja' => '卒業論文', 'mn' => 'Дипломын ажил', 'ms' => 'Tesis tamat pengajian', 'ne' => 'स्नातक शोधपत्र', 'ru' => 'Выпускная квалификационная работа', 'th' => 'วิทยานิพนธ์เพื่อสำเร็จการศึกษา', 'tr' => 'Bitirme tezi', 'uz' => 'Bitiruv malakaviy ishi', 'vi' => 'Khóa luận tốt nghiệp'],
            'category' => 'thesis', 'scoring' => 'pass_fail', 'sort_order' => 30,
        ]);
        AssessmentType::query()->create([
            'code' => 'TOPIK', 'name' => ['en' => 'TOPIK (Test of Proficiency in Korean)', 'ko' => '한국어능력시험(TOPIK)', 'zh_CN' => '韩国语能力考试（TOPIK）', 'lo' => 'ການສອບເສັງຄວາມສາມາດພາສາເກົາຫຼີ (TOPIK)', 'de' => 'TOPIK (Koreanisch-Sprachtest)', 'fil' => 'TOPIK (Test of Proficiency in Korean)', 'fr' => 'TOPIK (test de compétence en coréen)', 'ja' => '韓国語能力試験(TOPIK)', 'mn' => 'TOPIK (Солонгос хэлний түвшин тогтоох шалгалт)', 'ms' => 'TOPIK (Ujian Kecekapan Bahasa Korea)', 'ne' => 'कोरियाली भाषा दक्षता परीक्षा (TOPIK)', 'ru' => 'TOPIK (тест на знание корейского языка)', 'th' => 'การสอบวัดระดับความสามารถภาษาเกาหลี (TOPIK)', 'tr' => 'TOPIK (Korece Yeterlik Sınavı)', 'uz' => 'TOPIK (Koreys tilini bilish darajasi testi)', 'vi' => 'TOPIK (Kỳ thi năng lực tiếng Hàn)'],
            'category' => 'external', 'scoring' => 'pass_fail', 'allows_external' => true, 'sort_order' => 40,
        ]);
    }
}
