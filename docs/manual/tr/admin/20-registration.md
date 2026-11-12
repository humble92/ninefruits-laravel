# 20. Ders kaydı

Menü: **Ders kaydı > Ders kayıtları**, **Kısıtlamalar**, **Kural istisnaları**, **Ders kaydı kuralları**, **Kredi sınırları**, **Ders kaydı denemeleri** ve **Şubeler** düzenleme ekranının **Sınıf listesi** ve **Bekleme listesi** sekmeleri

| Yetki | Neye izin verir |
|---|---|
| `Ders kayıtlarını ve sınıf listelerini görüntüleme` | Ders kayıtlarını, sınıf listelerini ve ders kaydı denemelerini görüntüleme |
| `Öğrenciler adına ders ekleme, bırakma ve dersten çekilme (gerekçeyle)` | Personel tarafından ders kaydı, bırakma ve dersten çekme, bekleme listesinden çıkarma ve hemen kayda alma. Bir birim kapsamıyla atandığında yalnızca o birimin açtığı şubeler |
| `Kısıtlama koyma ve kaldırma, kural istisnası tanıma` | Kısıtlamalar, kural istisnaları. Bir kapsamla atandığında yalnızca o kapsamdaki öğrenciler |
| `Ders kaydı kural setlerini ve kredi sınırlarını yönetme` | Dönem başına kural setleri, kredi sınırları |

Rol varsayılanları: Öğrenci İşleri dört yetkinin tamamına sahiptir; Akademik İşler görüntüleme ile kurallar ve kredi sınırları yetkilerine; Bölüm Sekreteri görüntüleme ve ders kaydı düzenleme yetkilerine sahiptir (bölümün kapsamıyla atanır).

## Eski sistemden farklar

- Eski sistemde (YUSTIC) kredi sınırları veya kredi bankası derslerinin hariç tutulması gibi öğrenci numarası basamaklarına ya da bölüm kodlarına sabit kodlanmış kurallar **dönem başına ayarlar** hâline geldi. Eski YUST kuralları `yust-legacy` kurulum profiliyle yeniden üretilir (aşağıda).
- Bir istek reddedildiğinde **tüm gerekçeler aynı anda gösterilir** (eski sistem ilkinde duruyordu). Her deneme ve kural başına sonuç **Ders kaydı denemeleri** içinde saklanır.
- 2014 ve sonrası öğrenci numaralı hemşirelik fakültesi öğrencileri için eski kredi sınırı hatası (21 olması gereken yerde 18 kredi uygulanıyordu) yeniden üretilmez. 21 kredi uygulanır.
- Ders kaydı sırasında ders kitabı satın alma girişi yoktur.

## Bir dönemi hazırlama

1. **İşlem aralıkları** ([bölüm 10](10-terms.md)): ders sepeti, ders kaydı (sınıf düzeyine göre kademeli açılış), ekle/bırak, dersten çekilme. Ekle/bırak ve dersten çekilme aralıkları alt dönem başına ayarlanır.
2. **Ders kaydı kuralları > Bir dönem için kuralları hazırla**: dönemi ve kopyalanacak önceki bir dönemi seçin. Varsayılanlardan başlamak için boş bırakın. Kural seti olmayan bir dönem varsayılanları kullanır.
3. **Kredi sınırları**: dönem türü ve öğrenci grubu başına sınırlar (aşağıda).
4. **Şubeler**: kontenjan, ayrılmış kontenjanlar (ayrılmış kontenjanlar ve açılma zamanları), **Bekleme listesi büyüklüğü** ([bölüm 18](18-sections-and-timetables.md)). Bir şubenin ders kaydı alabilmesi için **yayımlanmış** olması gerekir.
5. **Kısıtlamalar**: kısıtlaması olan öğrencilerin listesini girin, örn. ödenmemiş harç (CSV ile içe aktarma mümkündür, [bölüm 9](09-csv-import.md)).
6. Kontrol: `php artisan registration:preflight 2027-SP` ([işletim komutları](../operations/commands.md)). İşlem aralıklarını, kural setini, kontenjanları ve kredi sınırı olmayan öğrenci sayısını raporlar.

## Kurallar

Tüm kurallar aşağıdaki sırayla kontrol edilir. **İşlem aralığı, şube açık ve kontenjan** her zaman uygulanır ve listede yer almaz.

