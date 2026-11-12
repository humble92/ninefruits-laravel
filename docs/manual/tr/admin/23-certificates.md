# 23. Belgeler

Menü: **Belgeler > Düzenlenen belgeler**, **Belge türleri**, **Ayarlar > Belge ayarları**. Belgeler, öğrenci kaydı ekranındaki **Belge düzenle** düğmesiyle düzenlenir (**Öğrenciler ve personel > Öğrenci kayıtları > Görüntüle**). Öğrenciler kendi belge PDF'lerini portalda **Belgeler** altından indirir. Herkes bir belgeyi `/verify/{code}` sayfasında doğrulayabilir.

| Yetki | Neye izin verir |
|---|---|
| `Belge düzenleme, yeniden oluşturma ve iptal etme` | Düzenleme, yeniden oluşturma, iptal etme, düzenlenen belgeleri görüntüleme (öğrenci kaydı görüntüleme kapsamı içinde) |
| `Belge türlerini, şablonlarını ve ayarlarını yönetme` | Türler ve dil başına şablonları, seri numarası biçimi, imzalayanlar |

Rol varsayılanları: Belge Sorumlusu belge düzenler, Öğrenci İşleri her ikisine sahiptir, Akademik İşler türleri ve şablonları yönetir.

## Eski sistemden farklar

- **Düzenlenen belgeler kaydı.** Eski sistem (YUSTIC) doğrudan ekrandan yazdırıyordu (`aca3/cti_*`) ve kaydı (`SRCERT`) yalnızca numara üretmek için vardı; bu nedenle bir belgeyi kimin düzenlediğini veya iptal edilip edilmediğini göstermiyordu. Yeni sistem her düzenleme için seri numarasını, doğrulama kodunu, personeli, kullanım amacını, nüsha sayısını, PDF dosyasını ve sağlama toplamını kaydeder; iptal edilen bir belge satırını korur.
- **Doğrulama.** Eski sistemde yoktu. Her belge bir doğrulama kodu ve QR kodu taşır; herkese açık bir sayfa belgenin düzenlendiğini, türünü, düzenlenme tarihini ve maskelenmiş belge sahibini (soyadı ve adının ilk ve son harfi, öğrenci numarasının ilk 4 hanesi) gösterir.
- **Şablonlar.** Eski sistemde her tür ve dil için ayrı bir PDF betiği vardı. Yeni sistem her tür için dil başına bir şablonu (başlık, metin, alt bilgi, imzalayan) ekranda düzenler.
- **Engeller.** Eski sistem belgeleri ders kaydı engeli listesinden bağımsız olarak düzenliyordu. Yeni sistem belge engeli olan bir öğrenciye belge düzenlemez ([bölüm 20](20-registration.md)).
- Ücretler kaydedilmez (eski sistem yalnızca tutarı kaydediyordu).
- Öğrencilerin çevrim içi talebi yoktur (taşıma planı 17.5). Belgeleri personel düzenler.

## Türler ve şablonlar

**Belge türleri**: kod (seri numarasında kullanılır), ad (3 dil), kimler için (tüm öğrenci kayıtları / öğrenciler / mezunlar), notları içerir, fotoğraf içerir, etkin, sıra. Kore üniversite profili üç türü hazır olarak yükler.

| Kod | Tür | Kimler için | Not |
|---|---|---|---|
| `ENR` | Öğrenci belgesi | Öğrenciler (mezun olmayan) | |
| `GRD` | Mezuniyet belgesi | Mezunlar | Mezuniyet tarihi, diploma numarası |
| `TRN` | Transkript | Tüm öğrenci kayıtları | Döneme göre kesinleşmiş notlar tablosu, genel not ortalaması ve kazanılan kredi |

**Şablonlar** (tür düzenleme ekranının altında): dil başına bir tane. Başlık, metin (paragraflar boş bir satırla ayrılır), alt bilgi, imzalayan. Metin süslü parantez içinde yer tutucular kullanır.

| Yer tutucu | Değer |
|---|---|
| `{institution}` | Kurum adı (o dilde) |
| `{student_name}`, `{student_number}` | Ad (o dildeki biçimiyle, yoksa Latin yazımıyla), öğrenci numarası |
| `{program}`, `{org_unit}` | Program, birim |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Giriş tarihi, mezuniyet tarihi, diploma numarası |
| `{status}`, `{year_level}` | Öğrenci durumu, sınıf düzeyi |
| `{birth_date}`, `{gender}`, `{nationality}` | Doğum tarihi, cinsiyet, uyruk |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Düzenlenme tarihi, seri numarası, kullanım amacı, nüsha sayısı |

Tarihler o dilin biçiminde yazdırılır. Eksik bir değer (örn. mezuniyet tarihi) boş olarak yazdırılır. Metnin üstüne otomatik olarak bir kişisel bilgiler tablosu eklenir (ad, öğrenci numarası, doğum tarihi, cinsiyet, uyruk, program, giriş tarihi; ardından mezunlar için mezuniyet tarihi, diğerleri için durum ve sınıf düzeyi).

