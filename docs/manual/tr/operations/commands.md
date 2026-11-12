# Artisan komut başvurusu

- Hedef kitle: BT operatörleri
- Kapsam: bu depoda kayıtlı tüm uygulama komutları. Bir komut eklendiğinde buraya da eklenmelidir, aksi halde CI başarısız olur (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Ortak kurallar

Her komut `php artisan {command}` olarak çalışır. Geliştirme ortamında `./vendor/bin/sail artisan {command}`.

| Öğe | Kural |
|---|---|
| Denetim kaydı | Veri değiştiren komutlar (aşağıda "Denetim: kaydedilir"), her çalıştırmada komutu, argümanları, seçenekleri, işletim sistemi kullanıcısını, çıkış kodunu ve sonucu işlem günlüğüne (günlük `command`) yazar. Bunları yönetim panelinde **Denetim > İşlem günlüğü** altında kontrol edin |
| Çalıştırma kimliği | Her çalıştırma, istek kimliği yerine `cmd-` ile başlayan bir kimlik alır. İşlem günlüğü, o çalıştırmanın yaptığı veri değişiklikleri ve gönderdiği kuyruk işleri aynı kimliği taşır; böylece "bu değişikliği hangi komut yaptı" sorusu kimlikle yanıtlanır. `--actor` operatörün verdiği bir değerdir, bu nedenle veri değişikliklerinin kullanıcı sütununa yazılmaz (gerçek işlemi yapan, işletim sistemi kullanıcısıdır) |
| `--actor=<email>` | Denetlenen tüm komutlarda ortaktır. Sorumlu yönetici hesabını kaydeder. Bilinmeyen bir e-posta çalıştırmayı durdurur |
| `--dry-run` | Hiçbir şeyi değiştirmeden sonucu yazdırır. Geri alınması zor işlemlerde bulunur |
| `--force` | Onay istemleri olmadan çalıştırır (zamanlamalar ve dağıtım betikleri için) |
| `--reason=` | Gerekçe gerektiren komutlarda bulunur |
| Çıkış kodları | 0 başarılı, 1 başarısız veya iptal edildi, 2 bir kontrol komutu sorun buldu |
| DB bağlamı | Komut çalışırken DB oturumundaki işlemi yapan kişi `system:command:{command}` olarak ayarlanır ve veri değişikliklerinde (tetikleyici) görünür |

## Kurulum ve demo

### `setup:install`

- Amaç: yeni kurulumlar ve güncellemeler. Sırasıyla migration'ları, temel verileri, kurum varsayılanlarını, kurulum profilini ve ilk yöneticiyi çalıştırır. Yeniden çalıştırmak güvenlidir (mevcut veriler ve yöneticilerin değiştirdiği değerler korunur).
- Ne zaman: ilk kurulumda ve her dağıtımda.
- Seçenekler: `--institution-name=`, `--locale=` (varsayılan dil), `--timezone=` (örneğin `Asia/Seoul`), `--profile=*` (kurulum profili anahtarı), `--grade-scale=` (not ölçeği `4.5` veya `4.3`. Bir kez ayarlandıktan sonra değiştirilemez. Boşsa ilk profilin ölçeği), `--admin-email=`, `--admin-name=`, `--force` (soru sormadan çalıştırır. Yönetici parolası standart girdiden okunur).
- Denetim: kaydedilir
- Örnekler

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Çıktının okunması: adım başına `DONE`/`FAIL`. `Administrator exists`, bir sistem yöneticisi atamasının zaten bulunduğu ve bu nedenle oluşturmanın atlandığı anlamına gelir.
- Başarısızlık durumunda: hata bir anahtarın eksik olduğunu söylüyorsa `key:generate` ve `pii:generate-key` ile üretilen değerleri ortam yapılandırmasına koyun. Bir migration hatası genellikle DB rollerinin hazırlanmadığı anlamına gelir (kurulum kılavuzuna bakın).

### `setup:demo`

- Amaç: factory'lerle hayali bir üniversitenin verilerini oluşturur. Rol başına sabit demo hesaplarını yazdırır (`registrar@demo.test` vb.).
- Ne zaman: geliştirme, eğitim ve tanıtım ortamları. **Üretimde (`APP_ENV=production`) `--force` ile bile çalışmaz.**
- Seçenekler: `--size=tiny|small|medium|large` (yaklaşık 20/100/2.000/20.000 öğrenci. `tiny` testler ve hızlı kontroller içindir), `--seed=` (aynı değer aynı verileri üretir), `--force`.
- `--student-accounts`: her etkin öğrenci için bir hesap oluşturur (`student{öğrenci numarası}@demo.test`, demo parolası) ve bunları `storage/app/private/load-accounts.csv` dosyasına yazar. Yük testleri içindir ([Yük testi](load-testing.md)).
- Denetim: kaydedilir
- Örnek: `php artisan setup:demo --size=small --seed=42`
- Başarısızlık durumunda: temel veriler eksikse demo hesaplarının rolleri olmaz ve atlanırlar. Önce `setup:install` çalıştırın.

## Temel veriler ve dönemler

### `reference:import`

- Amaç: temel verileri CSV'den içe aktarır. Yönetim panelindeki **CSV içe aktar** ile aynı içe aktarma kurallarını kullanır (mevcut satırlar koda göre eşleştirilip güncellenir, boş çeviri hücreleri mevcut değeri korur, yalnızca başarısız satırlar ayrıca kaydedilir).
- Argümanlar: `{type}` şunlardan biri: `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` / `{file}` başlık satırı olan UTF-8 bir CSV.
- Seçenekler: `--actor=` (zorunlu. İçe aktarma kaydı ve tamamlanma bildirimi bu hesaba gider), `--chunk=100` (kuyruk işi başına satır), `--dry-run` (yalnızca başlık kontrolü ve satır sayısı).
- Denetim: kaydedilir (tür, dosya adı, satır sayısı, başarısız satır sayısı)
- Sütun biçimi: [Yönetici kılavuzu 9. bölüm](../admin/09-csv-import.md).
- Çıkış kodları: kuyruk eşzamanlı çalışıyorsa sonuç hemen yazdırılır ve başarısız satırlar varsa çıkış kodu 2 olur. Eşzamansızsa (Horizon) içe aktarma yalnızca kabul edilir ve çıkış kodu 0 olur. Sonuç, `--actor` hesabının yönetim paneli bildirimlerinde başarısız satırlar CSV'si olarak gelir.
- Örnek: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Amaç: belirli bir zamanda süren dönemi ve açık işlem aralıklarını (tür, dönem, hedef ölçütleri, bitiş zamanı) gösterir. Dönem ve işlem aralığı yapılandırmasını kontrol etmek veya "ders kaydı neden açık değil" gibi soruları yanıtlamak için kullanın.
- Seçenekler: `--at=` (varsayılan şimdi. Örneğin `"2027-02-15 09:00"`. Saat dilimi verilmezse sunucu saat diliminde (UTC) yorumlanır. Bir saat dilimi `"2027-02-15 09:00 Asia/Seoul"` biçiminde verilebilir).
- Denetim: kaydedilmez
- Çıktının okunması: tarih kararları kurum saat dilimini kullanır. Planlanan veya arşivlenmiş durumdaki dönemlerin işlem aralıkları hiçbir zaman açılmaz.

## Güvenlik

### `security:create-administrator`

- Amaç: sistem yöneticisi rolüne sahip bir hesap oluşturur. Genellikle `setup:install` tarafından çağrılır.
- Seçenekler: `--email=`, `--name=`, `--password-stdin` (parola standart girdiden. Etkileşimsiz çalıştırmalarda zorunludur).
- Denetim: kaydedilir (oluşturulan kullanıcının kimliği)
- Örnek: `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- Başarısızlık durumunda: `system_administrator role is missing`, önce `setup:install` veya `db:seed` çalıştırılması gerektiği anlamına gelir. Parola en az 12 karakter olmalıdır.

### `security:sync-permissions`

- Amaç: DB'deki yetki listesini koddaki yetki kataloğuyla eşitler. Yeni yetkiler oluşturulur; katalogda bulunmayan yetkiler silinir (ve tüm rollerden kaldırılır). Sistem rolleri her zaman tüm yetkileri alır.
- Ne zaman: dağıtımdan sonra (`setup:install` bunu temel veri adımının parçası olarak yapar).
- Seçenekler: `--dry-run` (yalnızca oluşturma/silme listesini yazdırır), `--force` (silme onayını atlar).
- Denetim: kaydedilir (oluşturulan/silinen yetkiler)
- Çıktının okunması: `create` ve `delete` satırları. Silme olduğunda bir onay istemi görünür.
- Başarısızlık durumunda: onaya "hayır" yanıtı vermek 1 koduyla çıkar ve hiçbir şeyi değiştirmez.

### `security:reset-mfa`

- Amaç: bir kullanıcının iki adımlı doğrulamasını (kimlik doğrulayıcı uygulama, e-posta kodları, kurtarma kodları, hatırlanan cihazlar) sıfırlar. Kullanıcı bir sonraki oturum açışında yeniden kayıt yapar. Hiçbir yöneticinin yönetim paneline oturum açamadığı durumlarda kullanın (örneğin tek yönetici telefonunu kaybetti). Diğer durumlarda yönetim panelindeki **Kullanıcılar > İki adımlı doğrulamayı sıfırla** işlemini kullanın.
- Argümanlar/seçenekler: `{user}` kullanıcı e-postası, `--reason=` (zorunlu), `--actor=` (zorunlu. Sıfırlamaya karar veren yönetici).
- Denetim: kaydedilir (hem işlem günlüğünde hem güvenlik günlüğünde, gerekçeyle birlikte)
- Örnek: `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Amaç: tekrarlanan hatalı parolalar nedeniyle kilitlenen bir hesabın kilidini, kilit süresi dolmadan kaldırır. Yönetim panelindeki **Kullanıcılar > Oturum açma kilidini kaldır** ile aynıdır.
- Argümanlar/seçenekler: `{user}` kullanıcı e-postası, `--reason=` (zorunlu).
- Denetim: kaydedilir (güvenlik günlüğünde de)
- Çıktının okunması: `The account is not locked.` hiçbir şeyin değiştirilmediği anlamına gelir.
- Not: kilit eşikleri (başarısız deneme sayısı, kilit süresi) yönetim panelinde **Ayarlar > Oturum açma yöntemleri** altında ayarlanır.

## Kişiler ve öğrenci kayıtları

### `people:find-duplicates`

- Amaç: aynı kişi gibi görünen kişileri listeler. Ölçütler, aynı kimlik numarası (arama karması) veya aynı Latin harfli ad ve doğum tarihidir. Birleştirilmiş kişiler hariç tutulur.
- Seçenekler: `--limit=100` (yazdırılacak grup sayısı).
- Denetim: kaydedilmez (salt okunur)
- Çıkış kodları: mükerrer adaylar varsa 2.
- Sonraki adım: her grubu yönetim panelinde **Kişiler** altında inceleyin ve aynı kişiyse korunacak kişinin sayfasından **Mükerrer kaydı birleştir** işlemini çalıştırın. Birleştirme geri alınamaz ([Yönetici kılavuzu 14. bölüm](../admin/14-merge-and-invitations.md)).

## Öğrenci kabul

### `admission:matriculate`

- Amaç: bir başvuru dönemindeki kesin kaydı onaylanmış adaylar için öğrenci kayıtları (yeni öğrenci numaraları) oluşturur. Yönetim panelinde **Öğrenci kabul > Başvurular** altındaki toplu **Öğrenci kaydı oluştur** işlemiyle aynıdır. Her aday ayrı işlenir; böylece bir hata diğerlerini durdurmaz.
- Argümanlar/seçenekler: `{cycle}` başvuru dönemi kodu, `--term=` (zorunlu. Kabul dönemi kodu, örneğin `2027-SP`. Kabul tarihi dönemin ilk günüdür), `--actor=` (zorunlu. `Kabul edilen adaylar için öğrenci kaydı oluşturma` yetkisine sahip bir hesap. Yalnızca o hesabın yetki kapsamındaki adaylar işlenir), `--dry-run` (yalnızca hedef listesi).
- Denetim: kaydedilir (başvuru dönemi, dönem, hedef sayısı, oluşturulan sayısı, başarısız başvuru numaraları). Her aday için ayrıca bir işlem kaydı (`application.matriculated`) yazılır.
- Çıkış kodları: herhangi bir aday başarısız olursa 2.
- Örnek: `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- Başarısızlık durumunda: `not ready`, öğrenci kaydının zaten bulunduğu veya adayın kesin kayıt onaylanmış durumda olmadığı anlamına gelir; `program is not in effect`, programın kabul tarihinde yürürlükte olmadığı anlamına gelir (Temel veriler > Programlar altında geçerlilik süresini kontrol edin).

### `admission:expire-drafts`

- Amaç: kapanmış başvuru dönemlerinin taslak başvurularını ve yapılandırılan süre boyunca dokunulmamış taslak başvuruları (**Ayarlar > Öğrenci kabul > Dokunulmayan taslakların süresi dolsun (gün sonra)**, varsayılan 90 gün) **Süresi doldu** olarak işaretler. Süresi dolan başvurular, aynı başvuru döneminde yeni bir başvuruyu engellemez.
- Ne zaman: zamanlanmış (her gün 03:10'da, [Zamanlanmış işler](scheduled-jobs.md)).
- Seçenekler: `--dry-run` (yalnızca sayı).
- Denetim: kaydedilir (süresi dolan sayısı)

## Şubeler

### `section:copy`

- Amaç: yeni bir dönemin planlamasını önceki bir dönemin şubelerinden başlatır. Yönetim panelindeki **Şubeler > Başka bir dönemden kopyala** ile aynıdır. İptal edilmemiş şubeleri; öğretim elemanları (hâlâ görevdeyse), ders zamanları ve derslikler, ayrılmış kontenjanlar (serbest bırakma zamanları dönem başlangıç tarihleri arasındaki fark kadar kaydırılır) ve ders izlencesiyle (yayımlanmamış taslak olarak) birlikte taslak olarak kopyalar. Ders sürümü, yeni alt dönemin başlangıcında yürürlükte olan sürümle değiştirilir.
- Argümanlar/seçenekler: `{from-term}`, `{to-term}` dönem kodları (örneğin `2026-FA`), `--org-unit=*` (yalnızca bu birim kodunun şubeleri), `--dry-run` (yalnızca sonuçlar, hiçbir şey kaydedilmez), `--actor=` (zorunlu. Yalnızca o hesabın `Şubeleri planlama` yetki kapsamındaki şubeler kopyalanır).
- Denetim: kaydedilir (kopyalanan, atlanan ve inceleme gerektiren sayıları)
- Çıktının okunması: `skipped`, hedef dönemde aynı şube kodunun zaten bulunduğu veya yürürlükte bir ders sürümü olmadığı anlamına gelir; `note`, şubenin kopyalandığı ancak bir şeyin dışarıda bırakıldığı anlamına gelir (derslik zaten dolu olduğu için dersliksiz kopyalandı, emekli bir öğretim elemanı vb.). Alt dönemler koda göre eşleştirilir; eşleşme yoksa şube tam dönem alt dönemine girer.
- Örnek: `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Amaç: ders programını yayımlamadan önce bir dönemin şubelerini kontrol eder. Dersliklerin ve öğretim elemanlarının çift rezervasyonu zaten DB tarafından engellenir; bu komut DB'nin izin verdiği sorunları bulur: derslik kapasitesinin üzerinde kontenjan, alt dönem başlangıç tarihinde yürürlükte olmayan bir ders sürümü, emekli bir öğretim elemanı, kontenjanın üzerinde ayrılmış kontenjanlar, alt dönemin dışındaki ders tarihleri. Ayrıca yedekten geri yükleme gibi kısıtlamalar olmadan gelen verilerdeki derslik çakışmalarını da kontrol eder.
- Argümanlar: `{term}` dönem kodu.
- Denetim: kaydedilmez (salt okunur)
- Çıkış kodları: sorun varsa 2. Henüz öğretim elemanı veya dersliği olmayan şubeler yalnızca bilgi amaçlı gösterilir.

## Ders kaydı

### `registration:preflight`

- Amaç: ders kaydını açmadan önce bir dönemi kontrol eder. Ders kaydı ve ekle/bırak işlem aralıklarını, kural setini, yayımlanmış şube sayısını, kontenjanı 0 olan şubeleri, kontenjanın üzerindeki ayrılmış kontenjanları, alt dönem başlangıç tarihinde yürürlükte olmayan ders sürümlerini, bir kredi sınırı kapsamında olmayan etkin öğrenci sayısını ve ders kaydı kısıtlaması olan öğrenci sayısını gösterir.
- Argümanlar: `{term}` dönem kodu (örneğin `2027-SP`).
- Denetim: kaydedilmez (salt okunur)
- Çıkış kodları: sorun varsa 2. Eksik bir kural seti sorun olarak değil, bilgi olarak gösterilir (varsayılan kurallar uygulanır).
- Örnek: `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Amaç: öğrenci şubeye şimdi (veya belirli bir zamanda) kayıt olsaydı ne olacağını kural kural gösterir. Hiçbir şey kaydedilmez ve hiçbir deneme kaydı tutulmaz. "Neden kayıt olamıyorum?" soruları için kullanın.
- Argümanlar: `{student-number}` öğrenci numarası, `{section}` `ders kodu-şube kodu` (örneğin `CSE2010-01`).
- Seçenekler: `--term=` (dönem kodu. Varsayılan, o şubeye sahip en son dönemdir), `--at=` (değerlendirme zamanı. Örneğin `"2027-02-10 10:00 Asia/Seoul"`).
- Denetim: kaydedilmez (salt okunur)
- Çıktının okunması: kural başına geçti/kaldı, engelle/uyar ve mesaj. Bir kural istisnasının kapsadığı kural başarısız olarak gösterilir ancak engellemez. Kontenjan, kural tablosunun altında ayrıca gösterilir.
- Çıkış kodları: ders kaydı reddedilecekse 2.

### `waitlist:promote`

- Amaç: boş kontenjanı olan şubeler için bekleme listesindeki öğrencileri sırayla kaydeder. Kayıt sırasında tüm kurallar yeniden kontrol edilir; bir kuralı sağlamayan bekleme listesi kayıtları bekleme listesinden çıkarılır (gerekçesi kaydedilir). Ders kaydı ve ekle/bırak işlem aralıklarının ikisi de kapanmış şubelerin bekleme listelerinin süresi dolar. Öğrenciler sonucu e-postayla alır.
- Argümanlar/seçenekler: `{term?}` dönem kodu (varsayılan: bekleme listesi kaydı olan tüm dönemler), `--actor=`.
- Denetim: kaydedilir (şube, kaydedilen, çıkarılan, süresi dolan sayıları)
- Ne zaman: bir ders bırakıldığında, dersten çekildiğinde veya ayrılmış kontenjan serbest bırakıldığında, etkilenen şube bir kuyruk işiyle hemen işlenir. Bu komut, kaçırılan her şeyi işleyen saatlik zamanlanmış güvenlik ağıdır.
- Örnek: `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Amaç: serbest bırakma zamanı geçmiş ayrılmış kontenjanları (örneğin ana dal öğrencilerine ayrılmış kontenjanlar) işlendi olarak işaretler ve o şubeler için bekleme listesinden kayıt işini kuyruğa alır. Ayrılmış kontenjanlar, serbest bırakma zamanı geçer geçmez hesaplamada genel kontenjana dönüşür; bu nedenle bu komut yalnızca kontenjanları bekleme listesine devreder.
- Denetim: kaydedilmez (5 dakikada bir çalışır. Kayıt sonuçları ders kaydı kayıtlarında bulunur)
- Ne zaman: zamanlanmış (5 dakikada bir).

## Notlar

### `term:close`

- Amaç: bir dönemi kapatır. Dönemdeki tüm notlar kesinleşmiş olmalıdır. Her öğrenci için dönem kaydını (krediler, dönem ve genel not ortalaması) hesaplar, akademik durumu (iyi durum, akademik uyarı, ilişik kesme incelemesi) ve onur derecesi uygunluğunu (kuyruk toplu işi) belirler, ardından onur derecelerini program başına sıralar, dönem kayıtlarını resmî kayıt olarak kapatır ve dönemi **Notlar kapatıldı** durumuna getirir. Yönetim panelindeki **Notlar > Dönem kayıtları > Dönemi kapat** ile aynıdır.
- Argümanlar/seçenekler: `{term}` dönem kodu, `--dry-run` (yalnızca dönemin kapatılıp kapatılamayacağını kontrol eder), `--reason=` (kayıtta tutulan gerekçe), `--actor=`.
- Denetim: kaydedilir (öğrenci sayısı, notları kesinleşmemiş şube sayısı). Kapatma tamamlandığında `term.closed` denetim kaydı akademik uyarı, ilişik kesme incelemesi ve onur derecesi sayılarını tutar.
- Çıkış kodları: bazı şubelerde kesinleşmemiş notlar varsa 2 (şube başına kesinleşmemiş sayısı yazdırılır). Dönem yayımlanmış durumda değilse 1.
- Ne zaman: not girişi ve kesinleştirme tamamlandıktan sonra. Toplu iş ilerlemesini Horizon'dan izleyin. Kuyruk çalışanı yoksa dönem durumu değişmez.
- Örnekler: `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Amaç: dönem kayıtlarını (krediler, dönem ve genel not ortalaması) kesinleşmiş notlardan yeniden hesaplar. Değiştirilen bir not politikasını (not ortalaması ondalık basamakları, ders tekrarı işleme) geçmiş dönemlere uygulamak için kullanın. Akademik durum ve onur dereceleri dönem kapatmada belirlenen değerleri korur. Onaylanmış bir not düzeltmesi o öğrenciyi otomatik olarak yeniden hesaplar; bu nedenle bunun için bu komut gerekmez.
- Seçenekler: `--term=` (o dönemde notu olan öğrenciler, o dönemden itibaren), `--student-number=` (tek bir öğrenci), `--reason=` (zorunlu), `--actor=`.
- Denetim: kaydedilir (öğrenci sayısı, gerekçe)
- Örnek: `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Amaç: notları henüz gönderilmemiş (girilmemiş veya taslak olarak kaydedilmiş) şubelerin öğretim elemanlarına e-posta gönderir. Şubeler öğretim elemanı başına gruplanır ve her birine tek bir mesaj gider.
- Argümanlar/seçenekler: `{term}` dönem kodu, `--dry-run` (göndermeden hedefleri yazdırır), `--actor=`.
- Denetim: kaydedilir (şubeler, öğretim elemanları, gönderilen mesajlar)
- Ne zaman: not girişi işlem aralığı kapanmadan birkaç gün önce. Gerekirse zamanlamaya ekleyin.

## Ders değerlendirme

### `evaluation:remind`

- Amaç: ders değerlendirme işlem aralığı açıkken hâlâ değerlendirmediği dersleri olan öğrencilere e-posta gönderir. Kalan dersler öğrenci başına tek bir mesajda gruplanır.
- Argümanlar/seçenekler: `{term}` dönem kodu, `--dry-run` (yalnızca hedef öğrenci sayısı, hiçbir şey gönderilmez), `--actor=`.
- Denetim: kaydedilir (hedef öğrenciler, gönderilen mesajlar)
- Ne zaman: değerlendirme işlem aralığı boyunca bir veya iki kez. Gerekirse zamanlamaya ekleyin.

### `evaluation:publish-results`

- Amaç: dönemin ders değerlendirme sonuçlarını öğretim elemanlarına yayımlar. Yönetim panelindeki **Ders değerlendirme > Dönem değerlendirmeleri > Sonuçları yayımla** ile aynıdır. Yayımlandıktan sonra bile sonuçlar yalnızca en az yanıt sayısına ve yanıt oranına ulaşan şubeler için görünür.
- Argümanlar/seçenekler: `{term}` dönem kodu, `--actor=`.
- Denetim: kaydedilir (yanıt sayısı, daha önce yayımlanıp yayımlanmadığı)
- Ne zaman: değerlendirme işlem aralığı ve not işlemleri tamamlandıktan sonra. Daha önce yayımlanmış bir dönem değiştirilmez.

## Belgeler

### `certificate:verify`

- Amaç: bir belge doğrulama kodunu, herkese açık doğrulama sayfasıyla aynı ölçütlerle kontrol eder. Türü, seri numarasını, düzenleme tarihini ve maskelenmiş belge sahibini yazdırır.
- Argümanlar/seçenekler: `{code}` doğrulama kodu.
- Denetim: yok (salt okunur)
- Çıkış kodları: geçerliyse 0; bilinmeyen kod, iptal edilmiş belge veya geçerlilik süresi dolmuşsa 2.

### `certificate:render`

- Amaç: düzenlenmiş bir belgenin PDF'ini güncel veri ve şablonla yeniden oluşturur ve arşivlenmiş dosyanın yerine koyar. Yönetim panelindeki **Düzenlenen belgeler > Yeniden oluştur** ile aynıdır. Seri numarası ve doğrulama kodu aynı kalır; sağlama toplamı değişir.
- Argümanlar/seçenekler: `{issue}` seri numarası, `--reason=` (zorunlu), `--actor=`.
- Denetim: kaydedilir (seri numarası, yeni sağlama toplamı)
- Ne zaman: bir şablonu veya ad yazımını düzelttikten sonra belirli bir belgeyi yeniden üretmek için.

## Sınavlar

### `assessment:import-results`

- Amaç: bir sınav oturumunun sonuçlarını CSV'den girer. Yönetim panelindeki **Sınav oturumları > Sonuçları içe aktar (CSV)** ile aynı kurallar geçerlidir (yalnızca kayıtlı öğrenciler, geçti/kaldı puandan belirlenir, yayımlamadan önce yeniden giriş sonuçların yerini alır).
- Argümanlar/seçenekler: `{type}` sınav türü kodu / `{held_date}` sınav tarihi `YYYY-MM-DD` / `{file}` başlık satırı olan UTF-8 bir CSV, `--actor=` (zorunlu), `--dry-run` (yalnızca satır doğrulaması, hiçbir şey kaydedilmez).
- Sütun biçimi: [Yönetici kılavuzu 24. bölüm](../admin/24-assessments.md).
- Denetim: kaydedilir (oturum, dosya adı, satır sayısı, girilen/devamsız/hatalı sayıları)
- Çıkış kodları: hatalı satırlar varsa 2 (diğer satırlar kaydedilir).
- Örnek: `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Amaç: bir oturumun sonuçlarını öğrencilere yayımlar. Yönetim panelindeki **Sınav oturumları > Sonuçları yayımla** ile aynıdır.
- Argümanlar/seçenekler: `{type}` sınav türü kodu / `{held_date}` sınav tarihi, `--actor=` (zorunlu).
- Denetim: kaydedilir (oturum, sonuç sayısı)
- Ne zaman: sonuç girişi tamamlandıktan sonra. Daha önce yayımlanmış bir oturum başarısız olur.

## Devam

### `attendance:generate-sessions`

- Amaç: bir dönemin her şubesi için ders zamanlarından (gün, saat, süre) ders oturumları oluşturur. Öğretim elemanı portalı ve yönetim panelindeki **Oturumları oluştur** ile aynıdır. Mevcut oturumlar ve devam kayıtları korunur (yeniden çalıştırmak güvenlidir).
- Argümanlar/seçenekler: `{term}` dönem kodu, `--section=` (ders kodu-şube kodu, örneğin `CSE2010-01`, yalnızca bir tane), `--dry-run` (yalnızca hedef şube sayısı), `--actor=`.
- Denetim: kaydedilir (dönem, şubeler, oluşturulan oturumlar)
- Ne zaman: dönem başlamadan önce, ders zamanları kesinleştikten sonra. Ders zamanları değiştiğinde yeniden çalıştırın.

## Mezuniyet

### `degree-audit:run`

- Amaç: her etkin öğrenci için bir mezuniyet koşulları denetimi kaydeder. Yönetim panelindeki **Mezuniyet denetimleri > Denetimleri çalıştır** ile aynıdır. Kaydedilen en son denetim, mezuniyet işlemlerinin temelidir.
- Seçenekler: `--term=` (denetimin ait olduğu dönem kodu), `--student-number=` (tek bir öğrenci), `--min-year-level=` (yalnızca bu sınıf düzeyi ve üzeri), `--actor=`.
- Denetim: kaydedilir (öğrenci sayısı, koşulları sağlayan sayısı)
- Ne zaman: mezuniyet incelemesinden önce (dönem sonu) ve koşul setlerini veya eşdeğer dersleri değiştirdikten sonra.

## LMS entegrasyonu

### `lms:sync`

- Amaç: yayımlanmış şubeleri platforma gönderir (ders oluşturma ve bağlama, öğretim elemanları, kayıtlı öğrenciler). Yönetim panelinde **Ders bağlantıları > Yeniden eşitle** işleminin yaptığını bütün bir dönem için yapar. Zaten bağlı olan şubeler yalnızca yeniden güvence altına alınır (idempotent).
- Argümanlar/seçenekler: `{platform}` platform kodu, `--term=` (dönem kodu, varsayılan bitmemiş tüm dönemler), `--section=` (tek bir ders kodu-şube kodu), `--dry-run` (yalnızca gönderilecekleri "skipped" olarak kaydeder), `--actor=`.
- Denetim: kaydedilir (platform, şubeler, üyeler, hatalar)
- Çıkış kodları: herhangi bir şube başarısız olursa 2.

### `lms:reconcile`

- Amaç: platformdaki ders listelerini öğrenci bilgi sistemindeki kayıt listeleriyle karşılaştırır, eksik üyeleri ekler ve fazla olanları çıkarır. Farklar eşitleme günlüğünde (`reconcile`) tutulur.
- Argümanlar/seçenekler: `{platform?}` (atlanırsa tüm etkin platformlar), `--term=`, `--dry-run` (yalnızca farkları kaydeder), `--actor=`.
- Denetim: kaydedilir (platform başına şubeler, eklenen/çıkarılan/başarısız sayıları)
- Ne zaman: her gün 02:30'da zamanlanmış. Bir platform kesintisinden sonra elle çalıştırın.

## İşletim kontrolleri ve temizlik

### `system:health-check`

- Amaç: yapılandırmayı ve anahtarları (APP_KEY, PII anahtarları, APP_DEBUG), DB bağlantısını ve rolleri (runtime, migrator, reporting), kişisel veri tablolarındaki RLS'yi, bekleyen migration'ları, yetki kataloğu eşitlemesini, Redis'i, kuyruğu (Horizon), Gotenberg'i, medya depolamaya yazmayı ve zamanlayıcı nabzını kontrol eder.
- Seçenekler: `--json` (izleme için çıktı).
- Denetim: yok (salt okunur)
- Çıkış kodları: herhangi bir kontrol başarısız olursa 2.
- Ne zaman: dağıtımdan hemen sonra, bir kesinti şüphesi olduğunda, izleme sisteminden periyodik olarak.

### `audit:seal`

- Amaç: denetim tablolarının yeni satırlarını karma zincirine bağlar ([Denetim bütünlüğü](audit-and-maintenance.md)).
- Seçenekler: `--table=` (record_changes, activity_log, security_events seçeneklerinden biri; tekrarlanabilir), `--actor=`.
- Denetim: kaydedilir (tablo başına mühürlenen satırlar)
- Ne zaman: her gün 03:30'da zamanlanmış.

### `audit:verify-chain`

- Amaç: kurcalamayı, silmeleri ve sıra değişikliklerini bulmak için denetim satırlarının içerik karmalarını ve zincirini yeniden hesaplar.
- Seçenekler: `--table=`.
- Denetim: yok (salt okunur)
- Çıkış kodları: uyuşmazlık varsa 2.
- Ne zaman: yedeklemelerden önce, güvenlik incelemeleri sırasında, temizlemeden sonra.

### `audit:prune`

- Amaç: saklama süresi geçmiş **mühürlenmiş** denetim satırlarını siler. Şema sahibi bağlantısıyla çalışır.
- Seçenekler: `--before=YYYY-MM-DD` (zorunlu), `--table=`, `--dry-run` (yalnızca sayılar), `--actor=`.
- Denetim: kaydedilir (kesim tarihi, tablo başına silinen satırlar)
- Ne zaman: saklama politikasına göre, bir yedeklemeden sonra.

### `media:prune-orphans`

- Amaç: sahibi artık bulunmayan medya satırlarını ve satırı olmayan dizinleri siler (kişi fotoğrafları, başvuru evrakları, belge PDF'leri, sınav kanıtları).
- Seçenekler: `--dry-run` (yalnızca liste), `--actor=`.
- Denetim: kaydedilir (satırlar, dizinler)
- Ne zaman: ayda bir veya toplu bir silme işleminden sonra.

## Kişisel veri şifreleme anahtarları

### `pii:generate-key`

- Amaç: `PII_ENCRYPTION_KEY` ve `PII_HASH_KEY` için yeni anahtarlar yazdırır. Hiçbir şey kaydetmez.
- Denetim: kaydedilmez
- Dikkat: `PII_HASH_KEY` değiştirilirse tüm arama karmaları geçersiz olur. Anahtar yenileme prosedürü [security-keys.md](security-keys.md) belgesindedir.

### `pii:reencrypt`

- Amaç: bir anahtar yenilemesinden sonra, önceki bir anahtarla şifrelenmiş kişisel verileri geçerli anahtarla yeniden şifreler. Kuyruk toplu işi olarak çalışır.
- Argümanlar: model sınıfı (şu anda `App\\Models\\Person\\Person`).
- Seçenekler: `--chunk=500`, `--dry-run` (yalnızca önceki bir anahtarı kullanan kayıtları sayar), `--force`.
- Denetim: kaydedilir (toplu iş kimliği, iş sayısı / önizleme sayısı)
- Doğrulama: toplu iş ilerlemesini Horizon'dan (`/horizon`) izleyin. Tamamlandığında ve `--dry-run` 0 raporladığında önceki anahtarı `PII_PREVIOUS_KEYS` içinden kaldırın.

## Yerelleştirme

### `i18n:missing`

- Amaç: her dil için temel dile (en) göre eksik olan anahtarları ve artık kullanılmayan anahtarları gösterir.
- Argümanlar/seçenekler: `{locale?}` (yalnızca bir dil), `--strict` (anahtarlar eksikse çıkış kodu 2. CI'da kullanılır).
- Denetim: kaydedilmez

### `i18n:export`

- Amaç: çevirmenlere verilecek bir JSON dosyası oluşturur. Biçim: `{"key": {"source": "English text", "translation": "translation"}}`.
- Argümanlar/seçenekler: `{locale} {path}`, `--missing` (yalnızca eksik anahtarlar).
- Denetim: kaydedilmez
- Örnek: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Amaç: çevirmenlerin doldurduğu JSON'u `lang/{locale}` dizinine uygular. Yalnızca temel dilde bulunan anahtarlar içe aktarılır; boş çeviriler yok sayılır.
- Argümanlar/seçenekler: `{locale} {path}`, `--dry-run` (yalnızca yazılacak anahtarların listesini yazdırır).
- Denetim: kaydedilir
- Dikkat: çeviri dosyaları depoda yönetilir. İçe aktardıktan sonra değişiklikleri commit edin.

## Kontroller

### `naming:check`

- Amaç: migration'lardaki tablo ve sütun adlarını [adlandırma kurallarına](../../../development/naming.md) göre kontrol eder.
- Seçenekler: `--path=*` (kontrol edilecek dizinler, varsayılan `database/migrations`).
- Denetim: kaydedilmez
- Çıkış kodları: sorun varsa 2.

### `pdf:sample`

- Amaç: yazı tiplerini ve PDF servisine (Gotenberg) bağlantıyı kontrol etmek için Hangıl, CJK, Lao, Tay, Devanagari, Kiril ve Latin yazılarını karıştıran bir tabloyu PDF olarak oluşturur.
- Argümanlar: `{path=pdf-sample.pdf}` (yerel disk `storage/app/private` dizinine göre).
- Denetim: kaydedilmez
- Başarısızlık durumunda: bağlantı hatasında `GOTENBERG_URL` değerini ve Gotenberg konteynerinin durumunu kontrol edin.