| Kural | Neyi kontrol eder | Parametreler |
|---|---|---|
| Ders kaydı süresi | Öğrencinin hedef ölçütlerine uyan bir işlem aralığı açık olmalıdır. Bırakma için ders kaydı aralığı veya dersin alt döneminin ekle/bırak aralığı; dersten çekilme için alt dönemin dersten çekilme aralığı gerekir | Yok |
| Kısıtlamalar | Bir kısıtlama ders kaydını engelliyorsa reddedilir | Yok |
| Öğrenci durumu | Yalnızca seçilen öğrenci durumları (varsayılan: kayıtlı) ders kaydı yapabilir | Ders kaydı yapabilecek öğrenci durumları |
| Şube uygunluğu | Şubenin uygunluk ve hariç tutma ölçütleri | Yok |
| Aynı ders iki kez | Bir ders dönem başına yalnızca bir kez | Yok |
| Saat çakışması | Saat, kayıtlı başka bir şubeyle çakışıyorsa reddedilir. Tarihleri çakışmayan dönemin ilk ve ikinci yarısı çakışmaz | İzin verilen çakışma (dakika) |
| Kredi sınırı | Kayıtlı kredilerin toplamı ≤ uygulanan kredi sınırı. "Kredi sınırına dahil" ayarı kapalı olan dersler sayılmaz | Yok (Kredi sınırları ekranında ayarlanır) |
| Ön koşullar | Ön koşulların geçilmiş olması (eşdeğer dersler sayılır, en düşük not); eş koşullar aynı dönemde kaydedilebilir | Yok (ders ekranında ayarlanır) |
| Ders tekrarı | Geçilmiş bir dersin yeniden alınıp alınamayacağı | Ders tekrarına izin verilir, ders başına en fazla deneme, yalnızca şu notun altındaysa |

Her kuralın kendi ayarları vardır.

- **Açık**: kapalıyken kural kontrol edilmez.
- **Karşılanmadığında**: Engelle veya Yalnızca uyar. Uyarıda ders kaydı gerçekleşir ve öğrenci yalnızca bir mesaj görür.
- **İstisnaya izin verilir**: kural istisnalarının ve personel ders kaydındaki "Kurallardan muaf tut" alanının etkili olması için açık olmalıdır. Kısıtlamalar ve aynı ders iki kez için varsayılan olarak kapalıdır.

Ders tekrarları otomatik olarak algılanır ve ders kaydı **Ders tekrarı** olarak işaretlenir. Ders tekrarlarında notların işlenmesi Faz 6'dadır.

## Kredi sınırları

Dönem türünü (boş: tüm dönem türleri), öğrencileri (sınıf düzeyi, giriş yılı, program, birim, akademik durum), en fazla krediyi ve önceliği belirleyin. Akademik durum, dönemin notları kapatıldığında belirlenir ([bölüm 21](21-grades.md)). Örneğin akademik uyarıdaki öğrencileri 12 krediyle sınırlamak için akademik durumda Akademik uyarı'yı seçin ve sınıra genel sınırdan daha yüksek bir öncelik verin (demo verileri bu şekilde kurulmuştur). Bir öğrenciye birden fazla sınır uyduğunda yalnızca **en yüksek önceliğe sahip olan** uygulanır. Hiçbiri uymadığında sınır yoktur (`registration:preflight` bunu raporlar).

Örnek: eski YUST kuralları (`yust-legacy` profili)

| Dönem türü | Öğrenciler | En fazla | Öncelik |
|---|---|---|---|
| 1. ve 2. dönem | 2014 veya sonrası girişli, hemşirelik fakültesi (birim kodu `NURS`) | 21 | 40 |
| 1. ve 2. dönem | 2014 veya sonrası girişli | 18 | 30 |
| 1. ve 2. dönem | 2013 veya öncesi girişli, hemşirelik fakültesi | 20 | 20 |
| 1. ve 2. dönem | 2013 veya öncesi girişli | 17 | 10 |
| Yaz ve kış | 2014 veya sonrası girişli, hemşirelik fakültesi / diğerleri | 6 / 5 | 40 / 30 |
| Yaz ve kış | 2013 veya öncesi girişli | 6 | 10 |

Kodları `009` veya `900` ile başlayan kredi bankası dersleri için dersteki "Kredi sınırına dahil" ayarını kapatın. Profil `setup:install --profile=yust-legacy` ile kurulur (Kore üniversite profilini de içerir).

## Kontenjan ve bekleme listesi

- Kontenjan, ders kaydı anında şube satırı kilitlenerek güvenceye alınır. Çok sayıda öğrenci aynı anda kayıt yapsa bile kontenjan aşılmaz.
- **Ayrılmış kontenjanlar** (örn. ana dal öğrencileri için 30 kişilik kontenjan) açılma zamanına kadar yalnızca ilgili öğrencileri tarafından kullanılır. Artan ayrılmış kontenjanlar açılma zamanında açık kontenjana dönüşür. Portal bunları "açık, ayrılmış, toplam" olarak gösterir.
- **Bekleme listesi büyüklüğü** 0'dan büyükse öğrenciler dolu bir şubede **Bekleme listesine katıl** işlemini yapabilir. Bekleme listesine katılmak için de kontenjan dışındaki tüm kuralların karşılanması gerekir.
- Bir bırakma, dersten çekilme veya ayrılmış kontenjanların serbest kalmasıyla bir yer boşaldığında bekleyen öğrenciler sırayla **otomatik olarak kayda alınır**. Kayda alınırken tüm kurallar yeniden kontrol edilir. Bu arada başka bir derse kaydolmuş ve artık kredi sınırını aşan veya saat çakışması olan bir öğrenci, gerekçe kaydedilerek bekleme listesinden çıkarılır. Öğrenci bir e-posta alır.
- Ders kaydı ve ekle/bırak aralıklarının tamamı kapandığında kalan bekleme listesi kayıtlarının süresi dolar.
- Kontenjan, zaten kayıtlı öğrenci sayısının altına düşürülemez.

