# 27. Raporlar ve raporlama görünümleri

Menü: **Raporlar > Raporlar** (`Standart raporları görüntüleme ve indirme` yetkisi). Rol varsayılanları: Öğrenci İşleri, Akademik İşler, Bölüm Sekreteri (kapsam içinde).

## Standart raporlar

Bir rapor ve filtrelerini seçin: ekranda en fazla 500 satır gösterilir ve **CSV indir** tümünü alır (BOM'lu UTF-8, doğrudan Excel'de açılır). İndirmeler `reports.exported` denetim olayına kaydedilir. `=`, `+`, `-` veya `@` ile başlayan değerler, Excel'in bunları formül olarak çalıştırmaması için başlarına tek tırnak (`'`) eklenerek dışa aktarılır.

| Rapor | Filtreler | İçerik |
|---|---|---|
| Öğrenci listesi | Birim (altındaki birimler dahil), durum, sınıf düzeyi | Öğrenci numarası, ad, program, birim, sınıf düzeyi, durum, giriş tarihi, mezuniyet tarihi, diploma numarası |
| Birim, program, sınıf ve duruma göre öğrenci sayısı | Birim, durum | Öğrenci sayısı |
| Dönem not özeti | Dönem, akademik durum | Dönem ve genel kredi ile not ortalaması, akademik uyarı sayısı, onur sıralaması ([bölüm 21](21-grades.md)) |
| Mezuniyet adayları | Karşılandı veya karşılanmadı | Öğrenci ve program başına en son mezuniyet denetimi ([bölüm 26](26-graduation.md)) |
| Durum değişiklikleri | Dönem, tarih aralığı | Değişiklik türü, önceki ve sonraki durumlar, geçerlilik tarihi, talep durumu |
| Şube başına kayıtlı öğrenci | Dönem, birim | Şube, öğretim elemanları, kontenjan, kayıtlı, bekleyen |
| Bir dönemde ders kaydı yapmamış öğrenciler | Dönem, birim | O dönemde ders kaydı olmayan kayıtlı öğrenciler |

Görünenler, görüntüleyen kişinin yetki kapsamına (öğrenci kaydı görüntüleme kapsamı vb.) göre belirlenir. Eski sistemin (YUSTIC) ücret ve harç raporları (ödenmemiş harç vb.) kapsam dışıdır ve mevcut değildir.

## reporting görünümleri

Raporlar veritabanındaki `reporting` şemasının görünümlerini okur. BI araçları ve SQL kullanıcıları aynı görünümleri salt okunur `app_reporting` rolüyle sorgular.

| Görünüm | İçerik |
|---|---|
| `reporting.students` | Öğrenci kaydı + kişi adı + program ve birim adları |
| `reporting.headcount` | Birim, program, sınıf düzeyi ve duruma göre öğrenci sayısı |
| `reporting.term_records` | Dönem başına not özeti |
| `reporting.course_grades` | Ders başına notlar (kesinleşmiş olup olmadığı, not, geçti, ders tekrarı) |
| `reporting.section_enrollments` | Şube başına kayıtlı ve bekleyen öğrenciler, öğretim elemanları |
| `reporting.section_meetings` | Ders zamanları ve derslikler |
| `reporting.status_changes` | Durum değişiklikleri |
| `reporting.degree_audits` | Öğrenci ve program türü başına en son mezuniyet denetimi |

- Çevrilmiş sütunlar (`program_name` vb.) oturum yerel ayarına göre belirlenir: `SET app.locale = 'ko';`. Ham jsonb sütunları (`program_name_i18n` vb.) da kullanılabilir.
- Görünümler `security_invoker` olduğundan görüntüleyen kişinin satır düzeyi güvenliği aynen uygulanır. Bir öğrenci hesabıyla okunduğunda yalnızca o öğrencinin satırlarını döndürürler.
- Görünüm tanımları `database/sql/views/reporting.sql` dosyasındadır; bir değişiklikten sonra bir migration'dan yeniden uygulanırlar.

Eski `AV_*` görünümleri (yaklaşık 90 adet) kullanılmıyordu ve taşınmadı.
