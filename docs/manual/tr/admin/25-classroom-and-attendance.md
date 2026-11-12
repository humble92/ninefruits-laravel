# 25. Öğretim elemanı sınıf işlemleri ve devam

Menü: öğretim elemanları portalda **Verdiğim dersler > Sınıf listesi ve devam**, öğrenciler portalda **Devam** ekranını kullanır. Personel **Müfredat ve şubeler > Şubeler > Düzenle** ekranının altındaki **Ders oturumları** bölümünü kullanır.

| Kim | Neler yapabilir |
|---|---|
| Atanmış öğretim elemanları | Sınıf listesi (fotoğraflı) ve CSV, yoklama çizelgesi PDF'i, ders oturumlarını oluşturma, ekleme ve iptal etme, devam bilgisi girme |
| `Şubeleri planlama` yetkisine sahip personel (kapsam içinde) | Öğretim elemanlarıyla aynı + yönetim panelinde ders oturumları |
| `Şubeleri görüntüleme` yetkisine sahip personel (kapsam içinde) | Sınıf listesi, yoklama çizelgesi, devam bilgisini görüntüleme |
| Öğrenciler | Kendi devam durumları |

Duyurular, ödevler ve ders materyalleri gibi sınıf özellikleri LMS'e bırakılmıştır (taşıma planı bölüm 12).

## Eski sistemden farklar

- **Eski sistem (YUSTIC) devam bilgisini kaydetmiyordu.** "Yoklama çizelgesi", sınıf listesinin yanına tarih ve toplam olmadan boş kutular (nüshaya göre değişen 27 ila 34 adet) basan bir PDF'ti. Yeni sistem ders zamanlarından **ders oturumları** oluşturur, her oturum için var, geç, yok veya mazeretli bilgisini kaydeder ve kayıtları ile toplamları yoklama çizelgesi PDF'ine basar.
- **Sınıf listesi.** Eski öğretim elemanı ekranı yalnızca öğrenci numaralarını ve adları gösteriyordu (açılır pencerede kimlik numarasını ve adresi açığa çıkarıyordu) ve Excel indirme seçeneği yoktu. Yeni sistem yalnızca öğrenci numarası, ad, fotoğraf, program, sınıf düzeyi ve iletişim e-postasını gösterir ve CSV olarak indirilir. İndirmeler denetim günlüğüne kaydedilir.
- **Öğretim elemanlarının öğrencilerine erişimi.** Öğretim elemanlarının şubelerindeki öğrencilerin öğrenci kayıtlarını ve kişi bilgilerini görebilmesi için bir RLS politikası eklendi (önceden yalnızca danışmanlık öğrencilerini görebiliyorlardı; bu yüzden not girişi ekranında bile adlar boş kalıyordu).

## Ders oturumları

- **Oluşturma**: haftalık ders zamanlarının (gün, saat, ders saati) her tekrarı için bir oturum. Öğretim elemanları **Ders zamanlarından oluştur** düğmesini; personel yönetim panelinde **Ders oturumları > Oturumları oluştur** işlemini veya `attendance:generate-sessions {term}` komutunu (tüm dönem) kullanır. Mevcut oturumlara ve devam bilgilerine dokunulmaz, bu nedenle ders zamanları değiştikten sonra yeniden çalıştırılabilir.
- **Oturum ekle**: telafi dersi gibi ders zamanları dışındaki bir ders. Yalnızca alt dönem içindeki tarihler.
- **İptal et**: oturumu gerekçeyle iptal edin. Oturum kalır ancak toplamların dışında tutulur. **Geri al** onu geri getirir.
- Tatil takvimi yoktur. Resmî tatillerdeki oturumları iptal edin (bkz. bölüm 13.2; bir dönem takvimi eklendiğinde bu otomatik hâle gelecek).

## Devam bilgisi girme

Oturumun **Devam** ekranında her öğrenci için var, geç, yok veya mazeretli seçin ve bir not ekleyin (200 karakter). En hızlı yol **Hepsi var** seçip ardından istisnaları değiştirmektir.

- Devam bilgisi oturum gününden itibaren girilebilir (gelecekteki oturumlar için girilemez).
- Yeniden kaydetmek yalnızca değişen öğrencileri kaydeder ve denetim olayı (`attendance.recorded`) eski ve yeni değerleri saklar.
- Durumu temizlemek öğrenciyi yeniden "kaydedilmedi" durumuna getirir.

## Toplamlar

| Öğe | Tanım |
|---|---|
| Yapılan oturumlar | İptal edilmemiş ve bugün veya öncesine denk gelen oturumlar |
| Devam oranı | (var + geç + mazeretli) ÷ yapılan oturumlar × 100 |
| Kaydedilmedi | Kaydı olmayan yapılmış oturumlar |

Belirli sayıda geç kalmayı devamsızlığa çeviren veya üçte birin üzerindeki devamsızlıkta F veren bir kural yoktur. Bu, üniversiteden üniversiteye değiştiği için öğretim elemanları devamı nota kendileri yansıtır (inceleme maddesi 13.5).

## Yoklama çizelgesi PDF'i ve sınıf listesi CSV'si

Her iki indirme de denetim günlüğüne kaydedilir (`attendance.sheet_exported`; sınıf listesi CSV'si önceki gibi). `=`, `+`, `-` veya `@` ile başlayan CSV değerleri formül olarak çalıştırılmamaları için başlarına tek tırnak eklenerek dışa aktarılır.

- **Yoklama çizelgesi (PDF)**: yatay A4. Öğrenciler satırlarda, oturumlar sütunlarda (iptal edilenler hariç), öğrenci başına O/△/X/E işaretleri ve toplamlar. Henüz yapılmamış oturumlar boştur, bu nedenle elle kayıt için de kullanılabilir.
- **Sınıf listesi (CSV)**: öğrenci numarası, ad, program, sınıf düzeyi, iletişim e-postası, kayıt durumu. BOM'lu UTF-8, bu nedenle doğrudan Excel'de açılır. Her indirme `attendance.roster_exported` denetim olayına kaydedilir.

## Öğrenci portalı

**Devam**: dönem ve ders başına var, geç, yok ve mazeretli sayıları, devam oranı ve oturum başına kayıt (yalnızca öğretim elemanının girdikleri).

## İşletim prosedürü

1. Dönem başlamadan önce şubelerin ders zamanlarını kesinleştirin ([bölüm 18](18-sections-and-timetables.md)).
2. Oturumları `php artisan attendance:generate-sessions {term} --actor=...` ile oluşturun (öğretim elemanları da ekranda oluşturabilir).
3. Ders zamanları değiştiğinde yeniden çalıştırın. Kaldırılan zamanların oturumları otomatik olarak silinmez; bunları iptal edin.