## Ayarlar

**Ayarlar > Belge ayarları**

- **Seri numarası biçimi**: varsayılan `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. `{SEQ:n}` zorunludur. Sıra her farklı önek için ayrı sayılır. `{TYPE}-{SEQ:6}` yıllar boyunca tür başına, `{YYYY}{SEQ:5}` türler boyunca yıl başına sayar. Eşzamanlı düzenlemeler hiçbir zaman çakışmaz (sayaç veritabanında atomik olarak artırılır).
- **Doğrulama geçerlilik süresi (gün)**: 0 her zaman demektir. Süre dolduktan sonra doğrulama sayfası "süresi doldu" yanıtını verir. Düzenlemenin kendisi kayıtta kalır.
- **Dile göre imzalayan**: mührün üstüne yazdırılan ad ("Rektör, Ninefruits Üniversitesi"). Şablondaki bir imzalayan önceliklidir.

## Düzenleme

Öğrenci kaydı ekranında **Belge düzenle**: tür, dil, kullanım amacı, nüsha (1 ile 20 arası). Kaydedildiğinde PDF yeni bir sekmede açılır.

Aşağıdaki durumlarda düzenleme reddedilir:

- türün kimler için ayarı uymuyorsa (bir mezun için öğrenci belgesi, kayıtlı bir öğrenci için mezuniyet belgesi)
- tür etkin değilse
- o dil için şablon yoksa
- öğrenci için bir **belge engeli** yürürlükteyse (bir kısıtlama, [bölüm 20](20-registration.md)); önce kaldırın
- öğrenci kaydı görüntüleme kapsamının dışındaysa

Düzenleme işlem günlüğüne kaydedilir (`certificate.issued`: öğrenci numarası, tür, dil, seri numarası, nüsha, kullanım amacı).

## Düzenlenen belgeler

**Belgeler > Düzenlenen belgeler**: düzenlenme tarihi, seri numarası, tür ve dil, öğrenci numarası, ad, kullanım amacı, nüsha, durum, personel. Türe ve duruma göre filtreleyin. Görüntüleme kapsamı öğrenci kayıtlarıyla aynıdır.

- **PDF**: saklanan dosyayı açar. Yeniden oluşturulmaz, bu nedenle tam olarak düzenlendiği hâliyledir.
- **Doğrulama sayfası**: herkese açık sayfayı açar.
- **Yeniden oluştur**: mevcut verilerden yeni bir PDF oluşturur (örn. bir şablonu düzelttikten sonra) ve dosyayı değiştirir. Seri numarası ve doğrulama kodu korunur; sağlama toplamı değişir. Denetim olayı `certificate.rerendered`. `certificate:render` komutuyla aynıdır.
- **İptal et**: gerekçe zorunludur. Satır ve dosya korunur, durum iptal edildi olur ve doğrulama sayfası "iptal edildi" yanıtını verir. Denetim olayı `certificate.revoked`. İptal edilen bir belge geri alınmaz; yeni bir belge düzenlenir.

Kayıt satırları ekranda düzenlenemez veya silinemez.

## Doğrulama

- Doğrulama URL'si (`/verify/{code}`) ve bir QR kodu belgenin altına yazdırılır. Kod rastgele 20 karakterlik bir dizedir.
- Sayfa oturum açmadan açılır ve dakikada 30 istekle sınırlıdır. Belgenin düzenlenip düzenlenmediğini, türünü, seri numarasını, düzenlenme tarihini ve maskelenmiş ad ile öğrenci numarasını gösterir. İptal edilmiş, süresi dolmuş ve bilinmeyen kodların her biri farklı bir yanıt alır.
- Teslim edilen bir PDF dosyasının kendisini kontrol etmek için kayıttaki sağlama toplamını (SHA-256) dosyanın özetiyle karşılaştırın. `certificate:verify {code}` komutu da bunu yapar.

## Öğrenci portalı

**Belgeler**: öğrencinin kendi kaydına düzenlenmiş belgeler, PDF'leriyle. İptal edilenler durumlarıyla listelenir, ancak öğrenci iptal edilmiş bir belgenin PDF'ini indiremez (düzenleyen personel kaydı kontrol etmek için açabilir). Diğer öğrencilerin dosyaları açılamaz (uygulamada ve veritabanında zorunlu kılınır).

## Sık sorulan sorular

- **Ad yanlış yazılmış.** Kişinin ad varyantını (o dilde) düzeltin ve **Yeniden oluştur** işlemini yapın. Belge zaten teslim edilmişse iptal edin ve yenisini düzenleyin.
- **Şablonu değiştirdim; zaten düzenlenmiş belgeler ne olacak?** Değişmezler. Gerekirse **Yeniden oluştur** işlemini yapın.
- **Bir seri numarası atlandı.** Düzenleme sırasında bir hata oluştuğunda (örn. PDF hizmeti çalışmıyorsa) numara tüketilir ve hiçbir kayıt satırı kalmayabilir. Numaraların benzersiz olması garanti edilir, ardışık olması değil.