Bekleme listesini yeniden sıralamanın bir yolu yoktur. Belirli bir öğrenciyi öne almak için **Öğrenci kaydet** işlemini kullanın (şube doluysa kontenjanı artırın).

## Öğrencinin ekranı

Portalda **Ders kaydı** altında öğrenci dönemi seçer, ders kodu veya ders adına göre arar ve ders kaydı yapar, sepete ekler veya bekleme listesine katılır. Ekran ders kaydı süresinin durumunu (açık, yalnızca sepet, açılış zamanı), kredi sınırına göre kayıtlı kredileri ve ders programını gösterir.

- **Sepet**: ders sepeti aralığında öğrenciler dersleri toplar ve ders kaydı açıldığında **Tümüne kaydol** düğmesine basar. Sepete ekleme kuralları önceden çalıştırır ve sorunları bildirir, ancak eklemeyi yalnızca işlem aralığı, şube açık ve aynı ders iki kez kontrolleri engeller. Ders kaydında kurallar yeniden kontrol edilir.
- Bırakma ve dersten çekilme düğmeleri işlem aralıklarına göre değişir. Dersten çekilinen bir ders geçmişte **Dersten çekildi** olarak kalır (W notu Faz 6'dadır).
- Her kişi dakikada en fazla 30 istek yapabilir.

## Ders kayıtları ve personel düzenlemeleri

**Ders kayıtları**: döneme ve duruma göre filtreleyin, öğrenci numarası veya ada göre arayın. Varsayılan olarak içinde bulunulan (veya bir sonraki) dönemin kayıtlı girişlerini gösterir.

**Öğrenci kaydet** (Ders kayıtları'nın üstünde veya şubenin **Sınıf listesi** sekmesinde): öğrenci numarasını, şubeyi, muaf tutulacak kuralları ve gerekçeyi girin.

- Öğrenciler için geçerli olan kurallarla aynı kurallar kontrol edilir. **Ders kaydı süresi personel ders kaydına uygulanmaz.**
- "Kurallardan muaf tut" yalnızca **İstisnaya izin verilir** açık olan kurallar için çalışır. Şube açık ve kontenjan seçilemez (bunun yerine kontenjanı artırın).
- Sonuç (kayıtlı, bekleme listesine alındı, gerekçelerle reddedildi) bir bildirim olarak gösterilir. Gerekçesiyle birlikte işlem günlüğüne kaydedilir.

**Bırakma ve dersten çekme**: Ders kayıtları'ndaki satırdan veya Sınıf listesi sekmesinden, gerekçeyle. İşlem aralıklarından bağımsız olarak yapılabilir. Bir yer boşaldığında bekleme listesinden kayda alma gerçekleşir.

## Kısıtlamalar ve kural istisnaları

**Kısıtlamalar** (eski ders kaydı engeli `SRDENY`): öğrenci numarası, kısıtlama türü (Dönem kaydı yapılmamış, Mali, Akademik, İdari, Diğer), etkisi (ders kaydını / not görüntülemeyi / belgeleri engeller), başlangıç ve bitiş, gerekçe. Not görüntüleme ve belge engelleri varsayılan olarak kapalıdır. Ödenmemiş harcın yanı sıra, akademik usulsüzlük soruşturması gibi notların bir süre bekletilmesi gereken idari durumlar için de kullanılırlar. Not görüntüleme engeli varken öğrenci portaldaki not ekranını veya not dökümünü göremez ([bölüm 21](21-grades.md)). Personel ekranları ve öğretim elemanlarının not girişi bundan etkilenmez. Belge engeli varken personel belge düzenleyemez ([bölüm 23](23-certificates.md)). Kısıtlamalar silinmez; gerekçeyle **kaldırılır**. Varsayılan liste yürürlükteki kısıtlamaları gösterir.

**Kural istisnaları** (eski ders kaydı izni `SRALLOW`): öğrenci, dönem, kural, uygulandığı yer (boş: tüm dönem veya belirli bir şube ya da ders), sona erme, gerekçe. İstisna varken o kural karşılanmasa bile ders kaydı gerçekleşir (kuralda İstisnaya izin verilir açık olduğu sürece). Ders kaydı süresi kuralı da bir istisnayla açılabilir (örn. aralığı kaçıran mezun olacak bir öğrenci). Şube açık ve kontenjan için istisna tanınamaz. Artık gerekmediğinde istisnayı **İptal et** ile kaldırın.

## Ders kaydı denemeleri

Her ders kaydı, bırakma, dersten çekilme, bekleme listesi, kayda alma ve sepet denemesi; zamanı, sonucu, karşılanmayan kuralları ve mesajları, işlemi yapan personel veya öğrenci ve istek kimliğiyle birlikte saklanır. "Neden kayıt yaptıramadım" sorularında öğrenci numarasına göre arayın. Öğrenci şimdi ders kaydı yapsa ne olacağı `php artisan registration:simulate {student number} {course code-section number}` ile önizlenebilir.
