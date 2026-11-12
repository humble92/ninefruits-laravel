# 28. LMS entegrasyonu

Menü: **LMS entegrasyonu > LMS platformları**, **Ders bağlantıları**, **Eşitleme günlükleri** (`LMS platformlarını, bağlantıları ve eşitlemeyi yönetme` yetkisi; varsayılan: Sistem Yöneticisi ve Öğrenci İşleri'nin yanı sıra Akademik İşler). Öğretim elemanları **Verdiğim dersler** altındaki ve şube ayrıntısındaki LMS bağlantılarını ve not girişi ekranındaki **Puanları LMS üzerinden yükle** düğmesini kullanır. Yükleme, not girişiyle aynı koşullara tabidir (öğretim elemanları not girişi aralığı içinde, personel `Notlar` yönetim kapsamı içinde).

Geliştirici ayrıntıları [LMS entegrasyon kılavuzunda](../../../lms-integration-guide.md) yer alır.

## Ne nereye gider

| SIS → LMS | Ne zaman |
|---|---|
| Şube (ders oluşturma) + öğretim elemanları | Şube **yayımlandığında** ve `lms:sync` ile |
| Onaylanmış ders kaydı → derse kayıt | Ders kaydında, bekleme listesinden kayda almada veya personel ders kaydında hemen |
| Bırakma veya dersten çekilme → kayıt silme | Hemen |
| Tam mutabakat | Her gün 02:30'da (`lms:reconcile`) |

| LMS → SIS | Ne zaman |
|---|---|
| Son puanlar (taslak) | Öğretim elemanı not girişi ekranında **Puanları LMS üzerinden yükle** düğmesine tıkladığında. Kaydetme ve gönderme öğretim elemanı tarafından yapılır |

Kesinleşmiş notlar LMS'e gönderilmez. Ders materyalleri, ödevler ve kısa sınavlar LMS'te bulunur.

## Platformlar

**LMS platformları**: kod, ad, sürücü, temel URL, kimlik bilgileri (anahtar/değer, şifrelenmiş olarak saklanır, düzenlemede boş bırakılırsa korunur), etkin, sınıf listelerini eşitle, son puanları çek.

- **Yok (yalnızca kayıt)** sürücüsü: hiçbir şey çağırmaz ve gönderilecek olanı kaydeder. Entegrasyondan önce akışı kontrol etmek veya tanıtım için kullanın. Demo verilerindeki `DEMO` platformu bunu kullanır.
- `Moodle`, `Open edX`: henüz bağlayıcı yok (henüz uygulanmadı olarak işaretli). Seçildiğinde eşitlemeler başarısız olarak kaydedilir.
- **Etkin** açıldığında bundan sonra şube yayımlamaları ve ders kaydı değişiklikleri hemen aktarılır ve platform gece mutabakatına dahil edilir.

## Ders bağlantıları ve eşitleme günlükleri

- **Ders bağlantıları**: hangi şubenin hangi platform dersine (kimlik, URL) bağlı olduğu, durum (Beklemede/Bağlandı/Başarısız), son eşitleme ve hata. **Yeniden eşitle** şubeyi, öğretim elemanlarını ve sınıf listesini yeniden gönderir.
- **Eşitleme günlükleri**: adım başına sonuç ve mesaj (ders, üye ekleme ve çıkarma, puanları çekme, mutabakat). Bir hatanın nedenini burada arayın.
- Komutlar: `lms:sync {platform} --term={term}` (tam gönderim, `--dry-run`), `lms:reconcile` (mutabakat).

## Portal

- Platform ders bağlantısı, öğrenciler ve öğretim elemanları için ders ayrıntısında ve öğretim elemanının **Verdiğim dersler** listesinde gösterilir (yalnızca bağlı şubeler).
- Not girişi: platformda **Son puanları çek** açık ve şube bağlıysa **Puanları LMS üzerinden yükle** düğmesi görünür. Yalnızca puan sütununu doldurur ve kaydetmez.

## Sık sorulan sorular

- **Bir öğrenci LMS'te eksik.** Eşitlenebilmesi için öğrencinin bir hesabı (kullanıcı) olması gerekir. Hesabı yoksa davet edin ([bölüm 14](14-merge-and-invitations.md)); bir sonraki mutabakat onu ekler.
- **Bir eşitleme başarısız durumda kalıyor.** Eşitleme günlüğündeki mesajı okuyun. Süresi dolmuş kimlik bilgileri yaygın bir nedendir. Platformun düzenleme ekranındaki değerleri değiştirin ve **Yeniden eşitle** işlemini yapın.
- **Platform değiştirdik.** Yeni platformu oluşturun ve eskisinin Etkin ayarını kapatın. Bağlantılar her platform için ayrı oluşturulur.
