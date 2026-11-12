# Denetim bütünlüğü ve rutin bakım

## Denetim karma zinciri

Denetim tabloları (veri değişiklikleri için `audit.record_changes`, iş işlemleri için `audit.activity_log`, güvenlik olayları için `audit.security_events`) yalnızca ekleme yapılabilen tablolardır (tetikleyicilerle zorunlu kılınır). Bunun üzerine, kurcalamayı **ortaya çıkaran** bir mekanizma bulunur.

- Her satıra, eklendiği anda içeriğinin SHA-256 değeri (`row_hash`) verilir.
- `audit:seal` (her gün 03:30'da zamanlanmıştır) yeni satırları ID sırasına göre zincire bağlar: `chain_hash = SHA-256(önceki chain_hash || row_hash)`.
- `audit:verify-chain`, her satır için içerik karmasını ve zinciri yeniden hesaplar. İçeriği değişmiş bir satır "row mismatch", eksik ya da sırası değişmiş bir satır "chain mismatch" olarak raporlanır ve komut 2 çıkış koduyla döner.

Dikkat edilmesi gerekenler

- Mühürlenmeden önce (aynı gün içinde) değiştirilen bir satır zincir tarafından yakalanamaz. Bu nedenle mühürleme her gün çalışır; doğrulama ise yedeklemelerden önce ve güvenlik incelemeleri sırasında yapılır.
- Temizleme (`audit:prune`) en eski satırları sildiğinde, kalan ilk satırın bağlantısı kontrol edilmez (silinen aralık, temizleme komutunun denetim kaydında saklanır).
- `app.audit_maintenance = on` ayarını yalnızca DB yöneticisi (şema sahibi) yapabilir. Uygulama rolünün UPDATE veya DELETE yetkisi yoktur.

## Saklama ve temizleme

1. Saklama sürelerini belirleyin (örneğin veri değişiklikleri 3 yıl, iş işlemleri 5 yıl, güvenlik olayları 1 yıl).
2. Temizlemeden önce yedek alın ([Yedekleme ve geri yükleme](backup-restore.md)) ve `audit:verify-chain` ile zincirin sağlam olduğunu doğrulayın.
3. Sayıları `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run` ile kontrol edin, ardından `--dry-run` olmadan çalıştırın. Mühürlenmemiş satırlar hiçbir zaman silinmez; bu nedenle önce `audit:seal` çalıştırın.
4. Temizlemeden sonra `audit:verify-chain` komutunu yeniden çalıştırın.

Temizleme işleminin kendisi de kaydedilir: her çalıştırma, işlem günlüğüne (günlük `command`) kesim tarihini, seçenekleri, işlemi yapan kişiyi ve işletim sistemi kullanıcısını, ayrıca tablo başına **silinen satır sayısını, silinen ID aralığını ve sınırdaki chain_hash değerini** yazar; gerçek bir silme ayrıca `audit.pruned` güvenlik olayı olarak kaydedilir (**Denetim > Güvenlik olayları**). Bu kayıt satırı da bir sonraki mühürlemede zincire bağlanır. `audit:verify-chain` komutunun kalan ilk satırın bağlantısını kontrol etmemesinin nedeni tam da bu sınırdır; sınır karmasını temizleme kaydıyla karşılaştırarak silinen aralığın kayıtlı bir temizlemeyle eşleştiğini doğrulayabilirsiniz.

## Dosya temizliği

`media:prune-orphans --dry-run`, sahibi artık bulunmayan medya satırlarını ve satırı olmayan dizinleri listeler; `--dry-run` olmadan bunları siler. Kişi fotoğraflarını, başvuru evraklarını, belge PDF'lerini ve sınav kanıtlarını kapsar. Ayda bir veya toplu bir silme işleminden sonra çalıştırın.

## Sistem sağlığı kontrolü

`system:health-check`; yapılandırmayı ve anahtarları, DB rollerini ve RLS'yi, migration'ları, yetki kataloğunu, Redis'i ve kuyruğu (Horizon), Gotenberg'i, depolamayı ve zamanlayıcı nabzını (her dakika önbelleğe yazılır) kontrol eder ve bir sorun olduğunda 2 çıkış koduyla döner. İzleme sistemine bağlamak için `--json` kullanın.
