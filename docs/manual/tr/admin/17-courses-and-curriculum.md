# 17. Dersler ve mezuniyet koşulları

Menü: **Müfredat ve şubeler > Dersler**, **Mezuniyet koşulları** (`Dersleri, sürümleri ve ön koşulları yönetme`, `Mezuniyet koşullarını yönetme` yetkileri)

## Dersler ve sürümler

Bir ders, değişmeyen bir **kod** ve geçerlilik dönemleri olan **sürümler**den oluşur. Ders adı, kredi, saatler, ders türü, notlandırma esası ve ön koşullar sürüme aittir. Eski sistem (YUSTIC) dersleri ders kodu ve başlangıç tarihiyle ayırıyordu, ancak notlar yalnızca ders koduna bağlıydı; bu nedenle öğrencinin hangi sürümü aldığı belirsizdi. Yeni sistemde şubeler ve notlar bir sürüme bağlıdır.

- Aynı dersin sürümlerinin dönemleri çakışamaz (veritabanı kısıtı). Herhangi bir tarihte tam olarak bir sürüm geçerlidir.
- Şube, alt dönemin başlangıç tarihinde geçerli olan sürümle açılır.

| Alan | Açıklama |
|---|---|
| Ders kodu | örn. `CSE2010`. Kod iş anlamı taşımaz (eski `009*` kredi bankası kontrolü gibi kuralların yerini aşağıdaki alanlar alır) |
| Sorumlu birim | Dersi yöneten birim. Şubelerinin varsayılan açan birimi |
| Ders adı, Açıklama | Dil başına |
| Kredi, teorik ve uygulama saatleri | 0,5'lik adımlarla |
| Ders türü | Temel veriler > Ders türleri |
| Notlandırma | Harf notu veya Başarılı/başarısız |
| Kredi sınırına dahil | Kapalıyken ders, ders kaydı kredi sınırının dışında kalır (kredi bankası dersleri vb.) |
| Ders değerlendirme | Kapalıyken ders değerlendirilmez (uygulamalar, stajlar vb.; [bölüm 22](22-course-evaluation.md)) |
| Kredi için tekrar alınabilir | Dersi yeniden almak ders tekrarı sayılmaz, yeniden kredi kazandırır (konusu değişen seminerler vb.) |

## Dersi revize etme

Kredi veya ders adı değiştiğinde sürümü düzenlemeyin; bunun yerine **revize edin**. Dersin **Düzenle** ekranında, **Sürümler** sekmesinde geçerli sürümde **Bir tarihten itibaren revize et** düğmesine tıklayın ve yeni sürümün başlangıç tarihini, değişen değerleri ve gerekçeyi girin.

- Geçerli sürüm başlangıç tarihinden bir gün önce sona erer; yeni sürüm, geçerli sürümün ve ön koşullarının bir kopyasıyla başlar.
- Önceden açılmış şubeler ve geçmiş notlar önceki sürüme bağlı kalmaya devam eder.
- Bir sürümü yalnızca anlamını değiştirmeyen düzeltmeler (yazım hataları gibi) için doğrudan düzenleyin (bunlar veri değişikliği kaydında görünür).

## Ön koşullar ve eş koşullar

Sürüm düzenleme ekranının altındaki **Ön koşullar** bölümüne girilir.

| Alan | Açıklama |
|---|---|
| Tür | Ön koşul (önce tamamlanmış olmalı) veya Eş koşul (aynı dönemde birlikte alınır; daha önce tamamlanmışsa karşılanmış sayılır) |
| Grup | Aynı gruptaki derslerden yalnızca birinin tamamlanması yeterlidir (veya). Her grup karşılanmalıdır (ve) |
| En düşük not | Varsayılan not ölçeğindeki bir not kodu (örn. `C0`). Boş: geçer not |
| Aynı dönemde alınabilir | Ön koşul dersin aynı dönemde alınabildiği durumlar için |

Örnek: grup 1 `CSE1001`, grup 2 `MAT1001` ve `MAT1002` içerir → `CSE1001` ve (`MAT1001` veya `MAT1002`). Ön koşul kuralı bunu ders kaydı sırasında kontrol eder ([bölüm 20](20-registration.md)).

## Eşdeğer dersler

Bir müfredat değişikliği bir dersin yerine başka bir ders getirdiğinde, yeni dersi geçerlilik dönemiyle birlikte dersin **Düzenle** ekranındaki **Eşdeğer dersler** sekmesine girin. **Karşılıklı** açıkken her iki ders birbirinin yerine sayılır; kapalıyken yalnızca eşdeğer ders bu dersin yerine sayılır. Ders tekrarı kontrolünde ([bölüm 20](20-registration.md)) ve mezuniyet denetiminde (Faz 8) kullanılır.

## Mezuniyet koşulları

Her program, **Program türü** (ana program, çift ana dal, yan dal) ve **müfredat yılı** aralığı için bir koşul seti oluşturulur. Öğrenci, öğrenci kaydının müfredat yılına uyan sete tabidir.

- Aynı program ve program türünün müfredat yılları çakışamaz. Setin bir sonraki set başlayana kadar geçerli kalması için bitiş yılını boş bırakın.
- Koşul türleri

| Tür | Girdi | Örnek |
|---|---|---|
| Bir ders türünde kredi | Ders türü, en az kredi | 9 kredi genel eğitim (zorunlu) |
| Zorunlu dersler | Ders listesi | 4 zorunlu alan dersinin tamamı |
| Derslerden seçim | Ders listesi, en az kredi veya en az ders sayısı | Alan seçmeli derslerinden 12 kredi |
| Geçilmiş sınav | Sınav türü | İngilizce yeterlik sınavını geçmiş olmak ([bölüm 24](24-assessments.md)) |

Çift ana dallar ve yan dallar, ana programdan daha küçük kendi setlerine sahiptir (örn. çift ana dal için 36 kredi, yan dal için 21 kredi). Koşullar mezuniyet denetimiyle (Faz 8) kontrol edilir.
